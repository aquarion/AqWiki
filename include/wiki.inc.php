<?PHP
/*******************************************************************************
	AqWiki - Wiki functions
********************************************************************************

	Wiki functions, process documents, get pages etc.

	$Id$

	$log$

*******************************************************************************/

function process($text, $wiki){
	global $db;
	global $_EXTRAS;
	global $_CONFIG;
	$base = $_CONFIG['base']."/".$wiki;

	function stripSpaces($text){
		return ereg_replace("/[:space:]/","",$text);
	}


	preg_match_all("/\[\[VAR\|(.*?)\]\]/",$text, $matches);
	foreach ($matches[0] as $index => $match){
		$var = $_EXTRAS[$matches[1][$index]];
		$text = preg_replace("#".preg_quote($match,"#")."#",$var, $text);
	}


	#$text = preg_replace("/\[\[SEARCH\|(.*?)\]\]/",searchFor($wiki,'\1'), $text);
	#$text = preg_replace("/\[\[ALLBY\|(.*?)\]\]/",searchAuthor($wiki,'\1'), $text);
	$text = preg_replace("/\[\[RECENT\]\]/",viewRecent($wiki), $text);
	$text = preg_replace("/\[\[INDEX\]\]/",index($wiki), $text);




	// Search for User
	preg_match_all("/\[\[ALLBY\|(.*?)\]\]/", $text, $matches);
	foreach($matches[0] as $index => $match){
		$result = searchAuthor($wiki,$matches[1][$index]);
		$text = preg_replace("#".preg_quote($match,"#")."#",$result,$text);
		#$_EXTRAS[$matches[1][$index]] = $matches[2][$index];
	}

	// Search for Arbitaty
	preg_match_all("/\[\[SEARCH\|(.*?)\]\]/", $text, $matches);
	foreach($matches[0] as $index => $match){
		$datum = $matches[1][$index];
		$result = searchFor($wiki,$datum);
		$text = preg_replace("#".preg_quote($match,"#")."#",$result,$text);
		#$_EXTRAS[$matches[1][$index]] = $matches[2][$index];
	}

	// Set Variables
	preg_match_all("/\[\[SETVAR\|(.*?)\|(.*?)\]\]/", $text, $matches);
	foreach($matches[0] as $index => $match){
		$text = preg_replace("#".preg_quote($match,"#")."#","",$text);
		$_EXTRAS[$matches[1][$index]] = $matches[2][$index];
	}

	// [[MACRO|macroname]]
	preg_match_all("/\[\[MACRO\|(.*?)\\]\]/", $text, $matches);
	foreach($matches[0] as $index => $match){
		#print_r($matches);

		if (file_exists("macros/".$matches[1][$index].".inc")){
			ob_start();
			include("macros/".$matches[1][$index].".inc");
			$return = ob_get_contents();
			ob_end_clean();
		} else {
			$return = "Macro ".$matches[1][$index]." not defined";
		}
		
		$text = preg_replace("#".preg_quote($matches[0][$index],"#")."#",$return,$text);
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
		#ksort($ydata);
		$months = array_keys($ydata);
		foreach(range(min($months), max($months))  as $month){
			if ($month < 10){
				$month = "0".$month;
			}
		#foreach($ydata as $month => $mdata){
			$mdata = $ydata[$month];
			$caltext .= calendar($mdata, $month,$year);
		}
	}

	$links = array();	
	/*preg_match_all("/\(\(([.|\|]*?)\)\)/", $text, $matches);
	foreach($matches[1] as $index => $title){
		$link = preg_replace("/(\W)/", "", ucwords($matches[2][$index]));
		$links[] = array($matches[0][$index], $link, $title);
	}*/
	
	preg_match_all("/\(\((.*?)\)\)/",$text,$matches);
	foreach($matches[1] as $index => $title){
		if (! strpos($matches[1][$index], "|")){
			$link = preg_replace("/(\W)/", "", $title);
			$links[] = array($matches[0][$index],$link, $title);
		} else {
			$bang = explode("|",$matches[1][$index]);
			$link = preg_replace("/(\W)/", "", $bang[1]);
			$links[] = array($matches[0][$index],$link, $bang[0]);
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
			$link =  "%(uncreated)".$title."\"?(Uncreated)\":".$base."/".$stripped."?action=edit%";	
		} else {
			$link =  "\"".$title."\":".$base."/".$stripped;
		}

		#$link =  "\"".$match."\":".$base."/".$stripped;
		$text = preg_replace("/(\W)".$replace."(\W)/","$1$link$2", $text);
	}

	$text = textile($text);
	#$text = ereg_replace("[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]\"/]", "<a href=\"\\0\">\\0</a>", $text);
	#$text = preg_replace("#<a href=\"<a href=\"(.*)\">(.*)\"</a>>(.*)</a>#","<a href=\"$1\">$3</a>",$text);
	$text = preg_replace("/\[\[CAL\]\]/","<div class=\"calendar\">".$caltext."</div>", $text);
	$text = preg_replace("/\[CC\](.*?)\[CC\]/","(($1))",$text);

	$text = preg_replace("/\[CMD\](.*?)\[CMD\]/","[[$1]]",$text);


	$text = preg_replace("/\[\[TEXTAREA\]\]/",$_EXTRAS['textarea'],$text);



	return $text;
}

function wiki($wiki, $article){
	global $db;
	global $_CONFIG;
	global $_EXTRAS;
	$base = $_CONFIG['base']."/".$wiki;
	$url = $base."/".$article;

	function getSQL($wiki, $article, $crit = false){
		$sql = "select "
			."wikipage.*, revision.*, creatorname.username as origin, "
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

		case "src":
			
			$_EXTRAS['textarea'] = htmlspecialchars(getContent($wiki, $article));
			$content[2] .= "<pre>[[TEXTAREA]]</pre>.\"Normal\":$article";

			break;
		
		case "edit":


			if($_EXTRAS['reqEdit']){
				doAuth($_EXTRAS['reqEdit']);
			}

			$sql = getSQL($wiki, $article);
			$result = $db->query($sql);

			$form = true;
			$text = false;
			switch ($_POST['submit']){
				case "Preview":
					$out = $_POST['content'];
					$text = stripslashes($_POST['content']);
					break;

				case "Spell Check":
					$checker = new Spellchecker;

					$text = strip_tags(textile($_POST['content']));
					$num_errors = $checker->check($text);

					if ($num_errors > 0) {
						$out .= "h3. Spell Check\n\n";
						#$out .= "Items <span class=\"spellCorrect\">like this</span> could be errors, hover over for suggestions. Items <span class=\"spellNoSuggest\">like this</span> arn't in the dictionary, and the spell checker has no idea.\n\n";
						$errors = $checker->getErrors();
						$oldtext = $text;
						foreach ($errors as $word => $suggestions) {
							/*$title = trim(implode(', ', $suggestions));
							if ($title == ""){
								$span = '<|-|'.$title.'|-|>'.$word.'</-|>';
							} else {
								$span = '<|||'.$title.'|||>'.$word.'</||>';
							}*/		
							$suggs = implode(' ', $suggestions);
							if ($suggs  != " "){
								$errorlist .=  "*".$word.":* ".$suggs."\n\n";
							} else {
								$noidea[] = $word;
							}
							

							# $text = str_replace($word, $span, $text);
							#$text = preg_replace("/(\W|^)$word(\W|\$)/i", "$1$span$2", $text);
						}
						/*
						//if ($title == ""){
							$text = str_replace('<|-|', '<span class="spellNoSuggest"', $text);
							$text = str_replace('|-|>', '>', $text);
							$text = str_replace('</-|>', '</span>', $text);
						//} else {
							$text = str_replace('<|||', '<span class="spellCorrect" title="', $text);
							$text = str_replace('|||>', '">', $text);
							$text = str_replace('</||>', '</span>', $text);
						//}*/
					}
					if($noidea){
						$errorlist .= "*No idea about:* ".implode(' ', $noidea)."\n\n";
					}
					#$out .= $text;
					$out .= $errorlist."\n";
					$text = stripslashes($_POST['content']);
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
			
			if ($row['content'] == ""){
				$_POST['comment'] = "Start of a brand new world";
			}

			if ($form){
				$out .= "<form method=post action=\"".$_SERVER['REQUEST_URI']."\">\n";
				$out .= "<label for=\"creator\">Author</label>\n";
				$out .= $_EXTRAS['me']."<br>\n";
				$out .= "<label for=\"content\">Content</label>\n";
				$out .= "<textarea name=\"content\" id=\"content\" rows=\"30\" cols=\"72\">[[TEXTAREA]]</textarea>\n<br>\n";
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
				$content[2] = $row['content']."\n\n [ \"Edit This Page\":$url?action=edit | \"View Source\":$url?action=src ]";
				$content[3] = $row['creator'];
				$content[4] = date("r",$row['created']);
				$out = "\n\nh2. Versions:\n";
				$line = date("r",$row['created'])." - \"".$row['creator']."\":$base/~".$row['creator'];
					if ($row['comment']){
						$line .= " : ".$row['comment'];
					}
				$out .= "# ".$line." [ Current ]\n";

				$limit = 4;
				$current = 0;

				while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
					$line = date("r",$row['created'])." - \"".$row['creator']."\":$base/~".$row['creator'];
					if ($row['comment']){
						$line .= " : ".$row['comment'];
					}
					$out .= "# ".$line." [ <a href=\"".$url."?action=viewrev&amp;id=".$row['revision']."\" title=\"View this revision\">View</a> |"
					." <a href=\"".$url."?action=diff&amp;from=".$row['revision']."\"\" title=\"View differences between this and the current revision\">Diff</a> ]\n";
					$current++;
					if ($current >= $limit && $_GET['action'] != "allrev"){
						$out .= "# \"Show rest of revisions\":".$url."?action=allrev\n";
						break;
					}
				}
				$content[2] .= $out;
			}
	}
	return $content;

}

function getWikis(){
	global $db;

	$wikis = array();

	$sql = "select wiki, count(page) as count from wikipage group by wiki";
	$result = $db->query($sql);
	// Always check that $result is not an error
	if (DB::isError($result)) {
		panic($result->getMessage());
	}
	while ($row = $result->fetchRow()) {
		$wikis[] = $row;
	}

	return $wikis;
}

?>