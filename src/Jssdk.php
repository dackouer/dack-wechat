<?php
	namespace Dack;

	class Jssdk{
		private $appid = '';
		private $secret = '';
		private $access_token = '';
		private $dir = '';

		public function __construct($appid = '',$secret = '',$access_token = '',$dir = ''){
			if($appid){
				$this->appid = $appid;
			}
			if($secret){
				$this->secret = $secret;
			}
			if($access_token){
				$this->access_token = $access_token;
			}
			if($dir){
				$this->dir = $dir;
			}
		}

		public function getSignPackage() {
			$jsapiTicket = $this->getJsApiTicket();
			$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
			$url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
			$timestamp = time();
			$nonceStr = $this->createNonceStr();
			$string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
			$signature = sha1($string);
			$signPackage = array(
				"appId"     => $this->appId,
				"nonceStr"  => $nonceStr,
				"timestamp" => $timestamp,
				"url"       => $url,
				"signature" => $signature,
				"rawString" => $string
			);
			return $signPackage; 
		}

		private function createNonceStr($length = 16) {
			$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
			$str = "";
			for ($i = 0; $i < $length; $i++){$str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);}
			return $str;
		}

		private function getJsApiTicket() {
			$data = json_decode($this->get_php_file("jsapi_ticket.php"));
			if ($data->expire_time < time()){
				$url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=".$this->token;
				$res = json_decode($this->httpGet($url));
				$ticket = $res->ticket;
				if($ticket){
					$data->expire_time = time() + 7000;
					$data->jsapi_ticket = $ticket;
					$this->set_php_file("jsapi_ticket.php", json_encode($data));
				}
			}else{
				$ticket = $data->jsapi_ticket;
			}
			return $ticket;
		}
		
		private function httpGet($url) {
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_TIMEOUT, 500);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_URL, $url);
			$res = curl_exec($curl);
			curl_close($curl);
			return $res;
		}

		private function get_php_file($filename) {
			return trim(substr(file_get_contents($this->_dir.DIRECTORY_SEPARATOR.$filename), 15));
		}
		private function set_php_file($filename, $content) {
			$fp = fopen($this->_dir.DIRECTORY_SEPARATOR.$filename, "w");
			fwrite($fp, "<?php return; ?>" . $content);
			fclose($fp);
		}
	}
?>