<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class recurring extends CI_Controller {
    
	function __construct()
	{
		parent::__construct();
		$this->load->library('table');
		$tmpl = array (
                    'table_open'          => '<table border="0" cellpadding="2" cellspacing="0" width="100%" style="font-size: 12px">',
                    'row_start'           => '<tr class="trOdd">',
                    'row_alt_start'       => '<tr class="trEven">'
              );
        $this->table->set_template($tmpl); 
	}

	function index(){}
    function income($empid = 0){
        $tag['jscript'] = array('i18n/grid.locale-en', 'jquery.jqGrid.min', 'jquery-ui-custom.min');	    
        $tag['css'] = array('themes/ui.jqgrid', 'themes/ui.multiselect');
        
        $sql = "SELECT * FROM employee WHERE empid={$empid};";
		$employee = $this->db->query($sql)->row();
		$tag['name'] = $employee->lastname.', '.$employee->firstname.' '.$employee->middlename;
        $tag['recurring_income'] = $this->listincome($empid, false);
		$this->common->display("member/recurring/income.html", $tag);
    }

    function listincome($empid = 0, $echo = true){
        $this->load->library('xjqgrid');
        $sql = "SELECT 
                    ri.rincomeid, 
                    ri.empid, 
                    ri.amount, 
                    ri.description, 
                    ri.interval
				FROM recurring_income ri
				LEFT JOIN `interval` i ON i.intervalid=ri.interval
				WHERE ri.empid={$empid};";
		$this->xjqgrid->initGrid( $sql );
        $this->xjqgrid->setMethod('setUrl', "recurring/listincome/{$empid}/1");
        $oper = $_POST['oper'];
        if($oper == 'add'){
            $sql = "INSERT INTO recurring_income(amount, description, `interval`, empid) 
                    VALUES({$_POST['amount']}, '{$_POST['description']}', {$_POST['interval']}, {$empid});";
            $this->db->query($sql);    
        }elseif($oper == 'edit'){
            $sql = "UPDATE recurring_income 
                        SET description='{$_POST['description']}',
                            amount={$_POST['amount']},
                            `interval`={$_POST['interval']} 
                    WHERE rincomeid={$_POST['rincomeid']};";
            $this->db->query($sql);
        }
        
        $this->xjqgrid->setColProperty("rincomeid", 
            array("hidden" => true));
        $this->xjqgrid->setColProperty("empid", 
            array("hidden" => true));
        $this->xjqgrid->setColProperty("amount", 
            array(
                "label"     => "Amount",
                "width"     => "40",
                "sortable"  =>  false,
                "align"     => "right",
                "formatter" => "currency",
                "formatoptions" => array("thousandsSeparator"=>","),
                "editrules" => array(
                    "required"  => true,
                    "number"    => true,
                )));
        $this->xjqgrid->setColProperty("description", 
            array(
                "label"     => "Description",
                "sortable"  =>  false,
                ));
        $this->xjqgrid->setColProperty("interval", 
            array(
                "label"     => "Interval",
                "sortable"  =>  false,
                "width"     => "40",
                "align"     => "center"
                ));
        $this->xjqgrid->setSelect("interval", "SELECT * FROM `interval`", true, true, false );
        $this->xjqgrid->grid->addCol(array( 
                "name"=>"actions", 
                "label"=>"!",
                "formatter"=>"actions", 
                "editable"=>false, 
                "sortable"=>false, 
                "resizable"=>false, 
                "fixed"=>true, 
                "width"=>60,
                "formatoptions"=>array(
                    "keys"=>true
                ) 
                ), "first"); 
        $this->xjqgrid->grid->navigator = true; 
        $this->xjqgrid->grid->setNavOptions('navigator', array(
            "excel" => false,
            "del"   => false,
            "edit"  => false,
            "view"  => false,
            "search"=>false));
        $this->xjqgrid->grid->setGridOptions(array(
            "autowidth"     => true,
            "height"        => 335,
            "hoverrows"     => true,
            "altRows"       => true,
            "rownumbers"    => true, 
            ));
        if(!$echo)
            return $this->xjqgrid->renderGrid('#grid', '#pager', true, null, null, true, true, $echo);
            
        $this->xjqgrid->renderGrid('#grid', '#pager', true, null, null, true, true, $echo);
        $this->xjqgrid->close();
    }

    function deduct($empid = 0){
        $tag['jscript'] = array('i18n/grid.locale-en', 'jquery.jqGrid.min', 'jquery-ui-custom.min');	    
        $tag['css'] = array('themes/ui.jqgrid', 'themes/ui.multiselect');
        
        $sql = "SELECT * FROM employee WHERE empid={$empid};";
		$employee = $this->db->query($sql)->row();
		$tag['name'] = $employee->lastname.', '.$employee->firstname.' '.$employee->middlename;
        $tag['recurring_deduction'] = $this->listdeduct($empid, false);
		$this->common->display("member/recurring/deduction.html", $tag);
    }
    
	function listdeduct($empid = 0, $echo = true){
        $this->load->library('xjqgrid');
        $sql = "SELECT 
                    rd.rdeductid, 
                    rd.empid, 
                    rd.amount, 
                    rd.description, 
                    rd.interval
				FROM recurring_deduction rd
				LEFT JOIN `interval` i ON i.intervalid=rd.interval
				WHERE rd.empid={$empid};";
		$this->xjqgrid->initGrid( $sql );
        $this->xjqgrid->setMethod('setUrl', "recurring/listdeduct/{$empid}/1");
        $oper = $_POST['oper'];
        if($oper == 'add'){
            $sql = "INSERT INTO recurring_deduction(amount, description, `interval`, empid) 
                    VALUES({$_POST['amount']}, '{$_POST['description']}', {$_POST['interval']}, {$empid});";
            $this->db->query($sql);    
        }elseif($oper == 'edit'){
            $sql = "UPDATE recurring_deduction 
                        SET description='{$_POST['description']}',
                            amount={$_POST['amount']},
                            `interval`={$_POST['interval']} 
                    WHERE rdeductid={$_POST['rdeductid']};";
            $this->db->query($sql);
        }
        
        $this->xjqgrid->setColProperty("rdeductid", 
            array("hidden" => true));
        $this->xjqgrid->setColProperty("empid", 
            array("hidden" => true));
        $this->xjqgrid->setColProperty("amount", 
            array(
                "label"     => "Amount",
                "width"     => "40",
                "sortable"  =>  false,
                "align"     => "right",
                "formatter" => "currency",
                "formatoptions" => array("thousandsSeparator"=>","),
                "editrules" => array(
                    "required"  => true,
                    "number"    => true,
                )));
        $this->xjqgrid->setColProperty("description", 
            array(
                "label"     => "Description",
                "sortable"  =>  false,
                ));
        $this->xjqgrid->setColProperty("interval", 
            array(
                "label"     => "Interval",
                "sortable"  =>  false,
                "width"     => "40",
                "align"     => "center"
                ));
        $this->xjqgrid->setSelect("interval", "SELECT * FROM `interval`", true, true, false );
        $this->xjqgrid->grid->addCol(array( 
                "name"=>"actions", 
                "label"=>"!",
                "formatter"=>"actions", 
                "editable"=>false, 
                "sortable"=>false, 
                "resizable"=>false, 
                "fixed"=>true, 
                "width"=>60,
                "formatoptions"=>array(
                    "keys"=>true
                ) 
                ), "first"); 
        $this->xjqgrid->grid->navigator = true; 
        $this->xjqgrid->grid->setNavOptions('navigator', array(
            "excel" => false,
            "del"   => false,
            "edit"  => false,
            "view"  => false,
            "search"=>false));
        $this->xjqgrid->grid->setGridOptions(array(
            "autowidth"     => true,
            "height"        => 335,
            "hoverrows"     => true,
            "altRows"       => true,
            "rownumbers"    => true, 
            ));
        if(!$echo)
            return $this->xjqgrid->renderGrid('#grid', '#pager', true, null, null, true, true, $echo);
            
        $this->xjqgrid->renderGrid('#grid', '#pager', true, null, null, true, true, $echo);
        $this->xjqgrid->close();
    }

	function __deduct($empid=0){
		$sql = "SELECT * FROM employee WHERE empid={$empid};";
		$employee = $this->db->query($sql)->row();
	
		$sql = "SELECT rd.*, i.name as interval_name 
				FROM recurring_deduction rd
				LEFT JOIN `interval` i ON i.intervalid=rd.interval
				WHERE rd.empid={$empid};";
		$recurring = $this->db->query($sql);
		$this->table->clear();
		$this->table->set_heading('Amount', 'Description', 'Interval', '!');
		foreach($recurring->result() as $row)
			$this->table->add_row($row->amount, $row->description, $row->interval_name,
			'<a href="recurring/editdeduct/'.$row->rdeductid.'/'.$empid.'">Edit</a> | <a href="recurring/removededuct/'.$row->rdeductid.'/'.$empid.'">Remove</a>');
		$tag['empid'] = $empid;
		$tag['name'] = $employee->lastname.', '.$employee->firstname.' '.$employee->middlename;
		$tag['recurring_deduction'] = $this->table->generate();
		
		$this->common->display("member/recurring/deduction.html", $tag);
	}
	
}
