<?php

class DBabstraction
{
    private $db;
    private $h;
    private $errors = [];
    private $redis;
    protected $dbTable;
    function __construct()
    {
        if ($GLOBALS['db'])
            $this->db = $GLOBALS['db'];
        if(!$this->db) return false;

        //redis
        if(defined('REDIS_SERVER') && REDIS_SERVER!='')
        {
        $redis = new Redis();
        $redis->pconnect(REDIS_SERVER, REDIS_PORT);
        if (defined('REDIS_PASS') && REDIS_PASS)
            $redis->auth(REDIS_PASS);
        if(defined('REDIS_PREFIX') && REDIS_PREFIX)
            $redis->setOption(Redis::OPT_PREFIX, REDIS_PREFIX);
        $this->redis = $redis;
        }
        else $this->redis = false;

        $h = new \ClanCats\Hydrahon\Builder('mysql', function($query, $queryString, $queryParameters)
        {
            $statement = $GLOBALS['db']->prepare($queryString);

            if (!$statement) {
                $this->addError($this->db->errorInfo());
                return false;
            }

            $statement->execute($queryParameters);

            // when the query is fetchable return all results and let hydrahon do the rest
            // (there's no results to be fetched for an update-query for example)
            if ($query instanceof \ClanCats\Hydrahon\Query\Sql\Insert)
                return ($this->db->lastInsertId()!=='0'?:false);
            else if ($query instanceof \ClanCats\Hydrahon\Query\Sql\FetchableInterface)
                return $statement->fetchAll(\PDO::FETCH_ASSOC);
            
        });
        $this->h = $h->table($this->dbTable);
    }

    function q()
    {
        return $this->h;
    }

    function escape($string)
    {
        if($this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql')
            return mysqli_real_escape_string($this->db,$string);
        else if($this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite')
            return SQLite3::escapeString($string);
    }

    /**
     * select wrapper
     * 
     * @param $what string or array of fields to be selected
     * @param $from string Table (if empty will use $this->dbTable)
     * @param $conditions array ['id'=>88]
     * @param $options['single'] Onlye one answer will be returned
     * @param $options['conditiontype']=>'AND' is the condition type to compare the $conditions. default AND
     * @param $options['order']=>['by'=>'id','dir'=>'DESC'] order the results
     */
    function select($what,$from=false,$conditions=false,$options=[])
    {
        if(!$from) $from = $this->dbTable;

        $ws = '';
        if(is_array($what))
        {
            $what = array_map(array( __CLASS__,'_surroundWith'),$what);
            $ws = implode(',',$what);
        }
        else $ws = ($what=='*'?'*':$this->_surroundWith($what,'`'));

        $query = "SELECT $ws FROM `$from`";

        if(is_array($conditions))
        {
            if($options['conditiontype'])
                $conditiontype = $options['conditiontype'];
            else $conditiontype = 'AND';
            $cp = [];
            $query.=' WHERE ';
            foreach($conditions as $key=>$value)
                $cp[] = $this->_surroundWith($key,'`').' = \''.$this->escape($this->db, $value).'\'';
            $query.=implode(" $conditiontype ",$cp);
        }

        if($options['order'])
            $query.= 'ORDER BY '.$this->_surroundWith($options['order']['by'],'`').' '.$options['order']['dir'];

        return $this->simplequery($query,in_array('single',$options));
    }

    function del($from=false,$conditions=false,$options=[])
    {
        if(!$from) $from = $this->dbTable;

        $query = "DELETE FROM `$from`";

        if(is_array($conditions))
        {
            if($options['conditiontype'])
                $conditiontype = $options['conditiontype'];
            else $conditiontype = 'AND';
            $cp = [];
            $query.=' WHERE ';
            foreach($conditions as $key=>$value)
                $cp[] = $this->_surroundWith($key,'`').' = \''.$this->escape($this->db, $value).'\'';
            $query.=implode(" $conditiontype ",$cp);
        }

        return $this->simplequery($query,in_array('single',$options));
    }

    function justQuery($query)
    {
        if (!$this->db) return false;
        $stmt = $this->db->query($query);
        if (!$stmt) {
            $this->addError($this->db->errorInfo());
            return false;
        }

        return $stmt;
    }

    function query_count($query)
    {
        if (!$this->db) return false;
        $stmt = $this->db->query($query);
        if (!$stmt) {
            $this->addError($this->db->errorInfo());
            return false;
        }
        return count($stmt->fetchAll());
    }

    function simplequery($query, $onlyone = false)
    {
        if (!$this->db) return false;
        $stmt = $GLOBALS['db']->query($query.($onlyone===true?' LIMIT 1':''));
        if (!$stmt) {
            $this->addError($stmt->errorInfo());
            return false;
        }
        $out = array();
        while ($row = $stmt->fetch()){
            $o = array();
            if ($row)
                foreach ($row as $key => $rd)
                    if (!is_numeric($key))
                        $o[$key] = $rd;

            $out[] = $o;
        }
        if (count($out) == 1 && $onlyone)
            return $out[0];
        return $out;
    }

    function insert($data, $table=false)
    {
        if(!$table) $table = $this->dbTable;
        if (!$this->db) return false;
        $fields = [];
        $vals = [];
        if (is_array($data) && count($data))
            foreach ($data as $key => $value) {
                if($value===null) $value = 'NULL';
                $fields[]= $this->_surroundWith($key);
                if (endswith($value, '()') || $value == 'NULL')
                    $vals[] = $this->escape($this->db,$value);
                else
                    $vals[] = $this->_surroundWith($this->escape($this->db,$value),'\'');
            }
        else return false;

        $fields = implode(',',$fields);
        $vals = implode(',',$vals);
        $query = "INSERT INTO `" . $table . "` ($fields) VALUES ($vals)";
        $stmt = $GLOBALS['db']->query($query);
        if (!$stmt) {
            $this->addError($this->db->errorInfo());
            return false;
        }

        return $this->db->lastInsertId();;
    }

    function update($array, $table, $whereArr, $wheretype = 'AND')
    {
        if(!$table) $table = $this->dbTable;
        if (!$this->db) return false;
        $set = array();
        $where = array();
        foreach ($array as $field => $val)
        {
            if($val===null) $val = 'NULL';
            if (endswith($val, '()') || $val == 'NULL')
                $set[] = ' `' . $field . '` = ' . $this->escape($this->db, $val) . ' ';
            else
                $set[] = ' `' . $field . '` = \'' . $this->escape($this->db, $val) . '\' ';
        }

        foreach ($whereArr as $key => $value)
            $where[] = ' `' . $key . '` = \'' . $value . '\' ';

        $where = implode($wheretype, $where);

        $set = implode(',', $set);
        $q = "UPDATE `" . $table . "` SET " . $set . " WHERE $where";
        if (!mysqli_query($this->db, $q)) {
            $this->addError(mysqli_error($this->db));
            return false;
        }
        return true;
    }

    public function addError($data)
    {
        if(is_array($data))
            $data = implode('-',$data);
        $this->errors[] = $data;
    }

    function getErrors()
    {
        return $this->errors;
    }

    function getLastError()
    {
        return end($this->errors);
    }

    function _surroundWith($string,$surround='`')
    {
        return "$surround$string$surround";
    }
}
