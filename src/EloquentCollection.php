<?php

namespace Adapterap\DataTransferObject;

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
}
