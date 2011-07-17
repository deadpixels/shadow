<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class member extends CI_Controller {
    var $acctid = 0;
    var $ccompany = 0;
	function __construct(){
		parent::__construct();
		$this->common->verify('member');
		$this->sub_nav = array(
            'New Employee'       => 'employee/newemp',
            'Import Employee'    => 'employee/importemp'
        );
        $this->acctid = $_SESSION['acctid'];
		$this->ccompany = $_SESSION['dcompany'];
	}

	function index(){
	    $tag['jscript']     = array('i18n/grid.locale-en', 'jquery', 'jquery.jqChart', 'jquery-ui-custom.min');	    
        $tag['css']         = array('themes/ui.jqgrid', 'themes/ui.multiselect');
	    $tag['__sub_nav__'] = $this->sub_nav;
        $tag['chart']        = $this->showchart();
        $tag['ot_list']      = $this->showunderover();
        $tag['payroll_list'] = $this->showpayroll();
        //dummy data
        $tag['leave_list'] = array(array(
            "name"          => "James Bond",
            "type"          => "Vacation Leave (VL)",
            "detail"        => '06/20/2011 to 06/25/2011',
            "link_approve"  => "javascript:;",
            "link_decline"  => "javascript:;",
            "link_view"     => "javascript:;",
        ), array(
            "name"          => "Adam Savage",
            "type"          => "Vacation Leave (VL)",
            "detail"        => '06/20/2011 to 06/25/2011',
            "link_approve"  => "javascript:;",
            "link_decline"  => "javascript:;",
            "link_view"     => "javascript:;",
        ));

        $tag['sched_list'] = array(array(
            "name"          => "Grant Imahara",
            "type"          => "Monthly Shift",
            "detail"        => '',
            "link_approve"  => "javascript:;",
            "link_decline"  => "javascript:;",
            "link_view"     => "javascript:;",
        ), array(
            "name"          => "Kari Byron",
            "type"          => "Bi-Weekly Shift",
            "detail"        => '',
            "link_approve"  => "javascript:;",
            "link_decline"  => "javascript:;",
            "link_view"     => "javascript:;",
        ));
        
		$this->common->display("member/dashboard.html", $tag);
	}
    
    function newcompany(){
        $this->load->helper('form');
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('<div class="error">', '</div>');
        
        if(isset($_POST['newcompany']) && $this->form_validation->run()){
            $_POST['default'] = ((!isset($_POST['default'])) ? 0 : $_POST['default']);

            if($_POST['default'] == 1){
                //remove all default values
                $sql = "UPDATE company SET `default`=0 WHERE accountid={$this->acctid};";
                $this->db->query( $sql );
            }
            
            $sql = "INSERT INTO company(accountid,name,shortname,industryid,address,phone,email,website,company_size,`default`)
                    VALUES({$this->acctid},'{$_POST['name']}', '{$_POST['shortname']}',{$_POST['industryid']},
                    '{$_POST['address']}','{$_POST['phone']}','{$_POST['email']}','{$_POST['website']}',{$_POST['company_size']},{$_POST['default']})";
            $this->db->query($sql);

            $companyid = $this->db->insert_id();
            
            //insert settings
            $sql  = "INSERT INTO settings(companyid,name,label,value)
                    VALUES({$companyid},'reg_ot','Regular Overtime Rate','1.25');";
            $this->db->query($sql);
            $sql  = "INSERT INTO settings(companyid,name,label,value)
                    VALUES({$companyid},'nightdiff','Night Differential Rate','0.20');";
            $this->db->query($sql);
            $sql  = "INSERT INTO settings(companyid,name,label,value)
                    VALUES({$companyid},'holiday_ot','Holiday Overtime Rate','0.25');";
            $this->db->query($sql);
            $sql  = "INSERT INTO settings(companyid,name,label,value)
                    VALUES({$companyid},'legal_holiday','Legal Holiday Rate','1.00');";
            $this->db->query($sql);
            $sql  = "INSERT INTO settings(companyid,name,label,value)
                    VALUES({$companyid},'special_holiday','Special Holiday Rate','0.30');";
            $this->db->query($sql);
            $sql  = "INSERT INTO settings(companyid,name,label,value)
                    VALUES({$companyid},'maxlate','Maximum Lates','3.00');";
            $this->db->query($sql);

            //emailer
            $sql = "INSERT INTO emailer(companyid,email,password) VALUES({$companyid},'','');";
            $this->db->query($sql);

            
            redirect('member');            
        }else{
            $tag['errors'] = validation_errors();
        }
        //industry
        $sql = "SELECT * FROM industry";
        $query = $this->db->query($sql);
        $industry = array(''=>'[----Select One----]');
        foreach($query->result() as $row){
            $industry[$row->industryid] = $row->name;
        }
        $tag['industry'] = form_dropdown('industryid', $industry);

        //company size
        $sql = "SELECT * FROM company_size";
        $query = $this->db->query($sql);
        $c_size = array(''=>'[----Select One----]');
        foreach($query->result() as $row){
            $c_size[$row->companysizeid] = $row->label;
        }
        $tag['company_size'] = form_dropdown('company_size', $c_size);
        
        $this->common->display("member/newcompany.html", $tag);
    } 	

    function showunderover(){
        //get over time
        $sql = "SELECT * FROM over_time WHERE approved=0 AND companyid={$this->ccompany} LIMIT 0,2";
		$query = $this->db->query($sql);
		$data = array();
		foreach($query->result() as $row){
		    $data[] = array(
                "name"          => $row->filedname,
                "type"          => "Over Time",
                "detail"        => $row->otdate.' '.$row->timein.' to '.$row->timeout,
                "link_approve"  => "javascript:;",
                "link_decline"  => "javascript:;",
                "link_view"     => "javascript:;",
		    );
		}
		$sql = "SELECT * FROM under_time WHERE approved=0 AND companyid={$this->ccompany} LIMIT 0,2";
		$query = $this->db->query($sql);
		foreach($query->result() as $row){
		    $data[] = array(
                "name"          => $row->filedname,
                "type"          => "Under Time",
                "detail"        => $row->otdate.' '.$row->timein.' to '.$row->timeout,
                "link_approve"  => "javascript:;",
                "link_decline"  => "javascript:;",
                "link_view"     => "javascript:;",
		    );
		}
		return $data;
    }

    function showpayroll(){
        $sql = "SELECT
                payrollid, 
                DATE_FORMAT(payperiod_from, '%m/%d/%Y') as pay_from,
                DATE_FORMAT(payperiod_to, '%m/%d/%Y') as pay_to,
                remark
                FROM payroll WHERE approved=0 and active=1 and companyid={$this->ccompany} ORDER BY payperiod_from DESC LIMIT 4;";
        $query = $this->db->query($sql);
		$data = array();
		foreach($query->result() as $row){
		    $data[] = array(
                "name"          => $row->pay_from.' to '.$row->pay_to,
                "type"          => "Awaiting Approval",
                "detail"        => $row->remark,
                "link_approve"  => "javascript:;",
                "link_decline"  => "javascript:;",
                "link_view"     => "payroll/edit/".$row->payrollid,
		    );
		}
		return $data; 
    }
    
	function showchart(){
        $this->load->library('xjqchart');
        $this->xjqchart->initChart();
        $this->xjqchart->setChartOptions(array(
            "defaultSeriesType" => "column",
            "className" => "dashbox"
        )); 
        $this->xjqchart->setColors(array('#00a3d3'));
        $this->xjqchart->setTooltip(array( 
                "formatter"=>"function(){return '<b>'+ this.x +'</b> <br/>'+this.series.name+': '+ Highcharts.numberFormat(this.y, 2);}" 
            ));
        $this->xjqchart->chart->setExporting(array("enabled"=>false));
        $this->xjqchart->chart->setLegend(array('enabled'=> false));
        $this->xjqchart->setTitle(array('text'=>'Monthly Average'));
        $this->xjqchart->setxAxis(array("categories"=>array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec')));
        $this->xjqchart->setyAxis(array( "min"=>0, "title"=>array("text"=>"")));
        $this->xjqchart->addSeries('Salary', array(43500, 24300, 85647, 35627, 54627, 32623, 46352, 54636));
        return $this->xjqchart->renderChart();
	}
}
