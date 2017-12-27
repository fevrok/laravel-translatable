<?php

namespace LaravelArab\Tarjama;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use LaravelArab\Tarjama\Models\Translation;
use LaravelArab\Tarjama\Translator;

trait Translatable
{
    /**
     * Check if this model can translate.
     *
     * @return bool
     */
    public function translatable()
    {
        if (isset($this->translatable) && $this->translatable == false) {
            return false;
        }
        return !empty($this->getTranslatableAttributes());
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getAttributeValue($key)
    {
        if (!in_array($key, $this->getTranslatableAttributes())) {
            return $this->getAttributes()[$key];
        }
        return $this->getTranslation($key, config('app.locale'));
    }

    /**
     * Load translations relation.
     *
     * @return mixed
     */
    public function translations()
    {
        return $this->hasMany(Translation::class, 'foreign_key', $this->getKeyName())
            ->where('table_name', $this->getTable())
            ->whereIn('locale', config('tarjama.locales', [config('tarjama.locale')]));
    }

    /**
     * This scope eager loads the translations for the default and the fallback locale only.
     * We can use this as a shortcut to improve performance in our application.
     *
     * @param Builder     $query
     * @param string|null $locale
     * @param string|bool $fallback
     */
    public function scopeWithTranslation(Builder $query, $locale = null, $fallback = true)
    {
        if (is_null($locale)) {
            $locale = config('app.locale');
        }

        if ($fallback === true) {
            $fallback = config('app.fallback_locale', 'en');
        }

        $query->with(['translations' => function (Relation $query) use ($locale, $fallback) {
            $query->where('locale', $locale);

            if ($fallback !== false) {
                $query->orWhere('locale', $fallback);
            }
        }]);
    }

    /**
     * This scope eager loads the translations for the default and the fallback locale only.
     * We can use this as a shortcut to improve performance in our application.
     *
     * @param Builder           $query
     * @param string|null|array $locales
     * @param string|bool       $fallback
     */
    public function scopeWithTranslations(Builder $query, $locales = null, $fallback = true)
    {
        if (is_null($locales)) {
            $locales = config('tarjama.locales');
        }

        if ($fallback === true) {
            $fallback = config('app.fallback_locale', 'en');
        }

        $query->with(['translations' => function (Relation $query) use ($locales, $fallback) {
            if (is_null($locales)) {
                return;
            }

            if (is_array($locales)) {
                $query->whereIn('locale', $locales);
            } else {
                $query->where('locale', $locales);
            }

            if ($fallback !== false) {
                $query->orWhere('locale', $fallback);
            }
        }]);
    }

    /**
     * Translate the whole model.
     *
     * @param null|string $language
     * @param bool[string $fallback
     *
     * @return Translator
     */
    public function translate($language = null, $fallback = true)
    {
        if (!$this->relationLoaded('translations')) {
            $this->load('translations');
        }

        return (new Translator($this))->translate($language, $fallback);
    }

    /**
     * Get a single translated attribute.
     *
     * @param $attribute
     * @param null $language
     * @param bool $fallback
     *
     * @return null
     */
    public function getTranslation($attribute, $language = null, $fallback = true)
    {
        list($value) = $this->getTranslatedAttributeMeta($attribute, $language, $fallback);

        return $value;
    }

    public function getTranslationsOf($attribute, array $languages = null, $fallback = true)
    {
        if (is_null($languages)) {
            $languages = config('tarjama.locales', [config('app.locale')]);
        }

        $response = [];
        foreach ($languages as $language) {
            $response[$language] = $this->getTranslation($attribute, $language, $fallback);
        }

        return $response;
    }

    public function getTranslatedAttributeMeta($attribute, $locale = null, $fallback = true)
    {
        $default = config('tarjama.locale');
        // Attribute is translatable
        if (!in_array($attribute, $this->getTranslatableAttributes())) {
            return [$this->getAttributes()[$attribute], $default, false];
        }

        if (!$this->relationLoaded('translations')) {
            $this->load('translations');
        }

        if (is_null($locale)) {
            $locale = config('app.locale');
        }

        if ($fallback === true) {
            $fallback = config('app.fallback_locale', 'en');
        }

        if ($default == $locale) {
            return [$this->getAttribute($attribute), $default, true];
        }

        $translations = $this->getRelation('translations')
            ->where('column_name', $attribute);

        $localeTranslation = $translations->where('locale', $locale)->first();

        if ($localeTranslation) {
            return [$localeTranslation->value, $locale, true];
        }

        if ($fallback == $locale) {
            return [$this->getAttributes()[$attribute], $locale, false];
        }

        if ($fallback == $default) {
            return [$this->getAttributes()[$attribute], $locale, false];
        }

        $fallbackTranslation = $translations->where('locale', $fallback)->first();

        if ($fallbackTranslation && $fallback !== false) {
            return [$fallbackTranslation->value, $locale, true];
        }

        return [null, $locale, false];
    }

    /**
     * Get attributes that can be translated.
     *
     * @return array
     */
    public function getTranslatableAttributes()
    {
        return property_exists($this, 'translatable') ? $this->translatable : [];
    }

    public function setTranslation($attribute, $lang, $value, $save = false)
    {

        if (!$this->relationLoaded('translations')) $this->load('translations');

        $default = config('tarjama.locale', 'en');

        if ($lang != $default) {
            $tranlator = $this->translate($lang, false);
            $tranlator->$attribute = $value;
            if ($save) $tranlator->save();
            return $tranlator;
        }   

        $this->$attribute = $value;
    }

    public function setTranslations($attribute, array $translations, $save = false)
    {
        $response = [];

        if (!$this->relationLoaded('translations')) {
            $this->load('translations');
        }

        $default = config('tarjama.locale', 'en');
        $locales = config('tarjama.locales', [$default]);

        foreach ($locales as $locale) {
            if (!isset($translations[$locale])) {
                continue;
            }

            if ($locale == $default) {
                $this->$attribute = $translations[$locale];
                continue;
            }

            $tranlator = $this->translate($locale, false);
            $tranlator->$attribute = $translations[$locale];

            if ($save) {
                $tranlator->save();
            }

            $response[] = $tranlator;
        }

        return $response;
    }

    /**
     * Delete Translations
     * @param  array  $attributes [description]
     * @param  [type] $locales    [description]
     * @return [type]             [description]
     */
    public function deleteTranslations(array $attributes, $locales = null)
    {
        $this->translations()
            ->whereIn('column_name', $attributes)
            ->when(!is_null($locales), function ($query) use ($locales) {
                $method = is_array($locales) ? 'whereIn' : 'where';

                return $query->$method('locale', $locales);
            })
            ->delete();
    }

    /**
     * Save translations.
     *
     * @param object $translations
     *
     * @return void
     */
    public function saveTranslations($translations)
    {
        foreach ($translations as $field => $locales) {
            foreach ($locales as $locale => $translation) {
                $translation->save();
            }
        }
    }
}
