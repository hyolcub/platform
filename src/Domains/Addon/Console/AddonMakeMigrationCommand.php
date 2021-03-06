<?php

namespace SuperV\Platform\Domains\Addon\Console;

use SuperV\Platform\Contracts\Command;
use SuperV\Platform\Domains\Addon\AddonModel;

class AddonMakeMigrationCommand extends Command
{
    protected $signature = 'addon:migration';

    public function handle()
    {
        $mode = $this->choice('Create or Alter?', ['0' => 'Create', '1' => 'Alter'], 0);
        $addon = $this->choice('Addon ?', AddonModel::enabled()->latest()->get()->pluck('slug')->all());
        if ($mode === 'Alter') {
            $allTables = [];
            foreach (\DB::select('SHOW tables') as $key => $table) {
                $allTables[] = head($table);
            }
            $table = $this->askWithCompletion('Database table?', $allTables);
        } else {
            $table = $this->ask('Table name?');
        }
        $name = $this->ask('Add something to migration name?', '');

        $name = $name ? '_'.str_slug($name, '_') : '';

        if ($mode === 'Create') {
            $arguments = [
                'name'     => "create_{$table}_table".$name,
                '--create' => $table,
                '--scope'  => $addon,
            ];
        } else {
            $arguments = [
                'name'    => "alter_{$table}_table".$name,
                '--table' => $table,
                '--scope' => $addon,
            ];
        }

        $this->call('make:migration', $arguments);
    }
}