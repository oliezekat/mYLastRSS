 mYLastRSS (from lastRSS 0.9.1)
 Simple yet powerfull PHP class to parse several RSS files.
 http://sourceforge.net/projects/mylastrss/
 
 By Olivier D. alias ze kat, oliezekat@yahoo.fr
 http://life2front.com/oliezekat
 
 From original stuff named "lastRSS" of 
 Vojtech Semecky, vojtech.semecky@cmail.cz
 Latest version, features, manual and examples:
 http://lastrss.oslab.net/

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

 FEATURES
 
 mYLastRSS vs lastRSS (0.9.1) :
 - Support every tags of RSS 2.0, and <enclosure> from podcast
 - Fixed cache feature, and use cache if failed to download or parse feed
 - Allow to parse and merge several sources at same time
 - Support lot of XMLNS modules like Media RSS or Dublin Core

 HOWTO

 - See manual and examples of lastRSS on http://lastrss.oslab.net/ and use them for mYLastRSS
 - To use Cache feature, set your folder as CHMOD 777 with your FTP client
 - To do RSS-rewriting, aka to build new feed with mYLastRSS results.
   Use mylr2rss extended class (see "misc" folder)
 - To use Snoopy HTTP client, include its class before to create mYLastRSS object

