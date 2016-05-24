<?php
namespace tuanlq11\dbi18n;

use Illuminate\Database\Eloquent\Builder;

/**
 * Created by Fallen.
 */
trait I18NDBTrait
{
    public static $I18N = true;

    /** @var string */
    // protected $i18n_attribute_name = "i18n";
    /** @var string */
    // protected $i18n_default_locale = "en";
    /** @var string */
    // protected $i18n_primary = "id";
    /** @var null|string */
    // protected $i18n_class = null;
    /** @var string */
    // protected $i18n_field = "locale";
    /** @var array */
    // protected $i18n_fillable = [];

    /**
     * Store i18n data, before save
     * @var array
     */
    private $i18n_data = [];

    /**
     * Boot Handler
     */
    public static function bootI18NDBTrait()
    {
        self::saving(function ($model) {
            $model->filterI18NColumn();
        });

        self::saved(function ($model) {
            $model->saveI18N();
        });
    }

    /**
     * Add i18n data to variable
     * @param $data
     * @param $locale
     */
    public function addI18NData($data, $locale)
    {
        $this->i18n_data[$locale] = $data;
    }

    /**
     * Store I18N to database
     */
    public function saveI18N()
    {
        if (empty($this->i18n_data)) return;
        $this->i18n_relation()->getQuery()->whereIn($this->getI18NCodeField(), array_keys($this->i18n_data))->delete();
        foreach ($this->i18n_data as $locale => $data) {
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
        $overrideData                                = array_only($this->attributes, $this->i18n_fillable);
        $this->i18n_data[$this->i18n_default_locale] = $overrideData;

        if (isset($this->attributes[$this->i18n_attribute_name])) {
            $this->i18n_data = $this->attributes[$this->i18n_attribute_name];
            unset($this->attributes[$this->i18n_attribute_name]);
        }
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
        return (new $this->i18n_class())->getTable();
    }

    /**
     * Return column name of i18n code
     * @return string
     */
    protected function getI18NCodeField()
    {
        return isset($this->i18n_code_field) ? $this->i18n_code_field : 'locale';
    }

    /**
     * Join I18N data to this
     *
     * @param $query Builder
     * @param $locale string|null
     * @return  Builder
     */
    public function scopeI18N($query, $locale = null)
    {
        $i18nTranAlias = "i18n_translation";
        $i18nAlias     = "i18n";
        $primary       = $this->primaryKey;
        $i18nPrimary   = $this->i18n_primary;
        $table         = $this->table;
        $i18n_table    = (new $this->i18n_class())->getTable();

        if ($locale) {
            $query->leftJoin(\DB::raw("
            (
                SELECT {$i18nTranAlias}.*
                FROM {$i18n_table} as {$i18nTranAlias}
                WHERE {$i18nTranAlias}.{$this->getI18NCodeField()} = '{$locale}'
            ) as {$i18nAlias}"
            ), function ($join) use ($i18nAlias, $primary, $i18nPrimary, $table) {
                $join->on("{$i18nAlias}.{$i18nPrimary}", "=", "{$table}.{$primary}");
            });
        } else {
            $query->leftJoin("{$i18n_table} as {$i18nAlias}", "{$i18nAlias}.{$i18nPrimary}", "=", "{$table}.{$primary}");
        }

        return $query;
    }
}