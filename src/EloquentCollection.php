<?php


namespace Adapterap\DataTransferObject;


use Adapterap\DataTransferObject\Exceptions\ClosureIsRequiredForFakeMethod;
use Closure;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Collection as BaseEloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection as BaseSupportCollection;
use RuntimeException;
use stdClass;

class EloquentCollection extends BaseEloquentCollection implements Makeable
{
    /**
     * Имя класса, с которым работает коллекция.
     *
     * @var null|string
     */
    protected ?string $className = null;

    /**
     * Добавляет в конец коллекции несколько элементов.
     *
     * @param BaseEloquentCollection $collection
     *
     * @return BaseEloquentCollection
     */
    public function pushMany(BaseEloquentCollection $collection): BaseEloquentCollection
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
     * @return BaseSupportCollection
     */
    public function mapInto($class): BaseSupportCollection
    {
        if ($class !== $this->className) {
            return $this->toBase()->mapInto($class);
        }

        return parent::mapInto($class);
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
     * @param int          $count
     * @param null|Closure $closure
     *
     * @return BaseEloquentCollection|static
     *@throws ClosureIsRequiredForFakeMethod
     *
     */
    public static function fake(int $count = 5, Closure $closure = null): BaseEloquentCollection
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
     * @throws BindingResolutionException
     *
     * @return Makeable
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
     * @throws BindingResolutionException
     *
     * @return array
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
     * @throws BindingResolutionException
     *
     * @return mixed
     */
    protected function getArrayableItem($item)
    {
        $isStaticCollection = $item instanceof static;
        $instanceOfType = is_object($item) && $item instanceof $this->className;
        $isSelfType = is_object($item) && get_class($item) === $this->className;

        if ($isStaticCollection || $instanceOfType || $isSelfType) {
            return $item;
        }

        if ($item instanceof BaseSupportCollection) {
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
     * @throws BindingResolutionException
     *
     * @return null|mixed|string
     */
    protected function convertItemToClassName($item)
    {
        $classParents = class_parents($this->className);

        if (in_array(Model::class, $classParents, true)) {
            return Container::getInstance()->make($this->className, ['attributes' => (array) $item]);
        }

        if (in_array(Entity::class, $classParents, true)) {
            return Container::getInstance()->make($this->className, ['attributes' => (array) $item]);
        }

        throw new RuntimeException('Item type does not supported');
    }
}