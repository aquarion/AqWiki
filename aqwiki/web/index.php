<?php
require_once 'DB.php';
require_once "include/textile.inc";

$_CONFIG = array(
	'db' => "mysql://aquarion:halibutabrahms@localhost/aqwiki",
	'base' => ""
);

$url = preg_replace("/".preg_quote($_CONFIG['base'],"/")."/","",$HTTP_SERVER_VARS['REDIRECT_URL']);
$request = explode("/",$url);
array_shift($request);

$_EXTRAS = $_REQUEST;

// DB::connect will return a PEAR DB object on success
// or an PEAR DB Error object on error

$db = DB::connect($_CONFIG['db']);

function validate_user($username, $password){
	$q = "select * from users where username=\"".$username
		."\" and password = password(\"".$password."\")";
	$r = mysql_query($q);
	$rows = mysql_num_rows($r);
	if ($rows == 0){
		return false;
	} else {
		$row = mysql_fetch_assoc($r);
		$name = explode(" ", $row['realname']);
		$first = array_shift($name);
		$rest = implode($name, " ");
		
		return array(
			id => $row['id'], 
			firstname => $first, 
			lastname => $rest, 
			nickname => $username, 
			email => $row['email'],
			url => $row['url']
		);
	}
}

// With DB::isError you can differentiate between an error or
// a valid connection.
if (DB::isError($db)) {
    panic("MySQL", "Error",$db->getMessage());
}



if ($_GET['action'] == "login"){
	if (!$user = validate_user($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])){
	  // Bad or no username/password.
	  // Send HTTP 401 error to make the
	  // browser prompt the user.
	  header("WWW-Authenticate: " .
			 "Basic realm=\"Offical Logon: " .
			 "Enter your username and password " .
			 "for access.\"");
		header("HTTP/1.0 401 Unauthorized");
		 // Display message if user cancels dialog
	} else {
		setcookie ("me", $_SERVER['PHP_AUTH_USER'],time()+3600000);
		setcookie ("password", $_SERVER['PHP_AUTH_PW'],time()+3600000);
	}
}

if ($_COOKIE['me'] && $_COOKIE['password']){
	$user = validate_user($_COOKIE['me'],$_COOKIE['password']);
	if (!$user){
		header("location: ".$_SERVER['REQUEST_URI']."?action=login");
	}
	$_EXTRAS['id'] = $user['id'];
}


if ($_SERVER['PHP_AUTH_USER']){
	$_EXTRAS['me'] = $_SERVER['PHP_AUTH_USER'];
	$_EXTRAS['auth'] = "user";
	$_EXTRAS['id'] = $user['id'];
} elseif ($_COOKIE['me']){
	$_EXTRAS['me'] = $_COOKIE['me'];
	$_EXTRAS['auth'] = "cookie";
} else {
	$_EXTRAS['me'] = $_SERVER['REMOTE_ADDR'];
	$_EXTRAS['auth'] = "host";
}




if (preg_match("/^~(.*)$/",$request[1],$match)) {
	$content = user($request[0],$match[1]);
} elseif ($request[0] && $request[1]){
	// get Wiki Entry
	$content = wiki($request[0],$request[1]);
} elseif ($request[0]) {
	// get Wiki Front Page
	$content = wiki($request[0],"frontPage");
} else {
	$sql = "select wiki, count(page) as count from wikipage group by wiki";
	$result = $db->query($sql);
	// Always check that $result is not an error
	if (DB::isError($result)) {
		panic($result->getMessage());
	}
	while ($row = $result->fetchRow()) {
		$out .= "# <a href=\"".$row[0]."\">".$row[0]."</a>, ".$row[1]." pages\n";
	}
	$content = array(
		"page",
		"Index of Wikis",
		$out,
		"Aquarion (Admin)",
		date("r"));
}

if(file_exists("include/".$request[0].".rc.php")){
	require_once("include/".$request[0].".rc.php");
}

if($_EXTRAS['reqAuth']){
	$user = validate_user($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
	$auth = false;
	switch ($_EXTRAS['reqAuth']){
		case "user":
			if ($user['nickname'] == $_EXTRAS['reqUser']){
				$auth = true;
			}
			break;

		case "group":
			if (in_array($user['nickname'], $_EXTRAS['reqUsers'])){
				$auth = true;
			}
			break;

		case "register":
			if ($user){
				$auth = true;
			}
			break;
	}
	if ($auth){
		setcookie ("me", $_SERVER['PHP_AUTH_USER'],time()+3600000);
		setcookie ("password", $_SERVER['PHP_AUTH_PW'],time()+3600000);
	} else {
	  // Bad or no username/password.
	  // Send HTTP 401 error to make the
	  // browser prompt the user.
	  header("WWW-Authenticate: " .
			 "Basic realm=\"".$request[0]." Logon: " .
			 "Enter owner's username and password " .$user['nickname'] .
			 "for access.\"");
		header("HTTP/1.0 401 Unauthorized");
		die("Not authorised");
		 // Display message if user cancels dialog
	}
}

echo page($content);

#echo "<pre>";
#print_r($_EXTRAS);
#echo "</pre>";

#echo "<pre>".print_r_to_var($GLOBALS)."</pre>";

/*******************************************************\

					Functions

\*******************************************************/	

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
	$out .="<tr>";

	for ($i=0;$i <= $firstwas-1;$i++) {
		$out .="<td>&nbsp;</td>";
	}

	for ($i=1;$i<=$daysinmonth ; $i++) {

		$thedate = "$year-$month-$i";
		$dow = date("w",mktime(0,0,0,$month,$i,$year));		
		$check = 0;

		if ($dow == 0 || $dow == 6) {
			$style="archiveWeekend";
		} else {
			$style="archiveDay";
		}
		
		if ($i <= 10){
			$ii = "0$i";
		} else {
			$ii = $i;
		}

		if (isset($data[$ii])) {
				$out .="<td class=\"".$style."\"><a href=\"#".preg_replace("/(\W)/", "", $data[$ii])."\" class=\"bold\" title=\"".$data[$ii]."\">".$i."</a></td>";
		} else {
				$out .="<td class=\"".$style."\">".$i."</div></td>";
		}


		$out .="\n";

		unset($details);

		
		if ($dow == "6") {
			#$out .="<td><a href=\"index.php?from=$year-$month-$weekstart&amp;to=$year-$month-$ii\">W</a></td></tr>\n<tr>";
			$out .="</tr>\n<tr>";
			$weekstart=$ii+1;
		}
	}

	for ($i = $dow;$i <= 5;$i++) {
		$out .="<td>&nbsp;</td>";
	}
	$out .="</tr></table>";
	return $out;
}

function print_r_to_var($a) {
	ob_start();
	print_r($a);
	$b = ob_get_contents();
	ob_end_clean();
	return $b;
}

function panic($area, $error, $details){
	echo "<h2>$area</h2>$error<p>$details</p>";
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

function wiki($wiki, $article){
	global $db;
	global $_CONFIG;
	global $_EXTRAS;
	$base = $_CONFIG['base']."/".$wiki;
	$url = $base."/".$article;
	
	function getSQL($wiki, $article, $crit = false){
		$sql = "select "
			."wikipage.*, revision.*, users.username as creator, creatorname.username as origin, "
			."unix_timestamp(revision.created) as created "
			."from wikipage, revision "
			."left join users on revision.creator = users.id "
			."left join users as creatorname on creatorname.id = origin "
			."where wikipage.wiki = \"$wiki\" and wikipage.name = \"$article\" and wikipage.page = revision.page ";
		if ($crit){
			$sql .= $crit;
		}
		$sql .= " order by revision.created desc";
		
		return $sql;
	}
	$content = array(
		$wiki,
		$article,
		"",
		"aqWiki (Admin)",
		date("r"));

	switch($_GET['action']){
		case "viewrev":
			if(!$_GET['id']){
				die("Parameters incorrect");
			}
			$sql = getSQL($wiki, $article, "and revision.revision = ".$_GET['id']);
			$result = $db->query($sql);
			if (DB::isError($result)) {
				panic("database",$result->getMessage(), $sql);
			}
			if ($result->numRows() == 0){
				$content[2] = "That ID Is not related to this page";
			} else {
				$row = $result->fetchRow(DB_FETCHMODE_ASSOC);
				$content[2] = $row['content'];
				$content[3] = $row['creator'];
				$content[4] = date("r",$row['created']);

				$sql = getSQL($wiki, $article);
				$result = $db->query($sql);
				if (DB::isError($result)) {
					panic("database",$result->getMessage(), $sql);
				}
				
				$out = "\n\nh2. Versions:\n";
				$line = date("r",$row['created'])." - \"".$row['creator']."\":$base/~".$row['creator'];
					if ($row['comment']){
						$line .= " : ".$row['comment'];
					}
				$out .= "* ".$line." [ <a href=\"".$url."\" title=\"View this revision\">View</a> |"
						." <a href=\"".$url."?action=diff&amp;from=".$row['revision']."&amp;to=".$_GET['id']."\"\" title=\"View differences between this and the current revision\">Diff</a> ]\n";

				while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
					$line = date("r",$row['created'])." - \"".$row['creator']."\":$base/~".$row['creator'];
					if ($row['comment']){
						$line .= " : ".$row['comment'];
					}
					if ($_GET['id'] != $row['revision']){
						$out .= "* ".$line." [ <a href=\"".$url."?action=viewrev&amp;id=".$row['revision']."\" title=\"View this revision\">View</a> |"
						." <a href=\"".$url."?action=diff&amp;from=".$row['revision']."&amp;to=".$_GET['id']."\"\" title=\"View differences between this and the current revision\">Diff</a> ]\n";
					} else {
						$out .= "* ".$line." [Current]\n";
					}
				}
				$content[2] .= $out;
			}
			
			break;

		case "diff":
			
			if ($_GET['from']){
				$sql = getSQL($wiki, $article, " and revision = ".$_GET['from']);
				$result = $db->query($sql);
				$from = $result->fetchRow(DB_FETCHMODE_ASSOC);
			} else {
				die("From not supplied");
			}
			if ($_GET['to']){
				$sql = getSQL($wiki, $article, " and revision = ".$_GET['to']);
				$result = $db->query($sql);
				$to = $result->fetchRow(DB_FETCHMODE_ASSOC);
			} else {
				$sql = getSQL($wiki, $article);
				$result = $db->query($sql);
				$to = $result->fetchRow(DB_FETCHMODE_ASSOC);
			}

			$content[2] = "These are the differences between two versions of $article. Lines styled <span class=\"added\">like this</span> have been "
					."added to the entry, lines <span class=\"removed\">like this</span> have been removed.\n\n"
					."This is displaying the changes from ".date("Y-m-d h:m",$from['created'])." to ".date("Y-m-d h:m",$to['created']);

			if ($to['creator'] == $from['creator']){
				$author = $from['creator'];
			} else {
				$author = $from['creator'] . " &amp; ".$to['creator'];
			}
			$content[3] = $author;
			$content[4] = date("r",$to['created']);
			$_EXTRAS['diff'] = arr_diff(explode("\n", stripslashes($from['content'])),explode("\n", stripslashes($to['content'])));
			$content[2] .= "[[VAR|diff]]";

			break;
		
		case "edit":

			$sql = getSQL($wiki, $article);
			$result = $db->query($sql);

			$form = true;
			$text = false;
			switch ($_POST['submit']){
				case "Preview":
					$out = "<blockquote>".$_POST['content']."</blockquote>\n";
					$text = $_POST['content'];
					break;

				case "Spell Check":
					$checker = new Spellchecker;

					$text = strip_tags(textile($_POST['content']));
					$num_errors = $checker->check($text);

					if ($num_errors > 0) {
						$out .= "h3. Spell Check\n\n";
						$out .= "Items <span class=\"spellCorrect\">like this</span> could be errors, hover over for suggestions. Items <span class=\"spellNoSuggest\">like this</span> arn't in the dictionary, and the spell checker has no idea.\n\n";
						$errors = $checker->getErrors();
						$oldtext = $text;
						foreach ($errors as $word => $suggestions) {
							$title = trim(implode(', ', $suggestions));
							if ($title == ""){
								$span = '<|-|'.$title.'|-|>'.$word.'</-|>';
							} else {
								$span = '<|||'.$title.'|||>'.$word.'</||>';
							}
							# $text = str_replace($word, $span, $text);
							$text = preg_replace("/(\W|^)$word(\W|\$)/i", "$1$span$2", $text);
						}
						//if ($title == ""){
							$text = str_replace('<|-|', '<span class="spellNoSuggest"', $text);
							$text = str_replace('|-|>', '>', $text);
							$text = str_replace('</-|>', '</span>', $text);
						//} else {*/
							$text = str_replace('<|||', '<span class="spellCorrect" title="', $text);
							$text = str_replace('|||>', '">', $text);
							$text = str_replace('</||>', '</span>', $text);
						//}
					}
					$out = "<blockquote>".$text."</blockquote>\n";
					$text = $_POST['content'];
					break;

				case "Post":
					$post_res = $result;
					if ($result->numRows() == 0){
						$sql = "insert into wikipage (wiki, name, created, origin) values (\"".$wiki."\", \"".$article."\", NOW(), 1)";
						$post_res = $db->query($sql);
						$id = mysql_insert_id();
					} else {
						$row = $post_res->fetchRow(DB_FETCHMODE_ASSOC);
						$id = $row['page'];
					}

					$author = "\"".$_EXTRAS['me']."\"";

					$sql  = "insert into revision (content, comment, creator, page, created) values (\"".$_POST['content']."\", \"".htmlentities($_POST['comment'])."\", $author, $id, NOW())";
					$post_res = $db->query($sql);
					if (DB::isError($post_res)) {
						panic("database",$post_res->getMessage(), $sql);
					}
					$form = false;
			}


			if ($text){
				$_EXTRAS['textarea'] = $text;
			} elseif ($result->numRows() == 0){
				$_EXTRAS['textarea'] = "";
			} else {
				$row = $result->fetchRow(DB_FETCHMODE_ASSOC);
				$_EXTRAS['textarea'] = htmlentities(stripslashes($row['content']));
			}
			

			if ($form){
				$out .= "<form method=post action=\"".$_SERVER['REQUEST_URI']."\">\n";
				$out .= "<label for=\"creator\">Author</label>\n";
				$out .= $_EXTRAS['me']."<br>\n";
				$out .= "<label for=\"content\">Content</label>\n";
				$out .= "<textarea name=\"content\" id=\"content\" rows=\"30\" cols=\"72\">[[VAR|textarea]]</textarea>\n<br>\n";
				$out .= "<label for=\"comment\">Comment</label>\n";
				$out .= "<input type=\"text\" name=\"comment\" id=\"comment\" size=\"72\" value=\"".$_POST['comment']."\"><br>\n";
				$out .= "<input type=\"submit\" name=\"submit\" value=\"Post\">\n";
				$out .= "<input type=\"submit\" name=\"submit\" value=\"Preview\">\n";
				$out .= "<input type=\"submit\" name=\"submit\" value=\"Spell Check\">\n";
				$out .= "<input type=\"reset\" name=\"revert\" value=\"Revert to pre-editing\">\n";
				$out .= "</form>";
				$content[2] = $out;
				break;
			}
			

		default:
			$sql = getSQL($wiki, $article);
			$result = $db->query($sql);
			if (DB::isError($result)) {
				panic("database",$result->getMessage(), $sql);
			}
			if ($result->numRows() == 0){
				$content[2] = "This page doesn't exist yet, Would you like to create it?\n\n\"Go On Then\":".$_CONFIG['base']."/$wiki/$article?action=edit";
			} else {
				$_EXTRAS['nearby'] = nearby($wiki, $article);
				$row = $result->fetchRow(DB_FETCHMODE_ASSOC);
				$content[2] = $row['content']."\n\n [ \"Edit This Page\":$url?action=edit ]";
				$content[3] = $row['creator'];
				$content[4] = date("r",$row['created']);
				$out = "\n\nh2. Versions:\n";
				$line = date("r",$row['created'])." - \"".$row['creator']."\":$base/~".$row['creator'];
					if ($row['comment']){
						$line .= " : ".$row['comment'];
					}
				$out .= "* ".$line." [ Current ]\n";

				while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
					$line = date("r",$row['created'])." - \"".$row['creator']."\":$base/~".$row['creator'];
					if ($row['comment']){
						$line .= " : ".$row['comment'];
					}
					$out .= "* ".$line." [ <a href=\"".$url."?action=viewrev&amp;id=".$row['revision']."\" title=\"View this revision\">View</a> |"
					." <a href=\"".$url."?action=diff&amp;from=".$row['revision']."\"\" title=\"View differences between this and the current revision\">Diff</a> ]\n";
				}
				$content[2] .= $out;
			}
	}
	return $content;

}

function menu($items,$class="", $id=""){

	//Menu, Takes an array of items:
	// array("name" -> "Display Name", "link" -> "Display link" [, "title" => "Tooltip Text"]
	// and displays it as a list, with associated class. All the magic to put the brackets and
	// pipes in is now in CSS, as it should be. See either the stylesheets, or
	// <http://www.alistapart.com/stories/taminglists/> for details.
	unset($item);

	$total = count($items) -1;
	$i=0;
	$first = true;
	$out = "";

	if ($class != ""){$ulclass = " class=\"$class\"";}# else { $ulclass = " class=\"pipe\"";}
	if ($id != ""){$ulid = " id=\"$id\"";};
	foreach ($items as $item){

		if ($item['link']){
			$out .= "\t* \"".$item['name']."\":";
			$out .= $item['name']."\n";
		} else {
			$out .= "\t* ".$item['name']."\n";
		}
		$i++ ;
	}
	$out .= "\n";
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

	if (file_exists("include/".$content[0].".tpl")){
		$file = "include/".$content[0].".tpl";
		$template = implode("",file($file));
	} elseif (file_exists("include/page.tpl")){
		$template = implode("",file("include/page.tpl"));
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
	$out = preg_replace("/\[\[NEARBY\]\]/",menu($_EXTRAS['nearby'],"nearby"), $out);
	return $out;

}

function setVar($var,$value){
	global $_EXTRAS;
	return "SetVar";
}

function process($text, $wiki){
	global $db;
	global $_EXTRAS;
	global $_CONFIG;
	$base = $_CONFIG['base']."/".$wiki;

	function stripSpaces($text){
		return ereg_replace("/[:space:]/","",$text);
	}


	#$text = preg_replace("/\[\[SEARCH\|(.*?)\]\]/",searchFor($wiki,'\1'), $text);
	$text = preg_replace("/\[\[ALLBY\|(.*?)\]\]/",searchAuthor($wiki,'\1'), $text);
	$text = preg_replace("/\[\[RECENT\]\]/",viewRecent($wiki), $text);
	$text = preg_replace("/\[\[INDEX\]\]/",index($wiki), $text);




	preg_match_all("/\[\[SETVAR\|(.*?)\|(.*?)\]\]/", $text, $matches);
	foreach($matches[0] as $index => $match){
		$text = preg_replace("#".preg_quote($match,"#")."#","",$text);
		$_EXTRAS[$matches[1][$index]] = $matches[2][$index];
	}


	// [[CAL|year-mm-dd|Event]]
	preg_match_all("/\[\[CAL\|(....)\-(..)\-(..)\|(.*?)\]\]/", $text, $matches);
	$i = 0;

	$calendar = array();
	$caltext = "";

	foreach($matches[0] as $index => $match){
		$regex = "/".preg_quote($matches[0][$index],"/");
		$link = preg_replace("/(\W)/", "", $matches[4][$index]);
		$text = preg_replace($regex."/","<a name=\"".$link."\"></a>",$text);
		$calendar[$matches[1][$index]][$matches[2][$index]][$matches[3][$index]] = $matches[4][$index];
	}

	foreach($calendar as $year => $ydata){
		foreach($ydata as $month => $mdata){
			$caltext .= calendar($mdata, $month,$year);
		}
	}

	$links = array();	
	preg_match_all("/\(\((.*?)\|(.*?)\)\)/", $text, $matches);
	foreach($matches[1] as $index => $title){
		$link = preg_replace("/(\W)/", "", ucwords($matches[2][$index]));
		$links[] = array($matches[0][$index], $link, $title);
	}
	
	preg_match_all("/\(\((.*?)\)\)/",$text,$matches);
	foreach($matches[1] as $index => $title){
		if (! strpos($matches[1][$index], "|")){
			$link = preg_replace("/(\W)/", "", ucwords($title));
			$links[] = array($matches[0][$index],$link, $title);
		} 
	}

	foreach($links as $index => $matches){
		$replace = preg_quote($matches[0]);
		$stripped = $matches[1];
		$title = $matches[2];

		$sql = "select revision.page from revision, wikipage where revision.page = wikipage.page and wikipage.name = \"$stripped\"";
		$result = $db->query($sql);
		if (DB::isError($result)) {
			panic("database",$result->getMessage(), $sql);
		}
		if ($result->numRows() == 0){
			$link =  "%(uncreated)".$title."\"?(This hasn't been created yet. To do so, click here)\":".$base."/".$stripped."?action=edit%";	
		} else {
			$link =  "\"".$title."\":".$base."/".$stripped;
		}

		#$link =  "\"".$match."\":".$base."/".$stripped;
		$text = preg_replace("/".$replace."/",$link, $text);
	}

	$text = textile($text);
	#$text = ereg_replace("[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]\"/]", "<a href=\"\\0\">\\0</a>", $text);
	#$text = preg_replace("#<a href=\"<a href=\"(.*)\">(.*)\"</a>>(.*)</a>#","<a href=\"$1\">$3</a>",$text);
	$text = preg_replace("/\[\[CAL\]\]/","<div class=\"calendar\">".$caltext."</div>", $text);
	$text = preg_replace("/\[CC\](.*?)\[CC\]/","(($1))",$text);

	preg_match_all("/\[\[VAR\|(.*?)\]\]/",$text, $matches);
	foreach ($matches[0] as $index => $match){
		$var = $_EXTRAS[$matches[1][$index]];
		$text = preg_replace("#".preg_quote($match,"#")."#",$var, $text);
	}

	preg_match_all("/\[\[SEARCH\|(.*?)\]\]/", $text, $matches);
	foreach($matches[0] as $index => $match){
		$search = searchFor($matches[1][$index],$wiki);
		$text = preg_replace("#".preg_quote($match,"#")."#",$search,$text);
	}	
	$text = preg_replace("/\[CMD\](.*?)\[CMD\]/","[[$1]]",$text);


	return $text;
}

function viewRecent($wiki){
	global $_EXTRAS;
	global $_CONFIG;
	$base = $_CONFIG['base']."/".$wiki;
	global $db;

	$sql = "select "
			."wikipage.*, revision.*, users.username as creator, creatorname.username as origin, "
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

function searchFor($terms,$wiki){
	global $db;
	$sql = "SELECT wikipage.page, name, wikipage.created, max(revision.created) as revised, revision.revision"
	." FROM revision"
	." LEFT JOIN wikipage ON wikipage.page = revision.page"
	." WHERE content LIKE \"%".addslashes($terms)."%\" AND wiki = \"$wiki\""
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
	} else {
		$return[] = array('name' => "Nothing Found");
	}
	return menu($return);
}

function index($wiki){
	$alphabet = range('a', 'z');
	
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
				$now = $row['name'][0];
			}
			$return[$now][] = array('name' => $row['name'], 'link' => $row['name']);
		}
	} else {
		$return[][] = array('name' => "Nothing Found");
	}
	foreach ($return as $index => $letter){
		$menu .= " | \"".$index."\":#$index";
		$string .= "\n<a name=\"$index\"></a>\n\nh2. ".$index."\n\n";
		$string .= menu($letter);
	}
	return $menu." |<br>".$string;
}

function searchAuthor($wiki,$terms){
	global $db;
	global $_CONFIG;
	$sql = "select id from users where username = \"$terms\"";
	$result = $db->query($sql);
	if (DB::isError($result)) {
		panic("database",$result->getMessage(), $sql);
	}
	if ($result->numRows() != 0){
		$row = $result->fetchRow(DB_FETCHMODE_ASSOC);
		$author = "revision.creator = ".$row['id']." or revision.creator = \"".$terms."\"";
	} else {
		$author = "revision.creator = \"".$terms."\"";
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

/* Differences between two arrays, 
	http://www.holomind.de/phpnet/diff.src.php
	Daniel Unterberger: d.u.phpnet@holomind.de
*/
function arr_diff( $f1, $f2 ,$show_equal=0 )
{

	$c1=0;
	$c2=0;
	$max1=count( $f1 );
	$max2=count( $f2 );
	$outcount=0;
	$hit1="";
	$hit2="";

	while ( $c1<$max1 and $c2<$max2 and ($stop++)<1000 and $outcount<20 )
	{
		if ( trim( $f1[$c1]) == trim ( $f2[$c2])  )
		{
			$out.= ($show_equal==1) ?  formatline ( ($c1) , ($c2), "=", $f1[ $c1 ] ) : "";
			if ( $show_equal==1) { $outcount++; }
			$c1++;
			$c2++;
		}
		else
		{
			#find matching lines on left, or right later in array
			$b="";
			$s1=0;
			$s2=0;
			$found=0;
			$b1="";
			$b2="";
			$fstop=0;

			#fast search in on both sides for next match.
			while( $found==0 and ($c1+$s1<=$max1) and ($c2+$s2<=$max2) and $fstop++<10 )
			{

				#test left
				if ( trim( $f1[$c1+$s1] ) == trim( $f2[$c2])  )
				{
					$found=1;
					$s2=0;
					$c2--;
					$b=$b1;
				}
				#more
				else
				{
					if ( $hit1[ ($c1+$s1)."_".($c2) ]!=1)
					{
						$b1.= formatline( ($c1+$s1) , ($c2), "-", $f1[ $c1+$s1 ] );
						$hit1[ ($c1+$s1)."_".$c2 ]=1;
					}
				}



				#test right
				if ( trim (  $f1[$c1] )  == trim ( $f2[$c2+$s2])  )
				{
					$found=1;
					$s1=0;
					$c1--;
					$b=$b2;
				}
				else
				{
					if ( $hit2[ ($c1)."_".($c2+$s2) ]!=1)
					{
						$b2.= formatline ( ($c1) , ($c2+$s2), "+", $f2[ $c2+$s2 ] );
						$hit2[ ($c1)."_".($c2+$s2) ]=1;
					}

				 }

				#search in bigger distance
				$s1++;
				$s2++;
			}

			#add line as different on both arrays (no match found)
			if ( $found==0)
			{
				$b.= formatline ( ($c1) , ($c2), "-", $f1[ $c1 ] );
				$b.= formatline ( ($c1) , ($c2), "+", $f2[ $c2 ] );
			}
			$out.= $b;
			$outcount++; #

			$c1++;
			$c2++;

		} /*endif*/

	}/*endwhile*/

return $out;
}/*end func*/

/* Differences between two arrays, 
	http://www.holomind.de/phpnet/diff.src.php
	Daniel Unterberger: d.u.phpnet@holomind.de
*/
function formatline( $nr1, $nr2, $stat, &$value )  #change to $value if problems
{
	if ( trim( $value )=="" )
	{
		return "";
	}

	switch ($stat)
	{
		case "=":
			return "<div class=\"diff\">". $nr1. " : $nr2 : = ".htmlentities( $value )  ."</div>";
		break;

		case "+":
			return "<div class=\"diff added\">". $nr1. " : $nr2 : + ".htmlentities( $value )  ."</div>";
		break;

		case "-":
			return "<div class=\"diff removed\">". $nr1. " : $nr2 : - ".htmlentities( $value )  ."</div>";
		break;
	}

}
/* 
	 Spelchecker server code by Simon Willison, 
	 http://simon.incutio.com/archive/2003/03/18/#phpAndJavascriptSpellChecker 
*/


class SpellChecker {
    var $command;
    var $text;
    var $errors = array();
    function SpellChecker($command = 'ispell') {
        $this->command = $command;
    }
    function check($text) {
        // Spell checks text, returns int number of errors found
        $this->text = $text;
        // Create temporary file, write text to that file
        // Important: First, ensure every line starts with ^ to avoid special ispell commands
        // see http://savannah.gnu.org/download/aspell/manual/user/6_Writing.html
        $text = '^'.implode("\n^", explode("\n", $text));
        $tmp = tempnam('/tmp', 'spl');
        $fp = fopen($tmp, 'w');
        fwrite($fp, $text);
        fclose($fp);
        // Run the spell checking command, capture output
        $output = `{$this->command} -a < $tmp`;
        # print '<pre>'; print($output); print '</pre>';
        // Delete the temporary file
        unlink($tmp);
        // Parse output and populate errors array
        $num_errors = preg_match_all('|^& (\w+) \d+ \d+: (.*?)$|m', $output, $matches);
        // This regexp picks up words for which ispell made no suggestions (different pattern)
        $extra_errors = preg_match_all('|^# (\w+)( ).*$|m', $output, $matches2);
        // Merge the arrays
        for ($i = 0; $i < $extra_errors; $i++) {
            $matches[0][] = $matches2[0][$i];
            $matches[1][] = $matches2[1][$i];
            $matches[2][] = $matches2[2][$i];
        }
        $num_errors += $extra_errors;
        for ($i = 0; $i < $num_errors; $i++) {
            $word = $matches[1][$i];
            // Check that word has not already been found as an error
            if (in_array($word, array_keys($this->errors))) {
                continue;
            }
            $suggestions = explode(', ', $matches[2][$i]);
            $this->errors[$word] = $suggestions;
        }
        return $num_errors;
    }
    function getErrors() {
        return $this->errors;
        /* errors is an array with the following structure:
        Array
        (
            [speling] => Array
                (
                    [0] => spelling
                    [1] => spewing
                    [2] => spiling
                )
        
            [twwo] => Array
                (
                    [0] => two
                )
        )
        */
    }
}

// close conection
$db->disconnect();
?>

