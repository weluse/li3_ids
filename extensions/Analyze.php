<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace li3_ids\extensions;

Class Analyze extends \lithium\core\StaticObject {

	public static function run($params){
		//die(print_r(\func_get_args(),true));
		if(\array_key_exists('request', $params)){
			//die('22');
			$request = array(
				'GET' => $params['request']->query,
				'POST' => $params['request']->data,
			);
		}

		//die("hello ".print_r($request,true));
	}
}

?>