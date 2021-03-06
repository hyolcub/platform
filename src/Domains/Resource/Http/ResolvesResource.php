<?php

namespace SuperV\Platform\Domains\Resource\Http;

use SuperV\Platform\Domains\Resource\Contracts\ProvidesForm;
use SuperV\Platform\Domains\Resource\Contracts\ProvidesTable;
use SuperV\Platform\Domains\Resource\ResourceFactory;
use SuperV\Platform\Exceptions\PlatformException;

trait ResolvesResource
{
    /** @var \SuperV\Platform\Domains\Resource\Resource */
    protected $resource;

    /** @var \SuperV\Platform\Domains\Resource\Model\ResourceEntry */
    protected $entry;

    /** @return \SuperV\Platform\Domains\Resource\Resource */
    protected function resolveResource()
    {
        if ($this->resource) {
            return $this->resource;
        }
        $resource = request()->route()->parameter('resource');
        $this->resource = ResourceFactory::make(str_replace('-', '_', $resource));

        if (! $this->resource) {
            throw new \Exception("Resource not found [{$resource}]");
        }

        if ($id = request()->route()->parameter('id')) {
            if (! $this->entry = $this->resource->find($id)) {
                PlatformException::fail('Entry not found');
            }
            if ($keyName = $this->resource->getConfigValue('key_name')) {
                $this->entry->setKeyName($keyName);
            }
        }

        return $this->resource;
    }

    /**
     * @return \SuperV\Platform\Domains\Resource\Relation\Relation|ProvidesForm|ProvidesTable
     */
    protected function resolveRelation()
    {
        $relation = $this->resolveResource()->getRelation($this->route->parameter('relation'));
        if ($this->entry) {
            $relation->acceptParentEntry($this->entry);
        }

        return $relation;
    }

    protected function resolveTableAction()
    {
        return $this->resolveResource()
                    ->resolveTable()
                    ->getAction($this->route->parameter('action'));
    }
}