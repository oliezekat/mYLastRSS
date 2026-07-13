<?php
//include mYLastRSS and extends
require_once("../../mylastrss.class.php"); 

// Create mYLastRSS object 
$rss = new mYLastRSS; 

//header("Content-Type: application/rss+xml");
header("Content-Type: text/xml");
echo("<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>\n");
//iso-8859-1,UTF-8
echo("<?xml-stylesheet type=\"text/xsl\" href=\"../../misc/rss20-xslt/simple-preview.php?options=-withAuthor-withEnclosure-withSource\"?>\n");
?>
<rss version="2.0">
  <channel>
    <title><![CDATA[Olivier D. alias ze kat, WebReview]]></title>
    <link>http://360.yahoo.com/olie_ze_kat</link>
    <description><![CDATA[<?php echo(htmlspecialchars("Résumé de mes publications sur le Web",ENT_COMPAT)); ?>]]></description>
	<language>fr-fr</language>
	<managingEditor>oliezekat@yahoo.fr (Olivier D.)</managingEditor>
	<webMaster>oliezekat@yahoo.fr (Olivier D.)</webMaster>
	<generator>mYLastRSS</generator>
	<docs>http://blogs.law.harvard.edu/tech/rss</docs>
	<ttl><?php echo(ceil(60 * 24 * 1)); ?></ttl>
	<image>
		<url>http://ymplus.insideyahoo.net/common/img/kat.gif</url>
		<title>Olivier D. alias ze kat</title>
		<description><?php echo(htmlspecialchars("Blog de Olivier D. alias ze kat sur Yahoo! 360°",ENT_COMPAT)); ?></description>
		<link>http://360.yahoo.com/olie_ze_kat</link>
		<width>16</width>
		<height>16</height>
	</image>
	<?php
	
	
	// Set cache dir and cache time limit (1200 seconds) 
	// (don't forget to chmod cache dir to 777 to allow writing) 
	$rss->cache_dir = '../../cache'; 
	$rss->cache_time = 60 * 60 * 24 * 1;
	$rss->cache_all = TRUE;
	//$rss->default_cp = 'UTF-8';
	//$rss->cp = 'windows-1252';
	$rss->cp = 'iso-8859-1';
	$rss->CDATA = 'strip';
	$rss->stripHTML = FALSE;
	$rss->min_items_required = 1;
	$rss->use_cache_if_failed = TRUE;
	
	unset($sources);
	$sources[] = 'http://blog.360.yahoo.com/rss-FfAVOG01brY4lJgFOIoLXam9c8Pg';
	$sources[] = 'http://fr.search.yahoo.com/myweb/user/v6w7lIAedu4TFqEfwU5M9SLSbA--/Yahoo%21/rss.xml';
	$sources[] = 'http://answers.yahoo.com/rss/userq?kid=AA10001721';
	$sources[] = 'http://www.flickr.com/services/feeds/photos_public.gne?id=78854247@N00&format=rss_200';
	$sources[] = 'http://www.vbfrance.com/rss.aspx?type=AuteurCode&ID=14585';
	$sources[] = 'http://www.codes-sources.com/rss.aspx?type=AuteurCode&ID=14585';
	$sources[] = 'http://myweb2.search.yahoo.com/mywebrss/user/NYQv_.RZqu_gm4IE_vMfKA--/tag/yahoo/urls.xml';
	
	// Remove these lines to try with online feeds
	unset($sources);
	$sources[] = 'yahoo-360.test.rss.xml';
	$sources[] = 'codes-sources.test.rss.xml';
	$sources[] = 'yahoo-mon-web-yahoo.test.rss.xml';
	$sources[] = 'yahoo-mon-web-yahoo-bis.rss.xml';
	$sources[] = 'flickr-photos.mrss.xml';
	$sources[] = 'yahoo-answers.rss.xml';
	$sources[] = 'yahoo-my-web-2-yahoo.rss.xml';
	$sources[] = 'yahoo-groupes-yplus.rss.xml';
	
	// Try to load and parse RSS file 
	if ($rs = $rss->Get($sources))
		{ 
	    // Show last published articles (title, link, description) 
	    foreach($rs['items'] as $item)
			{
			echo("<item>\n"); 
			echo("<title><![CDATA[".$rss->unhtmlentities($item['title'])."]]></title>\n"); 
			echo("<pubDate>".$item['pubDate']."</pubDate>\n");
			if ($item['author'] != '')
				{
				echo("<author>".$item['author']."</author>\n"); 
				}
			echo("<link><![CDATA[".$item['link']."]]></link>\n"); 
			if ($item['category'] != '')
				{
				echo("<category>".$item['category']."</category>\n");
				}
			if ($item['guid'] != '')
				{
				if ($item['guid_isPermaLink'] === TRUE)
					{
					echo("<guid isPermaLink=\"true\">".$item['guid']."</guid>\n"); 
					}
				else if ($item['guid_isPermaLink'] === FALSE)
					{
					echo("<guid isPermaLink=\"false\">".$item['guid']."</guid>\n"); 
					}
				else
					{
					echo("<guid>".$item['guid']."</guid>\n"); 
					}
				}
			echo("<description><![CDATA[".htmlspecialchars($rss->unhtmlentities($item['description']),ENT_QUOTES)."]]></description>\n");
			if ($item['source'] != '')
				{
				if ($item['source_url'] != '')
					{
					//echo("<source url=\"".$item['source_url']."\"><![CDATA[".$item['source']."]]></source>\n"); 
					echo("<source><![CDATA[".$item['source']."]]></source>\n"); 
					}
				else
					{
					echo("<source><![CDATA[".$item['source']."]]></source>\n"); 
					}
				}
			if ($item['enclosure_url'] != '')
				{
				echo("<enclosure url=\"".$item['enclosure_url']."\" length=\"".$item['enclosure_length']."\" type=\"".$item['enclosure_type']."\" />\n");
				}
			echo("</item>\n\n");
	        } 
	    } 
	else
		{ 
	     
		}
	?>
  </channel>
</rss>
<!--
<?php
foreach($rss->_LAST_ERROR_MESSAGES as $errormsg)
	{
	echo("ERROR: ".$errormsg."\n");
	}
?>
-->
