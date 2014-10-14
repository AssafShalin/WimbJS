<?php
 
include_once dirname(__FILE__) . '/config.php';
 
class SQLException extends ErrorException {
 
    public function __construct($code, $message) {
        parent::__construct($message, $code, "0");
    }
 
}
 
class DB {
 
//-------------------------------------------------------------------------------------------
//This is DB connection Class
//Database info is in DB_Info.php
//Constructor does the link.
//use doQuery function to run a query on the mySQL server.
//-------------------------------------------------------------------------------------------
    //DB Link.
    private $link;
    //static singleton
 
    private static $instance;
 
    /**
     * get a shared singleton instance of db object
     * @return DB
     */
    public static function getInstance() {
        if (DB::$instance == NULL)
            DB::$instance = new DB();
        return DB::$instance;
    }
 
    private function __construct() {
        //Load DB info
        $dbinfo = new DB_Info();
 
        //Make connection
        $this->link = mysqli_connect($dbinfo->getHost(), $dbinfo->getUsr(), $dbinfo->getPass(), $dbinfo->getDbname()) or die(mysqli_error());
        //select db
        mysqli_select_db($this->link, $dbinfo->getDbname()) or dir(mysqli_error($this->link));
        mysqli_set_charset($this->link, "utf8");
    }
 
    private function getParamsType(array $params) {
        $type = "";
        foreach ($params as $param) {
            if (is_double($param))
                $type .= "d";
            elseif (is_int($param))
                $type .= "i";
            elseif (is_string($param))
                $type .= "s";
            else $type .= "s";
        }
        return $type;
    }
 
    /**
     * will preform a query on the database. all variables must be represended in the query as '@param`
     * and be specified later, as a function parameter
     * @param string $query the query itself
     * @param array an array of prams
     * @return DBResults
     */
    public function doQueryWithArray($query, array $params = array()) {
        //replace @param lecgacy with ? sqli
        $query = str_replace("'@param'", '?', $query);
        $query = str_replace('@param', '?', $query);
        $types = $this->getParamsType($params);
        $params = $this->unNullify($params);
        $params = $this->refValues($params);
         
        //debug object
        $dbg = new stdClass;
        $dbg->sql = $query;
        $dbg->params = $params;
        $dbg->types = $types;
         
         
         
        $stmt = mysqli_prepare($this->link, $query);
         
        if($stmt == false)
        {
            print_r($dbg);
            throw new SQLException(mysqli_errno ($this->link),  mysqli_error ($this->link));
        }
        if(count($params)>0)
            call_user_func_array('mysqli_stmt_bind_param', array_merge(array($stmt, $types), $this->refValues($params)));
        $stat = mysqli_stmt_execute($stmt);
        if($stat == false)
        {
            print_r($dbg);
            throw new SQLException(mysqli_errno ($this->link),  mysqli_error ($this->link));
        }
        mysqli_stmt_store_result($stmt);
        return new DBResults($stmt,$dbg);
    }
    private function unNullify($arr)
    {
        $un = array();
        foreach($arr as $item)
        {
            $item = str_replace("\\\"","\"",$item);
            $item = str_replace("\\'","'",$item);
            if(is_null($item)) $un [] = "";
            else $un [] = $item;
 
            //escape the escapement
 
        }
        return $un;
         
    }
    function refValues($arr)
    {
            $refs = array();
 
            foreach ($arr as $key => $value)
            {
                $refs[$key] = &$arr[$key];
            }
 
            return $refs;
          
 
       return $arr;
    }
    /**
     * will preform a query on the database. all variables must be represended in the query as '@param`
     * and be specified later, as a function parameter
     * @param string $query the query itself
     * @param string unlimited pramas
     * @return DBResults
     */
    public function doQuery($query, $params = null) {
        //prepare query
        $args = func_get_args();
        if ($args < 1)
            return "";
        $arr = array();
        for ($i = 1; $i < count($args); $i++) {
            $arr[] = $args[$i];
        }
        return $this->doQueryWithArray($query, $arr);
    }
 
    public function __destruct() {
        #mysql_close();
    }
 
}
 
class DBResults {
 
    private $stmt;
    private $dbg_obj;
    function __construct($stmt,$dbg) {
        $this->stmt = $stmt;
        $this->dbg_obj = $dbg;
    }
 
    public function getDebugInfo()
    {
        return $this->dbg_obj;
    }
    /**
     *  will return an object of the current row in the table
     * @return stdClass
     */
    public function getObject() {
        $data = mysqli_stmt_result_metadata($this->stmt);
        $count = 1; //start the count from 1. First value has to be a reference to stmt.
        $fieldnames[0] = &$this->stmt;
        $obj = new stdClass;
        while ($field = mysqli_fetch_field($data)) {
            $fn = $field->name; //get all the feild names
            $fieldnames[$count] = &$obj->$fn; //load the fieldnames into an object..
            $count++;
        }
        call_user_func_array('mysqli_stmt_bind_result', $fieldnames);
        $res = mysqli_stmt_fetch($this->stmt);
        if($res == false) return false;
        return $obj;
    }
 
    /**
     * will return an assosiative array that represents the current row of a table in the current results
     * @return array
     */
    function getArray() {
         
        $data = mysqli_stmt_result_metadata($this->stmt);
         
        $count = 1; //start the count from 1. First value has to be a reference to the stmt. because bind_param requires the link to $stmt as the first param.
        $fieldnames[0] = &$this->stmt;
        while ($field = mysqli_fetch_field($data)) {
            $fieldnames[$count] = &$array[$field->name]; //load the fieldnames into an array.
            $count++;
        }
        call_user_func_array('mysqli_stmt_bind_result', $fieldnames);
        $res = mysqli_stmt_fetch($this->stmt);
        if($res == false) return false;
        return $array;
    }
 
    /**
     * returns the num of rows in the current results
     * @return int
     */
    public function getCount() {
        return mysqli_stmt_num_rows($this->stmt);
    }
 
    /**
     * will return if the current results are empty
     * @return bool
     */
    public function isEmpty() {
        return(($this->getCount() == 0) ? TRUE : FALSE);
    }
 
    /**
     * will return the auto increment value of an insert query
     * @return int the value
     */
    public function getInsertAutoIncrementID() {
        return mysqli_stmt_insert_id($this->stmt);
    }
 
    /**
     * will return the number of affected rows
     * @return int the number of affected rows after a query
     */
    public function getAffectedRows() {
        $affected = mysqli_stmt_affected_rows($this->stmt);
        return $affected;
         
    }
 
    public function getAllRecordsAsArrayOfArrays() {
        $array = array();
        while (($record = $this->getArray()) != FALSE) {
            $array[] = $record;
        }
        return $array;
    }
 
    public function getAllRecordsAsObjectArray() {
        $array = array();
        while ($record = $this->getObject()) {
            $array[] = $record;
        }
        return $array;
    }
 
}
