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
        
        $query = $this->dbo->getQuery(true);
        $query->select($columns)->from('test_table')->where($this->dbo->whereClause('id',"=",$lastId));
        $this->dbo->setQuery($query);
        $result = $this->dbo->loadRow();
        
        $this->assertEquals($result, $values);
    }
    
    public function TearDown() {
        $this->dbo->disconnect();
    }
}