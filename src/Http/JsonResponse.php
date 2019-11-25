<?php
declare(strict_types=1);


namespace GECU\ShopList\Http;


use LogicException;

class JsonResponse extends Response
{
    protected $data;

    public function __construct()
    {
        parent::__construct(null);
        $this->setContentType('application/json');
    }

    public function getBodyContent(): string
    {
        return json_encode($this->data, JSON_THROW_ON_ERROR);
    }

    public function appendBodyContent(string $content): void
    {
        throw new LogicException("Cannot write contents of Json response");
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data): void
    {
        $this->data = $data;
    }
}
