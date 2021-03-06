<?php

namespace SuperV\Platform\Domains\Resource\Resource;

use Closure;
use Illuminate\Support\Collection;
use SuperV\Platform\Domains\Resource\Contracts\ProvidesFilter;
use SuperV\Platform\Domains\Resource\Field\Contracts\Field;
use SuperV\Platform\Domains\Resource\Resource;
use SuperV\Platform\Exceptions\PlatformException;

class Fields
{
    /**
     * @var \SuperV\Platform\Domains\Resource\Resource
     */
    protected $resource;

    /**
     * @var \Illuminate\Support\Collection
     */
    protected $fields;

    public function __construct(Resource $resource, $fields)
    {
        $this->resource = $resource;
        $this->fields = $fields instanceof Closure ? $fields() : $fields;
    }

    public function getAll()
    {
        return $this->fields;
    }

    public function sort()
    {
        $this->fields = $this->fields->sortBy(function (Field $field) {
            return $field->getConfigValue('sort_order', 100);
        });

        return $this;
    }

    public function get($name): Field
    {
        $field = $this->fields->first(
            function (Field $field) use ($name) {
                return $field->getName() === $name;
            });

        if (! $field) {
            PlatformException::fail("Field not found: [{$name}]");
        }

        return $field;
    }

    public function showOnIndex($name): Field
    {
        return $this->get($name)->showOnIndex();
    }

    public function keyByName(): Collection
    {
        return $this->fields->keyBy(function (Field $field) {
            return $field->getName();
        });
    }

    public function withFlag($flag): Collection
    {
        return $this->fields->filter(function (Field $field) use ($flag) {
            return $field->hasFlag($flag);
        });
    }

    public function getFilters(): Collection
    {
        $filters = $this->fields
            ->filter(function (Field $field) {
                return $field->hasFlag('filter');
            })->map(function (Field $field) {
                $fieldType = $field->resolveFieldType();
                if ($fieldType instanceof ProvidesFilter) {
                    return $fieldType->makeFilter($field->getConfigValue('filter'));
                }
            });

        return $filters->filter();
    }

    public function getHeaderImage(): ?Field
    {
        return $this->withFlag('header.show')->first();
    }
}