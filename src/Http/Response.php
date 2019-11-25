<?php
declare(strict_types=1);


namespace GECU\ShopList\Http;


class Response extends Message
{
    /**
     * Status code to send to the client
     *
     * @var int
     */
    protected $status = 200;

    public function __construct($body = 'php://memory')
    {
        if ($body) {
            $this->setBody($body, 'w+');
        }
    }

    /**
     * Gets the response status code.
     *
     * The status code is a 3-digit integer result code of the server's attempt
     * to understand and satisfy the request.
     *
     * @return int Status code.
     */
    public function getStatusCode(): int
    {
        return $this->status;
    }

    /**
     * Modifier for response status
     *
     * @param int $code The status code to set.
     * @return void
     */
    public function setStatus(int $code): void
    {
        $this->status = $code;
    }
}
