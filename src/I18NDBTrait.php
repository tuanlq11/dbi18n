<?php
namespace tuanlq11\dbi18n;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Psy\Util\Str;

/**
 * Created by Fallen.
 * @property string primaryKey
 * @property string table
 * @property string i18n_primary
 * @property string i18n_code_field
 * @property string i18n_table
 * @property string i18n_columns
 */
trait I18NDBTrait
{
    public function bootI18NDBTrait()
    {
        /** TODO: Nothing */
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function i18n_relation()
    {
        return $this->hasMany($this->i18n_class, $this->primaryKey, $this->primaryKey);
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
     * @return string
     */
    protected function getI18NCodeField()
    {
        return isset($this->i18n_code_field) ? $this->i18n_code_field : 'locale';
    }

    /**
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

        $query->addSelect('');

        return $query;
    }
}