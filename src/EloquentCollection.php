<?php

namespace Adapterap\DataTransferObject;

use Illuminate\Database\Eloquent\Collection as BaseEloquentCollection;

class EloquentCollection extends BaseEloquentCollection implements Makeable, CollectionContract
{
    use CollectionTrait;
}
