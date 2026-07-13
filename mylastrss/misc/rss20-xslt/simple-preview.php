<?php
if (isset($_REQUEST['options']))
	{
	$options = $_REQUEST['options'];
	if (strpos($options, "-withDescription") !== FALSE) {$withDescription=1;};
	if (strpos($options, "-withEnclosure") !== FALSE) {$withEnclosure=1;};
	if (strpos($options, "-withSource") !== FALSE) {$withSource=1;};
	if (strpos($options, "-withAuthor") !== FALSE) {$withAuthor=1;};
	}

header("Content-Type: text/xml");
echo("<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>\n");
?>
<!DOCTYPE xsl:stylesheet [<!ENTITY nbsp "&#160;">]>
<xsl:stylesheet version="1.1" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output method="html" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" />
	<xsl:variable name="version" select="/rss/@version"/>
	<xsl:variable name="title" select="/rss/channel/title"/>
	<xsl:variable name="link" select="/rss/channel/link"/>
	<xsl:variable name="imageurl" select="/rss/channel/image/url"/>
		
	<xsl:template match="/">
		<html>
			<head>
				<meta http-equiv="Content-Type" content="text/html; charset=windows-1252" />
				<title><xsl:value-of select="$title"/> (en RSS)</title>
				<link rel="shortcut icon" href="{$imageurl}" type="image/x-icon" />
				<?php if ($_SERVER['HTTP_REFERER'] != '') { ?>
				<link href="<?php echo($_SERVER['HTTP_REFERER']); ?>" title="{$title}" rel="alternate" type="application/rss+xml" />
				<?php } ?>
			</head>	
			<xsl:apply-templates select="rss/channel"/>
		</html>
	</xsl:template>

	<xsl:template match="channel">
		<body>
			<div class="Header">
				<div class="HeaderBackGround">
						<div class="FeedTitle">
							<nobr>
								<a href="{$link}" title="{description}" class="Title">
									<xsl:value-of select="$title"/>
								</a>
							</nobr>
						</div>
						<div class="FeedDescription">
							<i class="FeedDescription"><xsl:value-of select="description"/></i>
						</div>
				</div>
			</div>
			
			
			<div class="ContentRight">
			</div>
			
			<div class="ContentCenter">
			<fieldset>
				<ul class="ItemsList">
				<xsl:apply-templates select="item"/>
				</ul>
			</fieldset>
			</div>
		</body>
	</xsl:template>
	
	<xsl:template match="item">
		<li class="Item">
			<a class="ItemLink" href="{link}" title="{substring(pubDate,5)}"><xsl:value-of select="title"/></a>
			<?php if ($withDescription == 1) { ?>
				<div class="ItemDescription"><xsl:value-of select="description" disable-output-escaping="yes" />
					<?php if ($withEnclosure == 1) { ?>
					<ul class="EnclosuresList">
						<xsl:apply-templates select="enclosure"/>
					</ul>
					<?php } ?>
					<div>
					<?php if ($withAuthor == 1) { ?>
						<xsl:apply-templates select="author"/>
					<?php } ?>
					<?php if ($withSource == 1) { ?>
						<nobr><u>Source:</u>&nbsp;<i class="ItemSource"><xsl:value-of select="source" /></i></nobr><br/>
					<?php } ?>
					</div>
				</div>
			<?php } else { ?>
				<div class="ItemDescription">
					<?php if ($withEnclosure == 1) { ?>
					<ul class="EnclosuresList">
						<xsl:apply-templates select="enclosure"/>
					</ul>
					<?php } ?>
					<div>
					<?php if ($withAuthor == 1) { ?>
						<xsl:apply-templates select="author"/>
					<?php } ?>
					<?php if ($withSource == 1) { ?>
						<nobr><u>Source:</u>&nbsp;<i class="ItemSource"><xsl:value-of select="source" /></i></nobr><br/>
					<?php } ?>
					</div>
				</div>
			<?php } ?>
		</li>
	</xsl:template>
	
	<xsl:template match="enclosure">
		<li class="Enclosure">
			<xsl:variable name="enclosureurl" select="@url"/>
			<a href="{@url}" class="EnclosureURL"><xsl:value-of select="$enclosureurl" disable-output-escaping="yes" /></a>
			<br /><i><xsl:value-of select="@type" /></i>&#160;(<xsl:value-of select="@length" />&#160;octets)
		</li>
	</xsl:template>

	<xsl:template match="author">
		<nobr><u>Auteur:</u>&nbsp;<i class="ItemAuthor"><xsl:value-of select="../author" /></i></nobr><br/>
	</xsl:template>

</xsl:stylesheet>
