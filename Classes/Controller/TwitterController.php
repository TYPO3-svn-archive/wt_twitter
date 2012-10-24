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
 * The twitter controller for the wt_twitter package
 *
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class Tx_WtTwitter_Controller_TwitterController extends Tx_Extbase_MVC_Controller_ActionController {
	
	/**
	 * List action for this controller. Displays a list of twitter entries.
	 *
	 * @return string The rendered view
	 */
	public function listAction() {
		$tweets = $this->div->getArray($this->settings); // get array from twitter
		
		if (!is_array($tweets)) { // got a string - this is an errormessage
			if (!empty($tweets)) { // if there is an errormsg in $tweets
				$this->flashMessages->add($tweets); // show twitter error
			} else {
				$this->flashMessages->add('Error happens while connecting to twitter'); // show undefined error
			}
		} else {
			$this->view->assign('tweets', $tweets); // array to view
		}
		
		if (count($this->settings) < 6) { // only flexform config (but no typoscript)
			$this->flashMessages->add('Please add wt_twitter Static Template in the TYPO3 Backend'); // show missing template error
		}
		
		if ($this->settings['debug'] == 1) {
			t3lib_div::debug($this->settings, 'TypoScript and Flexform settings');
			t3lib_div::debug($tweets, 'Result Array from Twitter');
		}
	}

	/**
	 * Initializes the current action
	 *
	 * @return void
	 */
	public function initializeAction() {
		$this->div = t3lib_div::makeInstance('Tx_WtTwitter_Utility_Div');
		$this->div->mergeTypoScript2FlexForm($this->settings); // merge flexform and typoscript values
		
		// set cache
		if ($GLOBALS['TSFE']->page['cache_timeout'] == 0) { // if cache_timeout was not set in the current page
			$GLOBALS['TSFE']->set_cache_timeout_default($this->cache_timeout); // cache of current page should be renewed after 50 Min
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_twitter/Classes/Controller/TwitterController.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_twitter/Classes/Controller/TwitterController.php']);
}
?>