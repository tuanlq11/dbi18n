<?php
namespace tuanlq11\dbi18n;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Psy\Util\Str;

/**
 * Created by Fallen.
 *
 * @property string primaryKey
 * @property string table
 * @property string i18n_primary
 * @property string i18n_code_field
 * @property string i18n_table
 * @property string i18n_columns
 * @property string i18n_attribute_name
 * @property string default_locale
 */
trait I18NDBTrait
{
    public static $I18N = true;
    // protected     $i18n_attribute_name = "i18n";
    // protected     $default_locale      = "en";
    /**
     * Store i18n data, before save
     *
     * @var array
     */
    protected $i18n_columns_data = [];

    /**
     * I18NDBTrait constructor.
     *
     * @var $attributes array
     */
    public function __construct($attributes = [])
    {
        parent::__construct();

        $this->saving(function ($model) {
            $model->filterI18NColumn();
        });

        $this->saved(function ($model) {
            $model->saveI18N();
        });
    }

    /**
     * Add i18n data to variable
     *
     * @param $data
     * @param $locale
     */
    public function addI18NData($data, $locale)
    {
        $this->i18n_columns_data[$locale] = $data;
    }

    /**
     * Store I18N to database
     */
    public function saveI18N()
    {
        if (empty($this->i18n_columns_data)) return;
        $this->i18n_relation()->getQuery()->whereIn($this->getI18NCodeField(), array_keys($this->i18n_columns_data))->delete();
        foreach ($this->i18n_columns_data as $locale => $data) {
            $obj                             = new $this->i18n_class();
            $data[$this->i18n_primary]       = $this->id;
            $data[$this->getI18NCodeField()] = $locale;
            $obj->create($data);
        }
    }

    /**
     * Filter & remove i18n column from attributes.
     * Mean, is not save to model self.
     */
    public function filterI18NColumn()
    {
        if (!isset($this->attributes[$this->i18n_attribute_name])) return;
        $this->i18n_columns_data = $this->attributes[$this->i18n_attribute_name];
        unset($this->attributes[$this->i18n_attribute_name]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function i18n_relation()
    {
        return $this->hasMany($this->i18n_class, $this->i18n_primary, $this->primaryKey);
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

    /**
     * Return column name of i18n code
     *
     * @return string
     */
    protected function getI18NCodeField()
    {
        return isset($this->i18n_code_field) ? $this->i18n_code_field : 'locale';
    }

    /**
     * @param $query  Builder
     * @param $locale string|null
     *
     * @return  Builder
     */
    public function scopeI18N($query, $locale = null)
    {
        $i18nTranAlias = "i18n_translation";
        $i18nAlias     = "i18n";
        $primary       = $this->primaryKey;
        $i18nPrimary   = $this->i18n_primary;
        $table         = $this->table;

        if ($locale) {
            $query->leftJoin(\DB::raw("
            (
                SELECT {$i18nTranAlias}.*
                FROM {$this->i18n_table} as {$i18nTranAlias}
                WHERE {$i18nTranAlias}.{$this->getI18NCodeField()} = '{$locale}'
            ) as {$i18nAlias}"
            ), function ($join) use ($i18nAlias, $primary, $i18nPrimary, $table) {
                $join->on("{$i18nAlias}.{$i18nPrimary}", "=", "{$table}.{$primary}");
            });
        } else {
            $query->leftJoin("{$this->i18n_table} as {$i18nAlias}", "{$i18nAlias}.{$i18nPrimary}", "=", "{$table}.{$primary}");
        }

        return $query;
    }
}