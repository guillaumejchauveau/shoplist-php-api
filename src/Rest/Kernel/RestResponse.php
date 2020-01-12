<?php


namespace GECU\Rest\Kernel;


use JsonSerializable;
use Symfony\Component\HttpFoundation\JsonResponse;
use Throwable;

class RestResponse extends JsonResponse
{
    protected $restData;

    public function __construct($data = null, int $status = self::HTTP_OK, array $headers = [])
    {
        parent::__construct($data, $status, $headers, false);

        // Parent constructor will call RestResponse::setData with $data = [] if $data is null.
        if ($data === null) {
            $this->restData = $data;
            $this->update();
        }
    }

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
