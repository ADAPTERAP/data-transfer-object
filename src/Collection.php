<?php

namespace Adapterap\DataTransferObject;

use Illuminate\Support\Collection as BaseSupportCollection;

class Collection extends BaseSupportCollection implements Makeable
{
    use CollectionTrait;
}
