# Tencent_QRLogin_SDK
自己抓的QQ包以及整合了网上一些已经封装好了的代码

QQ:
```php
<?php
class QQ extends Curl_Api
{
    //获取登录验证码
    public function QRcode()
    {
        $url='https://ssl.ptlogin2.qq.com/ptqrshow?appid=549000912&e=2&l=M&s=4&d=72&v=4&t=0.5409099'.time().'&daid=5';
        $arr=$this->get_curl_split($url);
        preg_match('/qrsig=(.*?);/',$arr['header'],$match);
        if($qrsig=$match[1])
            return array('code'=>200,'qrsig'=>$qrsig,'data'=>base64_encode($arr['body']));
        else
            return array('code'=>400,'msg'=>'二维码获取失败');
    }
    public function ListenQR($qrsig)
    {
        $qrsig = $qrsig[0];
        if(empty($qrsig))return array('code'=>-1,'msg'=>'qrsig不能为空');
        $url='https://ssl.ptlogin2.qq.com/ptqrlogin?u1=https%3A%2F%2Fqzs.qq.com%2Fqzone%2Fv5%2Floginsucc.html%3Fpara%3Dizone&ptqrtoken='.$this->getqrtoken($qrsig).'&login_sig=&ptredirect=0&h=1&t=1&g=1&from_ui=1&ptlang=2052&action=0-0-'.time().'0000&js_ver=10194&js_type=1&pt_uistyle=40&aid=549000912&daid=5&';
        $ret = $this->get_curl($url,0,$url,'qrsig='.$qrsig.'; ',1);
        if(preg_match("/ptuiCB\('(.*?)'\)/", $ret, $arr)){
            $r=explode("','",str_replace("', '","','",$arr[1]));
            if($r[0]==0){
                preg_match('/uin=(\d+)&/',$ret,$uin);
                $uin=$uin[1];
                preg_match('/skey=@(.{9});/',$ret,$skey);
                preg_match('/superkey=(.*?);/',$ret,$superkey);
                $data=$this->get_curl($r[2],0,0,0,1);
                if($data) {
                    preg_match("/p_skey=(.*?);/", $data, $matchs);
                    $pskey = $matchs[1];
                }
                if($pskey){
                    if(isset($_GET['findpwd'])){
                        $_SESSION['findpwd_qq']=$uin;
                    }
                    return array('code'=>200,'uin'=>$uin,'skey'=>'@'.$skey[1],'pskey'=>$pskey,'superkey'=>$superkey[1],'nick'=>$r[5]);
                }else{
                    return array('code'=>201,'msg'=>'登录成功，获取相关信息失败！'.$r[2]);
                }
            }elseif($r[0]==65){
                return array('code'=>400,'msg'=>'二维码已失效。');
            }elseif($r[0]==66){
                return array('code'=>202,'msg'=>'二维码未失效。');
            }elseif($r[0]==67){
                return array('code'=>302,'msg'=>'正在验证二维码。');
            }else{
                return array('code'=>401,'msg'=>$r[4]);
            }
        }else{
            return array('code'=>403,'msg'=>$ret);
        }
  
    }
    private function getqrtoken($qrsig){
        $len = strlen($qrsig);
        $hash = 0;
        for($i = 0; $i < $len; $i++){
            $hash += (($hash << 5) & 2147483647) + ord($qrsig[$i]) & 2147483647;
            $hash &= 2147483647;
        }
        return $hash & 2147483647;
    }
}
```

微信:
```php
<?php
class Wechat extends Curl_Api
{
    //获取验证码
    public function QRcode()
    {
        $url = "https://login.weixin.qq.com/jslogin?appid=wx782c26e4c19acffb&fun=new&lang=zh_CN";
        $uuid = $this->get_curl($url);
//        var_dump($uuid);
        $uuid = substr($uuid,strpos($uuid,'"')+1,-2);
        $url = "https://login.wx.qq.com/qrcode/{$uuid}?t=webwx";
        $qrcode = file_get_contents($url);
        $result = ['code'=>200,'uuid'=>$uuid,'qrcode'=>base64_encode($qrcode)];
        return $result;
    }
    public function ListenQR($uuid)
    {
        $paras['ctime'] = 1000;
        $paras['rtime'] = 1000;
        $paras['refer'] = 'https://wx2.qq.com/';
        $api = 'https://login.wx2.qq.com/cgi-bin/mmwebwx-bin/login?loginicon=true&uuid=' . $uuid[0] . '&tip=0';
        $body = $this->curl($api, $paras);
        preg_match('/(\d){3}/', $body, $code);
        preg_match('/redirect_uri="(.*?)"/', $body, $url);
        if ($code[0] == '200') {
            $body = $this->curl($url[1]);
            preg_match('/<wxuin>(\d*?)<\/wxuin>/', $body, $wxuin);
            $ret['code'] = 200;
            $ret['data']['uin'] = $wxuin[1];
            $ret['data']['type'] = 'wx';
            $ret['msg'] = '登录成功';
        } else {
            $ret['code'] = 408;
            $ret['msg'] = '请使用手机微信扫码登录';
        }
        return $ret;
    }
}
```
为了方便跳用，这里我又封装了一个类
动态传入QQ微信的类名字符串快速实例化
Tencent类:
```php
<?php
Class Tencent{
    protected $path = __DIR__ . '/';
     private $cl;
     /*
      * 动态传入QQ或WX字符串，自动转换对应的api类登录
      */
    public function __construct($type)
    {
        //注册自动加载函数
        spl_autoload_register([$this,'Psr4Autoload']);
        //引入curl
        $this->cl = new $type();
    }
    public function Psr4Autoload($class)
    {
    $class_file = $this->path .'/'. $class . '.php';
    if (file_exists($class_file))
    {
        include "$class_file";
    }else{
        die('类文件'.$class_file .'不存在');
    }
    }
    public function QRcode()
    {
        return call_user_func([$this->cl,__FUNCTION__]);
    }
    public function ListenQR(...$args)
    {
        return call_user_func([$this->cl,__FUNCTION__],$args);
    }
    public function __call($name, $arguments)
    {
       call_user_func_array([$this->cl,$name],(array)$arguments);
    }
}
```
以及最后一个curl类:
```php
<?php
class Curl_Api
{
    public $ua = "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.100 Safari/537.36";
      
     public function get_curl($url,$post=0,$referer=0,$cookie=0,$header=0,$ua=0,$nobaody=0){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $httpheader[] = "Accept: application/json";
        $httpheader[] = "Accept-Encoding: gzip,deflate,sdch";
        $httpheader[] = "Accept-Language: zh-CN,zh;q=0.8";
        $httpheader[] = "Connection: keep-alive";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
        if($post){
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        if($header){
            curl_setopt($ch, CURLOPT_HEADER, TRUE);
        }
        if($cookie){
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        }
        if($referer){
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        }
        if($ua){
            curl_setopt($ch, CURLOPT_USERAGENT,$ua);
        }else{
            curl_setopt($ch, CURLOPT_USERAGENT,$this->ua);
        }
        if($nobaody){
            curl_setopt($ch, CURLOPT_NOBODY,1);
  
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_ENCODING, "gzip");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        $ret = curl_exec($ch);
        curl_close($ch);
        return $ret;
    }
    function curl($url, $paras = array()) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $httpheader[] = "Accept:*/*";
        $httpheader[] = "Accept-Encoding:gzip,deflate,sdch";
        $httpheader[] = "Accept-Language:zh-CN,zh;q=0.8";
        $httpheader[] = "Connection:close";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
        if ($paras['ctime']) { // 连接超时
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $paras['ctime']);
        }
        if ($paras['rtime']) { // 读取超时
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, $paras['rtime']);
        }
        if ($paras['post']) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $paras['post']);
        }
        if ($paras['header']) {
            curl_setopt($ch, CURLOPT_HEADER, true);
        }
        if ($paras['cookie']) {
            curl_setopt($ch, CURLOPT_COOKIE, $paras['cookie']);
        }
        if ($paras['refer']) {
            if ($paras['refer'] == 1) {
                curl_setopt($ch, CURLOPT_REFERER, 'http://m.qzone.com/infocenter?g_f=');
            } else {
                curl_setopt($ch, CURLOPT_REFERER, $paras['refer']);
            }
        }
        if ($paras['ua']) {
            curl_setopt($ch, CURLOPT_USERAGENT, $paras['ua']);
        } else {
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36");
        }
        if ($paras['nobody']) {
            curl_setopt($ch, CURLOPT_NOBODY, 1);
        }
        curl_setopt($ch, CURLOPT_ENCODING, "gzip");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $ret = curl_exec($ch);
        curl_close($ch);
        return $ret;
    }
     public function get_curl_split($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $httpheader[] = "Accept: */*";
        $httpheader[] = "Accept-Encoding: gzip,deflate,sdch";
        $httpheader[] = "Accept-Language: zh-CN,zh;q=0.8";
        $httpheader[] = "Connection: keep-alive";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_USERAGENT,$this->ua);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_ENCODING, "gzip");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        $ret = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($ret, 0, $headerSize);
        $body = substr($ret, $headerSize);
        $ret=array();
        $ret['header']=$header;
        $ret['body']=$body;
        curl_close($ch);
        return $ret;
    }
}
```
# 大致调用的流程大致调用的流程
1. 保存四个类到文件里面
2. 引入文件
3. 单独写两个接口，一个生成qr码(base64),一个轮询二维码扫码状态
4. 用户扫码成功后、会返回一个QQ号火微信唯一id
5. 这里我只演示一个微信扫码登陆的例子


生成二维码并轮询检测二维码状态 login.php :
```php
<?php
//我这里只引入了一个文件的原因是因为Wechat和QQ类不用引入、只需要把Curl_Api请求类引入进来就好，但我Tencent类内已经引入了。所以这里我只需要引入一个文件就好
include "Lib/Tencent/Tencent.php";
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
```
ajax.php :
```php
<?php
include "Lib/Tencent/Tencent.php";
$wx = new Tencent("Wechat");
//直接获取到uuid后，监听就好了
$ret = $wx->ListenQR($_GET['uuid']);
//var_dump($ret);
echo json_encode($ret,true);exit;
```
这个例子是微信的，QQ同样的代码一样可以运行

数据库用户表多一个qq和wxuin字段、用于保存用户绑定的QQ和微信
上面那个仅仅只是个例子，可能写的不是很好。大佬勿喷
有什么疑问可在帖子下方发表一下
