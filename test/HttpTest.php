<?php

declare(strict_types=1);

namespace AlienTech;

use PHPUnit\Framework\TestCase;
use RestMachine\Resource;
use Symfony\Component\HttpFoundation\Request;

class HttpTest extends TestCase  {
    function assertSuccess(Result $x) {
        if ($x->isFailure()) {
            $this->fail($x->unsafeGetFailure());
        }
    }
    function get($path) {
        return Request::create('http://example.com/' . trim($path, '/'));
    }
    static function fetchOne(string $id) {
        if ($id === '42') {
            return Effect::success(Some::of([
                'id' => 42,
                'text' => 'switch to alien technology',
                'done' => true
            ]));
        }
        return Effect::success(None::get());
    }
    function testDispatch() {
        $request = Request::create('http://example.com/foo/123');
        $request->headers->set('Accept', 'application/json');
        $routes = [
            '/' => Resource::create()->handleOk('{"hello": "world"}'),
            '/foo/$id' => fn(string $id) =>
                self::fetchOne($id)->map(fn(Optional $result) =>
                    $result->map(fn($todo) =>
                        Resource::create()
                            ->availableMediaTypes(['application/json'])
                            ->handleOk($todo)))
        ];
        $response = Http::unsafePerformDispatch($this->get('/foo/123'), $routes);
        $this->assertEquals(404, $response->getStatusCode());

        $response = Http::unsafePerformDispatch($this->get('/foo/42'), $routes);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotFalse($response->getContent());
        $body = Json::parse($response->getContent(), false);
        $this->assertSuccess($body);
        $todo = $body->unsafeGet();
        $this->assertEquals((object)[
            'id' => 42,
            'text' => 'switch to alien technology',
            'done' => true
        ], $todo);

        $response = Http::unsafePerformDispatch($this->get('/'), $routes);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('{"hello": "world"}', $response->getContent());
    }
}
