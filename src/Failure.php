<?php

declare(strict_types=1);

namespace AlienTech;

final class Failure implements Result {
    private $failure;
    private function __construct($failure) {
        $this->failure = $failure;
    }
    function isSuccess(): bool {
        return false;
    }
    function isFailure(): bool {
        return true;
    }
    function map(callable $f): self {
        return $this;
    }
    function chain(callable $f): self {
        return $this;
    }
    function whenSuccess(callable $f): void {}
    function getWithDefault($default) {
        return $default;
    }

    /**
     * @throws \Exception
     */
    function unsafeGet() {
        throw new \Exception('cannot get success value from failure');
    }

    static function of($x) {
        return new Failure($x);
    }

    function mapFailure(callable $f): Result {
        return self::of(call_user_func($f, $this->failure));
    }

    function chainFailure(callable $f): Result {
        return call_user_func($f, $this->failure);
    }

    function whenFailure(callable $f): void {
        call_user_func($f, $this->failure);
    }

    function unsafeGetFailure() {
        return $this->failure;
    }

    function getOrElse(callable $f) {
        return call_user_func($f, $this->failure);
    }

    function bimap(callable $f, callable $g): self {
        return self::of(call_user_func($f, $this->failure));
    }
}
