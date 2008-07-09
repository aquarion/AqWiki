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

function coordsurl($x, $y, $title = false){
	return sprintf('http://s7.ikariam.org/index.php?view=worldmap_iso&islandX=%1$d&islandY=%2$d', $x, $y);
}


function playerList($params = false){
	return $this->dbPlayerList($params, "<big style=\"font-size: x-large;color: %s\">&bull;</big> ((~%s))\n");
}

function commaPlayerList($params = false){
	return $this->genPlayerList($params, "<span style=\"color: %s\">%s</span> | ");
}

function getPlayerList(){
	

	if($this->playerlistCache !== false){
		return $this->playerlistCache;
	}
	
	$q = "select distinct ikariam_city.user from ikariam_city, ikariam_user where ikariam_user.user = ikariam_city.user and ikariam_user.status = 0";
	//$q = "select distinct users.username as user from ikariam_city, users where ikariam_city.user = users.username";


	$res = $this->data->sql_as_array($q);
	$roster = array();
	foreach($res as $player){
		$roster[$player['user']] = $player['user'];
	}
	
	natcasesort($roster);

	$this->playerlistCache = $roster;
	return $roster;
	
}

function getRetiredUsers(){
	
	if($this->playerlistCache !== false){
		return $this->playerlistCache;
	}
	
	$q = "select distinct ikariam_city.user from ikariam_city, ikariam_user where ikariam_user.user = ikariam_city.user and ikariam_user.status = 5";
	//$q = "select distinct users.username as user from ikariam_city, users where ikariam_city.user = users.username";


	$res = $this->data->sql_as_array($q);
	$roster = array();
	foreach($res as $player){
		$roster[$player['user']] = $player['user'];
	}
	
	natcasesort($roster);

	$this->playerlistCache = $roster;
	return $roster;
	
}

function dbPlayerList($params, $format){
	
	$q = "select distinct user from ikariam_city";
	
	$roster = $this->getPlayerList();
	
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
	
	
	
	return implode(array_values($display), '');#.sprintf($format, $multi, 'Multiple Towns');
	
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
	
	
	
	return implode(array_values($display), '');#.sprintf($format, $multi, 'Multiple Towns');
	

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



function showAllCities($params = false ){
	
	global $_EXTRAS;
	$base = $_CONFIG['base'];
	
	$owner = $params[0];
	
	$out = '';
	
	$city = new IkariamCity($this->data);
	$cities = $city->all_cities();
	foreach($cities as $city){
		
		$out .= sprintf("|%s| !%s! |%s|((~%s))|", 		
		ikariam::coordslink($city->x, $city->y), $city->resourceImage($city->resource), $city->name, $city->user);
		
		
		
		/*if (strtolower($this->settings['me']) == strtolower($city->user)){
			$out .= ' | <a href="/'.$base.'/~'.$city->user.'/cities?edit='.$city->get('id').'">Edit</a> |';
		} else {
			$out .= '| |';
		}*/
		
		$out .= "\n";
	}
	
	
	
//	$_EXTRAS['cityList'] = $out;
	
	//return '[[RAWVAR|cityList]]';
	
	return $out."\n\n";
	
}


function retirePlayer($params, $format){

	if(count($params) == 0){
		$user = $this->settings['user_page'];
	} else {
		$user = $params[0];
	}
	
	
	if(isset($this->settings['retire'])){

		if (
			(strtolower($this->settings['me']) != strtolower($user)) 
			&& (!in_array($this->settings['me'], $this->settings['admins']))	
		){
			panic("Ikariam User System", "You can only  retire yourself.","Nice try");
		}
		
		$q = sprintf("replace into ikariam_user (status, user) values (5, '%s')", $user);
		$res = $this->data->query($q);

	}
	
	if(isset($this->settings['unretire'])){

		if (
			(strtolower($this->settings['me']) != strtolower($user)) 
			&& (!in_array($this->settings['me'], $this->settings['admins']))	
		){
			panic("Ikariam User System", "You can only unretire yourself.","Nice try");
		}
		
		$q = sprintf("replace into ikariam_user (status, user) values (0, '%s')", $user);
		$res = $this->data->query($q);

	}
	
	if (in_array($this->settings['me'], $this->settings['admins'])){
		$retire_link =   '<input type="submit" name="retire"   value="(Admin) Mark account Retired"><br/>';
		$unretire_link = '<input type="submit" name="unretire" value="(Admin) Mark account Unretired"><br/>';
	}
	if (strtolower($this->settings['me']) == ($user)){
		$retire_link =   '<input type="submit" name="retire"   name="retire" value="Retire my account"><br/>';
		$unretire_link = '<input type="submit" name="unretire" name="retire" value="Recover my account"><br/>';
	}
	
	$q = sprintf("select status from ikariam_user where user = '%s'", $user );

	$res = $this->data->sql_as_array($q );

	if ($res[0]['status'] == 0){

	$form = '
	<form action="cultural" method="post" class="shiny" style="width: 250; float: right">
		<h2>Retire Account</h2>

		<p>This is currently an active account. Once your account has been marked as inactive and then eventually purged from Ikariam, you (or an admin) should retire your account here to remove it from the map and treaties lists</p>

		'.$retire_link.' 
	</form>
	';

	} elseif ($res[0]['status'] == 5){

	$form = '
	<form action="cultural" method="post" class="shiny" style="width: 250; float: right">
		<h2>Recover Account</h2>

		<p>This is currently an inactive account. If you have now got an account on Eta again, you should recover it.</p>

		'.$unretire_link.' 
	</form>
	';

	} 

	global $_EXTRAS;
	$_EXTRAS['retire'] = $form;
	
	return '[[RAWVAR|retire]]';
}


function savecities($params = false){
	
	
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
		
		$userline = '';
		
		switch ($treaty['user_status']){
			
			case NULL:
				$userline = '<img src="http://imperial.istic.net/static/icons/silk/user_red.png"/>'.$treaty['user']." (Non Alliance)";
				break;
				
			case 5:
				$userline = '<strike>'.userLink($treaty['user'])."</strike> (Retired)";	
				break;
			
			case 0:
				$userline = userLink($treaty['user']);	
				break;
				
		}
		
		$out .= "# ".$userline." [ <a href=\"cultural?delete=".$treaty['id']."\">Delete Treaty</a> ]\n";
		
	}
	return $out;
}

function listCultureSlots($params = false){
	
	global $_EXTRAS;
	
	$q = "select * from ikariam_user where culture_slots > 0 and user != \"".$_EXTRAS['me']."\" order by modified desc";
	$res = $this->data->sql_as_array($q);
	
	$culture = new IkariamCulturalExchange($this->data, $this->settings);
	$list = $culture->listTreaties($_EXTRAS['me']);
	
	$out = '';
	foreach ($res as $row){
		$out .= sprintf("* %s - %s slot%s (Updated %s) ", userlink($row['user']),  $row['culture_slots'], $row['culture_slots'] == 1 ? '' : 's', $row['modified']);
				
		if(isset($list[$row['user']])){
			$out .= " (You have an exchange with this person)";			
		}
		
		$out .= "\n";
	}
	
	return $out;
	
}

function cultureGrid(){

	global $_EXTRAS;
	
	if (!isset($_EXTRAS['included_dojo'])){
		$_EXTRAS['included_dojo'] = true;
		$_EXTRAS['head'] .= '
		
		<style type="text/css">
			@import "http://o.aolcdn.com/dojo/1.0.0/dijit/themes/tundra/tundra.css";
		</style>
		
		<script type="text/javascript" src="http://o.aolcdn.com/dojo/1.0.0/dojo/dojo.xd.js"
			djConfig="parseOnLoad: true"></script>
		<script type="text/javascript">
		   dojo.require("dojo.parser");
		   dojo.require("dijit.form.Slider");
		   dojo.require("dijit.form.ComboBox");
		</script>
		';
	}
	
	$q = "select * from ikariam_user where status = 0 order by user";
	$res = $this->data->sql_as_array($q);
	$slots = array();
	foreach($res as $player){
		$slots[$player['user']] = $player['culture_slots'];
	}

	$q = "select distinct ikariam_city.user from ikariam_city, ikariam_user where ikariam_city.user = ikariam_user.user and ikariam_user.status = 0 order by user";
	
	$res = $this->data->sql_as_array($q);
	
	$roster = array();
	$treaties = array();
	
	foreach($res as $player){
		$roster[] = $player['user'];
		$treaties[strtolower($player['user'])] = array();
	}
	
	
	$data = $this->data->sql_as_array('select * from ikariam_cultural order by initiator');
	
	foreach($data as $treaty){
		$treaties[strtolower($treaty['initiator'])][] = strtolower($treaty['acceptor']);
		$treaties[strtolower($treaty['acceptor'])][]  = strtolower($treaty['initiator']);
	}
	
	$out = '		
	<div id="cultureGridBox"><table class="cultureGrid" dojoType="dojox.grid"><tr><th>User &<br/> (Free Slots)</th>';
	foreach($roster as $player){
		$out .= sprintf("<th class=\"horizontal\">%s</th>", implode(str_split($player), '<br/>'));
	}
	
	$out .= '<th>User</th></tr>';
	$odd = true;
	
	foreach($roster as $vertical){
		$treaty = $treaties[strtolower($vertical)];
		
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
			
			$symbol = (in_array(strtolower($horizontal),$treaty) ? $tick : '&nbsp;');
			
			$out .= sprintf("<td class=\"%s\">%s</td>", $class, $symbol);
		}
		
		$out .= sprintf("<th nowrap=\"nobr\">%s</th></tr>\n", userLink($vertical, 'cultural'));
	}
	
	
	$out .= '		
	<tr><th>User &<br/> (Free Slots)</th>';
	foreach($roster as $player){
		$out .= sprintf("<th class=\"horizontal\">%s</th>", implode(str_split($player), '<br/>'));
	}
	
	$out .= '<th>User</th></tr>';
	
	$out .= '</table></div>
		';
	
	$_EXTRAS['cultureGrid'] = $out;
	
	return '[[RAWVAR|cultureGrid]]';
}


function drawMap(){

$players = $this->getPlayerColours();


$out = <<<EOW



<script type="text/javascript">

var highlights = [false, false, false, false, false, false];


var IkariamMap = {

	attachHighlighting : function(){
		$$('.highlightTown').each(function(n){
			n.observe('click', IkariamMap.highlightPlayer.bindAsEventListener(n));
			n.addClassName('highlightPlayer');
			//console.debug(n.childNodes[0].data);
		});

	},



	highlightPlayer : function(n){
		highlight = false;
		value = false;
		index = 0;
		//playername = this.childNodes[0].data.toLowerCase();
		playername = this.attributes['player'].nodeValue;

		//console.dir(this);
			
		console.debug(playername);
		
		if (this.highlighted == true){
			highlights.each(function(value){
				if ( value == playername){
					highlight = index;
				}
				index ++;
			});
			
			
			console.debug("UnHighlighting "+playername);
			$$('.player'+playername)[0].removeClassName('highlightPlayer'+playername);
			highlights[highlight] = false;
			
			
			$$('a.town'+playername).each(function(town){
				town.removeClassName('highlightPlayer'+playername);
			});
			
			this.highlighted = false;
			
			return true;
			
		}


		console.debug("Highlighting "+playername);

		$$('a.town'+playername).each(function(town){
			console.debug("Highlighting "+town);
			//console.debug(town.src);
			//town.src = 'highlight-'+highlight+'.png';
			town.addClassName('highlightPlayer'+playername);
		});
		
		
		$$('.player'+playername)[0].addClassName('highlightPlayer'+playername);

		
		this.highlighted = true;
	
	
	}
		
}


Event.observe(window, 'load', function() {
	//console.debug('load'),
  IkariamMap.attachHighlighting()
});






</script>

<style type="text/css">

#map {
	height: 500px;
	width: 500px;
	background-image: url('/static/map/empty.png');
	color: #EEE;
	position: relative;
	z-index: 50;
}

.town {
	display: block;
	background: #EEEEEE;
	height: 1%;
	width:  1%;
	position: absolute;
}

#map a {
	color: #EEE;
	text-decoration: none;
	display: relative;
	z-index: 100;
}

#map a img {
	border: none;
}

span.highlightPlayer {
	color: #555;
	text-decoration: underline;
}

EOW;


/*

span.highlightPlayer1 {
	color: #FF0000;
}
a.highlightPlayer1 {
	background: #FF0000;
}
*/

foreach($players as $player => $colour){

	$player = preg_replace('/\W/', '', $player);

$out .= '

span.highlightPlayer'.$player.' {
	border: 1px solid red;
}
span.Player'.$player.' {
	color: #'.$colour[1].$colour[2].$colour[3].';
}
a.highlightPlayer'.$player.' {
	background: #'.$colour[1].$colour[2].$colour[3].';
}

';

}


$out .= <<<EOW
</style>
 
<div id='map'>
EOW;


$cities = $this->data->sql_as_array("select ikariam_city.*, users.username as user, concat(x,':',y) as coords from ikariam_city, users, ikariam_user where ikariam_user.user = ikariam_city.user and ikariam_city.user = users.username and ikariam_user.status = 0");

//for ($y = 0; $y < 100; $y++){
//	for ($x = 0; $x < 100; $x++){


foreach($cities as $cityData){
	$class = '';
	$title = '';
	
	$coords = $cityData['coords'];
	$x = $cityData['x'];
	$y = $cityData['y'];
	
	$city = new IkariamCity($this->data);
	$city->load($cityData);
	
	$class = 'town town'.preg_replace('/\W/', '', $city->user);
	

	$title = sprintf('Town of %s belonging to %s, Harvesting %s', $city->name, $city->user, $city->resourceText());
	
	$out .= '<a href="'.$this->coordsurl($x, $y).'" class="'.$class.'" title="'.$title.'" style="position: absolute; top: '.$y.'%; left: '.$x.'%;">';
	$out .= '</a>';
}

$out .= '</div>';

$userlist = $this->getPlayerList();
natcasesort($userlist);

$out .= '<p>Click a name to highlight their towns. Click a town to go to it in the game</p>';

$out .= '<div id="userlist">| ';

foreach($userlist as $user){
	if(!empty($user)){
		$pid = preg_replace('/\W/', '', $user);
		$out .= '<span class="highlightTown player'.$pid.'" player="'.$pid.'">'.$user.'</span> | ';
	}
}

$out .= '</div>';


	global $_EXTRAS;
	$_EXTRAS['ikariamMap'] = $out;
	
	return '[[RAWVAR|ikariamMap]]';

}


///////////////////////////////////////////////////////// Graphical Map

function graphicalMap($params = false){


	$cities = $this->data->sql_as_array("select *, concat(x,':',y) as coords from ikariam_city");

	
	$players = $this->getPlayerColours(true);


	/* Set up image */

	if (isset($_GET['size'])){
		$size = $_GET['size'];
	} else {
		$size = 600;
	}

	if (isset($_GET['level'])){
		$level = $_GET['level'];
	} else {
		$level = 0;
	}
	if (isset($_GET['text'])){
		$text = false;
	} else {
		$text = true;
	}



	$center = round($size/2);

	$myImage = imagecreatetruecolor ($size, $size);
	imagesavealpha($myImage, true);

	$trans_colour = imagecolorallocatealpha($myImage, 0, 0, 0, 127);
	imagefill($myImage, 0, 0, $trans_colour);
	


	$check1 = imagecolorallocatealpha($myImage, 225, 225, 225, 127);
	$check2 = imagecolorallocatealpha($myImage, 225, 225, 225, 124);


	Imageinterlace($myImage, 1);




	// Draw Grid

	$cell = $size/100;

	$x = 0;
	$y = 0;
	$odd = false;

	while ($x < $size){
		//ImageLine($myImage, $x, 0, $x, $size, $grey);
		//ImageLine($myImage, 0, $y, $size, $y, $grey);
		//ImageLine($myImage, $x, $y, $x, $y+$size);
		$y = 0;
		while ($y < $size){
			imagefilledrectangle  ($myImage  , $x  , $y  , $x+$cell-1  , $y+$cell-1  , $odd ? $check1 : $check2  );
			$y += $cell;
			$odd = !$odd;
		}
		$x+=$cell;
		$odd = !$odd;
	}

	$cell = $size/100;

	foreach($cities as $cityData){
		$class = '';
		$title = '';
		
		$coords = $cityData['coords'];
		$x = $cityData['x']-1;
		$y = $cityData['y']-1;

		$color = $players[strtolower($cityData['user'])];

		$blue = ImageColorAllocate($myImage, hexdec($color[1]), hexdec($color[2]), hexdec($color[3]));

		imagefill  ($myImage , $x*$cell  , $y*$cell  , $blue  );
		
	}

	header("Content-type: image/png");
	Imagepng($myImage);
	Imagedestroy($myImage);  
	return;

}

function getPlayerColours($forceCase = false){


	if (!file_exists('macros/colours.txt')){
		return 'Could not find colours file '.getcwd();
	}

	$colours = file('macros/colours.txt');

	$players = $this->getPlayerList();

	foreach($players as $player => $playerN){
		if ($forceCase){
			unset($players[$player]);
			$player = strtolower($player);
		}
		$players[$player] = explode("\t", array_shift($colours));
	}


	return $players;



}

function colouredPlayerList($params = false){

	$players = $this->getPlayerColours();

	foreach($players as $player => $colour){
		
		$output .= '<span style="color: #'.$colour[1].$colour[2].$colour[3].'" title="'.$colour[0].'">'.$player.'</span> | ';

	}

	global $_EXTRAS;

	$id = uniqid('box');

	$_EXTRAS[$id] = $output;

	return '| [[RAWVAR|'.$id.']]';
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
			return sprintf('/static/icons/%s_small.gif', IkariamCity::resourceText($const));
	}
	
	function resourceText($const = 0){
		
			if($const == IkariamCity::UNKNOWN){
				return 'Unknown';
			}
			$resources = array(
					IkariamCity::CRYSTAL => 'Crystal',
					IkariamCity::WINE    => 'Wine',
					IkariamCity::MARBLE  => 'Marble',
					IkariamCity::SULPHUR => 'Sulphur'
			);
				
			return sprintf($resources[$const]);
	}
	
	function display(){
		return sprintf("City of %s, owned by %s, produces <img src=\"%s\"> and is at %s", $this->name, $this->user, $this->resourceImage($this->resource), ikariam::coordslink($this->x, $this->y));
		
	}
	
	function form($action){
		
		$level = intval($this->level);
		
		global $_EXTRAS;		
		
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


		if (!isset($_EXTRAS['included_dojo'])){
			$_EXTRAS['included_dojo'] = true;
			$_EXTRAS['head'] .= '
			
			<style type="text/css">
				@import "http://o.aolcdn.com/dojo/1.0.0/dijit/themes/tundra/tundra.css";
			</style>
			
			<script type="text/javascript" src="http://o.aolcdn.com/dojo/1.0.0/dojo/dojo.xd.js"
				djConfig="parseOnLoad: true"></script>
			<script type="text/javascript">
			   dojo.require("dojo.parser");
			   dojo.require("dijit.form.Slider");
			   dojo.require("dijit.form.ComboBox");
			</script>
			';
		}
		
		$form = <<<EOW
<aqWikiNoProcess>
<form method="POST" class="shiny" action="$action" id="Add City">
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
	
	function all_cities($sort = 'ikariam_city.user'){
		$sql = sprintf('select *, unix_timestamp(created) as created_epoch,  unix_timestamp(created) as created_epoch from ikariam_city, ikariam_user where ikariam_user.user = ikariam_city.user and ikariam_user.status = 0 order by %s asc ',$sort);
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
			return sprintf("%s has %d slot%s open. Last updated: %s", $user, $slots, ($slots == 1 ? '' : 's'), $mod);
		}

		global $_EXTRAS;

		if (!isset($_EXTRAS['included_dojo'])){
			$_EXTRAS['included_dojo'] = true;
			$_EXTRAS['head'] .= '
			
			<style type="text/css">
				@import "http://o.aolcdn.com/dojo/1.0.0/dijit/themes/tundra/tundra.css";
			</style>
			
			<script type="text/javascript" src="http://o.aolcdn.com/dojo/1.0.0/dojo/dojo.xd.js"
				djConfig="parseOnLoad: true"></script>
			<script type="text/javascript">
			   dojo.require("dojo.parser");
			   dojo.require("dijit.form.Slider");
			   dojo.require("dijit.form.ComboBox");
			</script>
			';
		}
		
		
		$out = <<<EOW
<aqWikiNoProcess>
	<form action="cultural" method="POST" class="shiny">
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
			return;
		}
		
		$res = $res[0];
		
		$initiator = strtolower($res['initiator']);
		$acceptor = strtolower($res['acceptor']);
		$me = strtolower($this->settings['me']);
		
		if($initiator != $me && $acceptor != $me) {
			$this->error = "Cannot delete that treaty, it isn't yours";
			return;
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
		
		//, ikariam_user where ikariam_user.user = ikariam_city.user and ikariam_user.status = 0
		$q = sprintf('select * from ikariam_cultural, ikariam_user as ikariam_user_initiator, ikariam_user as ikariam_user_acceptor 
		where 
			(ikariam_user_initiator.user = initiator and ikariam_user_initiator.status = 0)
		and
			(ikariam_user_acceptor.user = acceptor and ikariam_user_acceptor.status = 0)
		and
			((initiator = "%1$s" and acceptor = "%2$s") or (initiator = "%2$s" and acceptor = "%1$s"))  ', $this->settings['user_page'], $_POST['new_treaty_reciever']);
			
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
			
			return '';
		}
		
		$options = '';
		
		$q = "select distinct ikariam_city.user from ikariam_city, ikariam_user where ikariam_user.user = ikariam_city.user and ikariam_user.status = 0 order by user";
		
		$res = $this->db_cxn->sql_as_array($q);
		foreach($res as $player){
			$options .= sprintf('<option value="%s">%s</option>'."\n", $player['user'], $player['user']);
		}


		global $_EXTRAS;
		if (!isset($_EXTRAS['included_dojo'])){
			$_EXTRAS['included_dojo'] = true;
			$_EXTRAS['head'] .= '
			
			<style type="text/css">
				@import "http://o.aolcdn.com/dojo/1.0.0/dijit/themes/tundra/tundra.css";
			</style>
			
			<script type="text/javascript" src="http://o.aolcdn.com/dojo/1.0.0/dojo/dojo.xd.js"
				djConfig="parseOnLoad: true"></script>
			<script type="text/javascript">
			   dojo.require("dojo.parser");
			   dojo.require("dijit.form.Slider");
			   dojo.require("dijit.form.ComboBox");
			</script>
			';
		}		
		
		$out = '
		
<aqWikiNoProcess>
	<form action="cultural" method="POST" class="shiny">
	<h2>Add new Treaty</h2>
	I have <em>already</em> organised a treaty with 
	
	<select name="new_treaty_reciever"
			dojoType="dijit.form.ComboBox"
			autocomplete="true">
			'.$options.'
	</select>
	
	<input type="submit" value="Save" />
   	<p>
   		If your user isn\'t on the dropdown, type in their ikariam username or (if they\'re in the alliance) their wiki username. Only people who have cities in the Wiki\'s city database will appear in the dropdown. <b>They do not need to be in the drop-down to enter their name</b>.
   	</p>
	</form>
	

	
</aqWikiNoProcess>
			
';

	return $out;
	}
	
	function listTreaties($user){
	
		/*
		$q = sprintf('select * from ikariam_cultural, ikariam_user as ikariam_user_initiator, ikariam_user as ikariam_user_acceptor 
		where 
			(ikariam_user_initiator.user = initiator and ikariam_user_initiator.status = 0)
		and
			(ikariam_user_acceptor.user = acceptor and ikariam_user_acceptor.status = 0)
		and
			(initiator = "%1$s" or acceptor = "%1$s")  ', $user);
			*/
			
			
		$q = sprintf('select ikariam_cultural.*, ikariam_user_initiator.status as initiator_status,  ikariam_user_acceptor.status as acceptor_status from ikariam_cultural
			
			left join ikariam_user as ikariam_user_initiator on ikariam_user_initiator.user = initiator
			left join ikariam_user as ikariam_user_acceptor on ikariam_user_acceptor.user = acceptor
		where 
			(initiator = "%1$s" or acceptor = "%1$s")  ', $user);
		

		//$q = sprintf('select * from ikariam_cultural where (initiator = "%1$s" or acceptor = "%1$s")', $user);
		$raw_treaties = $this->db_cxn->sql_as_array($q);
		
		$treaties = array();
		foreach($raw_treaties as $treaty){
			if (strtolower($treaty['initiator']) == strtolower($user)){
				$treaty['user'] = $treaty['acceptor'];
				if (is_null($treaty['acceptor_status'])){
					//$treaty['acceptor_status'] = NULL;
				}
				$treaty['user_status'] = $treaty['acceptor_status'];
			} else {
				$treaty['user'] = $treaty['initiator'];
				$treaty['user_status'] = $treaty['initiator_status'];
			}
			$treaties[$treaty['user']] = $treaty;
		}
		
		ksort($treaties, SORT_STRING);
		return $treaties;
	}
}

?>
