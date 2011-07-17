<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class payroll extends CI_Controller {
    var $sub_nav;
    var $acctid = 0;
    var $ccompany = 0;
	function __construct(){
		parent::__construct();
		$this->common->verify();
		$this->acctid = $_SESSION['acctid'];
		$this->ccompany = $_SESSION['dcompany'];
		$this->sub_nav = array(
		    'List'      =>  'payroll/',
            'New'       =>  'payroll/newpayroll',
            'Options'   =>  'payroll/option'
        );
        $this->load->library('table');
		$tmpl = array (
                    'table_open'          => '<table border="0" cellpadding="4" cellspacing="0" width="100%" style="font-size: 12px">',
                    'row_start'           => '<tr class="tr_odd">',
                    'row_alt_start'       => '<tr class="tr_even">'
              );
        $this->table->set_template($tmpl); 
	}

	function index(){
	    $tag['jscript'] = array('i18n/grid.locale-en', 'jquery.jqGrid.min', 'jquery-ui-custom.min');	    
        $tag['css'] = array('themes/ui.jqgrid', 'themes/ui.multiselect');
        
        $tag['__sub_nav__'] = $this->sub_nav;

        $tag['list_payroll'] = $this->listpayroll(false);
        $this->common->display("member/payroll/dashboard.html", $tag);
	}

    function option(){
        $tag['__sub_nav__'] = $this->sub_nav;
        
		$sql = "SELECT * FROM payroll_month_end";
		$query = $this->db->query($sql);
		$this->table->set_heading('Date', '!');
		foreach($query->result() as $row)
			$this->table->add_row($row->date, 'Remove');
		
		$tag['options']  = '<h3>End of Month Cut-off Date</h3>';
		$tag['options'] .= $this->table->generate();	
		$this->common->display("member/payroll/options.html", $tag);	
	}

	function listpayroll($echo = true){
        $this->load->library('xjqgrid');
        $sql = "SELECT p.payrollid, 
                       p.payperiod_from, 
                       p.payperiod_to, 
                       CONCAT(
                            CONCAT(MONTHNAME(p.payperiod_from), ' ', DAY(p.payperiod_from), ', ', YEAR(p.payperiod_from)), 
                            ' To ', 
                            CONCAT(MONTHNAME(p.payperiod_to), ' ', DAY(p.payperiod_to), ', ', YEAR(p.payperiod_to)) ) as `Payroll Period`, 
                       remark, (SELECT count(*) as cn FROM payroll_data pd WHERE pd.payrollid=p.payrollid) as empcount,
                       p.payrollid as Actions 
                FROM payroll p  WHERE p.active=1 AND p.companyid={$this->ccompany}";
        $this->xjqgrid->initGrid( $sql );
        $this->xjqgrid->setMethod('setUrl', 'payroll/listpayroll/1');
        $this->xjqgrid->setProperty('toolbarfilter', false);
        $this->xjqgrid->setProperty('navigator', true);
        $this->xjqgrid->setMethod('setPrimaryKeyId', "payrollid");
        $oper = $_POST['oper'];
        if($oper == 'del'){
            if(!$this->common->isPayLocked($_POST['payrollid'])){
                $sql = "UPDATE payroll SET active=0 WHERE approved=0 AND payrollid={$_POST['payrollid']};";
                $this->db->query($sql);
            }
            
            $_POST = null;
            
        }
        
        $this->xjqgrid->setColProperty("payrollid", array("label"=>"ID", "hidden"=>true,));
        $this->xjqgrid->setColProperty("payperiod_from", array("hidden" => true));
        $this->xjqgrid->setColProperty("payperiod_to", array("hidden" => true));
        $this->xjqgrid->setColProperty("Payroll Period", array("sortable"  => false));
        $this->xjqgrid->setColProperty("remark", array("label"=>"Remark", "sortable"  => false));
        $this->xjqgrid->setColProperty("empcount", array("label"=>"Count", "sortable"  => false, "width"=>"20", "align"=>"center"));
        
        $this->xjqgrid->setColProperty("Actions", array(
            "width"     => 155,
            "formatter" => "js:formatActions", 
            "unformat"  => "js:unformat",
            "sortable"  => false,
            "search"    => false,
            "fixed"     => true,
            "align"     => "center",
            "resizable" => false
        ));
        
$customjs = <<<CUSTOMJS
function formatActions(cellValue, options, rowObject) { 
    var imageHtml = "<div style='padding-top: 2px'><a href='payroll/edit/" + cellValue + "' originalValue='" + cellValue + "' ><img title='Edit Payroll' src='image/report_edit.png'></a>&nbsp;&nbsp;"; 
        imageHtml += "<a href='javascript:;' originalValue='" + cellValue + "' onclick='window.open(\"payroll/printit/" + cellValue + "\",\"mywindow\",\"width=800,height=600,menubar=1,scrollbars=1,location=0,status=0\");'><img title='Print Payroll' src='image/printer.png'></a>&nbsp;&nbsp;";
        imageHtml += "<a href='payroll/holiday/" + cellValue + "' originalValue='" + cellValue + "'><img title='Holidays' src='image/folder_table.png'></a>&nbsp;&nbsp;";
        imageHtml += "<a href='payroll/alphalist/" + cellValue + "' originalValue='" + cellValue + "'><img title='Alpha List' src='image/bank_vs_amount.png'></a></div>";        
    return imageHtml; 
} 
function unformat(cellValue, options, cellObject) { 
    return $(cellObject.html()).attr("originalValue"); 
}
CUSTOMJS;

        $this->xjqgrid->grid->setJSCode($customjs); 
        $this->xjqgrid->grid->setNavOptions('navigator', array(
            "excel" => false,
            "add"   => false,
            "edit"  => false,
            "view"  => false, 
            "search"=> false)); 
        $this->xjqgrid->grid->setGridOptions(array( 
            "autowidth"     => true,
            "height"        => 335,
            "rownumbers"    => true, 
            "hoverrows"     => true,
            "rownumWidth"   => 35, 
            "rowNum"        => 15,
            "altRows"       => true,
            "sortname"      => "payperiod_from",
            "sortorder"     => "desc",
            "rowList"       => array(15,30,100),
            ));
            
        if(!$echo)
            return $this->xjqgrid->renderGrid('#grid','#pager',true, null, null, true, true, $echo);
            
        $this->xjqgrid->renderGrid('#grid','#pager',true, null, null, true, true, $echo);
        $this->xjqgrid->close();
	}

	function newpayroll(){
	    $tag['__sub_nav__'] = $this->sub_nav;
	    
        $this->load->library('form_validation');
		$this->form_validation->set_error_delimiters('<div class="error">', '</div>');
		if(isset($_POST['newpay']) && $this->form_validation->run() == TRUE){
			$date = explode("/", $_POST['payfrom']);
			$_POST['payfrom'] = date("Y-m-d", mktime(0,0,0,$date[0],$date[1],$date[2]));
			$date = explode("/", $_POST['payto']);
			$_POST['payto'] = date("Y-m-d", mktime(0,0,0,$date[0],$date[1],$date[2]));
			$sql = "INSERT INTO payroll(companyid,payperiod_from,payperiod_to,remark) VALUES({$this->ccompany},'{$_POST['payfrom']}','{$_POST['payto']}','{$_POST['remark']}');";
			$this->db->query($sql);
			
			redirect('payroll/edit/'.$this->db->insert_id());
		}else{
			$tag['errors'] = validation_errors();
		}
		
		$this->common->display("member/payroll/new_pay.html", $tag);
	}

	function edit($payrollid){
	    $tag['jscript'] = array('i18n/grid.locale-en', 'jquery.jqGrid.min', 'jquery-ui-custom.min');	    
        $tag['css'] = array('themes/ui.jqgrid', 'themes/ui.multiselect');
        
		$sql = "SELECT * FROM settings";
		$setting = array();
		$settings = $this->db->query($sql);
		
		foreach( $settings->result() as $r ){
			$setting[$r->name]=$r->value;
		}
		
		$sql = "SELECT * FROM payroll WHERE payrollid={$payrollid} AND active=1;";
		$payroll = $this->db->query($sql)->row();
		$datefrom = explode("-", $payroll->payperiod_from);
		$dateto = explode("-", $payroll->payperiod_to);
		$tag['datefrom'] = date('M d, Y', mktime(0,0,0,$datefrom[1],$datefrom[2],$datefrom[0]));
		$tag['dateto'] = date('M d, Y', mktime(0,0,0,$dateto[1],$dateto[2],$dateto[0]));
		$tag['payrollid'] = $payrollid;

        $sql = "SELECT e.idnumber, pd.empid, e.firstname, e.lastname, e.middlename, e.taxcode, e.email,
				b.amount, b.allowance, b.maxhours, b.maxbreakhours, b.maxworkdays, b.nightdiffin, b.nightdiffout, b.halfday,
				b.sss, b.pagibig, b.philhealth
				FROM payroll_detail pd 
				INNER JOIN employee e ON e.empid=pd.empid
				INNER JOIN basic b ON b.empid=pd.empid
				INNER JOIN schedule s ON s.empid=pd.empid 
				WHERE payrollid={$payrollid} 
				GROUP BY pd.empid ORDER BY lastname;";
		$currentemp = $this->db->query($sql);
        $tnetpay = 0;
        $sqls = array();
        foreach($currentemp->result() as $i=>$row){
            $tpay = $this->compute($payrollid, $row->empid);
            $sqls[] = "INSERT INTO payroll_data(payrollid, empname, empid, basic, gross, wtax, sss, phlt, pagibig, netpay)
                     VALUES({$payrollid},
                        '".$row->lastname.', '.$row->firstname.' '.$row->middlename."',
                        ".$row->empid.",
                        '".$tpay['basic']."',
                        '".$tpay['gross_pay']."',
                        '".$tpay['withholding']."',
                        '".$tpay['sss']."',
                        '".$tpay['philhealth']."',
                        '".$tpay['pagibig']."',
                        '".$tpay['netpay']."');";
            $tnetpay += $tpay['netpay'];
        }
		
		$sql_ = "SELECT sum(netpay) tnetpay FROM payroll_data WHERE payrollid={$payrollid}";
		$indata = $this->db->query($sql_)->num_rows();		

		if($indata->tnetpay != $tnetpay){
            $sql_ = "DELETE FROM payroll_data WHERE payrollid={$payrollid};";
            $this->db->query($sql_);
            
		    foreach($sqls as $sql){
		        $this->db->query( $sql );
		    }
        }
		
		//$tag['release_control']  = '<div align="right">'.(($payroll->approved==1)?'<img title="Payroll is locked" src="image/lock.png">':'<a onclick="if(confirm(\'Are you sure?\nRelease current Payroll?\')){return true;}else{return false;}" href="payroll/lock/'.$payrollid.'" title="Send Payslips"><img src="image/send.png"></a>').'</div>';
		$tag['release_control']  = '<div id="scripter" style="display:none"></div><div align="right">'.(($payroll->approved==1)?'<img title="Payroll is locked" src="image/lock.png">':'<div style="padding-top: 8px;"><a onclick="if(confirm(\'Are you sure?\nRelease current Payroll?\nThis Action will lock the this Payroll\')){modalbox(\'payroll/releasepay/'.$payrollid.'\', {title:\'Release Payroll\'})}else{return false;}" href="javascript:;" title="Send Payslips"><img src="image/send.png"></a></div>').'</div>';
		$tag['payroll_calculator'] = $this->currentpayroll($payrollid, false);
		$this->common->display("member/payroll/edit_pay.html", $tag);
	}

    function releasepay($payrollid){
        $tag['payrollid'] = $payrollid;
        $sql = "SELECT * FROM payroll_data WHERE payrollid={$payrollid};";
        $query = $this->db->query($sql);
        $tag['maxval'] = $query->num_rows();
        $data = "[";
        foreach($query->result() as $row){
            $data .= $row->empid.",";
        }
        $data = trim($data, ",");
        $data .= "]";
        $tag['paydata'] = $data;   
        $this->parser->parse("member/payroll/releasepay.html", $tag);
    }

    function addemp($payrollid = 0, $empid = 0){
        $tag['payrollid'] = $payrollid;
		$sql = "SELECT * FROM payroll WHERE payrollid={$payrollid};";
		$payroll = $this->db->query($sql)->row();
		
		$datefrom = explode("-", $payroll->payperiod_from);
		$dateto = explode("-", $payroll->payperiod_to);
		$tag['datefrom'] = date('M d, Y', mktime(0,0,0,$datefrom[1],$datefrom[2],$datefrom[0]));
		$tag['dateto'] = date('M d, Y', mktime(0,0,0,$dateto[1],$dateto[2],$dateto[0]));
		
        if($empid==0){
            //new form
            $this->load->library('form_validation');
            if($payroll->approved == 1){
				$this->common->display("member/payroll/locked.html", $tag);
				return;
			}
			if(isset($_POST['addemp']) && $this->form_validation->run('emp_payroll') == TRUE){
				$i 		= 0;
				$empid 	= $_POST['employee'];
				$sql = "SELECT * FROM schedule WHERE scheduleid={$_POST['scheduleid']};";
				$sched = $this->db->query($sql)->row();
				
				do{
					$currdate = date('Y-m-d', mktime(0,0,0,$datefrom[1],$datefrom[2],$datefrom[0])+(86400*($i)));
					$day = date('l', mktime(0,0,0,$datefrom[1],$datefrom[2],$datefrom[0])+(86400*$i));
					if( $sched && $day!= 'Sunday'){
						
						if( strtotime($sched->timein) > strtotime($sched->timeout) ){
							$outdate = explode("-", $currdate);
							$newoutdate = date('Y-m-d', mktime(0,0,0,$outdate[1],$outdate[2],$outdate[0])+86400);
							$sql = "INSERT INTO payroll_detail(payrollid,empid,detaildate,timein,timeout,scheduleid)
									VALUES({$payrollid},{$empid},'{$currdate}','{$currdate} {$sched->timein}', '{$newoutdate} {$sched->timeout}',{$sched->scheduleid});";
						}else{
							$sql = "INSERT INTO payroll_detail(payrollid,empid,detaildate,timein,timeout,scheduleid)
									VALUES({$payrollid},{$empid},'{$currdate}','{$currdate} {$sched->timein}', '{$currdate} {$sched->timeout}',{$sched->scheduleid});";
						}
						
					}else{
						
						$sql = "INSERT INTO payroll_detail(payrollid,empid,detaildate,timein,timeout)
								VALUES({$payrollid},{$empid},'{$currdate}','{$currdate} 00:00:00','{$currdate} 00:00:00');";
						
					}
					
					$this->db->query( $sql );
					
					$i++;
				}while($currdate!=date('Y-m-d', mktime(0,0,0,$dateto[1],$dateto[2],$dateto[0])));
				
				//check loans
				$sql = "SELECT l.*, sum(ld.amount) as total_pay FROM loan l
				LEFT JOIN loan_detail ld ON ld.loanid=l.loanid
				WHERE empid={$empid} AND active=1 GROUP BY l.loanid;";
				
				$loans = $this->db->query($sql);
				if($loans){
					foreach($loans->result() as $loan){
						$total = $loan->amount+($loan->amount*$loan->intrate);
						$balance = $total-$loan->total_pay;
						if($balance > 0){
							$payable = round(($total/$loan->term), 2);
							$sql = "INSERT INTO loan_detail(loanid,amount,paydate) 
									VALUES({$loan->loanid},{$payable},'".date('Y-m-d')."');";
							$this->db->query($sql);
							$insertid = $this->db->insert_id();
							$sql = "INSERT INTO loan_payroll_detail(payrollid,ldetailid)
									VALUES({$payrollid},{$insertid});";
							$this->db->query($sql);
						}
					}
				}
				
				//recurring income
				$sql = "SELECT * FROM recurring_income WHERE empid={$empid};";
				$recurring_incomes = $this->db->query($sql);
				$mothends = $this->_get_monthends();
				$currentends = date('d', strtotime($payroll->payperiod_to));
				
				foreach($recurring_incomes->result() as $row){
					if($row->interval==1 && in_array($currentends, $mothends) || $row->interval==2){
						$sql = "INSERT INTO other_income(amount,payrollid,empid,remark)
								VALUES({$row->amount},{$payrollid},{$empid},'{$row->description}');";
						$this->db->query($sql);
					}
				}
				
				//recurring income
				$sql = "SELECT * FROM recurring_deduction WHERE empid={$empid};";
				$recurring_deductions = $this->db->query($sql);
				$mothends = $this->_get_monthends();
				$currentends = date('d', strtotime($payroll->payperiod_to));
				
				foreach($recurring_deductions->result() as $row){
					if($row->interval==1 && in_array($currentends, $mothends) || $row->interval==2){
						$sql = "INSERT INTO other_deduction(amount,payrollid,empid,remark)
								VALUES({$row->amount},{$payrollid},{$empid},'{$row->description}');";
						$this->db->query($sql);
					}
				}
				
				//emailer
				$sql = "SELECT email FROM employee WHERE empid={$empid}";
				$email = $this->db->query($sql)->row()->email;
				if($email){
					$sql = "INSERT INTO payslip(payrollid,empid) VALUES({$payrollid},{$empid})";
					$this->db->query($sql);
				}
				
				redirect('payroll/addemp/'.$payrollid.'/'.$empid);
			}else{
				$tag['errors'] = validation_errors();
			}
            $this->load->helper('form');
            $sql = "SELECT * FROM employee WHERE empid 
                    not in(SELECT empid FROM payroll_detail WHERE 
                    payrollid={$payrollid}) AND companyid={$this->ccompany};";
			$query = $this->db->query($sql);
			$employees[""] = '[----Select One----]';
			foreach($query->result() as $row)
				$employees[$row->empid] = $row->firstname.' '.$row->middlename.' '.$row->lastname;
			$tag['employees'] 	= form_dropdown('employee', $employees, '', 
            'onchange="$(\'#schedule\').html(\'Please select an employee.\'); $.get(\'payroll/getschedule/\'+$(this).val(), function(d){
                $(\'#schedule\').html(d);
            });"');

			$this->common->display("member/payroll/add_emp.html", $tag);
        }else{
            //display data
            $tag['jscript'] = array('i18n/grid.locale-en', 'jquery.jqGrid.min', 'jquery-ui-custom.min');	    
            $tag['css'] = array('themes/ui.jqgrid', 'themes/ui.multiselect');
		    $sql = "SELECT e.*, b.maxhours, 
					    b.maxbreakhours, 
					    b.halfday 
				    FROM employee e
				    LEFT JOIN basic b ON b.empid=e.empid
				    WHERE e.empid={$empid};";
		    $employee = $this->db->query($sql)->row();
		    $tag['payrollid'] = $payrollid;
		    $tag['empid'] = $empid;
		    $tag['name'] = $employee->firstname.' '.$employee->middlename.' '.$employee->lastname;
		    $tag['islocked'] = $payroll->approved;
		    //schedule
		    $sql = "SELECT * FROM schedule WHERE empid={$empid} AND `show`=1;";
		    $scheds = $this->db->query( $sql );
		    $sched = array();
		    foreach($scheds->result() as $s){
			    $sched[$s->scheduleid] = date('h:i A', strtotime($s->timein)).' - '.date('h:i A', strtotime($s->timeout));
		    }
		    $tag['dtr'] = $this->getdtr($payrollid, $empid, false);
		    $tag['loan_pay']   = $this->loanpays($payrollid, $empid, false);
		    $tag['other_deds'] = $this->otherdeds($payrollid, $empid, false);
		    $tag['other_inco'] = $this->otherinco($payrollid, $empid, false);
		    $this->common->display("member/payroll/pay_detail.html", $tag);
		}
    }

    function getdtr($payrollid = 0, $empid = 0, $echo = true){
        $this->load->library('xjqgrid');
        
        $sql = "SELECT pd.pdetailid, pd.payrollid, pd.empid, 
                    DATE_FORMAT(pd.detaildate, '%W %M %e, %Y') as detaildate,
                    DATE_FORMAT(pd.timein, '%m/%d/%Y') as datein,
                    DATE_FORMAT(pd.timein, '%H:%i') as timein,
                    DATE_FORMAT(pd.timeout, '%m/%d/%Y') as dateout,
                    DATE_FORMAT(pd.timeout, '%H:%i') as timeout, 
                    TIME_TO_SEC(TIMEDIFF(pd.timeout, pd.timein)) as ttime, h.remark 
			FROM payroll_detail pd
			LEFT JOIN holidays h ON h.payrollid=pd.payrollid AND pd.detaildate=h.dates
			WHERE pd.payrollid={$payrollid} AND pd.empid={$empid} ORDER BY pd.detaildate;";
			
		$this->xjqgrid->initGrid( $sql );
        $this->xjqgrid->setMethod('setUrl', "payroll/getdtr/{$payrollid}/{$empid}/1");
        $this->xjqgrid->grid->setSubGridGrid("payroll/dtrsub");
        $this->xjqgrid->setMethod('setPrimaryKeyId', "pdetailid");
         
        $this->xjqgrid->grid->setGridOptions(array(
            "autowidth"     => true,
            "height"        => 335,
            "hoverrows"     => true,
            "altRows"       => true,
            "caption"       => "Daily Time Record".(($this->common->isPayLocked($payrollid) == 1)?" (Locked)":""),
            "subGrid"       => true,
            ));
        $this->xjqgrid->grid->setGridOptions(array(
            "subGridOptions"=>array(
                "plusicon"=>"ui-icon-triangle-1-e",
                "minusicon"=>"ui-icon-triangle-1-s",
                "openicon"=>"ui-icon-arrowreturn-1-e",
                // load the subgrid data only once
                // and the just show/hide
                "reloadOnExpand"=>true,
                // select the row when the expand column is clicked
                "selectOnExpand"=>true
            )
        ));
        
//##############################################################################
//### THIS IS A SECURITY BOX, PUT SECURITY CHECKS HERE                       ###
//### FOR PAYROLL LOCKING AND UNAUTHORIZED EDITING                           ###
//##############################################################################
if($this->common->isPayLocked($payrollid) == 0){
$onselrow = <<< ONSELROW
function(rowid, selected)
{
    if(rowid && rowid !== lastSelection) {
        $("#grid").jqGrid('restoreRow', lastSelection);      
        lastSelection = rowid;  
        $("#grid").jqGrid('editRow', rowid, true, 
            function(){}, 
            function(){}, 
            "clientArray", '', 
            function(rowid){
                var data = $("#grid").jqGrid('getRowData', rowid);
                $.post("payroll/updatedtr", data, function(d){
                    $("#grid").jqGrid('setCell', rowid, 'ttime', d);
                });
                lastSelection = null;
            }, 
            function(){}, 
            function(){
            lastSelection = null; //for escape
        });    
    }
}
ONSELROW;
        $this->xjqgrid->grid->setGridEvent('onSelectRow', $onselrow);
}
//##############################################################################
//### END OF SECURITY BOX                                                    ###
//##############################################################################

        $this->xjqgrid->setColProperty("pdetailid", array("hidden"=>true,));
        $this->xjqgrid->setColProperty("payrollid", array("hidden"=>true,));
        $this->xjqgrid->setColProperty("empid", array("hidden"=>true,));
        $this->xjqgrid->setColProperty("datein", array("hidden"=>true,));
        $this->xjqgrid->setColProperty("remark", array(
            "editable"  => false,
            "sortable"  => false,
            "label"     => "Remarks"));
        $this->xjqgrid->setColProperty("ttime", array(
            "editable" => false,
            "width"    => "40",
            "align"    => "center",
            "label"    => "Hours",
            "sortable" => false,
            "formatter"=> "js:formathours"));
        $this->xjqgrid->setColProperty("detaildate", 
            array(
                "label"         => "Date IN", 
                "sortable"      => false,
                "editable"      => false,
                "align"         => "right"));
        $this->xjqgrid->setColProperty("timein", 
            array(
                "label"     => "Time IN",
                "width"         => "100", 
                "sortable"  =>  false,
                "align"     => "center",
                "editrules" => array(
                    "required"  => true,
                    "time"      => true
                )));
        $this->xjqgrid->setColProperty("dateout", 
            array(
                "label"     => "Date OUT",
                "width"     => "100",
                "sortable"  =>  false,
                "datefmt"   => "mm/dd/YYYY",
                "align"     => "center",
                "editrules" => array(
                    "required"  => true,                    
                    "date"      => true
                )));
        $this->xjqgrid->grid->setDatepicker("dateout",
            array(
                "buttonOnly"=>false, 
                "dateFormat"=>"mm/dd/yy")); 
        $this->xjqgrid->setColProperty("timeout", 
            array(
                "label"     => "Time OUT",
                "width"     => "110", 
                "align"     => "center",
                "sortable"  =>  false,
                "editrules" => array(
                    "required"  => true,
                    "time"      => true
                )));
        
$myformat = <<<FORMATHOURS
function formathours (cellValue, options, rowdata) 
{
    var cellHtml = ((cellValue/60)/60).toFixed(2);
    return cellHtml; 
}
FORMATHOURS;
        $this->xjqgrid->grid->setJSCode($myformat); 
        
        if(!$echo)
            return $this->xjqgrid->renderGrid('#grid','',true, null, null, true, true, $echo);
            
        $this->xjqgrid->renderGrid('#grid','',true, null, null, true, true, $echo);
        $this->xjqgrid->close();
    }

    function otherdeds( $payrollid=0, $empid=0, $echo = true ){
        $this->load->library('xjqgrid');
        
		$sql = "SELECT deductid, remark, amount FROM other_deduction WHERE payrollid={$payrollid} AND empid={$empid};";
		
        $this->xjqgrid->initGrid( $sql );
		$this->xjqgrid->setMethod('setUrl', "payroll/otherdeds/{$payrollid}/{$empid}/1");
		
        $oper = $_POST['oper'];
        if($oper == 'add'){
            $_POST['payrollid'] = $payrollid;
            $_POST['empid']     = $empid;
            
            $this->xjqgrid->setProperty('table', 'other_deduction');
            $this->xjqgrid->setMethod('setPrimaryKeyId', "deductid");
        }else{
            $this->xjqgrid->setProperty('table', 'other_deduction');
            $this->xjqgrid->setMethod('setPrimaryKeyId', "deductid");
        }
        $this->xjqgrid->setColProperty("deductid", 
            array(  "hidden"    => true, 
                    "editable"  => false));
        $this->xjqgrid->setColProperty("amount", 
            array(
                "label"     => "Amount",
                "width"     => "80",
                "sortable"  =>  false,
                "align"     => "right",
                "formatter" => "currency",
                "formatoptions" => array("thousandsSeparator"=>","),
                "editrules" => array(
                    "required"  => true,
                    "number"    => true,
                ),
                "editoptions"=> array(
                    "defaultValue"=> '0.00'
                )));
        $this->xjqgrid->setColProperty("remark", 
            array(  "label"    => "Remark",
                "editrules" => array(
                    "required"  => true
                ) ));
        //#############################################################################
        //### BEGIN SECURITY BOX                                                    ###
        //#############################################################################
        if($this->common->isPayLocked($payrollid) != 1){
            $this->xjqgrid->grid->navigator = true; 
            $this->xjqgrid->grid->setNavOptions('navigator', array(
                "excel" => false,
                "view"  => false,
                "search"=>false));  
        }
        //#############################################################################
        //### END SECURITY BOX                                                      ###
        //#############################################################################        
        $this->xjqgrid->grid->setGridOptions(array( 
                "width"         => 475,
                "height"        => 150,
                "hoverrows"     => true,            
                "rowNum"        => 15,
                "altRows"       => true,
                "caption"       => "Other Deductions ".(($this->common->isPayLocked($payrollid) == 1)?" (Locked)":""),
                "rowList"       => array(15,30,100),
            ));
        
        if(!$echo)
            return $this->xjqgrid->renderGrid('#dedgrid','#dedpager',true, null, null, true, true, $echo);
            
        $this->xjqgrid->renderGrid('#dedgrid','#dedpager',true, null, null, true, true, $echo);
        $this->xjqgrid->close();
		
    }

    function otherinco( $payrollid=0, $empid=0, $echo = true ){
        $this->load->library('xjqgrid');
        
		$sql = "SELECT oincomeid, remark, amount FROM other_income WHERE payrollid={$payrollid} AND empid={$empid};";

        $this->xjqgrid->initGrid( $sql );
		$this->xjqgrid->setMethod('setUrl', "payroll/otherinco/{$payrollid}/{$empid}/1");
		
        $oper = $_POST['oper'];
        if($oper == 'add'){
            $_POST['payrollid'] = $payrollid;
            $_POST['empid']     = $empid;
            
            $this->xjqgrid->setProperty('table', 'other_income');
            $this->xjqgrid->setMethod('setPrimaryKeyId', "oincomeid");
        }else{
            $this->xjqgrid->setProperty('table', 'other_income');
            $this->xjqgrid->setMethod('setPrimaryKeyId', "oincomeid");
        }
        $this->xjqgrid->setColProperty("oincomeid", 
            array(  "hidden"    => true, 
                    "editable"  => false));
        $this->xjqgrid->setColProperty("amount", 
            array(
                "label"     => "Amount",
                "width"     => "80",
                "sortable"  =>  false,
                "align"     => "right",
                "formatter" => "currency",
                "formatoptions" => array("thousandsSeparator"=>","),
                "editrules" => array(
                    "required"  => true,
                    "number"    => true,
                ),
                "editoptions"=> array(
                    "defaultValue"=> '0.00'
                )));
        $this->xjqgrid->setColProperty("remark", 
            array(  "label"    => "Remark",
                "editrules" => array(
                    "required"  => true
                ) ));
        //#############################################################################
        //### BEGIN SECURITY BOX                                                    ###
        //#############################################################################
        if($this->common->isPayLocked($payrollid) != 1){
            $this->xjqgrid->grid->navigator = true; 
            $this->xjqgrid->grid->setNavOptions('navigator', array(
                "excel" => false,
                "view"  => false,
                "search"=>false));  
        }
        //#############################################################################
        //### END SECURITY BOX                                                      ###
        //#############################################################################        
        $this->xjqgrid->grid->setGridOptions(array( 
                "width"         => 475,
                "height"        => 150,
                "hoverrows"     => true,            
                "rowNum"        => 15,
                "altRows"       => true,
                "caption"       => "Other Income ".(($this->common->isPayLocked($payrollid) == 1)?" (Locked)":""),
                "rowList"       => array(15,30,100),
            ));
        
        if(!$echo)
            return $this->xjqgrid->renderGrid('#incgrid','#incpager',true, null, null, true, true, $echo);
            
        $this->xjqgrid->renderGrid('#incgrid','#incpager',true, null, null, true, true, $echo);
        $this->xjqgrid->close();
    }

    function loanpays( $payrollid=0, $empid=0, $echo = true ){
        $this->load->library('xjqgrid');
        
		$sql = "SELECT ld.loanid, lpd.ldetailid, ld.paydate, ld.amount
			        FROM loan_payroll_detail lpd
			        LEFT JOIN loan_detail ld ON ld.ldetailid=lpd.ldetailid
			        INNER JOIN loan l ON l.loanid=ld.loanid AND l.empid={$empid} 
			        WHERE lpd.payrollid={$payrollid};";
	
        $this->xjqgrid->initGrid( $sql );
		$this->xjqgrid->setMethod('setUrl', "payroll/loanpays/{$payrollid}/{$empid}/1");
		
        $oper = $_POST['oper'];
        if($oper == 'add'){
            $_POST['payrollid'] = $payrollid;
            $_POST['empid']     = $empid;
            
            $this->xjqgrid->setProperty('table', 'loan_detail');
            $this->xjqgrid->setMethod('setPrimaryKeyId', "ldetailid");
        }else{
            $this->xjqgrid->setProperty('table', 'loan_detail');
            $this->xjqgrid->setMethod('setPrimaryKeyId', "ldetailid");
        }
        $this->xjqgrid->setColProperty("ldetailid", 
            array(  "hidden"    => true, 
                    "editable"  => false));
        $this->xjqgrid->setColProperty("loanid", 
            array(
                "label"     => "Loan", 
                "sortable"  => false));
        $this->xjqgrid->setSelect("loanid", "SELECT l.loanid, CONCAT(DATE_FORMAT(l.loandate, '%m/%d/%Y'), ' (', sum(ld.amount), ')') as total_pay FROM loan l
				LEFT JOIN loan_detail ld ON ld.loanid=l.loanid
				WHERE empid={$empid} AND active=1 GROUP BY l.loanid", true, true, false);
        $this->xjqgrid->setColProperty("paydate", 
            array(
                "label"     => "Pay Date",
                "width"     => "100",
                "sortable"  =>  false,
                "datefmt"   => "mm/dd/YYYY",
                "align"     => "center",
                "editrules" => array(
                    "required"  => true,                    
                    "date"      => true
                )));
        $this->xjqgrid->grid->setDatepicker("paydate",
            array(
                "buttonOnly"=>false, 
                "dateFormat"=>"mm/dd/yy"));
        $this->xjqgrid->setColProperty("amount", 
            array(
                "label"     => "Amount",
                "width"     => "80",
                "sortable"  =>  false,
                "align"     => "right",
                "formatter" => "currency",
                "formatoptions" => array("thousandsSeparator"=>","),
                "editrules" => array(
                    "required"  => true,
                    "number"    => true,
                ),
                "editoptions"=> array(
                    "defaultValue"=> '0.00'
                )));
        $this->xjqgrid->setColProperty("remark", 
            array(  "label"    => "Remark",
                "editrules" => array(
                    "required"  => true
                ) ));
        //#############################################################################
        //### BEGIN SECURITY BOX                                                    ###
        //#############################################################################
        if($this->common->isPayLocked($payrollid) != 1){
            $this->xjqgrid->grid->navigator = true; 
            $this->xjqgrid->grid->setNavOptions('navigator', array(
                "excel" => false,
                "view"  => false,
                "search"=>false));  
        }
        //#############################################################################
        //### END SECURITY BOX                                                      ###
        //#############################################################################        
        $this->xjqgrid->grid->setGridOptions(array( 
                "autowidth"     => true,
                "height"        => 150,
                "hoverrows"     => true,            
                "rowNum"        => 15,
                "altRows"       => true,
                "caption"       => "Loan Payments ".(($this->common->isPayLocked($payrollid) == 1)?" (Locked)":""),
                "rowList"       => array(15,30,100),
            ));
        
        if(!$echo)
            return $this->xjqgrid->renderGrid('#loangrid','#loanpager',true, null, null, true, true, $echo);
            
        $this->xjqgrid->renderGrid('#loangrid','#loanpager',true, null, null, true, true, $echo);
        $this->xjqgrid->close();
    }
    
    function dtrsub(){
        $pdetailid = $this->input->post('rowid', true);
        $this->load->helper('form');
        //current schedule
        $sql = "SELECT payrollid, scheduleid, empid, paidleave, absent FROM payroll_detail WHERE pdetailid={$pdetailid};";
        $currpaydetail = $this->db->query($sql)->row();
        
        //schedule
		$sql = "SELECT * FROM schedule WHERE empid={$currpaydetail->empid} AND `show`=1;";
		$scheds = $this->db->query( $sql );
		$sched = array();
		foreach($scheds->result() as $s){
			$sched[$s->scheduleid] = date('h:i A', strtotime($s->timein)).' - '.date('h:i A', strtotime($s->timeout));
		}
        $tag['schedule']    = form_dropdown('schedule', $sched, $currpaydetail->scheduleid);
        $tag['pdetailid']    = $pdetailid;
        $tag['none']        = '<label><input type="radio" name="others" value="none" align="absmiddle" '.(($currpaydetail->paidleave == 0 && $currpaydetail->absent == 0)?'checked="checked"':'').' /> None</label>';
        $tag['pl']          = '<label><input type="radio" name="others" value="pl" align="absmiddle" '.(($currpaydetail->paidleave == 1)?'checked="checked"':'').' /> Paid Leave</label>';
        $tag['abs']         = '<label><input type="radio" name="others" value="abs" align="absmiddle" '.(($currpaydetail->absent == 1)?'checked="checked"':'').' /> Absent</label>';
        
        $tag['over_time']   = $this->ottable($pdetailid, false);
        $tag['under_time']  = $this->uttable($pdetailid, false);
        
        $tag['dtr_script']  = '<script>
            $(function(){
                $("#btn_dtrsub_'.$pdetailid.'").bind("click", function(){
                    '.(($this->common->isPayLocked($currpaydetail->payrollid)==0)?'
                    $.post("payroll/updatedtrothers/'.$pdetailid.'", $("#dtrsub_'.$pdetailid.'").serialize(), function(d){
                        if(d==1){
                            $("#cont_dtrsub_'.$pdetailid.'").effect("highlight");
                        }
                    });':'alert("This Payroll is locked."); return false;').'
                });
            });
        </script>';
        
        $this->parser->parse('member/payroll/dtr_sub.html', $tag); 
    }

    function updatedtrothers($pdetailid){
        $schedule   = $this->input->post('schedule', TRUE);
        $others     = $this->input->post('others', TRUE);
        $sql = "UPDATE payroll_detail SET scheduleid={$schedule}";
        if($others == "pl"){ $sql .= ", paidleave=1, absent=0"; }
        elseif($others == "abs"){ $sql .= ", paidleave=0, absent=1"; }
        else{ $sql .= ", paidleave=0, absent=0"; }

        $sql .= " WHERE pdetailid={$pdetailid};";

        if($this->db->query($sql)){
            echo 1;
        }else{
            echo 0;
        }
    }
    
    //OVERTIME TABLE
    function ottable($pdetailid = 0, $echo = true){
        //over time
        $tableid = "#ottable_{$pdetailid}";
        $pagerid = "#otpager_{$pdetailid}";
        $this->load->library('xjqgrid');
        $sql = "SELECT
                    pdetailotid,
                    DATE_FORMAT(timein, '%m/%d/%Y') as datein,
                    DATE_FORMAT(timein, '%H:%i') as timein,
                    DATE_FORMAT(timeout, '%m/%d/%Y') as dateout,
                    DATE_FORMAT(timeout, '%H:%i') as timeout, 
                    TIME_TO_SEC(TIMEDIFF(timeout, timein)) as ttime 
                FROM payroll_detail_ot WHERE pdetailid={$pdetailid}";

		$this->xjqgrid->initGrid( $sql );
        $this->xjqgrid->setMethod('setUrl', "payroll/ottable/{$pdetailid}/1");
        $this->xjqgrid->setProperty('table', 'payroll_detail_ot');
        $this->xjqgrid->setMethod('setPrimaryKeyId', "pdetailotid");
        $this->xjqgrid->setColProperty("pdetailotid", 
            array("hidden" => true));
        $this->xjqgrid->setColProperty("datein", 
            array(
                "label"     => "Date IN",
                "width"     => "100",
                "sortable"  =>  false,
                "datefmt"   => "mm/dd/YYYY",
                "align"     => "center",
                "editrules" => array(
                    "required"  => true,                    
                    "date"      => true
                )));
        $this->xjqgrid->grid->setDatepicker("datein",
            array(
                "buttonOnly"=>false, 
                "dateFormat"=>"mm/dd/yy"));
        $this->xjqgrid->setColProperty("timein", 
            array(
                "label"     => "Time IN",
                "width"         => "100", 
                "sortable"  =>  false,
                "align"     => "center",
                "editrules" => array(
                    "required"  => true,
                    "time"      => true
                )));
        $this->xjqgrid->setColProperty("dateout", 
            array(
                "label"     => "Date OUT",
                "width"     => "100",
                "sortable"  =>  false,
                "datefmt"   => "mm/dd/YYYY",
                "align"     => "center",
                "editrules" => array(
                    "required"  => true,                    
                    "date"      => true
                )));
        $this->xjqgrid->grid->setDatepicker("dateout",
            array(
                "buttonOnly"=>false, 
                "dateFormat"=>"mm/dd/yy")); 
        $this->xjqgrid->setColProperty("timeout", 
            array(
                "label"     => "Time OUT",
                "width"     => "110", 
                "align"     => "center",
                "sortable"  =>  false,
                "editrules" => array(
                    "required"  => true,
                    "time"      => true
                )));
        $this->xjqgrid->setColProperty("ttime", array(
            "editable" => false,
            "width"    => "40",
            "align"    => "center",
            "label"    => "Hrs",
            "sortable" => false,
            "formatter"=> "js:formathours"));
        $this->xjqgrid->grid->setGridOptions(array(
            "caption"       => "OVER TIME",
            "width"         => "448",
            "height"        => "100",
            "hoverrows"     => true,
            "altRows"       => true,
            "rowNum"        => 15,            
            "rowList"       => array(15,30,100),
            ));
//##############################################################################
//### THIS IS A SECURITY BOX, PUT SECURITY CHECKS HERE                       ###
//### FOR PAYROLL LOCKING AND UNAUTHORIZED EDITING                           ###
//##############################################################################
        $this->xjqgrid->grid->setGridEvent('onSelectRow', 
        "function(rowid, selected){
            if(rowid && rowid !== ot_lastSelection) {
                $('{$tableid}').jqGrid('restoreRow', ot_lastSelection);
                ot_lastSelection = rowid;  
                $('{$tableid}').jqGrid('editRow', rowid, true,
                    function(){}, 
                    function(){}, 
                    'clientArray', '', 
                    function(rowid){
                        var data = $('{$tableid}').jqGrid('getRowData', rowid);
                        $.post('payroll/updateot', data, function(d){
                           $('{$tableid}').jqGrid('setCell', rowid, 'ttime', d);
                        });
                        ot_lastSelection = null;
                    }, 
                    function(){}, 
                    function(){
                    lastSelection = null;
                    }
                );
            }
        }");
        
        $this->xjqgrid->grid->navigator = true; 
        $this->xjqgrid->grid->setNavOptions('navigator', array(
            "excel" => false,
            "add"   => false,
            "edit"  => false,
            "view"  => false,
            "search"=>false));
        $buttonoptions = array($pagerid, 
            array("caption"      => "", 
                  "onClickButton"=> "js: function(){
                        $.get('payroll/addot/{$pdetailid}', function(){
                            $('{$tableid}').trigger('reloadGrid');
                        });
                  }",
                  "buttonicon"   => "ui-icon-plus",
                  "position"     => "first",
                  "title"        => "Add"
                 ) 
        ); 
        $this->xjqgrid->grid->callGridMethod($tableid, "navButtonAdd", $buttonoptions); 
//##############################################################################
//### END OF SECURITY BOX                                                    ###
//##############################################################################        
$myformat = <<<FORMATHOURS
function formathours (cellValue, options, rowdata) 
{
    var cellHtml = ((cellValue/60)/60).toFixed(2);
    return cellHtml; 
}
FORMATHOURS;
        $this->xjqgrid->grid->setJSCode($myformat);
        if(!$echo)
            return $this->xjqgrid->renderGrid($tableid, $pagerid, true, null, null, true, true, $echo);
            
        $this->xjqgrid->renderGrid($tableid, $pagerid, true, null, null, true, true, $echo);
        $this->xjqgrid->close();
    }
    
    function updatedtr(){ //accept only post data
        
        $pdetailid = $this->input->post("pdetailid", TRUE);
        $datein    = $this->input->post("datein", TRUE);
        $timein    = $this->input->post("timein", TRUE);
        $dateout   = $this->input->post("dateout", TRUE);
        $timeout   = $this->input->post("timeout", TRUE);

        $newtimein = date("Y-m-d H:i:s", strtotime($datein.' '.$timein));
        $newtimeout = date("Y-m-d H:i:s", strtotime($dateout.' '.$timeout));
        $sql = "UPDATE payroll_detail SET 
                timein='{$newtimein}', 
                timeout='{$newtimeout}' 
            WHERE pdetailid={$pdetailid};";
        $this->db->query($sql);
        
        $sql = "SELECT TIME_TO_SEC(TIMEDIFF(timeout, timein)) as ttime FROM payroll_detail WHERE pdetailid={$pdetailid};";
        echo $this->db->query($sql)->row()->ttime;
    }
        
    function addot( $pdetailid ){
        $sql = "SELECT
                    DATE_FORMAT(timein, '%Y-%m-%d 00:00:00') as timein
                FROM payroll_detail WHERE pdetailid={$pdetailid}";
        $datetime = $this->db->query($sql)->row()->timein;
        $sql = "INSERT payroll_detail_ot(pdetailid, timein, timeout) 
                VALUE({$pdetailid}, '{$datetime}', '{$datetime}');";
        $this->db->query($sql);
    }

    function updateot(){
        $pdetailotid = $this->input->post("pdetailotid", TRUE);
        $datein      = $this->input->post("datein", TRUE);
        $timein      = $this->input->post("timein", TRUE);
        $dateout     = $this->input->post("dateout", TRUE);
        $timeout     = $this->input->post("timeout", TRUE);

        $newtimein = date("Y-m-d H:i:s", strtotime($datein.' '.$timein));
        $newtimeout = date("Y-m-d H:i:s", strtotime($dateout.' '.$timeout));
        $sql = "UPDATE payroll_detail_ot SET 
                timein='{$newtimein}', 
                timeout='{$newtimeout}' 
            WHERE pdetailotid={$pdetailotid};";
        $this->db->query($sql);
        
        $sql = "SELECT TIME_TO_SEC(TIMEDIFF(timeout, timein)) as ttime FROM payroll_detail_ot WHERE pdetailotid={$pdetailotid};";
        echo $this->db->query($sql)->row()->ttime;
    }

    //UNDER TIME TABLE
    function uttable($pdetailid = 0, $echo = true){
        //over time
        $tableid = "#utable_{$pdetailid}";
        $pagerid = "#utpager_{$pdetailid}";
        $this->load->library('xjqgrid');
        $sql = "SELECT
                    pdetailutid,
                    DATE_FORMAT(timein, '%m/%d/%Y') as datein,
                    DATE_FORMAT(timein, '%H:%i') as timein,
                    DATE_FORMAT(timeout, '%m/%d/%Y') as dateout,
                    DATE_FORMAT(timeout, '%H:%i') as timeout, 
                    TIME_TO_SEC(TIMEDIFF(timeout, timein)) as ttime 
                FROM payroll_detail_ut WHERE pdetailid={$pdetailid}";

		$this->xjqgrid->initGrid( $sql );
        $this->xjqgrid->setMethod('setUrl', "payroll/uttable/{$pdetailid}/1");
        $this->xjqgrid->setProperty('table', 'payroll_detail_ut');
        $this->xjqgrid->setMethod('setPrimaryKeyId', "pdetailutid");
        $this->xjqgrid->setColProperty("pdetailutid", 
            array("hidden" => true));
        $this->xjqgrid->setColProperty("datein", 
            array(
                "label"     => "Date IN",
                "width"     => "100",
                "sortable"  =>  false,
                "datefmt"   => "mm/dd/YYYY",
                "align"     => "center",
                "editrules" => array(
                    "required"  => true,                    
                    "date"      => true
                )));
        $this->xjqgrid->grid->setDatepicker("datein",
            array(
                "buttonOnly"=>false, 
                "dateFormat"=>"mm/dd/yy"));
        $this->xjqgrid->setColProperty("timein", 
            array(
                "label"     => "Time IN",
                "width"         => "100", 
                "sortable"  =>  false,
                "align"     => "center",
                "editrules" => array(
                    "required"  => true,
                    "time"      => true
                )));
        $this->xjqgrid->setColProperty("dateout", 
            array(
                "label"     => "Date OUT",
                "width"     => "100",
                "sortable"  =>  false,
                "datefmt"   => "mm/dd/YYYY",
                "align"     => "center",
                "editrules" => array(
                    "required"  => true,                    
                    "date"      => true
                )));
        $this->xjqgrid->grid->setDatepicker("dateout",
            array(
                "buttonOnly"=>false, 
                "dateFormat"=>"mm/dd/yy")); 
        $this->xjqgrid->setColProperty("timeout", 
            array(
                "label"     => "Time OUT",
                "width"     => "110", 
                "align"     => "center",
                "sortable"  =>  false,
                "editrules" => array(
                    "required"  => true,
                    "time"      => true
                )));
        $this->xjqgrid->setColProperty("ttime", array(
            "editable" => false,
            "width"    => "40",
            "align"    => "center",
            "label"    => "Hrs",
            "sortable" => false,
            "formatter"=> "js:formathours"));
        $this->xjqgrid->grid->setGridOptions(array(
            "caption"       => "UNDER TIME",
            "width"         => "448",
            "height"        => "100",
            "hoverrows"     => true,
            "altRows"       => true,
            "rowNum"        => 15,            
            "rowList"       => array(15,30,100),
            ));
//##############################################################################
//### THIS IS A SECURITY BOX, PUT SECURITY CHECKS HERE                       ###
//### FOR PAYROLL LOCKING AND UNAUTHORIZED EDITING                           ###
//##############################################################################
        $this->xjqgrid->grid->setGridEvent('onSelectRow', 
        "function(rowid, selected){
            if(rowid && rowid !== ut_lastSelection) {
                $('{$tableid}').jqGrid('restoreRow', ut_lastSelection);
                ot_lastSelection = rowid;  
                $('{$tableid}').jqGrid('editRow', rowid, true,
                    function(){}, 
                    function(){}, 
                    'clientArray', '', 
                    function(rowid){
                        var data = $('{$tableid}').jqGrid('getRowData', rowid);
                        $.post('payroll/updateut', data, function(d){
                           $('{$tableid}').jqGrid('setCell', rowid, 'ttime', d);
                        });
                        ut_lastSelection = null;
                    }, 
                    function(){}, 
                    function(){
                    lastSelection = null;
                    }
                );
            }
        }");
        
        $this->xjqgrid->grid->navigator = true; 
        $this->xjqgrid->grid->setNavOptions('navigator', array(
            "excel" => false,
            "add"   => false,
            "edit"  => false,
            "view"  => false,
            "search"=>false));
        $buttonoptions = array($pagerid, 
            array("caption"      => "", 
                  "onClickButton"=> "js: function(){
                        $.get('payroll/addut/{$pdetailid}', function(){
                            $('{$tableid}').trigger('reloadGrid');
                        });
                  }",
                  "buttonicon"   => "ui-icon-plus",
                  "position"     => "first",
                  "title"        => "Add"
                 ) 
        ); 
        $this->xjqgrid->grid->callGridMethod($tableid, "navButtonAdd", $buttonoptions); 
//##############################################################################
//### END OF SECURITY BOX                                                    ###
//##############################################################################        
$myformat = <<<FORMATHOURS
function formathours (cellValue, options, rowdata) 
{
    var cellHtml = ((cellValue/60)/60).toFixed(2);
    return cellHtml; 
}
FORMATHOURS;
        $this->xjqgrid->grid->setJSCode($myformat);
        if(!$echo)
            return $this->xjqgrid->renderGrid($tableid, $pagerid, true, null, null, true, true, $echo);
            
        $this->xjqgrid->renderGrid($tableid, $pagerid, true, null, null, true, true, $echo);
        $this->xjqgrid->close();
    }

    function addut( $pdetailid ){
        $sql = "SELECT
                    DATE_FORMAT(timein, '%Y-%m-%d 00:00:00') as timein
                FROM payroll_detail WHERE pdetailid={$pdetailid}";
        $datetime = $this->db->query($sql)->row()->timein;
        $sql = "INSERT payroll_detail_ut(pdetailid, timein, timeout) 
                VALUE({$pdetailid}, '{$datetime}', '{$datetime}');";
        $this->db->query($sql);
    }

    function updateut(){
        $pdetailotid = $this->input->post("pdetailutid", TRUE);
        $datein      = $this->input->post("datein", TRUE);
        $timein      = $this->input->post("timein", TRUE);
        $dateout     = $this->input->post("dateout", TRUE);
        $timeout     = $this->input->post("timeout", TRUE);

        $newtimein = date("Y-m-d H:i:s", strtotime($datein.' '.$timein));
        $newtimeout = date("Y-m-d H:i:s", strtotime($dateout.' '.$timeout));
        $sql = "UPDATE payroll_detail_ut SET 
                timein='{$newtimein}', 
                timeout='{$newtimeout}' 
            WHERE pdetailutid={$pdetailotid};";
        $this->db->query($sql);
        
        $sql = "SELECT TIME_TO_SEC(TIMEDIFF(timeout, timein)) as ttime FROM payroll_detail_ut WHERE pdetailutid={$pdetailutid};";
        echo $this->db->query($sql)->row()->ttime;
    }

    function getsched($schedid){
        $sql = "SELECT * FROM schedule WHERE scheduleid={$schedid};";
        $data = $this->db->query($sql)->result_array();
        echo $data[0]['timein'].'/'.$data[0]['timeout'];
    }
    
    function currentpayroll($payrollid=0, $echo = true){
        $this->load->library('xjqgrid');
        
        //select from cache
        $sql = "SELECT empid, empname, basic, gross, wtax, sss, phlt, pagibig, netpay, CONCAT('{$payrollid}/',empid) as Actions  
                FROM payroll_data 
                WHERE payrollid={$payrollid} ";
                
        $this->xjqgrid->initGrid( $sql );
        $this->xjqgrid->setMethod('setUrl', "payroll/currentpayroll/{$payrollid}");
        
        $this->xjqgrid->setColProperty("empid", array("label"=>"ID", "hidden"=>true,));
        $this->xjqgrid->setColProperty("empname", array("label"=>"Name", "width"=>"200", "sortable"  => false));
        $this->xjqgrid->setColProperty("basic", array("label"=>"Basic", "width"=>"60", "sortable"  => false, "align"=>"right", "formatter"=>"currency", "formatoptions"=>array("decimalPlaces"=>2,"thousandsSeparator"=>",")));
        $this->xjqgrid->setColProperty("gross", array("label"=>"Gross Pay", "width"=>"60", "sortable"  => false, "align"=>"right", "formatter"=>"currency", "formatoptions"=>array("decimalPlaces"=>2,"thousandsSeparator"=>",")));
        $this->xjqgrid->setColProperty("wtax", array("label"=>"WTax", "width"=>"60", "sortable"  => false, "align"=>"right", "formatter"=>"currency", "formatoptions"=>array("decimalPlaces"=>2,"thousandsSeparator"=>",")));
        $this->xjqgrid->setColProperty("sss", array("label"=>"SSS", "width"=>"60", "sortable"  => false, "align"=>"right", "formatter"=>"currency", "formatoptions"=>array("decimalPlaces"=>2,"thousandsSeparator"=>",")));
        $this->xjqgrid->setColProperty("phlt", array("label"=>"PHLT", "width"=>"60", "sortable"  => false, "align"=>"right", "formatter"=>"currency", "formatoptions"=>array("decimalPlaces"=>2,"thousandsSeparator"=>",")));
        $this->xjqgrid->setColProperty("pagibig", array("label"=>"Pag-ibig", "width"=>"60", "sortable"  => false, "align"=>"right", "formatter"=>"currency", "formatoptions"=>array("decimalPlaces"=>2,"thousandsSeparator"=>",")));
        $this->xjqgrid->setColProperty("netpay", array("label"=>"Net Pay", "width"=>"60", "sortable"  => false, "align"=>"right", "formatter"=>"currency", "formatoptions"=>array("decimalPlaces"=>2,"thousandsSeparator"=>",")));
        $this->xjqgrid->setColProperty("Actions", array(
                                                        "width"     => 60,
                                                        "formatter" => "js:formatActions", 
                                                        "unformat"  => "js:unformat",
                                                        "sortable"  => false,
                                                        "search"    => false,
                                                        "fixed"     => true,
                                                        "align"     => "center",
                                                        "resizable" => false));
$customjs = <<<CUSTOMJS
function formatActions(cellValue, options, rowObject) { 
    var imageHtml = "<div style='padding-top: 2px'><a href='payroll/addemp/" + cellValue + "' originalValue='" + cellValue + "' ><img title='Edit Payroll' src='image/calendar.png'></a>&nbsp;&nbsp;"; 
        imageHtml += "<a href='payroll/payslip/" + cellValue + "' originalValue='" + cellValue + "'><img title='Print Payroll' src='image/script.png'></a></div>";        
    return imageHtml; 
} 
function unformat(cellValue, options, cellObject) { 
    return $(cellObject.html()).attr("originalValue"); 
}
CUSTOMJS;
        $this->xjqgrid->setMethod('setPrimaryKeyId', "empid");
        $oper = $_POST['oper'];
        if($oper == 'del' && $this->common->isPayLocked($payrollid)==0){
            //delete from payroll detail
            $sql = "DELETE FROM payroll_detail WHERE empid={$_POST['empid']} AND payrollid={$payrollid};";
            $this->db->query( $sql );

            //delete from cache
            $sql = "DELETE FROM payroll_data WHERE empid={$_POST['empid']} AND payrollid={$payrollid};";
            $this->db->query( $sql );

            //loan payment
            $sql = "SELECT ldetailid FROM loan_payroll_detail WHERE payrollid={$payrollid}";
            $result = $this->db->query($sql);
            foreach($result->row() as $row){
                $sql = "DELETE FROM loan_detail WHERE ldetailid={$row->ldetailid}";
                $this->db->query($sql);
            }
            
            $sql  = "DELETE FROM loan_payroll_detail WHERE payrollid={$payrollid}";            
            $this->db->query($sql);
            //delete other incomes
            //delete other deductions
            
        }
        $this->xjqgrid->grid->setJSCode($customjs);
        $this->xjqgrid->setProperty('navigator', true);     
        //###########################################################################
        //#### BEIGN SECURITY BOX                                                ####
        //###########################################################################
        if($this->common->isPayLocked($payrollid) == 0){
            $this->xjqgrid->grid->setNavOptions('navigator', array(
                "add"   => false,
                "excel" => false,
                "edit"  => false,
                "view"  => false, 
                "search"=> false));
        }else{
            $this->xjqgrid->grid->setNavOptions('navigator', array(
                "add"   => false,
                "del"   => false,
                "excel" => false,
                "edit"  => false,
                "view"  => false, 
                "search"=> false));
        }
        //###########################################################################
        //#### END SECURITY BOX                                                  ####
        //###########################################################################
        $this->xjqgrid->grid->setGridOptions(array(
            "autowidth"     => true,
            "height"        => 335,
            "hoverrows"     => true,
            "rownumWidth"   => 35,
            "altRows"       => true,
            "subGrid"       => true,
            "sortname"      => "empname",
            "sortorder"     => "asc",
            "rowNum"        => 15,
            "rowList"       => array(15,30,100),
            ));
        $this->xjqgrid->grid->setGridOptions(array(
            "subGridOptions"=>array(
                "plusicon"          => "ui-icon-triangle-1-e",
                "minusicon"         => "ui-icon-triangle-1-s",
                "openicon"          => "ui-icon-arrowreturn-1-e",
                "reloadOnExpand"    => false,
                "selectOnExpand"    => true
            )
        )); 
        $this->xjqgrid->grid->setGridOptions(array("footerrow"=>true,"userDataOnFooter"=>true));
        $this->xjqgrid->grid->setSubGridGrid("payroll/subcurrentpayroll/{$payrollid}"); 
        $this->xjqgrid->grid->callGridMethod('#grid', 'footerData', array("set", 
                    array(
                        "empname"=>"Total:"
                    )));
        $summaryrows = array( 
            "basic"     => array("basic"    => "SUM"),
            "gross"     => array("gross"    => "SUM"),
            "wtax"      => array("wtax"     => "SUM"),
            "sss"       => array("sss"      => "SUM"),
            "phlt"      => array("phlt"     => "SUM"),
            "pagibig"   => array("pagibig"  => "SUM"),
            "netpay"    => array("netpay"   => "SUM"),
        );

        if(!$echo)
            return $this->xjqgrid->renderGrid('#grid','#pager',true, $summaryrows, null, true, true, $echo);
         
        $this->xjqgrid->renderGrid('#grid','#pager',true, $summaryrows, null, true, true, $echo);
        $this->xjqgrid->close();
    }
    
    function subcurrentpayroll($payrollid){
        $empid = $_REQUEST['rowid'];
        $subtable = $_REQUEST["subgrid"]; 
        
        if($empid < 0){ return; }
        
        $this->load->library('xjqgrid');
        $tpay = $this->compute($payrollid, $empid);
        $data = array();
        $data[] = array(
		        "ID"                => $empid,
                "Rate"              => number_format($tpay['rate_per_day'], 2),
                "Rate/Hour"         => number_format($tpay['rate_per_hour'], 2),
                "Reg Hr"            => $tpay['reghours'],
                "# Late"            => $tpay['late_hour'],
                "Nigth Diff Hrs"    => number_format($tpay['nitediff_hours'], 2),
                "OT Hours"          => number_format($tpay['ot_hours'], 2),
                "Holiday Hrs OT"    => number_format($tpay['ot_hours_holiday'], 2),
                "Hrs UT"            => number_format($tpay['undertime'], 2),
                "Reg OT Pay"        => number_format($tpay['reg_ot_pay'], 2),
                "Holiday OT Pay"    => number_format($tpay['holiday_ot_pay'], 2),
                "Night Diff Pay"    => number_format($tpay['nitediff_pay'], 2),
                "Other Income"      => number_format($tpay['other_income'], 2),
                "Undertime"         => number_format($tpay['undertime_amount'], 2),
                "Absences"          => number_format($tpay['absent'], 2),
                "Late Amount"       => number_format($tpay['late'], 2),
                "Other Deduction"  => number_format($tpay['other_deduction'], 2),
                "Loan"              => number_format($tpay['loans'], 2),
                "Allowances"        => number_format($tpay['allowance'], 2)
		    );
	    $Model = array( 
            array( "name" => "ID",              "hidden" => true ), 
            array( "name" => "Rate",            "width" => "60", "sortable" => false, "align" => "right" ), 
            array( "name" => "Rate/Hour",       "width" => "60", "sortable" => false, "align" => "right" ),
            array( "name" => "Reg Hr",          "width" => "60", "sortable" => false, "align" => "center" ),
            array( "name" => "# Late",          "width" => "60", "sortable" => false, "align" => "center" ),
            array( "name" => "Nigth Diff Hrs",  "width" => "60", "sortable" => false, "align" => "right" ),
            array( "name" => "OT Hours",        "width" => "60", "sortable" => false, "align" => "right" ),
            array( "name" => "Holiday Hrs OT",  "width" => "60", "sortable" => false, "align" => "right" ),
            array( "name" => "Hrs UT",          "width" => "60", "sortable" => false, "align" => "right" ),
            array( "name" => "Reg OT Pay",      "width" => "60", "sortable" => false, "align" => "right" ),
            array( "name" => "Holiday OT Pay",  "width" => "60", "sortable" => false, "align" => "right" ),
            array( "name" => "Night Diff Pay",  "width" => "60", "sortable" => false, "align" => "right" ),
            array( "name" => "Other Income",    "width" => "60", "sortable" => false, "align" => "right" ),
            array( "name" => "Undertime",       "width" => "60", "sortable" => false, "align" => "right" ),
            array( "name" => "Absences",        "width" => "60", "sortable" => false, "align" => "right" ),
            array( "name" => "Late Amount",     "width" => "60", "sortable" => false, "align" => "right" ),
            array( "name" => "Other Deduction", "width" => "60", "sortable" => false, "align" => "right" ),
            array( "name" => "Loan",            "width" => "60", "sortable" => false, "align" => "right" ),
            array( "name" => "Allowances",      "width" => "60", "sortable" => false, "align" => "right" )
        );

        $this->xjqgrid->initLocal( $Model );
        $this->xjqgrid->grid->setGridOptions(array(
                "autowidth"     => true,
                "height"        => 43,
                
            ));
        
        $subtable = '#'.$subtable."_t";
        $pager = '#'.$subtable."_p"; 
        
        $this->xjqgrid->grid->callGridMethod($subtable, 'addRowData', array("ID", $data));
        $this->xjqgrid->renderGrid($subtable, $pager, true, null, array(&$empid), true, true, true);
        $this->xjqgrid->close();
    }
    
	function compute( $payrollid=0, $empid=0 ){
		$sql = "SELECT * FROM settings";
		$setting = array();
		$settings = $this->db->query($sql);
		$this->load->model( 'payrollmd' );
		
		foreach( $settings->result() as $r ){
			$setting[$r->name]=$r->value;
		}
		
		$sql = "SELECT e.idnumber, pd.empid, e.firstname, e.lastname, e.middlename, e.taxcode, e.bankacct, e.email, 
				e.sssded, e.philhealthded, e.pagibigded,
				b.amount, b.allowance, b.maxhours, b.maxbreakhours, b.maxworkdays, b.nightdiffin, b.nightdiffout, b.halfday,
				b.sss, b.pagibig, b.philhealth, b.daily
				FROM payroll_detail pd 
				INNER JOIN employee e ON e.empid=pd.empid
				INNER JOIN basic b ON b.empid=pd.empid
				INNER JOIN schedule s ON s.empid=pd.empid 
				WHERE payrollid={$payrollid} 
				AND pd.empid={$empid};";
		$row = $this->db->query($sql)->row();
		
		//payroll
		$sql = "SELECT * FROM payroll WHERE payrollid={$payrollid};";
		$payroll = $this->db->query($sql)->row();
		
		//merits
		$merit_basic 			= $this->common->merit( $empid, $payrollid, 'amount' );
		$merit_allowance 		= $this->common->merit( $empid, $payrollid, 'allowance' );
		$merit_sss 				= $this->common->merit( $empid, $payrollid, 'sss' );
		$merit_pagibig 			= $this->common->merit( $empid, $payrollid, 'pagibig' );
		$merit_philhealth 		= $this->common->merit( $empid, $payrollid, 'philhealth' );
		$merit_maxhours 		= $this->common->merit( $empid, $payrollid, 'maxhours' );
		$merit_maxbreakhours 	= $this->common->merit( $empid, $payrollid, 'maxbreakhours' );
		$merit_maxworkdays		= $this->common->merit( $empid, $payrollid, 'maxworkdays' );
		$merit_halfday			= $this->common->merit( $empid, $payrollid, 'halfday', true );
		$merit_daily			= $this->common->merit( $empid, $payrollid, 'daily', true );

		//benefits setting
		$dedductsss 			= $this->common->merit( $empid, $payrollid, 'sssded', true );
		$dedductsss 			= (($dedductsss!='') ? $dedductsss : $row->sssded);
		$dedductpagibig 		= $this->common->merit( $empid, $payrollid, 'pagibigded', true );
		$dedductpagibig 		= (($dedductpagibig!='') ? $dedductpagibig : $row->pagibigded);
		$dedductphilhealth		= $this->common->merit( $empid, $payrollid, 'philhealthded', true );
		$dedductphilhealth 		= (($dedductphilhealth!='') ? $dedductphilhealth : $row->philhealthded);
		$dedducttax				= $this->common->merit( $empid, $payrollid, 'taxded', true );
		$dedducttax 			= (($dedducttax!='') ? $dedducttax : $row->philhealthded);

		$overridesss 			= $this->common->merit( $empid, $payrollid, 'sssovr', true );
		$overridesss 			= (($overridesss!='') ? $dedductsss : $row->sssovr);
		$overridepagibig 		= $this->common->merit( $empid, $payrollid, 'pagibigovr', true );
		$overridepagibig 		= (($overridepagibig!='') ? $dedductpagibig : $row->pagibigovr);
		$overridephilhealth		= $this->common->merit( $empid, $payrollid, 'philhealthovr', true );
		$overridephilhealth 		= (($overridephilhealth!='') ? $dedductphilhealth : $row->philhealthovr);
		
		//get employee schedule
		$sql = "SELECT timein, timeout FROM schedule WHERE empid={$row->empid};";
		$scheds = $this->db->query( $sql );
		$sched = array();
		foreach( $scheds->result() as $key=>$value ){
			$sched[$key][] = strtotime($value);
		}
		
		//reg hours
		$sql = "SELECT pd.*, h.dates as holiday_date, h.special, h.remark, b.*,
				s.timein as stimein, s.timeout as stimeout FROM payroll_detail pd
				LEFT JOIN holidays h ON h.payrollid=pd.payrollid AND pd.detaildate=h.dates 
				LEFT JOIN basic b ON b.empid=pd.empid
				LEFT JOIN schedule s ON s.scheduleid=pd.scheduleid
				WHERE pd.payrollid={$payrollid} AND pd.empid={$row->empid};";
		$emp = $this->db->query($sql);
		
		//with merits
		$basic_amount 		= ($row->amount+($merit_basic));
		$basic_maxhours 	=  ($row->maxhours+($merit_maxhours));
		$basic_maxworkdays 	= ($row->maxworkdays+($merit_maxworkdays));
		$basic_allowance 	= ($row->allowance+($merit_allowance));
		
		//daily rate 
		$rate_per_day 		= $this->payrollmd->rate_per_day($basic_amount, $basic_maxworkdays);
		$rate_per_hour		= $this->payrollmd->rate_per_hour($basic_amount, $basic_maxworkdays, $basic_maxhours);
		
		$bi_basic			= round($basic_amount/2, 2);
		$bi_allowance		= round($basic_allowance/2, 2);
		
		$allowance_per_hour = $this->payrollmd->allowance_per_hour($basic_allowance, $basic_maxworkdays, $basic_maxhours);
		$allowance_per_day	= $this->payrollmd->allowance_per_day($basic_allowance, $basic_maxworkdays);

		
		//time difference calculation
		$reg_hours 			= 0;
		$ot_hours			= 0;
		$ot_hours_holiday 	= 0;
		$nitediff_hours		= 0;
		
		$late 				= 0;
		$halfday_cnt 		= 0;
		$holiday_cnt		= 0;
		$pl 				= 0;
		
		$nitediff_pay		= 0;
		$holiday_ot_pay		= 0;
		
		$allowance			= 0;
		$absent 			= 0;
		$undertime 			= 0;
		$halfday 			= (($merit_halfday!='')?$merit_halfday:$e->halfday);
		$daily 				= ((isset($merit_daily))?$merit_daily:$e->daily);
		
		//DTR
		foreach( $emp->result() as $e ){
			//check if whole day work or halfday
			//day
			$day = date('w',strtotime($e->detaildate));
			$hour = $this->payrollmd->timediff($e->timein, $e->timeout);
			
			//breakhours with merits
			$maxbreakhours 	= $e->maxbreakhours+$merit_maxbreakhours;
			
			if($halfday==$day && $halfday!= ''){ //check if halfday
				//compute regular hours
				$hour = ((($hour > round(($e->maxhours/2), 2) || ($hour<($e->maxhours/2) && $e->undertime==0)) && $hour != 0 )?round(($e->maxhours/2), 2):$hour);
				$halfday_cnt  += 1;	
				$absent += (($e->absent == 1) ? 0.5 : 0);
			}else{
				//compute regular hours
				$hour = (($hour > 4) ? ($hour - $maxbreakhours) : $hour);
				$hour = (($hour > $e->maxhours)?$e->maxhours:$hour);
				$absent += (($e->absent == 1) ? 1 : 0);
			}
			
			//compute overtime
			//$ot_hours += ( $e->overtime == 1 )?$this->payrollmd->overtime($e->scheduleid, $e->timein, $e->timeout):0;

			//compute other overtime
			$ql = "SELECT * FROM payroll_detail_ot WHERE pdetailid={$e->pdetailid};";
			$qq = $this->db->query( $ql );
			foreach($qq->result() as $rr){
				$ot_hours += $this->payrollmd->timediff($rr->timein, $rr->timeout);
			}
			
			//night differential
			$nitediff_hours += $this->payrollmd->nightdiff($row->empid, $e->timein, $e->timeout, $payrollid);
			
			//if($e->opentime != 1 && $e->undertime == 1)
			//	$undertime += $this->payrollmd->undertime($e->scheduleid, $e->timein, $e->timeout);

			//compute other under time
			$ql = "SELECT * FROM payroll_detail_ut WHERE pdetailid={$e->pdetailid};";
			$qq = $this->db->query( $ql );
			$isutime = 0;
			foreach($qq->result() as $rr){
				$undertime += $this->payrollmd->timediff($rr->timein, $rr->timeout);
				$isutime = 1;
			}
			
			if( strtotime($e->holiday_date) == strtotime($e->detaildate) && $daily == 0){
				//holiday ot hours
				$ot_hours_holiday += $hour;
				
				//holiday ot pay for regular rate
				$basic_holiday_ot_pay 		= (($rate_per_hour*(($e->special==1)?$setting['special_holiday']:$setting['legal_holiday']))*$hour);
				$allowance_holiday_ot_pay 	= (($allowance_per_hour*(($e->special==1)?$setting['special_holiday']:$setting['legal_holiday']))*$hour);
				$holiday_ot_pay			   += ($basic_holiday_ot_pay+$allowance_holiday_ot_pay);
				
				$holiday_cnt += ($halfday==$day && $halfday!= '')?0.5:1;
			}else{
			
				$reg_hours += $hour;
			}
			
			//late counter 
			$timein 	 = strtotime(date('H:i', strtotime($e->timein)));
			$timeout 	 = strtotime(date('H:i', strtotime($e->timeout)));
			
			$late 		+= (($timein>strtotime($e->stimein) && 
							 $timein != $timeout && 
							 $isutime != 1 && 
							 $e->opentime != 1 &&
							 strtotime($e->holiday_date) != strtotime($e->detaildate))?1:0);
			
			
			//paid leave
			$pl += ($e->paidleave==1)?(($halfday==$day)?0.5:1):0;
		}
		
		//check late deduction
		if($late >= $setting['maxlate']){
			$late_basic 		= $rate_per_day;
			$late_allowance 	= $allowance_per_day;
			$less_late 			= $rate_per_day+$allowance_per_day; 
		}else{ 
			$late_basic 		= 0;
			$late_allowance 	= 0;
			$less_late 			= 0; 
		}
		
		//check reg hours
		$reghours = $this->payrollmd->maxhours($basic_maxworkdays, $basic_maxhours)-($halfday_cnt*round(($sched->maxhours/2), 2)).'('.round($reg_hours,2).')';
		
		//basic
		$basic = $reg_hours*$rate_per_hour;
		
		//night differentila payment
		$basic_nd_pay 		= (($rate_per_hour*$setting['nightdiff'])*$nitediff_hours);
		$allowance_nd_pay 	= (($allowance_per_hour*$setting['nightdiff'])*$nitediff_hours);
		$nitediff_pay 		= ($basic_nd_pay + $allowance_nd_pay);
		
		//allowance
		$allowance = ($allowance_per_hour*$reg_hours);
		
		//compute paid leave
		//$paidleave = $pl*$rate_per_day + $allowance_per_day*$pl;
		$paidleave = $pl;
		
		//holiday pay
		//$holiday_pay = ($holiday_cnt*$rate_per_day)+($holiday_cnt*$allowance_per_day);
		$holiday_pay = 0;
		
		//regular overtime pay
		$basic_ot_pay   = (($rate_per_hour*$setting['reg_ot'])*$ot_hours);
		$allowance_ot_pay = (($allowance_per_hour*$setting['reg_ot'])*$ot_hours);
		$reg_ot_pay 	= ($basic_ot_pay + $allowance_ot_pay);
		
		//loan payment
		$sql = "SELECT sum(ld.amount) as amount
				FROM loan_payroll_detail lpd 
				LEFT JOIN loan_detail ld ON ld.ldetailid=lpd.ldetailid
				INNER JOIN loan l ON l.loanid=ld.loanid
				WHERE lpd.payrollid={$payrollid} AND l.empid={$row->empid} GROUP BY lpd.payrollid;";
		$loans = $this->db->query($sql)->row()->amount;
		
		//other deduction
		$sql = "SELECT sum(amount) as amount FROM other_deduction WHERE payrollid={$payrollid} AND empid={$row->empid};";
		$other_deduction = $this->db->query($sql)->row()->amount;
		
		//other deduction
		$sql = "SELECT sum(amount) as amount FROM other_income WHERE payrollid={$payrollid} AND empid={$row->empid};";
		$other_income = $this->db->query($sql)->row()->amount;
		
		//absences
		$basic_absent 		= ($absent*$rate_per_day);
		$allowance_absent 	= ($absent*$allowance_per_day);
		$totalabsent 		= $basic_absent+$allowance_absent;
		
		//undertime
		$basic_undertime 	 = ($undertime*$rate_per_hour);
		$allowance_undertime = ($undertime*$allowance_per_hour);	
		$undertime_amount 	 = $basic_undertime+$allowance_undertime;

		if($daily == 0){
			if($dedductsss == 1){				
				$total_sss = (($overridesss == 1)?($row->sss+$merit_sss):$this->payrollmd->getsss($basic_amount));
			}else{ $total_sss = 0; }
			if($dedductphilhealth == 1){
				$total_philhealth = (($overridephilhealth==1)?($row->philhealth+$merit_philhealth):$this->payrollmd->getphilhealth($basic_amount));
			}else{ $total_philhealth = 0; }
			if($dedductpagibig == 1){
				$total_pagibig = (($overridepagibig==1)?($row->pagibig+$merit_pagibig):$this->payrollmd->getpagibig($basic_amount));
			}else{ $total_pagibig = 0; }

			//$total_pagibig = $this->common->merit( $empid, $payrollid, 'pagibigovr', true );

			//$total_sss 			= (($dedductsss == 1)?$this->payrollmd->getsss($basic_amount):0);//($row->sss+$merit_sss);
			//$total_philhealth 	= (($dedductphilhealth == 1)?$this->payrollmd->getphilhealth($basic_amount):0);//($row->philhealth+$merit_philhealth);
			//$total_pagibig		= (($dedductpagibig == 1)?$this->payrollmd->getpagibig($basic_amount):0);//($row->pagibig+$merit_pagibig);
			//$total_pagibig = $merit_pagibig+$row->pagibig;
		}else{
			$total_sss = 0;
			$total_philhealth = 0;
			$total_pagibig = 0;
		}
		//$total_deds = $total_sss+$total_philhealth+$total_pagibig+$withholding+$loans+$other_deduction+$totalabsent+$undertime_amount+$less_late;

		//$gross_pay = ($basic+$reg_ot_pay+$holiday_pay+$holiday_ot_pay+$nitediff_pay+$paidleave+$allowance);
		//$gross_pay = ($bi_basic+$reg_ot_pay+$holiday_ot_pay+$nitediff_pay+$bi_allowance);
		$gross_pay = (($bi_basic+
					   $basic_ot_pay+
					   $basic_nd_pay+
					   $other_income+
					   $holiday_ot_pay)-(
					   $basic_undertime+
					   $basic_absent+
					   $late_basic+
					   $other_deduction+$loans));

		//w-tax
		$sql = "SELECT wtc.wtcodeid, wtc.name, wtb.value as basic, wtbr.value, wtbr.percent
				FROM wtax_code wtc
				INNER JOIN wtax_basic wtb ON wtb.wtcodeid=wtc.wtcodeid
				INNER JOIN wtax_bracket wtbr ON wtbr.wtbracketid=wtb.bracketid WHERE wtc.wtcodeid={$row->taxcode}
				AND wtb.value BETWEEN 0 AND {$gross_pay} ORDER BY wtb.value DESC LIMIT 1";
		$tax_rate = $this->db->query($sql)->row();
		
		if($daily == 0){
			//$tax_on_excess = ($bi_basic-$tax_rate->basic)*$tax_rate->percent;
			$tax_on_excess = ($gross_pay-$tax_rate->basic)*$tax_rate->percent;
			$withholding = $tax_rate->value+$tax_on_excess;
			$withholding = (($dedducttax==1)?$withholding:0);
		}else{
			$withholding = 0;
		}
		//net pay
		//$netpay = $gross_pay+$other_income-$total_deds;
		$netpay = $gross_pay-($withholding+$total_sss+$total_philhealth+$total_pagibig)+
		(($bi_allowance+$allowance_ot_pay+$allowance_nd_pay)-($allowance_undertime+$allowance_absent+$late_allowance));

		return array(
			'idnumber'	=> $row->idnumber,
			'name'				=> $row->firstname.'&nbsp;'.$row->middlename.'&nbsp;'.$row->lastname,
			'rate_per_day' 		=> $rate_per_day,
			'rate_per_hour' 	=> $rate_per_hour,
			'basic'				=> $bi_basic,
			'allowance'			=> $bi_allowance,
			'ot_hours'			=> $ot_hours,
			'ot_hours_holiday'	=> $ot_hours_holiday,
			'undertime'			=> $undertime,
			'undertime_amount'	=> $undertime_amount,
			'reg_ot_pay'		=> $reg_ot_pay,
			'basic_ot_pay'		=> $basic_ot_pay,
			'allowance_ot_pay'	=> $allowance_ot_pay,
			'nitediff_pay'		=> $nitediff_pay,
			'holiday_ot_pay'	=> $holiday_ot_pay,
			'gross_pay'			=> $gross_pay,
			'loans'				=> $loans,
			'netpay'			=> $netpay,
			'late'				=> $less_late,
			'absent'			=> $totalabsent,
			'sss'				=> $total_sss,
			'philhealth'		=> $total_philhealth,
			'pagibig'			=> $total_pagibig,
			'withholding'		=> $withholding,
			'reg_hours'			=> $reg_hours,
			'late_hour'			=> $late,
			'nitediff_hours'	=> $nitediff_hours,
			'other_deduction'	=> $other_deduction,
			'other_income'		=> $other_income,
			'reghours'			=> $reghours,
			'bankacct'			=> $row->bankacct,
			'email'				=> $row->email
		);
	}

	function getschedule($empid){
        if($empid == 0){ echo 'Please select an employee.';}
		else{
		    $this->load->helper('form');
		    $sql = "SELECT * FROM schedule WHERE empid={$empid}";
		    $query = $this->db->query($sql);
		    $sched = array(''=>'[----Select One----]');
		    foreach($query->result() as $row)
			    $sched[$row->scheduleid] = date('h:i A',strtotime($row->timein)).' - '.date('h:i A',strtotime($row->timeout));
		    echo form_dropdown( 'scheduleid', $sched, '' );
		}
	}

	function holiday($payrollid=0){
	    $tag['jscript']     = array('i18n/grid.locale-en', 'jquery.jqGrid.min', 'jquery-ui-custom.min');	    
        $tag['css']         = array('themes/ui.jqgrid', 'themes/ui.multiselect');
        $sql = "SELECT * FROM payroll WHERE payrollid={$payrollid};";
		$payroll = $this->db->query($sql)->row();
		
		$datefrom = explode("-", $payroll->payperiod_from);
		$dateto = explode("-", $payroll->payperiod_to);
		$tag['datefrom'] = date('M d, Y', mktime(0,0,0,$datefrom[1],$datefrom[2],$datefrom[0]));
		$tag['dateto'] = date('M d, Y', mktime(0,0,0,$dateto[1],$dateto[2],$dateto[0]));
		
        $tag['table'] = $this->listholiday($payrollid, false);
		$this->common->display('member/payroll/holiday.html', $tag); 
	}

	function listholiday($payrollid=0, $echo = false){
	    $this->load->library('xjqgrid');
        $sql = "SELECT 
                    holidayid,
                    remark,
                    DATE_FORMAT(dates, '%m/%d/%Y') AS dates,
                    special 
                FROM holidays WHERE payrollid={$payrollid};";

        $this->xjqgrid->initGrid( $sql );
        $this->xjqgrid->setMethod('setUrl', "payroll/listholiday/{$payrollid}/1");
        $oper = $_POST['oper'];
        if($oper == 'add'){
            $_POST['payrollid'] = $payrollid;
            $this->xjqgrid->setProperty('table', 'holidays');
            $this->xjqgrid->setMethod('setPrimaryKeyId', "holidayid");
        }elseif($oper == 'del'){
            $this->xjqgrid->setProperty('table', 'holidays');
            $this->xjqgrid->setMethod('setPrimaryKeyId', "holidayid");            
            $data = $_POST;
            $this->xjqgrid->grid->delete($data);        
        }else{
            $this->xjqgrid->setProperty('table', 'holidays');
            $this->xjqgrid->setMethod('setPrimaryKeyId', "holidayid");
        }
        $this->xjqgrid->setColProperty("holidayid", array("label"=>"ID", "hidden"=>true, "editable"=>false));        
        $this->xjqgrid->setColProperty("dates", array(
            "label"     => "Dates",
            "sortable"  => false,
            "width"     => 60,
            "align"     => "center",
            "datefmt"   => "mm/dd/YYYY",
            "editrules" => array(
                    "required"  => true,                    
                    "date"   => true
                ) 
            ));
        $this->xjqgrid->grid->setDatepicker("dates",
            array(
                "buttonOnly"=>false, 
                "dateFormat"=>"mm/dd/yy"));
        $this->xjqgrid->setColProperty("remark", 
                array( "label"    => "Remark", 
                       "sortable" => false,
                       "editrules"=> array(
                            "required" => true
                   )));
        $this->xjqgrid->setColProperty("special", 
            array(
                "label"     => "Special", 
                "width"     => 40,
                "sortable"  => false,
                "formatter" => "checkbox",
                "align"     => "center",
                "edittype"  => "checkbox",
                "editoptions" => array(
                    "value" => "1:0"
                )));
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
            "edit"  => false,
            "del"   => false,
            "view"  => false, 
            "search"=> false));             
        $this->xjqgrid->grid->setGridOptions(array( 
            "autowidth"     => true,
            "height"        => 335,
            "rownumbers"    => true, 
            "hoverrows"     => true,
            "rownumWidth"   => 35, 
            "rowNum"        => 15,
            "altRows"       => true,
            "rowList"       => array(15,30,100),
            ));
            
        if(!$echo)
            return $this->xjqgrid->renderGrid('#grid','#pager',true, null, null, true, true, $echo);
            
        $this->xjqgrid->renderGrid('#grid','#pager',true, null, null, true, true, $echo);
        $this->xjqgrid->close();
	}

	function alphalist($payrollid=0){
	    $tag['jscript']     = array('i18n/grid.locale-en', 'jquery.jqGrid.min', 'jquery-ui-custom.min');	    
        $tag['css']         = array('themes/ui.jqgrid', 'themes/ui.multiselect');
       
		$sql = "SELECT * FROM settings";
		$setting = array();
		$settings = $this->db->query($sql);
		
		foreach( $settings->result() as $r ){
			$setting[$r->name]=$r->value;
		}

        //##########################################################################
        //clean cache data for alphalist
        $sql = "DELETE FROM payroll_data WHERE payrollid={$payrollid};";
        $this->db->query($sql);

        //
        $sql = "SELECT e.idnumber, pd.empid, e.firstname, e.lastname, e.middlename, e.taxcode, e.bankacct, e.email,
				b.amount, b.allowance, b.maxhours, b.maxbreakhours, b.maxworkdays, b.nightdiffin, b.nightdiffout, b.halfday,
				b.sss, b.pagibig, b.philhealth
				FROM payroll_detail pd 
				INNER JOIN employee e ON e.empid=pd.empid
				INNER JOIN basic b ON b.empid=pd.empid
				INNER JOIN schedule s ON s.empid=pd.empid 
				WHERE payrollid={$payrollid} 
				GROUP BY pd.empid ORDER BY lastname asc;";

		$currentemp = $this->db->query($sql);
		foreach($currentemp->result() as $i=>$row){
	        $tpay = $this->compute($payrollid, $row->empid);		        
            $sql = "INSERT INTO payroll_data(payrollid, empname, empid, basic, gross, wtax, sss, phlt, pagibig, netpay)
                     VALUES({$payrollid},
                        '".$row->lastname.', '.$row->firstname.' '.$row->middlename."',
                        ".$row->empid.",
                        '".$tpay['basic']."',
                        '".$tpay['gross_pay']."',
                        '".$tpay['withholding']."',
                        '".$tpay['sss']."',
                        '".$tpay['philhealth']."',
                        '".$tpay['pagibig']."',
                        '".$tpay['netpay']."');";
	        $this->db->query( $sql );
	    }
		//###########################################################################
		
		$sql = "SELECT * FROM payroll WHERE payrollid={$payrollid};";
		$payroll = $this->db->query($sql)->row();
		$datefrom = explode("-", $payroll->payperiod_from);
		$dateto = explode("-", $payroll->payperiod_to);
		$tag['datefrom'] = date('M d, Y', mktime(0,0,0,$datefrom[1],$datefrom[2],$datefrom[0]));
		$tag['dateto'] = date('M d, Y', mktime(0,0,0,$dateto[1],$dateto[2],$dateto[0]));
		$tag['payrollid'] = $payrollid;
		$tag['alphalist'] = $this->payalpha($payrollid, false);
		$this->common->display('member/payroll/alphalist.html', $tag);
	}

	function payalpha($payrollid=0, $echo=true){
	    $this->load->library('xjqgrid');
        $sql = "SELECT pd.empid, pd.empname, pd.basic, pd.gross, pd.wtax, pd.sss, pd.phlt, pd.pagibig, pd.netpay, e.bankacct  
                FROM payroll_data pd 
                LEFT JOIN employee e ON e.empid=pd.empid
                WHERE payrollid={$payrollid} ";

		$export = "SELECT pd.empname as Name, e.bankacct as `Account ID`, pd.netpay as Amount
                FROM payroll_data pd 
                LEFT JOIN employee e ON e.empid=pd.empid
                WHERE payrollid={$payrollid} ";
                			
		$Model = array( 
            array(  "name"      => "empname", "label" => "Name" ),
            array(  "name"      => "bankacct",
                    "width"     => 80, 
                    "sortable"  => false,
                    "label"     => "Account ID"),
            array(  "name"      =>  "netpay",
                    "label"     => "Amount",
                    "width"     =>  80, 
                    "formatter" =>  "currency", 
                    "formatoptions" => array(
                            "decimalPlaces"     => 2,
                            "thousandsSeparator"=>","), 
                    "sorttype"  => "currency") 
        ); 

        $this->xjqgrid->initGrid( $sql );
        $this->xjqgrid->exportCommand($export);
        $this->xjqgrid->grid->setColModel($Model);
        $this->xjqgrid->setMethod('setUrl', "payroll/payalpha/{$payrollid}/");
        $this->xjqgrid->grid->navigator = true; 
        $this->xjqgrid->grid->setNavOptions('navigator', array(
            "excel" => true,
            "csv"   => true,
            "pdf"   => true,
            "add"   => false,
            "edit"  => false,
            "del"   => false,
            "view"  => false, 
            "search"=> false));
            
        $this->xjqgrid->grid->setGridOptions(array(            
            "autowidth"     => true,
            "height"        => 335,
            "rownumbers"    => true, 
            "hoverrows"     => true,
            "footerrow"     => true,
            "userDataOnFooter"=>true,
            "rownumWidth"   => 35, 
            "rowNum"        => 15,
            "altRows"       => true,
            "sortname"      => "empname",
            "sortorder"     => "asc",
            "caption"       => "Payroll Alphalist",
            "rowList"       => array(15,30,100),));
            
        $summaryrows = array("netpay"=>array( "netpay" => "SUM" ));
        
        if(!$echo)
            return $this->xjqgrid->renderGrid('#paygrid','#paypager',true, $summaryrows, null, true, true, $echo);
            
        $this->xjqgrid->renderGrid('#paygrid','#paypager',true, $summaryrows, null, true, true, $echo);
	}

	function sssalpha($payrollid=0, $echo=true){
        $this->load->library('xjqgrid');
        $sql = "SELECT pd.empid, pd.empname, pd.basic, pd.gross, pd.wtax, pd.sss, pd.phlt, pd.pagibig, pd.netpay, e.bankacct  
                FROM payroll_data pd 
                LEFT JOIN employee e ON e.empid=pd.empid
                WHERE payrollid={$payrollid} ";

		$export = "SELECT pd.empname as Name, e.bankacct as `Account ID`, pd.netpay as Amount
                FROM payroll_data pd 
                LEFT JOIN employee e ON e.empid=pd.empid
                WHERE payrollid={$payrollid} ";
                			
		$Model = array( 
            array(  "name"      => "empname", "label" => "Name" ),
            array(  "name"      => "bankacct",
                    "width"     => 80, 
                    "sortable"  => false,
                    "label"     => "SSS Numner"),
            array(  "name"      =>  "sss",
                    "label"     => "Employee Comp",
                    "width"     =>  80, 
                    "formatter" =>  "currency", 
                    "formatoptions" => array(
                            "decimalPlaces"     => 2,
                            "thousandsSeparator"=>","), 
                    "sorttype"  => "currency") 
        ); 

        $this->xjqgrid->initGrid( $sql );
        $this->xjqgrid->exportCommand($export);
        $this->xjqgrid->grid->setColModel($Model);
        $this->xjqgrid->setMethod('setUrl', "payroll/payalpha/{$payrollid}/");
        $this->xjqgrid->grid->navigator = true; 
        $this->xjqgrid->grid->setNavOptions('navigator', array(
            "excel" => true,
            "csv"   => true,
            "pdf"   => true,
            "add"   => false,
            "edit"  => false,
            "del"   => false,
            "view"  => false, 
            "search"=> false));
            
        $this->xjqgrid->grid->setGridOptions(array(            
            "autowidth"     => true,
            "height"        => 335,
            "rownumbers"    => true, 
            "hoverrows"     => true,
            "footerrow"     => true,
            "userDataOnFooter"=>true,
            "rownumWidth"   => 35, 
            "rowNum"        => 15,
            "altRows"       => true,
            "caption"       => "Payroll Alphalist",
            "rowList"       => array(15,30,100),));
            
        $summaryrows = array("netpay"=>array( "netpay" => "SUM" ));
        
        if(!$echo)
            return $this->xjqgrid->renderGrid('#paygrid','#paypager',true, $summaryrows, null, true, true, $echo);
            
        $this->xjqgrid->renderGrid('#paygrid','#paypager',true, $summaryrows, null, true, true, $echo);
	}

	function printit( $payrollid=0 ){
		$this->load->model('payrollmd');
		$tmpl = array (
                    'table_open'          => '<table class="printing" border="0" cellpadding="4" cellspacing="1" width="100%" style="background:#333;font-size:12px;font-family:tahoma">',
                     'heading_cell_start'  => '<th style="background:#CCC;color:#000">',
                    'heading_cell_end'    => '</th style="background:#CCC;color:#000">',
                    'row_start'           => '<tr style="background:#FFF">',
                    'row_alt_start'       => '<tr style="background:#FFF">'
              );

		$this->table->set_template($tmpl); 
		$sql = "SELECT * FROM settings";
		$setting = array();
		$settings = $this->db->query($sql);
		
		foreach( $settings->result() as $r ){
			$setting[$r->name]=$r->value;
		}
		
		$sql = "SELECT * FROM payroll WHERE payrollid={$payrollid};";
		$payroll = $this->db->query($sql)->row();
		$datefrom = explode("-", $payroll->payperiod_from);
		$dateto = explode("-", $payroll->payperiod_to);
		$tag['datefrom'] = date('M d, Y', mktime(0,0,0,$datefrom[1],$datefrom[2],$datefrom[0]));
		$tag['dateto'] = date('M d, Y', mktime(0,0,0,$dateto[1],$dateto[2],$dateto[0]));
		$tag['payrollid'] = $payrollid;
		
		$sql = "SELECT e.idnumber, pd.empid, e.firstname, e.lastname, e.middlename, e.taxcode,
					b.amount, b.allowance, b.maxhours, b.maxbreakhours, b.maxworkdays, b.nightdiffin, b.nightdiffout, b.halfday,
					b.sss, b.pagibig, b.philhealth
					FROM payroll_detail pd 
					INNER JOIN employee e ON e.empid=pd.empid
					INNER JOIN basic b ON b.empid=pd.empid
					INNER JOIN schedule s ON s.empid=pd.empid 
					WHERE payrollid={$payrollid} 
					GROUP BY pd.empid;";
		$currentemp = $this->db->query($sql);
		$this->table->clear();
		$this->table->set_heading('ID', 
					  'Name', 
					  'Rate', 
					  'Rate/Hour', 
					  'Reg Hr', 
					  '# Late', 
					  'Nigth Diff Hrs', 
					  'OT Hours', 
					  'Holiday Hrs OT', 
					  'Hrs UT',
                      'Basic Pay', 
                      'Reg OT Pay', 
                      'Holiday OT Pay', 
                      'Night Diff Pay', 
                      'Other Income',
                      'Undertime',
                      'Absences', 
                      'Late Amount',
                      'Other Deductions',
                      'Gross Pay', 
                      'WTax', 
                      'SSS', 
                      'Philhealth', 
                      'Pagibig', 
                      'Loan', 
                      'Allowances',
                      'Net Pay');
		$net_total = 0;
		foreach($currentemp->result() as $row){
			$tpay = $this->compute($payrollid, $row->empid);
			
			$this->table->add_row(align($tpay['idnumber'], 'c'),  		//id number
			$tpay['name'],												//employee name
			align(number_format($tpay['rate_per_day'], 2), 'r'), 		//rate per day
			align(number_format($tpay['rate_per_hour'], 2), 'r'),		//rate per hour
			align($tpay['reghours'], 'c'), 								//regular hours
			align($tpay['late_hour'], 'c'),								//late counter
			align(number_format($tpay['nitediff_hours'], 2), 'c'), 		//night diff hours
			align(number_format($tpay['ot_hours'], 2), 'r'), 			//over time hours
			align(number_format($tpay['ot_hours_holiday'], 2), 'c'), 	//holiday ot hours
			align(number_format($tpay['undertime'], 2), 'c'), 			//undertime hours
			align(number_format($tpay['basic'], 2), 'r'), 				//basic pay
			align(number_format($tpay['reg_ot_pay'], 2), 'r'), 			//regular overtime pay
			align(number_format($tpay['holiday_ot_pay'], 2), 'r'),  	//holiday ot pay
			align(number_format($tpay['nitediff_pay'], 2), 'r'),  		//night diff pay
			align(number_format($tpay['other_income'], 2), 'r'), 		//other income
			align(number_format($tpay['undertime_amount'], 2), 'r'), 	//undertime
			align(number_format($tpay['absent'], 2), 'r'),				//absences
			align(number_format($tpay['late'], 2), 'r'),				//absences
			align(number_format($tpay['other_deduction'], 2), 'r'), 	//other deduction
			align(number_format($tpay['gross_pay'], 2), 'r'), 			//grosspay
			align(number_format($tpay['withholding'], 2), 'r'), 		//withholding tax
			align(number_format($tpay['sss'], 2), 'r'), 				//sss
			align(number_format($tpay['philhealth'], 2), 'r'), 			//philhealth
			align(number_format($tpay['pagibig'], 2), 'r'), 			//pagibig
			align(number_format($tpay['loans'], 2), 'r'), 				//loans
			align(number_format($tpay['allowance'], 2), 'r'), 			//allowance
			align(number_format($tpay['netpay'], 2), 'r') );			//netpay
			$net_total += $tpay['netpay'];
		}
		
		$this->table->add_row('&nbsp;','&nbsp;','&nbsp;','&nbsp;','&nbsp;','&nbsp;','&nbsp;','&nbsp;','&nbsp;','&nbsp;','&nbsp;','&nbsp;','&nbsp;','&nbsp;','&nbsp;','&nbsp;','&nbsp;','&nbsp;','&nbsp;','&nbsp;','&nbsp;','&nbsp;','&nbsp;','&nbsp;','&nbsp;','<strong>Total</strong>', align(number_format($net_total, 2), 'r'));
		
		//$tag['payroll_calculator'] = '<div style="width:745px;overflow:auto">'.$this->table->generate().'</div>';
		$tag['content']  = '<div>Payroll Period: <span style="text-decoration:underline">'.$tag['datefrom'].' - '.$tag['dateto'].'</span></div>';
		$tag['content'] .= '<div style="padding-bottom:50px">'.$this->table->generate().'</div>';
		$tag['content'] .= '<table cellpadding="2" cellspacing="0" width="100%" border="0" style="font-size:12px">
			<tr>
				<td width="35%" align="center"><span style="font-weight:bold">Sarah S. Enopia</span><br/>Prepared by</td>
				<td align="center"><span style="font-weight:bold">Arlyn Siarot</span><br/>Checked by</td>
				<td width="35%" align="center"><span style="font-weight:bold">Deo Dumaraos</span><br/>Approved by</td>
			</tr>
		</table>';
		echo $tag['content'];
		echo "<script>window.print();</script>";
	}

	function _get_intervals(){
		$sql = "SELECT * FROM `interval`";
		$query = $this->db->query($sql);
		$interval =array();
		foreach( $query->result() as $row )
			$interval[$row->intervalid]=$row->name;
		return $interval;
	}
	
	function _get_monthends(){
		$sql = "SELECT * FROM payroll_month_end";
		$query = $this->db->query($sql);
		$days =array();
		foreach( $query->result() as $row )
			$days[]=$row->date;
		return $days;
	}

	function payslip($payrollid=0, $empid=0, $return = false){
        $this->load->model('payrollmd');
		
		$sql = "SELECT * FROM settings";
		$setting = array();
		$settings = $this->db->query($sql);
		
		foreach( $settings->result() as $r ){
			$setting[$r->name]=$r->value;
		}
		
		$sql = "SELECT * FROM payroll WHERE payrollid={$payrollid};";
		$payroll = $this->db->query($sql)->row();
		$datefrom = explode("-", $payroll->payperiod_from);
		$dateto = explode("-", $payroll->payperiod_to);
		$tag['datefrom'] = date('M d, Y', mktime(0,0,0,$datefrom[1],$datefrom[2],$datefrom[0]));
		$tag['dateto'] = date('M d, Y', mktime(0,0,0,$dateto[1],$dateto[2],$dateto[0]));
		$tag['payrollid'] = $payrollid;
		
		//other deduction
		$sql = "SELECT * FROM other_deduction WHERE payrollid={$payrollid} AND empid={$empid};";
		$other_deduction = $this->db->query($sql);
		
		//other deduction
		$sql = "SELECT * FROM other_income WHERE payrollid={$payrollid} AND empid={$empid};";
		$other_income = $this->db->query($sql);
		
		$tpay = $this->compute($payrollid, $empid);

		$tag['payperiod']	= $tag['datefrom'].' - '.$tag['dateto'];
		$tag['name'] 		= $tpay['name'];
		$tag['basic'] 		= number_format($tpay['basic'], 2);
		$tag['allowance'] 	= number_format($tpay['allowance'], 2);
		
		$tag['holiday']		= number_format($tpay['holiday_ot_pay'], 2);
		$tag['overtime']	= number_format($tpay['reg_ot_pay'], 2);
		$tag['nightdiff']	= number_format($tpay['nitediff_pay'], 2);
		$tag['otherincome'] = array();
		$oincome = 0;
		foreach($other_income->result() as $x=>$r){
			$tag['otherincome'][$x] = array(
				'amount' => $r->amount,
				'remark' => $r->remark
			);
			$oincome += $r->amount;
		}
		
		$tag['totaladd']	= number_format(
				$tpay['holiday_ot_pay']+
				$tpay['holiday_pay']+
				$tpay['reg_ot_pay']+
				$tpay['nitediff_pay']+
				$oincome, 2);
		
		$tag['late']			= number_format($tpay['late'], 2);
		$tag['absent']			= number_format($tpay['absent'], 2);
		$tag['sss']				= number_format($tpay['sss'], 2); 
		$tag['philhealth']		= number_format($tpay['philhealth'], 2); 
		$tag['pagibig']			= number_format($tpay['pagibig'], 2);
		$tag['wtax']			= number_format($tpay['withholding'], 2);
		$tag['undertime']		= number_format($tpay['undertime_amount'], 2);
		$tag['otherdeduction']  = array();
		
		$odeduction = 0;
		foreach($other_deduction->result() as $x=>$r){
			$tag['otherdeduction'][$x] = array(
				'amount' => $r->amount,
				'remark' => $r->remark
			);
			$odeduction += $r->amount;
		}
		$tag['loans']		= number_format($tpay['loans'], 2);
		$tag['totalded']	= number_format($tpay['absent']+
									$tpay['late']+
									$tpay['sss']+
									$tpay['philhealth']+
									$tpay['pagibig']+
									$tpay['withholding']+
									$tpay['loans']+
									$tpay['undertime_amount']+
									$odeduction, 2);
		$tag['netpay']		= number_format($tpay['netpay'], 2);
		$tag['content']		= '';
		if(!$return)
			$tag['content']	   .=   '<div style="border-bottom:1px solid #666;padding-bottom:0px;padding: 8px 0px">
			                            <div class="fr"><a href="payroll/sendpayslip/'.$payrollid.'/'.$empid.'"><img src="image/send.png" align="absmiddle" title="Send to Email"></a></div>
			                            <div><a href="payroll/edit/'.$payrollid.'"><img src="image/exit.png"></a></div>
			                        </div>';
		
		$tag['content']    .= $this->parser->parse('member/payroll/payslip.html', $tag, TRUE);
		if( $return == true ){ return $tag['content'];}
		
		$this->common->display('member/payroll/payslip_holder.html', $tag);
	}

	function sendpayslip( $payrollid=0, $empid=0 ){
		$this->load->model('payrollmd');
		$this->load->model('mailermd');
		
		$sql = "SELECT * FROM settings";
		$setting = array();
		$settings = $this->db->query($sql);
		
		foreach( $settings->result() as $r ){
			$setting[$r->name]=$r->value;
		}
		
		$sql = "SELECT * FROM payroll WHERE payrollid={$payrollid};";
		$payroll = $this->db->query($sql)->row();
		$datefrom = explode("-", $payroll->payperiod_from);
		$dateto = explode("-", $payroll->payperiod_to);
		$tag['datefrom'] = date('M d, Y', mktime(0,0,0,$datefrom[1],$datefrom[2],$datefrom[0]));
		$tag['dateto'] = date('M d, Y', mktime(0,0,0,$dateto[1],$dateto[2],$dateto[0]));
		$sql = "SELECT email FROM employee WHERE empid={$empid};";
		$email = $this->db->query($sql)->row()->email;
		if($email){		
			$data['email'] = $email;
			$data['subject'] = 'Payroll Period: '.$tag['datefrom'].' - '.$tag['dateto'];
			$data['message'] = $this->payslip($payrollid, $empid, true);
			$this->mailermd->email($data);
			sleep(5);
			echo '<script>alert("Email Sent.");</script>';
		}else{
			echo '<script>alert("No Email Address Specified for this employee.");</script>';
		}
		
		redirect('payroll/payslip/'.$payrollid.'/'.$empid);
	}

	function ajaxsender( $payrollid = 0, $empid = 0 ){
		if($payrollid == 0 || $empid == 0 ){return false;}
        sleep(2); die();
		
		$sql = "SELECT * FROM payroll WHERE payrollid={$payrollid};";
		$payroll = $this->db->query($sql)->row();
		$datefrom = explode("-", $payroll->payperiod_from);
		$dateto = explode("-", $payroll->payperiod_to);
		$tag['datefrom'] = date('M d, Y', mktime(0,0,0,$datefrom[1],$datefrom[2],$datefrom[0]));
		$tag['dateto'] = date('M d, Y', mktime(0,0,0,$dateto[1],$dateto[2],$dateto[0]));
		$tag['payrollid'] = $payrollid;
		
		$sql = "SELECT e.idnumber, pd.empid, e.firstname, e.lastname, e.middlename, e.taxcode, e.email,
				b.amount, b.allowance, b.maxhours, b.maxbreakhours, b.maxworkdays, b.nightdiffin, b.nightdiffout, b.halfday,
				b.sss, b.pagibig, b.philhealth, pd.payrollid, p.payslipid
				FROM payroll_detail pd 
				INNER JOIN employee e ON e.empid=pd.empid
				INNER JOIN basic b ON b.empid=pd.empid
				INNER JOIN schedule s ON s.empid=pd.empid 
				LEFT JOIN payslip p ON p.payrollid=pd.payrollid AND p.empid=pd.empid
				WHERE pd.payrollid={$payrollid} AND p.emailed=0 AND pd.empid={$empid}
				GROUP BY pd.empid LIMIT 1;";
		$row = $this->db->query($sql)->row();
		
		$this->load->model('payrollmd');
		$this->load->model('mailermd');
		if($row->payslipid){
			$sql_ = "UPDATE payslip SET emailed=1, date_emailed='".date('Y-m-d')."' WHERE payslipid={$row->payslipid}";
			$this->db->query( $sql_ );
		}
		
		$data['email'] = $row->email;
		$data['subject'] = 'Payroll Period: '.$tag['datefrom'].' - '.$tag['dateto'];
		$data['message'] = $this->payslip($payrollid, $empid, true);
		if($this->mailermd->email($data)){
            echo 1;
		}
	}
}
