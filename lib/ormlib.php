<?php

if(!defined('INTERFACE_ACCESS')){die('Direct access not premitted');}

/**
 * ORM Class for entities.
 * ORM = Object Relation Mapper
 */
class ormlib {
    
    var $className;
    var $key;
    
    /**
     * Constructor
     * @param type $className
     * @param type $key
     */
    function __construct($className, $key) {
        $this->className = $className;
        $this->key = $key;
    }
    
    /**
     * Returns a list of properties of this class.
     * @return type
     */
    function get_properties() {
        $keys = array();
        $reflect = new ReflectionClass($this);
        $props = $reflect->getProperties();
        foreach($props as $prop) {
            $propname = $prop->name;
            if($propname != "className" && $propname != "key") {
                if($prop->class==$this->className) {
                    if(!is_array($this->$propname)) {
                        $keys[] = $propname;
                    }
                }
            }
        }
        return $keys;
    }
    
    /**
     * 
     */
    function read_from_request() {
        $props = $this->get_properties();
        foreach($props as $prop) {
            $this->$prop = $_REQUEST[$prop];
        }
    }
    
    /**
     * Create Object Instance
     * @return type
     */
    function create_instance() {
        $r = new ReflectionClass($this->className);
        return $r->newInstanceArgs();
    }
    
    /**
     * Insert entity.
     * @global type $dbconnection
     */
    function insert() {
        global $dbconnection;
        $reflect = new ReflectionClass($this);
        $props = $reflect->getProperties();
        $keys = array();
        $values = array();
        foreach($props as $prop) {
            $propname = $prop->name;
            if($prop->class==$this->className) {
                if(!is_array($this->$propname) && $this->key != $propname && $propname != "key" && $propname != "className") {
                    $keys[] = $propname;
                    $values[] = $this->$propname;
                }
            }
        }
        $query = "INSERT INTO `".$this->className."` (`".implode("`,`", $keys)."`) VALUES (\"".implode("\",\"",$values)."\")";
        error_log($query);
        $dbconnection->do_query_meta($query);
        $key1 = $this->key;
        $this->$key1 = $dbconnection->last_insert_meta();
    }
    
    /**
     * returns an entity with key
     * @param type $id
     * @return type
     */
    function get($key) {
        $keys = array($this->key);
        $values = array($key);
        return $this->get_with_parameter($keys, $values);
    }
    
    /**
     * Returns one entity with parameters
     * @global type $dbconnection
     * @param type $keys
     * @param type $values
     * @return null
     */
    function get_with_parameter($keys = array(), $values = array()) {
        global $dbconnection;
        $wheres = array();
        for($i=0;$i<count($keys);$i++) {
            $wheres[] = $keys[$i]."='".$values[$i]."'";
        }
        $where = "";
        if(count($wheres) > 0) {
            $where = " WHERE ".implode(" AND ", $wheres);
        }
        else {
            return NULL;
        }
        $query = "SELECT * FROM `".$this->className."` $where";
        $result = $dbconnection->do_query_meta_response($query);
        $object = $this->create_instance();
        $reflect = new ReflectionClass($this);
        $props = $reflect->getProperties();
        while($myrow = mysql_fetch_array($result)) {
            foreach($props as $prop) {
                $propname = $prop->name;
                if($prop->class==$this->className) {
                    if(!is_array($object->$propname)) {
                        $object->$propname = $myrow[$propname];
                    }
                }
            }
        }
        $key = $this->key;
        if($object->$key != "") {
            return $object;
        }
        else {
            return NULL;
        }
    }
    
    /**
     * Update entity.
     * @global type $dbconnection
     */
    function update() {
        global $dbconnection;
        $reflect = new ReflectionClass($this);
        $props = $reflect->getProperties();
        $keys = array();
        $key = "";
        foreach($props as $prop) {
            $propname = $prop->name;
            if($prop->class==$this->className) {
                if(!is_array($this->$propname) && $propname != $this->key) {
                    $keys[] = $propname."='".($this->$propname)."'";
                }
            }
            if($propname==$this->key) {
                $key = $this->$propname;
            }
        }
        $query = "update `".$this->className."` set ".implode(",", $keys)." where ".$this->key." = '$key'";
        error_log($query);
        $dbconnection->do_query_meta_response($query);
    }
    
    function remove() {
        global $dbconnection;
        $key = $this->key;
        $id = $this->$key;
        $query = "delete from `".$this->className."` where $key = '$id'";
        $dbconnection->do_query_meta_response($query);
    }
    
    /**
     * Returns a list of entity with parameters
     * @global type $dbconnection
     * @param type $keys
     * @param type $values
     * @param type $order_by order f.e. index desc
     * @return type
     */
    function get_list($keys = array(), $values = array(), $order_by = "") {
        global $dbconnection;
        $wheres = array();
        for($i=0;$i<count($keys);$i++) {
            $wheres[] = $keys[$i]."='".$values[$i]."'";
        }
        $where = "";
        if(count($wheres) > 0) {
            $where = " where ".implode(" AND ", $wheres);
        }
        $objects = array();
        $query = "select * from `".$this->className."` $where ".($order_by != "" ? "order by $order_by" : $order_by);
        $result = $dbconnection->do_query_meta_response($query);
        while($myrow = mysql_fetch_array($result)) {
            $object = $this->create_instance();
            $reflect = new ReflectionClass($object);
            $props = $reflect->getProperties();
            foreach($props as $prop) {
                $propname = $prop->name;
                if($prop->class==$object->className) {
                    if(!is_array($object->$propname)) {
                        $object->$propname = $myrow[$propname];
                    }
                }
            }
            $objects[] = $object;
        }
        return $objects;
    }
    
    public static function get_list_from_instance($instance, $keys = array(), $values = array()) {
        return $instance->get_list($keys, $values);
    }
    
    public static function get_from_instance($instance, $keys = array(), $values = array()) {
        return $instance->get_with_parameter($keys, $values);
    }
    
    public static function remove_from_instance($instance, $where) {
        global $dbconnection;
        $query = "delete from `".$instance->className."` where $where";
        error_log($query);
        $dbconnection->do_query_meta_response($query);
    }
    
}

?>