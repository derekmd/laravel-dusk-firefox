<?php

namespace Derekmd\Dusk;

use Laravel\Dusk\OperatingSystem as BaseOperatingSystem;

class OperatingSystem extends BaseOperatingSystem
{
    /**
     * Get the identifier of the current operating system for Geckodriver
     * binary discovery. This excludes 'mac-intel' that OperatorSystem::id()
     * returns for Chromedriver.
     *
     * @return string
     */
    public static function geckodriverId()
    {
        if (static::onWindows()) {
            return 'win';
        }

        if (static::onMac()) {
            if (php_uname('m') === 'arm64') {
                return 'mac-arm';
            }

            return 'mac';
        }

        return 'linux';
    }
}
