<?php
declare(strict_types=1);


namespace GECU\ShopList\Utils;


class PhpInputStream extends Stream
{
    /**
     * @var string
     */
    private $cache = '';
    /**
     * @var bool
     */
    private $reachedEof = false;

    /**
     * @param string|resource $stream
     */
    public function __construct($stream = 'php://input')
    {
        parent::__construct($stream, 'r');
    }

    public function getContents(): string
    {
        if ($this->reachedEof) {
            return $this->cache;
        }
        $contents = stream_get_contents($this->resource);
        $this->cache .= $contents;
        if (feof($this->resource)) {
            $this->reachedEof = true;
        }
        return $contents;
    }
}
