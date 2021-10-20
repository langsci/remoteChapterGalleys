<?php

/**
 * @file plugins/generic/remoteChapterGalleys/RemoteChapterGalleysPlugin.inc.php
 *
 * Copyright (c) 2017-2021 Language Science Press
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RemoteChapterGalleys
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class RemoteChapterGalleysPlugin extends GenericPlugin {

	/**
	 * Register the plugin.
	 * @param $category string
	 * @param $path string
	 */
	/**
	 * @copydoc Plugin::register()
	 */
	function register($category, $path, $mainContextId = null) {			
		if (parent::register($category, $path, $mainContextId)) {
			if ($this->getEnabled($mainContextId)) {
				HookRegistry::register('TemplateResource::getFilename', array($this, '_overridePluginTemplates')); // override templates
				HookRegistry::register('chapterform::display', array($this, 'modifyFormTemplate')); // add variables and initialize variables, chapterForm doesn't call initData hook !!!
				HookRegistry::register('chapterform::Constructor', array($this, 'constructForm')); 
				HookRegistry::register('chapterform::readuservars', array($this, 'readInputData'));
				HookRegistry::register('chapterdao::getAdditionalFieldNames', array($this, 'getAdditionalFieldNames'));
				HookRegistry::register('chapterform::execute', array($this, 'execute'));

				HookRegistry::register('TemplateManager::display',array($this, 'handleDisplay'));
			}
			return true;
		}
		return false;
	}

	function constructForm($hookName, $form) {
        if ($hookName == 'chapterform::Constructor') {
			$form[0]->addCheck(new FormValidatorUrl($form[0], 'urlRemote', 'optional', 'user.profile.form.urlInvalid'));
        }
	}

	function modifyFormTemplate($hookName, $params) {
		if ($hookName == 'chapterform::display') {
            $request = Application::get()->getRequest();
            $templateMgr = TemplateManager::getManager($request);
			
			$chapterForm = $params[0];
			$chapter = $chapterForm->getChapter();

            if ($chapter) {
                $urlRemote = $chapter->getData('urlRemote');

                $templateMgr->assign(array(
                	'urlRemote' => $urlRemote,
                	'remoteRepresentation' => $urlRemote,
            	));
            }
        }
        return false;
	}

	/**
	 * Assign form data to user-submitted data.
	 * @see Form::readInputData()
	 */
	function readInputData($hookName, $params) {
		$params[1] = array_merge($params[1],['urlRemote','remoteRepresentation']);
	}

	/**
	 * Get a list of additional fields that do not have
	 * dedicated accessors.
	 * @return array
	 */
	function getAdditionalFieldNames($hookName, $params) {
		$params[1] = ['urlRemote','remoteRepresentation'];
		return;
	}

/**
	 * Save chapter
	 * @see Form::execute()
	 */
	function execute(...$functionParams) {
		$chapterForm = $functionParams[1][0];

		$chapterDao = DAORegistry::getDAO('ChapterDAO'); /* @var $chapterDao ChapterDAO */
		$chapter = $chapterForm->getChapter();

		if ($chapter) {
			$chapter->setData('urlRemote', $chapterForm->getData('urlRemote'));
			$chapterDao->updateObject($chapter);
		}

		return true;
	}

	function handleDisplay($hookName, $args) {
		$template =& $args[1];
		$remoteChapters = array();
		$request = $this->getRequest();
		$templateMgr = TemplateManager::getManager($request);

        switch ($template) {
            case str_contains($template, "generic-bookPage:book.tpl"):

            $publication = $templateMgr->getTemplateVars('publication');

            $chapterDao = DAORegistry::getDAO('ChapterDAO'); /* @var $chapterDao ChapterDAO */
            $chapters = $chapterDao->getByPublicationId($publication->getid());

            
            while ($chapter = $chapters->next()) {
                if ($chapter->getData('urlRemote')) {
                    $remoteChapters[] = $chapter;
                }
            }
        }
		
		$templateMgr->assign(array(
			'remoteChapters' => $remoteChapters
        ));

		return false;
	}

	/**
	 * @copydoc PKPPlugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.generic.remoteChapterGalleys.displayName');
	}

	/**
	 * @copydoc PKPPlugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.generic.remoteChapterGalleys.description');
	}	
}

?>
