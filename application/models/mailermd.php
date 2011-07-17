<?php
	//$this->email->cc('another@another-example.com');
	//$this->email->bcc('them@their-example.com');
class mailermd extends CI_Model{
	var $acctid   = '';
    var $ccompany = 0;
	function __construct(){
		parent::__construct();
		$this->acctid = $_SESSION['acctid'];
		$this->ccompany = $_SESSION['dcompany'];
	}
	
	function email($data = array()){
	
		$config['protocol'] = 'smtp';
		$config['smtp_host'] = 'smtp.1and1.com';
		$config['smtp_port'] = '25';
		$config['mailtype'] = 'html';
		
		//get config
		$sql = "SELECT * FROM emailer WHERE companyid={$this->ccompany}";
		$mailer = $this->db->query($sql)->row();
		
		$config['smtp_user'] = $mailer->email;
		$config['smtp_pass'] = $mailer->password;
		
		if(fsockopen($config['smtp_host'], $config['smtp_port'], $err_no, $err_str)){
			$this->load->library('email', $config);
			$this->email->set_newline("\r\n");
			$this->email->from($config['smtp_user'],'Payroll Officer');
			$this->email->to($data['email']);
			$this->email->subject($data['subject']);
			$this->email->message($data['message']);
			$this->email->send();
			return TRUE;
		}else{
			return FALSE;
		}
			
	}	
}
