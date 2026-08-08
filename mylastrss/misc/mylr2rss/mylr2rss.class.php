<?php
// Class which extend mYLastRSS

class mYLR2RSS extends mYLastRSS
	{
	// Public
	var $feed_stylesheet_url 		= '';
	var $feed_stylesheet_type 		= '';
	var $feed_xmlns			 		= '';
	
	var $feed_title 				= 'Unamed feed';
	var $feed_link 					= '';
	var $feed_description 			= '';
	var $feed_category 				= '';
	
	var $feed_language 				= 'en';
	var $feed_editor 				= '';
	var $feed_webmaster 			= '';
	var $feed_copyright 			= '';
	var $feed_docs 					= 'http://blogs.law.harvard.edu/tech/rss';
	
	var $feed_image_url 			= '';
	var $feed_image_title 			= '';
	var $feed_image_description 	= '';
	var $feed_image_link 			= '';
	var $feed_image_width 			= '';
	var $feed_image_height 			= '';
	
	var $feed_channel 				= '';
	
	var $item_author_name 			= '';
	var $item_author_email 			= '';
	
	// Private
	var $sources;
	var $_MYLR_LAST_RESULT;
	var $_MYLR_RESULT_ITEMS_LIMIT = 0;
	var $_ENCLOSURE_ATTRIBUTES = array('url', 'length', 'type', 'duration','height','width');
	
	// To support Media RSS
	// http://search.yahoo.com/mrss
	var $enable_MediaRSS 			= TRUE; // Not use it... Will deprecated
	var $_MRSS_ITEM_TAGS 			= array('media:title','media:description','media:text','media:credit','media:category','media:copyright','media:rating','media:keywords'); // Basics tags to support: 'media:content','media:thumbnail'
	var $_MRSS_CHANNEL_TAGS			= array('media:rating');
	var $_MRSS_CONTENT_ATTRIBUTES 	= array('url', 'type', 'height', 'width', 'duration', 'fileSize');
	var $_MRSS_CONTENT_MIMES_TYPES 	= array('image/gif','image/jpeg','image/pjpeg','image/png','audio/mpeg','video/jpeg','video/mp4','video/quicktime','video/x-flv','application/x-shockwave-flash','video/x-msvideo','video/3gpp');
	var $_MRSS_THUMBNAIL_ATTRIBUTES = array('url', 'type', 'height', 'width');
	var $_MRSS_PLAYER_ATTRIBUTES 	= array('url', 'height', 'width');
	var $_MRSS_CREDIT_ATTRIBUTES 	= array('role'); // Todo

	// Private
	function __construct()
		{
		return $this->mYLR2RSS();
		}
	
	function mYLR2RSS()
		{
		parent::mYLastRSS();
		}
		
	// Public
	function Output($sources = '', $returnOutput = false)
		{
		if ((is_string($sources) && trim($sources) !== '') || (is_array($sources) && (count($sources) > 0)))
			{
			$this->Get($sources);
			}
		if (is_array($this->_MYLR_LAST_RESULT) === false)
			{
			return ($returnOutput === false ? false : null);
			}
		if (isset($this->_MYLR_LAST_RESULT['items']) === false)
			{
			return ($returnOutput === false ? false : null);
			}
		if (is_array($this->_MYLR_LAST_RESULT['items']) === false)
			{
			return ($returnOutput === false ? false : null);
			}
		$output = '';
		$echoer = function($string) use (&$output, $returnOutput) {
			if ($returnOutput === true) {
				$output .= $string;
			} else {
				echo $string;
			}
		};
		if (($returnOutput === false) && (headers_sent() === false)) 
			{
			header('Content-Type: text/xml; charset="'.$this->cp.'"', true, 200);
			}
		if ($this->_MYLR_RESULT_ITEMS_LIMIT > 0)
			{
			$this->_ArrayPop($this->_MYLR_LAST_RESULT['items'],$this->_MYLR_RESULT_ITEMS_LIMIT);
			}
			
		// Set XML header
		$echoer("<?xml version=\"1.0\" encoding=\"".$this->cp."\"?>\n");
		if ($this->feed_stylesheet_url) $echoer("<?xml-stylesheet media=\"screen\" type=\"".$this->feed_stylesheet_type."\" href=\"".$this->feed_stylesheet_url."\"?>\n");
		
		// Set RSS node
		$echoer("<rss\n");
		$echoer(" xmlns:content=\"http://purl.org/rss/1.0/modules/content/\"\n");
		$echoer(" xmlns:wfw=\"http://wellformedweb.org/CommentAPI/\"\n");
		$echoer(" xmlns:slash=\"http://purl.org/rss/1.0/modules/slash/\"\n");
		$echoer(" xmlns:dc=\"http://purl.org/dc/elements/1.1/\"\n");
		$echoer(" xmlns:dcterms=\"http://purl.org/dc/terms/\"\n");
		if ($this->enable_MediaRSS == TRUE) $echoer(" xmlns:media=\"http://search.yahoo.com/mrss/\"\n");
		$echoer(" xmlns:itunes=\"http://www.itunes.com/dtds/podcast-1.0.dtd\"\n");
		$echoer(" xmlns:live=\"http://schemas.microsoft.com/live/spaces/2006/rss\"\n");
		$echoer(" xmlns:mylr=\"http://mylastrss.sourceforge.net/\"\n");
		$echoer($this->feed_xmlns);
		$echoer(" version=\"2.0\">\n");
		
		// Channel node
		$echoer("<channel>\n");
		$echoer("<title><![CDATA[".$this->feed_title."]]></title>\n");
		$echoer("<link><![CDATA[".parent::unhtmlentities($this->feed_link)."]]></link>\n");
		$echoer("<description><![CDATA[".htmlspecialchars(parent::unhtmlentities($this->feed_description),ENT_COMPAT)."]]></description>\n");
		if ($this->feed_category != '')		$echoer("<category>".$this->feed_category."</category>\n");
		if ($this->feed_editor != '')		$echoer("<managingEditor>".$this->feed_editor."</managingEditor>\n");
		if ($this->feed_webmaster != '')	$echoer("<webMaster>".$this->feed_webmaster."</webMaster>\n");
		$echoer("<language>".$this->feed_language."</language>\n");
		$echoer("<generator>mYLastRSS</generator>\n");
		if ($this->feed_copyright != '')
			{
			$echoer("<copyright>".$this->feed_copyright."</copyright>\n");
			}
		$echoer("<docs>".$this->feed_docs."</docs>\n");
		if ($this->cache_time > 0) $echoer("<ttl>".ceil($this->cache_time / 60)."</ttl>\n\n");
		
		// Set Channel image node
		if ($this->feed_image_url != '')
			{
			$echoer("<image>\n");
			$echoer("<url><![CDATA[".$this->feed_image_url."]]></url>\n");
			$echoer("<title>".$this->feed_image_title."</title>\n");
			$echoer("<description>".htmlspecialchars(parent::unhtmlentities($this->feed_image_description),ENT_COMPAT)."</description>\n");
			$echoer("<link><![CDATA[".$this->feed_image_link."]]></link>\n");
			$echoer("<width>".$this->feed_image_width."</width>\n");
			$echoer("<height>".$this->feed_image_height."</height>\n");
			$echoer("</image>\n\n");
			}
		
		// User add-on for channel node
		$echoer($this->feed_channel."\n");
		
		// Items loop
		foreach($this->_MYLR_LAST_RESULT['items'] as $item)
			{
			// Check if enable, found, or useable enclosure for Media RSS tags
			$isMediaContent = false;
			if ($this->enable_MediaRSS === true)
				{
				$isMediaContent = $this->isItemWithMediaRss($item);
				}

			// Start Item node
			$echoer("<item>\n");
			
			// Title, guid, link
			if (strtoupper($this->cp) == 'UTF-8')
				{
				$echoer("<title>".mYLR_ContentEncoded(strip_tags(parent::unhtmlentities($item['title'])),NULL)."</title>\n"); 
				}
			else
				{
				$echoer("<title><![CDATA[".strip_tags(parent::unhtmlentities($item['title']))."]]></title>\n");
				}
			
			if ($item['guid'] != '')
				{
				if (isset($item['guid_isPermaLink']))
					{
					if ($item['guid_isPermaLink'] === true)
						{
						$echoer("<guid isPermaLink=\"true\"><![CDATA[".parent::unhtmlentities($item['guid'])."]]></guid>\n"); 
						}
					else
						{
						$echoer("<guid isPermaLink=\"false\"><![CDATA[".$item['guid']."]]></guid>\n"); 
						}
					}
				else
					{
					$echoer("<guid><![CDATA[".$item['guid']."]]></guid>\n"); 
					}
				}
			else
				{
				$echoer("<guid isPermaLink=\"false\"><![CDATA[".$item['kidx']."]]></guid>\n");
				}
			$echoer("<link><![CDATA[".mYLR_ContentEncoded(parent::unhtmlentities($item['link']),'CDATA')."]]></link>\n");
			
			if (isset($item['description']) && ($item['description'] !== ''))
				{
				$description = strip_tags(str_replace(array("\r","\n",'</P>','</p>','</LI>','</li>','<BR>','<br>','<BR />','<br />','<BR/>','<br/>','</div>','</DIV>'),"\n",parent::unhtmlentities($item['description'])));
				$echoer("<description><![CDATA[".$description." ]]></description>\n");
				if (isset($item['content:encoded']) && ($item['content:encoded'] !== ''))
					{
					$echoer("<content:encoded><![CDATA[ ".mYLR_ContentEncoded(parent::unhtmlentities($item['content:encoded']),'CDATA')." ]]></content:encoded>\n");
					}
				else
					{
					$echoer("<content:encoded><![CDATA[ ".mYLR_ContentEncoded(parent::unhtmlentities($item['description']),'CDATA')." ]]></content:encoded>\n");
					}
				}
			else if (isset($item['content:encoded']) && ($item['content:encoded'] !== ''))
				{
				$description = strip_tags(str_replace(array("\r","\n",'</P>','</p>','</LI>','</li>','<BR>','<br>','<BR />','<br />','<BR/>','<br/>','</div>','</DIV>'),"\n",parent::unhtmlentities($item['content:encoded'])));
				$echoer("<description><![CDATA[".$description." ]]></description>\n");
				$echoer("<content:encoded><![CDATA[ ".mYLR_ContentEncoded(parent::unhtmlentities($item['content:encoded']),'CDATA')." ]]></content:encoded>\n");
				}
			
			// Re-use media:content for enclosure if available
			if ((isset($item['enclosure_url']) === false) || ($item['enclosure_url'] === ''))
				{
				if (isset($item['media:content_url']) && ($item['media:content_url'] !== '') && isset($item['media:content_type']) && ($item['media:content_type'] !== ''))
					{
					$item['enclosure_url'] = $item['media:content_url'];
					$item['enclosure_type'] = $item['media:content_type'];
					if (isset($item['media:content_fileSize']) && ($item['media:content_fileSize'] !== ''))
						{
						$item['enclosure_length'] = $item['media:content_fileSize'];
						}
					}
				}
				
			// Set enclosure
			if (isset($item['enclosure_url']) && ($item['enclosure_url'] !== '') && isset($item['enclosure_type']) && ($item['enclosure_type'] !== ''))
				{
				$echoer("<enclosure");
				foreach($this->_ENCLOSURE_ATTRIBUTES as $attribute)
					{
					if (isset($item['enclosure_'.$attribute]) && ($item['enclosure_'.$attribute] !== '')) $echoer(" $attribute=\"".$item['enclosure_'.$attribute]."\"");
					}
				$echoer("/>\n");
				}
			
			// Media RSS
			if ($isMediaContent == TRUE)
				{
				// Content
				if ((isset($item['media:content_url']) === false) || ($item['media:content_url'] === ''))
					{
					if ($item['enclosure_url'] != '') $echoer("<media:content url=\"".$item['enclosure_url']."\" type=\"".$item['enclosure_type']."\" fileSize=\"".$item['enclosure_length']."\"/>\n");
					}
				else
					{
					$echoer("<media:content");
					foreach($this->_MRSS_CONTENT_ATTRIBUTES as $attribute)
						{
						if (isset($item['media:content_'.$attribute])) $echoer(" $attribute=\"".$item['media:content_'.$attribute]."\"");
						}
					$echoer("/>\n");
					}
				// Thumbnail
				if (isset($item['media:thumbnail_url']))
					{
					$echoer("<media:thumbnail");
					foreach($this->_MRSS_THUMBNAIL_ATTRIBUTES as $attribute)
						{
						if (isset($item['media:thumbnail_'.$attribute])) $echoer(" $attribute=\"".$item['media:thumbnail_'.$attribute]."\"");
						}
					$echoer("/>\n");
					}
				// Player
				if (isset($item['media:player_url']))
					{
					$echoer("<media:player");
					foreach($this->_MRSS_THUMBNAIL_ATTRIBUTES as $attribute)
						{
						if (isset($item['media:player_'.$attribute])) $echoer(" $attribute=\"".$item['media:player_'.$attribute]."\"");
						}
					$echoer("/>\n");
					}
				
				if (isset($item['media:title_type']))
					{
					$echoer("<media:title type=\"".$item['media:title_type']."\"><![CDATA[".parent::unhtmlentities($item['media:title'])."]]></media:title>\n");
					}
				else if (isset($item['media:title']))
					{
					$echoer("<media:title><![CDATA[".parent::unhtmlentities($item['media:title'])."]]></media:title>\n");
					}
				if (isset($item['media:description_type']))
					{
					$echoer("<media:description type=\"".$item['media:description_type']."\"><![CDATA[".parent::unhtmlentities($item['media:description'])."]]></media:description>\n");
					}
				else if (isset($item['media:description']))
					{
					$echoer("<media:description><![CDATA[".parent::unhtmlentities($item['media:description'])."]]></media:description>\n");
					}
				if (isset($item['media:credit_role']))
					{
					$echoer("<media:credit role=\"".$item['media:credit_role']."\"><![CDATA[".parent::unhtmlentities($item['media:credit'])."]]></media:credit>\n");
					}
				else if (isset($item['media:credit']))
					{
					$echoer("<media:credit><![CDATA[".parent::unhtmlentities($item['media:credit'])."]]></media:credit>\n");
					}
				if (isset($item['media:copyright_url']))
					{
					$echoer("<media:copyright url=\"".$item['media:copyright_url']."\"><![CDATA[".parent::unhtmlentities($item['media:copyright'])."]]></media:copyright>\n");
					}
				else if (isset($item['media:copyright']))
					{
					$echoer("<media:copyright><![CDATA[".parent::unhtmlentities($item['media:copyright'])."]]></media:copyright>\n");
					}
				}
				
			if (isset($item['dc:date.Taken']) && ($item['dc:date.Taken'] !== '')) $echoer("<dc:date.Taken>".$item['dc:date.Taken']."</dc:date.Taken>\n");
			
			// Categories
			if (isset($item['categories']) && is_array($item['categories']))
				{
				foreach($item['categories'] as $category)
					{
					if (trim($category) != '') $echoer("<category><![CDATA[".htmlspecialchars($category)."]]></category>\n");
					}
				}
			else if (isset($item['category']))
				{
				if (trim($item['category']) != '') $echoer("<category><![CDATA[".htmlspecialchars($item['category'])."]]></category>\n");
				}
			
			if (isset($item['comments']))
				{
				$echoer("<comments>".$item['comments']."</comments>\n");
				if ($item['slash:comments'] != '')
					{
					$echoer("<slash:comments>".$item['slash:comments']."</slash:comments>\n");
					}
				if ($item['wfw:commentRss'])
					{
					$echoer("<wfw:comment>".$item['comments']."</wfw:comment>\n");
					$echoer("<wfw:commentRss>".$item['wfw:commentRss']."</wfw:commentRss>\n");
					}
				else if ($item['wfw:commentRSS'])
					{
					// Pas bien cette balise, on corrige
					$echoer("<wfw:comment>".$item['comments']."</wfw:comment>\n");
					$echoer("<wfw:commentRss>".$item['wfw:commentRSS']."</wfw:commentRss>\n");
					}
				}
				
			if ($item['source'] !== '')
				{
				if ($item['source_url'] != '')
					{
					if (strpos($item['source_url'],'?') !== FALSE)
						{
						$echoer("<source><![CDATA[".parent::unhtmlentities($item['source'])."]]></source>\n"); 
						}
					else
						{
						$echoer("<source url=\"".parent::unhtmlentities($item['source_url'])."\"><![CDATA[".parent::unhtmlentities($item['source'])."]]></source>\n"); 
						}
					}
				else
					{
					$echoer("<source><![CDATA[".parent::unhtmlentities($item['source'])."]]></source>\n"); 
					}
				}
			if (isset($item['live:type'])) $echoer("<live:type>".$item['live:type']."</live:type>\n");
			if (isset($item['live:typelabel'])) $echoer("<live:typelabel>".$item['live:typelabel']."</live:typelabel>\n");
			
			if ($item['pubTimeStamp'])
				{
				// Can rewrite pubDate
				$echoer("<pubDate>".gmdate('D, d M Y H:i:s \G\M\T', $item['pubTimeStamp'])."</pubDate>\n");
				}
			else
				{
				if ($item['pubDate'] != '')
					{
					$echoer("<pubDate>".$item['pubDate']."</pubDate>\n");
					}
				else if ($item['dc:date'] != '')
					{
					$echoer("<dc:date>".$item['dc:date']."</dc:date>\n");
					}
				else if ($item['dcterms:modified'] != '')
					{
					$echoer("<dcterms:modified>".$item['dcterms:modified']."</dcterms:modified>\n");
					}
				}
			
			// Add or fix author/creator
			if (isset($item['author']) && ($item['author'] !== ''))
				{
				if (strpos($item['author'],'@') > 0)
					{
					$echoer("<author>".$item['author']."</author>\n");
					if (isset($item['dc:creator']))
						{
						$echoer("<dc:creator>".$item['dc:creator']."</dc:creator>\n");
						}
					}
				else if ($this->item_author_email != '')
					{
					if (isset($item['dc:creator']))
						{
						$echoer("<author>".$this->item_author_email." (".$item['dc:creator'].")</author>\n");
						$echoer("<dc:creator>".$item['dc:creator']."</dc:creator>\n");
						}
					else
						{
						$echoer("<author>".$this->item_author_email." (".$item['author'].")</author>\n");
						$echoer("<dc:creator>".$item['author']."</dc:creator>\n");
						}
					}
				else
					{
					if (isset($item['dc:creator']))
						{
						$echoer("<dc:creator>".$item['dc:creator']."</dc:creator>\n");
						}
					else
						{
						$echoer("<dc:creator>".$item['author']."</dc:creator>\n");
						}
					}
				}
			else if (isset($item['dc:creator']))
				{
				if ($this->item_author_email != '')
					{
					$echoer("<author>".$this->item_author_email." (".$item['dc:creator'].")</author>\n");
					}
				$echoer("<dc:creator>".$item['dc:creator']."</dc:creator>\n");
				}
			else if ($this->item_author_name != '')
				{
				if ($this->item_author_email != '')
					{
					$echoer("<author>".$this->item_author_email." (".$this->item_author_name.")</author>\n");
					}
				$echoer("<dc:creator>".$this->item_author_name."</dc:creator>\n");
				}
				
			$echoer("</item>\n\n");
			} 
		
		$echoer("</channel>\n");
		$echoer("</rss>\n");

		if ($returnOutput === true) {
			return $output;
			}
		return true;
		}
	
	function Get($sources = '',$Reset=FALSE)
		{
		if ($sources == '')
			{
			//$sources = $this->sources;
			}
		else
			{
			// Require because output will add CDATA
			$this->CDATA = 'strip';
			
			// Require to allow RSS-Filtering
			$this->_MYLR_RESULT_ITEMS_LIMIT = $this->items_limit;
			if ($this->items_limit > 0)
				{
				$this->items_limit = 0;
				}

			$this->_MYLR_LAST_RESULT = array();
			if ($this->_MYLR_LAST_RESULT = parent::Get($sources,$Reset))
				{
				$this->sources = $sources;
				}
				
			$this->items_limit = $this->_MYLR_RESULT_ITEMS_LIMIT;
			}
		
		return $this->_MYLR_LAST_RESULT;
		}
	
	function isItemWithMediaRss($item)
		{
        if (isset($item['media:content_url']) && ($item['media:content_url'] !== ''))
            {
            return true;
            }
        if (isset($item['media:player_url']) && ($item['media:player_url'] !== ''))
            {
            return true;
            }
        if (isset($item['enclosure_type']) && ($item['enclosure_type'] !== ''))
            {
            // Re-use enclosure if right mime-type
            if (in_array($item['enclosure_type'], $this->_MRSS_CONTENT_MIMES_TYPES))
                {
                return true;
                }
            }
        return false;
		}
	
	function htmlampchars($string)
		{
		return mYLR_URLentities($string);
		}
		
	}
?>
