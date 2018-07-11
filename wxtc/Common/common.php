<?php

	function getShortTime(){
		$date=date('H:i:s',time());
		return $date;
	}

	function getShortDate(){
		$date=date('Y-m-d',time());
		return $date;
	}

	function getDateTime($d){
		$date=date('Y-m-d H:i:s',strtotime($d));
		return $date;
	}

	//生成交易流水号
	function getMid() {
		list($t1, $t2) = explode(' ', microtime());
		$now=(float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
		$r=rand(100, 999);
		$mid=$now.$r;
		return  $mid;
	}

	
	function getAccessToken(){
		$data=file_get_contents('token.txt');
		$data=explode(',',$data);
		// dump($data);
		$token=$data[0];
		$startdate=strtotime($data[1]);
		$enddate=time();
		// echo $startdate.' ==='.$enddate.'<br>';
		$diff= $enddate-$startdate;
		$minute=floor($diff/60);
		if($minute>100){
			$token=getNewAccessToken();		//正式使用时不能注释
		}
		return $token;
	}
	function getNewAccessToken(){
		$appid=C('APPID');
		$secret=C('APPSECRET');
		$url='https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appid.'&secret='.$secret;
		// echo $url;
		$reData=wxGetData($url);
		// echo $reData;
		$reData = json_decode($reData,true);
		$token=$reData['access_token'];
		if($token){
			$content=$token.','.date('Y-m-d H:i:s',time());
			$fp = fopen("token.txt", "w");//文件被清空后再写入
			if($fp){
				$flag=fwrite($fp,$content); 
				// echo $flag.'<br>';
				if(!$flag){exit('token写入失败');}else{ return $token;}
			}
		}else{
			// exit('获取TOKEN失败');
		}
		return $token;
		// dump($reData);
	}
	//获取TICKET
	function getJsApiTicket() {
    // jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例

		$appid=C('APPID');
		$secret=C('APPSECRET');
	    $data = json_decode(file_get_contents("jsapi_ticket.json"));
	    if ($data->expire_time < time()) {
	      $accessToken =getAccessToken();
	      // 如果是企业号用以下 URL 获取 ticket
	      // $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";
	      $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
	      $res = json_decode(wxGetData($url));
	      $ticket = $res->ticket;
	      if ($ticket) {
	        $data->expire_time = time() + 7000;
	        $data->jsapi_ticket = $ticket;
	        $fp = fopen("jsapi_ticket.json", "w");
	        fwrite($fp, json_encode($data));
	        fclose($fp);
	      }
	    } else {
	      $ticket = $data->jsapi_ticket;
	    }

	    return $ticket;
  	}


  	function getSignPackage() {
	    $jsapiTicket = getJsApiTicket();

	    // 注意 URL 一定要动态获取，不能 hardcode.
	    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
	    $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

	    $timestamp = time();
	    $nonceStr = createNonceStr();

	    // 这里参数的顺序要按照 key 值 ASCII 码升序排序
	    $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

	    $signature = sha1($string);
	    $appid = C('APPID');
	    $signPackage = array(
	      "appId"     => $appid,
	      "nonceStr"  => $nonceStr,
	      "timestamp" => $timestamp,
	      "url"       => $url,
	      "signature" => $signature,
	      "rawString" => $string
	    );
		// dump($signPackage);
	    return $signPackage; 
	}
	function createNonceStr($length = 16) {
	    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
	    $str = "";
	    for ($i = 0; $i < $length; $i++) {
	      $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
	    }
	    return $str;
	}

	//图片转BASE64
	function imgToBase64($img){
		$image_info =getimagesize($img);
		// dump($image_info);data:{image/jpeg};base64,
		// $base64_image_content = "data:{".$image_info['mime']."};base64," .chunk_split(base64_encode(file_get_contents($img))); 
		$base64_image_content = chunk_split(base64_encode(file_get_contents($img))); 
		return $base64_image_content;
	}

	function postDataTo($data,$url){//发送消息
		// exit();
		// $url=C('API_URL');
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen($data))
		);
		$result = curl_exec($ch);

		// echo $result;
		$result = json_decode($result,true);

		// dump($result);
		return $result;

	}
	function wxPostData($data,$url){
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen($data))
		);
		$result = curl_exec($ch);
		// echo $result;
		// $result = json_decode($result,true);
		// dump($result);
		return $result;
    }

	
    function getOpenid(){
		
		$url='https://open.weixin.qq.com/connect/oauth2/authorize?appid='.C('APPID').'&redirect_uri='.C('WEB_URL').'/wx.php/Wx/redirectUrl/&response_type=code&scope=snsapi_base&state=1#wechat_redirect';
		// exit($url);
		// wxGetData($url);
		header("Location:" .$url);    //需要在所有的输出前执行
    }
	
    function getUserInfo(){
		$access_token=getAccessToken();
    	$url='https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token.'&openid='.$_COOKIE['openid'].'&lang=zh_CN';
    	$reData=wxGetData($url);
		$user = json_decode($reData,true);
		// dump($user);
		cookie('nickname',$user['nickname']);
		cookie('headimgurl',$user['headimgurl']);
		cookie('subscribe',$user['subscribe']);

    }

	function http_request($data=null,$url){
		logger("http_request:".$data);
    	$curl = curl_init();
    	curl_setopt($curl,CURLOPT_URL,$url);
    	curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,FALSE);
    	curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,FALSE);
    	if(!empty($data)){
    		curl_setopt($curl,CURLOPT_POST,1);
    		curl_setopt($curl,CURLOPT_POSTFIELDS,$data);
    	}
    	curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
    	$output = curl_exec($curl);
    	curl_close($curl);
    	return $output;
    }

	//log文件
    function logger($log=null){
    	if(true){
    		$txt = 'logs/log.txt';
			$content = "\n[".date("Y-m-d H:i:s")."] INFO ".$log;
			$fp = fopen($txt,"a+");
			fwrite($fp,$content);
			fclose($fp);
    	}
    }
