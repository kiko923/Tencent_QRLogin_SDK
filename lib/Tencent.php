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
