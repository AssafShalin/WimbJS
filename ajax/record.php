<?php
include_once dirname(__FILE__) . '/db.php';
 
class Record {
     
    protected $tableName;
    protected $tableFields = array();
    protected $primaryKey = 'id';
    protected $mapper = array();
    protected $cleanBeforeSave = FALSE;
    protected $tableCleaner = array();
 
    /**
     * example function "$obj->id == 12"
     * @param Function $func - must return boolean. use $obj as record parameter
     *
     */
    public static function fetchWhere($func, $orderBy = null)
    {
        $className = get_called_class();
        $records = self::privateFetchAll($className, $orderBy);
        $function = self::createFunction($func);
        foreach($records as $key => $record)
        {
 
            $boolean = $function($record);
            if(!$boolean)
            {
                unset($records[$key]);
            }
        }
        return array_values($records);
         
    }
     
    private static function createFunction($code)
    {
        $func = "return $code;";
        return create_function('$obj',$func);
 
    }
     
    public static function fetch($primaryKey)
    {
        $className = get_called_class();
         
        $disposableClass = new $className;
        $sql = "SELECT * FROM `{$disposableClass->tableName}` WHERE `{$disposableClass->primaryKey}` = '@param';";
        $db = DB::getInstance();
        $res = $db->doQuery($sql, $primaryKey);
        if($res->getCount() == 0) return false;
        $resultArray = Record::mapResults($res, $className);
 
        $record = end($resultArray);
        return $record;
         
    }
    private function getMyVars()
    {
        //will return an array with all the vars that are Record's
        return array('tableName','tableFields','primaryKey','mapper','cleanBeforeSave','tableCleaner');
    }
    private function getClassVars()
    {
        $vars = get_class_vars(get_class($this));
        $myVars = $this->getMyVars();
        //remove Records vars
        foreach($myVars as $myVar) unset($vars[$myVar]);
        return array_keys($vars);
    }
    private function getMappedTableFields()
    {
        $tableMap = array();
        foreach ($this->tableFields as $field)
        {
            if(isset($this->mapper[$field])) $tableMap[] = $this->mapper["$field"];
            else $tableMap[] = $field;
        }
        if($this->cleanBeforeSave)
        {
            foreach($tableMap as $key=>$map)
            {
                if(array_search($map,$this->tableCleaner) === false)
                {
                    unset($tableMap[$key]);
                }
            }
        }
        return array_values($tableMap);
    }
    public function __construct() {
        $this->tableName = get_class($this);
        $this->tableFields = $this->getClassVars();
    }
    private function prepareValues()
    {
        $values = array();
        foreach($this->tableFields as $field)
        {
            if(!($field == $this->primaryKey && !isset($this->$field)))
                $values[] = $this->$field;
        }
        return $values;
    }
    private function getTableFieldsWithoutPrimaryKey()
    {
        $classVars = $this->getMappedTableFields();
        unset($classVars[array_search($this->primaryKey, $classVars)]);
        return $classVars;
    }
    private function getValuesWithoutPrimaryKey()
    {
        $values = array();
        foreach($this->tableFields as $field)
        {
            if(!($field == $this->primaryKey))
            {
                if($this->cleanBeforeSave == true)
                {
                    if(array_search($field,$this->tableCleaner) != null)
                    {
                        $values[] = $this->$field;
                    }
                }
                else
                {
                        $values[] = $this->$field;
                }
            }
 
        }
        return $values;
    }
    public static function create($primaryKeyValue = null)
    {
        $className = get_called_class();
        $obj = new $className;
        if($primaryKeyValue != null)
            $obj->{$obj->primaryKey} = $primaryKeyValue;
        $obj->insert();
        return $obj;
    }
    private function insert()
    {
        $tableMap = $this->getMappedTableFields();
         
        //sql insert header
        $sql = "INSERT INTO `{$this->tableName}` (";
         
        //prepare the table insert
        foreach($tableMap as $key => $field)
        {
            $sql.= "`$field`";
            if($key != (count($tableMap)-1)) $sql .= ", ";
        }
        //end fields head
        $sql .= ") VALUES (";
         
 
        $classVars = $this->tableFields;
        //check if primary key is set
        if(!isset($this->{$this->primaryKey}))
        {
            $sql.= "NULL";
            unset($classVars[array_search($this->primaryKey, $classVars)]);
            if (count($classVars)>0) $sql .= ", ";
        }
         
         
        $classVars = array_values($classVars);
        foreach($classVars as $key => $vars)
        {
            $sql .= "'@param'";
            if($key != (count($classVars)-1)) $sql .= ", ";
        }
        $sql .= ");";
         
        $values = $this->prepareValues();
         
        $db = DB::getInstance();
        $res = $db->doQueryWithArray($sql,$values);
        $AI_KEY = $res->getInsertAutoIncrementID();
        if($AI_KEY != 0) $this->{$this->primaryKey} = $AI_KEY;
        return $res->getAffectedRows() == 1;
    }
    public function save()
    {
        if($this->isExists())
        {
            $this->update();
        }
 
        else
        {
            $this->insert ();
        }
    }
    public static function fetchAll($orderBy = null)
    {
        $className = get_called_class();
        return self::privateFetchAll($className,$orderBy);
    }
     
    private static function privateFetchAll($className, $orderBy = null)
    {
        $disposableClass = new $className;
        if($orderBy != null)
        {
 
            $orderBy = "ORDER BY " . $orderBy;
            $sql = "SELECT * FROM `{$disposableClass->tableName}` $orderBy";
        }
        else
        {
            $sql = "SELECT * FROM `{$disposableClass->tableName}`";
        }
 
 
        $db = DB::getInstance();
        $res = $db->doQuery($sql);
        return Record::mapResults($res, $className);
    }
    private static function mapResults(DBResults $res, $className)
    {
        $records = array();
        while($arr = $res->getArray())
        {
            $record = new $className;
             
            $record->mapObject($arr);
            $records[] = $record;
        }
        return $records;
    }
    private function mapObject($arr)
    {
        foreach($arr as $key=>$val)
        {
            $this->{$this->getObjectKey($key)} = $val;
        }
    }
    private function getObjectKey($tableField)
    {
        $key = array_search($tableField, $this->mapper);
        if($key == false) return $tableField;
    }
    private function update()
    {
 
        //will prepare an update statement based on a primary key
        $sqlHeader = "UPDATE `$this->tableName` SET";
        $sqlFooter = "WHERE `$this->primaryKey` = @param;";
         
        $tableFields = $this->getTableFieldsWithoutPrimaryKey();
        $tableValues = $this->getValuesWithoutPrimaryKey();
         
        $sqlBody = "";
        $tableFields = array_values($tableFields);
        foreach($tableFields as $key => $field)
        {
            $sqlBody .= "`$field` = @param";
            if($key != (count($tableFields)-1)) $sqlBody .= ", ";
        }
         
        $sql = $sqlHeader . " " .$sqlBody . " " .$sqlFooter;
        $values = array_merge($tableValues, array($this->{$this->primaryKey}));
         
        $db = DB::getInstance();
        $res = $db->doQueryWithArray($sql,$values);
        return $res->getAffectedRows() == 1;
    }
    public function delete()
    {
        if(!isset($this->{$this->primaryKey})) return false;
        $sql = "DELETE FROM `{$this->tableName}` WHERE `{$this->primaryKey}` = '@param';";
        $val = $this->{$this->primaryKey};
         
        $db = DB::getInstance();
        $res = $db->doQuery($sql, $val);
        if($res->getAffectedRows()>=1) return true;
        return false;
    }
    private function isExists()
    {
        if(!isset($this->{$this->primaryKey})) return false;
        $sql = "SELECT * FROM `{$this->tableName}` WHERE `{$this->primaryKey}` = '@param'";
        $db = DB::getInstance();
        $res = $db->doQuery($sql, $this->{$this->primaryKey});
        return !($res->isEmpty());
    }
     
}
 
//compatible with PHP 5.2
if(!function_exists('get_called_class')) {
    function get_called_class($bt = false,$l = 1) {
        if (!$bt) $bt = debug_backtrace();
        if (!isset($bt[$l])) throw new Exception("Cannot find called class -> stack level too deep.");
        if (!isset($bt[$l]['type'])) {
            throw new Exception ('type not set');
        }
        else switch ($bt[$l]['type']) {
            case '::':
                $lines = file($bt[$l]['file']);
                $i = 0;
                $callerLine = '';
                do {
                    $i++;
                    $callerLine = $lines[$bt[$l]['line']-$i] . $callerLine;
                } while (stripos($callerLine,$bt[$l]['function']) === false);
                preg_match('/([a-zA-Z0-9\_]+)::'.$bt[$l]['function'].'/',
                    $callerLine,
                    $matches);
                if (!isset($matches[1])) {
                    // must be an edge case.
                    throw new Exception ("Could not find caller class: originating method call is obscured.");
                }
                switch ($matches[1]) {
                    case 'self':
                    case 'parent':
                        return get_called_class($bt,$l+1);
                    default:
                        return $matches[1];
                }
            // won't get here.
            case '->': switch ($bt[$l]['function']) {
                case '__get':
                    // edge case -> get class of calling object
                    if (!is_object($bt[$l]['object'])) throw new Exception ("Edge case fail. __get called on non object.");
                    return get_class($bt[$l]['object']);
                default: return $bt[$l]['class'];
            }
 
            default: throw new Exception ("Unknown backtrace method type");
        }
    }
}