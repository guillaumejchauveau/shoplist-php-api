<?php
declare(strict_types=1);


namespace GECU\ShopList\Utils;


use InvalidArgumentException;
use LogicException;
use Throwable;

class Stream
{
    protected $resource;

    public function __construct($stream, ?string $mode)
    {
        $resource = $stream;
        if (is_string($stream)) {
            set_error_handler(function ($e) {
                if ($e === E_WARNING) {
                    throw new InvalidArgumentException("Could not open stream: $e");
                }
            });
            $resource = fopen($stream, $mode ?: 'r');
            restore_error_handler();
        }
        if (!is_resource($resource) || get_resource_type($resource) !== 'stream') {
            throw new InvalidArgumentException('Invalid resource');
        }
        $this->resource = $resource;
    }

    public function write(string $string): int
    {
        return fwrite($this->resource, $string);
    }

    public function __toString(): string
    {
        try {
            return $this->getContents();
        } catch (Throwable $e) {
            return '';
        }
    }

    public function getContents(): string
    {
        $this->rewind();
        return stream_get_contents($this->resource);
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function seek(int $offset, $whence = SEEK_SET): void
    {
        if (!$this->isSeekable()) {
            throw new LogicException('Stream is not seekable');
        }
        fseek($this->resource, $offset, $whence);
    }

    public function isSeekable(): bool
    {
        $meta = stream_get_meta_data($this->resource);
        return $meta['seekable'];
    }
}
