<?php
/**
 * User: ZhuJun
 * Date: 2021/5/14
 * Time: 11:04
 * Email: mr.zhujun1314@gmail.com
 */

namespace IMongo;

use MongoDB\Collection;

abstract class MongoModel extends Collection
{
    /**
     * 数据库配置信息
     */
    private $connection;

    /**
     * 数据库名称默认admin
     * @var string
     */
    protected $db = 'admin';

    /**
     * 数据表名称默认按模型名称自动识别
     * @var string
     */
    protected $table;

    /**
     * 模型名称
     * @var string
     */
    protected $name;

    public function __construct(ConfigStruct $config){
        if (empty($this->name)) {
            // 当前模型名
            $name       = str_replace('\\', '/', static::class);
            $this->name = basename($name);
        }
        parent::__construct(Connect::cli($config),$this->db,$this->table);
    }


    /**
     * @param array $where 条件数组
     * @param array $option 扩展条件
     * @param int $page 页码
     * @param int $limit 页数
     * @return array 返回值
     */
    public function page(array $where=[], array $option=[], int $page=1, int $limit=10): array
    {
        //设置分页数据
        $option['skip'] = ($page - 1) * $limit;
        $option['limit'] = $limit;
        //求总
        $count = $this->countDocuments($where,$option);
        //最大页及分页page限制
        $maxPage = ceil($count / $limit);
        if ($page > $maxPage) {
            $page = $maxPage;//不能大于最大页码|预防漏查
        } elseif ($page < 1) {
            $page = 1;
        }
        //返回数据
        $data['data'] = $this->find($where,$option);//数据查询
        $data['count'] = $count;
        $data['max_page'] = $maxPage;
        $data['page'] = $page;
        return $data;
    }
}