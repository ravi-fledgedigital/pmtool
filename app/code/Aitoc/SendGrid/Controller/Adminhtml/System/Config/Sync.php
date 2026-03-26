<?php
/**
 * @author Aitoc Team
 * @copyright Copyright (c) 2022 Aitoc (https://www.aitoc.com)
 * @package Aitoc_SendGrid
 */


namespace Aitoc\SendGrid\Controller\Adminhtml\System\Config;

class Sync extends \Magento\Backend\App\Action
{
    /**
     * @var \Aitoc\SendGrid\Model\SyncContacts
     */
    private $syncContacts;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Aitoc\SendGrid\Model\SyncContacts $syncContacts
    ) {
        $this->syncContacts = $syncContacts;
        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $this->syncContacts->sync();
            $this->messageManager->addSuccessMessage(
                __('Contacts is successfully synced.')
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        
        $this->_redirect( $this->_redirect->getRefererUrl());
    }
}