<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class portal extends CI_Controller {
    var $acctid   = 0;
    var $ccompany = 0;
    var $cctype   = 0;
	function __construct(){
		parent::__construct();
		$this->common->verify('portal');
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
        redirect('portal/dashboard');
	}
	
	function dashboard(){		
		$today      = date('d');
		$month      = date('m');
		$thisyear   = date('Y');
		
		$sql = "select 
		    firstname,
		    lastname,
		    DATE_FORMAT(birthdate, '%b %d') as birthdate 
		    FROM employee where 
		    MONTH(birthdate) = ".$month. " && DAY(birthdate) = ".$today.
		    " AND companyid={$this->ccompany}";
		    
		$query = $this->db->query($sql);
		$x=0;
		if($query->num_rows() > 0){
			foreach ($query->result() as $row){
				$data['today'][$x] = array(
					'birthdate' => $row->birthdate,
					'firstname' => $row->firstname,
					'lastname' => $row->lastname
				);
				$x++;
			}
		}
		
		else{
			$data['today'][0] = array(
                'birthdate' => '',
                'firstname' => 'No one is having birthday today.',
				'lastname'  => ''
			);
		}
        $sql = "SELECT 
            firstname,
		    lastname,
		    DATE_FORMAT(birthdate, '%b %d') as birthdate
		    from employee 
		    WHERE MONTH(birthdate) = ".$month." && DAY(birthdate) > ".$today." 
            AND companyid={$this->ccompany}
		    order by DAY(birthdate) asc";
		$query = $this->db->query($sql);
				
		$x = 0;
		
		if($query->num_rows() > 0){
		    foreach ($query->result() as $row){
			    $data['thismonth'][$x] = array(
				    'birthdate' => $row->birthdate,
				    'firstname' => $row->firstname,
				    'lastname' => $row->lastname
			    );
			    $x++;
		    }
		}else{
            $data['thismonth'][] = array(
                'firstname' => 'No one is having birthday this month.',
                'lastname'  => '',
                'birthdate' => ''
            );
		}
		
		$sql = "SELECT e.firstname, e.lastname, DATE_FORMAT(ep.date_hired, '%b %d, %Y') as date_hired 
		        FROM employee_pos ep
		        JOIN employee e ON e.empid=ep.empid
		        WHERE MONTH(ep.date_hired)={$month}
		        AND DAY(ep.date_hired)={$today}
		        AND YEAR(ep.date_hired)<{$thisyear}
		        AND companyid={$this->ccompany};";
		$query = $this->db->query($sql);
		$x = 0;
		
		if($query->num_rows() > 0){
			foreach ($query->result() as $row){				
			    $data['anniversary'][$x] = array(
				    'firstname'  => $row->firstname,
				    'lastname'   => $row->lastname,
				    'date_hired' => $row->date_hired
			    );
			    $x++;
			}
		}else{
            $data['anniversary'] = array();
		}
		$data['event_calendar'] = $this->calendar(date("m"), date("Y"), true);
		$this->common->display("portal/dashboard.html", $data);
	}

	function calendar($month, $year, $return = false){			
        $today = explode("/", date("F/d/Y"));
       
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
					    'col_class' => 'event_days_blank',
					    'is_today'  => '');
				}else{
				    $dday = ($num - $blank);
				    $sql = "SELECT ecalid FROM event_calendar 
				            WHERE DATE_FORMAT(eventstart, '%M %e %Y') = '{$month_title} {$dday} {$year}';";
				    
					$data['row'][$y]['col'][$num] = array(
					    'day'       => $dday,
					    'col_class' => '',
					    'is_today'  => (($today[0]==$month_title && $today[1]==$dday && $today[2]==$year)?'color_today':''),
					    'has_event' => (($this->db->query($sql)->num_rows()>0)?'color_hasevent':'') );
				}
			}else{
			    $dday = ($num - $blank);
			    $sql = "SELECT ecalid FROM event_calendar 
				            WHERE DATE_FORMAT(eventstart, '%M %e %Y') = '{$month_title} {$dday} {$year}';";
				$data['row'][$y]['col'][$num - $blank] = array( 
				        'day' => $dday,
				        'col_class' => '',
				        'is_today'  => (($today[0]==$month_title && $today[1]==$dday && $today[2]==$year)?'color_today':''),
					    'has_event' => (($this->db->query($sql)->num_rows()>0)?'color_hasevent':'') );
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
						
		$cal = $this->parser->parse('portal/eventcalendar.html', $data, true);
		if($return){return $cal;}
		echo $cal;
		
	}

	function viewevent($month, $day, $year){
		$query = $this->db->query("select what as `What`, location as `Location`, DATE_FORMAT(eventstart, '%h:%i %p') as `Start`, DATE_FORMAT(eventend, '%h:%i %p') as `End`, description as `Description` from event_calendar where DAY(eventstart) = ".$day." && MONTH(eventstart) = ".$month." && YEAR(eventstart) = ".$year);
		$this->load->library('table');
		$table = $this->table->generate($query);
		
		$data['table'] = $table;
		$data['day'] = $day;
		$data['month'] = $month;
		$data['year'] = $year;
		
		$this->parser->parse("portal/view_event.html", $data);
	}
}
