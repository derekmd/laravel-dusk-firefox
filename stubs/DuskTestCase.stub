<?php

namespace Tests;

use Derekmd\Dusk\Firefox\SupportsFirefox;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Firefox\FirefoxDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Laravel\Dusk\TestCase as BaseTestCase;

abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication, SupportsFirefox;

    /**
     * Prepare for Dusk test execution.
     *
     * @beforeClass
     * @return void
     */
    public static function prepare()
    {
        if (env('DUSK_CHROME')) {
            static::startChromeDriver();
        } else {
            static::startFirefoxDriver();
        }
    }

    /**
     * Create the RemoteWebDriver instance.
     *
     * @return \Facebook\WebDriver\Remote\RemoteWebDriver
     */
    protected function driver()
    {
        if (env('DUSK_CHROME')) {
            return $this->chromeDriver();
        }

        return $this->firefoxDriver();
    }

    /**
     * Create the ChromeDriver instance.
     *
     * @return \Facebook\WebDriver\Remote\RemoteWebDriver
     */
    protected function chromeDriver()
    {
        $options = (new ChromeOptions)->addArguments([
            '--disable-gpu',
            '--headless',
            '--window-size=1920,1080',
        ]);

        return RemoteWebDriver::create(
            'http://localhost:9515', DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }

    /**
     * Create the Geckodriver instance.
     *
     * @return \Facebook\WebDriver\Remote\RemoteWebDriver
     */
    protected function firefoxDriver()
    {
        $options = [
            'args' => [
                '--headless',
                '--window-size=1920,1080',
            ],
        ];

        $capabilities = DesiredCapabilities::firefox()
            ->setCapability('moz:firefoxOptions', $options);

        $capabilities->getCapability(FirefoxDriver::PROFILE)
            ->setPreference('devtools.console.stdout.content', true);

        return RemoteWebDriver::create('http://localhost:4444', $capabilities);
    }
}
