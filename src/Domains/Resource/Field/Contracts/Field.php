<?php

namespace SuperV\Platform\Domains\Resource\Field\Contracts;

use Closure;
use Illuminate\Http\Request;
use SuperV\Platform\Domains\Database\Model\Contracts\EntryContract;
use SuperV\Platform\Domains\Database\Model\Contracts\Watcher;
use SuperV\Platform\Domains\Resource\Field\Types\FieldType;

interface Field
{
    public function getName();

    public function getColumnName();

    public function getLabel(): string;

    public function setLabel(string $label): Field;

    public function getType();

    public function getValue();

    public function getConfig();

    public function getConfigValue($key, $default = null);

    public function setConfigValue($key, $value = null);

    public function setWatcher(Watcher $watcher);

    public function isHidden();

    public function isUnique();

    public function isRequired();

    public function isUnbound();

    public function doesNotInteractWithTable();

    public function hide();

    public function getAlterQueryCallback();

    public function getRules();

    public function getPlaceholder();

    public function fillFromEntry(EntryContract $entry);

    public function setCallback($trigger, $callback);

    public function getCallback($trigger);

    public function resolveRequest(Request $request, ?EntryContract $entry = null);

    public function resolveFromEntry($entry);

    public function resolveFieldType(): FieldType;

    public function getAccessor($for);

    public function getComposer($for);

    public function getPresenter($for);

    public function getMutator($for);

    public function setPresenter(Closure $callback);

    /**
     * Add a flag to hide the field on forms
     *
     * @return \SuperV\Platform\Domains\Resource\Field\Contracts\Field
     */
    public function hideOnForms(): Field;

    /**
     * Add a flag to show the field on index table
     *
     * @return \SuperV\Platform\Domains\Resource\Field\Contracts\Field
     */
    public function showOnIndex(): Field;

    /**
     * Generate filter from field and add to filters
     *
     * @return \SuperV\Platform\Domains\Resource\Field\Contracts\Field
     */
    public function copyToFilters(array $params = []): Field;

    /**
     * Set the display order for the field
     *
     * @param $order
     * @return \SuperV\Platform\Domains\Resource\Field\Contracts\Field
     */
    public function displayOrder($order): Field;

    /**
     * Add css class(es)
     *
     * @param string $class
     * @return \SuperV\Platform\Domains\Resource\Field\Contracts\Field
     */
    public function addClass(string $class): Field;

    /**
     * Add a boolean flag
     *
     * @param string $flag
     * @return \SuperV\Platform\Domains\Resource\Field\Contracts\Field
     */
    public function addFlag(string $flag): Field;

    /**
     * Determine if the field has given flag
     *
     * @param string $flag
     * @return bool
     */
    public function hasFlag(string $flag): bool;

    /**
     * Remove a flag from the field
     *
     * @param string $flag
     * @return \SuperV\Platform\Domains\Resource\Field\Contracts\Field
     */
    public function removeFlag(string $flag): Field;
}