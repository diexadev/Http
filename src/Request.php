<?php

namespace Imagine\Http;

use Exception;
use Psr\Http\Message\RequestInterface;

class Request  extends Message implements RequestInterface{
	
	
	public function __construct(string $method = null, string $url = null, array $headers = null, Response $body = null){
		//$this->createFromBase();
		
		if(!is_null($method)){
			$this->setMethod($method);
		}
		if(!is_null($url)){
			$this->setUrl($url);
		}
		if(!is_null($headers)){
			$this->setHeaders($headers);
		}
		if(!is_null($body)){
			$this->setBody($body);
		}
	}
	
	
	
	public function parserUrl($url){
		$components = array();
		
		$r  = "^(?:(?P<scheme>\w+)://)?";
		$r .= "(?:(?P<user>\w+):(?P<pass>\w+)@)?";
		$r .= "(?P<host>[\w+\.+\-]*)?";
		$r .= "(?::(?P<port>\d+))?";
		$r .= "(?P<path>[\w/]*/(?P<file>\w+(?:\.\w+)?)?)?";
		$r .= "(?:\?(?P<query>[\w+\:=&]+))?";
		$r .= "(?:#(?P<fragment>\w+))?";
		$r  = "!$r!";
		
		preg_match ( $r, $url, $m);
		foreach($m as $key => $value){
			if(is_string($key)){
				$components[$key] = $value;
			}
		}//print_r($components);exit;
		if(array_key_exists('path', $components) && !is_null($components['path'])){
			$components['uri'] = $components['path'];
		}
		if(!empty($components['query'])){
			$components['uri'] .= '?'.$components['query'];
		}
		//print_r($components);exit;
		return $components;
	}
	public function buildComponent(array $components){
		$defaultComponents = array(
			'scheme' => 'http',
			'user' => null,
			'pass' => null,
			'host' => 'localhost',
			'port' => 80,
			'path' => '/',
			'query' => null,
			'fragment' => null,
			'method' => 'GET',
			'protocolVersion' => '1.1',
			'headers' => array(
				// request
				'Accept' => 'application/json,application/xml',
				'Accept-Encoding' => 'gzip, deflate, sdch',
				'Accept-Language' => 'id-ID,id;q=0.8',
				'Accept-Charset' => 'utf-8',
				'Connection' => 'Keep-Alive',
				'Keep-Alive' => 300,
				'Cookie' => 'PHPSESSID=r2t5uvjq435r4q7ib3vtdjq120',
				'Pragma' => 'no-cache',
				'Cache-Control' => 'no-cache',
				//response
				'Transfer-Encoding' => 'chunked',
				'Date' => 'Sat, 28 Nov 2009 04:36:25 GMT',
				'Server' => 'Apache/2.4.38, (Win32), OpenSSL/1.1.1a, PHP/7.3.2',
				'Connection' => 'Keep-Alive',
				'Keep-Alive: timeout=5, max=100',
				'X-Powered-By' => 'PHP/7.3.2',
				'Pragma' => 'public',
				'Expires' => 'Sat, 28 Nov 2009 05:36:25 GMT',
				'Etag' => "pub1259380237;gz",
				'Cache-Control' => 'max-age=3600, public',
				'Content-Type' => 'text/html; charset=UTF-8',
				'Content-Length' => '4190',
				'Last-Modified' => 'Sat, 28 Nov 2009 03:50:37 GMT',
				'X-Pingback' => 'https://net.tutsplus.com/xmlrpc.php',
				'Content-Encoding' => 'gzip',
				'Vary' => 'Accept-Encoding, Cookie, User-Agent',
				),
			'body' => null,
			'postData' => null,
			'userAgent' => 'Mozilla/5.0 (compatible; PHP Request library)',
			'ssl' => false,
			'connectTimeout' => 100,
			'timeout' => 150
		);
		
		foreach($components as $name => $value){
			if(!empty($components[$name]) || is_null($components[$name])){
				$defaultComponents[$name] = $value;
			}
		}
		
		if($defaultComponents['scheme'] == 'https'){
			$defaultComponents['port'] = 443;
			$defaultComponents['ssl'] = true;
		}
		
		return $defaultComponents;
	}
	
	
	public function setMethod(string $method){$this->method = strtoupper($method);}
	public function setUrl($url){
		foreach($components = $this->parserUrl($url) as $name => $value){
			$this->$name = $value;
		}
		$this->url = $url;
		if($this->scheme != 'http'){
			$this->port = 443;
			$this->ssl = 'on';
		}
		
	}
	public function setScheme(string $scheme){$this->scheme = $scheme;}
	public function setHost(string $host){$this->host = $host;}
	public function setPort(int $port){$this->port = $port;}
	public function setProtocol(string $protocol){$this->protocol = $protocol;}
	public function setPath(string $path){$this->path = $path;}
	public function setQuery(array $query){$this->query = $query;}
	public function setFragment(string $fragment){$this->fragment = $fragment;}
	public function setHeaders(array $headers){$this->headers = $headers;}
	public function setBody($body){$this->body = $body;}
	public function setSsl(string $ssl){$this->ssl = $ssl;}
	public function setUri(string $uri){$this->uri = $uri;}
	
	public function getPath(){return $this->uri;}
	
	public function get($url){
		$this->url = $url;
		
		$this->execute();
	}
	
	
	
	
	public function getResponse(){
		$this->execute();
		
		if(!empty($this->error)){
			return $this->error;
		}elseif(!empty($this->response)){
			return array(
				'status' => $this->response['status'],
				'headers' => $this->response['headers'],
				'body' => $this->response['body']
			);
		}	
	}
	public function getMethod(){return $this->method;}
	public function getUrl(): string{return $this->url;}
	public function getQueryParameters(): array{
        $url = $this->getUrl();
        if (false === ($index = strpos($url, '?'))) {
            return [];
        }

        parse_str(substr($url, $index + 1), $queryParams);

        return $queryParams;
    }
	public function getRawServerValue(string $name){return $this->rawServerData[$name] ?? null;}
	public function getHeaders(){return $this->headers;}
	public function getBody(){
		return $this->body;
	}
	public function getError(){
		return $this->error;
	}
	
	public function execute(){
		foreach($this->prepareBaseComponents() as $name => $value){
			if(empty($this->$name)){
				$this->$name = $value;
			}
		}//print_r($this);exit;
		$curlOpt = $this->prepareCurlOpt();
		$curlInit = curl_init();
        curl_setopt_array($curlInit, $curlOpt);
		
		
		$curlExec = curl_exec($curlInit);
		$this->error = curl_error($curlInit);
		$info = curl_getinfo($curlInit);
		$responseCode = curl_getinfo($curlInit, CURLINFO_HTTP_CODE);
		$header_size = curl_getinfo($curlInit, CURLINFO_HEADER_SIZE);
		$latency = curl_getinfo($curlInit, CURLINFO_TOTAL_TIME);
		
		curl_close($curlInit);
		
		//return $this;
		//return $response;
	}
	public function prepareBaseComponents(){//print_r($this);exit;
		$components = array(
			'method' => 'GET',
			'scheme' => 'http',
			'host' => 'localhost',
			'port' => '80',
			'protocol' => 'HTTP/1.1',
			'uri' => '/',
			'query' => null,
			'headers' => null,
			'body' => null,
			'url' => 'http://localhost:80/',
			'ssl' => 'off',
			//'post' =>
			//'server' =>
			'files' => null,
			'cookie' => null,
			'connectTimeout' => 300,
			'timeout' => 300,
		);
		
		return $components;
	}
	public function prepareCurlOpt(){
		$curlOpt = array(
			// basic
			CURLOPT_AUTOREFERER => TRUE, // automatically set the Referer.
			CURLOPT_FOLLOWLOCATION => TRUE, // Follow redirects.
			CURLOPT_MAXREDIRS => 5, // stop after 5 redirects
			CURLOPT_HEADER => false, // Output the header in the content.
			CURLOPT_RETURNTRANSFER => FALSE, // Don't print the response; return it from curl_exec().
			CURLOPT_CONNECTTIMEOUT => $this->connectTimeout, // timeout on connect
			CURLOPT_TIMEOUT => $this->timeout, // timeout on response
			// http
			CURLOPT_URL => $this->url,
            CURLINFO_HEADER_OUT => true,
			// port
			CURLOPT_PORT => $this->port,
			// protocol
			CURLOPT_PROTOCOLS => CURLPROTO_ALL,
			// ssl
			CURLOPT_SSL_VERIFYPEER => $this->ssl, // SSL support.
			CURLOPT_SSL_VERIFYHOST => 2,
			//CURLOPT_CAINFO,
			//CURLOPT_CAPATH,
			//CURLOPT_SSLCERT,
			//CURLOPT_SSLCERTPASSWD,
			CURLOPT_COOKIE => $this->cookie,
			// handle stream response
			CURLOPT_HEADERFUNCTION => array($this, 'handleResponseHeader'),
			CURLOPT_WRITEFUNCTION => array($this, 'handleResponseBody'),
			CURLOPT_BINARYTRANSFER => TRUE,
        );
		
		// http version
		switch($this->protocol){
			case 'HTTP/1.0':
				$curlOpt[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_0;
				break;
			case 'HTTP/1.1':
				$curlOpt[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_1;
				break;
			case 'HTTP/2.0':
				$curlOpt[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_2_0;
				break;
			default:
				$curlOpt[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_NONE;
				break;
		}
		
		// method
		if(isset($this->method)){
			$curlOpt[CURLOPT_CUSTOMREQUEST] = $this->method;
			// request body.
			if(in_array($this->method, array('POST', 'DELETE', 'PUT', 'OPTIONS'))){
				$curlOpt[CURLOPT_POST] = true;
				$curlOpt[CURLOPT_POSTFIELDS] = $this->body;
			}
			// no request body
			if(in_array($this->method, array('HEAD', 'CONNECT'))){
				$curlOpt[CURLOPT_NOBODY] = true;
			}
		}
		
		// autentication
		if(isset($this->auth)){
			switch($this->auth){
				case 'basic':
					$curlOpt[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
					break;
				case 'digest':
					$curlOpt[CURLOPT_HTTPAUTH] = CURLAUTH_DIGEST;
					break;
			}
			$curlOpt[CURLOPT_USERPWD] = $this->user.':'.$this->pass;
		}
		
		// headers
		if(isset($this->headers)){
			if(array_key_exists('User-Agent', $this->headers)){
				$curlOpt[CURLOPT_USERAGENT] = $this->headers['User-Agent']; // Set a custom UA string.
			}
			if(array_key_exists('Referer', $this->headers)){
				$curlOpt[CURLOPT_USERAGENT] = $this->headers['Referer'];
			}
			
			$curlOpt[CURLOPT_HTTPHEADER] = $this->headers; // Set a custom headers
			
		}
		// proxy
		if(isset($this->proxyType)){
			$curlOpt[CURLOPT_PROXYTYPE] = $this->proxyType;
			$curlOpt[CURLOPT_PROXY] = $this->proxyHost;
			$curlOpt[CURLOPT_PROXYPORT] = $this->proxyPort;
			$curlOpt[CURLOPT_PROXYUSERPWD] = $this->proxyUserPass;
		}
		
		//cookie
		switch($this->cookie){
			case true:
				$curlOpt[CURLOPT_COOKIEJAR] = $this->cookiePath;
				$curlOpt[CURLOPT_COOKIEFILE] = $this->cookiePath;
				break;
		}
		
		return $curlOpt;
	}
	private function handleResponseHeader($thiscurl, $headerData){
		$rStatus = "/^(?P<version>\s*.*\s*\/\s*\d*\.\d*)\s*(?P<code>\d*)\s(?P<phrase>.*)\r\n/";
		preg_match($rStatus , $headerData, $matches);
		foreach(array('version', 'code', 'phrase') as $part){
			if(isset($matches[$part])){
				$this->response['status'][$part] = isset($matches[$part]) ? $matches[$part] : null;
			}
		}
		
		//header
		$rHeader = "/^\s*(?P<attributeName>[a-zA-Z0-9-]*):\s*(?P<attributeValue>.*)\r\n/";
		preg_match($rHeader , $headerData, $matches);
		if(isset($matches['attributeName'])){
			$this->response['headers'][$matches['attributeName']] = isset($matches['attributeValue']) ? $matches['attributeValue'] : null;
		}
		
		return strlen($headerData);
    }
	private function handleResponseBody($thiscurl, $bodyData){
		$this->response['body'] = $bodyData;
		return strlen($bodyData);
    }
	
	
	
	
	
	public static function capture(){
		$serverBag = $_SERVER;
		//$filesBag = $_FILES;
		//$cookieBag = $_COOKIE;
		
		// If we're running off the CLI, we're going to set some default settings.
		if('cli' === PHP_SAPI){
			$serverBag['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';
			$serverBag['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
		}

		$captured = self::createFromServerArray($serverBag);//print_r($captured);exit;
		$request =  new static;
		
		foreach($captured as $data => $value){
			$request->$data = $value;
		}
		
		$request->server = $serverBag;
		$request->url = $captured['scheme'].'://'.$captured['host'].':'.$captured['port'].$captured['uri'];
		$request->query = $_GET ?: null;
		//$request->body = fopen('php://input', 'r');
		//$request->post = empty($_POST) ? null : $_POST; //tenary operator
		if(empty($_POST)){
			if(!empty($body = file_get_contents('php://input'))){
				$data = json_decode($body, true);
				if(!json_last_error()){
					$this->post = $data;
				}
			}
		}else{
            $this->post = $_POST;
        }
		$request->files = $_FILES ?: null; // elvis operator
		$request->cookie = $_COOKIE ?: null;
		
		if($request instanceof static){
			return $request;
		}
		exit('aaaa');
	}
	private static function createFromServerArray($serverBag){
		$data = [];
		
		foreach($serverBag as $key => $value){
			switch($key){
				case 'REQUEST_METHOD':
					$data['method'] = $value;
					break;
				case 'REQUEST_SCHEME':
					$data['scheme'] = $value;
					break;
				case 'HTTP_HOST':
					$data['host'] = $value;
					break;
				case 'SERVER_PORT':
					$data['port'] = $value;
					break;
				case 'SERVER_PROTOCOL':
					$data['protocol'] = $value;
					break;
				case 'REQUEST_URI':
					$data['uri'] = $value;
					break;
				/* case 'QUERY_STRING':
					if(!empty($value)){
						$data['query'] = $value;
					}
					break;
				 */case 'HTTPS':
					$data['ssl'] = $value;
					break;
				default:
					if(!array_key_exists('ssl', $data)){
						$data['ssl'] = 'off';
					}
					
					if('HTTP_' === substr($key, 0, 5)){
                        // It's a HTTP header, Normalizing it to be prettier.
                        $header = strtolower(substr($key, 5));

                        // Transforming dashes into spaces, and uppercasing every first letter.
                        $header = ucwords(str_replace('_', ' ', $header));

                        // Turning spaces into dashes.
                        $header = str_replace(' ', '-', $header);
                        $data['headers'][$header] = $value;
                    }
					
                    break;
				
			}
		}
		
		return $data;
	}
}