<?php

namespace SuperV\Platform\Domains\Resource\Jobs;

use SuperV\Platform\Domains\Addon\Events\AddonUninstallingEvent;
use SuperV\Platform\Domains\Resource\Nav\Section;
use SuperV\Platform\Domains\Resource\ResourceModel;

class DeleteAddonResources
{
    public function handle(AddonUninstallingEvent $event)
    {
        $addon = $event->addon;

        ResourceModel::query()
                     ->where('addon', $addon->slug())
                     ->get()->map->delete();

        Section::query()
               ->where('addon', $addon->slug())
               ->get()->map->delete();
    }
}