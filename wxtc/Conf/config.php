<?php
$config1=require'./config.ini.php';
$config2=array(
	'TMPL_ACTION_ERROR'     =>  'Public/error',
	'TMPL_ACTION_SUCCESS'   =>  'Public/success',	
	'URL_MODEL'   =>  1,
);

$config=array_merge($config1,$config2);
return array_merge($config);
?>