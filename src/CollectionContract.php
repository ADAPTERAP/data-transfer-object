<?php

namespace Adapterap\DataTransferObject;

use Closure;

interface CollectionContract
{
    /**
     * Добавляет в конец коллекции несколько элементов.
     *
     * @param CollectionContract $collection
     *
     * @return CollectionContract
     */
    public function pushMany(CollectionContract $collection): self;

    /**
     * Map the values into a new class.
     *
     * @param string $class
     *
     * @return CollectionContract|static
     */
    public function mapInto($class);

    /**
     * Метод для генерации коллекции для тестов.
     *
     * @param int $count
     * @param null|Closure $closure
     *
     * @return CollectionContract|static
     *
     * @throws ClosureIsRequiredForFakeMethod
     */
    public static function fake(int $count = 5, Closure $closure = null): self;
}
