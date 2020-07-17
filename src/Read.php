<?php

declare(strict_types=1);

namespace AlienTech;

class Read {
    static function instanceReader($className) {
        return fn(array $props) => Read::newInstance($className, $props);
    }

    static function key(string $key) {
        return fn(array $xs) => @$xs[$key];
    }

    static function path(array $path) {
        if (count($path) < 2) {
            throw new \InvalidArgumentException();
        }
        return function(array $xs) use ($path) {
            $head = array_shift($path);
            $x = $xs[$head];
            foreach ($path as $segment) {
                $x = $x[$segment];
            }
            return $x;
        };
    }

    static function instance(string $key, string $className, array $readers = []) {
        return fn(array $props) => Read::newInstance($className, @$props[$key], $readers);
    }

    static function getReader(\ReflectionProperty $prop, array $readers) {
        $reader = @$readers[$prop->getName()];
        if (is_callable($reader)) {
            return $reader;
        }
        $type = $prop->getType();
        if (!$type) {
            return self::key($prop->getName());
        }
        if ($type->isBuiltin()) {
            return self::key($prop->getName());
        } else {
            return self::instance($prop->getName(), $type->getName(), @$readers[$prop->getName()] ?? []);
        }
    }

    static function readInstance($inst, \ReflectionClass $class, array $propValues, array $readers) {
        foreach ($class->getProperties() as $prop) {
            if ($prop->isStatic()) continue;
            $reader = self::getReader($prop, $readers);
            $result = Results::wrapSuccess(call_user_func($reader, $propValues));
            if ($result->isFailure()) {
                return $result;
            }
            $prop->setAccessible(true);
            try {
                $prop->setValue($inst, $result->unsafeGet());
            } catch (\TypeError $err) {
                return Failure::of($err->getMessage());
            }
        }
        return Success::of($inst);
    }

    static function newInstance(string $className, array $propValues, array $readers = []): Result {
        return Results::fromTryCatch(fn() => new \ReflectionClass($className))
            ->chain(fn(\ReflectionClass $class) =>
            Results::fromTryCatch(fn() => $class->newInstance())
                ->chain(fn($inst) => self::readInstance($inst, $class, $propValues, $readers)));
    }

}

