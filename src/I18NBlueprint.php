<?php
namespace tuanlq11\dbi18n;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\Grammar;

/**
 * Created by Fallen.
 */
class I18NBlueprint extends Blueprint
{
    public    $i18n_table   = null;
    protected $i18n_columns = [];
    protected $i18n_primary = [];

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
        return sprintf("%s_i18n", $this->table);
    }

    /**
     * Store list primary to create i18n
     * @param array|string $columns
     * @param null $name
     * @return \Illuminate\Support\Fluent
     */
    public function primary($columns, $name = null)
    {
        $columns = (array)$columns;
        foreach ($columns as $column) {
            foreach ($this->columns as $regCol) {
                if ($regCol->get('name') === $column) {
                    $this->i18n_primary[$column] = $regCol;
                }
            }
        }
        return parent::primary($columns, $name);
    }


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

                $table->string('lang_code', 10);
                $table->primary(array_merge(array_keys($primary), ['lang_code']));
            });
        }
    }


    /**
     * Add i18n text field
     * @param $name
     * @return \Illuminate\Support\Fluent
     */
    public function i18n_text($name)
    {
        $this->i18n_columns[] = $column = $this->text($name);
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
        $this->i18n_columns[] = $column = $this->string($name, $length);
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
        $this->i18n_columns[] = $column = $this->char($name, $length);
        return $column;
    }
}