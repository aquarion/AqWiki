<?PHP
/*******************************************************************************
	AqWiki - System
********************************************************************************

	System functions. Set up users, panics etc.

	$Id$

	$log$

*******************************************************************************/

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


function validate_user($username, $password){
	$q = "select * from users where username=\"".$username
		."\" and password = \"".$password."\"";
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
			username => $username, 
			email => $row['email'],
			url => $row['url']
		);
	}
}

function getContent($wiki, $article){
	global $db;
	$sql = "select revision.*"
				."from wikipage, revision "
				."where wikipage.wiki = \"$wiki\" and wikipage.name = \"$article\" and wikipage.page = revision.page "
				."order by revision.created desc limit 1";

	$result = $db->query($sql);
	if (DB::isError($result)) {
		panic("database",$result->getMessage(), $sql);
	}
	if ($result->numRows() == 0){
		return false;
	} else {
		$row = $result->fetchRow(DB_FETCHMODE_ASSOC);
		return $row['content'];
	}
}



function doAuth($requirement){
	$user = validate_user($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
	$auth = false;
	if (is_array($requirement)){
		if (in_array($user['username'], $requirement)){
			$auth = true;
		}
	} elseif($requirement = "register"){
		if ($user){
			$auth = true;
		}
	
	} else {
		if ($user['username'] == $requirement){
			$auth = true;
		}
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
			 "Enter owner's username and password " .$_EXTRAS['reqUser'] .
			 " for access.\"");
		header("HTTP/1.0 401 Unauthorized");
		print_r($user);
		die("Really Not authorised");
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
function arr_diff( $f1, $f2 ,$show_equal=1 )
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
			$out .= $b;
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
?>