<?PHP
/*******************************************************************************
	AqWiki
********************************************************************************

	Start page. Set up variables and libraries, generate and display the page.

	$Id$

	$Log$
	Revision 1.10  2004/08/12 19:37:52  aquarion
	+ RSS output
	+ Detailed RSS output for Recent
	* Slight redesign of c/datasource (recent now outputs an array) to cope with above
	* Fixed Recent to cope with oneWiki format
	+ added Host configuation directive

	Revision 1.9  2004/07/05 20:29:05  aquarion
	* Lets try actually using _real_ CVS keywords, not words I guess at this time
	+ [[AQWIKI]] template tag
	+ Default template finally exists! Sing yay!
	* Fixed Non-oneWiki [[BASE]] by adding $_EXTRAS['wiki']
	* Minor fixen
	

*******************************************************************************/

require_once 'include/system.inc.php'; // How to interact with the system
require_once 'include/wiki.inc.php'; // How to display Wiki pages
require_once 'include/elements.inc.php'; // Things to put in Wiki Pages
require_once 'include/mysql4.class.php'; // How to store your world.

/* Use this :*/

require_once 'include/textile.inc'; // How to format your world.


/* Use this if you have it:*/

/*require_once 'include/classTextile.php'; // How to format your world.

function textile($text, $lite=''){
	$textile = new Textile;
	return $textile->TextileThis($text);
}*/

$_FILES['index'] = '$Revision$';

$DEBUG = array();

$_CONFIG = array(
	'db' => false, // Databasy goodness
	'base' => '',
	'host' => "http://".$_SERVER['SERVER_NAME']
);


$_CONFIG = parse_ini_file('etc/aqwiki.ini', true);

#$url = preg_replace("/".preg_quote($_CONFIG['base'],"/")."/","",$HTTP_SERVER_VARS['REDIRECT_URL']);
$url = parse_url($HTTP_SERVER_VARS['REDIRECT_URL']); // Much better :-)

if ($_CONFIG['base']){
	$url['path'] = preg_replace("/".preg_quote($_CONFIG['base'],"/")."/","",$url['path']);
}

$url['path'] = trim($url['path'],"/");

$request = explode('/',$url['path']);

#print_r($request);

if ($_CONFIG['oneWiki']){
	array_unshift($request,$_CONFIG['oneWiki']);
}

/* Send all get & post variables to an array called "Extras", which gets used lots. It's also a way to get at post, get and internal variables from inside macros, also, vice versa */

$_EXTRAS = $_REQUEST;

$_EXTRAS['version'] = "0.0a";

$_EXTRAS['versionURL'] = 'http://aqwiki.sf.net/release?v='.$_EXTRAS['version'];

$_EXTRAS['versionString'] = '<A HREF="http://aqwiki.sf.net">AqWiki</A> '
	.'<A HREF="'.$_EXTRAS['versionURL'].'">v'.$_EXTRAS['version'].'</A>';


/* Wiki configuation files are in etc/ with the template data */

if(file_exists('etc/'.$request[0].'.rc.php')){
	require_once('etc/'.$request[0].'.rc.php');
}


/* At this point we have everything we need */

$dataSource = new mysql4($_CONFIG['db']);
$dataSource->wiki = $request[0];
$_EXTRAS['wiki'] = $request[0];

if ($_GET['action'] == "relogin") {
	$_SERVER['PHP_AUTH_USER'] = false;
	$_SERVER['PHP_AUTH_PW'] = false;
	$url = parse_url($_SERVER['REQUEST_URI']);
	$url['query'] = "?action=login";
	header("location: ".implode("", $url));
}

if ($_GET['action'] == "login"){
	doAuth("register");
}

if ($_COOKIE['me'] && $_COOKIE['password']){
	$user = $dataSource->validateUser($_COOKIE['me'],$_COOKIE['password']);
	if (!$user){
		header("location: ".$_SERVER['REQUEST_URI']."?action=login");
	}
	$_EXTRAS['id'] = $user['id'];
}


if ($_SERVER['PHP_AUTH_USER']){
	$_EXTRAS['me'] = $_SERVER['PHP_AUTH_USER'];
	$_EXTRAS['auth'] = "username/password";
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
	$_EXTRAS['current'] = $request[1];
	debug("User page ".$match);
} elseif ($request[0]) {
	// get Wiki Front Page
	if ($request[1]){
		$_EXTRAS['current'] = $request[1];
		debug("Wiki page ".$request[1]);
	} else {
		$_EXTRAS['current'] = "frontPage";
		debug("User page blank, going for frontPage");
	}
	// get Wiki Entry
	if ($_CONFIG['newwikis'] != true){
		debug("No new Wikis allowed, checking");

		#if ( array_key_exists($request[0], getWikis(true) ) ){
		if ( striarray($request[0], $dataSource->listOfWikis(true) ) ){
			$content = wiki($request[0],$_EXTRAS['current']);
			debug("...that's fine.");
		} else {
			debug("...not allowing");
			$content = array(
				"page",
				"Not a valid Wiki",
				"That (".$request[0].") not a Wiki I am aware of, and current config forbids creation of arbitrary new wikis",
				"Aquarion (Admin)",
				date("r"));
		}
	} else {
		debug("Loading wikipage ".$_EXTRAS['current']);
		$content = wiki($request[0],$_EXTRAS['current']);
	}
} else {
	debug("Listing wikis");
	$listOfwikis = $dataSource->listOfWikis();

	foreach ($listOfwikis as $row) {
		$out .= "# <a href=\"".$row[0]."\">".$row[0]."</a>, ".$row[1]." pages\n";
	}
	$content = array(
		"page",
		"Index of Wikis",
		$out,
		"Aquarion (Admin)",
		date("r"));
}


if($_EXTRAS['reqAuth']){
	debug("Requiring auth ".$_EXTRAS['reqAuth']);
	switch ($_EXTRAS['reqAuth']){
		case "user":
			doAuth($_EXTRAS['reqUser']);
			break;

		case "group":
			doAuth(explode(",",$_EXTRAS['reqUsers']));
			break;

		case "register":
			doAuth("register");
			break;
	}
}

echo page($content);


debug("Game over, No high score.");
?>