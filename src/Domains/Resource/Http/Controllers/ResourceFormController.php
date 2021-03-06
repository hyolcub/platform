<?php

namespace SuperV\Platform\Domains\Resource\Http\Controllers;

use SuperV\Platform\Domains\Resource\Form\Form;
use SuperV\Platform\Domains\Resource\Http\ResolvesResource;
use SuperV\Platform\Domains\UI\Page\Page;
use SuperV\Platform\Http\Controllers\BaseApiController;

class ResourceFormController extends BaseApiController
{
    use ResolvesResource;

    public function create()
    {
        $resource = $this->resolveResource();

        $form = Form::for($resource)
                    ->setUrl($resource->route('store'))
                    ->setRequest($this->request)
                    ->make();

        if ($callback = $resource->getCallback('creating')) {
            app()->call($callback, ['form' => $form]);
        }

        $page = Page::make('Create new '.$resource->getSingularLabel());
        $page->addBlock($form);

        return $page->build();
    }

    public function store()
    {
        $resource = $this->resolveResource();

        return Form::for($this->entry ?? $resource)
                   ->setRequest($this->request)
                   ->make()
                   ->save();
    }

    public function edit()
    {
        $resource = $this->resolveResource();
        $form = Form::for($this->entry)
                    ->setUrl($resource->route('update', $this->entry))
                    ->setRequest($this->request)
                    ->make();

        if ($callback = $resource->getCallback('editing')) {
            app()->call($callback, ['form' => $form]);
        }

        return $form->makeComponent();
    }

    public function update()
    {
        $this->resolveResource();

        return Form::for($this->entry)
                   ->setRequest($this->request)
                   ->make()
                   ->save();
    }
}