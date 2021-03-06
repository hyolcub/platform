<?php

namespace Tests\Platform\Domains\Database\Migrations\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery as m;
use SuperV\Platform\Domains\Database\Migrations\Console\MigrateCommand;
use SuperV\Platform\Domains\Database\Migrations\Migrator;
use SuperV\Platform\Domains\Database\Migrations\Scopes;
use Tests\Platform\TestCase;
use Tests\Platform\TestsConsoleCommands;

class MigrateCommandTest extends TestCase
{
    use RefreshDatabase;
    use TestsConsoleCommands;

    /** @test */
    function migrate_command_calls_migrator_with_proper_arguments()
    {
        $migrateCommand = new MigrateCommand(
            $migrator = m::mock(Migrator::class)->shouldIgnoreMissing()
        );
        $migrateCommand->setLaravel($this->app);


        Scopes::register('test-scope', __DIR__ . '/../migrations/baz');

        $migrator->shouldReceive('repositoryExists')->once()->andReturn(true);
        $migrator->shouldReceive('setScope')->with('test-scope')->once();
        $migrator->shouldReceive('setOutput')->once()->andReturnSelf();

        $this->runCommand($migrateCommand, ['--scope' => 'test-scope']);
    }

    /** @test */
    function migrate_command_get_path_from_registered_scopes()
    {
        Scopes::register('superv.addons.sample', base_path('tests/Platform/__fixtures__/sample-addon/database/migrations'));

        $this->artisan('migrate', ['--scope' => 'superv.addons.sample']);
        $this->assertDatabaseHas('migrations', ['migration' => '2016_01_01_200000_addon_foo_migration',
                                                'scope'     => 'superv.addons.sample']);
        $this->assertDatabaseHas('migrations', ['migration' => '2016_01_01_200100_addon_bar_migration',
                                                'scope'     => 'superv.addons.sample']);
        $this->assertDatabaseHas('migrations', ['migration' => '2016_01_01_200200_addon_baz_migration',
                                                'scope'     => 'superv.addons.sample']);
    }
}