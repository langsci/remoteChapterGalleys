<?php

/**
 * @file plugins/generic/remoteChapterGalleys/classes/ChapterOffsetForm.php
 *
 * Distributed under the GNU GPL v3. For full terms see the file docs/LICENSE.
 *
 * @class ChapterOffsetForm
 *
 * @brief Form to set the chapter number offset of a publication.
 */

namespace APP\plugins\generic\remoteChapterGalleys\classes;

use APP\facades\Repo;
use APP\plugins\generic\remoteChapterGalleys\RemoteChapterGalleysPlugin;
use APP\publication\Publication;
use PKP\form\Form;
use PKP\form\validation\FormValidatorCSRF;
use PKP\form\validation\FormValidatorPost;
use PKP\form\validation\FormValidatorRegExp;

class ChapterOffsetForm extends Form
{
    public function __construct(protected RemoteChapterGalleysPlugin $plugin, protected Publication $publication)
    {
        parent::__construct($plugin->getTemplateResource('chapterOffsetForm.tpl'));
        $this->addCheck(new FormValidatorRegExp($this, RemoteChapterGalleysPlugin::OFFSET_FIELD, 'required', 'plugins.generic.remoteChapterGalleys.offset.invalid', '/^-?\d{1,4}$/'));
        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
    }

    /**
     * @copydoc Form::initData()
     */
    public function initData()
    {
        $this->setData(RemoteChapterGalleysPlugin::OFFSET_FIELD, $this->plugin->getChapterOffset($this->publication));
        $this->setData(RemoteChapterGalleysPlugin::LABEL_FIELD, $this->publication->getData(RemoteChapterGalleysPlugin::LABEL_FIELD));
        $this->setData('submissionId', $this->publication->getData('submissionId'));
        $this->setData('publicationId', $this->publication->getId());
    }

    /**
     * @copydoc Form::readInputData()
     */
    public function readInputData()
    {
        $this->readUserVars([RemoteChapterGalleysPlugin::OFFSET_FIELD, RemoteChapterGalleysPlugin::LABEL_FIELD, 'submissionId', 'publicationId']);
    }

    /**
     * @copydoc Form::execute()
     */
    public function execute(...$functionArgs)
    {
        parent::execute(...$functionArgs);
        Repo::publication()->edit($this->publication, [
            RemoteChapterGalleysPlugin::OFFSET_FIELD => (int) $this->getData(RemoteChapterGalleysPlugin::OFFSET_FIELD),
            RemoteChapterGalleysPlugin::LABEL_FIELD => trim((string) $this->getData(RemoteChapterGalleysPlugin::LABEL_FIELD)) ?: null,
        ]);
    }
}
