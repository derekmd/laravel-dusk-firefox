<?php

namespace Derekmd\Dusk\Tests;

use Derekmd\Dusk\Firefox\StdoutLog;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class StdoutLogTest extends TestCase
{
    public function test_it_removes_geckodriver_debugging_output()
    {
        $process = m::mock(Process::class);
        $process->shouldReceive('getOutput')
            ->once()
            ->andReturn(file_get_contents(__DIR__.'/fixtures/geckodriver-stdout.log'));

        $log = new StdoutLog($process);

        $this->assertEquals("console.error: \"foo\"\n", $log->getOutput());
    }
}
