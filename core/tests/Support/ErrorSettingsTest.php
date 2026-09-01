<?php

declare(strict_types=1);

namespace Ava\Tests\Support;

use Ava\Support\ErrorSettings;
use Ava\Testing\TestCase;

final class ErrorSettingsTest extends TestCase
{
    public function testLoggingDefaultsToEnabledOutsideDebugMode(): void
    {
        $settings = ErrorSettings::resolve(['enabled' => false]);

        $this->assertTrue($settings['log_errors']);
        $this->assertFalse($settings['display_errors']);
    }

    public function testLoggingCanBeExplicitlyDisabled(): void
    {
        $settings = ErrorSettings::resolve([
            'enabled' => false,
            'log_errors' => false,
        ]);

        $this->assertFalse($settings['log_errors']);
    }

    public function testDisplayErrorsRequiresDebugMode(): void
    {
        $production = ErrorSettings::resolve([
            'enabled' => false,
            'display_errors' => true,
        ]);
        $debug = ErrorSettings::resolve([
            'enabled' => true,
            'display_errors' => true,
        ]);

        $this->assertFalse($production['display_errors']);
        $this->assertTrue($debug['display_errors']);
    }

    public function testConfiguredErrorLevelIsPreserved(): void
    {
        $settings = ErrorSettings::resolve(['level' => 'all']);

        $this->assertEquals('all', $settings['level']);
    }

    public function testInvalidErrorLevelTypeUsesDefault(): void
    {
        $settings = ErrorSettings::resolve(['level' => ['all']]);

        $this->assertEquals('errors', $settings['level']);
    }
}
