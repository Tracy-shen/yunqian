<?php
/**
 * 将对数据库的操作封装成类
 * 对某张表的增删改查
 */

class Model extends PDO
{
	protected $tabName;		//用于存储要操作的表名
	protected $sql = '';	//存最后执行的sql语句
	protected $allFields = [];	//存所有的字段名
	protected $limit = '';	//存limit条件
	protected $order = '';	//存order排序条件
	protected $field = '*';	//存field字段
	protected $where = '';	//存where条件　

	//构造方法
	public function __construct($tabName)
	{
		parent::__construct('mysql:host='.HOST.';dbname='.DB.';charset=utf8', USER, PWD);

		//存储表名
		$this->tabName = FIX.$tabName;

		//获取当前表的所有字段
		$this->getFields();
	}

	/**
	 * 判断数据表是否存在
	 */
	protected function getFields()
	{
		$sql = "desc {$this->tabName}";
		$stmt = $this->query($sql);
		if ($stmt) {
			$res = $stmt->fetchAll(2);
			// var_dump($res);
			$tmp = [];
			foreach ($res as $v) {
				$tmp[] = $v['Field'];
			}
			// var_dump($tmp);
			$this->allFields = $tmp;
		} else {
			die('表名错误');
		}
	}

	/**
	 * 查询所有数据
	 * @return array 查到的数据数组
	 */
	public function select()
	{
		$sql = "select {$this->field} from {$this->tabName} {$this->where} {$this->order} {$this->limit}";
		$this->sql = $sql;
		$stmt = $this->query($sql);
		if ($stmt) {
			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		}

		return [];
	}

	/**
	 * 根据id查询1条数据
	 * @return array 查到的一维数组
	 */
	public function find($id)
	{
		$sql = "select * from {$this->tabName} where id={$id}";
		$this->sql = $sql;
		$stmt = $this->query($sql);
		if ($stmt) {
			return $stmt->fetch(PDO::FETCH_ASSOC);
		}

		return [];
	}

	/**
	 * 统计总条数
	 * @return int 统计出来的数量
	 */
	public function count()
	{
		$sql = "select count(*) from {$this->tabName} {$this->where} limit 1";
		$stmt = $this->query($sql);
		if ($stmt) {
			return (int)$stmt->fetch()[0];
		}

		return 0;
	}

	/**
	 * 添加数据
	 * @param array $data 要添加的数据数组
	 * @return int 返回受影响行数
	 */
	public function add($data)
	{
		//过滤非法字段
		foreach ($data as $k=>$v) {
			if (!in_array($k, $this->allFields)) {
				// '干掉'
				unset($data[$k]);
			}
		}
		//判断是否全特么是非法字段
		if (empty($data)) {
			die('滚，哪里来的神经病~');
		}

		//拼接合法的内容
		$keys = join(array_keys($data), ',');
		$vals = join($data, "','");
		$sql = "insert into {$this->tabName}({$keys}) values('{$vals}')";
		$this->sql = $sql;
		
		return $this->exec($sql);
	}

	/**
	 * 根据ID删除某一条数据
	 * @param  int $id 要删除数据的ID
	 * @return int   返回受影响行数
	 */
	public function delete($id = false)
	{
		//如果传了id就根据id删除
		if ($id) $this->where = "where id={$id}";
		//防止把整张表给删了
		if ($this->where=='' && $id==false) return 0;
		$sql = "delete from {$this->tabName} {$this->where}"; 
		$this->sql = $sql;

		return $this->exec($sql);
	}

	/**
	 * 修改数据
	 * @param  array $data 要修改的数组
	 * @return 成功返回受影响行，失败返回false
	 */
	public function save($data = [])
	{
		if (empty($data)) $data = $_POST; 
		//过滤非法字段
		$str = '';
		foreach ($data as $k=>$v) {
			if (!in_array($k, $this->allFields)) {
				unset($data[$k]);
			} else {
				$str .= "`$k`='$v', ";
			}
		}
		//非法数据
		if (empty($data) || empty($this->where)) return false;

		//处理要插入的数据
		$str = rtrim($str, ', ');

		$sql = "update {$this->tabName} set {$str} {$this->where}";
		$this->sql = $sql;

		//成功返回受影响行，失败返回false
		return $this->exec($sql);
	}

	/**
	 * 存储limit条件的方法
	 * @param  string|int $limit limit条件
	 * @return object  返回自己，保证连贯操作
	 */
	public function limit($limit)
	{
		if (is_string($limit) || is_int($limit)) {
			$this->limit = 'limit '.$limit;
		}
		return $this;
	}

	/**
	 * 设置排序的字段
	 * @param  string $order 根据哪个字段进行排序
	 *                       倒序：'id desc'
	 * @return object  返回自己，保证连贯操作
	 */
	public function order($order)
	{
		$this->order = 'order by '.$order;
		return $this;
	}

	

	/**
	 * 限定查询的字段
	 * @param  string $field 要查询的字段
	 * @return object  返回自己，保证连贯操作
	 */
	public function field($field)
	{ 
		$this->field = $field;
		return $this;
	}

	/**
	 * 存储where条件
	 * @param  string $where 要查询的where条件 
	 * @return object  返回自己，保证连贯操作
	 */
	public function where($where) 
	{ 
		if (!empty($where)) 
			$this->where = ' where '.$where;
		return $this;
	}

	/**
	 * 获取最后执行的sql语句
	 * @return string 最后执行的sql语句
	 */
	public function _sql()
	{
		return $this->sql;  
	}
}

