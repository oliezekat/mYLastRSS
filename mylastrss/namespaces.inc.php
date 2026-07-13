<?php
unset($MYLR_FORMATS);
$MYLR_FORMATS = Array();

/* RSS 0.91, 0.92 and 2.0 http://blogs.law.harvard.edu/tech/rss */

$MYLR_FORMATS['rss']['channel_tags']					 = array('title','link','description','category','language','generator','copyright','managingEditor','webMaster','pubDate','lastBuildDate','rating','docs','ttl','skipHours','skipDays');
// Todo: 'image','textinput',
$MYLR_FORMATS['rss']['channel_image_tags']				 = array('title','url','link','description','width','height');
$MYLR_FORMATS['rss']['channel_textinput_tags']			 = array('title','description','name','link');
// Todo: "textInput" uppercase from Userland RSS 0.91

$MYLR_FORMATS['rss']['item_tags']						 = array('title','link','enclosure','description','author','category','comments','guid','pubDate','source','expirationDate','mobile_link','imageCaption','image','fullText','storytitle','headline','image_big','alink','copyright','photo');
// "expirationDate" by Userland RSS 0.93
// "mobile_link" not official, used by Yahoo! News
// "image" not official, used by 20min.ch, lessentiel.lu, and RSR.ch
// "image" (attribute size=small/medium/big), "imageCaption", "fullText" not official, used by RSR.ch
// "storytitle", "headline", "image_big" not official, used by 20min.ch
// "alink" not official, used by french.news.cn
// "copyright" not official, frequent mistake
// "photo" not official, frequent mistake
$MYLR_FORMATS['rss']['item_enclosure_attributes']		 = array('url','length','type','duration','height','width');
// "height" and "width" are not official
$MYLR_FORMATS['rss']['item_guid_attributes']			 = array('isPermaLink');
$MYLR_FORMATS['rss']['item_category_attributes']		 = array('domain');

/* RDF/RSS 1.0 http://web.resource.org/rss/1.0/ */

$MYLR_FORMATS['rdf']									 = $MYLR_FORMATS['rss'];

/* Atom http://www.w3.org/2005/Atom */

$MYLR_FORMATS['atom']['channel_tags']					 = array('updated','link','title','info','category','logo','icon','subtitle','language','modified','id','copyright','generator','tagline','author','rights');
$MYLR_FORMATS['atom']['channel_author_tags']			 = array('name','email','uri');
$MYLR_FORMATS['atom']['channel_generator_attributes']	 = array('url','version','uri');
$MYLR_FORMATS['atom']['channel_info_attributes']		 = array('type','mode');
$MYLR_FORMATS['atom']['channel_link_attributes']		 = array('href','type','ref','rel','hreflang');
$MYLR_FORMATS['atom']['channel_title_attributes']	 	 = array('type');
$MYLR_FORMATS['atom']['channel_subtitle_attributes'] 	 = array('type');

$MYLR_FORMATS['atom']['item_tags']						 = array('id','link','title','updated','issued','created','published','modified','info','content','author','category','summary','source','contributor');
$MYLR_FORMATS['atom']['item_author_attributes']			 = array('name','url','email','uri');
$MYLR_FORMATS['atom']['item_category_attributes']		 = array('term','scheme','label');
$MYLR_FORMATS['atom']['item_content_attributes']		 = array('type','mode','xml:lang','xml:base');
$MYLR_FORMATS['atom']['item_info_attributes']		 	 = array('type','mode');
$MYLR_FORMATS['atom']['item_link_attributes']		 	 = array('href','type','src','ref','rel','length','title');
$MYLR_FORMATS['atom']['item_source_tags'] 				 = array('id','title','updated','rights');
$MYLR_FORMATS['atom']['item_summary_attributes'] 		 = array('type');
$MYLR_FORMATS['atom']['item_title_attributes']	 		 = array('type');
$MYLR_FORMATS['atom']['item_contributor_tags']			 = array('name','uri','email');

unset($MYLR_XMLNS);
$MYLR_XMLNS = Array();

/* http://www.w3.org/2005/Atom */

$MYLR_XMLNS['a10']['channel_tags']						 = array();
$MYLR_XMLNS['a10']['item_tags']							 = array('a10:updated');

/* http://purl.org/rss/1.0/modules/content/ */

$MYLR_XMLNS['content']['channel_tags']					 = array();
$MYLR_XMLNS['content']['item_tags']						 = array('content:encoded');

/* http://backend.userland.com/creativeCommonsRssModule */

$MYLR_XMLNS['creativeCommons']['channel_tags']			 = array('creativeCommons:license');
$MYLR_XMLNS['creativeCommons']['item_tags']				 = array();

/* http://purl.org/dc/elements/1.1/ */

$MYLR_XMLNS['dc']['channel_tags']						 = array('dc:date','dc:creator','dc:language','dc:rights');
$MYLR_XMLNS['dc']['item_tags']							 = array('dc:creator','dc:source','dc:subject','dc:date','dc:date.Taken','dc:language','dc:tag','dc:format');

/* http://purl.org/dc/terms/ */

$MYLR_XMLNS['dcterms']['channel_tags']					 = array();
$MYLR_XMLNS['dcterms']['item_tags']						 = array('dcterms:modified','dcterms:created');

/* http://purl.org/dc/terms/ */

$MYLR_XMLNS['dct']['channel_tags']						 = array();
$MYLR_XMLNS['dct']['item_tags']							 = array('dct:alternative');

/* http://digg.com/docs/diggrss/ */

$MYLR_XMLNS['digg']['channel_tags']						 = array();
$MYLR_XMLNS['digg']['item_tags']						 = array('digg:diggCount','digg:category','digg:commentCount','digg:username','digg:userimage');

/* http://www.dailymotion.com/dmrss */

$MYLR_XMLNS['dm']['channel_tags']						 = array();
$MYLR_XMLNS['dm']['item_tags']							 = array('dm:id','dm:author','dm:text','dm:videorating','dm:videovotes','dm:views','dm:comments','dm:favorites','dm:authorAvatar','dm:description','dm:sex','dm:city','dm:country','dm:age');

/* http://www.exif.org/specifications.html */

$MYLR_XMLNS['exif']['channel_tags']						 = array();
$MYLR_XMLNS['exif']['item_tags']						 = array('exif:DateTimeOriginal');

/* http://rssnamespace.org/feedburner/ext/1.0 */

$MYLR_XMLNS['feedburner']['channel_tags']				 = array('feedburner:awareness','feedburner:emailServiceId','feedburner:feedburnerHostname');
$MYLR_XMLNS['feedburner']['item_tags']					 = array('feedburner:awareness','feedburner:origLink','feedburner:origEnclosureLink');

/* http://www.feedsky.com/namespace/feed */

$MYLR_XMLNS['fs']['channel_tags']						 = array('fs:self_link');
$MYLR_XMLNS['fs']['item_tags']							 = array('fs:itemid','fs:srcfeed','fs:srclink');

/* http://www.w3.org/2003/01/geo/wgs84_pos# */

$MYLR_XMLNS['geo']['channel_tags']						 = array('geo:Point','geo:long','geo:lat','geo:alt');
$MYLR_XMLNS['geo']['item_tags']							 = array('geo:Point','geo:long','geo:lat','geo:alt');

/* http://www.georss.org/georss */

$MYLR_XMLNS['georss']['channel_tags']					 = array();
$MYLR_XMLNS['georss']['item_tags']						 = array('georss:point','georss:line','georss:polygon','georss:featuretypetag','georss:relationshiptag','georss:featurename','georss:elev','georss:floor','georss:radius');

/* http://www.itunes.com/DTDs/Podcast-1.0.dtd */

$MYLR_XMLNS['itunes']['channel_tags']					 = array('itunes:author','itunes:explicit','itunes:subtitle','itunes:keywords','itunes:block','itunes:summary');
// without 'itunes:image','itunes:category'... Basic tag to support
$MYLR_XMLNS['itunes']['item_tags']						 = array('itunes:subtitle','itunes:summary','itunes:duration','itunes:block','itunes:explicit','itunes:author','itunes:keywords');

/* http://schemas.microsoft.com/live/spaces/2006/rss */

$MYLR_XMLNS['live']['channel_tags']						 = array();
$MYLR_XMLNS['live']['item_tags']						 = array('live:type','live:typelabel');

/* http://www.rssboard.org/media-rss http://search.yahoo.com/mrss */

$MYLR_XMLNS['media']['channel_tags']					 = array('media:rating');
$MYLR_XMLNS['media']['item_tags']						 = array('media:title','media:description','media:text','media:credit','media:category','media:copyright','media:rating','media:keywords','media:license');
// Todo: support several 'media:group','media:content','media:thumbnail','media:player','media:embed'
$MYLR_XMLNS['media']['item_media:group_tags']			 = array('media:content','media:credit','media:category','media:rating');
$MYLR_XMLNS['media']['item_media:content_attributes']	 = array('url','type','height','width','duration','fileSize','expression','bitrate','channels');
$MYLR_XMLNS['media']['item_media:content_tags']			 = array('media:title','media:keywords','media:credit','media:category','media:rating');
$MYLR_XMLNS['media']['item_media:thumbnail_attributes']	 = array('url','type','height','width','time');
$MYLR_XMLNS['media']['item_media:player_attributes']	 = array('url','height','width');
$MYLR_XMLNS['media']['item_media:credit_attributes']	 = array('role');

/* http://a9.com/-/spec/opensearchrss/1.0/ */

$MYLR_XMLNS['openSearch']['channel_tags']				 = array('openSearch:totalResults','openSearch:startIndex','openSearch:itemsPerPage');
$MYLR_XMLNS['openSearch']['item_tags']					 = array();

/* http://www.pheed.com/pheed/ */

$MYLR_XMLNS['photo']['channel_tags']					 = array();
$MYLR_XMLNS['photo']['item_tags']						 = array('photo:imgsrc','photo:thumbnail');

/* http://purl.org/rss/1.0/modules/slash/ */

$MYLR_XMLNS['slash']['channel_tags']					 = array();
$MYLR_XMLNS['slash']['item_tags']						 = array('slash:comments');

/* http://slideshare.net/api/1 */

$MYLR_XMLNS['slideshare']['channel_tags']				 = array();
$MYLR_XMLNS['slideshare']['item_tags']					 = array('slideshare:embed','slideshare:views','slideshare:comments','slideshare:thumbnail');

/* http://www.life2front.com/streamxd */

$MYLR_XMLNS['streamxd']['channel_tags']					 = array();
$MYLR_XMLNS['streamxd']['item_tags']					 = array('streamxd:type','streamxd:pubdate');

/* http://purl.org/rss/1.0/modules/syndication/ */

$MYLR_XMLNS['sy']['channel_tags']						 = array('sy:updatePeriod','sy:updateFrequency','sy:updateBase');
$MYLR_XMLNS['sy']['item_tags']							 = array();

/* http://wakoopa.com/ns */

$MYLR_XMLNS['wakoopa']['channel_tags']					 = array();
$MYLR_XMLNS['wakoopa']['item_tags']						 = array('wakoopa:icon','wakoopa:thumb_icon');

/* http://wellformedweb.org/CommentAPI/ */

$MYLR_XMLNS['wfw']['channel_tags']						 = array();
$MYLR_XMLNS['wfw']['item_tags']							 = array('wfw:commentRSS','wfw:commentRss','wfw:comment');

/* urn:ietf:params:xml:ns:xcal */

$MYLR_XMLNS['xCal']['channel_tags']						 = array();
$MYLR_XMLNS['xCal']['item_tags']						 = array('xCal:summary','xCal:location','xCal:x-calconnect-city','xCal:x-calconnect-region','xCal:x-calconnect-country','xCal:dtstart','xCal:dtend');

?>