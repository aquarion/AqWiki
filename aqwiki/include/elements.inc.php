<?PHP

/*******************************************************************************

	AqWiki - Elements

*******************************************************************************
	
	(c) Nicholas 'Aquarion' Avenell 2004

	Released under the Artistic Licence, a copy of which is in docs/licence.txt
	or can be found at http://opensource.org/licenses/artistic-license.php

********************************************************************************

$Id$



	$Log$
	Revision 1.16  2004/08/29 17:25:08  aquarion
	Install:
	   * Fixed various SQL statement errors (Appended semi-colons) (MP)
	   * aqwiki.ini.orig now refered to as such, rather than aqwiki.orig
	   - Removed  CHARSET=latin1;
	Config:
	   * 'base' needs preceeding slash
	Wiki:
	   + "source" output mode (elements.inc.php)
	   * Fixed add-user (mysql4.class.php)
	   + Created 'mysql' datasource (for versions <4) and moved
	   	relivant sections to it. We now support mysql4. W00t :-)
		(mysql4.class.php - which needs rearranging and possibly
		renaming)
	   * Made ((-)) notation support ((~Aquarion)) urls (Possibly should
	   	make this an ini-config option for those who don't want
		~user urls)
	   * After a sucessful posting, system now redirects you to the new
	   	entry, meaning that hitting "refresh" after you've submitted
		an entry doesn't make it submit it again.

	Revision 1.15  2004/08/14 11:09:42  aquarion
	+ Artistic Licence
	+ Actual Documentation (Shock)
	+ Config examples
	+ Install guide
	
	Revision 1.14  2004/08/12 19:53:23  aquarion
	* Fixed config directive defaults
	* Fixed absolute URIs on RSS feeds
	
	Revision 1.13  2004/08/12 19:37:53  aquarion
	+ RSS output
	+ Detailed RSS output for Recent
	* Slight redesign of c/datasource (recent now outputs an array) to cope with above
	* Fixed Recent to cope with oneWiki format
	+ added Host configuation directive
	
	

*******************************************************************************/

$_FILES['elements'] = '$Revision$';

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

	if ($_CONFIG['oneWiki']){
		$base = $_CONFIG['base'];
	} else {
		$base = $_CONFIG['base']."/".$_EXTRAS['wiki'];
	}

	/* $content is an array containing:
			
			[0] Name of Wiki template
			[1] Title of page
			[2] Content of page
			[3] Author of page
			[4] Date of modification */

	switch($_GET['output']){

	case "rss":
		header("Content-Type: text/xml");
		$out = buildRSS($content);
		break;

	case "source":
		header("Content-Type: text/plain");
		$out = $content[2];
		break;

	default:
		if (file_exists("etc/".$content[0].".tpl")){
			$file = "etc/".$content[0].".tpl";
			$template = implode("",file($file));
			debug("Using template ".$file);
		} elseif (file_exists("etc/default.tpl")){
			$template = implode("",file("etc/default.tpl"));
			debug("Using default template");
		} else {
			debug("No default template found, using basic");
			$template = "<html>\n<head>\n<title>[[WIKI]] - [[TITLE]]</title>\n</head>\n<body>\n<h1>[[TITLE]]</h1>\n[[CONTENT]]\n<hr>\n[[AUTHOR]] @ [[DATE]]<br>You Are: [[USER]] Identified by [[AUTH]] [ <a href=\"[[URL]]?action=login\">Login</a> | <a href=\"[[URL]]?action=newUser\">New User</a> ]<br>Generated by [[AQWIKI]]</body>\n</html>";
		}
		$text = process($content[2],$content[0]);

		$out = $template;
		$out = preg_replace("/\[\[CONTENT\]\]/",$text, $out);
		$out = preg_replace("/\[\[AQWIKI\]\]/",$_EXTRAS['versionString'], $out);
		$out = preg_replace("/\[\[WIKI\]\]/",$content[0], $out);
		$out = preg_replace("/\[\[TITLE\]\]/",$content[1], $out);
		$out = preg_replace("/\[\[AUTHOR\]\]/",$content[3], $out);
		$out = preg_replace("/\[\[DATE\]\]/",$content[4], $out);
		$out = preg_replace("/\[\[URL\]\]/",$_SERVER['REDIRECT_URL'], $out);
		$out = preg_replace("/\[\[BASE\]\]/",$base, $out);
		$out = preg_replace("/\[\[USER\]\]/",$_EXTRAS['me'], $out);
		$out = preg_replace("/\[\[AUTH\]\]/",$_EXTRAS['auth'], $out);
		$out = preg_replace("/\[\[NEARBY\]\]/",textile(menu($_EXTRAS['nearby'],"nearby")), $out);

		if ($_CONFIG['debug']){
			global $DEBUG;
			$out .= "<p>".implode("<br>\n",$DEBUG)."</p>";
		}

	
	}


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

	if ($_CONFIG['oneWiki']){
		$base = $_CONFIG['base'];
	} else {
		$base = $_CONFIG['base']."/".$dataSource->wiki;
	}
	

	$listOfPages = $dataSource->listOfPages();


	if (count($listOfPages) != 0){
		foreach($listOfPages as $row){
			if ($row['name'][0] != $now){
				$now = strtoupper($row['name'][0]);
			}
			$link = $base."/".$row['name'];
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

function recent($wiki){
	global $dataSource;
	global $_EXTRAS;
	global $_CONFIG;

	if ($_CONFIG['oneWiki']){
		$base = $_CONFIG['base'];
	} else {
		$base = $_CONFIG['base']."/".$dataSource->wiki;
	}
	
	$recent = $dataSource->viewRecent($wiki);
	foreach ($recent as $row){
		$line = date("r",$row['created'])." - \"".$row['name']."\":$base/".$row['name']." - \"".$row['creator']."\":$base/~".$row['creator'];
		if ($row['comment']){
			$line .= " : ".$row['comment'];
		}

		$out .= "* ".$line." [ <a href=\"".$base."/".$row['name']."?action=viewrev&amp;id=".$row['revision']."\" title=\"View this revision\">View</a> |"
		." <a href=\"".$base."/".$row['name']."?action=diff&amp;from=".$row['revision']."\"\" title=\"View differences between this and the newest revision\">Diff</a> ]\n";

		// Data for RSS feed
		// Data is of form array(url, title, description, date);
		$data[] = array(
			$base."/".$row['name']."?action=viewrev&amp;id=".$row['revision'],
			$row['name'],
			$row['comment'],
			$row['created']
		);

	}

	$_EXTRAS['data'] = $data;
	return $out;
}

?>