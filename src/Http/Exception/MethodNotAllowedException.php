<?php
declare(strict_types=1);


namespace GECU\ShopList\Http\Exception;


use Throwable;

class MethodNotAllowedException extends HttpException
{
    public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
    {
        parent::__construct($message ?: 'Method Not Allowed', $code ?: 405, $previous);
    }
}
