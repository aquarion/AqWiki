<?PHP
/*******************************************************************************
	AqWiki - Elements
********************************************************************************

	Page elements. Calendar, Indexs, recent files, searches etc.

	$Id$

	$log$

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

function nearby($wiki, $page){
	global $db;
	$sql = "SELECT wikipage.page, name, wikipage.created, max(revision.created) as revised, revision.revision"
	." FROM revision"
	." LEFT JOIN wikipage ON wikipage.page = revision.page"
	." WHERE content LIKE \"%((".$page."))%\" AND wiki = \"$wiki\""
	." GROUP BY wikipage.page";
	
	$result = $db->query($sql);
	if (DB::isError($result)) {
		panic("database",$result->getMessage(), $sql);
	}
	$return = array();
	if ($result->numRows() != 0){
		while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
			$return[] = array('name' => $row['name'], 'link' => $row['name']);
		}
	}
	return $return;
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
	} elseif (file_exists("etc/page.tpl")){
		$template = implode("",file("etc/page.tpl"));
	} else {
		$template = "<html>\n<head>\n<title>[[WIKI]] - [[TITLE]]</title>\n</head>\n<body>\n<h1>[[TITLE]]</h1>\n[[CONTENT]]\n<hr>\n[[AUTHOR]] @ [[DATE]]<br>You Are: [[USER]]<br><a href=\"[[URL]]?action=login\">login</a></body>\n</html>";
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
	$out = preg_replace("/\[\[NEARBY\]\]/",textile(menu($_EXTRAS['nearby'],"nearby")), $out);
	return $out;

}

function setVar($var,$value){
	global $_EXTRAS;
	return "SetVar";
}


function viewRecent($wiki){
	global $_EXTRAS;
	global $_CONFIG;
	$base = $_CONFIG['base']."/".$wiki;
	global $db;

	$sql = "select "
			."wikipage.*, revision.*, creatorname.username as origin, "
			."unix_timestamp(revision.created) as created "
			."from wikipage, revision "
			."left join users on revision.creator = users.id "
			."left join users as creatorname on creatorname.id = origin "
			."where wikipage.wiki = \"$wiki\" and wikipage.page = revision.page "
			."order by revision.created desc limit 50";

	$result = $db->query($sql);
	if (DB::isError($result)) {
		panic("database",$result->getMessage(), $sql);
	}
	while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
		$line = date("r",$row['created'])." - \"".$row['name']."\":$base/".$row['name']." - \"".$row['creator']."\":$base/~".$row['creator'];
		if ($row['comment']){
			$line .= " : ".$row['comment'];
		}
		$out .= "* ".$line." [ <a href=\"".$base."/".$row['name']."?action=viewrev&amp;id=".$row['revision']."\" title=\"View this revision\">View</a> |"
		." <a href=\"".$base."/".$row['name']."?action=diff&amp;from=".$row['revision']."\"\" title=\"View differences between this and the newest revision\">Diff</a> ]\n";
	}
	return $out;

}

function searchFor($wiki,$terms){
	$return = array();
	global $db;
	global $_EXTRAS;
	$sql = "SELECT wikipage.page, name, wikipage.created, max(revision.created) as revised, revision.revision"
	." FROM revision"
	." LEFT JOIN wikipage ON wikipage.page = revision.page"
	." WHERE content LIKE \"%".addslashes($terms)."%\" AND wiki = \"$wiki\""
	." GROUP BY wikipage.page";


	$result = $db->query($sql);
	if (DB::isError($result)) {
		panic("database",$result->getMessage(), $sql);
	}
	if ($result->numRows() != 0){
		while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
			if ($row['name'] != $_EXTRAS['current']){
				$return[] = array('name' => $row['name'], 'link' => $row['name']);
			}
		}
	} else {
		$return[] = array('name' => "Nothing Found for $terms");
	}
	return "h3. Search for $terms\n".menu($return);
}

function index($wiki){
	$alphabet = range('A', 'Z');
	global $_CONFIG;
	
	global $db;
	$sql = "SELECT wikipage.page, name, wikipage.created, max(revision.created) as revised, revision.revision"
	." FROM revision"
	." LEFT JOIN wikipage ON wikipage.page = revision.page"
	." WHERE wiki = \"".$wiki."\""
	." GROUP BY wikipage.page"
	." ORDER BY wikipage.name";


	$result = $db->query($sql);
	if (DB::isError($result)) {
		panic("database",$result->getMessage(), $sql);
	}
	$return = array();

	$now = "";

	if ($result->numRows() != 0){
		while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
			if ($row['name'][0] != $now){
				$now = strtoupper($row['name'][0]);
			}
			$link = $_CONFIG['base']."/".$wiki."/".$row['name'];
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

function searchAuthor($wiki,$terms){
	global $db;
	$line = "All items by $terms\n";
	global $_CONFIG;
	$sql = "select id from users where username = \"$terms\"";
	$result = $db->query($sql);
	if (DB::isError($result)) {
		panic("database",$result->getMessage(), $sql);
	}
	if ($result->numRows() != 0){
		$row = $result->fetchRow(DB_FETCHMODE_ASSOC);
		$author = "(revision.creator = ".$row['id']." or revision.creator = \"".$terms."\")";
	} else {
		$author = " revision.creator = \"".$terms."\"";
	}
	$sql = "select revision.*, unix_timestamp(revision.created) as created, wikipage.name, wikipage.origin from revision, wikipage where revision.page = wikipage.page and $author and wiki = \"$wiki\" order by created desc";
	$result = $db->query($sql);
	if (DB::isError($result)) {
		panic("database",$result->getMessage(), $sql);
	}
	while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
		$line .= "# ".date("r",$row['created'])." - <a href=\"".$_CONFIG['base']."/".$wiki."/".$row['name']."\">".$row['name']."</a>\n";
	}
	return $line;
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