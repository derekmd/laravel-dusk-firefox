<?php

namespace Derekmd\Dusk\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Env;
use Illuminate\Support\Str;

class ChromeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dusk:chrome {--without-tty : Disable output to TTY}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the Dusk tests for the application in Google Chrome.';

    /**
     * The environment variable temporarily appended to .env.dusk.
     *
     * @var string
     */
    protected $environmentVariable = PHP_EOL.'DUSK_CHROME=1'.PHP_EOL;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->ignoreValidationErrors();
    }

    /**
     * Execute the console command.
     *
     * @return int|null
     */
    public function handle()
    {
        if (! $this->hasChromedriverSetup()) {
            $this->info('It looks like you must configure your application for Chromedriver.');
            $this->info('Run "php artisan dusk:install-firefox --with-chrome" to install new scaffolding.');

            return 1;
        }

        if (! $this->putEnvironmentVariable()) {
            $this->error('Unable to update file '.$this->duskFile().'. Check the file exists and has write permissions.');

            return 1;
        }

        try {
            return $this->call('dusk', [
                '--without-tty' => $this->option('without-tty'),
            ]);
        } finally {
            $this->clearEnvironmentVariable();
        }
    }

    /**
     * Determine if command 'dusk:install-firefox --with-chrome' was run.
     *
     * @return bool
     */
    protected function hasChromedriverSetup()
    {        
        return Str::contains($this->duskTestCaseContents(), 'startChromeDriver()');
    }

    /**
     * @return string|bool
     */
    protected function duskTestCaseContents()
    {
        return file_get_contents(base_path('tests/DuskTestCase.php'));
    }

    /**
     * Write the temporary environment variable to the .env.dusk.
     *
     * @return bool
     */
    protected function putEnvironmentVariable()
    {
        if (! file_exists($file = $this->duskFile())) {
            return false;
        }

        if (($contents = file_get_contents($file)) === false) {
            return false;
        }

        if (! file_put_contents($file, $contents.$this->environmentVariable)) {
            return false;
        }

        return true;
    }

    /**
     * Remove the temporary variable from the .env.dusk file.
     */
    protected function clearEnvironmentVariable()
    {
        if (! file_exists($file = $this->duskFile())) {
            return;
        }

        if (($contents = file_get_contents($file)) === false) {
            return;
        }

        file_put_contents($file, str_replace($this->environmentVariable, '', $contents));
    }

    /**
     * Get the path of the Dusk file for the environment.
     *
     * @return string
     */
    protected function duskFile()
    {
        static $file;

        if ($file === null) {
            $file = base_path('.env.dusk.'.$this->laravel->environment());

            if (! file_exists($file)) {
                $file = base_path('.env.dusk');
            }
        }

        return $file;
    }
}
