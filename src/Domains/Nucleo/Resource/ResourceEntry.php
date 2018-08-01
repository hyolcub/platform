<?php

namespace SuperV\Platform\Domains\Nucleo\Resource;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;
use SuperV\Platform\Domains\Nucleo\Prototype;

class ResourceEntry extends Model
{
    protected $guarded = [];

    protected $hasUUID;

    protected $onCreate;

    protected static function boot()
    {
        $instance = new static;

        $class = get_class($instance);
        $events = $instance->getObservableEvents();
        $observer = $class.'Observer';
        $observing = class_exists($observer);

        if ($events && $observing) {
            self::observe(app($observer));
        }

        if ($events && ! $observing) {
            self::observe(ResourceEntryObserver::class);
        }

        if ($instance->hasUUID) {
            $instance->incrementing = false;
            static::creating(function (Model $model) {
                if (! isset($model->attributes[$model->getKeyName()])) {
                    $model->incrementing = false;
                    $uuid = Uuid::uuid4();
                    $model->attributes[$model->getKeyName()] = str_replace('-', '', $uuid->toString());
                }
            });
        }

        parent::boot();
    }

    public function getId()
    {
        return $this->getKey();
    }

    public function compose()
    {
        return $this->toArray();
    }

    public function prototype()
    {
        return Prototype::where('slug', $this->getTable())->first();
    }

    /**
     * @param Closure $callback
     * @return \SuperV\Platform\Domains\Nucleo\Resource\ResourceEntry
     */
    public function onCreate(Closure $callback)
    {
        $this->onCreate = $callback;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getOnCreateCallback()
    {
        return $this->onCreate;
    }

}