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

    private function __construct($config) {
        if (!$config) {
            throw new \Exception('不能链接芒果数据库');
        }
        $this->mongo = (new Manager('mongodb://' . $config['userName'] . ':' . $config['password'] . '@' . $config['host'] . ':' . $config['port']));
    }

    public static function cli($config): Manager
    {
        if(!(self::$client instanceof self)){
            self::$client = new self($config);
        }
        return self::$client->mongo;
    }
}