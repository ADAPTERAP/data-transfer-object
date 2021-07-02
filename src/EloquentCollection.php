<?php

namespace Adapterap\DataTransferObject;

use MyCLabs\Enum\Enum;
use Illuminate\Database\Eloquent\Collection as BaseEloquentCollection;

class EloquentCollection extends BaseEloquentCollection implements Makeable, CollectionContract
{
    use CollectionTrait;

    /**
     * Имя класса, с которым работает коллекция.
     *
     * @var null|string
     */
    protected ?string $className = null;

    /**
     * @inheritDoc
     */
    protected function valueRetriever($value)
    {
        if ($this->useAsCallable($value)) {
            return $value;
        }

        return function ($item) use ($value) {
            $result = data_get($item, $value);

            return $result instanceof Enum ? $result->getValue() : $result;
        };
    }
}
