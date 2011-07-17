<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class account extends CI_Controller {
    var $sub_nav;
    var $acctid = 0;
    var $ccompany = 0;
	function __construct(){
		parent::__construct();
		$this->acctid = $_SESSION['acctid'];
		$this->ccompany = $_SESSION['dcompany'];		
	}

	function index(){}

	function login(){
        $this->load->library('form_validation');
        if(isset($_POST['login']) && $this->form_validation->run()==TRUE){
			$sql = "SELECT * FROM account WHERE email='{$_POST['username']}' AND password='{$_POST['password']}'";
			$account = $this->db->query($sql)->row();
			if($account->accountid != ''){
				$_SESSION['acctid'] = $account->accountid;
				$_SESSION['acctype'] = 1;
				//get default company
                $sql = "SELECT companyid FROM company WHERE accountid={$_SESSION['acctid']} AND `default`=1;";
                $_SESSION['dcompany'] = $this->db->query($sql)->row()->companyid;
                
				redirect('member');
			}else{
				return 'form_error';
			}
		}else{
			return 'form_error';
		}

		return '';
	}

	function logout(){
        $_SESSION['acctid'] = '';
        redirect('');
	}

	function setcompany($companyid){
        //get default company
        $sql = "SELECT companyid FROM company WHERE accountid={$this->acctid} AND companyid={$companyid}";
        if($this->db->query($sql)->row()->companyid)
            $_SESSION['dcompany'] = $this->db->query($sql)->row()->companyid;
        redirect('member');
	}
}
