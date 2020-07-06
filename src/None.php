<?php

declare(strict_types=1);

namespace AlienTech;

final class None implements Optional {
    static private ?None $instance = null;

    private function __construct() {}

    static function get() {
        if (self::$instance == null) {
            self::$instance = new None();
        }
        return self::$instance;
    }

    function isSome(): bool {
        return false;
    }

    function isNone(): bool {
        return true;
    }

    function map(callable $f): self {
        return $this;
    }

    function chain(callable $f): Optional {
        return $this;
    }

    function whenSome(callable $f): void {}

    function whenNone(callable $f): void {
        call_user_func($f);
    }

    function getOrElse(callable $f) {
        return call_user_func($f);
    }

    function getWithDefault($default) {
        return $default;
    }

    /**
     * @throws \Exception
     */
    function unsafeGet() {
        throw new \Exception();
    }
}