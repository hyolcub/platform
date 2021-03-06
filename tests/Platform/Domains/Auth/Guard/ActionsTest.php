<?php

namespace Tests\Platform\Domains\Auth\Guard;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use SuperV\Platform\Domains\Auth\Access\Role;
use SuperV\Platform\Domains\Auth\User;
use Tests\Platform\TestCase;

class ActionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    function assigns_roles_to_user()
    {
        $user = $this->newUser();

        $this->assertTrue($user->isA('user'));
        $this->assertTrue($user->isAn('user'));
        $this->assertTrue($user->isNotA('admin'));
        $this->assertTrue($user->isNotAn('admin'));
    }

    /** @test */
    function add_actions_to_role()
    {
        $user = $this->newUser();

        /** @var \SuperV\Platform\Domains\Auth\User $user */
        $admin = factory(User::class)->create();
        $admin->assign('admin');

        Role::withSlug('admin')->allow('*');
        Role::withSlug('user')->allow('user.action');

        $this->assertTrue($user->cannot('admin.action'));
        $this->assertTrue($user->can('user.action'));

        $this->assertTrue($admin->can('admin.action'));
        $this->assertTrue($admin->can('user.action'));
    }

    /** @test */
    function allow_user_for_actions()
    {
        /** @var \SuperV\Platform\Domains\Auth\User $user */
        $user = $this->newUser();

        $user->allow('user.action');

        $this->assertTrue($user->can('user.action'));
    }

    /** @test */
    function forbid_user_from_actions()
    {
        /** @var \SuperV\Platform\Domains\Auth\User $user */
        $user = $this->newUser();

        $user->allow('*');
        $user->forbid('forbidden.action');

        $this->assertTrue($user->cannot('forbidden.action'));
    }

    /** @test */
    function supports_wildcard_actions()
    {
        $userA = $this->newUser();
        $userA->allow('moduleA.*');

        $userB = $this->newUser();
        $userB->allow('moduleB.*');

        $this->assertTrue($userA->can('moduleA.read'));
        $this->assertTrue($userA->can('moduleA.write'));
        $this->assertTrue($userB->cannot('moduleA.read'));
        $this->assertTrue($userB->cannot('moduleA.write'));

        $this->assertTrue($userB->can('moduleB.read'));
        $this->assertTrue($userB->can('moduleB.write'));
        $this->assertTrue($userA->cannot('moduleB.read'));
        $this->assertTrue($userA->cannot('moduleB.write'));
    }

    /** @test */
    function forbid_precedes_over_wildcard()
    {
        $user = $this->newUser();
        $user->allow('*');

        $user->forbid('smoke');
        $this->assertFalse($user->can('smoke'));

        $user->assign('admin');
        Role::withSlug('admin')->allow('*');
        $this->assertFalse($user->can('smoke'));
    }

    /** @test */
    function user_has_the_assigned_role()
    {
        $this->newUser();

        $roles = ['user'];

        $roles = Role::query()->whereIn('slug', $roles)->pluck('id')->all();

        $this->assertTrue(User::query()->whereHas('roles', function (Builder $query) use ($roles) {
            $query->whereIn('role_id', $roles);
        })->exists());
    }
}


















