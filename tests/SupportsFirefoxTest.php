<?php

namespace Derekmd\Dusk\Tests;

use Derekmd\Dusk\Firefox\SupportsFirefox;
use PHPUnit\Framework\TestCase;

class SupportsFirefoxTest extends TestCase
{
    use SupportsFirefox;

    public function test_it_can_run_firefox_process()
    {
        $process = static::buildFirefoxProcess(['-v']);

        $process->start();

        // Wait for the process to start up, and output any issues
        sleep(2);

        $process->stop();

        $this->assertStringContainsString('geckodriver', $process->getOutput());
        $this->assertSame('', $process->getErrorOutput());
    }
}
