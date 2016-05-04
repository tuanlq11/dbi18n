<?php
namespace tuanlq11\dbi18n;

use Illuminate\Database\Eloquent\Model;

/**
 * Created by Fallen.
 */
class I18NDBTrait extends Model
{
    /** @var null|string */
    protected $i18n_table = null;
    /** @var array */
    protected $i18n_columns = [];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function i18n_relation()
    {
        return $this->hasMany($this->getI18NTableName(), $this->primaryKey, $this->primaryKey);
    }

    /**
     * @return null|string
     */
    public function getI18NTableName()
    {
        if ($this->i18n_table) return $this->i18n_table;

        $this->i18n_table = $table = sprintf("%s_i18n", $this->table);

        return $table;
    }

//    /**
//     * @param $column
//     */
//    public function translation($column, $locale)
//    {
//        $relation = $this->hasMany()
//    }
//
    public function bootI18NDBTrait()
    {
        $this->addGlobalScope(new I18NScope());
    }
}