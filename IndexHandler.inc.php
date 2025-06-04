<?php

/**
 * @file pages/index/IndexHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IndexHandler
 * @ingroup pages_index
 *
 * @brief Handle site index requests.
 */

import('lib.pkp.pages.index.PKPIndexHandler');

class IndexHandler extends PKPIndexHandler {

	/**
	 * If no journal is selected, display list of journals.
	 * Otherwise, display the index page for the selected journal.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function index($args, $request) {
		$this->validate(null, $request);
		$journal = $request->getJournal();

		if (!$journal) {
			$hasNoContexts = null;
			$journal = $this->getTargetContext($request, $hasNoContexts);
			if ($journal) {
				$request->redirect($journal->getPath());
			}
			if ($hasNoContexts && Validation::isSiteAdmin()) {
				$request->redirect(null, 'admin', 'contexts');
			}
		}

		$this->setupTemplate($request);
		$templateMgr = TemplateManager::getManager($request);

		if ($journal) {
			$templateMgr->assign(array(
				'additionalHomeContent' => $journal->getLocalizedData('additionalHomeContent'),
				'homepageImage' => $journal->getLocalizedData('homepageImage'),
				'homepageImageAltText' => $journal->getLocalizedData('homepageImageAltText'),
				'journalDescription' => $journal->getLocalizedData('description'),
			));

			$issueDao = DAORegistry::getDAO('IssueDAO');
			$issue = $issueDao->getCurrent($journal->getId(), true);

			if ($issue && $journal->getData('publishingMode') != PUBLISHING_MODE_NONE) {
				import('pages.issue.IssueHandler');
				IssueHandler::_setupIssueTemplate($request, $issue);
			}

			$this->_setupAnnouncements($journal, $templateMgr);
			$templateMgr->display('frontend/pages/indexJournal.tpl');

		} else {
			$journalDao = DAORegistry::getDAO('JournalDAO');
			$site = $request->getSite();

			if ($site->getRedirect() && ($journal = $journalDao->getById($site->getRedirect()))) {
				$request->redirect($journal->getPath());
			}

			$templateMgr->assign(array(
				'pageTitleTranslated' => $site->getLocalizedTitle(),
				'about' => $site->getLocalizedAbout(),
				'journalFilesPath' => $request->getBaseUrl() . '/' . Config::getVar('files', 'public_files_dir') . '/journals/',
				'journals' => $journalDao->getAll(true)->toArray(),
				'site' => $site,
			));

			$templateMgr->setCacheability(CACHEABILITY_PUBLIC);
			$templateMgr->display('frontend/pages/indexSite.tpl');
		}
	}
}
