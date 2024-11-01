<?php
class Tactile_Json_Client{
	
	protected $_token;
	
	protected $_site;
	
	protected $_apiBase = 'tactilecrm.com';
	
	protected $_apiUrl;
	
	protected $_responseInfo;
	
	protected $_headers = array(
							'Content-Type: application/json'
							);
	
	
	public function __construct($site, $token){
		$this->_site = $site;
		$this->_token = $token;
		
		$this->_apiUrl = $this->_site.'.'.$this->_apiBase;
	}
	
	public function request($function, $get=array(), $post=false){
		
		$url = "http://".$this->_apiUrl."/{$function}?api_token={$this->_token}";
		if(!empty($get)){
			foreach($get as $k=>$v){
				$url.="&$k=".urlencode($v);
			}
		}
		
		if(false !== $post){
			$post = json_encode($post);
			if(is_null($post)){
				return false;
			}
		} 
		
		
		//echo $url;
		// create a new cURL resource
		$ch = curl_init();
		
		// set URL and other appropriate options
		curl_setopt($ch, CURLOPT_URL, $url);
		
		curl_setopt($ch, CURLOPT_VERBOSE, 0);
		curl_setopt($ch, CURLOPT_NOBODY, 0);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $this->_headers);		
		
		if(!empty($post)){
			CURLOPT_POST;
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}
		
		$response = curl_exec($ch);
		$this->_responseInfo=curl_getinfo($ch);
		curl_close($ch);
		
		if( intval( $this->_responseInfo['http_code'] ) == 200 ){
			return json_decode($response);
		} else {
			return false;
		}
	}
}
?>