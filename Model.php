<?php
/**
 * Created by PhpStorm.
 * User: Addison Berker
 * Date: 12.01.2015
 * Time: 21:21
 */

namespace Engine\MVC;
use Engine\MVC\Config\DatabaseConfig;
use Engine\MVC\Helpers\Parsers\Object;
use Engine\MVC\Helpers\Parsers\Objects;
use Engine\MVC\Helpers\Parsers\SetObject;
use Engine\MVC\Interfaces\ModelInterface;

abstract class Model implements ModelInterface
{
    protected $allowedFields = null;
    /**
     * @var bool $useGroupConcat
     * */
    protected $useGroupConcat = false;
    /**
     * @var int $groupConcatLength
     * */
    protected $groupConcatLength = 1000000;
    /**
     * @var object $state
     * Will save current relation statement
     * */
    protected $state            = null;
    /**
     * @var string $tableName
     * */
    protected $tableName = null;
    /**
     * @var bool $returnAsObject
     * Will detect global return type
     * */
    protected $returnAsObject = false;
    /**
     * @var Database $db
     * Will save Database object
     * */
    protected $db;
    /**
     * @var string $currentModel
     * In this variable always saved the model name we are using at time
     * */
    protected $currentModel;
    /**
     * new method
     * You can use this method to add new row in table by functions per column (like setName($name),setLastName($lastName))
     * @return SetObject
     * */
    public function add()
    {
        $schema = array_diff(array_column($this->schema(),"Field"),$this->excepts);
        $setter = new SetObject();
        return $setter->init($schema,$this);
    }
    /**
     * existed method
     * You can use this method to edit existed row in table by functions per column (like setName($name),setLastName($lastName))
     * @param int $id
     * @return SetObject
     * */
    public function existed($id)
    {
        $schema = array_diff(array_column($this->schema(),"Field"),$this->excepts);
        $setter = new SetObject();
        return $setter->init($schema,$this,$id);
    }
    /**
     * count method
     * Will count current table rows You can do this without this method inside your model using db count method
     * @param array $where
     * @return integer
     * */
    public function count($where = null)
    {
        $data = $this->db
            ->count()
            ->from()
            ->where($where)
            ->query();
        return $data->fetchColumn();
    }
    /**
     * all method
     * Will return all rows from current table
     * @return array|object
     * */
    public function all()
    {
        $data = $this->_startSelector();
        $data = $data->query();
        return $this->detectReturnType($data->fetchAll(),true);
    }
    /**
     * allWhere method
     * Will find rows witch where array
     * @param array $where
     * @return object|array|null
     * */
    public function allWhere($where)
    {
        $data = $this->_startSelector();
        $data->where($where)->query();
        return $this->detectReturnType($data->fetchAll(),true);
    }
    /**
     * allWhere method
     * Will find rows witch where array
     * @param string $column
     * @param array $matchArray
     * @return object|array|null
     * */
    public function allWhereIn($column, $matchArray)
    {
        $data = $this->_startSelector();
        $data->whereIn($column,$matchArray)->query();
        return $this->detectReturnType($data->fetchAll(),true);
    }
    /**
     * limited method
     * @param int $start
     * @param int $end
     * @return object|array|null
     * */
    public function limited($start, $end = null)
    {
        $data = $this->_startSelector();
        $data->limit($start,$end)->query();
        return $this->detectReturnType($data->fetchAll(),true);
    }
    /**
     * limited method
     * @param array $where
     * @param int $start
     * @param int $end
     * @return object|array|null
     * */
    public function limitedWhere($where, $start, $end = null)
    {
        $data = $this->_startSelector();
        $data->where($where)->limit($start,$end)->query();
        return $this->detectReturnType($data->fetchAll(),true);
    }
    /**
     * first method
     * Will return first result from current table
     * @return object|array|null
     * */
    public function first()
    {
        $data = $this->_startSelector();
        $data->limit(1)->query();
        return $this->detectReturnType($data->fetch());
    }
    /**
     * last method
     * Will return first result from current table
     * @return object|array|null
     * */
    public function last()
    {
        $data = $this->_startSelector();
        $data->limit(1)->order('id','desc')->query();
        return $this->detectReturnType($data->fetch());
    }
    /**
     * onByID method
     * @param int $id
     * @return object|array|null
     * */
    public function oneByID($id)
    {
        $data = $this->_startSelector();
        $data->where($this->db->getTableName().'.id',$id)->query();
        return $this->detectReturnType($data->fetch());
    }
    /**
     * OnByRow method
     * @param array|string $columnOrArray
     * @param string $valueOrNull
     * @return object|array|null
     * */
    public function oneWhere($columnOrArray, $valueOrNull = null)
    {
        $data = $this->_startSelector();
        is_array($columnOrArray)
            ? $data->where($columnOrArray)->query()
            : $data->where($columnOrArray,$valueOrNull)->query();
        return $this->detectReturnType($data->fetch());
    }
    /**
     * saveMethod
     * @param null||array $array
     * @return int
     * */
    public function save($array)
    {
        $data = $this->db
            ->insert($array);
        return $data;
    }
    /**
     * updateByID method
     * @param int $id
     * @param array $data
     * @return boolean
     * */
    public function updateByID($id, $data)
    {
        $data = $this->db
            ->where('id',$id)
            ->update($data);
        return $data;
    }
    /**
     * updateByID method
     * @param array $where
     * @param array $data
     * @return boolean
     * */
    public function updateWhere($where, $data)
    {
        $data = $this->db
            ->where($where)
            ->update($data);
        return $data;
    }
    /**
     * removeByID method
     * @param int $id
     * @return boolean
     * */
    public function removeByID($id)
    {
        $data = $this->db
            ->where('id',$id)
            ->delete();

        $last = $this->last(false);
        $id = ($last == null) ? 0 : $last->id;

        $this->db->query("ALTER TABLE ".$this->db->getTableName()." AUTO_INCREMENT=".$id);
        return $data;
    }
    /**
     * removeByRow method
     * @param array $where
     * @return boolean
     * */
    public function removeWhere($where)
    {
        $data = $this->db
            ->where($where)
            ->delete();
        return $data;
    }
    /**
     * schema method
     * @param bool $onlyColumns
     * @return array
     * */
    public function schema($onlyColumns = false)
    {
        $schema = $this->db
            ->getSchema()
            ->fetchAll();
        return ($onlyColumns == true) ? array_diff(array_column($schema,"Field"),$this->excepts) : $schema;
    }
    /**
     * init method
     * @param array $args
     * @return void
     * */
    public function init($args = null)
    {
        if(!is_null($args)):
            foreach($args as $key=>$value):
                if(property_exists($this,$key))
                    $this->{$key} = $value;
            endforeach;
        endif;

        $this->db = is_null($this->db) ? new Database() : $this->db;
        $this->db = $this->db->init(DatabaseConfig::get());

        $parts = explode('\\',get_called_class());

        $className = end($parts);
        $this->currentModel = Func::splitAtUpperCase($className);
        unset($this->currentModel[(sizeof($this->currentModel) - 1)]);
        $this->currentModel = implode("_",$this->currentModel);

        $name = strtolower($this->currentModel);

        if(is_null($this->tableName)):
            if($this->db->tableExists($name.'s'))
                $this->tableName = $name.'s';
            elseif($this->db->tableExists($name))
                $this->tableName = $name;
            else{
                exit("Please set correct table name for ".$this->currentModel." model");
            }
        endif;
        $this->db->setTableName($this->tableName);
        if($this->useGroupConcat)
            $this->db->query('SET SESSION group_concat_max_len = '.$this->groupConcatLength);
    }
    /**
     * _startSelector method
     * @return object
     * */
    private function _startSelector()
    {
        return !is_null($this->state)
            ? $this->state
            : $this->db->select($this->rowDetector())->from();
    }
    /**
     * rowDetector method
     * @return string
     */
    private function rowDetector()
    {
        if(!is_null($this->allowedFields))
        {
            array_walk($this->allowedFields, function(&$value, $key) { $value = $this->db->getTableName().".".$value; });
            return implode(",",$this->allowedFields);
        }
        return '*';
    }
    /**
     * detectReturnType
     * @param \stdClass|array $data
     * @param boolean $multiple
     * @return Object|Objects|\stdClass|array|null
     * */
    protected function detectReturnType($data,$multiple = false)
    {
        if(!$data)
            return null;

        if($this->returnAsObject)
        {
            $object = ($multiple === TRUE) ? new Objects() : new Object();
            return $object->init($data);
        }
        else
            return $data;
    }
}