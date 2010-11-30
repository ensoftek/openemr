<?php

require_once("ORDataObject.class.php");

/**
 * class Laboratory
 *
 */
class Laboratory extends ORDataObject {

    var $id;
    var $laboratory_name;

    /**
     * Constructor sets all Laboratory attributes to their default value
     */
    function Laboratory($id = "", $prefix = "") {
        $this->id = $id;
        $this->laboratory_name = "";
        $this->_table = "laboratories";

        if ($this->id != "") {
            $this->populate();
        }
    }

    function set_id($id = "") {
        $this->id = $id;
    }

    function get_id() {
        return $this->id;
    }

    function set_laboratory_name($laboratory_name) {
        $this->laboratory_name = $laboratory_name;
    }

    function get_laboratory_name() {
        return $this->laboratory_name;
    }

    function set_name($laboratory_name) {
        echo "called set_name()<br>";
        $this->laboratory_name = $laboratory_name;
    }

    function get_name() {
        echo "called get_name()<br>";
        return $this->laboratory_name;
    }

    function populate() {
        parent::populate();
    }

    function persist() {
        parent::persist();
    }

    function laboratories_factory($city = "", $sort = "ORDER BY laboratory_name, id") {
        $p = new Laboratory();
        $ilaboratories = array();

        $sql = "SELECT id, laboratory_name FROM " . $p->_table . " " . mysql_real_escape_string($sort);

        //echo $sql . "<bR />";
        $results = sqlQ($sql);
        //echo "sql: $sql";
        //print_r($results);
        while ($row = mysql_fetch_array($results)) {
            $ilaboratories[] = new Laboratory($row['id']);
        }

        return $ilaboratories;
    }
}
?>
