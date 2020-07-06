<?php

declare(strict_types=1);

namespace AlienTech;

final class Success implements Result {
    private $success;
    private function __construct($success) {
        $this->success = $success;
    }
    function isSuccess(): bool {
        return true;
    }
    function isFailure(): bool {
        return false;
    }
    function map(callable $f): self {
        return new Success(call_user_func($f, $this->success));
    }
    function chain(callable $f): Result {
        return call_user_func($f, $this->success);
    }
    function whenSuccess(callable $f): void {
        call_user_func($f, $this->success);
    }
    function getWithDefault($default) {
        return $this->success;
    }
    function unsafeGet() {
        return $this->success;
    }
    static function of($x) {
        return new Success($x);
    }
    function mapFailure(callable $f): self {
        return $this;
    }
    function chainFailure(callable $f): Result {
        return $this;
    }
    function whenFailure(callable $f): void {}

    /**
     * @throws \Exception
     */
    function unsafeGetFailure() {
        throw new \Exception('cannot get failure value from success');
    }

    function getOrElse(callable $f) {
        return $this->success;
    }

    function bimap(callable $f, callable $g): self {
        return self::of(call_user_func($g, $this->success));
    }

    function fold(callable $f, callable $g) {
        return call_user_func($g, $this->success);
    }
}
