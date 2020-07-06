<?php

declare(strict_types=1);

namespace AlienTech;

use PHPUnit\Framework\TestCase;

class EffectTest extends TestCase
{
    function testBasic() {
        $called = false;
        $eff = Effect::of(function() use (&$called) {
            $called = true;
            return 42;
        });
        $this->assertFalse($called);
        $x = $eff->unsafePerformEffect();
        $this->assertTrue($called);
        $this->assertEquals(42, $x->unsafeGet());

        $called = false;
        $eff2 = $eff->map(fn($x) => $x * 10);
        $this->assertFalse($called);
        $x = $eff2->unsafePerformEffect();
        $this->assertTrue($called);
        $this->assertEquals(420, $x->unsafeGet());
    }

    function testChain() {
        $spy = new \stdClass;
        $spy->called = false;
        $spy->called2 = false;
        $eff = Effect::of(function() use ($spy) {
            $spy->called = true;
            return 42;
        });
        $eff2 = $eff->chain(fn($x) => Effect::of(function() use ($x, $spy) {
            $spy->called2 = true;
            return $x * 10;
        }));

        $this->assertFalse($spy->called);
        $this->assertFalse($spy->called2);

        $x = $eff2->unsafePerformEffect();
        $this->assertTrue($spy->called);
        $this->assertTrue($spy->called2);
        $this->assertEquals(420, $x->unsafeGet());
    }
}
