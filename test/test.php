<?php
/**
 * User: ZhuJun
 * Date: 2021/10/29
 * Time: 17:57
 * Email: mr.zhujun1314@gmail.com
 */

$model = new \IMongo\TestModel();

$model->deleteOne(['a'=>['$eq'=>'1']]);
$model->insertOne(['a'=>'1','b'=>'2']);
$model->insertMany([['a'=>'1','b'=>'2'],['a'=>'3','b'=>'4']]);

$data = $model->find(['a'=>['$eq','1']]);