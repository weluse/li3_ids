<?php
/**
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace li3_ids\extensions;

use lithium\storage\Session;

/**
 *
 *
 *
 */
class Analyze extends \lithium\core\Adaptable {

	/**
	 * Classes used by `Analyze`.
	 *
	 * @todo obsolete??
	 *
	 * @var array
	 */
	public static $_classes = array(
		'request' => 'lithium\action\Request',
		'logger' => 'lithium\analysis\Logger',
	);

	/**
	 * Placeholder for the Request Object
	 *
	 * @var array
	 */
	protected static $_requestInstance = NULL;

	/**
	 * Runs the Analyzer
	 *
	 * @param array $params
	 * @return true
	 */
	public static function run($params){
		//die(print_r(\func_get_args(),true));

		if(\array_key_exists('request', $params)){
			self::$_requestInstance = $params['request'];

			$request = array(
				'GET' => $params['request']->query,
				'POST' => $params['request']->data,
			);
		}


		$path = get_include_path();
		try{
			//awful code structure is following... :(

			//fetch settings from init/config
			$idsPath = \dirname(__FILE__) . '/../libraries/' . 'phpids/lib';

			if(!$path = \realpath($idsPath)){
				die('Path to IDS lib does not exists: ' . $idsPath);
			}else{
				$idsPath = $path;
			}

			set_include_path($idsPath);
			require_once 'IDS/Init.php';
			$init = \IDS_Init::init($idsPath . '/IDS/Config/Config.ini.php');
			$init->config['General']['base_path'] = $idsPath . '/IDS/';
			$init->config['General']['use_base_path'] = True;


			$ids        = new \IDS_Monitor($request, $init);
			$result     = $ids->run();

			//re set the include path
			set_include_path($path);

			if($result instanceof \IDS_Report){
			 static::react($result);
			}
		} catch (Exception $e){
			die($e->getMessage());
		}
		return True;
	}

	/**
	 * React
	 *
	 * @param object $result \IDS_Report
	 * @return	void
	 */
	protected static function react(\IDS_Report $result){

		$currentImpact = $result->getImpact();
		$config = self::config('default');
		
		$impact = Session::read('impact',array('name'=>'ids'));
		$impact += $currentImpact;
		if(!Session::write('impact', (string)$impact, array('name' => 'ids'))){
			throw new \Exception('Session write not possible!');
		}
		switch(TRUE) {
			case $impact >= $config['threshold']['kick']:
				//IP to Blacklist
				static::log($result,'kick',$impact);
				//die('Ihre IP wurde gesperrt!
				//Total impact:'.$impact.' vs '.$config['threshold']['kick']);
				break;
			case $impact >= $config['threshold']['warn']:
				//warn
				static::log($result,'warn',$impact);
				break;
			case $impact >= $config['threshold']['mail']:
				//mail
				static::log($result,'mail',$impact);
				break;
			case $impact >= $config['threshold']['log']:
				//log this
				static::log($result,'log',$impact);
				break;
			default:
				//nothing
				break;
		}
	}

	/**
	 * Logs an IDS_Report
	 *
	 * @param object $result \IDS_Report
	 * @param string $threshold
	 * @param integer $totalImpact
	 * @return boolean
	 */
	protected static function log(\IDS_Report $result,$threshold,$totalImpact){
		$request = self::$_requestInstance;
		$params = compact('result','threshold','totalImpact','request');

		$_classes = static::$_classes;

		return static::_filter(__FUNCTION__, $params, function($self, $params) use ($_classes) {
			$request = $params['request'];
			$result = $params['result'];
			$totalImpact = $params['totalImpact'];

			$config = $self::config('default');

			$threshold = $config['threshold'][$params['threshold']];
			$ip = $request->env('REMOTE_ADDR');

			$msg = $ip . ' Total Impact: ' . $totalImpact
			 . ' is raised by: ' . $result->getImpact()
			 . ' and higher than threshold: ' . $threshold;

			$_classes['logger']::write('warning',$msg,array('name'=>'ids'));
		});
	}
}

?>