<?php

namespace Adapterap\DataTransferObject;

use Adapterap\DataTransferObject\Exceptions\ClosureIsRequiredForFakeMethod;
use Closure;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use RuntimeException;
use stdClass;

trait CollectionTrait
{
    /**
     * Добавляет в конец коллекции несколько элементов.
     *
     * @param CollectionContract $collection
     *
     * @return CollectionTrait|Collection|EloquentCollection|static
     */
    public function pushMany(CollectionContract $collection): self
    {
        $collection->each(function ($item) {
            $this->push($item);
        });

        return $this;
    }

    /**
     * Map the values into a new class.
     *
     * @param string $class
     *
     * @return CollectionContract|Collection|EloquentCollection|static
     */
    public function mapInto($class)
    {
        if ($class !== $this->className) {
            return $this->toBase()->mapInto($class);
        }

        return parent::mapInto($class);
    }

    /**
     * Get the values of a given key.
     *
     * @param string|array|int|null $value
     * @param string|null $key
     *
     * @return CollectionContract|Collection|EloquentCollection|static
     */
    public function pluck($value, $key = null)
    {
        return $this->toBase()->pluck($value, $key);
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        $result = [];

        foreach ($this->all() as $key => $value) {
            $result[$key] = $value instanceof Arrayable ? $value->toArray() : $value;
        }

        return $result;
    }

    /**
     * Метод для генерации коллекции для тестов.
     *
     * @param int $count
     * @param null|Closure $closure
     *
     * @return CollectionContract|Collection|EloquentCollection|static
     * @throws ClosureIsRequiredForFakeMethod
     *
     */
    public static function fake(int $count = 5, Closure $closure = null): self
    {
        $result = new static();

        for ($i = 0; $i < $count; ++$i) {
            if (!$closure && $result->className && method_exists($result->className, 'fake')) {
                $result->push($result->className::fake());
            } elseif (!$closure) {
                throw new ClosureIsRequiredForFakeMethod(static::class);
            } else {
                $result->push($closure());
            }
        }

        return $result;
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

        return Container::getInstance()->make(static::class, ['items' => $attributes]);
    }

    /**
     * Results array of items from Collection or Arrayable.
     * Преобразовывает элементы коллекции в элементы нужного класса.
     *
     * @param mixed $items
     *
     * @return array
     * @throws BindingResolutionException
     *
     */
    protected function getArrayableItems($items): array
    {
        $arrayItems = parent::getArrayableItems($items);

        if ($this->className) {
            foreach ($arrayItems as $index => $item) {
                $arrayItems[$index] = $this->getArrayableItem($item);
            }
        }

        return $arrayItems;
    }

    /**
     * Results an item from Collection or Arrayable.
     *
     * @param $item
     *
     * @return mixed
     * @throws BindingResolutionException
     *
     */
    protected function getArrayableItem($item)
    {
        $isStaticCollection = $item instanceof static;
        $instanceOfType = is_object($item) && $item instanceof $this->className;
        $isSelfType = is_object($item) && get_class($item) === $this->className;

        if ($isStaticCollection || $instanceOfType || $isSelfType) {
            return $item;
        }

        if ($item instanceof Makeable) {
            return new static($item->all());
        }

        if ($item instanceof Arrayable) {
            return Container::getInstance()->make($this->className, $item->toArray());
        }

        if (is_object($item) && !$item instanceof stdClass) {
            throw new RuntimeException('В коллекции найден экземпляр другого класса');
        }

        if (is_array($item) && is_object(Arr::first($item))) {
            $result = [];

            foreach ($item as $key => $value) {
                $result[$key] = $this->getArrayableItem($value);
            }

            return $result;
        }

        return $this->convertItemToClassName($item);
    }

    /**
     * @param $item
     *
     * @return null|mixed|string
     * @throws BindingResolutionException
     *
     */
    protected function convertItemToClassName($item)
    {
        $classParents = class_parents($this->className);

        if (in_array(Model::class, $classParents, true)) {
            return Container::getInstance()->make($this->className, ['attributes' => (array)$item]);
        }

        if (in_array(Entity::class, $classParents, true)) {
            return Container::getInstance()->make($this->className, ['attributes' => (array)$item]);
        }

        throw new RuntimeException('Item type does not supported');
    }
}
