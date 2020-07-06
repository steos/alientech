<?php

declare(strict_types=1);

namespace AlienTech;

interface Optional {
    function isSome(): bool;
    function isNone(): bool;
    function map(callable $f): self;
    function chain(callable $f): Optional;
    function whenSome(callable $f): void;
    function whenNone(callable $f): void;

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
}
