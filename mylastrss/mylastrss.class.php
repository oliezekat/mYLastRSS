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
	
	var $write_mode				= 'direct';		// How to save file : write 'direct' to destination filename, 'copy' or 'move' temporary file
	var $writelock_ext			= '.wlock';		// Extension added to filename for write-locking feature (set blank to disable write-locking)
	var $writelock_delay		= 0;			// Delay before to ignore write-locking (set zero to disable write-locking)
	var $max_write_errors		= 1;			// Not try to write/copy/move file if reach this limit
	
	var $transport				= '';			// Let blank to auto choose between fopen, WpRequests, Requests, or Snoopy.
	var $query_limit			= 0;			// Limit number of HTTP queries to fetch feed content.
	var $max_execution_time		= 0;			// Overall time allowed to process feeds. Set 0 to disable.
	var $userAgent				= 'mYLastRSS';	// Used for Snoopy only
	var $timeOut				= 0;			// Used for Snoopy only, set 0 to disable. Unused if set max_execution_time.
	var $minTimeOut				= 6;			// minimal time-out per Snoopy request, used if set max_execution_time
	var $min_items_required 	= 0; 			// Before to use last file cached
	var $retry_delay			= 1200;			// 60 * 20 * 1 time wait before to try again. Require cache_dir.
	
	var $useSnoopy				= FALSE;		// deprecated
	
	// -------------------------------------------------------------------
	// Private variables (Don't use them)
	// -------------------------------------------------------------------
	
	// For RSS parsing
	var $rsscp = '';
	var $channeltags 	= array(); // Now build at runtime for each feed
	var $itemtags 		= array(); // Now build at runtime for each feed
	
	var $_MIMES_TYPES = array(	'zip'	=>	'application/x-zip-compressed',
								'exe'	=>	'application/x-msdownload',
								'gif'	=>	'image/gif',
								'jpg'	=>	'image/jpeg',
								'jpe'	=>	'image/jpeg',
								'jpeg'	=>	'image/jpeg',
								'png'	=>	'image/png',
								'webp'	=>	'image/webp',
								'avif'	=>	'image/avif',
								'mp3'	=>	'audio/mpeg',
								'mp4'	=>	'video/mp4',
								'flv'	=>	'video/x-flv',
								'avi'	=>	'video/x-msvideo',
								'swf'	=>	'application/x-shockwave-flash',
								'3gp'	=>	'video/3gpp'
								);
	// To support Media RSS
	var $enable_MediaRSS = TRUE; // Deprecated
	var $_MRSS_CONTENT_MIMES_TYPES 	= array('image/avif','image/webp','image/gif','image/jpeg','image/pjpeg','image/png','audio/mpeg','video/jpeg','video/mp4','video/quicktime','video/x-flv','application/x-shockwave-flash','video/x-msvideo','video/3gpp');
	var $_ANSI_ENCODING = 'ISO-8859-15';
	var $_ANSI_ENCODINGS = array('ISO-8859-1', 'ISO8859-1', 'ISO-8859-15', 'ISO8859-15', 'CP1252', 'WINDOWS-1252', '1252');

	// Internal global vars
	var $_USE_SEVERAL_SOURCES 	= FALSE;
	var $_STARTED_INDEX 		= 0;
	var $_SOURCES			 	= array();
	var $_STARTED_TIME 			= 0;
	var $_QUERY_COUNT			= 0;
	var $_FWRITE_FAIL_COUNT		= 0;		// Amount of write/copy/move errors. Not reset between several request.
	var $_HTML_ENTITIES_TRANS 	= array(); 	// Build into constructor method.
	var $_LAST_ERROR_MESSAGES 	= array(); 	// Error messages (in english) which help to debug... Don't use if debugging is finished.
	var $_EMOJIS_TRANS       	= array(); 	// Array to replace emojis (from UTF-8 content only).
    var $_GLOBAL_FORMATS        = null; // Replace previous global $MYLR_FORMATS
    var $_GLOBAL_XMLNS          = null; // Replace previous global $MYLR_XMLNS
		
	// -------------------------------------------------------------------
	// Constructor
	// -------------------------------------------------------------------
	
	function __construct()
		{
		return $this->mYLastRSS();
		}
	
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
    
	function _InitGlobalsFormatsXmlNs()
		{
        if (($this->_GLOBAL_FORMATS !== null) && ($this->_GLOBAL_XMLNS !== null)) return;
		$MYLR_FORMATS = null;
		$MYLR_XMLNS = null;
        if (defined('MYLASTRSS_NS_PATH'))
            {
            require(MYLASTRSS_NS_PATH);
            }
        else
            {
            require('namespaces.inc.php');
            }
        $this->_GLOBAL_FORMATS = $MYLR_FORMATS;
        $this->_GLOBAL_XMLNS = $MYLR_XMLNS;
        }
            
	function _InitEntitiesArray()
		{
		if ((is_array($this->_HTML_ENTITIES_TRANS) === FALSE) OR (count($this->_HTML_ENTITIES_TRANS) === 0))
			{
			// Init _HTML_ENTITIES_TRANS array for unhtmlentities()
			// Get HTML entities table
			$this->_HTML_ENTITIES_TRANS = get_html_translation_table (HTML_ENTITIES, ENT_QUOTES, $this->_ANSI_ENCODING); // if default_charset is UTF-8
			// Flip keys<==>values
			$this->_HTML_ENTITIES_TRANS = array_flip ($this->_HTML_ENTITIES_TRANS);
			
			if (strtoupper($this->cp) == 'UTF-8')
				{
				foreach($this->_HTML_ENTITIES_TRANS as $entity => $value)
					{
					$this->_HTML_ENTITIES_TRANS[$entity] = $this->encodeIso8859ToUtf8($value);
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
			$this->_HTML_ENTITIES_TRANS["&#x153;"] = 'oe';
			$this->_HTML_ENTITIES_TRANS += array("&"."#x202F;" => " ");
			$this->_HTML_ENTITIES_TRANS["&hellip;"] = '...';
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
			$this->_HTML_ENTITIES_TRANS += array("&"."#x203A;" => ">");
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
					$this->_HTML_ENTITIES_TRANS += array("&#".$i.";" => $this->encodeIso8859ToUtf8(chr($i)));
					if ($i < 100) $this->_HTML_ENTITIES_TRANS += array("&#0".$i.";" => $this->encodeIso8859ToUtf8(chr($i)));
					// coComment entities
					$this->_HTML_ENTITIES_TRANS += array("&#x".strtoupper(dechex($i)).";" => $this->encodeIso8859ToUtf8(chr($i)));
					$this->_HTML_ENTITIES_TRANS += array("&#x".strtolower(dechex($i)).";" => $this->encodeIso8859ToUtf8(chr($i)));
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
				}
			if (in_array(strtoupper($this->cp), $this->_ANSI_ENCODINGS))
				{
				$this->_HTML_ENTITIES_TRANS['&szlig;']	 = 'ﬂ';
				$this->_HTML_ENTITIES_TRANS["&euro;"]	 = 'Ä';
                $this->_HTML_ENTITIES_TRANS["&copy;"]	 = '©';
				}
			$this->_HTML_ENTITIES_TRANS["&#xa0;"]	 = ' '; // espace fine insecable
			$this->_HTML_ENTITIES_TRANS["&#160;"]	 = ' '; // espace fine insecable
			$this->_HTML_ENTITIES_TRANS["&#038;"]	 = '&';
			$this->_HTML_ENTITIES_TRANS["&#39;"]	 = "'";
			$this->_HTML_ENTITIES_TRANS["&#34;"]	 = '"';
			$this->_HTML_ENTITIES_TRANS["&#339;"]	 = 'oe';
			$this->_HTML_ENTITIES_TRANS["&#xA;"]	 = PHP_EOL;
			$this->_HTML_ENTITIES_TRANS["&#34;"]	 = '"';
			}
		}
		
	function _InitSupportedTags($Processor='unknown',$namespaces=NULL)
		{
		// Processor=unknown|rss|rdf|atom
		
		$this->_InitGlobalsFormatsXmlNs();
		
		if (($Processor != 'unknown') AND isset($this->_GLOBAL_FORMATS[$Processor]))
			{
			$this->channeltags	 = $this->_GLOBAL_FORMATS[$Processor]['channel_tags'];
			$this->itemtags		 = $this->_GLOBAL_FORMATS[$Processor]['item_tags'];
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
				if (isset($this->_GLOBAL_XMLNS[$xmlns]))
					{
					$this->channeltags	 = array_merge($this->channeltags,$this->_GLOBAL_XMLNS[$xmlns]['channel_tags']);
					$this->itemtags		 = array_merge($this->itemtags,$this->_GLOBAL_XMLNS[$xmlns]['item_tags']);
					}
				}
			}
		}
	
	function _InitEmojisArray()
		{
		if ((is_array($this->_EMOJIS_TRANS) === FALSE) OR (count($this->_EMOJIS_TRANS) === 0))
			{
            if (defined('MYLASTRSS_EMOJIS_PATH') and is_file(MYLASTRSS_EMOJIS_PATH))
                {
                $this->_EMOJIS_TRANS = require MYLASTRSS_EMOJIS_PATH;
                }
            if (is_array($this->_EMOJIS_TRANS) === FALSE)
                {
                $this->_EMOJIS_TRANS = array();
                }
            //todo emoj fin phrase avant ponctuation
            }
        }       
                
	function _InitDirectories()
		{
        if ($this->cache_dir != '') {
            @mkdir($this->cache_dir, 0777, true);
            if ($this->cache_feed_dir == '') $this->cache_feed_dir = $this->cache_dir;
            if ($this->cache_feeds_dir == '') $this->cache_feeds_dir = $this->cache_dir;
            if ($this->cache_errors_dir == '') $this->cache_errors_dir = $this->cache_dir;
            }
        if ($this->cache_feed_dir != '') {
            @mkdir($this->cache_feed_dir, 0777, true);
            }
        if ($this->cache_feeds_dir != '') {
            @mkdir($this->cache_feeds_dir, 0777, true);
            }
        if ($this->cache_errors_dir != '') {
            @mkdir($this->cache_errors_dir, 0777, true);
            }
        }       
                
	function Init($Reset=FALSE,$Processor='unknown')
		// Processor=unknown|none|rss|rdf|atom
		{
		if ($Reset)
			{
			$this->channeltags           = array();
			$this->itemtags              = array();
			$this->_LAST_ERROR_MESSAGES  = array();
			$this->_SOURCES              = array();
			$this->_HTML_ENTITIES_TRANS  = array();
			$this->_EMOJIS_TRANS         = array();
			}
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
        $this->_InitDirectories();
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
        $this->_InitDirectories();
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
					$cache_file = $this->cache_feeds_dir.'/'.$this->cache_feeds_prefix.'_cache_'.md5(serialize($sources).'?limit='.$this->items_limit.'&html='.$this->stripHTML.'&date='.$this->date_format.'&cdata='.$this->CDATA.'&cp='.$this->cp.'&kidx_rule='.$this->kidx_rule);
					}
				}
			}
		else
			{
			// TODO: support single feed
			}
			
        clearstatcache(true, $cache_file);
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
			$this->_SOURCES = (isset($result['sources']) ? $result['sources'] : []);
			$result['updatedTime'] = filemtime($cache_file);
			}
			
		return $result;
		}
	
	/* Return array of HTML images attributes */
	function fetchimg($content, $convert_encoding = TRUE)
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
					$temp = $this->my_preg_match("'$imgatt=[\'\"](.*?)[\'\"]'si", $imgcnt, $convert_encoding);
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
            $string = str_replace("&lt;&lt;",'´',$string);
            $string = str_replace("&gt;&gt;",'ª',$string);
			}
		
		// Replace entities by values
		$string = strtr ($string, $this->_HTML_ENTITIES_TRANS);
		
		if (strtoupper($this->cp) == 'UTF-8')
			{
			$string = preg_replace_callback(
				'~&#([0-9]+);~',
				function ($matches) {
					return mYLR_unichr($matches[1]);
				},
				$string
				);
			}
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
			
            clearstatcache(true, $cache_file);
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
				$result = $this->ParseFromSeveralSources();
				
				if (is_array($result))
					{
					$result['sources'] = $this->_SOURCES;
					if (isset($result['missingSource']) && ($result['missingSource'] === true) && ($this->cache_feeds_if_failed === false))
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
			$result = $this->ParseFromSeveralSources();
			if ($result)
				{
				$result['sources'] = $this->_SOURCES;
				$result['cached'] = 0;
				}
			}
			
        if (is_array($result))
            {
            if ($result['cached'] == 1)
                {
                $this->_SOURCES = $result['sources'];
                $result['updatedTime'] = filemtime($cache_file);
                }
            else
                {
                $result['updatedTime'] = time();
                }
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
		
	function ParseFromSeveralSources()
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
                clearstatcache(true, $cache_file);
				if (file_exists($cache_file))
					{
					$this->_SOURCES[$source_kidx]['cachedFileName']=$cacheFilename;
					$this->_SOURCES[$source_kidx]['updatedTime'] = filemtime($cache_file);
					}
				else $this->_SOURCES[$source_kidx]['updatedTime'] = 0;
				
				$errorFilename = 'mylr_content_'.$this->_URL2FileName($source['url']).'.txt';
				$error_file = $this->cache_errors_dir.'/'.$errorFilename;
                clearstatcache(true, $error_file);
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
		
		$result = [];
		$result['items'] = array(); // create array even if there are no items
		$result['namespaces'] = array();
		$i_source = 0;
		$sources_nb = count($this->_SOURCES);
		foreach($this->_SOURCES as $source_kidx => $source)
			{
			$i_source++;
			unset($oneresult);
			// How many time we could allow to download this source ?
			if ($this->max_execution_time !== 0)
				{
				$elapsedTime = time() - $this->_STARTED_TIME;
				$remainedTime = max(0,$this->max_execution_time - $elapsedTime);
				if (0 < $this->query_limit)
					{
					$remainedQueries = min(max(1, $this->query_limit - $this->_QUERY_COUNT), $sources_nb - $i_source);
					}
				else
					{
					$remainedQueries = $sources_nb - $i_source;
					}
                if ($remainedQueries > 0)
                    {
                    $this->timeOut = max($this->minTimeOut, ceil($remainedTime / $remainedQueries));
                    }
                else
                    {
                    $this->timeOut = max($this->minTimeOut, $remainedTime);
                    }
				}
				
			$oneresult = $this->GetFromOneSource($source['url'],$source_kidx);
			if (is_array($oneresult) === false)
				{
			    $result['missingSource'] = true;
                $this->_LAST_ERROR_MESSAGES[] = "Missing source '" . $source['url'] . "'.";
                continue;
				}
			if (isset($oneresult['namespaces']) && (count($oneresult['namespaces']) > 0))
				{
                $result['namespaces'] = array_merge($result['namespaces'], $oneresult['namespaces']);
                }
			if ((isset($oneresult['items']) === false) || (is_array($oneresult['items']) === false) || (count($oneresult['items']) === 0))
				{
			    $result['missingSource'] = true;
                $this->_LAST_ERROR_MESSAGES[] = "Empty source '" . $source['url'] . "'.";
                continue;
				}
            foreach($oneresult['items'] as $kidx => $item)
                {
                if (((isset($item['category']) === false) || ($item['category'] == '')) && isset($oneresult['category']))
                    {
                    $oneresult['items'][$kidx]['category'] = $oneresult['category'];
                    }
                $oneresult['items'][$kidx]['source_format'] = (isset($oneresult['feed_format']) ? $oneresult['feed_format'] : '');
                if (isset($item['source']) && ($item['source'] != ''))
                    {
                    $oneresult['items'][$kidx]['source_orig'] = $item['source'];
                    }
                $oneresult['items'][$kidx]['source'] = (isset($oneresult['title']) ? $oneresult['title'] : '');
                if (isset($item['source_url']) && ($item['source_url'] != ''))
                    {
                    $oneresult['items'][$kidx]['source_orig_url'] = $item['source_url'];
                    }
                $oneresult['items'][$kidx]['source_url'] = $source['url'];
                
                $oneresult['items'][$kidx]['source_link'] = (isset($oneresult['link']) ? $oneresult['link'] : '');
                $oneresult['items'][$kidx]['source_kidx'] = $source_kidx;
                }
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
		
	function _SourceCacheFileName($kidx,$limit,$stripHTML,$dateFormat,$CDATA,$CP,$kidx_rule='guid')
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
            $cacheFileExists = false;
			if (isset($this->_SOURCES[$source_kidx]['cachedFileName']))
				{
				$cacheFilename = $this->_SOURCES[$source_kidx]['cachedFileName'];
				$cache_file = $this->cache_feed_dir . DIRECTORY_SEPARATOR . $cacheFilename;
                clearstatcache(true, $cache_file);
				$cacheFileExists = file_exists($cache_file);
				$cacheFileTime = 0;
				if ($cacheFileExists === true) $cacheFileTime = $this->_SOURCES[$source_kidx]['updatedTime'];
				}
			if ($cacheFileExists === false)
				{
				$cacheFilename = $this->_SourceCacheFileName($source_kidx,$this->items_limit,$this->stripHTML,$this->date_format,$this->CDATA,$this->cp,$this->kidx_rule);
				$cache_file = $this->cache_feed_dir . DIRECTORY_SEPARATOR . $cacheFilename;
                clearstatcache(true, $cache_file);
				$cacheFileExists = file_exists($cache_file);
				$cacheFileTime = 0;
				if ($cacheFileExists === true) $cacheFileTime = filemtime($cache_file);
				}
			
			if (isset($this->_SOURCES[$source_kidx]['errorFileName']))
				{
				$errorFilename = $this->_SOURCES[$source_kidx]['errorFileName'];
				$error_content_file = $this->cache_errors_dir.'/'.$errorFilename;
				$errorFileExists = true;
				$errorFileTime = $this->_SOURCES[$source_kidx]['errorTime'];
				}
			else if ($this->retry_delay > 0)
				{
				$errorFilename = 'mylr_content_'.$this->_URL2FileName($rss_url).'.txt';
				$error_content_file = $this->cache_errors_dir.'/'.$errorFilename;
                clearstatcache(true, $error_content_file);
				$errorFileExists = file_exists($error_content_file);
				$errorFileTime = 0;
				if ($errorFileExists === true) $errorFileTime = filemtime($error_content_file);
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
				
			if (($cacheFileExists === true) && (($timedif < $this->cache_time) OR (($this->query_limit > 0) AND ($this->query_limit <= $this->_QUERY_COUNT))))
				{
				// cached file is fresh enough, return cached array
				$result = $this->_LoadCacheFile($cache_file);
				// set 'cached' to 1 only if cached file is correct
				if (is_array($result))
					{
					$result['cached'] = 1;
					}
				else
					{
					$this->_LAST_ERROR_MESSAGES[] = "[GetFromOneSource] Fail load '$rss_url' cached file.";
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
							if (is_array($result))
								{
								$result['cached'] = 1;
								}
							else
								{
								$this->_LAST_ERROR_MESSAGES[] = "[GetFromOneSource] Fail load '$rss_url' cached file.";
								}
							}
						// Don't use cache
						else $result['cached'] = 0;
						}
					else
						{
						$result['cached'] = 0;
						// cached file is too old, create new
						if ($this->_USE_SEVERAL_SOURCES == FALSE)
							{
							$result['sources'][$source_kidx]['title'] 		 = (isset($result['title']) ? $result['title'] : '');
							$result['sources'][$source_kidx]['link'] 		 = (isset($result['link']) ? $result['link'] : '');
							$result['sources'][$source_kidx]['items_count']  = $result['items_count'];
							$result['sources'][$source_kidx]['cached'] 	 	 = $result['cached'];
							$result['sources'][$source_kidx]['updatedTime']  = (isset($result['updatedTime']) ? $result['updatedTime'] : time());
							$result['sources'][$source_kidx]['encoding']	 = $result['encoding'];
							$result['sources'][$source_kidx]['feed_format']	 = $result['feed_format'];
							$result['sources'][$source_kidx]['generator']	 = $result['generator'];
							$result['sources'][$source_kidx]['namespaces']	 = $result['namespaces'];
							}
						if (($this->_USE_SEVERAL_SOURCES == FALSE) OR ($this->cache_all == TRUE))
							{
							if ($this->_SaveCacheFileAs($cache_file,$result))
                                {
                                $errorFilename = 'mylr_content_'.$this->_URL2FileName($rss_url).'.txt';
                                $error_content_file = $this->cache_errors_dir.'/'.$errorFilename;
                                @unlink($error_content_file);
                                }
							}
						}
					}
				// Feed not found or failed
				else if (($this->use_cache_if_failed == TRUE) AND ($cacheFileExists == TRUE))
					{
					$result = $this->_LoadCacheFile($cache_file);
					// set 'cached' to 1 only if cached file is correct
					if (is_array($result))
						{
						$result['cached'] = 1;
						}
					else
						{
						$this->_LAST_ERROR_MESSAGES[] = "[GetFromOneSource] Fail load '$rss_url' cached file.";
						}
					}
				}
			}
		// If CACHE DISABLED >> load and parse the file directly
		else
			{
			$result = $this->Parse($rss_url,$source_kidx);
			if (is_array($result))
				{
				$result['cached'] = 0;
				}
			}
		
		if (is_array($result))
			{
            if ($result['cached'] == 1)
                {
                $result['updatedTime'] = $cacheFileTime;
                $this->_SOURCES[$source_kidx]['cachedFileName'] = $cacheFilename;
                }
            else if ($result) $result['updatedTime'] = time();
			$this->_SOURCES[$source_kidx]['title'] 		 = (isset($result['title']) ? $result['title'] : '');
			$this->_SOURCES[$source_kidx]['link'] 		 = (isset($result['link']) ? $result['link'] : '');
			$this->_SOURCES[$source_kidx]['items_count'] = $result['items_count'];
			$this->_SOURCES[$source_kidx]['cached'] 	 = $result['cached'];
			$this->_SOURCES[$source_kidx]['updatedTime'] = $result['updatedTime'];
			$this->_SOURCES[$source_kidx]['encoding']	 = $result['encoding'];
			$this->_SOURCES[$source_kidx]['feed_format'] = $result['feed_format'];
			$this->_SOURCES[$source_kidx]['generator']	 = $result['generator'];
			$this->_SOURCES[$source_kidx]['namespaces']	 = $result['namespaces'];
			}
		else
			{
			$this->_SOURCES[$source_kidx]['items_count'] 	= 0;
			$this->_SOURCES[$source_kidx]['missingSource']	= TRUE;
			}
			
		return $result;
		}
	
	// to replace utf8_encode
	function encodeIso8859ToUtf8($string = '')
		{
			if (function_exists('mb_convert_encoding')) {
				return mb_convert_encoding($string, 'UTF-8', $this->_ANSI_ENCODING);
			}
			if (function_exists('iconv')) {
				return iconv($this->_ANSI_ENCODING, 'UTF-8', $string);
			}
			if (function_exists('utf8_encode')) {
				return utf8_encode($string);
			}
			return $string;
		}
	
	function my_convert_encoding($encStr='')
		{
		$result = $encStr;
		$strCP = $this->rsscp;
        
		if (strtolower($strCP) === 'utf-8')
            {
            // replace emojis if utf-8
            $this->_InitEmojisArray();
			$result=str_replace('¬©','&copy;',$result);
			$result=str_replace('‚ñ™Ô∏é','-',$result); // emoji petit carre noir
       		$result = strtr($result, $this->_EMOJIS_TRANS);
			$result=str_replace('‚Äã','',$result); // ZWSP U+200B espace sans chasse
			$result=str_replace('EÃÄ','&Egrave;',$result); // » ou E avec diacritic &#768;
            }
		
		// If code page is set convert character encoding to required
		if (strtoupper($this->cp) == 'UTF-8')
			{
			if (in_array(strtoupper($strCP), $this->_ANSI_ENCODINGS))
				{
				$result=str_replace('Ä','&euro;',$result);
				$result=str_replace('ﬂ','&szlig;',$result);
				$result = $this->encodeIso8859ToUtf8($result);
				}
			$result=str_replace(array('‚Äô','‚Äò'),"'",$result);
			$result=str_replace(array('‚Äú','‚Äù'),'"',$result);
			$result=str_replace('≈ì','oe',$result);
			$result=str_replace('&'.'euro;','‚Ç¨',$result);
			$result=str_replace('‚Äì','-',$result);
			$result=str_replace('‚Ä¶','...',$result);
			}
		else if ($this->cp != '')
			{
			if(function_exists('mb_convert_encoding'))
				{
				if ($strCP == '')
					{
					$this->rsscp = $strCP = 'auto';
					$this->_LAST_ERROR_MESSAGES[] = "mb_convert_encoding() not allow blank value encoding";
					}
					
				if (in_array(strtolower($strCP),array('auto','utf-8')))
					{
					$result=str_replace(' ‚Äå',' ',$result); //espace ?
					$result=str_replace('¬©','&copy;',$result);
					$result=str_replace('‚ñ™Ô∏é','*',$result);
					$result=str_replace(array('‚Ç¨'),'&'.'euro;',$result);
					$result=str_replace('‚ÄØ‚Äã',' ',$result); //espace fine
                    $result=str_replace('cÃß','&ccedil;',$result); // Á
					$result=str_replace('AÃÄ','&Agrave;',$result); // ¿
                    $result=str_replace('eÃÄ','&egrave;',$result); // Ë
                    $result=str_replace('aÃÄ','&agrave;',$result); // ‡
                    $result=str_replace('uÃÄ','&ugrave;',$result); // ˘
                    $result=str_replace('uÃÇ','&ucirc;',$result); // ˚
					$result=str_replace('‚Äì','-',$result);
					$result=str_replace('‚àí','-',$result);
					$result=str_replace('Ã∂','-',$result);
					$result=str_replace('‚Äë','-',$result);
					$result=str_replace('‚Ä¶','...',$result);
					$result=str_replace('‚Äù','-',$result);
					$result=str_replace('Ôºö',': ',$result);
					$result=str_replace('ÔΩú',' | ',$result);
					$result=str_replace('‚∏ª','---',$result);
					$result=str_replace('Ôªø','',$result); // bom utf8
					$result=str_replace('‚ÄÖ',' ',$result); //espace insecable ?
					$result=str_replace('‚ÄØ',' ',$result); //espace insecable ?
					$result=str_replace('·µâ','e',$result); // Lettre modificative minuscule E
					$result=str_replace('À¢','s',$result); // Lettre modificative minuscule S
					$result=str_replace('ƒÉ','a',$result); // a avec diacritic breve
					$result=str_replace('»ô','s',$result); // s avec diacritic
					$result=str_replace('≈ë','o',$result); // o double accent aigu
					$result=str_replace('ƒõ','e',$result); // e antiflexe
					$result=str_replace('ƒº','l',$result); // L virgule souscrite
					$result=str_replace('nÃÉ','n',$result); // n tilde
					$result=str_replace('IÃÇ','&Icirc;',$result); 
					$result=str_replace('EÃù','&Eacute;',$result); // …
                    $result=str_replace('eÃÇ','&ecirc;',$result); // Í
                    $result=str_replace('√´','&euml;',$result); // Î
                    $result=str_replace('eÃà','&euml;',$result); // Î
					$result=str_replace('eÃù','&eacute;',$result); // È
                    $result=str_replace('aÃÇ','&acirc;',$result); // ‚
                    $result=str_replace('oÃÇ','&ocirc;',$result); // Ù
                    $result=str_replace('√Æ','&icirc;',$result); // Ó
					$result=str_replace('iÃà','&iuml;',$result); //i trema minuscule
                    $result=str_replace('iÃÇ','&icirc;',$result); // Ó
                    $result=str_replace('√π','&ugrave;',$result); // ˘
                    $result=str_replace('√ß','&ccedil;',$result); // Á
					$result=str_replace('ƒü','g',$result); // g turc avec diacritic
					$result=str_replace('–°','C',$result); // C majuscule bizarre
					$result=str_replace(array('≈ì'),'oe',$result);
					$result=str_replace(' ≥','r',$result); // Lettre modificative minuscule R
					$result=str_replace('ƒá','c',$result); // c accent aigu
					$result=str_replace(array('‚Äâ','‚Ää','‚ñ†'),' ',$result);
					$result=str_replace(array('‚Äô','‚Äò'),"'",$result);
					$result=str_replace(array('‚Äú','‚Äù','Àù'),'"',$result);
					$result=str_replace('ƒù','c',$result); // c avec diacritic
					$result=str_replace('ƒù','a',$result); // a avec diacritic
					$result=str_replace('≈ô','r',$result); // lettre R diacritÈe d'un caron
                    $result=str_replace(' ‚Ä™',' ',$result); //espace suivie LEFT-TO-RIGHT EMBEDDING
					$result=str_replace(' ‚Äù',' ',$result); //espace suivie liant sans chasse
					$result=str_replace(' ‚ù†',' ',$result); //espace fine ?
					}
				
				$result = @mb_convert_encoding($result, $this->cp, $strCP);
				
				$result=str_replace('ú','oe',$result);
				$result=str_replace('å','OE',$result);
				
				if (in_array(strtoupper($this->cp), $this->_ANSI_ENCODINGS))
					{
                    $result = str_replace('†',' ',$result); // Espace etrange, insecable en ANSI ?
					$result=str_replace(array('¥','í'),"'",$result);
					$result=str_replace(array('Àù'),'"',$result);
					}
				}
			else if (function_exists('iconv'))
				{
				if ($strCP == 'auto')
					{
					$this->rsscp = $strCP = '';
					$this->_LAST_ERROR_MESSAGES[] = "iconv() not allow 'auto' value encoding";
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
	function my_preg_match ($pattern, $subject, $convert_encoding = TRUE) {
		// start regullar expression
		preg_match($pattern, $subject, $out);

		// if there is some result... process it and return it
		if(isset($out[1])) {
			// Process CDATA (if present)
			$out[1] = $this->process_cdata($out[1]);

			// If code page is set convert character encoding to required
			if (($convert_encoding === TRUE) AND ($this->cp != ''))
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
		
	function _sourceIsURL($rss_url)
		{
		if (substr($rss_url, 0, 7) === 'http://') return TRUE;
		if (substr($rss_url, 0, 8) === 'https://') return TRUE;
		return FALSE;
		}
		
	function _getSourceClientOptions($rss_url,$source_kidx='')
		{
		$options = array();
		
		$options['transport']		 = $this->transport;
		
		if ($this->timeOut > 0)
			{
			$options['time-out'] = $this->timeOut;
			}
			
		if ('' === trim($options['transport']))
			{
			$options['transport'] = 'fopen';
			if (class_exists('WpOrg\Requests\Autoload'))
				{
				$options['transport'] = 'WpRequests';
				}
			else if (class_exists('\Requests'))
				{
				$options['transport'] = 'Requests';
				}
			else if (class_exists('\Snoopy'))
				{
				$options['transport'] = 'Snoopy';
				}
			}
		
		if (FALSE === $this->_sourceIsURL($rss_url))
			{
			$options['transport'] = 'fopen';
			unset($options['time-out']);
			}
			
		$this->_SOURCES[$source_kidx]['client'] = $options;
		
		if ($this->userAgent !== '')
			{
			$options['user-agent'] = $this->userAgent;
			}
		if ($this->cache_dir != '')
			{
			$options['temp-dir'] = $this->cache_dir;
			}
			
		return $options;
		}
		
	// -------------------------------------------------------------------
	// Parse() is private method used by Get() to load and parse RSS file.
	// Don't use Parse() in your scripts - use Get($rss_file) instead.
	// -------------------------------------------------------------------
	function Parse ($rss_url,$source_kidx='')
		{
		$this->_InitGlobalsFormatsXmlNs();
		
		$parsing_started_time = time();
		if ($source_kidx == '') $source_kidx = $this->_SourceKIDX($rss_url);
		if (isset($this->_SOURCES[$source_kidx]['errorFileName']))
			{
			$errorFilename = $this->_SOURCES[$source_kidx]['errorFileName'];
			}
		else
			{
			$errorFilename = 'mylr_content_'.$this->_URL2FileName($rss_url).'.txt';
			}
		$error_content_file = $this->cache_errors_dir.'/'.$errorFilename;
		
		$client = new mYLR_Client($this->_getSourceClientOptions($rss_url,$source_kidx));
		if ($this->_sourceIsURL($rss_url))
			{
			$this->_QUERY_COUNT++;
			}
		$rss_content = $client->getContent($rss_url);
		if ($client->isRedirect())
			{
			$this->_LAST_ERROR_MESSAGES[] = $client->getTransportName().'->getContent('.$rss_url.') redirected to "'.$client->getLastRedirect().'"';
			}
		if (0 === strlen(trim($rss_content)))
			{
			if ($client->isTimedOut())
				{
				$this->_LAST_ERROR_MESSAGES[] = $client->getTransportName().'->getContent('.$rss_url.') timed out';
				}
			else if ((0 !== $client->getStatusCode()) OR ('' !== $client->getLastErrorMessage()))
				{
				$this->_LAST_ERROR_MESSAGES[] = $client->getTransportName().'->getContent('.$rss_url.') return status '.$client->getStatusCode().' "'.$client->getLastErrorMessage().'"';
				}
			else
				{
				$this->_LAST_ERROR_MESSAGES[] = $client->getTransportName().'->getContent('.$rss_url.') return empty response';
				}
			if ($this->cache_dir != '')
				{
				@touch($error_content_file);
				}
			return FALSE;
			}
			
		// Clean-up first lines (and prevent PHP/Apache errors displayed)
		if (($posXML = strpos($rss_content,'<?xml')) AND ($posXML > 0))
			{
			$rss_content = trim(substr($rss_content,$posXML));
			}
		// Clean-up HTML comments
		$rss_content = trim(mYLR_StripHTMLcomment($rss_content));
		// Clean useless spaces
		$rss_content = trim(mYLR_TrimXmlTags($rss_content));
		// Create header chunk to detect format
		$rss_content_chunk = trim(strtolower(substr($rss_content,0,350)));
		
		if (strlen($rss_content_chunk) == 0)
			{
			// Error in opening return False
			$this->_LAST_ERROR_MESSAGES[] = $client->getTransportName().'->getContent('.$rss_url.') return useless content';
			if ($this->cache_dir != '')
				{
				@touch($error_content_file);
				}
			return False;
			}
		else if (strpos($rss_content_chunk,'<html') !== FALSE)
			{
			$this->_LAST_ERROR_MESSAGES[] = "HTML content downloaded from '$rss_url'";
			if ($this->cache_dir != '')
				{
				@touch($error_content_file);
				}
			return False;
			}
		else if (strpos($rss_content_chunk,'<feed') !== FALSE)
			{
			return $this->_ParseAtom($rss_url,$source_kidx,$rss_content);
			}
		else if (strpos($rss_content_chunk,'<urlset') !== FALSE)
			{
			return $this->_ParseSitemap($rss_url,$source_kidx,$rss_content);
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
			if ($channel_content == '')
				{
				$this->_LAST_ERROR_MESSAGES[] = "Empty channel content in '$rss_url'";
				}
				
			// Parse CHANNEL info
			foreach($this->channeltags as $channeltag)
				{
				$temp = trim($this->my_preg_match("'<$channeltag.*?>(.*?)</$channeltag>'si", $channel_content));
				if ($temp != '') $result[$channeltag] = $temp; // Set only if not empty
				}
			
			// If lastBuildDate is valid
			if (isset($result['lastBuildDate']) && ($result['lastBuildDate'] != '') && (($timestamp = mYLR_RSSPubDate2UnixTimeStamp($result['lastBuildDate'])) > 0))
				{
				$result['lastBuildTimeStamp'] = $timestamp;
				// If date_format is specified
				if ($this->date_format != '') {
					// convert lastBuildDate to specified date format
					$result['lastBuildDate'] = gmdate($this->date_format, $timestamp);
					}
				}
			else if (isset($result['lastBuildDate']) && ($result['lastBuildDate'] != ''))
				{
				$this->_LAST_ERROR_MESSAGES[] = "'$rss_url'".' has bad lastBuildDate format';
				}

			// If pubDate is valid
			if (isset($result['pubDate']) && ($result['pubDate'] != '') && (($timestamp = mYLR_RSSPubDate2UnixTimeStamp($result['pubDate'])) > 0))
				{
				$result['pubTimeStamp'] = $timestamp;
				// If date_format is specified
				if ($this->date_format != '') {
					// convert lastBuildDate to specified date format
					$result['pubDate'] = gmdate($this->date_format, $timestamp);
					}
				}
			else if (isset($result['dc:date']) && ($result['dc:date'] != ''))
				{
				$timestamp = mYLR_DCDate2UnixTimeStamp($result['dc:date']);
				if ($timestamp !== null)
					{
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
				else
					{
                    $this->_LAST_ERROR_MESSAGES[] = "'$rss_url'".' has bad dc:date format';
					}
				}
			else if (isset($result['lastBuildTimeStamp']) && ($result['lastBuildTimeStamp'] != ''))
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
			else if (isset($result['pubDate']) && ($result['pubDate'] != ''))
				{
				$this->_LAST_ERROR_MESSAGES[] = "'$rss_url'".' has bad pubDate format';
				}

			// Parse TEXTINPUT info
			// Todo: fix for <textInput></textInput>
			preg_match("'<textinput(|[^>]*[^/])>(.*?)</textinput>'si", $channel_content, $out_textinfo);
			// This a little strange regexp means:
			// Look for tag <textinput> with or without any attributes, but skip truncated version <textinput /> (it's not beggining tag)
			if (isset($out_textinfo[2])) {
				foreach($this->_GLOBAL_FORMATS[$feed_format]['channel_textinput_tags'] as $textinputtag) {
					$temp = $this->my_preg_match("'<$textinputtag.*?>(.*?)</$textinputtag>'si", $out_textinfo[2]);
					if ($temp != '') $result['textinput_'.$textinputtag] = $temp; // Set only if not empty
				}
			}
			// Parse IMAGE info
			preg_match("'<image.*?>(.*?)</image>'si", $channel_content, $out_imageinfo);
			if (isset($out_imageinfo[1])) {
				foreach($this->_GLOBAL_FORMATS[$feed_format]['channel_image_tags'] as $imagetag) {
					$temp = $this->my_preg_match("'<$imagetag.*?>(.*?)</$imagetag>'si", $out_imageinfo[1]);
					if ($temp != '') $result['image_'.$imagetag] = $temp; // Set only if not empty
				}
			}
			
			$result['items'] = array(); // create array even if there are no items
			// Parse ITEMS
			preg_match_all("'<item(| .*?)>(.*?)</item>'si", $rss_content, $items);
			$rss_items = $items[2];
			$i = 0;
			if (count($rss_items) == 0)
				{
				$this->_LAST_ERROR_MESSAGES[] = "No item found in '$rss_url'";
				}
			foreach($rss_items as $rss_item)
				{
				$itemResult = array();
				$rss_item = trim($rss_item);	
				if ($rss_item === '') continue;
				
				// Parse item tags to $itemResult[]
				foreach($this->itemtags as $itemtag)
					{
					$temp = $this->my_preg_match("'<$itemtag\b.*?>(.*?)</$itemtag>'si", $rss_item, TRUE);
					if ($temp != '')
						{
                        // dedoublon prendre le dernier
                        $itemtag_results = array();
                        preg_match_all("'<$itemtag(| .*?)>(.*?)</$itemtag>'si", $rss_item, $itemtag_results);
                        $itemtag_values = $itemtag_results[2];
                        $nb_itemtag_values = count($itemtag_values);
                        if ($nb_itemtag_values > 1)
                            {
                            $temp = $this->my_convert_encoding($itemtag_values[($nb_itemtag_values - 1)]);
                            }
						$itemResult[$itemtag] = $temp; // Set only if not empty
						}
					}
				if (count($itemResult) == 0)
					{
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
					if (isset($itemResult['feedburner:origLink']) && ($itemResult['feedburner:origLink'] != ''))
						{
						$itemResult['feedburner:trackLink'] = $itemResult['link'];
						$itemResult['link'] = $itemResult['feedburner:origLink'];
						unset($itemResult['feedburner:origLink']);
						}
					else if (isset($itemResult['fs:srclink']) && ($itemResult['fs:srclink'] != ''))
						{
						$itemResult['fs:trackLink'] = $itemResult['link'];
						$itemResult['link'] = $itemResult['fs:srclink'];
						unset($itemResult['fs:srclink']);
						}
					else if (isset($result['generator']) && ($result['generator'] == 'Feediz'))
						{
						$itemResult['feediz:trackLink'] = $itemResult['link'];
						$itemResult['link'] = $itemResult['guid'];
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
						
					if (strpos($itemResult['link'],'acpm.fr/track') > 1)
						{
						$itemResult['acpm:trackLink'] = $itemResult['link'];
						$itemResult['link'] = mYLR_DecodeAcpmURL($itemResult['link']);
						}
						
					if (strpos($itemResult['link'],'ns_campaign=') > 1)
						{
						$itemResult['nedstat:trackLink'] = $itemResult['link'];
						$itemResult['link'] = mYLR_StripNedStatFragment($itemResult['link']);
						}
						
					if (strpos($itemResult['link'],'*') > 1)
						{
						$itemResult['yahoo:trackLink'] = $itemResult['link'];
						$itemResult['link'] = urldecode(substr(strrchr($itemResult['link'],'*'),1));
						}
						
					if (strpos($itemResult['link'],'xtor=') > 1)
						{
						$itemResult['xtor:trackLink'] = $itemResult['link'];
						$itemResult['link'] = mYLR_StripXtorFragment($itemResult['link']);
						}
						
					if (strpos($itemResult['link'],'utm_') > 1)
						{
						$itemResult['utm:trackLink'] = $itemResult['link'];
						$itemResult['link'] = mYLR_StripUtmFragment($itemResult['link']);
						}
						
					if (strpos($itemResult['link'],'at_medium') > 1)
						{
						$itemResult['atmedium:trackLink'] = $itemResult['link'];
						$itemResult['link'] = mYLR_StripAtMediumFragment($itemResult['link']);
						}
						
					if (strpos($itemResult['link'],'?rss') > 1)
						{
						$itemResult['rss:trackLink'] = $itemResult['link'];
						$itemResult['link'] = mYLR_StripRssFragment($itemResult['link']);
						}
						
					if (strpos($itemResult['link'],'?cache=') > 1)
						{
                        // use by RDS.ca
						$itemResult['cache:trackLink'] = $itemResult['link'];
						$itemResult['link'] = mYLR_StripCacheFragment($itemResult['link']);
						}
					}
				$itemResult['link'] = mYLR_URLunEntities($itemResult['link']);
					
				// If pubDate is valid
				if (isset($itemResult['pubDate']) && ($itemResult['pubDate'] != '') && (($timestamp = mYLR_RSSPubDate2UnixTimeStamp($itemResult['pubDate'])) > 0))
					{
					$itemResult['pubTimeStamp'] = $timestamp;
					// If date_format is specified
					if ($this->date_format != '')
						{
						// convert pubDate to specified date format
						$itemResult['pubDate'] = gmdate($this->date_format, $timestamp);
						}
					}
				else if (isset($itemResult['dc:date']) && ($itemResult['dc:date'] != ''))
					{
					$timestamp = mYLR_DCDate2UnixTimeStamp($itemResult['dc:date']);
                    if ($timestamp !== null)
                        {
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
                        $this->_LAST_ERROR_MESSAGES[] = "'$rss_url'".' has bad dc:date format';
                        }
					}
				else if (isset($itemResult['dcterms:modified']) && ($itemResult['dcterms:modified'] != ''))
					{
					$timestamp = mYLR_DCDate2UnixTimeStamp($itemResult['dcterms:modified']);
                    if ($timestamp !== null)
                        {
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
                        $this->_LAST_ERROR_MESSAGES[] = "'$rss_url'".' has bad dcterms:modified format';
                        }
					}
				else if (isset($itemResult['a10:updated']) && ($itemResult['a10:updated'] != ''))
					{
					$timestamp = mYLR_DCDate2UnixTimeStamp($itemResult['a10:updated']);
                    if ($timestamp !== null)
                        {
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
                        $this->_LAST_ERROR_MESSAGES[] = "'$rss_url'".' has bad a10:updated format';
                        }
					}
				else if (isset($itemResult['pubDate']) && ($itemResult['pubDate'] != ''))
					{
					$timestamp = mYLR_DCDate2UnixTimeStamp($itemResult['pubDate']);
                    if ($timestamp !== null)
                        {
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
                        $this->_LAST_ERROR_MESSAGES[] = "'$rss_url'".' has bad pubDate format';
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
					
				$itemResult['title'] = mYLR_StripCR(isset($itemResult['title']) ? $itemResult['title'] : '');
				
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
						continue;
						}
					}
				else if ($this->kidx_rule == 'date+title')
					{
					// Create unique index (with MD5) from date & title for this item
					if ((isset($itemResult['pubTimeStamp'])) AND ($itemResult['title'] != ''))
						{
						$kidx = md5(gmdate('dmY',$itemResult['pubTimeStamp']).$this->_StandardizedStr($itemResult['title']));
						}
					else
						{
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
				$result['items'][$kidx]['source_link'] = (isset($result['link']) ? $result['link'] : '');
				$result['items'][$kidx]['source_kidx'] = $source_kidx;
				
				// Parse ENCLOSURE info
				unset($result['items'][$kidx]['enclosure']); // May not exists
				preg_match("'<enclosure(.*?)>'si", $rss_item, $out_enclosure);
				if (isset($out_enclosure[1]))
					{
					foreach($this->_GLOBAL_FORMATS[$feed_format]['item_enclosure_attributes'] as $enclosureprop)
						{
                        $convert_encoding = FALSE; // todo url require alway be in utf8
						$temp = $this->my_preg_match("'\s$enclosureprop=[\'\"](.*?)[\'\"]'si", $out_enclosure[1], $convert_encoding);
						if ($temp != '') $result['items'][$kidx]['enclosure_'.$enclosureprop] = $temp; // Set only if not empty
						}
					}
				if ($this->useOrigLink == TRUE)
					{
					if (isset($result['items'][$kidx]['feedburner:origEnclosureLink']) && ($result['items'][$kidx]['feedburner:origEnclosureLink'] != ''))
						{
						$result['items'][$kidx]['feedburner:trackEnclosure'] = $result['items'][$kidx]['enclosure_url'];
						$result['items'][$kidx]['enclosure_url'] = $result['items'][$kidx]['feedburner:origEnclosureLink'];
						unset($result['items'][$kidx]['feedburner:origEnclosureLink']);
						}
					
					if (isset($result['items'][$kidx]['enclosure_url']) && (strpos($result['items'][$kidx]['enclosure_url'],'/0L') > 1))
						{
						$result['items'][$kidx]['feedsportal:trackEnclosure'] = $result['items'][$kidx]['enclosure_url'];
						$result['items'][$kidx]['enclosure_url'] = mYLR_DecodeFeedPortalURL($result['items'][$kidx]['enclosure_url']);
						}
					}

				// Parse Media RSS info
				preg_match("'<media:content(.*?)>'si", $rss_item, $out_enclosure);
				if (isset($out_enclosure[1]))
					{
					foreach($this->_GLOBAL_XMLNS['media']['item_media:content_attributes'] as $enclosureprop)
						{
						$temp = $this->my_preg_match("'\s$enclosureprop=[\'\"](.*?)[\'\"]'si", $out_enclosure[1]);
						if ($temp != '') $result['items'][$kidx]['media:content_'.$enclosureprop] = $temp; // Set only if not empty
						}
					}
				preg_match("'<media:thumbnail(.*?)>'si", $rss_item, $out_enclosure);
				if (isset($out_enclosure[1]))
					{
					foreach($this->_GLOBAL_XMLNS['media']['item_media:thumbnail_attributes'] as $enclosureprop)
						{
						$temp = $this->my_preg_match("'\s$enclosureprop=[\'\"](.*?)[\'\"]'si", $out_enclosure[1]);
						if ($temp != '') $result['items'][$kidx]['media:thumbnail_'.$enclosureprop] = $temp; // Set only if not empty
						}
					}
				preg_match("'<media:player(.*?)>'si", $rss_item, $out_enclosure);
				if (isset($out_enclosure[1]))
					{
					foreach($this->_GLOBAL_XMLNS['media']['item_media:player_attributes'] as $enclosureprop)
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
			$this->_LAST_ERROR_MESSAGES[] = $client->getTransportName().'->getContent('.$rss_url.') return unknown content';
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
	
	function _ParseSitemap($rss_url,$source_kidx,$rss_content)
		{
		$this->_InitGlobalsFormatsXmlNs();
		
		$result = array();
		$result['source_url'] 		= $rss_url;
		$result['source_kidx'] 		= $source_kidx;
		$result['feed_format'] 		= 'sitemap';
		$result['generator'] 		= '';
        
		// Parse document encoding
		$result['encoding'] = $this->my_preg_match("'\sencoding=[\'\"](.*?)[\'\"]'si", $rss_content);
		if ($result['encoding'] != '')
			{ $this->rsscp = $result['encoding']; } 
		else
			{ $this->rsscp = $this->default_cp; } 
		
		// detect extension namespaces
		preg_match_all("'\sxmlns:(.*?)=[\'\"](.*?)[\'\"]'si", $rss_content, $nspaces_results);
		$result['namespaces'] = $nspaces_results[1];
		$result['namespaces'] = array_values(array_unique($result['namespaces']));
		
		$this->_InitSupportedTags($result['feed_format'],$result['namespaces']);
        
		$result['items'] = array(); // create array even if there are no items
		// Parse ITEMS
		preg_match_all("'<url(| .*?)>(.*?)</url>'si", $rss_content, $items);
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
					if (strpos($itemtag,':') === FALSE)
						{
						$itemResult['sitemap:'.$itemtag] = $temp;
						}
					else
						{
						$itemResult[$itemtag] = $temp;
						}
					}
				}
			if (count($itemResult) === 0)
				{
				continue;
				}

			if (isset($itemResult['sitemap:loc']))
				{
				$itemResult['guid'] = $itemResult['sitemap:loc'];
				$itemResult['guid_isPermaLink'] = true;
				$itemResult['link'] = $itemResult['sitemap:loc'];
				unset($itemResult['sitemap:loc']);
				}
            else
				{
				continue;
				}
			$itemResult['link'] = mYLR_URLunEntities($itemResult['link']);
                
			// Search pubdate
			if (isset($itemResult['news:publication_date']) && ($itemResult['news:publication_date'] != ''))
				{
				$itemResult['dc:date'] = $itemResult['news:publication_date'];
				}
			else if (isset($itemResult['video:publication_date']) && ($itemResult['video:publication_date'] != ''))
				{
				$itemResult['dc:date'] = $itemResult['video:publication_date'];
				}
			else if (isset($itemResult['sitemap:lastmod']) && ($itemResult['sitemap:lastmod'] != ''))
				{
				$itemResult['dc:date'] = $itemResult['sitemap:lastmod'];
				unset($itemResult['sitemap:lastmod']);
				}
			if ($itemResult['dc:date'] != '')
				{
				$timestamp = mYLR_DCDate2UnixTimeStamp($itemResult['dc:date']);
                if ($timestamp !== null)
                    {
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
                    $this->_LAST_ERROR_MESSAGES[] = "'$rss_url'".' has bad dc:date format';
                    }
				}
			else
				{
				$this->_LAST_ERROR_MESSAGES[] = 'Item '.$itemResult['guid'].' has not pubDate';
				}
                
			if (isset($itemResult['news:title']))
				{
				$itemResult['title'] = $itemResult['news:title'];
				}
			else if (isset($itemResult['video:title']))
				{
				$itemResult['title'] = $itemResult['video:title'];
				}
			else if (isset($itemResult['image:title']) && (in_array(substr($item['image:title'], -4), ['.jpg', '.png']) === false))
				{
				$itemResult['title'] = $itemResult['image:title'];
				}
			$itemResult['title'] = mYLR_StripCR(isset($itemResult['title']) ? $itemResult['title'] : '');
                
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
				else
					{
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
					continue;
					}
				}
			else if ($this->kidx_rule == 'date+title')
				{
				// Create unique index (with MD5) from date & title for this item
				if ((isset($itemResult['pubTimeStamp'])) AND ($itemResult['title'] != ''))
					{
					$kidx = md5(gmdate('dmY',$itemResult['pubTimeStamp']).$this->_StandardizedStr($itemResult['title']));
					}
				else
					{
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
                
			// search desc and content
			if (isset($result['items'][$kidx]['video:description']))
				{
				$result['items'][$kidx]['description'] = $result['items'][$kidx]['video:description'];
				}

			// Strip HTML tags and other bullshit from TITLE
			if ($this->stripHTML && $result['items'][$kidx]['title'])
				$result['items'][$kidx]['title'] = mYLR_Trim(strip_tags($this->unhtmlentities($result['items'][$kidx]['title'])));
			
			// Strip HTML tags and other bullshit from DESCRIPTION
			if ($this->stripHTML && $result['items'][$kidx]['description'])
				{
				$result['items'][$kidx]['description'] = mYLR_Trim(strip_tags($this->unhtmlentities($result['items'][$kidx]['description'])));
				}

			$item_categories = [];
			if (isset($result['items'][$kidx]['news:keywords']))
				{
				$item_categories = explode(',', $result['items'][$kidx]['news:keywords']);
				}
			else if (isset($result['items'][$kidx]['video:tag']))
				{
				$item_categories = explode(',', $result['items'][$kidx]['video:tag']);
				}
			if (count($item_categories) > 0)
				{
                $result['items'][$kidx]['categories'] = [];
				foreach($item_categories as $item_category)
					{
					$result['items'][$kidx]['categories'][] = trim($item_category);
					}
				$result['items'][$kidx]['category'] = trim($item_categories[0]);
				}
            
			if (isset($result['items'][$kidx]['image:loc']))
				{
				$result['items'][$kidx]['media:thumbnail_url'] = $result['items'][$kidx]['image:loc'];
				}
			else if (isset($result['items'][$kidx]['video:thumbnail_loc']))
				{
				$result['items'][$kidx]['media:thumbnail_url'] = $result['items'][$kidx]['video:thumbnail_loc'];
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
	
	function _ParseAtom($rss_url,$source_kidx,$rss_content)
		{
		$this->_InitGlobalsFormatsXmlNs();
		
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
		$result['namespaces'][] = 'atom';
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
		
		// Parse CHANNEL info
		foreach($this->channeltags as $channeltag)
			{
			$temp = trim($this->my_preg_match("'<$channeltag.*?>(.*?)</$channeltag>'si", $channel_content));
			if ($temp != '')
				{
				if (strpos($channeltag,':') === FALSE)
					{
					$result['atom:'.$channeltag] = $temp;
					}
				else
					{
					$result[$channeltag] = $temp;
					}
				}
			}
			
		if (isset($result['atom:title']) && ($result['atom:title'] != ''))
			{
			$result['title'] = $result['atom:title'];
			unset($result['atom:title']);
			}
		
        $convert_encoding = true; // todo url require alway be in utf8
		$temp = $this->my_preg_match("'<link.*?rel=[\'\"]alternate[\'\"].*?href=[\'\"](.*?)[\'\"].*?>'si", $channel_content, $convert_encoding);
		if ($temp != '') $result['link'] = $temp; // Set only if not empty
		if ((isset($result['link']) === false) || ($result['link'] == ''))
			{
			$temp = $this->my_preg_match("'<link.*?href=[\'\"](.*?)[\'\"].*?rel=[\'\"]alternate[\'\"].*?>'si", $channel_content, $convert_encoding);
			if ($temp != '') $result['link'] = $temp; // Set only if not empty
			}
		if ((isset($result['link']) === false) || ($result['link'] == ''))
			{
			$temp = $this->my_preg_match("'<link.*?rel=[\'\"]self[\'\"].*?href=[\'\"](.*?)[\'\"].*?type=[\'\"]text/html[\'\"].*?>'si", $channel_content, $convert_encoding);
			if ($temp != '') $result['link'] = $temp; // Set only if not empty
			}
	
		// Search pubdate
		if (isset($result['atom:updated']) && ($result['atom:updated'] != ''))
			{
			$result['dc:date'] = $result['atom:updated'];
			}
		else if (isset($result['atom:published']) && ($result['atom:published'] != ''))
			{
			$result['dc:date'] = $result['atom:published'];
			}
		if (isset($result['dc:date']) && ($result['dc:date'] != ''))
			{
			$timestamp = mYLR_DCDate2UnixTimeStamp($result['dc:date']);
            if ($timestamp !== null)
                {
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
            else
                {
                $this->_LAST_ERROR_MESSAGES[] = "'$rss_url'".' has bad dc:date format';
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
			
			// Parse item tags to $itemResult[]
			foreach($this->itemtags as $itemtag)
				{
				$temp = $this->my_preg_match("'<$itemtag\b.*?>(.*?)</$itemtag>'si", $rss_item);
				if ($temp != '')
					{
					if (strpos($itemtag,':') === FALSE)
						{
						$itemResult['atom:'.$itemtag] = $temp;
						}
					else
						{
						$itemResult[$itemtag] = $temp;
						}
					}
				}
			if (count($itemResult) == 0)
				{
				continue;
				}
			
			if ($itemResult['atom:id'] != '')
				{
				$itemResult['guid'] = $itemResult['atom:id'];
				$itemResult['guid_isPermaLink'] = FALSE;
				unset($itemResult['atom:id']);
				}
			
			// Search pubdate
			if (isset($itemResult['atom:updated']) && ($itemResult['atom:updated'] != ''))
				{
				$itemResult['dc:date'] = $itemResult['atom:updated'];
				}
			else if (isset($itemResult['atom:modified']) && ($itemResult['atom:modified'] != ''))
				{
				$itemResult['dc:date'] = $itemResult['atom:modified'];
				}
			else if ($itemResult['atom:published'] != '')
				{
				$itemResult['dc:date'] = $itemResult['atom:published'];
				}
			else if ($itemResult['atom:issued'] != '')
				{
				$itemResult['dc:date'] = $itemResult['atom:issued'];
				}
			else if ($itemResult['atom:created'] != '')
				{
				$itemResult['dc:date'] = $itemResult['atom:created'];
				}
			if ($itemResult['dc:date'] != '')
				{
				$timestamp = mYLR_DCDate2UnixTimeStamp($itemResult['dc:date']);
                if ($timestamp !== null)
                    {
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
                    $this->_LAST_ERROR_MESSAGES[] = "'$rss_url'".' has bad dc:date format';
                    }
				}
			else
				{
				$this->_LAST_ERROR_MESSAGES[] = 'Item '.$itemResult['guid'].' has not pubDate';
				}
			
			// Search link
            $convert_encoding = true; // todo url require alway be in utf8
			$temp = $this->my_preg_match("'<link.*?href=[\'\"](.*?)[\'\"].*?rel=[\'\"]alternate[\'\"].*?>'si", $rss_item, $convert_encoding);
			if ($temp != '') $itemResult['link'] = $temp;
			if ((isset($itemResult['link']) === false) || ($itemResult['link'] == ''))
				{
				$temp = $this->my_preg_match("'<link.*?rel=[\'\"]alternate[\'\"].*?href=[\'\"](.*?)[\'\"].*?>'si", $rss_item, $convert_encoding);
				if ($temp != '') $itemResult['link'] = $temp;
				}
			if ((isset($itemResult['link']) === false) || ($itemResult['link'] == ''))
				{
				$temp = $this->my_preg_match("'<link.*?href=[\'\"](.*?)[\'\"].*?>'si", $rss_item, $convert_encoding);
				if ($temp != '') $itemResult['link'] = $temp;
				}
			if ($this->useOrigLink == TRUE)
				{
				if (isset($itemResult['feedburner:origLink']) && ($itemResult['feedburner:origLink'] != ''))
					{
					$itemResult['feedburner:trackLink'] = $itemResult['link'];
					$itemResult['link'] = $itemResult['feedburner:origLink'];
					unset($itemResult['feedburner:origLink']);
					}
				else if (isset($itemResult['fs:srclink']) && ($itemResult['fs:srclink'] != ''))
					{
					$itemResult['fs:trackLink'] = $itemResult['link'];
					$itemResult['link'] = $itemResult['fs:srclink'];
					unset($itemResult['fs:srclink']);
					}
				else if ($result['generator'] == 'Feediz')
					{
					// need test case
					/*
					$itemResult['feediz:trackLink'] = $itemResult['link'];
					$itemResult['link'] = $itemResult['guid'];
					*/
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
						
				if (strpos($itemResult['link'],'acpm.fr/track') > 1)
					{
					$itemResult['acpm:trackLink'] = $itemResult['link'];
					$itemResult['link'] = mYLR_DecodeAcpmURL($itemResult['link']);
					}
						
				if (strpos($itemResult['link'],'ns_campaign=') > 1)
					{
					$itemResult['nedstat:trackLink'] = $itemResult['link'];
					$itemResult['link'] = mYLR_StripNedStatFragment($itemResult['link']);
					}
						
				if (strpos($itemResult['link'],'*') > 1)
					{
					$itemResult['yahoo:trackLink'] = $itemResult['link'];
					$itemResult['link'] = urldecode(substr(strrchr($itemResult['link'],'*'),1));
					}
					
				if (strpos($itemResult['link'],'xtor=') > 1)
					{
					$itemResult['xtor:trackLink'] = $itemResult['link'];
					$itemResult['link'] = mYLR_StripXtorFragment($itemResult['link']);
					}
					
				if (strpos($itemResult['link'],'utm_') > 1)
					{
					$itemResult['utm:trackLink'] = $itemResult['link'];
					$itemResult['link'] = mYLR_StripUtmFragment($itemResult['link']);
					}
						
                if (strpos($itemResult['link'],'at_medium') > 1)
                    {
                    $itemResult['atmedium:trackLink'] = $itemResult['link'];
                    $itemResult['link'] = mYLR_StripAtMediumFragment($itemResult['link']);
                    }
					
				if (strpos($itemResult['link'],'?rss') > 1)
					{
					$itemResult['rss:trackLink'] = $itemResult['link'];
					$itemResult['link'] = mYLR_StripRssFragment($itemResult['link']);
					}
                    
                if (strpos($itemResult['link'],'?cache=') > 1)
                    {
                    // use by RDS.ca
                    $itemResult['cache:trackLink'] = $itemResult['link'];
                    $itemResult['link'] = mYLR_StripCacheFragment($itemResult['link']);
                    }
				}
			$itemResult['link'] = mYLR_URLunEntities($itemResult['link']);

			if (isset($itemResult['atom:title']) && ($itemResult['atom:title'] != ''))
				{
				$itemResult['title'] = $itemResult['atom:title'];
				unset($itemResult['atom:title']);
				}
			if (isset($itemResult['title'])) { $itemResult['title'] = mYLR_StripCR($itemResult['title']); }
			
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
					continue;
					}
				}
			else if ($this->kidx_rule == 'date+title')
				{
				// Create unique index (with MD5) from date & title for this item
				if ((isset($itemResult['pubTimeStamp'])) && isset($itemResult['title']) && ($itemResult['title'] != ''))
					{
					$kidx = md5(gmdate('dmY',$itemResult['pubTimeStamp']).$this->_StandardizedStr($itemResult['title']));
					}
				else
					{
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
			
			// search desc and content
			if (isset($result['items'][$kidx]['atom:summary']))
				{
				$result['items'][$kidx]['description'] = $result['items'][$kidx]['atom:summary'];
				unset($result['items'][$kidx]['atom:summary']);
				}
			if (isset($result['items'][$kidx]['atom:description']))
				{
				$result['items'][$kidx]['description'] = $result['items'][$kidx]['atom:description'];
				unset($result['items'][$kidx]['atom:description']);
				}
			if (isset($result['items'][$kidx]['atom:content']))
				{
				$temp = $this->my_preg_match("'<content.*?type=[\'\"]text[\'\"].*?>(.*?)</content>'si", $rss_item);
				if ($temp != '') $result['items'][$kidx]['description'] = $temp;
					
				$temp = $this->my_preg_match("'<content.*?type=[\'\"].*?html[\'\"].*?>(.*?)</content>'si", $rss_item);
				if ($temp != '') $result['items'][$kidx]['content:encoded'] = $temp;
				
				unset($result['items'][$kidx]['atom:content']);
				}

			// Strip HTML tags and other bullshit from TITLE
			if ($this->stripHTML && $result['items'][$kidx]['title'])
				$result['items'][$kidx]['title'] = mYLR_Trim(strip_tags($this->unhtmlentities($result['items'][$kidx]['title'])));
			
			// Strip HTML tags and other bullshit from DESCRIPTION
			if ($this->stripHTML && $result['items'][$kidx]['description'])
				{
				if (isset($result['items'][$kidx]['content:encoded']) == FALSE) $result['items'][$kidx]['content:encoded'] = $result['items'][$kidx]['description'];
				$result['items'][$kidx]['description'] = mYLR_Trim(strip_tags($this->unhtmlentities($result['items'][$kidx]['description'])));
				}
			
			if (isset($result['items'][$kidx]['atom:author']))
				{
				$temp = $this->my_preg_match("'<author>.*?<name>(.*?)</name>.*?</author>'si", $rss_item);
				if ($temp != '') $result['items'][$kidx]['dc:creator'] = $temp;
				unset($result['items'][$kidx]['atom:author']);
				}
			
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
			unset($result['items'][$kidx]['atom:category']);
		
			// Parse ENCLOSURE info
			preg_match("'<link\b[^<>]+rel=[\'\"]enclosure[\'\"]?[^<>]+>'si", $rss_item, $out_enclosure);
			if (isset($out_enclosure[0]))
				{
				foreach($this->_GLOBAL_FORMATS['atom']['item_link_attributes'] as $enclosureprop)
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
				if (isset($result['items'][$kidx]['feedburner:origEnclosureLink']) && ($result['items'][$kidx]['feedburner:origEnclosureLink'] != ''))
					{
					$result['items'][$kidx]['feedburner:trackEnclosure'] = $result['items'][$kidx]['enclosure_url'];
					$result['items'][$kidx]['enclosure_url'] = $result['items'][$kidx]['feedburner:origEnclosureLink'];
					unset($result['items'][$kidx]['feedburner:origEnclosureLink']);
					}
				
				if (isset($result['items'][$kidx]['enclosure_url']) && (strpos($result['items'][$kidx]['enclosure_url'],'/0L') > 1))
					{
					$result['items'][$kidx]['feedsportal:trackEnclosure'] = $result['items'][$kidx]['enclosure_url'];
					$result['items'][$kidx]['enclosure_url'] = mYLR_DecodeFeedPortalURL($result['items'][$kidx]['enclosure_url']);
					}
				}

			// Parse Media RSS info
			preg_match("'<media:content(.*?)>'si", $rss_item, $out_enclosure);
			if (isset($out_enclosure[1]))
				{
				foreach($this->_GLOBAL_XMLNS['media']['item_media:content_attributes'] as $enclosureprop)
					{
					$temp = $this->my_preg_match("'\s$enclosureprop=[\'\"](.*?)[\'\"]'si", $out_enclosure[1]);
					if ($temp != '') $result['items'][$kidx]['media:content_'.$enclosureprop] = $temp; // Set only if not empty
					}
				}
			preg_match("'<media:thumbnail(.*?)>'si", $rss_item, $out_enclosure);
			if (isset($out_enclosure[1]))
				{
				foreach($this->_GLOBAL_XMLNS['media']['item_media:thumbnail_attributes'] as $enclosureprop)
					{
					$temp = $this->my_preg_match("'\s$enclosureprop=[\'\"](.*?)[\'\"]'si", $out_enclosure[1]);
					if ($temp != '') $result['items'][$kidx]['media:thumbnail_'.$enclosureprop] = $temp; // Set only if not empty
					}
				}
			preg_match("'<media:player(.*?)>'si", $rss_item, $out_enclosure);
			if (isset($out_enclosure[1]))
				{
				foreach($this->_GLOBAL_XMLNS['media']['item_media:player_attributes'] as $enclosureprop)
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
		
        clearstatcache(true, $filepath);
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
            clearstatcache(true, $lockpath);
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
                clearstatcache(true, $filepath);
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
	
class mYLR_Client
	{
	/* Private properties */
	
	var $_source				 = '';
	var $_transport_name		 = '';
	var $_transport_class_name	 = '';
	var $_transport				 = null;
	var $_transport_options		 = array();
	
	/* Constructor */
	
	function __construct($options = array())
		{
		return $this->mYLR_Client($options);
		}
	
	function mYLR_Client($options = array())
		{
		$this->_transport_name = '';
		$this->_transport_options['user-agent'] = '';
		$this->_transport_options['time-out'] = 0;
		
		if (is_array($options))
			{
			if (isset($options['transport']) AND ('' !== $options['transport']))
				{
				$this->_transport_name = $options['transport'];
				}
			if (isset($options['time-out']) AND (0 < $options['time-out']))
				{
				$this->_transport_options['time-out'] = $options['time-out'];
				}
			if (isset($options['user-agent']) AND ('' !== $options['user-agent']))
				{
				$this->_transport_options['user-agent'] = $options['user-agent'];
				}
			if (isset($options['temp-dir']) AND ('' !== $options['temp-dir']))
				{
				$this->_transport_options['temp-dir'] = $options['temp-dir'];
				}
			}
			
		if ($this->_transport_name === 'fopen')
			{
			$this->_transport_class_name	 = 'mYLR_Transport_FOpen';
			$this->_transport				 = new mYLR_Transport_FOpen($this->_transport_options);
			}
		else if ($this->_transport_name === 'WpRequests')
			{
            if (class_exists('WpOrg\Requests\Autoload') === false)
                {
                throw new \Exception("Transport class of " . $this->_transport_name . " not found.", 500);
                }
			$this->_transport_class_name	 = 'mYLR_Transport_WpRequests';
			$this->_transport				 = new mYLR_Transport_WpRequests($this->_transport_options);
			}
		else if ($this->_transport_name === 'Requests')
			{
            if (class_exists('\Requests') === false)
                {
                throw new \Exception("Transport class of " . $this->_transport_name . " not found.", 500);
                }
			$this->_transport_class_name	 = 'mYLR_Transport_Requests';
			$this->_transport				 = new mYLR_Transport_Requests($this->_transport_options);
			}
		else if ($this->_transport_name === 'Snoopy')
			{
            if (class_exists('\Snoopy') === false)
                {
                throw new \Exception("Transport class of " . $this->_transport_name . " not found.", 500);
                }
			$this->_transport_class_name	 = 'mYLR_Transport_Snoopy';
			$this->_transport				 = new mYLR_Transport_Snoopy($this->_transport_options);
			}
		else
			{
			// Todo: support custom transport
			$this->_transport_name			 = 'fopen';
			$this->_transport_class_name	 = 'mYLR_Transport_FOpen';
			$this->_transport				 = new mYLR_Transport_FOpen($this->_transport_options);
			}
		}
	
	/* Public methods */
	
	function getTransportName()
		{
		return $this->_transport_name;
		}
		
	function getContent($source = '')
		{
		$this->_source = $source;
		$raw_content = $this->_transport->getContent($source);
		if ($this->isTimedOut())
			{
			return '';
			}
		return $raw_content;
		}
		
	function isTimedOut()
		{
		if (in_array($this->getStatusCode(),array(408, 504, 522, 524)))
			{
			return TRUE;
			}
		return $this->_transport->isTimedOut();
		}
		
	function getStatusCode()
		{
		return intval($this->_transport->getStatusCode(),10);
		}
		
	function getLastErrorMessage()
		{
		return $this->_transport->getLastErrorMessage();
		}

	function isRedirect()
		{
		if ('' !== $this->getLastRedirect())
			{
			return TRUE;
			}
		return $this->_transport->isRedirect();
		}

	function getLastRedirect()
		{
		return $this->_transport->getLastRedirect();
		}
	}
	
class mYLR_Transport_FOpen
	{
	/* Private properties */
	
	var $_last_error_message				 = '';
	
	/* Constructor */
	
	function __construct($options = array())
		{
		$this->mYLR_Transport_FOpen($options);
		}
	
	function mYLR_Transport_FOpen($options = array())
		{
		}
	
	/* Public methods */
	
	function getContent($source = '')
		{
		$raw_content = ''; 
		if ($f = @fopen($source, 'rb'))
			{ 
            while (!feof($f))
				{ 
                $raw_content .= fgets($f, 4096); 
            	}
            fclose($f); 
			}
		else
			{
			$this->_last_error_message = 'Failed to fopen()';
			return '';
			}
		return $raw_content;
		}
		
	function isTimedOut()
		{
		return FALSE;
		}
		
	function getStatusCode()
		{
		return 0;
		}
		
	function getLastErrorMessage()
		{
		return $this->_last_error_message;
		}
	
	function isRedirect()
		{
		return FALSE;
		}
		
	function getLastRedirect()
		{
		return '';
		}
	}

class mYLR_Transport_Snoopy
	{
    // https://sourceforge.net/projects/snoopy/
        
	/* Private properties */
	
	var $_snoopy = null;
	
	/* Constructor */
	
	function __construct($options = array())
		{
		$this->mYLR_Transport_Snoopy($options);
		}
	
	function mYLR_Transport_Snoopy($options = array())
		{
		$className = '\Snoopy';
		$this->_snoopy = new $className();
		$this->_snoopy->maxframes		 = 1;
		$this->_snoopy->maxredirs		 = 4;
		$this->_snoopy->offsiteok		 = TRUE;
		$this->_snoopy->passcookies		 = TRUE;
			
		if (is_array($options))
			{
			if (isset($options['time-out']) AND (0 < $options['time-out']))
				{
				$this->_snoopy->read_timeout = $options['time-out'];
				}
			if (isset($options['user-agent']) AND ('' !== $options['user-agent']))
				{
				$this->_snoopy->agent = $options['user-agent'];
				}
			if (isset($options['temp-dir']) AND ('' !== $options['temp-dir']))
				{
				$this->_snoopy->temp_dir = $options['temp-dir'];
				}
			}
		}
	
	/* Public methods */
	
	function getContent($source = '')
		{
		$raw_content = '';
		if (@$this->_snoopy->fetch($source))
			{
			if ($this->_snoopy->timed_out === TRUE)
				{
				return '';
				}
			
			if (is_Array($this->_snoopy->results))
				{
				$raw_content = implode('', $this->_snoopy->results);
				}
			else
				{
				$raw_content = $this->_snoopy->results;
				}
			}
		return $raw_content;
		}
		
	function isTimedOut()
		{
		return $this->_snoopy->timed_out;
		}
		
	function getStatusCode()
		{
		return $this->_snoopy->response_code;
		}
		
	function getLastErrorMessage()
		{
		return $this->_snoopy->error;
		}
	
	function isRedirect()
		{
		if ('' !== $this->getLastRedirect())
			{
			return TRUE;
			}
		return FALSE;
		}
		
	function getLastRedirect()
		{
		return $this->_snoopy->lastredirectaddr;
		}
	}

class mYLR_Transport_Requests
	{
    // https://github.com/WordPress/Requests
        
	/* Private properties */
	
	var $_headers = array(
        );
	var $_options = array(
		'verify'         => false,
		'verifyname'     => false,
		);
	var $_response					 = null;
	var $_last_error_message		 = '';
	var $_is_timed_out				 = FALSE;
		
	/* Constructor */
	
	function __construct($options = array())
		{
		$this->mYLR_Transport_Requests($options);
		}
	
	function mYLR_Transport_Requests($options = array())
		{
		$className = '\Requests';
		$className::register_autoloader();
		if (is_array($options))
			{
			if (isset($options['time-out']) AND (0 < $options['time-out']))
				{
				$this->_options['timeout'] = max(6,$options['time-out']);
				$this->_options['connect_timeout'] = 5;
				}
			if (isset($options['user-agent']) AND ('' !== $options['user-agent']))
				{
				$this->_options['useragent'] = $options['user-agent'];
				}
			}
		}
	
	/* Public methods */
	
	function getContent($source = '')
		{
		$className = '\Requests';
		$raw_content = '';
		try
			{
			$this->_response = $className::get($source, $this->_headers, $this->_options);
			if ($this->_response->status_code === 200)
				{
				$raw_content = $this->_response->body;
				}
			else
				{
				return '';
				}
			}
		catch(Exception $e)
			{
			$this->_last_error_message = 'Exception('.$e->getCode().'): '.$e->getMessage();
			if (FALSE !== strpos($e->getMessage(),'timed out'))
				{
				// "cURL error 28: Operation timed out after 30000 milliseconds with 0 bytes received"
				$this->_is_timed_out				 = TRUE;
				}
			return '';
			}
		return $raw_content;
		}
		
	function isTimedOut()
		{
		return $this->_is_timed_out;
		}
		
	function getStatusCode()
		{
        if ($this->_response === null) return 0;
		return $this->_response->status_code;
		}
		
	function getLastErrorMessage()
		{
		return $this->_last_error_message;
		}
	
	function isRedirect()
		{
		if (null === $this->_response)
			{
			return FALSE;
			}
		if (0 < $this->_response->redirects)
			{
			return TRUE;
			}
		if ('' !== $this->getLastRedirect())
			{
			return TRUE;
			}
		return $this->_response->is_redirect();
		}
		
	function getLastRedirect()
		{
        if ($this->_response === null) return '';
		if (FALSE === is_array($this->_response->history))
			{
			return '';
			}
		$nb = count($this->_response->history);
		if (0 === $nb)
			{
			return '';
			}
		$lastlocation = $this->_response->history[$nb-1]->headers['location'];
		if (is_array($lastlocation))
			{
			return implode('',$lastlocation);
			}
		return $lastlocation;
		}
	}

class mYLR_Transport_WpRequests
	{
    // https://github.com/WordPress/Requests
        
	/* Private properties */
	
	var $_headers = array(
        );
	var $_options = array(
		'verify'         => false,
		'verifyname'     => false,
		);
	var $_response					 = null;
	var $_last_error_message		 = '';
	var $_is_timed_out				 = FALSE;
		
	/* Constructor */
	
	function __construct($options = array())
		{
		$this->mYLR_Transport_WpRequests($options);
		}
	
	function mYLR_Transport_WpRequests($options = array())
		{
		$className = 'WpOrg\Requests\Autoload';
		$className::register();
		if (is_array($options))
			{
			if (isset($options['time-out']) AND (0 < $options['time-out']))
				{
				$this->_options['timeout'] = max(6,$options['time-out']);
				$this->_options['connect_timeout'] = 5;
				}
			if (isset($options['user-agent']) AND ('' !== $options['user-agent']))
				{
				$this->_options['useragent'] = $options['user-agent'];
				}
			}
		}
	
	/* Public methods */
	
	function getContent($source = '')
		{
		$className = 'WpOrg\Requests\Requests';
		$raw_content = '';
		try
			{
			$this->_response = $className::get($source, $this->_headers, $this->_options);
			if ($this->_response->status_code === 200)
				{
				$raw_content = $this->_response->body;
				}
			else
				{
				return '';
				}
			}
		catch(Exception $e)
			{
			$this->_last_error_message = 'Exception('.$e->getCode().'): '.$e->getMessage();
			if (FALSE !== strpos($e->getMessage(),'timed out'))
				{
				// "cURL error 28: Operation timed out after 30000 milliseconds with 0 bytes received"
				$this->_is_timed_out				 = TRUE;
				}
			return '';
			}
		return $raw_content;
		}
		
	function isTimedOut()
		{
		return $this->_is_timed_out;
		}
		
	function getStatusCode()
		{
        if ($this->_response === null) return 0;
		return $this->_response->status_code;
		}
		
	function getLastErrorMessage()
		{
		return $this->_last_error_message;
		}
	
	function isRedirect()
		{
		if (null === $this->_response)
			{
			return FALSE;
			}
		if (0 < $this->_response->redirects)
			{
			return TRUE;
			}
		if ('' !== $this->getLastRedirect())
			{
			return TRUE;
			}
		return $this->_response->is_redirect();
		}
		
	function getLastRedirect()
		{
        if ($this->_response === null) return '';
		if (FALSE === is_array($this->_response->history))
			{
			return '';
			}
		$nb = count($this->_response->history);
		if (0 === $nb)
			{
			return '';
			}
		$lastlocation = $this->_response->history[$nb-1]->headers['location'];
		if (is_array($lastlocation))
			{
			return implode('',$lastlocation);
			}
		return $lastlocation;
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
function mYLR_ContentEncoded($string,$mode='CDATA')
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
		$string=htmlspecialchars($string);
		}
	
	// Remove duplicate entities
	$string = str_replace('&amp;#','&#',$string);
	
	//$string = str_replace('È','&'.'eacute;',$string);
	//$string = str_replace('¥','&'.'acute;',$string);
	$string = str_replace('&amp;euro;','&'.'euro;',$string);
	return $string;
	}

// Convert "Dublin Core" date format to UNIX timestamp (for GMT)
/*
Input: date like 
2006-06-02T04:45:16-0700
2006-12-09T18:24:29Z
2007-04-06T11:07:01+02:00
2026-07-09T11:12:14Z
*/
function mYLR_DCDate2UnixTimeStamp($DateTime)
	{
	$TimeStamp = 0;
	$timeStr = explode(' ',trim(str_replace(array('-','T','Z',':','+'),' ',$DateTime)),7);
    if (count($timeStr) < 6) {
        return null;
    }
	$timeDec = trim(str_replace(':','',(isset($timeStr[6]) ? $timeStr[6] : '')));
	$timeSign = substr($DateTime, 19, 1);
	$TimeStamp = gmmktime($timeStr[3],$timeStr[4],intval($timeStr[5], 10),$timeStr[1],$timeStr[2],$timeStr[0]);
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
			$TimeStamp = $TimeStamp + $timeSign * 60 * 60 * intval($timeDec, 10);
			}
		else
			{
			$TimeStamp = $TimeStamp + $timeSign * 60 * 60 * intval(substr($timeDec, 0, 2), 10) + $timeSign * 60 * intval(substr($timeDec, 2, 2), 10) ;
			}
		}
	return $TimeStamp;
	}

// Convert "RSS" (RFC 822) date format to UNIX timestamp (for GMT)
// Input: date like 
// Mon, 15 Aug 05 15:52:01 +0000
// Thu, 19 Apr 2007 10:15:40 UT
function mYLR_RSSPubDate2UnixTimeStamp($pubDate)
	{
    $isRFC822 = true;
    if (strpos($pubDate,',') !== 3)
        {
        $isRFC822 = false;
        }
    if ($isRFC822 === true)
        {
        // todo fix wrong time zone summer time while winter
        if (substr($pubDate, -3) === ' UT') {
            $pubDate = substr($pubDate, 0, -3) . ' +0000';
            }
        }
	$TimeStamp = strtotime($pubDate, date('U'));
    if ($TimeStamp === false) return null;
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

function mYLR_TrimXmlTags($content)
	{
	$offsetContent = 0;
	$newContent = '';
	$openCmt = strpos($content,'<',$offsetContent);
	while ($openCmt !== FALSE)
		{
		if ($openCmt > $offsetContent)
			{
			$newContent .= substr($content,$offsetContent,$openCmt-$offsetContent);
			}
		if (substr($content,$openCmt,9) == '<![CDATA[')
			{
			// keep CDATA as is
			$closeCmt = strpos($content,']]>',$openCmt);
			if ($closeCmt !== FALSE)
				{
				$newContent .= substr($content,$openCmt,$closeCmt+3-$openCmt);
				$offsetContent = $closeCmt+3;
				}
			else
				{
				break;
				}
			}
		else
			{
			$closeCmt = strpos($content,'>',$openCmt);
			if ($closeCmt !== FALSE)
				{
				// clean XML tag
				$newTag = substr($content,$openCmt,$closeCmt+1-$openCmt);
				$newTag = mYLR_StripCR($newTag);
				$newTag = mYLR_Trim($newTag);
				$newTag = str_replace(' >','>',$newTag);
				$newContent .= $newTag;
				$offsetContent = $closeCmt+1;
				}
			else
				{
				break;
				}
			}
		$openCmt = strpos($content,'<',$offsetContent);
		}
	return $newContent;
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
	
function mYLR_StripHTMLscript($content)
	{
	$offsetContent = 0;
	while (($openCmt = strpos($content,'<script',$offsetContent)) !== FALSE)
		{
		$closeCmt = strpos($content,'</script>',$openCmt);
		
		if ($closeCmt !== FALSE)
			{
			$content = substr($content,0,$openCmt).substr($content,$closeCmt+9);
			$offsetContent = $openCmt;
			}
		else
			{
			break;
			}
		}
	return $content;
	}
	
function mYLR_StripHTMLstyle($content)
	{
	$offsetContent = 0;
	while (($openCmt = strpos($content,'<style',$offsetContent)) !== FALSE)
		{
		$closeCmt = strpos($content,'</style>',$openCmt);
		
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
	
function mYLR_StripHTMLiframe($content)
	{
	$offsetContent = 0;
	while (($openCmt = strpos($content,'<iframe',$offsetContent)) !== FALSE)
		{
		$closeCmt = strpos($content,'</iframe>',$openCmt);
		
		if ($closeCmt !== FALSE)
			{
			$content = substr($content,0,$openCmt).substr($content,$closeCmt+9);
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
	$texte = str_replace(array('   ','  '),' ',$texte);
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
	return trim($newURL);
	}
	
function mYLR_StripAtMediumFragment($oldURL)
	{
	$newURL = $oldURL;
	if ($diesePos = strpos($newURL,'?at_medium='))
		{
		$newURL = substr($newURL,0,$diesePos);
		}
	if ($diesePos = strpos($newURL,'#at_medium='))
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
	
function mYLR_StripCacheFragment($oldURL)
	{
	$newURL = $oldURL;
	if ($diesePos = strpos($newURL,'?cache='))
		{
		$newURL = substr($newURL,0,$diesePos);
		}
	return $newURL;
	}
	
function mYLR_StripGAFragment($oldURL)
	{
	// Google Universal Analytics cookie
	$newURL = $oldURL;
	if ($diesePos = strpos($newURL,'?_ga='))
		{
		$newURL = substr($newURL,0,$diesePos);
		}
	return $newURL;
	}
	
function mYLR_StripItokFragment($oldURL)
	{
	// Drupal image token
	$newURL = $oldURL;
	if ($diesePos = strpos($newURL,'?itok='))
		{
		$newURL = substr($newURL,0,$diesePos);
		}
	else if ($diesePos = strpos($newURL,'&itok='))
		{
		$newURL = substr($newURL,0,$diesePos);
		}
	return $newURL;
	}
	
function mYLR_StripSocSrcFragment($oldURL)
	{
	// Origin unknown ; perhaps from sharing buttons ?
	$newURL = $oldURL;
	if ($diesePos = strpos($newURL,'?soc_src='))
		{
		$newURL = substr($newURL,0,$diesePos);
		}
	return $newURL;
	}
	
function mYLR_StripTwitterFragment($oldURL)
	{
	// Twitter tracking feature as #.xxxx.twitter
	$newURL = $oldURL;
	if ($diesePos = strpos($newURL,'#.'))
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
	
function mYLR_DecodeAcpmURL($oldURL)
	{
	if ($posURL = strpos(strtolower($oldURL),'cible='))
		{
		$oldURL = urldecode(substr($oldURL,$posURL+6));
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

