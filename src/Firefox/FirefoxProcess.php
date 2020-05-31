<?php

namespace Derekmd\Dusk\Firefox;

use Laravel\Dusk\OperatingSystem;
use RuntimeException;
use Symfony\Component\Process\Process;

class FirefoxProcess
{
    /**
     * The path to the Geckodriver.
     *
     * @var string
     */
    protected $driver;

    /**
     * Create a new FirefoxProcess instance.
     *
     * @param  string  $driver
     * @return void
     *
     * @throws \RuntimeException
     */
    public function __construct($driver = null)
    {
        $this->driver = $driver;

        if (! is_null($driver) && realpath($driver) === false) {
            throw new RuntimeException("Invalid path to Geckodriver [{$driver}].");
        }
    }

    /**
     * Build the process to run Geckodriver.
     *
     * @param  array  $arguments
     * @return \Symfony\Component\Process\Process
     */
    public function toProcess(array $arguments = [])
    {
        if ($this->driver) {
            return $this->process($arguments);
        }

        if ($this->onWindows()) {
            $this->driver = realpath(__DIR__.'/../../bin/geckodriver-win.exe');
        } elseif ($this->onMac()) {
            $this->driver = realpath(__DIR__.'/../../bin/geckodriver-mac');
        } else {
            $this->driver = realpath(__DIR__.'/../../bin/geckodriver-linux');
        }

        return $this->process($arguments);
    }

    /**
     * Build the Geckodriver with Symfony Process.
     *
     * @param  array  $arguments
     * @return \Symfony\Component\Process\Process
     */
    protected function process(array $arguments = [])
    {
        return new Process(
            array_merge([realpath($this->driver)], $arguments), null, $this->geckoEnvironment()
        );
    }

    /**
     * Get the Geckodriver environment variables.
     *
     * @return array
     */
    protected function geckoEnvironment()
    {
        if ($this->onMac() || $this->onWindows()) {
            return [];
        }

        return ['DISPLAY' => $_ENV['DISPLAY'] ?? ':0'];
    }

    /**
     * Determine if Dusk is running on Windows or Windows Subsystem for Linux.
     *
     * @return bool
     */
    protected function onWindows()
    {
        return OperatingSystem::onWindows();
    }

    /**
     * Determine if Dusk is running on Mac.
     *
     * @return bool
     */
    protected function onMac()
    {
        return OperatingSystem::onMac();
    }
}
