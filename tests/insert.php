<?php

class InsertTestCase extends TestCase {
    private $dbo;
    public function SetUp() {
        $this->dbo = new DatabaseDriver('localhost', 'testuser', 'jEv5HWz9Wf726WKD', 'testuser');
    }
    
    public function Run() {
        $query = $this->dbo->getQuery(true);
        $columns = array('name', 'surname', 'telephone');
        $values = array('Mickey', 'Mouse', '353532662');
        $query->insert('test_table')->columns($columns)->values($values);
        $this->dbo->setQuery($query);
        
        $this->assertEquals($this->dbo->execQuery(), true);
        $lastId = $this->dbo->insertid();
        $this->assertEquals($this->dbo->getAffectedRows(), 1);
        
        $query = $this->dbo->getQuery(true);
        $query->select($columns)->from('test_table')->where("id=$lastId");
        $this->dbo->setQuery($query);
        $result = $this->dbo->loadRow();
        
        $this->assertEquals($result, $values);
        
        
        $row = array("name" => "Donald", "surname" => "Duck", "telephone" => "36464474"); 
        
        $this->assertEquals($this->dbo->insertAssoc('test_table', $row), true);
        $lastId = $this->dbo->insertid();
        $this->assertEquals($this->dbo->getAffectedRows(), 1);
        
        $query = $this->dbo->getQuery(true);
        $query->select($columns)->from('test_table')->where("id=$lastId");
        $this->dbo->setQuery($query);
        $result = $this->dbo->loadRow();
        
        $this->assertEquals($result, array_values($row));
        
        
        $row = new stdClass(); 
        $row->name = "Bugs";
        $row->surname = "Bunny";
        $row->telephone = "689645412"; 
        
        $this->assertEquals($this->dbo->insertObject('test_table', $row), true);
        $lastId = $this->dbo->insertid();
        $this->assertEquals($this->dbo->getAffectedRows(), 1);
        
        $query = $this->dbo->getQuery(true);
        $query->select($columns)->from('test_table')->where("id=$lastId");
        $this->dbo->setQuery($query);
        $result = $this->dbo->loadRow();
        
        $this->assertEquals($result, array_values(get_object_vars($row)));
        
        
        $row = array('NULL', 'Yosemite', 'Sam', '7595906487');
        
        $this->assertEquals($this->dbo->insertArray('test_table', $row), true);
        $lastId = $this->dbo->insertid();
        $this->assertEquals($this->dbo->getAffectedRows(), 1);
        
        $query = $this->dbo->getQuery(true);
        $query->select($columns)->from('test_table')->where("id=$lastId");
        $this->dbo->setQuery($query);
        $result = $this->dbo->loadRow();
        
        $this->assertEquals($result, array_slice($row, 1));
    }
    
    public function TearDown() {
        $this->dbo->disconnect();
    }
}