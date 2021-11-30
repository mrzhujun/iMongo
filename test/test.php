<?php
/**
 * User: ZhuJun
 * Date: 2021/10/29
 * Time: 17:57
 * Email: mr.zhujun1314@gmail.com
 */

$model = new \IMongo\TestModel();

$model->deleteByWhere(['a'=>['$eq'=>'1']]);
$model->insert(['a'=>'1','b'=>'2']);
$model->insertAll([['a'=>'1','b'=>'2'],['a'=>'3','b'=>'4']]);

$data = $model->getByWhere(['a'=>['$eq','1']]);