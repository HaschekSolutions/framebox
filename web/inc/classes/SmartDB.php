<?php

class SmartDB extends DBabstraction
{
    public $data;
    public $id = false;
    //private $errors;

    function __construct()
    {
        parent::__construct(); //connect to db in parent
    }

    /**
     * Magic getter function
     *
     * @param $name Variable name
     *
     * @return mixed
     */
    public function __get($name)
    {
        if (isset($this->data[$name]))
            return $this->data[$name];
    }


    /**
     * Magic setter function
     *
     * @return mixed
     */
    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function getDBFields()
    {
        return $this->dbFields;
    }

    /**
     * Returns all data fields but removes filtered datapoints like passwords, etc
     */
    public function getDataFiltered()
    {
        $data = $this->data;
        if(is_array($this->hidden))
            foreach($this->hidden as $secretfield)
                unset($data[$secretfield]);
        return $data;
    }

    public function save()
    {
        if (!$this->validate())
            return false;
        if($this->id)
            return $this->q()->update($this->data)->where('id','=',$this->id)->execute();
            //return $this->update($this->data,false,['id'=>$this->id]);
        else
        {
            //$id = $this->insert($this->data);
            $id = $this->q()->insert($this->data)->execute();
            $this->load($id);
            return $id;
        }
    }

    public function load($value,$key='id',$order=false)
    {
        $this->$key = $value;
        $keys = array_keys($this->dbFields);

        if($key!='id')
            $keys[] = 'id';

        $options = ['single'];
        if($order)
            $options = array_merge($options,array('order'=>$order));
        
        $data = $this->q()->select($keys)->where($key,$value)->get();
        //$data = $this->select($keys,false,[$key=>$value],$options);
        if(!$data) return false;
        foreach($data as $key=>$value)
            if($key!='id')
            {
                if($value!==NULL) //we'll leave null values
                    switch($this->dbFields[$key][0])
                    {
                        case 'int': $value = intval($value);break;
                        case 'bool': $value = boolval($value);break;
                        case 'float': $value = floatval($value);break;
                        case 'double': $value = doubleval($value);break;
                    }
                $this->data[$key] = $value;
            }
        if(!$this->id)
            $this->id = intval($data['id']);
    }

    /**
     * @param array $data
     */
    private function validate()
    {
        if (!$this->dbFields)
            return true;

        $data = $this->data;

        foreach ($this->dbFields as $key => $options) {
            $type = null;
            $required = false;
            
            if(in_array('autoupdate',$options))
                $this->data[$key] = NULL;

            if (isset($data[$key]))
                $value = $data[$key];
            else
                $value = null;
            
            if (is_array($value))
                continue;

            if (isset($desc[0]))
                $type = $desc[0];
            if (in_array('required',$options))
                $required = true;

            if($value===null && $options['autoValMethod'] && method_exists($this,$options['autoValMethod']))
            {
                $value = $this->{$options['autoValMethod']}();
                $this->data[$key] = $value;
            }

            if ($required && strlen($value) == 0) {
                $this->addError($this->dbTable . "." . $key . " is required");
                continue;
            }
            if ($value == null)
                continue;

            switch ($type) {
                case 'email':
                    $regexp = null;
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $this->addError("$type validation failed");
                        continue 2;
                    }
                break;
                case "text":
                    $regexp = null;
                    break;
                case "int":
                    $regexp = "/^[0-9]*$/";
                    break;
                case "double":
                    $regexp = "/^[0-9\.]*$/";
                    break;
                case "bool":
                    $regexp = '/^(yes|no|0|1|true|false)$/i';
                    break;
                case "datetime":
                    $regexp = "/^[0-9a-zA-Z -:]*$/";
                    break;
                default:
                    $regexp = $type;
                    break;
            }
            if (!$regexp)
                continue;

            if (!preg_match($regexp, $value)) {
                $this->addError($this->dbTable . "." . $key . " $type validation failed");
                continue;
            }
        }
        return !count($this->getErrors()) > 0;
    }

    function delete()
    {
        return $this->del(false,['id'=>$this->id]);
    }

    function gen_uuid() {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
    
            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ),
    
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000,
    
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,
    
            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }

    function getDateTime($time=false)
    {
        return date('Y-m-d H:i:s',($time?$time:time()));
    }
}
