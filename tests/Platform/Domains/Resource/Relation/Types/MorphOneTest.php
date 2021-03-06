<?php

namespace Tests\Platform\Domains\Resource\Relation\Types;

use SuperV\Platform\Domains\Database\Model\Contracts\Watcher;
use SuperV\Platform\Domains\Database\Model\Entry;
use SuperV\Platform\Domains\Database\Model\Repository;
use SuperV\Platform\Domains\Database\Schema\Blueprint;
use SuperV\Platform\Domains\Resource\Contracts\AcceptsParentEntry;
use SuperV\Platform\Domains\Resource\Contracts\ProvidesForm;
use SuperV\Platform\Domains\Resource\Form\Form;
use SuperV\Platform\Domains\Resource\ResourceConfig;
use SuperV\Platform\Domains\Resource\Testing\FormTester;
use Tests\Platform\Domains\Resource\ResourceTestCase;

class MorphOneTest extends ResourceTestCase
{
    /** @var \SuperV\Platform\Domains\Resource\Resource */
    protected $parent;

    /** @var \SuperV\Platform\Domains\Resource\Resource */
    protected $related;

    protected function setUp()
    {
        parent::setUp();

        $this->parent = $this->create('t_users', function (Blueprint $table, ResourceConfig $resource) {
            $resource->resourceKey('user');

            $table->increments('id');
            $table->string('name');
            $table->morphOne('t_tags', 'tag', 'owner');
            $table->morphOne('t_tacs', 'tac', 'owner');
            $table->morphOne('t_profiles', 'profile', 'owner', TestProfileRepository::class);
        });

        $this->related = $this->create('t_tags', function (Blueprint $table) {
            $table->increments('id');
            $table->string('label');
            $table->morphTo('owner');
        });

        $this->create('t_tacs', function (Blueprint $table, ResourceConfig $resource) {
            $resource->model(TestTac::class);

            $table->increments('id');
            $table->string('label');
            $table->morphTo('owner');
        });
    }

    /** @test */
    function create_morph_one_relation()
    {
        $this->assertColumnDoesNotExist('t_users', 'address');
        $this->assertColumnDoesNotExist('t_users', 'address_id');

        $relation = $this->parent->getRelation('tag');
        $this->assertEquals('morph_one', $relation->getType());
        $this->assertEquals([
            'related_resource' => 't_tags',
            'morph_name'       => 'owner',
        ], $relation->getRelationConfig()->toArray());
    }

    function test__makes_form()
    {
        $user = $this->parent->fake();

        $tag = $user->tag()->make(['label' => 'blue']);
        $this->assertEquals($user->getId(), $tag->owner_id);
        $this->assertEquals($user->getHandle(), $tag->owner_type);

        /** @var \SuperV\Platform\Domains\Resource\Relation\Types\MorphOne $relation */
        $relation = $this->parent->getRelation('tag');
        $this->assertInstanceOf(ProvidesForm::class, $relation);
        $this->assertInstanceOf(AcceptsParentEntry::class, $relation);
        $relation->acceptParentEntry($user);

        /** @var Form $form */
        $form = $relation->makeForm();
        $this->assertInstanceOf(Form::class, $form);
        $this->assertNull($form->getField('user'));
        $this->assertNull($form->composeField('label')->get('value'));

        $relatedEntry = $form->getEntry();
        $this->assertEquals($user->getId(), $relatedEntry->owner_id);
        $this->assertEquals($user->getHandle(), $relatedEntry->owner_type);

        $this->withoutExceptionHandling();
        (new FormTester($this->basePath()))->test($form);
    }

    function test__makes_form_custom_model()
    {
        $user = $this->parent->fake();
        $relationQuery = $user->tac();

        $tac = $relationQuery->make(['label' => 'blue']);
        $this->assertInstanceOf(TestTac::class, $tac);

        /** @var \SuperV\Platform\Domains\Resource\Relation\Types\MorphOne $relation */
        $relation = $this->parent->getRelation('tac');
        $relation->acceptParentEntry($user);

        /** @var Form $form */
        $form = $relation->makeForm();
        $relatedEntry = $form->getEntry();
        $this->assertInstanceOf(TestTac::class, $relatedEntry);

        $this->assertEquals($user->getId(), $relatedEntry->owner_id);
        $this->assertEquals($user->getHandle(), $relatedEntry->owner_type);

        $this->withoutExceptionHandling();
        (new FormTester($this->basePath()))->test($form);
    }

    /** @test */
    function return_none_eloquent_model_if_provided()
    {
        $this->create('t_profiles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->morphTo('owner');
        });

        $this->assertEquals(TestProfileRepository::class, $this->parent->getRelation('profile')->getRelationConfig()->getTargetModel());

        $user = $this->parent->create(['name' => 'some']);

        $user->profile()->make(['title' => 'Admin'])->save();

        $profile = $user->fresh()->getProfile();

        $this->assertInstanceOf(TestProfile::class, $profile);

        $this->assertEquals('Admin', $profile->entry->title);
        $this->assertEquals($user->getId(), $profile->entry->owner_id);
        $this->assertEquals($user->getMorphClass(), $profile->entry->owner_type);
    }
}

class TestTac extends Entry implements Watcher
{
    protected $table = 't_tacs';

    public $timestamps = false;
}

class TestProfile
{
    public $entry;

    public function __construct($entry)
    {
        $this->entry = $entry;
    }
}

class TestProfileRepository implements Repository
{
    public function make($entry, $owner)
    {
//        return new TestProfile($entry);
    }

    public function resolve($entry, $owner)
    {
        return new TestProfile($entry);
    }
}



























