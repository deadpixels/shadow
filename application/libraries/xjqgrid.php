<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once "jqgrid/jq-config.php";
require_once "jqgrid/jqGrid.php";
require_once "jqgrid/jqCalendar.php";
require_once "jqgrid/jqGridPdo.php";
class xjqgrid {
    public $grid = null;
    public $con  = null;
    
    function xjqgrid(){
        $this->conn = new PDO(DB_DSN,DB_USER,DB_PASSWORD);        
        //$this->conn->query("SET NAMES utf8");
    }

    function exportCommand($command){
        $this->grid->ExportCommand = $command;
    }
    
    function initGrid($query = ''){
        $this->grid = new jqGridRender($this->conn);
        $this->grid->SelectCommand = $query;
        // set the ouput format to json
        $this->grid->dataType = 'json';
        // Let the grid create the model
        
        $this->grid->setColModel();
    }

    function initLocal($Model){
        $this->grid = new jqGridRender();
        $this->grid->dataType = 'local';
        $this->grid->setColModel($Model);
    }
    
    function renderGrid($tblelement = '', $pager = '', $script = true, $summary = null, $params = null, $createtbl = false, $createpg = false, $echo = true){              
        // Change some property of the field(s)
        /*$this->grid->setColProperty("empid", array("label"=>"ID", "width"=>60));
        $this->grid->setColProperty("birthdate", array(
            "formatter"=>"date",
            "formatoptions"=>array("srcformat"=>"Y-m-d H:i:s","newformat"=>"m/d/Y")
            )
        );*/
        // Enjoy
        return $this->grid->renderGrid($tblelement, $pager, $script, $summary, $params, $createtbl, $createpg, $echo);
        //return $this->grid->renderGrid();
    }
    function close(){
        $this->conn = null;
    }
    function setColProperty($colname, $aproperties){
        $this->grid->setColProperty($colname, $aproperties);
    }
    
    function setMethod($method, $value){
        $this->grid->{$method}($value);
    }
    
    function setProperty($property, $value){
        $this->grid->{$property} = $value;
    }

    function setSelect($colname, $data, $formatter , $editing , $searching , $defvals){
        $this->grid->setSelect($colname, $data, $formatter, $editing, $searching, $defvals);
    }
    
}
