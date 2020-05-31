<?php

namespace Derekmd\Dusk\Tests;

use Derekmd\Dusk\DuskServiceProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Mockery as m;
use Orchestra\Testbench\TestCase;
use Psr\Http\Message\ResponseInterface;

class FirefoxDriverCommandTest extends TestCase
{
    protected $archiveFilename;
    protected $binaryFilename;
    private $filesystem;
    private $tempDir;

    protected function getEnvironmentSetUp($app)
    {
        $this->archiveFilename = $this->archiveFilename();
        $this->binaryFilename = $this->binaryFilename();
        $this->tempDir = __DIR__.'/tmp';
        $this->filesystem = new Filesystem(new Local($this->tempDir));
    }

    protected function tearDown(): void
    {
        $filesystem = new Filesystem(new Local(dirname($this->tempDir)));
        $filesystem->deleteDir(basename($this->tempDir));

        $this->archiveFilename = null;
        $this->binaryFilename = null;
        $this->tempDir = null;
        $this->filesystem = null;

        m::close();

        parent::tearDown();
    }

    protected function archiveFilename()
    {
        switch (PHP_OS_FAMILY) {
            case 'Windows':
                return 'geckodriver-v0.26.0-win64.zip';
            case 'Darwin':
                return 'geckodriver-v0.26.0-macos.tar.gz';
            default:
                return 'geckodriver-v0.26.0-linux64.tar.gz';
        }
    }

    protected function binaryFilename()
    {
        switch (PHP_OS_FAMILY) {
            case 'Windows':
                return 'geckodriver-win.exe';
            case 'Darwin':
                return 'geckodriver-mac';
            default:
                return 'geckodriver-linux';
        }
    }

    protected function getPackageProviders($app)
    {
        return [
            DuskServiceProvider::class,
        ];
    }

    public function test_it_can_download_and_uncompress_latest_geckodriver_for_current_os()
    {
        $this->app->instance(Client::class, $http = m::mock(Client::class . '[request]'));

        $http->shouldReceive('request')
            ->with('GET', 'https://api.github.com/repos/mozilla/geckodriver/releases/latest', [])
            ->andReturn($versionResponse = m::mock(ResponseInterface::class));
        $versionResponse->shouldReceive('getBody')
            ->andReturn(file_get_contents(__DIR__.'/fixtures/geckodriver-latest.json'));

        $http->shouldReceive('request')->with(
            'GET',
            'https://github.com/mozilla/geckodriver/releases/download/v0.26.0/'.$this->archiveFilename,
            ['sink' => $this->tempDir.'/'.$this->archiveFilename]
        )->andReturnUsing(function () {
            copy(__DIR__.'/fixtures/'.$this->archiveFilename, $this->tempDir.'/'.$this->archiveFilename);
        });

        $this->artisan('dusk:firefox-driver', ['--output' => $this->tempDir])
            ->expectsOutput('Geckodriver binary successfully installed for version v0.26.0.')
            ->assertExitCode(0);

        $this->assertFileDoesNotExist($this->tempDir.'/'.$this->archiveFilename);
        $this->assertStringEqualsFile($this->tempDir.'/'.$this->binaryFilename, 'foo');
    }

    public function test_it_can_download_through_proxy_without_ssl()
    {
        $this->app->instance(Client::class, $http = m::mock(Client::class . '[request]'));

        $http->shouldReceive('request')->with(
            'GET',
            'https://api.github.com/repos/mozilla/geckodriver/releases/latest',
            [
                'proxy' => 'tcp://127.0.0.1:9000',
                'verify' => false,
            ]
        )->andReturn($versionResponse = m::mock(ResponseInterface::class));
        $versionResponse->shouldReceive('getBody')
            ->andReturn(file_get_contents(__DIR__.'/fixtures/geckodriver-latest.json'));

        $http->shouldReceive('request')->with(
            'GET',
            'https://github.com/mozilla/geckodriver/releases/download/v0.26.0/'.$this->archiveFilename,
            [
                'sink' => $this->tempDir.'/'.$this->archiveFilename,
                'proxy' => 'tcp://127.0.0.1:9000',
                'verify' => false,
            ]
        )->andReturnUsing(function () {
            copy(__DIR__.'/fixtures/'.$this->archiveFilename, $this->tempDir.'/'.$this->archiveFilename);
        });

        $this->artisan('dusk:firefox-driver', [
            '--output' => $this->tempDir,
            '--proxy' => 'tcp://127.0.0.1:9000',
            '--ssl-no-verify' => true,
        ])
            ->expectsOutput('Geckodriver binary successfully installed for version v0.26.0.')
            ->assertExitCode(0);

        $this->assertFileDoesNotExist($this->tempDir.'/'.$this->archiveFilename);
        $this->assertStringEqualsFile($this->tempDir.'/'.$this->binaryFilename, 'foo');
    }

    public function test_it_can_download_geckodriver_for_linux_mac_and_windows()
    {
        $this->app->instance(Client::class, $http = m::mock(Client::class . '[request]'));

        $http->shouldReceive('request')
            ->with('GET', 'https://api.github.com/repos/mozilla/geckodriver/releases/latest', [])
            ->andReturn($versionResponse = m::mock(ResponseInterface::class));
        $versionResponse->shouldReceive('getBody')
            ->andReturn(file_get_contents(__DIR__.'/fixtures/geckodriver-latest.json'));

        $http->shouldReceive('request')->with(
            'GET',
            'https://github.com/mozilla/geckodriver/releases/download/v0.26.0/geckodriver-v0.26.0-win64.zip',
            ['sink' => $this->tempDir.'/geckodriver-v0.26.0-win64.zip']
        )->andReturnUsing(function () {
            copy(__DIR__.'/fixtures/geckodriver-v0.26.0-win64.zip', $this->tempDir.'/geckodriver-v0.26.0-win64.zip');
        });

        $http->shouldReceive('request')->with(
            'GET',
            'https://github.com/mozilla/geckodriver/releases/download/v0.26.0/geckodriver-v0.26.0-macos.tar.gz',
            ['sink' => $this->tempDir.'/geckodriver-v0.26.0-macos.tar.gz']
        )->andReturnUsing(function () {
            copy(__DIR__.'/fixtures/geckodriver-v0.26.0-macos.tar.gz', $this->tempDir.'/geckodriver-v0.26.0-macos.tar.gz');
        });

        $http->shouldReceive('request')->with(
            'GET',
            'https://github.com/mozilla/geckodriver/releases/download/v0.26.0/geckodriver-v0.26.0-linux64.tar.gz',
            ['sink' => $this->tempDir.'/geckodriver-v0.26.0-linux64.tar.gz']
        )->andReturnUsing(function () {
            copy(__DIR__.'/fixtures/geckodriver-v0.26.0-linux64.tar.gz', $this->tempDir.'/geckodriver-v0.26.0-linux64.tar.gz');
        });

        $this->artisan('dusk:firefox-driver', [
            '--all' => true,
            '--output' => $this->tempDir,
        ])
            ->expectsOutput('Geckodriver binaries successfully installed for version v0.26.0.')
            ->assertExitCode(0);

        $this->assertFileDoesNotExist($this->tempDir.'/geckodriver-v0.26.0-win64.zip');
        $this->assertStringEqualsFile($this->tempDir.'/geckodriver-win.exe', 'foo');

        $this->assertFileDoesNotExist($this->tempDir.'/geckodriver-v0.26.0-macos.tar.gz');
        $this->assertStringEqualsFile($this->tempDir.'/geckodriver-mac', 'foo');

        $this->assertFileDoesNotExist($this->tempDir.'/geckodriver-v0.26.0-linux64.tar.gz');
        $this->assertStringEqualsFile($this->tempDir.'/geckodriver-linux', 'foo');
    }

    public function test_it_will_handle_network_connection_lost()
    {
        $handlerStack = HandlerStack::create(new MockHandler([
            new Response(200, [], file_get_contents(__DIR__.'/fixtures/geckodriver-latest.json')),
            new RequestException(
                'curl (7): Failed to connect to api.github.com port 80: Connection refused',
                new Request('GET', 'https://github.com/mozilla/geckodriver/releases/download/v0.26.0/'.$this->archiveFilename)
            )
        ]));
        $http = new Client(['handler' => $handlerStack]);
        $this->app->instance(Client::class, $http);

        $expectedError = 'Failed to download https://github.com/mozilla/geckodriver/releases/download/v0.26.0/'.
            $this->archiveFilename.
            ': curl (7): Failed to connect to api.github.com port 80: Connection refused';

        $this->artisan('dusk:firefox-driver', ['--output' => $this->tempDir])
            ->expectsOutput($expectedError)
            ->assertExitCode(1);

        $this->assertFileDoesNotExist($this->tempDir.'/'.$this->archiveFilename);
        $this->assertFileDoesNotExist($this->tempDir.'/'.$this->binaryFilename, 'foo');
    }
}
