<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class page extends CI_Controller {

	function __construct()
	{
		parent::__construct();
	}

	function index()
	{	    
        $tag['jscript'] = array('jquery.lightbox-0.5.min');	    
        $tag['css'] = array('jquery.lightbox-0.5');
        $tag['form_error'] = $this->common->login();
		$this->common->display("page/home.html", $tag);
	}

	function portal(){
        if(isset($_POST['register'])){
            $this->load->library('form_validation');
            $this->form_validation->set_error_delimiters('<div class="ui-widget" style="margin-bottom: 0.5em"><div class="ui-state-error ui-corner-all">','</div></div>');
            if($this->form_validation->run()== TRUE){
                //check both ref code matching            
                $sql = "SELECT 
                            e.companyid, 
                            e.email, 
                            e.empid 
                        FROM employee e
                        RIGHT JOIN company c ON c.companyid=e.companyid
                        WHERE e.refcode='{$_POST['erefcode']}' 
                          AND c.refcode='{$_POST['crefcode']}';";
                $emp = $this->db->query($sql)->row();
                if(!$emp){
                    $tag['errors'] = '<div class="ui-widget" style="margin-bottom: 0.5em"><div class="ui-state-error ui-corner-all">Employee and Company Reference code do not match.</div></div>';
                }else{
                    $activation = md5(date("m/d/Y"));
                    $sql ="INSERT INTO account(empid,username,password,email,activation,type,active)
                            VALUES({$emp->empid},'{$_POST['username']}','{$_POST['password']}','{$emp->email}','{$activation}',2,0);";
                    if($this->db->query($sql)){
                        //email activation code
                        $this->load->model('mailermd');
                        
                    }
                }
            }else{
                $tag['errors'] = validation_errors();
            }
        }elseif(isset($_POST['login'])){
            $this->load->library('form_validation');
            $this->form_validation->set_error_delimiters('<div class="ui-widget" style="margin-bottom: 0.5em"><div class="ui-state-error ui-corner-all">','</div></div>');
            if($this->form_validation->run('portal/login') == TRUE){
                $sql = "SELECT a.accountid, e.companyid, e.firstname, e.lastname, e.empid, a.activation, e.active FROM account a
                        RIGHT JOIN employee e ON e.empid=a.empid
                        WHERE a.username='{$_POST['username']}' 
                            AND a.password='{$_POST['password']}' 
                            AND a.type=2";
                $query =  $this->db->query($sql)->row();
                if(!$query){
                    $tag['errors']      = '';
                    $tag['form_error']  = 'form_error';
                }elseif($query->active == 1){
                    $_SESSION['acctid']     = $query->accountid;
                    $_SESSION['dcompany']   = $query->companyid;
                    $_SESSION['acctype']    = 2;
                    $_SESSION['empid']      = $query->empid;
                    $_SESSION['full_name']  = ucfirst($query->firstname).' '.ucfirst($query->lastname);
                    redirect('portal');
                }else{
                    //inactive account
                    if($query->activation == ''){
                        redirect('page/terminated/');
                    }else{
                        redirect('page/activation/');
                    }
                }
            }else{
                $tag['errors'] = '';
                $tag['form_error'] = 'form_error';
            }
        }else{
            $tag['errors'] = '';
            $tag['form_error'] = '';
        }
	    $tag['title'] = "Employee's Portal";
        $this->common->display("page/portal.html", $tag);
	}

	function terminated(){
        $this->common->display("page/terminated.html", $tag);
	}
}
