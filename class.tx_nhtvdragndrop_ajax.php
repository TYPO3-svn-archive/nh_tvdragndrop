<?php
require_once(t3lib_extMgm::extPath('templavoila') .
	'class.tx_templavoila_api.php');

class tx_nhtvdragndrop_ajax {
	private $apiObj;
	
	public function __construct() {
		$this->apiObj = new tx_templavoila_api();
	}
	
	public function moveRecord($params, &$ajaxObj) {
		$sourcePointer = 
			$this->apiObj->flexform_getPointerFromString(t3lib_div::_GP('source'));
		
		$destinationPointer = 
			$this->apiObj->flexform_getPointerFromString(t3lib_div::_GP('destination'));

		$this->apiObj->moveElement ($sourcePointer, $destinationPointer);		
	}
	
	public function unlinkRecord($params, &$ajaxObj) {
		$unlinkPointer =
			$this->apiObj->flexform_getPointerFromString(t3lib_div::_GP('unlink'));
		
		$this->apiObj->unlinkElement($unlinkPointer);
	}
}

?>