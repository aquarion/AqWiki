<?PHP

$me = $_SERVER['PHP_SELF'];
$gkhs = array (

	"index" => array(
		"color" => "#575757",
		"name" => "AqWiki",
	),

	"docs" => array(
		"color" => "#000042",
		"name" => "Docs",
	),

	"people" => array(
		"color" => "#40230F",
		"name" => "People",
	),

	"sourceforge" => array(	
		"color" => "#425757",
		"name" => "Sourceforge",
	),
/*
	"dmz" => array(	
		"color" => "#420000",
		"name" => "DMZ",
		"owner" => "Aquarion",
		"email" => "webmaster@localhost"
	)*/

);

function filearray($file){
	$f = array();
		$f['len'] = strlen($file);
		$f['dot'] = strpos($file, ".");
		if ($f['dot'] == 0){
			$f['name'] = $file;
			$f['exi'] = "";
		} else {
			$f['name'] = substr ($file, 0, $f['dot']);
			$f['ext'] = substr($file, ($f['dot']+1), $f['len']);
		}
		return $f;
}

$tabwidth = 100/count($gkhs);

if (isset($_GET['h'])){
	$section = $_GET['h'];
} else {
	$section = "index";
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<title> [AqWiki] <?=$gkhs[$section]['name']?></title>
<meta name="Generator" content="EditPlus">
<meta name="Author" content="Nicholas 'Aquarion' Avenell">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

<style type="text/css">
body {
	background: white; 
	padding: 0; 
	margin: 0; 
	font-family: sans-serif;
}

a {
	color: #404040;
}

.tab {
	border: 1px solid white; 
	text-align: center;
	/* width: <?PHP echo $tabwidth ?>%; */
	color: white;
	border-right: none;
	padding-left: 1em;
	padding-right: 1em;
}

.tab a, .sectionbar a {
	color: #FFFFFF;
}

.sectionbar {
	text-align: center; 
	border-bottom: 1px solid white; 
	color: white; 
	background: <?PHP echo $gkhs[$section]['color'] ?> url('images/shade.png') top left;
}

.entry {
	border: 1px solid black;
	margin: 10px 10% 10px 10%;
}

.dateline {
	text-align: center; 
	border-bottom: 1px solid black; 
	color: white; 
	background: <?PHP echo $gkhs[$section]['color'] ?> url('images/shade.png') top left;
}

div.gkhs {
	background: #000000; 
	height: 100px; 
	text-align: center;
	vertical-align: middle; 
	color: #FFFFFF; 
	font-size: smaller
}

div.tabbar {
	background: #000000; 
	text-align: right;
	width: 100%;
}

.content {
	padding: 7px 7px 7px 7px;
	margin: auto;
	max-width: 80%;
}

a img {
	border: none;
}

<?PHP
foreach($gkhs as $thissec => $thisthing){
echo 'a.'.$thissec."Tab {\n";
echo "\t background: ".$thisthing['color'].";\n";

	if ($thissec == $section){
		echo "\tborder-bottom: none;\n";
	}
	if ($i == count($gkhs)){
		echo " \tborder-right: 1px solid white;\n";
	}
echo "}\n";
}
?>

</style>

</head>

<body>

<div class="gkhs"><br>
<img src="images/aqwiki.png" width="125" height="99" alt="AqWiki">
</div>
<div class="tabbar">
<?PHP
$i = 0;
foreach($gkhs as $thissec => $thisthing){
	$i++;
echo "<a class=\"tab ".$thissec."Tab\"  href=\"".$me."?h=".$thissec."\">"
	.$gkhs[$thissec]['name']
	."</a>";
}

echo "</div>"
	."<div class=\"sectionbar\">";

echo "| <a href=\"".$me."?h=".$section."\">Home</a>";
if (@is_dir($section)){
	$dir = opendir($section) or die('Could not open dir.');
	while($file = readdir($dir)){
		if(strval($file == "." || $file == ".." || $file == "home.php")) {}
		else {
			$f = filearray($file);
			echo " | <a href=\"".$me."?h=".$section."&amp;f=".$file."\">".ucwords($f['name'])."</a>";
		}
	}
}
echo " |";

echo "</div>\n<div class=\"content\">";

if (isset($_GET['f'])){
	$f = filearray($_GET['f']);
	if (file_exists($section."/".$_GET['f'])){
		echo "<h1>".$gkhs[$section]['name']." - ".ucwords($f['name'])."</h1>";
		if ($f['ext'] == "txt"){
			echo "<pre>\n";
			include($section."/".$_GET['f']);
			echo "</pre>\n";
		} else {
			include($section."/".$_GET['f']);
		}
	} else {
		echo "File Not Found. Go away";
	}
} else {
	echo "<h1>".$gkhs[$section]['name']."</h1>";
	if (file_exists($section."/home.php")){
		include($section."/home.php");	
	} else {
		echo "Fallback fell though. Panic!";
	}
}


echo "<hr>\n&copy; Nicholas Avenell 2004";
?>
<A href="http://sourceforge.net">
	<IMG src="http://sourceforge.net/sflogo.php?group_id=89793&amp;type=1" width="88" height="31" alt="SourceForge.net Logo" /></A>
<a href="http://sourceforge.net/donate/index.php?group_id=89793"><img src="http://images.sourceforge.net/images/project-support.jpg" width="88" height="32" alt="Support This Project" /> </a>

</div>


</body>
</html>
