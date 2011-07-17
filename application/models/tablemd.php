<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class tablemd extends CI_Model{
    var $accountid   = '';
    function __construct()
	{
		parent::__construct();
		session_start();
		$this->accountid = $_SESSION['acctid'];
	}

	function tax(){
		$sql = "SELECT 
					wtc.name,
					wtb.value,
					wtb.bracketid
				FROM wtax_code wtc
				LEFT JOIN wtax_basic wtb ON wtb.wtcodeid = wtc.wtcodeid";
		$query = $this->db->query( $sql );

		$sql_ = "SELECT * FROM wtax_bracket";
		$query_ = $this->db->query( $sql_ );

		$tax_value = array();
		foreach( $query->result() as $row ){
			if( !in_array($row->name,$tax_status) ){
				$tax_status[] = $row->name;
			}
			$tax_value[$row->name][$row->bracketid] = number_format($row->value, 2);
		}
		
		$tax_bracket = array();
		$heading[0] = array('0'=>'Bracket WTAX');
		$heading[1] = array('0'=>'Tax on Excess');
		foreach( $query_->result() as $row ){
			$heading[0][$row->wtbracketid] = number_format($row->value, 2);
			$heading[1][$row->wtbracketid] = '+'.($row->percent*100).'% over';
		}
		
		$this->table->add_row( $heading[0] );
		$this->table->add_row( $heading[1] );
		foreach($tax_status as $statuses){
			$data = array();
			$data[] = $statuses;
			foreach($tax_value[$statuses] as $item){
				$data[] = $item;
			}
			
			$this->table->add_row( $data );
			
		}
		$tag['content']  = '<div style="padding-top:6px;padding-bottom:6px;font-size:12pt;font-weight:bold;font-family:Trebuchet MS">TAX Table</div>';
		$tag['content'] .= '<div style="font-weight:bold">Tax on Excess = ([Bi-Monthly Rate] - [Bracket] x [%over]</div>';
		$tag['content'] .= '<div style="font-weight:bold">Withholding Tax = [Bracket WTax] + [Tax on Excess]</div>';
		$tag['content'] .= '<div>&nbsp;</div>';
		$tag['content'] .= $this->table->generate();
		$tag['content'] .= '<div style="font-weight:bold"><br/>Example:</div>';
		$tag['content'] .= '<div style="font-weight:bold">Tax on Excess = (3,500.00[Bi-Monthly Rate] - 3,125.00[Bracket]) x 0.05[%over] = 18.75</div>';
		$tag['content'] .= '<div style="font-weight:bold">Withholding Tax = (0.00[Bracket WTax] + 18.75[Tax on Excess]) = 18.75</div>';

		return $tag['content'];
	}

	function sss(){
		$sql = "SELECT * FROM sss";
		$query = $this->db->query( $sql );
		$max = $query->num_rows();
		$this->table->set_heading( 'Bi-Monthly', 'Employee Share',  'Employer Share');
		$i = 1;
		foreach($query->result() as $row){
			$to = (($i==$max)?'up':number_format(($row->base + 499), 2));
			$this->table->add_row( array(number_format($row->base, 2).' - '.$to, $row->mpremium, $row->mpremium) );
			$i++;
		}
		
		$tag['content']  = '<div style="padding-top:6px;padding-bottom:6px;font-size:12pt;font-weight:bold;font-family:Trebuchet MS">SSS Table</div>';
		$tag['content'] .= $this->table->generate();
		return $tag['content'];
	}

	function pagibig(){
		$sql = "SELECT * FROM pagibig";
		$query = $this->db->query( $sql );
		$this->table->set_heading( 'Bi-Monthly', 'Employee',  'Employer Share');
		
		foreach($query->result() as $i=>$row){
			$this->table->add_row( array($row->base.(($i==0)?' and below':' and up'), (($row->mpremium/2)*100).'%', (($row->mpremium/2)*100).'%') );
		}
		
		$tag['content']  = '<div style="padding-top:6px;padding-bottom:6px;font-size:12pt;font-weight:bold;font-family:Trebuchet MS">Pag-ibig Computation</div>';
		$tag['content'] .= $this->table->generate();
		return $tag['content'];
	}
	
	function philhealth(){
		$sql = "SELECT * FROM philhealth";
		$query = $this->db->query( $sql );
		$max = $query->num_rows();
		
		$this->table->set_heading( 'Bi-Monthly', 'Employee Share',  'Employer Share');
		$i = 1;
		foreach($query->result() as $row){
			$to = (($i==$max)?'up':number_format(($row->base + 999), 2));
			$this->table->add_row( array(number_format($row->base,2).' - '.$to, ($row->mpremium/2), ($row->mpremium/2)) );
			$i++;
		}
		
		$tag['content']  = '<div style="padding-top:6px;padding-bottom:6px;font-size:12pt;font-weight:bold;font-family:Trebuchet MS">Philheath Table</div>';
		$tag['content'] .= $this->table->generate();
		return $tag['content'];
	}
}
