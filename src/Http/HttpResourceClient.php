<?php

declare(strict_types=1);

namespace BEAR\Dev\Http;

use BEAR\Resource\RequestInterface;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use InvalidArgumentException;
use LogicException;
use Ray\Di\InjectorInterface;
use ReflectionClass;

use function assert;
use function class_exists;
use function dirname;
use function exec;
use function file_put_contents;
use function filter_var;
use function http_build_query;
use function implode;
use function json_encode;
use function parse_url;
use function sprintf;
use function strrpos;
use function substr;

use const FILE_APPEND;
use const FILTER_VALIDATE_URL;
use const PHP_EOL;
use const PHP_URL_PATH;
use const PHP_URL_QUERY;

class HttpResourceClient implements ResourceInterface
{
    /** @var ResourceInterface */
    private $resource;

    /** @var string */
    private $logFile = '';

    /** @var string */
    private $baseUri;

    /**
     * @psalm-param class-string $className
     */
    public function __construct(string $baseUri, InjectorInterface $injector, string $className)
    {
        if (! filter_var($baseUri, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException($baseUri);
        }

        $this->logFile = $this->getLogFile($className);
        file_put_contents($this->logFile, '');
        $this->resource = $injector->getInstance(ResourceInterface::class);
        $this->baseUri = $baseUri;
    }

    public function newInstance($uri): ResourceObject
    {
        throw new LogicException();
    }

    public function object(ResourceObject $ro): RequestInterface
    {
        throw new LogicException();
    }

    public function uri($uri): RequestInterface
    {
        throw new LogicException();
    }

    public function href(string $rel, array $query = []): ResourceObject
    {
        throw new LogicException();
    }

    public function get(string $uri, array $query = []): ResourceObject
    {
        $response = $this->resource->get($uri, $query);
        $this->safeLog($uri, $query);

        return $response;
    }

    public function post(string $uri, array $query = []): ResourceObject
    {
        $response = $this->resource->put($uri, $query);
        $this->unsafeLog('POST', $uri, $query);

        return $response;
    }

    public function put(string $uri, array $query = []): ResourceObject
    {
        $response = $this->resource->put($uri, $query);
        $this->unsafeLog('PUT', $uri, $query);

        return $response;
    }

    public function patch(string $uri, array $query = []): ResourceObject
    {
        throw new LogicException();
    }

    public function delete(string $uri, array $query = []): ResourceObject
    {
        $response = $this->resource->put($uri, $query);
        $this->unsafeLog('DELETE', $uri, $query);

        return $response;
    }

    public function head(string $uri, array $query = []): ResourceObject
    {
        throw new LogicException();
    }

    public function options(string $uri, array $query = []): ResourceObject
    {
        throw new LogicException();
    }

    public function getLogFile(string $className): string
    {
        $class = substr($className, (int) strrpos($className, '\\') + 1);
        assert(class_exists($className));
        $dir = dirname((string) (new ReflectionClass($className))->getFileName());

        return sprintf('%s/log/%s.log', $dir, $class);
    }

    /**
     * @param array<string> $query
     */
    private function safeLog(string $uri, array $query): void
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $query += (array) parse_url($uri, PHP_URL_QUERY);
        $queryParameter = $query ? '?' . http_build_query($query) : '';
        $curl = sprintf("curl -s -i '%s%s%s'", $this->baseUri, $path, $queryParameter);
        exec($curl, $output);
        $responseLog = implode(PHP_EOL, $output);
        $log = sprintf("%s\n\n%s", $curl, $responseLog) . PHP_EOL . PHP_EOL;
        file_put_contents($this->logFile, $log, FILE_APPEND);
    }

    /**
     * @param array<string> $query
     */
    private function unsafeLog(string $method, string $uri, array $query): void
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $query += (array) parse_url($uri, PHP_URL_QUERY);
        $json = json_encode($query);
        $curl = sprintf("curl -s -i -H 'Content-Type:application/json' -X %s -d '%s' %s%s", $method, $json, $this->baseUri, $path);
        exec($curl, $output);
        $responseLog = implode(PHP_EOL, $output);
        $log = sprintf("%s\n\n%s", $curl, $responseLog) . PHP_EOL . PHP_EOL;
        file_put_contents($this->logFile, $log, FILE_APPEND);
    }
}
