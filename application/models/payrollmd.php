<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class payrollmd extends  CI_Model {

	function __construct()
	{
		parent::__construct();
	}
	function index()
	{
		//do nothing
	}
	
	
	function rate_per_day( $amount = 0, $maxdays = 0){
		return ($amount/$maxdays);
	}
	
	function rate_per_hour( $amount = 0, $maxdays = 0, $maxhours = 0){
		return ($amount/$maxdays)/$maxhours;
	}
	
	function maxhours($maxdays=0, $maxhours=0){
		return ($maxdays*$maxhours)/2;
	}
	
	function timediff($timein=0, $timeout=0){
		$sec = strtotime($timeout)-strtotime($timein)+(strtotime($timeout)<strtotime($timein));
		//$sec += (5*3600);
		if($sec == 0){ 
			return 0; 
		}else{
			return date('H', $sec)+(date('i', $sec)/60); 
		}
	}
	
	function overtime($scheduleid = 0, $timein=0, $timeout=0){
		$sql = "SELECT * FROM schedule WHERE scheduleid={$scheduleid};";
		$sched = $this->db->query($sql)->row();
		
		$sched_timein = date('Y-m-d ', strtotime($timein))." ".$sched->timein;
		$sched_timeout = date('Y-m-d ', strtotime($timeout))." ".$sched->timeout;
		
		return $this->timediff($timein, $timeout)-$this->timediff($timein, $sched_timeout);
	}
	
	function undertime($scheduleid = 0, $timein=0, $timeout=0){
		$sql = "SELECT * FROM schedule WHERE scheduleid={$scheduleid};";
		$sched = $this->db->query($sql)->row();
		$sched_timein = date('Y-m-d', strtotime($timein))." ".$sched->timein;
		$sched_timeout = date('Y-m-d', strtotime($timeout))." ".$sched->timeout;
		
		return (($this->timediff($sched_timein, $timein)>0 && strtotime($sched_timein)<strtotime($timein))?$this->timediff($sched_timein, $timein):0) + 
			   (($this->timediff($timeout, $sched_timeout)>0 && strtotime($sched_timeout)>strtotime($timeout))?$this->timediff($timeout, $sched_timeout):0);
	}
	
	function nightdiff($empid = 0, $timein=0, $timeout=0, $payrollid=0){
		$sql 	= "SELECT * FROM basic WHERE empid={$empid};";
		$sched 	= $this->db->query($sql)->row();
		
		$merit_nightdiffin		= $this->common->merit( $empid, $payrollid, 'nightdiffin', true);
		$merit_nightdiffout		= $this->common->merit( $empid, $payrollid, 'nightdiffout', true);
		$merit_opentime			= $this->common->merit( $empid, $payrollid, 'opentime', true );
		
		$nightdiffin 	= (($merit_nightdiffin!='')?$merit_nightdiffin:$sched->nightdiffin );
		$nightdiffout 	= (($merit_nightdiffout!='')?$merit_nightdiffout:$sched->nightdiffout);
		$opentime 		= (($merit_opentime!='')?$merit_opentime:$sched->opentime);
		
		if((date('H',strtotime($nightdiffin)) == 0 && date('H',strtotime($nightdiffout))== 0) || $opentime == 1){ return 0; }
		
		$mid 	= $this->timediff($timein, $timeout);
		$left 	= (strtotime(date('H:i',strtotime($timein)))<strtotime($nightdiffin)?$this->timediff($timein, $nightdiffin):0);
		$right 	= (strtotime(date('H:i',strtotime($timeout)))>strtotime($nightdiffout)?$this->timediff($nightdiffout, $timeout):0);
		
		$total = $mid-$left-$right;
		$total = ($total > $sched->maxhours)?$sched->maxhours:$total;
		
		return ($total<0) ? 0 : $total;
	}
	
	function allowance_per_day($amount = 0, $maxdays = 0){
		return $this->rate_per_day( $amount, $maxdays);
	}
	
	function allowance_per_hour($amount = 0, $maxdays = 0, $maxhours = 0){
		return $this->rate_per_hour( $amount, $maxdays, $maxhours);
	}

	function getphilhealth($basic = 0){
		$basic =  $basic;
		$sql = "SELECT mpremium FROM philhealth WHERE base BETWEEN 0 AND {$basic} ORDER BY mpremium DESC LIMIT 1;";
		$return = round(($this->db->query($sql)->row()->mpremium/4),2);
		return $return;
	}
	
	function getsss($basic = 0){
		$sql = "SELECT mpremium FROM sss WHERE base BETWEEN 0 AND {$basic} ORDER BY mpremium DESC LIMIT 1;";
		$return = round(($this->db->query($sql)->row()->mpremium/2), 2);
		return $return;
	}

	function getpagibig($basic=0){
		$basic =  $basic;
		$sql = "SELECT mpremium FROM pagibig WHERE base BETWEEN 0 AND {$basic} ORDER BY mpremium DESC LIMIT 1;";
		$return = round(($basic*$this->db->query($sql)->row()->mpremium/2),2);
		return $return;
	}
}
