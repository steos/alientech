<?php

declare(strict_types=1);

namespace AlienTech;

use PHPUnit\Framework\TestCase;

class ReadTestFoo {
    public string $foo;
    public bool $bar;
}

class ReadTestBar {
    public ReadTestFoo $foo;
    public ?string $bar;
}

class ReadTest extends TestCase
{
    function assertSuccess(Result $x) {
        if ($x->isFailure()) {
            $this->fail($x->unsafeGetFailure());
        }
    }
    function testSimpleInstance() {
        $result = Read::newInstance(ReadTestFoo::class, [
            'foo' => 'hey',
            'bar' => false
        ]);
        $this->assertSuccess($result);
        $this->assertEquals('hey', $result->unsafeGet()->foo);
        $this->assertEquals(false, $result->unsafeGet()->bar);
    }

    function testSimpleInstanceFailure() {
        $result = Read::newInstance(ReadTestFoo::class, [
            'foo' => 'hey',
        ]);
        $this->assertTrue($result->isFailure());
    }

    function testNestedInstance() {
        $result = Read::newInstance(ReadTestBar::class, [
            'foo' => [
                'foo' => 'ole',
                'bar' => false
            ]
        ]);
        $this->assertSuccess($result);
        $this->assertEquals('ole', $result->unsafeGet()->foo->foo);
        $this->assertFalse($result->unsafeGet()->foo->bar);
        $this->assertNull($result->unsafeGet()->bar);
    }

    function testNestedInstanceWithCustomReaders() {
        $input =  [
            'quux' => [
                'a' => 'ole',
                'b' => false
            ],
            'lorem' => ['abc', 'xyz']
        ];
        $result = Read::newInstance(ReadTestBar::class, $input, [
            'bar' => Read::fieldPath(['lorem', 1]),
            'foo' => Read::field('quux', Read::instance([
                'foo' => Read::field('a'),
                'bar' => Read::field('b'),
            ]))
        ]);
        $this->assertSuccess($result);
        /** @var ReadTestBar $x */
        $x = $result->unsafeGet();
        $this->assertEquals('ole', $x->foo->foo);
        $this->assertEquals('xyz', $x->bar);
    }

    function testNestedInstanceWithCustomReadersInNestedTypeOnly() {
        $input =  [
            'foo' => [
                'a' => 'ole',
                'b' => false
            ],
        ];
        $result = Read::newInstance(ReadTestBar::class, $input, [
            'foo' => [
                'foo' => Read::field('a'),
                'bar' => Read::field('b'),
            ]
        ]);
        $this->assertSuccess($result);
        /** @var ReadTestBar $x */
        $x = $result->unsafeGet();
        $this->assertEquals('ole', $x->foo->foo);
        $this->assertFalse($x->foo->bar);
    }

    function testBuiltinTypeMismatch() {
        $result = Read::newInstance(ReadTestFoo::class, [
            'foo' => 'hey',
            'bar' => 42
        ]);
        $this->assertTrue($result->isFailure());
    }
}
