<?php

declare(strict_types=1);

namespace AlienTech;

class Read {

    static function field(string $key, ?callable $f = null) {
        return fn(array $xs, \ReflectionProperty $prop) => $f ? call_user_func($f, @$xs[$key], $prop) : @$xs[$key];
    }

    static function fieldPath(array $path, ?callable $f = null) {
        if (count($path) < 2) {
            throw new \InvalidArgumentException();
        }
        return function(array $xs, \ReflectionProperty $prop) use ($path, $f) {
            $head = array_shift($path);
            $x = $xs[$head];
            foreach ($path as $segment) {
                $x = $x[$segment];
            }
            return $f ? call_user_func($f, $x, $prop) : $x;
        };
    }

    static function instance(array $readers = []) {
        return fn(array $props, \ReflectionProperty $prop) => self::newInstance($prop->getType()->getName(), $props, $readers);
    }

    static function getReader(\ReflectionProperty $prop, array $readers): Result {
        $reader = @$readers[$prop->getName()];
        if (is_callable($reader)) {
            return Success::of($reader);
        }
        $type = $prop->getType();
        if (!$type || $type->isBuiltin()) {
            return Success::of(self::field($prop->getName()));
        }
        if (!($type instanceof \ReflectionNamedType)) {
            return Failure::of("type of property {$prop->getName()} is not a named type");
        }
        return Success::of(self::field($prop->getName(), self::instance(@$readers[$prop->getName()] ?? [])));
    }

    static function readInstanceProps($inst, \ReflectionClass $class, array $propValues, array $readers): Result {
        foreach ($class->getProperties() as $prop) {
            if ($prop->isStatic()) continue;
            $result = self::getReader($prop, $readers)->chain(fn($reader) =>
                Results::wrapSuccess(call_user_func($reader, $propValues, $prop)));
            if ($result->isFailure()) {
                return $result;
            }
            $prop->setAccessible(true);
            try {
                $value = $result->unsafeGet();
                $type = $prop->getType();
                if ($type && $type->isBuiltin() && $type instanceof \ReflectionNamedType) {
                    $typeNameMap = [
                        'bool' => 'boolean',
                        'int' => 'integer'
                    ];
                    $valueTypeName = gettype($value);
                    $propTypeName = $type->getName();
                    $propTypeNameNormalized = $typeNameMap[$propTypeName] ?? $propTypeName;
                    if ($valueTypeName === 'NULL' && !$type->allowsNull()) {
                        return Failure::of("property {$prop->getName()} is not nullable but value is null");
                    } else if ($valueTypeName !== 'NULL' && $valueTypeName !== $propTypeNameNormalized) {
                        return Failure::of("property {$prop->getName()} expects type $propTypeNameNormalized but value has type $valueTypeName");
                    }
                }
                $prop->setValue($inst, $value);
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
                ->chain(fn($inst) => self::readInstanceProps($inst, $class, $propValues, $readers)));
    }

}

