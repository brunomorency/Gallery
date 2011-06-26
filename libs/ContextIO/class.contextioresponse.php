<?php
/*
Copyright (C) 2011 DokDok Inc.

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

/**
 * Container of the ContextIOResponse class
 * @copyright Copyright (C) 2011 DokDok Inc.
 * @licence http://opensource.org/licenses/mit-license MIT Licence
 */

class ContextIOResponse {

	protected $headers;
	protected $rawResponseHeaders;
	protected $rawRequestHeaders;
	protected $rawResponse;
	protected $decodedResponse;
	protected $httpCode;
	protected $contentType;
	protected $hasError;

	function __construct($httpCode, $requestHeaders, $responseHeaders, $contentType, $rawResponse) {
		$this->httpCode = $httpCode;
		$this->contentType = $contentType;
		$this->rawResponse = $rawResponse;
		$this->rawResponseHeaders = (is_array($responseHeaders)) ? $responseHeaders : false;
		$this->rawRequestHeaders = (is_array($requestHeaders)) ? $requestHeaders : false;
		$this->hasError = false;
		$this->headers = array('request'=>$requestHeaders, 'response'=>null); 
		$this->_decodeResponse();
		$this->_parseHeaders('response');
		$this->_parseHeaders('request');
	}
	
	private function _parseHeaders($which = 'response') {
		$raw = ($which == 'response') ? $this->rawResponseHeaders : $this->rawRequestHeaders;
		
		if ($raw !== false) {
			$headers = array();
			$headers[($which == 'response') ? 'Status-Line' : 'Request-Line'] = trim(array_shift($raw));
			$headerName = '';
			foreach ($raw as $headerLine) {
				$firstChar = substr($headerLine, 0, 1);
				if ($firstChar == chr(32) || $firstChar == chr(9)) {
					// continuing value of previous header line
					if (is_array($headers[$headerName])) {
						$idx = count($headers[$headerName]) - 1;
						$headers[$headerName][$idx] .= "\n".trim($headerLine);
					}
					else {
						$headers[$headerName] .= "\n".trim($headerLine);
					}
				}
				else {
					// New header line
					$idx = strpos($headerLine, ':');
					if ($idx !== false) {
						$headerName = trim(substr($headerLine, 0, $idx));
						$headerValue = trim(substr($headerLine, $idx + 1));
						if (array_key_exists($headerName, $this->headers)) {
							// Already have an occurence of this header. Make the header an array with all occurences
							if (is_array($headers[$headerName])) {
								$headers[$headerName][] = $headerValue;
							}
							else {
								$headers[$headerName] = array(
									$headers[$headerName],
									$headerValue
								);
							}
						}
						else {
							// First occurence, simply give value as a string
							$headers[$headerName] = $headerValue;
						}
					}
				}
			}
			$this->headers[$which] = $headers;
		}
	}

	private function _decodeResponse() {
		if ($this->httpCode != '200') {
			$this->hasError = true;
			return;
		}
		if ($this->contentType != 'application/json') {
			$this->hasError = true;
			return;
		}
		$this->decodedResponse = json_decode($this->rawResponse, true);
		if (array_key_exists('messages', $this->decodedResponse) && (count($this->decodedResponse['messages']) > 0)) {
			$this->hasError = true;
		}
	}

	public function getRawResponse() {
		return $this->rawResponse;
	}

	public function getRawResponseHeaders() {
		return $this->rawResponseHeaders;
	}

	public function getResponseHeaders() {
		return $this->headers['response'];
	}

	public function getRawRequestHeaders() {
		return $this->rawRequestHeaders;
	}
	
	public function getRequestHeaders() {
		return $this->headers['request'];
	}
	
	public function getHttpCode() {
		return $this->httpCode;
	}

	public function getSystem() {
		return $this->decodedResponse['system'];
	}

	public function getTimestamp() {
		return $this->decodedResponse['timestamp'];
	}

	public function getMessages() {
		return $this->decodedResponse['messages'];
	}

	public function getData() {
		return $this->decodedResponse['data'];
	}

	public function hasError() {
		return $this->hasError;
	}
}

?>
