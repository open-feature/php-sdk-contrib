<?php

declare(strict_types=1);

namespace OpenFeature\Hooks\Validators\Regexp;

use OpenFeature\Hooks\Validators\Exceptions\ValidationException;
use OpenFeature\interfaces\flags\EvaluationContext;
use OpenFeature\interfaces\flags\FlagValueType;
use OpenFeature\interfaces\hooks\Hook;
use OpenFeature\interfaces\hooks\HookContext;
use OpenFeature\interfaces\hooks\HookHints;
use OpenFeature\interfaces\provider\ResolutionDetails;
use Throwable;

use function is_int;
use function preg_match;

class RegexpValidatorHook implements Hook
{
    private string $regexp;

    public function __construct(string $regexp)
    {
        $this->regexp = self::validateRegexp($regexp);
    }

    public function before(HookContext $context, HookHints $hints): ?EvaluationContext
    {
        return null;
    }

    public function after(HookContext $context, ResolutionDetails $details, HookHints $hints): void
    {
      /** @var string $resolvedValue */
        $resolvedValue = $details->getValue();

        if ($this->testResolvedValue($resolvedValue)) {
            return;
        }

        throw new ValidationException();
    }

    public function error(HookContext $context, Throwable $error, HookHints $hints): void
    {
      // no-op
    }

    public function finally(HookContext $context, HookHints $hints): void
    {
      // no-op
    }

    public function supportsFlagValueType(string $flagValueType): bool
    {
        return $flagValueType === FlagValueType::STRING;
    }

    private function testResolvedValue(string $resolvedValue): bool
    {
        return preg_match($this->regexp, $resolvedValue) === 1;
    }

    private static function validateRegexp(string $regexp): string
    {
        if (self::isValidRegexp($regexp)) {
            return $regexp;
        }

        throw new InvalidRegularExpressionException($regexp);
    }

    private static function isValidRegexp(string $regexp): bool
    {
        return is_int(@preg_match($regexp, ''));
    }
}
