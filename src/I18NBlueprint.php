<?php
namespace tuanlq11\dbi18n;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\Grammar;

/**
 * Created by PhpStorm.
 * User: arch
 * Date: 5/4/16
 * Time: 7:20 AM
 */
class I18NBlueprint extends Blueprint
{
    public    $i18n_table   = null;
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
            $primary = $this->i18n_primary;
            $schema->create($this->getI18NTableName(), function (Blueprint $table) use ($primary) {
                foreach ($primary as $name => $col) {
                    $table->columns[] = $col;
                }

                $table->primary(array_keys($primary));
            });
        }
    }


    public function i18n()
    {

    }
}