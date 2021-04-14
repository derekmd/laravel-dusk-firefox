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

    protected const VERSION = 'v0.29.0';

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

        parent::tearDown();
    }

    protected function archiveFilename()
    {
        switch (PHP_OS_FAMILY) {
            case 'Windows':
                return 'geckodriver-'.static::VERSION.'-win64.zip';
            case 'Darwin':
                if (php_uname('m') === 'arm64') {
                    return 'geckodriver-'.static::VERSION.'-macos-aarch64.tar.gz';
                }

                return 'geckodriver-'.static::VERSION.'-macos.tar.gz';
            default:
                return 'geckodriver-'.static::VERSION.'-linux64.tar.gz';
        }
    }

    protected function binaryFilename()
    {
        switch (PHP_OS_FAMILY) {
            case 'Windows':
                return 'geckodriver-win.exe';
            case 'Darwin':
                if (php_uname('m') === 'arm64') {
                    return 'geckodriver-mac-arm';
                }

                return 'geckodriver-mac';
            default:
                return 'geckodriver-linux';
        }
    }

    protected function os()
    {
        switch (PHP_OS_FAMILY) {
            case 'Windows':
                return 'win';
            case 'Darwin':
                if (php_uname('m') === 'arm64') {
                    return 'mac-arm';
                }

                return 'mac';
            default:
                return 'linux';
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
            ->andReturn($this->mockDownloadResponse(__DIR__.'/fixtures/geckodriver-latest.json'));

        $http->shouldReceive('request')->with(
            'GET',
            vsprintf('https://github.com/mozilla/geckodriver/releases/download/%s/%s', [
                static::VERSION,
                $this->archiveFilename,
            ]),
            ['sink' => $this->tempDir.'/'.$this->archiveFilename]
        )->andReturnUsing(function () {
            return $this->copyMockBinary($this->archiveFilename);
        });

        $this->artisan('dusk:firefox-driver', ['--output' => $this->tempDir])
            ->expectsOutput('Geckodriver binary successfully installed for version '.static::VERSION.'.')
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
        )->andReturn($this->mockDownloadResponse(__DIR__.'/fixtures/geckodriver-latest.json'));

        $http->shouldReceive('request')->with(
            'GET',
            vsprintf('https://github.com/mozilla/geckodriver/releases/download/%s/%s', [
                static::VERSION,
                $this->archiveFilename,
            ]),
            [
                'sink' => $this->tempDir.'/'.$this->archiveFilename,
                'proxy' => 'tcp://127.0.0.1:9000',
                'verify' => false,
            ]
        )->andReturnUsing(function () {
            return $this->copyMockBinary($this->archiveFilename);
        });

        $this->artisan('dusk:firefox-driver', [
            '--output' => $this->tempDir,
            '--proxy' => 'tcp://127.0.0.1:9000',
            '--ssl-no-verify' => true,
        ])
            ->expectsOutput('Geckodriver binary successfully installed for version '.static::VERSION.'.')
            ->assertExitCode(0);

        $this->assertFileDoesNotExist($this->tempDir.'/'.$this->archiveFilename);
        $this->assertStringEqualsFile($this->tempDir.'/'.$this->binaryFilename, 'foo');
    }

    public function test_it_can_download_geckodriver_for_linux_mac_and_windows()
    {
        $this->app->instance(Client::class, $http = m::mock(Client::class . '[request]'));

        $http->shouldReceive('request')
            ->with('GET', 'https://api.github.com/repos/mozilla/geckodriver/releases/latest', [])
            ->andReturn($this->mockDownloadResponse(__DIR__.'/fixtures/geckodriver-latest.json'));

        $http->shouldReceive('request')->with(
            'GET',
            vsprintf('https://github.com/mozilla/geckodriver/releases/download/%s/geckodriver-%s-win64.zip', [
                static::VERSION,
                static::VERSION,
            ]),
            ['sink' => $this->tempDir.'/geckodriver-'.static::VERSION.'-win64.zip']
        )->andReturnUsing(function () {
            return $this->copyMockBinary('geckodriver-'.static::VERSION.'-win64.zip');
        });

        $http->shouldReceive('request')->with(
            'GET',
            vsprintf('https://github.com/mozilla/geckodriver/releases/download/%s/geckodriver-%s-macos.tar.gz', [
                static::VERSION,
                static::VERSION,
            ]),
            ['sink' => $this->tempDir.'/geckodriver-'.static::VERSION.'-macos.tar.gz']
        )->andReturnUsing(function () {
            return $this->copyMockBinary('geckodriver-'.static::VERSION.'-macos.tar.gz');
        });

        $http->shouldReceive('request')->with(
            'GET',
            vsprintf('https://github.com/mozilla/geckodriver/releases/download/%s/geckodriver-%s-macos-aarch64.tar.gz', [
                static::VERSION,
                static::VERSION,
            ]),
            ['sink' => $this->tempDir.'/geckodriver-'.static::VERSION.'-macos-aarch64.tar.gz']
        )->andReturnUsing(function () {
            return $this->copyMockBinary('geckodriver-'.static::VERSION.'-macos-aarch64.tar.gz');
        });

        $http->shouldReceive('request')->with(
            'GET',
            vsprintf('https://github.com/mozilla/geckodriver/releases/download/%s/geckodriver-%s-linux64.tar.gz', [
                static::VERSION,
                static::VERSION,
            ]),
            ['sink' => $this->tempDir.'/geckodriver-'.static::VERSION.'-linux64.tar.gz']
        )->andReturnUsing(function () {
            return $this->copyMockBinary('geckodriver-'.static::VERSION.'-linux64.tar.gz');
        });

        $this->artisan('dusk:firefox-driver', [
            '--all' => true,
            '--output' => $this->tempDir,
        ])
            ->expectsOutput('Geckodriver binaries successfully installed for version '.static::VERSION.'.')
            ->assertExitCode(0);

        $this->assertFileDoesNotExist($this->tempDir.'/geckodriver-'.static::VERSION.'-win64.zip');
        $this->assertStringEqualsFile($this->tempDir.'/geckodriver-win.exe', 'foo');

        $this->assertFileDoesNotExist($this->tempDir.'/geckodriver-'.static::VERSION.'-macos.tar.gz');
        $this->assertStringEqualsFile($this->tempDir.'/geckodriver-mac', 'foo');

        $this->assertFileDoesNotExist($this->tempDir.'/geckodriver-'.static::VERSION.'-macos-aarch64.tar.gz');
        $this->assertStringEqualsFile($this->tempDir.'/geckodriver-mac-arm', 'foo');

        $this->assertFileDoesNotExist($this->tempDir.'/geckodriver-'.static::VERSION.'-linux64.tar.gz');
        $this->assertStringEqualsFile($this->tempDir.'/geckodriver-linux', 'foo');
    }

    public function test_it_will_handle_network_connection_lost()
    {
        $handlerStack = HandlerStack::create(new MockHandler([
            new Response(200, [], file_get_contents(__DIR__.'/fixtures/geckodriver-latest.json')),
            new RequestException(
                'curl (7): Failed to connect to api.github.com port 80: Connection refused',
                new Request('GET', vsprintf('https://github.com/mozilla/geckodriver/releases/download/%s/%s', [
                    static::VERSION,
                    $this->archiveFilename,
                ]))
            ),
        ]));
        $http = new Client(['handler' => $handlerStack]);
        $this->app->instance(Client::class, $http);

        $expectedError = vsprintf('Failed to download https://github.com/mozilla/geckodriver/releases/download/%s/%s', [
            static::VERSION,
            $this->archiveFilename,
        ]).': curl (7): Failed to connect to api.github.com port 80: Connection refused';

        $this->artisan('dusk:firefox-driver', ['--output' => $this->tempDir])
            ->expectsOutput($expectedError)
            ->assertExitCode(1);

        $this->assertFileDoesNotExist($this->tempDir.'/'.$this->archiveFilename);
        $this->assertFileDoesNotExist($this->tempDir.'/'.$this->binaryFilename, 'foo');
    }

    public function test_it_can_handle_empty_archive_missing_geckodriver_binary_file()
    {
        $this->app->instance(Client::class, $http = m::mock(Client::class . '[request]'));

        $downloadedFilename = str_replace(static::VERSION, 'missing-binary', $this->archiveFilename);

        $latestVersionResponse = $this->mockDownloadResponse(
            __DIR__.'/fixtures/geckodriver-latest.json',
            function ($body) use ($downloadedFilename) {
                return str_replace(static::VERSION, 'missing-binary', $body);
            }
        );

        $http->shouldReceive('request')
            ->with('GET', 'https://api.github.com/repos/mozilla/geckodriver/releases/latest', [])
            ->andReturn($latestVersionResponse);

        $http->shouldReceive('request')->with(
            'GET',
            "https://github.com/mozilla/geckodriver/releases/download/missing-binary/$downloadedFilename",
            ['sink' => $this->tempDir.'/'.$downloadedFilename]
        )->andReturnUsing(function () use ($downloadedFilename) {
            return $this->copyMockBinary($downloadedFilename);
        });

        $this->artisan('dusk:firefox-driver', ['--output' => $this->tempDir])
            ->expectsOutput('Geckodriver binary installation failed for '.$this->os().'.')
            ->assertExitCode(1);

        $this->assertFileDoesNotExist($this->tempDir.'/'.$this->archiveFilename);
        $this->assertFileDoesNotExist($this->tempDir.'/'.$this->binaryFilename);
    }

    protected function mockDownloadResponse($path, $callback = null)
    {
        $body = file_get_contents($path);

        if ($callback) {
            $body = $callback($body);
        }

        $response = m::mock(ResponseInterface::class);
        $response->shouldReceive('getBody')
            ->andReturn($body);

        return $response;
    }

    protected function copyMockBinary($filename)
    {
        copy(__DIR__.'/fixtures/'.$filename, $this->tempDir.'/'.$filename);

        return $this->mockDownloadResponse(__DIR__.'/fixtures/'.$filename);
    }
}
