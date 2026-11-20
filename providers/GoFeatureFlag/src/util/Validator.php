<?php

declare(strict_types=1);

namespace OpenFeature\Providers\GoFeatureFlag\util;

use JsonException;
use OpenFeature\Providers\GoFeatureFlag\config\Config;
use OpenFeature\Providers\GoFeatureFlag\exception\InvalidConfigException;
use OpenFeature\Providers\GoFeatureFlag\exception\ParseException;

use function array_diff;
use function array_key_exists;
use function array_keys;
use function count;
use function filter_var;
use function get_debug_type;
use function implode;
use function is_array;
use function is_scalar;
use function is_string;
use function json_decode;
use function key_exists;

use const FILTER_VALIDATE_URL;
use const JSON_THROW_ON_ERROR;

class Validator
{
    /**
     * @param ?Config $config - The configuration object to validate
     *
     * @throws InvalidConfigException - if the config is invalid we return an error
     */
    public static function validateConfig(?Config $config): void
    {
        if ($config === null) {
            throw new InvalidConfigException('Config is null');
        }
        self::validateEndpoint($config->getEndpoint());
    }

    /**
     * @param string $endpoint - The endpoint to validate
     *
     * @throws InvalidConfigException
     */
    private static function validateEndpoint(string $endpoint): void
    {
        if (!filter_var($endpoint, FILTER_VALIDATE_URL)) {
            throw new InvalidConfigException('Invalid endpoint URL: ' . $endpoint);
        }
    }

    /**
     * @param string $response - The API response to validate, as a JSON string
     *
     * @return array<array-key, mixed>
     *
     * @throws ParseException
     */
    public static function validateSuccessApiResponse(string $response): array
    {
        try {
            $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $jex) {
            throw new ParseException('Cannot parse the response', previous: $jex);
        }

        if (!is_array($data)) {
            $dataType = get_debug_type($data);

            throw new ParseException("Expected a valid JSON object, received {$dataType}.");
        }

        // Check for any missing required keys
        $requiredKeys = ['key', 'value', 'reason', 'variant'];
        $missingKeys = array_diff($requiredKeys, array_keys($data));
        if (count($missingKeys) > 0) {
            throw new ParseException(
                'missing keys in the success response: ' . implode(', ', $missingKeys),
            );
        }

        if (!is_string($data['key'])) {
            throw new ParseException('key is not a string');
        }

        if (!is_string($data['variant'])) {
            throw new ParseException('variant is not a string');
        }

        if (!is_string($data['reason'])) {
            throw new ParseException('reason is not a string');
        }

        $result = [
            'key' => $data['key'],
            'value' => $data['value'],
            'reason' => $data['reason'],
            'variant' => $data['variant'],
        ];

        if (array_key_exists('metadata', $data) && is_array($data['metadata'])) {
            $result['metadata'] = $data['metadata'];
        }

        return $result;
    }

    /**
     * @param mixed $data - The data to validate
     *
     * @return array<array-key, mixed>
     *
     * @throws ParseException
     *
     * @phpstan-return array{errorCode: string, ...}
     */
    public static function validateErrorApiResponse(mixed $data): array
    {
        if (!is_array($data)) {
            throw new ParseException('invalid json object, expected associative array');
        }

        $requiredKeys = ['key', 'errorCode'];
        $missingKeys = array_diff($requiredKeys, array_keys($data));
        if (count($missingKeys) > 0) {
            throw new ParseException(
                'missing keys in the error response: ' . implode(', ', $missingKeys),
            );
        }

        if (!is_string($data['errorCode'])) {
            throw new ParseException('errorCode is not a string', null);
        }

        if (key_exists('errorDetails', $data) && !is_string($data['errorDetails'])) {
            throw new ParseException('errorDetails is not a string', null);
        }

        return $data;
    }

    /**
     * @param string $flagKey - The flag key to validate
     *
     * @throws InvalidConfigException
     */
    public static function validateFlagKey(string $flagKey): void
    {
        if ($flagKey === '') {
            throw new InvalidConfigException('Flag key is null or empty');
        }
    }

    /**
     * Sanitize the flag metadata, if any, to be compliant with https://openfeature.dev/specification/types#flag-metadata
     *
     * @param mixed $metadata - The metadata to validate
     *
     * @return array<string,bool|float|int|string>|null
     */
    public static function sanitizeFlagMetadata(mixed $metadata): ?array
    {
        if (!is_array($metadata)) {
            // We short-circuit by returning null, since only associative arrays can be treated as valid metadata containers
            return null;
        }

        /** @var array<string,bool|float|int|string>|null $sanitized */
        $sanitized = [];
        /** @psalm-suppress MixedAssignment */
        foreach ($metadata as $key => $value) {
            if (is_scalar($value)) {
                $sanitized[(string) $key] = $value;
            }
        }

        return $sanitized;
    }
}
