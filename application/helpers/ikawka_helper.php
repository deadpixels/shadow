<?php

function align($string,$align="left"){
	if($align=='right' || $align=='r' || $align==2){
		//$style = 'style="padding-right:10px"';
		$align = 'align="right"';
	}elseif($align=='left' || $align=='l' || $align==1){
		//$style = 'style="padding-left:10px"';
		$align = 'align="left"';
	}else{
		$style = '';
		$align = 'align="center"';
	}
	return '<div '.$align.' >'.$string.'</div>';
}
