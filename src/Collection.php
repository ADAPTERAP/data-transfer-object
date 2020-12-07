<?php

namespace Adapterap\DataTransferObject;

use Illuminate\Support\Collection as BaseSupportCollection;

class Collection extends BaseSupportCollection implements Makeable, CollectionContract
{
    use CollectionTrait;

    /**
     * Имя класса, с которым работает коллекция.
     *
     * @var null|string
     */
    protected ?string $className = null;
}
