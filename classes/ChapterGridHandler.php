<?php

/**
 * @file plugins/generic/remoteChapterGalleys/classes/ChapterGridHandler.php
 *
 * Distributed under the GNU GPL v3. For full terms see the file docs/LICENSE.
 *
 * @class ChapterGridHandler
 *
 * @brief Chapter grid with an additional action to set the chapter number offset.
 *   Loaded instead of the core handler through the LoadComponentHandler hook.
 */

namespace APP\plugins\generic\remoteChapterGalleys\classes;

use APP\controllers\grid\users\chapter\ChapterGridHandler as CoreChapterGridHandler;
use APP\plugins\generic\remoteChapterGalleys\RemoteChapterGalleysPlugin;
use PKP\core\JSONMessage;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\security\Role;

class ChapterGridHandler extends CoreChapterGridHandler
{
    public function __construct(protected RemoteChapterGalleysPlugin $plugin)
    {
        parent::__construct();
        $this->addRoleAssignment(
            [Role::ROLE_ID_AUTHOR, Role::ROLE_ID_SUB_EDITOR, Role::ROLE_ID_MANAGER, Role::ROLE_ID_ASSISTANT],
            ['editChapterOffset', 'updateChapterOffset']
        );
    }

    /**
     * @copydoc CoreChapterGridHandler::initialize()
     *
     * @param null|mixed $args
     */
    public function initialize($request, $args = null)
    {
        parent::initialize($request, $args);

        if (!$this->getReadOnly() && $this->canAdminister($request->getUser())) {
            $this->addAction(
                new LinkAction(
                    'editChapterOffset',
                    new AjaxModal(
                        $request->getRouter()->url($request, null, null, 'editChapterOffset', null, $this->getRequestArgs()),
                        __('plugins.generic.remoteChapterGalleys.offset.title')
                    ),
                    __('plugins.generic.remoteChapterGalleys.offset.title'),
                    'edit'
                )
            );
        }
    }

    /**
     * Display the chapter number offset form.
     *
     * @param array $args
     * @param \APP\core\Request $request
     */
    public function editChapterOffset($args, $request)
    {
        if (!$this->canAdminister($request->getUser())) {
            return new JSONMessage(false);
        }
        $form = new ChapterOffsetForm($this->plugin, $this->getPublication());
        $form->initData();
        return new JSONMessage(true, $form->fetch($request));
    }

    /**
     * Save the chapter number offset.
     *
     * @param array $args
     * @param \APP\core\Request $request
     */
    public function updateChapterOffset($args, $request)
    {
        if (!$this->canAdminister($request->getUser())) {
            return new JSONMessage(false);
        }
        $form = new ChapterOffsetForm($this->plugin, $this->getPublication());
        $form->readInputData();
        if (!$form->validate()) {
            return new JSONMessage(true, $form->fetch($request));
        }
        $form->execute();
        return new JSONMessage(true);
    }
}
