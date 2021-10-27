<?php
/**
 * User: ZhuJun
 * Date: 2021/5/14
 * Time: 14:15
 * Email: mr.zhujun1314@gmail.com
 */

namespace IMongo;


use MongoDB\Driver\Manager;

class Connect
{
    private static $client;

    private $mongo;

    private function __construct(ConfigStruct $config) {
        $this->mongo = (new Manager($this->getUrl($config)));
    }

    public static function cli($config): Manager
    {
        if(!(self::$client instanceof self)){
            self::$client = new self($config);
        }
        return self::$client->mongo;
    }
    
    private function getUrl(ConfigStruct $config):string {
        $url = 'mongodb://';
        if ($config->userName) {
            $url .= $config->userName . ':' . $config->password.'@';
        }
        $url .= $config->host . ':' . $config->port;
        return $url;
    }
}