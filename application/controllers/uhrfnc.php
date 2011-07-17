<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class uhrfnc extends CI_Controller {
    var $acctid = 0;
    var $ccompany = 0;
	var $empid = 0;
	var $acctype = 0;
	
	function __construct(){
		parent::__construct();
		//$this->common->verify(); 
		$this->acctid   = $_SESSION['acctid'];
		$this->ccompany = $_SESSION['dcompany'];
        $this->acctype  = $_SESSION['acctype'];
        $this->empid    = $_SESSION['empid'];
		
        $this->load->library('table');
		$tmpl = array (
                    'table_open'          => '<table border="0" cellpadding="4" cellspacing="0" width="100%" style="font-size: 12px">',
                    'row_start'           => '<tr class="tr_odd">',
                    'row_alt_start'       => '<tr class="tr_even">'
              );
        $this->table->set_template($tmpl); 
	}

    function ikawka(){}

	function index(){
        redirect('uhrfnc/dashboard');
	}
	
	function getpendingleave(){
        $sql = "SELECT * FROM leave_type";
        $query = $this->db->query( $sql );
        $leave_type = array();
        foreach($query->result() as $row){
            $leave_type[$row->leavetypeid] = $row->name;
        }
        
        $query = $this->db->select('leaveid,leavetypeid,startdate,enddate,description')
                          ->where('empid', $this->empid)
		                  ->where('approved', 0)
		                  ->order_by('leaveid', 'desc')
		                  ->limit(4)
		                  ->get('leave');

        $data = array();		
	    foreach($query->result() as $row){
	        $start_date = date("M d, Y", strtotime($row->startdate));
	        $end_date   = date("M d, Y", strtotime($row->enddate));
	        
            $data[] = array(                
                'type'   => $leave_type[$row->leavetypeid],
                'name'   => $start_date." - ".$end_date,
                'detail' => $row->description,
                'leaveid'=> $row->leaveid
            );    
	    }
	    return $data;
    }

    function dashboard(){
        $data['leave_list'] = $this->getpendingleave();
        $this->common->display("portal/uhrfnc/dashboard.html", $data);
    }
	
	
	// add ovetime or undertime form
	function addotutform() {	
		
		if (($_POST['startdate']!=NULL) && ($_POST['enddate']!="") && ($_POST['description']!="")) {
					
					$empid = $this->empid;
					$ccompany = $this->ccompany; 
					
					//for ( $hour = 1; $hour <= 24; $hour ++) {
					//	$data['hour'] = $hour;
					//}

					
					//$date_timein1 = $_POST['startdate']."".$_POST['starttime'];
					//$date_timeout1 = $_POST['enddate']."".$_POST['endtime'];
					
					$date_timein=date('Y-m-d h:i:s',strtotime($this->input->post('startdate').$this->input->post('starttime')));
					$date_timeout=date('Y-m-d h:i:s',strtotime($this->input->post('enddate').$this->input->post('endtime')));
					
					$db_data = array(
				
					'empid'=> ($empid),
					'companyid'=> ($ccompany),
					'date_timein' => $date_timein,
					'date_timeout' => $date_timeout,
					'description' => $this->input->post('description')
					);
			
					
					
					if ($_POST['choosetype']==0) {	
						$this->db->insert("over_time", $db_data);
						$data['error']="You filled an overtime.";
					}
					
					else {
						$this->db->insert("under_time", $db_data);
						$data['error']="You filled an undertime.";
					}
					
					
					
						
				}
				
				else {
					
					if (isset($_POST['trap'])){
						$data['error']="Please complete all fields.";
					}
					
					else {
						$data['error']="";
					}
				}
	
			$this->common->display("portal/addotutform.html", $data);	
	}
	
	
	//view all list of overtime undertime
	function viewotut() {
			
		
		$approvedid = array(0=>'Pending', 1=>'Approved', 2=>'Denied');
		
		//query overtime table
		$query_ot = $this->db->where('empid', $this->empid)->get('over_time');
		$this->table->set_heading('Date Time-in', 'Date Time-out','Description','Approved Status');
			
			foreach($query_ot->result() as $row){
				$data['leave'][$x] = array(
										'leavetypeid' => $row->leavetypeid
										
										);
				
				if (($approvedid[$row->approved])=='Pending'){
					$link_cancel = anchor('urhrfnc/cancelleave/'.$row->otimeid,'Cancel');
					 
				}
				else {
					$link_cancel = '';
				}
				
				
				$this->table->add_row($row->date_timein,$row->date_timeout,$row->description,$approvedid[$row->approved].' | '.$link_cancel);
			}
		$table_ot = $this->table->generate();
		$data['table_ot'] = $table_ot;
		
		
		//query undertime table
		$query_ut = $this->db->where('empid', $this->empid)->get('under_time');
		$this->table->set_heading('Date Time-in', 'Date Time-out','Description','Approved Status');
			
			foreach($query_ut->result() as $row){
				$data['leave'][$x] = array(
										'leavetypeid' => $row->leavetypeid
										
										);
				
				
				$this->table->add_row($row->date_timein,$row->date_timeout,$row->description,$approvedid[$row->approved]);
			}
			
			$table_ut = $this->table->generate();
			$data['table_ut'] = $table_ut;
	

		
		$this->common->display("portal/viewotut.html", $data);
	
	}

	function editleave($leaveid=0){        
        $data['__sub_nav__'] = array(
    	    'Dashboard'         => 'uhrfnc/dashboard',
            'View All Requests' => 'uhrfnc/viewleaves',
        );
        
        $data['error']="";
        if (($_POST['startdate']!=NULL) && ($_POST['enddate']!="") && ($_POST['description']!="")) {				
			$empid          = $this->empid;
			$ccompany       = $this->ccompany; 				
			$newstartdate   = date('Y-m-d',strtotime($this->input->post('startdate', TRUE)));
			$newenddate     = date('Y-m-d',strtotime($this->input->post('enddate', TRUE)));

			$db_data = array(
			    'companyid'     => ($ccompany),
			    'leavetypeid'   => $this->input->post('leavetypeid', TRUE),
			    'startdate'     => $newstartdate,
			    'enddate'       => $newenddate,
			    'description'   => $this->input->post('description', TRUE)
			);
			
            $this->db->where('leaveid', $leaveid)->where('empid', $this->empid)->update('leave', $db_data); 
	        if( $this->db->insert( "leave", $db_data ) ){
				$data['error']  = "Successfully Updated leave request.";				
	        }else{
	            $data['error']  = "Unable to Update leave request, please contact the admistrator."; 
	        }
		}elseif (isset($_POST['newleave'])){
			$data['error']='Fields with <span style="color: #ff0000">*<span> are required.';
		}
		
        $sql = "SELECT 
                    leavetypeid, 
                    DATE_FORMAT(startdate, '%m/%d/%Y') as startdate,
                    DATE_FORMAT(enddate, '%m/%d/%Y') as enddate,
                    description
                FROM `leave` 
                WHERE leaveid={$leaveid} AND empid={$this->empid};";
        $row =$this->db->query( $sql )->row();
        
        $sql = "SELECT * FROM leave_type";
        $query = $this->db->query( $sql );
        $leave_type = array();
        
        foreach($query->result() as $r){
            $data['leave_type'][] = array(
                'value' => $r->leavetypeid,
                'label' => $r->name,
                'selected' => (($row->leavetypeid==$r->leavetypeid) ? 'SELECTED' : '')
            );
        } 
        
        $data['startdate']      = $row->startdate;
        $data['enddate']        = $row->enddate;
        $data['description']    = $row->description;
        $this->common->display("portal/uhrfnc/editleave.html", $data);
	}
	
	
	// add leave form
	function addleave() {	
	    $data['__sub_nav__'] = array(
    	    'Dashboard'         => 'uhrfnc/dashboard',
            'View All Requests' => 'uhrfnc/viewleaves',
        );
        
	    $sql = "SELECT * FROM leave_type";
        $query = $this->db->query( $sql );
        $leave_type = array();
        foreach($query->result() as $row){
            $data['leave_type'][] = array(
                'value' => $row->leavetypeid,
                'label' => $row->name
            );
        }        	    
        $data['error']="";
	    if (($_POST['startdate']!=NULL) && ($_POST['enddate']!="") && ($_POST['description']!="")) {				
			$empid          = $this->empid;
			$ccompany       = $this->ccompany; 				
			$newstartdate   = date('Y-m-d',strtotime($this->input->post('startdate', TRUE)));
			$newenddate     = date('Y-m-d',strtotime($this->input->post('enddate', TRUE)));

			$db_data = array(			
			    'empid'         => ($empid),
			    'companyid'     => ($ccompany),
			    'leavetypeid'   => $this->input->post('leavetypeid', TRUE),
			    'startdate'     => $newstartdate,
			    'enddate'       => $newenddate,
			    'description'   => $this->input->post('description', TRUE)
			);
	        if( $this->db->insert( "leave", $db_data ) ){
				$data['error']  = "You have successfully added your leave request.";				
	        }else{
	            $data['error']  = "Unable to add leave request, please contact the admistrator."; 
	        }
		}elseif (isset($_POST['newleave'])){
			$data['error']='Fields with <span style="color: #ff0000">*<span> are required.';
		}

		$this->common->display("portal/uhrfnc/addleave.html", $data);	
	}

	function cancelleave($leaveid=0, $echo = false){
        $sql = "UPDATE `leave` SET approved=3 WHERE leaveid={$leaveid} AND empid={$this->empid};";
        if( $this->db->query($sql) ){
            if($echo){echo 1; exit;}
            redirect('uhrfnc/viewleaves');
        }        
	} 
	
	//view all list of leaves
	function viewleaves() {
	    $data['__sub_nav__'] = array(
	        'Dashboard'         => 'uhrfnc/dashboard',
		    'New Leave Request' => 'uhrfnc/addleave',
		);
				
		$approvedid = array(
		        0 => 'Pending', 
		        1 => 'Approved', 
		        2 => 'Denied');
		$sql = "SELECT
		            lv.leaveid, 
		            lt.name as leave_type,
		            DATE_FORMAT(lv.startdate, '%b %d, %Y') as startdate,
		            DATE_FORMAT(lv.enddate, '%b %d, %Y') as enddate,
		            description,
		            lv.approved
		        FROM `leave` lv 
		        LEFT JOIN leave_type lt ON lt.leavetypeid=lv.leavetypeid
		        WHERE lv.empid={$this->empid} 
		        AND lv.approved != 3 
		        ORDER BY lv.startdate DESC";
		$query = $this->db->query($sql); //$this->db->where('empid', $this->empid)->get('leave');		
		$this->table->set_heading('Leave Type', 'Leave Date','Description','Status', '!');
		foreach($query->result() as $row){
            $this->table->add_row(
                $row->leave_type,
                array('data' => $row->startdate.' - '.$row->enddate, 'align' => 'center'),
                $row->description,
                array('data' => $approvedid[$row->approved], 'align' => 'center'),
                array('data' => (($row->approved==0)?'<a href="uhrfnc/cancelleave/'.$row->leaveid.'" onclick="if(!confirm(\'Are you sure, you want to cancel this request?\')){return false;}"><img src="image/btn_cancel.png"></a>':'<img src="image/btn_canceldis.png">').' <a href="uhrfnc/editleave/'.$row->leaveid.'"><img src="image/btn_view.png"></a>', 'align' => 'center'));
		}
			
		$table = $this->table->generate();			
		$data['table'] = $table;

		$this->common->display("portal/uhrfnc/viewleavelist.html", $data);	
	}
	
	
	
	
	//view all list of failure
	function viewfailure() {
		
		
		$failure = array(0=>'Time In', 1=>'Time Out');
		
		$query = $this->db->where('empid', $this->empid)->get('failure_inout');
		
		$this->table->set_heading('Failure Date', 'Failure Type', 'Date Filled');
			
			foreach($query->result() as $row){
				$data['failure_inout'][$x] = array(
										'failuretype' => $row->failuretype
										
										);
				
				
				$this->table->add_row($row->failuredate,$failure[$row->failuretype],$row->datefilled);
			}
			
			
			$table = $this->table->generate();
			
			$data['table'] = $table;

		$this->common->display("portal/viewfailurelist.html", $data);
	
	}
	
	
	//add form for new failure in and out
	function newfailure(){
		
				if (($_POST['failuredate']!=NULL) || ($_POST['failuredate']!="")) {
				$empid=$this->empid;
				
				$newdate=date('Y-m-d',strtotime($this->input->post('failuredate')));
				
				$db_data = array(
			
				'empid'=> ($empid),
				'failuredate' => $newdate,
				'failuretype' => $this->input->post('failuretype'),
				'datefilled' => date('Y-m-d H:i:s')	
				);
		
				$data['error']="";
				$this->db->insert("failure_inout", $db_data);
			}
			
			else {
				
				if (isset($_POST['newfailure'])){
					$data['error']="Please Select Date";
				}
				
				else {
					$data['error']="";
				}
			}
			
		
				$this->common->display("portal/addfailureform.html", $data);
	}
}
