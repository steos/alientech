<?php

declare(strict_types=1);

namespace AlienTech;

class Results
{
    static function fromTryCatch(callable $f): Result {
        try {
            return Success::of(call_user_func($f));
        } catch (\Throwable $ex) {
            return Failure::of($ex);
        }
    }

    static function fromNullable($x): Result {
        return $x === null ? Failure::of($x) : Success::of($x);
    }

    static function fromTruthy($x): Result {
        return $x ? Success::of($x) : Failure::of($x);
    }
}
