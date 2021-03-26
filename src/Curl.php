<?php

namespace Imagine\Http;

class Curl{
	private $curl;
	private $curlOpt;
	private $error;
	
	private $request = array(
		'scheme' 			=> 'http', //http
		'auth' 				=> '', //basic or digest
		'user' 				=> '', //dika
		'pass' 				=> '', //qwerty
		'host' 				=> 'localhost', //smg-19.adau.net.id
		'port' 				=> 80, //443
		'path' 				=> '/', ///server/debian/system/status.php
		'query' 			=> null, //?id=smg-45&tl=27
		'fragment' 			=> null, //#memory
		'method' 			=> 'GET', //get
		'protocolVersion' 	=> 1.1, //1.1
		'headers' 			=> array(
			'Accept' 			=> 'application/json,application/xml',
			'Accept-Encoding' 	=> 'gzip, deflate, sdch',
			'Accept-Language' 	=> 'id-ID,id;q=0.8',
			'Accept-Charset' 	=> 'utf-8',
			'Connection' 		=> 'close'
		),
		'body' 				=> null,
		'postData' 			=> null, // If the $requestType is POST, you can also add post fields.
		'userAgent' 		=> 'Imagine/1.0 (compatible; PHP Request library)',
		'ssl' 				=> false, // Enable or disable SSL/TLS.
		'connectTimeout' 	=> 20, // timeout on connect
		'timeout' 			=> 20, // timeout on response
	);
	private $response = array(
		'status' 	=> array(
			'version' 	=> null,
			'code' 		=> null,
			'reason' 	=> null
		),
		'headers' 	=> null,
		'body' 		=> null
	);
	
	public function __construct(){
		if(!extension_loaded('curl')){
			throw new Exception('The cURL extensions is not loaded, make sure you have installed the cURL extension');
		}
	}
	
	public function setMethod(string $method){$this->request['method'] = strtoupper($method);}
	public function setScheme(string $scheme){$this->request['scheme'] = $scheme;}
	public function setHost(string $host){
		$components = $this->parse($host);
		foreach($components as $name => $value){
			if(!empty($components[$name]) || is_null($components[$name])){
				$this->request[$name] = $value;
			}
		}
		if($components['scheme'] == 'https'){
			$this->request['port'] = 443;
			$this->request['ssl'] = true;
		}
	}
	public function setPort(string $port){$this->request['port'] = $port;}
	public function setProtocol(string $protocol){$this->request['protocol'] = $protocol;}
	public function setHeaders(array $headers){$this->request['headers'] = $headers;}
	public function setOptions($options = null){$this->request['options'] = $options;}
	public function setBody($body = null){$this->request['body'] = $body;}
	public function setSsl($ssl){$this->request['ssl'] = $ssl;}
	public function setUser(string $user){$this->request['user'] = $user;}
	public function setPass(string $pass){$this->request['pass'] = $pass;}
	public function setPath(string $path){$this->request['path'] = $path;}
	public function setQuery(array $query){$this->request['query'] = $query;}
	public function setFragment(string $fragment){$this->request['fragment'] = $fragment;}
	public function setUserAgent(string $userAgent){$this->request['userAgent'] = $userAgent;}
	public function setPostData(array $postData){$this->request['postData'] = $postData;}
	public function setConnectTimeout(string $connectTimeout){$this->request['connectTimeout'] = $connectTimeout;}
	public function setTimeout(string $timeout){$this->request['timeout'] = $timeout;}
	
	public function parse($url){
		$components = array();
		
		$pattern  = "^(?:(?P<scheme>\w+)://)?";
		$pattern .= "(?:(?P<user>\w+):(?P<pass>\w+)@)?";
		$pattern .= "(?P<host>[\w+\.+\-]*)?";
		$pattern .= "(?::(?P<port>\d+))?";
		$pattern .= "(?P<path>[\w/]*/(?P<file>\w+(?:\.\w+)?)?)?";
		$pattern .= "(?:\?(?P<query>[\w=&]+))?";
		$pattern .= "(?:#(?P<fragment>\w+))?";
		$pattern  = "!$pattern!";
		
		preg_match ($pattern, $url, $mach);
		foreach($mach as $key => $value){
			if(is_string($key)){
				$components[$key] = $value;
			}
		}
		
		return $components;
	}
	public function execute(){
		$this->curl = curl_init();
		
		$this->buildOptions($this->request);
		//print_r($this);
	}
	public function buildOptions($request){
		
	}
	public function setOpt($option, $value){
		return curl_setopt($this->curl, $option, $value);
	}
}