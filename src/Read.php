<?php

declare(strict_types=1);

namespace AlienTech;

class Read {
    static function instanceReader($className) {
        return fn(array $props) => Read::instanceFromArray($className, $props);
    }

    static private function getBuiltinTypeName($x) {
        $map = [
            'boolean' => 'bool',
            'integer' => 'int',
        ];
        $type = gettype($x);
        return $map[$type] ?? $type;
    }

    static private function readProperty(\ReflectionProperty $prop, $instance, $propValues) {
        $propType = $prop->getType();
        if ($propType == null) {
            return Failure::of('no type info for property ' . $prop->getName());
        }
        $propName = $prop->getName();
        $propValue = @$propValues[$propName];
        if (!($propType instanceof \ReflectionNamedType)) {
            return Failure::of("type of $propName is not a named type");
        }
        $propTypeName = $propType->getName();
        $prop->setAccessible(true);

        if ($propValue === null) {
            if ($propType->allowsNull()) {
                $prop->setValue($instance, null);
                return Success::of(true);
            } else {
                return Failure::of($prop->getName() . ' is not nullable but no value is present');
            }
        }

        if ($propType->isBuiltin()) {
            $typeName = self::getBuiltinTypeName($propValue);
            if ($typeName !== $propTypeName) {
                return Failure::of("property {$prop->getName()} expects type $propTypeName but has $typeName");
            }
            $prop->setValue($instance, $propValue);
        } else {
            $propInstance = self::instanceFromArray($propTypeName, $propValue);
            if ($propInstance->isFailure()) {
                return $propInstance;
            }
            $propInstance->whenSuccess(fn($value) => $prop->setValue($instance, $value));
        }
        return Success::of($instance);
    }

    static private function readInstance($inst, \ReflectionClass $class, array $propValues): Result {
        foreach ($class->getProperties() as $prop) {
            if ($prop->isStatic()) continue;
            $result = self::readProperty($prop, $inst, $propValues);
            if ($result->isFailure()) {
                return $result;
            }
        }
        return Success::of($inst);
    }

    static function instanceFromArray(string $className, array $propValues): Result {
        return Results::fromTryCatch(fn() => new \ReflectionClass($className))
            ->chain(fn(\ReflectionClass $class) =>
                Results::fromTryCatch(fn() => $class->newInstance())
                    ->chain(fn($inst) => self::readInstance($inst, $class, $propValues)));
    }
}

