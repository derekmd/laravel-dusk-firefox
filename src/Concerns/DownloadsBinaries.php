<?php

namespace Derekmd\Dusk\Concerns;

use Derekmd\Dusk\Exceptions\DownloadException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\ProcessUtils;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

trait DownloadsBinaries
{
    /**
     * The HTTP client instance.
     *
     * @var \GuzzleHttp\Client
     */
    protected $http;

    /**
     * Extract the binary from the archive and delete the archive.
     *
     * @param  string  $archive  Path to the file being extracted.
     * @param  string  $slug  Name of the file being extracted.
     * @param  string  $directory  Destination path for the extraction.
     * @return string
     *
     * @throws \RuntimeException
     */
    protected function extract($archive, $slug, $directory)
    {
        if (preg_match('/\.zip$/', $slug)) {
            $binary = $this->extractZip($archive, $directory);
        } else {
            $binary = $this->extractTarball($archive, $directory);
        }

        unlink($archive);

        if (empty($binary)) {
            throw new RuntimeException("Unable to find executable in downloaded file $archive");
        }

        return $binary;
    }

    /**
     * Extract the binary from the .zip archive.
     *
     * @param  string  $archive
     * @param  string  $directory
     * @return string
     */
    protected function extractZip($archive, $directory)
    {
        $zip = new ZipArchive;

        $zip->open($archive);

        $zip->extractTo($directory);

        $binary = $zip->getNameIndex(0);

        $zip->close();

        return $binary;
    }

    /**
     * Extract the binary from the .tar.gz archive.
     *
     * @param  string  $archive
     * @param  string  $directory
     * @return string|null
     *
     * @throws \RuntimeException
     */
    protected function extractTarball($archive, $directory)
    {
        $output = [];
        $exitCode = 0;

        $isSuccessful = exec(vsprintf('tar -xvzf %s -C %s 2>&1', [
            ProcessUtils::escapeArgument($archive),
            ProcessUtils::escapeArgument($directory),
        ]), $output, $exitCode);

        // Handle Mingw-w64 mounted paths.
        if (in_array($exitCode, [2, 128], true) && preg_match('/^[A-Z]:/', $archive)) {
            try {
                return $this->extractTarball(
                    $this->formatMountedWindowsPath($archive),
                    $this->formatMountedWindowsPath($directory)
                );
            } catch (RuntimeException $e) {
                // Suppress this and instead report below on the original path.
            }
        }

        if ($isSuccessful === false || $exitCode !== 0) {
            throw new RuntimeException(collect([
                'Unable to execute "tar" to extract downloaded file '.$archive,
            ])->when($output, function ($message, $output) {
                return $message->push('Output:')->merge($output);
            })->join("\n"));
        }

        if (! empty($output)) {
            $binaryPath = $output[count($output) - 1];

            return basename(Str::after($binaryPath, 'x '));
        }
    }

    /**
     * Cygwin & Mingw-w64 use mounted paths. i.e., Unix commands like 'tar'
     * can't handle the native Windows path C:\laravel-dusk-firefox/tests/bin.
     * Instead reformat such a string as /c/laravel-dusk-firefox/tests/bin.
     *
     * @param  string  $path
     * @return string
     */
    protected function formatMountedWindowsPath($path)
    {
        if (preg_match('/^[A-Z]:/', $path, $matches)) {
            return '/'.
                strtolower($matches[0][0]).
                str_replace('\\', '/', substr($path, 2));
        }

        return $path;
    }

    /**
     * Get the contents of a URL and put it in local storage.
     *
     * @param  string  $url
     * @param  string  $path
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws \Derekmd\Dusk\Exceptions\DownloadException
     */
    protected function downloadTo($url, $path)
    {
        return $this->downloadUrl($url, ['sink' => $path]);
    }

    /**
     * Get the contents of a URL using the 'proxy' and 'ssl-no-verify' command options.
     *
     * @param  string  $url
     * @param  array   $options
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws \Derekmd\Dusk\Exceptions\DownloadException
     */
    protected function downloadUrl($url, array $options = [])
    {
        if ($this->option('proxy')) {
            $options['proxy'] = $this->option('proxy');
        }

        if ($this->option('ssl-no-verify')) {
            $options['verify'] = false;
        }

        try {
            return $this->getHttpClient()->request('GET', $url, $options);
        } catch (GuzzleException $e) {
            throw new DownloadException($url, $e);
        }
    }

    /**
     * Get the HTTP client instance.
     *
     * @return \GuzzleHttp\Client
     */
    public function getHttpClient()
    {
        if (! $this->http) {
            $this->http = app(Client::class);
        }

        return $this->http;
    }

    /**
     * Get the value of a command option.
     *
     * @param  string|null  $key
     * @return string|array|bool|null
     */
    abstract public function option($key = null);
}
