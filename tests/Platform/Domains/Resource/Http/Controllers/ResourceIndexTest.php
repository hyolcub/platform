<?php

namespace Tests\Platform\Domains\Resource\Http\Controllers;

use SuperV\Platform\Domains\Database\Model\Contracts\EntryContract;
use SuperV\Platform\Domains\Database\Schema\Blueprint;
use SuperV\Platform\Domains\Resource\Extension\Extension;
use SuperV\Platform\Domains\Resource\Filter\SearchFilter;
use SuperV\Platform\Domains\Resource\Resource;
use Tests\Platform\Domains\Resource\Fixtures\HelperComponent;
use Tests\Platform\Domains\Resource\ResourceTestCase;

class ResourceIndexTest extends ResourceTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->withoutExceptionHandling();
    }

    protected function tearDown()
    {
        parent::tearDown();

        Extension::flush();
    }

    function test__bsmllh()
    {
        $users = $this->schema()->users();

        $page = $this->getPageFromUrl($users->route('index'));
        $table = HelperComponent::from($page->getProp('blocks.0'));

        $this->assertEquals('sv-loader', $table->getName());
        $this->assertEquals(sv_url($users->route('index.table')), $table->getProp('url'));
    }

    function test__index_table_config()
    {
        $users = $this->schema()->users();

        $response = $this->getJsonUser($users->route('index.table'))->assertOk();
        $table = HelperComponent::from($response->decodeResponseJson('data'));

        $this->assertEquals(sv_url($users->route('index.table').'/data'), $table->getProp('config.data_url'));

        $fields = $table->getProp('config.fields');
        $this->assertEquals(3, count($fields));
        foreach ($fields as $key => $field) {
            $this->assertTrue(is_numeric($key));
            $this->assertEquals([
                'uuid',
                'name',
                'label',
                'classes',
            ], array_keys($field));

            $rowActions = $table->getProp('config.row_actions');
            $this->assertEquals(1, count($rowActions));

            $this->assertEquals('view', $rowActions[0]['props']['name']);
            $this->assertEquals('View', $rowActions[0]['props']['title']);
            $this->assertEquals('sv/res/t_users/{entry.id}', $rowActions[0]['props']['url']);
            $this->assertEquals('View', $rowActions[0]['props']['button']['title']);
        }
    }

    function test__index_table_data()
    {
        $this->withoutExceptionHandling();
        $users = $this->schema()->users();
        $userB = $users->fake(['group_id' => 2]);
        $userA = $users->fake(['group_id' => 1]);

        $rows = $this->getTableRowsOfResource($users);
        $this->assertEquals(2, count($rows));

        $rowA = $rows[0];
        $this->assertEquals($userA->getId(), $rowA['id']);

        $label = $rowA['fields'][0];
        $this->assertEquals('text', $label['type']);
        $this->assertEquals('label', $label['name']);
        $this->assertEquals($users->getEntryLabel($userA), $label['value']);

        $age = $rowA['fields'][1];
        $this->assertEquals('number', $age['type']);
        $this->assertEquals('age', $age['name']);
        $this->assertSame((int)$userA->age, $age['value']);

        $group = $rowA['fields'][2];
        $this->assertEquals('belongs_to', $group['type']);
        $this->assertEquals('group_id', $group['name']);
        $this->assertNull(array_get($group, 'meta.options'));

        $groups = sv_resource('t_groups');
        $usersGroup = $groups->find(1);
        $this->assertSame($usersGroup->title, $group['value']);
        $this->assertEquals($groups->route('view.page', $usersGroup), $group['meta']['link']);
    }

    function test__fields_extending()
    {
        Resource::extend('t_posts')->with(function (Resource $resource) {
            $resource->getField('user')
                     ->showOnIndex()
                     ->setCallback('table.presenting', function (EntryContract $entry) {
                         return $entry->user->email;
                     });
        });

        $this->withExceptionHandling();
        $users = $this->schema()->users();
        $userA = $users->fake(['group_id' => 1]);

        $posts = $this->schema()->posts();
        $posts->fake(['user_id' => $userA->getId()]);
        $posts->fake(['user_id' => $userA->getId()]);

        $row = $this->getJsonUser($posts->route('index.table').'/data')->decodeResponseJson('data.rows.0');

        $fields = collect($row['fields'])->keyBy('name');
        $this->assertEquals($userA->email, $fields->get('user_id')['value']);
    }

    function test__filters_in_table_config()
    {
        Resource::extend('t_users')->with(function (Resource $resource) {
            $resource->addFilter(new SearchFilter);
        });

        $table = $this->getTableConfigOfResource($this->schema()->users());

        $filter = $table->getProp('config.filters.0');
        $this->assertEquals('search', $filter['name']);
        $this->assertEquals('text', $filter['type']);
    }

    function test__filters_apply()
    {
        $users = $this->schema()->users();
        Resource::extend('t_users')->with(function (Resource $resource) {
            $resource->searchable(['name']);
        });
        $users->fake(['name' => 'none']);
        $users->fake(['name' => 'yks']);
        $users->fake(['name' => 'done']);

        $this->assertEquals(1, count($this->getTableRowsOfResource($users, 'filters='.base64_encode(json_encode(['search' => 'yks'])))));
    }

    function test__filters_apply_on_relations()
    {
        $users = $this->schema()->users();
        Resource::extend('t_users')->with(function (Resource $resource) {
            $resource->searchable(['group.title']);
        });
        $group = sv_resource('t_groups')->create(['title' => 'Ottomans']);
        $users->fake(['group_id' => $group->getId()], 2);
        $users->fake(['group_id' => 99], 3);

        $this->assertEquals(2, count($this->getTableRowsOfResource($users, 'filters='.base64_encode(json_encode(['search' => 'ttoma'])))));
    }

    function test__builds_search_filter_from_searchable_fields()
    {
        $users = $this->schema()->users(function (Blueprint $table) {
            $table->getColumn('name')->searchable();
        });
        $users->fake(['name' => 'none']);
        $users->fake(['name' => 'yks']);
        $users->fake(['name' => 'done']);

        $this->assertEquals(2, count($this->getTableRowsOfResource($users, 'filters='.base64_encode(json_encode(['search' => 'one'])))));
    }

    function test__builds__filter_from_relation_fields_config()
    {
        $this->withoutExceptionHandling();

        $users = $this->schema()->users();

        Resource::extend('t_users')->with(function (Resource $resource) {
            $resource->getRelation('group')->addFlag('filter');
        });
        $group = sv_resource('t_groups')->create(['title' => 'Ottomans']);
        $users->fake(['group_id' => $group->getId()], 2);
        $users->fake(['group_id' => 999], 3);

        $table = $this->getTableConfigOfResource($users);

        $filter = $table->getProp('config.filters.0');
        $this->assertEquals('group', $filter['name']);
        $this->assertEquals('select', $filter['type']);
        $this->assertEquals(
            sv_resource('t_groups')->testHelper()->asOptions(),
            $table->getProp('config.filters.0.meta.options')
        );
    }

    function test__builds__filter_from_relation_fields_data()
    {
        // dont merge with above. consecutive getJson
        // calls looses query on the second one
        //
        $this->withoutExceptionHandling();
        $users = $this->schema()->users();
        Resource::extend('t_users')->with(function (Resource $resource) {
            $resource->getRelation('group')->addFlag('filter');
        });
        $group = sv_resource('t_groups')->create(['title' => 'Ottomans']);
        $users->fake(['group_id' => $group->getId()], 2);
        $users->fake(['group_id' => 999], 3);

        $this->assertEquals(2, count($this->getTableRowsOfResource($users, 'filters='.base64_encode(json_encode(['group' => $group->getId()])))));
    }

    function test__builds__filter_from_select_fields()
    {
        $users = $this->schema()->users(function (Blueprint $table) {
            $table->select('gender')->options(['m' => 'Male', 'f' => 'Female'])->addFlag('filter');
        });

        $table = $this->getTableConfigOfResource($users);

        $filter = $table->getProp('config.filters.0');
        $this->assertEquals('gender', $filter['name']);
        $this->assertEquals('select', $filter['type']);
        $this->assertEquals([
                ['value' => 'm', 'text' => 'Male'],
                ['value' => 'f', 'text' => 'Female']
            ], $table->getProp('config.filters.0.meta.options')
        );
        $this->assertEquals('Select Gender', $filter['placeholder']);
    }

    function test__builds__select_filter_from_text_fields_with_distinct_options()
    {
        $this->withoutExceptionHandling();
        $users = $this->schema()->users();
        Resource::extend('t_users')->with(function (Resource $resource) {
            $resource->getField('name')->addFlag('filter');
        });
        $users->fake(['name' => 'tic'], 2);
        $users->fake(['name' => 'tac'], 3);
        $users->fake(['name' => 'toe']);

        $table = $this->getTableConfigOfResource($users);

        $filter = $table->getProp('config.filters.0');
        $this->assertEquals('name', $filter['name']);
        $this->assertEquals('select', $filter['type']);
        $this->assertEquals(
            [
                ['value' => 'tic', 'text' => 'tic'],
                ['value' => 'tac', 'text' => 'tac'],
                ['value' => 'toe', 'text' => 'toe']
            ],
            $table->getProp('config.filters.0.meta.options')
        );
        $this->assertEquals('Select Name', $filter['placeholder']);
    }
}



