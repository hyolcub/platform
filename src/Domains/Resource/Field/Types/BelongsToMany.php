<?php

namespace SuperV\Platform\Domains\Resource\Field\Types;

class BelongsToMany extends FieldType
{
    public function show(): bool
    {
        return false;
    }
}