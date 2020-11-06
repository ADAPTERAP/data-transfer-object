<?php


namespace Adapterap\DataTransferObject\Support;


class Str extends \Illuminate\Support\Str
{
    /**
     * Приводит текст к PascalCase.
     *
     * @param string $value
     *
     * @return string
     */
    public static function pascal(string $value): string
    {
        return ucfirst(self::camel($value));
    }
}