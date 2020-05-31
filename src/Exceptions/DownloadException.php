<?php

namespace Derekmd\Dusk\Exceptions;

use Exception;

class DownloadException extends Exception
{
    /**
     * Create a download exception instance.
     *
     * @param string  $url
     * @param \Exception|null $previous
     */
    public function __construct($url, Exception $previous = null)
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
}
