<?php

namespace AlienTech;

class Json {
    static function parse(string $str, bool $assoc = true, int $depth = 512, int $options = 0): Result {
        $json = json_decode($str, $assoc, $depth, $options);
        return $json === null ? Failure::of(json_last_error_msg()) : Success::of($json);
    }
}
