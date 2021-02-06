<?php

namespace LaravelArab\Tarjama;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use LaravelArab\Tarjama\Models\Translation;
use LaravelArab\Tarjama\Translator;

trait Translatable
{
    /**
     * Load translations relation.
     *
     * @return HasMany|Builder
     */
    public function translations(): HasMany
    {
        return $this->hasMany($this->translationsModel(), 'foreign_key', $this->getKeyName())
            ->whereTableName($this->getTable());
    }

    /**
     * Translate the whole model.
     * 
     * @param string|null $locale 
     * @param string|bool $fallback 
     * @return Translator 
     */
    public function translate($locale = null, $fallback = true)
    {
        if (!$this->relationLoaded('translations')) {
            $this->load('translations');
        }

        return (new Translator($this))->translate($locale, $fallback);
    }

    /**
     * Check if this model can translate.
     * 
     * @return bool 
     */
    public function translatable(): bool
    {
        if (isset($this->translatable) && $this->translatable == false) {
            return false;
        }

        return !empty($this->getTranslatableAttributes());
    }

    /**
     * This scope eager loads the translations for the default and the fallback locale only.
     * We can use this as a shortcut to improve performance in our application.
     * 
     * @param Builder $query 
     * @param string|null $locale 
     * @param string|bool $fallback 
     * @return Builder
     */
    public function scopeWithTranslation(Builder $query, $locale = null, $fallback = true)
    {
        if (is_null($locale)) {
            $locale = app()->getLocale();
        }

        if ($fallback === true) {
            $fallback = config('app.fallback_locale', 'en');
        }

        $query->with([$this->translationsModel() => function (Relation $query) use ($locale, $fallback) {
            $query->where(function ($q) use ($locale, $fallback) {
                $q->whereLocale($locale);

                if ($fallback !== false) {
                    $q->orWhereLocale($fallback);
                }
            });
        }]);
    }

    /**
     * This scope eager loads the translations for the default and the fallback locale only.
     * We can use this as a shortcut to improve performance in our application.
     * 
     * @param Builder $query 
     * @param string|null|array $locales 
     * @param string|bool $fallback 
     * @return Builder
     */
    public function scopeWithTranslations(Builder $query, $locales = null, $fallback = true)
    {
        if (is_null($locales)) {
            $locales = app()->getLocale();
        }

        if ($fallback === true) {
            $fallback = config('app.fallback_locale', 'en');
        }

        $query->with([$this->translationsModel() => function (Relation $query) use ($locales, $fallback) {
            if (is_null($locales)) {
                return;
            }

            $query->where(function ($q) use ($locales, $fallback) {
                if (is_array($locales)) {
                    $q->whereInLocale($locales);
                } else {
                    $q->whereLocale($locales);
                }

                if ($fallback !== false) {
                    $q->orWhereLocale($fallback);
                }
            });
        }]);
    }

    /**
     * Get entries filtered by translated value.
     *
     * @example  Class::whereTranslation('title', '=', 'zuhause', ['de', 'iu'])
     * @example  $query->whereTranslation('title', '=', 'zuhause', ['de', 'iu'])
     *
     * @param Builder $query 
     * @param string $field {required} the field your looking to find a value in.
     * @param string $operator {required} value you are looking for or a relation modifier such as LIKE, =, etc.
     * @param string $value {optional} value you are looking for. Only use if you supplied an operator.
     * @param string|array $locales  {optional} locale(s) you are looking for the field.
     * @param bool $default {optional} if true checks for $value is in default database before checking translations.
     *
     * @return Builder
     */
    public function scopeWhereTranslation($query, $field, $operator, $value = null, $locales = null, $default = true)
    {
        if ($locales && !is_array($locales)) {
            $locales = [$locales];
        }

        return $query->whereIn(
            $this->getKeyName(),
            $this->translationsModel()::whereTableName($this->getTable())
                ->whereColumnName($field)
                ->whereValue($operator, $value)
                ->when(!is_null($locales), function ($query) use ($locales) {
                    return $query->whereInLocale($locales);
                })
                ->pluck('foreign_key')
        )->when($default, function ($query) use ($field, $operator, $value) {
            return $query->orWhere($field, $operator, $value);
        });
    }

    /**
     * Get attribute translations of many launguages.
     * 
     * @param mixed $attribute 
     * @param array|null $locales 
     * @param bool $fallback 
     * @return array 
     */
    public function getTranslationsOf($attribute, array $locales = null, $fallback = true)
    {
        if (is_null($locales)) {
            $locales = [config('app.locale')];
        }

        $response = [];
        foreach ($locales as $locale) {
            $response[$locale] = $this->getTranslatedAttribute($attribute, $locale, $fallback);
        }

        return $response;
    }

    /**
     * Get a single translated attribute.
     *
     * @param $attribute
     * @param null $locale
     * @param bool $fallback
     *
     * @return null
     */
    public function getTranslatedAttribute($attribute, $locale = null, $fallback = true)
    {
        // If multilingual is not enabled don't check for translations
        if (!config('tarjama.enabled')) {
            return $this->getAttributeValue($attribute);
        }

        list($value) = $this->getTranslatedAttributeMeta($attribute, $locale, $fallback);

        return $value;
    }

    /**
     * Get translated attribute meta.
     * 
     * @param string $attribute 
     * @param string|null $locale 
     * @param string|bool $fallback 
     * @return array 
     */
    public function getTranslatedAttributeMeta($attribute, $locale = null, $fallback = true)
    {
        // Attribute is translatable
        //
        if (!in_array($attribute, $this->getTranslatableAttributes())) {
            return [$this->getAttribute($attribute), config('tarjama.locale'), false];
        }

        if (is_null($locale)) {
            $locale = app()->getLocale();
        }

        if ($fallback === true) {
            $fallback = config('app.fallback_locale', 'en');
        }

        $default = config('tarjama.locale');

        if ($default == $locale) {
            return [$this->getAttribute($attribute), $default, true];
        }

        if (!$this->relationLoaded('translations')) {
            $this->load('translations');
        }

        $translations = $this->translations()
            ->whereColumnName($attribute);

        $localeTranslation = $translations->whereLocale($locale)->first();

        if ($localeTranslation) {
            return [$localeTranslation->value, $locale, true];
        }

        if ($fallback == $locale) {
            return [$this->getAttribute($attribute), $locale, false];
        }

        if ($fallback == $default) {
            return [$this->getAttribute($attribute), $locale, false];
        }

        $fallbackTranslation = $translations->whereLocale($fallback)->first();

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

    /**
     * Get translations model.
     *
     * @return Translation
     */
    public function translationsModel(): Translation
    {
        $model = Translation::class;

        if (property_exists($this, 'translations_model')) {
            $model = $this->translations_model;
        }

        return app($model);
    }

    /**
     * Set translation.
     * 
     * @param string $attribute 
     * @param array $translations 
     * @param bool $save 
     * @return array 
     */
    public function setAttributeTranslations($attribute, array $translations, $save = false)
    {
        $response = [];

        if (!$this->relationLoaded('translations')) {
            $this->load('translations');
        }

        $default = config('tarjama.locale', 'en');

        foreach ($translations as $locale  => $translation) {
            if (empty($translation)) {
                continue;
            }

            if ($locale == $default) {
                $this->$attribute = $translation;
                continue;
            }

            $tranlator = $this->translate($locale, false);
            $tranlator->$attribute = $translation;

            if ($save) {
                $tranlator->save();
            }

            $response[] = $tranlator;
        }

        return $response;
    }

    /**
     * Save translations.
     * 
     * @param mixed $translations 
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

    /**
     * Check if attribute has custom translator method.
     * 
     * @param string $name 
     * @return bool 
     */
    public function hasTranslatorMethod($name)
    {
        if (!isset($this->translatorMethods)) {
            return false;
        }

        return isset($this->translatorMethods[$name]);
    }

    /**
     * Get attribute custom translator method.
     * 
     * @param string $name 
     * @return mixed 
     */
    public function getTranslatorMethod($name)
    {
        if (!$this->hasTranslatorMethod($name)) {
            return;
        }

        return $this->translatorMethods[$name];
    }

    /**
     * Delete attributes translations.
     * 
     * @param array $attributes 
     * @param array|string|null $locales 
     * @return void 
     */
    public function deleteAttributeTranslations(array $attributes, $locales = null)
    {
        $this->translations()
            ->whereInColumnName($attributes)
            ->when(!is_null($locales), function ($query) use ($locales) {
                if (is_array($locales)) {
                    return $query->whereInLocale($locales);
                }

                return $query->whereLocale($locales);
            })
            ->delete();
    }

    /**
     * Delete Attribute translation.
     * 
     * @param string $attribute 
     * @param array|string|null $locales 
     * @return void 
     */
    public function deleteAttributeTranslation($attribute, $locales = null)
    {
        $this->translations()
            ->whereColumnName($attribute)
            ->when(!is_null($locales), function ($query) use ($locales) {
                if (is_array($locales)) {
                    return $query->whereInLocale($locales);
                }

                return $query->whereLocale($locales);
            })
            ->delete();
    }
}
