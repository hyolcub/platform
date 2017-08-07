<?php namespace SuperV\Platform\Domains\UI\Button;

class ButtonRegistry
{
    protected $buttons = [
        'view' => [
            'text' => 'View',
            'icon' => 'fa fa-eye',
            'type' => 'info',
        ],
    ];

    public function get($button)
    {
        if (!$button) {
            return null;
        }

        return array_get($this->buttons, $button);
    }
}