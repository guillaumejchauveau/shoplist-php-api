<?php


namespace GECU\Rest\Kernel;


use JsonSerializable;
use Symfony\Component\HttpFoundation\JsonResponse;
use Throwable;

/**
 * A JsonResponse that keeps the original data.
 */
class RestResponse extends JsonResponse
{
    /**
     * @var mixed|null
     */
    protected $restData;

    /**
     * @inheritDoc
     */
    public function __construct($data = null, int $status = self::HTTP_OK, array $headers = [])
    {
        parent::__construct($data, $status, $headers, false);
        $this->headers->set('Access-Control-Allow-Origin', '*');

        // Parent constructor will call RestResponse::setData with $data = [] if $data is null.
        if ($data === null) {
            $this->restData = $data;
            $this->update();
        }
    }

    /**
     * @inheritDoc
     */
    protected function update(): self
    {
        parent::update();
        if ($this->getData() === null && $this->isSuccessful()) {
            $this->setStatusCode(self::HTTP_NO_CONTENT);
            $this->setContent(null);
            $this->headers->remove('Content-Type');
        }
        return $this;
    }

    /**
     * @return mixed|null The original data of the response
     */
    public function getData()
    {
        return $this->restData;
    }

    /**
     * @inheritDoc
     */
    public function setData($data = null): self
    {
        $this->restData = $data;
        if ($data instanceof Throwable && !($data instanceof JsonSerializable)) {
            $data = [
              'message' => $data->getMessage()
            ];
        }
        parent::setData($data);
        return $this;
    }
}
