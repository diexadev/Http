<?php

namespace Imagine\Http;

use Psr\Http\Message\MessageInterface;

abstract class Message implements MessageInterface{
    public function setHeader(string $name, string$value){
		$name = strtolower($name);
		$this->headers[$name] = $value;
	}
	public function getBodyAsStream()
    {
        $body = $this->getBody();
        if (is_callable($this->body)) {
            $body = $this->getBodyAsString();
        }
        if (is_string($body) || null === $body) {
            $stream = fopen('php://temp', 'r+');
            fwrite($stream, (string) $body);
            rewind($stream);

            return $stream;
        }

        return $body;
    }
	public function getBodyAsString(): string
    {
        $body = $this->getBody();
        if (is_string($body)) {
            return $body;
        }
        if (null === $body) {
            return '';
        }
        if (is_callable($body)) {
            ob_start();
            $body();

            return ob_get_clean();
        }
        /**
         * @var string|int|null
         */
        $contentLength = $this->getHeader('Content-Length');
        if (is_int($contentLength) || ctype_digit($contentLength)) {
            return stream_get_contents($body, (int) $contentLength);
        }

        return stream_get_contents($body);
    }
}