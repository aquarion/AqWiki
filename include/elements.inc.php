<?PHP

/*******************************************************************************

	AqWiki - Elements

*******************************************************************************
$Id$



	$Log$
	Revision 1.8  2004/06/28 16:40:11  aquarion
	Added some files that missed the last commit, fixed a couple of "oneWiki" bugs

	Revision 1.7  2004/06/25 15:07:13  aquarion
	* various fixes resulting from the abstraction of the data layer.
	
	Revision 1.6  2004/06/25 12:54:25  aquarion
	All change, apparently. All I've done is abstracted the data layer a bit, why every file's changed I'm not quite sure...
	



*******************************************************************************/


function calendar ($data, $month, $year) {
	$out = "";
	if ($month == "date"){
		$month = date(m);
	}
	if ($year == "date"){
		$year = date(Y);
	}


	$weekday = date("l",mktime(0,0,0,$month,1,$year));
	$firstwas = date("w",mktime(0,0,0,$month,1,$year));
	$daysinmonth = date("t",mktime(0,0,0,$month,1,$year));
	$weekstart=01;
	$nicedate = date("F Y",mktime(0,0,0,$month,1,$year));

	$out .="<table class=\"calendar\">\n";
	$out .="<tr><th colspan=\"7\">$nicedate</th></tr>\n";
	$out .="<tr><th>S</th><th>M</th><th>T</th><th>W</th><th>T</th><th>F</th><th>S</th></tr>\n";
	$week = date("W",strtotime("$year-$month-1"));
	if ($week == date("W")){
		$weekclass = " class=\"thisweek week".$week."\"";
	} else {
		$weekclass = " class=\"week".$week."\"";
	}
	$out .="<tr".$weekclass.">";

	for ($i=0;$i <= $firstwas-1;$i++) {
		$out .="<td>&nbsp;</td>";
	}

	for ($i=1;$i<=$daysinmonth ; $i++) {

		$thedate = "$year-$month-$i";
		$dow = date("w",mktime(0,0,0,$month,$i,$year));		
		$check = 0;

		#echo $year."-".$month."-".$i." - ".date("Y-n-j")."<br>";

		
		if ($dow == 0 || $dow == 6) {
			$style="archiveWeekend";
		} else {
			$style="archiveDay";
		}

		if ($year."-".$month."-".$i == date("Y-m-j")) {
			$style .= " today";
		}

		if ($i <= 10){
			$ii = "0$i";
		} else {
			$ii = $i;
		}

		if (isset($data[$ii])) {
				$out .="<td class=\"".$style."\"><a href=\"#".preg_replace("/(\W)/", "", $data[$ii])."\" title=\"".$data[$ii]."\">".$i."</a></td>";
		} else {
				$out .="<td class=\"".$style."\">".$i."</div></td>";
		}


		$out .="\n";

		unset($details);

		
		if ($dow == "6") {
			$week = date("W",strtotime("$year-$month-".($i+1)));
			if ($week == date("W")-1){
				$weekclass = " class=\"thisweek week".$week."\"";
			} else {
				$weekclass = " class=\"week".$week."\"";
			}
			#$out .="<td><a href=\"index.php?from=$year-$month-$weekstart&amp;to=$year-$month-$ii\">W</a></td></tr>\n<tr>";
			$out .="</tr>\n<tr".$weekclass.">";
			$weekstart=$ii+1;
		}
	}

	for ($i = $dow;$i <= 5;$i++) {
		$out .="<td>&nbsp;</td>";
	}
	$out .="</tr></table>";
	return $out;
}


function page($content){
	global $_EXTRAS;
	global $_CONFIG;
	$base = $_CONFIG['base']."/".$content[0];
	/* $content is an array containing:
			
			[0] Name of Wiki template
			[1] Title of page
			[2] Content of page
			[3] Author of page
			[4] Date of modification */

	if (file_exists("etc/".$content[0].".tpl")){
		$file = "etc/".$content[0].".tpl";
		$template = implode("",file($file));
	} elseif (file_exists("etc/default.tpl")){
		$template = implode("",file("etc/page.tpl"));
	} else {
		$template = "<html>\n<head>\n<title>[[WIKI]] - [[TITLE]]</title>\n</head>\n<body>\n<h1>[[TITLE]]</h1>\n[[CONTENT]]\n<hr>\n[[AUTHOR]] @ [[DATE]]<br>You Are: [[USER]] [ <a href=\"[[URL]]?action=login\">Login</a> | <a href=\"[[URL]]?action=newUser\">New User</a> ]</body>\n</html>";
	}


	$text = process($content[2],$content[0]);

	$out = $template;
	$out = preg_replace("/\[\[CONTENT\]\]/",$text, $out);
	$out = preg_replace("/\[\[WIKI\]\]/",$content[0], $out);
	$out = preg_replace("/\[\[TITLE\]\]/",$content[1], $out);
	$out = preg_replace("/\[\[AUTHOR\]\]/",$content[3], $out);
	$out = preg_replace("/\[\[DATE\]\]/",$content[4], $out);
	$out = preg_replace("/\[\[URL\]\]/",$_SERVER['REDIRECT_URL'], $out);
	$out = preg_replace("/\[\[BASE\]\]/",$base, $out);
	$out = preg_replace("/\[\[USER\]\]/",$_EXTRAS['me'], $out);
	$out = preg_replace("/\[\[AUTH\]\]/",$_EXTRAS['auth'], $out);
	$out = preg_replace("/\[\[NEARBY\]\]/",textile(menu($_EXTRAS['nearby'],"nearby")), $out);

	global $DEBUG;
	$out .= implode("<br>\n",$DEBUG);

	return $out;

}

function setVar($var,$value){
	global $_EXTRAS;
	return "SetVar";
}

function index(){

	global $dataSource;
	global $_CONFIG;

	$alphabet = range('A', 'Z');

	$return = array();
	

	$listOfPages = $dataSource->listOfPages();


	if (count($listOfPages) != 0){
		foreach($listOfPages as $row){
			if ($row['name'][0] != $now){
				$now = strtoupper($row['name'][0]);
			}
			$link = $_CONFIG['base']."/".$dataSource->wiki."/".$row['name'];
			$return[$now][] = array('name' => $row['name'], 'link' => $link);
		}
	} else {
		$return[][] = array('name' => "Nothing Found");
	}


	foreach ($alphabet as $letter){
		if ($return[$letter]){
			$index = $letter;
			$menu .= " | \"".$index."\":#$index";
			$string .= "\n<a name=\"".$index."\"></a>\n\nh2. ".$index."\n\n";
			$string .= menu($return[$letter]);
		} else {
			$menu .= " | ".$letter;
		}
	}

	/*foreach ($return as $index => $letter){
		$menu .= " | \"".$index."\":#$index";
		$string .= "\n<a name=\"$index\"></a>\n\nh2. ".$index."\n\n";
		$string .= menu($letter);
	}*/
	return $menu." |<br>".$string;
}

function user($wiki, $user){
	$content = array(
		$wiki,
		"User ".$user,
		"[[ALLBY|$user]]",
		#"[[SEARCH|[[VAR|goldenrod]]]]",
		"aqWiki",
		date("r"));
	
	return $content;	
}

?>