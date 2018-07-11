<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>微信访客后台 - 登录</title>
    <link rel="shortcut icon" href="favicon.ico">
    <link href="../Public/admin/css/bootstrap.min.css?v=3.3.6" rel="stylesheet">
    <link href="../Public/admin/css/font-awesome.css?v=4.4.0" rel="stylesheet">

    <link href="../Public/admin/css/animate.css" rel="stylesheet">
    <link href="../Public/admin/css/style.css?v=4.1.0" rel="stylesheet">
    <!--[if lt IE 9]>
    <meta http-equiv="refresh" content="0;ie.html" />
    <![endif]-->
    <script>if(window.top !== window.self){ window.top.location = window.location;}</script>
</head>

<body class="gray-bg">

    <div class="middle-box text-center loginscreen  animated fadeInDown">
        <div>
            <div>

                <h1 class="logo-name">&nbsp;&nbsp;</h1>

            </div>
            <h3>欢迎使用 bosiny微信访客</h3>

            <form class="m-t" role="form" method="post" action="__URL__/loginForm/">
                <div class="form-group">
                    <input type="text" class="form-control" placeholder="用户名" required="" name="userName">
                </div>
                <div class="form-group">
                    <input type="password" class="form-control" placeholder="密码" required="" name="password">
                </div>
                <button type="submit" class="btn btn-primary block full-width m-b">登 录</button>


                <p class="text-muted text-center"> <a href="login.html#"><small>忘记密码了？</small></a>
                </p>

            </form>
        </div>
    </div>

    <!-- 全局js -->
    <script src="../Public/admin/js/jquery.min.js?v=2.1.4"></script>
    <script src="../Public/admin/js/bootstrap.min.js?v=3.3.6"></script>

</body>

</html>