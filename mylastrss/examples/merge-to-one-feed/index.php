<?php
//include mYLastRSS and extends
require_once("../../mylastrss.class.php"); 
require_once("../../misc/mylr2rss/mylr2rss.class.php");

// Create mYLastRSS extended object 
$rss = new mYLR2RSS; 

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
$sources[] = 'yahoo-my-web-2-imguillaume.xml';

$rss->feed_stylesheet_type = 'text/xsl';
$rss->feed_stylesheet_url = '../../misc/rss20-xslt/simple-preview.php?options=-withAuthor-withEnclosure-withSource';

$rss->feed_title = 'Olivier D. alias ze kat, WebReview';
$rss->feed_link = 'http://360.yahoo.com/olie_ze_kat';
$rss->feed_description = 'Résumé de mes publications sur le Web';

$rss->feed_language = 'fr-fr';
$rss->feed_editor = 'oliezekat@yahoo.fr (Olivier D.)';
$rss->feed_webmaster = 'oliezekat@yahoo.fr (Olivier D.)';
//$rss->feed_docs = 'http://blogs.law.harvard.edu/tech/rss';

$rss->feed_image_url = 'http://ymplus.insideyahoo.net/common/img/kat.gif';
$rss->feed_image_title = 'Olivier D. alias ze kat';
$rss->feed_image_description = 'Blog de Olivier D. alias ze kat sur Yahoo! 360°';
$rss->feed_image_link = 'http://360.yahoo.com/olie_ze_kat';
$rss->feed_image_width = '16';
$rss->feed_image_height = '16';

$rss->Output($sources);
?>
