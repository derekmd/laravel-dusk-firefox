<?php

namespace Derekmd\Dusk\Tests\Stubs;

use Derekmd\Dusk\Concerns\TogglesHeadlessMode;
use Laravel\Dusk\TestCase;

class TogglesHeadlessModeStub extends TestCase
{
    use TogglesHeadlessMode {
        filterHeadlessArguments as filterHeadlessArgumentsProtected;
    }

    public function __construct()
    {
        parent::__construct('TogglesHeadless');
    }

    public function createApplication()
    {
        // Not needed in this test suite. Method must be declared for PHPUnit v10.
    }

    public function filterHeadlessArguments($args)
    {
        return $this->filterHeadlessArgumentsProtected($args);
    }
}
