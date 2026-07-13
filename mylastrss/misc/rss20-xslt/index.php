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
								<xsl:apply-templates select="image"/>
								<a href="{$link}" title="{description}" class="Title">
									<xsl:value-of select="$title"/>
								</a>
								&#160;<i>en</i>&#160;
								<?php if ($_SERVER['HTTP_REFERER'] == '') { ?>
								<xsl:if test="system-property('xsl:vendor')='Microsoft'">
								<script language="javascript" type="text/javascript"><![CDATA[
								var url=window.location;
							  	window.document.write('<a href="'+ url +'" title="Glissez-déposez ce bouton dans votre aggrégateur XML/RSS" rel="alternate" type="application/rss+xml">');
							  	window.document.write('<b>RSS</b>');
							  	window.document.write('</a>');
								]]></script>
								</xsl:if>
								<?php } else { ?>
								<a href="<?php echo($_SERVER['HTTP_REFERER']); ?>" title="Glissez-déposez ce bouton dans votre aggrégateur XML/RSS" rel="alternate" type="application/rss+xml">
									<b>RSS</b>
								</a>
								<?php } ?>
								&#160;<span class="Version"><xsl:value-of select="$version"/></span>
							</nobr>
						</div>
						<div class="FeedDescription">
							<i class="FeedDescription"><xsl:value-of select="description"/></i>
						</div>
				</div>
			</div>
			
			<fieldset>
				<legend>Qu'est-ce que cette page et le RSS ?</legend>
				<p>
				<b>L'original de cette page est un flux RSS (<i>Really Simple Syndication</i>)</b> ; une méthode simple et efficace pour accéder aux meilleures sources d'information sur le Web.
				Le RSS permet aux sites Internet et aux blogs de proposer leur contenu de manière simplifiée et instantanée.
				</p>
				<p>
				Pour être informé et/ou alerté instantanément par un fil RSS, <b>vous aurez besoin d'un logiciel ou service Web spécialisé</b> (Ex: <a target="_blank" href="http://www.sharpreader.net/">SharpReader</a> ou <a target="_blank" href="http://fr.my.yahoo.com/">Mon Yahoo!</a>) ; appelé aussi un "aggrégateur d'actualités XML/RSS".
				Puis, lorsque vous désirerez en savoir plus, vous n'aurez qu'à cliquer sur le titre pour lire l'article en intégralité. C'est la partie "Réellement Simple" du RSS.
				</p>
			</fieldset>
			
			<div class="ContentRight">
			<fieldset>
				<legend>Comment s'inscrire à ce flux RSS ?</legend>
				<p>Si vous utilisez l'un des services Web ou logiciel ci-dessous, cliquez sur le lien correspondant.</p>
				<p><u><b>Ajouter ce flux à :</b></u></p>
				<ul>
					<?php if ($_SERVER['HTTP_REFERER'] == '') { ?>
					<xsl:if test="system-property('xsl:vendor')='Microsoft'">
					<script language="javascript" type="text/javascript"><![CDATA[
						var url=window.location;
						window.document.write('<li><a href="http://www.bloglines.com/sub/'+ url +'">Bloglines</a></li>');
					  	window.document.write('<li><a href="http://my.msn.com/addtomymsn.armx?id=rss&ut='+ url +'&ru='+ url +'">My MSN</a></li>');
					  	window.document.write('<li><a href="http://fusion.google.com/add?feedurl='+ url +'">Google</a></li>');
					  	window.document.write('<li><a href="http://www.rojo.com/add-subscription?resource='+ url +'">Rojo</a></li>');
					  	window.document.write('<li><a href="http://feeds.my.aol.com/add.jsp?url='+ url +'">My AOL</a></li>');
					  	window.document.write('<li><a href="http://add.my.yahoo.com/rss?url='+ url +'">Mon Yahoo!</a></li>');
					  	window.document.write('<li><a href="http://www.newsburst.com/Source/?add='+ url +'">Newsburst</a></li>');
						window.document.write('<li><a href="http://www.newsgator.com/ngs/subscriber/subext.aspx?url='+ url +'">Newsgator</a></li>');
					]]></script>
					</xsl:if>
					<?php } else { ?>
					<li><a href="http://www.bloglines.com/sub/<?php echo($_SERVER['HTTP_REFERER']); ?>">
						Bloglines</a></li>
					<li><a href="http://fusion.google.com/add?feedurl=<?php echo($_SERVER['HTTP_REFERER']); ?>">
						Google</a></li>
					<li><a href="http://www.rojo.com/add-subscription?resource=<?php echo($_SERVER['HTTP_REFERER']); ?>">
						Rojo</a></li>
					<li><a href="http://feeds.my.aol.com/add.jsp?url=<?php echo($_SERVER['HTTP_REFERER']); ?>">
						My AOL</a></li>
					<li><a href="http://add.my.yahoo.com/rss?url=<?php echo($_SERVER['HTTP_REFERER']); ?>">
						Mon Yahoo!</a></li>
					<li><a href="http://www.newsburst.com/Source/?add=<?php echo($_SERVER['HTTP_REFERER']); ?>">
						Newsburst</a></li>
					<li><a href="http://www.newsgator.com/ngs/subscriber/subext.aspx?url=<?php echo($_SERVER['HTTP_REFERER']); ?>">
						NewsGator</a></li>
					<?php } ?>
				</ul>
				<p>Si vous utilisez un logiciel aggrégateur de flux XML, suivez l'une de ces méthodes.</p>
				<ul>
					<li>Glissez-déposez le bouton RSS orange dans votre logiciel.</li>
					<li>Glissez-déposez l'URL de cette page (depuis la barre d'Adresse de votre navigateur Web) dans votre logiciel.</li>
					<li>Copiez-collez l'URL de cette page dans la barre d'adresse de votre logiciel.</li>
					<li>Cliquez sur le bouton carré et orange de <i>Firefox</i> pour l'ajouter à vos <i>Marque-Pages</i> dynamiques.</li>
				</ul>
			</fieldset>
			</div>
			
			<div class="ContentCenter">
			<fieldset>
				<legend>Aperçu du contenu de ce flux RSS :</legend>
				<ul class="ItemsList">
				<xsl:apply-templates select="item"/>
				</ul>
			</fieldset>
			</div>
			
			<div class="Footer">
				<fieldset>
					<legend>Mentions légales &amp; copyright :</legend>
					<p><b>Pour toutes réclamations relatives au contenu de ce fil RSS</b>, vous devez contacter le responsable de <a href="{$link}"><xsl:value-of select="$title"/></a></p>
				</fieldset>
			</div>
		</body>
	</xsl:template>
	
	<xsl:template match="image">
		<a href="{link}" title="{description}">
			<img src="{url}" alt="{title}" border="0" align="absmiddle" class="ChannelImage" />
		</a>&#160;
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
