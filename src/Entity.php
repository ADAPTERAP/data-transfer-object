<?php

namespace Adapterap\DataTransferObject;

use Adapterap\DataTransferObject\Support\Str;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

abstract class Entity implements Arrayable, Makeable
{
    /**
     * Entity constructor.
     *
     * @param mixed ...$attributes
     *
     * @throws ReflectionException
     */
    public function __construct(...$attributes)
    {
        $this->fill($attributes);
    }

    /**
     * Наполняет сущность указанными данными.
     *
     * @param array $attributes
     *
     * @return $this
     * @throws ReflectionException
     *
     */
    public function fill(array $attributes): Entity
    {
        if (count($attributes) === 1 && is_array(Arr::get($attributes, 0))) {
            $attributes = $attributes[0];
        }

        // Заполняем сущность и проверяем заполненность обязательных полей
        $reflection = new ReflectionClass(static::class);
        $reflectionProperties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($reflectionProperties as $property) {
            $propertyName = $property->getName();
            $snackPropertyName = Str::snake($property->getName());

            if (Arr::has($attributes, $propertyName)) {
                $this->fillAttribute($property, Arr::get($attributes, $propertyName), $attributes);
            } elseif (Arr::has($attributes, $snackPropertyName)) {
                $this->fillAttribute($property, Arr::get($attributes, $snackPropertyName), $attributes);
            }

            if (!$property->isInitialized($this)) {
                $staticClassName = static::class;

                throw new ReflectionException("Property {$propertyName} is not initialized in {$staticClassName}");
            }
        }

        return $this;
    }

    /**
     * Приводит сущность к массиву, исключая null в значениях.
     *
     * @return array
     * @throws ReflectionException
     *
     */
    public function toArrayWithoutNullables(): array
    {
        return array_filter($this->toArray(), static function ($value) {
            return $value !== null;
        });
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     * @throws ReflectionException
     *
     */
    public function toArray(): array
    {
        $properties = get_class_vars(static::class);
        $result = [];

        foreach ($properties as $key => $null) {
            $reflectionProperty = new ReflectionProperty($this, $key);

            if ($reflectionProperty->isPublic()) {
                $value = $this->{$key};
                $convertedKey = $this->convertToSnackCase() ? Str::snake($key) : $key;

                if ($value instanceof Arrayable) {
                    $result[$convertedKey] = $value->toArray();
                } else {
                    $result[$convertedKey] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * Фабричный метод.
     *
     * @param array $attributes
     *
     * @return Entity
     * @throws BindingResolutionException
     *
     */
    public static function make(...$attributes): Entity
    {
        return Container::getInstance()->make(static::class, $attributes);
    }

    /**
     * Создает новый экземпляр текущего класса по переданным атрибутам
     *
     * @param mixed ...$attributes
     *
     * @return Makeable
     * @throws BindingResolutionException
     *
     */
    public static function makeable(...$attributes): Makeable
    {
        if (count($attributes) === 1) {
            $attributes = Arr::first($attributes);
        }

        return Container::getInstance()->make(static::class, ['attributes' => $attributes]);
    }

    /**
     * Заполняет атрибут значением.
     *
     * @param ReflectionProperty $property
     * @param mixed $value
     * @param array $attributes
     */
    protected function fillAttribute(ReflectionProperty $property, $value, array $attributes): void
    {
        $type = $property->getType();
        $typeName = $type ? $type->getName() : null;
        $typeIsClass = class_exists($typeName);
        $name = $property->getName();

        if ($this->hasSetter($name)) {
            $value = $this->mutate($name, $value, $attributes);
        }

        $done = false;

        if ($typeIsClass && ($value !== null || !$type->allowsNull())) {
            if (is_object($value) && get_class($value) === $typeName) {
                $done = true;
                $property->setValue($this, $value);
            } elseif (is_a($typeName, Makeable::class, true)) {
                $done = true;
                $property->setValue($this, $typeName::makeable($value));
            }
        }

        if (!$done) {
            $property->setValue($this, $value);
        }
    }

    /**
     * Проверяет существование сеттера.
     *
     * @param string $name
     *
     * @return bool
     */
    protected function hasSetter(string $name): bool
    {
        return method_exists($this, $this->getSetterName($name));
    }

    /**
     * Вызов геттера для атрибута.
     *
     * @param string $name
     * @param mixed $value
     * @param array $attributes
     *
     * @return mixed
     */
    protected function mutate(string $name, $value, array $attributes)
    {
        return $this->{$this->getSetterName($name)}($value, $attributes);
    }

    /**
     * Если true, тогда все имена параметров будут конвертированы в snack_case
     * при вызове toArray().
     *
     * @return bool
     */
    protected function convertToSnackCase(): bool
    {
        return true;
    }

    /**
     * Возвращает имя сеттера.
     *
     * @param string $name
     *
     * @return string
     */
    private function getSetterName(string $name): string
    {
        return 'set' . Str::pascal($name) . 'Attribute';
    }
}
