<?php

/**
 * @file plugins/generic/remoteChapterGalleys/RemoteChapterGalleysPlugin.php
 *
 * Copyright (c) 2021 Language Science Press
 * Distributed under the GNU GPL v3. For full terms see the file docs/LICENSE.
 *
 * @class RemoteChapterGalleysPlugin
 *
 * @brief Adds remote galley URLs to chapters.
 */

namespace APP\plugins\generic\remoteChapterGalleys;

use APP\plugins\generic\remoteChapterGalleys\classes\ChapterGridHandler;
use APP\publication\Publication;
use APP\template\TemplateManager;
use Illuminate\Support\Facades\DB;
use PKP\db\DAORegistry;
use PKP\form\validation\FormValidatorUrl;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;

class RemoteChapterGalleysPlugin extends GenericPlugin
{
    /** Chapter settings added by this plugin */
    public const FIELDS = ['urlRemote', 'urlRemoteLabel'];

    /** Publication setting holding the chapter number offset */
    public const OFFSET_FIELD = 'chapterNumberOffset';

    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null)
    {
        if (!parent::register($category, $path, $mainContextId)) {
            return false;
        }
        if ($this->getEnabled($mainContextId)) {
            // Only the chapter form template is overridden by this plugin
            Hook::add('TemplateResource::getFilename', [$this, 'overrideChapterFormTemplate']);
            Hook::add('chapterform::Constructor', [$this, 'constructForm']);
            Hook::add('chapterform::display', [$this, 'initFormData']);
            Hook::add('chapterform::readuservars', [$this, 'readInputData']);
            Hook::add('chapterform::execute', [$this, 'executeForm']);
            Hook::add('chapterdao::getAdditionalFieldNames', [$this, 'getAdditionalFieldNames']);
            Hook::add('Templates::Catalog::Book::Main', [$this, 'displayRemoteChapters']);
            Hook::add('Schema::get::publication', [$this, 'addPublicationSchema']);
            Hook::add('LoadComponentHandler', [$this, 'loadChapterGridHandler']);
        }
        return true;
    }

    /**
     * Add the chapter number offset to the publication schema.
     *
     * @param string $hookName Schema::get::publication
     * @param array $args [&$schema]
     */
    public function addPublicationSchema($hookName, $args)
    {
        $schema = &$args[0];
        $schema->properties->{self::OFFSET_FIELD} = (object) [
            'type' => 'integer',
            'description' => 'The number that is added to the position of a chapter to get its displayed chapter number.',
            'validation' => ['nullable'],
        ];
        return false;
    }

    /**
     * Replace the chapter grid handler with the subclass that offers the offset action.
     *
     * @param string $hookName LoadComponentHandler
     * @param array $args [&$component, &$op, &$componentInstance]
     */
    public function loadChapterGridHandler($hookName, $args)
    {
        if ($args[0] !== 'grid.users.chapter.ChapterGridHandler') {
            return false;
        }
        $args[2] = new ChapterGridHandler($this);
        return true;
    }

    /**
     * Get the chapter number offset of a publication.
     */
    public function getChapterOffset(Publication $publication): int
    {
        return (int) $publication->getData(self::OFFSET_FIELD);
    }

    /**
     * Get the chapter ids of a publication in display order.
     *
     * @return int[]
     */
    protected function getOrderedChapterIds(int $publicationId): array
    {
        $chapterDao = DAORegistry::getDAO('ChapterDAO'); /** @var \APP\monograph\ChapterDAO $chapterDao */
        $ids = [];
        foreach ($chapterDao->getByPublicationId($publicationId)->toArray() as $chapter) {
            $ids[] = $chapter->getId();
        }
        return $ids;
    }

    /**
     * Override the chapter form template, and nothing else.
     *
     * @param string $hookName TemplateResource::getFilename
     * @param array $args [&$filePath, $template]
     */
    public function overrideChapterFormTemplate($hookName, $args)
    {
        if (!str_ends_with((string) $args[1], 'chapter/form/chapterForm.tpl')) {
            return false;
        }
        return $this->_overridePluginTemplates($hookName, $args);
    }

    /**
     * Validate the remote URL.
     *
     * @param string $hookName chapterform::Constructor
     * @param array $args [$form, &$template]
     */
    public function constructForm($hookName, $args)
    {
        $form = $args[0];
        $form->addCheck(new FormValidatorUrl($form, 'urlRemote', 'optional', 'user.profile.form.urlInvalid'));
        return false;
    }

    /**
     * Load the chapter's remote settings into the form, pre-fill the label of
     * a new chapter and provide the read-only chapter number.
     *
     * @param string $hookName chapterform::display
     * @param array $args [$form, &$returner]
     */
    public function initFormData($hookName, $args)
    {
        $form = $args[0];
        $chapter = $form->getChapter();
        $publication = $form->getPublication();
        $chapterIds = $this->getOrderedChapterIds($publication->getId());

        if ($chapter) {
            foreach (self::FIELDS as $field) {
                if ($form->getData($field) === null) {
                    $form->setData($field, $chapter->getData($field));
                }
            }
            $position = array_search($chapter->getId(), $chapterIds);
            $position = $position === false ? count($chapterIds) : $position;
        } else {
            if ($form->getData('urlRemoteLabel') === null) {
                $form->setData('urlRemoteLabel', __('plugins.generic.remoteChapterGalleys.chapterLabel'));
            }
            $position = count($chapterIds);
        }
        $form->setData('remoteChapterNumber', $this->getChapterOffset($publication) + $position + 1);
        return false;
    }

    /**
     * Read the submitted values.
     *
     * @param string $hookName chapterform::readuservars
     * @param array $args [$form, &$vars]
     */
    public function readInputData($hookName, $args)
    {
        $args[1] = array_merge($args[1], self::FIELDS);
        return false;
    }

    /**
     * Declare the chapter settings so that they are loaded and stored.
     * There is no chapter schema in OMP 3.5, so this deprecated hook is still required.
     *
     * DAO hooks are fired with Hook::run, so the arguments are spread, not wrapped in an array.
     *
     * @param string $hookName chapterdao::getAdditionalFieldNames
     * @param \PKP\db\DAO $dao
     * @param array $fields
     */
    public function getAdditionalFieldNames($hookName, $dao, &$fields)
    {
        $fields = array_merge($fields, self::FIELDS);
        return false;
    }

    /**
     * Set the remote settings on the chapter. For an existing chapter, core
     * persists the same chapter object right after this hook has run. A new
     * chapter does not exist yet at this point, so its settings are written
     * once the request is finished (see saveNewChapterSettings()).
     *
     * @param string $hookName chapterform::execute
     * @param array $args [$form, ..., &$returner]
     */
    public function executeForm($hookName, $args)
    {
        $form = $args[0];
        $values = [];
        foreach (self::FIELDS as $field) {
            $value = trim((string) $form->getData($field));
            $values[$field] = $value === '' ? null : $value;
        }
        // The pre-filled label is meaningless without a URL
        if ($values['urlRemote'] === null) {
            $values['urlRemoteLabel'] = null;
        }

        $chapter = $form->getChapter();
        if ($chapter) {
            foreach ($values as $field => $value) {
                $chapter->setData($field, $value);
            }
        } else {
            register_shutdown_function([$this, 'saveNewChapterSettings'], $form, $values);
        }
        return false;
    }

    /**
     * Store the remote settings of a chapter that was created by the form.
     * Core sets the new chapter on the form at the end of ChapterForm::execute().
     *
     * @param \APP\controllers\grid\users\chapter\form\ChapterForm $form
     */
    public function saveNewChapterSettings($form, array $values): void
    {
        $chapter = $form->getChapter();
        if (!$chapter || !$chapter->getId()) {
            return;
        }
        foreach ($values as $field => $value) {
            $key = ['chapter_id' => (int) $chapter->getId(), 'locale' => '', 'setting_name' => $field];
            if ($value === null) {
                DB::table('submission_chapter_settings')->where($key)->delete();
            } else {
                DB::table('submission_chapter_settings')->updateOrInsert($key, ['setting_value' => $value, 'setting_type' => 'string']);
            }
        }
    }

    /**
     * Append the remote chapter galleys to the book page.
     *
     * @param string $hookName Templates::Catalog::Book::Main
     * @param array $args [$params, $smarty, &$output]
     */
    public function displayRemoteChapters($hookName, $args)
    {
        $templateMgr = $args[1];
        $output = &$args[2];

        $publication = $templateMgr->getTemplateVars('publication');
        $offset = $publication ? $this->getChapterOffset($publication) : 0;

        // The number counts all chapters, not only those with a remote galley
        $remoteChapters = [];
        $position = 0;
        foreach ((array) $templateMgr->getTemplateVars('chapters') as $chapter) {
            $position++;
            if (preg_match('#^https?://#i', (string) $chapter->getData('urlRemote'))) {
                $chapter->setData('remoteChapterNumber', $offset + $position);
                $remoteChapters[] = $chapter;
            }
        }
        if (!$remoteChapters) {
            return false;
        }

        $templateMgr->assign('remoteChapters', $remoteChapters);
        $output .= $templateMgr->fetch($this->getTemplateResource('remoteChapters.tpl'));
        return false;
    }

    /**
     * @copydoc PKPPlugin::getDisplayName()
     */
    public function getDisplayName()
    {
        return __('plugins.generic.remoteChapterGalleys.displayName');
    }

    /**
     * @copydoc PKPPlugin::getDescription()
     */
    public function getDescription()
    {
        return __('plugins.generic.remoteChapterGalleys.description');
    }
}
