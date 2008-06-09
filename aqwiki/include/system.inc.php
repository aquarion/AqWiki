<?PHP
/*******************************************************************************
	AqWiki - System
********************************************************************************
	
	(c) Nicholas 'Aquarion' Avenell 2004

	Released under the Artistic Licence, a copy of which is in docs/licence.txt
	or can be found at http://opensource.org/licenses/artistic-license.php

********************************************************************************

	System functions. Set up users, panics etc.

	$Id: system.inc.php,v 1.17 2006/10/10 09:32:47 aquarion Exp $

	$Log: system.inc.php,v $
	Revision 1.17  2006/10/10 09:32:47  aquarion
	* Development 2006
	
	Revision 1.16  2005/02/16 17:13:37  aquarion
	* Database fixes
	* New Textile Library support
	* Developement resumes, yay
	
	Revision 1.15  2004/10/22 13:56:08  aquarion
	* Fixed Stuff
	
	Revision 1.14  2004/09/29 10:19:34  aquarion
	* Use better textile if available
	* Fix links in RSS feeds
	
	Revision 1.13  2004/09/05 10:16:48  aquarion
	Moved versions and edit this page to templates
	
	Revision 1.12  2004/08/29 20:27:12  aquarion
	* Cleaning up auth system
	+ restrictNewPages configuration option
	+ Restrict usernames (Don't contain commas or be 'register')
	
	Revision 1.11  2004/08/14 11:09:42  aquarion
	+ Artistic Licence
	+ Actual Documentation (Shock)
	+ Config examples
	+ Install guide
	
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

$_FILES['system'] = '$Revision: 1.17 $';

function print_r_to_var($a) {
	ob_start();
	print_r($a);
	$b = ob_get_contents();
	ob_end_clean();
	return $b;
}

function panic($area, $error, $details){

echo <<<EOW
<style type="text/css">
#panic {
	border: 1px solid black;
	width: 500px;
	background: url("http://imperial.istic.net/static/icons/dialog-error.png") no-repeat 10px center;
	margin-bottom: 20px;
	font-family: sans-serif;
	position: relitive;
}

#panic h2 {
	margin: 0;
	border-bottom: 1px solid black;
	font-size: 14pt;
	color: white;
	background: #00007F;
}

#panic p {
	padding-left: 75px;
}
</style>
EOW;

	$backtraceR = debug_backtrace();
	#array_shift($backtraceR);

	$backtrace = "<table>";

	foreach($backtraceR as $trace){

		if (isset($trace['class'])){
			$coords = basename($trace['file']).":".$trace['line']." ".$trace['class']."->".$trace['function']."()";
		} else {
			$coords = basename($trace['file']).":".$trace['line']." ".$trace['function']."()";
		}

		$backtrace .= "<tr> <td>".$trace['function']."</td>"
			."<td>".$coords."</td>"
			#."<td>".print_r($trace,1)."</td>"
			."</tr>";
	}
	$backtrace .= "</table>";

	echo "<div id=\"panic\"><h2>$area</h2><p>AqWiki has encountered an absolutely terminal fatal error-type-thing and has decided to give up entirely and go home. Sorry about that. Normal people should stop reading about here, because the bit under this is for the admin to either understand immediately and go fix, or alternatively put into <a href=\"http://www.google.com/?q=".urlencode($error)."\">Google</a> to find out what the hell's up with the stupid thing.</p><p><b>$error</b><br/>$details</p>$backtrace</div>";die();
}

function checkAuth($action){
	global $_EXTRAS;
	switch($action){
		case "edit":
			if (isset($_EXTRAS['reqEdit'])){
				if ($_EXTRAS['reqEdit'] == $_EXTRAS['me']){
					return true;
				} elseif ( ($_EXTRAS['reqEdit'] == 'register') && isset($_SERVER['PHP_AUTH_USER'])) {
					return true;
				}
			} elseif(isset($_EXTRAS['reqEdits'])) {
				if (in_array($_EXTRAS['me'],explode(",",$_EXTRAS['reqEdits']))){
					return true;
				}
			} else {
				return true;
			}
			
		case "enter":
			if (isset($_EXTRAS['reqUser'])){
				if ($_EXTRAS['reqUser'] == $_EXTRAS['me']){
					return true;
				} elseif ( ($_EXTRAS['reqUser'] == 'register') && isset($_SERVER['PHP_AUTH_USER'])) {
					return true;
				}
			} elseif(isset($_EXTRAS['reqUsers'])) {
				if (in_array($_EXTRAS['me'],explode(",",$_EXTRAS['reqUsers']))){
					return true;
				}
			} else {
				return true;
			}
	}
	return false;
}

function doAuth($requirement, $action = "access this"){
	global $dataSource;

	debug("You have to be: ".$requirement." to ".$action);

	if ($_GET['action'] == "login"){
		debug("Checking request for login");
		if(isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])){
			$user = $dataSource->validateUser($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
		}
	} elseif (isset($_COOKIE['me']) && isset($_COOKIE['password'])){
		debug("Using cookies");
		$user = $dataSource->validateUser($_COOKIE['me'], $_COOKIE['password']);
	} else {
		debug("Using http auth");
		if(isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])){
			$user = $dataSource->validateUser($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
		}
	}


	$notAuth = true;
	if (strstr($requirement,',')){
		$requirement = explode(",",strtolower($requirement));
		$req_message = "member of the group allowed to ".$action;
		if (in_array(strtolower($user['username']), $requirement)){
			$notAuth = false;
		} elseif ($user) {
			$notAuth = "Your user (".$user['username'].") isn't allowed to ".$action;
		}
	} elseif($requirement == "register"){
		$req_message = "registered user";
		if ($user){
			$notAuth = false;
		} else {
			$notAuth = " Not a valid username/password";
		}
	
	} else {
		$req_message = "the person (".$requirement.") allowed to ".$action;;
		if ($user['username'] == $requirement){
			$notAuth = false;
		} elseif ($user) {
			$notAuth = "Your user (".$user['username'].") isn't allowed to ".$action;
		} else {
			$notAuth = $user['username']." Not a valid user";
		}
	}
	if (!$notAuth){
		setcookie ("me", $user['username'],time()+3600000);
		setcookie ("password", $user['password'],time()+3600000);
		
		return $user;
	} else {
	  // Bad or no username/password.
	  // Send HTTP 401 error to make the
	  // browser prompt the user.
	  header("WWW-AUTHENTICATE: " .
			 "Basic realm=\"AqWiki Logon: " .
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
				$out .= "* \"".$item['name']."\":";
				$out .= $item['link']."\n";
			} else {
				$out .= "* ".$item['name']."\n";
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

			$desc = htmlspecialchars(break_string(strtr($data[2],"’","'"),400),ENT_NOQUOTES);
			$out .="<item>\n"
				 ."\t<title>".htmlspecialchars($data[1],ENT_NOQUOTES)."</title>\n"
				 ."\t<link>".$_CONFIG['host'].$_CONFIG['base'].$data[0]."</link>\n"
		#		 ."\t<comments>".$guid."</comments>\n"
				 ."\t<description>".$desc."</description>\n"
				 ."\t<guid isPermaLink=\"true\">".$_CONFIG['host'].$_CONFIG['base'].$data[0]."</guid>\n";

			$out .= "\t<content:encoded><![CDATA[".strtr($data[2],"’","'")."]]></content:encoded>\n"
				 ."\t<dc:date>".date("Y-m-d\TH:i:00O",$data[3])."</dc:date>\n";
			$out .= "</item>\n";
		}

	} else {
		$desc = htmlspecialchars(break_string(strtr($content[2],"’","'"),400),ENT_NOQUOTES);
		$out .="<item>\n"
			 ."\t<title>".htmlspecialchars($content[1],ENT_NOQUOTES)."</title>\n"
			 ."\t<link>".$url."</link>\n"
	#		 ."\t<comments>".$guid."</comments>\n"
			 ."\t<description>".$desc."</description>\n"
			 ."\t<guid isPermaLink=\"true\">".$url."</guid>\n";

		$out .= "\t<content:encoded><![CDATA[".strtr($content[2],"’","'")."]]></content:encoded>\n"
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

function parse_ini_str($Str,$ProcessSections = TRUE) {
   $Section = NULL;
   $Data = array();
   if ($Temp = strtok($Str,"\r\n")) {
     do {
         switch ($Temp{0}) {
           case ';':
           case '#':
               break;
           case '[':
               if (!$ProcessSections) {
                 break;
               }
               $Pos = strpos($Temp,'[');
               $Section = substr($Temp,$Pos+1,strpos($Temp,']',$Pos)-1);
               $Data[$Section] = array();
               break;
         default:
           $Pos = strpos($Temp,'=');
           if ($Pos === FALSE) {
               break;
           }
		   $Data[$Section];

		   $value = trim(substr($Temp,$Pos+1),' "');
		   $field = trim(substr($Temp,0,$Pos));

			$true = array("true", "yes", "ja", "True", "Yes", "Y", "y");
			$false = array("false", "no", "nein", "False", "No", "N", "n"); 

		   if (in_array($value, $false)){
			  # echo "Set $Section $field false<br>";
				$value = false;
		   } elseif (in_array($value, $true)) {
				$value = true;
		   }

           if ($ProcessSections) {
               $Data[$Section][$field] = $value;
           }
           else {
               $Data[$field] = $value;
           }
           break;
         }
     } while ($Temp = strtok("\r\n"));
   }
   return $Data;
}

function sendAdminEmail($subject, $content){
	
	global $_CONFIG;
	global $_EXTRAS;
	
	$to      = $_CONFIG['admin_email'];
	$subject = 'AqWiki '.$subject;
	$message = $content.print_r($_EXTRAS,1);
	$headers = 'From: webmaster@example.com' . "\r\n" .
	    'X-Mailer: AqWiki';

	mail($to, $subject, $message, $headers);
}

?>
