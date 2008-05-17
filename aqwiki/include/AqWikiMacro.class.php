<?PHP
class AqWikiMacro {
	var $data = false;
	var $settings = false;

	function __construct($data, $settings){
		$this->data = $data;
		$this->settings = $settings;
		
		if (method_exists($this, 'init')){
			$this->init();			
		}
	}

}

?>
