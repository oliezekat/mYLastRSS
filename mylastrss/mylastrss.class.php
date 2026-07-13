<?php
/*
 ======================================================================
 
 mYLastRSS (from lastRSS 0.9.1)
 Simple yet powerfull PHP class to parse several RSS files.
 http://sourceforge.net/projects/mylastrss/
 
 By Olivier D. alias ze kat, oliezekat@yahoo.fr
 http://life2front.com/oliezekat
 
 From original stuff named "lastRSS" of 
 Vojtech Semecky, vojtech.semecky@cmail.cz
 Latest version, features, manual and examples:
 http://lastrss.oslab.net/
 
 IMPORTANT: keep this file with ANSI encoding
 
 ----------------------------------------------------------------------
 LICENSE

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License (GPL)
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.

 To read the license please visit http://www.gnu.org/copyleft/gpl.html
 
 ----------------------------------------------------------------------
 mYLastRSS vs lastRSS (0.9.1) :
 - Support all tags of RSS 2.0
 - Fixed cache feature, and use cache if failed to download or parse feed
 - Allow to parse, merge/aggregate, and sort several sources at same time

 ======================================================================
*/
if (defined('MYLASTRSS_NS_PATH'))
	{
	require_once(MYLASTRSS_NS_PATH);
	}
else
	{
	require_once('namespaces.inc.php');
	}

class mYLastRSS
	{
	// -------------------------------------------------------------------
	// Public properties
	// -------------------------------------------------------------------
	var $default_cp 			= ''; 			// default code-page if not found. Leave blank or 'auto'
	var $CDATA 					= 'strip';
	var $cp 					= 'utf-8';		// output code-page
	var $items_limit 			= 0;
	var $items_limit_per_source = 0;
	var $stripHTML 				= FALSE;
	var $date_format 			= '';
	var $useOrigLink			= FALSE;		// Search original link while detect tracking URL (of FeedBurner, FeedPortal, etc)
	var $kidx_rule 				= 'guid'; 		// Which use as unique item's id ; guid, link, date+title, link/date+title, or date+title/link
	
	var $cache_dir 				= '';			// Create CHMOD 777 directory or use /tmp on Linux server
	var $cache_feed_dir			= '';			// Let blank if same cache_dir
	var $cache_feed_prefix		= 'mylr_feed';
	var $cache_feeds_dir		= '';			// Let blank if same cache_dir
	var $cache_feeds_prefix		= 'mylr_feeds';
	var $cache_feeds_filename	= '';			// Use cautiously. But usefull if sources list change frequently.
	var $cache_errors_dir		= '';			// Let blank if same cache_dir
	var $cache_time 			= 3600; 		// 60 * 60 * 1
	var $cache_all 				= TRUE; 		// Set TRUE to cache each feeds if you make several differents Get(array(...)) with differents sources
	var $cache_feed_only		= FALSE; 		// Set TRUE if you don't need cache of merged feeds.
	var $cache_feeds_if_failed	= FALSE;		// Set TRUE to save cache while one feed failed
	var $incremental_cache_time = 1800; 		// 60 * 30 * 1 First source use $cache_time, second source use $cache_time+$incremental_cache_time, etc.
	var $use_cache_if_failed 	= TRUE;			// Set TRUE to use cached file if feed request failed
	var $min_items_required 	= 0; 			// Before to use last file cached
	var $retry_delay			= 1200;			// 60 * 20 * 1 time wait before to try again. Require cache_dir.
	var $query_limit			= 0;			// Limit number of queries to fetch feed content. Could need several queries per source. Set 0 to disable.
	var $max_execution_time		= 0;			// set 0 to disable
	
	var $write_mode				= 'direct';		// How to save file : write 'direct' to destination filename, 'copy' or 'move' temporary file
	var $writelock_ext			= '.wlock';		// Extension added to filename for write-locking feature (set blank to disable write-locking)
	var $writelock_delay		= 0;			// Delay before to ignore write-locking (set zero to disable write-locking)
	var $max_write_errors		= 1;			// Not try to write/copy/move file if reach this limit
	
	var $useSnoopy				= FALSE;		// Use Snoopy class to download feeds
	var $userAgent				= 'mYLastRSS';	// For Snoopy
	var $timeOut				= 0;			// For Snoopy, set 0 to disable... Unused if set max_execution_time
	var $minTimeOut				= 3;			// minimal time-out per Snoopy request, used if set max_execution_time
	
	// -------------------------------------------------------------------
	// Private variables (Don't use them)
	// -------------------------------------------------------------------
	
	// For RSS parsing
	
	var $channeltags 	= array(); // Now build at runtime for each feed
	var $itemtags 		= array(); // Now build at runtime for each feed
	
	var $_MIMES_TYPES = array(	'zip'	=>	'application/x-zip-compressed',
								'exe'	=>	'application/x-msdownload',
								'gif'	=>	'image/gif',
								'jpg'	=>	'image/jpeg',
								'jpe'	=>	'image/jpeg',
								'jpeg'	=>	'image/jpeg',
								'png'	=>	'image/png',
								'mp3'	=>	'audio/mpeg',
								'mp4'	=>	'video/mp4',
								'flv'	=>	'video/x-flv',
								'avi'	=>	'video/x-msvideo',
								'swf'	=>	'application/x-shockwave-flash',
								'3gp'	=>	'video/3gpp'
								);
	// To support Media RSS
	var $enable_MediaRSS = TRUE; // Deprecated
	var $_MRSS_CONTENT_MIMES_TYPES 	= array('image/gif','image/jpeg','image/pjpeg','image/png','audio/mpeg','video/jpeg','video/mp4','video/quicktime','video/x-flv','application/x-shockwave-flash','video/x-msvideo','video/3gpp');
		
	// Internal global vars
	var $_USE_SEVERAL_SOURCES 	= FALSE;
	var $_STARTED_INDEX 		= 0;
	var $_SOURCES			 	= array();
	var $_STARTED_TIME 			= 0;
	var $_QUERY_COUNT			= 0;
	var $_FWRITE_FAIL_COUNT		= 0;		// Amount of write/copy/move errors. Not reset between several request.
	var $_HTML_ENTITIES_TRANS 	= array(); 	// Build into constructor method.
	var $_LAST_ERROR_MESSAGES 	= array(); 	// Error messages (in english) which help to debug... Don't use if debugging is finished.
		
	// -------------------------------------------------------------------
	// Constructor
	// -------------------------------------------------------------------
	
	function mYLastRSS()
		{
		$this->_STARTED_TIME = time();
		$this->Init(TRUE,'none');
		// Check availables functions
		if (function_exists('mb_convert_encoding'))
			{
			// So nice, better function to convert encoding is available :o)
			}
		else if (function_exists('iconv'))
			{
			$this->_LAST_ERROR_MESSAGES[] = "mb_convert_encoding() not available to convert encoding, but could use iconv()";
			}
		else
			{
			$this->_LAST_ERROR_MESSAGES[] = "iconv() and mb_convert_encoding() not availables to convert encoding";
			}
		}
		
	// -------------------------------------------------------------------
	// Publics methods
	// -------------------------------------------------------------------
	function _InitEntitiesArray()
		{
		if (!$this->_HTML_ENTITIES_TRANS)
			{
			// Init _HTML_ENTITIES_TRANS array for unhtmlentities()
			// Get HTML entities table
			$this->_HTML_ENTITIES_TRANS = get_html_translation_table (HTML_ENTITIES, ENT_QUOTES);
			// Flip keys<==>values
			$this->_HTML_ENTITIES_TRANS = array_flip ($this->_HTML_ENTITIES_TRANS);
			
			if (strtoupper($this->cp) == 'UTF-8')
				{
				foreach($this->_HTML_ENTITIES_TRANS as $entity => $value)
					{
					$this->_HTML_ENTITIES_TRANS[$entity] = utf8_encode($value);
					}
				}
			
			// Add support for entities which missing in HTML_ENTITIES
			$this->_HTML_ENTITIES_TRANS += array("&apos;" => "'");
			$this->_HTML_ENTITIES_TRANS += array("&quot;" => '"');
			$this->_HTML_ENTITIES_TRANS += array("&lt;" => '<');
			$this->_HTML_ENTITIES_TRANS += array("&gt;" => '>');
			$this->_HTML_ENTITIES_TRANS += array("&amp;" => '&');
			$this->_HTML_ENTITIES_TRANS += array("&mdash;" => '-');
			$this->_HTML_ENTITIES_TRANS += array("&ndash;" => '-');
			$this->_HTML_ENTITIES_TRANS += array("&bull;" => '-');
			$this->_HTML_ENTITIES_TRANS["&nbsp;"] = ' ';
			$this->_HTML_ENTITIES_TRANS["&oelig;"] = 'oe';
			// Entities from OpenOffice
			$this->_HTML_ENTITIES_TRANS += array("&rsquo;" => "'"); 
			$this->_HTML_ENTITIES_TRANS += array("&lsquo;" => "'");
			$this->_HTML_ENTITIES_TRANS += array("&lrsquo;" => "'");
			// add &ldquo; &rdquo; &lsquo; &rsquo; 
			// Entities from Delicious
			$this->_HTML_ENTITIES_TRANS += array("&"."#x2013;" => "-");
			$this->_HTML_ENTITIES_TRANS += array("&"."#x2014;" => "-");
			$this->_HTML_ENTITIES_TRANS += array("&"."#x2019;" => "'");
			$this->_HTML_ENTITIES_TRANS += array("&"."#x201C;" => '"');
			$this->_HTML_ENTITIES_TRANS += array("&"."#x201D;" => '"');
			$this->_HTML_ENTITIES_TRANS += array("&"."#x2026;" => "...");
			$this->_HTML_ENTITIES_TRANS += array("&"."#x203A;" => "õ");
			// Entities from WordPress
			$this->_HTML_ENTITIES_TRANS += array("&"."#8211;" => "-");
			$this->_HTML_ENTITIES_TRANS += array("&"."#8216;" => "'");
			$this->_HTML_ENTITIES_TRANS += array("&"."#8217;" => "'");
			$this->_HTML_ENTITIES_TRANS += array("&"."#8220;" => '"');
			$this->_HTML_ENTITIES_TRANS += array("&"."#8221;" => '"');
			$this->_HTML_ENTITIES_TRANS += array("&"."#8230;" => '...');
			// From Fanfou
			$this->_HTML_ENTITIES_TRANS += array("&"."#65306;" => ':');
			
			if (strtoupper($this->cp) == 'UTF-8')
				{
				// Add support for numeric entities which missing in HTML_ENTITIES
				for ($i = 32;$i < 255;$i++)
					{
					$this->_HTML_ENTITIES_TRANS += array("&#".$i.";" => utf8_encode(chr($i)));
					if ($i < 100) $this->_HTML_ENTITIES_TRANS += array("&#0".$i.";" => utf8_encode(chr($i)));
					// coComment entities
					$this->_HTML_ENTITIES_TRANS += array("&#x".strtoupper(dechex($i)).";" => utf8_encode(chr($i)));
					$this->_HTML_ENTITIES_TRANS += array("&#x".strtolower(dechex($i)).";" => utf8_encode(chr($i)));
					}
				}
			else
				{
				// Add support for numeric entities which missing in HTML_ENTITIES
				for ($i = 32;$i < 255;$i++)
					{
					$this->_HTML_ENTITIES_TRANS += array("&#".$i.";" => chr($i));
					if ($i < 100) $this->_HTML_ENTITIES_TRANS += array("&#0".$i.";" => chr($i));
					// coComment entities
					$this->_HTML_ENTITIES_TRANS += array("&#x".strtoupper(dechex($i)).";" => chr($i));
					$this->_HTML_ENTITIES_TRANS += array("&#x".strtolower(dechex($i)).";" => chr($i));
					}
					
				$this->_HTML_ENTITIES_TRANS['&szlig;']	 = 'ﬂ';
				}
			$this->_HTML_ENTITIES_TRANS["&#xa0;"]	 = ' ';
			$this->_HTML_ENTITIES_TRANS["&#038;"]	 = '&';
			$this->_HTML_ENTITIES_TRANS["&#39;"]	 = "'";
			$this->_HTML_ENTITIES_TRANS["&#34;"]	 = '"';
			$this->_HTML_ENTITIES_TRANS["&#339;"]	 = 'oe';
			
			// Convert special quotes
			/*
			$this->_HTML_ENTITIES_TRANS += array("`" => "'");
			$this->_HTML_ENTITIES_TRANS += array("¥" => "'");
			$this->_HTML_ENTITIES_TRANS += array("ë" => "'");
			$this->_HTML_ENTITIES_TRANS += array("í" => "'");
			$this->_HTML_ENTITIES_TRANS += array("ì" => '"');
			$this->_HTML_ENTITIES_TRANS += array("î" => '"');
			$this->_HTML_ENTITIES_TRANS += array("Ö" => "...");
			*/
			}
		}
		
	function _InitSupportedTags($Processor='unknown',$namespaces=NULL)
		{
		// Processor=unknown|rss|rdf|atom
		
		GLOBAL $MYLR_FORMATS;
		GLOBAL $MYLR_XMLNS;
		
		if (($Processor != 'unknown') AND ($MYLR_FORMATS[$Processor]))
			{
			$this->channeltags	 = $MYLR_FORMATS[$Processor]['channel_tags'];
			$this->itemtags		 = $MYLR_FORMATS[$Processor]['item_tags'];
			}
		else
			{
			$this->channeltags	 = array();
			$this->itemtags		 = array();
			}
			
		if (is_array($namespaces))
			{
			foreach ($namespaces as $xmlns)
				{
				if ($MYLR_XMLNS[$xmlns])
					{
					$this->channeltags	 = array_merge($this->channeltags,$MYLR_XMLNS[$xmlns]['channel_tags']);
					$this->itemtags		 = array_merge($this->itemtags,$MYLR_XMLNS[$xmlns]['item_tags']);
					}
				}
			}
		}
	
	function Init($Reset=FALSE,$Processor='unknown')
		// Processor=unknown|none|rss|rdf|atom
		{
		if ($Reset)
			{
			$this->_HTML_ENTITIES_TRANS = array();
			$this->channeltags = array();
			$this->itemtags = array();
			$this->_LAST_ERROR_MESSAGES = array();
			$this->_SOURCES = array();
			$this->_HTML_ENTITIES_TRANS = array();
			}
				
		if ($this->cache_feed_dir == '') $this->cache_feed_dir = $this->cache_dir;
		if ($this->cache_feeds_dir == '') $this->cache_feeds_dir = $this->cache_dir;
		if ($this->cache_errors_dir == '') $this->cache_errors_dir = $this->cache_dir;
		}
	
	// -------------------------------------------------------------------
	// Parse RSS file and returns associative array.
	// -------------------------------------------------------------------
	function Get($sources,$Reset=FALSE)
		// One source : Get('http://www.site.com/.../rss.xml');
		// Several sources : Get($sourceArray);
		// with $sourceArray = array ( URL 1, URL 2, ...);
		{
		$this->_STARTED_TIME = time();
		if (($this->timeOut !== 0) AND ($this->max_execution_time !== 0)) $this->timeOut = $this->max_execution_time;
		
		$this->Init($Reset,'none');
		$this->_USE_SEVERAL_SOURCES = FALSE;
		$this->_SOURCES = array();
		$this->_LAST_ERROR_MESSAGES = array();
		$this->_QUERY_COUNT = 0;
		
		if (is_array($sources) === TRUE)
			{
			foreach($sources as $source)
				{
				$source_kidx = $this->_SourceKIDX($source);
				$this->_SOURCES[$source_kidx]['url'] = $source;
				}
			
			return $this->GetFromSeveralSources($sources);
			}
		else
			{
			$source_kidx = $this->_SourceKIDX($sources);
			$this->_SOURCES[$source_kidx]['url'] = $sources;
			
			return $this->GetFromOneSource($sources,$source_kidx);
			}
		}
		
	function GetCache($sources,$Reset=FALSE)
		{
		$cache_file = '';
		$result = NULL;
		
		if (is_array($sources) === TRUE)
			{
			if ($this->cache_feeds_dir != '')
				{
				// If CACHE ENABLED
				if ($this->cache_feeds_filename != '')
					{
					$cache_file = $this->cache_feeds_dir.'/'.$this->cache_feeds_filename;
					}
				else
					{
					$cache_file = $this->cache_feeds_dir.'/'.$this->cache_feeds_prefix.'_cache_'.md5(serialize($sourcesArray).'?limit='.$this->items_limit.'&html='.$this->stripHTML.'&date='.$this->date_format.'&cdata='.$this->CDATA.'&cp='.$this->cp);
					}
				}
			}
		else
			{
			// TODO: support single feed
			}
			
		if (($cache_file != '') AND (file_exists($cache_file) == FALSE))
			{
			$cache_file = '';
			}
			
		if ($cache_file != '')
			{
			// cached file is fresh enough, return cached array
			$result = $this->_LoadCacheFile($cache_file);
			// set 'cached' to 1 only if cached file is correct
			}
		
		if ($result)
			{
			$result['cached'] = 1;
			$this->_SOURCES = $result['sources'];
			$result['updatedTime'] = filemtime($cache_file);
			}
			
		return $result;
		}
	
	/* Return array of HTML images attributes */
	function fetchimg($content)
		{
		$images = array();
		$imgatts = array('src','height','width','alt','title');
		
		preg_match_all("'<img(| .*?)>'si", $content, $results);
		$imgscnts = $results[1];
		if (count($imgscnts) > 0)
			{
			foreach($imgscnts as $imgcnt)
				{
				$image = array();
				foreach($imgatts as $imgatt)
					{
					$temp = $this->my_preg_match("'$imgatt=[\'\"](.*?)[\'\"]'si", $imgcnt);
					if ($temp != '') $image[$imgatt] = $temp; // Set only if not empty
					}
				$images[] = $image;
				}
			}
		
		return $images;
		}

	// -------------------------------------------------------------------
	// Replace HTML entities &something; by real characters
	// -------------------------------------------------------------------
	function unhtmlentities($string,$strict=TRUE)
		{
		$this->_InitEntitiesArray();
		
		// Bad feeds had double entities for amp
		if ($strict)
			{
			$string = str_replace(array('&amp;#038;','&amp;#38;','&amp;','&#x26;','&#38;','&#038;'),'&',$string);
			}
		
		// Replace entities by values
		$string = strtr ($string, $this->_HTML_ENTITIES_TRANS);
		//$string = html_entity_decode($string,ENT_QUOTES,$this->cp);
		
		if (strtoupper($this->cp) == 'UTF-8')
			{
			$string = preg_replace('~&#([0-9]+);~e', 'mYLR_unichr("\\1")', $string);
			}
		
		// Remplace les entitÈs numÈriques
   		//$string = preg_replace('~&#x([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $string);
    	//$string = preg_replace('~&#([0-9]+);~e', 'chr("\\1")', $string);
		
		// Fucking quotes :o|
		//$string = str_replace(array('`','ë','í','¥'),"'",$string);
		//$string = str_replace(array('ì','î'),'"',$string);
		//$string = str_replace(array('Ö'),'...',$string);
		
		return $string;
		}

	// -------------------------------------------------------------------
	// Private methods
	// -------------------------------------------------------------------

	function GetFromSeveralSources($sourcesArray)
		{
		if (($this->cache_feeds_dir != '') AND ($this->cache_feed_only == FALSE))
			{
			// If CACHE ENABLED
			if ($this->cache_feeds_filename != '')
				{
				$cache_file = $this->cache_feeds_dir.'/'.$this->cache_feeds_filename;
				}
			else
				{
				$cache_file = $this->cache_feeds_dir.'/'.$this->cache_feeds_prefix.'_cache_'.md5(serialize($sourcesArray).'?limit='.$this->items_limit.'&html='.$this->stripHTML.'&date='.$this->date_format.'&cdata='.$this->CDATA.'&cp='.$this->cp.'&kidx_rule='.$this->kidx_rule);
				}
			
			if (($this->cache_time != 0) AND (file_exists($cache_file) == TRUE))
				{
				$timedif = (time() - filemtime($cache_file));
				}
			else $timedif = $this->cache_time;
				
			if ($timedif < $this->cache_time)
				{
				// cached file is fresh enough, return cached array
				$result = $this->_LoadCacheFile($cache_file);
				// set 'cached' to 1 only if cached file is correct
				if ($result)
					{
					$result['cached'] = 1;
					}
				}
			else
				{
				// cached file is too old, create new
				$result = $this->ParseFromSeveralSources($sourcesArray);
				
				if ($result)
					{
					$result['sources'] = $this->_SOURCES;
					if (($result['missingSource'] == TRUE) AND ($this->cache_feeds_if_failed == FALSE))
						{
						// Not allow save if missing sources
						}
					else
						{
						$this->_SaveCacheFileAs($cache_file,$result);
						}
					$result['cached'] = 0;
					}
				}
			}
		else
			{
			$result = $this->ParseFromSeveralSources($sourcesArray);
			if ($result)
				{
				$result['sources'] = $this->_SOURCES;
				$result['cached'] = 0;
				}
			}
			
		if ($result['cached'] == 1)
			{
			$this->_SOURCES = $result['sources'];
			$result['updatedTime'] = filemtime($cache_file);
			}
		else if ($result)
			{
			$result['updatedTime'] = time();
			}

		return $result;
		}
		
	function _SourceKIDX($urlPath)
		{
		if ((substr($urlPath, 0, 7) == 'http://') OR (substr($urlPath, 0, 8) == 'https://'))
			{
			return md5($urlPath);
			}
		else return md5(realpath($urlPath));
		}
	
	function _URL2FileName($urlPath)
		{
		return str_replace(array(':','/','.','?','=','&',';','%','@','_','#','*'),'-',$urlPath);
		}
		
	function ParseFromSeveralSources($sourcesArray)
		{
		$this->_USE_SEVERAL_SOURCES = TRUE;
		$this->_STARTED_INDEX = 0;
		$_ALL_SOURCES_ITEMS_LIMIT = $this->items_limit;
		$this->items_limit = $this->items_limit_per_source;
		if ($this->kidx_rule == 'link/date+title')
			{
			$_ALL_SOURCES_KIDX_RULE = 'date+title';
			$this->kidx_rule = 'link';
			}
		else if ($this->kidx_rule == 'date+title/link')
			{
			$_ALL_SOURCES_KIDX_RULE = 'link';
			$this->kidx_rule = 'date+title';
			}
		else if ($this->kidx_rule != '')
			{
			$_ALL_SOURCES_KIDX_RULE = $this->kidx_rule;
			}
		else
			{
			$_ALL_SOURCES_KIDX_RULE = 'guid';
			$this->kidx_rule = 'guid';
			}
		
		// Search current cached files
		if ($this->cache_feed_dir != '')
			{
			foreach($this->_SOURCES as $source_kidx => $source)
				{
				$cacheFilename = $this->_SourceCacheFileName($source_kidx,$this->items_limit,$this->stripHTML,$this->date_format,$this->CDATA,$this->cp,$this->kidx_rule);
				$cache_file = $this->cache_feed_dir.'/'.$cacheFilename;
				if (file_exists($cache_file))
					{
					$this->_SOURCES[$source_kidx]['cachedFileName']=$cacheFilename;
					$this->_SOURCES[$source_kidx]['updatedTime'] = filemtime($cache_file);
					}
				else $this->_SOURCES[$source_kidx]['updatedTime'] = 0;
				
				$errorFilename = 'mylr_content_'.$this->_URL2FileName($source['url']).'.txt';
				$error_file = $this->cache_errors_dir.'/'.$errorFilename;
				if (file_exists($error_file))
					{
					$this->_SOURCES[$source_kidx]['errorFileName']=$errorFilename;
					$this->_SOURCES[$source_kidx]['errorTime'] = filemtime($error_file);
					}
				else $this->_SOURCES[$source_kidx]['errorTime'] = 0;
				}
				
			// Re-order sources array
			uasort($this->_SOURCES,'mYLR_CompareSourcesTime');
			}
		
		$result['items'] = array(); // create array even if there are no items
		$result['namespaces'] = array();
		$i = 0;
		$sources_nb = count($sourcesArray);
		foreach($this->_SOURCES as $source_kidx => $source)
			{
			//$source_kidx = $this->_SourceKIDX($source);
			unset($oneresult);
			
			// How many time we could allow to download this source ?
			if ($this->max_execution_time !== 0)
				{
				$timeOut = ceil(($this->max_execution_time - (time() - $this->_STARTED_TIME)) / ($sources_nb - $i));
				if ($this->timeOut < $this->minTimeOut)	$this->timeOut = $this->minTimeOut;
				}
				
			$oneresult = $this->GetFromOneSource($source['url'],$source_kidx);
			if ($oneresult)
				{
				$result['namespaces'] = array_merge($result['namespaces'],$oneresult['namespaces']);
				}
			
			if ($oneresult AND (count($oneresult['items']) > 0))
				{
				foreach($oneresult['items'] as $kidx => $item)
					{
					if ($item['category'] == '')
						{
						$oneresult['items'][$kidx]['category'] = $oneresult['category'];
						}
						
					if ($item['source'] != '')
						{
						$oneresult['items'][$kidx]['source_orig'] = $item['source'];
						}
					$oneresult['items'][$kidx]['source'] = $oneresult['title'];
					if ($item['source_url'] != '')
						{
						$oneresult['items'][$kidx]['source_orig_url'] = $item['source_url'];
						}
					$oneresult['items'][$kidx]['source_url'] = $source['url'];
					
					$oneresult['items'][$kidx]['source_link'] = $oneresult['link'];
					$oneresult['items'][$kidx]['source_kidx'] = $source_kidx;
					}
				
				// $result['items'] = array_merge($result['items'],$oneresult['items']);
				// Manual array merge
				$addedItems = 0;
				foreach($oneresult['items'] as $kidx => $item)
					{
					// $item['link'] = mYLR_URLunEntities($item['link']); //Deja fait normalement
					
					if ($_ALL_SOURCES_KIDX_RULE == 'guid')
						{
						// Create unique index (with MD5) from guid or link for this item
						if (isset($item['guid']))
							{
							$apply_kidx = md5($item['guid']);
							}
						else if (isset($item['pubTimeStamp']) AND isset($item['link']))
							{
							$apply_kidx = md5($item['pubTimeStamp'].$item['link']);
							$item['guid'] = $apply_kidx;
							$item['guid_isPermaLink'] = FALSE;
							}
						else if (isset($item['pubTimeStamp']) AND isset($oneresult['link']))
							{
							$apply_kidx = md5($item['pubTimeStamp'].$oneresult['link']);
							$item['guid'] = $apply_kidx;
							$item['link'] = $oneresult['link'];
							$item['guid_isPermaLink'] = FALSE;
							}
						// C'est inacceptable :o|
						else continue;
						}
					else if ($_ALL_SOURCES_KIDX_RULE == 'link')
						{
						// Create unique index (with MD5) from link for this item
						if (isset($item['link']))
							{
							$apply_kidx = md5($item['link']);
							}
						// C'est inacceptable :o|
						else continue;
						}
					else if ($_ALL_SOURCES_KIDX_RULE == 'date+title')
						{
						// Create unique index (with MD5) from date & title for this item
						if ((isset($item['pubTimeStamp'])) AND ($item['title'] != ''))
							{
							$apply_kidx = md5(gmdate('dmY',$item['pubTimeStamp']).$this->_StandardizedStr($item['title']));
							}
						// C'est inacceptable :o|
						else continue;
						}
					else $apply_kidx=$kidx;
					
					$item['kidx'] = $apply_kidx;
					
					if (isset($result['items'][$apply_kidx]))
						{
						if ($result['items'][$apply_kidx]['pubTimeStamp'] < $item['pubTimeStamp'])
							{
							unset($result['items'][$apply_kidx]);
							$result['items'][$apply_kidx] = $item;
							$addedItems++; // pour provoquer le tri par date
							}
						}
					else
						{
						$result['items'][$apply_kidx] = $item;
						$addedItems++;
						}
					}
				
				$this->_STARTED_INDEX = $this->_STARTED_INDEX + $addedItems;
				
				$this->cache_time = $this->cache_time + $this->incremental_cache_time;
				}
			else if ($oneresult)
				{
				// Just empty feed
				}
			else $result['missingSource'] = TRUE;
			
			$i++;
			}
			
		$this->items_limit = $_ALL_SOURCES_ITEMS_LIMIT;
				
		if ($this->_STARTED_INDEX > 0)
			{
			uasort($result['items'],'mYLR_CompareItemsTime');
			
			if (($this->items_limit != 0) AND ($this->_STARTED_INDEX > $this->items_limit))
				{
				$this->_ArrayPop($result['items'],$this->items_limit);
				}
				
			$result['severalSources'] 	= TRUE;
			$result['items_count'] 		= count($result['items']);
			
			$result['namespaces'] = array_values(array_unique($result['namespaces']));
			
			// return result
			return $result;
			}
		else return FALSE;
		
		}
		
	function _SourceCacheFileName($kidx,$limit=0,$stripHTML,$dateFormat='',$CDATA,$CP,$kidx_rule='guid')
		{
		return $this->cache_feed_prefix.'_cache_'.md5($kidx.strtolower('?limit='.$limit.'&html='.$stripHTML.'&date='.$dateFormat.'&cdata='.$CDATA.'&cp='.$CP.'&kidx_rule='.$kidx_rule));
		}
		
	// -------------------------------------------------------------------
	// Parse RSS file and returns associative array.
	// -------------------------------------------------------------------
	function GetFromOneSource($rss_url,$source_kidx='')
		{
		if ($source_kidx == '') $source_kidx = $this->_SourceKIDX($rss_url);
		
		// If CACHE ENABLED
		if ($this->cache_feed_dir != '')
			{
			if ($this->_SOURCES[$source_kidx]['cachedFileName'])
				{
				$cacheFilename = $this->_SOURCES[$source_kidx]['cachedFileName'];
				$cache_file = $this->cache_feed_dir.'/'.$cacheFilename;
				$cacheFileExists = TRUE;
				$cacheFileTime = $this->_SOURCES[$source_kidx]['updatedTime'];
				}
			else
				{
				$cacheFilename = $this->_SourceCacheFileName($source_kidx,$this->items_limit,$this->stripHTML,$this->date_format,$this->CDATA,$this->cp,$this->kidx_rule);
				$cache_file = $this->cache_feed_dir.'/'.$cacheFilename;
				$cacheFileExists = file_exists($cache_file);
				$cacheFileTime = 0;
				if ($cacheFileExists == TRUE) $cacheFileTime = filemtime($cache_file);
				}
			
			if ($this->_SOURCES[$source_kidx]['errorFileName'])
				{
				$errorFilename = $this->_SOURCES[$source_kidx]['errorFileName'];
				$error_content_file = $this->cache_errors_dir.'/'.$errorFilename;
				$errorFileExists = TRUE;
				$errorFileTime = $this->_SOURCES[$source_kidx]['errorTime'];
				}
			else if ($this->retry_delay > 0)
				{
				$errorFilename = 'mylr_content_'.$this->_URL2FileName($rss_url).'.txt';
				$error_content_file = $this->cache_errors_dir.'/'.$errorFilename;
				$errorFileExists = file_exists($error_content_file);
				$errorFileTime = 0;
				if ($errorFileExists == TRUE) $errorFileTime = filemtime($error_content_file);
				}

			if (($this->retry_delay > 0) AND ($errorFileExists == TRUE) AND ((time() - $errorFileTime) <= $this->retry_delay))
				{
				$timedif = 0;
				}
			else if (($this->cache_time != 0) AND ($cacheFileExists == TRUE))
				{
				$timedif = (time() - $cacheFileTime);
				}
			else $timedif = $this->cache_time;
				
			if (($timedif < $this->cache_time) OR (($this->query_limit > 0) AND ($this->query_limit <= $this->_QUERY_COUNT)))
				{
				// cached file is fresh enough, return cached array
				$result = $this->_LoadCacheFile($cache_file);
				// set 'cached' to 1 only if cached file is correct
				if ($result)
					{
					$result['cached'] = 1;
					}
				else
					{
					$this->_LAST_ERROR_MESSAGES[] = "Fail load '$rss_url' cached file.";
					}
				}
			else
				{
				if ( (($this->max_execution_time === 0) OR (time()-$this->_STARTED_TIME <= $this->max_execution_time)) AND $result=$this->Parse($rss_url,$source_kidx) )
					{
					if (($this->min_items_required !== 0) AND ($result['items_count'] < $this->min_items_required))
						{
						// Not enough items
						$this->_LAST_ERROR_MESSAGES[] = 'Not enough items obtain from '.$rss_url.'';
						
						if (($this->use_cache_if_failed==TRUE) AND ($cacheFileExists == TRUE))
							{
							$result = $this->_LoadCacheFile($cache_file);
							// set 'cached' to 1 only if cached file is correct
							if ($result)
								{
								$result['cached'] = 1;
								}
							else
								{
								$this->_LAST_ERROR_MESSAGES[] = "Fail load '$rss_url' cached file.";
								}
							}
						// Don't use cache
						//$result = FALSE;
						else $result['cached'] = 0;
						}
					else
						{
						$result['cached'] = 0;
						// cached file is too old, create new
						if ($this->_USE_SEVERAL_SOURCES == FALSE)
							{
							$result['sources'][$source_kidx]['title'] 		 = $result['title'];
							$result['sources'][$source_kidx]['link'] 		 = $result['link'];
							$result['sources'][$source_kidx]['items_count']  = $result['items_count'];
							$result['sources'][$source_kidx]['cached'] 	 	 = $result['cached'];
							$result['sources'][$source_kidx]['updatedTime']  = $result['updatedTime'];
							$result['sources'][$source_kidx]['feed_format']	 = $result['feed_format'];
							$result['sources'][$source_kidx]['generator']	 = $result['generator'];
							$result['sources'][$source_kidx]['namespaces']	 = $result['namespaces'];
							}
						if (($this->_USE_SEVERAL_SOURCES == FALSE) OR ($this->cache_all == TRUE))
							{
							$this->_SaveCacheFileAs($cache_file,$result);
							}
						}
					}
				// Feed not found or failed
				else if (($this->use_cache_if_failed == TRUE) AND ($cacheFileExists == TRUE))
					{
					$result = $this->_LoadCacheFile($cache_file);
					// set 'cached' to 1 only if cached file is correct
					if ($result)
						{
						$result['cached'] = 1;
						}
					else
						{
						$this->_LAST_ERROR_MESSAGES[] = "Fail load '$rss_url' cached file.";
						}
					}
				}
			}
		// If CACHE DISABLED >> load and parse the file directly
		else
			{
			$result = $this->Parse($rss_url,$source_kidx);
			if ($result)
				{
				$result['cached'] = 0;
				}
			}
			
		if ($result['cached'] == 1)
			{
			$result['updatedTime'] = $cacheFileTime;
			$this->_SOURCES[$source_kidx]['cachedFileName'] = $cacheFilename;
			}
		else if ($result) $result['updatedTime'] = time();
		
		if ($result)
			{
			$this->_SOURCES[$source_kidx]['title'] 		 = $result['title'];
			$this->_SOURCES[$source_kidx]['link'] 		 = $result['link'];
			$this->_SOURCES[$source_kidx]['items_count'] = $result['items_count'];
			$this->_SOURCES[$source_kidx]['cached'] 	 = $result['cached'];
			$this->_SOURCES[$source_kidx]['updatedTime'] = $result['updatedTime'];
			$this->_SOURCES[$source_kidx]['feed_format'] = $result['feed_format'];
			$this->_SOURCES[$source_kidx]['generator']	 = $result['generator'];
			$this->_SOURCES[$source_kidx]['namespaces']	 = $result['namespaces'];
			}
		else
			{
			$this->_SOURCES[$source_kidx]['items_count'] 	= 0;
			$this->_SOURCES[$source_kidx]['missingSource']	= TRUE;
			}
			
		// return result
		return $result;
		}
	
	function my_convert_encoding($encStr='')
		{
		$result = $encStr;
		//$this->rsscp = '';
		$strCP = $this->rsscp;
		
		// If code page is set convert character encoding to required
		if (strtoupper($this->cp) == 'UTF-8')
			{
			if (in_array(strtolower($strCP),array('iso-8859-1','windows-1252')))
				{
				$result=str_replace('Ä','&'.'euro;',$result);
				//$result = utf8_decode($result);
				
				$result = utf8_encode($result);
				}
			$result=str_replace(array('‚Äô','‚Äò'),"'",$result);
			$result=str_replace(array('‚Äú','‚Äù'),'"',$result);
			$result=str_replace('≈ì','oe',$result);
			$result=str_replace('&'.'euro;','‚Ç¨',$result);
			$result=str_replace('‚Äì','--',$result);
			$result=str_replace('‚Ä¶','...',$result);
			}
		else if ($this->cp != '')
			{
			if(function_exists('mb_convert_encoding'))
				{
				if ($strCP == '')
					{
					$this->rsscp = $strCP = 'auto';
					$this->_LAST_ERROR_MESSAGES[] = "mb_convert_encoding() not allow blank value for encodage";
					}
					
				if (in_array(strtolower($strCP),array('auto','utf-8')))
					{
					$result=str_replace('‚Äì','--',$result);
					$result=str_replace('‚Ä¶','...',$result);
					$result=str_replace(array('‚Äô','‚Äò'),"'",$result);
					$result=str_replace(array('‚Äú','‚Äù','Àù'),'"',$result);
					$result=str_replace(array('≈ì'),'oe',$result);
					$result=str_replace('‚Ç¨','&'.'euro;',$result);
					$result=str_replace('‚Äâ',' ',$result);
					}
				
				$result=str_replace('ú','oe',$result);
				
				$result = @mb_convert_encoding($result, $this->cp, $strCP);
				
				if (in_array(strtolower($this->cp),array('iso-8859-1','windows-1252')))
					{
					$result=str_replace(array('¥','í'),"'",$result);
					$result=str_replace(array('Àù'),'"',$result);
					}
				}
			else if (function_exists('iconv'))
				{
				if ($strCP == 'auto')
					{
					$this->rsscp = $strCP = '';
					$this->_LAST_ERROR_MESSAGES[] = "iconv() not allow 'auto' value for encodage";
					}
				$result = @iconv($strCP, $this->cp.'//TRANSLIT', $result);
				}
			else
				{
				// Do nothing :o(
				}
			}
		
		return $result;
		}
		
	function process_cdata($cdStr='')
		{
		// Process CDATA (if present)
		if ($this->CDATA == 'content')
			{ // Get CDATA content (without CDATA tag)
			$cdStr = mYLR_StripCDATA($cdStr);
			}
		else if ($this->CDATA == 'strip')
			{ // Strip CDATA
			$cdStr = mYLR_StripCDATA($cdStr);
			}
			
		return $cdStr;
		}
	
	// -------------------------------------------------------------------
	// Modification of preg_match(); return trimed field with index 1
	// from 'classic' preg_match() array output
	// -------------------------------------------------------------------
	function my_preg_match ($pattern, $subject) {
		// start regullar expression
		preg_match($pattern, $subject, $out);

		// if there is some result... process it and return it
		if(isset($out[1])) {
			// Process CDATA (if present)
			$out[1] = $this->process_cdata($out[1]);

			// If code page is set convert character encoding to required
			if ($this->cp != '')
				{
				$out[1] = $this->my_convert_encoding($out[1]);
				}
			// Return result
			$out[1] = str_replace("\r\n","\n",trim($out[1]));
			$out[1] = str_replace("\r","\n",$out[1]);
			return $out[1];
		} else {
		// if there is NO result, return empty string
			return '';
		}
	}
	
	function GetContent($path='',$timeOut)
		{
		// TODO: check timeOut
		$this->_QUERY_COUNT++;
        $raw_content = ''; 
		if ($f = @fopen($path, 'rb'))
			{ 
            while (!feof($f))
				{ 
                $raw_content .= fgets($f, 4096); 
            	}
            fclose($f); 
			}
		else
			{
			$this->_LAST_ERROR_MESSAGES[] = "Failed to fopen('$path')";
			$this->_QUERY_COUNT++;
			if ($arraycontent = @file($path))
				{
				$raw_content = implode('', $arraycontent);
				}
			else
				{
				$this->_LAST_ERROR_MESSAGES[] = "Failed to file('$path')";
				}
			}
				
		return mYLR_StripHTMLcomment($raw_content);
		}

	function _StandardizedStr($title)
		{
		$title = mYLR_StripCDATA($title);
		$title = $this->unhtmlentities($title);
		$title = strip_tags($title);
		$title = mYLR_UnAccentuate($title);
		$title = str_replace(array('∑','¥','[',']','´','ª','"','?','{','}','<','>','(',')',"'",':','.',',',';','!','%','-','_','/','\\','+','*','&','#'),' ',$title);
		$title = strtolower($title);
		$title = preg_replace("/[^\w\d]/"," ",$title);
		$title = mYLR_Trim($title);
		
		return $title;
		}
		
	// -------------------------------------------------------------------
	// Parse() is private method used by Get() to load and parse RSS file.
	// Don't use Parse() in your scripts - use Get($rss_file) instead.
	// -------------------------------------------------------------------
	function Parse ($rss_url,$source_kidx='')
		{
		GLOBAL $MYLR_FORMATS;
		GLOBAL $MYLR_XMLNS;
		
		$parsing_started_time = time();
		if ($source_kidx == '') $source_kidx = $this->_SourceKIDX($rss_url);
		if ($this->_SOURCES[$source_kidx]['errorFileName'])
			{
			$errorFilename = $this->_SOURCES[$source_kidx]['errorFileName'];
			}
		else
			{
			$errorFilename = 'mylr_content_'.$this->_URL2FileName($rss_url).'.txt';
			}
		$error_content_file = $this->cache_errors_dir.'/'.$errorFilename;
		
		// Open and load RSS file
		if (($this->useSnoopy) AND (class_exists('Snoopy')) AND ((substr($rss_url, 0, 7) == 'http://') OR (substr($rss_url, 0, 8) == 'https://')))
			{
			// With Snoopy client
			$client = new Snoopy();
			$client->agent = $this->userAgent;
			$client->maxframes = 1; // Some feeds use a frame !!!! :o.
			$client->maxredirs = 4;
			$client->offsiteok = true;
			$client->passcookies = true; // Bugged ? :o(
			if ($this->timeOut > 0) $client->read_timeout = $this->timeOut;
			if ($this->cache_dir != '') $client->temp_dir = $this->cache_dir;
			
			$this->_QUERY_COUNT++;
			
			if (@$client->fetch($rss_url))
				{
				if ($client->lastredirectaddr != '')
					{
					$this->_LAST_ERROR_MESSAGES[] = "Snoopy detect and follow '$rss_url' redirect to '$client->lastredirectaddr'";
					}
				
				if (is_Array($client->results))
					{
					// If framed :o|
					$rss_content = trim(mYLR_StripHTMLcomment(implode('', $client->results)));
					}
				else
					{
					$rss_content = trim(mYLR_StripHTMLcomment($client->results));
					}
					
				if ($client->timed_out === TRUE)
					{
					$this->_LAST_ERROR_MESSAGES[] = "Snoopy fetch('$rss_url') timed out, Error: ".$client->error.", Response code: ".$client->response_code."";
					$rss_content = ''; // Better to ignore it
					}
				else if (strlen(trim($rss_content)) == 0)
					{
					$this->_LAST_ERROR_MESSAGES[] = "Snoopy fetch('$rss_url') empty content, Error: ".$client->error.", Response code: ".$client->response_code."";
					if (($this->timeOut === 0) OR (time()-$parsing_started_time < $this->timeOut))
						{
						$rss_content = $this->GetContent($rss_url,($this->timeOut-(time()-$parsing_started_time)));
						}
					}
				else if (strpos(strtolower(substr($rss_content,0,350)),'<html') !== FALSE)
					{
					// Stranger content like HTML page
					//if ($this->cache_dir != '') $this->_SaveRawFileAs($error_content_file,$rss_content);
					$this->_LAST_ERROR_MESSAGES[] = "Snoopy fetch('$rss_url') HTML content, Error: ".$client->error.", Response code: ".$client->response_code."";
					if (($this->timeOut === 0) OR (time()-$parsing_started_time < $this->timeOut))
						{
						$rss_content = $this->GetContent($rss_url,($this->timeOut-(time()-$parsing_started_time)));
						}
					}
				}
			else
				{
				$this->_LAST_ERROR_MESSAGES[] = "Snoopy failed to fetch('$rss_url'), Error: ".$client->error.", Response code: ".$client->response_code."";
				}
			}
		else
			{
			$rss_content = trim($this->GetContent($rss_url,$this->timeOut));
			}
			
		// Clean-up first lines (and prevent PHP/Apache errors displayed)
		if (($posXML = strpos($rss_content,'<?xml')) AND ($posXML > 0))
			{
			$rss_content = trim(substr($rss_content,$posXML));
			}
		
		$rss_content_chunk = trim(strtolower(substr($rss_content,0,350)));
		
		if (strlen($rss_content_chunk) == 0)
			{
			// Error in opening return False
			$this->_LAST_ERROR_MESSAGES[] = "No content downloaded from '$rss_url'";
			if ($this->cache_dir != '')
				{
				//$this->_SaveRawFileAs($error_content_file,'empty');
				@touch($error_content_file);
				}
			return False;
			}
		else if (strpos($rss_content_chunk,'<html') !== FALSE)
			{
			$this->_LAST_ERROR_MESSAGES[] = "HTML content downloaded from '$rss_url'";
			if ($this->cache_dir != '')
				{
				//$this->_SaveRawFileAs($error_content_file,$rss_content);
				@touch($error_content_file);
				}
			return False;
			}
		else if (strpos($rss_content_chunk,'<feed') !== FALSE)
			{
			//$this->_LAST_ERROR_MESSAGES[] = "Atom content downloaded from '$rss_url'";
			return $this->_ParseAtom($rss_url,$source_kidx,$rss_content);
			}
		else if ((strpos($rss_content_chunk,'<rss') !== FALSE) OR (strpos($rss_content_chunk,'<rdf') !== FALSE))
			{
			$result = array();
			$result['source_url'] 	= $rss_url;
			$result['source_kidx'] 	= $source_kidx;
			$feed_format = '';
			if (strpos($rss_content_chunk,'<rss') !== FALSE)
				{
				$feed_format = 'rss';
				}
			else if (strpos($rss_content_chunk,'<rdf') !== FALSE)
				{
				$feed_format = 'rdf';
				}
			$result['feed_format'] = $feed_format;
			$result['generator'] = '';

			// Parse document encoding
			$result['encoding'] = $this->my_preg_match("'\sencoding=[\'\"](.*?)[\'\"]'si", $rss_content);
			// if document codepage is specified, use it
			if ($result['encoding'] != '')
				{ $this->rsscp = $result['encoding']; } // This is used in my_preg_match()
			// otherwise use the default codepage
			else
				{ $this->rsscp = $this->default_cp; } // This is used in my_preg_match()
			
			// detect extension namespaces
			preg_match_all("'\sxmlns:(.*?)=[\'\"](.*?)[\'\"]'si", $rss_content, $nspaces_results);
			$result['namespaces'] = $nspaces_results[1];
			$result['namespaces'] = array_values(array_unique($result['namespaces']));
			
			$this->_InitSupportedTags($result['feed_format'],$result['namespaces']);
			
			// Clean channel info
			$channel_content = '';
			if (($openChannel = strpos($rss_content,'<channel>')) AND ($openChannel !== FALSE))
				{
				$channel_content = trim(substr($rss_content,$openChannel+9));
				if (($closeChannel = strpos($channel_content,'</channel>')) AND ($closeChannel !== FALSE))
					{
					$channel_content = trim(substr($channel_content,0,$closeChannel));
					}
				}
			else
				{
				preg_match("'<channel.*?>(.*?)</channel>'si", $rss_content, $out_channel);
				$channel_content = trim($out_channel[1]);
				}
			if ($result['feed_format'] == 'rdf')
				{
				$channel_content = trim($this->_StripRdfItems($channel_content));
				}
			else
				{
				$channel_content = trim($this->_StripItems($channel_content));
				}
				
			// Parse CHANNEL info
			foreach($this->channeltags as $channeltag)
				{
				$temp = trim($this->my_preg_match("'<$channeltag.*?>(.*?)</$channeltag>'si", $channel_content));
				if ($temp != '') $result[$channeltag] = $temp; // Set only if not empty
				}
			
			// If lastBuildDate is valid
			if (($result['lastBuildDate'] != '') AND (($timestamp = strtotime($result['lastBuildDate'])) !== -1) AND ($timestamp > 0))
				{
				$result['lastBuildTimeStamp'] = $timestamp;
				// If date_format is specified
				if ($this->date_format != '') {
					// convert lastBuildDate to specified date format
					$result['lastBuildDate'] = gmdate($this->date_format, $timestamp);
					}
				}
			else if ($result['lastBuildDate'] != '')
				{
				$this->_LAST_ERROR_MESSAGES[] = "'$rss_url'".' has bad lastBuildDate format';
				}

			// If pubDate is valid
			if (($result['pubDate'] != '') AND (($timestamp = strtotime($result['pubDate'])) !== -1))
				{
				$result['pubTimeStamp'] = $timestamp;
				// If date_format is specified
				if ($this->date_format != '') {
					// convert lastBuildDate to specified date format
					$result['pubDate'] = gmdate($this->date_format, $timestamp);
					}
				}
			else if ($result['dc:date'] != '')
				{
				$timestamp = mYLR_DCDate2UnixTimeStamp($result['dc:date']);
				$result['pubTimeStamp'] = $timestamp;
				if ($this->date_format != '')
					{
					// create pubDate to specified date format
					$result['pubDate'] = gmdate($this->date_format, $timestamp);
					}
				else
					{
					// create pubDate to GMT/CUT date format
					$result['pubDate'] = gmdate('D, d M Y H:i:s \G\M\T', $timestamp);
					}
				}
			else if ($result['lastBuildTimeStamp'] != '')
				{
				$timestamp = $result['lastBuildTimeStamp'];
				$result['pubTimeStamp'] = $timestamp;
				if ($this->date_format != '')
					{
					// create pubDate to specified date format
					$result['pubDate'] = gmdate($this->date_format, $timestamp);
					}
				else
					{
					// create pubDate to GMT/CUT date format
					$result['pubDate'] = gmdate('D, d M Y H:i:s \G\M\T', $timestamp);
					}
				}
			else if ($result['pubDate'] != '')
				{
				$this->_LAST_ERROR_MESSAGES[] = "'$rss_url'".' has bad pubDate format';
				}

			// Parse TEXTINPUT info
			// Todo: fix for <textInput></textInput>
			preg_match("'<textinput(|[^>]*[^/])>(.*?)</textinput>'si", $channel_content, $out_textinfo);
			// This a little strange regexp means:
			// Look for tag <textinput> with or without any attributes, but skip truncated version <textinput /> (it's not beggining tag)
			if (isset($out_textinfo[2])) {
				foreach($MYLR_FORMATS[$feed_format]['channel_textinput_tags'] as $textinputtag) {
					$temp = $this->my_preg_match("'<$textinputtag.*?>(.*?)</$textinputtag>'si", $out_textinfo[2]);
					if ($temp != '') $result['textinput_'.$textinputtag] = $temp; // Set only if not empty
				}
			}
			// Parse IMAGE info
			preg_match("'<image.*?>(.*?)</image>'si", $channel_content, $out_imageinfo);
			if (isset($out_imageinfo[1])) {
				foreach($MYLR_FORMATS[$feed_format]['channel_image_tags'] as $imagetag) {
					$temp = $this->my_preg_match("'<$imagetag.*?>(.*?)</$imagetag>'si", $out_imageinfo[1]);
					if ($temp != '') $result['image_'.$imagetag] = $temp; // Set only if not empty
				}
			}
			
			$result['items'] = array(); // create array even if there are no items
			// Parse ITEMS
			preg_match_all("'<item(| .*?)>(.*?)</item>'si", $rss_content, $items);
			$rss_items = $items[2];
			$i = 0;
			foreach($rss_items as $rss_item)
				{
				$itemResult = array();
				$rss_item = trim($rss_item);	
				if ($rss_item === '') continue;
				
				// Parse item tags to $itemResult[]
				foreach($this->itemtags as $itemtag)
					{
					$temp = $this->my_preg_match("'<$itemtag\b.*?>(.*?)</$itemtag>'si", $rss_item);
					if ($temp != '')
						{
						$itemResult[$itemtag] = $temp; // Set only if not empty
						}
					}
				
				if (count($itemResult) == 0)
					{
					// On s'fout de la gueule du monde l‡ ?!
					continue;
					}
				
				// Parse GUID info
				if (isset($itemResult['guid']))
					{
					preg_match("'<guid(.*?)>.*?</guid>'si", $rss_item, $out_source);
					$temp = $this->my_preg_match("'\sisPermaLink=[\'\"](.*?)[\'\"]'si", $out_source[1]);
					if ($temp == 'false')
						{
						// Set only if not empty
						$itemResult['guid_isPermaLink'] = FALSE;
						}
					else if ($temp == 'true')
						{
						// Set only if not empty
						$itemResult['guid_isPermaLink'] = TRUE;
						}
					}
					
				if (isset($itemResult['link']) == FALSE)
					{
					if ($itemResult['alink'] != '')
						{
						$itemResult['link'] = $itemResult['alink'];
						}
					}
					
				if ($this->useOrigLink == TRUE)
					{
					if ($itemResult['feedburner:origLink'] != '')
						{
						$itemResult['feedburner:trackLink'] = $itemResult['link'];
						$itemResult['link'] = $itemResult['feedburner:origLink'];
						unset($itemResult['feedburner:origLink']);
						}
					else if ($itemResult['fs:srclink'] != '')
						{
						$itemResult['fs:trackLink'] = $itemResult['link'];
						$itemResult['link'] = $itemResult['fs:srclink'];
						unset($itemResult['fs:srclink']);
						}
					else if ($result['generator'] == 'Feediz')
						{
						$itemResult['feediz:trackLink'] = $itemResult['link'];
						$itemResult['link'] = $itemResult['guid'];;
						}
						
					if (strpos($itemResult['link'],'/0L') > 1)
						{
						$itemResult['feedsportal:trackLink'] = $itemResult['link'];
						$itemResult['link'] = mYLR_DecodeFeedPortalURL($itemResult['link']);
						}
						
					if (strpos($itemResult['link'],'xiti.com/go.url') > 1)
						{
						$itemResult['xiti:trackLink'] = $itemResult['link'];
						$itemResult['link'] = mYLR_DecodeXitiURL($itemResult['link']);
						}
						
					if (strpos($itemResult['link'],'ns_campaign=') > 1)
						{
						$itemResult['nedstat:trackLink'] = $itemResult['link'];
						$itemResult['link'] = mYLR_StripNedStatFragment($itemResult['link']);
						}
						
					if (strpos($itemResult['link'],'*'))
						{
						$itemResult['yahoo:trackLink'] = $itemResult['link'];
						$itemResult['link'] = urldecode(substr(strrchr($itemResult['link'],'*'),1));
						}
						
					if (strpos($itemResult['link'],'xtor='))
						{
						$itemResult['xtor:trackLink'] = $itemResult['link'];
						$itemResult['link'] = mYLR_StripXtorFragment($itemResult['link']);
						}
						
					if (strpos($itemResult['link'],'utm_'))
						{
						$itemResult['utm:trackLink'] = $itemResult['link'];
						$itemResult['link'] = mYLR_StripUtmFragment($itemResult['link']);
						}
						
					if (strpos($itemResult['link'],'?rss'))
						{
						$itemResult['rss:trackLink'] = $itemResult['link'];
						$itemResult['link'] = mYLR_StripRssFragment($itemResult['link']);
						}
					}
					
				// If pubDate is valid
				if (($itemResult['pubDate'] != '') AND (($timestamp = strtotime($itemResult['pubDate'])) !== -1))
					{
					$itemResult['pubTimeStamp'] = $timestamp;
					// If date_format is specified
					if ($this->date_format != '')
						{
						// convert pubDate to specified date format
						$itemResult['pubDate'] = gmdate($this->date_format, $timestamp);
						}
					}
				else if ($itemResult['dc:date'] != '')
					{
					$timestamp = mYLR_DCDate2UnixTimeStamp($itemResult['dc:date']);
					$itemResult['pubTimeStamp'] = $timestamp;
					if ($this->date_format != '')
						{
						// create pubDate to specified date format
						$itemResult['pubDate'] = gmdate($this->date_format, $timestamp);
						}
					else
						{
						// create pubDate to GMT/CUT date format
						$itemResult['pubDate'] = gmdate('D, d M Y H:i:s \G\M\T', $timestamp);
						}
					}
				else if ($itemResult['dcterms:modified'] != '')
					{
					$timestamp = mYLR_DCDate2UnixTimeStamp($itemResult['dcterms:modified']);
					$itemResult['pubTimeStamp'] = $timestamp;
					if ($this->date_format != '')
						{
						// create pubDate to specified date format
						$itemResult['pubDate'] = gmdate($this->date_format, $timestamp);
						}
					else
						{
						// create pubDate to GMT/CUT date format
						$itemResult['pubDate'] = gmdate('D, d M Y H:i:s \G\M\T', $timestamp);
						}
					}
				else if ($itemResult['a10:updated'] != '')
					{
					$timestamp = mYLR_DCDate2UnixTimeStamp($itemResult['a10:updated']);
					$itemResult['pubTimeStamp'] = $timestamp;
					if ($this->date_format != '')
						{
						// create pubDate to specified date format
						$itemResult['pubDate'] = gmdate($this->date_format, $timestamp);
						}
					else
						{
						// create pubDate to GMT/CUT date format
						$itemResult['pubDate'] = gmdate('D, d M Y H:i:s \G\M\T', $timestamp);
						}
					}
				else if ($itemResult['pubDate'] != '')
					{
					$timestamp = mYLR_DCDate2UnixTimeStamp($itemResult['pubDate']);
					$itemResult['pubTimeStamp'] = $timestamp;
					if ($this->date_format != '')
						{
						// create pubDate to specified date format
						$itemResult['pubDate'] = gmdate($this->date_format, $timestamp);
						}
					else
						{
						// create pubDate to GMT/CUT date format
						$itemResult['pubDate'] = gmdate('D, d M Y H:i:s \G\M\T', $timestamp);
						}
					}
				else if ($result['pubTimeStamp'] != '')
					{
					// Use channel pubDate :o|
					$timestamp = $result['pubTimeStamp']+count($rss_items)-$i;
					$itemResult['pubTimeStamp'] = $timestamp;
					if ($this->date_format != '')
						{
						// create pubDate to specified date format
						$itemResult['pubDate'] = gmdate($this->date_format, $timestamp);
						}
					else
						{
						// create pubDate to GMT/CUT date format
						$itemResult['pubDate'] = gmdate('D, d M Y H:i:s \G\M\T', $timestamp);
						}
					}
				else
					{
					$this->_LAST_ERROR_MESSAGES[] = 'Item '.$itemResult['guid'].' has not pubDate';
					}
					
				$itemResult['title'] = mYLR_StripCR($itemResult['title']);
				$itemResult['link'] = mYLR_URLunEntities($itemResult['link']);
				
				if ($this->kidx_rule == 'guid')
					{
					// Create unique index (with MD5) from guid or link for this item
					if (isset($itemResult['guid']))
						{
						$kidx = md5($itemResult['guid']);
						}
					else if (isset($itemResult['pubTimeStamp']) AND isset($itemResult['link']))
						{
						$kidx = md5($itemResult['pubTimeStamp'].$itemResult['link']);
						$itemResult['guid'] = $kidx;
						$itemResult['guid_isPermaLink'] = FALSE;
						}
					else if (isset($itemResult['pubTimeStamp']) AND isset($result['link']))
						{
						$kidx = md5($itemResult['pubTimeStamp'].$result['link']);
						$itemResult['guid'] = $kidx;
						$itemResult['link'] = $result['link'];
						$itemResult['guid_isPermaLink'] = FALSE;
						}
					else
						{
						// C'est inacceptable :o|
						continue;
						}
					}
				else if ($this->kidx_rule == 'link')
					{
					// Create unique index (with MD5) from link for this item
					if (isset($itemResult['link']))
						{
						$kidx = md5($itemResult['link']);
						}
					else
						{
						// C'est inacceptable :o|
						continue;
						}
					}
				else if ($this->kidx_rule == 'date+title')
					{
					// Create unique index (with MD5) from date & title for this item
					if ((isset($itemResult['pubTimeStamp'])) AND ($itemResult['title'] != ''))
						{
						//$kidx = md5(gmdate('dmY',$itemResult['pubTimeStamp']).strtolower(str_replace(array(' ','_','(',')','[',']'),'',strip_tags($this->unhtmlentities($itemResult['title'])))));
						$kidx = md5(gmdate('dmY',$itemResult['pubTimeStamp']).$this->_StandardizedStr($itemResult['title']));
						}
					else
						{
						// C'est inacceptable :o|
						continue;
						}
					}
				else
					{
					continue;
					}
					
				if (isset($result['items'][$kidx]))
					{
					if ($result['items'][$kidx]['pubTimeStamp'] > $itemResult['pubTimeStamp'])
						{
						continue;
						}
					else
						{
						unset($result['items'][$kidx]);
						}
					}
				
				$result['items'][$kidx] = $itemResult;
				$result['items'][$kidx]['kidx'] = $kidx;
					
				// Parse multiple category tags
				if (isset($result['items'][$kidx]['category']))
					{
					$result['items'][$kidx]['categories'] = array(); // create array
					
					preg_match_all("'<category(| .*?)>(.*?)</category>'si", $rss_item, $categories);
					$item_categories = $categories[2];
					if (count($item_categories) > 1)
						{
						foreach($item_categories as $item_category)
							{
							$result['items'][$kidx]['categories'][] = $this->my_convert_encoding($this->process_cdata($item_category));
							}
						$result['items'][$kidx]['category'] = $result['items'][$kidx]['categories'][0];
						}
					else
						{
						$result['items'][$kidx]['categories'][] = $result['items'][$kidx]['category'];
						}
						
					$item_categories_props = $categories[1];
					$temp = $this->my_preg_match("'\sdomain=[\'\"](.*?)[\'\"]'si", $item_categories_props[0]);
					if ($temp != '') $result['items'][$kidx]['category_domain'] = $temp; // Set only if not empty
					}
				else if (isset($result['items'][$kidx]['dc:tag']))
					{
					$result['items'][$kidx]['categories'] = array(); // create array
					
					preg_match_all("'<dc:tag(| .*?)>(.*?)</dc:tag>'si", $rss_item, $categories);
					$item_categories = $categories[2];
					if (count($item_categories) > 0)
						{
						foreach($item_categories as $item_category)
							{
							$result['items'][$kidx]['categories'][] = $this->my_convert_encoding($this->process_cdata($item_category));
							}
						$result['items'][$kidx]['category'] = $result['items'][$kidx]['categories'][0];
						}
					else
						{
						$result['items'][$kidx]['categories'][] = $result['items'][$kidx]['dc:tag'];
						}
					}
				else if (isset($result['items'][$kidx]['media:category']))
					{
					$result['items'][$kidx]['categories'] = array(); // create array
					
					preg_match_all("'<media:category(| .*?)>(.*?)</media:category>'si", $rss_item, $categories);
					$item_categories = $categories[2];
					if (count($item_categories) > 0)
						{
						foreach($item_categories as $item_category)
							{
							$result['items'][$kidx]['categories'][] = $this->my_convert_encoding($this->process_cdata($item_category));
							}
						$result['items'][$kidx]['category'] = $result['items'][$kidx]['categories'][0];
						}
					else
						{
						$result['items'][$kidx]['categories'][] = $result['items'][$kidx]['media:category'];
						}
					}
				else if (isset($result['items'][$kidx]['dc:subject']))
					{
					$result['items'][$kidx]['categories'] = array(); // create array
					
					preg_match_all("'<dc:subject(| .*?)>(.*?)</dc:subject>'si", $rss_item, $categories);
					$item_categories = $categories[2];
					if (count($item_categories) > 0)
						{
						foreach($item_categories as $item_category)
							{
							$result['items'][$kidx]['categories'][] = $this->my_convert_encoding($this->process_cdata($item_category));
							}
						$result['items'][$kidx]['category'] = $result['items'][$kidx]['categories'][0];
						}
					else
						{
						$result['items'][$kidx]['categories'][] = $result['items'][$kidx]['dc:subject'];
						}
					}
				
				// Strip HTML tags and other bullshit from DESCRIPTION
				if ($this->stripHTML && $result['items'][$kidx]['description'])
					{
					if (isset($result['items'][$kidx]['content:encoded']) == FALSE) $result['items'][$kidx]['content:encoded'] = $result['items'][$kidx]['description'];
					$result['items'][$kidx]['description'] = mYLR_Trim(strip_tags($this->unhtmlentities($result['items'][$kidx]['description'])));
					}
				// Strip HTML tags and other bullshit from TITLE
				if ($this->stripHTML && $result['items'][$kidx]['title'])
					$result['items'][$kidx]['title'] = mYLR_Trim(strip_tags($this->unhtmlentities($result['items'][$kidx]['title'])));
					
				// Parse SOURCE info
				if (isset($result['items'][$kidx]['source']))
					{
					preg_match("'<source(.*?)>.*?</source>'si", $rss_item, $out_source);
					$temp = $this->my_preg_match("'\surl=[\'\"](.*?)[\'\"]'si", $out_source[1]);
					if ($temp != '') $result['items'][$kidx]['source_url'] = $temp; // Set only if not empty
					}
				else
					{
					$result['items'][$kidx]['source'] = $result['title'];
					$result['items'][$kidx]['source_url'] = $rss_url;
					}
				$result['items'][$kidx]['source_link'] = $result['link'];
				$result['items'][$kidx]['source_kidx'] = $source_kidx;
				
				// Parse ENCLOSURE info
				unset($result['items'][$kidx]['enclosure']); // May not exists
				preg_match("'<enclosure(.*?)>'si", $rss_item, $out_enclosure);
				if (isset($out_enclosure[1]))
					{
					foreach($MYLR_FORMATS[$feed_format]['item_enclosure_attributes'] as $enclosureprop)
						{
						$temp = $this->my_preg_match("'\s$enclosureprop=[\'\"](.*?)[\'\"]'si", $out_enclosure[1]);
						if ($temp != '') $result['items'][$kidx]['enclosure_'.$enclosureprop] = $temp; // Set only if not empty
						}
					}
					
				if ($this->useOrigLink == TRUE)
					{
					if ($result['items'][$kidx]['feedburner:origEnclosureLink'] != '')
						{
						$result['items'][$kidx]['feedburner:trackEnclosure'] = $result['items'][$kidx]['enclosure_url'];
						$result['items'][$kidx]['enclosure_url'] = $result['items'][$kidx]['feedburner:origEnclosureLink'];
						unset($result['items'][$kidx]['feedburner:origEnclosureLink']);
						}
					
					if (strpos($result['items'][$kidx]['enclosure_url'],'/0L') > 1)
						{
						$result['items'][$kidx]['feedsportal:trackEnclosure'] = $result['items'][$kidx]['enclosure_url'];
						$result['items'][$kidx]['enclosure_url'] = mYLR_DecodeFeedPortalURL($result['items'][$kidx]['enclosure_url']);
						}
					}

				// Parse Media RSS info
				preg_match("'<media:content(.*?)>'si", $rss_item, $out_enclosure);
				if (isset($out_enclosure[1]))
					{
					foreach($MYLR_XMLNS['media']['item_media:content_attributes'] as $enclosureprop)
						{
						$temp = $this->my_preg_match("'\s$enclosureprop=[\'\"](.*?)[\'\"]'si", $out_enclosure[1]);
						if ($temp != '') $result['items'][$kidx]['media:content_'.$enclosureprop] = $temp; // Set only if not empty
						}
					}
				preg_match("'<media:thumbnail(.*?)>'si", $rss_item, $out_enclosure);
				if (isset($out_enclosure[1]))
					{
					foreach($MYLR_XMLNS['media']['item_media:thumbnail_attributes'] as $enclosureprop)
						{
						$temp = $this->my_preg_match("'\s$enclosureprop=[\'\"](.*?)[\'\"]'si", $out_enclosure[1]);
						if ($temp != '') $result['items'][$kidx]['media:thumbnail_'.$enclosureprop] = $temp; // Set only if not empty
						}
					}
				preg_match("'<media:player(.*?)>'si", $rss_item, $out_enclosure);
				if (isset($out_enclosure[1]))
					{
					foreach($MYLR_XMLNS['media']['item_media:player_attributes'] as $enclosureprop)
						{
						$temp = $this->my_preg_match("'\s$enclosureprop=[\'\"](.*?)[\'\"]'si", $out_enclosure[1]);
						if ($temp != '') $result['items'][$kidx]['media:player_'.$enclosureprop] = $temp; // Set only if not empty
						}
					}
										
				// Item counter
				$i++;
				}
				
			// Order or filter items (future feature)
			uasort($result['items'],'mYLR_CompareItemsTime');
			
			// Remove items after limit value (after to order items)
			if (($this->items_limit != 0) AND ($i > $this->items_limit))
				{
				$this->_ArrayPop($result['items'],$this->items_limit);
				}

			$result['items_count'] = count($result['items']);
			
			if (($this->min_items_required != 0) AND ($result['items_count'] < $this->min_items_required))
				{
				// Usefull while downed server send tiny feed with error message
				if ($this->cache_dir != '')
					{
					//$this->_SaveRawFileAs($error_content_file,$rss_content);
					@touch($error_content_file);
					}
				}
			
			return $result;
			}
		else
			{
			$this->_LAST_ERROR_MESSAGES[] = "Unknown content downloaded from '$rss_url'";
			if ($this->cache_dir != '')
				{
				//$this->_SaveRawFileAs($error_content_file,$rss_content);
				@touch($error_content_file);
				}
			return False;
			}
		}

	function _StripAtomEntries($content)
		{
		$offsetContent = 0;
		while (($openCmt = strpos($content,'<entry',$offsetContent)) !== FALSE)
			{
			$closeCmt = strpos($content,'</entry>',$openCmt);
			
			if ($closeCmt !== FALSE)
				{
				$content = substr($content,0,$openCmt).substr($content,$closeCmt+8);
				$offsetContent = $openCmt;
				}
			else
				{
				break;
				}
			}
		return $content;
		}

	function _StripRdfItems($content)
		{
		$offsetContent = 0;
		while (($openCmt = strpos($content,'<items',$offsetContent)) !== FALSE)
			{
			$closeCmt = strpos($content,'</items>',$openCmt);
			
			if ($closeCmt !== FALSE)
				{
				$content = substr($content,0,$openCmt).substr($content,$closeCmt+8);
				$offsetContent = $openCmt;
				}
			else
				{
				break;
				}
			}
		return $content;
		}

	function _StripItems($content)
		{
		$offsetContent = 0;
		while (($openCmt = strpos($content,'<item',$offsetContent)) AND ($openCmt !== FALSE))
			{
			$closeCmt = strpos($content,'</item>',$openCmt);
			
			if ($closeCmt !== FALSE)
				{
				$content = substr($content,0,$openCmt).substr($content,$closeCmt+7);
				$offsetContent = $openCmt;
				}
			else
				{
				break;
				}
			}
		return $content;
		}
	
	function _ParseAtom($rss_url,$source_kidx,$rss_content)
		{
		GLOBAL $MYLR_FORMATS;
		GLOBAL $MYLR_XMLNS;
		
		$result = array();
		$result['source_url'] 		= $rss_url;
		$result['source_kidx'] 		= $source_kidx;
		$result['feed_format'] 		= 'atom';
		$result['generator'] 		= '';

		// Parse document encoding
		$result['encoding'] = $this->my_preg_match("'\sencoding=[\'\"](.*?)[\'\"]'si", $rss_content);
		// if document codepage is specified, use it
		if ($result['encoding'] != '')
			{ $this->rsscp = $result['encoding']; } // This is used in my_preg_match()
		// otherwise use the default codepage
		else
			{ $this->rsscp = $this->default_cp; } // This is used in my_preg_match()
		
		// detect extension namespaces
		preg_match_all("'\sxmlns:(.*?)=[\'\"](.*?)[\'\"]'si", $rss_content, $nspaces_results);
		$result['namespaces'] = $nspaces_results[1];
		$result['namespaces'][] = 'dc';
		$result['namespaces'][] = 'content';
		//$result['namespaces'][] = 'atom';
		$result['namespaces'] = array_values(array_unique($result['namespaces']));
		
		$this->_InitSupportedTags($result['feed_format'],$result['namespaces']);

		// Parse CHANNEL info
		$channel_content = '';
		if (($openChannel = strpos($rss_content,'<feed>')) AND ($openChannel !== FALSE))
			{
			$channel_content = trim(substr($rss_content,$openChannel+9));
			if (($closeChannel = strpos($channel_content,'</feed>')) AND ($closeChannel !== FALSE))
				{
				$channel_content = trim(substr($channel_content,0,$closeChannel));
				}
			}
		else
			{
			preg_match("'<feed.*?>(.*?)</feed>'si", $rss_content, $out_channel);
			$channel_content = trim($out_channel[1]);
			}
		$channel_content = trim($this->_StripAtomEntries($channel_content));
		
		$temp = $this->my_preg_match("'<title.*?>(.*?)</title>'si", $channel_content);
		if ($temp != '') $result['title'] = $temp; // Set only if not empty
		
		$temp = $this->my_preg_match("'<link.*?rel=[\'\"]alternate[\'\"].*?href=[\'\"](.*?)[\'\"].*?>'si", $channel_content);
		if ($temp != '') $result['link'] = $temp; // Set only if not empty
		
		if ($result['link'] == '')
			{
			$temp = $this->my_preg_match("'<link.*?href=[\'\"](.*?)[\'\"].*?rel=[\'\"]alternate[\'\"].*?>'si", $channel_content);
			if ($temp != '') $result['link'] = $temp; // Set only if not empty
			}
	
		if ($result['link'] == '')
			{
			$temp = $this->my_preg_match("'<link.*?rel=[\'\"]self[\'\"].*?href=[\'\"](.*?)[\'\"].*?type=[\'\"]text/html[\'\"].*?>'si", $channel_content);
			if ($temp != '') $result['link'] = $temp; // Set only if not empty
			}
	
		$temp = $this->my_preg_match("'<updated.*?>(.*?)</updated>'si", $channel_content);
		if ($temp != '')
			{
			$result['dc:date'] = $temp;
			$timestamp = mYLR_DCDate2UnixTimeStamp($result['dc:date']);
			$result['pubTimeStamp'] = $timestamp;
			if ($this->date_format != '')
				{
				// create pubDate to specified date format
				$result['pubDate'] = gmdate($this->date_format, $timestamp);
				}
			else
				{
				// create pubDate to GMT/CUT date format
				$result['pubDate'] = gmdate('D, d M Y H:i:s \G\M\T', $timestamp);
				}
			}
		
		$result['items'] = array(); // create array even if there are no items
		// Parse ITEMS
		preg_match_all("'<entry(| .*?)>(.*?)</entry>'si", $rss_content, $items);
		$rss_items = $items[2];
		$i = 0;
		
		foreach($rss_items as $rss_item)
			{
			$itemResult = array();
			$rss_item = trim($rss_item);	
			if ($rss_item === '') continue;
			
			$temp = $this->my_preg_match("'<id.*?>(.*?)</id>'si", $rss_item);
			if ($temp != '')
				{
				$itemResult['guid'] = $temp;
				$itemResult['guid_isPermaLink'] = FALSE;
				}
			
			// Recherche de date
			$temp = $this->my_preg_match("'<modified.*?>(.*?)</modified>'si", $rss_item);
			if ($temp != '')
				{
				$itemResult['dc:date'] = $temp;
				}
			$temp = $this->my_preg_match("'<updated.*?>(.*?)</updated>'si", $rss_item);
			if ($temp != '')
				{
				$itemResult['dc:date'] = $temp;
				}
			$temp = $this->my_preg_match("'<issued.*?>(.*?)</issued>'si", $rss_item);
			if ($temp != '')
				{
				$itemResult['dc:date'] = $temp;
				}
			$temp = $this->my_preg_match("'<published.*?>(.*?)</published>'si", $rss_item);
			if ($temp != '')
				{
				$itemResult['dc:date'] = $temp;
				}
			$temp = $this->my_preg_match("'<created.*?>(.*?)</created>'si", $rss_item);
			if ($temp != '')
				{
				$itemResult['dc:date'] = $temp;
				}
			// Decryptage de date
			if ($itemResult['dc:date'] != '')
				{
				$timestamp = mYLR_DCDate2UnixTimeStamp($itemResult['dc:date']);
				$itemResult['pubTimeStamp'] = $timestamp;
				if ($this->date_format != '')
					{
					// create pubDate to specified date format
					$itemResult['pubDate'] = gmdate($this->date_format, $timestamp);
					}
				else
					{
					// create pubDate to GMT/CUT date format
					$itemResult['pubDate'] = gmdate('D, d M Y H:i:s \G\M\T', $timestamp);
					}
				}
			else
				{
				$this->_LAST_ERROR_MESSAGES[] = 'Item '.$itemResult['guid'].' has not pubDate';
				}
		
			$temp = $this->my_preg_match("'<link.*?href=[\'\"](.*?)[\'\"].*?rel=[\'\"]alternate[\'\"].*?>'si", $rss_item);
			if ($temp != '') $itemResult['link'] = $temp; // Set only if not empty
			if ($itemResult['link'] == '')
				{
				$temp = $this->my_preg_match("'<link.*?rel=[\'\"]alternate[\'\"].*?href=[\'\"](.*?)[\'\"].*?>'si", $rss_item);
				if ($temp != '') $itemResult['link'] = $temp; // Set only if not empty
				}
			if ($itemResult['link'] == '')
				{
				$temp = $this->my_preg_match("'<link.*?href=[\'\"](.*?)[\'\"].*?>'si", $rss_item);
				if ($temp != '') $itemResult['link'] = $temp; // Set only if not empty
				}
				
			$temp = $this->my_preg_match("'<feedburner:origLink>(.*?)</feedburner:origLink>'si", $rss_item);
			if ($temp != '') $itemResult['feedburner:origLink'] = $temp; // Set only if not empty
				
			$temp = $this->my_preg_match("'<fs:srclink>(.*?)</fs:srclink>'si", $rss_item);
			if ($temp != '') $itemResult['fs:srclink'] = $temp; // Set only if not empty

			if ($this->useOrigLink == TRUE)
				{
				if ($itemResult['feedburner:origLink'] != '')
					{
					$itemResult['feedburner:trackLink'] = $itemResult['link'];
					$itemResult['link'] = $itemResult['feedburner:origLink'];
					unset($itemResult['feedburner:origLink']);
					}
				else if ($itemResult['fs:srclink'] != '')
					{
					$itemResult['fs:trackLink'] = $itemResult['link'];
					$itemResult['link'] = $itemResult['fs:srclink'];
					unset($itemResult['fs:srclink']);
					}
					
				if (strpos($itemResult['link'],'/0L') > 1)
					{
					$itemResult['feedsportal:trackLink'] = $itemResult['link'];
					$itemResult['link'] = mYLR_DecodeFeedPortalURL($itemResult['link']);
					}
					
				if (strpos($itemResult['link'],'xiti.com/go.url') > 1)
					{
					$itemResult['xiti:trackLink'] = $itemResult['link'];
					$itemResult['link'] = mYLR_DecodeXitiURL($itemResult['link']);
					}
						
				if (strpos($itemResult['link'],'ns_campaign=') > 1)
					{
					$itemResult['nedstat:trackLink'] = $itemResult['link'];
					$itemResult['link'] = mYLR_StripNedStatFragment($itemResult['link']);
					}
						
				if (strpos($itemResult['link'],'*'))
					{
					$itemResult['yahoo:trackLink'] = $itemResult['link'];
					$itemResult['link'] = urldecode(substr(strrchr($itemResult['link'],'*'),1));
					}
					
				if (strpos($itemResult['link'],'xtor='))
					{
					$itemResult['xtor:trackLink'] = $itemResult['link'];
					$itemResult['link'] = mYLR_StripXtorFragment($itemResult['link']);
					}
					
				if (strpos($itemResult['link'],'utm_'))
					{
					$itemResult['utm:trackLink'] = $itemResult['link'];
					$itemResult['link'] = mYLR_StripUtmFragment($itemResult['link']);
					}
					
				if (strpos($itemResult['link'],'?rss'))
					{
					$itemResult['rss:trackLink'] = $itemResult['link'];
					$itemResult['link'] = mYLR_StripRssFragment($itemResult['link']);
					}
				}

			$temp = $this->my_preg_match("'<title.*?>(.*?)</title>'si", $rss_item);
			if ($temp != '')
				{
				$itemResult['title'] = mYLR_StripCR($temp);
				}
					
			$itemResult['title'] = mYLR_StripCR($itemResult['title']);
			$itemResult['link'] = mYLR_URLunEntities($itemResult['link']);
			
			if ($this->kidx_rule == 'guid')
				{
				// Create unique index (with MD5) from guid or link for this item
				if (isset($itemResult['guid']))
					{
					$kidx = md5($itemResult['guid']);
					}
				else if (isset($itemResult['pubTimeStamp']) AND isset($itemResult['link']))
					{
					$kidx = md5($itemResult['pubTimeStamp'].$itemResult['link']);
					$itemResult['guid'] = $kidx;
					$itemResult['guid_isPermaLink'] = FALSE;
					}
				else if (isset($itemResult['pubTimeStamp']) AND isset($result['link']))
					{
					$kidx = md5($itemResult['pubTimeStamp'].$result['link']);
					$itemResult['guid'] = $kidx;
					$itemResult['link'] = $result['link'];
					$itemResult['guid_isPermaLink'] = FALSE;
					}
				else
					{
					// C'est inacceptable :o|
					continue;
					}
				}
			else if ($this->kidx_rule == 'link')
				{
				// Create unique index (with MD5) from guid or link for this item
				if (isset($itemResult['link']))
					{
					$kidx = md5($itemResult['link']);
					}
				else
					{
					// C'est inacceptable :o|
					continue;
					}
				}
			else if ($this->kidx_rule == 'date+title')
				{
				// Create unique index (with MD5) from date & title for this item
				if ((isset($itemResult['pubTimeStamp'])) AND ($itemResult['title'] != ''))
					{
					//$kidx = md5(gmdate('dmY',$itemResult['pubTimeStamp']).strtolower(str_replace(array(' ','_','(',')','[',']'),'',strip_tags($this->unhtmlentities($itemResult['title'])))));
					$kidx = md5(gmdate('dmY',$itemResult['pubTimeStamp']).$this->_StandardizedStr($itemResult['title']));
					}
				else
					{
					// C'est inacceptable :o|
					continue;
					}
				}
			else
				{
				continue;
				}
					
			if (isset($result['items'][$kidx]))
				{
				if ($result['items'][$kidx]['pubTimeStamp'] > $itemResult['pubTimeStamp'])
					{
					continue;
					}
				else
					{
					unset($result['items'][$kidx]);
					}
				}
				
			$result['items'][$kidx] = $itemResult;
			$result['items'][$kidx]['kidx'] = $kidx;
				
			$temp = $this->my_preg_match("'<summary.*?>(.*?)</summary>'si", $rss_item);
			if ($temp != '') $result['items'][$kidx]['description'] = $temp; // Set only if not empty
				
			$temp = $this->my_preg_match("'<content.*?type=[\'\"]text[\'\"].*?>(.*?)</content>'si", $rss_item);
			if ($temp != '') $result['items'][$kidx]['description'] = $temp; // Set only if not empty
				
			$temp = $this->my_preg_match("'<content.*?type=[\'\"].*?html[\'\"].*?>(.*?)</content>'si", $rss_item);
			if ($temp != '') $result['items'][$kidx]['content:encoded'] = $temp; // Set only if not empty

			// Strip HTML tags and other bullshit from TITLE
			if ($this->stripHTML && $result['items'][$kidx]['title'])
				$result['items'][$kidx]['title'] = mYLR_Trim(strip_tags($this->unhtmlentities($result['items'][$kidx]['title'])));
			
			// Strip HTML tags and other bullshit from DESCRIPTION
			if ($this->stripHTML && $result['items'][$kidx]['description'])
				{
				if (isset($result['items'][$kidx]['content:encoded']) == FALSE) $result['items'][$kidx]['content:encoded'] = $result['items'][$kidx]['description'];
				$result['items'][$kidx]['description'] = mYLR_Trim(strip_tags($this->unhtmlentities($result['items'][$kidx]['description'])));
				}
			
			$temp = $this->my_preg_match("'<author>.*?<name>(.*?)</name>.*?</author>'si", $rss_item);
			if ($temp != '') $result['items'][$kidx]['dc:creator'] = $temp; // Set only if not empty
			
			$temp = $this->my_preg_match("'<yt:username>(.*?)</yt:username>'si", $rss_item);
			if ($temp != '') $result['items'][$kidx]['yt:username'] = $temp; // Set only if not empty
			
			$result['items'][$kidx]['categories'] = array(); // create array
			preg_match_all("'<category.*?term=[\'\"](.*?)[\'\"].*?>'si", $rss_item, $categories);
			$item_categories = $categories[1];
			if (count($item_categories) > 0)
				{
				foreach($item_categories as $item_category)
					{
					$result['items'][$kidx]['categories'][] = $this->my_convert_encoding($this->process_cdata($item_category));
					}
				$result['items'][$kidx]['category'] = $result['items'][$kidx]['categories'][0];
				}
		
			// Parse ENCLOSURE info
			preg_match("'<link\b[^<>]+rel=[\'\"]enclosure[\'\"]?[^<>]+>'si", $rss_item, $out_enclosure);
			if (isset($out_enclosure[0]))
				{
				foreach($MYLR_FORMATS['atom']['item_link_attributes'] as $enclosureprop)
					{
					$temp = $this->my_preg_match("'\s$enclosureprop=[\'\"](.*?)[\'\"]'si", $out_enclosure[0]);
					if ($temp != '')
						{
						// Set only if not empty
						if ($enclosureprop == 'href')
							{
							$result['items'][$kidx]['enclosure_url'] = $temp;
							}
						else if ($enclosureprop == 'src')
							{
							$result['items'][$kidx]['enclosure_url'] = $temp;
							}
						else if ($enclosureprop == 'type')
							{
							$result['items'][$kidx]['enclosure_type'] = $temp;
							}
						else if ($enclosureprop == 'length')
							{
							$result['items'][$kidx]['enclosure_length'] = $temp;
							}
						}
					}
				}
				
			if ($this->useOrigLink == TRUE)
				{
				if ($result['items'][$kidx]['feedburner:origEnclosureLink'] != '')
					{
					$result['items'][$kidx]['feedburner:trackEnclosure'] = $result['items'][$kidx]['enclosure_url'];
					$result['items'][$kidx]['enclosure_url'] = $result['items'][$kidx]['feedburner:origEnclosureLink'];
					unset($result['items'][$kidx]['feedburner:origEnclosureLink']);
					}
				
				if (strpos($result['items'][$kidx]['enclosure_url'],'/0L') > 1)
					{
					$result['items'][$kidx]['feedsportal:trackEnclosure'] = $result['items'][$kidx]['enclosure_url'];
					$result['items'][$kidx]['enclosure_url'] = mYLR_DecodeFeedPortalURL($result['items'][$kidx]['enclosure_url']);
					}
				}

			// Parse Media RSS info
			preg_match("'<media:content(.*?)>'si", $rss_item, $out_enclosure);
			if (isset($out_enclosure[1]))
				{
				foreach($MYLR_XMLNS['media']['item_media:content_attributes'] as $enclosureprop)
					{
					$temp = $this->my_preg_match("'\s$enclosureprop=[\'\"](.*?)[\'\"]'si", $out_enclosure[1]);
					if ($temp != '') $result['items'][$kidx]['media:content_'.$enclosureprop] = $temp; // Set only if not empty
					}
				}
			preg_match("'<media:thumbnail(.*?)>'si", $rss_item, $out_enclosure);
			if (isset($out_enclosure[1]))
				{
				foreach($MYLR_XMLNS['media']['item_media:thumbnail_attributes'] as $enclosureprop)
					{
					$temp = $this->my_preg_match("'\s$enclosureprop=[\'\"](.*?)[\'\"]'si", $out_enclosure[1]);
					if ($temp != '') $result['items'][$kidx]['media:thumbnail_'.$enclosureprop] = $temp; // Set only if not empty
					}
				}
			preg_match("'<media:player(.*?)>'si", $rss_item, $out_enclosure);
			if (isset($out_enclosure[1]))
				{
				foreach($MYLR_XMLNS['media']['item_media:player_attributes'] as $enclosureprop)
					{
					$temp = $this->my_preg_match("'\s$enclosureprop=[\'\"](.*?)[\'\"]'si", $out_enclosure[1]);
					if ($temp != '') $result['items'][$kidx]['media:player_'.$enclosureprop] = $temp; // Set only if not empty
					}
				}

			// Item counter
			$i++;
			}
		
		// Order or filter items (future feature)
		uasort($result['items'],'mYLR_CompareItemsTime');
		
		// Remove items after limit value (after to order items)
		if (($this->items_limit != 0) AND ($i > $this->items_limit))
			{
			$this->_ArrayPop($result['items'],$this->items_limit);
			}

		$result['items_count'] = count($result['items']);
		
		return $result;
		}
	
	function _SaveCacheFileAs($cache_file,$result)
		{
		$serialized = serialize($result);
		
		if ($this->_SaveRawFileAs($cache_file,$serialized))
			{
			return TRUE;
			}
		else
			{
			//$this->_LAST_ERROR_MESSAGES[] = "Fail to save serialized content";
			return FALSE;
			}
		}
		
	function _SaveRawFileAs($filepath,$content)
		{
		$result = FALSE;
		
		if ($this->_FWRITE_FAIL_COUNT >= $this->max_write_errors) return FALSE;
		
		if (file_exists($filepath) AND (@filemtime($filepath) > $this->_STARTED_TIME))
			{
			$this->_LAST_ERROR_MESSAGES[] = 'Prevent to overwrite more recent file ('.(@filemtime($filepath)-$this->_STARTED_TIME).'s): '.$filepath.'';
			return FALSE;
			}
		
		$contentlen = strlen($content);
		if ($contentlen == 0)
			{
			$this->_LAST_ERROR_MESSAGES[] = 'Prevent to save empty file: '.$filepath.'';
			return FALSE;
			}
		
		$lockpath = '';
		if (($this->writelock_delay > 0) AND ($this->writelock_ext != ''))
			{
			$lockpath = $filepath.''.$this->writelock_ext;
			if (file_exists($lockpath) AND (@filemtime($lockpath) >= (date('U')-$this->writelock_delay)))
				{
				$this->_LAST_ERROR_MESSAGES[] = 'Blocked by write-locking: '.$lockpath.'';
				return FALSE;
				}
			else
				{
				@touch($lockpath);
				}
			}
		
		$tempath = $filepath;
		if ($this->write_mode == 'copy')
			{
			$tempath .= '.'.date('U').'-'.mt_rand(100,999).'.tmp';
			}
		else if ($this->write_mode == 'move')
			{
			$tempath .= '.'.date('U').'-'.mt_rand(100,999).'.tmp';
			}
		
		if ($f = @fopen($tempath, 'wb'))
			{
			$result = fwrite($f, $content, $contentlen);
			if ($result === FALSE)
				{
				$this->_FWRITE_FAIL_COUNT++;
				$this->_LAST_ERROR_MESSAGES[] = "Fail to fwrite(): $tempath";
				}
			else if ($result != $contentlen)
				{
				$this->_FWRITE_FAIL_COUNT++;
				$this->_LAST_ERROR_MESSAGES[] = "Fail to fwrite(), not reach content length: $tempath";
				}
			else
				{
				$result = fclose($f);
				if ($result === FALSE)
					{
					$this->_FWRITE_FAIL_COUNT++;
					$this->_LAST_ERROR_MESSAGES[] = "Fail to fclose(): $tempath";
					}
				}
			}
		else
			{
			$this->_FWRITE_FAIL_COUNT++;
			$this->_LAST_ERROR_MESSAGES[] = "Fail to fopen(): $tempath";
			$result = FALSE;
			}
			
		if ($this->write_mode == 'move')
			{
			if ($result == TRUE) 
				{
				if (file_exists($filepath) AND (@filemtime($filepath) > $this->_STARTED_TIME))
					{
					$this->_LAST_ERROR_MESSAGES[] = 'Existed file is more recent ('.(@filemtime($filepath)-$this->_STARTED_TIME).'s), rename() aborted: '.$filepath.'';
					if (@unlink($tempath) == FALSE)
						{
						$this->_LAST_ERROR_MESSAGES[] = 'Fail to unlink(): '.$tempath.'';
						}
					$result = FALSE;
					}
				else
					{
					$result = @rename($tempath, $filepath);
					if ($result == FALSE)
						{
						$this->_FWRITE_FAIL_COUNT++;
						$this->_LAST_ERROR_MESSAGES[] = "Fail to rename(): $tempath";
						if (@unlink($tempath) == FALSE)
							{
							$this->_LAST_ERROR_MESSAGES[] = 'Fail to unlink(): '.$tempath.'';
							}
						}
					}
				}
			else
				{
				if (@unlink($tempath) == FALSE)
					{
					$this->_LAST_ERROR_MESSAGES[] = 'Fail to unlink(): '.$tempath.'';
					}
				}
			}
		else if ($this->write_mode == 'copy')
			{
			if ($result == TRUE) 
				{
				$result = @copy($tempath, $filepath);
				if ($result == FALSE)
					{
					$this->_FWRITE_FAIL_COUNT++;
					$this->_LAST_ERROR_MESSAGES[] = "Fail to copy(): $tempath";
					}
				}
			@unlink($tempath);
			}
			
		if ($lockpath != '') 
			{
			if ($result == TRUE)
				{
				if (@unlink($lockpath) == FALSE)
					{
					$this->_LAST_ERROR_MESSAGES[] = 'Fail to unlink(): '.$lockpath.'';
					}
				}
			}
			
		return $result;
		}
		
	function _LoadCacheFile($cache_file)
		{
		if ($f = @fopen($cache_file, 'rb'))
			{ 
	        $raw_content = ''; 
            while (!feof($f))
				{ 
                $raw_content .= fgets($f, 4096); 
            	}
            fclose($f); 
			$result = unserialize($raw_content);
			}
		else
			{
			$this->_LAST_ERROR_MESSAGES[] = "Can't open cached file '$cache_file'.";
			$result = NULL;
			}
		return $result;
		}
	
	function _ArrayPop(&$arraytopop,$length=-1)
		{
		mYLR_ArrayPop($arraytopop,$length);
		}
		
	}

// -------------------------------------------------------------------
// Private functions
// -------------------------------------------------------------------

function mYLR_ArrayPop(&$arraytopop,$length=-1)
	{
	$arraytopop = array_slice($arraytopop, 0, $length);
	}

function mYLR_CompareItemsTime($itemA,$itemB)
	{
	if ($itemA['pubTimeStamp'] == $itemB['pubTimeStamp']) return 0;
	return ($itemA['pubTimeStamp'] > $itemB['pubTimeStamp']) ? -1 : 1;
	}

function mYLR_CompareSourcesTime($itemA,$itemB)
	{
	if ($itemA['updatedTime'] > $itemA['errorTime'])
		{
		$itemAtime = $itemA['updatedTime'];
		}
	else
		{
		$itemAtime = $itemA['errorTime'];
		}
	
	if ($itemB['updatedTime'] > $itemB['errorTime'])
		{
		$itemBtime = $itemB['updatedTime'];
		}
	else
		{
		$itemBtime = $itemB['errorTime'];
		}
	
	if ($itemAtime == $itemBtime) return 0;
	return ($itemAtime > $itemBtime) ? 1 : -1;
	}

// ADVICE: disabled because use CDATA TAG... More usefull for non-utf8 contents
function mYLR_ContentEncoded ($string,$mode='CDATA')
	{
	// Replace by entities
	//$string = str_replace('&','&amp;',$string);
	//$string = str_replace('@','&'.'#032;',$string);
	//$string = str_replace(array("'"),'&'.'#039;',$string);
	//$string = str_replace(array("'","`","ë","í"),'&'.'#039;',$string);
	//$string = str_replace(array("ì","î"),'&quot;',$string);
	//$string = str_replace("Ö",'...',$string);
	
	
	if ($mode == 'CDATA')
		{
		// Do nothing
		}
	else
		{
		// convert < > " ' & 
		$string=htmlspecialchars($string,$mode);
		}
	
	// Remove duplicate entities
	$string = str_replace('&amp;#','&#',$string);
	
	//$string = str_replace('È','&'.'eacute;',$string);
	//$string = str_replace('¥','&'.'acute;',$string);
	$string = str_replace('&amp;euro;','&'.'euro;',$string);
	return $string;
	}

// Convert "Dublin Core" date format to UNIX timestamp (for GMT)
// Input: date like 2006-06-02T04:45:16-0700 or 2006-12-09T18:24:29Z or 2007-04-06T11:07:01+02:00
function mYLR_DCDate2UnixTimeStamp($DateTime)
	{
	$TimeStamp = 0;
	
	$timeStr = explode(' ',trim(str_replace(array('-','T','Z',':','+'),' ',$DateTime)),7);
	$timeDec = trim(str_replace(':','',$timeStr[6]));
	$timeSign = substr($DateTime, 19, 1);
	$TimeStamp = gmmktime($timeStr[3],$timeStr[4],$timeStr[5],$timeStr[1],$timeStr[2],$timeStr[0]);
	if (($timeSign != '') AND ($timeSign != 'Z'))
		{
		if ($timeSign == '+')
			{
			$timeSign = -1;
			}
		else
			{
			$timeSign = 1;
			}
		if (strlen($timeDec) <= 2)
			{
			$TimeStamp = $TimeStamp + $timeSign * 60 * 60 * intval($timeDec);
			}
		else
			{
			$TimeStamp = $TimeStamp + $timeSign * 60 * 60 * intval(substr($timeDec, 0, 2)) + $timeSign * 60 * intval(substr($timeDec, 2, 2)) ;
			}
		}
	
	return $TimeStamp;
	}
	
function mYLR_URLentities($url)
	{
	$url = mYLR_URLunEntities($url);
	$url = str_replace('&','&amp;',$url);
	
	return $url;
	}

function mYLR_URLunEntities($url)
	{
	$url = str_replace(array('&amp;','&#x26;'),'&',$url);
	
	return $url;
	}

function mYLR_StripHTMLcomment($content)
	{
	$offsetContent = 0;
	while (($openCmt = strpos($content,'<!--',$offsetContent)) !== FALSE)
		{
		$closeCmt = strpos($content,'-->',$openCmt);
		
		if ($closeCmt !== FALSE)
			{
			$content = substr($content,0,$openCmt).substr($content,$closeCmt+3);
			$offsetContent = $openCmt;
			}
		else
			{
			break;
			}
		}
	return $content;
	}
	
function mYLR_UnAccentuate($text)
	{
	$text = strtr($text,"¿¡¬√ƒ≈‡·‚„‰Â“”‘’÷ÿÚÛÙıˆ¯»… ÀËÈÍÎ«ÁÃÕŒœÏÌÓÔŸ⁄€‹˘˙˚¸ˇ—Ò","AAAAAAaaaaaaOOOOOOooooooEEEEeeeeCcIIIIiiiiUUUUuuuuyNn");
	$text = str_replace('ú','oe',$text);
	
	return $text;
	}
	
function mYLR_UnTagsEntities($content)
	{
	$content = str_replace('&lt;','<',$content);
	$content = str_replace('&gt;','>',$content);
	$content = str_replace('&quot;','"',$content);
	
	return $content;
	}
	
function mYLR_StripCDATA($text)
	{
	$text = strtr($text, array('&lt;![CDATA['=>'',']]&gt;'=>'','<![CDATA['=>'',']]>'=>''));
	
	return $text;
	}
	
function mYLR_StripCR($content)
	{
	$content = trim(str_replace(array("\r","\n"),' ',$content));
	return $content;
	}
	
function mYLR_Trim($texte)
	{
	$texte = str_replace('†',' ',$texte); // Espace Ètrange, insÈcable ?
	$texte = preg_replace('/\s\s+/',' ', $texte); // trim inner duplicated spaces
	return trim($texte,"\x00..\x1F");
	}
	
function mYLR_StripXtorFragment($oldURL)
	{
	$newURL = $oldURL;
	
	if ($diesePos = strpos($newURL,'#ens_id='))
		{
		$newURL = substr($newURL,0,$diesePos);
		}
	else if ($diesePos = strpos($newURL,'#xtor='))
		{
		$newURL = substr($newURL,0,$diesePos);
		}
	else if ($diesePos = strpos($newURL,'?xtor='))
		{
		$newURL = substr($newURL,0,$diesePos);
		}
	
	return $newURL;
	}
	
function mYLR_StripUtmFragment($oldURL)
	{
	$newURL = $oldURL;
	
	if ($diesePos = strpos($newURL,'#utm_'))
		{
		$newURL = substr($newURL,0,$diesePos);
		}
	else if ($diesePos = strpos($newURL,'?utm_'))
		{
		$newURL = substr($newURL,0,$diesePos);
		}
	else if ($diesePos = strpos($newURL,'&utm_'))
		{
		$newURL = substr($newURL,0,$diesePos);
		}
	
	return $newURL;
	}
	
function mYLR_StripNedStatFragment($oldURL)
	{
	$newURL = $oldURL;
	
	if ($diesePos = strpos($newURL,'?ns_campaign='))
		{
		$newURL = substr($newURL,0,$diesePos);
		}
	else if ($diesePos = strpos($newURL,'&ns_campaign='))
		{
		$newURL = substr($newURL,0,$diesePos);
		}
	
	return $newURL;
	}
	
function mYLR_StripRssFragment($oldURL)
	{
	$newURL = $oldURL;
	
	if ($diesePos = strpos($newURL,'?rss'))
		{
		$newURL = substr($newURL,0,$diesePos);
		}
	
	return $newURL;
	}
	
function mYLR_DecodeXitiURL($oldURL)
	{
	if ($posURL = strpos(strtolower($oldURL),'url='))
		{
		$oldURL = substr($oldURL,$posURL+4);
		}
		
	return $oldURL;
	}
	
function mYLR_DecodeFeedPortalURL($oldURL)
	{
	if (strpos($oldURL,'/0L') > 1)
		{
		$URLParts = explode('/',$oldURL);
		$newURL = $URLParts[count($URLParts)-2];
		if (substr($newURL,0,2) == '0L')
			{
			$newURL = str_replace('0Y',')',$newURL);
			$newURL = str_replace('0X','(',$newURL);
			$newURL = str_replace('0W','!',$newURL);
			$newURL = str_replace('0V','~',$newURL);
			$newURL = str_replace('0T','#',$newURL);
			$newURL = str_replace('0S','www.',$newURL);
			$newURL = str_replace('0N','.com',$newURL);
			$newURL = str_replace('0L','http://',$newURL);
			$newURL = str_replace('0J','%',$newURL);
			$newURL = str_replace('0I','_',$newURL);
			$newURL = str_replace('0H',',',$newURL);
			$newURL = str_replace('0G','&',$newURL);
			$newURL = str_replace('0F','=',$newURL);
			$newURL = str_replace('0E','-',$newURL);
			$newURL = str_replace('0D','?',$newURL);
			$newURL = str_replace('0C','/',$newURL);
			$newURL = str_replace('0B','.',$newURL);
			$newURL = str_replace('0A','0',$newURL);
			
			return mYLR_DecodeFeedPortalURL($newURL);
			}
		else
			{
			return $oldURL;
			}
		}
	else
		{
		return $oldURL;
		}
	}

// Remove Feedburner content of FeedFlare buttons
function mYLR_StripFeedFlare($content)
	{
	$offsetContent = 0;
	while (($openCmt = strpos($content,'<div class="feedflare">',$offsetContent)) !== FALSE)
		{
		$closeCmt = strpos($content,'</div>',$openCmt);
		
		if ($closeCmt !== FALSE)
			{
			$content = substr($content,0,$openCmt).substr($content,$closeCmt+6);
			$offsetContent = $openCmt;
			}
		else
			{
			break;
			}
		}
	
	return $content;
	}

// Remove FeedsPortal (Mediafed) bookmark buttons
function mYLR_StripFeedPortalViral($content)
	{
	$offsetContent = 0;
	while (($openCmt = strpos($content,'<div class=\'mf-viral\'>',$offsetContent)) !== FALSE)
		{
		$closeCmt = strpos($content,'</div>',$openCmt);
		
		if ($closeCmt !== FALSE)
			{
			$content = substr($content,0,$openCmt).substr($content,$closeCmt+6);
			$offsetContent = $openCmt;
			}
		else
			{
			break;
			}
		}
	
	return $content;
	}

// Remove FeedsPortal (Mediafed) related posts list
function mYLR_StripFeedPortalRelated($content)
	{
	$offsetContent = 0;
	while (($openCmt = strpos($content,'<div class=\'mf-related\'>',$offsetContent)) !== FALSE)
		{
		$closeCmt = strpos($content,'</div>',$openCmt);
		
		if ($closeCmt !== FALSE)
			{
			$content = substr($content,0,$openCmt).substr($content,$closeCmt+6);
			$offsetContent = $openCmt;
			}
		else
			{
			break;
			}
		}
	
	return $content;
	}

// Remove last DIV
function mYLR_StripLastDIV($content)
	{
	$offsetContent = 0;
	$openCmt = FALSE;
	$openTemp = FALSE;
	while (($openTemp = strpos($content,'<div ',$offsetContent)) !== FALSE)
		{
		$openCmt = $openTemp;
		$offsetContent = $openTemp+1;
		}
	if ($openCmt !== FALSE)
		{
		$closeCmt = strpos($content,'</div>',$openCmt);
		
		if ($closeCmt !== FALSE)
			{
			$content = substr($content,0,$openCmt).substr($content,$closeCmt+6);
			}
		}
	return $content;
	}

// Remove last UL
function mYLR_StripLastUL($content)
	{
	$offsetContent = 0;
	$openCmt = FALSE;
	$openTemp = FALSE;
	while (($openTemp = strpos($content,'<ul ',$offsetContent)) !== FALSE)
		{
		$openCmt = $openTemp;
		$offsetContent = $openTemp+1;
		}
	if ($openCmt !== FALSE)
		{
		$closeCmt = strpos($content,'</ul>',$openCmt);
		
		if ($closeCmt !== FALSE)
			{
			$content = substr($content,0,$openCmt).substr($content,$closeCmt+5);
			}
		}
	return $content;
	}

// By Miguel Perez
// http://fr.php.net/manual/fr/function.chr.php#77911
function mYLR_unichr($c)
	{
    if ($c <= 0x7F) {
        return chr($c);
    } else if ($c <= 0x7FF) {
        return chr(0xC0 | $c >> 6) . chr(0x80 | $c & 0x3F);
    } else if ($c <= 0xFFFF) {
        return chr(0xE0 | $c >> 12) . chr(0x80 | $c >> 6 & 0x3F)
                                    . chr(0x80 | $c & 0x3F);
    } else if ($c <= 0x10FFFF) {
        return chr(0xF0 | $c >> 18) . chr(0x80 | $c >> 12 & 0x3F)
                                    . chr(0x80 | $c >> 6 & 0x3F)
                                    . chr(0x80 | $c & 0x3F);
    } else {
        return false;
    }
	}
?>
