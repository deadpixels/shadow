<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class setting extends CI_Controller {
    var $sub_nav;
    var $acctid = 0;
    var $ccompany = 0;
	function __construct(){
		parent::__construct();
		$this->common->verify();
		$this->sub_nav = array(
		    'List'          => 'setting/',
            'Tax Table'     => 'setting/tax',
            'SSS Table'     => 'setting/sss',
            'Pag-ibig'      => 'setting/pagibig',
            'Philhealth'    => 'setting/philhealth',
            'Mailer'        => 'setting/mailer',
            'Company Detail'=> 'setting/company',
        );
        $this->load->library('table');
		$tmpl = array (
                    'table_open'          => '<table border="0" cellpadding="4" cellspacing="0" width="100%" style="font-size: 12px">',
                    'row_start'           => '<tr class="tr_odd">',
                    'row_alt_start'       => '<tr class="tr_even">'
              );
        $this->table->set_template($tmpl); 
        $this->acctid = $_SESSION['acctid'];
		$this->ccompany = $_SESSION['dcompany'];
	}

	function index(){
        $tag['__sub_nav__'] = $this->sub_nav;
        $tag['jscript']     = array('i18n/grid.locale-en', 'jquery.jqGrid.min', 'jquery-ui-custom.min');	    
        $tag['css']         = array('themes/ui.jqgrid', 'themes/ui.multiselect');
        
		$tag['setting'] = $this->listsetting(false);
		$this->common->display("member/setting/dashboard.html", $tag);
	}

	function listsetting($echo = true){
	    $this->load->library('xjqgrid');
        $sql = "SELECT name, label, value FROM settings WHERE companyid={$this->ccompany};";

        $this->xjqgrid->initGrid( $sql );
		$this->xjqgrid->setMethod('setUrl', "setting/listsetting/1");
		$this->xjqgrid->setProperty('table', 'settings');
        $this->xjqgrid->setMethod('setPrimaryKeyId', "settingsid");
        
		$this->xjqgrid->setColProperty("settingsid", 
            array(  "hidden"    => true,));
        $this->xjqgrid->setColProperty("name",  
            array(  "editable"  => false,
                    "label"     => "Name"));
        $this->xjqgrid->setColProperty("label", 
            array(  "editable"  => false,
                    "label"     => "Label"));
        $this->xjqgrid->setColProperty("value", 
            array(  "label"     => "Value",
                    "width"     => 80,
                    "editrules" => array(
                        "required"  => true,
                        "number"    => true,
                    )));
        $this->xjqgrid->grid->addCol(array( 
                "name"      => "actions", 
                "label"     => "!",
                "formatter" => "actions", 
                "editable"  => false, 
                "sortable"  => false, 
                "resizable" => false, 
                "fixed"     => true, 
                "width"     => 60,
                "formatoptions" => array(
                    "keys" => true,
                    "delbutton"  => false
                ) 
                ), "first"); 
        $this->xjqgrid->grid->navigator = true; 
        $this->xjqgrid->grid->setNavOptions('navigator', array(
            "excel" => false,
            "view"  => false,
            "del"   => false,
            "edit"  => false,
            "add"   => false,
            "search"=>false));
        $this->xjqgrid->grid->setGridOptions(array( 
                "autowidth"     => true,
                "height"        => 150,
                "hoverrows"     => true,            
                "rowNum"        => 15,
                "altRows"       => true,
                "rowList"       => array(15,30,100),
            ));
        
        if(!$echo)
            return $this->xjqgrid->renderGrid('#grid','#pager',true, null, null, true, true, $echo);
            
        $this->xjqgrid->renderGrid('#grid','#pager',true, null, null, true, true, $echo);
        $this->xjqgrid->close();
	}

	function tax(){
	    $tag['__sub_nav__'] = $this->sub_nav;
	    
	    $this->load->model("tablemd");
        $tag['table'] = $this->tablemd->tax();
        $this->common->display("member/setting/table.html", $tag);
	}

	function sss(){
	    $tag['__sub_nav__'] = $this->sub_nav;
	    
	    $this->load->model("tablemd");
        $tag['table'] = $this->tablemd->sss();
        $this->common->display("member/setting/table.html", $tag);
	}

	function pagibig(){
        $tag['__sub_nav__'] = $this->sub_nav;
	    
	    $this->load->model("tablemd");
        $tag['table'] = $this->tablemd->pagibig();
        $this->common->display("member/setting/table.html", $tag);
	}

	function philhealth(){
        $tag['__sub_nav__'] = $this->sub_nav;
	    
	    $this->load->model("tablemd");
        $tag['table'] = $this->tablemd->philhealth();
        $this->common->display("member/setting/table.html", $tag);
	}

	function mailer(){
	    $tag['__sub_nav__'] = $this->sub_nav;
		$this->load->library('form_validation');
		$this->form_validation->set_error_delimiters('<div class="error">', '</div>');
		if(isset($_POST['mailer']) && $this->form_validation->run() == TRUE){
			$sql = "UPDATE emailer SET email='{$_POST['email']}', 
					password='{$_POST['pass']}' WHERE emailerid={$_POST['emailerid']};";
			$this->db->query($sql);
			$tag['errors'] = "Updated Setting Successfully.";
		}else{
			$tag['errors'] = validation_errors();
		}
		$sql = "SELECT * FROM emailer WHERE companyid={$this->ccompany} LIMIT 1;";
		$item = $this->db->query($sql)->row();
		$tag['email'] = $item->email;
		$tag['pass'] = $item->password;
		$tag['emailerid'] = $item->emailerid;
		
		$this->common->display("member/setting/mailer.html", $tag);
	}

	function company(){
	    $this->load->helper('form');
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('<div class="error">', '</div>');
	    $tag['__sub_nav__'] = $this->sub_nav;
        if(isset($_POST['updatecompany']) && $this->form_validation->run('member/newcompany')){
            $_POST['default'] = ((!isset($_POST['default'])) ? 0 : $_POST['default']);

            if($_POST['default'] == 1){
                //remove all default values
                $sql = "UPDATE company SET `default`=0 WHERE accountid={$this->acctid};";
                $this->db->query( $sql );
            }
            
            $sql = "UPDATE company SET 
                        name='{$_POST['name']}',
                        shortname='{$_POST['shortname']}',
                        industryid={$_POST['industryid']},
                        address='{$_POST['address']}',
                        phone='{$_POST['phone']}',
                        email='{$_POST['email']}',
                        website='{$_POST['website']}',
                        company_size={$_POST['company_size']},
                        `default`={$_POST['default']} WHERE companyid={$this->ccompany};";
            $this->db->query($sql);
            
            $tag['errors'] = '<div class="error">Updated Company Detail.</div>';
        }else{
            $tag['errors'] = validation_errors();    
        }
        
        $sql = "SELECT * FROM company WHERE companyid={$this->ccompany};";
        $company = $this->db->query($sql)->row();

        $tag['name']            = $company->name;
        $tag['sshortname']      = $company->shortname;
        $tag['address']         = $company->address;
        $tag['phone']           = $company->phone;
        $tag['email']           = $company->email;
	    $tag['website']         = $company->website;
        $tag['default']         = (($company->default == 1)?'checked="true"':'');
	    $tag['referencecode']   = $company->refcode;
	    //industry
        $sql = "SELECT * FROM industry";
        $query = $this->db->query($sql);
        $industry = array(''=>'[----Select One----]');
        foreach($query->result() as $row){
            $industry[$row->industryid] = $row->name;
        }
        $tag['industry'] = form_dropdown('industryid', $industry, $company->industryid);

        //company size
        $sql = "SELECT * FROM company_size";
        $query = $this->db->query($sql);
        $c_size = array(''=>'[----Select One----]');
        foreach($query->result() as $row){
            $c_size[$row->companysizeid] = $row->label;
        }
        $tag['company_size'] = form_dropdown('company_size', $c_size, $company->company_size);
        $this->common->display("member/setting/editcompany.html", $tag);
	}

}
