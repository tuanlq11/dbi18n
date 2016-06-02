<?php
namespace tuanlq11\dbi18n;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Support\Fluent;

/**
 * Created by Fallen.
 */
class I18NBlueprint extends Blueprint
{
    public    $i18n_table      = null;
    public    $i18n_code_field = "locale";
    protected $i18n_columns    = [];
    protected $i18n_primary    = [];

    /**
     * Parse origin command to human command
     * @param $originCommand
     * @return int|null|string
     */
    public function parseCommand($originCommand)
    {
        $mappings = [
            'create' => ['create'],
            'drop'   => ['drop', 'dropIfExists'],
        ];

        foreach ($mappings as $key => $mapping) {
            if (in_array($originCommand, $mapping)) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Return i18n table name
     * @return null
     */
    public function getI18NTableName()
    {
        if ($this->i18n_table) return $this->i18n_table;
        return sprintf("%s_i18ns", $this->table);
    }

    /**
     * @param $columns
     */
    protected function addCachePrimary($columns)
    {
        $columns = (array)$columns;
        foreach ($columns as $column) {
            /** @var Fluent $registerColumn */
            foreach ($this->columns as $registerColumn) {
                $newCol = clone  $registerColumn;
                if ($newCol->get('name') === $column) {
                    if ($newCol->__isset('autoIncrement')) $newCol->__unset('autoIncrement');
                    $this->i18n_primary[$column] = $newCol;
                }
            }
        }
    }

    /**
     * Store list primary to create i18n
     * @param array|string $columns
     * @param null $name
     * @return \Illuminate\Support\Fluent
     */
    public function primary($columns, $name = null)
    {
        $this->addCachePrimary($columns);
        return parent::primary($columns, $name);
    }

    /**
     * @param string $column
     * @return Fluent
     */
    public function increments($column)
    {
        $result = parent::increments($column);
        $this->addCachePrimary($column);
        return $result;
    }

    /**
     * @param string $column
     * @return Fluent
     */
    public function bigIncrements($column)
    {
        $result = parent::bigIncrements($column);
        $this->addCachePrimary($column);
        return $result;
    }

    /**
     * Overrride build function
     * @param Connection $connection
     * @param Grammar $grammar
     */
    public function build(Connection $connection, Grammar $grammar)
    {
        $command = $this->parseCommand($this->commands[0]->get('name'));
        $schema  = $connection->getSchemaBuilder();

        if ($command === "drop") {
            $schema->dropIfExists($this->getI18NTableName());
        }

        parent::build($connection, $grammar);

        if ($command === "create") {
            $primary      = $this->i18n_primary;
            $i18n_columns = $this->i18n_columns;
            $table_name   = $this->table;
            $schema->create($this->getI18NTableName(), function (Blueprint $table) use ($table_name, $primary, $i18n_columns) {
                foreach ($primary as $name => $col) {
                    $table->columns[] = $col;

                    $table->foreign($col->get('name'))->references($col->get('name'))->on($table_name)->onDelete('cascade');
                }

                foreach ($i18n_columns as $col) {
                    $table->columns[] = $col;
                }

                $table->string($this->i18n_code_field, 10);
                $table->primary(array_merge(array_keys($primary), [$this->i18n_code_field]));
            });
        }
    }

    /**
     * Create column fluent
     * @param $name
     * @param $type
     * @param array $parameters
     * @return Fluent
     */
    protected function createColumn($name, $type, array $parameters = [])
    {
        $attributes = array_merge(compact('type', 'name'), $parameters);

        $column = new Fluent($attributes);

        return $column;
    }

    /**
     * Add i18n text field
     * @param $name
     * @return \Illuminate\Support\Fluent
     */
    public function i18n_text($name)
    {
        $this->i18n_columns[] = $column = $this->createColumn($name, 'text');
        return $column;
    }

    /**
     * Add i18n string field
     * @param $name
     * @param int $length
     * @return \Illuminate\Support\Fluent
     */
    public function i18n_string($name, $length = 255)
    {
        $this->i18n_columns[] = $column = $this->createColumn($name, 'string', compact('length'));
        return $column;
    }

    /**
     * Add i18n char field
     * @param $name
     * @param int $length
     * @return \Illuminate\Support\Fluent
     */
    public function i18n_char($name, $length = 255)
    {
        $this->i18n_columns[] = $column = $this->createColumn($name, 'char', compact('length'));
        return $column;
    }
}