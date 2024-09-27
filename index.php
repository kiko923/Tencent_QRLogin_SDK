<?php
//我这里只引入了一个文件的原因是因为Wechat和QQ类不用引入、只需要把Curl_Api请求类引入进来就好，但我Tencent类内已经引入了。所以这里我只需要引入一个文件就好
include "lib/Tencent.php";
$wx = new Tencent("Wechat");
$ret = $wx->QRcode();
?>
<!--直接生成QR码、记得把uuid给带上-->
<img id="wx" src="data:text/html;base64,<?=$ret['qrcode']?>" uuid="<?=$ret['uuid']?>">
  
<script src="https://cdn.bootcss.com/jquery/3.4.1/jquery.js"></script>
<script>
    // setTimeout(function () {
    //     var uuid = document.getElementById('wx').getAttribute("uuid");
    //     var url ="/ajax.php?uuid="+uuid;
    //     console.log(url);
    // },1000);
    //每秒去查询一次二维码状态
    $(document).ready(function () {
        setInterval(function () {
            var uuid = document.getElementById('wx').getAttribute("uuid");
            var url ="/ajax.php?uuid="+uuid;
            $.ajax({type:"GET",url:url,success:function (data) {
                    if (data.code == 200)
                    {
                        alert("登陆成功，uin为:"+data.uid);
                    }
                }});
        },1000);
    });
  
</script>
