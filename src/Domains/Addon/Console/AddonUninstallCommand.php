<?php

namespace SuperV\Platform\Domains\Addon\Console;

use SuperV\Platform\Contracts\Command;
use SuperV\Platform\Domains\Addon\AddonModel;
use SuperV\Platform\Domains\Addon\Jobs\UninstallAddon;

class AddonUninstallCommand extends Command
{
    protected $signature = 'addon:uninstall {--addon=}';

    public function handle()
    {
        if (! $addon = $this->option('addon')) {
            $addon = $this->choice('Select Addon to Uninstall', AddonModel::enabled()->latest()->get()->pluck('slug')->all());
        }
        $this->comment(sprintf('Uninstalling %s', $addon));
        $this->call('migrate:reset', ['--scope' => $addon]);

        if ($this->dispatch(new UninstallAddon($addon))) {
            $this->info('The ['.$addon.'] addon successfully uninstalled.');
        } else {
            $this->error('Addon could not be uninstalled');
        }
    }
}