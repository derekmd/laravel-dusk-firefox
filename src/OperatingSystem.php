<?php

namespace Derekmd\Dusk;

use Laravel\Dusk\OperatingSystem as BaseOperatingSystem;

class OperatingSystem extends BaseOperatingSystem
{
    /**
     * Get the identifier of the current operating system, exclusive of
     * the architecture that OperatingSystem::id() returns.
     *
     * @return string
     */
    public static function parentId()
    {
        return static::onWindows() ? 'win' : (static::onMac() ? 'mac' : 'linux');
    }
}
