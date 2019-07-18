<?php

use ILIAS\Filesystem\Filesystems;
use ILIAS\FileUpload\FileUpload;
use ILIAS\HTTP\GlobalHttpState;
use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;


/**
 * @author
 * @ilCtrl_isCalledBy ilWebDAVMountInstructionsUploadGUI:  ilObjFileAccessSettingsGui
 */
class ilWebDAVMountInstructionsUploadGUI {

    const ACTION_SAVE_ADD_DOCUMENT_FORM = 'saveAddDocumentForm';
    const ACTION_SAVE_EDIT_DOCUMENT_FORM = 'saveEditDocumentForm';

    public function __construct(
		ilObjFileAccessSettings $file_access_settings,
		ilGlobalPageTemplate $tpl,
		ilObjUser $user,
		ilCtrl $ctrl,
		ilLanguage $lng,
		ilRbacSystem $rbacsystem,
		ilErrorHandling $error,
		ilLogger $log,
		ilToolbarGUI $toolbar,
		GlobalHttpState $http_state,
		Factory $ui_factory,
		Renderer $ui_renderer,
		Filesystems $file_systems,
		FileUpload $file_upload,
		ilWebDAVMountInstructionsRepository $mount_instructions_repository
	) {
		$this->file_access_settings          = $file_access_settings;
		$this->tpl                           = $tpl;
		$this->ctrl                          = $ctrl;
		$this->lng                           = $lng;
		$this->rbacsystem                    = $rbacsystem;
		$this->error                         = $error;
		$this->user                          = $user;
		$this->log                           = $log;
		$this->toolbar                       = $toolbar;
		$this->http_state                    = $http_state;
		$this->ui_factory                    = $ui_factory;
		$this->ui_renderer                   = $ui_renderer;
		$this->file_systems                  = $file_systems;
		$this->file_upload                   = $file_upload;
		$this->mount_instructions_repository = $mount_instructions_repository;
		$this->document_purifier = NULL;
	}

	/**
	 *
	 */
	public function executeCommand() : void
	{
		$cmd = $this->ctrl->getCmd();

		if (!$this->rbacsystem->checkAccess('read', $this->file_access_settings->getRefId())) {
			$this->error->raiseError($this->lng->txt('permission_denied'), $this->error->MESSAGE);
		}

		if ($cmd == '' || !method_exists($this, $cmd)) {
			$cmd = 'showDocuments';
		}
		$this->$cmd();
	}

    /**
     * @throws ilTemplateException
     */
	protected function showDocuments() : void
	{
		if ($this->rbacsystem->checkAccess('write', $this->file_access_settings->getRefId()))
		{
			$addDocumentBtn = ilLinkButton::getInstance();
			$addDocumentBtn->setPrimary(true);
			$addDocumentBtn->setUrl($this->ctrl->getLinkTarget($this, 'showAddDocumentForm'));
			$addDocumentBtn->setCaption('webdav_add_instructions_btn_label');
			$this->toolbar->addStickyItem($addDocumentBtn);
		}

		$document_tbl_gui = new ilWebDAVMountInstructionsDocumentTableGUI(  // new ilTermsOfServiceDocumentTableGUI(
			$this,
			'showDocuments',
			$this->ui_factory,
			$this->ui_renderer,
			$this->rbacsystem->checkAccess('write', $this->file_access_settings->getRefId())
		);
		$document_tbl_gui->setProvider(new ilWebDAVMountInstructionsTableDataProvider($this->mount_instructions_repository));
		$document_tbl_gui->populate();

		$this->tpl->setCurrentBlock('mess');
		$this->tpl->setVariable('MESSAGE', 'Message');
		//$this->tpl->setVariable('MESSAGE', $this->getResetMessageBoxHtml());
		$this->tpl->parseCurrentBlock('mess');
		$this->tpl->setContent($document_tbl_gui->getHTML());
	}

    /**
     * @param ilWebDAVMountInstructionsDocument $a_document
     * @return ilWebDAVMountInstructionsDocumentFormGUI
     */
	protected function getDocumentForm(ilWebDAVMountInstructionsDocument $a_document)
    {
        if($a_document->getId() > 0)
        {
            $this->ctrl->setParameter($this, 'doc_id', $a_document->getId());

            $form_action  = $this->ctrl->getFormAction($this, self::ACTION_SAVE_EDIT_DOCUMENT_FORM);
            $save_command = self::ACTION_SAVE_EDIT_DOCUMENT_FORM;
        }
        else
        {
            $form_action = $this->ctrl->getFormAction($this, self::ACTION_SAVE_ADD_DOCUMENT_FORM);
            $save_command = self::ACTION_SAVE_ADD_DOCUMENT_FORM;
        }

        $form = new ilWebDAVMountInstructionsDocumentFormGUI(
            $a_document,
            $this->mount_instructions_repository,
            $this->document_purifier,
            $this->user,
            $this->file_systems->temp(),
            $this->file_upload,
            $form_action,
            $save_command,
            'showDocuments',
            $this->rbacsystem->checkAccess('write', $this->file_access_settings->getRefId())
        );

        return $form;
    }

	protected function showAddDocumentForm()
	{
        if (!$this->rbacsystem->checkAccess('write', $this->file_access_settings->getRefId())) {
            $this->error->raiseError($this->lng->txt('permission_denied'), $this->error->MESSAGE);
        }

        $form = $this->getDocumentForm(new ilWebDAVMountInstructionsDocument());
        $this->tpl->setContent($form->getHTML());
	}

	protected function showEditDocumentForm()
    {
        if (!$this->rbacsystem->checkAccess('write', $this->file_access_settings->getRefId())) {
            $this->error->raiseError($this->lng->txt('permission_denied'), $this->error->MESSAGE);
        }
    }

    /**
     *
     */
	protected function saveAddDocumentForm()
    {
        if (!$this->rbacsystem->checkAccess('write', $this->file_access_settings->getRefId())) {
            $this->error->raiseError($this->lng->txt('permission_denied'), $this->error->MESSAGE);
        }

        $form = $this->getDocumentForm(new ilWebDAVMountInstructionsDocument());
        if($form->saveObject())
        {
            ilUtil::sendSuccess($this->lng->txt('saved_successfully'), true);
            if($form->hasTranslatedInfo())
            {
                ilUtil::sendInfo($form->getTranslatedInfo(), true);
            }
            $this->ctrl->redirect($this, 'showDocuments');
        }
        else if ($form->hasTranslatedError())
        {
            ilUtil::sendFailure($form->getTranslatedError());
        }

        $this->tpl->setContent($form->getHTML());
    }

    /**
     *
     */
    protected function saveEditDocumentForm()
    {
        if (!$this->rbacsystem->checkAccess('write', $this->file_access_settings->getRefId())) {
            $this->error->raiseError($this->lng->txt('permission_denied'), $this->error->MESSAGE);
        }

        $document = $this->getFirstDocumentFromList($this->getDocumentsByServerRequest());

    }

    protected function getDocumentsByServerRequest()
    {
        $documents = [];

        $documentsIds = $this->httpState->request()-getParsedBody()['instructions_id'] ?? [];
    }
}