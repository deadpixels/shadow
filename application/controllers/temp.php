<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class temp extends CI_Controller {
    var $acctid = 0;
    var $ccompany = 0;
	var $empid = 0;
	var $acctype = 0;
	
	function __construct(){
		parent::__construct();
		
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

	function index(){
	}
	
	function viewmemo(){
		$query = $this->db->where('companyid', $this->ccompany)
						  ->where('empid', $this->empid)
						  ->order_by('memo_date', 'desc')
						  ->get('memo');
		$x = 1;
		$data_memo[0]['memo_date'] = "Memo Date"; 
		$data_memo[0]['memo_type'] = "Memo Type"; 
		$data_memo[0]['memo_content'] = "Content"; 
		
		foreach($query->result() as $row){
			list($date, $time) = explode(" ", $row->memo_date);
			$data_memo[$x]['memo_date'] = $date;
			$query_memotype = $this->db->where('mtypeid', $row->memo_type)->get('memo_type');
			foreach($query_memotype->result() as $row2){
				$data_memo[$x]['memo_type'] = $row2->memo_name;
			}
			$data_memo[$x]['memo_content'] = $row->memo_content;
			$x++;
		}
		$this->load->library('table');
		$table = $this->table->generate($data_memo);
			
		$data['memo_table'] = $table;
		
		$this->common->display("temp1/temp1.html", $data);
	}
	
	function schedplotter(){
		$data['errors'] = null;
		
		if(isset($_POST['plotsched'])){
			$this->load->library('form_validation');
			$this->form_validation->set_error_delimiters('<div class="ui-widget" style="margin-bottom: 0.5em"><div class="ui-state-error ui-corner-all">','</div></div>');
			if($this->form_validation->run()== TRUE){
				$db_data['empid'] = $this->acctid;
				$db_data['label'] = $_POST['label'];
				$db_data['status'] = 0;
								
				$this->db->insert('employee_sched', $db_data);
				
			}
			
			else{
				$data['errors'] =  validation_errors();
			}
		}
		
		$query = $this->db->where('empid', $this->acctid)->get('employee_sched');
		$x = 0;
		foreach($query->result() as $row){
			$data['sched'][$x] = array('eschedid' => $row->eschedid,
							'label' => $row->label);
			$x++;
		}
		
		$this->common->display("temp1/scheduleplotter.html", $data);
	}
	
	function plotsched($eschedid){
		$data['jscript'] = array('jquery.inputmask');
		$data['errors'] = null;
		
		if(isset($_POST['plot'])){
			$this->load->library('form_validation');
			$this->form_validation->set_error_delimiters('<div class="ui-widget" style="margin-bottom: 0.5em"><div class="ui-state-error ui-corner-all">','</div></div>');
			
			if($this->form_validation->run('temp/plotsched')== TRUE){
				$db_data['eschedid'] = $eschedid;
				list($hour_in, $minute_in) = explode(":", $_POST['time_in']);
				list($hour_out, $minute_out) = explode(":", $_POST['time_out']);
				$db_data['date_in'] = Date("Y-m-d H:i:s", strtotime($_POST['date_in']." ".$_POST['time_in']));	
				$db_data['date_out'] = Date("Y-m-d H:i:s", strtotime($_POST['date_out']." ".$_POST['time_out']));
				
				if(!($_POST['time_in'] == "^[0-9]{2}:[0-9]{2}$" || $_POST['time_in'] == "^[0-9]{2}:[0-9]{2}$")){
					$data['errors'] = "Invalid input date/time";
				}
				else{
					if($hour_in > 23 || $hour_out > 23 || $minute_in > 59 || $minute_out > 59){
						$data['errors'] = "Invalid input time";
					}
					
					else{
						$this->db->insert('esched_detail', $db_data);
					}
				}
			}
			
			else{
				$data['errors'] =  validation_errors();
			}
		}
		
		$query = $this->db->where('eschedid', $eschedid)->get('esched_detail');
		
		$data_esched[0]['date_in'] = "Date In"; 
		$data_esched[0]['date_out'] = "Date Out";
		$data_esched[0]['row3'] = "";
		$x=1;
		foreach($query->result() as $row){
			$data_esched[$x]['date_in'] = substr($row->date_in, 0, 16); 
			$data_esched[$x]['date_out'] = substr($row->date_out, 0, 16); 
			$data_esched[$x]['row3'] = "<a href='temp/deletesched/".$row->eschedid."/".$row->escheddetailid."'>Delete</a>"; 
			$x++;
		}
		
		$this->load->library('table');
		$table = $this->table->generate($data_esched);
		
		$data['sched_table'] = $table;
		$this->common->display("temp1/plotsched.html", $data);
	}
	
	function deletesched($eschedid, $escheddetailid){
		$this->db->where('escheddetailid', $escheddetailid)->delete('esched_detail');
		header("Location: http://".$_SERVER['SERVER_NAME']."/my1530/temp/plotsched/".$eschedid);
	}

}
