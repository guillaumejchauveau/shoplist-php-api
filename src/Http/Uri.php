<?php
declare(strict_types=1);


namespace GECU\ShopList\Http;

use Throwable;

class Uri
{
    public const DEFAULT_SCHEME_PORTS = [
      'http' => 80,
      'https' => 443,
    ];
    /**
     * @var string
     */
    protected $scheme;
    /**
     * @var string
     */
    protected $user;
    /**
     * @var string
     */
    protected $password;
    /**
     * @var string
     */
    protected $host;
    /**
     * @var int
     */
    protected $port;
    /**
     * @var string
     */
    protected $path;
    /**
     * @var string[][]
     */
    protected $query;
    /**
     * @var string
     */
    protected $fragment;

    public function __construct($config = [])
    {
        $config += [
          'scheme' => '',
          'user' => '',
          'password' => null,
          'host' => '',
          'port' => null,
          'path' => '',
          'query' => []
        ];
        $this->scheme = $config['scheme'];
        $this->user = $config['user'];
        $this->password = $config['password'];
        $this->host = $config['host'];
        $this->port = $config['port'];
        $this->path = $config['path'];
        $this->query = static::parseQuery($config['query']);
    }

    protected static function parseQuery($query): array
    {
        if (is_array($query)) {
            return $query;
        }
        if (strpos($query, '?') === 0) {
            $query = substr($query, 1);
        }
        $parts = explode('&', $query);
        $query = [];
        foreach ($parts as $index => $part) {
            $data = explode('=', $part, 2);
            if (!isset($data[1])) {
                $data[] = null;
            }
            [$name, $value] = $data;
            if (!isset($query[$name])) {
                $query[$name] = [];
            }
            $query[$name][] = $value;
        }
        return $query;
    }

    public function __toString(): string
    {
        try {
            return static::createUriString(
              $this->scheme,
              $this->getAuthority(),
              $this->getPath(),
              $this->query,
              $this->fragment
            );
        } catch (Throwable $e) {
            return '';
        }
    }

    /**
     * Create a URI string from its various parts
     * @param string $scheme
     * @param string $authority
     * @param string $path
     * @param array $query
     * @param string $fragment
     * @return string
     */
    protected static function createUriString(
      string $scheme,
      string $authority,
      string $path,
      array $query,
      string $fragment
    ): string {
        $uri = '';
        if ('' !== $scheme) {
            $uri .= sprintf('%s:', $scheme);
        }
        if ('' !== $authority) {
            $uri .= '//' . $authority;
        }
        if ('' !== $path && '/' !== substr($path, 0, 1)) {
            $path = '/' . $path;
        }
        $uri .= $path;

        $uri .= '?';
        foreach ($query as $key => $value) {
            $uri .= $key;
            if ($value !== null) {
                $uri .= '=' . $value;
            }
            $uri .= '&';
        }
        substr($uri, -1);

        if ('' !== $fragment) {
            $uri .= sprintf('#%s', $fragment);
        }
        return $uri;
    }

    public function getAuthority(): string
    {
        if ('' === $this->getHost()) {
            return '';
        }
        $authority = $this->getHost();
        if ('' !== $this->getUserInfo()) {
            $authority = $this->getUserInfo() . '@' . $authority;
        }
        if ($this->isNonStandardPort($this->scheme, $this->host, $this->port)) {
            $authority .= ':' . $this->port;
        }
        return $authority;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function setHost(string $host): void
    {
        $this->host = strtolower($host);
    }

    public function getUserInfo(): string
    {
        $userInfo = $this->getUser();
        if ($this->getPassword() !== null) {
            $userInfo .= ':' . $this->getPassword();
        }
        return $userInfo;
    }

    public function getUser(): string
    {
        return $this->user;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Is a given port non-standard for the current scheme?
     * @param string $scheme
     * @param string $host
     * @param int|null $port
     * @return bool
     */
    protected function isNonStandardPort(string $scheme, string $host, ?int $port): bool
    {
        if ('' === $scheme) {
            return '' === $host || null !== $port;
        }
        if ('' === $host || null === $port) {
            return false;
        }
        return !isset($this->allowedSchemes[$scheme]) || $port !== self::DEFAULT_SCHEME_PORTS[$scheme];
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function setScheme(string $scheme): void
    {
        $this->scheme = $scheme;
    }

    /**
     * @param string $user
     * @param string $password
     */
    public function setUserInfo(string $user, string $password = null): void
    {
        $this->user = $user;
        $this->password = $password;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function setPort(int $port): void
    {
        $this->port = $port;
    }

    public function withPath(string $path): Uri
    {
        $new = clone $this;
        $new->path = $path;
        return $new;
    }

    public function getQuery(): array
    {
        return $this->query;
    }

    public function setQuery($query): void
    {
        $this->query = static::parseQuery($query);
    }

    public function getQueryValue(string $key): array
    {
        return $this->query[$key];
    }

    public function setQueryValue(string $key, string $value): void
    {
        $this->query[$key] = $value;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    public function setFragment(string $fragment): void
    {
        $this->fragment = $fragment;
    }
}
