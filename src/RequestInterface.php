<?php

namespace Imagine\Http;

interface RequestInterface{
    public function setMethod(string $method);
    public function setHost(string $method);
    public function setPort(int $method);
    public function setProtocol(string $method);
	public function setHeaders(array $headers);
	public function setBody(array $body);
	public function setSsl(string $ssl);
	public function getResponse();
}