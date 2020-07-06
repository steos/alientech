<?php

declare(strict_types=1);

namespace AlienTech;

final class Effect {
    private $effectFn;
    private function __construct(callable $fn) {
        $this->effectFn = $fn;
    }
    function map(callable $f): Effect {
        return new Effect(fn() => $this->unsafePerformEffect()->map($f));
    }
    function chain(callable $f): Effect {
        return new Effect(function() use ($f) {
            $result = $this->unsafePerformEffect();
            if ($result->isSuccess()) {
                $x = call_user_func($f, $result->unsafeGet());
                if (!($x instanceof Effect)) {
                    throw new \RuntimeException();
                }
                return $x->unsafePerformEffect();
            }
            return $result;
        });
    }
    function unsafePerformEffect(): Result {
        $result = call_user_func($this->effectFn);
        return $result instanceof Result ? $result : Success::of($result);
    }
    static function of(callable $f): self {
        return new Effect($f);
    }
    static function success($x) {
        return Effect::of(fn() => Success::of($x));
    }
    static function failure($x) {
        return Effect::of(fn() => Failure::of($x));
    }
}
