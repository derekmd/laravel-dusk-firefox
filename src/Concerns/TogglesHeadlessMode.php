<?php

namespace Derekmd\Dusk\Concerns;

trait TogglesHeadlessMode
{
    /**
     * Remove browser option arguments for headless mode when it is disabled.
     *
     * @param  array  $args
     * @return array
     */
    protected function filterHeadlessArguments(array $args = [])
    {
        return collect($args)->when($this->hasHeadlessDisabled(), function ($args) {
            return $args->diff([
                '--disable-gpu',
                '--headless',
            ]);
        })->values()->all();
    }
}
