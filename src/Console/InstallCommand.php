<?php

namespace Derekmd\Dusk\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dusk:install-firefox
                {--with-chrome : Include a Chromedriver configuration in the DuskTestCase class.}
                {--proxy= : The proxy to download the binary through (example: "tcp://127.0.0.1:9000")}
                {--ssl-no-verify : Bypass SSL certificate verification when installing through a proxy}
                {--output= : Directory path to copy test class into. (debug-only option)}
                {--force : Skip confirmation prompt when overwriting DuskTestCase.php.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Dusk for Firefox into the application';

    /**
     * Execute the console command.
     *
     * @return int|null
     */
    public function handle()
    {
        if (! is_dir(base_path('tests/Browser/screenshots')) && ! $this->option('output')) {
            $this->info('Laravel Dusk command "php artisan dusk:install" must be run first.');

            return 1;
        }

        $stubDestination = ($this->option('output') ?: base_path('tests')).'/DuskTestCase.php';

        if (! $this->option('force') &&
            file_exists($stubDestination) &&
            ! $this->confirm("Overwrite file $stubDestination?")
        ) {
            $this->comment("Firefox scaffolding not installed.");

            return;
        }

        if (! copy(__DIR__.'/../../stubs/'.$this->stubFile(), $stubDestination)) {
            $this->error("Unable to copy Firefox scaffolding to: $stubDestination");

            return 1;
        }

        $this->info('Firefox scaffolding installed successfully.');

        $this->comment('Downloading Geckodriver binaries...');

        $this->call('dusk:firefox-driver', $this->driverCommandArgs());
    }

    /**
     * Find the class stub to copy into the application.
     *
     * @return array
     */
    protected function stubFile()
    {
        if ($this->option('with-chrome')) {
            return 'DuskTestCase.stub';
        }

        return 'FirefoxDuskTestCase.stub';
    }

    /**
     * Build arguments for the driver download command.
     *
     * @return array
     */
    protected function driverCommandArgs()
    {
        $args = [];

        if ($this->option('proxy')) {
            $args['--proxy'] = $this->option('proxy');
        }

        if ($this->option('ssl-no-verify')) {
            $args['--ssl-no-verify'] = true;
        }

        return $args;
    }
}
