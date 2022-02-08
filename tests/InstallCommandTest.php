<?php

namespace Derekmd\Dusk\Tests;

use Derekmd\Dusk\DuskServiceProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Orchestra\Testbench\TestCase;

class InstallCommandTest extends TestCase
{
    private $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = new Support\TempDirectory(__DIR__.'/tmp');

        // Don't fully run child command 'dusk:firefox-driver'.
        // This makes the command fail early, leaving downloaded binaries alone.
        $handlerStack = HandlerStack::create(new MockHandler([
            new Response(404, [], 'Resource not found.'),
        ]));
        $http = new Client(['handler' => $handlerStack]);
        $this->app->instance(Client::class, $http);
    }

    protected function tearDown(): void
    {
        $this->tempDir->delete();
        $this->tempDir = null;

        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return [
            DuskServiceProvider::class,
        ];
    }

    public function test_it_can_copy_test_stub()
    {
        $this->artisan('dusk:install-firefox', ['--output' => $this->tempDir->path()])
            ->expectsOutput('Firefox scaffolding installed successfully.')
            ->expectsOutput('Downloading Geckodriver binaries...')
            ->assertExitCode(0);

        $this->assertFileExists($this->tempDir->path('DuskTestCase.php'));
        $this->assertStringContainsString('static::startFirefoxDriver();', file_get_contents($this->tempDir->path('DuskTestCase.php')));
    }

    public function test_it_can_overwrite_existing_test_class_when_confirmed()
    {
        file_put_contents($this->tempDir->path('DuskTestCase.php'), 'foo');

        $this->artisan('dusk:install-firefox', [
            '--output' => $this->tempDir->path(),
            '--with-chrome' => true,
        ])
            ->expectsConfirmation('Overwrite file '.$this->tempDir->path('DuskTestCase.php?'), 'yes')
            ->expectsOutput('Firefox scaffolding installed successfully.')
            ->expectsOutput('Downloading Geckodriver binaries...')
            ->assertExitCode(0);

        $this->assertFileExists($this->tempDir->path('DuskTestCase.php'));
        $this->assertStringContainsString('static::startChromeDriver();', file_get_contents($this->tempDir->path('DuskTestCase.php')));
    }

    public function test_it_will_not_overwrite_test_class_when_declined()
    {
        file_put_contents($this->tempDir->path('DuskTestCase.php'), 'foo');

        $this->artisan('dusk:install-firefox', ['--output' => $this->tempDir->path()])
            ->expectsConfirmation('Overwrite file '.$this->tempDir->path('DuskTestCase.php?'), 'no')
            ->expectsOutput('Firefox scaffolding not installed.')
            ->assertExitCode(0);

        $this->assertStringEqualsFile($this->tempDir->path('DuskTestCase.php'), 'foo');
    }

    public function test_it_can_overwrite_test_class_unprompted()
    {
        file_put_contents($this->tempDir->path('DuskTestCase.php'), 'foo');

        $this->artisan('dusk:install-firefox', [
            '--output' => $this->tempDir->path(),
            '--with-chrome' => true,
            '--force' => true,
        ])
            ->expectsOutput('Firefox scaffolding installed successfully.')
            ->expectsOutput('Downloading Geckodriver binaries...')
            ->assertExitCode(0);

        $this->assertFileExists($this->tempDir->path('DuskTestCase.php'));
        $this->assertStringContainsString('static::startChromeDriver();', file_get_contents($this->tempDir->path('DuskTestCase.php')));
    }
}
