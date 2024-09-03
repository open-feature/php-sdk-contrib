<?php

declare(strict_types=1);

namespace OpenFeature\Providers\GoFeatureFlag\model;

use DateTime;
use OpenFeature\Providers\GoFeatureFlag\exception\ParseException;
use OpenFeature\Providers\GoFeatureFlag\util\Mapper;

use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_null;
use function is_string;

class OfrepApiSuccessResponse
{
    /**
     * @var array<mixed>|array<string, mixed>|bool|DateTime|float|int|string|null
     */
    private array | bool | DateTime | float | int | string | null $value;
    private string $reason;
    private string $variant;

    // TODO: Commenting Metadata here because it is not supported by the SDK yet.
    // private array $metadata;

    /**
     * @param array<string, mixed> $apiData
     *
     * @throws ParseException
     */
    public function __construct(
        array $apiData,
    ) {
        if (
            is_null($apiData['value'])
            || is_array($apiData['value'])
            || is_bool($apiData['value'])
            || $apiData['value'] instanceof DateTime
            || is_float($apiData['value'])
            || is_int($apiData['value'])
            || is_string($apiData['value'])
        ) {
            $this->value = $apiData['value'];
        } else {
            throw new ParseException('Invalid type for value');
        }

        $this->variant = is_string($apiData['variant']) ? $apiData['variant'] : 'error in provider';
        $this->reason = Mapper::reason(is_string($apiData['reason']) ? $apiData['reason'] : '');
        // $this->metadata = $apiData['metadata'] ?? [];
    }

    /**
     * @return array<mixed>|array<string, mixed>|bool|DateTime|float|int|string|null
     */
    public function getValue(): array | bool | DateTime | float | int | string | null
    {
        return $this->value;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getVariant(): string
    {
        return $this->variant;
    }

    //    /**
    //     * @return array<string, mixed>
    //     */
    //    public function getMetadata(): array
    //    {
    //        return $this->metadata;
    //    }
}
