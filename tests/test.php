<?php
require_once ('../src/database.php');
$db = new DatabaseDriver('localhost', 'testuser', 'jEv5HWz9Wf726WKD', 'testuser');

$query = $db->getQuery(true);
$query->select(array('name', 'surname'))->from('test_table')->where('latitude=128.3456');
echo $query->toString() . "\n";
$db->setQuery($query);
var_dump($db->loadObjectList());
echo $db->getAffectedRows() . "\n";
var_dump($db->getTableList());
/*var_dump($db->getTableColumns('test_table'));
var_dump($db->getTableColumns('test_table', false));
/*
$fields = array(
    $db->quoteName('name') . "=" . $db->quote("Lollo"),
    $db->quoteName('surname'). "=" . $db->quote("Lollissimo")
);

$query = $db->getQuery(true);
$query->update('test_table')->set($fields)->where($db->whereClause('id',"=",39));
$db->setQuery($query);
echo $db->execQuery();
*//*
$clauses = array(
    $db->quoteName('id') . "=" . 39,
    $db->quoteName('surname'). "=" . $db->quote("Lollissimo")
);

$query = $db->getQuery(true);
$query->delete('test_table')->where($clauses);
$db->setQuery($query);
echo $db->execQuery();
*/
/*
$lol = new stdClass();
$lol->id = 38;
$lol->name = "John";
$lol->surname = "Petrucci";
$lol->telephone = "3579359";
echo $db->updateObject('test_table', $lol, array('id', 'telephone'));
*/
//echo $objList[0]->name . ", " . $objList[0]->surname . "<br/>";
/*
$query = $db->getQuery(true);
$query->insert('test_table')->columns(array('name', 'surname', 'telephone'))->values(array('Bugs', 'Bunny', '5352353535'));

$db->setQuery($query);
echo $db->execQuery();

$cartoon = array("name" => "Duffy", "surname" => "Duck", "telephone" => "3579359");
echo $db->insertAssoc('test_table', $cartoon);

$cart = new stdClass();
$cart->name = "Paperon";
$cart->surname = "De Paperoni";
$cart->telephone = "4u96694y4694";
echo $db->insertObject('test_table', $cart);

$abc = array('NULL', "Pico", "De Paperis", "75945795");
echo $db->insertArray('test_table', $abc);*/
?>