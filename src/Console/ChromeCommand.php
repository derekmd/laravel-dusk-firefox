<?php

namespace Derekmd\Dusk\Console;

use Derekmd\Dusk\DuskFile;
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
    protected $environmentVariable = PHP_EOL.'DUSK_CHROME=1 # added by Artisan command "dusk:chrome"'.PHP_EOL;

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

        $duskFile = new DuskFile($this->laravel->environment());

        if (! $duskFile->append($this->environmentVariable)) {
            $this->error('Unable to update '.$duskFile->path().'. Check the file exists and has write permissions.');

            return 1;
        }

        try {
            return $this->call('dusk', [
                '--without-tty' => $this->option('without-tty'),
            ]);
        } finally {
            $duskFile->clear($this->environmentVariable);
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
     * Get DuskTestCase.php code from the filesystem.
     *
     * @return string|bool
     */
    protected function duskTestCaseContents()
    {
        return file_get_contents(base_path('tests/DuskTestCase.php'));
    }
}
