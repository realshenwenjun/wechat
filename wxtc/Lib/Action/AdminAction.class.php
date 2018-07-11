<?php

class AdminAction extends Action
{
    //初始化
    public function _initialize()
    {
        //判断用户是否已经登录
        $requestUrl = $_SERVER['REQUEST_URI'];
        logger($requestUrl);
        $whiteName = array(
            //以下为需要配置的白名单
            'login.html',
            'loginForm',
            'valid'
        );
        if (!$this->checkUrl($requestUrl, $whiteName)) {
            if (!isset($_SESSION['uid'])) {
                $this->redirect('Admin/login');
            }
        }
    }

    public function checkUrl($u, $whiteName)
    {
        foreach ($whiteName as $i) {
            if (strpos($u, $i)) {
                return true;
            }
        }
        return false;
    }

    //主页
    public function index()
    {
        $this->display();
    }

    //登陆页面
    public function login()
    {
        $this->display();
    }
    public function loginOut()
    {
        unset($_SESSION['uid']);
        $this->redirect('Admin/login');
    }

    //登陆
    public function loginForm()
    {
        $userName = $_POST['userName'];
        $password = $_POST['password'];
        if (C("USER_NAME") == $userName && C("PASSWORD") == $password) {
            session('uid', $userName);
            $this->redirect("Admin/index");
        } else {
            $this->error("用户名活密码错误");
        }
    }

    public function valid()
    {
        $echoStr = $_GET["echostr"];
        if ($echoStr != '') {
            //valid signature , option
            if ($this->checkSignature()) {
                echo $echoStr;
                exit;
            }
        } else {
            $this->responseMsg();
        }
        echo '';
        exit;
    }

    private function checkSignature()
    {
        // you must define TOKEN by yourself
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = C("TOKEN");
        $tmpArr = array($token, $timestamp, $nonce);
        // use SORT_STRING rule
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }

    public function responseMsg()
    {
        //get post data, May be due to the different environments
        $postStr = file_get_contents("php://input");
        logger($postStr);
        //extract post data
        if (!empty($postStr)) {
            /* libxml_disable_entity_loader is to prevent XML eXternal Entity Injection,
               the best way is to check the validity of xml by yourself */
            libxml_disable_entity_loader(true);
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $fromUsername = $postObj->FromUserName;
            $toUsername = $postObj->ToUserName;
            $event = $postObj->Event;
            $eventKey = $postObj->EventKey;
            if ($event == 'subscribe' || $event == 'SCAN') {//扫描带参数二维码
                if ($event == 'subscribe') {
                    $eventKey = str_replace('qrscene_', '', $eventKey);
                }
                logger("$eventKey:" . $eventKey);
                if ($eventKey != "") {
                    $array = explode('&', $eventKey);
                    //发送消息模板消息
                    $data = '{"touser":"' . $fromUsername . '","template_id":"1eBQ6Tftp4FvvJqcydaIgVq4CfMKl-TrrkqSxsJrN2A","url":"' . C("WEB_URL") . '/wx.php//Index/passdoc.html?ordercode=' . $array[0] . '","topcolor":"#FF0000","data":{"first":{"value":"尊敬的 ' . $array[1] . ' 先生/小姐，您提交的预约申请已审核完毕。","color":"#173177"},"keyword1":{"value":"访客预约","color":"#173177"},"keyword2":{"value":"拜访 ' . $array[2] . '","color":"#173177"},"keyword3":{"value":"审核通过","color":"#173177"},"keyword4":{"value":"拜访中","color":"#173177"},"keyword5":{"value":"' . date("Y-m-d H:i:s", time()) . '","color":"#173177"},"remark":{"value":"如有任何疑问，请咨询被访人：' . $array[3] . '","color":"#173177"}}}';
                    //postDataTo($data, self::$sendToWxUrl);
                    http_request1($data,self::$template_url.getAccessToken());
                }
            }

        }
    }

    /**
     * 菜单列表
     */
    public function customerMenu()
    {
        $string = file_get_contents("db.json");
        $dbObj = json_decode($string);
        foreach ($dbObj as $key => $value) {

            if ($key == "customerMenu") {
                $this->assign('customerMenu', json_encode($value));
                break;
            }
        }
        $this->display();
//		echo $reData;
    }

    /**
     * 删除单个菜单
     */
    public function deleteCustomerMenu()
    {
        $id = $_GET['id'];
        $string = file_get_contents("db.json");
        $dbObj = json_decode($string);
        $customerMenu = [];
        foreach ($dbObj as $key => $value) {
            if ($key == "customerMenu") {
                $customerMenu = $value;
                break;
            }
        }
        $this->del_menu_by_id($customerMenu,$id);
        $dbObj->customerMenu = $customerMenu;
        $fp = fopen("db.json", "w");
        fwrite($fp, json_encode($dbObj));
        fclose($fp);
        $this->ajaxReturn($string, '成功', 0);
    }

    private function del_menu_by_id(&$menuArray,$id){
        foreach($menuArray as $k => $v){
            if(strcmp($v->id, $id) == 0){
                unset($menuArray[$k]);
            }else{
                if(count($v->sub_button) > 0){
                    $this->del_menu_by_id($menuArray[$k]->sub_button,$id);
                }
            }
        }
    }


    /**
     * 发布自定义菜单
     */
    public function publishCustomerMenu()
    {
        $string = file_get_contents("db.json");
        $dbObj = json_decode($string);
        $customerMenu = [];
        foreach ($dbObj as $key => $value) {

            if ($key == "customerMenu") {
                $customerMenu = $value;
                break;
            }
        }
        $customerMenuWx = [];
        foreach ($customerMenu as $k => $v) {
            array_push($customerMenuWx, $this->convertDb2Menu($customerMenu, $k, $v));
        }

        $p = '{"button":'.json_encode($customerMenuWx,JSON_UNESCAPED_UNICODE).'}';
//        //先删除菜单
//        $url = 'https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=' . getAccessToken();
//        $reData = wxGetData($url);
//        $reData = json_decode($reData);
//        if ($reData->errcode == 0) {
//
//        } else
//            $this->ajaxReturn($reData, '失败', 1);
        //删除成功后再次发布
        $reData = http_request1($p, self::$menu_create_url.getAccessToken());
        $reData = json_decode($reData);
        if ($reData->errcode == 0) {
            $dbObj->customerMenu = $customerMenu;
            $fp = fopen("db.json", "w");
            fwrite($fp, json_encode($dbObj));
            fclose($fp);
            $this->ajaxReturn($reData, '成功', 0);
        } else
            $this->ajaxReturn($p, $reData->errmsg, 2);
    }

    private function convertDb2Menu(&$pmenu, $k, $v)
    {
        $pmenu[$k]->state = '1';
        $m = json_decode("{}");
        if ($v->button == 'button') {
            if( $v->type == 'view'){
                $m->name = $v->name;
                if (count($v->sub_button) > 0) {
                    $m->sub_button = [];
                    foreach ($v->sub_button as $sk => $sv) {
                        array_push($m->sub_button, $this->convertDb2Menu($pmenu[$k]->sub_button, $sk, $sv));
                    }

                }else{
                    $m->type = $v->type;
                    $m->url = $v->url;
                }
            }else if($v->type == "view_limited"){
                $m->name = $v->name;
                if (count($v->sub_button) > 0) {
                    $m->sub_button = [];
                    foreach ($v->sub_button as $sk => $sv) {
                        array_push($m->sub_button, $this->convertDb2Menu($pmenu[$k]->sub_button, $sk, $sv));
                    }

                }else{
                    $m->type = $v->type;
                    $m->media_id = $v->media_id;
                }
            }

        }

        return $m;
    }

    /**
     * 永久素材列表 post
     */
    public function batchget_material()
    {
        $type = $_POST["type"];
        $offset = $_POST["offset"];
        $count = $_POST["count"];
        $postStr = '{"type":"' . $type . '","offset":' . $offset . ',"count":' . $count . '}';

        //http请求方式: POST

        $reData = http_request1($postStr, self::$batchget_material_url.getAccessToken());
        $reData = json_decode($reData);
        $reData->page = $this->page($reData->total_count,$offset,$count);
        if (isset($reData->errcode)) {
            $this->ajaxReturn($reData, '失败', 1);
        }else $this->ajaxReturn($reData, '成功', 0);
//        $r = '{"item":[{"media_id":"iOCNARdq5rt1Dl4amaZItHrin1nndsGi_TXD6s435gM","content":{"news_item":[{"title":"软件许可及服务协议","author":"","digest":"欢迎您使用博西尼“乐在家”软件及服务！\n使用前，敬请阅读并遵守《博西尼“乐在家”软件许可及服务协议》。","content":"<p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">导言<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">欢迎您使用博西尼“乐在家”软件及服务！<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">为使用博西尼“乐在家”软件（以下简称“本软件”）及服务，您应当阅读并遵守《博西尼“乐在家”软件许可及服务协议》（以下简称“本协议”）。请您务必审慎阅读、充分理解各条款内容，特别是免除或者限制责任的条款，以及开通或使用某项服务的单独协议，并选择接受或不接受。限制、免责条款可能以加粗形式提示您注意。<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">除非您已阅读并接受本协议所有条款，否则您无权下载、安装或使用本软件及相关服务。您的下载、安装、使用、激活乐在家帐号、登录等行为即视为您已阅读并同意上述协议的约束。<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">如果您未满18周岁，请在法定监护人的陪同下阅读本协议及其他上述协议，并特别注意未成年人使用条款。<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">一、协议的范围<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">1.1&nbsp;<\/span><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">协议适用主体范围<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">本协议是您与博西尼之间关于您下载、安装、使用、复制本软件，以及使用博西尼提供相关服务所订立的协议。<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">1.2&nbsp;<\/span><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">协议关系及冲突条款<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">本协议被视为博西尼与第三方社区管理服务主体关于出入口控制系统应用、物业服务等合同的补充协议，是其不可分割的组成部分，与其构成统一整体。本协议与上述内容存在冲突的，以本协议为准。<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">本协议内容同时包括博西尼可能不断发布的关于本服务的相关协议、业务规则等内容。上述内容一经正式发布，即为本协议不可分割的组成部分，您同样应当遵守。<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">二、关于本服务<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">2.1&nbsp;<\/span><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">本服务的内容<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">本服务内容是指博西尼向用户提供的跨平台智慧社区服务工具（以下简称“乐在家”），包括：用户使用APP便捷开门、访客邀请、自助服务、查看社区实景、查看社区通知、缴纳每月的物业管理费、停车管理费用、故障报修、投诉建议、大屏信息发布等应用场景的软件许可及服务（以下简称“本服务”）。<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">2.2&nbsp;<\/span><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">本服务的形式<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">2.2.1&nbsp;<\/span><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">您使用本服务需要下载博西尼乐在家客户端软件，对于这些软件，博西尼给予您一项个人的、不可转让及非排他性的许可。您仅可为访问或使用本服务的目的而使用这些软件及服务。<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">2.2.2&nbsp;<\/span><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">本服务中博西尼乐在家客户端软件提供包括但不限于iOS、Android等多个应用版本，用户必须选择与所安装终端设备相匹配的软件版本。<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">2.2.3<\/span><\/strong><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">本服务中博西尼乐在家客户端软件可能需要但不局限于第三方社区管理服务主体提供的出入口控制管理系统的硬件、网络配合，第三方社区管理服务主体（以下简称“系统服务机构”）包括但不限于各类组织的物业管理机构、安保管理机构。<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">2.3&nbsp;<\/span><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">本服务许可的范围<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">2.3.1&nbsp;<\/span><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">博西尼给予您一项个人的、不可转让及非排他性的许可，以使用本软件。您可以为非商业目的在单一台终端设备上安装、使用、显示、运行本软件。<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">2.3.2&nbsp;<\/span><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">本条及本协议其他条款未明示授权的其他一切权利仍由博西尼保留，您在行使这些权利时须另外取得博西尼的书面许可。博西尼如果未行使前述任何权利，并不构成对该权利的放弃。<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">三、软件的获取<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">3.1&nbsp;<\/span><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">您可以直接从博西尼授权的第三方获取。<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">3.2&nbsp;<\/span><\/strong><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">如果您从未经博西尼授权的第三方获取本软件或与本软件名称相同的安装程序，博西尼无法保证该软件能够正常使用，并对因此给您造成的损失不予负责。<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">四、软件的安装与卸载<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">4.1&nbsp;<\/span><\/strong><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">博西尼可能为不同的终端设备开发了不同的软件版本，您应当根据实际情况选择下载合适的版本进行安装。<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">4.2&nbsp;<\/span><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">下载安装程序后，您需要按照该程序提示的步骤正确安装。<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">4.3&nbsp;<\/span><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">如果您不再需要使用本软件或者需要安装新版软件，可以自行卸载。<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">五、软件的更新<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">5.1&nbsp;<\/span><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">为了改善用户体验、完善服务内容，博西尼将不断努力开发新的服务，并为您不时提供软件更新（这些更新可能会采取软件替换、修改、功能强化、版本升级等形式）。<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">5.2&nbsp;<\/span><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">为了保证本软件及服务的安全性和功能的一致性，博西尼有权不经向您特别通知而对软件进行更新，或者对软件的部分功能效果进行改变或限制。<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">5.3&nbsp;<\/span><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">本软件新版本发布后，旧版本的软件可能无法使用。博西尼不保证旧版本软件继续可用及相应的客户服务，请您随时核对并下载最新版本。<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">六、用户个人信息保护<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">6.1&nbsp;<\/span><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">保护用户个人信息是博西尼的一项基本原则，博西尼将会采取合理的措施保护用户的个人信息。博西尼对相关信息采用专业加密存储与传输方式，保障用户个人信息的安全。用户的个人房屋信息、出入口控制权限、开门记录、访客记录、物业缴费、信息发布等由用户授权的系统服务机构执行管理，由于系统服务机构导致的个人信息留存与博西尼无关。<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">6.2&nbsp;<\/span><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">您在注册帐号或使用本服务的过程中，需要提供一些必要的信息，例如：为向您提供帐号注册服务或进行用户身份识别，需要您填写手机号码；需要选择您已经在系统服务机构注册的房屋地址；支付功能需要您同意允许相关支付调用等。若国家法律法规或政策有特殊规定的，您需要提供真实的身份信息。若您提供的信息不完整，则无法使用本服务或在使用过程中受到限制。<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">6.3&nbsp;<\/span><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">一般情况下，您可以通过打开乐在家软件内的通行二维码，在已获得授权的二维码读卡器上扫描，即可通行；可以随时登记并生成访客二维码并分享给访客使用；可以支付停车管理费、物业管理费、水电费等由系统服务机构托管代收的费用；可以进行物业报修、社区信息发布、建议投诉等信息服务；可以随时修改个人登录密码；但出于安全性和身份识别的考虑，您可能需要系统服务机构的帮助才可以完成设备重新激活、初始注册信息寻回等操作。<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">6.4&nbsp;<\/span><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">除了向您提供服务的系统服务机构，未经您的同意，博西尼不会向以外的任何公司、组织和个人披露您的个人信息，但法律法规另有规定的除外。<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">七、主权利义务条款<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">7.1&nbsp;<\/span><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">帐号使用规范<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">7.1.1&nbsp;<\/span><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">您在使用本服务前需要激活一个乐在家帐号。乐在家帐号必须经由系统服务机构预先登记及授权后，方可进行激活。账号需要与手机号码进行绑定。<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">7.1.2&nbsp;<\/span><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">用户完成申请激活手续后，仅获得乐在家帐号的使用权，且该使用权仅属于初始申请激活人。同时，初始申请激活人不得赠与、借用、租用、转让或售卖乐在家帐号或者以其他方式许可非初始申请激活人使用乐在家帐号。<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">7.1.3&nbsp;<\/span><\/strong><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">用户有责任妥善保管注册帐户信息及帐户密码的安全，用户需要对激活帐户以及密码下的行为承担法律责任。用户同意在任何情况下不向他人透露帐户及密码信息。当在您怀疑他人在使用您的帐号时，您应立即通知向您提供服务的系统服务机构。<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">7.1.4&nbsp;<\/span><\/strong><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">如因系统服务机构对用户在其管理范围内的开门权限进行回收、挂失、删除等操作而影响用户通行的，由此带来的任何影响均由系统服务机构承担。<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">7.2&nbsp;<\/span><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">用户注意事项<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">7.2.1&nbsp;<\/span><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">您理解并同意：为了向您提供有效的服务，本软件会利用您终端设备的处理器和带宽等资源。本软件使用过程中可能产生数据流量的费用，用户需自行向运营商了解相关资费信息，并自行承担相关费用。<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">7.2.2&nbsp;<\/span><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">您理解并同意：本软件的某些功能可能会让第三方系统服务机构知晓用户的信息，例如：系统服务机构可以管理授权您使用的出入口控制点、使用时间；系统服务机构可以查看您的通行开门记录。<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">7.2.3&nbsp;<\/span><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">您理解并同意博西尼将会尽其商业上的合理努力保障您在本软件及服务中的数据存储安全，但是，博西尼并不能就此提供完全保证，包括但不限于以下情形：<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">7.2.3.1&nbsp;<\/span><\/strong><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">博西尼不对您在本软件及服务中相关数据的删除或储存失败负责；<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">7.2.3.2&nbsp;<\/span><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">博西尼有权根据实际情况自行决定单个用户在本软件及服务中数据的最长储存期限，并在服务器上为其分配数据最大存储空间等。<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">7.2.3.3&nbsp;<\/span><\/strong><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">如果您被系统服务机构停止使用其管理范围内的出入口控制点，系统服务机构可以从服务器上永久地删除您的数据。博西尼没有义务向您返还任何数据。<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">7.2.4&nbsp;<\/span><\/strong><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">用户在使用本软件及服务时，须自行承担如下来自博西尼不可掌控的风险内容，包括但不限于：<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">7.2.4.1&nbsp;<\/span><\/strong><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">由于不可抗拒因素可能引起的个人信息丢失、泄漏等风险；<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">7.2.4.2&nbsp;<\/span><\/strong><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">用户必须选择与所安装终端设备相匹配的软件版本，否则，由于软件与终端设备型号不相匹配所导致的任何问题或损害，均由用户自行承担；<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">7.2.4.3&nbsp;<\/span><\/strong><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">用户在使用本软件时，需要系统服务机构提供的出入口控制点的门禁、停车管理等硬件设备、网络支持，因系统服务机构提供出入口控制服务所可能导致的风险，由用户及系统服务机构自行承担；<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">7.2.4.4&nbsp;<\/span><\/strong><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">用户因终端设备遗失而可能带来的风险和责任；<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">7.2.5.5&nbsp;<\/span><\/strong><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">由于用户终端设备无线网络或移动数据网络不稳定等原因，所引起的博西尼乐在家登录失败、信息无法提交或提交耗时较长的风险。<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">7.3&nbsp;<\/span><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">第三方系统管理机构产品和服务<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">7.3.1&nbsp;<\/span><\/strong><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">您在使用关联本软件的第三方系统管理机构提供的产品或服务时，除遵守本协议约定外，还应遵守第三方的用户协议。博西尼和第三方系统管理机构对可能出现的纠纷在法律规定和约定的范围内各自承担责任。<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">7.3.2&nbsp;<\/span><\/strong><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">本软件可能会根据第三方系统管理机构对其管辖范围内的服务的要求，提供不同的控制策略。例如访客通行权限可能限制使用时间、限制使用次数等。此类控制策略的设置由第三方系统管理机构执行，博西尼不保证其控制策略的安全性、准确性、规范性及其他不确定的风险，由此若引发的任何争议及损害，与博西尼无关，博西尼不承担任何责任。<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">八、用户行为规范<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">8.1&nbsp;<\/span><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">软件使用规范<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">8.1.1&nbsp;<\/span><\/strong><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">除非法律允许或博西尼书面许可，您使用本软件过程中不得从事下列行为：<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">8.1.1.1&nbsp;<\/span><\/strong><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">删除本软件及其副本上关于著作权的信息；<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">8.1.1.2&nbsp;<\/span><\/strong><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">对本软件进行反向工程、反向汇编、反向编译，或者以其他方式尝试发现本软件的源代码；<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">8.1.1.3&nbsp;<\/span><\/strong><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">对本软件或者本软件运行过程中释放到任何终端内存中的数据、软件运行过程中客户端与服务器端的交互数据，以及本软件运行所必需的系统数据，进行复制、修改、增加、删除、挂接运行或创作任何衍生作品，形式包括但不限于使用插件、外挂或非博西尼经授权的第三方工具\/服务接入本软件和相关系统；<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">8.1.1.4&nbsp;<\/span><\/strong><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">通过修改或伪造软件运行中的指令、数据，增加、删减、变动软件的功能或运行效果，或者将用于上述用途的软件、方法进行运营或向公众传播，无论这些行为是否为商业目的；<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">8.1.1.5&nbsp;<\/span><\/strong><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">通过非博西尼开发、授权的第三方软件、插件、外挂、系统，登录或使用博西尼软件及服务，或制作、发布、传播上述工具；自行或者授权他人、第三方软件对本软件及其组件、模块、数据进行干扰；<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">8.1.1.7&nbsp;<\/span><\/strong><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">其他未经博西尼明示授权的行为。<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">8.2<\/span><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">对自己行为负责<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">您充分了解并同意，您必须为自己注册帐号下的一切行为负责，包括您所对出入口控制系统操作以及由此产生的任何后果。<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">您应对借用设备、账号密码保护、邀请访客、缴费支付、信息发布等操作加以判断，并承担因使用而引起的所有风险。博西尼无法且不会对因前述风险而导致的任何损失或损害承担责任。<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">九、知识产权声明<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">9.1&nbsp;<\/span><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">博西尼是本软件的知识产权权利人。本软件的一切著作权、商标权、专利权、商业秘密等知识产权，以及与本软件相关的所有信息内容（包括但不限于文字、图片、音频、视频、界面设计、版面框架、使用说明等）均受中华人民共和国法律法规和相应的国际条约保护，博西尼享有上述知识产权，但相关权利人依照法律规定应享有的权利除外。<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">9.2&nbsp;<\/span><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">未经博西尼或相关权利人书面同意，您不得为任何商业或非商业目的自行或许可任何第三方实施、利用、转让上述知识产权。<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">十、终端安全责任<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">10.1&nbsp;<\/span><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">您理解并同意，本软件同大多数移动终端软件一样，可能会受多种因素影响，包括但不限于用户原因、网络服务质量、社会环境等；也可能会受各种安全问题的侵扰，包括但不限于他人非法利用用户资料，进行现实中的骚扰；用户下载安装的其他软件或访问的其他网站中可能含有病毒、木马程序或其他恶意程序，威胁您的终端设备信息和数据安全，继而影响本软件的正常使用等。因此，您应加强信息安全及个人信息的保护意识，注意密码保护，以免遭受损失。<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">10.2&nbsp;<\/span><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">您不得制作、发布、使用、传播用于窃取乐在家帐号及他人个人信息、财产的恶意程序。<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">10.3&nbsp;<\/span><\/strong><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">维护软件安全与正常使用是博西尼和您的共同责任，博西尼将按照行业标准合理审慎地采取必要技术措施保护您的终端设备信息和数据安全，但是您承认和同意博西尼并不能就此提供完全保证。<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">十一、第三方软件或技术<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">11.1&nbsp;<\/span><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">本软件可能会使用第三方软件或技术（包括本软件可能使用的开源代码和公共领域代码等，下同），这种使用已经获得合法授权。<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">11.2&nbsp;<\/span><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">本软件如果使用了第三方的软件或技术，博西尼将按照相关法规或约定，对相关的协议或其他文件，可能通过本协议附件、在本软件安装包特定文件夹中打包、或通过开源软件页面等形式进行展示，它们可能会以“软件使用许可协议”、“授权协议”、“开源代码许可证”或其他形式来表达。前述通过各种形式展现的相关协议、其他文件及网页，均是本协议不可分割的组成部分，与本协议具有同等的法律效力，您应当遵守这些要求。如果您没有遵守这些要求，该第三方或者国家机关可能会对您提起诉讼、罚款或采取其他制裁措施，并要求博西尼给予协助，您应当自行承担法律责任。<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">11.3&nbsp;<\/span><\/strong><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">如因本软件使用的第三方系统管理机构所提供技术及服务的任何纠纷，应由该第三方负责解决，博西尼不承担任何责任。博西尼无法代替第三方系统管理机构对业务操作、设备管理、权限管理等提供客服支持，请您与第三方系统管理机构联系需求支持。<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">十二、其他<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">12.1&nbsp;<\/span><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">您使用本软件即视为您已阅读并同意受本协议的约束。<\/span><\/strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">博西尼有权在必要时修改本协议条款。您可以在本软件的最新版本中查阅相关协议条款。本协议条款变更后，如果您继续使用本软件，即视为您已接受修改后的协议。如果您不接受修改后的协议，应当停止使用本软件。<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">12.2&nbsp;<\/span><\/strong><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">本协议签订地为中华人民共和国广东省深圳市宝安区。<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">12.3&nbsp;<\/span><\/strong><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">本协议的成立、生效、履行、解释及纠纷解决，适用中华人民共和国大陆地区法律（不包括冲突法）。<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">12.4&nbsp;<\/span><\/strong><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">若您和博西尼之间发生任何纠纷或争议，首先应友好协商解决；协商不成的，您同意将纠纷或争议提交本协议签订地有管辖权的人民法院管辖。<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">12.5&nbsp;<\/span><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">本协议所有条款的标题仅为阅读方便，本身并无实际涵义，不能作为本协议涵义解释的依据。<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">12.6&nbsp;<\/span><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">本协议条款无论因何种原因部分无效或不可执行，其余条款仍有效，对双方具有约束力。（正文结束）<\/span><\/p><p style=\";margin-bottom:.1px;line-height:26px;background:white\"><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">&nbsp;<\/span><\/p><p style=\";margin-bottom:.1px;text-align:right;line-height:26px;background:white\"><strong><span style=\"font-size: 14px;font-family: 微软雅黑, sans-serif\">深圳市博西尼电子有限公司<\/span><\/strong><\/p><p style=\";margin-bottom:.1px;line-height:26px\"><span style=\"font-size: 18px;font-family: Helvetica, sans-serif\">&nbsp;<\/span><\/p><p>&nbsp;<\/p><p><br  \/><\/p>","content_source_url":"","thumb_media_id":"iOCNARdq5rt1Dl4amaZItLjHQITlxaX42MJKzu12xxY","show_cover_pic":0,"url":"http:\/\/mp.weixin.qq.com\/s?__biz=MzI0MzQ5MDc2OQ==&mid=100000020&idx=1&sn=87b975bb805951168b9719632a7069f0&chksm=696d0baa5e1a82bc6642d0fba0a03ea1cc7cb55807c2923a9d0bc2e1b8da08e6188d1b3bb0d9#rd","thumb_url":"http:\/\/mmbiz.qpic.cn\/mmbiz_png\/IYECc9VMw8k8Cv7ic2ibVEicJmdKTfhbIceVHl2PtibWjLgmT4OPJUpV8IN44IrJrlpxdLpvcH6BzEImTGfbZ79rgg\/0?wx_fmt=png","need_open_comment":1,"only_fans_can_comment":0},{"title":"“乐在家”功能介绍","author":"","digest":"乐在家，欢乐每一家","content":"<p><img data-s=\"300,640\" data-type=\"png\" data-src=\"http:\/\/mmbiz.qpic.cn\/mmbiz_png\/IYECc9VMw8k8Cv7ic2ibVEicJmdKTfhbIced0HP0TCYia9UptPkFiac7CiahTpfYibXlUibUOpYvicVJ0xyWIELYd9LiaoFQ\/0?wx_fmt=png\" data-ratio=\"2.857142857142857\" data-w=\"350\"  \/><br  \/><img data-s=\"300,640\" data-type=\"png\" data-src=\"http:\/\/mmbiz.qpic.cn\/mmbiz_png\/IYECc9VMw8k8Cv7ic2ibVEicJmdKTfhbIceYvpDnU3BdNZbicDUWwkJan4YRJKiaMU0mudgsw7UbTfguxT9y69QibzBA\/0?wx_fmt=png\" data-ratio=\"2.857142857142857\" data-w=\"350\"  \/><br  \/><img data-s=\"300,640\" data-type=\"png\" data-src=\"http:\/\/mmbiz.qpic.cn\/mmbiz_png\/IYECc9VMw8k8Cv7ic2ibVEicJmdKTfhbIceJNJnP3WJwf1zkiaw9hqHV26rTPEMdcaWCyiaSWO89t7eP0jkicIbgtMEQ\/0?wx_fmt=png\" data-ratio=\"2.857142857142857\" data-w=\"350\"  \/><br  \/><img data-s=\"300,640\" data-type=\"png\" data-src=\"http:\/\/mmbiz.qpic.cn\/mmbiz_png\/IYECc9VMw8k8Cv7ic2ibVEicJmdKTfhbIceI4jA6kAVmHY4nHWcaJZprnJGlFnDia2f6tpzUUGSh1CnBVmrYJyzUeg\/0?wx_fmt=png\" data-ratio=\"2.857142857142857\" data-w=\"350\"  \/><br  \/><img data-s=\"300,640\" data-type=\"png\" data-src=\"http:\/\/mmbiz.qpic.cn\/mmbiz_png\/IYECc9VMw8k8Cv7ic2ibVEicJmdKTfhbIcelYH831KCviaqRSCLJHStnwkhvBSKy1se9SHbSYhvm7dS47LfNH8HDiaw\/0?wx_fmt=png\" data-ratio=\"2.857142857142857\" data-w=\"350\"  \/><br  \/><img data-s=\"300,640\" data-type=\"png\" data-src=\"http:\/\/mmbiz.qpic.cn\/mmbiz_png\/IYECc9VMw8k8Cv7ic2ibVEicJmdKTfhbIcerCcRUto5AXOe0UTbp2SpFs0ZvxiaxAXCbgLsV9z9WqqBLoygM8fKKmg\/0?wx_fmt=png\" data-ratio=\"2.857142857142857\" data-w=\"350\"  \/><br  \/><img data-s=\"300,640\" data-type=\"png\" data-src=\"http:\/\/mmbiz.qpic.cn\/mmbiz_png\/IYECc9VMw8k8Cv7ic2ibVEicJmdKTfhbIceQusx2ZXN2IiaMcNOibDn6QnibOrkBtLoZogZ9iaehOvIF26dPZjAhpiczcQ\/0?wx_fmt=png\" data-ratio=\"2.857142857142857\" data-w=\"350\"  \/><br  \/><\/p><p><img data-s=\"300,640\" data-type=\"png\" data-src=\"http:\/\/mmbiz.qpic.cn\/mmbiz_png\/IYECc9VMw8k8Cv7ic2ibVEicJmdKTfhbIceYXL1svlI7VJom9qUAMuzzEZuHxeY1RDaUwLczrzshqofTT9WHMicNVg\/0?wx_fmt=png\" style=\"\" data-ratio=\"2.857142857142857\" data-w=\"350\"  \/><\/p><p><img data-s=\"300,640\" data-type=\"png\" data-src=\"http:\/\/mmbiz.qpic.cn\/mmbiz_png\/IYECc9VMw8k8Cv7ic2ibVEicJmdKTfhbIceuOLmj720WibKlpgw514V5HCOeib7SCN5HC5PzCd0Rdk3GY1RRCWbicXhQ\/0?wx_fmt=png\" style=\"\" data-ratio=\"2.857142857142857\" data-w=\"350\"  \/><\/p><p><img data-s=\"300,640\" data-type=\"png\" data-src=\"http:\/\/mmbiz.qpic.cn\/mmbiz_png\/IYECc9VMw8k8Cv7ic2ibVEicJmdKTfhbIceTeuVwoveZwKrcbO1ojiavwFtfsl4TOIRrDZkxiaL8eGD7QsQgiaYu7IUQ\/0?wx_fmt=png\" style=\"\" data-ratio=\"1.1457142857142857\" data-w=\"350\"  \/><\/p><p style=\"text-align: center;\">乐在家，欢乐每一家<\/p><p><br  \/><\/p>","content_source_url":"","thumb_media_id":"iOCNARdq5rt1Dl4amaZItG07j2wwm0Pq-Rd-ZtEeKzc","show_cover_pic":0,"url":"http:\/\/mp.weixin.qq.com\/s?__biz=MzI0MzQ5MDc2OQ==&mid=100000020&idx=2&sn=948748e94131fc9d31dc489c6ed4d567&chksm=696d0baa5e1a82bcd865cb7ce402b7c9ee62687480a3d909ceab4b200ff7e4cd33d60480c699#rd","thumb_url":"http:\/\/mmbiz.qpic.cn\/mmbiz_png\/IYECc9VMw8k8Cv7ic2ibVEicJmdKTfhbIceo9KoHnW9U2TcaMKjibiclZvKQqQCbcLxR26LUwlt5eRJslTFkItEBBcg\/0?wx_fmt=png","need_open_comment":1,"only_fans_can_comment":0}],"create_time":1482805244,"update_time":1482805693},"update_time":1482805693},{"media_id":"iOCNARdq5rt1Dl4amaZItG7KMEiHns6aWbhEsIJmy38","content":{"news_item":[{"title":"关于博西尼科技","author":"博西尼人车智慧","digest":"博西尼科技是领先的出入口控制解决方案供应商。公司通过覆盖全国多个省市和地区的销售网络为客户提供创新技术与产品","content":"<p style=\" margin: 0px; padding: 0px; max-width: 100%; box-sizing: border-box ! important; word-wrap: break-word ! important; clear: both; min-height: 1em; white-space: pre-wrap; color: rgb(62, 62, 62) ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; \">博西尼电子是领先的出入口控制解决方案供应商。公司通过覆盖全国多个省市和地区的销售网络为客户提供创新技术与产品解决方案，让用户享有智能车辆进出管理、超声波泊车诱导、视频反向寻车、门禁控制、通道控制、电梯控制、访客管理、考勤消费管理等全方位的出入口控制系统产品及解决方案。<\/p><p style=\" margin: 0px; padding: 0px; max-width: 100%; box-sizing: border-box ! important; word-wrap: break-word ! important; clear: both; min-height: 1em; white-space: pre-wrap; color: rgb(62, 62, 62) ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; \"><br style=\"margin: 0px; padding: 0px; max-width: 100%; box-sizing: border-box ! important; word-wrap: break-word ! important;\"  \/><\/p><p style=\" margin: 0px; padding: 0px; max-width: 100%; box-sizing: border-box ! important; word-wrap: break-word ! important; clear: both; min-height: 1em; white-space: pre-wrap; color: rgb(62, 62, 62) ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; \"><span style=\"color: rgb(62, 62, 62); white-space: pre-wrap;\">博西尼公司<\/span>拥有出入口控制行业领域最完整的、点到点的产品线和整体解决方案，掌握全系列的RFID技术、iBeacon蓝牙4.0技术、视频流人脸识别技术、WeChat人车智慧平台技术、车牌识别技术、超声波探测技术、车辆视频定位技术，致力于创新技术在智能建筑的产业化、行业化、专业化应用，通过专业的前期方案设计与无微不至的售后服务，灵活满足不同地产开发商用户、政府及企业客户的差异化需求以及快速创新的追求。目前，<span style=\"color: rgb(62, 62, 62); white-space: pre-wrap;\">博西尼公司<\/span>已全面服务于国内主流地产开发商及政府、企业客户，智能视频反向寻车及车位引导技术、访客出入口控制物联技术、速通门通道管理技术等，位居行业领先地位，并成功应用于多个超大型商业综合体项目。<\/p><p style=\" margin: 0px; padding: 0px; max-width: 100%; box-sizing: border-box ! important; word-wrap: break-word ! important; clear: both; min-height: 1em; white-space: pre-wrap; color: rgb(62, 62, 62) ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; \"><br style=\"margin: 0px; padding: 0px; max-width: 100%; box-sizing: border-box ! important; word-wrap: break-word ! important;\"  \/><\/p><p style=\" margin: 0px; padding: 0px; max-width: 100%; box-sizing: border-box ! important; word-wrap: break-word ! important; clear: both; min-height: 1em; white-space: pre-wrap; color: rgb(62, 62, 62) ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; \">博西尼公司坚持以持续技术创新为客户不断创造价值。公司在深圳、上海、北京、成都、广州、杭州、天津、西安、南京、武汉、合肥等地共设有28个销售网络及售后服务机构，近20名研发人员专注于行业技术创新。2015年度博西尼车位引导系统及通道系统销售量位居行业前列。公司依托分布全国的28个分支机构，凭借不断增强的创新能力、突出的灵活定制能力、日趋完善的服务能力赢得全国合作伙伴的信任与合作。<\/p><p style=\" margin: 0px; padding: 0px; max-width: 100%; box-sizing: border-box ! important; word-wrap: break-word ! important; clear: both; min-height: 1em; white-space: pre-wrap; color: rgb(62, 62, 62) ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; \"><br style=\"margin: 0px; padding: 0px; max-width: 100%; box-sizing: border-box ! important; word-wrap: break-word ! important;\"  \/><\/p><p style=\" margin: 0px; padding: 0px; max-width: 100%; box-sizing: border-box ! important; word-wrap: break-word ! important; clear: both; min-height: 1em; white-space: pre-wrap; color: rgb(62, 62, 62) ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; \"><span style=\"color: rgb(62, 62, 62); white-space: pre-wrap;\">博西尼公司<\/span>一直坚持为用户提供安全可靠、稳定、技术领先的产品与解决方案，提供及时、便捷、可持续化服务，以此获得用户的肯定与认可，实现用户、企业及相关者的共同发展。多年来博西尼产品已经在全国的中高端住宅、商业综合体、酒店、写字楼、大型会展中心、交通枢纽、政府办公楼、企业办公楼、医院等项目成功应用，积累了良好口碑与品牌价值。<\/p><p style=\" margin: 0px; padding: 0px; max-width: 100%; box-sizing: border-box ! important; word-wrap: break-word ! important; clear: both; min-height: 1em; white-space: pre-wrap; color: rgb(62, 62, 62) ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; \"><br style=\"margin: 0px; padding: 0px; max-width: 100%; box-sizing: border-box ! important; word-wrap: break-word ! important;\"  \/><\/p><p style=\" margin: 0px; padding: 0px; max-width: 100%; box-sizing: border-box ! important; word-wrap: break-word ! important; clear: both; min-height: 1em; white-space: pre-wrap; color: rgb(62, 62, 62) ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; \">未来，<span style=\"color: rgb(62, 62, 62); white-space: pre-wrap;\">博西尼公司<\/span>将继续致力于引领出入口控制产业的发展，应对出入口控制领域更趋严格的技术挑战与多样的应用需求。<\/p><p style=\" margin: 0px; padding: 0px; max-width: 100%; box-sizing: border-box ! important; word-wrap: break-word ! important; clear: both; min-height: 1em; white-space: pre-wrap; color: rgb(62, 62, 62) ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; ; \"><br  \/><\/p><p style=\"margin: 0px; padding: 0px; max-width: 100%; min-height: 1em; white-space: pre-wrap; color: rgb(62, 62, 62); box-sizing: border-box !important; word-wrap: break-word !important;\"><br  \/><\/p>","content_source_url":"","thumb_media_id":"iOCNARdq5rt1Dl4amaZItLEFbaT8hKys41iztjHmagc","show_cover_pic":1,"url":"http:\/\/mp.weixin.qq.com\/s?__biz=MzI0MzQ5MDc2OQ==&mid=100000008&idx=1&sn=bc852b7d08466787fa77d58047f36854#rd","thumb_url":"http:\/\/mmbiz.qpic.cn\/mmbiz_png\/IYECc9VMw8mXqmhsz6iaH4jnV8lao1y7tFbzBTGualFIFZ0EBWm0iarGg4UE1CPRofCTuOmiasLD2T5mA1KAAIQDg\/0?wx_fmt=png","need_open_comment":1,"only_fans_can_comment":0}],"create_time":1471573453,"update_time":1471860415},"update_time":1471860415},{"media_id":"iOCNARdq5rt1Dl4amaZItNvsUsJyziL4Xcb2Sgbc09E","content":{"news_item":[{"title":"Bosiny人车智慧","author":"博西尼人车智慧","digest":"Bosiny人车智慧平台是博西尼科技领先的出入口控制整体解决方案平台，是博西尼科技基于多年工程实践，自主创新","content":"<p style=\"max-width: 100%; min-height: 1em; color: rgb(62, 62, 62); line-height: 25.6px; box-sizing: border-box ! important; word-wrap: break-word ! important; background-color: rgb(255, 255, 255);\">Bosiny人车智慧平台是博西尼科技领先的出入口控制整体解决方案平台，是博西尼科技基于多年工程实践，自主创新的领先技术结晶。<\/p><p style=\"max-width: 100%; min-height: 1em; color: rgb(62, 62, 62); line-height: 25.6px; box-sizing: border-box ! important; word-wrap: break-word ! important; background-color: rgb(255, 255, 255);\"><br style=\"max-width: 100%; box-sizing: border-box ! important; word-wrap: break-word ! important;\"  \/><\/p><p style=\"max-width: 100%; min-height: 1em; color: rgb(62, 62, 62); line-height: 25.6px; box-sizing: border-box ! important; word-wrap: break-word ! important; background-color: rgb(255, 255, 255);\">Bosiny人车智慧平台整合全系列的RFID技术、iBeacon蓝牙4.0技术、访客管理、智能停车、车牌识别技术、超声波探测技术、车辆视频定位技术、反向寻车技术、门禁及通道管理等，借助微信公众号平台入口，实现人员及车辆出入口控制的全面智慧管理。<\/p><p style=\"max-width: 100%; min-height: 1em; color: rgb(62, 62, 62); line-height: 25.6px; box-sizing: border-box ! important; word-wrap: break-word ! important; background-color: rgb(255, 255, 255);\"><br style=\"max-width: 100%; box-sizing: border-box ! important; word-wrap: break-word ! important;\"  \/><\/p><p style=\"max-width: 100%; min-height: 1em; color: rgb(62, 62, 62); line-height: 25.6px; box-sizing: border-box ! important; word-wrap: break-word ! important; background-color: rgb(255, 255, 255);\">Bosiny人车智慧平台已完全具备访客预约及审核（Visitor+），智能停车预约及便捷支付（Car Parking+），停车引导及反向寻车（Car Locating+）三大核心应用，真正实现了博西尼科技「Safer • Smarter• Simpler」的品牌理念，使用户通过微信公众号即可通行人车智慧的世界，享受「更安全•更智能•更简便」的新生活。<\/p><p style=\"max-width: 100%; min-height: 1em; color: rgb(62, 62, 62); line-height: 25.6px; box-sizing: border-box ! important; word-wrap: break-word ! important; background-color: rgb(255, 255, 255);\"><br style=\"max-width: 100%; box-sizing: border-box ! important; word-wrap: break-word ! important;\"  \/><\/p><p style=\"max-width: 100%; min-height: 1em; color: rgb(62, 62, 62); line-height: 25.6px; box-sizing: border-box ! important; word-wrap: break-word ! important; background-color: rgb(255, 255, 255);\">“博西尼人车智慧”是Bosiny人车智慧平台微信公众体验账号<\/p><p style=\"max-width: 100%; min-height: 1em; color: rgb(62, 62, 62); line-height: 25.6px; box-sizing: border-box ! important; word-wrap: break-word ! important; background-color: rgb(255, 255, 255);\">体验步骤如下：<\/p><ol class=\" list-paddingleft-2\" style=\"max-width: 100%; color: rgb(62, 62, 62); line-height: 25.6px; white-space: normal; box-sizing: border-box ! important; word-wrap: break-word ! important; background-color: rgb(255, 255, 255);\"><li><p style=\"max-width: 100%; min-height: 1em; box-sizing: border-box ! important; word-wrap: break-word ! important;\">关注博西尼科技人车智慧；<\/p><\/li><li><p style=\"max-width: 100%; min-height: 1em; box-sizing: border-box ! important; word-wrap: break-word ! important;\">体验车智慧：包括剩余车位查询、停车状况查询、临时车缴费、停车锁定等功能；<\/p><\/li><li><p style=\"max-width: 100%; min-height: 1em; box-sizing: border-box ! important; word-wrap: break-word ! important;\">体验人智慧：包括访<span style=\"max-width: 100%; color: rgb(255, 255, 255); box-sizing: border-box ! important; word-wrap: break-word ! important; background-color: rgb(79, 129, 189);\"><span style=\"max-width: 100%; box-sizing: border-box ! important; word-wrap: break-word ! important;\"><span style=\"max-width: 100%; color: rgb(0, 0, 0); box-sizing: border-box ! important; word-wrap: break-word ! important; background-color: rgb(255, 255, 255);\">客预约、预约状态查询、通行二维码等功能。<\/span><\/span><\/span><\/p><p style=\"max-width: 100%; min-height: 1em; box-sizing: border-box ! important; word-wrap: break-word ! important;\"><span style=\"max-width: 100%; color: rgb(255, 255, 255); box-sizing: border-box ! important; word-wrap: break-word ! important; background-color: rgb(79, 129, 189);\"><span style=\"max-width: 100%; box-sizing: border-box ! important; word-wrap: break-word ! important;\"><span style=\"max-width: 100%; color: rgb(0, 0, 0); box-sizing: border-box ! important; word-wrap: break-word ! important; background-color: rgb(255, 255, 255);\"><\/span><\/span><\/span><\/p><\/li><br style=\"max-width: 100%; box-sizing: border-box ! important; word-wrap: break-word ! important;\"  \/><\/ol><p style=\"max-width: 100%; min-height: 1em; color: rgb(62, 62, 62); line-height: 25.6px; box-sizing: border-box ! important; word-wrap: break-word ! important; background-color: rgb(255, 255, 255);\"><br style=\"max-width: 100%; box-sizing: border-box ! important; word-wrap: break-word ! important;\"  \/><\/p><p style=\"max-width: 100%; min-height: 1em; color: rgb(62, 62, 62); line-height: 25.6px; box-sizing: border-box ! important; word-wrap: break-word ! important; background-color: rgb(255, 255, 255);\"><span style=\"max-width: 100%; box-sizing: border-box ! important; word-wrap: break-word ! important;\">“博西尼人车智慧”还支撑了更多长期停车、访客审核等内部功能，欢迎与博西尼销售及技术服务团队联系，获得内测注册权限。<\/span><\/p><p style=\"max-width: 100%; min-height: 1em; color: rgb(62, 62, 62); line-height: 25.6px; box-sizing: border-box ! important; word-wrap: break-word ! important; background-color: rgb(255, 255, 255);\"><span style=\"max-width: 100%; box-sizing: border-box ! important; word-wrap: break-word ! important;\"><img data-s=\"300,640\" data-type=\"png\" data-ratio=\"0.95625\" data-w=\"640\" data-src=\"http:\/\/mmbiz.qpic.cn\/mmbiz\/kuYNDY8iaMXAyooTvPZ7sQvvY0IQ7srFZSB0qrcm2saIhFymM5ibtdWh6ianYuUeLVp8swqFIXZicuAia9TMQnUDLWw\/640?wx_fmt=png\" style=\"box-sizing: border-box ! important; word-wrap: break-word ! important; width: auto ! important; visibility: visible ! important;\"  \/><\/span><\/p>","content_source_url":"http:\/\/mp.weixin.qq.com\/s?__biz=MzA5ODMzMzA0Ng==&mid=400141952&idx=1&sn=f03c0857c566562c84f26a21481419da&scene=20#wechat_redirect","thumb_media_id":"iOCNARdq5rt1Dl4amaZItOOCrYIHteNPpMnzYqb0UD0","show_cover_pic":1,"url":"http:\/\/mp.weixin.qq.com\/s?__biz=MzI0MzQ5MDc2OQ==&mid=100000004&idx=1&sn=08c82ad4140976fd403fe60659aad518#rd","thumb_url":"http:\/\/mmbiz.qpic.cn\/mmbiz_jpg\/IYECc9VMw8mXqmhsz6iaH4jnV8lao1y7tH1ibvfZ2q4tiaPL5K6TXnOwtTZUw9CrAFl3MPUgU3SCq71RNEiae9qMrg\/0?wx_fmt=jpeg","need_open_comment":1,"only_fans_can_comment":0}],"create_time":1471426032,"update_time":1471574704},"update_time":1471574704}],"total_count":3,"item_count":3}';
//        $r = json_decode($r);
//        $r->page = $this->page($r->total_count,$offset,$count);
//        $this->ajaxReturn($r, '成功', 0);
    }
    private function page($total,$offset,$count){
        $page = json_decode('{"total":'.$total.',"totalPage":'.floor(($total + $count)/$count).',"currentPage":'.($offset/$count + 1).',"count":'.$count.',"offset":'.$offset.'}');
        return $page;
    }
    /**
     * 新增菜单
     */
    public function add_menu()
    {
        $pid = $_POST["pid"];
        $type = $_POST["type"];
        $name = $_POST["name"];
        $key = $_POST["key"];
        $url = $_POST["url"];
        $media_id = $_POST["media_id"];
        $level = $pid == null ? 1:2;
        $id = time();
        $postStr = '{"level":'.$level.',"type":"' . $type . '","name":"' . $name . '","key":"' . $key . '","url":"' . $url . '","media_id":"' . $media_id . '","sub_button":[],"state":"0","button":"button","create_time":"' . date("Y-m-d H:i:s", $id) . '"}';
        $postStr = json_decode($postStr);
        $postStr->id = $id;
        $string = file_get_contents("db.json");
        $dbObj = json_decode($string);
        $customerMenu = [];
        foreach ($dbObj as $key => $value) {
            if ($key == "customerMenu") {
                $customerMenu = $value;
                break;
            }
        }
        if($postStr->level == 1){
            if(count($customerMenu) >= 3){
                $this->ajaxReturn(null, '最多设置三个一级菜单', 1);
                return;
            }
        }
        //array_push($customerMenu, $postStr);
        $r =  $this->add_menu_2_db($customerMenu,$pid,$postStr);
        if(strlen($r) > 0){
            $this->ajaxReturn(null, $r, 1);
            return;
        }

        $dbObj->customerMenu = $customerMenu;
        $fp = fopen("db.json", "w");
        fwrite($fp, json_encode($dbObj));
        fclose($fp);
        $this->ajaxReturn($customerMenu, '成功', 0);
    }

    private function add_menu_2_db(&$menuArray,$pid,$c){
        $r = "";
        foreach($menuArray as $k => $v){
            if(strcmp($v->id, $pid) == 0){
                if(count($v->sub_button) >= 3){
                    $r = "每个一级菜单最多包含5个二级菜单";
                    break;
                }else
                    array_push($menuArray[$k]->sub_button,$c);
            }else{
                $this->add_menu_2_db($menuArray[$k]->sub_button,$pid,$c);
            }
        }
        return $r;
    }
}