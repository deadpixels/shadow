<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class temp2 extends CI_Controller {
    var $acctid = 0;
    var $ccompany = 0;
	function __construct(){
		parent::__construct();
		
		$this->acctid   = $_SESSION['acctid'];
		$this->ccompany = $_SESSION['dcompany'];
        $this->acctype  = $_SESSION['acctype'];

        $this->load->library('table');
		$tmpl = array (
                    'table_open'          => '<table border="0" cellpadding="4" cellspacing="0" width="100%" style="font-size: 12px">',
                    'row_start'           => '<tr class="tr_odd">',
                    'row_alt_start'       => '<tr class="tr_even">'
              );
        $this->table->set_template($tmpl); 
	}

	function index(){
        $this->common->display('temp2/temp2.html');
	}

}
