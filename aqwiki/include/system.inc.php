<?PHP
/*******************************************************************************
	AqWiki - System
********************************************************************************

	System functions. Set up users, panics etc.

	$Id$

	$Log$
	Revision 1.10  2004/08/13 21:01:43  aquarion
	* Fixed diff to make it work with the new data abstraction layer

	Revision 1.9  2004/08/12 19:53:23  aquarion
	* Fixed config directive defaults
	* Fixed absolute URIs on RSS feeds
	
	Revision 1.8  2004/08/12 19:37:53  aquarion
	+ RSS output
	+ Detailed RSS output for Recent
	* Slight redesign of c/datasource (recent now outputs an array) to cope with above
	* Fixed Recent to cope with oneWiki format
	+ added Host configuation directive
		

*******************************************************************************/

$_FILES['system'] = '$Revision$';

function print_r_to_var($a) {
	ob_start();
	print_r($a);
	$b = ob_get_contents();
	ob_end_clean();
	return $b;
}

function panic($area, $error, $details){
	echo "<h2>$area</h2>$error<p>$details</p>";die();
}

function doAuth($requirement){
	global $dataSource;

	if ($_GET['action'] == "login"){
		$user = $dataSource->validateUser($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
	} elseif ($_COOKIE['me'] && $_COOKIE['password']){
		$user = $dataSource->validateUser($_COOKIE['me'], $_COOKIE['password']);
	} else {
		$user = $dataSource->validateUser($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
	}
	$notAuth = true;
	if (is_array($requirement)){
		$req_message = "member of the group allowed to access this";
		if (in_array(strtolower($user['username']), $requirement)){
			$notAuth = false;
		} elseif ($user) {
			$notAuth = "Your user (".$user['username'].") isn't allowed in";
		}
	} elseif($requirement == "register"){
		$req_message = "registered user";
		if ($user){
			$notAuth = false;
		} else {
			$notAuth = " Not a valid username/password";
		}
	
	} else {
		$req_message = "the person (".$requirement.") allowed to access this";
		if ($user['username'] == $requirement){
			$notAuth = false;
		} elseif ($user) {
			$notAuth = "Your user (".$user['username'].") isn't allowed to do this";
		} else {
			$notAuth = $user['username']." Not a valid user";
		}
	}
	if (!$notAuth){
		setcookie ("me", $_SERVER['PHP_AUTH_USER'],time()+3600000);
		setcookie ("password", $_SERVER['PHP_AUTH_PW'],time()+3600000);
	} else {
	  // Bad or no username/password.
	  // Send HTTP 401 error to make the
	  // browser prompt the user.
	  header("WWW-AUTHenticate: " .
			 "Basic realm=\"".$request[0]." Logon: " .
			 "Please log in as  ".$req_message .
			 " for access.\"");
		header("HTTP/1.0 401 Unauthorized");
		die($notAuth);
		 // Display message if user cancels dialog
	}
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

	if (is_array($items)){
		foreach ($items as $item){

			if ($item['link']){
				$out .= "\t* \"".$item['name']."\":";
				$out .= $item['link']."\n";
			} else {
				$out .= "\t* ".$item['name']."\n";
			}
			$i++ ;
		}
		$out .= "\n";
	} 
	return $out;
}

/* Differences between two arrays, 
	http://www.holomind.de/phpnet/diff.src.php
	Daniel Unterberger: d.u.phpnet@holomind.de
*/

include("diff.inc");

function formatline( $nr1, $nr2, $stat, &$value )  #change to $value if problems
{
	if ( trim( $value )=="" )
	{
		return "";
	}

	switch ($stat)
	{
		case "=":
			return "<li class=\"diff\">". $nr1. " : $nr2 : = ".htmlentities( $value )  ."</li>";
		break;

		case "+":
			return "<li class=\"diff added\">". $nr1. " : $nr2 : + ".htmlentities( $value )  ."</li>";
		break;

		case "-":
			return "<li class=\"diff removed\">". $nr1. " : $nr2 : - ".htmlentities( $value )  ."</li>";
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

// Case insenstive string search
//	In: Needle - String to search for
//		Haystack - Array to search in
//	
//	Out: 0 - Is not in array
//		 1 - Is in array key
//		 2 - is in Array values

function striarray($needle, $haystack){
	if (!is_array($haystack)){
		trigger_error ( "striarray 2nd argument should be an array", E_USER_ERROR);
	}
	if (is_array($needle)){
		trigger_error ( "striarray 1st argument shouldn't be an array", E_USER_ERROR);
	}
	foreach($haystack as $st => $raw){
		if ($st == $needle){
			return 1;
		} elseif ($raw == $needle){
			return 2;
		}
	}
	return 0;
}

function debug($message) {
	global $_CONFIG;
	if ($_CONFIG['debug']){
		global $DEBUG;
		$DEBUG[] = $message;
	}
}

function buildRSS($content){

	/* $content is an array containing:
			
			[0] Name of Wiki template
			[1] Title of page
			[2] Content of page
			[3] Author of page
			[4] Date of modification */

	global $_CONFIG;
	global $_EXTRAS;

	$content[2] = process($content[2],$content[0]);
	$content[4] = strtotime($content[4]);

	if ($_CONFIG['oneWiki']){
		$base = $_CONFIG['base'];
		$url = $_CONFIG['host'].$_CONFIG['base']."/".$content[1];
	} else {
		$base = $_CONFIG['base']."/".$content[0];
		$url = $_CONFIG['host'].$_CONFIG['base']."/".$content[0]."/".$content[1];
	}

	$out ="<?xml version=\"1.0\" encoding=\"utf-8\"?>\n"
		 ."<rss version=\"2.0\" \n"
		 ."  xmlns:dc=\"http://purl.org/dc/elements/1.1/\"\n"
#		 ."  xmlns:sy=\"http://purl.org/rss/1.0/modules/syndication/\"\n"
		 ."  xmlns:admin=\"http://webns.net/mvcb/\"\n"
		 ."  xmlns:rdf=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\"\n"
		 ."  xmlns:content=\"http://purl.org/rss/1.0/modules/content/\"\n"
#		 ."  xmlns:slash=\"http://purl.org/rss/1.0/modules/slash/\"\n"
#		 ."  xmlns:trackback=\"http://madskills.com/public/xml/rss/module/trackback/\"
		 .">\n"
		 ."<channel>\n"
		 ."<title>".$content[0]." - ".$content[1]."</title>\n"
		 ."<link>".$url."</link>\n"
		 ."<description>A Wiki Page</description>\n"
		 ."<dc:language>en-gb</dc:language>\n"
		 ."<dc:creator>".$content[3]."</dc:creator>\n"
		 ."<dc:rights>Copyright ".date("Y", $content[4])." ".$content[3]."</dc:rights>\n"
		 ."<dc:date>".date("Y-m-d\TH:i:00O", $content[4])."</dc:date>\n"

		 ."<admin:generatorAgent rdf:resource=\"".$_EXTRAS['versionURL']."\" />\n";
#		 ."<admin:errorReportsTo rdf:resource=\"mailto:".$_EP['admin']."\"/>\n"

	if ($_EXTRAS['data']){
		// Data is of form array(url, title, description, date);
		foreach ($_EXTRAS['data'] as $data){

			$desc = htmlspecialchars(break_string(strtr($data[2],"�","'"),400),ENT_NOQUOTES);
			$out .="<item>\n"
				 ."\t<title>".htmlspecialchars($data[1],ENT_NOQUOTES)."</title>\n"
				 ."\t<link>".$data[0]."</link>\n"
		#		 ."\t<comments>".$guid."</comments>\n"
				 ."\t<description>".$desc."</description>\n"
				 ."\t<guid isPermaLink=\"true\">".$data[1]."</guid>\n";

			$out .= "\t<content:encoded><![CDATA[".strtr($data[1],"�","'")."]]></content:encoded>\n"
				 ."\t<dc:date>".date("Y-m-d\TH:i:00O",$data[3])."</dc:date>\n";
			$out .= "</item>\n";
		}

	} else {
		$desc = htmlspecialchars(break_string(strtr($content[2],"�","'"),400),ENT_NOQUOTES);
		$out .="<item>\n"
			 ."\t<title>".htmlspecialchars($content[1],ENT_NOQUOTES)."</title>\n"
			 ."\t<link>".$url."</link>\n"
	#		 ."\t<comments>".$guid."</comments>\n"
			 ."\t<description>".$desc."</description>\n"
			 ."\t<guid isPermaLink=\"true\">".$url."</guid>\n";

		$out .= "\t<content:encoded><![CDATA[".strtr($content[2],"�","'")."]]></content:encoded>\n"
			 ."\t<dc:date>".date("Y-m-d\TH:i:00O",$content[4])."</dc:date>\n";
		$out .= "</item>\n";

	}
	$out .= "</channel>\n"
		."</rss>";

	#echo $out;

	return $out;
}

/* Break a string at the nearest space to $length */
function break_string($string_to_break, $length, $highlight=false) {

    /* Breaks a string at $length chars.*/
    $string_to_break = strip_tags($string_to_break);
    
    if(strlen($string_to_break)>($length + ($length/10))) 
    { 
        $string = substr($string_to_break,0,($length-1)); 
        if($string != " ") 
        { 
        $last_space = strrpos($string, " "); 
        $string = substr($string_to_break,0,$last_space); 
        return($string."..."); 
        } else { 
        $return = $string."..."; 
        } 
    } else { 
        $return = $string_to_break; 
    }
    if ($highlight){
        preg_replace("/($highlight)/i", "<span class=\"searchword\">\\1</span>",$return);
    }
    return $return; 
}

?>