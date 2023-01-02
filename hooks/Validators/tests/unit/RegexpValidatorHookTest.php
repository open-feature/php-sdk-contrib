<?php

declare(strict_types=1);

namespace OpenFeature\Hooks\Validators\Test\integration;

use OpenFeature\Hooks\Validators\Exceptions\ValidationException;
use OpenFeature\Hooks\Validators\Regexp\InvalidRegularExpressionException;
use OpenFeature\Hooks\Validators\Regexp\RegexpValidatorHook;
use OpenFeature\Hooks\Validators\Test\TestCase;
use OpenFeature\implementation\common\Metadata;
use OpenFeature\implementation\hooks\HookContextFactory;
use OpenFeature\implementation\hooks\HookHints;
use OpenFeature\implementation\provider\ResolutionDetailsFactory;
use OpenFeature\interfaces\flags\FlagValueType;
use OpenFeature\interfaces\hooks\Hook;

class RegexpValidatorHookTest extends TestCase
{
    public function testCanCreateValidRegexpForHook(): void
    {
        $hook = new RegexpValidatorHook('/^[a-z]+$/');

        $this->assertInstanceOf(Hook::class, $hook);
    }

    public function testCannotCreateInvalidRegexpForHook(): void
    {
        $this->expectException(InvalidRegularExpressionException::class);

        new RegexpValidatorHook('/[\\]/');
    }

    public function testAlphanumericRegexpHookPasses(): void
    {
        $hook = new RegexpValidatorHook('/^[A-Za-z0-9]+$/');

        $this->assertInstanceOf(Hook::class, $hook);

        $this->executeHook($hook, 'Abc123');
    }

    public function testAlphanumericRegexpHookFails(): void
    {
        $hook = new RegexpValidatorHook('/^[A-Za-z0-9]+$/');

        $this->assertInstanceOf(Hook::class, $hook);

        $this->expectException(ValidationException::class);

        $this->executeHook($hook, 'This, a sentence, has other invalid characters.');
    }

    public function testHexadecimalRegexpHookPasses(): void
    {
        $hook = new RegexpValidatorHook('/^[0-9a-f]+$/');

        $this->assertInstanceOf(Hook::class, $hook);

        $this->executeHook($hook, 'deadbeef007');
    }

    public function testHexadecimalRegexpHookFails(): void
    {
        $hook = new RegexpValidatorHook('/^[0-9a-f]+$/');

        $this->assertInstanceOf(Hook::class, $hook);

        $this->expectException(ValidationException::class);

        $this->executeHook($hook, '0123456789abcdefg');
    }

    public function testAsciiRegexpHookPasses(): void
    {
        $hook = new RegexpValidatorHook('/^[ -~]+$/');

        $this->assertInstanceOf(Hook::class, $hook);

        $this->executeHook($hook, 'Only ASCII characters get used here: See?');
    }

    public function testAsciiRegexpHookFails(): void
    {
        $hook = new RegexpValidatorHook('/^[ -~]+$/');

        $this->assertInstanceOf(Hook::class, $hook);

        $this->expectException(ValidationException::class);

        $this->executeHook($hook, '死');
    }

    private function executeHook(Hook $hook, string $resolvedValue): void
    {
        $ctx = HookContextFactory::from(
            'any-key',
            FlagValueType::STRING,
            'default-value',
            null,
            new Metadata('client'),
            new Metadata('provider'),
        );

        $details = ResolutionDetailsFactory::fromSuccess($resolvedValue);

        $nullHints = new HookHints();

        $hook->after($ctx, $details, $nullHints);
    }
}
