<?php

declare(strict_types=1);

namespace AlienTech;

interface Result {
    function isSuccess(): bool;
    function isFailure(): bool;
    function map(callable $f): self;
    function mapFailure(callable $f): self;
    function chain(callable $f): Result;
    function chainFailure(callable $f): Result;
    function whenSuccess(callable $f): void;
    function whenFailure(callable $f): void;
    function bimap(callable $f, callable $g): self;

    /**
     * @return mixed
     */
    function getOrElse(callable $f);

    /**
     * @param $default
     * @return mixed
     */
    function getWithDefault($default);
    /**
     * @return mixed
     */
    function unsafeGet();

    /**
     * @return mixed
     */
    function unsafeGetFailure();
}
