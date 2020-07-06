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
        $result = Read::instanceFromArray(ReadTestFoo::class, [
            'foo' => 'hey',
            'bar' => false
        ]);
        $this->assertSuccess($result);
        $this->assertEquals('hey', $result->unsafeGet()->foo);
        $this->assertEquals(false, $result->unsafeGet()->bar);
    }

    function testSimpleInstanceFailure() {
        $result = Read::instanceFromArray(ReadTestFoo::class, [
            'foo' => 'hey',
        ]);
        $this->assertTrue($result->isFailure());
    }

    function testNestedInstance() {
        $result = Read::instanceFromArray(ReadTestBar::class, [
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
}