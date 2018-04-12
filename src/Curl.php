<?PHP
class Curl {
    /**
	* @var string $link: curl request url
	*/
    public $link = '';
	/**
	* @var string $target: 'bittrex' adds bittrex API secure vars to curl request
	*/
	public $target = '';
	/**
	* @var string $contentType: this variable will contain retrieved content type info
	*/
	public $contentType = '';
    /**
	* Fetch an url with curl
	* @return JSON-decoded result of curl request or bool 'false' on error
	*/
	public function curlRequest() {
	    global $db;
		$ch = curl_init();
		$link = $this->link;
		//Define Bittrex security settings
		if ($this->target == "bittrex") {
			$nonce = time();
		    $link .= '&apikey=' . BITTREX_PUBLIC_KEY . '&nonce=' . $nonce;
			$sign = hash_hmac('sha512', $link, BITTREX_PRIVATE_KEY);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('apisign:'.$sign));
		}
		curl_setopt($ch, CURLOPT_URL, $link);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		$result = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$curlErr = curl_errno($ch);
		$curlError = curl_error($ch);
		$this->contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
		curl_close($ch);
		//Check server response for errors
		if ($curlErr != 0) {
		    $log = new Log;
			$log->origin = 'Curl module';
			$log->type = 'ERROR';
			$log->message =  $link . ' HTTP code: ' . $httpCode . ' ' . $curlErr . ' ' . $curlError;
            $log->write();
            $result = false;
		}
		return $result;
	}
}