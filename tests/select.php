<?php

require_once ('database.php');

class stdClassEquality extends stdClass implements Equality {
    public function Equals($obj) {
        return (get_object_vars($this) == get_object_vars($obj));
    }
}

class SelectTestCase extends TestCase {
    private $dbo;
    public function SetUp() {
        $this->dbo = new DatabaseDriver('localhost', 'testuser', 'jEv5HWz9Wf726WKD', 'testuser');
    }
    
    public function Run() {
        $query = $this->dbo->getQuery(true);
        $query->select('surname')->from('test_table')->where($this->dbo->whereClause('id',"=",2));
        $this->dbo->setQuery($query);
        $result = $this->dbo->loadResult();
        
        $this->assertEquals($result, "Doe");
        
        
        $query = $this->dbo->getQuery(true);
        $query->select('name')->select('surname')->from('test_table')->where($this->dbo->whereClause('id',"=",2));
        $this->dbo->setQuery($query);
        $result = $this->dbo->loadRow();
        
        $this->assertEquals($result, array("John", "Doe"), 'Simple chaining fields with ->select');
        
        $query = $this->dbo->getQuery(true);
        $query->select('name')->select('surname')->select('telephone')->from('test_table')->where($this->dbo->whereClause('id',"=",2));
        $this->dbo->setQuery($query);
        $result = $this->dbo->loadRow();
        
        $this->assertEquals($result, array("John", "Doe", "987654321"), 'Double chaining ->select()->select()');
        
        $query = $this->dbo->getQuery(true);
        $query->select('name')->select(array('surname', 'telephone'))->from('test_table')->where($this->dbo->whereClause('id',"=",2));
        $this->dbo->setQuery($query);
        $result = $this->dbo->loadRow();
        
        $this->assertEquals($result, array("John", "Doe", "987654321"), 'Select field chained with ->select(array())');
        
        
        $query = $this->dbo->getQuery(true);
        $query->select(array('name', 'surname'))->from('test_table')->where($this->dbo->whereClause('id',"=",2));
        $this->dbo->setQuery($query);
        $result = $this->dbo->loadAssoc();
        
        $this->assertEquals($result, array("name" => "John", "surname" => "Doe"));
        
        
        $query = $this->dbo->getQuery(true);
        $query->select(array('name', 'surname'))->from('test_table')->where($this->dbo->whereClause('id',"=",2));
        $this->dbo->setQuery($query);
        $result = $this->dbo->loadObject();
        
        $actual = new stdClassEquality();
        $actual->name = "John";
        $actual->surname = "Doe";
        
        $this->assertEquals($result, $actual);
        
        $query = $this->dbo->getQuery(true);
        $query->select(array('name', 'surname'))->from('test_table')->where($this->dbo->whereClause('id',"<",5));
        $this->dbo->setQuery($query);
        $result = $this->dbo->loadRowList();
        
        $actual = array( array("John", "Smith"), array("John", "Doe"), array("Jane", "Doe"), array("Peter", "Parker") );
        
        $this->assertEquals($this->dbo->getAffectedRows(), 4);
        $this->assertEquals($result, $actual);
        
        
        $query = $this->dbo->getQuery(true);
        $query->select(array('name', 'surname'))->from('test_table')->where($this->dbo->whereClause('id',"<",5));
        $this->dbo->setQuery($query);
        $result = $this->dbo->loadAssocList();
        
        $actual = array( 
            array("name" => "John", "surname" => "Smith"), 
            array("name" => "John", "surname" => "Doe"), 
            array("name" => "Jane", "surname" => "Doe"), 
            array("name" => "Peter", "surname" => "Parker") );
        
        $this->assertEquals($this->dbo->getAffectedRows(), 4);
        $this->assertEquals($result, $actual);
        
        
        $query = $this->dbo->getQuery(true);
        $query->select(array('name', 'surname'))->from('test_table')->where($this->dbo->whereClause('id',"<",5));
        $this->dbo->setQuery($query);
        $result = $this->dbo->loadObjectList();
        
        $obj1 = new stdClassEquality();
        $obj1->name = "John";
        $obj1->surname = "Smith";
        $obj2 = new stdClassEquality();
        $obj2->name = "John";
        $obj2->surname = "Doe";
        $obj3 = new stdClassEquality();
        $obj3->name = "Jane";
        $obj3->surname = "Doe";
        $obj4 = new stdClassEquality();
        $obj4->name = "Peter";
        $obj4->surname = "Parker";
        
        $actual = array( $obj1, $obj2, $obj3, $obj4 );
        
        $this->assertEquals($this->dbo->getAffectedRows(), 4);
        $this->assertEquals($result, $actual);
        
        
        $query = $this->dbo->getQuery(true);
        $query->select('id')->select(array('name', 'surname'))->from('test_table')->where($this->dbo->whereClause('id',"<",5));
        $this->dbo->setQuery($query);
        $result = $this->dbo->loadAssocList('id');
        
        $actual = array( 
            "1" => array("id" => "1", "name" => "John", "surname" => "Smith"), 
            "2" => array("id" => "2", "name" => "John", "surname" => "Doe"), 
            "3" => array("id" => "3", "name" => "Jane", "surname" => "Doe"), 
            "4" => array("id" => "4", "name" => "Peter", "surname" => "Parker") );
        
        $this->assertEquals($this->dbo->getAffectedRows(), 4);
        $this->assertEquals($result, $actual, 'Select and loading assocList indexed with the \'id\' key');
        
        
        $query = $this->dbo->getQuery(true);
        $query->select('id')->select(array('name', 'surname'))->from('test_table')->where($this->dbo->whereClause('id',"<",5));
        $this->dbo->setQuery($query);
        $result = $this->dbo->loadObjectList('id');
        
        $obj1 = new stdClassEquality();
        $obj1->id = "1";
        $obj1->name = "John";
        $obj1->surname = "Smith";
        $obj2 = new stdClassEquality();
        $obj2->id = "2";
        $obj2->name = "John";
        $obj2->surname = "Doe";
        $obj3 = new stdClassEquality();
        $obj3->id = "3";
        $obj3->name = "Jane";
        $obj3->surname = "Doe";
        $obj4 = new stdClassEquality();
        $obj4->id = "4";
        $obj4->name = "Peter";
        $obj4->surname = "Parker";
        
        $actual = array( 
            "1" => $obj1, 
            "2" => $obj2, 
            "3" => $obj3, 
            "4" => $obj4 );
        
        $this->assertEquals($this->dbo->getAffectedRows(), 4);
        $this->assertEquals($result, $actual, 'Select and loading objectList indexed with the \'id\' key');
    }
    
    public function TearDown() {
    }
}

?>