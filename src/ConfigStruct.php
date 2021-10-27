<?php
/**
 * User: ZhuJun
 * Date: 2021/10/27
 * Time: 17:25
 * Email: mr.zhujun1314@gmail.com
 */

namespace IMongo;


class ConfigStruct
{
    public $host;
    public $port;
    public $userName;
    public $password;
    
    public function __construct($host,$port,$userName='',$password='')
    {
        $this->host = $host;
        $this->port = $port;
        $this->userName = $userName;
        $this->password = $password;
    }
}