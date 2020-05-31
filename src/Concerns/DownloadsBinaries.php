<?php

namespace Derekmd\Dusk\Concerns;

use Derekmd\Dusk\Exceptions\DownloadException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Phar;
use PharData;
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
     * @param  string  $archive
     * @param  string  $slug
     * @return string
     */
    protected function extract($archive, $slug)
    {
        if (preg_match('/\.zip$/', $slug)) {
            $binary = $this->extractZip($archive);
        } else {
            $binary = $this->extractTarball($archive);
        }

        unlink($archive);

        return $binary;
    }

    /**
     * Extract the binary from the .zip archive.
     *
     * @param  string  $archive
     * @return string
     */
    protected function extractZip($archive)
    {
        $zip = new ZipArchive;

        $zip->open($archive);

        $zip->extractTo($this->directory);

        $binary = $zip->getNameIndex(0);

        $zip->close();

        return $binary;
    }

    /**
     * Extract the binary from the .tar.gz archive.
     *
     * @param  string  $archive
     * @return string
     */
    protected function extractTarball($archive)
    {
        $gzip = new PharData($archive);
        $gzip->convertToData(Phar::ZIP);

        $zipArchive = str_replace('tar.gz', 'zip', $archive);

        $binary = $this->extractZip($zipArchive);

        unlink($zipArchive);

        return $binary;
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
