<?php
//set Anlalyzer Config!

use li3_ids\extensions\Analyze;
use lithium\analysis\Logger;

use lithium\storage\Session;
use lithium\storage\session\adapter\Php;

Session::config(array(
	'ids' => array('adapter' => new Php, 'filters' => array()),
));
//@todo build a threshhold-Level Logger-Level Mapper

Analyze::config(array(
	'default'=>array(
		'threshold' => array(
			'log'      => 3,
			'mail'     => 9,
			'warn'     => 27,
			'kick'     => 81,
			),

		'email' => array(
			'address1@what.ever',
			'address2@what.ever',
			),
		),
	));

Logger::config(array(
 	'default' => array('adapter' => 'Syslog'),
 	'ids' => array(
 		'adapter' => 'File',
 		'priority' => array('emergency', 'alert', 'critical', 'error','warning')
 	)
));

?>