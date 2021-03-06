<?php

namespace Tests\Platform\Domains\Database\Migrations\Console;

use Mockery as m;
use SuperV\Platform\Domains\Database\Migrations\Console\MigrateMakeCommand;
use SuperV\Platform\Domains\Database\Migrations\MigrationCreator;
use SuperV\Platform\Domains\Database\Migrations\Scopes;
use Tests\Platform\TestCase;
use Tests\Platform\TestsConsoleCommands;

class MakeCommandTest extends TestCase
{
    use TestsConsoleCommands;

    protected $tmpDirectory = 'test-migrations';

//    /** @test */
//    function calls_creator_with_proper_arguments()
//    {
//        $command = new MigrateMakeCommand(
//            $creator = m::mock(MigrationCreator::class),
//            m::mock('Illuminate\Support\Composer')->shouldIgnoreMissing()
//        );
//        $command->setLaravel($this->app);
//
//        $creator->shouldReceive('setScope')->with('test-scope')->once();
//        $creator->shouldReceive('create')->once();
//
//        $this->runCommand($command, ['name' => 'CreateMigrationMake', '--scope' => 'test-scope']);
//    }

    /**
     * @test
     *
     * @group filesystem
     */
    function sets_path_from_registered_scopes()
    {
        Scopes::register('sample', $this->tmpDirectory);

        $this->assertCount(0, \File::files($this->tmpDirectory));
        $this->artisan('make:migration', ['name' => 'CreateMigrationMake', '--scope' => 'sample']);
        $this->assertCount(1, \File::files($this->tmpDirectory));
    }
}