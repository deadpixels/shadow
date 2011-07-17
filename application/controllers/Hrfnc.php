<?php

	class Hrfnc extends CI_Controller{
		
		var $acctid = 0;
    	var $ccompany = 0;
		function __construct(){
			parent::__construct();
			//$this->common->verify('member');
			$this->sub_nav = array(
				'New Event' => 'hrfnc/newevent',
				'Today'    	=> 'hrfnc/showtoday'
			);
			$this->acctid = $_SESSION['acctid'];
			$this->ccompany = $_SESSION['dcompany'];
			$this->load->library('table');
			$tmpl = array (
					   'table_open'          => '<table border="0" cellpadding="4" cellspacing="0" width="100%" style="font-size: 12px">',
					   'row_start'           => '<tr class="tr_odd">',
					   'row_alt_start'       => '<tr class="tr_even">'
			 );
			$this->table->set_template($tmpl); 
		}
		
		function index(){
			$month = date("m");	
			$year = date("Y");
			
			
			$this->calendar($month, $year);
		}
		
		function calendar($month, $year){
			
			$data['__sub_nav__'] = $this->sub_nav;
			
			$first_day = mktime(0,0,0,$month,1,$year);	//make time of first day of month
			$month_title = date("F", $first_day);		//the entire month word
			$day_of_week = date("w", $first_day);		//first day of week
			$blank = $day_of_week;						//number of blanks starting from sun
			$num_days_month = Date("t", $first_day);	//number of days in the month
			
			$data['month_title'] = $month_title;
			$data['month'] = $month;
			$data['year'] = $year;
			$data['prev_month'] = $month - 1;
			$data['next_month'] = $month + 1;
			$data['prev_year'] = $year;
			$data['next_year'] = $year;
			if($month == 12){
				$data['next_month'] = 1;	
				$data['next_year'] = $year + 1;
			}
			else if($month == 1){
				$data['prev_month'] = 12;	
				$data['prev_year'] = $year - 1;
			}
			
			foreach(range(1, $blank) as $num){			//blank
				$data['blank'][$num] = null;
			}
			
			$y = 0;
			$counter = 7;
			foreach(range(1, $num_days_month + $blank) as $num){				
				if($y == 0){
					if($num <= $blank){
						$data['row'][$y]['col'][$num] = array(
						    'day'       => null,
						    'col_class' => 'event_days_blank');
					}else{
						$data['row'][$y]['col'][$num] = array(
						    'day'       => $num - $blank,
						    'col_class' => '');
					}
				}else{
					$data['row'][$y]['col'][$num - $blank] = array( 
					        'day' => $num - $blank);
				}
				
				$counter--;
				if($counter == 0){
					$y++;
					$counter = 7;
				}
			}
			if($counter != 7){
			    $start = (7 - $counter);
			    for($i=$start; $i<7; $i++){
                    $data['row'][$y]['col'][$i] = array(
						    'day'       => null,
						    'col_class' => 'event_days_blank');
                }
			}
			$this->common->display("member/hrfnc/hrfnc.html", $data);
		
		}
		
		function addevent($year, $month, $day){
			$data['jscript'] = array('jquery.inputmask');
			//add events
			$data['errors'] = null;
			
			if(isset($_POST['newevent'])){
				$this->load->library('form_validation');
				$this->form_validation->set_error_delimiters('<div class="ui-widget" style="margin-bottom: 0.5em"><div class="ui-state-error ui-corner-all">','</div></div>');
				if($this->form_validation->run('hrfnc/addevent')== TRUE){
					$db_data['companyid'] = $this->ccompany;
					$db_data['what'] = $_POST['what'];
					$db_data['location'] = $_POST['location'];
					//when
					$start = strtotime($start_date . " " . $_POST['when12']);
					$end = strtotime($end_date . " " . $_POST['when22']);
					$db_data['eventstart'] = Date("Y-m-d H:i:s", $start);
					$db_data['eventend'] = Date("Y-m-d H:i:s", $end);
					$db_data['location'] = $_POST['location'];
					$db_data['description'] = $_POST['description'];
					$db_data['created_by'] = $this->acctid;
					
					list($h1, $m1) = explode(":", $data['when12']);
					list($h2, $m2) = explode(":", $_POST['when22']);
					
					if(!($_POST['when12'] == "^[0-9]{2}:[0-9]{2}$" || $_POST['when22'] == "^[0-9]{2}:[0-9]{2}$") || $start >= $end){
						$data['errors'] = "Invalid input date/time";
					}
					else{
						if(($h1 > 23 || $h2 > 23) || ($m1 > 59 || $m2 > 59)){
							$data['errors'] = "Invalid input time";
						}
						else
							$this->db->insert('event_calendar', $db_data);
					}
					
				}
				else{
					$data['errors'] =  validation_errors();
				}
				
			}
			
			//view events
			$query = $this->db->query("select what, location, eventstart, eventend, description from event_calendar where DAY(eventstart) = ".$day." && MONTH(eventstart) = ".$month." && YEAR(eventstart) = ".$year." && companyid = ".$this->ccompany);
			$this->load->library('table');
			$table = $this->table->generate($query);
			
			$data['event_table'] = $table;
			$data['day'] = $day;
			$data['month'] = $month;
			$data['year'] = $year;
			
			$this->common->display("member/hrfnc/add_event.html", $data);
		}
		
		function addmemo(){
			$data['errors'] = null;
			//post
			if(isset($_POST['newmemo'])){
				$this->load->library('form_validation');
				$this->form_validation->set_error_delimiters('<div class="ui-widget" style="margin-bottom: 0.5em"><div class="ui-state-error ui-corner-all">','</div></div>');
				if($this->form_validation->run()== TRUE){
					$db_data['empid'] = $_POST['empid'];
					$db_data['companyid'] = $this->ccompany;
					$db_data['memo_date'] = Date("Y-m-d", strtotime($_POST['memo_date']));
					$db_data['memo_type'] = $_POST['memo_type'];
					$db_data['memo_content'] = $_POST['memo_content'];
					$db_data['created_by'] = $this->acctid;
					
					$this->db->insert('memo', $db_data);
				}
				
				else{
					$data['errors'] =  validation_errors();
				}
			}
			
			//form
			$query = $this->db->where('companyid', $this->ccompany)->get('employee');
			$x = 0;
			foreach($query->result() as $row){
				$data['employee'][$x] = array(
										'empid' => $row->empid,
										'firstname' => $row->firstname,
										'lastname' => $row->lastname);
				$x++;
			}
			
			$query = $this->db->get('memo_type');
			$x = 0;
			foreach($query->result() as $row){
				$data['memo'][$x] = array(
									'memoid' => $row->mtypeid,
									'type' => $row->memo_name);
				$x++;
			}
			
			//view memo
			$query = $this->db->where('companyid', $this->ccompany)->order_by('memo_date', 'desc')->get('memo');
			$x = 1;
			$data_memo[0]['firstname'] = "First Name"; 
			$data_memo[0]['lastname'] = "Last Name"; 
			$data_memo[0]['memo_date'] = "Memo Date"; 
			$data_memo[0]['memo_type'] = "Memo Type"; 
			$data_memo[0]['memo_content'] = "Content"; 
			$data_memo[0]['date_created'] = "Date Created"; 
			foreach($query->result() as $row){
				$query_employee = $this->db->where('empid', $row->empid)->get('employee');
				
				foreach($query_employee->result() as $row2){
					$data_memo[$x]['firstname'] = $row2->firstname;
					$data_memo[$x]['lastname'] = $row2->lastname;
				}
				list($date, $time) = explode(" ", $row->memo_date);
				$data_memo[$x]['memo_date'] = $date;
				$query_memotype = $this->db->where('mtypeid', $row->memo_type)->get('memo_type');
				foreach($query_memotype->result() as $row2){
					$data_memo[$x]['memo_type'] = $row2->memo_name;
				}
				$data_memo[$x]['memo_content'] = $row->memo_content;
				$data_memo[$x]['date_created'] = $row->date_created;
				$x++;
			}
			$this->load->library('table');
			$table = $this->table->generate($data_memo);
			
			$data['memo_table'] = $table;
			
			$this->common->display("member/hrfnc/add_memo.html", $data);
		}
	}
?>
