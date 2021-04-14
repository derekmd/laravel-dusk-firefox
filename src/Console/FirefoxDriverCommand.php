<?php

namespace Derekmd\Dusk\Console;

use Derekmd\Dusk\Concerns\DownloadsBinaries;
use Derekmd\Dusk\Exceptions\DownloadException;
use Derekmd\Dusk\OperatingSystem;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * @copyright Proxy downloads are based on https://github.com/staudenmeir/dusk-updater
 *            by Jonas Staudenmeir.
 */
class FirefoxDriverCommand extends Command
{
    use DownloadsBinaries;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dusk:firefox-driver {version?}
                    {--all : Install a Geckodriver binary for every OS}
                    {--proxy= : The proxy to download the binary through (example: "tcp://127.0.0.1:9000")}
                    {--ssl-no-verify : Bypass SSL certificate verification when installing through a proxy}
                    {--output= : Directory path to store binaries in. (debug-only option)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the Geckodriver binary';

    /**
     * Hardcode a recent version in case $latestVersionUrl can't be reached.
     *
     * @var string
     */
    protected $latestVersion = 'v0.29.1';

    /**
     * URL to discover latest release version.
     *
     * @var string
     */
    protected $latestVersionUrl = 'https://api.github.com/repos/mozilla/geckodriver/releases/latest';

    /**
     * URL to the Geckodriver download.
     *
     * @var string
     */
    protected $downloadUrl = 'https://github.com/mozilla/geckodriver/releases/download/{version}/geckodriver-{version}-{os}';

    /**
     * Path to the bin directory.
     *
     * @var string
     */
    protected $directory = __DIR__.'/../../bin';

    /**
     * Execute the console command.
     *
     * @return int|null
     */
    public function handle()
    {
        $this->directory = $this->option('output') ?: $this->directory;

        try {
            $version = $this->version();
        } catch (DownloadException $e) {
            $this->error($e->getMessage());

            return 1;
        }

        $osSuccesses = [];
        $osFailures = [];

        foreach ($this->binaryStubNames() as $os => $slug) {
            try {
                $archive = $this->download($version, $slug);

                $binary = $this->extract($archive, $slug, $this->directory);

                $this->rename($binary, $os);

                $osSuccesses[] = $os;
            } catch (DownloadException | RuntimeException $e) {
                $this->error($e->getMessage());

                $osFailures[] = $os;
            }
        }

        if (! empty($osFailures)) {
            $this->info(vsprintf('Geckodriver binary installation failed for %s.', [
                implode(', ', $osFailures),
            ]));

            if (! empty($osSuccesses)) {
                $this->info(vsprintf('Geckodriver %s successfully installed for version %s on %s.', [
                    count($osSuccesses) === 1 ? 'binary' : 'binaries',
                    $version,
                    implode(', ', $osSuccesses),
                ]));
            }

            return 1;
        }

        $this->info(vsprintf('Geckodriver %s successfully installed for version %s.', [
            $this->option('all') ? 'binaries' : 'binary',
            $version,
        ]));
    }

    /**
     * Stub name of the binary download for available operating systems.
     *
     * @return array
     */
    protected function binaryStubNames()
    {
        return collect([
            'linux' => 'linux64.tar.gz',
            'mac' => 'macos.tar.gz',
            'mac-arm' => 'macos-aarch64.tar.gz',
            'win' => 'win64.zip',
        ])->unless($this->option('all'), function ($items) {
            return $items->only(OperatingSystem::geckodriverId());
        })->all();
    }

    /**
     * Get the desired Geckodriver version.
     *
     * @return string
     */
    protected function version()
    {
        return $this->argument('version') ?: $this->latestVersion();
    }

    /**
     * Get the latest stable Geckodriver version.
     *
     * @return string
     *
     * @throws \Derekmd\Dusk\Exceptions\DownloadException
     */
    protected function latestVersion()
    {
        try {
            $body = $this->downloadUrl($this->latestVersionUrl)->getBody();
        } catch (DownloadException $e) {
            if (! $e->isRateLimited()) {
                throw $e;
            }

            $this->info(vsprintf('%s is rate limited from this IP address. Assuming %s.', [
                $this->latestVersionUrl,
                $this->latestVersion,
            ]));

            return $this->latestVersion;
        }

        $version = json_decode($body, true)['tag_name'] ?? null;

        if (empty($version)) {
            $this->error('GitHub release JSON property "tag_name" is not defined. Unable to discover the latest version.');

            throw new DownloadException($this->latestVersionUrl);
        }

        return $version;
    }

    /**
     * Download the Geckodriver archive.
     *
     * @param  string  $version
     * @param  string  $slug
     * @return string
     */
    protected function download($version, $slug)
    {
        $url = strtr($this->downloadUrl, [
            '{version}' => $version,
            '{os}' => $slug,
        ]);

        $this->downloadTo(
            $url, $archive = $this->directory.'/'.basename($url)
        );

        return $archive;
    }

    /**
     * Rename the Geckodriver binary and make it executable.
     *
     * @param  string  $binary
     * @param  string  $os
     * @return void
     */
    protected function rename($binary, $os)
    {
        $newName = str_replace('geckodriver', 'geckodriver-'.$os, $binary);

        rename($this->directory.'/'.$binary, $this->directory.'/'.$newName);

        chmod($this->directory.'/'.$newName, 0755);
    }
}
