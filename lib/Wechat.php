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
