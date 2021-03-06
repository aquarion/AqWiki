<?PHP

// $Id: comics.inc,v 1.5 2005/02/16 17:13:52 aquarion Exp $
class comics extends AqWikiMacro {
	function niceTime($time, $shortform = false){
	
	if ($time > time()){
		$since = $time - time();
	} else {
		$since = time() - $time;
	}

	// 60 // minute
	// 3600 = hour
	// 86400 = day
	// 604800 = week

	if ($shortform){
		$units = array ('s','m','hr','d','wk','yr');
	} else {
		$units = array ('second','minute','hour','day','week','year');
	}

	if ($since < 60){
		$date = $since;
		$unit = $units[0];

	} elseif ($since < 4000){
		$date = round($since/60);
		$unit = $units[1];

	} elseif ($since < 82000){
		$date = round($since/3600);
		$unit = $units[2];

	} elseif ($since < 603800){
		$date = round($since/86400);
		$unit = $units[3];
		$plus = " on ".date("jS M");

	} elseif ($since < 31440000){
		$date = round($since/604800);
		$unit = $units[4];

	} else {
		$date = round($since/(604800 * 52));
		#$date = " over a year";
		$unit = $units[5];
	}

	if ($date == 1 || $unit == ""){
		$date = $date." ".$unit;
	} else {
		$date = $date." ".$unit."s";
	}

	if (!$shortform){
		$date .= " ago".$plus;
	}

	#$date .= " (".$since.")";

	return $date;

	}

function today(){

	$dataSource = $this->data;


	$days = array("sun", "mon", "tue", "wed", "thur", "fri", "sat", "all");

	if (isset($_GET['day'])){
		$day = $_GET['day'];
	} else {
		$day = date("w");
	}


	$out = "Wk".date("W\/D")."\n\n";

$comic = parse_ini_str($dataSource->getContent("comicsData"), true);

#$out .=$data;

#if ($data){
#	eval($data);
#} else {
#	$data = "comicsData";
#}


if (is_array($comic)){
	$todays = array();
	foreach ($comic as $name => $strip){
		if ($_GET['day'] == 7){
			#if ($strip[$day]){
			#	$name .= "(*)";
			#}
			$todays[$name] =  "* \"".$name."\":".$strip["url"];
		} else {
			if ($strip[$days[$day]] == true){
				if ($strip["year"]){
				}
				if ((isset($strip["hiatus"]) && strtotime($strip["hiatus"]) > time())){
					$todays[$name] = "* ".$name."";# [<a href=\"".$strip["url"]."\">H</a>]\n";
					$todays[$name] .= " (For ".$this->nicetime(strtotime($strip["hiatus"]),true).")";
				} else {
					$todays[$name] =  "* \"".$name."\":".$strip["url"];
					$short .=  "\".\":".$strip["url"]." ";
				}
			}
		}
	}
	ksort($todays);
	$out .=implode("\n",$todays);
} else {
	$out .="((comicsData)) not valid";
}
//$out .="<ul>\n";

$out .="\n\nData from ((comicsData))";

$out .="\n\n Be ";
for($i = 0; $i <= 7; $i++){
	$out .="<a href=\"[[BASE]]/[[TITLE]]?day=".$i."\" title=\"".$days[$i]."\">".$days[$i][0]."</a> ";
}
$out .="\n".$short;
	return $out;
}

}

?>
