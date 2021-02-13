<?php

namespace Derekmd\Dusk\Firefox;

use Laravel\Dusk\Browser;

trait SupportsFirefox
{
    /**
     * The path to the custom Geckodriver binary.
     *
     * @var string|null
     */
    protected static $firefoxDriver;

    /**
     * The Geckodriver process instance.
     *
     * @var \Symfony\Component\Process\Process
     */
    protected static $firefoxProcess;

    /**
     * The logger for the Geckodriver process instance.
     *
     * @var \Derekmd\Dusk\Firefox\StdoutLog
     */
    protected static $firefoxLog;

    /**
     * @after
     */
    public function tearDownFirefoxConsoleOutput()
    {
        if (static::$firefoxLog) {
            $this->storeConsoleLog(
                $this->getCallerName(), static::$firefoxLog->getOutput()
            );

            static::$firefoxLog = null;
        }
    }

    /**
     * Store the console output with the given name.
     *
     * @param  string  $name
     * @param  string  $console
     */
    protected function storeConsoleLog($name, $console)
    {
        if (! empty($console)) {
            file_put_contents(
                sprintf('%s/%s.log', rtrim(Browser::$storeConsoleLogAt, '/'), $name),
                $console
            );
        }
    }

    /**
     * Start the Geckodriver process.
     *
     * @param  array  $arguments
     * @return void
     *
     * @throws \RuntimeException
     */
    public static function startFirefoxDriver(array $arguments = [])
    {
        static::$firefoxProcess = static::buildFirefoxProcess($arguments);

        static::$firefoxLog = new StdoutLog(static::$firefoxProcess);

        static::$firefoxProcess->start();

        static::afterClass(function () {
            static::stopFirefoxDriver();
        });
    }

    /**
     * Stop the Geckodriver process.
     *
     * @return void
     */
    public static function stopFirefoxDriver()
    {
        if (static::$firefoxProcess) {
            static::$firefoxProcess->stop();
        }
    }

    /**
     * Build the process to run the Geckodriver.
     *
     * @param  array  $arguments
     * @return \Symfony\Component\Process\Process
     *
     * @throws \RuntimeException
     */
    protected static function buildFirefoxProcess(array $arguments = [])
    {
        return (new FirefoxProcess(static::$firefoxDriver))->toProcess($arguments);
    }

    /**
     * Set the path to the custom Geckodriver.
     *
     * @param  string  $path
     * @return void
     */
    public static function useGeckodriver($path)
    {
        static::$firefoxDriver = $path;
    }

    /**
     * Determine if the tests are running within Laravel Sail.
     *
     * @return bool
     */
    protected static function runningFirefoxInSail()
    {
        return isset($_ENV['LARAVEL_SAIL']) && $_ENV['LARAVEL_SAIL'] == '1';
    }
}
