<?php

declare(strict_types=1);

namespace OpenFeature\Providers\GoFeatureFlag\util;

use OpenFeature\Providers\GoFeatureFlag\config\Config;
use OpenFeature\Providers\GoFeatureFlag\exception\InvalidConfigException;
use OpenFeature\Providers\GoFeatureFlag\exception\ParseException;

use function array_diff;
use function array_keys;
use function count;
use function filter_var;
use function implode;
use function is_array;
use function is_string;
use function key_exists;

use const FILTER_VALIDATE_URL;

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
     * @param mixed $data - The data to validate
     *
     * @return array{key: string, reason: string, variant: string}
     *
     * @throws ParseException
     */
    public static function validateSuccessApiResponse(mixed $data): array
    {
        if (!is_array($data)) {
            throw new ParseException('invalid json object, expected associative array');
        }

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

        if (key_exists('metadata', $data) && !is_array($data['metadata'])) {
            throw new ParseException('metadata is not an array');
        }

        return $data;
    }

    /**
     * @param mixed $data - The data to validate
     *
     * @return array{errorCode: string}
     *
     * @throws ParseException
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
            throw new ParseException('key is not a string', null);
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
}
