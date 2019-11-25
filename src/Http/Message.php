<?php
declare(strict_types=1);


namespace GECU\ShopList\Http;


use GECU\ShopList\Utils\Stream;
use Throwable;

abstract class Message
{
    protected $charset = 'UTF-8';
    protected $headers = [];
    /**
     * @var Stream
     */
    protected $body;

    /**
     * Retrieves all message headers.
     *
     * The keys represent the header name as it will be sent over the wire, and
     * each value is an array of strings associated with the header.
     *
     *     // Represent the headers as a string
     *     foreach ($message->getHeaders() as $name => $values) {
     *         echo $name . ": " . implode(", ", $values);
     *     }
     *
     *     // Emit headers iteratively:
     *     foreach ($message->getHeaders() as $name => $values) {
     *         foreach ($values as $value) {
     *             header(sprintf('%s: %s', $name, $value), false);
     *         }
     *     }
     *
     * @return array Returns an associative array of the message's headers. Each
     *     key MUST be a header name, and each value MUST be an array of strings.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Clear header
     *
     * @param string $header Header key.
     * @return void
     */
    public function clearHeader(string $header): void
    {
        if (!$this->hasHeader($header)) {
            return;
        }
        $normalized = strtolower($header);
        unset($this->headers[$normalized]);
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $header Case-insensitive header name.
     * @return bool Returns true if any header names match the given header
     *     name using a case-insensitive string comparison. Returns false if
     *     no matching header name is found in the message.
     */
    public function hasHeader(string $header): bool
    {
        return isset($this->headers[strtolower($header)]);
    }

    /**
     * Returns the current content type.
     *
     * @return string
     */
    public function getContentType(): string
    {
        return $this->getHeader('Content-Type');
    }

    /**
     * Retrieves a message header value by the given case-insensitive name.
     *
     * This method returns an array of all the header values of the given
     * case-insensitive header name.
     *
     * If the header does not appear in the message, this method MUST return an
     * empty array.
     *
     * @param string $header Case-insensitive header field name.
     * @return string|null
     */
    public function getHeader(string $header): ?string
    {
        $normalized = strtolower($header);
        if (!$this->hasHeader($normalized)) {
            return null;
        }
        return $this->headers[$normalized];
    }

    /**
     * Formats the Content-Type header based on the configured contentType and charset
     * the charset will only be set in the header if the response is of type text/*
     *
     * @param string $contentType
     * @return void
     */
    public function setContentType(string $contentType): void
    {
        $whitelist = [
          'application/javascript',
          'application/xml',
          'application/rss+xml',
        ];
        if (
          strpos($contentType, 'text/') === 0 ||
          in_array($contentType, $whitelist, true)
        ) {
            $this->setHeader('Content-Type', "{$contentType}; charset={$this->charset}");
        } else {
            $this->setHeader('Content-Type', $contentType);
        }
    }

    /**
     * Sets a header.
     *
     * @param string $header Header key.
     * @param string $value Header value.
     * @return void
     */
    public function setHeader(string $header, string $value): void
    {
        $normalized = strtolower($header);
        $this->headers[$normalized] = $value;
    }

    public function appendBodyContent(string $content): void
    {
        $this->body->write($content);
    }

    public function __toString(): string
    {
        try {
            return $this->getBodyContent();
        } catch (Throwable $e) {
            return '';
        }
    }

    public function getBodyContent(): string
    {
        return $this->body->getContents();
    }

    protected function setBody($body, ?string $mode = null): void
    {
        if ($body instanceof Stream) {
            $this->body = $body;
        } else {
            $this->body = new Stream($body, $mode);
        }
    }
}
