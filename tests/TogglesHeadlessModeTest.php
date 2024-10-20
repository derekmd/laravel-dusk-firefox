<?php

namespace Derekmd\Dusk\Tests;

use Derekmd\Dusk\Tests\Stubs\TogglesHeadlessModeStub;
use PHPUnit\Framework\TestCase;

class TogglesHeadlessModeTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_ENV['DUSK_HEADLESS_DISABLED']);

        parent::tearDown();
    }

    public function test_building_arguments_with_headless_mode()
    {
        unset($_ENV['DUSK_HEADLESS_DISABLED']);

        $args = (new TogglesHeadlessModeStub)->filterHeadlessArguments([
            '--disable-gpu',
            '--headless',
            '--window-size=1920,1080',
        ]);

        $this->assertSame([
            '--disable-gpu',
            '--headless',
            '--window-size=1920,1080',
        ], $args);
    }

    public function test_building_arguments_when_headless_mode_is_disabled()
    {
        $_ENV['DUSK_HEADLESS_DISABLED'] = true;

        $args = (new TogglesHeadlessModeStub)->filterHeadlessArguments([
            '--disable-gpu',
            '--headless',
            '--window-size=1920,1080',
        ]);

        $this->assertSame(['--window-size=1920,1080'], $args);
    }
}
