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
	
	// Private
	function mYLR2RSS()
		{
		parent::mYLastRSS();
		}
		
	// Public
	function Output($sources = '')
		{
		if ($sources == '')
			{
			//$sources = $this->sources;
			}
		else
			{
			$this->Get($sources);
			}
			
		if ($this->_MYLR_RESULT_ITEMS_LIMIT > 0)
			{
			$this->_ArrayPop($this->_MYLR_LAST_RESULT['items'],$this->_MYLR_RESULT_ITEMS_LIMIT);
			}
			
		if ($this->_MYLR_LAST_RESULT)
			{
			header('Content-Type: text/xml; charset="'.$this->cp.'"');
			//header("Content-Type: application/rss+xml");
			//header("Content-Type: text/xml");
			
			// Set XML header
			echo("<?xml version=\"1.0\" encoding=\"".$this->cp."\"?>\n");
			if ($this->feed_stylesheet_url) echo("<?xml-stylesheet media=\"screen\" type=\"".$this->feed_stylesheet_type."\" href=\"".$this->feed_stylesheet_url."\"?>\n");
			
			// Set RSS node
			echo("<rss\n");
			echo(" xmlns:content=\"http://purl.org/rss/1.0/modules/content/\"\n");
			echo(" xmlns:wfw=\"http://wellformedweb.org/CommentAPI/\"\n");
			echo(" xmlns:slash=\"http://purl.org/rss/1.0/modules/slash/\"\n");
			echo(" xmlns:dc=\"http://purl.org/dc/elements/1.1/\"\n");
			echo(" xmlns:dcterms=\"http://purl.org/dc/terms/\"\n");
			if ($this->enable_MediaRSS == TRUE) echo(" xmlns:media=\"http://search.yahoo.com/mrss/\"\n");
			echo(" xmlns:itunes=\"http://www.itunes.com/dtds/podcast-1.0.dtd\"\n");
			echo(" xmlns:live=\"http://schemas.microsoft.com/live/spaces/2006/rss\"\n");
			echo(" xmlns:mylr=\"http://mylastrss.sourceforge.net/\"\n");
			echo($this->feed_xmlns);
			echo(" version=\"2.0\">\n");
			
			// Channel node
			echo("<channel>\n");
			echo("<title><![CDATA[".$this->feed_title."]]></title>\n");
			echo("<link><![CDATA[".parent::unhtmlentities($this->feed_link)."]]></link>\n");
			echo("<description><![CDATA[".htmlspecialchars(parent::unhtmlentities($this->feed_description),ENT_COMPAT)."]]></description>\n");
			if ($this->feed_category != '')		echo("<category>".$this->feed_category."</category>\n");
			if ($this->feed_editor != '')		echo("<managingEditor>".$this->feed_editor."</managingEditor>\n");
			if ($this->feed_webmaster != '')	echo("<webMaster>".$this->feed_webmaster."</webMaster>\n");
			echo("<language>".$this->feed_language."</language>\n");
			//echo("<atom10:link xmlns:atom10=\"http://www.w3.org/2005/Atom\" rel=\"self\" href=\""."http://".$_SERVER['HTTP_HOST'].$_SERVER["PHP_SELF"]."\" type=\"application/rss+xml\" />\n");
			echo("<generator>mYLastRSS</generator>\n");
			if ($this->feed_copyright != '')
				{
				echo("<copyright>".$this->feed_copyright."</copyright>\n");
				}
			echo("<docs>".$this->feed_docs."</docs>\n");
			/*
			if ($this->_MYLR_LAST_RESULT['xslt4rss:shortcutIcon'])
				{
				echo("<xslt4rss:shortcutIcon>".$this->_MYLR_LAST_RESULT['xslt4rss:shortcutIcon']."</xslt4rss:shortcutIcon>\n");
				}
			if ($this->_MYLR_LAST_RESULT['xslt4rss:stylesheet'])
				{
				echo("<xslt4rss:stylesheet type=\"text/css\">".$this->_MYLR_LAST_RESULT['xslt4rss:stylesheet']."</xslt4rss:stylesheet>\n");
				}
			if ($this->_MYLR_LAST_RESULT['xslt4rss:feedsIndex'])
				{
				echo("<xslt4rss:feedsIndex type=\"rss\">".$this->_MYLR_LAST_RESULT['xslt4rss:feedsIndex']."</xslt4rss:feedsIndex>\n");
				}
			*/
			if ($this->cache_time > 0) echo("<ttl>".ceil($this->cache_time / 60)."</ttl>\n\n");
			
			// Set Channel image node
			if ($this->feed_image_url != '')
				{
				echo("<image>\n");
				echo("<url><![CDATA[".$this->feed_image_url."]]></url>\n");
				echo("<title>".$this->feed_image_title."</title>\n");
				echo("<description>".htmlspecialchars(parent::unhtmlentities($this->feed_image_description),ENT_COMPAT)."</description>\n");
				echo("<link><![CDATA[".$this->feed_image_link."]]></link>\n");
				echo("<width>".$this->feed_image_width."</width>\n");
				echo("<height>".$this->feed_image_height."</height>\n");
				echo("</image>\n\n");
				}
			
			// User add-on for channel node
			echo($this->feed_channel."\n");
			
			// Items loop
		    foreach($this->_MYLR_LAST_RESULT['items'] as $item)
				{
				// Check if enable, found, or useable enclosure for Media RSS tags
				if ($this->enable_MediaRSS == FALSE)
					{
					$isMediaContent = FALSE;
					}
				else if (($item['media:content_url'] != '') OR ($item['media:player_url'] != ''))
					{
					$isMediaContent = TRUE;
					}
				else if ($item['enclosure_type'] != '')
					{
					// Re-use enclosure if right mime-type
					if (in_array($item['enclosure_type'],$this->_MRSS_CONTENT_MIMES_TYPES))
						{
						$isMediaContent = TRUE;
						}
					else
						{
						$isMediaContent = FALSE;
						}
					}
				else
					{
					$isMediaContent = FALSE;
					}

				// Start Item node
				echo("<item>\n");
				
				// Title, guid, link
				if (strtoupper($this->cp) == 'UTF-8')
					{
					echo("<title>".mYLR_ContentEncoded(strip_tags(parent::unhtmlentities($item['title'])),NULL)."</title>\n"); 
					}
				else
					{
					echo("<title><![CDATA[".strip_tags(parent::unhtmlentities($item['title']))."]]></title>\n");
					}
				
				if ($item['guid'] != '')
					{
					if ($item['guid_isPermaLink'] === TRUE)
						{
						echo("<guid isPermaLink=\"true\"><![CDATA[".parent::unhtmlentities($item['guid'])."]]></guid>\n"); 
						}
					else if ($item['guid_isPermaLink'] === FALSE)
						{
						echo("<guid isPermaLink=\"false\"><![CDATA[".$item['guid']."]]></guid>\n"); 
						}
					else
						{
						echo("<guid><![CDATA[".$item['guid']."]]></guid>\n"); 
						}
					}
				else
					{
					echo("<guid isPermaLink=\"false\"><![CDATA[".$item['kidx']."]]></guid>\n");
					}
				echo("<link><![CDATA[".parent::unhtmlentities($item['link'])."]]></link>\n");
				
				if ($item['description'])
					{
					$description = strip_tags(str_replace(array("\r","\n",'</P>','</p>','</LI>','</li>','<BR>','<br>','<BR />','<br />','<BR/>','<br/>','</div>','</DIV>'),"\n",parent::unhtmlentities($item['description'])));
					echo("<description><![CDATA[".$description." ]]></description>\n");
					if ($item['content:encoded'] != '')
						{
						echo("<content:encoded><![CDATA[ ".mYLR_ContentEncoded(parent::unhtmlentities($item['content:encoded']),'CDATA')." ]]></content:encoded>\n");
						}
					else
						{
						echo("<content:encoded><![CDATA[ ".mYLR_ContentEncoded(parent::unhtmlentities($item['description']),'CDATA')." ]]></content:encoded>\n");
						}
					}
				else if ($item['content:encoded'])
					{
					$description = strip_tags(str_replace(array("\r","\n",'</P>','</p>','</LI>','</li>','<BR>','<br>','<BR />','<br />','<BR/>','<br/>','</div>','</DIV>'),"\n",parent::unhtmlentities($item['content:encoded'])));
					echo("<description><![CDATA[".$description." ]]></description>\n");
					echo("<content:encoded><![CDATA[ ".mYLR_ContentEncoded(parent::unhtmlentities($item['content:encoded']),'CDATA')." ]]></content:encoded>\n");
					}
				
				// Re-use media:content for enclosure if available
				if (($item['enclosure_url'] == '') AND ($item['media:content_url'] != '') AND ($item['media:content_type'] != ''))
					{
					$item['enclosure_url'] = $item['media:content_url'];
					$item['enclosure_type'] = $item['media:content_type'];
					if ($item['media:content_fileSize'] != '') $item['enclosure_length'] = $item['media:content_fileSize'];
					}
					
				// Set enclosure
				if (($item['enclosure_url'] != '') AND ($item['enclosure_type'] != ''))
					{
					echo("<enclosure");
					foreach($this->_ENCLOSURE_ATTRIBUTES as $attribute)
						{
						if ($item['enclosure_'.$attribute] != '') echo(" $attribute=\"".$item['enclosure_'.$attribute]."\"");
						}
					echo("/>\n");
					}
				
				// Media RSS
				if ($isMediaContent == TRUE)
					{
					// Content
					if ($item['media:content_url'] == '')
						{
						if ($item['enclosure_url'] != '') echo("<media:content url=\"".$item['enclosure_url']."\" type=\"".$item['enclosure_type']."\" fileSize=\"".$item['enclosure_length']."\"/>\n");
						}
					else
						{
						echo("<media:content");
						foreach($this->_MRSS_CONTENT_ATTRIBUTES as $attribute)
							{
							if ($item['media:content_'.$attribute] != '') echo(" $attribute=\"".$item['media:content_'.$attribute]."\"");
							}
						echo("/>\n");
						}
					// Thumbnail
					if ($item['media:thumbnail_url'] != '')
						{
						echo("<media:thumbnail");
						foreach($this->_MRSS_THUMBNAIL_ATTRIBUTES as $attribute)
							{
							if ($item['media:thumbnail_'.$attribute] != '') echo(" $attribute=\"".$item['media:thumbnail_'.$attribute]."\"");
							}
						echo("/>\n");
						}
					// Player
					if ($item['media:player_url'] != '')
						{
						echo("<media:player");
						foreach($this->_MRSS_THUMBNAIL_ATTRIBUTES as $attribute)
							{
							if ($item['media:player_'.$attribute] != '') echo(" $attribute=\"".$item['media:player_'.$attribute]."\"");
							}
						echo("/>\n");
						}
					
					if ($item['media:title_type'] != '')
						{
						echo("<media:title type=\"".$item['media:title_type']."\"><![CDATA[".parent::unhtmlentities($item['media:title'])."]]></media:title>\n");
						}
					else if ($item['media:title'] != '')
						{
						echo("<media:title><![CDATA[".parent::unhtmlentities($item['media:title'])."]]></media:title>\n");
						}
					if ($item['media:description_type'] != '')
						{
						echo("<media:description type=\"".$item['media:description_type']."\"><![CDATA[".parent::unhtmlentities($item['media:description'])."]]></media:description>\n");
						}
					else if ($item['media:description'] != '')
						{
						echo("<media:description><![CDATA[".parent::unhtmlentities($item['media:description'])."]]></media:description>\n");
						}
					if ($item['media:credit_role'] != '')
						{
						echo("<media:credit role=\"".$item['media:credit_role']."\"><![CDATA[".parent::unhtmlentities($item['media:credit'])."]]></media:credit>\n");
						}
					else if ($item['media:credit'] != '')
						{
						echo("<media:credit><![CDATA[".parent::unhtmlentities($item['media:credit'])."]]></media:credit>\n");
						}
					if ($item['media:copyright_url'] != '')
						{
						echo("<media:copyright url=\"".$item['media:copyright_url']."\"><![CDATA[".parent::unhtmlentities($item['media:copyright'])."]]></media:copyright>\n");
						}
					else if ($item['media:copyright'] != '')
						{
						echo("<media:copyright><![CDATA[".parent::unhtmlentities($item['media:copyright'])."]]></media:copyright>\n");
						}
					}
					
				if ($item['dc:date.Taken'] != '') echo("<dc:date.Taken>".$item['dc:date.Taken']."</dc:date.Taken>\n");
				
				// Categories
				if ($item['categories'])
					{
					foreach($item['categories'] as $category)
						{
						if (trim($category) != '') echo("<category>".$category."</category>\n");
						}
					}
				else if ($item['category'])
					{
					if (trim($category) != '') echo("<category>".$item['category']."</category>\n");
					}
				if ($item['dc:subject'])
					{
					// Not require ; mYLastRSS use this tag to build categories
					// echo("<dc:subject>".$item['dc:subject']."</dc:subject>\n");
					}
					
				if ($item['comments'])
					{
					echo("<comments>".$item['comments']."</comments>\n");
					if ($item['slash:comments'] != '')
						{
						echo("<slash:comments>".$item['slash:comments']."</slash:comments>\n");
						}
					if ($item['wfw:commentRss'])
						{
						echo("<wfw:comment>".$item['comments']."</wfw:comment>\n");
						echo("<wfw:commentRss>".$item['wfw:commentRss']."</wfw:commentRss>\n");
						}
					else if ($item['wfw:commentRSS'])
						{
						// Pas bien cette balise, on corrige
						echo("<wfw:comment>".$item['comments']."</wfw:comment>\n");
						echo("<wfw:commentRss>".$item['wfw:commentRSS']."</wfw:commentRss>\n");
						}
					}
					
				if ($item['source'] != '')
					{
					if ($item['source_url'] != '')
						{
						if (strpos($item['source_url'],'?') !== FALSE)
							{
							echo("<source><![CDATA[".parent::unhtmlentities($item['source'])."]]></source>\n"); 
							}
						else
							{
							echo("<source url=\"".parent::unhtmlentities($item['source_url'])."\"><![CDATA[".parent::unhtmlentities($item['source'])."]]></source>\n"); 
							}
						}
					else
						{
						echo("<source><![CDATA[".parent::unhtmlentities($item['source'])."]]></source>\n"); 
						}
					}
				if ($item['live:type'] != '') echo("<live:type>".$item['live:type']."</live:type>\n");
				if ($item['live:typelabel'] != '') echo("<live:typelabel>".$item['live:typelabel']."</live:typelabel>\n");
				
				if ($item['pubTimeStamp'])
					{
					// Can rewrite pubDate
					echo("<pubDate>".gmdate('D, d M Y H:i:s \G\M\T', $item['pubTimeStamp'])."</pubDate>\n");
					//if ($item['dc:date'] != '') echo("<dc:date>".$item['dc:date']."</dc:date>\n");
					}
				else
					{
					if ($item['pubDate'] != '')
						{
						echo("<pubDate>".$item['pubDate']."</pubDate>\n");
						}
					else if ($item['dc:date'] != '')
						{
						echo("<dc:date>".$item['dc:date']."</dc:date>\n");
						}
					else if ($item['dcterms:modified'] != '')
						{
						echo("<dcterms:modified>".$item['dcterms:modified']."</dcterms:modified>\n");
						}
					}
				
				
				// Add or fix author/creator
				if ($item['author'] != '')
					{
					if (strpos($item['author'],'@') > 0)
						{
						echo("<author>".$item['author']."</author>\n");
						if ($item['dc:creator'] != '')
							{
							echo("<dc:creator>".$item['dc:creator']."</dc:creator>\n");
							}
						}
					else if ($this->item_author_email != '')
						{
						if ($item['dc:creator'] != '')
							{
							echo("<author>".$this->item_author_email." (".$item['dc:creator'].")</author>\n");
							echo("<dc:creator>".$item['dc:creator']."</dc:creator>\n");
							}
						else
							{
							echo("<author>".$this->item_author_email." (".$item['author'].")</author>\n");
							echo("<dc:creator>".$item['author']."</dc:creator>\n");
							}
						}
					else
						{
						if ($item['dc:creator'] != '')
							{
							echo("<dc:creator>".$item['dc:creator']."</dc:creator>\n");
							}
						else
							{
							echo("<dc:creator>".$item['author']."</dc:creator>\n");
							}
						}
					}
				else if ($item['dc:creator'])
					{
					if ($this->item_author_email != '')
						{
						echo("<author>".$this->item_author_email." (".$item['dc:creator'].")</author>\n");
						}
					echo("<dc:creator>".$item['dc:creator']."</dc:creator>\n");
					}
				else if ($this->item_author_name != '')
					{
					if ($this->item_author_email != '')
						{
						echo("<author>".$this->item_author_email." (".$this->item_author_name.")</author>\n");
						}
					echo("<dc:creator>".$this->item_author_name."</dc:creator>\n");
					}
					
				echo("</item>\n\n");
		        } 
			
			echo("</channel>\n");
			/*
			if (count($this->_LAST_ERROR_MESSAGES) > 0)
				{
				echo("<mylr:errors>\n");
				foreach($this->_LAST_ERROR_MESSAGES as $errormsg)
					{
					echo("<mylr:error><![CDATA[ mYLastRSS: ".$errormsg." ]]></mylr:error>\n");
					}
				echo("</mylr:errors>\n");
				}
			*/
			echo("</rss>\n");
			
			return TRUE;
			}
		else
			{
			return FALSE;
			}
		}
	
	function Get($sources = '')
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
			if ($this->_MYLR_LAST_RESULT = parent::Get($sources))
				{
				$this->sources = $sources;
				}
				
			$this->items_limit = $this->_MYLR_RESULT_ITEMS_LIMIT;
			}
		
		return $this->_MYLR_LAST_RESULT;
		}
	
	function htmlampchars($string)
		{
		return mYLR_URLentities($string);
		}
		
	}
?>
