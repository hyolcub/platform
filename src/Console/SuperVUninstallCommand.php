<?php

namespace SuperV\Platform\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Platform;

class SuperVUninstallCommand extends Command
{
    protected $signature = 'superv:uninstall';

    protected $description = 'Uninstall SuperV Platform';

    public function handle()
    {
        $this->comment('Uninstalling SuperV');
//        foreach (Platform::tables() as $table) {
//            Schema::dropIfExists($table);
//        }

        $this->call('migrate:rollback', ['--scope' => 'platform', '--force' => true]);

        $this->setEnv('SV_INSTALLED=false');
//        if (Schema::dropIfExists('sv_addons')) {
//            Schema::table('migrations', function (Blueprint $table) {
//                $table->string('scope')->nullable();
//            });
//        } else {
//            $this->call('migrate', ['--force' => true]);
//        }
//        $this->call('migrate', ['--scope' => 'platform', '--force' => true]);
//
//        $this->setEnv('SV_INSTALLED=true');
//
//        $this->call('vendor:publish', ['--tag' => 'superv.config']);
//        $this->call('vendor:publish', ['--tag' => 'superv.views']);
//        $this->call('vendor:publish', ['--tag' => 'superv.assets']);

        $this->comment("SuperV Uninstalled..! \n");
    }

    public function setEnv($line)
    {
        list($variable, $value) = explode('=', $line, 2);

        $data = $this->readEnvironmentFile();

        array_set($data, $variable, $value);

        $this->writeEnvironmentFile($data);
    }

    protected function readEnvironmentFile()
    {
        $data = [];

        $file = base_path('.env');

        if (! file_exists($file)) {
            return $data;
        }

        foreach (file($file) as $line) {
            // Check for # comments.
            if (starts_with($line, '#')) {
                $data[] = $line;
            } elseif ($operator = strpos($line, '=')) {
                $key = substr($line, 0, $operator);
                $value = substr($line, $operator + 1);

                $data[$key] = $value;
            }
        }

        return $data;
    }

    protected function writeEnvironmentFile($data)
    {
        $contents = '';

        foreach ($data as $key => $value) {
            if ($key) {
                $contents .= PHP_EOL.strtoupper($key).'='.$value;
            } else {
                $contents .= PHP_EOL.$value;
            }
        }

        $file = base_path('.env');

        file_put_contents($file, $contents);
    }
}