<?php
/* 
Convenience classes for manipulating a MySQL-based Database 
Copyright (C) 2014 Fioratto Raffaele 
Version:    2.0
Date:       01/09/2014
Email:      raffaele.fioratto@gmail.com

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

class DatabaseQueryLogicExpressionNode {
    private $childrenNodes = array();
    private $parentNode = NULL;
    private $type = NULL;
    private $data = NULL;
    
    const NODE_AND = 1;
    const NODE_OR = 2;
    const NODE_PARS = 3;
    const LEAF = 4;
    
    public function __construct($type) {
        $this->type = $type;
    }
    
    public function setChildren($childrenNodes) {
        $this->childrenNodes = $childrenNodes;
    }
    
    public function getChildren() {
        return $this->childrenNodes;
    }
    
    public function getChild($index = 0) {
        return $this->childrenNodes[$index];
    }
    
    public function appendChild($childNode) {
        array_push($this->childrenNodes, $childNode);
    }
    
    public function setData($data) {
        $this->data = $data;
    }
    
    public function getData() {
        return $this->data;
    }
    
    public function setParent($parent) {
        $this->parentNode = $parent;
    }
    
    public function getType() {
        return $this->type;
    }
}

class DatabaseQueryLogicExpression {
    
    private $dbo = NULL;
    private $rootNode = NULL;
    
    public function __construct($dbo) {
        $this->dbo = $dbo;
    }
    
    public function construct($node) {
        $this->rootNode = $node;
    }
    
    public function _and() {
        $clauses = func_get_args();
        /*return implode($clauses, " AND ");*/
        $node = new DatabaseQueryLogicExpressionNode(NODE_AND);
        $node->setChildren($clauses);
        return $node;
    }
    
    public function _or() {
        $clauses = func_get_args();
        /*return implode($clauses, " OR ");*/
        $node = new DatabaseQueryLogicExpressionNode(NODE_OR);
        $node->setChildren($clauses);
        return $node;
    }
    
    public function pars($clause) {
       // return "($clause)";
        $node = new DatabaseQueryLogicExpressionNode(NODE_PARS);
        $node->appendChild($clause);
        return $node;
    }
    
    public function eq($field, $value) {
        return $this->__createClause($field, $value, "=");
    }
    
    public function lt($field, $value) {
        return $this->__createClause($field, $value, "<");
    }
    
    public function lte($field, $value) {
        return $this->__createClause($field, $value, "<=");
    }
    
    public function gt($field, $value) {
        return $this->__createClause($field, $value, ">");
    }
    
    public function gte($field, $value) {
        return $this->__createClause($field, $value, ">=");
    }
    
    public function like($field, $value) {
        return $this->__createClause($field, $value, "LIKE");
    }
    
    private function __createClause($field, $value, $op) {
        $clause = ($this->dbo->getQuoteStrings() ? $this->dbo->quoteName($field) : $field) . $op;
        switch(gettype($value)) {
            case 'string': 
                $clause .= $this->dbo->quote($value);
                break;
            default:
                $clause .= $value;
        }
        $node = new DatabaseQueryLogicExpressionNode(LEAF);
        $node->setData($clause);
        return $node;
    }
    
    public function toString() {
        return $this->__processNode($this->rootNode);
    }
    
    private function __processNode($node) {
        switch($node->getType()) {
            case LEAF:
                return $node->getData();
            case NODE_AND: {
                $clauses = array();
                foreach($node->getChildren() as $child) {
                    array_push($clauses, $this->__processNode($child));
                }
                return implode($clauses, " AND ");
            }
            case NODE_OR: {
                $clauses = array();
                foreach($node->getChildren() as $child) {
                    array_push($clauses, $this->__processNode($child));
                }
                return implode($clauses, " OR ");
            }
            case NODE_PARS: {
                return "(" . $this->__processNode($node->getChild()) . ")";
            }
        }
    }
}

class DatabaseQuery {
    private $queryType = NULL;
    
    // SELECT FIELDS 
    private $fieldsName = array();
    private $tables = array();
    private $whereClauses = array();
    private $orderFields = array();
    
    // INSERT FIELDS
    private $insertValues = array();
    
    private $dbo = NULL;
    public  $result = NULL;
    public  $executed;
    
    public function __construct($dbo) {
        $this->dbo = $dbo;
        $this->executed = false;
    }
    
    public function select($fields) {
        $this->queryType = "SELECT";
        switch (gettype($fields)) {
            case "string":
                array_push($this->fieldsName, ($this->dbo->getQuoteStrings() ? $this->dbo->quoteName($fields) : $fields));
                break;
            case "array":
                $this->fieldsName = array_merge($this->fieldsName, ($this->dbo->getQuoteStrings() ? array_map(array($this->dbo, 'quoteName'), $fields) : $fields));
        }
        return $this;
    }
    
    public function from($table) {
        $this->tables[] = ($this->dbo->getQuoteStrings() ? $this->dbo->quoteName($table) : $table);
        return $this;
    }
    
    public function where($clauses, $needsEscape = false, $glue = 'AND') {
        /*switch (gettype($clauses)) {
            case "string":
                $this->whereClauses .= $clauses . " ";
                break;
            case "array":
                $this->whereClauses .= implode(" " . $glue . " ", $clauses) . " ";
                break;
        }
        return $this;*/
        switch (gettype($clauses)) {
            case "string":
                $this->whereClauses[$glue][] = ($needsEscape ? $this->__escapeClause($clauses) : $clauses); 
                break;
            case "array":
                $this->whereClauses[$glue] = array_merge($this->whereClauses[$glue], $needsEscape ? array_map(array($this, '__escapeClause'), $clauses) : $clauses);
                break;
        }
        return $this;
    }
    
    public function order($fields, $type = 'ASC') {
        switch (gettype($fields)) {
            case "string":
                array_push($this->orderFields, ($this->dbo->getQuoteStrings() ? $this->dbo->quoteName($fields) : $fields) . " " .$type);
                break;
            case "array":
                $qotedFields = ($this->dbo->getQuoteStrings() ? array_map(array($this->dbo, 'quoteName'), $fields) : $fields);
                $this->orderFields = array_merge($this->orderFields, array_map(function($field) use(&$type) { return $field . " " . $type; }, $fields));
                break;
        }
        $this->orderType = $type; 
        return $this;
    }
    
    public function insert($table) {
        $this->queryType = "INSERT INTO";
        $this->table = ($this->dbo->getQuoteStrings() ? $this->dbo->quoteName($table) : $table);
        return $this;
    }
    
    public function columns($columns_array) {
        $this->fieldsName = array_merge($this->fieldsName, ($this->dbo->getQuoteStrings() ? array_map(array($this->dbo, 'quoteName'), $columns_array) : $columns_array));
        return $this;
    }
    
    public function values($values_array) {
        $this->insertValues = array_merge($this->insertValues, array_map(array($this->dbo, 'quote'), $values_array));
        return $this;
    }
    
    public function update($table) {
        $this->queryType = "UPDATE";
        $this->table = ($this->dbo->getQuoteStrings() ? $this->dbo->quoteName($table) : $table);
        return $this;
    }
    
    public function set($values_array) {
        $this->insertValues = array_merge($this->insertValues, $values_array);
        return $this;
    }
    
    public function delete($table) {
        $this->queryType = "DELETE";
        $this->table = ($this->dbo->getQuoteStrings() ? $this->dbo->quoteName($table) : $table);
        return $this;
    }
    
    public function toString() {
        $queryStr = $this->queryType . " ";
        switch($this->queryType) {
            case "SELECT":
                $queryStr .= implode(",", $this->fieldsName);
                $queryStr .= " FROM " . implode($this->tables, ",");
                if (false === empty($this->whereClauses)) {
                    $queryStr .= " WHERE " . implode(' AND ', $this->whereClauses['AND']);
                }
                if (false === empty($this->orderFields)) {
                    $queryStr .= " ORDER BY " . implode(",", $this->orderFields);
                }
                return $queryStr;
            case "INSERT INTO":
                $queryStr .= "$this->table ";
                if (!empty($this->fieldsName)) {
                    $queryStr .= "(" . implode(",", $this->fieldsName) . ") ";
                }
                if (isset($this->insertValues)) {
                    $queryStr .= "VALUES (" . implode(",", $this->insertValues) . ") ";
                }
                return $queryStr;
            case "UPDATE":
                $queryStr .= $this->table;
                if (isset($this->insertValues)) {
                    $queryStr .= " SET " . implode(",", $this->insertValues);
                }
                if (false === empty($this->whereClauses)) {
                    $queryStr .= " WHERE " . implode(' AND ', $this->whereClauses['AND']);
                }
                return $queryStr;
            case "DELETE":
                $queryStr .= " FROM {$this->table}";
                if (false === empty($this->whereClauses)) {
                    $queryStr .= " WHERE " . implode(' AND ', $this->whereClauses['AND']);
                }
                return $queryStr;
            default:
                die("Query type error!");
        }
    }
    
    public function getClauseConstructor() {
        return new DatabaseQueryLogicExpression($this->dbo);
    }
    
    public function eq($field, $value) {
        return $this->__createClause($field, $value, "=");
    }
    
    public function lt($field, $value) {
        return $this->__createClause($field, $value, "<");
    }
    
    public function lte($field, $value) {
        return $this->__createClause($field, $value, "<=");
    }
    
    public function gt($field, $value) {
        return $this->__createClause($field, $value, ">");
    }
    
    public function gte($field, $value) {
        return $this->__createClause($field, $value, ">=");
    }
    
    public function like($field, $value) {
        return $this->__createClause($field, $value, "LIKE");
    }
    
    private function __escapeClause($clause) {
        $tokens = preg_split("/[=|<|>]+/", $clause, -1, PREG_SPLIT_OFFSET_CAPTURE);
        $clauseStr = ($this->dbo->getQuoteStrings() ? $this->dbo->quoteName($tokens[0][0]) : $tokens[0][0]);
        $column_end_offs = $tokens[0][1] + strlen($tokens[0][0]);
        $clauseStr .= substr($clause, $column_end_offs, $tokens[1][1] - $column_end_offs);
        $num = $tokens[1][0] + 0;
        if (("$num" == $tokens[1][0])) {
           $clauseStr .= $num;
        } else {
           $clauseStr .= $this->dbo->quote($tokens[1][0]);
        }
        return $clauseStr;
    }
    
    private function __createClause($field, $value, $op) {
        $clause = ($this->dbo->getQuoteStrings() ? $this->dbo->quoteName($field) : $field) . $op;
        switch(gettype($value)) {
            case 'string': 
                $clause .= $this->dbo->quote($value);
                break;
            default:
                $clause .= $value;
        }
        return $clause;
    }
}

class DatabaseDriver {
  
    private $queryObj = NULL;
    private $quoteStrings = true;
    private $mysqliObj = NULL;
    private $numQuery;
    
    public function __construct($host, $user, $pass, $db) {
        $this->mysqliObj = new mysqli($host, $user, $pass, $db);
        if (mysqli_connect_error()) {
            die('Connect Error(' . mysqli_connect_errno() . ') ' . mysql_connect_error());
        }
        $this->numQuery = 0;
    }
    
    public function getQuery($new = false) {
        if (true === $new)
            return new DatabaseQuery($this);
        return $this->queryObj;
    }
    
    public function setQuery($query) {
        $this->queryObj = $query;
    }
    
    public function execQuery() {
        return $this->__execQuery();
    }
    
    public function setQuoteStrings($quotes = true) {
        $this->quoteStrings = $quotes;
    }
    
    public function getQuoteStrings() {
        return $this->quoteStrings;
    }
    
    public function loadResult() {
        if (false === $this->__checkQueryObj())
            return NULL;
         
        return $this->queryObj->result->fetch_row()[0];
    }
    
    public function loadRow() {
        if (false === $this->__checkQueryObj())
            return NULL;
         
        return $this->queryObj->result->fetch_row();
    }
    
    public function loadAssoc() {
        if (false === $this->__checkQueryObj())
            return NULL;
         
        return $this->queryObj->result->fetch_assoc();
    }
    
    public function loadObject() {
        if (false === $this->__checkQueryObj())
            return NULL;
         
        return $this->queryObj->result->fetch_object();
    }
    
    public function loadRowList() {
        if (false === $this->__checkQueryObj())
            return NULL;
        while(($row = $this->queryObj->result->fetch_row()) !== NULL)
            $rowList[] = $row;
    
        return $rowList;
    }
    
    public function loadAssocList($key = NULL) {
        if (false === $this->__checkQueryObj())
            return NULL;
        while(($row = $this->queryObj->result->fetch_assoc()) !== NULL)
            if ((true === isset($key)) && (true === array_key_exists($key, $row)))
                $assocList[$row[$key]] = $row;
            else 
                $assocList[] = $row;
    
        return $assocList;
    }
    
    public function loadObjectList($key = NULL) {
        if (false === $this->__checkQueryObj())
            return NULL;
        while(($row = $this->queryObj->result->fetch_object()) !== NULL)
            if ((true === isset($key)) && (true === isset($row->$key)))
                $assocList[$row->$key] = $row;
            else 
                $assocList[] = $row;
    
        return $assocList;
    }
    
    public function quoteName($name, $alias = NULL) {
        $name = trim($name);
        if (("string" == gettype($name)) && 
                ("`" !== substr($name, 0, 1)) && ("`" !== substr($name, -1, 1)))
            $field = "`" . $this->mysqliObj->real_escape_string($name) . "`";
        else
            $field = $name;
        
        if (isset($alias)) {
            if (("string" == gettype($alias)) && 
                ("`" !== substr($alias, 0, 1)) && ("`" !== substr($alias, -1, 1)))
            $field .= "AS `" . $alias . "`";
        else
            $field .= "AS $alias";
        }
        return $field;
    }
    
    public function quote($value, $real_escape = true) {
        $value = trim($value);
        if (("string" == gettype($value)) && 
                ("'" !== substr($value, 0, 1)) && ("'" !== substr($value, -1, 1)))
            return "'" . ($real_escape ? $this->mysqliObj->real_escape_string($value) : $value) . "'";
        else
            return $value;
    }
    
    public function insertAssoc($table, $assoc) {
        if (false === $this->__isAssoc($assoc))
            return NULL;

        $query = new DatabaseQuery($this);
        $query->insert($table)->columns(array_keys($assoc))->values(array_values($assoc));
        $this->queryObj = $query;
        
        return $this->__execQuery();     
    }
    
    public function insertArray($table, $array) {
        if (true === $this->__isAssoc($array))
            return $this->insertAssoc($table, $array);
        
        $query = new DatabaseQuery($this);
        $query->insert($table)->values(array_values($array));
        $this->queryObj = $query;
        
        return $this->__execQuery();
    }
    
    public function insertObject($table, $object) {
        return $this->insertAssoc($table, get_object_vars($object));
    }
    
    public function updateAssoc($table, $assoc, $keys = NULL) {
         if (false === $this->__isAssoc($assoc))
             return NULL;
        
        $query = new DatabaseQuery($this);
        $fields = $this->__toSetFieldsArray($assoc);
        $clauses = NULL;  
        $query->update($table)->set($fields);
        if (isset($keys)) {
            switch(gettype($keys)) {
                case "string":
                    if (true === array_key_exists($keys, $assoc))
                        $clauses = $query->eq($keys, $assoc[$keys]);
                    break;
                case "array":
                    $clauses = array();
                    foreach($keys as $key) {
                        if (true === array_key_exists($key, $assoc))
                            $clauses[] = $query->eq($key, $assoc[$key]); 
                    }
                    break;
            }
            $query->where($clauses);
        }  
        $this->queryObj = $query;
        
        return $this->__execQuery();
    }
    
    public function updateObject($table, $object, $keys = NULL) {
        return $this->updateAssoc($table, get_object_vars($object), $keys);
    }
    
    /*public function whereClause($column, $op, $value) {
        $clause = ($this->getQuoteStrings() ? $this->quoteName($column) : $column);
        $clause .= $op;
        switch (gettype($value))
        {
            case "string":
                $clause .= $this->quote($value);
                break;
            default:
                $clause .= $value;
        }
        return $clause;
    }*/
    
    public function getCount() {
        return $this->numQuery;
    }
    
    public function getAffectedRows() {
        return $this->mysqliObj->affected_rows;
    }
    
    public function getNumRows() {
        if ((false === isset($this->queryObj)) && (false === $this->queryObj->executed))
            return 0;
        
        return $this->queryObj->num_rows;
    }
    
    public function setUTF() {
        return $this->mysqliObj->query("set names utf8");
    }
    
    public function insertid() {
        return $this->mysqliObj->insert_id;
    }
    
    public function getTableList() {
        $res = $this->mysqliObj->query("show tables");
        while(($row = $res->fetch_row()) !== NULL)
            $tables[] = $row[0];
        return $tables;
    }
    
    public function getTableColumns($table, $typeOnly = true) {
        $res = $this->mysqliObj->query("describe " . ($this->getQuoteStrings() ? $this->quoteName($table) : $table));
        while(($row = $res->fetch_row()) !== NULL)
            $columns[] = ($typeOnly ? $row[1] : $row);
        return $columns;
    }
    
    public function disconnect() {
        $this->mysqliObj->close();
    }
    
    private function __checkQueryObj() {
        if (false === isset($this->queryObj))
            return false;
        
        if (false === $this->queryObj->executed)
            return $this->__execQuery(); 
    }
    
    private function __execQuery() {
        $res = $this->mysqliObj->query($this->queryObj->toString()) or die('Query error(' . $this->mysqliObj->errno . ') ' . $this->mysqliObj->error);
        $this->queryObj->result = $res;
        $this->queryObj->executed = true;
        
        $this->numQuery++;
        
        return $res;
    }
    
    private function __isAssoc($array) {
        return (array_values($array) !== $array);
    }
    
    private function __toSetFieldsArray($assoc) {
        $seq_array = array();
        foreach($assoc as $column => $value) {
            array_push($seq_array, ($this->getQuoteStrings() ? $this->quoteName($column) : $column) . "=" . $this->quote($value));
        }
        return $seq_array;
    }
}

?>