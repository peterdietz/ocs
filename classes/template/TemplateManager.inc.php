<?php

/**
 * @file TemplateManager.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TemplateManager
 * @ingroup template
 *
 * @brief Class for accessing the underlying template engine.
 * Currently integrated with Smarty (from http://smarty.php.net/).
 *
 */

import('classes.search.PaperSearch');
import('classes.file.PublicFileManager');
import('lib.pkp.classes.template.PKPTemplateManager');

class TemplateManager extends PKPTemplateManager {
	/**
	 * Constructor.
	 * Initialize template engine and assign basic template variables.
	 * @param $request PKPRequest FIXME: is optional for backwards compatibility only - make mandatory
	 */
	function TemplateManager($request = null) {
		parent::PKPTemplateManager($request);

		// Retrieve the router
		$router =& $this->request->getRouter();
		assert(is_a($router, 'PKPRouter'));

		if (!defined('SESSION_DISABLE_INIT')) {
			/**
			 * Kludge to make sure no code that tries to connect to
			 * the database is executed (e.g., when loading
			 * installer pages).
			 */

			$conference =& $router->getContext($this->request, 1);
			$schedConf =& $router->getContext($this->request, 2);
			$site =& $this->request->getSite();
			$this->assign('siteTitle', $site->getLocalizedTitle());

			$publicFileManager = new PublicFileManager();
			$siteFilesDir = $this->request->getBaseUrl() . '/' . $publicFileManager->getSiteFilesPath();
			$this->assign('sitePublicFilesDir', $siteFilesDir);

			$this->assign('homeContext', array('conference' => 'index', 'schedConf' => 'index'));

			$siteStyleFilename = $publicFileManager->getSiteFilesPath() . '/' . $site->getSiteStyleFilename();
			if (file_exists($siteStyleFilename)) $this->addStyleSheet($this->request->getBaseUrl() . '/' . $siteStyleFilename);

			if (isset($conference)) {
				$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
				$archivedSchedConfsExist = $schedConfDao->archivedSchedConfsExist($conference->getId());
				$currentSchedConfsExist = $schedConfDao->currentSchedConfsExist($conference->getId());
				$this->assign('archivedSchedConfsExist', $archivedSchedConfsExist);
				$this->assign('currentSchedConfsExist', $currentSchedConfsExist);

				$this->assign_by_ref('currentConference', $conference);
				$conferenceTitle = $conference->getConferenceTitle();

				$this->assign('numPageLinks', $conference->getSetting('numPageLinks'));
				$this->assign('itemsPerPage', $conference->getSetting('itemsPerPage'));

				// Load and apply theme plugin, if chosen
				$themePluginPath = $conference->getSetting('conferenceTheme');

				if (!empty($themePluginPath)) {
					// Load and activate the theme
					$themePlugin =& PluginRegistry::loadPlugin('themes', $themePluginPath);
					if ($themePlugin) $themePlugin->activate($this);
				}

				// Assign additional navigation bar items
				$navMenuItems =& $conference->getLocalizedSetting('navItems');
				$this->assign_by_ref('navMenuItems', $navMenuItems);

				$this->assign('publicFilesDir', $this->request->getBaseUrl() . '/' . $publicFileManager->getConferenceFilesPath($conference->getId()));
				$this->assign('displayPageHeaderTitle', $conference->getPageHeaderTitle());
				$this->assign('displayPageHeaderLogo', $conference->getPageHeaderLogo());
				$this->assign('displayPageHeaderTitleAltText', $conference->getLocalizedSetting('pageHeaderTitleImageAltText'));
				$this->assign('displayPageHeaderLogoAltText', $conference->getLocalizedSetting('pageHeaderLogoImageAltText'));
				$this->assign('displayFavicon', $conference->getLocalizedFavicon());
				$this->assign('faviconDir', $this->request->getBaseUrl() . '/' . $publicFileManager->getConferenceFilesPath($conference->getId()));
				$this->assign('alternatePageHeader', $conference->getLocalizedSetting('conferencePageHeader'));
				$this->assign('metaSearchDescription', $conference->getLocalizedSetting('searchDescription'));
				$this->assign('metaSearchKeywords', $conference->getLocalizedSetting('searchKeywords'));
				$this->assign('metaCustomHeaders', $conference->getLocalizedSetting('customHeaders'));
				$this->assign('enableAnnouncements', $conference->getSetting('enableAnnouncements'));

				$this->assign('pageFooter', $conference->getLocalizedSetting('conferencePageFooter'));
				$this->assign('displayCreativeCommons', $conference->getSetting('postCreativeCommons'));

				if (isset($schedConf)) {

					// This will be needed if inheriting public conference files from the scheduled conference.
					$this->assign('publicSchedConfFilesDir', $this->request->getBaseUrl() . '/' . $publicFileManager->getSchedConfFilesPath($schedConf->getId()));
					$this->assign('primaryLocale', $conference->getSetting('primaryLocale'));
					$this->assign('alternateLocales', $conference->getPrimaryLocale());

					$this->assign_by_ref('currentSchedConf', $schedConf);

					// Assign common sched conf vars:
					$currentTime = time();
					$submissionsCloseDate = $schedConf->getSetting('submissionsCloseDate');
					$this->assign('submissionsCloseDate', $submissionsCloseDate);
					$this->assign('schedConfPostTimeline', $schedConf->getSetting('postTimeline'));
					$this->assign('schedConfPostOverview', $schedConf->getSetting('postOverview'));
					$this->assign('schedConfPostTrackPolicies', $schedConf->getSetting('postTrackPolicies'));
					$this->assign('schedConfPostPresentations', $schedConf->getSetting('postPresentations'));
					$this->assign('schedConfPostAccommodation', $schedConf->getSetting('postAccommodation'));
					$this->assign('schedConfPostSupporters', $schedConf->getSetting('postSupporters'));
					$this->assign('schedConfPostPayment', $schedConf->getSetting('postPayment'));

					// CFP displayed
					$showCFPDate = $schedConf->getSetting('showCFPDate');
					$postCFP = $schedConf->getSetting('postCFP');
					if ($postCFP && $showCFPDate && $submissionsCloseDate && $currentTime > $showCFPDate && $currentTime < $submissionsCloseDate) {
						$this->assign('schedConfShowCFP', true);
					}

					// Schedule displayed
					$postScheduleDate = $schedConf->getSetting('postScheduleDate');
					if ($postScheduleDate && $currentTime > $postScheduleDate && $schedConf->getSetting('postSchedule')) {
						$this->assign('schedConfPostSchedule', true);
					}

					// Program
					if ($schedConf->getSetting('postProgram') && ($schedConf->getSetting('program') || $schedConf->getSetting('programFile'))) {
						$this->assign('schedConfShowProgram', true);
					}

					// Submissions open
					$submissionsOpenDate = $schedConf->getSetting('submissionsOpenDate');
					$postSubmission = $schedConf->getSetting('postProposalSubmission');
					$this->assign('submissionsOpenDate', $submissionsOpenDate);

					import('classes.payment.ocs.OCSPaymentManager');
					$paymentManager = new OCSPaymentManager($this->request);
					$this->assign('schedConfPaymentsEnabled', $paymentManager->isConfigured());

				}

				// Assign conference stylesheet and footer
				$conferenceStyleSheet = $conference->getSetting('conferenceStyleSheet');
				if ($conferenceStyleSheet) {
					$this->addStyleSheet($this->request->getBaseUrl() .
					'/' .	$publicFileManager->getConferenceFilesPath($conference->getId()) .
					'/' . $conferenceStyleSheet['uploadName']);
				}

				// Assign scheduled conference stylesheet and footer (after conference stylesheet!)
				if($schedConf) {
					$schedConfStyleSheet = $schedConf->getSetting('schedConfStyleSheet');
					if ($schedConfStyleSheet) {
						$this->addStyleSheet($this->request->getBaseUrl() .
						'/' .	$publicFileManager->getSchedConfFilesPath($schedConf->getId()) .
						'/' . $schedConfStyleSheet['uploadName']);
					}
				}
			} else { // Not within conference context
				// Add the site-wide logo, if set for this locale or the primary locale
				$displayPageHeaderTitle = $site->getLocalizedPageHeaderTitle();
				$this->assign('displayPageHeaderTitle', $displayPageHeaderTitle);
				if (isset($displayPageHeaderTitle['altText'])) $this->assign('displayPageHeaderTitleAltText', $displayPageHeaderTitle['altText']);
				$this->assign('publicFilesDir', $this->request->getBaseUrl() . '/' . $publicFileManager->getSiteFilesPath());

				// Load and apply theme plugin, if chosen
				$themePluginPath = $site->getSetting('siteTheme');
				if (!empty($themePluginPath)) {
					// Load and activate the theme
					$themePlugin =& PluginRegistry::loadPlugin('themes', $themePluginPath);
					if ($themePlugin) $themePlugin->activate($this);
				}
			}

			// Add java script for notifications
			$user =& $this->request->getUser();
			if ($user) $this->addJavaScript('lib/pkp/js/lib/jquery/plugins/jquery.pnotify.js');
		}
	}

	/**
	 * Smarty usage: {get_help_id key="(dir)*.page.topic" url="boolean"}
	 *
	 * Custom Smarty function for retrieving help topic ids.
	 * Direct mapping of page topic key to a numerical value representing the associated help topic xml file
	 * @params $params array associative array, must contain "key" parameter for string to translate
	 * @params $smarty Smarty
	 * @return numerical help topic id
	 */
	function smartyGetHelpId($params, &$smarty) {
		import('classes.help.Help');
		$help =& Help::getHelp();
		if (isset($params) && !empty($params)) {
			if (isset($params['key'])) {
				$key = $params['key'];
				unset($params['key']);
				$translatedKey = $help->translate($key);
			} else {
				$translatedKey = $help->translate('');
			}

			if ($params['url'] == "true") {
				return $this->request->url(null, null, 'help', 'view', explode('/', $translatedKey));
			} else {
				return $translatedKey;
			}
		}
	}

	/**
	 * Smarty usage: {help_topic key="(dir)*.page.topic" text="foo"}
	 *
	 * Custom Smarty function for creating anchor tags
	 * @params $params array associative array
	 * @params $smarty Smarty
	 * @return anchor link to related help topic
	 */
	function smartyHelpTopic($params, &$smarty) {
		import('classes.help.Help');
		$help =& Help::getHelp();
		if (isset($params) && !empty($params)) {
			$translatedKey = isset($params['key']) ? $help->translate($params['key']) : $help->translate('');
			$link = $this->request->url(null, null, 'help', 'view', explode('/', $translatedKey));
			$text = isset($params['text']) ? $params['text'] : '';
			return "<a href=\"$link\">$text</a>";
		}
	}

	/**
	 * Display page links for a listing of items that has been
	 * divided onto multiple pages.
	 * Usage:
	 * {page_links
	 * 	name="nameMustMatchGetRangeInfoCall"
	 * 	iterator=$myIterator
	 *	additional_param=myAdditionalParameterValue
	 * }
	 */
	function smartyPageLinks($params, &$smarty) {
		$iterator = $params['iterator'];
		$name = $params['name'];
		if (isset($params['anchor'])) {
			$anchor = $params['anchor'];
			unset($params['anchor']);
		} else {
			$anchor = null;
		}
		if (isset($params['all_extra'])) {
			$allExtra = ' ' . $params['all_extra'];
			unset($params['all_extra']);
		} else {
			$allExtra = '';
		}

		unset($params['iterator']);
		unset($params['name']);

		$numPageLinks = $smarty->get_template_vars('numPageLinks');
		if (!is_numeric($numPageLinks)) $numPageLinks=10;

		$page = $iterator->getPage();
		$pageCount = $iterator->getPageCount();
		$itemTotal = $iterator->getCount();

		$pageBase = max($page - floor($numPageLinks / 2), 1);
		$paramName = $name . 'Page';

		if ($pageCount<=1) return '';

		$value = '';

		if ($page>1) {
			$params[$paramName] = 1;
			$value .= '<a href="' . $this->request->url(null, null, null, null, $this->request->getRequestedArgs(), $params, $anchor, true) . '"' . $allExtra . '>&lt;&lt;</a>&nbsp;';
			$params[$paramName] = $page - 1;
			$value .= '<a href="' . $this->request->url(null, null, null, null, $this->request->getRequestedArgs(), $params, $anchor, true) . '"' . $allExtra . '>&lt;</a>&nbsp;';
		}

		for ($i=$pageBase; $i<min($pageBase+$numPageLinks, $pageCount+1); $i++) {
			if ($i == $page) {
				$value .= "<strong>$i</strong>&nbsp;";
			} else {
				$params[$paramName] = $i;
				$value .= '<a href="' . $this->request->url(null, null, null, null, $this->request->getRequestedArgs(), $params, $anchor, true) . '"' . $allExtra . '>' . $i . '</a>&nbsp;';
			}
		}
		if ($page < $pageCount) {
			$params[$paramName] = $page + 1;
			$value .= '<a href="' . $this->request->url(null, null, null, null, $this->request->getRequestedArgs(), $params, $anchor, true) . '"' . $allExtra . '>&gt;</a>&nbsp;';
			$params[$paramName] = $pageCount;
			$value .= '<a href="' . $this->request->url(null, null, null, null, $this->request->getRequestedArgs(), $params, $anchor, true) . '"' . $allExtra . '>&gt;&gt;</a>&nbsp;';
		}

		return $value;
	}
}

?>
