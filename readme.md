# Geckodriver support for Laravel Dusk

This package will make [Laravel Dusk](https://github.com/laravel/dusk/) browser tests run in Firefox. Instead of using Chromedriver, Laravel application tests are sent to Firefox's [Geckodriver](https://github.com/mozilla/geckodriver) proxy server.

* [Features](#features)
* [Requirements](#requirements)
* [Installation](#installation)
* [Updating Geckodriver](#updating-geckodriver)
* [Configuring Geckodriver](#configuring-geckodriver)
* [Running both Firefox & Google Chrome](#running-firefox-and-chrome)
  * [Selecting desired browser in local environment](#selecting-browser-in-local)
  * [Selecting desired browser in continuous integration](#selecting-browser-in-ci)
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

> Currently, Laravel developers using Geckodriver in Windows environments must also install [Microsoft Visual Studio redistributable runtime](https://support.microsoft.com/en-us/help/2977003/the-latest-supported-visual-c-downloads) separately. Geckodriver's release notes from Oct 2019 indicate this manual dependency will eventually be fixed.

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

use Facebook\WebDriver\Firefox\FirefoxDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Laravel\Dusk\TestCase as BaseTestCase;

abstract class DuskTestCase extends BaseTestCase
{
    // ...

    protected function driver()
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
```

* Firefox profile boolean flag `devtools.console.stdout.content` must be turned on to generate logs for debugging JavaScript errors.
* `--headless` runs tests without opening any windows which is useful for continuous integrations. Remove this option to see the browser viewport while the test runs.
* `--window-size` controls the width and height of the browser viewport. If your UI assertions are failing from elements being off-screen, you may need to change this setting.

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
