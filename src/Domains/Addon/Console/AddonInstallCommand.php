<?php

namespace SuperV\Platform\Domains\Addon\Console;

use Illuminate\Console\Command;
use SuperV\Platform\Domains\Addon\Installer;
use SuperV\Platform\Domains\Addon\Locator;
use SuperV\Platform\Exceptions\ValidationException;

class AddonInstallCommand extends Command
{
    protected $signature = 'addon:install {addon} {--path=} {--seed}';

    public function handle(Installer $installer)
    {
        try {
            $this->comment(sprintf('Installing %s', $this->argument('addon')));
            $installer->setCommand($this);
            $installer->setSlug($this->argument('addon'));

            if ($this->option('path')) {
                $installer->setPath($this->option('path'));
            } else {
                $installer->setLocator(new Locator());
            }

            $installer->install();

            if ($this->option('seed')) {
                $installer->seed();
            }

            $this->comment(sprintf("Addon %s installed \n", $this->argument('addon')));
        } catch (ValidationException $e) {
            $this->error($e->getErrorsAsString());
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}