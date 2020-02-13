<?php

declare(strict_types=1);

namespace BEAR\Dev\Http;

use BEAR\Resource\RequestInterface;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use function implode;
use function json_encode;
use const PHP_EOL;
use const PHP_URL_QUERY;
use Ray\Di\InjectorInterface;
use function sprintf;

class HttpResourceClient implements ResourceInterface
{
    /**
     * @var ResourceInterface
     */
    private $resource;

    /**
     * @var string
     */
    private $fileName;

    public function __construct(InjectorInterface $injector, string $fileName)
    {
        $this->resource = $injector->getInstance(ResourceInterface::class);
        $this->fileName = $fileName;
    }

    public function newInstance($uri) : ResourceObject
    {
        throw new \LogicException;
    }

    public function object(ResourceObject $ro) : RequestInterface
    {
        throw new \LogicException;
    }

    public function uri($uri) : RequestInterface
    {
        throw new \LogicException;
    }

    public function href(string $rel, array $query = []) : ResourceObject
    {
        throw new \LogicException;
    }

    public function get(string $uri, array $query = []) : ResourceObject
    {
        $response = $this->resource->get($uri, $query);
        $this->safeLog($uri, $query);

        return $response;
    }

    public function post(string $uri, array $query = []) : ResourceObject
    {
        $response = $this->resource->put($uri, $query);
        $this->unsafeLog('POST', $uri, $query);

        return $response;
    }

    public function put(string $uri, array $query = []) : ResourceObject
    {
        $response = $this->resource->put($uri, $query);
        $this->unsafeLog('PUT', $uri, $query);

        return $response;
    }

    public function patch(string $uri, array $query = []) : ResourceObject
    {
        throw new \LogicException;
    }

    public function delete(string $uri, array $query = []) : ResourceObject
    {
        $response = $this->resource->put($uri, $query);
        $this->unsafeLog('DELETE', $uri, $query);

        return $response;
    }

    public function head(string $uri, array $query = []) : ResourceObject
    {
        throw new \LogicException;
    }

    public function options(string $uri, array $query = []) : ResourceObject
    {
        throw new \LogicException;
    }

    private function safeLog(string $uri, array $query) : void
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $query = $query + (array) parse_url($uri, PHP_URL_QUERY);
        $queryParameter = $query ? '?' . http_build_query($query) : '';
        $curl = sprintf("curl -s -i 'http://127.0.0.1:8088%s%s'", $path, $queryParameter);
        exec($curl, $output);
        $responseLog = implode($output, PHP_EOL);
        $log = sprintf("%s\n\n%s", $curl, $responseLog) . PHP_EOL . PHP_EOL;
        file_put_contents($this->fileName, $log, FILE_APPEND);
    }

    private function unsafeLog(string $method, string $uri, array $query) : void
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $query = $query + (array) parse_url($uri, PHP_URL_QUERY);
        $json = json_encode($query);
        $curl = sprintf("curl -s -i -H 'Content-Type:application/json' -X %s -d '%s' http://127.0.0.1:8088%s", $method, $json, $path);
        exec($curl, $output);
        $responseLog = implode($output, PHP_EOL);
        $log = sprintf("%s\n\n%s", $curl, $responseLog) . PHP_EOL . PHP_EOL;
        file_put_contents($this->fileName, $log, FILE_APPEND);
    }
}