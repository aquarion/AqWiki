<?PHP
/*******************************************************************************
	AqWiki - Wiki functions
********************************************************************************
	
	(c) Nicholas 'Aquarion' Avenell 2004

	Released under the Artistic Licence, a copy of which is in docs/licence.txt
	or can be found at http://opensource.org/licenses/artistic-license.php

********************************************************************************

	Wiki functions, process documents, get pages etc.

	$Id: wiki.inc.php,v 1.23 2006/10/10 09:32:47 aquarion Exp $

	$Log: wiki.inc.php,v $
	Revision 1.23  2006/10/10 09:32:47  aquarion
	* Development 2006
	
	Revision 1.22  2005/02/16 17:13:38  aquarion
	* Database fixes
	* New Textile Library support
	* Developement resumes, yay
	
	Revision 1.21  2004/10/22 13:56:08  aquarion
	* Fixed Stuff
	
	Revision 1.20  2004/09/29 15:11:19  aquarion
	Fixing formatting bug (Links at the very end of the
		text were not being recognised)
	
	Revision 1.19  2004/09/29 10:49:50  aquarion
	+ Fixed character encoding bugs
	
	Revision 1.18  2004/09/05 10:16:48  aquarion
	Moved versions and edit this page to templates
	
	Revision 1.17  2004/08/30 01:26:00  aquarion
	+ Added 'stripDirectories' option, because mod_rewrite doesn't like me much
	* Fixed non-mysql4 search. We now work with mysql 4.0! and probably 3! Woo!
	+ Added 'newuser' to the abstracted data class. No idea how I missed it, tbh.
	
	Revision 1.16  2004/08/29 20:27:12  aquarion
	* Cleaning up auth system
	+ restrictNewPages configuration option
	+ Restrict usernames (Don't contain commas or be 'register')
	
	Revision 1.15  2004/08/29 17:25:08  aquarion
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
	
	Revision 1.14  2004/08/15 16:04:03  aquarion
	+ Fix bugs 1009244, 1009268 & 1009266
	+ Fixed other things
	
	Revision 1.13  2004/08/14 11:09:42  aquarion
	+ Artistic Licence
	+ Actual Documentation (Shock)
	+ Config examples
	+ Install guide
	
	Revision 1.12  2004/08/13 21:01:43  aquarion
	* Fixed diff to make it work with the new data abstraction layer
	
	Revision 1.11  2004/08/12 19:37:53  aquarion
	+ RSS output
	+ Detailed RSS output for Recent
	* Slight redesign of c/datasource (recent now outputs an array) to cope with above
	* Fixed Recent to cope with oneWiki format
	+ added Host configuation directive
	
	Revision 1.10  2004/07/05 20:29:05  aquarion
	* Lets try actually using _real_ CVS keywords, not words I guess at this time
	+ [[AQWIKI]] template tag
	+ Default template finally exists! Sing yay!
	* Fixed Non-oneWiki [[BASE]] by adding $_EXTRAS['wiki']
	* Minor fixen
	

*******************************************************************************/


$_FILES['wiki'] = '$Revision: 1.23 $';

function process($text, $wiki){
	global $dataSource;
	global $_EXTRAS;
	global $_CONFIG;

	$text = $text."\n\n";

	if ($_CONFIG['oneWiki']){
		$base = $_CONFIG['base'];
	} else {
		$base = $_CONFIG['base']."/".$wiki;
	}

	function stripSpaces($text){
		return ereg_replace("/[:space:]/","",$text);
	}


	/*// Conditional includes
	preg_match_all("/\[\[IFEDIT\|(.*?)\]\]/", $text, $matches);
	foreach($matches[0] as $index => $match){
		$result = $matches[1][$index];
		if (checkAuth("edit")){
			$text = preg_replace("#".preg_quote($match,"#")."#",$result,$text);
		} else {
			$text = preg_replace("#".preg_quote($match,"#")."#","Can't Edit",$text);
		}
		#$_EXTRAS[$matches[1][$index]] = $matches[2][$index];
	}

	preg_match_all("/\[\[IFEDIT\|(.*?)\|(.*?)\]\]/", $text, $matches);
	foreach($matches[0] as $index => $match){
		if (checkAuth("edit")){
			$text = preg_replace("#".preg_quote($match,"#")."#", $matches[1][$index],$text);
		} else {
			$text = preg_replace("#".preg_quote($match,"#")."#", $matches[2][$index],$text);
		}
		#$_EXTRAS[$matches[1][$index]] = $matches[2][$index];
	}*/


	preg_match_all("/\[\[INCLUDE\|(.*?)\]\]/",$text, $matches);
	foreach ($matches[0] as $index => $match){
		$include = $dataSource->getContent($matches[1][$index]);
		$text = preg_replace("#".preg_quote($match,"#")."#",$include,$text);
	}

	// Set Variables
	preg_match_all("/\[\[SETVAR\|(.*?)\|(.*?)\]\]/", $text, $matches); // [[CALC|var|value]]
	foreach($matches[0] as $index => $match){
		$text = preg_replace("#".preg_quote($match,"#")."#","",$text);
		$_EXTRAS[$matches[1][$index]] = $matches[2][$index];
	}


	preg_match_all("/\[\[CALC\|(.*?)\|(.*?)\]\]/", $text, $matches); // [[CALC|var|opp]]
	foreach($matches[0] as $index => $match){
		$text = preg_replace("#".preg_quote($match,"#")."#","<!-- Calculate functions removed -->",$text);
		#$eval = "\$_EXTRAS[".$matches[1][$index]."] = \$_EXTRAS[".$matches[1][$index]."] ".$matches[2][$index].";";
		#eval($eval);
		#$_EXTRAS[$matches[1][$index]] = eval($eval);
	}

	preg_match_all("/\[\[RCALC\|(.*?)\|(.*?)\]\]/", $text, $matches); // [[RCALC|ropp|var]]
	foreach($matches[0] as $index => $match){
		$text = preg_replace("#".preg_quote($match,"#")."#","<!-- Calculate functions removed -->",$text);
		#$eval = "\$_EXTRAS[".$matches[2][$index]."] = ".$matches[1][$index]." \$_EXTRAS[".$matches[2][$index]."];";
		#eval($eval);
		#$_EXTRAS[$matches[1][$index]] = eval($eval);
	}

	


	preg_match_all("/\[\[VAR\|(.*?)\]\]/",$text, $matches);
	foreach ($matches[0] as $index => $match){
		$var = $_EXTRAS[$matches[1][$index]];
		$text = preg_replace("#".preg_quote($match,"#")."#",$var, $text);
	}


	#$text = preg_replace("/\[\[SEARCH\|(.*?)\]\]/",searchFor($wiki,'\1'), $text);
	#$text = preg_replace("/\[\[ALLBY\|(.*?)\]\]/",searchAuthor($wiki,'\1'), $text);
	if (preg_match("#\[\[RECENT\]\]#",$text)){
		$text = preg_replace("/\[\[RECENT\]\]/",recent($wiki), $text);
	}
	if (preg_match("/\[\[INDEX\]\]/",$text)){
		$text = preg_replace("/\[\[INDEX\]\]/",index(), $text);
	}



	// Search for User
	preg_match_all("/\[\[ALLBY\|(.*?)\]\]/", $text, $matches);
	foreach($matches[0] as $index => $match){
		$result = author($matches[1][$index]);
		$text = preg_replace("#".preg_quote($match,"#")."#",$result,$text);
		#$_EXTRAS[$matches[1][$index]] = $matches[2][$index];
	}

	// Search for Arbitaty
	preg_match_all("/\[\[SEARCH\|(.*?)\]\]/", $text, $matches);
	foreach($matches[0] as $index => $match){
		$datum = $matches[1][$index];
		$result = $dataSource->search($datum);
		$text = preg_replace("#".preg_quote($match,"#")."#",$result,$text);
		#$_EXTRAS[$matches[1][$index]] = $matches[2][$index];
	}

	/*// [[MACRO|macroname|arguments]]
	preg_match_all("/\[\[MACRO\|(.*?)\|(.*?)\\]\]/", $text, $matches);
	foreach($matches[0] as $index => $match){
		#print_r($matches);

		if (file_exists("macros/".$matches[1][$index].".inc")){
			ob_start();
			$var = $matches[2][$index];
			include("macros/".$matches[1][$index].".inc");
			$return = ob_get_contents();
			ob_end_clean();
		} else {
			$return = "Macro ".$matches[1][$index]." not defined";
		}
		
		$text = preg_replace("#".preg_quote($matches[0][$index],"#")."#",$return,$text);
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
	*/

	//New Macros code

	$macros = array();

	// [[MACRO|macroname|arguments]]
	preg_match_all("/\[\[MACRO\|(.*?)\|(.*?)\\]\]/", $text, $matches);
	foreach($matches[0] as $index => $match){
		#print_r($matches);
		$return = "";

		$macro = $matches[1][$index];
		$command = $matches[2][$index];

		$params = false;

		if($pos = strpos($command, '|')){
			$params = explode(',', substr($command, $pos+1));
			$command = substr($command, 0, $pos);
		}

		debug("Macro: $macro: $command");

		if ($command == "INIT"){
			if (file_exists("macros/".$matches[1][$index].".inc")){
				include("macros/".$matches[1][$index].".inc");
				$macros[$macro] = new $macro($dataSource, $_EXTRAS);
			} else {
				$return = "Macro ".$matches[1][$index]." not defined";
			}

		} elseif($command == "LIST"){
			$dir = opendir("macros");
			while($line = readdir($dir)){
				if (substr($line, -4) != ".inc"){
					continue;
				}
				$return .= "* $line\n";
			}
		} elseif (!isset($macros[$macro])){
			$return = "Macro $macro used before defined!";
		} elseif(!in_array($command, get_class_methods($macro))) {
			$return = "Macro $macro cannot execute $command";
			
		} else {
			#$return = call_user_func(array($macro, $command)); 
			if($params){
				$return = $macros[$macro]->$command($params); 
			} else {
				$return = $macros[$macro]->$command(); 
			}
		}

		
		$text = preg_replace("#".preg_quote($matches[0][$index],"#")."#",$return,$text);
		$_EXTRAS[$matches[1][$index]] = $matches[2][$index];
	}

	// [[MACRO|macroname]]


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
	
	preg_match_all("/\(\((.*?)\)\)/","\n".$text."\n",$matches);
	foreach($matches[1] as $index => $title){
		if (! strpos($matches[1][$index], "|")){
			$link = preg_replace("/(\W)/", "", $title);
			#$links[] = array($matches[0][$index],$link, $title);
		} else {
			$bang = explode("|",$matches[1][$index]);
			$link = preg_replace("/(\W)/", "", $bang[1]);
			$title = $bang[0];
		}

		if ($title[0] == '~'){
			$link = '~'.$link;
		}
		
		$links[] = array($matches[0][$index],$link, $title);
	}

	foreach($links as $index => $matches){
		$replace = preg_quote($matches[0]);
		$stripped = $matches[1];
		$title = $matches[2];

		if (!$dataSource->pageExists($stripped)){
			#$link =  "%(uncreated)".$title."\"?\":".$base."/".$stripped."?action=edit%";
			#$link =  "\"".$title."\":".$base."/".$stripped;	
			$link = '<a href="'.$base."/".$stripped.'" class="uncreated wiki" title="Uncreated article '.$title.'">¿'.$title.'?</a>';
		} else {
			#$link =  "\"".$title."\":".$base."/".$stripped;
			$link = '<a href="'.$base."/".$stripped.'" class="wiki" title="Internal link to article '.$title.'">'.$title.'</a>';
		}

		#$link =  "\"".$match."\":".$base."/".$stripped;
		#echo $replace;
		$text = preg_replace("/(\W|^)".$replace."(\W)/","$1$link$2", $text);
		#$text = preg_replace("/(\W|^)".$replace."(\W)/","$1|$replace|$2", $text);
	}

	$text = textile($text);
	#$text = ereg_replace("[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]\"/]", "<a href=\"\\0\">\\0</a>", $text);
	#$text = preg_replace("#<a href=\"<a href=\"(.*)\">(.*)\"</a>>(.*)</a>#","<a href=\"$1\">$3</a>",$text);
	$text = preg_replace("/\[\[CAL\]\]/","<div class=\"calendar\">".$caltext."</div>", $text);
	$text = preg_replace("/\[CC\](.*?)\[CC\]/","(($1))",$text);

	$text = preg_replace("/\[CMD\](.*?)\[CMD\]/","[[$1]]",$text);


	if(!isset($_EXTRAS['textarea'])){
		$_EXTRAS['textarea'] = "";
	}
	$text = preg_replace("/\[\[TEXTAREA\]\]/",$_EXTRAS['textarea'],$text);


	return $text;
}

function entitize($input){
	return $input;
	#return htmlentities($input, ENT_NOQUOTES);
}

function wiki($wiki, $article){
	global $dataSource;
	global $_CONFIG;
	global $_EXTRAS;

	//if ($_CONFIG['oneWiki']){
		$base = $_CONFIG['base'];
		$url = $_CONFIG['base']."/$article";
	//} else {
	//	$base = $_CONFIG['base']."/".$wiki;
	//	$url = $_CONFIG['base']."/$wiki/$article";
	//}

	$content = array(
		$wiki,
		$article,
		"",
		"aqWiki (Admin)",
		date("r"));

	if(!isset($_GET['action'])){
		$_GET['action'] = false;
	}

	switch($_GET['action']){
		case "viewrev":

			if(!$_GET['id']){
				die("Parameters incorrect");
			}

			$id = $_GET['id'];

			$pages = $dataSource->getPage($article);

			debug("Found ".count($pages)." pages");

			$row = $pages[$id];
			
			$content[2] = $row['content'];#."\n\n [ \"Edit This Page\":$url?action=edit | \"View Source\":$url?action=src ]";
			$content[3] = $row['creator'];
			$content[4] = date("r",$row['created']);


			$limit = 4;
			$current = 0;

			foreach ($pages as $row) {

				$line = date("r",$row['created'])." - \"".$row['creator']."\":$base/~".$row['creator'];
				if ($row['comment']){
					$line .= " : ".$row['comment'];
				}
				

				if ($row['revision'] == $id){
					$_EXTRAS['versions'] .= "# ".$line." [ Current ]\n";
				} else {
					$_EXTRAS['versions'] .= "# ".$line." [ <a href=\"".$url."?action=viewrev&amp;id=".$row['revision']."\" title=\"View this revision\">View</a> |"
						." <a href=\"".$url."?action=diff&amp;from=".$id."&amp;to=".$row['revision']
						."\"\" title=\"View differences between this and the current revision\">Diff</a> ]\n";
				}

				$current++;
				if ($id < $row['revision']){
					// Nothing happens
				} elseif (($current >= $limit && $_GET['action'] != "allrev") ){
					$_EXTRAS['versions'] .= "# \"Show rest of revisions\":".$url."?action=allrev\n";
					break;
				}

			}

			$content[2] .= $out;
			
			break;

		case "diff":
			$content[2] = "These are the differences between two versions of (($article)). Lines styled <span class=\"added\">"
				."like this</span> have been added to the entry, lines <span class=\"removed\">like this</span> have been removed.\n\n";
			
			$_EXTRAS['textarea'] = $dataSource->diff($article, $_GET['from'], $_GET['to']);
			$content[2] .= "[[TEXTAREA]]";

			break;

		case "newUser":
			/*mysql> describe users;
			+---------------+------------------+-------------------+
			| Field         | Type             | Collation         |
			+---------------+------------------+-------------------+
			| id            | int(10) unsigned | binary            |
			| username      | varchar(64)      | latin1_swedish_ci |
			| real_name     | tinytext         | latin1_swedish_ci |
			| email         | tinytext         | latin1_swedish_ci |
			| birthday      | date             | latin1_swedish_ci |
			| password      | tinytext         | latin1_swedish_ci |
			| location      | int(11)          | binary            |
			| last_access   | timestamp        | latin1_swedish_ci |
			| date_creation | timestamp        | latin1_swedish_ci |
			| access_level  | int(11)          | binary            |
			+---------------+------------------+-------------------+
			10 rows in set (0.05 sec)
			*/

			$form = '<form method=post action="'.$_SERVER['REQUEST_URI'].'">'."\n\n"
			.'|Username|<input type="text" name="username" value="'.$_POST['username'].'">|(Must not be blank)|'."\n"
			.'|Display Name|<input type="text" name="name" value="'.$_POST['name'].'">|(Must not be blank)<br>|'."\n"
			.'|e-Mail|<input type="text" name="email" value="'.$_POST['email'].'">|(Must not be blank)<br>|'."\n"
			.'|Password|<input type="password" name="password">|(Must not be blank)<br>|'."\n"
			.'|Repeat Password |<input type="password" name="password2">| (Must match above) |'."\n"
			.'| Submit |<input type="submit" name="submit" value="Send Form">| Bow to my will |'."\n\n"
			.'</form>';

			#print_r($_POST);

			if ($_POST['submit']){
				$errors = array();
				if ($_POST['username'] == ""){
					$errors[] = "Username cannot be blank";
				} elseif(strstr($_POST['username'],",")){
					$errors[] = "Username cannot contain commas";
				} elseif(in_array($_POST['username'], $_EXTRAS['reservedUsers'])){
					$errors[] = "Username cannot contain commas";
				} elseif (!$dataSource->unique("users", "username", $_POST['username'])){
					$errors[] = "Username must be unique";
				}	

				if ($_POST['email'] == ""){
					$errors[] = "email cannot be blank";
				} elseif (!$dataSource->unique("users", "email", $_POST['email'])){
					$errors[] = "email must be unique";
				}

				if ($_POST['name'] == ""){
					$errors[] = "Display Name cannot be blank";
				} elseif (!$dataSource->unique("users", "real_name", $_POST['name'])){
					$errors[] = "Display Name must be unique";
				}

				if ($_POST['password'] == ""){
					$errors[] = "password cannot be blank";
				} elseif ($_POST['password'] != $_POST['password2']){
					$errors[] = "passwords must match";
				}

				if (count($errors) == 0){

					$dataSource->newUser($_POST['username'], $_POST['name'], $_POST['password'], $_POST['email']);

					$out = "h2. New user created\n\n";
					$out .= "Hi, ".$_POST['name'].", Welcome to this aqWiki install.\n\n";
					$url = parse_url($_SERVER['REQUEST_URI']);
					$out .= "You should now \"login\":".$url['path']."?action=login";

				} else {
					$out = "h2. Error in user creation\n\n";
					foreach($errors as $error){
						$out .= "* ".$error."\n";
					}
					$out .= "\n\n".$form;
				}
			} else {
				$out = "h2. New user\n\n";
				$out .= $form;
			}
			

			$content[2] = $out;

			break;
		
		case "edit":


			if($_EXTRAS['reqEdit']){
				doAuth($_EXTRAS['reqEdit'], "edit a page");
			}


			if($_EXTRAS['restrictNewPages']){
				doAuth($_EXTRAS['restrictNewPages'], "create a new page");
			}

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
					$dataSource->post($article, $_POST['content'], $_POST['comment']);
					$form = false;
					header("location: $url");
			}

			if ($text){
				$_EXTRAS['textarea'] =  $text;
			} elseif (!$dataSource->pageExists($article)){
				$_POST['comment'] = "Start of a brand new world";
				$_EXTRAS['textarea'] = "";
			} else {
				$_EXTRAS['textarea'] = stripslashes($dataSource->getContent($article));
			}
			
			if ($form){
				$out .= "<form method=post action=\"".$_SERVER['REQUEST_URI']."\">\n";
				$out .= "<p>You should read the ((help)). If you are having problems with the formatting, post it and add a note explaining the problem to ((formattingProblems)) and I'll dive in and fix it. If you believe you've found a bug in the wiki software, post your problem to \"this wiki page\":http://www.gkhs.net/aqwikiBug and I'll dive in and fix that too.</p>\n";
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
		
		case "allrev":
		
		
			if (!$dataSource->pageExists($article)){
				$content[2] = 'Error: Page doesn\'t exist. What are you playing at?';
				break;		
			}
			
			$content[2] = 'h2. Viewing all revisions for (('.$article."))\n\n";
			
			$content[2] .= 'Select the <input type="radio" /> boxes to compare two revisions';
			
			$content[2] .= '<form method="GET" action="'.$url.'">'."\n\n";
			
			$pages = $dataSource->getPage($article);
			$pages = array_reverse($pages);
			
			
			foreach ($pages as $row) {
			
				$line = '<input type="radio" name="from" value="'.$row['revision'].'">';
				$line .= '<input type="radio" name="to" value="'.$row['revision'].'">';
				
				$line .= date("Y-m-d H:i",$row['created'])." - ".userlink($row['creator']);
				if ($row['comment']){
					$line .= " : ".$row['comment'];
				}
				$content[2] .= "# ".$line." [ <a href=\"".$url."?action=viewrev&amp;id=".$row['revision']."\" title=\"View this revision\">View</a> |"
				." <a href=\"".$url."?action=diff&amp;from=".$row['revision']."\"\" title=\"View differences between this and the current revision\">Diff</a> ]\n";
			}
			$content[2] .= '<input type="submit" value="Compare Revisions">
			<input type="hidden" value="diff" name="action">
			</form>';
		
			break;
				
				

		default:
			$_EXTRAS['versions'] = "";
			if (!$dataSource->pageExists($article)){
				if ($_EXTRAS['restrictNewPages'] || $_EXTRAS['reqEdit']){
					if ($_EXTRAS['restrictNewPages'] == "register"){
						$message = "any registered users";
					} else {
						$message = "only certain users";
					}
					if (!isset($_EXTRAS['newPageMessage'])){
						$npm = "This page doesn't exist yet. [[TYPES]] can create new pages. Do you want to do so?\n\n\"Go On Then\":[[EDITURL]]";
					} else {
						$npm = $_EXTRAS['newPageMessage'];
					}

					$content[2] = str_replace(
							array("[[TYPES]]", "[[EDITURL]]"), 
							array($message, $url."?action=edit"), 
							$npm);
				} else {
					$content[2] = "This page doesn't exist yet, Would you like to create it?\n\n\"Go On Then\":".$url."?action=edit";
				}
			} else {
				$_EXTRAS['nearby'] = $dataSource->nearby($article);
	
				$pages = $dataSource->getPage($article);

				$row = array_shift($pages);

				if (strcmp ($row['wiki'] , $wiki) != 0){
					$base = $_CONFIG['base']."/".$row['wiki'];
					$url = $base."/".$article;
					header("location: ".$url);	
				}
				$content[2] = $row['content'];

				$content[3] = $row['creator'];
				$content[4] = date("r",$row['created']);

				$line = date("r",$row['created'])." - ".userlink($row['creator']);
					if ($row['comment']){
						$line .= " : ".$row['comment'];
					}
				$_EXTRAS['versions'] .= "# ".$line." [ Current ]\n";

				$limit = 4;
				$current = 0;

				foreach ($pages as $row) {
					$line = date("Y-m-d\tH:i",$row['created'])." - ".userlink($row['creator']);
					if ($row['comment']){
						$line .= " : ".$row['comment'];
					}
					$_EXTRAS['versions'] .= "# ".$line." [ <a href=\"".$url."?action=viewrev&amp;id=".$row['revision']."\" title=\"View this revision\">View</a> |"
					." <a href=\"".$url."?action=diff&amp;from=".$row['revision']."\"\" title=\"View differences between this and the current revision\">Diff</a> ]\n";
					$current++;
					if ($_GET['action'] != "allrev"){
						$_EXTRAS['versions'] .= "# \"Show list of revisions\":".$url."?action=allrev\n";
						break;
					}
				}
				#$content[2] .= $out;
			}
	}
	return $content;

}


?>
