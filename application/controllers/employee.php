<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class employee extends CI_Controller {
    var $sub_nav;
    var $acctid = 0;
    var $ccompany = 0;
	function __construct(){
		parent::__construct();
		$this->common->verify('employee');
		
		$this->acctid = $_SESSION['acctid'];
		$this->ccompany = $_SESSION['dcompany'];
		
		$this->sub_nav = array(
		    'List'      =>  'employee/',
            'New'       =>  'employee/newemp',
            'Grouping'  =>  'employee/grouping',
            'Import'    =>  'employee/importemp'
        );
        $this->load->library('table');
		$tmpl = array (
                    'table_open'          => '<table border="0" cellpadding="4" cellspacing="0" width="100%" style="font-size: 12px">',
                    'row_start'           => '<tr class="tr_odd">',
                    'row_alt_start'       => '<tr class="tr_even">'
              );
        $this->table->set_template($tmpl); 
	}

    //list grouping
    function grouping(){
        $tag['__sub_nav__'] = $this->sub_nav;
        $tag['jscript']     = array('i18n/grid.locale-en', 'jquery.jqGrid.min', 'jquery-ui-custom.min');	    
        $tag['css']         = array('themes/ui.jqgrid', 'themes/ui.multiselect');
        
		$tag['grouping'] = $this->listgroup(false);
		$this->common->display("member/employee/grouping.html", $tag);
    }

    function listgroup($echo=true){
        $this->load->library('xjqgrid');
        $sql = "SELECT 
                    g.groupingid, 
                    g.name, 
                    g.empid,                     
                    e.email,
                    (SELECT count(gd.gdetailid) as cn FROM grouping_detail gd WHERE gd.groupingid=g.groupingid) as members
                FROM grouping g
				    LEFT JOIN employee e ON e.empid=g.empid 
				WHERE g.companyid={$this->ccompany} ";
		//CONCAT( e.lastname, ', ', e.firstname, ' ',e.middlename, '.') as supervisor,		
		$this->xjqgrid->initGrid( $sql );
        $this->xjqgrid->setMethod('setUrl', 'employee/listgroup/1');
        $this->xjqgrid->setMethod('setSubGridGrid', 'employee/groupmembers/');
        
        $oper = $_POST['oper'];
        if($oper == 'add'){
            $_POST['companyid'] = $this->ccompany;
            $this->xjqgrid->setProperty('table', 'grouping');
            $this->xjqgrid->setMethod('setPrimaryKeyId', "groupingid");            
        }elseif($oper == 'edit'){
            $this->xjqgrid->setProperty('table', 'grouping');
            $this->xjqgrid->setMethod('setPrimaryKeyId', "groupingid");
            $data = $_POST;            
            $this->xjqgrid->grid->update($data);
        }elseif($oper == "del"){
            $id = $_POST['false'];
            $sql = "SELECT count(gd.gdetailid) as cn FROM grouping_detail gd WHERE gd.groupingid={$id};";
            $member = $this->db->query($sql)->row()->cn;
            if($member == 0){
                $sql = "DELETE FROM grouping WHERE groupingid={$id}";
                $this->db->query($sql);   
            }
        }
        
        $this->xjqgrid->grid->setNavOptions('add',array("closeAfterAdd"=>true));
        $this->xjqgrid->setProperty('navigator', true);        
        $this->xjqgrid->setColProperty("groupingid", array("label"=>"ID", "hidden"=>true,));
        $this->xjqgrid->setColProperty("name", array(
            "label" => "Group Name",
            "sortable"=> false,
            "editrules" => array(
                    "required"  => true
                ) 
            ));
        $this->xjqgrid->setColProperty("email", 
            array(
                "label"     => "Email",
                "sortable"  => false,
                "editable"  => false));
        $this->xjqgrid->setColProperty("empid", array(
            "label" => "Group Lead",
            "sortable"=> false,
            "editrules" => array(
                    "required"  => true,                    
                    "integer"   => true
                ) 
            ));        
        $this->xjqgrid->setSelect("empid", "SELECT empid, CONCAT(lastname, ', ', firstname, ' ', middlename, '.') as name FROM employee WHERE companyid={$this->ccompany}", true, true, false);
        $this->xjqgrid->setColProperty("members", array("label"=>"Members", 
            "align"=>"center",
            "width"=>60, "editable"  => false));
        $this->xjqgrid->grid->addCol(array( 
                "name"      => "actions", 
                "label"     => "!",
                "formatter" => "actions", 
                "editable"  => false, 
                "sortable"  => false, 
                "resizable" => false, 
                "fixed"     => true, 
                "width"     => 50,
                "formatoptions" => array(
                        "keys"      => true,
                        "delbutton" => false,
                    ) 
                ), "first");
        $this->xjqgrid->grid->setNavOptions('navigator', array(
            "excel" => false,
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
            "subGrid"       => true,
            "rowList"       => array(15,30,100),
            "subGridOptions"=>array( 
                "plusicon"=>"ui-icon-triangle-1-e", 
                "minusicon"=>"ui-icon-triangle-1-s", 
                "openicon"=>"ui-icon-arrowreturn-1-e", 
                // load the subgrid data only once 
                // and the just show/hide 
               // "reloadOnExpand"=>false, 
                // select the row when the expand column is clicked 
                "selectOnExpand"=>true 
            )
            ));
            
        if(!$echo)
            return $this->xjqgrid->renderGrid('#grid','#pager',true, null, null, true, true, $echo);
            
        $this->xjqgrid->renderGrid('#grid','#pager',true, null, null, true, true, $echo);
        $this->xjqgrid->close();
    }

    function groupmembers(){
        $groupingid = $_POST['rowid'];
        $this->listgroupmembers($groupingid);
    }

    function listgroupmembers($groupingid = 0){
        $gridname = "#gmem_{$groupingid}";
        $gridpage = "#pgmem_{$groupingid}";
        $this->load->library('xjqgrid');
        $sql = "SELECT gd.gdetailid, gd.empid, 
					e.email, g.name as gender, c.name as civilstatus, DATE_FORMAT(e.birthdate, '%b %e, %Y') as birthdate 
				FROM grouping_detail gd 
				LEFT JOIN employee e ON e.empid=gd.empid
				LEFT JOIN gender g ON g.genderid=e.gender
				LEFT JOIN civil_status c ON c.civilstatusid=e.civilstatus
				WHERE gd.groupingid={$groupingid} ORDER BY e.lastname";
	    $this->xjqgrid->initGrid( $sql );
        $this->xjqgrid->setMethod('setUrl', "employee/listgroupmembers/{$groupingid}");
        $oper = $_POST['oper'];
        if($oper == 'add'){
            $_POST['groupingid'] = $groupingid;
            $this->xjqgrid->setProperty('table', 'grouping_detail');
            $this->xjqgrid->setMethod('setPrimaryKeyId', "gdetailid");            
        }else{
            $this->xjqgrid->setProperty('table', 'grouping_detail');
            $this->xjqgrid->setMethod('setPrimaryKeyId', "gdetailid");
        }
        $this->xjqgrid->setProperty('navigator', true); 
        $this->xjqgrid->setColProperty("gdetailid", array("label"=>"ID", "hidden"=>true, "editable"=>false));
        $this->xjqgrid->setColProperty("empid", array(
            "label"     => "Name",
            "sortable"  => false,
            "editrules" => array(
                    "required"  => true,                    
                    "integer"   => true
                ) 
            ));        
        $this->xjqgrid->setSelect("empid", "SELECT empid, CONCAT(lastname, ', ', firstname, ' ', middlename, '.') as name FROM employee WHERE companyid={$this->ccompany}", true, true, false);
        $this->xjqgrid->setColProperty("email", array(
            "label"     => "Email Address", 
            "editable"  => false, 
            "sortable"  => false,));
        $this->xjqgrid->setColProperty("gender", array(
            "label"     => "Gender", 
            "editable"  => false, 
            "sortable"  => false,
            "align"     => "center",
            "width"     => 40));
        $this->xjqgrid->setColProperty("civilstatus", array(
            "label"     => "Civil Status", 
            "editable"  => false, 
            "sortable"  => false,
            "align"     => "center",
            "width"     => 50));
        $this->xjqgrid->setColProperty("birthdate", array(
            "label"     => "Birth Date", 
            "editable"  => false, 
            "sortable"  => false,
            "width"     => 70));
        $this->xjqgrid->grid->setNavOptions('navigator', array(
            "excel" => false,
            "edit"  => false,
            "view"  => false, 
            "search"=> false,
            'edit')); 
        $this->xjqgrid->grid->setNavOptions('add', array(
            "addCaption"=>"Add Member",
            "bSubmit"       => "Save",
            "bCancel"       => "Cancel",
            "closeAfterAdd" => true,
            "dataheight"    => 60
        ));    
        $this->xjqgrid->grid->setGridOptions(array( 
            "autowidth"     => true,
            "height"        => 150,
            "hoverrows"     => true,
            "rownumWidth"   => 35, 
            "rowNum"        => 15,
            "altRows"       => true,
            "rowList"       => array(15,30,100)
            ));
            
        $this->xjqgrid->renderGrid($gridname, $gridpage,true, null, null, true, true, true); 
    }
     
	function index(){
	    
	    $tag['jscript'] = array('i18n/grid.locale-en', 'jquery.jqGrid.min', 'jquery-ui-custom.min');	    
        $tag['css'] = array('themes/ui.jqgrid', 'themes/ui.multiselect');
        
        $tag['__sub_nav__'] = $this->sub_nav;

		$tag['list_emp'] = $this->listempgrid(false);
		$this->common->display("member/employee/dashboard.html", $tag);
	}

	function listempgrid($echo = true){
        $this->load->library('xjqgrid');
        $sql = "SELECT empid, 
                       firstname, 
                       lastname, 
                       birthdate, 
                       (SELECT name from gender WHERE genderid=gender) as Gender, 
                       empid as Actions
                FROM employee WHERE companyid={$this->ccompany} AND active=1";
        $this->xjqgrid->initGrid( $sql );
        $this->xjqgrid->grid->ExportCommand = "";
        $this->xjqgrid->setMethod('setUrl', 'employee/listempgrid/1');
        $this->xjqgrid->setMethod('setSubGridGrid', 'employee/empsubdetails');
        $this->xjqgrid->setMethod('setPrimaryKeyId', "empid");
        $this->xjqgrid->setProperty('toolbarfilter', true);
        $this->xjqgrid->setProperty('navigator', true);
        $oper = $_POST['oper'];
        if($oper == 'del'){            
            $sql = "UPDATE employee SET active=0 WHERE empid={$_POST['empid']};";
            $this->db->query( $sql );
            $_POST = null;
        }
        
        $this->xjqgrid->setColProperty("empid", array("label"=>"ID", "hidden"=>true,));
        $this->xjqgrid->setColProperty("firstname", array("label"=>"First Name"));
        $this->xjqgrid->setColProperty("lastname", array("label"=>"Last Name"));
        $this->xjqgrid->setColProperty("Gender", array("width"=>"80"));
        $this->xjqgrid->setColProperty("birthdate", array(
            "label"         => "Birth Date",
            "width"         => "80",
            "formatter"     => "date",
            "formatoptions" => array("srcformat"=>"Y-m-d H:i:s","newformat"=>"M d, Y")
            )
        );
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
    var imageHtml = "<div style='padding-top: 2px'><a href='employee/editemp/" + cellValue + "' originalValue='" + cellValue + "' ><img title='Edit Record' src='image/pencil.png'></a>&nbsp;&nbsp;";         
        imageHtml += "<a href='employee/basic/" + cellValue + "' originalValue='" + cellValue + "'><img title='Edit Basic' src='image/coinstacks.png'></a>&nbsp;&nbsp;";
        imageHtml += "<a href='employee/loan/" + cellValue + "' originalValue='" + cellValue + "'><img title='Edit Loans' src='image/loan.png'></a>&nbsp;&nbsp;";
        imageHtml += "<a href='recurring/income/" + cellValue + "' originalValue='" + cellValue + "'><img title='Edit Recurring Income' src='image/rincome.png'></a>&nbsp;&nbsp;";
        imageHtml += "<a href='recurring/deduct/" + cellValue + "' originalValue='" + cellValue + "'><img title='Edit Recurring Deduction' src='image/rdeduct.png'></a>&nbsp;&nbsp;";    
        imageHtml += "<a href='employee/schedule/" + cellValue + "' originalValue='" + cellValue + "'><img title='Edit Schedule' src='image/calendar.png'></a></div>";        
    return imageHtml; 
} 
function unformat(cellValue, options, cellObject) { 
    return $(cellObject.html()).attr("originalValue"); 
}
CUSTOMJS;
        $this->xjqgrid->grid->setJSCode($customjs); 
        $this->xjqgrid->setSelect("Gender", "SELECT name, name as value FROM gender", false, false, true, array(""=>"All"));
        $this->xjqgrid->grid->setGridOptions(array( 
            "subGridOptions"=>array( 
                "plusicon"=>"ui-icon-triangle-1-e", 
                "minusicon"=>"ui-icon-triangle-1-s", 
                "openicon"=>"ui-icon-arrowreturn-1-e", 
                // load the subgrid data only once 
                // and the just show/hide 
                "reloadOnExpand"=>false, 
                // select the row when the expand column is clicked 
                "selectOnExpand"=>true 
            ) 
        )); 
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
            "altRows"       => true,
            "rownumWidth"   => 35, 
            "rowNum"        => 15,
            "sortname"      => "lastname",
            "rowList"       => array(15,30,100),
            ));

        if(!$echo)
            return $this->xjqgrid->renderGrid('#grid','#pager',true, null, null, true, true, $echo);
        $this->xjqgrid->renderGrid('#grid','#pager',true, null, null, true, true, $echo);
        $this->xjqgrid->close();
	}

	function empsubdetails(){
	    $this->load->library('xjqgrid');
        $rowid = $_REQUEST["rowid"];
        if(!$rowid) die("Missed parameters");
        // Get details
        $SQL = "SELECT * FROM employee WHERE empid=".(int)$rowid;
        $result = $this->db->query( $SQL )->row();
        
        $s = "<table border='0' cellpadding='4' cellspacing='0' width='100%'><tbody>";
        $s .= "<tr><td width='180'><b>SSS Number</b></td><td>: ".$result->sssnum."</td>";
        $s .= "<tr class='ui-priority-secondary'><td><b>PHIC Number</b></td><td>: ".$result->phicnum."</td>";
        $s .= "<tr><td width='180'><b>Email</b></td><td>: ".$result->email."</td>";
        $s .= "<tr class='ui-priority-secondary'><td><b>Bank Account</b></td><td>: ".$result->bankacct."</td>";
        $s .= "</tbody></table>";
        echo $s;
	}

    function newemp(){
        $tag['__sub_nav__'] = $this->sub_nav;
		$this->load->library('form_validation');
		$this->form_validation->set_error_delimiters('<div class="error">', '</div>');
		if(isset($_POST['newemp']) && $this->form_validation->run() == TRUE){
			
			$date = explode("/", $_POST['birthdate']);
			$_POST['birthdate'] = date("Y-m-d", mktime(0,0,0,$date[0],$date[1],$date[2]));
			$sql = "INSERT INTO employee(idnumber,accountid,companyid,firstname,lastname,middlename,gender,birthdate,civilstatus,taxcode,email,bankacct,sssnum,phicnum) 
							VALUES({$_POST['idnumber']},{$this->acctid},{$this->ccompany},'{$_POST['firstname']}','{$_POST['lastname']}','{$_POST['middlename']}',
							{$_POST['gender']},'{$_POST['birthdate']}',{$_POST['civilstatus']},{$_POST['wtaxcode']},'{$_POST['email']}','{$_POST['bankacct']}','{$_POST['sssnum']}','{$_POST['phicnum']}');";
			$this->db->query($sql);
			
			//$tag['errors'] = 'Successfully added new employee';
			redirect('employee/editemp/'.$this->db->insert_id());
		}else{
			$tag['errors'] = validation_errors();
		}
	
		$this->load->helper('form');
		$sql = "SELECT * FROM civil_status";
		$q = $this->db->query($sql);
		$civil_status[""]= '[----Select One----]';
		foreach($q->result() as $i)
			$civil_status[$i->civilstatusid]=$i->name;
		
		$sql = "SELECT * FROM wtax_code";
		$q = $this->db->query($sql);
		$wtax_code[""]= '[----Select One----]';
		foreach($q->result() as $i)
			$wtax_code[$i->wtcodeid]=$i->name;
			
		$tag['civil_status'] = form_dropdown('civilstatus', $civil_status);
		$tag['tax_code'] = form_dropdown('wtaxcode', $wtax_code);
		
		//$tag['content'] = $this->parser->parse('member/employee/new_emp.html', $tag, TRUE);
		
		$this->common->display("member/employee/new_emp.html", $tag);
	}

	function editemp($empid = 0){
        $this->load->library('form_validation');
		$this->form_validation->set_error_delimiters('<div class="error">', '</div>');
		$year  = date('Y');
		$tag['__sub_nav__'] = $this->sub_nav;
		//check existing approved payroll payrolls
		$isEditable = $this->common->iseditable($empid);
		
		if(isset($_POST['updateleave'])  && $this->form_validation->run('empleave') == TRUE){
			//leave credits
			$sql = "SELECT * FROM leave_credits WHERE empid={$empid} AND year={$year};";
			$leave = $this->db->query( $sql )->row();
			
			if(!$leave){
				$sql = "INSERT INTO leave_credits(empid,year,vacation,sick,emergency)
						VALUES({$empid},{$year},{$_POST['vacationleave']},
						{$_POST['sickleave']},{$_POST['emergencyleave']});";
				$this->db->query( $sql );
			}else{
				$sql = "UPDATE leave_credits SET vacation={$_POST['vacationleave']}, 
						sick={$_POST['sickleave']}, 
						emergency={$_POST['emergencyleave']} 
						WHERE empid={$empid} AND year={$year};";
				$this->db->query( $sql );
			}
			$tag['errors'] = '<div class="error">Successfully updated leave credits</div>';
		}elseif(isset($_POST['editemp']) && $isEditable == false && $this->form_validation->run('editemp') == TRUE){
			$date = explode("/", $_POST['birthdate']);
			$_POST['birthdate'] = date("Y-m-d", mktime(0,0,0,$date[0],$date[1],$date[2]));
			
			$sql = "UPDATE employee SET 
			            idnumber={$_POST['idnumber']}, 
			            firstname='{$_POST['firstname']}', 
			            lastname='{$_POST['lastname']}',
					    middlename='{$_POST['middlename']}', 
					    gender={$_POST['gender']}, 
					    birthdate='{$_POST['birthdate']}', 
					    civilstatus={$_POST['civilstatus']}, 
					    taxcode={$_POST['wtaxcode']}, 
					    email='{$_POST['email']}', 
					    bankacct='{$_POST['bankacct']}',
					    sssnum='{$_POST['sssnum']}',
					    phicnum='{$_POST['phicnum']}' 
					WHERE empid={$empid};";
			
			$this->db->query($sql);
			$tag['errors'] = '<div class="error">Successfully updated employee record.</div>';
		}elseif(isset($_POST['editemp']) && $isEditable == true && $this->form_validation->run('editemp') == TRUE){
			$date = explode("/", $_POST['birthdate']);
			$_POST['birthdate'] = date("Y-m-d", mktime(0,0,0,$date[0],$date[1],$date[2]));
			
			$sql = "UPDATE employee SET 
			            idnumber={$_POST['idnumber']}, 
			            firstname='{$_POST['firstname']}', 
			            lastname='{$_POST['lastname']}',
					    middlename='{$_POST['middlename']}', 
					    gender={$_POST['gender']}, 
					    birthdate='{$_POST['birthdate']}', 
					    civilstatus={$_POST['civilstatus']}, 
					    email='{$_POST['email']}', 
					    bankacct='{$_POST['bankacct']}',
					    sssnum='{$_POST['sssnum']}',
					    phicnum='{$_POST['phicnum']}' 
					WHERE empid={$empid};";
			
			$this->db->query($sql);
			$tag['errors'] = '<div class="error">Successfully updated employee record.</div>';
		}else{
			$tag['errors'] = validation_errors();
		}
		
		$sql = "SELECT * FROM employee WHERE empid={$empid};";
		$emp = $this->db->query($sql)->row();
		
		$this->load->helper('form');
		
		$sql = "SELECT * FROM civil_status";
		$q = $this->db->query($sql);
		$civil_status[""]= '[----Select One----]';
		foreach($q->result() as $i)
			$civil_status[$i->civilstatusid]=$i->name;
		
		$sql = "SELECT * FROM wtax_code";
		$q = $this->db->query($sql);
		$wtax_code[0]= '[----Select One----]';
		foreach($q->result() as $i)
			$wtax_code[$i->wtcodeid] = $i->name;
		
		//leave credits
		$sql = "SELECT * FROM leave_credits WHERE empid={$empid} AND year={$year};";
		$leave = $this->db->query( $sql )->row();
		
		$tag['year'] = $year;
		$tag['vacationleave'] = $leave->vacation;
		$tag['sickleave'] = $leave->sick;
		$tag['emergencyleave'] = $leave->emergency;
		
		$tag['readonly'] = (($isEditable)?'readonly':'');
		$tag['empid'] = $empid;
		$tag['idnumber'] = $emp->idnumber;
		$tag['firstname'] = $emp->firstname;
		$tag['lastname'] = $emp->lastname;
		$tag['middlename'] = $emp->middlename;
		$tag['birthdate'] = date('m/d/Y', strtotime($emp->birthdate));
		$tag['gender']	= form_dropdown('gender', array(''=>'[----Select One----]', '1'=>'Male', '2'=>'Female'), $emp->gender);
		$tag['civil_status'] = form_dropdown('civilstatus', $civil_status, $emp->civilstatus );
		if($isEditable){
			//halfday
			$taxcode = $this->common->getmerit($empid, 'taxcode');
			$curr_amount 	= (($taxcode['amount']!=-1)?$taxcode['amount']:$emp->taxcode);
			$effective 		= (($taxcode['date'])?('<div class="fr"><em>(dated '.$taxcode['date'].')</em></div>'):'');
			
			$tag['tax_code']  = '<a href="javascript:;" onclick="this.blur(); modalbox(\'employee/merit/taxcode/'.$empid.'/1\', {width:600,height:300,title:\'Update Tax-Code\'});"><img src="image/merit.png" align="absmiddle"></a> ';
			$tag['tax_code'] .= '<span>'.$wtax_code[$curr_amount].'</span>'.$effective;
		}else{
			$tag['tax_code'] = form_dropdown('wtaxcode', $wtax_code, $emp->taxcode);
		}		
		$tag['email'] 		  = $emp->email;
		$tag['bankacct'] 	  = $emp->bankacct;
        $tag['sssnum'] 		  = $emp->sssnum;
		$tag['phicnum'] 	  = $emp->phicnum;
		$tag['activation']    = '<a href="employee/sendactivation/'.$empid.'/'.$emp->refcode.'">Send Activation</a>';
		if($isEditable){
			$sssded = $this->common->getmerit($empid, 'sssded');
			$curr_amount 	= (($sssded['amount']!=-1)?$sssded['amount']:$emp->sssded);
			$effective 		= (($sssded['date'])?('<div class="fr"><em>(dated '.$sssded['date'].')</em></div>'):'');
			$tag['sssded']  = '<a href="javascript:;" onclick="this.blur(); modalbox(\'employee/merit/sssded/'.$empid.'/1\', {width:600,height:300,title:\'Update SSS Deduction\'});"><img src="image/merit.png" align="absmiddle"></a> ';
			$tag['sssded'] .= '<span>'.(($curr_amount==1)?'Yes':'No').'</span>'.$effective;

			$pagibigded = $this->common->getmerit($empid, 'pagibigded');
			$curr_amount 	= (($pagibigded['amount']!=-1)?$pagibigded['amount']:$emp->pagibigded);
			$effective 		= (($pagibigded['date'])?('<div class="fr"><em>(dated '.$pagibigded['date'].')</em></div>'):'');
			$tag['pagibigded']  = '<a href="javascript:;" onclick="this.blur(); modalbox(\'employee/merit/pagibigded/'.$empid.'/1\', {width:600,height:300,title:\'Update Pagibig Deduction\'});"><img src="image/merit.png" align="absmiddle"></a> ';
			$tag['pagibigded'] .= '<span>'.(($curr_amount==1)?'Yes':'No').'</span>'.$effective;

			$philhealthded = $this->common->getmerit($empid, 'philhealthded');
			$curr_amount 	= (($philhealthded['amount']!=-1)?$philhealthded['amount']:$emp->philhealthded);
			$effective 		= (($philhealthded['date'])?('<div class="fr"><em>(dated '.$philhealthded['date'].')</em></div>'):'');
			$tag['philhealthded']  = '<a href="javascript:;" onclick="this.blur(); modalbox(\'employee/merit/philhealthded/'.$empid.'/1\', {width:600,height:300,title:\'Update Pagibig Deduction\'});"><img src="image/merit.png" align="absmiddle"></a> ';
			$tag['philhealthded'] .= '<span>'.(($curr_amount==1)?'Yes':'No').'</span>'.$effective;

			$override = array( '0'=>'Auto', '1'=>'Manual' );

			$philhealthded = $this->common->getmerit($empid, 'philhealthded');
			$curr_amount 	= (($philhealthded['amount']!=-1)?$philhealthded['amount']:$emp->philhealthded);
			$effective 		= (($philhealthded['date'])?('<div class="fr"><em>(dated '.$philhealthded['date'].')</em></div>'):'');

			$sssovr = $this->common->getmerit($empid, 'sssovr');
			$curr_amount 	= (($sssovr['amount']!=-1)?$sssovr['amount']:$emp->sssovr);
			$effective 		= (($sssovr['date'])?('<div class="fr"><em>(dated '.$sssovr['date'].')</em></div>'):'');
			$tag['sssovr']  = '<a href="javascript:;" onclick="this.blur(); modalbox(\'employee/merit/sssovr/'.$empid.'/1\', {width:600,height:300,title:\'Update SSS Override\'});"><img src="image/merit.png" align="absmiddle"></a> ';
			$tag['sssovr'] .= '<span>'.$override[$curr_amount].'</span>'.$effective;

			$pagibigovr = $this->common->getmerit($empid, 'pagibigovr');
			$curr_amount 	= (($pagibigovr['amount']!=-1)?$pagibigovr['amount']:$emp->pagibigovr);
			$effective 		= (($pagibigovr['date'])?('<div class="fr"><em>(dated '.$pagibigovr['date'].')</em></div>'):'');
			$tag['pagibigovr']  = '<a href="javascript:;" onclick="this.blur(); modalbox(\'employee/merit/pagibigovr/'.$empid.'/1\', {width:600,height:300,title:\'Update SSS Override\'});"><img src="image/merit.png" align="absmiddle"></a> ';
			$tag['pagibigovr'] .= '<span>'.$override[$curr_amount].'</span>'.$effective;

			$philhealthovr = $this->common->getmerit($empid, 'philhealthovr');
			$curr_amount 	= (($philhealthovr['amount']!=-1)?$philhealthovr['amount']:$emp->philhealthovr);
			$effective 		= (($philhealthovr['date'])?('<div class="fr"><em>(dated '.$philhealthovr['date'].')</em></div>'):'');
			$tag['philhealthovr']  = '<a href="javascript:;" onclick="this.blur(); modalbox(\'employee/merit/philhealthovr/'.$empid.'/1\', {width:600,height:300,title:\'Update SSS Override\'});"><img src="image/merit.png" align="absmiddle"></a> ';
			$tag['philhealthovr'] .= '<span>'.$override[$curr_amount].'</span>'.$effective;
			
		}else{
			$tag['sssded'] 		  = '<input type="checkbox" name="sssded" value="1" '.(($emp->sssded!=0)?'checked':'').' />';
			$tag['pagibigded'] 	  = '<input type="checkbox" name="pagibigded" value="1" '.(($emp->pagibigded!=0)?'checked':'').' />';
			$tag['philhealthded'] = '<input type="checkbox" name="philhealthded" value="1" '.(($emp->philhealthded!=0)?'checked':'').' />';

			$override = array( '0'=>'Auto', '1'=>'Manual' );

			$tag['sssovr']		  = form_dropdown( 'sssover', $override, $emp->sssovr );
			$tag['pagibigovr']	  = form_dropdown( 'pagibigovr', $override, $emp->pagibigovr );
			$tag['philhealthovr'] = form_dropdown( 'philhealthovr', $override, $emp->philhealthovr );
		}
		
        //$tag['content'] 	  = $this->parser->parse('employees/edit_emp.html', $tag, TRUE);
		$this->common->display("member/employee/edit_emp.html", $tag);
	}

	function merit($type = '', $empid=0){
		$this->load->library('form_validation');
		$this->form_validation->set_error_delimiters('<div class="error">', '</div>');
		
		switch($type){
			case 'amount':{
				if(isset($_POST['delid'])){
					$sql = "DELET FROM middle_table WHERE mtableid={$_POST['delid']};";
					$this->db->query($sql);
				}elseif(isset($_POST['merits']) && $this->form_validation->run('merit') == TRUE){
					$sql = "SELECT mtable_typeid FROM mtable_type WHERE name='{$type}';";
					$mtabletype = $this->db->query($sql)->row()->mtable_typeid;
					$sql = "INSERT INTO middle_table(sourceid,mtabletype,entrydate,value)
							VALUES({$empid},{$mtabletype},'".date('Y-m-d', strtotime($_POST['entrydate']))."', {$_POST['value']});";
					$this->db->query($sql);
					$tag['errors'] = 'Successfully Added New Merit.';
				}else{
					$tag['errors'] = validation_errors();
				}
				
				$tag['merit_title'] = 'Merit Basic Amount';
				$sql = "SELECT mt.*, b.amount FROM middle_table mt
						LEFT JOIN basic b ON b.empid=mt.sourceid
						WHERE mt.sourceid={$empid} AND mtabletype=(SELECT mtable_typeid FROM mtable_type WHERE name='{$type}') 
						ORDER BY mt.entrydate ASC;";
				$query = $this->db->query( $sql );
				$this->table->clear();
				$this->table->set_heading('Date Effective', 'Merit Entry', 'New Basic', '!');
				$total_merit = 0;
				
				foreach($query->result() as $row){
					$date = date('M d, Y', strtotime($row->entrydate));
					$total_merit += $row->value;
					$this->table->add_row($date, $row->value, $row->amount+$total_merit, '<a href="javascript:;">Remove</a>');
				}
				$tag['empid'] = $empid;
				$tag['type'] = $type;
				$tag['current_merit'] = $this->table->generate();
			}break;
			case 'allowance':{
				if(isset($_POST['delid'])){
					$sql = "DELET FROM middle_table WHERE mtableid={$_POST['delid']};";
					$this->db->query($sql);
				}elseif(isset($_POST['merits']) && $this->form_validation->run('merit') == TRUE){
					$sql = "SELECT mtable_typeid FROM mtable_type WHERE name='{$type}';";
					$mtabletype = $this->db->query($sql)->row()->mtable_typeid;
					$sql = "INSERT INTO middle_table(sourceid,mtabletype,entrydate,value)
							VALUES({$empid},{$mtabletype},'".date('Y-m-d', strtotime($_POST['entrydate']))."', {$_POST['value']});";
					$this->db->query($sql);
					$tag['errors'] = 'Successfully Added New Merit.';
				}else{
					$tag['errors'] = validation_errors();
				}
				
				$tag['merit_title'] = 'Merit Allowance';
				$sql = "SELECT mt.*, b.allowance FROM middle_table mt
						LEFT JOIN basic b ON b.empid=mt.sourceid
						WHERE mt.sourceid={$empid} AND mtabletype=(SELECT mtable_typeid FROM mtable_type WHERE name='{$type}') 
						ORDER BY mt.entrydate ASC;";
				$query = $this->db->query( $sql );
				$this->table->clear();
				$this->table->set_heading('Date Effective', 'Merit Entry', 'New Allowance', '!');
				$total_merit = 0;
				foreach($query->result() as $row){
					$date = date('M d, Y', strtotime($row->entrydate));
					$total_merit += $row->value;
					$this->table->add_row($date, $row->value, $row->allowance+$total_merit, '<a href="javascript:;">Remove</a>');
				}
				$tag['empid'] = $empid;
				$tag['type'] = $type;
				$tag['current_merit'] = $this->table->generate();
			}break;
			case 'sss':{
				if(isset($_POST['delid'])){
					$sql = "DELET FROM middle_table WHERE mtableid={$_POST['delid']};";
					$this->db->query($sql);
				}elseif(isset($_POST['merits']) && $this->form_validation->run('merit') == TRUE){
					$sql = "SELECT mtable_typeid FROM mtable_type WHERE name='{$type}';";
					$mtabletype = $this->db->query($sql)->row()->mtable_typeid;
					$sql = "INSERT INTO middle_table(sourceid,mtabletype,entrydate,value)
							VALUES({$empid},{$mtabletype},'".date('Y-m-d', strtotime($_POST['entrydate']))."', {$_POST['value']});";
					$this->db->query($sql);
					$tag['errors'] = 'Successfully Added New SSS Value.';
				}else{
					$tag['errors'] = validation_errors();
				}
				
				$tag['merit_title'] = 'SSS Compensation';
				$sql = "SELECT mt.*, b.sss FROM middle_table mt
						LEFT JOIN basic b ON b.empid=mt.sourceid
						WHERE mt.sourceid={$empid} AND mtabletype=(SELECT mtable_typeid FROM mtable_type WHERE name='{$type}') 
						ORDER BY mt.entrydate ASC;";
				$query = $this->db->query( $sql );
				$this->table->clear();
				$this->table->set_heading('Date Effective', 'Amount', 'New Compensation', '!');
				$total_merit = 0;
				foreach($query->result() as $row){
					$date = date('M d, Y', strtotime($row->entrydate));
					$total_merit += $row->value;
					$this->table->add_row($date, number_format($row->value, 2), number_format($row->sss+$total_merit, 2), '<a href="javascript:;">Remove</a>');
				}
				$tag['empid'] = $empid;
				$tag['type'] = $type;
				$tag['current_merit'] = $this->table->generate();
			}break;
			case 'pagibig':{
				if(isset($_POST['delid'])){
					$sql = "DELET FROM middle_table WHERE mtableid={$_POST['delid']};";
					$this->db->query($sql);
				}elseif(isset($_POST['merits']) && $this->form_validation->run('merit') == TRUE){
					$sql = "SELECT mtable_typeid FROM mtable_type WHERE name='{$type}';";
					$mtabletype = $this->db->query($sql)->row()->mtable_typeid;
					$sql = "INSERT INTO middle_table(sourceid,mtabletype,entrydate,value)
							VALUES({$empid},{$mtabletype},'".date('Y-m-d', strtotime($_POST['entrydate']))."', {$_POST['value']});";
					$this->db->query($sql);
					$tag['errors'] = 'Successfully Added New Pagibig Value.';
				}else{
					$tag['errors'] = validation_errors();
				}
				
				$tag['merit_title'] = 'Pagibig Compensation';
				$sql = "SELECT mt.*, b.pagibig FROM middle_table mt
						LEFT JOIN basic b ON b.empid=mt.sourceid
						WHERE mt.sourceid={$empid} AND mtabletype=(SELECT mtable_typeid FROM mtable_type WHERE name='{$type}') 
						ORDER BY mt.entrydate ASC;";
				$query = $this->db->query( $sql );
				$this->table->clear();
				$this->table->set_heading('Date Effective', 'Amount', 'New Compensation', '!');
				$total_merit = 0;
				foreach($query->result() as $row){
					$date = date('M d, Y', strtotime($row->entrydate));
					$total_merit += $row->value;
					$this->table->add_row($date, number_format($row->value, 2), number_format($row->pagibig+$total_merit, 2), '<a href="javascript:;">Remove</a>');
				}
				$tag['empid'] = $empid;
				$tag['type'] = $type;
				$tag['current_merit'] = $this->table->generate();
			}break;
			case 'philhealth':{
				if(isset($_POST['delid'])){
					$sql = "DELET FROM middle_table WHERE mtableid={$_POST['delid']};";
					$this->db->query($sql);
				}elseif(isset($_POST['merits']) && $this->form_validation->run('merit') == TRUE){
					$sql = "SELECT mtable_typeid FROM mtable_type WHERE name='{$type}';";
					$mtabletype = $this->db->query($sql)->row()->mtable_typeid;
					$sql = "INSERT INTO middle_table(sourceid,mtabletype,entrydate,value)
							VALUES({$empid},{$mtabletype},'".date('Y-m-d', strtotime($_POST['entrydate']))."', {$_POST['value']});";
					$this->db->query($sql);
					$tag['errors'] = 'Successfully Added New Philhealth Value.';
				}else{
					$tag['errors'] = validation_errors();
				}
				
				$tag['merit_title'] = 'Philhealth Compensation';
				$sql = "SELECT mt.*, b.philhealth FROM middle_table mt
						LEFT JOIN basic b ON b.empid=mt.sourceid
						WHERE mt.sourceid={$empid} AND mtabletype=(SELECT mtable_typeid FROM mtable_type WHERE name='{$type}') 
						ORDER BY mt.entrydate ASC;";
				$query = $this->db->query( $sql );
				$this->table->clear();
				$this->table->set_heading('Date Effective', 'Amount', 'New Compensation', '!');
				$total_merit = 0;
				foreach($query->result() as $row){
					$date = date('M d, Y', strtotime($row->entrydate));
					$total_merit += $row->value;
					$this->table->add_row($date, number_format($row->value, 2), number_format($row->philhealth+$total_merit, 2), '<a href="javascript:;">Remove</a>');
				}
				$tag['empid'] = $empid;
				$tag['type'] = $type;
				$tag['current_merit'] = $this->table->generate();
			}break;
			case 'maxhours':{
				if(isset($_POST['delid'])){
					$sql = "DELET FROM middle_table WHERE mtableid={$_POST['delid']};";
					$this->db->query($sql);
				}elseif(isset($_POST['merits']) && $this->form_validation->run('merit') == TRUE){
					$sql = "SELECT mtable_typeid FROM mtable_type WHERE name='{$type}';";
					$mtabletype = $this->db->query($sql)->row()->mtable_typeid;
					$sql = "INSERT INTO middle_table(sourceid,mtabletype,entrydate,value)
							VALUES({$empid},{$mtabletype},'".date('Y-m-d', strtotime($_POST['entrydate']))."', {$_POST['value']});";
					$this->db->query($sql);
					$tag['errors'] = 'Successfully Added New Max Hours Value.';
				}else{
					$tag['errors'] = validation_errors();
				}
				
				$tag['merit_title'] = 'Max Hours';
				$sql = "SELECT mt.*, b.maxhours FROM middle_table mt
						LEFT JOIN basic b ON b.empid=mt.sourceid
						WHERE mt.sourceid={$empid} AND mtabletype=(SELECT mtable_typeid FROM mtable_type WHERE name='{$type}') 
						ORDER BY mt.entrydate ASC;";
				$query = $this->db->query( $sql );
				$this->table->clear();
				$this->table->set_heading('Date Effective', 'Hours', 'New Max Hours', '!');
				$total_merit = 0;
				foreach($query->result() as $row){
					$date = date('M d, Y', strtotime($row->entrydate));
					$total_merit += $row->value;
					$this->table->add_row($date, number_format($row->value, 2), number_format($row->maxhours+$total_merit, 2), '<a href="javascript:;">Remove</a>');
				}
				$tag['empid'] = $empid;
				$tag['type'] = $type;
				$tag['current_merit'] = $this->table->generate();
			}break;
			case 'maxbreakhours':{
				if(isset($_POST['delid'])){
					$sql = "DELET FROM middle_table WHERE mtableid={$_POST['delid']};";
					$this->db->query($sql);
				}elseif(isset($_POST['merits']) && $this->form_validation->run('merit') == TRUE){
					$sql = "SELECT mtable_typeid FROM mtable_type WHERE name='{$type}';";
					$mtabletype = $this->db->query($sql)->row()->mtable_typeid;
					$sql = "INSERT INTO middle_table(sourceid,mtabletype,entrydate,value)
							VALUES({$empid},{$mtabletype},'".date('Y-m-d', strtotime($_POST['entrydate']))."', {$_POST['value']});";
					$this->db->query($sql);
					$tag['errors'] = 'Successfully Added New Max Break Hours Value.';
				}else{
					$tag['errors'] = validation_errors();
				}
				
				$tag['merit_title'] = 'Max Break Hours';
				$sql = "SELECT mt.*, b.maxbreakhours FROM middle_table mt
						LEFT JOIN basic b ON b.empid=mt.sourceid
						WHERE mt.sourceid={$empid} AND mtabletype=(SELECT mtable_typeid FROM mtable_type WHERE name='{$type}') 
						ORDER BY mt.entrydate ASC;";
				$query = $this->db->query( $sql );
				$this->table->clear();
				$this->table->set_heading('Date Effective', 'Hours', 'New Max Break Hours', '!');
				$total_merit = 0;
				foreach($query->result() as $row){
					$date = date('M d, Y', strtotime($row->entrydate));
					$total_merit += $row->value;
					$this->table->add_row($date, number_format($row->value, 2), number_format($row->maxbreakhours+$total_merit, 2), '<a href="javascript:;">Remove</a>');
				}
				$tag['empid'] = $empid;
				$tag['type'] = $type;
				$tag['current_merit'] = $this->table->generate();
			}break;
			case 'maxworkdays':{
				if(isset($_POST['delid'])){
					$sql = "DELET FROM middle_table WHERE mtableid={$_POST['delid']};";
					$this->db->query($sql);
				}elseif(isset($_POST['merits']) && $this->form_validation->run('merit') == TRUE){
					$sql = "SELECT mtable_typeid FROM mtable_type WHERE name='{$type}';";
					$mtabletype = $this->db->query($sql)->row()->mtable_typeid;
					$sql = "INSERT INTO middle_table(sourceid,mtabletype,entrydate,value)
							VALUES({$empid},{$mtabletype},'".date('Y-m-d', strtotime($_POST['entrydate']))."', {$_POST['value']});";
					$this->db->query($sql);
					$tag['errors'] = 'Successfully Added New Max Work Days Value.';
				}else{
					$tag['errors'] = validation_errors();
				}
				
				$tag['merit_title'] = 'Max Work Days';
				$sql = "SELECT mt.*, b.maxworkdays FROM middle_table mt
						LEFT JOIN basic b ON b.empid=mt.sourceid
						WHERE mt.sourceid={$empid} AND mtabletype=(SELECT mtable_typeid FROM mtable_type WHERE name='{$type}') 
						ORDER BY mt.entrydate ASC;";
				$query = $this->db->query( $sql );
				$this->table->clear();
				$this->table->set_heading('Date Effective', 'Days', 'New Max Work Days', '!');
				$total_merit = 0;
				foreach($query->result() as $row){
					$date = date('M d, Y', strtotime($row->entrydate));
					$total_merit += $row->value;
					$this->table->add_row($date, number_format($row->value, 2), number_format($row->maxworkdays+$total_merit, 2), '<a href="javascript:;">Remove</a>');
				}
				$tag['empid'] = $empid;
				$tag['type'] = $type;
				$tag['current_merit'] = $this->table->generate();
			}break;
			case 'nightdiffin':{
				if(isset($_POST['delid'])){
					$sql = "DELET FROM middle_table WHERE mtableid={$_POST['delid']};";
					$this->db->query($sql);
				}elseif(isset($_POST['merits']) && $this->form_validation->run('merit') == TRUE){
					$sql = "SELECT mtable_typeid FROM mtable_type WHERE name='{$type}';";
					$mtabletype = $this->db->query($sql)->row()->mtable_typeid;
					$sql = "INSERT INTO middle_table(sourceid,mtabletype,entrydate,value)
							VALUES({$empid},{$mtabletype},'".date('Y-m-d', strtotime($_POST['entrydate']))."', '{$_POST['value']}');";
					$this->db->query($sql);
					$tag['errors'] = 'Successfully Added New Night Differencial Time In.';
				}else{
					$tag['errors'] = validation_errors();
				}
				
				$tag['merit_title'] = 'Night Differential Time In (24Hour Format)';
				$sql = "SELECT mt.*, b.nightdiffin FROM middle_table mt
						LEFT JOIN basic b ON b.empid=mt.sourceid
						WHERE mt.sourceid={$empid} AND mtabletype=(SELECT mtable_typeid FROM mtable_type WHERE name='{$type}') 
						ORDER BY mt.entrydate ASC;";
				$query = $this->db->query( $sql );
				$this->table->clear();
				$this->table->set_heading('Date Effective', 'Time In', '!');
				$total_merit = 0;
				foreach($query->result() as $row){
					$date = date('M d, Y', strtotime($row->entrydate));
					$total_merit += $row->value;
					$this->table->add_row($date, date('H:i', strtotime($row->value)), '<a href="javascript:;">Remove</a>');
				}
				$tag['empid'] = $empid;
				$tag['type'] = $type;
				$tag['current_merit'] = $this->table->generate();
			}break;
			case 'nightdiffout':{
				if(isset($_POST['delid'])){
					$sql = "DELET FROM middle_table WHERE mtableid={$_POST['delid']};";
					$this->db->query($sql);
				}elseif(isset($_POST['merits']) && $this->form_validation->run('merit') == TRUE){
					$sql = "SELECT mtable_typeid FROM mtable_type WHERE name='{$type}';";
					$mtabletype = $this->db->query($sql)->row()->mtable_typeid;
					$sql = "INSERT INTO middle_table(sourceid,mtabletype,entrydate,value)
							VALUES({$empid},{$mtabletype},'".date('Y-m-d', strtotime($_POST['entrydate']))."', '{$_POST['value']}');";
					$this->db->query($sql);
					$tag['errors'] = 'Successfully Added New Night Differencial Time Out.';
				}else{
					$tag['errors'] = validation_errors();
				}
				
				$tag['merit_title'] = 'Night Differential Time Out (24Hour Format)';
				$sql = "SELECT mt.*, b.nightdiffout FROM middle_table mt
						LEFT JOIN basic b ON b.empid=mt.sourceid
						WHERE mt.sourceid={$empid} AND mtabletype=(SELECT mtable_typeid FROM mtable_type WHERE name='{$type}') 
						ORDER BY mt.entrydate ASC;";
				$query = $this->db->query( $sql );
				$this->table->clear();
				$this->table->set_heading('Date Effective', 'Time Out', '!');
				$total_merit = 0;
				foreach($query->result() as $row){
					$date = date('M d, Y', strtotime($row->entrydate));
					$total_merit += $row->value;
					$this->table->add_row($date, date('H:i', strtotime($row->value)), '<a href="javascript:;">Remove</a>');
				}
				$tag['empid'] = $empid;
				$tag['type'] = $type;
				$tag['current_merit'] = $this->table->generate();
			}break;
			case 'halfday':{
				if(isset($_POST['delid'])){
					$sql = "DELET FROM middle_table WHERE mtableid={$_POST['delid']};";
					$this->db->query($sql);
				}elseif(isset($_POST['merits']) && $this->form_validation->run('merit') == TRUE){
					$sql = "SELECT mtable_typeid FROM mtable_type WHERE name='{$type}';";
					$mtabletype = $this->db->query($sql)->row()->mtable_typeid;
					$sql = "INSERT INTO middle_table(sourceid,mtabletype,entrydate,value)
							VALUES({$empid},{$mtabletype},'".date('Y-m-d', strtotime($_POST['entrydate']))."', '{$_POST['value']}');";
					$this->db->query($sql);
					$tag['errors'] = 'Successfully Added Halfday Setting.';
				}else{
					$tag['errors'] = validation_errors();
				}
				
				$tag['merit_title'] = 'Halfday Settings';
				$sql = "SELECT mt.*, b.halfday FROM middle_table mt
						LEFT JOIN basic b ON b.empid=mt.sourceid
						WHERE mt.sourceid={$empid} AND mtabletype=(SELECT mtable_typeid FROM mtable_type WHERE name='{$type}') 
						ORDER BY mt.entrydate ASC;";
				$query = $this->db->query( $sql );
				$this->table->clear();
				$this->table->set_heading('Date Effective', 'Day', '!');
				$total_merit = 0;
				$days = array(
					'7' => '[----Select One----]', 
					'0' => 'Sunday', 
					'1' => 'Monday',
					'2' => 'Tuesday',
					'3' => 'Wednesday',
					'4' => 'Thursday',
					'5' => 'Friday',
					'6' => 'Saturday');
				foreach($query->result() as $row){
					$date = date('M d, Y', strtotime($row->entrydate));
					$total_merit += $row->value;
					$this->table->add_row($date, $days[$row->value], '<a href="javascript:;">Remove</a>');
				}
				$tag['empid'] = $empid;
				$tag['type'] = $type;
				$tag['current_merit'] = $this->table->generate();
				
				$tag['value'] = form_dropdown('value', $days, '');
				
				$currpay = $this->common->latesPaydate($empid);
				$currpay = explode( '-', $currpay->payperiod_from ); //year,moth,date
				$tag['curr_year'] 	= $currpay[0];
				$tag['curr_month'] 	= $currpay[1];
				$tag['curr_date'] 	= $currpay[2];
		
				echo $this->parser->parse('member/employee/halfday_merit.html', $tag, TRUE);
				return;
			}break;
			case 'opentime':{
				if(isset($_POST['delid'])){
					$sql = "DELET FROM middle_table WHERE mtableid={$_POST['delid']};";
					$this->db->query($sql);
				}elseif(isset($_POST['merits']) && $this->form_validation->run('merit') == TRUE){
					$sql = "SELECT mtable_typeid FROM mtable_type WHERE name='{$type}';";
					$mtabletype = $this->db->query($sql)->row()->mtable_typeid;
					$sql = "INSERT INTO middle_table(sourceid,mtabletype,entrydate,value)
							VALUES({$empid},{$mtabletype},'".date('Y-m-d', strtotime($_POST['entrydate']))."', '{$_POST['value']}');";
					$this->db->query($sql);
					$tag['errors'] = 'Successfully Added Open Time Setting.';
				}else{
					$tag['errors'] = validation_errors();
				}
				
				$tag['merit_title'] = 'Open Time Settings';
				$sql = "SELECT mt.*, b.opentime FROM middle_table mt
						LEFT JOIN basic b ON b.empid=mt.sourceid
						WHERE mt.sourceid={$empid} AND mtabletype=(SELECT mtable_typeid FROM mtable_type WHERE name='{$type}') 
						ORDER BY mt.entrydate ASC;";
				$query = $this->db->query( $sql );
				$this->table->clear();
				$this->table->set_heading('Date Effective', 'New Value', '!');
				$total_merit = 0;
				$opentime = array(
					'' => '[----Select One----]',
					'1' => 'Yes',
					'0' => 'No'
				);
				foreach($query->result() as $row){
					$date = date('M d, Y', strtotime($row->entrydate));
					$total_merit += $row->value;
					$this->table->add_row($date, $opentime[$row->value], '<a href="javascript:;">Remove</a>');
				}
				$tag['empid'] = $empid;
				$tag['type'] = $type;
				$tag['current_merit'] = $this->table->generate();
				
				$tag['value'] = form_dropdown('value', $opentime, '');

				$currpay = $this->common->latesPaydate($empid);
				$currpay = explode( '-', $currpay->payperiod_from ); //year,moth,date
				$tag['curr_year'] 	= $currpay[0];
				$tag['curr_month'] 	= $currpay[1];
				$tag['curr_date'] 	= $currpay[2];
				
				echo $this->parser->parse('member/employee/halfday_merit.html', $tag, TRUE);
				return;
			}break;
			case 'daily':{
				if(isset($_POST['delid'])){
					$sql = "DELET FROM middle_table WHERE mtableid={$_POST['delid']};";
					$this->db->query($sql);
				}elseif(isset($_POST['merits']) && $this->form_validation->run('merit') == TRUE){
					$sql = "SELECT mtable_typeid FROM mtable_type WHERE name='{$type}';";
					$mtabletype = $this->db->query($sql)->row()->mtable_typeid;
					$sql = "INSERT INTO middle_table(sourceid,mtabletype,entrydate,value)
							VALUES({$empid},{$mtabletype},'".date('Y-m-d', strtotime($_POST['entrydate']))."', '{$_POST['value']}');";
					$this->db->query($sql);
					$tag['errors'] = 'Successfully Added Daily Setting.';
				}else{
					$tag['errors'] = validation_errors();
				}
				
				$tag['merit_title'] = 'Daily Basis';
				$sql = "SELECT mt.*, b.daily FROM middle_table mt
						LEFT JOIN basic b ON b.empid=mt.sourceid
						WHERE mt.sourceid={$empid} AND mtabletype=(SELECT mtable_typeid FROM mtable_type WHERE name='{$type}') 
						ORDER BY mt.entrydate ASC;";
				$query = $this->db->query( $sql );
				$this->table->clear();
				$this->table->set_heading('Date Effective', 'New Value', '!');
				$total_merit = 0;
				$opentime = array(
					'' => '[----Select One----]',
					'1' => 'Yes',
					'0' => 'No'
				);
				foreach($query->result() as $row){
					$date = date('M d, Y', strtotime($row->entrydate));
					$total_merit += $row->value;
					$this->table->add_row($date, $opentime[$row->value], '<a href="javascript:;">Remove</a>');
				}
				$tag['empid'] = $empid;
				$tag['type'] = $type;
				$tag['current_merit'] = $this->table->generate();
				
				$tag['value'] = form_dropdown('value', $opentime, '');

				$currpay = $this->common->latesPaydate($empid);
				$currpay = explode( '-', $currpay->payperiod_from ); //year,moth,date
				$tag['curr_year'] 	= $currpay[0];
				$tag['curr_month'] 	= $currpay[1];
				$tag['curr_date'] 	= $currpay[2];
				
				echo $this->parser->parse('member/employee/halfday_merit.html', $tag, TRUE);
				return;
			}break;
			case 'sssovr':
			case 'pagibigovr':
			case 'philhealthovr':{
				$title = array(
					'sssovr'		=> 'SSS Override',
					'pagibigovr'	=> 'Pagibig Override',
					'philhealthovr'	=> 'Philhealth Override'
				);
				if(isset($_POST['delid'])){
					$sql = "DELET FROM middle_table WHERE mtableid={$_POST['delid']};";
					$this->db->query($sql);
				}elseif(isset($_POST['merits']) && $this->form_validation->run('merit') == TRUE){
					$sql = "SELECT mtable_typeid FROM mtable_type WHERE name='{$type}';";
					$mtabletype = $this->db->query($sql)->row()->mtable_typeid;
					$sql = "INSERT INTO middle_table(sourceid,mtabletype,entrydate,value)
							VALUES({$empid},{$mtabletype},'".date('Y-m-d', strtotime($_POST['entrydate']))."', '{$_POST['value']}');";
					$this->db->query($sql);
					$tag['errors'] = 'Successfully Added '.$title[$type].' Setting.';
				}else{
					$tag['errors'] = validation_errors();
				}
				
				$tag['merit_title'] = $title[$type];
				$sql = "SELECT mt.*, emp.{$type} FROM middle_table mt
						LEFT JOIN basic b ON b.empid=mt.sourceid
						LEFT JOIN employee emp ON emp.empid={$empid}
						WHERE mt.sourceid={$empid} AND mtabletype=(SELECT mtable_typeid FROM mtable_type WHERE name='{$type}') 
						ORDER BY mt.entrydate ASC;";
				$query = $this->db->query( $sql );
				$this->table->clear();
				$this->table->set_heading('Date Effective', 'New Value', '!');
				$total_merit = 0;
				$opentime = array(
					'' => '[----Select One----]',
					'0' => 'Auto',
					'1' => 'Manual'
				);
				foreach($query->result() as $row){
					$date = date('M d, Y', strtotime($row->entrydate));
					$total_merit += $row->value;
					$this->table->add_row($date, $opentime[$row->value], '<a href="javascript:;">Remove</a>');
				}
				$tag['empid'] = $empid;
				$tag['type'] = $type;
				$tag['current_merit'] = $this->table->generate();
				
				$tag['value'] = form_dropdown('value', $opentime, '');

				$currpay = $this->common->latesPaydate($empid);
				$currpay = explode( '-', $currpay->payperiod_from ); //year,moth,date
				$tag['curr_year'] 	= $currpay[0];
				$tag['curr_month'] 	= $currpay[1];
				$tag['curr_date'] 	= $currpay[2];
				
				echo $this->parser->parse('member/employee/halfday_merit.html', $tag, TRUE);
				return;
			}break;
			case 'sssded':
			case 'pagibigded':
			case 'philhealthded':{
				$title = array(
					'sssded'		=> 'Deduct SSS',
					'pagibigded'	=> 'Deduct Pagibig',
					'philhealthded'	=> 'Deduct Philhealth'
				);
				if(isset($_POST['delid'])){
					$sql = "DELET FROM middle_table WHERE mtableid={$_POST['delid']};";
					$this->db->query($sql);
				}elseif(isset($_POST['merits']) && $this->form_validation->run('merit') == TRUE){
					$sql = "SELECT mtable_typeid FROM mtable_type WHERE name='{$type}';";
					$mtabletype = $this->db->query($sql)->row()->mtable_typeid;
					$sql = "INSERT INTO middle_table(sourceid,mtabletype,entrydate,value)
							VALUES({$empid},{$mtabletype},'".date('Y-m-d', strtotime($_POST['entrydate']))."', '{$_POST['value']}');";
					$this->db->query($sql);
					$tag['errors'] = 'Successfully Added '.$title[$type].' Setting.';
				}else{
					$tag['errors'] = validation_errors();
				}
				
				$tag['merit_title'] = $title[$type];
				$sql = "SELECT mt.*, emp.{$type} FROM middle_table mt
						LEFT JOIN basic b ON b.empid=mt.sourceid
						LEFT JOIN employee emp ON emp.empid={$empid}
						WHERE mt.sourceid={$empid} AND mtabletype=(SELECT mtable_typeid FROM mtable_type WHERE name='{$type}') 
						ORDER BY mt.entrydate ASC;";
				$query = $this->db->query( $sql );
				$this->table->clear();
				$this->table->set_heading('Date Effective', 'New Value', '!');
				$total_merit = 0;
				$opentime = array(
					'' => '[----Select One----]',
					'1' => 'Yes',
					'0' => 'No'
				);
				foreach($query->result() as $row){
					$date = date('M d, Y', strtotime($row->entrydate));
					$total_merit += $row->value;
					$this->table->add_row($date, $opentime[$row->value], '<a href="javascript:;">Remove</a>');
				}
				$tag['empid'] = $empid;
				$tag['type'] = $type;
				$tag['current_merit'] = $this->table->generate();
				
				$tag['value'] = form_dropdown('value', $opentime, '');

				$currpay = $this->common->latesPaydate($empid);
				$currpay = explode( '-', $currpay->payperiod_from ); //year,moth,date
				$tag['curr_year'] 	= $currpay[0];
				$tag['curr_month'] 	= $currpay[1];
				$tag['curr_date'] 	= $currpay[2];
				
				echo $this->parser->parse('member/employee/halfday_merit.html', $tag, TRUE);
				return;
			}break;
			case 'taxcode':{
				if(isset($_POST['delid'])){
					$sql = "DELET FROM middle_table WHERE mtableid={$_POST['delid']};";
					$this->db->query($sql);
				}elseif(isset($_POST['merits']) && $this->form_validation->run('merit') == TRUE){
					$sql = "SELECT mtable_typeid FROM mtable_type WHERE name='{$type}';";
					$mtabletype = $this->db->query($sql)->row()->mtable_typeid;
					$sql = "INSERT INTO middle_table(sourceid,mtabletype,entrydate,value)
							VALUES({$empid},{$mtabletype},'".date('Y-m-d', strtotime($_POST['entrydate']))."', '{$_POST['value']}');";
					$this->db->query($sql);
					$tag['errors'] = 'Successfully Updated Tax-code Setting.';
				}else{
					$tag['errors'] = validation_errors();
				}
				
				$tag['merit_title'] = 'Tax Code';
				$sql = "SELECT mt.*, b.daily FROM middle_table mt
						LEFT JOIN basic b ON b.empid=mt.sourceid
						WHERE mt.sourceid={$empid} AND mtabletype=(SELECT mtable_typeid FROM mtable_type WHERE name='{$type}') 
						ORDER BY mt.entrydate ASC;";
				$query = $this->db->query( $sql );
				$this->table->clear();
				$this->table->set_heading('Date Effective', 'New Value', '!');
				$total_merit = 0;

				$sql = "SELECT * FROM wtax_code";
				$q = $this->db->query( $sql );
				$wtaxcode[0]= '[----Select One----]';
				foreach($q->result() as $i)
					$wtaxcode[$i->wtcodeid]=$i->name;

				foreach($query->result() as $row){
					$date = date('M d, Y', strtotime($row->entrydate));
					$total_merit += $row->value;
					$this->table->add_row($date, $wtaxcode[$row->value], '<a href="javascript:;">Remove</a>');
				}
				$tag['empid'] = $empid;
				$tag['type'] = $type;
				$tag['current_merit'] = $this->table->generate();
				
				$tag['value'] = form_dropdown('value', $wtaxcode, '');

				$currpay = $this->common->latesPaydate($empid);
				$currpay = explode( '-', $currpay->payperiod_from ); //year,moth,date

				$tag['curr_year'] 	= $currpay[0];
				$tag['curr_month'] 	= $currpay[1];
				$tag['curr_date'] 	= $currpay[2];
				
				echo $this->parser->parse('member/employee/halfday_merit.html', $tag, TRUE);
				return;
			}break;
		}
		
		$currpay = $this->common->latesPaydate($empid);
		$currpay = explode( '-', $currpay->payperiod_from ); //year,moth,date
		$tag['curr_year'] 	= $currpay[0];
		$tag['curr_month'] 	= $currpay[1];
		$tag['curr_date'] 	= $currpay[2];
		echo $this->parser->parse('member/employee/merit.html', $tag, TRUE);
	}

	function importemp(){
		$tag = array();
		$tag['__sub_nav__'] = $this->sub_nav;
		if(isset($_POST['importdata'])){
			if($_FILES["importemp"]['size'] != 0 && $_FILES["importemp"]['error'] == 0 && $_FILES["importemp"]['type'] == 'application/vnd.ms-excel'){
				
				$tmp_name = $_FILES["importemp"]["tmp_name"];
        		$name = $_FILES["importemp"]["name"];
        		$file = "file/upload/$name";
        		$sql ="SELECT * FROM wtax_code";
        		$tcode = $this->db->query($sql);
        		$wtcodes = array();
        		foreach($tcode->result() as $r)
        			$wtcodes["{$r->name}"] = $r->wtcodeid;
        		if(move_uploaded_file($tmp_name, $file)){
		    		$this->load->library('csv');
		    		$data = $this->csv->to_array($file);
		    		$gender = array('Male'=>'1', 'Female'=>'2');
		    		
		    		
		    		foreach($data as $item){
		    			$bdate = date('Y-m-d', strtotime($item['Birth Date']));
		    			$sql = "INSERT INTO employee(idnumber,companyid,firstname,lastname,middlename,gender,birthdate,civilstatus,taxcode)
		    					VALUES({$item['ID Number']},{$this->ccompany},'{$item['First Name']}','{$item['Last Name']}',
		    					'{$item['Middle Name']}',{$gender[$item['Gender']]},'{$bdate}',1,".$wtcodes["{$item['Tax Code']}"].");";
		    			$this->db->query($sql);
		    			$empid = $this->db->insert_id();
		    			//basic
		    			$sql = "INSERT INTO basic(empid,amount,allowance,sss,pagibig,philhealth,maxhours,maxbreakhours,maxworkdays,nightdiffin,nightdiffout,halfday)
		    					VALUES({$empid},'{$item['Basic']}','{$item['Allowance']}','{$item['SSS']}','{$item['Pag-ibig']}','{$item['Philhealth']}',0,0,0,'00:00:00','00:00:00',7);";
		    			$this->db->query($sql);
		    			//schedule
		    			$sql = "INSERT INTO schedule(empid,timein,timeout)
		    					VALUES({$empid},'{$item['Time In']}', '{$item['Time Out']}')";
		    			$this->db->query($sql);
		    			//$sql = "INSERT INTO schedule(empid,timein,timeout)
		    			//		VALUES({$empid},'09:00:00', '12:00:00')";
		    			//$this->db->query($sql);
		    			
		    		}
		    		redirect('employee/');
        		}
			}
		}
		$this->common->display("member/employee/import.html", $tag);
	}

	function loan($empid = 0){
        $sql = "SELECT * FROM employee WHERE empid={$empid};";

        $employee = $this->db->query($sql)->row();

		$tag['emp_name'] = $employee->lastname. ', '.$employee->firstname;
		$tag['jscript'] = array('i18n/grid.locale-en', 'jquery.jqGrid.min', 'jquery-ui-custom.min');	    
        $tag['css'] = array('themes/ui.jqgrid', 'themes/ui.multiselect');
        $tag['loan_list'] = $this->listloan($empid, false);
		$this->common->display("member/employee/loan.html", $tag);
	}

    function listloan($empid = 0, $echo = true){
        $this->load->library('xjqgrid');
        $sql = "SELECT 
                    l.loanid,
                    l.loandate,
                    l.amount, 
                    l.term, 
                    l.intrate, 
                    l.paymode, 
                    l.active, 
                    sum(ld.amount) as totalpay 
                FROM loan l
				    LEFT JOIN loan_detail ld ON ld.loanid=l.loanid
				WHERE empid={$empid} GROUP BY l.loanid";
		$this->xjqgrid->initGrid( $sql );
		$this->xjqgrid->setMethod('setUrl', "employee/listloan/{$empid}/1");
		$this->xjqgrid->setMethod('setSubGridGrid', "employee/listloandetail/");
		 
        $oper = $_POST['oper'];
        if($oper == 'add') {
		    $this->xjqgrid->setProperty('table', 'loan');
            $this->xjqgrid->setMethod('setPrimaryKeyId', "loanid");
            $data = $_POST;
            $data['empid'] = $empid;
            $this->xjqgrid->grid->insert($data);
        }elseif($oper == 'edit'){
            $this->xjqgrid->setMethod('setPrimaryKeyId', "loanid");
            $_POST['loandate'] = date("Y-m-d", strtotime($_POST['loandate']));
            $sql = "UPDATE loan SET 
                        active={$_POST['active']},
                        amount='{$_POST['amount']}',
                        intrate='{$_POST['intrate']}',
                        loandate='{$_POST['loandate']}',
                        paymode={$_POST['paymode']},
                        `term`={$_POST['term']}
                    WHERE loanid={$_POST['loanid']}";
            $this->db->query($sql);
        }
        $this->xjqgrid->setColProperty("loanid", 
            array("hidden" => true));
        $this->xjqgrid->setColProperty("loandate", 
            array(
                "label"     => "Loan Date",
                "width"     => "100",
                "sortable"  =>  false,
                "datefmt"   => "m/d/Y",
                "formatter" => "date", 
                "formatoptions" => array("srcformat"=>"Y-m-d","newformat"=>"m/d/Y"),
                "align"     => "center",
                "editrules" => array(
                    "required"  => true,                    
                    "date"      => true
                )));
        $this->xjqgrid->grid->setDatepicker("loandate",
            array(
                "buttonOnly"=>false, 
                "dateFormat"=>"mm/dd/yy"));
        $this->xjqgrid->setColProperty("amount", 
            array(
                "label"     => "Amount",
                "width"     => "100",
                "sortable"  =>  false,
                "align"     => "right",
                "formatter" => "currency",
                "formatoptions" => array("thousandsSeparator"=>","),
                "editrules" => array(
                    "required"  => true,
                    "number"    => true,
                )));
        $this->xjqgrid->setColProperty("term", 
            array(
                "label" => "Term", 
                "align" => "center",
                "width" => 40,
                "sortable"=>false,
                "editrules" => array(
                    "required"  => true,
                    "integer"    => true,
                )));
        $this->xjqgrid->setColProperty("intrate", 
            array(
                "label"     => "Int Rate %",
                "width"     => "80",
                "sortable"  =>  false,
                "align"     => "center",
                "formatter" => "js:function(cellValue){return (cellValue*100).toFixed(2)+'%';}",
                "unformat"  => "js:function(cellValue, options, cellObject){return (cellValue.replace('%', '')/100).toFixed(4);}",                
                "editrules" => array(
                    "required"  => true,
                    "number"    => true
                )));
        $this->xjqgrid->setColProperty("paymode", 
            array(
                "label"     => "Payment Mode", 
                "width"     => 100,
                "sortable"  => false));
        $this->xjqgrid->setSelect("paymode", array("0"=>"Bi-Monthly", "1"=>"Monthly"), true, true, false);
        $this->xjqgrid->setColProperty("active", 
            array(
                "label"     => "Active", 
                "width"     => 40,
                "sortable"  => false,
                "formatter" => "checkbox",
                "align"     => "center",
                "edittype"  => "checkbox",
                "editoptions" => array(
                    "value" => "1:0"
                )));
        $this->xjqgrid->setColProperty("totalpay", 
            array(
                "label"         => "Payments Made", 
                "width"         => 100,
                "sortable"      => false,
                "formatter"     => "currency",
                "editable"      => false,
                "formatoptions" => array(
                    "thousandsSeparator"=>","
                ),
                "align"     => "right"));
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
                    "keys"=>true,
                    "delbutton"=>false,
                ) 
                ), "first");   
        $this->xjqgrid->grid->navigator = true; 
        $this->xjqgrid->grid->setNavOptions('navigator', array(
            "excel" => false,
            "add"   => true,
            "del"   => false,
            "edit"  => false,
            "view"  => false,
            "search"=>false));  
        $this->xjqgrid->grid->setGridOptions(array( 
                "autowidth"     => true,
                "height"        => 335,
                "hoverrows"     => true,            
                "rowNum"        => 15,
                "altRows"       => true,
                "sortname"      => "loandate",
                "sortorder"     => "desc",
                "subGrid"       => true,
                "rowList"       => array(15,30,100),
                "subGridOptions"=> array(
                    "plusicon"=>"ui-icon-triangle-1-e",
                    "minusicon"=>"ui-icon-triangle-1-s",
                    "openicon"=>"ui-icon-arrowreturn-1-e",
                    // load the subgrid data only once
                    // and the just show/hide
                    "reloadOnExpand"=>true,
                    // select the row when the expand column is clicked
                    "selectOnExpand"=>true)
            ));
        
        if(!$echo)
            return $this->xjqgrid->renderGrid('#grid','#pager',true, null, null, true, true, $echo);
            
        $this->xjqgrid->renderGrid('#grid','#pager',true, null, null, true, true, $echo);
        $this->xjqgrid->close();
        
    }

    function listloandetail(){
        $loanid = $this->input->post('rowid', true);
        $this->loanpaydetail($loanid);
    }
    
    function loanpaydetail($loanid = 0){
        $tableid = "#lptable_{$loanid}";
        $pagerid = "#lppager_{$loanid}";
        if($loanid < 0){ return; }
        
        $this->load->library('xjqgrid');
        $sql = "SELECT ld.ldetailid, ld.loanid, ld.paydate, ld.amount, 
                    lpd.payrollid 
                FROM loan_detail ld
				    LEFT JOIN loan_payroll_detail lpd ON lpd.ldetailid= ld.ldetailid
				WHERE ld.loanid={$loanid}";
				
		$this->xjqgrid->initGrid( $sql );
        $this->xjqgrid->setMethod('setUrl', "employee/loanpaydetail/{$loanid}");
        
        $oper = $_POST['oper'];
        if($oper == 'add') {
            $_POST['paydate'] = date("Y-m-d", strtotime($_POST['paydate']));
            $sql = "INSERT INTO loan_detail(loanid,amount,paydate) 
                    VALUES({$loanid},'{$_POST['amount']}', '{$_POST['paydate']}');";
            $this->db->query($sql);
        }elseif($oper == 'del'){
            $this->xjqgrid->setProperty('table', 'loan_detail');
            $this->xjqgrid->setMethod('setPrimaryKeyId', "ldetailid");            
            $data = $_POST;
            $this->xjqgrid->grid->delete($data);
        }

        $this->xjqgrid->setColProperty("ldetailid", 
            array("hidden" => true));
        $this->xjqgrid->setColProperty("loanid", 
            array("hidden" => true));
        $this->xjqgrid->setColProperty("payrollid", 
            array(
                "editable"  => false,
                "sortable"  => false,
                "width"     => "20",
                "label"     => "!",
                "formatter" => 'js:function(cellValue, options, rowdata){
                    var innerHTML;
                    if(cellValue != null){
                        innerHTML = \'<span class="ui-icon ui-icon-link" title="Linked to Payroll"><img title="Linked to Payroll" src="image/1x1.gif" width="16" height="16" /></span>\';
                    }else{
                        innerHTML = \'<a href="javascript:;" onclick="$(\\\''.$tableid.'\\\').delGridRow(\'+rowdata.ldetailid+\', {delData:{ldetailid:\\\'\'+rowdata.ldetailid+\'\\\'}});"><span class="ui-icon ui-icon-trash"><img src="image/1x1.gif" width="16" height="16" /></span></a>\';
                    }
                return innerHTML }',
                ));
        $this->xjqgrid->setColProperty("paydate", 
            array(
                "label"     => "Date",
                "width"     => "100",
                "sortable"  => false,
                "formatter" => "date", 
                "formatoptions" => array("srcformat"=>"Y-m-d H:i:s","newformat"=>"m/d/Y"),
                "align"     => "center"));
        $this->xjqgrid->grid->setDatepicker("paydate",
            array(
                "buttonOnly"=>false, 
                "dateFormat"=>"mm/dd/yy"));
        $this->xjqgrid->setColProperty("amount", 
            array(
                "label"         => "Amount", 
                "sortable"      => false,
                "formatter"     => "currency",
                "formatoptions" => array(
                    "thousandsSeparator"=>","
                ),
                "align"     => "right",
                "editoptions" => array("defaultValue" => "0.00"))); 
        $this->xjqgrid->grid->navigator = true; 
        $this->xjqgrid->grid->setNavOptions('navigator', array(
            "excel" => false,
            "add"   => true,
            "del"   => false,
            "edit"  => false,
            "view"  => false,
            "search"=>false));   
        $this->xjqgrid->grid->setGridOptions(array( 
                "width"         => 480,
                "height"        => 150,
                "hoverrows"     => true,            
                "rowNum"        => 15,
                "altRows"       => true,
                "sortname"      => "paydate",
                "sortorder"     => "desc",
                "rowList"       => array(15,30,100),
            ));
        
        $this->xjqgrid->renderGrid($tableid, $pagerid, true, null, null, true, true, true);
        $this->xjqgrid->close();
    }

    function getphilhealth($echo = false, $basic = 0){
		$this->load->model( 'payrollmd' );
		$philhealth =  number_format( $this->payrollmd->getphilhealth($basic), 2 );
		if($echo){ echo $philhealth; }
		else{ return $philhealth; }
	}

	function getsss($echo = false, $basic = 0){
		$this->load->model( 'payrollmd' );
		$sss =  number_format( $this->payrollmd->getsss($basic), 2 );
		if($echo){ echo $sss; }
		else{ return $sss; }
	}

	function getpagibig($echo = false, $basic=0){
		$this->load->model( 'payrollmd' );
		$pagibig =  number_format( $this->payrollmd->getpagibig( $basic ), 2 );
		if($echo){ echo $pagibig; }
		else{ return $pagibig; }
	}
    
    function basic($empid = 0){
		$this->load->library('form_validation');
		$this->load->model( 'payrollmd' );
		$this->form_validation->set_error_delimiters('<div class="error">', '</div>');
		$sql = "SELECT * FROM basic WHERE empid={$empid} LIMIT 1;";
		$basic = $this->db->query($sql)->row();
		
		//check existing approved payroll payrolls
		$isEditable = $this->common->iseditable($empid);
		
		if(isset($_POST['empbasic']) && $isEditable == false && $this->form_validation->run('basic') == TRUE){
			$opentime = ((isset($_POST['opentime']))?1:0);
			$daily = ((isset($_POST['daily']))?1:0);
			if($basic){
				$sql = "UPDATE basic SET amount={$_POST['basic']}, allowance={$_POST['allowance']},maxhours={$_POST['maxhours']},maxbreakhours={$_POST['maxbreakhours']},maxworkdays={$_POST['maxworkdays']},
				nightdiffin='{$_POST['nightdiffin']}',nightdiffout='{$_POST['nightdiffout']}',halfday={$_POST['halfday']},opentime={$opentime},daily={$daily} WHERE empid={$empid};";
			}else{
				$sql = "INSERT INTO basic(empid,amount,allowance,maxhours,maxbreakhours,
						maxworkdays,nightdiffin,nightdiffout,halfday,opentime,daily) VALUES({$empid},{$_POST['basic']},{$_POST['allowance']},{$_POST['maxhours']},{$_POST['maxbreakhours']},{$_POST['maxworkdays']},
					    '{$_POST['nightdiffin']}','{$_POST['nightdiffout']}',{$_POST['halfday']},{$opentime},{$daily});";
			}
			
			$this->db->query($sql);
			$tag['errors'] = "Updated Basic Information";
			
			$sql = "SELECT * FROM basic WHERE empid={$empid} LIMIT 1;";
			$basic = $this->db->query($sql)->row();
		}else{
			//if($isEditable)
			//	$tag['errors'] = '<div class="error">This item is locked</div>';
			//else
				$tag['errors'] = validation_errors();
		}
		
		$sql = "SELECT * FROM employee WHERE empid={$empid}";
		$employee = $this->db->query($sql)->row();
		$name = $employee->firstname.' '.$employee->middlename.' '.$employee->lastname;
		
		$tag['empid'] 		= $empid;
		$tag['name'] 		= $name;
		if($isEditable){
			//basic
			$basic_ = $this->common->getmerit($empid, 'amount');
			$curr_amount 	= (($basic_['amount']!=0)?$basic_['amount']:$basic->amount);
			$effective 		= (($basic_['date'])?('<div class="fr"><em>(dated '.$basic_['date'].')</em></div>'):'');
			$tag['basic'] 	= '<span style="font-size:14px">'.number_format($curr_amount, "2").'</span>'.$effective;

			$ddaily 	= $this->common->getmerit($empid, 'daily');
			$isdaily	= (($ddaily['amount']!=-1)?$ddaily['amount']:$basic->daily);
			
			if( $isdaily == 1 ){
				$tag['sss'] 		= '---';
				$tag['pagibig'] 	= '---';
				$tag['philhealth'] 	= '---';
			}else{
				$sssovr 		= $this->common->getmerit( $empid, 'sssovr' );
				$sssovr			= (($sssovr['amount']!=-1)?$sssovr['amount']:$employee->sssovr);
				$pagibigovr 	= $this->common->getmerit( $empid, 'pagibigovr' );
				$pagibigovr		= (($pagibigovr['amount']!=-1)?$pagibigovr['amount']:$employee->pagibigovr);
				$philhealthovr	= $this->common->getmerit( $empid, 'philhealthovr' );
				$philhealthovr	= (($philhealthovr['amount']!=-1)?$philhealthovr['amount']:$employee->philhealthovr);
				
				$sssded 		= $this->common->getmerit( $empid, 'sssded' );
				$sssded			= (($sssded['amount']!=-1)?$sssded['amount']:$employee->sssded);
				$pagibigded 	= $this->common->getmerit( $empid, 'pagibigded' );
				$pagibigded		= (($pagibigded['amount']!=-1)?$pagibigded['amount']:$employee->pagibigded);
				$philhealthded	= $this->common->getmerit( $empid, 'philhealthded' );
				$philhealthded	= (($philhealthded['amount']!=-1)?$philhealthded['amount']:$employee->philhealthded);
				
				if($sssded == 1){
					if($sssovr == 1){
						//sss
						$sss 			= $this->common->getmerit($empid, 'sss');
						$curr_amount 	= (($sss['amount'])?$sss['amount']:$basic->sss);
						$effective 		= (($sss['date'])?('<div class="fr"><em>(dated '.$sss['date'].')</em></div>	'):'');
						$tag['sss'] 	= '<span style="font-size:14px">'.number_format($curr_amount, "2").'</span>'.$effective;
					}else{
						$tag['sss'] 	= '<span id="basic_sss" style="font-size: 14px">'.number_format( $this->payrollmd->getsss( (int) $curr_amount ), 2 ).'</span>';
					}
					$tag['sss']	   .= (($sssovr==1)?('<div class="fr"><em>(Overriden)</em></div>'):'');
				}else{
					$tag['sss'] = '';
				}
				
				if($pagibigded == 1){
					if($pagibigovr==1){
						//pagibig
						$pagibig = $this->common->getmerit($empid, 'pagibig');
						$curr_amount 	= (($pagibig['amount'])?$pagibig['amount']:$basic->pagibig);
						$effective 		= (($pagibig['date'])?('<div class="fr"><em>(dated '.$pagibig['date'].')</em></div>'):'');
						$tag['pagibig'] = '<span style="font-size:14px">'.number_format($curr_amount, "2").'</span>'.$effective;
					}else{
						$tag['pagibig'] = '<span id="basic_pagibig" style="font-size: 14px">'.number_format( $this->payrollmd->getpagibig( (int) $curr_amount ), "2").'</span>';
					}
					$tag['pagibig'].= (($pagibigovr==1)?('<div class="fr"><em>(Overriden)</em></div>'):'');
					}else{
					$tag['pagibig'] = '';
				}
				if($philhealthded == 1){
					if($philhealthovr==1){
						//philhealth
						$philhealth = $this->common->getmerit($empid, 'philhealth');
						$curr_amount 	= (($philhealth['amount'])?$philhealth['amount']:$basic->philhealth);
						$effective 		= (($philhealth['date'])?('<div class="fr"><em>(dated '.$philhealth['date'].')</em></div>'):'');
						$tag['philhealth'] = '<span style="font-size:14px">'.number_format($curr_amount, "2").'</span>'.$effective;
					}else{
						$tag['philhealth'] = '<span id="basic_philhealth" style="font-size: 14px">'.number_format( $this->payrollmd->getphilhealth( (int) $curr_amount ), "2").'</span>';
					}
					$tag['philhealth'].= (($philhealthovr==1)?('<div class="fr"><em>(Overriden)</em></div>'):'');
				}else{
					$tag['philhealth'] = '';
				}
			}
			
			//allowance
			$allowance 		= $this->common->getmerit($empid, 'allowance');
			$curr_amount 	= (($allowance['amount']!=0)?$allowance['amount']:$basic->allowance);
			$effective 		= (($allowance['date'])?('<div class="fr"><em>(dated '.$allowance['date'].')</em></div>'):'');
			$tag['allowance'] = '<span style="font-size:14px">'.number_format($curr_amount, "2").'</span>'.$effective;

			//maxhours
			$maxhours = $this->common->getmerit($empid, 'maxhours');
			$curr_amount 	= (($maxhours['amount']!=0)?$maxhours['amount']:$basic->maxhours);
			$effective 		= (($maxhours['date'])?('<div class="fr"><em>(dated '.$maxhours['date'].')</em></div>'):'');
			$tag['maxhours'] = '<span style="font-size:14px">'.number_format($curr_amount, "2").'</span>'.$effective;

			//maxbreakhours
			$maxbreakhours = $this->common->getmerit($empid, 'maxbreakhours');
			$curr_amount 	= (($maxbreakhours['amount']!=0)?$maxbreakhours['amount']:$basic->maxbreakhours);
			$effective 		= (($maxbreakhours['date'])?('<div class="fr"><em>(dated '.$maxbreakhours['date'].')</em></div>'):'');
			$tag['maxbreakhours'] = '<span style="font-size:14px">'.number_format($curr_amount, "2").'</span>'.$effective;

			//maxworkdays
			$maxworkdays = $this->common->getmerit($empid, 'maxworkdays');
			$curr_amount 	= (($maxworkdays['amount']!=0)?$maxworkdays['amount']:$basic->maxworkdays);
			$effective 		= (($maxworkdays['date'])?('<div class="fr"><em>(dated '.$maxworkdays['date'].')</em></div>'):'');
			$tag['maxworkdays'] = '<span style="font-size:14px">'.number_format($curr_amount, "2").'</span>'.$effective;

			//maxworkdays
			$maxworkdays = $this->common->getmerit($empid, 'maxworkdays');
			$curr_amount 	= (($maxworkdays['amount']!=0)?$maxworkdays['amount']:$basic->maxworkdays);
			$effective 		= (($maxworkdays['date'])?('<div class="fr"><em>(dated '.$maxworkdays['date'].')</em></div>'):'');
			$tag['maxworkdays'] = '<span style="font-size:14px">'.number_format($curr_amount, "2").'</span>'.$effective;

			//nightdiffin
			$nightdiffin = $this->common->getmerit($empid, 'nightdiffin');
			$curr_amount 	= (($nightdiffin['amount']!=0)?$nightdiffin['amount']:$basic->nightdiffin);
			$effective 		= (($nightdiffin['date'])?('<div class="fr"><em>(dated '.$nightdiffin['date'].')</em></div>'):'');
			$tag['nightdiffin'] = '<span style="font-size:14px">'.$curr_amount.'</span>'.$effective;

			//nightdiffout
			$nightdiffout = $this->common->getmerit($empid, 'nightdiffout');
			$curr_amount 	= (($nightdiffout['amount']!=0)?$nightdiffout['amount']:$basic->nightdiffout);
			$effective 		= (($nightdiffout['date'])?('<div class="fr"><em>(dated '.$nightdiffout['date'].')</em></div>'):'');
			$tag['nightdiffout'] = '<span style="font-size:14px">'.$curr_amount.'</span>'.$effective;
			$days = array(
				'7' => '[----Select One----]', 
				'0' => 'Sunday', 
				'1' => 'Monday',
				'2' => 'Tuesday',
				'3' => 'Wednesday',
				'4' => 'Thursday',
				'5' => 'Friday',
				'6' => 'Saturday');
			//halfday
			$halfday = $this->common->getmerit($empid, 'halfday');
			$curr_amount 	= (($halfday['amount']!=-1)?$halfday['amount']:$basic->halfday);
			$effective 		= (($halfday['date'])?('<div class="fr"><em>(dated '.$halfday['date'].')</em></div>'):'');
			$tag['halfday'] = '<span style="font-size:14px">'.$days[$curr_amount].'</span>'.$effective;

			//opentime
			$opentime = $this->common->getmerit($empid, 'opentime');
			$curr_amount 	= (($opentime['amount']!=-1)?$opentime['amount']:$basic->opentime);
			$effective 		= (($opentime['date'])?('<div class="fr"><em>(dated '.$opentime['date'].')</em></div>'):'');
			$tag['opentime'] = '<span style="font-size:14px">'.(($curr_amount==1)?'Yes':'No').'</span>'.$effective;

			//daily
			$daily = $this->common->getmerit($empid, 'daily');
			$curr_amount 	= (($daily['amount']!=-1)?$daily['amount']:$basic->daily);
			$effective 		= (($daily['date'])?('<div class="fr"><em>(dated '.$daily['date'].')</em></div>'):'');
			$tag['daily'] = '<span style="font-size:14px">'.(($curr_amount==1)?'Yes':'No').'</span>'.$effective;
		}else{
			$tag['basic'] 			= form_input(array('name'=>'basic', 'value'=>(($basic->amount)?$basic->amount:0), 
													   'onChange'=>"var basic = this.value; 
                                                        $.ajax('employee/getsss/1/'+basic,
                                                        {beforeSend: function(){
                                                            $('#basic_sss').html('Updating...');
                                                        }, success: function(d){
                                                            $('#basic_sss').html(d);
                                                            $.ajax('employee/getpagibig/1/'+basic,
                                                            {beforeSend: function(){
                                                                $('#basic_pagibig').html('Updating...');
                                                            }, success: function(d){
                                                                $('#basic_pagibig').html(d);
                                                                $.ajax('employee/getphilhealth/1/'+basic,
                                                                {beforeSend: function(){
                                                                    $('#basic_philhealth').html('Updating...');
                                                                }, success: function(d){
                                                                    $('#basic_philhealth').html(d);
                                                                }});
                                                            }});
                                                        }});" ));
			$tag['allowance'] 		= form_input(array('name'=>'allowance', 'value'=>(($basic->allowance)?$basic->allowance:0)));
			if( $basic->daily == 1 ){
				$tag['sss']			= '---';
				$tag['pagibig']		= '---';
				$tag['philhealth']	= '---';
			}else{
				$tag['sss']				= '<span id="basic_sss">'.number_format( $this->payrollmd->getsss( (int) $basic->amount ), 2 ).'</span>';//form_input(array('name'=>'sss', 'value'=>(($basic->sss)?$basic->sss:0)));
				$tag['pagibig']			= '<span id="basic_pagibig">'.number_format( $this->payrollmd->getpagibig( (int) $basic->amount ), 2 ).'</span>';//form_input(array('name'=>'pagibig', 'value'=>(($basic->pagibig)?$basic->pagibig:0)));
				$tag['philhealth']		= '<span id="basic_philhealth">'.number_format( $this->payrollmd->getphilhealth( (int) $basic->amount ), 2 ).'</span>';//form_input(array('name'=>'philhealth', 'value'=>(($basic->philhealth)?$basic->philhealth:0)));
			}
			
			$tag['maxhours'] 		= form_input(array('name'=>'maxhours', 'value'=>(($basic->maxhours)?$basic->maxhours:0)));
			$tag['maxbreakhours'] 	= form_input(array('name'=>'maxbreakhours', 'value'=>(($basic->maxbreakhours)?$basic->maxbreakhours:0)));
			$tag['maxworkdays'] 	= form_input(array('name'=>'maxworkdays', 'value'=>(($basic->maxworkdays)?$basic->maxworkdays:0)));
			$tag['nightdiffin']		= form_input(array('name'=>'nightdiffin', 'class'=>"TCMask[##:##:##,".(($basic->nightdiffin)?$basic->nightdiffin:0)."]"));
			$tag['nightdiffout']	= form_input(array('name'=>'nightdiffout', 'class'=>"TCMask[##:##:##,".(($basic->nightdiffout)?$basic->nightdiffout:0)."]"));
			$days = array(
				'7' => '[----Select One----]', 
				'0' => 'Sunday', 
				'1' => 'Monday',
				'2' => 'Tuesday',
				'3' => 'Wednesday',
				'4' => 'Thursday',
				'5' => 'Friday',
				'6' => 'Saturday');
			$tag['halfday'] = form_dropdown('halfday', $days, $basic->halfday);
			$tag['opentime'] = '<input type="checkbox" name="opentime" value="1" '.(($basic->opentime==1)?'Checked':'').'>';
			$tag['daily'] = '<input type="checkbox" name="daily" value="1" '.(($basic->daily==1)?'checked':'').'>';
			
		}	
		
		$tag['save_button'] = (($isEditable)?'none':'');
		
		if($isEditable){
			$sssovr 		= $this->common->getmerit( $empid, 'sssovr' );
			$sssovr			= (($sssovr['amount']!=-1)?$sssovr['amount']:$employee->sssovr);
			$pagibigovr 	= $this->common->getmerit( $empid, 'pagibigovr' );
			$pagibigovr		= (($pagibigovr['amount']!=-1)?$pagibigovr['amount']:$employee->pagibigovr);
			$philhealthovr	= $this->common->getmerit( $empid, 'philhealthovr' );
			$philhealthovr	= (($philhealthovr['amount']!=-1)?$philhealthovr['amount']:$employee->philhealthovr);

			$sssded 		= $this->common->getmerit( $empid, 'sssded' );
			$sssded			= (($sssded['amount']!=-1)?$sssded['amount']:$employee->sssded);
			$pagibigded 	= $this->common->getmerit( $empid, 'pagibigded' );
			$pagibigded		= (($pagibigded['amount']!=-1)?$pagibigded['amount']:$employee->pagibigded);
			$philhealthded	= $this->common->getmerit( $empid, 'philhealthded' );
			$philhealthded	= (($philhealthded['amount']!=-1)?$philhealthded['amount']:$employee->philhealthded);
			
			$tag['b_merit'] 			= '<a href="javascript:;" onclick="this.blur(); modalbox(\'employee/merit/amount/'.$empid.'\', {width:600,height:300,title:\'Merit Amount\'});"><img src="image/merit.png"></a>';
			$tag['a_merit'] 			= '<a href="javascript:;" onclick="this.blur(); modalbox(\'employee/merit/allowance/'.$empid.'\', {width:600,height:300,title:\'Merit Allowance\'});"><img src="image/merit.png"></a>';
			if($sssded == 1){
				$tag['sss_merit'] 		= (($sssovr==1)?'<a href="javascript:;" onclick="this.blur(); modalbox(\'employee/merit/sss/'.$empid.'\', {width:600,height:300,title:\'SSS Compensation\'});"><img src="image/merit.png"></a>':'');
			}else{
				$tag['sss_merit'] 		= '';
			}
			if($pagibigded == 1){
				$tag['pagibig_merit'] 	= (($pagibigovr==1)?'<a href="javascript:;" onclick="this.blur(); modalbox(\'employee/merit/pagibig/'.$empid.'\', {width:600,height:300,title:\'Pagibig Compensation\'});"><img src="image/merit.png"></a>':'');
			}else{
				$tag['pagibig_merit'] 	= '';
			}
			if($philhealthded == 1){
				$tag['philhealth_merit'] 	= (($philhealthovr==1)?'<a href="javascript:;" onclick="this.blur(); modalbox(\'employee/merit/philhealth/'.$empid.'\', {width:600,height:300,title:\'Philhealth Compensation\'});"><img src="image/merit.png"></a>':'');
			}else{
				$tag['philhealth_merit'] 	= '';
			}
			$tag['maxhour_merit'] 		= '<a href="javascript:;"  onclick="this.blur(); modalbox(\'employee/merit/maxhours/'.$empid.'\', {width:600,height:300,title:\'Max Hours\'});"><img src="image/merit.png"></a>';
			$tag['maxbreak_merit'] 		= '<a href="javascript:;" onclick="this.blur(); modalbox(\'employee/merit/maxbreakhours/'.$empid.'\', {width:600,height:300,title:\'Max Break Hours\'});"><img src="image/merit.png"></a>';
			$tag['maxworkdays_merit'] 	= '<a href="javascript:;" onclick="this.blur(); modalbox(\'employee/merit/maxworkdays/'.$empid.'\', {width:600,height:300,title:\'Max Work Days\'});"><img src="image/merit.png"></a>';
			$tag['nightdiffin_merit'] 	= '<a href="javascript:;" onclick="this.blur(); modalbox(\'employee/merit/nightdiffin/'.$empid.'/1\', {width:600,height:300,title:\'Night Difference Time In\'});"><img src="image/merit.png"></a>';
			$tag['nightdiffout_merit'] 	= '<a href="javascript:;" onclick="this.blur(); modalbox(\'employee/merit/nightdiffout/'.$empid.'/1\', {width:600,height:300,title:\'Night Difference Time Out\'});"><img src="image/merit.png"></a>';
			$tag['halfday_merit'] 		= '<a href="javascript:;" onclick="this.blur(); modalbox(\'employee/merit/halfday/'.$empid.'/1\', {width:600,height:300,title:\'Night Difference Time Out\'});"><img src="image/merit.png"></a>';
			$tag['opentime_merit'] 		= '<a href="javascript:;" onclick="this.blur(); modalbox(\'employee/merit/opentime/'.$empid.'/1\', {width:600,height:300,title:\'Night Difference Time Out\'});"><img src="image/merit.png"></a>';
			$tag['daily_merit'] 		= '<a href="javascript:;" onclick="this.blur(); modalbox(\'employee/merit/daily/'.$empid.'/1\', {width:600,height:300,title:\'Night Difference Time Out\'});"><img src="image/merit.png"></a>';
		}else{
			$tag['b_merit'] = '';
			$tag['a_merit'] = '';
			$tag['sss_merit'] = '';
			$tag['pagibig_merit'] = '';
			$tag['philhealth_merit'] = '';
			$tag['maxhour_merit'] = '';
			$tag['maxbreak_merit'] = '';
			$tag['maxworkdays_merit'] = '';
			$tag['nightdiffin_merit'] = '';
			$tag['nightdiffout_merit'] = '';
			$tag['halfday_merit'] = '';
			$tag['opentime_merit'] = '';
			$tag['daily_merit'] = '';
		}
		
		$this->common->display("member/employee/basic.html", $tag);
	}

	function schedule($empid = 0){
		$tag['jscript'] = array('i18n/grid.locale-en', 'jquery.jqGrid.min', 'jquery-ui-custom.min');	    
        $tag['css'] = array('themes/ui.jqgrid', 'themes/ui.multiselect');
        
        $sql = "SELECT * FROM employee WHERE empid={$empid};";
		$employee = $this->db->query($sql)->row();
		$tag['name'] = $employee->lastname.', '.$employee->firstname.' '.$employee->middlename;
		$tag['schedule_list'] = $this->listschedule($empid, false);
		$this->common->display("member/employee/schedule.html", $tag);
	}

	function listschedule($empid = 0, $echo = true){
	    $this->load->library('xjqgrid');
        $sql = "SELECT 
                  s.scheduleid, 	
                  s.empid,
                  DATE_FORMAT(s.timein, '%H:%i') as timein,
                  DATE_FORMAT(s.timeout, '%H:%i') as timeout,
                  TIME_TO_SEC(TIMEDIFF(s.timeout, s.timein)) as ttime,
                  s.show,
                  (SELECT 
                        p.approved 
                    FROM payroll_detail pd 
                    LEFT JOIN payroll p ON p.payrollid=pd.payrollid 
                    WHERE pd.scheduleid=s.scheduleid AND approved=1 LIMIT 1) as approve
                FROM schedule s
                WHERE s.empid={$empid}";
        $this->xjqgrid->initGrid( $sql );
        $this->xjqgrid->setMethod('setUrl', "employee/listschedule/{$empid}/1");
        $oper = $_POST['oper'];
        if($oper == 'add'){
            $sql = "INSERT INTO schedule(timein, timeout, `show`, empid) 
                    VALUES('{$_POST['timein']}', '{$_POST['timeout']}', {$_POST['show']}, {$empid});";
            $this->db->query($sql);   
        }

        $this->xjqgrid->setColProperty("scheduleid", 
            array("hidden"=>true,"editable"=>false));
        $this->xjqgrid->setColProperty("empid", 
            array("hidden"=>true,"editable"=>false));
        $this->xjqgrid->setColProperty("timein", 
            array("label"=>"Time IN", 
                    "sortable"  =>  false,
                    "align"     => "center",
                    "editrules" => array(
                        "required"  => true,
                        "time"      => true
                    )));
        $this->xjqgrid->setColProperty("timeout", 
            array("label"=>"Time OUT", 
                    "sortable"  =>  false,
                    "align"     => "center",
                    "editrules" => array(
                        "required"  => true,
                        "time"      => true
                    )));
        $this->xjqgrid->setColProperty("show", 
            array(  "label"=>"Active", 
                    "formatter" => "checkbox", 
                    "edittype"  => "checkbox",
                    "width"     => 60,
                    "align"     => "center",
                    "editoptions" => array(
                        "value" => "1:0"
                        )
                    ));
        $this->xjqgrid->setColProperty("approve", 
            array("label"   => "Info",
                "editable"  => false, 
                "sortable"  => false,
                "formatter" => "select",
                "align"     => "center"
                ));
        $this->xjqgrid->setColProperty("ttime", array(
            "editable" => false,
            "width"    => "40",
            "align"    => "center",
            "label"    => "Hours",
            "sortable" => false,
            "formatter"=> "js:formathours"));
$myformat = <<<FORMATHOURS
function formathours (cellValue, options, rowdata) 
{
    var cellHtml = ((cellValue/60)/60).toFixed(2);
    return cellHtml; 
}
FORMATHOURS;
        $this->xjqgrid->grid->setJSCode($myformat);
        $this->xjqgrid->setSelect("approve", array("null"=>"Not Used", "1"=>"In Use"), true, true, false);    
//##############################################################################
//### THIS IS A SECURITY BOX, PUT SECURITY CHECKS HERE                       ###
//### FOR PAYROLL LOCKING AND UNAUTHORIZED EDITING                           ###
//##############################################################################
$onselrow = <<< ONSELROW
function(rowid, selected)
{
    if(rowid && rowid !== lastSelection) {
        $("#grid").jqGrid('restoreRow', lastSelection);
        lastSelection = currSelection = rowid;  

        var dat = $("#grid").jqGrid('getRowData', rowid);
        if(dat.approve == 1){return;}
        $("#grid").jqGrid('editRow', rowid, true, 
            function(){}, 
            function(){}, 
            "clientArray", '', 
            function(rowid){
                var data = $("#grid").jqGrid('getRowData', rowid);
                    $.post("employee/updatesched", data, function(d){
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
        $buttonoptions = array("#pager", 
            array("caption"      => "", 
                  "onClickButton"=> "js: function(){
                        var data = $('#grid').jqGrid('getRowData', currSelection); 
                        if(data.approve != 'null'){
                            alert('You cannot delete used items.');    
                        }else{
                            $.get('employee/deletesched/'+currSelection, function(d){
                                if(d==1){
                                    $('#grid').jqGrid('delRowData', currSelection); 
                                }else{
                                    alert('Unable to delete data.');
                                }
                            });                            
                        }
                  }",
                  "buttonicon"   => "ui-icon-trash",
                  "position"     => "first",
                  "title"        => "Delete"
                 ) 
        ); 
        $this->xjqgrid->grid->callGridMethod("#grid", "navButtonAdd", $buttonoptions); 
//##############################################################################
//### END OF SECURITY BOX                                                    ###
//##############################################################################
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
    
	function updatesched(){
        $scheduleid = $this->input->post('scheduleid', true);
        $timein     = $this->input->post('timein', true);
        $timeout    = $this->input->post('timeout', true);
        $show       = $this->input->post('show', true);

        $sql = "UPDATE schedule SET timein='{$timein}', timeout='{$timeout}', `show`={$show} WHERE scheduleid={$scheduleid};";
        $this->db->query($sql);
        $sql = "SELECT TIME_TO_SEC(TIMEDIFF(timeout, timein)) as ttime FROM schedule WHERE scheduleid={$scheduleid};";
        echo $this->db->query($sql)->row()->ttime; 
	}

	function deletesched( $scheduleid = 0 ){
        $sql = "DELETE FROM schedule WHERE scheduleid={$scheduleid}";
        if( $this->db->query( $sql ) ){
            echo 1;
        }else{
            echo 0;
        }        
	}

	function sendactivation($empid, $refcode=""){
        $this->load->model('mailermd');
        $sql = "SELECT * FROM employee WHERE empid={$empid}";
        $employee = $this->db->query($sql)->row();
        if(preg_match('/^([a-z0-9])(([-a-z0-9._])*([a-z0-9]))*\@([a-z0-9])*(\.([a-z0-9])([-a-z0-9_-])([a-z0-9])+)*$/i', $employee->email)){
            if($refcode == ""){
                $hash = md5($employee->email);
                $refcode = substr($hash, 0, 8);
                
                //update hash ref code
                $sql = "UPDATE employee SET refcode='{$refcode}' WHERE empid={$empid};";
                $this->db->query($sql);

                //get company ref code
                $sql = "SELECT refcode FROM company WHERE companyid={$this->ccompany};";
                $crefcode = $this->db->query($sql)->row()->refcode;
                
                //fill info
                $tag['name']        = $employee->firstname.' '.$employee->lastname;
                $tag['crefcode']    = $crefcode;
                $tag['erefcode']    = $refcode;

                
                $data['email']      = $employee->email;
                $data['subject']    = 'Registration Codes';
                $data['message']    = $this->parser->parse('member/employee/activation.html', $tag, TRUE);
                if($this->mailermd->email($data)){
                    echo '<script>alert("Successfully Sent")</script>'; 
                }
            }
        }else{
            echo '<script>alert("Invalid Email Address")</script>'; 
        }
	}
}
