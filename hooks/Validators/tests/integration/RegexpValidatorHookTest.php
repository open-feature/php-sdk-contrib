<?php

declare(strict_types=1);

namespace OpenFeature\Hooks\Validators\Test\integration;

use OpenFeature\Hooks\Validators\Regexp\RegexpValidatorHook;
use OpenFeature\Hooks\Validators\Test\TestCase;
use OpenFeature\interfaces\hooks\Hook;

class RegexpValidatorHookTest extends TestCase
{
    public function testCanCreateRegexpHook(): void
    {
        $hook = new RegexpValidatorHook('/^[a-z]+$/');

        $this->assertInstanceOf(Hook::class, $hook);
    }
}
