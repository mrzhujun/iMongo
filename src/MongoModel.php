<?php
/**
 * User: ZhuJun
 * Date: 2021/5/14
 * Time: 11:04
 * Email: mr.zhujun1314@gmail.com
 */

namespace IMongo;


use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Command;
use MongoDB\Driver\Exception\Exception;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Query;
use MongoDB\Driver\WriteConcern;

abstract class MongoModel
{
    /**
     * 数据库配置信息
     * @var Manager
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
     * 初始化过的模型.
     * @var array
     */
    protected static $initialized = [];
    /**
     * 模型名称
     * @var string
     */
    protected $name;
    private $dbStr;
    private $_id;

    public function __construct($config){
        if (empty($this->name)) {
            // 当前模型名
            $name       = str_replace('\\', '/', static::class);
            $this->name = basename($name);
        }
        $this->connection = Connect::cli($config);
        $this->dbStr = $this->db . "." . self::parseName($this->name);
    }

    /**
     * 获取当前模型名称
     * @access public
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 获取最后一次更新的主键
     * @return mixed
     */
    public function getLastId() {
        return ((array)$this->_id)['oid'];
    }

    /**
     * 获取最后一次插入的数据
     * @return mixed
     */
    public function getLastData(): ?array
    {
        if (!$this->_id) {
            return null;
        }else{
            try {
                return $this->getByWhere(['_id' => ['$eq' => $this->_id]]);
            } catch (Exception $e) {
                return null;
            }
        }
    }


    /**
     * 多条数据写入
     * @param array $data 使用二维数组
     * @return int|null 正常返回数据处理成功条数
     */
    public function insertAll(array $data): ?int
    {
        $bulk = new BulkWrite();
        foreach ($data as $ve) {
            $bulk->insert($ve);
        }
        //返回数据处理结果
        return $this->connection->executeBulkWrite(
            $this->dbStr,
            $bulk,
            (new WriteConcern(WriteConcern::MAJORITY, 1000)
            )
        )->getInsertedCount();
    }

    /**
     * 单条数据写入
     * @param array $data 使用一维数组
     * @return int|null
     */
    public function insert(array $data): ?int
    {
        $bulk = new BulkWrite();
        $this->_id = $bulk->insert($data);
        //返回数据处理结果
        return $this->connection->executeBulkWrite(
            $this->dbStr,
            $bulk,
            (new WriteConcern(WriteConcern::MAJORITY, 1000)
            )
        )->getInsertedCount();
    }

    /**
     * 数据更新
     * @param array $where 数据更新的条件 指定更新的目标对象 mongodb默认如果条件不成立，新增加数据，相当于insert
     * @param array $data 需要更新的数据 指定用于更新匹配记录的对象
     * @param array $expand 扩展选项 指定较为复杂的更新方式
     * @return integer|bool|object 正常成功下返回更新数据影响的总条数
     * ↓↓↓↓ ps:一些常用的更新扩展设置方式 ↓↓
     * upsert：默认为false, 若设置为true，当没有匹配文档的时候会创建一个新的文档。
     * multiple：默认为false,若设置为true，匹配文档将全部被更新。
     * ↑↑↑↑↑
     * 注意：若不使用任何修改操作符[expand为空]，则匹配文档将直接被整个替换
     */
    public function update(array $where,array $data, $expand = array('multiple' => false, 'upsert' => false))
    {
        $bulk = new BulkWrite();
        $bulk->update($where, $data, $expand);
        //返回处理成功的结果
        return $this->connection->executeBulkWrite(
            $this->dbStr,
            $bulk,
            (new WriteConcern(WriteConcern::MAJORITY, 1000)
            )
        )->getModifiedCount();
    }

    /**
     * 根据条件获取数据
     * @param array $where 条件数组 例如:['x' => ['$gt' => 1]];
     * @param array $option 扩展条件
     * @return array 查询结果数组
     * @throws Exception
     */
    public function getByWhere(array $where, $option = array()): array
    {
        $query = new Query($where, $option);
        $cursor = $this->connection->executeQuery($this->dbStr, $query);
        $data = [];
        foreach ($cursor as $doc) {
            $data[] = (array)$doc;
        }
        return $data;
    }

    /**
     * 删除
     * @param array $where 删除条件数组 例如:['x' => ['$gt' => 1]];
     * @return int|null|bool|object 删除成功的影响条数
     */
    public function deleteByWhere(array $where)
    {
        if (empty($where)) {
            return false;
        }
        $bulk = new BulkWrite();
        $bulk->delete($where);//按条件删除
        return $this->connection->executeBulkWrite(
            $this->dbStr,
            $bulk,
            (new WriteConcern(WriteConcern::MAJORITY, 1000)
            )
        )->getDeletedCount();
    }

    /**
     * 数量
     * @param array $where 条件数组
     * @return int 总数
     * @throws Exception
     */
    public function getCount($where = array()): int
    {
        $command = new Command(['count' => $this->table, 'query' => $where]);
        $result = $this->connection->executeCommand($this->db, $command);
        $res = $result->toArray();
        $cnt = 0;
        if ($res) {
            $cnt = $res[0]->n;
        }
        return $cnt;
    }


    /**
     * 按条件获取分组条数
     * @param array $where  注：group by count/sum
     * @param array $group
     * @param int $find_action
     * @param null $page
     * @param null $limit
     * @return array 总数
     * @throws Exception
     */
    public function getGroupCount($where = array(),$group = array(),$find_action = 1,$page=null,$limit=null): array
    {
        $where =[
            'aggregate' =>$this->table,
            'pipeline' => [
                [
                    '$match'=>$where,
                ],
                [
                    '$group' => ['_id' => $group['group_key'],$group['count_key'] => ['$sum' =>$find_action],'count'=>array('$sum'=>1)],
                ],
            ],
            'cursor' => ['batchSize' => 0],
        ];
        if ($find_action !== 1){
            $where['pipeline'][] = array(
                '$sort' =>array(
                    $group['count_key'] => -1,
                    'create_time' => -1,
                ),
            );
        }
        if (!empty($page) && !empty($limit)){
            //设置分页数据
            $skip= ($page - 1) * $limit;
            $where['pipeline'][] = array('$skip' =>$skip);
            $where['pipeline'][] = array('$limit' =>$limit);
        }
        $command = new Command($where);
        $result = $this->connection->executeCommand($this->db, $command);
        $res = $result->toArray();
        $data = [];
        if (!empty($res)){
            foreach ($res as $key => $document) {
                $data[] =(array)$document;
            }
        }else{
            $data = array();
        }
        return $data;
    }


    /**
     * @param array $where 条件数组
     * @param array $option 扩展条件
     * @param int $page 页码
     * @param int $limit 页数
     * @return false|string|array 返回值
     * @throws Exception
     */
    public function page($where=[],$option=[],$page=1,$limit=10)
    {
        //设置分页数据
        $option['skip'] = ($page - 1) * $limit;
        $option['limit'] = $limit;
        //求总
        $count = $this->getCount($where);
        //最大页及分页page限制
        $maxPage = ceil($count / $limit);
        if ($page > $maxPage) {
            $page = $maxPage;//不能大于最大页码|预防漏查
        } elseif ($page < 1) {
            $page = 1;
        }
        //返回数据
        $data['data'] = $this->getByWhere($where,$option);//数据查询
        $data['count'] = $count;
        $data['max_page'] = $maxPage;
        $data['page'] = $page;
        return $data;
    }


    /**
     * 字符串命名风格转换
     * type 0 将Java风格转换为C的风格 1 将C风格转换为Java的风格
     * @access public
     * @param string $name 字符串
     * @param integer $type 转换类型
     * @param bool $ucfirst 首字母是否大写（驼峰规则）
     * @return string
     */
    public static function parseName(string $name, $type = 0, $ucfirst = true): string
    {
        if ($type) {
            $name = preg_replace_callback('/_([a-zA-Z])/', function ($match) {
                return strtoupper($match[1]);
            }, $name);
            return $ucfirst ? ucfirst($name) : lcfirst($name);
        }

        return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
    }
}