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


	preg_match_all("/\[\[IfLoggedIn\|(.*?)\|(.*?)\]\]/", $text, $matches);
	foreach($matches[0] as $index => $match){
		$result = $matches[1][$index];
		if (isset($_EXTRAS['me'])){
			$text = preg_replace("#".preg_quote($match,"#")."#",$matches[1][$index],$text);
		} else {
			$text = preg_replace("#".preg_quote($match,"#")."#",$matches[2][$index],$text);
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
		$text = str_replace("[[RECENT]]",recent($wiki), $text);
	}
	if (preg_match("/\[\[INDEX\]\]/",$text)){
		$text = str_replace("[[INDEX]]",index(), $text);
	}
	
	
	
	preg_match_all("/\[\[LOCKED\|(.*?)\]\]/",$text, $matches);
	foreach ($matches[0] as $index => $match){
		
		$users = $matches[1][$index];
		doAuth($users, "view this");
		
		$users_array = explode(',',$users);
		
		if (count($users_array) == 1){
			$users_text = $users;
		} else {
			$last = array_pop($users_array);
			$users_text = implode(", ", $users_array).' &amp; '.$last;
		}
		
		$text = preg_replace("#".preg_quote($match,"#")."#",
			'<div class="locked">Page is locked to '.$users_text.' </div>',
			$text);
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


		if(!isset($macros[$macro])){
			if (file_exists("macros/".$matches[1][$index].".inc")){
				include("macros/".$matches[1][$index].".inc");
				$macros[$macro] = new $macro($dataSource, $_EXTRAS);
			}
		}


		if(!isset($macros[$macro])){
			// Macro load failed.

			$return = "*!!Macro ".$matches[1][$index]." not defined!!*";

		} elseif ($command == "INIT"){
			
			// Explicit INIT disabled

			//if (file_exists("macros/".$matches[1][$index].".inc")){
			//	include("macros/".$matches[1][$index].".inc");
			//	$macros[$macro] = new $macro($dataSource, $_EXTRAS);
			//} else {
			//	$return = "Macro ".$matches[1][$index]." not defined";
			//}


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

		
		#$text = preg_replace("#".preg_quote($matches[0][$index],"#")."#",$return,$text);
		$text = str_replace($matches[0][$index], $return , $text);

		$_EXTRAS[$matches[1][$index]] = $matches[2][$index];
	}

	// [[MACRO|macroname]]


	// [[CAL|year-mm-dd|Event]]
	preg_match_all("/\[\[CAL\|(....)\-(..)\-(..)\|(.*?)\]\]/", $text, $matches);
	$i = 0;

	$calendar = array();
	$caltext = "";

	foreach($matches[0] as $index => $match){

		$link = preg_replace("/(\W)/", "", $matches[4][$index]);
		$text = str_replace($matches[0][$index], "<a name=\"".$link."\"></a>" , $text);
		$calendar[$matches[1][$index]][$matches[2][$index]][$matches[3][$index]] = $matches[4][$index];
	}


	preg_match_all("/\[\[VAR\|(.*?)\]\]/",$text, $matches);
	foreach ($matches[0] as $index => $match){
		if(isset($_EXTRAS[$matches[1][$index]])){
		$var = $_EXTRAS[$matches[1][$index]];
		} else {
			$var = '[ERR: '.$matches[1][$index].' Undefined]';
		}
		#$text = preg_replace("#".preg_quote($match,"#")."#",$var, $text);
		$text = str_replace($match, $var , $text);
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
		$replace = preg_quote($matches[0], '/');
		$stripped = $matches[1];
		$title = $matches[2];

		if ($title[0] == '~'){
			$title = substr($title, 1);
			#$link =  "%(uncreated)".$title."\"?\":".$base."/".$stripped."?action=edit%";
			#$link =  "\"".$title."\":".$base."/".$stripped;	
			$link = userLink($title);
			
		} elseif (!$dataSource->pageExists($stripped)){
			#$link =  "%(uncreated)".$title."\"?\":".$base."/".$stripped."?action=edit%";
			#$link =  "\"".$title."\":".$base."/".$stripped;	
			$link = '<a href="'.$base."/".$stripped.'" class="uncreated wiki" title="Uncreated article '.$title.'">'.$title.'</a>';
		} else {
			#$link =  "\"".$title."\":".$base."/".$stripped;
			$link = '<a href="'.$base."/".$stripped.'" class="wiki" title="Internal link to article '.$title.'">'.$title.'</a>';
		}

		#$link =  "\"".$match."\":".$base."/".$stripped;
		#echo $replace;
		$text = preg_replace("/(\W|^)".$replace."(\W)/","$1$link$2", $text);
		#$text = preg_replace("/(\W|^)".$replace."(\W)/","$1|$replace|$2", $text);
	}


	//preg_match_all("/<aqWikiNoProcess>(.*?)<\/aqwikiNoProcess>/m",$text, $matches);
	
	$text = str_replace("\n", '[[BR]]',$text);
	$text = str_replace("\r", '',$text);
	
		
	preg_match_all("/<aqWikiNoProcess>(.*?)<\/aqWikiNoProcess>/",$text, $matches);
	foreach ($matches[0] as $index => $match){
		$id = uniqid();
		$EXTRAS['noProcess'][$id] = $matches[1][$index];
		#$text = preg_replace("#".preg_quote($match,"#")."#",'[[NOPROCESS|'.$id.']]',$text);
		$text = str_replace($match,  '[[NOPROCESS|'.$id.']]', $text);
	}
	
	

	$text = str_replace("[[BR]]", "\n",$text);

	$text = textile($text);
	#$text = ereg_replace("[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]\"/]", "<a href=\"\\0\">\\0</a>", $text);
	#$text = preg_replace("#<a href=\"<a href=\"(.*)\">(.*)\"</a>>(.*)</a>#","<a href=\"$1\">$3</a>",$text);
	$text = preg_replace("/\[CC\](.*?)\[CC\]/","(($1))",$text);
	$text = preg_replace("/\[CMD\](.*?)\[CMD\]/","[[$1]]",$text);
	
	$text = str_replace('[[CAL]]', "<div class=\"calendar\">".$caltext."</div>" , $text);


	if(!isset($_EXTRAS['textarea'])){
		$_EXTRAS['textarea'] = "";
	}
	$text = preg_replace("/\[\[TEXTAREA\]\]/",$_EXTRAS['textarea'],$text);
	$text = str_replace('[[TEXTAREA]]' , $_EXTRAS['textarea'], $text);



	preg_match_all("/\[\[RAWVAR\|(.*?)\]\]/",$text, $matches);
	foreach ($matches[0] as $index => $match){
		if(isset($_EXTRAS[$matches[1][$index]])){
		$var = $_EXTRAS[$matches[1][$index]];
		} else {
			$var = '[ERR: '.$matches[1][$index].' Undefined]';
		}

		$text = str_replace($match, $var , $text);
	}

	preg_match_all("/\[\[NOPROCESS\|(.*?)\]\]/",$text, $matches);
	foreach ($matches[0] as $index => $match){
		$id = $matches[1][$index];
		$text = str_replace($match, $EXTRAS['noProcess'][$id] , $text);
	}
	
	$text = str_replace("[[BR]]", "\n",$text);

	

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
	
	$out = '';

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
				panic("View Revision","Parameters incorrect");
			}

			$id = $_GET['id'];

			$pages = $dataSource->getPage($article);

			debug("Found ".count($pages)." pages");

			$row = $pages[$id];
			
			$content[2] = '<div class="info"><b>Note:</b> This is a <i>specific revision</i> of this page, and may be outdated, The current version is ((here|'.$article.')). You can see the differences between this and the current revision <a href="'.$url.'?action=diff&amp;from='.$id.'">here</a></div>';
			
			if(in_array($_EXTRAS['me'], $_EXTRAS['admins']) ){
				$content[2] .= '<div class="adminFunctions">Admin Actions: 
				<a href="'.$url.'?action=revert&id='.$id.'">Revert back to this version</a>
				</div>';
			}
			
			$content[2] .= $row['content'];#."\n\n [ \"Edit This Page\":$url?action=edit | \"View Source\":$url?action=src ]";
			$content[3] = $row['creator'];
			$content[4] = date("r",$row['created']);


			$limit = 3;
			$current = 0;
			
			$_EXTRAS['versions'] = '';

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
					if ($id == $row['revision']){
						$limit+=6;
					} else {
						$_EXTRAS['versions'] .= "# \"Show rest of revisions\":".$url."?action=allrev\n";
						break;
					}
				}

			}

			$content[2] .= $out;
			
			break;

		case "diff":
			$content[2] = "These are the differences between two versions of (($article)). Lines styled <span class=\"added\">"
				."like this</span> have been added to the entry, lines <span class=\"removed\">like this</span> have been removed.\n\n";
			
			$from = isset($_GET['from']) ? $_GET['from'] : false;
			$to   = isset($_GET['to'])   ? $_GET['to']   : false;
			$_EXTRAS['textarea'] = $dataSource->diff($article, $from, $to);
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

			$form = '<form class="shiny" method=post action="'.$_SERVER['REQUEST_URI'].'"><h2>New User</h2>'."\n\n"
			.'|Username|<input type="text" name="username" value="'.$_POST['username'].'">|(Must not be blank)|'."\n"
			.'|Display Name|<input type="text" name="name" value="'.$_POST['name'].'">|(Must not be blank)<br>|'."\n"
			.'|e-Mail|<input type="text" name="email" value="'.$_POST['email'].'">|(Must not be blank)<br>|'."\n"
			.'|Password|<input type="password" name="password">|(Must not be blank)<br>|'."\n"
			.'|Repeat Password |<input type="password" name="password2">| (Must match above) |'."\n\n";
			
			
			if (isset($_CONFIG['recaptcha_public_key'])){
				
				require_once('recaptchalib.php');

				$public_key = $_CONFIG['recaptcha_public_key'];
				
				$form .= '<aqWikiNoProcess>'.recaptcha_get_html($public_key)."</aqWikiNoProcess>\n\n";

			} 
			
			$form .= '<input type="submit" name="submit" value="Create User">'."\n\n"
			.'</form>';

			#print_r($_POST);

			if ($_POST['submit']){
				$errors = array();
				if ($_POST['username'] == ""){
					$errors[] = "Username cannot be blank";
				} elseif(strstr($_POST['username'],",")){
					$errors[] = "Username cannot contain commas";
				} elseif(isset($_EXTRAS['reservedUsers']) && in_array($_POST['username'], $_EXTRAS['reservedUsers'])){
					$errors[] = "Username invalid";
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
				
				if (isset($_CONFIG['recaptcha_private_key'])){
					$privatekey = $_CONFIG['recaptcha_private_key'];
					$resp = recaptcha_check_answer ($privatekey,
					                                $_SERVER["REMOTE_ADDR"],
					                                $_POST["recaptcha_challenge_field"],
					                                $_POST["recaptcha_response_field"]);
					
					if (!$resp->is_valid) {
					  $errors[] = "Captcha invalid";
					}
				}

				if (count($errors) == 0){

					$dataSource->newUser($_POST['username'], $_POST['name'], $_POST['password'], $_POST['email']);
					
					sendAdminEmail('New User Created', $_POST);

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
				
								
					$page = array_shift($dataSource->getPage($article));
										
					if($page['rev_created'] > $_POST['edittime']){
						$content[2] .=  collision_detection($page, $_POST);
						$text = $_POST['content'];
						//$form = false;				
					} else {
						$dataSource->post($article, $_POST['content'], $_POST['comment']);
						$form = false;
						header("location: $url");
					}
				
			}

			if ($text){
				$_EXTRAS['textarea'] =  $text;
			} elseif (!$dataSource->pageExists($article)){
				$_POST['comment'] = "Start of a brand new world";
				$_EXTRAS['textarea'] = "";
			} else {
				$_EXTRAS['textarea'] = stripslashes($dataSource->getContent($article));
			}

			preg_match_all("/\[\[LOCKED\|(.*?)\]\]/",$_EXTRAS['textarea'], $matches);
			foreach ($matches[0] as $index => $match){
				$users = $matches[1][$index];
				doAuth($users, "view this");
			}

			if ($form){
				$out .= "<form method=post action=\"".$_SERVER['REQUEST_URI']."\" class=\"shiny wikiedit\">";
				$out .= '<h2>Editing "'.$content[1].'"</h2>';
				$out .= "<p>You should read the ((help)). If you are having problems with the formatting, post it and add a note explaining the problem to ((formattingProblems)) and I'll dive in and fix it. If you believe you've found a bug in the wiki software, post your problem to \"the bug tracker\":http://trac.aqxs.net/aqwiki/newticket and I'll dive in and fix that too.</p>\n";
				//$out .= "<label for=\"creator\">Author</label>\n";
				//$out .= $_EXTRAS['me']."<br>\n";
				$out .= "<label for=\"content\">Content of page \"".$content[1]."\"</label>\n";
				$out .= "<textarea name=\"content\" id=\"content\" rows=\"30\" cols=\"72\">[[TEXTAREA]]</textarea>\n<br>\n";
				$out .= "<label for=\"comment\">Comment</label>\n";
				$out .= "<input type=\"text\" name=\"comment\" id=\"comment\" size=\"72\" value=\"".$_POST['comment']."\"><br>\n";
				$out .= "<input class=\"submit\" type=\"hidden\" name=\"edittime\" value=\"".time()."\">\n";
				$out .= "<input class=\"submit\" type=\"submit\" name=\"submit\" value=\"Post\"> ";
				$out .= "<input class=\"submit\" type=\"submit\" name=\"submit\" value=\"Preview\"> ";
				$out .= "<input class=\"submit\" type=\"submit\" name=\"submit\" value=\"Spell Check\"> ";
				$out .= "<input class=\"submit\" type=\"reset\"  name=\"revert\" value=\"Revert to pre-editing\">\n";
				$out .= "</form>";
				$content[2] .= $out;
				break;
			}
		
		case "allrev":
		
		
			if (!$dataSource->pageExists($article)){
				$content[2] = 'Error: Page doesn\'t exist. What are you playing at?';
				break;		
			}
			
			$content[2] = '<form method="GET" action="'.$url.'" style="width: auto;">';
			
			$content[2] .= '<h2>Viewing all revisions for (('.$article."))</h2>\n\n";
			
			$content[2] .= 'Select the <input type="radio" /> boxes to compare two revisions'."\n\n";
			
			
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
		
		case "revert":
		
			if(!in_array($_EXTRAS['me'], $_EXTRAS['admins']) ){
				panic('AqWiki Reversion', 'You\'re not an admin, you can\'t do this shit');
				
			}
			
		
			if(!$_GET['id']){
				die("Parameters incorrect");
			}

			$id = $_GET['id'];

			$pages = $dataSource->getPage($article);
			
			$oldVersion = $pages[$id];

			//die($oldVersion['content']);

			$dataSource->post($article, $oldVersion['content'], 'reverted back to version '.$id);
			
			$form = false;
			
			$content[2] = 'Reverted (('.$article.')) back to version '.$id;
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
					
				
				if ($_EXTRAS['current'] != $article){
					$pages = $dataSource->getPage($_EXTRAS['current']);
					$row = array_shift($pages);
				}
				
				$_EXTRAS['versions'] .= "# ".$line." [ Current ]\n";

				$limit = 10;
				$current = 0;

				foreach ($pages as $row) {
					$line = date("Y-m-d\tH:i",$row['created'])." - ".userlink($row['creator']);
					if ($row['comment']){
						$line .= " : ".$row['comment'];
					}
					$_EXTRAS['versions'] .= "# ".$line." [ <a href=\"".$url."?action=viewrev&amp;id=".$row['revision']."\" title=\"View this revision\">View</a> |"
					." <a href=\"".$url."?action=diff&amp;from=".$row['revision']."\"\" title=\"View differences between this and the current revision\">Diff</a> ]\n";
					$current++;
					if ($_GET['action'] != "allrev" && $current > $limit){
						$_EXTRAS['versions'] .= "# \"Show list of revisions\":".$url."?action=allrev\n";
						break;
					}
				}
				#$content[2] .= $out;
			}
	}
	return $content;

}

function collision_detection($current, $new){
	$out = "h1. Mid-air collision detected\n\n";
	
	$out .= "While you were editing that, someone else submitted an edit. Below are the differences between them:\n\n";
	
	$out .= diff(wordwrap(stripslashes($new['content'])),wordwrap(stripslashes($current['content'])));
	
	$out .= "\n\nWhatever you submit now will be the new copy, please fold in the previous person's information. \n\n";
	
	return $out;
}

?>
