<?php
/**
 * User: ZhuJun
 * Date: 2021/11/30
 * Time: 23:21
 * Email: mr.zhujun1314@gmail.com
 */

namespace IMongo;

class Base extends MongoModel
{
    public function __construct()
    {
        $config = new ConfigStruct('127.0.0.1','27017','admin','123456');
        parent::__construct($config);
    }

}