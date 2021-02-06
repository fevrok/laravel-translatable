<?php

namespace LaravelArab\Tarjama;

use Exception;
use ArrayAccess;
use JsonSerializable;
use Illuminate\Database\Eloquent\Model;
use LaravelArab\Tarjama\Models\Translation;

class Translator implements ArrayAccess, JsonSerializable
{
    /**
     * Holds translated model instance.
     * 
     * @var Model
     */
    protected $model;

    /**
     * Get all of the current attributes on the model.
     * 
     * @var array
     */
    protected $attributes = [];

    /**
     * Holds the current locale or fallback to english.
     * 
     * @var string
     */
    protected $locale;

    /**
     * Setup translator class.
     * 
     * @param Model $model 
     * @return void 
     */
    public function __construct(Model $model)
    {
        if (!$model->relationLoaded('translations')) {
            $model->load('translations');
        }

        $this->model = $model;
        $this->locale = config('app.locale');
        $attributes = [];

        foreach ($this->model->getAttributes() as $attribute => $value) {
            $attributes[$attribute] = [
                'value'    => $value,
                'locale'   => $this->locale,
                'exists'   => true,
                'modified' => false,
            ];
        }

        $this->attributes = $attributes;
    }

    /**
     * 
     * @param mixed|null $locale 
     * @param bool $fallback 
     * @return $this 
     */
    public function translate($locale = null, $fallback = true)
    {
        $this->locale = $locale;

        foreach ($this->model->getTranslatableAttributes() as $attribute) {
            $this->translateAttribute($attribute, $locale, $fallback);
        }

        return $this;
    }

    /**
     * Save changes made to the translator attributes.
     *
     * @return bool
     */
    public function save()
    {
        $attributes = $this->getModifiedAttributes();
        $savings = [];

        foreach ($attributes as $key => $attribute) {
            if ($attribute['exists']) {
                $translation = $this->getTranslationModel($key);
            } else {
                $translation = $this->translationsModel()::whereTableName($this->model->getTable())
                    ->whereColumnName($key)
                    ->where('foreign_key', $this->model->getKey())
                    ->whereLocale($this->locale)
                    ->first();
            }

            $translation = $translation ?? $this->translationsModel();

            $translation->fill([
                'table_name'  => $this->model->getTable(),
                'column_name' => $key,
                'foreign_key' => $this->model->getKey(),
                'value'       => $attribute['value'],
                'locale'      => $this->locale,
            ]);

            $savings[] = $translation->save();

            $this->attributes[$key]['locale'] = $this->locale;
            $this->attributes[$key]['exists'] = true;
            $this->attributes[$key]['modified'] = false;
        }

        return in_array(false, $savings);
    }

    public function getModel()
    {
        return $this->model;
    }

    /**
     * Return translations model
     * 
     * @return Translation 
     */
    public function translationsModel()
    {
        return $this->getModel()->translationsModel();
    }

    public function getRawAttributes()
    {
        return $this->attributes;
    }

    public function getLocale()
    {
        return $this->locale;
    }

    public function getOriginalAttributes()
    {
        return $this->model->getAttributes();
    }

    /**
     * Get original attribute by key.
     * 
     * @param string $key 
     * @return mixed 
     */
    public function getOriginalAttribute($key)
    {
        return $this->model->getAttribute($key);
    }

    /**
     * Get model translations where column name equal to key.
     * 
     * @param string $key 
     * @param string|null $locale 
     * @return Model|null 
     */
    public function getTranslationModel($key, $locale = null)
    {
        return $this->model->translations()
            ->whereColumnName($key)
            ->whereLocale($locale ? $locale : $this->locale)
            ->first();
    }

    /**
     * Collect and return modified attributes.
     * 
     * @return array 
     */
    public function getModifiedAttributes()
    {
        return collect($this->attributes)->where('modified', 1)->all();
    }

    /**
     * Translate attribute.
     * 
     * @param string $attribute 
     * @param array|string|null $locale 
     * @param string|bool $fallback 
     * @return $this 
     */
    protected function translateAttribute($attribute, $locale = null, $fallback = true)
    {
        list($value, $locale, $exists) = $this->model->getTranslatedAttributeMeta($attribute, $locale, $fallback);

        $this->attributes[$attribute] = [
            'value'    => $value,
            'locale'   => $locale,
            'exists'   => $exists,
            'modified' => false,
        ];

        return $this;
    }

    /**
     * Translate attribute to original.
     * 
     * @param string $attribute 
     * @return $this 
     */
    protected function translateAttributeToOriginal($attribute)
    {
        $this->attributes[$attribute] = [
            'value'    => $this->model->attributes[$attribute],
            'locale'   => config('app.locale'),
            'exists'   => true,
            'modified' => false,
        ];

        return $this;
    }

    /**
     * Get model field value.
     * 
     * @param string $name 
     * @return mixed 
     */
    public function __get($name)
    {
        if (!isset($this->attributes[$name])) {
            if (isset($this->model->$name)) {
                return $this->model->$name;
            }

            return;
        }

        if (!$this->attributes[$name]['exists'] && !$this->attributes[$name]['modified']) {
            return $this->getOriginalAttribute($name);
        }

        return $this->attributes[$name]['value'];
    }

    /**
     * Set value to attribute.
     * 
     * @param string $name 
     * @param mixed $value 
     * @return mixed 
     */
    public function __set($name, $value)
    {
        $this->attributes[$name]['value'] = $value;

        if (!in_array($name, $this->model->getTranslatableAttributes())) {
            return $this->model->$name = $value;
        }

        $this->attributes[$name]['modified'] = true;
    }

    public function offsetGet($offset)
    {
        return $this->attributes[$offset]['value'];
    }

    public function offsetSet($offset, $value)
    {
        $this->attributes[$offset]['value'] = $value;

        if (!in_array($offset, $this->model->getTranslatableAttributes())) {
            return $this->model->$offset = $value;
        }

        $this->attributes[$offset]['modified'] = true;
    }

    public function offsetExists($offset)
    {
        return isset($this->attributes[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);
    }

    public function translationAttributeExists($name)
    {
        if (!isset($this->attributes[$name])) {
            return false;
        }

        return $this->attributes[$name]['exists'];
    }

    public function translationAttributeModified($name)
    {
        if (!isset($this->attributes[$name])) {
            return false;
        }

        return $this->attributes[$name]['modified'];
    }

    /**
     * Fill translation and save to database (in current locale).
     * 
     * @param string $key 
     * @param mixed $value 
     * @return mixed 
     */
    public function createTranslation($key, $value)
    {
        if (!isset($this->attributes[$key])) {
            return false;
        }

        if (!in_array($key, $this->model->getTranslatableAttributes())) {
            return false;
        }

        $translation = $this->translationsModel();
        $translation->fill([
            'table_name'  => $this->model->getTable(),
            'column_name' => $key,
            'foreign_key' => $this->model->getKey(),
            'value'       => $value,
            'locale'      => $this->locale,
        ]);
        $translation->save();

        $this->model->getRelation('translations')->add($translation);

        $this->attributes[$key]['exists'] = true;
        $this->attributes[$key]['value'] = $value;

        return $this->model->translations()
            ->where('key', $key)
            ->whereLocale($this->locale)
            ->first();
    }

    /**
     * Create many translations.
     * 
     * @param array $translations 
     * @return void 
     */
    public function createTranslations(array $translations)
    {
        foreach ($translations as $key => $value) {
            $this->createTranslation($key, $value);
        }
    }

    /**
     * Delete translation by key and current locale.
     * 
     * @param string $key 
     * @return bool 
     */
    public function deleteTranslation($key)
    {
        if (!isset($this->attributes[$key])) {
            return false;
        }

        if (!$this->attributes[$key]['exists']) {
            return false;
        }

        $translations = $this->model->getRelation('translations');
        $locale = $this->locale;

        $this->translationsModel()::whereTableName($this->model->getTable())
            ->whereColumnName($key)
            ->where('foreign_key', $this->model->getKey())
            ->whereLocale($locale)
            ->delete();

        $this->model->setRelation('translations', $translations->filter(function ($translation) use ($key, $locale) {
            return $translation->column_name != $key && $translation->locale != $locale;
        }));

        $this->attributes[$key]['value'] = null;
        $this->attributes[$key]['exists'] = false;
        $this->attributes[$key]['modified'] = false;

        return true;
    }

    /**
     * Delete multiple keys translations.
     * 
     * @param array $keys 
     * @return void 
     */
    public function deleteTranslations(array $keys)
    {
        foreach ($keys as $key) {
            $this->deleteTranslation($key);
        }
    }

    /**
     * Call whenever a method doesnt exists.
     * 
     * @param string $method 
     * @param array $arguments 
     * @return mixed 
     * @throws Exception 
     */
    public function __call($method, array $arguments)
    {
        if (!$this->model->hasTranslatorMethod($method)) {
            throw new Exception('Call to undefined method LaravelArab\Tarjama\Translator::' . $method . '()');
        }

        return call_user_func_array([$this, 'runTranslatorMethod'], [$method, $arguments]);
    }

    /**
     * Call model custom translator method.
     * 
     * @param string $method 
     * @param array $arguments 
     * @return mixed 
     */
    public function runTranslatorMethod($method, array $arguments)
    {
        array_unshift($arguments, $this);

        $method = $this->model->getTranslatorMethod($method);

        return call_user_func_array([$this->model, $method], $arguments);
    }

    /**
     * Serialize raw attributes.
     * 
     * @return array 
     */
    public function jsonSerialize()
    {
        return array_map(function ($array) {
            return $array['value'];
        }, $this->getRawAttributes());
    }
}
