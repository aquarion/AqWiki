<?PHP

class ikariam extends AqWikiMacro {

	var $playerlistCache = false;

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

function getPlayerList(){
	
	if($this->playerlistCache !== false){
		return $this->playerlistCache;
	}
	
	$q = "select distinct user from ikariam_city";
	$res = $this->data->sql_as_array($q);
	$roster = array();
	foreach($res as $player){
		$roster[$player['user']] = $player['user'];
	}
	
	$this->playerlistCache = $roster;
	return $roster;
	
}

function dbPlayerList($params, $format){
	
	$q = "select distinct user from ikariam_city";
	
	$roster = $this->getPlayerList();
	
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
		
		
		if (strtolower($this->settings['me']) == strtolower($city->user)){
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
		
		if (strtolower($this->settings['me']) != strtolower($city->user)){
			panic("Ikariam Cities System", "You can only delete your own cities.","Nice try");
		}
		
			
		$city->delete();
	}
	
	if(!isset($this->settings['city'])){
		return;	
	}
	
	$city = new IkariamCity($this->data);
	$city->load($this->settings['city']);
	
		
	if (strtolower($this->settings['me']) != strtolower($city->user)){
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
		return;
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


function cultureSlots($params){
	
	$culture = new IkariamCulturalExchange($this->data, $this->settings);
	
	if(count($params) == 0){
		$user = $this->settings['user_page'];
	} else {
		$user = $params[0];
	}
	
	return $culture->cultureSlotsBar($user, (strtolower($user) == strtolower($this->settings['me'])));
}


function addNewTreaty($params){
	
	
	$culture = new IkariamCulturalExchange($this->data, $this->settings);
	
	if(count($params) == 0){
		$user = $this->settings['user_page'];
	} else {
		$user = $params[0];
	}
	
	return $culture->addNewTreaty($user);
}

function saveTreaty(){
	
		if(!isset($_POST['new_treaty_reciever'])){
			return ;
		}
		
		if (strtolower($this->settings['me']) != strtolower($this->settings['user_page'])){
			panic('Ikariam Treaties System', "You don't have permission to do that");
			return ;
		}
		
	$culture = new IkariamCulturalExchange($this->data, $this->settings);
	
	$res = $culture->saveTreaty();
	
	if(!$res){
		return sprintf('<div style="border: 1px solid red; color: red">Couldn\'t add treaty: %s</div>', $culture->error);
	}
	
}


function deleteTreaty(){

	if(!isset($this->settings['delete'])){
		return ;
	}
	
	$culture = new IkariamCulturalExchange($this->data, $this->settings);
	
	$res = $culture->deleteTreaty($this->settings['delete']);
	
	if($culture->error){
		return sprintf('<div style="border: 1px solid red; color: red">%s</div>', $culture->error);
	}
}

function showTreaties(){
	if(count($params) == 0){
		$user = $this->settings['user_page'];
	} else {
		$user = $params[0];
	}
	
	$culture = new IkariamCulturalExchange($this->data, $this->settings);
	$list = $culture->listTreaties($user);
	
	$out .= '';
	
	$sortedlist = array_keys($list);
	natcasesort($sortedlist);
	
	foreach($sortedlist as $username){
		$treaty = $list[$username];
		$out .= "* ".userLink($treaty['user'])." [ <a href=\"cultural?delete=".$treaty['id']."\">Delete Treaty</a> ]\n";
		
	}
	return $out;
}


function cultureGrid(){

	global $_EXTRAS;
	
	
	$q = "select * from ikariam_user order by user";
	$res = $this->data->sql_as_array($q);
	$slots = array();
	foreach($res as $player){
		$slots[$player['user']] = $player['culture_slots'];
	}

	$q = "select distinct user from ikariam_city order by user";
	
	$res = $this->data->sql_as_array($q);
	
	$roster = array();
	$treaties = array();
	
	foreach($res as $player){
		$roster[] = $player['user'];
	}
	
	
	$data = $this->data->sql_as_array('select * from ikariam_cultural order by initiator');
	
	foreach($data as $treaty){
		$treaties[$treaty['initiator']][] = $treaty['acceptor'];
		$treaties[$treaty['acceptor']][] = $treaty['initiator'];
	}
	
	$out = '		
	<div id="cultureGridBox"><table class="cultureGrid" dojoType="dojox.grid"><tr><th>User &<br/> (Free Slots)</th>';
	foreach($roster as $player){
		$out .= sprintf("<th class=\"horizontal\">%s</th>", implode(str_split($player), '<br/>'));
	}
	
	$out .= '</tr>';
	$odd = true;
	
	foreach($roster as $vertical){
		$treaty = $treaties[$vertical];
		
		$odd = !$odd;
		$even = $odd ? 'odd' : 'even';
		
		if (isset($slots[$vertical])){
			$freeslots = $slots[$vertical];
		} else {
			$freeslots = 0;
		}
		
		$out .= sprintf("<tr class=\"%s\"><th nowrap=\"nobr\">%s (%s)</th>", $even,  userLink($vertical, 'cultural'), $freeslots);
		
		
		foreach($roster as $horizontal){			
			$tick = '<img src="http://imperial.istic.net/static/icons/silk/tick.png" />';
			
			if ($horizontal == $vertical){
				$class = 'match';
			} else {
				$class = '';
			}
			
			$symbol = (in_array($horizontal,$treaty) ? $tick : '&nbsp;');
			
			$out .= sprintf("<td class=\"%s\">%s</td>", $class, $symbol);
		}
		$out .= "</tr>\n";
	}
	
	$out .= '</table></div>
		';
	
	$_EXTRAS['cultureGrid'] = $out;
	
	return '[[RAWVAR|cultureGrid]]';
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
			panic("Ikariam Cities System", "Cannot delete unsaved city.");
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



class IkariamCulturalExchange {
	/*
	create table ikariam_cultural (
			id int auto_increment, 
			initiator tinytext,
			acceptor  tinytext,
			 modified timestamp,
			 primary key(id)Pu	
			 	
	);
	
	create table ikariam_user (
			user varchar(255), 
			culture_slots tinyint,
			 modified timestamp,
			 primary key(user)
	);	
	*/
	
	protected $db_cxn;
	
	public $testFail;
	
	protected $id = false;
	var $settings;
	var $error;
	
	function get($var){
		return $this->$var;
	}
	
	function __construct(&$db_cxn = null, $settings = null){
		if (is_null($db_cxn)){
			panic('Ikariam Macro', 'DB not given', E_ERROR);	
		} elseif (!is_subclass_of($db_cxn, 'dataSource')){
			panic('Ikariam Macro', 'DB not a dataSource', get_class($db_cxn));	
		} else {
			$this->db_cxn = $db_cxn;
		}
		
		$this->settings = $settings;
	}
	
	
	function cultureSlotsBar($user, $writable){
				
		if(isset($_POST['edit_culture_slots']) && $writable){
			$q = sprintf('replace into ikariam_user (user, culture_slots) values ("%s", %d)', $user, $_POST['edit_culture_slots']);
			$res = $this->db_cxn->query($q);
		}
		
		$q = sprintf('select *, unix_timestamp(`modified`) as modified_epoch from ikariam_user where user = "%s"', $user);
		$res = $this->db_cxn->sql_as_array($q);
		
		if(count($res) == 0){
			$slots = 0;
			$mod = 'Never';
		} else {
			$slots = $res[0]['culture_slots'];
			$mod = $res[0]['modified'];
		}
		
		if(!$writable){
			return sprintf("%s has %d slots open. Last updated: %s", $user, $slots, $mod);
		}
		
		
		$out = <<<EOW
<aqWikiNoProcess>
	<form action="cultural" method="POST">
	<h2>Culture Slots</h2>
		
	How many open culture slots do you have?
		<div id="horizontalSlider" dojoType="dijit.form.HorizontalSlider"
   value="{$slots}" minimum="0" maximum="15" discreteValues="16"
   intermediateChanges="true"
   onChange="dojo.byId('edit_culture_slots').value = arguments[0]; dojo.byId('edit_culture_slots').isValid();"
   handleSrc="http://o.aolcdn.com/dojo/1.0.0/dijit/themes/tundra/images/preciseSliderThumb.png"
>
	
	<input 
			id="edit_culture_slots" 
			name="edit_culture_slots" 
			value="{$slots}"
			type="text" size="2"
			constraints="{min:0,max:150,places:0}"
			required= "true"
			/><input type="submit" value="Update" />
   </div>
   	
   	
	
	</form>
	

	
</aqWikiNoProcess>
			
EOW;
		
		return $out;
	}
	
	function deleteTreaty($id){
				
		$q = sprintf("select * from ikariam_cultural where id = %d", $id);
		$res = $this->db_cxn->sql_as_array($q);
		if(count($res) == 0){
			$this->error = "Cannot delete that treaty, it doesn't exist";
		}
		
		$res = $res[0];
		
		if($res['initiator'] != $this->settings['me'] && $res['acceptor'] != $this->settings['me'] ){
			$this->error = "Cannot delete that treaty, it isn't yours";
		}
		
		$q = sprintf("delete from ikariam_cultural where id = %d limit 1", $id);
		$this->db_cxn->query($q);
		return true;
	}
	
	function saveTreaty($user){
		
		if(!isset($_POST['new_treaty_reciever'])){
			return true;
		}
		
		if ($settings['me'] != $settings['user_page']){
			panic('Ikariam Treaties System', "You don't have permission to do that");
			return false;
		}
		
		if (strtolower($this->settings['me']) == strtolower($_POST['new_treaty_reciever'])) {
			$this->error = "Don't do that, you'll go blind.";
			return false;
		}
		
		
		$q = sprintf('select * from ikariam_cultural where (initiator = "%1$s" and acceptor = "%2$s") or (initiator = "%2$s" and acceptor = "%1$s")', $this->settings['user_page'], $_POST['new_treaty_reciever']);
			
		if (count($this->db_cxn->sql_as_array($q)) != 0){
			$this->error = "There is already a treaty registered between those people";
			return false;
		} else {
			$q = sprintf('insert into ikariam_cultural (initiator, acceptor) values ("%1$s", "%2$s")',$this->settings['user_page'], $_POST['new_treaty_reciever']);
			$this->db_cxn->query($q);
			return true;
		}
		
	}
	
	
	function addNewTreaty(){
				
		$settings = $this->settings;
		
		if (strtolower($settings['me']) != strtolower($settings['user_page']) ){
			
			return $settings['me'].' != '.$settings['user_page'];
		}
		
		$options = '';
		
		$q = "select distinct user from ikariam_city order by user";
		
		$res = $this->db_cxn->sql_as_array($q);
		foreach($res as $player){
			$options .= sprintf('<option value="%s">%s</option>'."\n", $player['user'], $player['user']);
		}

		
		
		$out = <<<EOW
		
<aqWikiNoProcess>
	<form action="cultural" method="POST">
	<h2>Add new Treaty</h2>
	I have <em>already</em> organised a treaty with 
	
	<select name="new_treaty_reciever"
			dojoType="dijit.form.ComboBox"
			autocomplete="true">
			$options
	</select>
	
	<input type="submit" value="Save" />
   	<p>
   		If your user isn't on the dropdown, type in their ikariam username or (if they're in the alliance) their wiki username. Only people who have cities in the Wiki's city database will appear in the dropdown. <b>They do not need to be in the drop-down to enter their name</b>.
   	</p>
	</form>
	

	
</aqWikiNoProcess>
			
EOW;

	return $out;
	}
	
	function listTreaties($user){
		$q = sprintf('select * from ikariam_cultural where (initiator = "%1$s" or acceptor = "%1$s")', $user);
		$raw_treaties = $this->db_cxn->sql_as_array($q);
				
		$treaties = array();
		foreach($raw_treaties as $treaty){
			if (strtolower($treaty['initiator']) == strtolower($user)){
				$treaty['user'] = $treaty['acceptor'];
			} else {
				$treaty['user'] = $treaty['initiator'];
			}
			$treaties[$treaty['user']] = $treaty;
		}
		
		ksort($treaties, SORT_STRING);
		return $treaties;
	}
}
?>
