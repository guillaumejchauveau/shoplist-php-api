<?php


namespace GECU\Rest\Kernel;


use JsonSerializable;
use Symfony\Component\HttpFoundation\JsonResponse;
use Throwable;

class RestResponse extends JsonResponse
{
    protected $restData;

    public function __construct($data = null, int $status = 200, array $headers = [])
    {
        parent::__construct(static::convertData($data), $status, $headers, false);
        $this->restData = $data;
    }

    protected static function convertData($data)
    {
        if ($data instanceof Throwable && !($data instanceof JsonSerializable)) {
            return [
              'message' => $data->getMessage()
            ];
        }
        return $data;
    }

    public function getData()
    {
        return $this->restData;
    }

    /**
     * @inheritDoc
     */
    public function setData($data = [])
    {
        parent::setData(static::convertData($data));
        $this->restData = $data;
        return $this;
    }
}
