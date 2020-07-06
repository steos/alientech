<?php

declare(strict_types=1);

namespace AlienTech;

use GuzzleHttp\Psr7\Request as Psr7Request;
use Psr\Http\Client\ClientInterface as Client;
use Psr\Http\Message\ResponseInterface;
use RestMachine\Resource;
use RestMachine\WebMachine;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Http
{
    static function validateResponseStatus($status): callable {
        return fn(ResponseInterface $res) =>
            $status === $res->getStatusCode()
                ? Success::of($res)
                : Failure::of("Expected response status $status but is " . $res->getStatusCode());
    }

    static function parseJsonResponse(): callable {
        return fn(ResponseInterface $res) => Json::parse($res->getBody()->getContents());
    }

    static function request(Client $client, string $method, string $uri, array $headers = [], $body = null): Effect {
        return Effect::of(fn() => Results::fromTryCatch(fn() => $client->sendRequest(new Psr7Request($method, $uri, $headers, $body))));
    }

    static function get(Client $client, string $uri, array $options = []): Effect {
        return self::request($client, 'GET', $uri, $options);
    }

    static function post(Client $client, string $uri, array $options = []): Effect {
        return self::request($client, 'POST', $uri, $options);
    }

    static function put(Client $client, string $uri, array $options = []): Effect {
        return self::request($client, 'PUT', $uri, $options);
    }

    static function delete(Client $client, string $uri, array $options = []): Effect {
        return self::request($client, 'DELETE', $uri, $options);
    }

    static function serve(array $routes): void {
        self::unsafePerformDispatch(Request::createFromGlobals(), $routes)->send();
    }

    static function unsafePerformDispatch(Request $request, array $routes): Response {
        return self::dispatch($request, $routes)
            ->unsafePerformEffect()
            ->chainFailure(fn($err) => Success::of(
                new Response('Internal Server Error',
                Response::HTTP_INTERNAL_SERVER_ERROR)))
            ->unsafeGet();
    }

    static private function runWebmachine(Resource $resource, Request $request): Response {
        $machine = new WebMachine();
        return $machine->run($resource, $request);
    }

    static function dispatch(Request $request, array $routes): Effect {
        $notFound = new Response('Not Found', Response::HTTP_NOT_FOUND);
        foreach ($routes as $route => $routeValue) {
            $result = self::matchRoute($request->getPathInfo(), $route);
            if ($result->isSome()) {
                $value = is_callable($routeValue) && !($routeValue instanceof Resource)
                    ? call_user_func_array($routeValue, $result->unsafeGet())
                    : $routeValue;
                $eff = $value instanceof Effect ? $value : Effect::success($value);
                return $eff
                    ->map(fn($x) => $x instanceof Optional ? $x : Some::of($x))
                    ->map(fn(Optional $result) =>
                        $result->map(fn(Resource $resource) => self::runWebmachine($resource, $request))
                               ->getWithDefault($notFound));
            }
        }
        return Effect::success($notFound);
    }

    static private function matchRoute(string $path, string $routePattern): Optional {
        $pathSegments = explode('/', $path);
        $routeSegments = explode('/', $routePattern);
        $pathSegmentCount = count($pathSegments);
        if ($pathSegmentCount != count($routeSegments)) {
            return None::get();
        }
        $routeArgs = [];
        for ($i = 0; $i < $pathSegmentCount; ++$i) {
            $pathSegment = $pathSegments[$i];
            $routeSegment = $routeSegments[$i];
            if (substr($routeSegment, 0, 1) == '$') {
                $routeArgs[substr($routeSegment, 1)] = $pathSegment;
            } else if ($routeSegment != $pathSegment) {
                return None::get();
            }
        }
        return Some::of($routeArgs);
    }
}
