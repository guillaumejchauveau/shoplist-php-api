<?php
declare(strict_types=1);


namespace GECU\ShopList\Http;


use GECU\ShopList\Http\Exception\HeadersAlreadySentException;
use RuntimeException;
use Throwable;

class Server
{
    /**
     * @var ServerRequestHandlerInterface
     */
    protected $requestHandler;
    /**
     * @var ErrorHandlerInterface
     */
    protected $errorHandler;

    public function __construct(
      ServerRequestHandlerInterface $requestHandler,
      ?ErrorHandlerInterface $errorHandler = null
    ) {
        $this->requestHandler = $requestHandler;
        $this->errorHandler = $errorHandler;
    }

    public static function crash(Throwable $e)
    {
        http_response_code($e->getCode() ?: 500);
        header('Content-Type: text/plain', true);
        echo $e->getMessage();
    }

    public function emit(Response $response): void
    {
        if (headers_sent()) {
            throw new HeadersAlreadySentException();
        }
        http_response_code($response->getStatusCode());
        $this->emitHeaders($response);
        $this->emitBody($response);
    }

    protected function emitHeaders(Response $response): void
    {
        foreach ($response->getHeaders() as $name => $value) {
            header("$name: $value", true);
        }
    }

    protected function emitBody(Response $response): void
    {
        if (in_array($response->getStatusCode(), [204, 304], true)) {
            return;
        }
        echo $response->getBodyContent();
    }

    public function run(?ServerRequest $request = null): Response
    {
        $request = $request ?: ServerRequestFactory::fromGlobals();
        try {
            return $this->requestHandler->handle($request);
        } catch (RuntimeException $e) {
            if ($this->errorHandler) {
                return $this->errorHandler->handle($e);
            }
            throw $e;
        }
    }
}
