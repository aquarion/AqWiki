<p>I needed a wiki to import existing data into, and discovered that all existing wiki make that nigh on impossible, so I wrote my own.</p>

<H2>Features</H2>
<UL>
	<LI>Sensible (textile) formatting</LI>
	<LI>Clean URL structure</LI>
	<LI>Anti-Magic</LI>
	<LI>Authentication system</LI>
	<LI>Macros</LI>
	<LI>Multiple wiki on a single install/db</LI>
</UL>

<H2>Anti-Magic?</H2>
<p>Most existing wiki are far too "magic", they have special pages for too many things. aqWiki has only one magic page, which is for creating new users. Everything else is within tags. So a "recent Edits" page is just a page with "[[RECENT]]" in it. A search is [[SEARCH|terms]] and the contents of a variable will be printed by [[GETVAR|varname]] (And since var can be a HTTP "get" variable, you can do [[SEARCH|[[GETVAR|query]]]] so "http://wikihost/wiki/search?query=glob" (where the wiki page "search" contains [[SEARCH|[[GETVAR|query]]]]) will search the wiki for the word "glob". This is simpler than it sounds)</p>

<h2>Where is it being used?</h2>
<p>Currently my <A HREF="http://www.browserangel.com">workplace</A> use it as a brain-dump, and my life is run though an install of it on my local server.</p>

<H2>What does it need?</H2>
<p>PHP, Apache with Mod_Rewrite, and the <A HREF="http://pear.php.net">Pear</A> DB library</p>

<p>For databases, currently mysql4. But that's only because I haven't written the generic modules yet. Eventually it'll work with loads of databases and even flat files.</p>

<H2>How do I get it?</H2>
<p>You don't. Well, you can. You can grab the source from <A HREF="https://sourceforge.net/cvs/?group_id=89793">CVS</A> should you want to, but it's a little complicated to install right now, and I haven't written any docs.</p>

<H2>A quick guide?</H2>
<p>Very well. check out the module "aqwiki" from CVS and put it somewhere you can see it on a web server. Enable mod_rewrite for that directory on the web server. Create a new database of whatever name you like, give someone permissions to edit it, and run the sql script in "support/tables.sql" to set up the initial tables. Then, edit "etc/aqwiki.ini" to reflect your own reality. Even if you don't want anyone else to create any new wiki, you should set "newwiki" to "true" so you can start the first one.</p>

<h2>I don't understand! How do you...?</h2>
<p>Hense the "You don't" above. Wait until I get it released properly and with docs.</p>

<h2>It works! But..</h2>
<p>Great. If you find any problems, <A HREF="https://sourceforge.net/tracker/?group_id=89793&amp;atid=591420">report them as bugs</A> or <A HREF="https://sourceforge.net/tracker/?group_id=89793&amp;atid=591423">requests for enhancement</A>. You could even join the <A HREF="https://lists.sourceforge.net/mailman/listinfo/aqwiki-devel">aqwiki-devel@lists.sourceforge.net</A> mailing list.</p>

<h2>Why doesn't this page use aqwiki?</h2>
<p>Me and Sourceforge's Apache install had a falling out</p>

<H2>Is there anywhere I can see it working?</H2>
<p>Until the above resolves, not quite yet. My install is hosted over a cable modem, so you're not getting at it</p>



