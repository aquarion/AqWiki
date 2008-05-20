<?PHP

/*******************************************************************************

	AqWiki - Elements

*******************************************************************************
	
	(c) Nicholas 'Aquarion' Avenell 2004

	Released under the Artistic Licence, a copy of which is in docs/licence.txt
	or can be found at http://opensource.org/licenses/artistic-license.php

********************************************************************************

$Id: elements.inc.php,v 1.20 2006/10/10 09:32:47 aquarion Exp $



	$Log: elements.inc.php,v $
	Revision 1.20  2006/10/10 09:32:47  aquarion
	* Development 2006
	
	Revision 1.19  2005/02/16 17:13:36  aquarion
	* Database fixes
	* New Textile Library support
	* Developement resumes, yay
	
	Revision 1.18  2004/10/22 13:56:08  aquarion
	* Fixed Stuff
	
	Revision 1.17  2004/09/05 10:16:48  aquarion
	Moved versions and edit this page to templates
	
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

$_FILES['elements'] = '$Revision: 1.20 $';

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

	$row = 1;

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
			$row++;
			$weekstart=$ii+1;
		}
	}

	for ($i = $dow;$i <= 5;$i++) {
		$out .="<td>&nbsp;</td>";
	}

	if ($row < 6){
		$out .= "</tr>\n<tr><td colspan=\"7\">&nbsp;</td></tr>";
	}
	
	$out .="</tr></table>";
	return $out;
}


function page($content){
	global $_EXTRAS;
	global $_CONFIG;
	global $dataSource;

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

	$output = isset($_GET['output']) ? $_GET['output'] : "html";

	switch($output){

	case "rss":
		header("Content-Type: text/xml");
		$out = buildRSS($content);
		break;

	case "fragment":
		header("Content-Type: text/html");
		$out = process($content[2],$content[0]);
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
			$template = "<html>\n<head>\n<title>[[WIKI]] - [[TITLE]]</title>\n</head>\n<body>\n<h1>[[TITLE]]</h1>\n[[CONTENT]]\n<a href=\"[[URL]]?action=edit\">Edit This Page</a>\n[[VERSIONS]]<hr>\n[[AUTHOR]] @ [[DATE]]<br>You Are: [[USER]] Identified by [[AUTH]] [ <a href=\"[[URL]]?action=login\">Login</a> | <a href=\"[[URL]]?action=newUser\">New User</a> ]<br>Generated by [[AQWIKI]]</body>\n</html>";
		}
		
		
		
		$text = process($content[2],$content[0]);
	
		$out = $template;

		$out = preg_replace("/\[\[CONTENT\]\]/",$text, $out);
		$out = preg_replace("/\[\[AQWIKI\]\]/",$_EXTRAS['versionString'], $out);
		$out = preg_replace("/\[\[WIKI\]\]/",$content[0], $out);
		$out = preg_replace("/\[\[TITLE\]\]/",$content[1], $out);
		$out = preg_replace("/\[\[PAGE\]\]/",$_EXTRAS['argv'][1], $out);
		$out = preg_replace("/\[\[AUTHOR\]\]/", userLink($content[3]), $out);
		$out = preg_replace("/\[\[DATE\]\]/",$content[4], $out);
		$out = preg_replace("/\[\[URL\]\]/",$_CONFIG['host']."/".$base.$_EXTRAS['current'], $out);
		$out = preg_replace("/\[\[BASE\]\]/",$base, $out);
		$out = preg_replace("/\[\[USER\]\]/",userLink($_EXTRAS['me']), $out);
		$out = preg_replace("/\[\[AUTH\]\]/",$_EXTRAS['auth'], $out);
		$out = preg_replace("/\[\[NEARBY\]\]/",textile(menu($_EXTRAS['nearby'],"nearby")), $out);
		$out = preg_replace("/\[\[VERSIONCOUNT\]\]/",  count($dataSource->getPage($content[1]))  , $out);
		$out = preg_replace("/\[\[VERSIONS\]\]/",textile($_EXTRAS['versions']), $out);


		// Conditional includes
		preg_match_all("/\[\[IFEDIT\|(.*?)\|(.*?)\]\]/", $out, $matches);
		foreach($matches[0] as $index => $match){
			if (checkAuth("edit")){
				$out = preg_replace("#".preg_quote($match,"#")."#", stripslashes($matches[1][$index]),$out);
			} else {
				$out = preg_replace("#".preg_quote($match,"#")."#", stripslashes($matches[2][$index]),$out);
			}
			#$_EXTRAS[$matches[1][$index]] = $matches[2][$index];
		}
		preg_match_all("/\[\[IFEDIT\|(.*?)\]\]/", $out, $matches);
		foreach($matches[0] as $index => $match){
			$result = stripslashes($matches[1][$index]);
			if (checkAuth("edit")){
				$out = preg_replace("#".preg_quote($match,"#")."#",$result,$out);
			} else {
				$out = preg_replace("#".preg_quote($match,"#")."#","",$out);
			}
			#$_EXTRAS[$matches[1][$index]] = $matches[2][$index];
		}


		preg_match_all("/\[\[PLURAL\|(.*?)\]\]/", $out, $matches);
		foreach($matches[0] as $index => $match){
			if(intval($matches[1][$index]) != 1){
				$plural = 's';
			} else {
				$plural = '';
			}
			$out = preg_replace("#".preg_quote($match,"#")."#",$plural,$out);
			#$_EXTRAS[$matches[1][$index]] = $matches[2][$index];
		}

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

function userpage($wiki, $author){


	global $dataSource;
	global $_EXTRAS;
	global $_CONFIG;

	$_EXTRAS['user_page'] = $author;
	
	if ($_CONFIG['oneWiki']){
		$base = $_CONFIG['base'];
	} else {
		$base = $_CONFIG['base']."/".$dataSource->wiki;
	}
	
	
	$content = "h1. {$author}'s user page\n\n";
	
	$default = 'mypage';
	
	$author = strtolower($author);
	
	$menuItems = array();
	
	
	$menuItems['mypage'] = 'Own Page';	
	$menuItems['changes'] 	 = 'Recent Changes';
	
	if (strcasecmp($_EXTRAS['me'], $author) == 0){
		$menuItems['management'] = 'Manage Login';	
	} 
	
	if (isset($_EXTRAS['user_page_nav'])){
		$menuItems = array_merge($menuItems, $_EXTRAS['user_page_nav']);
		
	}
	
	$menu = '';
	
	$count = 0;
	foreach ($menuItems as $path => $title){
		$menu .= "<a href=\"$base/~{$author}/$path\">$title</a> ";
		$count++;
		if ($count != count($menuItems)){
			$menu .= ' | ';
		}
	}
	
	
	$content .= '[ '.$menu." ]\n\n";
	
	if(isset($_EXTRAS['argv'][2])){ // 0 - Wiki, 1- page, 2- subpage
		$submenu = 	$_EXTRAS['argv'][2];
	} else {
		$submenu = $default;
	}
	
	
	
	switch ($submenu){
		
		case "changes":
		
			$content .= "h2. Contributions:\n\n[[ALLBY|".$author."]]";
			break;
			
		case "management":
		
			$content .= "h2. Account Management\n\n...is not yet implemented, Stay tuned ;)";
			break;
			
		case "mypage":
		
			if ($dataSource->pageExists($author)){
				$default = 'mypage';
				$content .= "_This is a mirror of the wiki page (($author)), edits go there_\n\n[[INCLUDE|$author]]";
				$_EXTRAS['versions'] = 'Versioning information & editing on "original page":'.$base.'/'.$author;
			} else {
				$content .= "This user's page is empty, ((fix this|$author))?";
			}
			break;
			
		default:	
			$template_file = 'etc/user_page_'.$submenu.'.tpl';
			if (file_exists($template_file)){
				$content .= file_get_contents($template_file);	
			} else {
				$content .= 'Unknown user page function "'.$submenu.'"';
				
			}
	}
	
	
	
	$content = array(
		$wiki,
		"User ".$author,
		$content,
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
	
	$last = "";
	$count = 1;

	foreach ($recent as $index => $row){
		
		if ($recent[$index+1]['name'] == $row['name']){
			$count++;
		} else {
			$line = date("r",$row['created'])." - \"".$row['name']."\":$base/".$row['name']." ";
			
			if ($count != 1){
				$line .= "x".$count." changes ";
			}

			$line .="- \"".$row['creator']."\":$base/~".$row['creator'];
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
			$count = 1;
		}


		$last = $row['name'];

	}

	$_EXTRAS['data'] = $data;
	return $out;
}

function author($author){
	global $dataSource;
	global $_EXTRAS;
	global $_CONFIG;
	
	$author = $dataSource->searchAuthor($author);
	
	$count = 1;
	
	foreach ($author as $index => $row){
		if ($author[$index+1]['name'] == $row['name']){
			$count++;
		} else {
			$content .= "* ".date("r",$row['created'])." - ";
			if ($count != 1){
				$content .= $count." consecutive changes to ";
			}	
			$content .="<a href=\"".$base."/".$row['name']."\">".$row['name']."</a>\n";
			$count = 1;
		}
	}

	return $content;

}

function userLink($username, $subsection = false){
	global $_CONFIG;
	global $_EXTRAS;
	$base = $_CONFIG['base'];
	
	global $dataSource;


	
		
	if($user = $dataSource->userExists($username)){
		
		$link = $user['username'].($subsection ? '/'.$subsection : '');
	
		if (in_array($user['username'], $_EXTRAS['admins'])){
			$image = 'http://imperial.istic.net/static/icons/silk/user_suit.png';
		} else {
			$image = 'http://imperial.istic.net/static/icons/silk/user.png';
		}
	
		return '<nobr><a href="'.$base.'/~'.$link.'" class="userlink"><img src="'.$image.'" class="icon">'.$user['username'].'</a></nobr>';
	} else {
		
		$link = $username.($subsection ? '/'.$subsection : '');
		
		return '<nobr><a href="'.$base.'/~'.$link.'" class="userlink notauser"><img src="http://imperial.istic.net/static/icons/silk/user_gray.png" class="icon">'.$username.'</a></nobr>';
	}
}

?>
