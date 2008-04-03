<?php

/**
 * @file RoleBlockPlugin.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.blocks.role
 * @class RoleBlockPlugin
 *
 * Class for role block plugin
 *
 * $Id$
 */

import('plugins.BlockPlugin');

class RoleBlockPlugin extends BlockPlugin {
	function register($category, $path) {
		$success = parent::register($category, $path);
		if ($success) {
			$this->addLocaleData();
		}
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'RoleBlockPlugin';
	}

	/**
	 * Install default settings on journal creation.
	 * @return string
	 */
	function getNewJournalPluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Get the display name of this plugin.
	 * @return String
	 */
	function getDisplayName() {
		return Locale::translate('plugins.block.role.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return Locale::translate('plugins.block.role.description');
	}

	/**
	 * Get the supported contexts (e.g. BLOCK_CONTEXT_...) for this block.
	 * @return array
	 */
	function getSupportedContexts() {
		return array(BLOCK_CONTEXT_LEFT_SIDEBAR, BLOCK_CONTEXT_RIGHT_SIDEBAR);
	}

	/**
	 * Override the block contents based on the current role being
	 * browsed.
	 * @return string
	 */
	function getBlockTemplateFilename() {
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$user =& Request::getUser();
		if (!$conference || !$schedConf || !$user) return null;

		$userId = $user->getUserId();
		$conferenceId = $conference->getConferenceId();
		$schedConfId = $schedConf->getSchedConfId();

		$templateMgr =& TemplateManager::getManager();

		switch (Request::getRequestedPage()) {
			case 'presenter': switch (Request::getRequestedOp()) {
				case 'submit':
				case 'saveSubmit':
				case 'submitSuppFile':
				case 'saveSubmitSuppFile':
				case 'deleteSubmitSuppFile':
				case 'expediteSubmission':
					// Block disabled for submission
					return null;
				default:
					$presenterSubmissionDao =& DAORegistry::getDAO('PresenterSubmissionDAO');
					$submissionsCount = $presenterSubmissionDao->getSubmissionsCount($userId, $schedConfId);
					$templateMgr->assign('submissionsCount', $submissionsCount);
					return 'presenter.tpl';
			}
			case 'director':
				if (Request::getRequestedOp() == 'index') return null;
				$directorSubmissionDao =& DAORegistry::getDAO('DirectorSubmissionDAO');
				$submissionsCount =& $directorSubmissionDao->getDirectorSubmissionsCount($schedConfId);
				$templateMgr->assign('submissionsCount', $submissionsCount);
				return 'director.tpl';
			case 'trackDirector':
				$trackDirectorSubmissionDao =& DAORegistry::getDAO('TrackDirectorSubmissionDAO');
				$submissionsCount =& $trackDirectorSubmissionDao->getTrackDirectorSubmissionsCount($userId, $schedConfId);
				$templateMgr->assign('submissionsCount', $submissionsCount);
				return 'trackDirector.tpl';
			case 'reviewer':
				$reviewerSubmissionDao =& DAORegistry::getDAO('ReviewerSubmissionDAO');
				$submissionsCount = $reviewerSubmissionDao->getSubmissionsCount($userId, $schedConfId);
				$templateMgr->assign('submissionsCount', $submissionsCount);
				return 'reviewer.tpl';
		}
		return null;
	}
}

?>
