<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class common extends CI_Model{
    var $app_name    = "my15/30";
    var $jscript_dir = "script/";
    var $css_dir     = "css/";
    var $acctid      = '';
    var $ccompany    = 0;
    var $acctype     = 0;
    var $full_name   = '';
    function __construct()
	{
		parent::__construct();
		session_start();
		$this->acctid    = $_SESSION['acctid'];
		$this->ccompany  = $_SESSION['dcompany'];
		$this->acctype   = $_SESSION['acctype'];
		$this->full_name = $_SESSION['full_name'];
	}

    function display( $file, $tag = array() ){
        $uri = $this->uri->segment_array(); 
        $tag['title']       = $this->app_name.((isset($tag['title']))?' - '.$tag['title']:'');
        $tag['base_url']    = base_url();
        $tag['script']      = '';
        $tag['style']       = '';
        $fram_base          = '';
        $menu = array(                
                'FEATURES'      =>  $fram_base.'page/features',
                'ADD-ONS'       =>  $fram_base.'page/addons',
                'SUPPORT'       =>  $fram_base.'page/support',
                'PRICING'       =>  $fram_base.'page/pricing',
            );

        $member = array(
                'Dashboard'     => 'member/',
                'Employees'     => 'employee/',
                'Payroll'       => 'payroll/',
                'HR Functions'  => 'hrfnc/',
                'Reports'       => 'report/',
                'Settings'      => 'setting/'
        );

        $portal = array(
                'Dashboard'     => 'portal/dashboard',
                'Payroll'       => 'portal/payroll',
                'HR Functions'  => 'uhrfnc/',
                'Profile'       => 'porta/profile' 
        );
        
        if(is_array($tag['jscript'])){
            foreach( $tag['jscript'] as $jsrc ){
                $tag['script'] .= '<script src="'.$this->jscript_dir.$jsrc.'.js"></script>'."\r\n";
            }
        }elseif(isset($tag['jscript'])){
            $tag['script'] = '<script src="'.$this->jscript_dir.$tag['jscript'].'.js"></script>'."\r\n";
        }

        if(is_array($tag['css'])){
            foreach( $tag['css'] as $css ){
                $tag['style'] .= '<link href="'.$this->css_dir.$css.'.css" rel="stylesheet" type="text/css" />'."\r\n";
            }
        }elseif(isset($tag['css'])){
            $tag['style'] = '<link href="'.$this->css_dir.$tag['css'].'.css" rel="stylesheet" type="text/css" />'."\r\n";
        }

       
        $tag['menu'] = array();
        if($this->acctid != 0){
            //get companies
            $sql = "SELECT * FROM company WHERE accountid={$this->acctid};";
            $query = $this->db->query( $sql );
            $company_list = array();
            foreach($query->result() as $row){
                $company_list[] = array(
                    'companyid' => $row->companyid,
                    'shortname' => (($row->shortname != '')?$row->shortname:$row->name),
                );
                if($row->default == 1 && $this->ccompany == 0){
                    $tag['current_company'] = (($row->shortname != '')?$row->shortname:$row->name);
                }elseif($this->ccompany == $row->companyid){
                    $tag['current_company'] = (($row->shortname != '')?$row->shortname:$row->name);
                }
            }
            $tag['company_list'] = $company_list;

            if($this->acctype == 1){            
                foreach($member as $key=>$val){
                    $tag['menu'][] = array(
                        'link'       => $val,
                        'link_name'  => $key,
                        'current'    => (($fram_base.$uri[1].'/'==$val)?'class="current"':'')
                    );
                }
                
                $tag['user_menu']   = $this->parser->parse('member/navigation.html', $tag, true);
                $tag['bottom_menu'] = $this->parser->parse('footnav.html', $tag, true);
            }else{

                $sql = "SELECT * FROM company WHERE companyid={$this->ccompany};";
                $query = $this->db->query( $sql );
                $company_list = array();
                foreach($query->result() as $row){
                    $company_list[] = array(
                        'companyid' => $row->companyid,
                        'shortname' => (($row->shortname != '')?$row->shortname:$row->name),
                    );
                    if($row->default == 1 && $this->ccompany == 0){
                        $tag['current_company'] = (($row->shortname != '')?$row->shortname:$row->name);
                    }elseif($this->ccompany == $row->companyid){
                        $tag['current_company'] = (($row->shortname != '')?$row->shortname:$row->name);
                    }
                }
                $tag['company_list'] = $company_list;
                
                foreach($portal as $key=>$val){
                    $tag['menu'][] = array(
                        'link'       => $val,
                        'link_name'  => $key,
                        'current'    => (($fram_base.$uri[1].'/'==$val || $fram_base.$uri[1].'/'.$uri[2]==$val)?'class="current"':'')
                    );
                }
                
                $tag['user_menu']   = $this->parser->parse('portal/navigation.html', $tag, true);
                $tag['bottom_menu'] = $this->parser->parse('portal/footnav.html', $tag, true);
            }

        }else{
            foreach($menu as $key=>$val){
                $tag['menu'][] = array(
                    'link'       => $val,
                    'link_name'  => $key,
                    'current'    => (($fram_base.$uri[1].'/'.$uri[2]==$val)?'class="current"':'')
                );
            }
            
            $tag['user_menu']   = $this->parser->parse('navigation.html', $tag, true);
            $tag['bottom_menu'] = $this->parser->parse('footnav.html', $tag, true);
        }
        
        if(isset($tag['__sub_nav__'])){
            $tag['submenu'] = array();
            foreach($tag['__sub_nav__'] as $key=>$val){                
                $tag['submenu'][] = array(
                    'link'       => $val,
                    'link_name'  => $key,
                    'current'    => (($uri[1].'/'.$uri[2]==$val)?'class="current"':'')
                );
            }
            $tag['sub_menu'] = $this->parser->parse('subnav.html', $tag, true);
        }else{
            $tag['sub_menu'] = '';
        }
        
        $tag['full_name'] = $this->full_name;
        $tag['body_content'] = $this->parser->parse( $file, $tag, TRUE );
        if($uri[1] == '' || $uri[1] == 'page'){
            $html  = $this->parser->parse( 'header.html',  $tag, TRUE );        
            $html .= $this->parser->parse( 'body.html',    $tag, TRUE );
            $html .= $this->parser->parse( 'footer.html',  $tag, TRUE );
        }elseif($this->acctype == 1){
            $html  = $this->parser->parse( 'member/header.html',  $tag, TRUE );        
            $html .= $this->parser->parse( 'body.html',    $tag, TRUE );
            $html .= $this->parser->parse( 'member/footer.html',  $tag, TRUE );
        }elseif($this->acctype == 2){
            $html  = $this->parser->parse( 'portal/header.html',  $tag, TRUE );        
            $html .= $this->parser->parse( 'body.html',    $tag, TRUE );
            $html .= $this->parser->parse( 'portal/footer.html',  $tag, TRUE );
        }
        
        echo $html;
    }

    function login(){
        $this->load->library('form_validation');
        
        if(isset($_POST['login']) && $this->form_validation->run('account/login')==TRUE){
			$sql = "SELECT * FROM account WHERE email='{$_POST['email']}' AND password='{$_POST['password']}'";
			$account = $this->db->query($sql)->row();
			if($account->accountid != ''){
				$_SESSION['acctid'] = $account->accountid;
				$_SESSION['acctype'] = 1;
				$this->acctid = $_SESSION['acctid'];
				//get default company
                $sql = "SELECT companyid FROM company WHERE accountid={$_SESSION['acctid']} AND `default`=1;";
                $_SESSION['dcompany'] = $this->db->query($sql)->row()->companyid;
                
				redirect('member');
			}else{
				return 'form_error';
			}
		}elseif(isset($_POST['login'])){
			return 'form_error';
		}

		return '';
    }

    function merit($empid=0, $payrollid=0, $type='', $replace = 0){
		//payroll
		$sql = "SELECT * FROM payroll WHERE payrollid={$payrollid};";
		$payroll = $this->db->query($sql)->row();
		
		//merits for amount
		if($replace){
			$sql = "SELECT value FROM middle_table WHERE sourceid={$empid} 
					AND entrydate <= '".$payroll->payperiod_from."'
					AND mtabletype=(SELECT mtable_typeid FROM mtable_type 
					WHERE name='{$type}') ORDER BY entrydate DESC LIMIT 1;";
			$row = $this->db->query($sql)->row();
			if(!$row)
				return '';
			
			return $row->value;
		}else{
			$sql = "SELECT sum(value) as amount from middle_table 
					WHERE sourceid={$empid} 
					AND entrydate <= '".$payroll->payperiod_to."'
					AND mtabletype=(SELECT mtable_typeid FROM mtable_type WHERE name='{$type}');";
		}
		$row = $this->db->query($sql)->row();			
		return $row->amount;
	}

	function getmerit($empid=0, $type=''){
		if($type == 'taxcode' || $type == 'sssded' || $type == 'philhealthded' || $type == 'pagibigded'
		  || $type == 'sssovr' || $type == 'pagibigovr' || $type == 'philhealthovr'){
			$sql = "SELECT mt.*, emp.{$type} FROM middle_table mt
					LEFT JOIN basic b ON b.empid=mt.sourceid
					LEFT JOIN employee emp ON emp.empid=mt.sourceid
					WHERE mt.sourceid={$empid} AND mtabletype=(SELECT mtable_typeid FROM mtable_type WHERE name='{$type}') 
					ORDER BY mt.entrydate ASC;";
		}else{
			$sql = "SELECT mt.*, b.{$type} FROM middle_table mt
					LEFT JOIN basic b ON b.empid=mt.sourceid
					WHERE mt.sourceid={$empid} AND mtabletype=(SELECT mtable_typeid FROM mtable_type WHERE name='{$type}') 
					ORDER BY mt.entrydate ASC;";
		}
		$query = $this->db->query( $sql );

		$total_merit = -1;
		$total_amount = -1;

		if($type == 'halfday' || $type == 'opentime' 
		  || $type == 'daily' || $type == 'taxcode'
		  || $type == 'sssded' || $type == 'philhealthded' || $type == 'pagibigded'
		  || $type == 'sssovr' || $type == 'pagibigovr' || $type == 'philhealthovr'){
			foreach($query->result() as $row){
				$date = date('M d, Y', strtotime($row->entrydate));
				$total_amount = $row->value;
			}
		}elseif($type == 'nightdiffin' || $type == 'nightdiffout'){
			$total_amount = 0;
			$total_merit  = 0;
			foreach($query->result() as $row){
				$date = date('M d, Y', strtotime($row->entrydate));
				$total_amount = date('H:i', strtotime($row->value));
			}
		}else{
			$total_amount = 0;
			$total_merit  = 0;
			foreach($query->result() as $row){
				$date = date('M d, Y', strtotime($row->entrydate));
				$total_merit += $row->value;
				$total_amount = ($row->{$type}+$total_merit);
			}
		}

		return array(
			'date'	 => $date,
			'amount' => $total_amount
		);
	}
	
	function iseditable($empid=0){
		$sql = "SELECT p.approved 
				FROM payroll_detail pd
				LEFT JOIN payroll p ON p.payrollid=pd.payrollid
				WHERE empid={$empid} GROUP BY pd.payrollid;";
		$query = $this->db->query( $sql );
		$editable = false;
		foreach( $query->result() as $row ){
			if( $row->approved == 1 )
				$editable = true;
		}
		return $editable;
	}

	function latesPaydate($empid=0){
		$sql = "SELECT p.payperiod_from, p.payperiod_to 
				FROM payroll_detail pd
				LEFT JOIN payroll p ON p.payrollid=pd.payrollid
				WHERE empid={$empid} AND p.approved=1 GROUP BY pd.payrollid 
				ORDER by p.payperiod_to desc LIMIT 1;";
		return $this->db->query($sql)->row();
	}
	
	function timeDiff($firstTime, $lastTime){
		// convert to unix timestamps
		$firstTime = strtotime($firstTime);
		$lastTime = strtotime($lastTime);

		// perform subtraction to get the difference (in seconds) between times
		$timeDiff = $lastTime-$firstTime;

		// return the difference
		return $timeDiff;
	}
	
	function display_print($tag = array(), $type = 1){
		$tag['title'] = "Payroll Express ".(isset($tag['title'])? '- '.$tag['title'] : '');
		$tag['styles'] = (isset($tag['styles'])? $this->dostyles( $tag['styles'] ) : '');
		$tag['scripts'] = (isset($tag['scripts'])? $this->doscripts( $tag['scripts'] ) : '');
		$tag['menu'] = '';
		$tag['base'] = base_url();
		$tag['navigation'] = '';
		$tag['logo'] 	= '';
		$tag['logo2'] 	= '';
		if($type == 1)
			$tag['logo'] = $this->parser->parse('common/logo.html', $tag, TRUE);
		else
			$tag['logo2'] = $this->parser->parse('common/logo.html', $tag, TRUE);
		$tag['content'] = (isset($tag['content'])? $tag['content'] : 'No content specified');
		
		$this->parser->parse('common/index.html', $tag, FALSE);
	}
	
	function get_pages($parentid=0){
		$sql = "SELECT * FROM page_access WHERE parent={$parentid};";
		$query = $this->db->query($sql);
		
		if($query->num_rows() == 0){return;}
		
		$tag['pages'] = array();
		foreach($query->result() as $i=>$row)
			$tag['pages'][] = array(
					'pageid' 		=> $row->accessid,
					'class'			=> (($parentid%2)?(($i%2)?'page_odd':'page_even'):(($i%2)?'page_even':'page_odd')),
					'pagename'		=> $row->page_label,
					'page_child' 	=> $this->get_pages( $row->accessid ),
					'control'		=> '<a href="javascript:;" onclick="Modalbox.show(\'account/newpage/'.$row->accessid.'\');">Add Child</a> | Remove | In Use'
				);
			
		return $this->parser->parse('pages/pageloop.html', $tag, TRUE);
	}
	
	function verify($page = ''){
		$accountid = $_SESSION['acctid'];
		if($accountid == ''){
			redirect('');
		}

		if($_SESSION['acctype'] == 2 && $page != 'portal'){
            redirect('portal');
		}
	}
	
	function access(){
		$accountid = $_SESSION['acctid'];
	}

	function format_date($data = '', $format = ''){
		$date = explode( "-", $data );
		return date( $format, mktime( 0, 0, 0, $date[1], $date[2], $date[0] ) );
	}

	function isPayLocked($payrollid=0){
        //payroll
        $sql = "SELECT approved FROM payroll WHERE payrollid={$payrollid};";
        return $this->db->query($sql)->row()->approved;
	}
}
