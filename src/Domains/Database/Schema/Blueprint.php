<?php

namespace SuperV\Platform\Domains\Database\Schema;

use Closure;
use Current;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint as LaravelBlueprint;
use Illuminate\Database\Schema\Grammars\Grammar;
use SuperV\Platform\Domains\Database\Events\ColumnCreatedEvent;
use SuperV\Platform\Domains\Database\Events\ColumnDroppedEvent;
use SuperV\Platform\Domains\Database\Events\ColumnUpdatedEvent;
use SuperV\Platform\Domains\Database\Events\TableCreatedEvent;
use SuperV\Platform\Domains\Database\Events\TableCreatingEvent;
use SuperV\Platform\Domains\Database\Events\TableDroppedEvent;
use SuperV\Platform\Domains\Resource\ResourceConfig;

class Blueprint extends LaravelBlueprint
{
    use CreatesRelations;
    use CreatesFields;

    /**
     * @var \SuperV\Platform\Domains\Database\Schema\Schema
     */
    protected $builder;

    /** @var \Illuminate\Support\Collection|\SuperV\Platform\Domains\Database\Schema\ColumnDefinition[] */
    protected $columns = [];

    /** @var \Illuminate\Support\Collection|\Closure[] */
    protected $postBuildCallbacks;

    public function __construct(string $table, ?\Closure $callback = null, Schema $builder = null)
    {
        parent::__construct($table, $callback);

        $this->builder = $builder;
    }

    public function addColumn($type, $name, array $parameters = []): ColumnDefinition
    {
        // Here, while adding a column let's pass along
        // the resource blueprint to each column
        $this->columns[] = $column = new ColumnDefinition(
            $this->builder ? $this->builder->resource() : new \SuperV\Platform\Domains\Resource\ResourceConfig,
            array_merge(compact('type', 'name'), $parameters)
        );

        return $column;
    }

    public function getColumn($name): ColumnDefinition
    {
        return collect($this->columns)->first(function($column) use ($name) {
            return $column->name ===  $name;
        });
    }

    public function build(Connection $connection, Grammar $grammar)
    {
        if ($this->dropping()) {
            if (! $this->builder->justRun) {
                parent::build($connection, $grammar);
            }

            TableDroppedEvent::dispatch($this->tableName());

            return;
        }

        $this->postBuildCallbacks = collect();
        $this->columns = collect($this->columns)->keyBy('name');

        if ($this->creating()) {
            TableCreatingEvent::dispatch($this->tableName(), $this->columns, $this->resourceConfig(), Current::migrationScope());
        } else {
            $this->runDropOperations();
        }

        $this->columns = $this->columns->map(
            function (ColumnDefinition $column) {
                if ($column->change) {
                    ColumnUpdatedEvent::dispatch($this->tableName(), $this, $column);
                } else {
                    ColumnCreatedEvent::dispatch($this->tableName(), $this, $column, $this->resourceConfig()->model);
                }

                return $column->ignore ? null : $column;
            }
        )->filter()->all();

        $this->applyPostBuildCallbacks();

        if (! $this->builder->justRun) {
            parent::build($connection, $grammar);
        }

        if ($this->creating()) {
            TableCreatedEvent::dispatch($this->tableName(), $this->columns);
        }
    }

    /**
     * Specify an index for the table.
     *
     * @param  string|array $columns
     * @param  string       $name
     * @param  string|null  $algorithm
     * @return \Illuminate\Support\Fluent
     */
    public function index($columns, $name = null, $algorithm = null)
    {
        $indexName = $name ?? md5(uniqid());

        return $this->indexCommand('index', $columns, $indexName, $algorithm);
    }

    public function tableName()
    {
        return $this->table;
    }

    public function dropping()
    {
        return collect($this->commands)->contains(function ($command) {
            return $command->name == 'drop' || $command->name == 'dropIfExists';
        });
    }

    public function getColumnNames(): array
    {
        return sv_collect($this->getColumns())->pluck('name')->all();
    }

    public function resourceConfig(): ResourceConfig
    {
        return $this->builder->resource();
    }

    public function runDropOperations(): void
    {
        foreach ($this->commands as $command) {
            if ($command->name === 'dropColumn') {
                sv_collect($command->columns)->map(function ($column) {
                    ColumnDroppedEvent::dispatch($this->tableName(), $column);
                });
            }
        }
    }

    public function getPostBuildCallbacks()
    {
        return $this->postBuildCallbacks;
    }

    public function applyPostBuildCallbacks()
    {
        $this->postBuildCallbacks->map(function (Closure $callback) {
            $callback($this);
        });
    }

    public function addPostBuildCallback(Closure $callback)
    {
        $this->postBuildCallbacks[] = $callback;

        return $this;
    }
}