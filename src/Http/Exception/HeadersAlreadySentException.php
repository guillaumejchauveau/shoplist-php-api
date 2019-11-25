<?php
declare(strict_types=1);


namespace GECU\ShopList\Http\Exception;


use Throwable;

class HeadersAlreadySentException extends InternalServerErrorException
{
    public function __construct(
      ?string $message = 'Headers already sent',
      ?int $code = null,
      ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
