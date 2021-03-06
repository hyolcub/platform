<?php

namespace SuperV\Platform\Testing;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use SuperV\Platform\Domains\Addon\Installer;
use SuperV\Platform\Domains\Addon\Locator;
use SuperV\Platform\PlatformServiceProvider;
use Tests\CreatesApplication;
use Tests\TestCase;

class PlatformTestCase extends TestCase
{
    use RefreshDatabase;
    use CreatesApplication;
    use DispatchesJobs;
    use TestHelpers;

    protected $installs = [];

    protected $port;

    protected $theme;

    protected function setUp()
    {
        parent::setUp();

        $this->artisan('superv:install');
        config(['superv.installed' => true]);

        $this->handlePostInstallCallbacks();

        foreach ($this->installs as $addon) {
            app(Installer::class)->setLocator(new Locator())
                                 ->setSlug($addon)
                                 ->install();
        }

        (new PlatformServiceProvider($this->app))->boot();

        $this->setUpMacros();

        $this->loadFactoriesUsing($this->app, __DIR__.'/../../tests/database/factories');
    }
}