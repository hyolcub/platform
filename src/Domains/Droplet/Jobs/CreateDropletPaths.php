<?php

namespace SuperV\Platform\Domains\Droplet\Jobs;

use SuperV\Platform\Contracts\Filesystem;
use SuperV\Platform\Domains\Droplet\Model\DropletModel;

class CreateDropletPaths
{
    /** @var \SuperV\Platform\Domains\Droplet\Model\DropletModel */
    private $model;

    public function __construct(DropletModel $model)
    {
        $this->model = $model;
    }

    public function handle(Filesystem $filesystem)
    {
        $this->model->setPath("{$this->model->path}");

        $path = base_path($this->model->getPath());
        $filesystem->makeDirectory($path, 0755, true, true);
        $filesystem->makeDirectory("{$path}/src", 0755, true, true);
        $filesystem->makeDirectory("{$path}/src/Domains", 0755, true, true);
        $filesystem->makeDirectory("{$path}/src/Features", 0755, true, true);
        $filesystem->makeDirectory("{$path}/src/Console", 0755, true, true);
        $filesystem->makeDirectory("{$path}/resources", 0755, true, true);
        $filesystem->makeDirectory("{$path}/routes", 0755, true, true);
        $filesystem->makeDirectory("{$path}/config", 0755, true, true);
        $filesystem->makeDirectory("{$path}/database/migrations", 0755, true, true);
    }
}
