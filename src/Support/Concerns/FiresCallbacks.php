<?php

namespace SuperV\Platform\Support\Concerns;

use Closure;

trait FiresCallbacks
{
    protected $callbacks = [];

    public function setCallback($trigger, ?Closure $callback)
    {
        return $this->on($trigger, $callback);
    }

    public function on($trigger, ?Closure $callback)
    {
        if (is_null($callback)) { // to avoid ifs in parent
            return $this;
        }

        if (! isset($this->callbacks[$trigger])) {
            $this->callbacks[$trigger] = [];
        }

        $this->callbacks[$trigger][] = $callback;

        return $this;
    }

    public function fire($trigger, array $parameters = [])
    {
        $method = camel_case('on_'.$trigger);

        // Call onQuerying..
        //
        if (method_exists($this, $method)) {
            app()->call([$this, $method], $parameters);
        }

        foreach (array_get($this->callbacks, $trigger, []) as $callback) {
            //  Class:class or Closure
            //
            if (is_string($callback) || $callback instanceof \Closure) {
                app()->call($callback, $parameters);
            }

            // Class:class@handle
            //
            if (method_exists($callback, 'handle')) {
                app()->call([$callback, 'handle'], $parameters);
            }
        }

        return $this;
    }

    public function mergeCallbacks(array $callbacks)
    {
        $this->callbacks = array_merge($this->callbacks, $callbacks);
    }

    public function hasCallback($trigger)
    {
        return isset($this->callbacks[$trigger]);
    }

    public function getCallback($trigger): ?Closure
    {
        if (! $this->hasCallback($trigger)) {
            return null;
        }

        return $this->callbacks[$trigger][0];
    }

    public function getCallbacks(): array
    {
        return $this->callbacks;
    }
}
