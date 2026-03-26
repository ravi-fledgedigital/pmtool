<?php

namespace OnitsukaTiger\ImportExport\Plugin\Result;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\View\Element\Context;

class Page
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var AuthorizationInterface
     */
    protected $_authorization;

    /**
     * @param Context $context
     * @param AuthorizationInterface $authorization
     */
    public function __construct(
        Context $context,
        AuthorizationInterface $authorization
    ) {
        $this->context = $context;
        $this->_authorization = $authorization;
    }

    /**
     * @param \Magento\Framework\View\Result\Page $subject
     * @param ResponseInterface $response
     * @return ResponseInterface[]
     */
    public function beforeRenderResult(
        \Magento\Framework\View\Result\Page $subject,
        ResponseInterface $response
    ){
        if($this->context->getRequest()->getFullActionName() == 'import_export_job_edit' && !$this->isEdit()){
            $subject->getConfig()->addBodyClass('readonly-export');
        }

        return [$response];
    }

    /**
     * @return bool
     */
    public function isEdit () {
        return $this->_authorization->isAllowed('OnitsukaTiger_ImportExport::action_edit');
    }
}