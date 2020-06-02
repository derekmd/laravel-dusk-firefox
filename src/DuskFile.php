<?php

namespace Derekmd\Dusk;

class DuskFile
{
    /**
     * The environment for the current application.
     *
     * @var string
     */
    protected $environment;

    /**
     * Create a DuskFile instance.
     *
     * @param  string  $environment
     */
    public function __construct($environment)
    {
        $this->environment = $environment;
    }

    /**
     * Append a string to the end of the .env.dusk file.
     *
     * @param  string  $value
     *
     * @return bool
     */
    public function append($value)
    {
        if (is_null($contents = $this->read())) {
            return false;
        }

        return $this->write($contents.$value);
    }

    /**
     * Remove a string from the .env.dusk file.
     *
     * @param  string  $value
     *
     * @return void
     */
    public function clear($value)
    {
        if (! is_null($contents = $this->read())) {
            $this->write(str_replace($value, '', $contents));
        }
    }

    /**
     * Get the path of the Dusk file for the environment.
     *
     * @return string
     */
    public function path()
    {
        static $path;

        if ($path === null) {
            $path = base_path('.env.dusk.'.$this->environment);

            if (! is_file($path)) {
                $path = base_path('.env.dusk');
            }
        }

        return $path;
    }

    /**
     * Read the Dusk file contents from the filesystem.
     *
     * @return string|null
     */
    protected function read()
    {
        if (! is_file($file = $this->path())) {
            return;
        }

        if (($contents = file_get_contents($file)) !== false) {
            return $contents;
        }
    }

    /**
     * Rewrite the Dusk file contents to the filesystem.
     *
     * @return bool
     */
    protected function write($contents)
    {
        return file_put_contents($this->path(), $contents) !== false;
    }
}
