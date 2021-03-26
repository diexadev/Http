<?php

namespace Imagine\Http;

class Client{
	private $request;
	private $response = array(
		'status' => null,
		'headers' => null,
		'body' => null
	);
	private $error;
	
	public function __construct(string $method = null, string $url = null){
		if(!function_exists('curl_init')){
			die('fungsi curl tidak ada');
		}
		
		if(!is_null($url)){
			foreach($this->buildComponents($this->parserUrl($url)) as $name => $value){
				$this->request[$name] = $value;
			}
		}
		
		if(!is_null($method)){
			$this->request['method'] = strtoupper($method);
		}
		
		return $this;
	}
	public function parserUrl($url){
		$r  = "^(?:(?P<scheme>\w+)://)?";
		$r .= "(?:(?P<user>\w+):(?P<pass>\w+)@)?";
		$r .= "(?P<host>[\w+\.+\-]*)?";
		$r .= "(?::(?P<port>\d+))?";
		$r .= "(?P<path>[\/\.\-\_\w/]*)?";
		$r .= "(?:\?(?P<query>[\w=&]+))?";
		$r .= "(?:#(?P<fragment>\w+))?";
		$r  = "!$r!";
		
		preg_match ( $r, $url, $m);
		foreach($m as $key => $value){
			if(is_string($key)){
				$components[$key] = $value;
			}
		}
		//print_r($components);exit;
		return $components;
	}
	public function buildComponents($request){
		$default = array(
			'scheme' => 'http',
			'auth' => null,
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
				'Referer' => null,
				'Accept' => 'application/json,application/xml',
				'Accept-Encoding' => 'gzip, deflate, sdch',
				'Accept-Language' => 'id-ID,id;q=0.8',
				'Accept-Charset' => 'utf-8',
				'Connection' => 'Keep-Alive',
				'Keep-Alive' => 300,
				'User-Agent' => 'Mozilla/5.0 (compatible; PHP Request library)',
				'Cookie' => null,
				'Pragma' => 'no-cache',
				'Cache-Control' => 'no-cache',
				),
			'proxyType' => null,
			'proxyHost' => null,
			'proxyPort' => null,
			'proxyUserPwd' => null,
			'body' => null,
			'ssl' => false,
			'cookies' => null,
			'connectTimeout' => 1000,
			'timeout' => 1500
		);
		foreach($request as $name => $value){
			if(!empty($request[$name]) || is_null($request[$name])){
				$default[$name] = $value;
			}
		}
		
		if($default['scheme'] == 'https'){
			$default['port'] = 443;
			$default['ssl'] = true;
		}
		
		if(is_null($default['auth']) && isset($default['user']) && isset($default['pass'])){
			$default['auth'] = 'basic';
		}
		
		return $default;
	}
	
	
	
	
	public function initCurl(){
		$url = $this->request['scheme'].'://'.$this->request['host'].':'.$this->request['port'].$this->request['path'];//.'?'.$this->request['query'];
		
		if(isset($this->request['query'])){
			$url .= '?'.$this->request['query'];
		}
		
		$curlOpt = array(
			// basic
			CURLOPT_AUTOREFERER => TRUE, // automatically set the Referer.
			CURLOPT_FOLLOWLOCATION => TRUE, // Follow redirects.
			CURLOPT_MAXREDIRS => 5, // stop after 5 redirects
			CURLOPT_HEADER => false, // Output the header in the content.
			CURLOPT_RETURNTRANSFER => FALSE, // Don't print the response; return it from curl_exec().
			CURLOPT_CONNECTTIMEOUT => $this->request['connectTimeout'], // timeout on connect
			CURLOPT_TIMEOUT => $this->request['timeout'], // timeout on response
			// http
			CURLOPT_URL => $url,
            CURLINFO_HEADER_OUT => true,
			// port
			CURLOPT_PORT => $this->request['port'],
			// protocol
			CURLOPT_PROTOCOLS => CURLPROTO_ALL,
			// ssl
			CURLOPT_SSL_VERIFYPEER => $this->request['ssl'], // SSL support.
			CURLOPT_SSL_VERIFYHOST => 2,
			//CURLOPT_CAINFO,
			//CURLOPT_CAPATH,
			//CURLOPT_SSLCERT,
			//CURLOPT_SSLCERTPASSWD,
			// header
			CURLOPT_USERAGENT => $this->request['headers']['User-Agent'], // Set a custom UA string.
			CURLOPT_REFERER => $this->request['headers']['Referer'],
			CURLOPT_HTTPHEADER => $this->request['headers'], // Set a custom headers
			CURLOPT_COOKIE => $this->request['cookies'],
			// handle stream response
			CURLOPT_HEADERFUNCTION => array($this, 'handleResponseHeader'),
			CURLOPT_WRITEFUNCTION => array($this, 'handleResponseBody'),
			CURLOPT_BINARYTRANSFER => TRUE,
        );
		
		// http version
		switch($this->request['protocolVersion']){
			case '1.0':
				$curlOpt[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_0;
				break;
			case '1.1':
				$curlOpt[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_1;
				break;
			case '2.0':
				$curlOpt[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_2_0;
				break;
			default:
				$curlOpt[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_NONE;
				break;
		}
		
		// method
		if(isset($this->request['method'])){
			$curlOpt[CURLOPT_CUSTOMREQUEST] = $this->request['method'];
			// request body.
			if(in_array($this->request['method'], array('POST', 'DELETE', 'PUT', 'OPTIONS'))){
				$curlOpt[CURLOPT_POST] = true;
				$curlOpt[CURLOPT_POSTFIELDS] = $this->request['body'];
			}
			// no request body
			if(in_array($this->request['method'], array('HEAD', 'CONNECT'))){
				$curlOpt[CURLOPT_NOBODY] = true;
			}
		}
		
		// autentication
		if(isset($this->request['auth'])){
			switch($this->request['auth']){
				case 'basic':
					$curlOpt[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
					break;
				case 'digest':
					$curlOpt[CURLOPT_HTTPAUTH] = CURLAUTH_DIGEST;
					break;
			}
			$curlOpt[CURLOPT_USERPWD] = $this->request['user'].':'.$this->request['pass'];
		}
		
		// proxy
		if(isset($this->request['proxyType'])){
			$curlOpt[CURLOPT_PROXYTYPE] = $this->request['proxyType'];
			$curlOpt[CURLOPT_PROXY] = $this->request['proxyHost'];
			$curlOpt[CURLOPT_PROXYPORT] = $this->request['proxyPort'];
			$curlOpt[CURLOPT_PROXYUSERPWD] = $this->request['proxyUserPwd'];
		}
		
		//cookie
		switch($this->request['cookies']){
			case true:
				$curlOpt[CURLOPT_COOKIEJAR] = $this->request['cookiePath'];
				$curlOpt[CURLOPT_COOKIEFILE] = $this->request['cookiePath'];
				break;
		}
		//print_r($curlOpt);
		// initialize cURL
        $this->curl = curl_init();
        curl_setopt_array($this->curl, $curlOpt);
		//print_r($this->curl);
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
		$this->response['body'] .= $bodyData;
		return strlen($bodyData);
    }
	public function execute(){print_r($this);
		$this->initCurl();
		
		$response = curl_exec($this->curl);
		$error = curl_error($this->curl);
		$info = curl_getinfo($this->curl);
		$responseCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
		$header_size = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);
		$latency = curl_getinfo($this->curl, CURLINFO_TOTAL_TIME);
		
		curl_close($this->curl);
		$this->error = $error;
		//return $response;
		//print_r($info);
	}
	public function getResponse(){
		if(!empty($this->error)){
			return array('error' => $this->error);
		}
		
		return array(
			'status' => $this->response['status'],
			'headers' => $this->response['headers'],
			'body' => $this->response['body']
		);
	}
}