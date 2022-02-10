<?php

namespace Derekmd\Dusk\Tests\Support;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

class TempDirectory
{
    private $path;

    /**
     * Create a local temporary directory.
     *
     * @param  string  $path
     */
    public function __construct($path)
    {
        $this->path = $path;

        $this->localFilesystem($path);
    }

    /**
     * Get the path of the created local temporary directory.
     *
     * @param  string|null  $append
     * @return string
     */
    public function path($append = null)
    {
        return $this->path.($append === null ? '' : '/'.$append);
    }

    /**
     * Delete the created local temporary directory.
     *
     * @return void
     */
    public function delete()
    {
        $filesystem = $this->localFilesystem(dirname($this->path));

        method_exists($filesystem, 'deleteDir')
            ? $filesystem->deleteDir(basename($this->path))
            : $filesystem->deleteDirectory(basename($this->path));
    }

    /**
     * Open a local directory using league/flysystem version 1.x, 2.x, or 3.x.
     *
     * @param  string  $path
     * @return \League\Flysystem\Filesystem
     */
    protected function localFilesystem($path)
    {
        $adapter = class_exists(Local::class)
            ? new Local($path)
            : new LocalFilesystemAdapter($path);

        return new Filesystem($adapter);
    }
}
