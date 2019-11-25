<?php
declare(strict_types=1);


namespace GECU\ShopList\Http;


class ServerRequest extends Message
{
    /**
     * @var Uri
     */
    protected $uri;
    /**
     *
     */
    protected $server;

    public function __construct($config = []
    ) {
        $config += [
          'server' => [],
          'uri' => null,
          'headers' => [],
          'body' => null
        ];
        $this->server = $config['server'];
        $this->setUri($config['uri']);
        $this->headers = $config['headers'];
        $this->setBody($config['body']);
    }

    /**
     * Get the HTTP method used for this request.
     * There are a few ways to specify a method.
     *
     * - If your client supports it you can use native HTTP methods.
     * - You can set the HTTP-X-Method-Override header.
     * - You can submit an input with the name `_method`
     *
     * Any of these 3 approaches can be used to set the HTTP method used
     * by CakePHP internally, and will effect the result of this method.
     *
     * @return string The name of the HTTP method used.
     * @link http://www.php-fig.org/psr/psr-7/ This method is part of the PSR-7 server request interface.
     */
    public function getMethod(): string
    {
        return $this->getServerParam('REQUEST_METHOD');
    }

    public function getServerParam(string $key): string
    {
        return $this->server[$key];
    }

    /**
     * Retrieves the URI instance.
     *
     * @return Uri Returns a UriInterface instance
     *   representing the URI of the request.
     */
    public function getUri(): Uri
    {
        return $this->uri;
    }

    public function setUri(Uri $uri)
    {
        $this->uri = $uri;
        $host = $uri->getHost();
        if (!$host) {
            return;
        }
        $port = $uri->getPort();
        if ($port) {
            $host .= ':' . $port;
        }
        $this->setHeader('HOST', $host);
    }

    /**
     * Get the path of current request.
     *
     * @return string
     * @since 3.6.1
     */
    public function getPath(): string
    {
        return $this->uri->getPath();
    }

    /**
     *
     * @return array
     */
    public function getQuery(): array
    {
        return $this->uri->getQuery();
    }

    public function getQueryValue(string $key): array
    {
        return $this->uri->getQueryValue($key);
    }

    /**
     *
     * @param array $query The query string data to use
     */
    public function setQuery(array $query): void
    {
        $this->uri->setQuery($query);
    }

    public function setQueryValue(string $key, string $value): void
    {
        $this->uri->setQueryValue($key, $value);
    }

    protected function setServerParam(string $key, $value)
    {
        $this->server[$key] = $value;
    }
}
