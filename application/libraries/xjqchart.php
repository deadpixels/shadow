<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once "jqgrid/jq-config.php";
require_once "jqgrid/jqUtils.php";
require_once "jqgrid/jqChart.php";
//require_once "jqgrid/jqGridPdo.php";

class xjqchart {
    public $chart = null;
    public $conn  = null;
    
    function xjqchart(){
        //$this->conn = new PDO(DB_DSN,DB_USER,DB_PASSWORD);        
    } 

    function initChart(){
        $this->chart = new jqChart();
    }

    function setChartOptions($name, $mixvalue = ''){
        $this->chart->setChartOptions($name, $mixvalue);
    }

    function setColors( $avalue ){
        $this->chart->setColors( $avalue );
    }
    
    function setTitle( $name, $mixvalue = ''){
        $this->chart->setTitle( $name, $mixvalue );
    }

    function setxAxis($name, $mixvalue = ''){
        $this->chart->setxAxis($name, $mixvalue);
    }

    function setyAxis($name, $mixvalue = ''){
        $this->chart->setyAxis($name, $mixvalue);
    }
    
    function setTooltip($name, $mixvalue = ''){
        $this->chart->setTooltip($name, $mixvalue);
    }

    function addSeries( $name, $value, $params = null, $limit = false, $offset = 0){
        $this->chart->addSeries($name, $value, $params, $limit, $offset);
    }
    
    function renderChart($width=460, $height=250){
        return $this->chart->renderChart('', true, $width, $height); 
    }
    
}
