<?php

use AlienTech\Effect;
use AlienTech\Http;
use RestMachine\Resource;

class Todo {
    static function fetchOne(string $id) {
        return Effect::failure("no todo found with id \"$id\"");
    }
}

Http::serve([
    '/' => Resource::create()->handleOk('hello world'),
    '/foo/$id' => fn(string $id) =>
        Todo::fetchOne($id)->map(fn($todo) =>
            Resource::create()->handleOk($todo))
]);

