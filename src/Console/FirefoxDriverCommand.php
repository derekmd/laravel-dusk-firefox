<?php

namespace Derekmd\Dusk\Console;

use Derekmd\Dusk\Concerns\DownloadsBinaries;
use Derekmd\Dusk\Exceptions\DownloadException;
use Illuminate\Console\Command;
use Laravel\Dusk\OperatingSystem;

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
     * URL to the latest release version.
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
     * Download slugs for the available operating systems.
     *
     * @var array
     */
    protected $slugs = [
        'linux' => 'linux64.tar.gz',
        'mac' => 'macos.tar.gz',
        'win' => 'win64.zip',
    ];

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

        $currentOS = OperatingSystem::id();

        foreach ($this->slugs as $os => $slug) {
            if ($this->option('all') || ($os === $currentOS)) {
                try {
                    $archive = $this->download($version, $slug);
                } catch (DownloadException $e) {
                    $this->error($e->getMessage());

                    return 1;
                }

                $binary = $this->extract($archive, $slug);

                $this->rename($binary, $os);
            }
        }

        $this->info(vsprintf('Geckodriver %s successfully installed for version %s.', [
            $this->option('all') ? 'binaries' : 'binary',
            $version,
        ]));

        if (OperatingSystem::onWindows()) {
            $this->comment('Geckodriver on Windows requires installing Microsoft Visual Studio redistributable runtime.');
            $this->comment('Download binaries at https://support.microsoft.com/en-us/help/2977003/the-latest-supported-visual-c-downloads');
        }
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
        $body = $this->downloadUrl($this->latestVersionUrl)->getBody();

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
