<?php

namespace Derekmd\Dusk\Tests;

use Derekmd\Dusk\Firefox\FirefoxProcess;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Process\Process;

class FirefoxProcessTest extends TestCase
{
    public function test_build_process_with_custom_driver()
    {
        $driver = __DIR__;

        $process = (new FirefoxProcess($driver))->toProcess();

        $this->assertInstanceOf(Process::class, $process);
        $this->assertStringContainsString("$driver", $process->getCommandLine());
    }

    public function test_build_process_for_windows()
    {
        $process = (new FirefoxProcessWindows)->toProcess();

        $this->assertInstanceOf(Process::class, $process);
        $this->assertStringContainsString('geckodriver-win.exe', $process->getCommandLine());
    }

    public function test_build_process_for_darwin()
    {
        $process = (new FirefoxProcessDarwin)->toProcess();

        $this->assertInstanceOf(Process::class, $process);
        $this->assertStringContainsString('geckodriver-mac', $process->getCommandLine());
    }

    public function test_build_process_for_linux()
    {
        $process = (new FirefoxProcessLinux)->toProcess();

        $this->assertInstanceOf(Process::class, $process);
        $this->assertStringContainsString('geckodriver-linux', $process->getCommandLine());
    }

    public function test_invalid_path()
    {
        $this->expectException(RuntimeException::class);

        (new FirefoxProcess('/not/a/valid/path'))->toProcess();
    }
}

class FirefoxProcessWindows extends FirefoxProcess
{
    protected function onWindows()
    {
        return true;
    }
}

class FirefoxProcessDarwin extends FirefoxProcess
{
    protected function onMac()
    {
        return true;
    }

    protected function onWindows()
    {
        return false;
    }
}

class FirefoxProcessLinux extends FirefoxProcess
{
    protected function onMac()
    {
        return false;
    }

    protected function onWindows()
    {
        return false;
    }
}
