<?php

namespace Derekmd\Dusk\Firefox;

use Derekmd\Dusk\OperatingSystem;
use RuntimeException;
use Symfony\Component\Process\Process;

class FirefoxProcess
{
    /**
     * The path to the Geckodriver.
     *
     * @var string|null
     */
    protected $driver;

    /**
     * Create a new FirefoxProcess instance.
     *
     * @param  string|null  $driver
     * @return void
     */
    public function __construct($driver = null)
    {
        $this->driver = $driver;
    }

    /**
     * Build the process to run Geckodriver.
     *
     * @param  array  $arguments
     * @return \Symfony\Component\Process\Process
     *
     * @throws \RuntimeException
     */
    public function toProcess(array $arguments = [])
    {
        if ($this->driver) {
            $driver = $this->driver;
        } else {
            $driver = __DIR__.'/../../bin/'.[
                'linux' => 'geckodriver-linux',
                'mac' => 'geckodriver-mac',
                'mac-arm' => 'geckodriver-mac-arm',
                'win' => 'geckodriver-win.exe',
            ][$this->operatingSystemId()];
        }

        $this->driver = realpath($driver);

        if ($this->driver === false) {
            throw new RuntimeException(
                'Invalid path to Geckodriver ['.$driver.']. '.
                'Make sure to install the Geckodriver first by running the dusk:firefox-driver command.'
            );
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

    /**
     * Determine OS ID.
     *
     * @return string
     */
    protected function operatingSystemId()
    {
        return OperatingSystem::geckodriverId();
    }
}
