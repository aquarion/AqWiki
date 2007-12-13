<?PHP


class addReadme extends AqWikiMacro {


	function append(){
		
		$dataSource = $this->data;
	
		if(!isset($_GET['wikipage'])){
			$wikipage = "readme";
		} else {
			$wikipage = $_GET['wikipage'];
		}
	
	$readme = $dataSource->getContent($wikipage);
	
	if ($_GET['url'] && $_GET['title']) {
		$readme .= '* "'.$_GET['title'].'":'.$_GET['url']." ".date("Y-m-d H:i")."\n";
		$readme = $dataSource->post($wikipage, $readme, "Added automatically");
		header("location: ".$_GET['url']);
	} else {
		return "You're going to need more than that, buddy";
	}
	
	}
	/*
	javascript:location.href='http://narciss.istic.net/addToReadme?url='+encodeURIComponent(location.href)+'&title='+encodeURIComponent(document.title)
	*/
	
}
?>