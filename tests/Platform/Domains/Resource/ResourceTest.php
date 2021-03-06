<?php

namespace Tests\Platform\Domains\Resource;

use SuperV\Platform\Domains\Database\Schema\Blueprint;
use SuperV\Platform\Domains\Resource\Model\ResourceEntry;
use SuperV\Platform\Domains\Resource\Resource;
use SuperV\Platform\Domains\Resource\ResourceConfig;
use Tests\Platform\Domains\Resource\Fixtures\TestResourceEntry;

class ResourceTest extends ResourceTestCase
{
    function test__creates_anonymous_model_class_if_not_provided()
    {
        $resource = $this->makeResource('t_users');

        $entry = $resource->newEntryInstance();

        $this->assertInstanceOf(ResourceEntry::class, $entry);
        $this->assertEquals('t_users', $entry->getHandle());
    }

    function test__instantiates_entries_using_provided_model()
    {
        $resource = $this->create('t_entries',
            function (Blueprint $table, ResourceConfig $resource) {
                $table->increments('id');

                $resource->model(TestResourceEntry::class);
            });

        $entry = $resource->newEntryInstance();
        $this->assertInstanceOf(TestResourceEntry::class, $entry);
        $this->assertInstanceOf(TestResourceEntry::class, $resource->fake());
        $this->assertEquals('t_entries', $entry->getTable());
    }

    function test__get_creation_rules()
    {
        $users = $this->schema()->users();

        $this->assertEquals([
            'name'     => ['max:255', 'required'],
            'email'    => ['unique:t_users,email,NULL,id', 'required'],
            'bio'      => ['max:255', 'string', 'nullable'],
            'group_id' => ['required'],
            'age'      => ['integer', 'min:0', 'nullable'],
            'avatar'   => ['nullable'],
        ], $users->getRules());
    }

    function test__get_update_rules()
    {
        $users = $this->schema()->users();

        $user = $users->fake();

        $this->assertEquals([
            'name'     => ['max:255', 'sometimes', 'required'],
            'email'    => ['unique:t_users,email,'.$user->getId().',id', 'sometimes', 'required'],
            'bio'      => ['max:255', 'string', 'nullable'],
            'group_id' => ['sometimes', 'required'],
            'age'      => ['integer', 'min:0', 'nullable'],
            'avatar'   => ['nullable'],
        ], $users->getRules($user));
    }

    function test__rules_for_dynamically_added_fields()
    {
        $this->schema()->users();
        Resource::extend('t_users')
                ->with(function (Resource $resource) {
                    $resource->indexFields()->add(['type' => 'text', 'name' => 'isot']);
                });

        $users = sv_resource('t_users');

        $this->assertFalse(in_array('isot', array_keys($users->getRules())));
    }

    function test__count()
    {
        $res = $this->makeResource('t_items');
        $res->fake([], 3);

        $this->assertEquals(3, $res->count());
    }
}
