<?PHP
/*******************************************************************************
	MySQL4 data access class
********************************************************************************
	
	(c) Nicholas 'Aquarion' Avenell 2004

	Released under the Artistic Licence, a copy of which is in docs/licence.txt
	or can be found at http://opensource.org/licenses/artistic-license.php

********************************************************************************

	Gives AqWiki MySQL support

	$Id$

	$Log$
	Revision 1.9  2004/08/29 17:25:08  aquarion
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

	Revision 1.8  2004/08/14 11:09:42  aquarion
	+ Artistic Licence
	+ Actual Documentation (Shock)
	+ Config examples
	+ Install guide
	
	Revision 1.7  2004/08/13 21:01:43  aquarion
	* Fixed diff to make it work with the new data abstraction layer
	
	Revision 1.6  2004/08/12 19:37:53  aquarion
	+ RSS output
	+ Detailed RSS output for Recent
	* Slight redesign of c/datasource (recent now outputs an array) to cope with above
	* Fixed Recent to cope with oneWiki format
	+ added Host configuation directive
	
	Revision 1.5  2004/07/05 20:29:05  aquarion
	* Lets try actually using _real_ CVS keywords, not words I guess at this time
	+ [[AQWIKI]] template tag
	+ Default template finally exists! Sing yay!
	* Fixed Non-oneWiki [[BASE]] by adding $_EXTRAS['wiki']
	* Minor fixen
	
	Revision 1.4  2004/07/05 18:09:46  aquarion
	+ clash repair, no content changed.
	
	Revision 1.3  2004/07/03 21:35:37  aquarion
	* Updated SQL output
	* Fixed Search function (Thanks to Tom Pike)
	* Added version tracking part 1/3
	
	Revision 1.2  2004/06/25 15:07:13  aquarion
	* various fixes resulting from the abstraction of the data layer.
	
	Revision 1.1  2004/06/25 12:54:25  aquarion
	All change, apparently. All I've done is abstracted the data layer a bit, why every file's changed I'm not quite sure...
	

*******************************************************************************/

$_FILES['system'] = '$Revision$';

class dataSource {
	var $db; // Database connection link
	var $wiki; // wiki we're currently viewing


	//function: Constructor(connectiondetails) - Connect to the database

	//function: newUser(username, name, password, email) - create a new user
	//function: validateUser(username, password) - Check a username and password match
	//function: unique(field, data, existingData) - Check a field is unique

	//function: listOfWikis(quicklist?) - return a list of valid wikis with the db.
	//function: listOfPages();

//function: pageExists(page) - does a wiki already have this page?
//function: getPage(page, revision) - return the contents of a wikiPage (and previous revisions)
	//function: getContent(page) - just return the content of a given page
	//function: diff(from, to?) 
	//function: post(); Add something to the wiki

	//function: nearby(page);
	//function: recent();
	//function: search();
	//function: author();


}

class pearDB extends dataSource {
	
	//function: Constructor(connectiondetails) - Connect to the database
	function pearDB($connectionParams) { // Constructor
		global $_CONFIG;
		
		require_once 'DB.php';
		/*		CONNECT TO THE DATABASE			*/


		// DB::connect will return a PEAR DB object on success
		// or an PEAR DB Error object on error

		$this->db = DB::connect($_CONFIG['db']);

		#mysql_connect("localhost", "wiki", "wild") || die(mysql_error());


		// With DB::isError you can differentiate between an error or
		// a valid connection.
		if (DB::isError($db)) {
			panic("MySQL", "Error",$db->getMessage());
		}
	}

	function query($sql){
		$db = $this->db;
		$result = $db->query($sql);
		if (DB::isError($result)) {
			panic("database",$result->getMessage(), $sql);
		}
		return $result;
	}
	
	//function: newUser(username, name, password, email) - create a new user

	//function: validateUser(username, password) - Check a username and password match
	function validateUser($username, $password){
		$q = "select * from users where username=\"".$username
			."\" and password = \"".$password."\"";
		$result = $this->query($q);
		if ($result->numRows() == 0){
			return false;
		} else {
			$row = $result->fetchRow(DB_FETCHMODE_ASSOC);
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
	} // end validateUser


	//function: unique(field, data, existingData) - Check a field is unique
	function unique($table, $field, $value, $changeID = false){
		global $db;
		if (is_String($value)){
			$value = "\"$value\"";
		}
		$sql = "select id"
					." from ".$table
					." where ".$field." = ".$value;

		$result = $this->query($sql);
		if (DB::isError($result)) {
			panic("database",$result->getMessage(), $sql);
		}
		if ($result->numRows() == 0){
			return true;
		} else {
			return false;
		}
		
	}

	//function: listOfWikis(quicklist?) - return a list of valid wikis with the db.
	function listOfWikis($quickList = false){
		$wikis = array();

		$sql = "select wiki, count(page) as count from wikipage group by wiki";
		$result = $this->db->query($sql);
		while ($row = $result->fetchRow()) {
			if ($quickList){
				$wikis[] = $row[0];
			} else {
				$wikis[] = $row;
			}
		}

		return $wikis;
	}


	//function: listOfPages();
	function listOfPages(){
	$sql = "SELECT wikipage.page, name, wikipage.created, max(revision.created) as revised, revision.revision"
		." FROM revision"
		." LEFT JOIN wikipage ON wikipage.page = revision.page"
		." WHERE wiki = \"".$this->wiki."\""
		." GROUP BY wikipage.page"
		." ORDER BY wikipage.name";
		$result = $this->query($sql);
		while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC)){
			$return[] = $row;
		}
		return $return;
	}

	//function: pageExists(page) - does a wiki already have this page?
	function pageExists($article){
		$sql = $this->getSQL($article);
		$result = $this->query($sql);
		if ($result->numRows() == 0){
			return false;
		} else {
			$row = $result->fetchRow(DB_FETCHMODE_ASSOC);
			return $row['page']; 
		}
	}


	//function: getPage(page, revision) - return the contents of a wikiPage (and previous revisions)
	function getPage($article){
		$return = array();

		$sql = $this->getSQL($article);

		$result=$this->query($sql);

		while	($row = $result->fetchRow(DB_FETCHMODE_ASSOC)){
			$return[$row['revision']] = $row;
		}

		return $return;
	}

	//function: getContent(page) - just return the content of a given page
	function getContent($article){
		$sql = "select revision.*"
					."from wikipage, revision "
					."where wikipage.wiki = \"".$this->wiki."\" and wikipage.name = \"$article\" and wikipage.page = revision.page "
					."order by revision.created desc limit 1";

		$result = $this->query($sql);
		if ($result->numRows() == 0){
			return false;
		} else {
			$row = $result->fetchRow(DB_FETCHMODE_ASSOC);
			return $row['content'];
		}
	} // end getContent


	//function: diff(from, to?) 
	function diff($article, $from, $to){
		if ($from){
			$sql = $this->getSQL($article, " and revision = ".$from);
			$result = $this->query($sql);
			$from = $result->fetchRow(DB_FETCHMODE_ASSOC);
		} else {
			die("From not supplied");
		}
		if ($to){
			$sql = $this->getSQL($article, " and revision = ".$to);
			$result = $this->query($sql);
			$to = $result->fetchRow(DB_FETCHMODE_ASSOC);
		} else {
			$sql = $this->getSQL($article);
			$result = $this->query($sql);
			$to = $result->fetchRow(DB_FETCHMODE_ASSOC);
		}

		if ($to['creator'] == $from['creator']){
			$author = $from['creator'];
		} else {
			$author = $from['creator'] . " &amp; ".$to['creator'];
		}
		$content[3] = $author;
		$content[4] = date("r",$to['created']);
		#$_EXTRAS['textarea'] = arr_diff(explode("\n", wordwrap(stripslashes($from['content']))),explode("\n", wordwrap(stripslashes($to['content']))));
		$out = "This is displaying the changes from ".date("Y-m-d h:m",$from['created'])." to ".date("Y-m-d h:m",$to['created']);
		return $out.diff(wordwrap(stripslashes($from['content'])),wordwrap(stripslashes($to['content'])));
	}

	//function: post(); Add something to the wiki

	//function: nearby(page);
	function nearby($page){
		$sql = "SELECT wikipage.page, name, wikipage.created, max(revision.created) as revised, revision.revision"
		." FROM revision"
		." LEFT JOIN wikipage ON wikipage.page = revision.page"
		." WHERE content LIKE \"%((".$page."))%\" AND wiki = \"".$this->wiki."\""
		." GROUP BY wikipage.page";
		
		$result = $this->query($sql);
		$return = array();
		if ($result->numRows() != 0){
			while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
				$return[] = array('name' => $row['name'], 'link' => $row['name']);
			}
		}
		return $return;
	}

	//function: recent();
	function viewRecent(){
		$recent = array();

		$sql = "select "
				."wikipage.*, revision.*, creatorname.username as origin, "
				."unix_timestamp(revision.created) as created "
				."from wikipage, revision "
				."left join users on revision.creator = users.id "
				."left join users as creatorname on creatorname.id = origin "
				."where wikipage.wiki = \"".$this->wiki."\" and wikipage.page = revision.page "
				."order by revision.created desc limit 50";

		$result = $this->query($sql);

		$recent = array();
		while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
			$recent[] = $row;
		}
		return $recent;

	}


	//function: search();
	function search($terms){
		$return = array();
		global $_EXTRAS;

		/********************
			Thanks to Tom Pike <http://www.xiven.com> for reminding me that 'HAVING' exists and
			helping me get this query right :-) (Aq - 03/07/2004)
																				********************/

		$sql = 'SELECT wikipage.page, wikipage.name, wikipage.created,'
			.' revision.created as revised, revision.revision, max(revision.revision) as toprev'
			.' FROM revision'
			.' LEFT JOIN wikipage ON wikipage.page = revision.page'
			.' WHERE content LIKE "'.$terms.'" AND wiki = "'.$this->wiki.'"'
			.' GROUP BY wikipage.page'
			.' HAVING revision.revision = toprev'
			.' ORDER BY revision.revision desc';

		$result = $this->query($sql);
		if ($result->numRows() != 0){
			while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
				if ($row['name'] != $_EXTRAS['current']){
					$return[] = array('name' => $row['name'], 'link' => $row['name']);
				}
			}
		} else {
			$return[] = array('name' => "Nothing Found for $terms");
		}
		return "h3. Search for $terms\n".menu($return);
	}

	//function: author();
	function searchAuthor($terms){
		$line = "All items by $terms\n";
		$sql = "select id from users where username = \"$terms\"";

		$result = $this->query($sql);
		if ($result->numRows() != 0){
			$row = $result->fetchRow(DB_FETCHMODE_ASSOC);
			$author = "(revision.creator = ".$row['id']." or revision.creator = \"".$terms."\")";
		} else {
			$author = " revision.creator = \"".$terms."\"";
		}

		$sql = "select revision.*, unix_timestamp(revision.created) as created, wikipage.name, wikipage.origin from revision, wikipage where revision.page = wikipage.page and $author and wiki = \"".$this->wiki."\" order by created desc";
		$result = $this->query($sql);
		while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
			$line .= "# ".date("r",$row['created'])." - <a href=\"".$_CONFIG['base']."/".$wiki."/".$row['name']."\">".$row['name']."</a>\n";
		}
		return $line;
	}


	function getSQL($article, $crit = false){
		$sql = "select "
			."wikipage.*, revision.*, creatorname.username as origin, "
			."unix_timestamp(revision.created) as created "
			."from wikipage, revision "
			."left join users on revision.creator = users.id "
			."left join users as creatorname on creatorname.id = origin "
			."where wikipage.wiki = \"".$this->wiki."\" and wikipage.name = \"$article\" and wikipage.page = revision.page ";
		if ($crit){
			$sql .= $crit;
		}
		$sql .= " order by revision.created desc";
		
		return $sql;
	}
}

class mysql extends pearDB {

	// Anything not here is provided by pearDB;
	
	function post($name, $content, $comment){

		global $_EXTRAS;

		if (!$id = $this->pageExists($name)){
			$sql = "insert into wikipage (wiki, name, created, origin) values (\"".$this->wiki."\", \"".$name."\", NOW(), 1)";
			$post_res = $this->query($sql);
			$id = mysql_insert_id();
		}

		$author = "\"".$_EXTRAS['me']."\"";

		$sql  = "insert into revision (content, comment, creator, page, created) values (\"".$content."\", \"".htmlentities($_POST['comment'])."\", $author, $id, NOW())";

		$this->query($sql);
	}
}

class mysql4 extends mysql {

	// Anything not here is provided by pearDB;
	
	//function: search();
	function search($terms){
		$return = array();
		global $_EXTRAS;

		/* This code returns all the pages that *now* contain the search term, but is MySQL 4+ 
			(and anything else that supports subqueries) only */
		$sql = "SELECT wikipage.page, name, wikipage.created, max(revision.created) as revised, revision.revision"
		." FROM revision"
		." LEFT JOIN wikipage ON wikipage.page = revision.page and revision.revision = (SELECT max(r2.revision) from revision as r2 where r2.page = revision.page)"
		." WHERE content LIKE \"%".addslashes($terms)."%\" AND wiki = \"".$this->wiki."\""
		." GROUP BY wikipage.page"; 


		$result = $this->query($sql);
		if ($result->numRows() != 0){
			while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
				if ($row['name'] != $_EXTRAS['current']){
					$return[] = array('name' => $row['name'], 'link' => $row['name']);
				}
			}
		} else {
			$return[] = array('name' => "Nothing Found for $terms");
		}
		return "h3. Search for $terms\n".menu($return);
	}
}

//End PHP ?>