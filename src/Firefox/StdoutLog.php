<?php

namespace Derekmd\Dusk\Firefox;

use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class StdoutLog
{
    /**
     * The Geckodriver proxy server process.
     *
     * @var \Symfony\Component\Process\Process
     */
    protected $process;

    /**
     * Create the Geckodriver stdout log instance.
     *
     * @param \Symfony\Component\Process\Process $process
     */
    public function __construct(Process $process)
    {
        $this->process = $process;
    }

    /**
     * Get the Geckodriver formatted stdout with Mozilla debugging removed.
     *
     * @return string
     */
    public function getOutput()
    {
        return collect(
            preg_split('/[\r\n]+/', $this->process->getOutput())
        )->reject(function ($line) {
            return Str::contains($line, [
                "INFO\tListening on ",
                "INFO\tRunning command",
                "WARN\tLoading extension",
                "INFO\tMarionette enabled",
                'No settings file exists, new profile?',
                'failed mapping default framebuffer',
            ]);
        })->implode("\n");
    }
}
