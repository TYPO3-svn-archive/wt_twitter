<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Alexander Kellner <alexander.kellner@einpraegsam.net>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

	
require_once(PATH_tslib . 'class.tslib_pibase.php');


/**
 * Plugin 'Show Twitter Feeds' for the 'wt_twitter' extension.
 *
 * @author	Alex Kellner <alexander.kellner@einpraegsam.net>
 * @package	TYPO3
 * @subpackage	tx_wttwitter
 */
class user_wttwitteruserfuncs extends tslib_pibase {
	
	public $prefixId = 'tx_wttwitter';		// Same as class name
	public $extKey = 'wt_twitter';	// The extension key.
	
	
	/**
	 * Remove a given string from another string
	 *
	 * @var		string		$content: Empty content variable
	 * @var		array		$conr: TypoScript configuartion for this userFunc
	 * @return	string		$string: given string without another string
	 */
	function user_wttwitter_remove($content = '', $conf = array()) {
		// config
		global $TSFE;
		$local_cObj = $TSFE->cObj; // cObject
		$this->conf = $conf['userFunc.']; // ts config
		
		// let's go
		$string = $local_cObj->cObjGetSingle($this->conf['string'], $this->conf['string.']); // get string from ts
		$remove = $local_cObj->cObjGetSingle($this->conf['remove'], $this->conf['remove.']); // get remove part from ts
		
		return trim(str_replace($remove, '', $string)); // return string without another string
	}
	
	
	/**
	 * Use typolink to convert all urls, names and hashtags in a string to html links
	 *
	 * @var		string		$content: Empty content variable
	 * @var		array		$conr: TypoScript configuartion for this userFunc
	 * @return	string		$string: changed string
	 */
	function user_wttwitter_link($content = '', $conf = array()) {
		// config
		$conf = $conf['userFunc.']; // ts config
		$string = $this->cObj->data[$conf['field']]; // get date from typoscript

		// 1. rewrite URL with typolink
		$string = str_replace('http://www.', 'www.', $string); // replace all link beginnings to http://www (part 1)
        $string = str_replace('www.', 'http://www.', $string); // replace all link beginnings to http://www (part 2)
		preg_match_all('/([\w]+:\/\/[\w-?&;#~=\.\/\@]+[\w\/])/i', $string, $arr_result); // get all links of the string
		foreach ((array) $arr_result[0] as $key => $url) { // one loop for every link in the string
			if (!empty($url)) { // if there is a URL
				$typolinkconf = array('parameter' => $url); // typolink configuration
				$typolinkconf = array_merge((array) $conf['typolink.'], $typolinkconf); // get params from typoscript
				$string = str_replace($url, $this->cObj->typolink($url, $typolinkconf), $string); // replace each url with typolink
			}
		}
		
		// 2. rewrite @name with typolink to www.twitter.com/name
		preg_match_all('/(^|\s)@(\w+)/', $string, $arr_result2); // get all twitternames of the string
		foreach ((array) $arr_result2[0] as $key => $value) { // one loop for every twittername in string
			$value = trim($value); // trim it
			if (!empty($value)) { // if there is a value
				$typolinkconf = array('parameter' => 'http://www.twitter.com/' . str_replace('@', '', $value)); // typolink configuration
				$typolinkconf = array_merge((array) $conf['typolink.'], $typolinkconf); // get params from typoscript
				$string = str_replace($value, $this->cObj->typolink($value, $typolinkconf), $string); // replace each url with typolink
			}
		}
		
		// 3. rewrite #hashtag with typolink to search.twitter.com/search?q=#hashtag
		#preg_match_all('/(^|\s)#(\w+)/', $string, $arr_result3); // get all twitternames of the string
		preg_match_all('/(^|\s)#([\S]+)/', $string, $arr_result3); 
		foreach ((array) $arr_result3[0] as $key => $value) { // one loop for every twittername in string
			$value = trim($value); // trim it
			if (!empty($value)) { // if there is a value
				$typolinkconf = array('parameter' => 'http://search.twitter.com/search?q=%23' . str_replace('#', '', $value)); // typolink configuration
				$typolinkconf = array_merge((array) $conf['typolink.'], $typolinkconf); // get params from typoscript
				$string = str_replace($value, $this->cObj->typolink($value, $typolinkconf), $string); // replace each url with typolink
			}
		}
		
		return $string; // return string without another string
	}
	
	
	/**
	 * change Changes "RT" to retweet icon
	 *
	 * @var		string		$content: Empty content variable
	 * @var		array		$conr: TypoScript configuartion for this userFunc
	 * @return	string		$content: new content
	 */
	function user_wttwitter_retweetIcon($content = '', $conf = array()) {
		// config
		$conf = $conf['userFunc.']; // ts config
		
		// let's go
		$string = $this->cObj->cObjGetSingle($conf['string'], $conf['string.']); // get string from ts
		$image = $this->cObj->cObjGetSingle($conf['image'], $conf['image.']); // get image from ts
		
		return preg_replace('/RT /', $image . ' ', $string); // replace and return
	}
	
	
	/**
	 * change xml pubDate to a readable format
	 *
	 * @var		string		$content: Empty content variable
	 * @var		array		$conr: TypoScript configuartion for this userFunc
	 * @return	string		$date: timestamp from pubDate
	 */
	function user_wttwitter_date($content = '', $conf = array()) {
		$conf = $conf['userFunc.']; // ts config
		
		$date = $this->cObj->data[$conf['field']]; // get date from typoscript
		$timestamp = strtotime($date); // change to timestamp
		$date = strftime($conf['strftime'], $timestamp); // change back to readable format
		
		return $date;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_twitter/Resources/Private/UserFuncs/user_wttwitter_userfuncs.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_twitter/Resources/Private/UserFuncs/user_wttwitter_userfuncs.php']);
}
?>