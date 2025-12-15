<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagsmith\service;

use Flagsmith\Flagsmith;
use Flagsmith\Models\BaseFlag;
use Flagsmith\Models\Flags;
use OpenFeature\implementation\provider\ResolutionDetailsBuilder;
use OpenFeature\implementation\provider\ResolutionError;
use OpenFeature\interfaces\provider\ErrorCode;
use OpenFeature\interfaces\provider\ResolutionDetails;
use Psr\Log\LoggerInterface;
use Throwable;

use function floatval;
use function gettype;
use function intval;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_numeric;
use function is_object;
use function is_scalar;
use function is_string;
use function json_decode;
use function json_encode;
use function json_last_error;
use function json_last_error_msg;
use function strtolower;

use const JSON_ERROR_NONE;

class FlagEvaluator
{
    private Flagsmith $flagsmithClient;
    public ?LoggerInterface $logger = null;

    public function __construct(Flagsmith $flagsmithClient)
    {
        $this->flagsmithClient = $flagsmithClient;
    }

    /**
     * Evaluates a boolean flag.
     *
     * @param string $flagKey The flag key to evaluate
     * @param bool $defaultValue The default value if evaluation fails
     * @param string|null $identifier The user identifier for targeting
     * @param object|null $traits The user traits for targeting
     */
    public function evaluateBoolean(
        string $flagKey,
        bool $defaultValue,
        ?string $identifier,
        ?object $traits,
    ): ResolutionDetails {
        try {
            // Get flags and determine base reason
            $result = $this->getFlagsAndBaseReason($identifier, $traits);
            $flags = $result['flags'];
            $baseReason = $result['baseReason'];

            // Get the flag object
            $flag = $flags->getFlag($flagKey);

            // Treat default flags as not found
            if ($flag->getIsDefault()) {
                $this->logger?->warning("Flag '{$flagKey}' was not found");

                return (new ResolutionDetailsBuilder())
                    ->withValue($defaultValue)
                    ->withError(new ResolutionError(
                        ErrorCode::FLAG_NOT_FOUND(),
                        "Flag '{$flagKey}' was not found",
                    ))
                    ->withReason('ERROR')
                    ->build();
            }

            // Special boolean handling: if no boolean value, return enabled state
            /** @var mixed $flagValue */
            $flagValue = $flag->getValue();
            $boolValue = $this->tryParseBoolean($flagValue);
            if ($boolValue === null) {
                // No valid boolean value, fall back to enabled state
                $boolValue = $flag->getEnabled();
            }

            // Determine final reason based on flag state
            $reason = $this->determineReason($flag, $baseReason);

            // Build and return resolution details
            return (new ResolutionDetailsBuilder())
                ->withValue($boolValue)
                ->withReason($reason)
                ->build();
        } catch (Throwable $e) {
            $this->logger?->error("Error evaluating boolean flag '{$flagKey}': {$e->getMessage()}");

            return (new ResolutionDetailsBuilder())
                ->withValue($defaultValue)
                ->withError(new ResolutionError(ErrorCode::GENERAL(), $e->getMessage()))
                ->withReason('ERROR')
                ->build();
        }
    }

    /**
     * Evaluates a string flag.
     *
     * @param string $flagKey The flag key to evaluate
     * @param string $defaultValue The default value if evaluation fails
     * @param string|null $identifier The user identifier for targeting
     * @param object|null $traits The user traits for targeting
     */
    public function evaluateString(
        string $flagKey,
        string $defaultValue,
        ?string $identifier,
        ?object $traits,
    ): ResolutionDetails {
        try {
            // Get flags and determine base reason
            $result = $this->getFlagsAndBaseReason($identifier, $traits);
            $flags = $result['flags'];
            $baseReason = $result['baseReason'];

            // Get the flag object
            $flag = $flags->getFlag($flagKey);

            // Treat default flags as not found
            if ($flag->getIsDefault()) {
                $this->logger?->warning("Flag '{$flagKey}' was not found");

                return (new ResolutionDetailsBuilder())
                    ->withValue($defaultValue)
                    ->withError(new ResolutionError(
                        ErrorCode::FLAG_NOT_FOUND(),
                        "Flag '{$flagKey}' was not found",
                    ))
                    ->withReason('ERROR')
                    ->build();
            }

            // Get flag value and convert to string
            /** @var mixed $flagValue */
            $flagValue = $flag->getValue();

            // Only scalar values can be converted to string
            if (!is_scalar($flagValue) && $flagValue !== null) {
                $this->logger?->warning("Type mismatch for flag '{$flagKey}': expected string but received " . gettype($flagValue));

                return (new ResolutionDetailsBuilder())
                    ->withValue($defaultValue)
                    ->withError(new ResolutionError(
                        ErrorCode::TYPE_MISMATCH(),
                        'Expected string but received ' . gettype($flagValue),
                    ))
                    ->withReason('ERROR')
                    ->build();
            }

            // Handle null and empty string explicitly
            $stringValue = $flagValue === null ? '' : (string) $flagValue;

            // Determine final reason based on flag state
            $reason = $this->determineReason($flag, $baseReason);

            // Build and return resolution details
            return (new ResolutionDetailsBuilder())
                ->withValue($stringValue)
                ->withReason($reason)
                ->build();
        } catch (Throwable $e) {
            $this->logger?->error("Error evaluating string flag '{$flagKey}': {$e->getMessage()}");

            return (new ResolutionDetailsBuilder())
                ->withValue($defaultValue)
                ->withError(new ResolutionError(ErrorCode::GENERAL(), $e->getMessage()))
                ->withReason('ERROR')
                ->build();
        }
    }

    /**
     * Evaluates an integer flag.
     *
     * @param string $flagKey The flag key to evaluate
     * @param int $defaultValue The default value if evaluation fails
     * @param string|null $identifier The user identifier for targeting
     * @param object|null $traits The user traits for targeting
     */
    public function evaluateInteger(
        string $flagKey,
        int $defaultValue,
        ?string $identifier,
        ?object $traits,
    ): ResolutionDetails {
        try {
            // Get flags and determine base reason
            $result = $this->getFlagsAndBaseReason($identifier, $traits);
            $flags = $result['flags'];
            $baseReason = $result['baseReason'];

            // Get the flag object
            $flag = $flags->getFlag($flagKey);

            // Treat default flags as not found
            if ($flag->getIsDefault()) {
                $this->logger?->warning("Flag '{$flagKey}' was not found");

                return (new ResolutionDetailsBuilder())
                    ->withValue($defaultValue)
                    ->withError(new ResolutionError(
                        ErrorCode::FLAG_NOT_FOUND(),
                        "Flag '{$flagKey}' was not found",
                    ))
                    ->withReason('ERROR')
                    ->build();
            }

            // Try to parse flag value as integer
            /** @var mixed $flagValue */
            $flagValue = $flag->getValue();
            $intValue = $this->tryParseInt($flagValue);

            if ($intValue === null) {
                $this->logger?->warning("Type mismatch for flag '{$flagKey}': expected integer but received " . gettype($flagValue));

                return (new ResolutionDetailsBuilder())
                    ->withValue($defaultValue)
                    ->withError(new ResolutionError(
                        ErrorCode::TYPE_MISMATCH(),
                        'Expected integer but received ' . gettype($flagValue),
                    ))
                    ->withReason('ERROR')
                    ->build();
            }

            // Determine final reason based on flag state
            $reason = $this->determineReason($flag, $baseReason);

            // Build and return resolution details
            return (new ResolutionDetailsBuilder())
                ->withValue($intValue)
                ->withReason($reason)
                ->build();
        } catch (Throwable $e) {
            $this->logger?->error("Error evaluating integer flag '{$flagKey}': {$e->getMessage()}");

            return (new ResolutionDetailsBuilder())
                ->withValue($defaultValue)
                ->withError(new ResolutionError(ErrorCode::GENERAL(), $e->getMessage()))
                ->withReason('ERROR')
                ->build();
        }
    }

    /**
     * Evaluates a float flag.
     *
     * @param string $flagKey The flag key to evaluate
     * @param float $defaultValue The default value if evaluation fails
     * @param string|null $identifier The user identifier for targeting
     * @param object|null $traits The user traits for targeting
     */
    public function evaluateFloat(
        string $flagKey,
        float $defaultValue,
        ?string $identifier,
        ?object $traits,
    ): ResolutionDetails {
        try {
            // Get flags and determine base reason
            $result = $this->getFlagsAndBaseReason($identifier, $traits);
            $flags = $result['flags'];
            $baseReason = $result['baseReason'];

            // Get the flag object
            $flag = $flags->getFlag($flagKey);

            // Treat default flags as not found
            if ($flag->getIsDefault()) {
                $this->logger?->warning("Flag '{$flagKey}' was not found");

                return (new ResolutionDetailsBuilder())
                    ->withValue($defaultValue)
                    ->withError(new ResolutionError(
                        ErrorCode::FLAG_NOT_FOUND(),
                        "Flag '{$flagKey}' was not found",
                    ))
                    ->withReason('ERROR')
                    ->build();
            }

            // Try to parse flag value as float
            /** @var mixed $flagValue */
            $flagValue = $flag->getValue();
            $floatValue = $this->tryParseFloat($flagValue);

            if ($floatValue === null) {
                $this->logger?->warning("Type mismatch for flag '{$flagKey}': expected float but received " . gettype($flagValue));

                return (new ResolutionDetailsBuilder())
                    ->withValue($defaultValue)
                    ->withError(new ResolutionError(
                        ErrorCode::TYPE_MISMATCH(),
                        'Expected float but received ' . gettype($flagValue),
                    ))
                    ->withReason('ERROR')
                    ->build();
            }

            // Determine final reason based on flag state
            $reason = $this->determineReason($flag, $baseReason);

            // Build and return resolution details
            return (new ResolutionDetailsBuilder())
                ->withValue($floatValue)
                ->withReason($reason)
                ->build();
        } catch (Throwable $e) {
            $this->logger?->error("Error evaluating float flag '{$flagKey}': {$e->getMessage()}");

            return (new ResolutionDetailsBuilder())
                ->withValue($defaultValue)
                ->withError(new ResolutionError(ErrorCode::GENERAL(), $e->getMessage()))
                ->withReason('ERROR')
                ->build();
        }
    }

    /**
     * Evaluates an object flag.
     *
     * @param string $flagKey The flag key to evaluate
     * @param array<mixed> $defaultValue The default value if evaluation fails
     * @param string|null $identifier The user identifier for targeting
     * @param object|null $traits The user traits for targeting
     */
    public function evaluateObject(
        string $flagKey,
        array $defaultValue,
        ?string $identifier,
        ?object $traits,
    ): ResolutionDetails {
        try {
            // Get flags and determine base reason
            $result = $this->getFlagsAndBaseReason($identifier, $traits);
            $flags = $result['flags'];
            $baseReason = $result['baseReason'];

            // Get the flag object
            $flag = $flags->getFlag($flagKey);

            // Treat default flags as not found
            if ($flag->getIsDefault()) {
                $this->logger?->warning("Flag '{$flagKey}' was not found");

                return (new ResolutionDetailsBuilder())
                    ->withValue($defaultValue)
                    ->withError(new ResolutionError(
                        ErrorCode::FLAG_NOT_FOUND(),
                        "Flag '{$flagKey}' was not found",
                    ))
                    ->withReason('ERROR')
                    ->build();
            }

            // Try to parse flag value as object/array
            /** @var mixed $flagValue */
            $flagValue = $flag->getValue();
            $arrayValue = $this->tryParseObject($flagValue);

            if ($arrayValue === null) {
                if (is_string($flagValue)) {
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $this->logger?->warning("Parse error for flag '{$flagKey}': " . json_last_error_msg());

                        return (new ResolutionDetailsBuilder())
                            ->withValue($defaultValue)
                            ->withError(new ResolutionError(
                                ErrorCode::PARSE_ERROR(),
                                'Failed to parse JSON: ' . json_last_error_msg(),
                            ))
                            ->withReason('ERROR')
                            ->build();
                    }

                    $this->logger?->warning("Type mismatch for flag '{$flagKey}': expected object but received scalar JSON value");

                    return (new ResolutionDetailsBuilder())
                        ->withValue($defaultValue)
                        ->withError(new ResolutionError(
                            ErrorCode::TYPE_MISMATCH(),
                            'Expected object but received scalar JSON value',
                        ))
                        ->withReason('ERROR')
                        ->build();
                }

                $this->logger?->warning("Type mismatch for flag '{$flagKey}': expected object but received " . gettype($flagValue));

                return (new ResolutionDetailsBuilder())
                    ->withValue($defaultValue)
                    ->withError(new ResolutionError(
                        ErrorCode::TYPE_MISMATCH(),
                        'Expected object but received ' . gettype($flagValue),
                    ))
                    ->withReason('ERROR')
                    ->build();
            }

            // Determine final reason based on flag state
            $reason = $this->determineReason($flag, $baseReason);

            // Build and return resolution details
            return (new ResolutionDetailsBuilder())
                ->withValue($arrayValue)
                ->withReason($reason)
                ->build();
        } catch (Throwable $e) {
            $this->logger?->error("Error evaluating object flag '{$flagKey}': {$e->getMessage()}");

            return (new ResolutionDetailsBuilder())
                ->withValue($defaultValue)
                ->withError(new ResolutionError(ErrorCode::GENERAL(), $e->getMessage()))
                ->withReason('ERROR')
                ->build();
        }
    }

    /**
     * Get flags and determine the base reason code based on whether targeting is used.
     *
     * @return array{flags: Flags, baseReason: string}
     */
    private function getFlagsAndBaseReason(?string $identifier, ?object $traits): array
    {
        if ($identifier !== null) {
            return [
                'flags' => $this->flagsmithClient->getIdentityFlags($identifier, $traits),
                'baseReason' => 'TARGETING_MATCH',
            ];
        }

        return [
            'flags' => $this->flagsmithClient->getEnvironmentFlags(),
            'baseReason' => 'STATIC',
        ];
    }

    /**
     * Determine the appropriate reason code based on flag state.
     *
     * @param string $baseReason The base reason (STATIC or TARGETING_MATCH)
     *
     * @return string The final reason code
     */
    private function determineReason(BaseFlag $flag, string $baseReason): string
    {
        if (!$flag->getEnabled()) {
            return 'DISABLED';
        }

        return $baseReason;
    }

    /**
     * Try to parse a value as an integer.
     * Accepts integers and numeric strings using PHP's native parsing.
     */
    private function tryParseInt(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && filter_var($value, FILTER_VALIDATE_INT) !== false) {
            return intval($value);
        }

        return null;
    }

    /**
     * Try to parse a value as a float.
     * Accepts floats, integers, and numeric strings using PHP's native parsing.
     */
    private function tryParseFloat(mixed $value): ?float
    {
        if (is_float($value)) {
            return $value;
        }
        if (is_int($value)) {
            return (float) $value;
        }
        if (is_string($value) && is_numeric($value)) {
            return floatval($value);
        }

        return null;
    }

    /**
     * Try to parse a value as a boolean.
     * Accepts booleans and the strings "true"/"false" (case-insensitive, no whitespace trimming).
     */
    private function tryParseBoolean(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_string($value)) {
            $lower = strtolower($value);
            if ($lower === 'true') {
                return true;
            }
            if ($lower === 'false') {
                return false;
            }
        }

        return null;
    }

    /**
     * Try to parse a value as an object (array in PHP).
     * Accepts arrays, objects, and JSON strings.
     *
     * @return array<mixed>|null
     */
    private function tryParseObject(mixed $value): ?array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_object($value)) {
            $json = json_encode($value);
            if ($json === false) {
                return null; // Circular reference or encoding error
            }

            /** @var mixed $decoded */
            $decoded = json_decode($json, true);

            return is_array($decoded) ? $decoded : null;
        }
        if (is_string($value)) {
            /** @var mixed $parsed */
            $parsed = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
                return $parsed;
            }
        }

        return null;
    }
}
