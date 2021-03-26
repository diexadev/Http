<?php

namespace Imagine\Http;

use Psr\Http\Message\ResponseInterface;

class Response extends Message implements ResponseInterface{
	private $codes = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authorative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status', // RFC 4918
        208 => 'Already Reported', // RFC 5842
        226 => 'IM Used', // RFC 3229
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot', // RFC 2324
        421 => 'Misdirected Request', // RFC7540 (HTTP/2)
        422 => 'Unprocessable Entity', // RFC 4918
        423 => 'Locked', // RFC 4918
        424 => 'Failed Dependency', // RFC 4918
        426 => 'Upgrade Required',
        428 => 'Precondition Required', // RFC 6585
        429 => 'Too Many Requests', // RFC 6585
        431 => 'Request Header Fields Too Large', // RFC 6585
        451 => 'Unavailable For Legal Reasons', // draft-tbray-http-legally-restricted-status
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage', // RFC 4918
        508 => 'Loop Detected', // RFC 5842
        509 => 'Bandwidth Limit Exceeded', // non-standard
        510 => 'Not extended',
        511 => 'Network Authentication Required', // RFC 6585
    ];
	private $protocol;
	private $code;
	private $phrase;
	private $headers;
	private $body;
	private $cookies;
	
	public function __construct(Request $request, int $code = 500, array $headers = null, $body = null){
		$this->request = $request;
        if(null !== $code){
            $this->setCode($code);
        }
        if(null !== $headers){
            $this->setHeaders($headers);
        }
        if(null !== $body){
            $this->setBody($body);
        }
    }
	public function setCode(int $code = 500){
		if($code < 100 || $code > 599){
			throw new \Exception("Bad HTTP response '$code'.");
		}
		$this->code = $code;
		$this->phrase = $this->codes[$code];
		return $this;
	}
	public function getCode(): int{return $this->code;}
	public function setHeader(string $name, string $value){$this->headers[$name] = $value;}
	public function addHeader(string $name, string $value){}
	public function deleteHeader(string $name){header_remove($name);return $this;}
	public function getHeader(string $name){return $this->headers[$name];}
	public function getHeaders(){return $this->headers;}
	public function setContentType(string $type, string $charset = null){
		$this->setHeader('Content-Type', $type . ($charset ? '; charset=' . $charset : ''));
		return $this;
	}
	public function setExpiration(?string $time){
		$this->setHeader('Pragma', null);
		if(!$time){ // no cache
			$this->setHeader('Cache-Control', 's-maxage=0, max-age=0, must-revalidate');
			$this->setHeader('Expires', 'Mon, 23 Jan 1978 10:00:00 GMT');
			return $this;
		}

		$time = DateTime::from($time);
		$this->setHeader('Cache-Control', 'max-age=' . ($time->format('U') - time()));
		$this->setHeader('Expires', Helpers::formatDate($time));
		return $this;
	}
	public function setCookie(
		string $name,
		string $value,
		int $time,
		string $path = null,
		string $domain = null,
		bool $secure = null,
		bool $httpOnly = null
	){
		$options = [
			'expires' => $time,
			'path' => $path,
			'domain' => $domain,
			'secure' => $secure,
			'httponly' => $httpOnly,
			'samesite' => $sameSite
		];
		if(PHP_VERSION_ID >= 70300){
			setcookie($name, $value, $options);
		}else{
			setcookie(
				$name,
				$value,
				$options['expires'],
				$options['path'],
				$options['domain'],
				$options['secure'],
				$options['httponly']
			);
		}
		return $this;
	}
	public function setBody($body){
		$this->body = $body;
	}
	public function getBody(){
		return $this->body;
	}
	
	public function __toString(){
		$body    = $this->getBody();
		$headers = $this->getHeaders();
		return implode("\r\n", $headers)."\r\n\r\n$body";
	}
	public function sendd(){
		header_remove();
		$body    = $this->getBody();
		$headers = $this->getHeaders();
		
		if(!empty($headers)){
			foreach($headers as $header){
				header($header);
			}
		}
		if(!empty($this->cookie)){
			foreach($this->cookie as $name => $value){
				setcookie($cookie[$name]);
			}
		}
		if(!empty($this->body) && !is_null($this->body)){
			print $this->body;
		}
		print_r($this);
	}
	public function sendFile(string $fileName){
		$this->setHeader('Content-Disposition', 'attachment; filename="'.str_replace('"', '', $fileName).'"; '."filename*=utf-8''".rawurlencode($fileName));
		return $this;
	}
	public function sendJson(){
		header_remove();
		foreach($this->headers as $header){
			header($header);
		}
		if(!empty($this->cookies)){
			foreach($this->cookies as $value){
				setcookie($value);
			}
		}
		header(sprintf($this->protocol, $this->code, $this->phrase), true, $this->code);
		header('Content-Type', 'application/json');
		return json_encode($this->body);
	}
	public function sendPdf($file){
		header_remove();
		// We'll be outputting a PDF
		header('Content-Type: application/iso');
		// It will be called downloaded.pdf
		header('Content-Disposition: attachment; filename='.$file);
		// The PDF source is in original.pdf
		readfile($file);
	}
	public function redirect(string $url, int $code){
		header_remove();
		header('Location: '.$url, true, $code);
	}
	public function send($content){
		//print $content->body;
		print_r($content);
	}
	
	public function __destruct(){
		/* if(!$this->isKeepAlive()){
			$this->close();
		} */
	}
	public function close(){
		if(!empty($this->resource)){
			fclose($this->resource);
		}
		$this->resource = NULL;
	}
}