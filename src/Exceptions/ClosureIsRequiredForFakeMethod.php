<?php

namespace Adapterap\DataTransferObject\Exceptions;

use Exception;

class ClosureIsRequiredForFakeMethod extends Exception
{
    /**
     * ClosureIsRequiredForFakeMethod constructor.
     *
     * @param string $collectionClassName
     */
    public function __construct(string $collectionClassName)
    {
        parent::__construct("Closure param is required for {$collectionClassName}::fake() method when \$className does not have static [fake] method");
    }
}
