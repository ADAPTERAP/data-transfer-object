<?php


namespace Adapterap\DataTransferObject;


interface Makeable
{
    /**
     * Создает новый экземпляр текущего класса по переданным атрибутам
     *
     * @param mixed ...$attributes
     *
     * @return Makeable
     */
    public static function makeable(...$attributes): Makeable;
}