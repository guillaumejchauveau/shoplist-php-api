<?php
declare(strict_types=1);


namespace GECU\ShopList\Rest\Resource;


use JsonSerializable;

interface ResourceInterface extends JsonSerializable
{
    public static function getResourceName(): ?string;

    public static function fromJsonDecode($json): ResourceInterface;
}
