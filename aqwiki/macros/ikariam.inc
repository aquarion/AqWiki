<?PHP

class ikariam extends AqWikiMacro {


	//var $data = false;
	//var $settings = false;
	
function init(){

	include('JSON.php');
	$this->json = new Services_JSON();
}

function coords($params){
	
	switch (count($params)){

	case 3:
		return $this->coordslink($params[0], $params[1], $params[2]);
		break;

	default:
		return $this->coordslink($params[0], $params[1]);
		break;
	}
}

function coordslink($x, $y, $title = false){
	if($title){
		return sprintf('"%3$s":http://s7.ikariam.org/index.php?view=worldmap_iso&islandX=%1$d&islandY=%2$d', $x, $y, $title);
	} else {
		return sprintf('"%1$d:%2$d":http://s7.ikariam.org/index.php?view=worldmap_iso&islandX=%1$d&islandY=%2$d', $x, $y);
	}
}


function playerList($params){
	return $this->dbPlayerList($params, "<big style=\"font-size: x-large;color: %s\">&bull;</big> ((~%s))\n");
}

function commaPlayerList($params){
	return $this->genPlayerList($params, "<span style=\"color: %s\">%s</span> | ");
}

function dbPlayerList($params, $format){
	
	$q = "select distinct user from ikariam_city";
	
	$res = $this->data->sql_as_array($q);
	$roster = array();
	foreach($res as $player){
		$roster[$player['user']] = $player['user'];
	}
	
	
	$translationTable = array(
		'Mort' => 'Khris',
		'tamara' => 'Troo',
		'__multi' => 'Multiple Towns',
	
	);
	
	$display = array();
	
	
	foreach($roster as $index => $color){
	
		$username = ucwords($index);
	
		if(isset($translationTable[$index])){
			$username = $translationTable[$index];
			unset($roster[$index]);
		}
	
		$display[$username] = sprintf($format, $color, $username);
	}
	
	ksort($display);	
	
	
	
	return implode(array_values($display), '').sprintf($format, $multi, 'Multiple Towns');
	
}

function genPlayerList($params, $format){

	$json = $this->json;
	$url = $params[0];



	$roster = $json->decode(file_get_contents($url));

	$roster = (array)$roster;

	$multi = $roster['__multi'];

	unset($roster['__multi']);
	

	$translationTable = array(
		'Mort' => 'Khris',
		'tamara' => 'Troo',
		'__multi' => 'Multiple Towns',
	
	);
	
	$display = array();
	
	
	foreach($roster as $index => $color){
	
		$username = ucwords($index);
	
		if(isset($translationTable[$index])){
			$username = $translationTable[$index];
			unset($roster[$index]);
		}
	
		$display[$username] = sprintf($format, $color, $username);
	}
	
	ksort($display);	
	
	
	
	return implode(array_values($display), '').sprintf($format, $multi, 'Multiple Towns');
	

}

function showcities($params){
	
	$owner = $params[0];
	
	$out = '';
	
	$city = new IkariamCity($this->data);
	$cities = $city->cities_by_owner($owner);
	foreach($cities as $city){
		$out .= '* '.$city->display();
		
		
		if ($this->settings['me'] == $city->user){
			$out .= ' [<a href="cities?edit='.$city->get('id').'">Edit</a> | <a href="cities?delete='.$city->get('id').'">Delete</a>]';
		}
		
		$out .= "\n";
	}
	
	return $out;
	
}

function savecities($params){
	
	
	if(isset($this->settings['delete'])){
		$city = new IkariamCity($this->data);
		$city->load($this->settings['delete']);
		
		if ($this->settings['me'] != $city->user){
			panic("Ikariam Cities System", "You can only delete your own cities.","Nice try");
		}
		
			
		$city->delete();
	}
	
	if(!isset($this->settings['city'])){
		return;	
	}
	
	$city = new IkariamCity($this->data);
	$city->load($this->settings['city']);
	
		
	if ($this->settings['me'] != $city->user){
		panic("Ikariam Cities System", "You can only edit your own cities.","Nice try");
	}
		
	unset($this->settings['city']);
	
	if ($city->validate()){
		return $city->save();
	} else {
		return '<pre>'.print_r($city->testFail, 1).'</pre>';
	}
	
	
		
}

function addcity(){
	
	if(!isset($this->settings['user_page']) || strcasecmp($this->settings['user_page'], $this->settings['me']) != 0){
		return '';
	}
	
	
	$city = new IkariamCity($this->data);
	if(isset($this->settings['edit'])){
		$city->load($this->settings['edit']);
	}
	if(isset($this->settings['city'])){
		$city->load($this->settings['city']);
	}
	
	$city->user = $this->settings['me'];
		
	return $city->form('cities');
}

} // End Ikariam Class


class IkariamCity {
	/*
	create table ikariam_city (
			id int auto_increment, 
			user varchar not null, 
			coordinates varchar(5),
			resource tinyint, 
			level tinyint,
			created datetime,
			 modified timestamp);	
	*/
	
	const UNKNOWN = 0;
	const CRYSTAL = 1;
	const WINE    = 2;
	const MARBLE  = 3;
	const SULPHUR = 4;
	
	protected $db_cxn;
	
	public $testFail;
	
	protected $id = false;
	var $user;
	var $name;
	var $x;
	var $y;
	var $resource;
	var $level = 1;
	var $created_epoch;
	var $modified_epoch;
	
	function get($var){
		return $this->$var;
	}
	
	function __construct(&$db_cxn = null){
		if (is_null($db_cxn)){
			panic('Ikariam Macro', 'DB not given', E_ERROR);	
		} elseif (!is_subclass_of($db_cxn, 'dataSource')){
			panic('Ikariam Macro', 'DB not a dataSource', get_class($db_cxn));	
		} else {
			$this->db_cxn = $db_cxn;
		}
	}
	
	function load($data){
		if(is_array($data)){
			foreach($data as $key => $value){
				$this->$key = $value;
			}
		} elseif(is_numeric($data)) {
			$this->load_from_id($data);
		}
	}
	
	function load_from_id($id){
		$sql = sprintf('select *, unix_timestamp(created) as created_epoch,  unix_timestamp(created) as created_epoch from ikariam_city where id = %d', $id);
		
		$result = $this->db_cxn->sql_as_array($sql);
		
		if(count($result) == 0){
			panic('Ikariam Macro', 'City ID not found');	
		}
		
		$result = $result[0];
		foreach($result as $key => $value){
			$this->$key = $value;
		}
	}
	
	function validate(){
		$this->testFail = array();
		
		foreach(array('user', 'name') as $var){
			if(empty($this->$var)){
				$this->testFail[$var] = $var.' cannot be empty';
			}
		}
		
		foreach(array('x', 'y', 'resource', 'level') as $var){
			if(!is_numeric($this->$var) || empty($this->$var)){
				$this->testFail[$var] = $var.' cannot be non-numeric ('.$this->$var.')';
			}
		}
		
		if (count($this->testFail) == 0){
			return true;
		} else {
			return false;
		}
	}
	
	function save($id){
		if ($this->validate()){
			if($this->id){
				$q = sprintf('update ikariam_city set name = "%s", user = "%s", x = %d, y = %d, resource=%d, level=%d where id = %d ', $this->name, $this->user, $this->x, $this->y,$this->resource, $this->level, $this->id);
				$this->db_cxn->query($q);
			} else {
				$q = sprintf('insert into ikariam_city (name, user, x, y, resource, level, created) values ("%s", "%s", %d, %d, %d, %d, NOW())', $this->name, $this->user, $this->x, $this->y, $this->resource, $this->level);
				
				$this->db_cxn->query($q);
			}
		} else {
			return false;
		}
		
	}
	
	function delete(){
		if(!$this->id){
			panic("Ikariam Cities System", "Cannot delete unsaved city, moron.");
		}
		$q = sprintf("delete from ikariam_city where id = %d", $this->id);
		$this->db_cxn->query($q);
	}
		
	function resourceImage($const){
			if($const == IkariamCity::UNKNOWN){
				return 'http://imperial.istic.net/static/icons/silk/help.png';
			}
		
			$resources = array(
					IkariamCity::CRYSTAL => 'Crystal',
					IkariamCity::WINE    => 'Wine',
					IkariamCity::MARBLE  => 'Marble',
					IkariamCity::SULPHUR => 'Sulphur'
			);
				
			return sprintf('/static/icons/%s_small.gif', $resources[$const]);
	}
	
	function display(){
		return sprintf("City of %s, owned by %s, produces <img src=\"%s\"> and is at %s", $this->name, $this->user, $this->resourceImage($this->resource), ikariam::coordslink($this->x, $this->y));
		
	}
	
	function form($action){
		
		$level = intval($this->level);
		
		
		if($this->id !== false){
			$title = "Edit City ".$this->name;
			$idElement = '<input type="hidden" name="city[id]" id="city[id]" value="'.$this->id.'" />';
		} else {
			$title = "Add a new City";
			$idElement = '';
		}
		
		$selected = ' SELECTED="SELECTED"';
		
		$sel_crystal = ($this->resource == IkariamCity::CRYSTAL  ? $selected : '');
		$sel_wine    = ($this->resource == IkariamCity::WINE     ? $selected : '');
		$sel_sulphur = ($this->resource == IkariamCity::SULPHUR  ? $selected : '');
		$sel_marble  = ($this->resource == IkariamCity::MARBLE   ? $selected : '');
		
		$form = <<<EOW
<aqWikiNoProcess>
<form method="POST" action="$action" id="Add City">
<h2>$title</h2>
<dl>
	$idElement
	<input type="hidden" name="city[user]" id="city[user]" value="{$this->user}" />
		
	<dt><label for="city[name]">City Name</label></dt>
	<dd><input type="text" name="city[name]" id="city[name]" value="{$this->name}" /></dd>
	<dt><label for="city[coords]">Coordinates</label></dt>
	<dd id="city[coords]">
		<input type="text" name="city[x]" id="city[x]" value="{$this->x}" maxlength="2" size="2"/>:<input type="text" name="city[y]" id="city[y]" value="{$this->y}" maxlength="2" size="2" />
	</dd>
		
	<dt><label for="city[resource]">Resource</label></dt>
	<dd>
	<select id="city[resource]" name="city[resource]">
		<option value="1" {$sel_crystal}>Crystal</option>
		<option value="2" {$sel_wine}>Wine</option>
		<option value="3" {$sel_marble}>Marble</option>
		<option value="4" {$sel_sulphur}>Sulphur</option>
	</select>
	</dd>
		
	<dt><label for="city[level]">Level</label></dt>
	<dd>
		<img id="cityImage" src="http://utop.istic.net/static/city/city_{$level}_red.gif" />
		<div id="horizontalSlider" dojoType="dijit.form.HorizontalSlider"
   value="{$this->level}" minimum="1" maximum="8" discreteValues="8"
   intermediateChanges="false"
   onChange="dojo.byId('city[level]').value = arguments[0];dojo.byId('cityImage').src = '/static/city/city_'+dojo.byId('city[level]').value+'_red.gif';"
   handleSrc="http://o.aolcdn.com/dojo/1.0.0/dijit/themes/tundra/images/preciseSliderThumb.png"
></div>
		
	</dd>
</dl>
	
<input id="city[level]" name="city[level]" value="{$this->level}" type="hidden" />
	
<input type="submit" value="Save Changes"/>



</form>
</aqWikiNoProcess>
EOW;

	return $form;
		
	}
	
	
	
	function cities_by_owner($owner){
		$sql = sprintf('select *, unix_timestamp(created) as created_epoch,  unix_timestamp(created) as created_epoch from ikariam_city where user = "%s"', $owner);
		$result = $this->db_cxn->sql_as_array($sql);
		$cities = array();
		foreach($result as $data){
			$city = new IkariamCity($this->db_cxn);
			$city->load($data);
			$cities[] = $city;
		}
		return $cities;
	}
}
?>
