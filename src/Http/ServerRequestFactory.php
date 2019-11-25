<?php
declare(strict_types=1);

namespace GECU\ShopList\Http;


use GECU\ShopList\Utils\PhpInputStream;

abstract class ServerRequestFactory
{
    public static function fromGlobals()
    {
        $user_host = explode(':', $_SERVER['HTTP_HOST']);
        if (!isset($user_host[1])) {
            $user_host[] = Uri::DEFAULT_SCHEME_PORTS[$_SERVER['REQUEST_SCHEME']];
        }
        $uri = new Uri([
          'scheme' => $_SERVER['REQUEST_SCHEME'],
          'user' => isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : null,
          'password' => isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : null,
          'host' => $user_host[0],
          'port' => intval($user_host[1]),
          'path' => explode('?', $_SERVER['REQUEST_URI'])[0],
          'query' => $_SERVER['QUERY_STRING']
        ]);
        return new ServerRequest([
          'server' => $_SERVER,
          'uri' => $uri,
          'headers' => getallheaders(),
          'body' => new PhpInputStream()
        ]);
    }
}
