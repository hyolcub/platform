<?php

namespace SuperV\Platform\Domains\Resource\Relation\Types;

use Illuminate\Database\Eloquent\Relations\BelongsTo as EloquentBelongsTo;
use Illuminate\Database\Eloquent\Relations\Relation as EloquentRelation;
use SuperV\Platform\Domains\Database\Model\Contracts\EntryContract;
use SuperV\Platform\Domains\Resource\Contracts\AcceptsParentEntry;
use SuperV\Platform\Domains\Resource\Contracts\ProvidesFilter;
use SuperV\Platform\Domains\Resource\Filter\SelectFilter;
use SuperV\Platform\Domains\Resource\Relation\Relation;

class BelongsTo extends Relation implements AcceptsParentEntry, ProvidesFilter
{
    protected function newRelationQuery(?EntryContract $relatedEntryInstance = null): EloquentRelation
    {
        return new EloquentBelongsTo(
            $relatedEntryInstance->newQuery(),
            $this->parentEntry,
            $this->relationConfig->getForeignKey(),
            'id',
            $this->getName()
        );
    }

    public function makeFilter(?array $params = [])
    {
        $resource = sv_resource($this->getRelationConfig()->getRelatedResource());
        $options = $resource->newQuery()->get()->map(function (EntryContract $entry) use ($resource) {
            return ['value' => $entry->getId(), 'text' => $resource->getEntryLabel($entry)];
        })->all();

//        $options = array_merge([['value' => null, 'text' => $resource->getSingularLabel()]], $options);

        return SelectFilter::make($this->getName(), $resource->getSingularLabel())
                           ->setOptions($options)
                           ->setAttribute($this->getRelationConfig()->getForeignKey());
    }
}