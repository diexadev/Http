<?php

namespace Imagine\Http;

class Capture{
	public static function request(){
		$serverBag = $_SERVER;
		
		// If we're running off the CLI, we're going to set some default settings.
		if('cli' === PHP_SAPI){
			$serverBag['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';
			$serverBag['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
		}

		$request = self::createFromServerArray($serverBag);
		$request->setBody(fopen('php://input', 'r'));

		return $request;
	}
	private static function createFromServerArray($serverBag){
		$data = array(
			'method' => null,
			'scheme' => null,
			'host' => null,
			'port' => null,
			'protocol' => null,
			'path' => null,
			'query' => null,
			'fragment' => null,
			'headers' => null,
			'body' => null,
			'ssl' => null,
			'uri' => null,
		);
		
		foreach($serverBag as $key => $value){
			switch($key){
				case 'REQUEST_METHOD':
					if(!empty($value)){
						$data['method'] = $value;
					}
					break;
				case 'REQUEST_SCHEME':
					if(!empty($value)){
						$data['scheme'] = $value;
					}
					break;
				case 'HTTP_HOST':
					if(!empty($value)){
						$data['host'] = $value;
					}
					break;
				case 'SERVER_PORT':
					if(!empty($value)){
						$data['port'] = $value;
					}
					break;
				case 'SERVER_PROTOCOL':
					if(!empty($value)){
						$data['protocol'] = $value;
					}
					break;
				case 'QUERY_STRING':
					if(!empty($value)){
						$data['query'] = $value;
					}
					break;
				case 'HTTPS':
					if(!empty($value)){
						$data['ssl'] = $value;
					}
					break;
				case 'REQUEST_URI':
					if(!empty($value)){
						$data['uri'] = $value;
					}
					break;
				default:
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
		
		$request = new Request($data['method'], $data['host'].$data['uri']);
		$request->setRawServerData($data);
		$request->setAbsoluteUrl($data['scheme'].'://'.$data['host'].':'.$data['port'].$data['uri']);
		
		return $request;
	}
}