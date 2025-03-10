<?php
	namespace Dack;

	use support\Request;
	use Curl\Curl;

	class Wechat{
		private $wxurl = 'https://api.weixin.qq.com';
		private $config = [];
		private $expires_time = 0;
		private $is_array = false;

		public function __construct($config = []){
			if($config){
				$this->config = $config;
			}
		}

		/**
		 * 验证消息
		 * @param  Request $request [description]
		 * @return [type]           [description]
		 */
		public function valid(Request $request){
			$signature = $request->input('signature');
			$timestamp = $request->input('timestamp');
			$nonce 	   = $request->input('nonce');
			$echostr   = $request->input('echostr');
			$token     = $this->getConfig('token');

			$arr = array($token,$timestamp,$nonce);

			sort($arr,SORT_STRING);
			$arr = join($arr);
			$arr = sha1($arr);
			
			if($arr === $signature){
				return response((int)$echostr);
			}	
			return 0;
		}

		/**
		 * 回复消息
		 * @param  Request $request [description]
		 * @return [type]           [description]
		 */
		public function responseMsg(Request $request){
			$data = PHP_VERSION >= 5.6 ? file_get_contents('php://input') : $GLOBALS["HTTP_RAW_POST_DATA"];
			
			$type = $request->input('type','text');

			return true;

			switch(\strtolower($type)){
				case 'text':
					break;
				case 'event':
					break;
				default: 

			}


		}

		/**
		 * 获取用户信息
		 * @param  Request $request [description]
		 * @param  integer $id      [description]
		 * @return [type]           [description]
		 */
		public function getUser(Request $request,$id = 0){
			$type = $request->input('type');
			if($type && $type == 'init'){
				return '参数配置有误';
			}else{
				$access_token = $this->getAccessToken($request);
				if(!is_string($access_token)){
					return $access_token;
				}

				$id = $id ? $id : $request->input('id',0);

				if($id){
					$lang = trim($request->input('lang','zh_CN'));
					$uri = "/cgi-bin/user/info?access_token=".$access_token."&openid=".$id."&lang=".$lang;
				}else{
					$next_openid = trim($request->input('next_openid',''));
					$uri = "/cgi-bin/user/get?access_token=".$access_token."&next_openid=".$next_openid;
				}

				$response = $this->http($uri);
				return $response;
			}
		}

		/**
		 * 获取菜单列表
		 * @param  Request $request [description]
		 * @return [type]           [description]
		 */
		public function getMenu(Request $request){
			$access_token = $this->getAccessToken($request);
			if(!is_string($access_token)){
				return $access_token;
			}

			$uri = "/cgi-bin/get_current_selfmenu_info?access_token=".$access_token;
			return $this->http($uri);
		}

		/**
		 * 创建菜单
		 * @param  Request $request [description]
		 * @param  array   $data    [description]
		 * @return [type]           [description]
		 */
		public function createMenu(Request $request,$data = []){
			$access_token = $this->getAccessToken($request);
			if(!is_string($access_token)){
				return $access_token;
			}

			$data = $data ?? $request->post('data');
			if(!$data || !is_array($data)){
				return '无效的菜单格式';
			}

			$data = json_encode($data);
			$uri = "/cgi-bin/menu/create?access_token=".$access_token;
			return $this->http($uri,$data,'post');
		}

		/**
		 * 删除菜单
		 * @param  Request $request [description]
		 * @return [type]           [description]
		 */
		public function deleteMenu(Request $request){
			$access_token = $this->getAccessToken($request);
			if(!is_string($access_token)){
				return $access_token;
			}

			$uri = "/cgi-bin/menu/delete?access_token=".$access_token;

			return $this->http($uri);
		}

		/**
		 * 获取文章
		 * @param  Request $request [description]
		 * @param  integer $id      [description]
		 * @return [type]           [description]
		 */
		public function getArticle(Request $request,$id = 0){
			$access_token = $this->getAccessToken($request);
			if(!is_string($access_token)){
				return $access_token;
			}

			if(!$id){
				$offset = $request->input('offset',0);
				$count = $request->input('count',10);
				$no_content = $request->input('no_content',0);
				$data = [
					'offset' 	 => $offset,
					'count' 	 => $count,
					'no_content' => $no_content,
				];
				$uri = "/cgi-bin/draft/batchget?access_token=".$access_token;
				$result = $this->http($uri,json_encode($data),'post');
				if($result && is_object($result) && property_exists($result,'item') && property_exists($result,'total_count')){
					return ['rows' => $result->total_count,'data' => $result->item];
				}

				return $result;
			}else{
				$id = $id ? $id : $request->input('id',0);
				$data = ['media_id' => $id];
				$uri = "/cgi-bin/draft/get?access_token=".$access_token;

				return $this->http($uri,$data,'post');
			}
		}

		/**
		 * 新建文章
		 * @param  Request $request [description]
		 * @param  array   $data    [description]
		 * @return [type]           [description]
		 */
		public function createArticle(Request $request,$data = []){
			$access_token = $this->getAccessToken($request);
			if(!is_string($access_token)){
				return $access_token;
			}

			$uri = "/cgi-bin/draft/add?access_token=".$access_token;
			return $this->http($uri,$data,'post');
		}

		/**
		 * 修改文章
		 * @param  Request $request [description]
		 * @param  integer $id      [description]
		 * @return [type]           [description]
		 */
		public function updateArticle(Request $request,$id = 0){
			if(!$id){
				return false;
			}

			$access_token = $this->getAccessToken($request);
			if(!is_string($access_token)){
				return $access_token;
			}

			$uri = "/cgi-bin/draft/update?access_token=".$access_token;
			$data = [];
			return $this->http($uri,$data,'post');
		}

		/**
		 * 删除文章
		 * @param  Request $request [description]
		 * @param  integer $id      [description]
		 * @return [type]           [description]
		 */
		public function deleteArticle(Request $request,$id = 0){
			if(!$id){
				return false;
			}

			$access_token = $this->getAccessToken($request);
			if(!is_string($access_token)){
				return $access_token;
			}

			$uri = "/cgi-bin/draft/delete?access_token=".$access_token;
			$data = ['media_id' => $id];
			return $this->http($uri,$data,'post');
		}

		/**
		 * 发布文章
		 * @param  Request $request [description]
		 * @param  integer $id      [description]
		 * @return [type]           [description]
		 */
		public function pushArticle(Request $request,$id = 0){
			if(!$id){
				return false;
			}

			$access_token = $this->getAccessToken($request);
			if(!is_string($access_token)){
				return $access_token;
			}

			$uri = "/cgi-bin/freepublish/submit?access_token=".$access_token;
			$data = ['media_id' => $id];
			return $this->http($uri,$data,'post');
		}

		/**
		 * 生成短链接
		 * @param  string $link [description]
		 * @return [type]       [description]
		 */
		public function makeLink($link = ''){
			if(!$link){
				return '';
			}

			$access_token = $this->getAccessToken($request);
			if(!is_string($access_token)){
				return $access_token;
			}

			$uri = "/cgi-bin/shorturl?access_token=".$access_token;
			$data = [
				'action'   => 'long2short',
				'long_url' => $link
			];

			return $this->http($uri,$data,'post');
		}

		/**
		 * 上传临时素材
		 * @param  Request $request   [description]
		 * @param  [type]  $mediaFile [description]
		 * @param  string  $type      [description]
		 * @return [type]             [description]
		 */
		public function uploadMedia(Request $request,$mediaFile, $type = 'image'){
			$mediaFile = \realpath($mediaFile);
			if(!file_exists($mediaFile)){
				return ['errcode' => 'error', 'msg' => '本地文件不存在'];
			}
			$miniType = \mime_content_type($mediaFile);

			$access_token = $this->getAccessToken($request);
			if(!is_string($access_token)){
				return $access_token;
			}

			$uri = '/cgi-bin/media/upload?access_token='.$access_token.'&type='.$type;
			$data   = ['media' => '@'.$mediaFile];
			if(class_exists('CurlFile')){
				$media = new CurlFile($mediaFile);
				$media->setMimeType($miniType);
				$data  = ['media' => $media];
			}

			return $this->http($uri,$data,'post');
		}

		/**
		 * 下载临时素材
		 * @param  Request $request      [description]
		 * @param  [type]  $mediaId      [description]
		 * @param  [type]  $saveFileName [description]
		 * @return [type]                [description]
		 */
		public function downloadMedia(Request $request,$mediaId, $saveFileName){
			$access_token = $this->getAccessToken($request);
			if(!is_string($access_token)){
				return $access_token;
			}

			$uri = '/cgi-bin/media/get?access_token='.$access_token.'&media_id='.$mediaId;
			$res = $this->http($uri);
			\file_put_contents($saveFileName, $res);
			return $saveFileName;
		}

		/**
		 * 生成二维码
		 * @param  Request $request  [description]
		 * @param  [type]  $data     [description]
		 * @param  [type]  $filename [description]
		 * @param  integer $expire   [description]
		 * @return [type]            [description]
		 */
		public function makeQrcode(Request $request,$data, $filename, $expire = 2592000){
			$access_token = $this->getAccessToken($request);
			if(!is_string($access_token)){
				return $access_token;
			}

			$url = '/cgi-bin/qrcode/create?access_token='.$access_token;
			if($expire == 'allTime'){
				$postData = [
					'action_name' => 'QR_LIMIT_SCENE',
					'action_info' => ['scene' => $data]
				];
				if(!empty($data['scene_str'])){
					$postData['action_name'] = 'QR_LIMIT_STR_SCENE';
				}
			}else{
				$postData = [
					'action_name'    => 'QR_SCENE',
					'expire_seconds' => $expire,
					'action_info' 	 => ['scene' => $data]
				];
			}
			$res = $this->http($uri, json_encode($postData),'post');
			$qrcode = json_decode($res, true);
			if(empty($qrcode['ticket'])){
				return ['errcode' => 'error', 'msg' => '二维码创建失败'];
			}
			$uri = '/cgi-bin/showqrcode?ticket='.$qrcode['ticket'];
			$res = $this->http($uri);
			file_put_contents($filename.'.png', $res);
			return $filename.'.png';
		}

		/**
		 * 获取微信服务器IP
		 * @param  Request $request [description]
		 * @return [type]           [description]
		 */
		public function getWxIp(Request $request){
		    $access_token = $this->getAccessToken($request);
			if(!is_string($access_token)){
				return $access_token;
			}

		    $uri = '/cgi-bin/getcallbackip?access_token='.$access_token;
		    return $this->http($uri);
		}

		/**
		 * 获取jsapi_ticket用于网页开发
		 * @param  Request $request [description]
		 * @return [type]           [description]
		 */
		public function getJsTicket(Request $request){
		    $access_token = $this->getAccessToken($request);
			if(!is_string($access_token)){
				return $access_token;
			}

			$appid = $this->getConfig('appid');
			$secret = $this->getConfig('secret');


			$jssdk = new Jssdk($appid, $secret, $access_token, HCWT_CACHES);
			return $jssdk->GetSignPackage();
		}

		/**
		 * 获取access_token
		 * @param  Request $request [description]
		 * @return [type]           [description]
		 */
		public function getAccessToken(Request $request,$flag = false){
			if($this->expires_time && time() < $this->expires_time){
				return '';
			}
			$type = 'client_credential';
			$appid = $this->getConfig('appid');
			$secret = $this->getConfig('secret');
			$uri = "/cgi-bin/token?grant_type=".$type."&appid=".$appid."&secret=".$secret;

			$response = $this->http($uri);
			// var_dump($response);
			if(property_exists($response,'access_token')){
				return $flag ? $response : $response->access_token;
			}
			return $response;
		}

		private function getConfig($key = ''){
			if(!$key){
				return '';
			}

			return isset($this->config[$key]) ? $this->config[$key] : '';
		}
	
		private function filterName($str) { 
	        $str = preg_replace('/\xEE[\x80-\xBF][\x80-\xBF]|\xEF[\x81-\x83][\x80-\xBF]/', '', $str);
	        $str = preg_replace('/xE0[x80-x9F][x80-xBF]‘.‘|xED[xA0-xBF][x80-xBF]/S','?', $str);
			$str = str_replace(' ', '', $str);
	        return $str;
		}

		private function http($uri,$data = [],$method = 'GET'){
			$curl = new Curl();
			$url = $this->wxurl . $uri;
			if(\strtolower($method) == 'get'){
				$curl->get($url,$data);
			}else{
				$curl->post($url,$data);
			}
			$curl->close();
			// var_dump(json_decode($curl->response));
			return json_decode($curl->response);
		}

		// http_curl函数
		private function http_curl($url,$data = [],$type = 'get',$res = 'json'){
			$ch = curl_init();	
			curl_setopt($ch,CURLOPT_URL,$url);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
			if(strtolower($type) == 'post'){
				curl_setopt($ch,CURLOPT_POST,1);
				curl_setopt($ch,CURLOPT_POSTFIELDS,$data);	
			}
			$output = curl_exec($ch);
			curl_close($ch);
			if(strtolower($res) == 'json'){
				return json_decode($output,true);	
			}
		}

	}
?>