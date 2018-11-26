<?php

namespace SuperV\Platform\Domains\UI\Components;

use Illuminate\Contracts\Support\Responsable;
use SuperV\Platform\Domains\UI\Components\Concerns\StyleHelper;
use SuperV\Platform\Support\Composer\Composable;
use SuperV\Platform\Support\Composer\Composition;
use SuperV\Platform\Support\Concerns\FiresCallbacks;

abstract class BaseComponent implements ComponentContract, Composable
{
    use FiresCallbacks;
    use StyleHelper;

    protected $props;

    protected $name;

    protected $uuid;

    protected $classes = [];

    abstract public function getName(): string;

    public function __construct(array $props = [])
    {
        $this->setProps($props);
    }

    public function addClass(string $class)
    {
        $this->classes[] = $class;

        return $this;
    }

    public function uuid()
    {
        return $this->uuid;
    }

    public function getProps(): Props
    {
        return $this->props;
    }

    public function setProps($props)
    {
        $this->props = new Props($props);

        return $this;
    }

    public function compose(\SuperV\Platform\Support\Composer\Tokens $tokens = null)
    {
        $composition = new Composition([
            'component' => $this->getName(),
            'uuid'      => $this->uuid(),
            'props'     => $this->getProps(),
            'classes'   => implode(' ', $this->getClasses()),
        ]);

        $this->fire('composed', ['composition' => $composition]);

        return $composition->toArray();
    }

    public function getHandle(): string
    {
        return 'cmp';
    }

    public function getClasses(): array
    {
        return $this->classes;
    }

    public function getProp($key)
    {
        return $this->props->get($key);
    }

    public function setProp($key, $value): self
    {
        $this->props->set($key, $value);

        return $this;
    }

    /** return @static */
    public static function make($name = '')
    {
        $static = new static;
        if ($name) {
            $static->name = $name;
        }

        return $static;
    }
}