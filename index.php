<?PHP
/*******************************************************************************
	AqWiki
********************************************************************************

	Start page. Set up variables and libraries, generate and display the page.

	$Id$

	$log$

*******************************************************************************/
require_once 'DB.php';
require_once 'include/system.inc.php'; // How to interact with the system
require_once 'include/wiki.inc.php'; // How to display Wiki pages
require_once 'include/elements.inc.php'; // Things to put in Wiki Pages
require_once 'include/textile.inc'; // How to format your world.


$_CONFIG = array(
	'db' => false, // Databasy goodness
	'base' => ''
);

$_CONFIG = parse_ini_file('etc/aqwiki.ini', true);

#$url = preg_replace("/".preg_quote($_CONFIG['base'],"/")."/","",$HTTP_SERVER_VARS['REDIRECT_URL']);
$url = parse_url($HTTP_SERVER_VARS['REDIRECT_URL']); // Much better :-)

if ($_CONFIG['base']){
	$url['path'] = preg_replace("/".preg_quote($_CONFIG['base'],"/")."/","",$url['path']);
}

$url['path'] = trim($url['path'],"/");

$request = explode('/',$url['path']);

if ($_CONFIG['oneWiki']){
	array_unshift($request,$_CONFIG['oneWiki']);
}

/* Send all get & post variables to an array called "Extras", which gets used lots. It's also a way to get at post, get and internal variables from inside macros, also, vice versa */

$_EXTRAS = $_REQUEST;

/* Wiki configuation files are in etc/ with the template data */

if(file_exists('etc/'.$request[0].'.rc.php')){
	require_once('etc/'.$request[0].'.rc.php');
}


/* At this point we have everything we need */


/*		CONNECT TO THE DATABASE			*/


// DB::connect will return a PEAR DB object on success
// or an PEAR DB Error object on error

$db = DB::connect($_CONFIG['db']);

#mysql_connect("localhost", "wiki", "wild") || die(mysql_error());


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
	$_EXTRAS['current'] = $request[1];
} elseif ($request[0]) {
	// get Wiki Front Page
	if ($request[1]){
		$_EXTRAS['current'] = $request[1];
	} else {
		$_EXTRAS['current'] = "frontPage";
	}
	// get Wiki Entry
	if ($_CONFIG['newwikis'] != true){

		if ( in_array($request[0], getWikis(true) ) ){
			$content = wiki($request[0],$_EXTRAS['current']);
		} else {
			$content = array(
				"page",
				"Not a valid Wiki",
				"That (".$request[0].") not a Wiki I am aware of, and current config forbids creation of arbitrary new wikis",
				"Aquarion (Admin)",
				date("r"));
		}
	} else {
		$content = wiki($request[0],$_EXTRAS['current']);
	}
} else {
	$listOfwikis = getWikis();
	while ($row = $wikis) {
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
	switch ($_EXTRAS['reqAuth']){
		case "user":
			doAuth($_EXTRAS['reqUser']);
			break;

		case "group":
			doAuth($_EXTRAS['reqUsers']);
			break;

		case "register":
			doAuth("register");
			break;
	}
}


echo page($content);


// close conection
$db->disconnect();
?>

