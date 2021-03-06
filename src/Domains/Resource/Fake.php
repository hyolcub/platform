<?php

namespace SuperV\Platform\Domains\Resource;

use Faker\Generator;
use Illuminate\Http\UploadedFile;
use SuperV\Platform\Domains\Resource\Field\Contracts\Field;

class Fake
{
    /**
     * @var \SuperV\Platform\Domains\Resource\Resource
     */
    protected $resource;

    /**
     * @var array
     */
    protected $overrides;

    /** @var \Faker\Generator */
    protected $faker;

    /** @var array */
    protected $attributes = [];

    public function __construct()
    {
        $this->faker = app(Generator::class);
    }

    public function __invoke(Resource $resource, array $overrides = [])
    {
        $this->resource = $resource;
        $this->overrides = $overrides;

        $resource->getFields()
                 ->map(function (Field $field) {
                     if ($this->shouldFake($field)) {
                         $this->attributes[$field->getColumnName()] = $this->fake($field);
                     }
                 })->filter()
                 ->toAssoc()
                 ->all();

        return array_filter_null($this->attributes);
    }

    protected function shouldFake(Field $field)
    {
        return ! $field->isHidden()
            && ! $field->hasFlag('form.hide')
            && ! $field->doesNotInteractWithTable();
    }

    protected function fake(Field $field)
    {
        if ($value = array_get($this->overrides, $field->getColumnName())) {
            return $value;
        }
        if (method_exists($this, $method = camel_case('fake_'.$field->getType()))) {
            return $this->$method($field);
        }

        return $this->faker->text;
    }

    protected function fakeBelongsTo(Field $field)
    {
        $relatedResource = ResourceFactory::make($field->getConfigValue('related_resource'));

        if ($relatedResource->count() === 0) {
            if ($relatedResource->getHandle() === $this->resource->getHandle()) {
                return rand(1, 5); // otherwise causes dead recursionr
            } else {
                $relatedResource->fake([]);
            }
//            $relatedResource->fake([]);
        }

        return $relatedResource->newQuery()->inRandomOrder()->value('id');
    }

    protected function fakeFile()
    {
        return new UploadedFile(SV_TEST_BASE.'/__fixtures__/square.png', 'square.png');
    }

    protected function fakeText(Field $field)
    {
        $fieldName = $field->getName();

        if (in_array($fieldName, [
                'email',
                'currency_code',
                'first_name',
                'last_name']) && $fake = $this->faker->__get(camel_case($fieldName))) {
            return $fake;
        }

        if (in_array($fieldName, [
                'color',
                'domain']) && $fake = $this->faker->__get(camel_case($fieldName.'_name'))) {
            return $fake;
        }

        if (ends_with($fieldName, '_name') || $fieldName === 'name') {
            return $this->faker->name;
        }

        if (ends_with($fieldName, '_at')) {
            return $this->faker->dateTime;
        }

        if (ends_with($fieldName, '_phone') || $fieldName === 'phone' || $fieldName === 'cell') {
            return $this->faker->phoneNumber;
        }

        if (ends_with($fieldName, '_number') || $fieldName === 'number') {
            return $this->faker->randomNumber(8);
        }

        if (ends_with($fieldName, '_address') || $fieldName === 'address') {
            return $this->faker->address;
        }

        if (ends_with($fieldName, '_url') || $fieldName === 'website') {
            return $this->faker->url;
        }

        if (ends_with($fieldName, '_rate') || $fieldName === 'rate') {
            return $this->faker->randomFloat(2, 0.5, 10);
        }

        if (ends_with($fieldName, '_price') || $fieldName === 'price') {
            return $this->faker->randomFloat(2, 0.5, 100);
        }

        if ($fieldName === 'quantity' || $fieldName === 'qty' || $fieldName === 'count') {
            return $this->faker->randomNumber(2);
        }

        return $this->faker->word;
    }

    protected function fakeTextarea()
    {
        return $this->faker->text;
    }

    protected function fakeSelect(Field $field)
    {
        return array_random(wrap_array($field->getConfigValue('options')));
    }

    protected function fakeNumber(Field $field)
    {
        if ($field->getConfigValue('type') === 'decimal') {
            $max = $field->getConfigValue('total', 5) - 2;

            return $this->faker->randomFloat($field->getConfigValue('places', 2), 0, $max);
        }

        return $field->getName() === 'age' ? $this->faker->numberBetween(10, 99) : $this->faker->randomNumber();
    }

    protected function fakeEmail()
    {
        return $this->faker->safeEmail;
    }

    protected function fakeBoolean()
    {
        return $this->faker->boolean;
    }

    protected function fakeDatetime(Field $field)
    {
        return $field->getConfigValue('time') ? $this->faker->dateTime : $this->faker->date();
    }

    public static function field(Field $field)
    {
        return (new static)->fake($field);
    }

    public static function create(Resource $resource, array $overrides = [])
    {
        return $resource->create(static::make($resource, $overrides));
    }

    public static function make(Resource $resource, array $overrides = [])
    {
        return (new static())->__invoke($resource, $overrides);
    }
}