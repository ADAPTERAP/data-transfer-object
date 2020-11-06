<?php

namespace Tests\Unit;

use Adapterap\DataTransferObject\BaseCollection;
use Adapterap\DataTransferObject\Entity;
use Adapterap\DataTransferObject\Support\Str;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * @internal
 * @coversNothing
 */
class EntityTest extends TestCase
{
    /**
     * Проверяет корректное заполнение свойств сущности из массива.
     *
     * @throws ReflectionException
     */
    public function testFillFromArray(): void
    {
        $attributes = [
            'someString' => Str::random(),
            'someNullableString' => Str::random(),
            'someNullableStringWithDefaultValue' => Str::random(),
            'child' => [
                'someNumber' => random_int(1, 100),
            ],
            'children' => [
                [
                    'someNumber' => random_int(1, 100),
                ],
            ],
        ];

        $abstractEntity = new AbstractEntity($attributes);

        self::assertEquals($attributes['someString'], $abstractEntity->someString);
        self::assertEquals($attributes['someNullableString'], $abstractEntity->someNullableString);
        self::assertEquals($attributes['someNullableStringWithDefaultValue'], $abstractEntity->someNullableStringWithDefaultValue);

        self::assertInstanceOf(ChildAbstractEntity::class, $abstractEntity->child);
        self::assertEquals($attributes['child']['someNumber'], $abstractEntity->child->someNumber);

        self::assertInstanceOf(ChildAbstractCollection::class, $abstractEntity->children);
        self::assertCount(1, $abstractEntity->children);
        self::assertEquals($attributes['children'][0]['someNumber'], $abstractEntity->children->get(0)->someNumber);
    }
}

class AbstractEntity extends Entity
{
    /**
     * Свойство, хранящее строку.
     *
     * @var string
     */
    public string $someString;

    /**
     * Свойство, хранящее строку или null.
     *
     * @var null|string
     */
    public ?string $someNullableString;

    /**
     * Свойство, хранящее строку или null и имеющее значение по умолчанию.
     *
     * @var null|string
     */
    public ?string $someNullableStringWithDefaultValue = null;

    /**
     * Дочерняя сущность.
     *
     * @var ChildAbstractEntity
     */
    public ChildAbstractEntity $child;

    /**
     * Дочерняя коллекция.
     *
     * @var ChildAbstractCollection
     */
    public ChildAbstractCollection $children;
}

class ChildAbstractEntity extends Entity
{
    /**
     * Свойство, хранящее число.
     *
     * @var int
     */
    public int $someNumber;
}

class ChildAbstractCollection extends BaseCollection
{
    /**
     * Имя класса, с которым работает коллекция.
     *
     * @var null|string
     */
    protected ?string $className = ChildAbstractEntity::class;
}
