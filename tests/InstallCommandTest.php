<?php

namespace Derekmd\Dusk\Tests;

use Derekmd\Dusk\DuskServiceProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Orchestra\Testbench\TestCase;

class InstallCommandTest extends TestCase
{
    private $filesystem;
    private $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = __DIR__.'/tmp';
        $this->filesystem = new Filesystem(new Local($this->tempDir));

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
        $filesystem = new Filesystem(new Local(dirname($this->tempDir)));
        $filesystem->deleteDir(basename($this->tempDir));

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
        $this->artisan('dusk:install-firefox', ['--output' => $this->tempDir])
            ->expectsOutput('Firefox scaffolding installed successfully.')
            ->expectsOutput('Downloading Geckodriver binaries...')
            ->assertExitCode(0);

        $this->assertFileExists($this->tempDir.'/DuskTestCase.php');
        $this->assertStringContainsString('static::startFirefoxDriver();', file_get_contents($this->tempDir.'/DuskTestCase.php'));
    }

    public function test_it_can_overwrite_existing_test_class_when_confirmed()
    {
        file_put_contents($this->tempDir.'/DuskTestCase.php', 'foo');

        $this->artisan('dusk:install-firefox', [
            '--output' => $this->tempDir,
            '--with-chrome' => true,
        ])
            ->expectsConfirmation('Overwrite file '.$this->tempDir.'/DuskTestCase.php?', 'yes')
            ->expectsOutput('Firefox scaffolding installed successfully.')
            ->expectsOutput('Downloading Geckodriver binaries...')
            ->assertExitCode(0);

        $this->assertFileExists($this->tempDir.'/DuskTestCase.php');
        $this->assertStringContainsString('static::startChromeDriver();', file_get_contents($this->tempDir.'/DuskTestCase.php'));
    }

    public function test_it_will_not_overwrite_test_class_when_declined()
    {
        file_put_contents($this->tempDir.'/DuskTestCase.php', 'foo');

        $this->artisan('dusk:install-firefox', ['--output' => $this->tempDir])
            ->expectsConfirmation('Overwrite file '.$this->tempDir.'/DuskTestCase.php?', 'no')
            ->expectsOutput('Firefox scaffolding not installed.')
            ->assertExitCode(0);

        $this->assertStringEqualsFile($this->tempDir.'/DuskTestCase.php', 'foo');
    }

    public function test_it_can_overwrite_test_class_unprompted()
    {
        file_put_contents($this->tempDir.'/DuskTestCase.php', 'foo');

        $this->artisan('dusk:install-firefox', [
            '--output' => $this->tempDir,
            '--with-chrome' => true,
            '--force' => true,
        ])
            ->expectsOutput('Firefox scaffolding installed successfully.')
            ->expectsOutput('Downloading Geckodriver binaries...')
            ->assertExitCode(0);

        $this->assertFileExists($this->tempDir.'/DuskTestCase.php');
        $this->assertStringContainsString('static::startChromeDriver();', file_get_contents($this->tempDir.'/DuskTestCase.php'));
    }
}
