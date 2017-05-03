<?php
namespace Lib\ORM;

use Lib\ORM\Driver\Connection;
/**
 * Description of Database
 *
 * @author Himel
 */
class Database {
    
    const table = null;
    protected static $conn;
    protected static $self;
    protected static $take;
    protected static $skip;
    
    protected static $sqlCommand;
    
    protected static $where;
    protected static $select;
    protected static $sortBy;
    protected static $sortOrder;
    
    
    
    const CLAUSE_FROM  = 'from';
    const CLAUSE_SELECT = 'select';
    const CLAUSE_WHERE = 'where';
    
    public function __get($name) {
        
        if(method_exists($this, $name)){
            $this->$name = $this->$name()->get();
        }
    }
    
    public static function clear()
    {
        self::$where = null;
        self::$select = null;
        self::$sortBy = null;
        self::$sortOrder = null;
        static::$sqlCommand = null;
        
    }
    

    public static function find($id)
    {
        $self = new static;
        static::$sqlCommand = "SELECT * FROM ".static::table." WHERE id=".$id;
        self::$conn = Connection::getInstance();   
        $stmt = self::$conn->query(static::$sqlCommand);
        $stmt->execute();
        $stmt->setFetchMode(\PDO::FETCH_CLASS,  get_class($self));
        return $stmt->fetch();
    }
    
    public function save()
    {
        
        $columnNames  = [];
        $columnValues = [];
        $bindWildCard = [];
        
        $fields = get_object_vars($this);
        
        foreach($fields as $field => $fieldVal){
            if(property_exists($this, $field)){
                if(isset($fieldVal)){
                    $columnNames[]  = "`".$field."`";
                    $columnValues[] = $fieldVal;
                    $bindWildCard[] = "?";
                }
            }
        }
        if(!empty($this->id)){
            self::$sqlCommand = "UPDATE ".static::table." SET ";
            $updateData = [];
            foreach($columnNames as $i => $col){
                $updateData[]  = "$col=?"; 
            }
            self::$sqlCommand .= implode(",",$updateData)." WHERE id=".$this->id;
            
            self::$conn = Connection::getInstance();   
            $stmt = self::$conn->prepare(static::$sqlCommand);
            if(!empty($stmt)){
                $stmt->execute($columnValues);
                return $stmt->rowCount();
            }else{
                throw new \Exception("SQL ERROR: [".$this->lastQuery()."]");
            }
        }else{
            
            if(Connection::isOracle()){
                self::$sqlCommand = "SELECT ".static::$sequence.".nextval as id from dual";
                $stmt = self::$conn->query(static::$sqlCommand);
                if(!empty($stmt)){
                    $stmt->execute();
                    $lastInsertId = $stmt->fetchColumn(0);
                    if(!in_array("ID",$columnNames)){
                        array_unshift($columnNames,"ID");
                        array_unshift($bindWildCard, "?");
                        array_unshift($columnValues,$lastInsertId);
                    }
                }else{
                    throw new \Exception("SQL ERROR: [".$this->lastQuery()."]"); 
                }
            }
            
            self::$sqlCommand = "INSERT INTO ".static::table." (".implode(",",$columnNames).") VALUES (".implode(",",$bindWildCard).")";
            
            self::$conn = Connection::getInstance();   
            $stmt = self::$conn->prepare(static::$sqlCommand);
            if(!empty($stmt)){
                $stmt->execute($columnValues);
                if(Connection::isOracle()){
                    $this->ID = $lastInsertId;
                }else{
                    $this->id = self::$conn->lastInsertId();
                }
                
                return $this;
            }else{
                throw new \Exception("SQL ERROR: [".$this->lastQuery()."]");
            }
        }
        return null;
    }

    public static function all($take=null,$skip=null,$sortBy=null,$sortOrder=null)
    {
        $self = new static;
        static::$sqlCommand = "SELECT * FROM ".static::table;
        
        if(!empty($sortBy) && !empty($sortOrder)){
            static::$sqlCommand .= " ORDER BY `$sortBy` $sortOrder";
        }
        
        if(!empty($take)){
            static::$sqlCommand .= " LIMIT ".$take;
        }
        if(isset($skip)){
            static::$sqlCommand .= " OFFSET ".$skip;
        }
        self::$conn = Connection::getInstance();   
        $stmt = self::$conn->query(static::$sqlCommand);
        
        if(!empty($stmt)){
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_CLASS,  get_class($self));
        }else{
            throw new \Exception("SQL ERROR: [".$this->lastQuery()."]");
        }
    }
    
    public static function query($sqlCommand)
    {
        
        $self = new static;
        static::$sqlCommand = $sqlCommand;
        self::$conn = Connection::getInstance();   
        $stmt = self::$conn->query(static::$sqlCommand);
        if(!empty($stmt)){
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_CLASS,  get_class($self));
        }else{
            throw new \Exception("SQL ERROR: [".$this->lastQuery()."]"); 
        }
    }
    
    public static function where($sqlCommand)
    {
        $self = new static;
        self::$select = null;
        self::$where = new Clause($self,$sqlCommand,self::CLAUSE_WHERE);
        return self::$where;
    }
    
    
    
    public static function select($sqlCommand=null)
    {
        $self = new static;
        if(empty($sqlCommand)){
            $sqlCommand = "*";
        }
        self::$where  = null;
        self::$select = new Clause($self, $sqlCommand, self::CLAUSE_SELECT);
        return self::$select;
    }
    
    public function count()
    {
        static::$sqlCommand = "SELECT COUNT(*) as total FROM ".static::table;
        if(self::$where){
            static::$sqlCommand .= " WHERE ".self::$where;
        }
        self::$conn = Connection::getInstance();   
        $stmt = self::$conn->query(static::$sqlCommand);
        $stmt->execute();
        $stmt->setFetchMode(\PDO::FETCH_ASSOC);
        return $stmt->fetch();
    }
    
    public function delete()
    {
        static::$sqlCommand = "DELETE FROM ".static::table;
        if(!empty($this->id)){
            static::$sqlCommand .= " WHERE id=".$this->id;
        }
        if(self::$where){
            static::$sqlCommand .= " WHERE ".self::$where;
        }
        self::$conn = Connection::getInstance();   
        $stmt = self::$conn->query(static::$sqlCommand);
        $stmt->execute();
        $stmt->setFetchMode(\PDO::FETCH_ASSOC);
        return $stmt->fetch();
    }


    protected function prepareQuery()
    {
        if(empty(self::$select)){
            $select = static::$select = " * ";
            
        }else{
            $select  = static::$select->getSelectClause();
            $where   = static::$select->getWhereClause();
            $orderBy = static::$select->getOrderBy();
            $groupBy = static::$select->getGroupBy();
            $having  = static::$select->getHaving();
        }
        static::$sqlCommand = "SELECT ".$select." FROM ".static::table;
        
        if(!empty(self::$where)){
            $where = static::$where->getWhereClause();
            $orderBy = static::$where->getOrderBy();
            $groupBy = static::$where->getGroupBy();
            $having  = static::$where->getHaving();
        }
        
        if(!empty($where)){
            self::$sqlCommand .= " WHERE ".$where;
        }
        
        if(!empty($groupBy)){
            static::$sqlCommand .= " GROUP BY ".implode(",",$groupBy);
        }
        
        if(!empty($having)){
            static::$sqlCommand .= " HAVING ".$having;
        }
        
        if(!empty($orderBy)){
            static::$sqlCommand .= " ORDER BY ".implode(",",$orderBy);
        }
        
    }
    
    /**
     * 
     * @return string
     */
    public static function lastQuery()
    {
        return static::$sqlCommand;
    }
    
    public function get()
    {
        $this->prepareQuery();
        self::$conn = Connection::getInstance();   
        $stmt = self::$conn->query(static::$sqlCommand);
        if(!empty($stmt)){
            $stmt->execute();
            $stmt->setFetchMode(\PDO::FETCH_CLASS,  get_class($this));
            return $stmt->fetchAll();
        }else{
            throw new \Exception("SQL ERROR: [".$this->lastQuery()."]");
        }
    }
    
    public function first()
    {
        $this->prepareQuery();
        self::$conn = Connection::getInstance();   
        $stmt = self::$conn->query(static::$sqlCommand);
        if(!empty($stmt)){
            $stmt->execute();
            $stmt->setFetchMode(\PDO::FETCH_CLASS,  get_class($this));
            return $stmt->fetch();
        }else{
            throw new \Exception("SQL ERROR: [".$this->lastQuery()."]");
        }
    }
    
    
    
    public function getWhere()
    {
        self::$select = null;
        self::$where = new Clause($this,null,self::CLAUSE_WHERE);
        return self::$where;
    }
    
    /**
     * Use within a model to get data by one to many relation
     * @param string $className
     * @param string $primaryKey
     * @param string $foreignKey
     * @return \Lib\ORM\Relation
     */
    public function hasMany($className,$primaryKey,$foreignKey)
    {
        $relation = new Relation($this);
        $relation->setClass($className);
        $relation->setPrimaryKey($primaryKey);
        $relation->setForeignKey($foreignKey);
        return $relation;
    }
    
    public function hasOne($className,$primaryKey,$foreignKey)
    {
        $relation = new Relation($this);
        $relation->setClass($className);
        $relation->setPrimaryKey($primaryKey);
        $relation->setForeignKey($foreignKey);
        return $relation;
    }

    public function __destruct() {
        self::$conn = null;
    }
    
    
}
