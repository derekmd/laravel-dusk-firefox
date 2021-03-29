<?php

namespace Derekmd\Dusk\Exceptions;

use Exception;
use GuzzleHttp\Exception\GuzzleException;

class DownloadException extends Exception
{
    /**
     * Create a download exception instance.
     *
     * @param  string  $url
     * @param  \GuzzleHttp\Exception\GuzzleException|null  $previous
     */
    public function __construct($url, GuzzleException $previous = null)
    {
        parent::__construct(
            collect([
                'Failed to download '.$url,
                $previous ? $previous->getMessage() : '',
            ])->filter()->implode(': '),
            $previous ? $previous->getCode() : 0,
            $previous
        );
    }

    /**
     * Determine if the download failure reason is being HTTP rate limited.
     * This may happen from CI environments with many clients on the same
     * IP address fetching from GitHub.
     *
     * @return bool
     */
    public function isRateLimited()
    {
        return $this->getCode() === 403;
    }
}
