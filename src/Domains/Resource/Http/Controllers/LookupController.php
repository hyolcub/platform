<?php

namespace SuperV\Platform\Domains\Resource\Http\Controllers;

use SuperV\Platform\Domains\Resource\Http\ResolvesResource;
use SuperV\Platform\Domains\Resource\Table\ResourceTable;
use SuperV\Platform\Domains\UI\Jobs\MakeComponentTree;
use SuperV\Platform\Http\Controllers\BaseApiController;

class LookupController extends BaseApiController
{
    use ResolvesResource;

    public function __invoke(ResourceTable $table)
    {
        $relation = $this->resolveRelation();
        $resource = $relation->getRelatedResource();

        $table->setResource($resource);
        $table->setDataUrl(url()->current().'/data');

        if ($this->route->parameter('data')) {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = $resource->newQuery();

            $keyName = $query->getModel()->getKeyName();
            $alreadyAttachedItems = $this->entry->{$relation->getName()}()
                                                ->pluck($resource->getHandle().'.'.$keyName);

            $query->whereNotIn($keyName, $alreadyAttachedItems);
            $table->setQuery($query);

            return $table->build($this->request);
        }

        return MakeComponentTree::dispatch($table)->withTokens(['res' => $resource->toArray()]);
    }
}