<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Alex Kellner <alexander.kellner@einpraegsam.net>
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

/**
 * Div methods for the wt_twitter package
 */
class Tx_WtTwitter_Utility_Div {
	
	/**
	 * Get twitter array
	 *
	 * @param	array		$settings: Conf Array
	 * @return	array		$arr: Array with twitter feeds
	 */
	public static function getArray($settings) {
		// config
		$arr = array();
		
		// which file should be connected
		switch ($settings['mode']) {
			default:
			case 'showOwn': // show own feeds
				$arr = Tx_WtTwitter_Utility_Div::getArrayFromXML($settings);
				
				return $arr;
				break;
			
			case 'showFromSearch': // show feeds with searchterm
				if (!function_exists('curl_init')) { // if CURL is installed
					return 'Please enable CURL on your server'; // show error
				}
				$url = 'http://search.twitter.com/search.json?q=' . urlencode($settings['hashtag']) . '&rpp=' . intval($settings['limit']);
				$curl = curl_init();
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_URL, $url);
				$res = curl_exec($curl);
				$result = json_decode($res, 1);
				$arr = $result['results'];
				curl_close($curl);
				if (isset($arr['errors'][0]['message'])) {
					return $arr['errors'][0]['message']; // if there is a twitter error - return message
				}
				$arr = Tx_WtTwitter_Utility_Div::arrayThree2ArrayTwo($arr); // max 2 levels
				$arr = array_chunk($arr, $settings['limit']); // split on limit
				
				return $arr[0];
				break;
		}
	}
	
	/**
	 * Get Array from XML
	 *
	 * @param	array		$settings: Conf Array
	 * @return	array		$array
	 */
	public function getArrayFromXML($settings) {
		$i = 0;
		$profile_image_url = Tx_WtTwitter_Utility_Div::getProfileImageUrl($settings['account']); // get image src
		// $url = 'http://twitter.com/statuses/user_timeline/' . $settings['account'] . '.rss'; // old twitter rss feed
		$url = 'http://api.twitter.com/1/statuses/user_timeline.rss?screen_name=' . $settings['account'];
		$tmp_array = t3lib_div::xml2tree(t3lib_div::getURL($url)); // change rss to an array tree

		if (!is_array($tmp_array)) { // no array - invalid URL
			return 'No XML to this URL: ' . htmlspecialchars($url); // no valid url
		}
		// main
		$tmp_array2['main'] = $tmp_array['rss']['0']['ch']['channel']['0']['ch']; // get main part from array
		foreach ((array) $tmp_array2['main'] as $key => $value) { // one loop for every key
			if (!stristr($key, ':')) { // don't want "atom:link"
				if (trim($tmp_array2['main'][$key][0]['values'][0])) { // don't want "item"
					$array['main'][$key] = $tmp_array2['main'][$key][0]['values'][0]; // write main part - like array('main' => array('title' => 'blabla', ...))
				}
			}
		}
		
		// item
		$tmp_array3['item'] = $tmp_array['rss']['0']['ch']['channel']['0']['ch']['item']; // get whole item array
		foreach ((array) $tmp_array3['item'] as $key => $value) { // one loop for every item
			foreach ((array) $tmp_array3['item'][$key]['ch'] as $key2 => $value2) {
				$array['item'][$i]['created_at'] = $tmp_array3['item'][$key]['ch']['pubDate'][0]['values'][0];
				$array['item'][$i]['source'] = $tmp_array3['item'][$key]['ch']['twitter:source'][0]['values'][0];
				$array['item'][$i]['from_user'] = htmlspecialchars($settings['account']);
				$array['item'][$i]['profile_image_url'] = $profile_image_url;
				
				// text
				$array['item'][$i]['text'] = $tmp_array3['item'][$key]['ch']['description'][0]['values'][0];
				$array['item'][$i]['text'] = substr($array['item'][$i]['text'], strlen($settings['account'])+2); // without account name in front of tweet
				$array['item'][$i]['text'] = Tx_WtTwitter_Utility_Div::convertArrayCharset($array['item'][$i]['text'], $settings); // utf8_encode or decode
			}
			$i++; // increase counter
			if ($i == intval($settings['limit'])) {
				break; // stop loop if limit reached
			}
		}
		
		return $array['item'];
	}
	
	/**
	 * Prefunction for arraytwo2arrayone
	 *
	 * @param	string		$account: account name
	 * @return	string		Picture Source
	 */
	public function getProfileImageUrl($account) {
		$string = t3lib_div::getURL('http://twitter.com/' . $account);
		preg_match_all('/src="([^"]*)"/i', $string, $matches);
		foreach ((array) $matches[1] as $image) {
			if (stristr($image, 'normal.png')) {
				return $image;
			}
		}
	}
	
	/**
	 * Prefunction for arraytwo2arrayone
	 *
	 * @param	array		$array: Any array with values
	 * @return	array		$array
	 */
	public function arrayThree2ArrayTwo($array) {
		foreach ((array) $array as $key => $value) {
			$array[$key] = Tx_WtTwitter_Utility_Div::arrayTwo2ArrayOne($value);
		}
		return $array;
	}
	
	/**
	 * Function arraytwo2arrayone() changes array with two levels to an array with one level
	 * array('v1', array('v2')) => array('v1', 'v1_v2)
	 *
	 * @param	array		$array: Any array with values
	 * @return	array		$newarray
	 */
	public function arrayTwo2ArrayOne($array) {
		$newarray = array();
		
		if (count($array) > 0 && is_array($array)) {
			foreach ($array as $k => $v) {
				if (!is_array($v)) { // first level
					
					$newarray[$k] = $v; // no change
				
				} else { // second level
					if (count($v) > 0) {
						
						foreach ($v as $k2 => $v2) {
							if (!is_array($v2)) $newarray[$k . '_' . $k2] = $v2; // change to first level
						}
					
					}
				}
			}
		}
		
		return $newarray;
	}
	
	/**
	 * change charset of string values
	 *
	 * @param	string		$str: Any type of string text
	 * @param	array		$arr: TypoScript Configuration
	 * @return	void
	 */
	protected static function convertArrayCharset($str, $settings) {
		switch ($settings['main']['utf8']) {
			case 'utf8_encode':
				$str = utf8_encode($str);
				break;
			case 'utf8_decode':
				$str = utf8_decode($str);
				break;
		}
		return $str;
	}
	
	/**
	 * Merge TypoScript and Flexform values (Flexform has the priority)
	 *
	 * @param	array		$settings: values from flexform and typoscript
	 * @return	void
	 */
	public static function mergeTypoScript2FlexForm(&$settings) {
		$tmp_settings = array();
		
		if (isset($settings['setup']) && is_array($settings['setup'])) {
			$tmp_settings = $settings['setup']; // copy typoscript part to tmp
		}
		
		if (isset($settings['flexform']) && is_array($settings['flexform'])) {
			foreach ((array) $settings['flexform'] as $key => $value) {
				if (!empty($value)) {
					$tmp_settings[$key] = $value; // overwrite settings if not empty
				}
			}
		}
		
		$settings = $tmp_settings;
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_twitter/Classes/Utility/Div.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_twitter/Classes/Utility/Div.php']);
}
?>