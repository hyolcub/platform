<?php

namespace SuperV\Platform\Http\Controllers;

use Current;
use SuperV\Platform\Domains\Resource\Field\FieldComposer;
use SuperV\Platform\Domains\Resource\Nav\Nav;
use SuperV\Platform\Domains\Resource\Nav\NavGuard;
use SuperV\Platform\Exceptions\PlatformException;

class DataController extends BaseApiController
{
    public function init()
    {
        $user = auth()->user();
        $userArray = $user->toArray();

        if ($user->profile) {
            $avatar = sv_resource('sv_profiles')->getField('avatar');

            $userArray['avatar_url'] = (new FieldComposer($avatar))
                ->forView($user->profile)
                ->get('image_url');

            $userArray['first_name'] = $user->profile->first_name;
            $userArray['last_name'] = $user->profile->last_name;
            $userArray['profile_id'] = $user->profile->id;
        }

        return [
            'data' => [
                'user' => $userArray,
            ],
        ];
    }

    public function nav()
    {
        if (! $portNav = Current::port()->getNavigationSlug()) {
            PlatformException::fail('Current port has no navigation');
        }

//        $nav = Nav::get($portNav)->compose();

        $nav = (new NavGuard(auth()->user(), Nav::get('acp')))->compose();

        return [
            'data' => [
                'nav' => $nav,
            ],
        ];
    }
}