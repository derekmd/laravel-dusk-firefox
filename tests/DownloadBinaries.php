<?php

use Derekmd\Dusk\DuskServiceProvider;
use Orchestra\Testbench\TestCase;

class DownloadBinaries extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            DuskServiceProvider::class,
        ];
    }

    public function testDownload()
    {
        $this->artisan('dusk:firefox-driver', [
            '--all' => true,
            '--output' => __DIR__.'/../bin',
        ])->assertExitCode(0);

        echo "Geckodriver binaries downloaded to the package's bin/ directory.".PHP_EOL;
    }
}
