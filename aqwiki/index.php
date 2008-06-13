<?PHP
/*******************************************************************************
	AqWiki
********************************************************************************
	
	(c) Nicholas 'Aquarion' Avenell 2004

	Released under the Artistic Licence, a copy of which is in docs/licence.txt
	or can be found at http://opensource.org/licenses/artistic-license.php

********************************************************************************

	Start page. Set up variables and libraries, generate and display the page.

	$Id: index.php,v 1.16 2006/10/10 09:32:47 aquarion Exp $

	$Log: index.php,v $
	Revision 1.16  2006/10/10 09:32:47  aquarion
	* Development 2006
	
	Revision 1.15  2004/09/29 10:19:34  aquarion
	* Use better textile if available
	* Fix links in RSS feeds
	
	Revision 1.14  2004/08/30 01:25:59  aquarion
	+ Added 'stripDirectories' option, because mod_rewrite doesn't like me much
	* Fixed non-mysql4 search. We now work with mysql 4.0! and probably 3! Woo!
	+ Added 'newuser' to the abstracted data class. No idea how I missed it, tbh.
	
	Revision 1.13  2004/08/29 20:27:11  aquarion
	* Cleaning up auth system
	+ restrictNewPages configuration option
	+ Restrict usernames (Don't contain commas or be 'register')
	
	Revision 1.12  2004/08/14 11:09:42  aquarion
	+ Artistic Licence
	+ Actual Documentation (Shock)
	+ Config examples
	+ Install guide
	
	Revision 1.11  2004/08/12 19:53:23  aquarion
	* Fixed config directive defaults
	* Fixed absolute URIs on RSS feeds
	
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

ini_set("include_path", ".:/usr/share/php/:/usr/share/pear/");

#if(get_magic_quotes_runtime == 0){
#	define(MAGICQUOTES, False);
#} else {
	define( "MAGICQUOTES", True);
#}

require_once 'include/system.inc.php'; // How to interact with the system
require_once 'include/wiki.inc.php'; // How to display Wiki pages
require_once 'include/elements.inc.php'; // Things to put in Wiki Pages
require_once 'include/mysql4.class.php'; // How to store your world.
require_once 'include/AqWikiMacro.class.php'; // How to store your world.


$_FILES['index'] = '$Revision: 1.16 $';

/* Use this :*/

$DEBUG = array();

#if (file_exists('include/textilephp/Textile.php')){
#	# From http://jimandlissa.com/project/textilephp
#	$DEBUG[] = "Using Jim & Lissa's Textile";
#	require_once 'include/textilephp/Textile.php';
 #       
#	function textile($text, $lite=''){
 #               $textile = new Textile;
  #              return $textile->process($text);
#        }
#
#} else
if (file_exists('include/classTextile.php')){
	require_once 'include/classTextile.php'; // How to format your world.
	function textile($text, $lite=''){
		$textile = new Textile;
		return $textile->TextileThis($text);
	}
	$DEBUG[] = "Using better Textile";
} else {
	require_once 'include/textile.inc'; // How to format your world.
}

$_FILES['index'] = '$Revision: 1.16 $';

$_CONFIG = array(
	'db' => false, // Databasy goodness
	'base' => '',
	'host' => "http://".$_SERVER['SERVER_NAME'], // 
	'restrictNewPages' => false,
	'reservedUsers' => array("register")
);


$_CONFIG = array_merge($_CONFIG, parse_ini_file('etc/aqwiki.ini', true));

if (file_exists('/etc/aqwiki.ini')){
	debug("Including global config");
	$_CONFIG = array_merge($_CONFIG, parse_ini_file('/etc/aqwiki.ini', true));
}

#$url = preg_replace("/".preg_quote($_CONFIG['base'],"/")."/","",$HTTP_SERVER_VARS['REDIRECT_URL']);
$url = parse_url($_SERVER['REDIRECT_URL']); // Much better :-)

if ($_SERVER['SERVER_PORT']) { // We're a website
	debug("We're a website");

	define("MODE", "WEB");

	if ($_CONFIG['base']){
		$url['path'] = preg_replace("/".preg_quote($_CONFIG['base'],"/")."/","",$url['path']);
	}
	
	$url['path'] = trim($url['path'],"/");
	
	$request = explode('/',$url['path']);

	
	if ($_CONFIG['stripFromStart']){ // Strip this number of directories from the start
		$request = array_slice ($request, $_CONFIG['stripFromStart']);
	}
		
	if ($_CONFIG['oneWiki']){
		array_unshift($request,$_CONFIG['oneWiki']);
	}

} else { // We're a script
	$_REQUEST = array();
	$request = array($argv[1], $argv[2]);
	define("MODE", "SHELL");
}

/* Send all get & post variables to an array called "Extras", which gets used lots. It's also a way to get at post, get and internal variables from inside macros, also, vice versa */

$_EXTRAS = $_REQUEST;

$EXTRAS['noProcess'] = array();

$_EXTRAS['argv'] = $request;
$_EXTRAS['head'] = '';

$_EXTRAS['version'] = "0.0b-SVN";

$_EXTRAS['versionURL'] = 'http://aqwiki.sf.net/release?v='.$_EXTRAS['version'];

$_EXTRAS['versionString'] = '<A HREF="http://aqwiki.sf.net">AqWiki</A> '
	.'<A HREF="'.$_EXTRAS['versionURL'].'">v'.$_EXTRAS['version'].'</A>';


/* Wiki configuation files are in etc/ with the template data */

if(file_exists('etc/'.$request[0].'.rc.php')){
	require_once('etc/'.$request[0].'.rc.php');
}


/* At this point we have everything we need */

$dataSource = new mysql($_CONFIG['db']);
$dataSource->wiki = $request[0];
$_EXTRAS['wiki'] = $request[0];

if (isset($_GET['action']) && $_GET['action'] == "relogin") {
	$_SERVER['PHP_AUTH_USER'] = false;
	$_SERVER['PHP_AUTH_PW'] = false;
	$url = parse_url($_SERVER['REQUEST_URI']);
	$url['query'] = "?action=login";
	header("location: ".implode("", $url));
}

if (isset($_GET['action']) && $_GET['action'] == "login"){
	doAuth("register");
}

if (isset($_COOKIE['me']) && isset($_COOKIE['password'])){
	$user = $dataSource->validateUser($_COOKIE['me'],$_COOKIE['password']);
	if (!$user){
		header("location: ".$_SERVER['REQUEST_URI']."?action=login");
	}
	$_EXTRAS['id'] = $user['id'];
}


if (MODE =="SHELL"){
							
} else {
	if (isset($_SERVER['PHP_AUTH_USER'])){
		$user = $dataSource->userExists($_SERVER['PHP_AUTH_USER']);
		$_EXTRAS['me'] = $user['username'];
		$_EXTRAS['auth'] = "username/password";
		$_EXTRAS['id'] = $user['id'];
	} elseif (isset($_COOKIE['me'])){
		$_EXTRAS['me'] = $_COOKIE['me'];
		$_EXTRAS['auth'] = "cookie";
	} else {
		$headers = apache_request_headers();
		if (isset($headers['X-Forwarded-For'])){
			$_EXTRAS['me'] = $headers['X-Forwarded-For'];
		} else {
			$_EXTRAS['me'] = $_SERVER['REMOTE_ADDR'];
		}
		unset($headers);
		$_EXTRAS['auth'] = "host";
	}
}

if (preg_match("/^~(.*)$/",$request[1],$match)) {
	$content = userpage($request[0],$match[1]);
	$_EXTRAS['current'] = substr($request[1],1);
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


if(isset($_EXTRAS['reqUser'])){
	debug("Requiring auth ".$_EXTRAS['reqAuth']);
	doAuth($_EXTRAS['reqUser'], "enter");
}

if(isset($_EXTRAS['reqAuth'])){
	debug("Requiring auth ".$_EXTRAS['reqAuth']);
	doAuth($_EXTRAS['reqAuth'], "enter");
}

debug("Memory Track: ".number_format(memory_get_usage()));

echo page($content);


debug("Game over, No high score.");
?>
