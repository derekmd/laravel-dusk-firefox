# Geckodriver support for Laravel Dusk

This package will make [Laravel Dusk](https://github.com/laravel/dusk/) browser tests run in Mozilla Firefox. Instead of using Chromedriver, Laravel application tests are sent to Firefox's [Geckodriver](https://github.com/mozilla/geckodriver) proxy server.

* [Features](#features)
* [Requirements](#requirements)
* [Installation](#installation)
* [Updating Geckodriver](#updating-geckodriver)
* [Configuring Geckodriver](#configuring-geckodriver)
* [Running both Firefox & Google Chrome](#running-firefox-and-chrome)
  * [Selecting desired browser in local environment](#selecting-browser-in-local)
  * [Selecting desired browser in continuous integration](#selecting-browser-in-ci)
* [Running with Laravel Sail](#running-with-laravel-sail)
  * Developing only with Laraval Sail
  * Mixing other development environments with Laravel Sail
* [Development](#development)
* [FAQ](#faq)
* [Contributing](#contributing)
* [Credits](#credits)
* [License](#license)

<a name="features"></a>
## Features

1. Downloads the latest stable Geckodriver binary for your operating system.
2. Handles automating startup and shutdown of the Geckodriver proxy server process.
3. Captures Firefox browser screenshots when tests fail.
4. Generates a debugging log file when JavaScript `console` errors occur.

<a name="requirements"></a>
## Requirements

* PHP 7.2+
* Laravel Framework 6.0+
* Laravel Dusk 6.0+
* Mozilla Firefox browser installed locally

<a name="installation"></a>
## Installation

First ensure Laravel Dusk command `php artisan dusk:install` has been run. This will copy files into your application and generate required subdirectories.

```
composer require --dev derekmd/laravel-dusk-firefox
php artisan dusk:install-firefox
```

This will overwrite file `tests/DuskTestCase.php` in your application to support running Firefox. Your browser test suite will now open in Firefox rather than Google Chrome:

```
php artisan dusk
```

<a name="updating-geckodriver"></a>
## Updating Geckodriver

To download the latest stable Geckodriver binary for your current operating system:

```
php artisan dusk:firefox-driver
```

Use the `--all` option to install all three binaries for Linux, macOS, and Windows.

```
php artisan dusk:firefox-driver --all
```

If you wish to download [older binaries](https://github.com/mozilla/geckodriver/releases), pass the GitHub release tag version as the first command line argument. Keep in mind Geckodriver's versioning schema does not relate to Firefox's release version.

```
php artisan dusk:firefox-driver v0.19.1
```

The command can also download the binaries through a local proxy server:

```
php artisan dusk:firefox-driver --proxy=tcp://127.0.0.1:9000 --ssl-no-verify
```

<a name="configuring-geckodriver"></a>
## Configuring Geckodriver

After `tests/DuskTestCase.php` is copied into your application, you may update the class as you please.

```php
namespace Tests;

use Derekmd\Dusk\Concerns\TogglesHeadlessMode;
use Derekmd\Dusk\Firefox\SupportsFirefox;
use Facebook\WebDriver\Firefox\FirefoxOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Laravel\Dusk\TestCase as BaseTestCase;

abstract class DuskTestCase extends BaseTestCase
{
    // ...

    protected function driver()
    {
        $capabilities = DesiredCapabilities::firefox();

        $capabilities->getCapability(FirefoxOptions::CAPABILITY)
            ->addArguments($this->filterHeadlessArguments([
                '--headless',
                '--window-size=1920,1080',
            ]))
            ->setPreference('devtools.console.stdout.content', true);

        return RemoteWebDriver::create('http://localhost:4444', $capabilities);
    }
}
```

* Firefox profile boolean flag `devtools.console.stdout.content` must be turned on to generate logs for debugging JavaScript errors.
* `--headless` runs tests without opening any windows which is useful for continuous integrations. Remove this option to see the browser viewport while the test runs.
* `--window-size` controls the width and height of the browser viewport. If your UI assertions are failing from elements being off-screen, you may need to change this setting.
* The `$this->filterHeadlessArguments()` call allows the `--headless` argument to be removed when the command `php artisan dusk --browse` is run during local dev. This allows the Firefox browser window to be displayed while Laravel Dusk tests run. Headless mode is still enabled when the command line argument `--browse` isn't used.

Read the [Geckodriver usage documentation](https://firefox-source-docs.mozilla.org/testing/geckodriver/index.html) to see which options are available.

<a name="running-firefox-and-chrome"></a>
## Running both Firefox & Google Chrome

You may wish to run tests in both Google Chrome and Firefox to check for feature parity in your application. This package supports a `--with-chrome` option to generate `tests/DuskTestCase.php` so it may run in both browsers.

```
php artisan dusk:install-firefox --with-chrome
```

<a name="selecting-browser-in-local"></a>
#### Selecting desired browser in local environment

The Laravel Dusk command will default to running tests in Firefox:

```
php artisan dusk
```

A new command has been added to make tests run in Google Chrome:

```
php artisan dusk:chrome
```

You may also pass PHPUnit arguments to the command:

```
php artisan dusk:chrome tests/Browser/HomepageTest.php --filter testFooter
```

This command will append environment variable `DUSK_CHROME=1` to your .env.dusk file and remove it after tests complete.

> If Laravel Dusk crashes or you have cancelled the test suite process using CTRL+C, you may need to manually remove leftover line `DUSK_CHROME=1` from your .env.dusk file.

<a name="selecting-browser-in-ci"></a>
#### Selecting desired browser in continuous integration

When running automated test flows through tools such as Chipper CI, CircleCI, Travis CI, or Github Actions, you can setup one job to run Google Chrome and a second job for Firefox. The custom Artisan commands can be skipped and you can instead just set the environment variable. The job configured with `DUSK_CHROME=1` will run Google Chrome. The second job missing the environment variable defaults to Firefox.

<a name="running-with-laravel-sail"></a>
## Running with Laravel Sail

Laravel Sail is a command-line interface for interacting with your Laravel application's Docker development environment. Laravel Dusk tests can run in Firefox once an additional Docker image is added to Sail's configuration file.

You must uncomment and edit the "selenium" service in the `docker-compose.yml` file to install [standalone Firefox for Selenium](https://github.com/SeleniumHQ/docker-selenium).

```
version: '3'
services:
    laravel.test:
        # ....
        depends_on:
            - mysql
            - redis
            - selenium
    selenium:
        image: 'selenium/standalone-firefox'
        volumes:
            - '/dev/shm:/dev/shm'
        networks:
            - sail
```

### Developing only with Laraval Sail

**You may not require this package** if you exclusively use Laravel Sail for development.

Over 90% of this package's solution is focused on managing a local Geckodriver process through PHPUnit's event hooks. Laravel Sail replaces Chromedriver/Geckodriver with a Selenium server so the only custom code you'll require in your application is a WebDriver configuration for Firefox. [Copy this driver() method](https://github.com/derekmd/laravel-dusk-firefox/blob/58d10303c580b120d0f70cb4a202d188c89a192b/stubs/FirefoxDuskTestCase.stub#L37-L52) into your application's `tests/DuskTestCase.php` file. Then use the above `docker-compose.yml` instructions to install Docker image "selenium/standalone-firefox".

### Mixing other development environments with Laravel Sail

For projects that have a team of developers across many environments (local native development, Laravel Valet, Laravel Homestead, Laravel Sail) or use a Docker-less continuous integration, this package will allow Laravel Dusk to run Firefox in any of those environments.

Install the package using the `sail` commands:

```
./vendor/bin/sail composer require --dev derekmd/laravel-dusk-firefox
./vendor/bin/sail artisan dusk:install-firefox
```

This will copy a `tests/DuskTestCase.php` file into your application that is configured to recognize Laravel Sail's environment variables. When Sail isn't installed, Laravel Dusk will behave as normal.

Run Laravel Dusk tests in Firefox by executing the command:

```
./vendor/bin/sail dusk
```

Other developers not using Laravel Sail can execute the usual Dusk command:

```
php artisan dusk
```

> This configuration only allows running Dusk test with Mozilla Firefox. To make the command `php artisan dusk:chrome` work with a "selenium/standalone-chrome" image, additional service and `sail.sh` file changes are required that fall outside the 80% use case of Laravel Sail.

<a name="development"></a>
## Development

To run the test suite, Geckodriver binaries for each 64-bit operating systems will need to be downloaded:

```
composer download
```

or call the PHP script. Yes, using PHPUnit to run an Artisan command is a hack. Package development!

```
./vendor/phpunit/phpunit/phpunit tests/DownloadBinaries.php
```

Run the tests in the command line through Composer:

```
composer test
```

or call PHPUnit directly:

```
./vendor/phpunit/phpunit/phpunit
```

<a name="faq"></a>
## FAQ

1. How do I fix error "Failed to connect to localhost port 4444: Connection refused"?

   By default Geckodriver runs locally on port 4444. The process may have failed to start.

   * Run `php artisan dusk:firefox-driver` to ensure the Geckodriver binary is downloaded.
   * The Geckodriver proxy server may already be running which can happen after a crash. Kill the conflicting process ("End Task" in Windows) and try running `php artisan dusk` again.
   * If another service is using port 4444, open `tests/DuskTestCase.php` and change the `driver()` method to configure another port number.
2. My test suite that passed 100% using Chromedriver now fails in Firefox. How do I fix my tests?
   
   You may find Firefox is more temperamental when calling Laravel Dusk method `visit()` or navigating between web pages. For HTTP redirects and form submissions, you may wish to avoid first calling methods `assertPathIs()` `assertPortIsNot()` or `assertPathBeginsWith()`. [Waiting for elements](https://laravel.com/docs/7.x/dusk#waiting-for-elements) methods such as the `waitForText()` method are the best fit to delay the test until the next web page has finished loading. When all else fails, add trial-and-error `pause(milliseconds)` calls to make the test determinstic in all environments.
3. Can you help me get my tests running in Firefox?
   
   Sorry, no. That would be outside of the scope of support for this package. You can try [Laravel community support channels](https://laravel.com/docs/master/contributions#support-questions) such as the https://laracasts.com/ and https://laravel.io/ forums.
4. Why doesn't the saved browser error log show scripting warnings, such as a .js file failing to load due to CORS (cross-origin resource sharing) restrictions?
   
   Chromedriver implements Selenium's `commands.GetLog` endpoint which provides a wider range of testing feedback. Unfortunately this endpoint is not currently part of the [W3C WebDriver API](https://developer.mozilla.org/en-US/docs/Web/WebDriver) so Geckodriver does not support it.
   
   This limitation is the reason Firefox support isn't built into the official Laravel Dusk package.

<a name="contributing"></a>
## Contributing

When submitting a pull request:

1. Write new PHP code and docblock in the same style as the Laravel ecoystem. #NoTimeForTypehints
   * Running command `./vendor/friendsofphp/php-cs-fixer/php-cs-fixer fix` will auto-correct the code styling.
2. Add cases to the test suite to ensure code coverage is never reduced.
3. Please do not try to support more browsers stubs beyond Chrome & Firefox.

I'll complete a contribution guide when this package warrants it. However I expect this codebase to have a small footprint given its narrow focus.

<a name="credits"></a>
## Credits

* [Derek MacDonald](https://github.com/derekmd)
* [Jonas Staudenmeir](https://github.com/staudenmeir), PHP class `FirefoxDriverCommand` is based on proxy downloads in his Laravel Dusk third-party package "staudenmeir/dusk-updater".
* [All contributors](https://github.com/derekmd/laravel-dusk-firefox/contributors)

<a name="license"></a>
## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
