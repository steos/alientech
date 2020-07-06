<?php

declare(strict_types=1);

namespace AlienTech;

class Some implements Optional
{
    private $value;

    private function __construct($value) {
        $this->value = $value;
    }

    static function of($x) {
        return new Some($x);
    }

    function isSome(): bool {
        return true;
    }

    function isNone(): bool {
        return false;
    }

    function map(callable $f): self {
        return self::of(call_user_func($f, $this->value));
    }

    function chain(callable $f): Optional {
        return call_user_func($f, $this->value);
    }

    function whenSome(callable $f): void {
        call_user_func($f, $this->value);
    }

    function whenNone(callable $f): void {}

    function getOrElse(callable $f) {
        return $this->value;
    }

    function getWithDefault($default) {
        return $this->value;
    }

    function unsafeGet() {
        return $this->value;
    }
}