<?php
include "lib/Tencent.php";
$wx = new Tencent("Wechat");
//直接获取到uuid后，监听就好了
$ret = $wx->ListenQR($_GET['uuid']);
//var_dump($ret);
echo json_encode($ret,true);exit;
