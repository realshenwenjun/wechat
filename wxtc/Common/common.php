<?php
	//审核状态
	function getVisitorStatus($id){
		switch($id){
			case '-1':
				return '等待审核';
				break;

			case '0':
				return '审核通过';
				break;

			case '1':
				return '审核不通过';
				break;

			default:
				return '未知';
				break;
		}

	}
	//车辆状态
	function getCarStatus($id){
		switch($id){
			case '0':
				return '已锁定';
				break;

			case '1':
				return '未锁定';
				break;

			default:
				return '未知';
				break;
		}

	}
	//获取访客审核状态
	function getVisitorResult($id){
		switch($id){
			case '-1':
				return '待审';
				break;

			case '0':
				return '已审';
				break;

			case '1':
				return '拒绝';
				break;
		}

	}
	//获取访客类型
	function getVisitmode($id=0){
		switch($id){
			case '0':
				return '外来拜访';
				break;
			case '1':
				return '邀约拜访';
				break;
			case '2':
				return '内部互访';
				break;
			case '3':
				return '生活服务';
				break;
		}
	}
	//进出标识
	function getInoutFlag($id){
		switch($id){
			case '0':
				return '入场';
				break;

			default:
				return '未知';
				break;
		}
	}
	//车辆类型
	function getVehicletypeName($id){
		switch($id){
			case '0':
				return '不区分车类型';
				break;
			case '1':
				return '摩托车';
				break;
			case '2':
				return '小型汽车';
				break;
			case '3':
				return '中型汽车';
				break;
			case '4':
				return '大型汽车';
				break;
			case '5':
				return '其它车型';
				break;
			default:
				return '未知（'.$id.'）';
				break;
		}
	}
	//车辆模式
	function getVehiclemodeName($id){
		switch($id){
			case '0':
				return '临时车';
				break;
			case '1':
				return '套餐车';
				break;
			case '2':
				return '特权车';
				break;
			case '6':
				return '储值车';
				break;
			default:
				return '未知（'.$id.'）';
				break;
		}
	}
	function getFeeunitName($id){
		switch ($id) {
			case '0':
				return '月';
				break;
			case '1':
				return '季度';
				break;
			case '2':
				return '半年';
				break;
			case '3':
				return '年';
				break;
			case '4':
				return '';
				break;
			
		}
	}
	function getShortTime(){
		$date=date('His',time());
		return $date;
	}
	//20151211 格式
	function getShortDate(){
		$date=date('Ymd',time());
		return $date;
	}
	//把2015-12-11 12:12:12转成20151211 格式
	function getYmdDate($d){
		$date=date('Ymd',strtotime($d));
		return $date;
	}
	//把2015-12-11 12:12:12转成12:12:12 格式
	function getHisDate($d){
		$date=date('His',strtotime($d));
		return $date;
	}
	//把20151212121212格式转化为 2015-12-12 12:12:12  格式
	function changeDate($a){
		$date=substr($a, 0,4).'-'.substr($a, 4, 2).'-'.substr($a, 6, 2).' '.substr($a, 8, 2).':'.substr($a, 10, 2).':'.substr($a, 12, 2);
		return $date;
	}
	//把20151212101010格式转化为 2015-12-12 格式
	function changeShortDate($a){
		$date=substr($a, 0,4).'-'.substr($a, 4, 2).'-'.substr($a, 6, 2);
		return $date;
	}
	//把101010格式转化为 10:10:10  格式
	function changeShortTime($a){
		$date=substr($a, 0,2).':'.substr($a, 2, 2).':'.substr($a, 4, 2);
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

	function getBdAccessToken() {
		$data=file_get_contents('token_bd.txt');
		if ($data != ''){
			$data=explode(',',$data);
			// dump($data);
			$token=$data[0];
			$startdate=strtotime($data[1]);
			$enddate=time();
			// echo $startdate.' ==='.$enddate.'<br>';
			$diff= $enddate-$startdate;
			$minute=floor($diff/60);
		}else $minute = 1;


		if($minute>0){

			$post_data['grant_type']       = 'client_credentials';
			$post_data['client_id']      = C("API_KEY_BD");
			$post_data['client_secret'] = C("SECRET_KEY_BD");
			$o = "";
			foreach ( $post_data as $k => $v )
			{
				$o.= "$k=" . urlencode( $v ). "&" ;
			}
			$curlPost = substr($o,0,-1);
			$curl = 'https://aip.baidubce.com/oauth/2.0/token?'.$curlPost;
			$postUrl = $curl;
			$curl = curl_init();//初始化curl
//			curl_setopt($curl, CURLOPT_URL,$postUrl);//抓取指定网页
//			curl_setopt($curl, CURLOPT_HEADER, 0);//设置header
//			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
//			curl_setopt($curl, CURLOPT_POST, 1);//post提交方式
//			curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);

			curl_setopt ($curl, CURLOPT_URL,$postUrl);
			curl_setopt ($curl,CURLOPT_SSL_VERIFYPEER,FALSE);
			curl_setopt ($curl,CURLOPT_SSL_VERIFYHOST,FALSE);
			curl_setopt ($curl,CURLOPT_RETURNTRANSFER,1);

			$data = curl_exec($curl);//运行curl
			curl_close($curl);
			$data = json_decode($data,true);
			$token=$data['access_token'];

			if($token){
				$content=$token.','.date('Y-m-d H:i:s',time() + $data['expires_in']);
				$fp = fopen("token_bd.txt", "w");//文件被清空后再写入
				if($fp){
					fwrite($fp,$content);
				}
			}else{
				$token = '';
			}
		}
		return $token;
	}

	function request_bd_post($data){
		$url = 'https://aip.baidubce.com/rest/2.0/face/v3/detect?access_token=' . getBdAccessToken();
		$postUrl = $url;
		$curlPost = $data;
		// 初始化curl
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $postUrl);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		// 要求结果为字符串且输出到屏幕上
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		// post提交方式
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
		// 运行curl
		$data = curl_exec($curl);
		curl_close($curl);

		return $data;
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


	//获取图片
	function getJsonImg($var,$vehicleno){
		// echo $var;
		//获得字符串“inphoto”首次出现的位置，返回类型为int型
		$number1 = stripos($var,"inphoto");
		// echo $number1."</br>";
		//获得字符串“parkingname”首次出现的位置，返回类型为int型
		$number2 = stripos($var,"parkingname");
		// echo $number2."</br>";
		//取得截取字符串"inphoto"到字符串"parkingname"的长度
		$number3 = $number2 - $number1;
		// echo $number3."</br>";
		//由于图片内容不包括字符串"inphoto"与"parkingname"，所以要分别减去它们的长度$number1+10，$number3-13
		$result = substr($var,$number1+10,$number3-13);

		//base64_decode函数解码图片
		$img =  base64_decode($result);
		//将图片类型转换为jpg格式
		file_put_contents($vehicleno.'.jpg', $img);

		return $result;

	}
	//获取图片
	function getCarPhoto($var,$vehicleno){
		$number1 = stripos($var,"photo");
		// echo $number1."</br>";
		$number2 = stripos($var,"}]}");
		// echo $number2."</br>";
		$number3 = $number2 - $number1;
		$result = substr($var,$number1+8,$number3-9);
		$img =  base64_decode($result);
		file_put_contents($vehicleno.'.jpg', $img);

		return $result;
	}
	//获取图片
	function getImg($var,$img){
		$number1 = stripos($var,"photo");
		$number2 = stripos($var,"}]}");
		$number3 = $number2 - $number1;
		$result = substr($var,$number1+8,$number3-9);
		$result=str_replace('\/', '/', $result);
		// echo $result;
		if($result){
			$result =  base64_decode($result);
			file_put_contents($img.'.jpg', $result);
			return '/'.$img.'.jpg';
		}else{
			return '/wxtc/Tpl/Public/img/visitor_no.png';
		}
	}
	//将含有图片编码的字串转换图片地址
	//$var:原字串   $img：图片文件名
	function tranImg($var,$img){
		$var = str_replace(array("\r\n","\r","\n"), "", $var);
		$res = json_decode($var, true);
		$currentphoto = $res['body'][0]['currentphoto'];
		$photo = $res['body'][0]['photo'];
		$prefix = rand(1,100);
		if ($currentphoto){//第一个图层
			$name = $prefix.$img.'1';
			$currentphoto=str_replace('\/', '/', $currentphoto);
			$currentphoto =  base64_decode($currentphoto);
			file_put_contents($name.'.jpg', $currentphoto);//将字符转换为图片
			$res['body'][0]['currentphoto'] = $name.'.jpg';//photo转为图片的地址
		}
		if($photo){//第二个图层
			$name = $prefix.$img.'2';
			$photo=str_replace('\/', '/', $photo);
			$photo =  base64_decode($photo);
			file_put_contents($name.'.jpg', $photo);//将字符转换为图片
			$res['body'][0]['photo'] = $name.'.jpg';//photo转为图片的地址
		}
		return json_encode($res);
	}

	//图片转BASE64
	function imgToBase64($img){
		$image_info =getimagesize($img);
		// dump($image_info);data:{image/jpeg};base64,
		// $base64_image_content = "data:{".$image_info['mime']."};base64," .chunk_split(base64_encode(file_get_contents($img))); 
		$base64_image_content = chunk_split(base64_encode(file_get_contents($img))); 
		return $base64_image_content;
	}

	//获取照片
	function getPhoto($imgno){
		$mcode='300206';
		$phonenumber=$_COOKIE['phonenumber'];
		$wechatcode=$_COOKIE['wechatcode'];
		$head=getHead($mcode); $data='{'.$head.',"body":[{"phonenumber":"'.$phonenumber.'","wechatcode":"'.$wechatcode.'","imgno":"'.$imgno.'"}]}';
		$reData=postDataJson($data);
		// echo $reData;
		if(strpos($reData,'0000')){
			$img=getImg($reData,$imgno);
			// echo '<img src="data:{image/jpeg};base64,'.$img.'">';
			return $img;
			//echo '/wxtc/Tpl/Public/images/visiter.png';
		}else{
			return '/wxtc/Tpl/Public/images/visiter.png';
		}
	}
	// POST提交到接口并解析JOSIN
	function postData($data){
		// exit();
		// $url=C('API_URL');

		logger('request000:'.$data);//请求数据的log
		$url=C('API_URL');
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen($data))
		);
		$result = curl_exec($ch);

		logger("response000:$result");//响应数据的log

		// echo $result;
		$result = json_decode($result,true);

		logger("decoe000:$result");//响应数据的log
		// dump($result);
		return $result;
 
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
function postMediaDataTo($target,$url){//发送消息
	// exit();
	// $url=C('API_URL');
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");

	curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'User-Agent: bosiny.smarthome',
			'Image-Type: user'
			)
	);
	$file_info = array (
		'picture' => '@'.$target
	);
//	$d = array("media" => $data, 'form-data' => $file_info);
	curl_setopt($ch, CURLOPT_POSTFIELDS,$file_info);
	$result = curl_exec($ch);
	curl_close($ch);
	return $result;

}
// POST提交到接口
	function postDataJson($data){
		logger('request111:'.$data);//请求数据的log
		$url=C('API_URL');
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen($data))
		);
		$result = curl_exec($ch);
		logger("response111:$result");//响应数据的log
		return $result;
    }
	// POST提交到接口
	function postDataJsonAPP($data){
		logger('request111:'.$data);//请求数据的log
		$url=C('APP_URL');
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_NOSIGNAL, 1);     //注意，毫秒超时一定要设置这个
		curl_setopt($ch, CURLOPT_TIMEOUT_MS, 3000); //超时毫秒，cURL 7.16.2中被加入。从PHP 5.2.3起可使用
		curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen($data))
		);
		$result = curl_exec($ch);
		try{
			$reData = json_decode($result, true);
			if ($reData['head'][0]['rcode'] == '0031') {
				foreach ($_COOKIE as $key => $value) {
					setcookie($key,"",time()-60);
				}
				setcookie('phonenumber', '',time()-60,'/');
			}
		}catch(Exception $e){

		}

		logger("response111:$result");//响应数据的log
		return $result;
	}
	function postPayDataJsonAPP($data,$commid,$cmd){
		logger('request111:'.$data);//请求数据的log
		$url=C('APP_URL')."/smartcommunity?commid=".$commid."&cmd=".$cmd;
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_NOSIGNAL, 1);     //注意，毫秒超时一定要设置这个
		curl_setopt($ch, CURLOPT_TIMEOUT_MS, 3000); //超时毫秒，cURL 7.16.2中被加入。从PHP 5.2.3起可使用
		curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen($data))
		);
		$result = curl_exec($ch);
		try{
			$reData = json_decode($result, true);
			if ($reData['head'][0]['rcode'] == '0031') {
				foreach ($_COOKIE as $key => $value) {
					setcookie($key,"",time()-60);
				}
				setcookie('phonenumber', '',time()-60,'/');
			}
		}catch(Exception $e){

		}

		logger("response111:$result");//响应数据的log
		return $result;
	}
	function request_post($data)
    {
        
		$access_token=getAccessToken();
		$postUrl='https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;
        $postUrl = $url;
        $curlPost = $data;
        $ch = curl_init(); //初始化curl
        curl_setopt($ch, CURLOPT_URL, $postUrl); //抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0); //设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1); //post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $result = curl_exec($ch); //运行curl
        curl_close($ch);
		echo $result;
		// echo 'a';
        return $result;
    }
	
	function wxPostData2($data){
		$access_token=getAccessToken();
		$url='https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;
		$ch = curl_init($url);
		$data='';
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen($data))
		);
		$result = curl_exec($ch);
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
	
	
		// GET提交到接口
	function wxGetData($url){
		$ch = curl_init();
		curl_setopt ($ch, CURLOPT_URL,$url);
		curl_setopt ($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
		curl_setopt ($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
		curl_setopt ($ch,CURLOPT_RETURNTRANSFER,1);
		$result = curl_exec($ch) ;
		curl_close($ch);
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
	function getHead($mcode){
		$data['mcode']=$mcode;
		$data['mid']=getMid();
		$data['date']=getShortDate();
		$data['time']=getShortTime();
		$data['ver']='0001';
		$data['msgatr']='10';
		$data['safeflg']='11';
		$data['key']=C('MAC_KEY');
		$data['mac']=strtoupper(MD5('mcode='.$data['mcode'].'&mid='.$data['mid'].'&date='.$data['date'].'&time='.$data['time'].'&ver='.$data['ver'].'&msgatr='.$data['msgatr'].'&safeflg='.$data['safeflg'].'&key='.$data['key'].''));
		// dump($data);
		$head='"head":[{"ver":"'.$data['ver'].'","mid":"'.$data['mid'].'","mac":"'.$data['mac'].'","mcode":"'.$data['mcode'].'","msgatr":"10","safeflg":"'.$data['safeflg'].'","time":"'.$data['time'].'","date":"'.$data['date'].'"}]';
		
		return $head;
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
    	dump($output);
    	return $output;
    }
	function http_request1($data=null,$url){//结果去除<pre>
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



	function http_simulate_head()
	{
		$data['rcode']='0000';
		$head='"head":[{"rcode":"'.$data['rcode'].'"}]';
		return $head;
	}

	function http_simulate_body($mcode)
	{
		if($mcode=='400305')
		{
			$body='"body":[{"class_id":"001","class_name":"早餐"},{"class_id":"002","class_name":"中餐"},{"class_id":"003","class_name":"晚餐"},{"class_id":"004","class_name":"宵夜"}]';
			return $body;
		}
		else if($mcode=='400306')
		{
			$body='"body":[{"item_id":"001","item_name":"宫保鸡丁","price":"10.89","total":"100"},{"item_id":"002","item_name":"鱼香茄子","price":"9.99","total":"200"},{"item_id":"003","item_name":"番茄鸡蛋","price":"9.99","total":"200"},{"item_id":"004","item_name":"糖醋排骨","price":"9.99","total":"200"},{"item_id":"005","item_name":"红烧肉饭","price":"9.99","total":"200"},{"item_id":"006","item_name":"糖醋里脊","price":"9.99","total":"200"},{"item_id":"007","item_name":"毛血旺","price":"9.99","total":"200"},{"item_id":"008","item_name":"大盘鸡","price":"9.99","total":"200"}]';
			//$body='"body":[{"item_id":"001","item_name":"宫保鸡丁","price":"10.89","total":"100"},{"item_id":"002","item_name":"鱼香茄子","price":"9.99","total":"200"}]';
			return $body;
		}

	}

	//log文件
    function logger($log=null){
    	if(true){
    		$txt = 'log.txt';
			$content = "\n\n".date('Y-m-d H:i:s').$log;
			$fp = fopen($txt,"a+");
			fwrite($fp,$content);
			fclose($fp);
    	}
    }
	function loggerDebug($log=null){
		$txt = 'debug.txt';
		$content = "\n\n".date('Y-m-d H:i:s')."=".$log;
		$fp = fopen($txt,"a+");
		fwrite($fp,$content);
		fclose($fp);
	}

	/**
	 * 图片加水印(适用于png/jpg/gif格式)
	 *
	 * @author flynetcn
	 *
	 * @param $srcImg 原图片
	 * @param $x 水印位置的X坐标
	 * @param $y 水印位置的Y坐标
	 *
	 * @return 成功 -- 加水印后的新图片地址
	 *     失败 -- -1:原文件不存在, -2:水印图片不存在, -3:原文件图像对象建立失败-4:水印文件图像对象建立失败, -5:加水印后的新图片保存失败
	 */
	function img_water_mark($srcImg, $x, $y){

	    $waterImg = "./wxtc/Tpl/Public/img/local.png"; //水印图片路径
		$temp = pathinfo($srcImg);
		$exte = $temp['extension'];
		$name = $temp['filename'];

		$savename = $name.'_local'.rand(1,100).'.'.$exte;
		$savefile = $savename;

		$srcinfo = @getimagesize($srcImg);
		if (!$srcinfo) {
			return -1; //原文件不存在
		}
		$waterinfo = @getimagesize($waterImg);
		if (!$waterinfo) {
			return -2; //水印图片不存在
		}
		$srcImgObj = image_create_from_ext($srcImg);
		if (!$srcImgObj) {
			return -3; //原文件图像对象建立失败
		}
		$waterImgObj = image_create_from_ext($waterImg);
		if (!$waterImgObj) {
			return -4; //水印文件图像对象建立失败
		}

		//水印位置
		$x = ($srcinfo[0] * $x) - ($waterinfo[0] / 2);
		$y = ($srcinfo[1] * $y) - ($waterinfo[1] / 2);

		//如果水印图片本身带透明色，则使用imagecopy方法
		imagecopy($srcImgObj, $waterImgObj, $x, $y, 0, 0, $waterinfo[0], $waterinfo[1]);

		switch ($srcinfo[2]) {
			case 1: imagegif($srcImgObj, $savefile); break;
			case 2: imagejpeg($srcImgObj, $savefile); break;
			case 3: imagepng($srcImgObj, $savefile); break;
			default: return -5; //保存失败
		}
		imagedestroy($srcImgObj);
		imagedestroy($waterImgObj);
		return $savefile;
	}

	function image_create_from_ext($imgfile){
		$info = getimagesize($imgfile);
		$im = null;
		switch ($info[2]) {
			case 1: $im=imagecreatefromgif($imgfile); break;
			case 2: $im=imagecreatefromjpeg($imgfile); break;
			case 3: $im=imagecreatefrompng($imgfile); break;
		}
		return $im;
	}
