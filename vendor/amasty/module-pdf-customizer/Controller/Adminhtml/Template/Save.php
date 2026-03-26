<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Controller\Adminhtml\Template;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

class Save extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Amasty_PDFCustom::template';

    /**
     * @var \Amasty\PDFCustom\Model\TemplateFactory
     */
    private $templateFactory;

    /**
     * @var \Magento\Backend\Model\Session
     */
    private $session;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $dateTime;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Backend\Model\Session $session,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Amasty\PDFCustom\Model\TemplateFactory $templateFactory
    ) {
        parent::__construct($context);
        $this->templateFactory = $templateFactory;
        $this->session = $session;
        $this->dateTime = $dateTime;
    }

    /**
     * Save transactional email action
     *
     * @return ResponseInterface|Redirect|ResultInterface
     */
    /**
     * @return ResponseInterface|Redirect|ResultInterface
     */
    public function execute()
    {
        $request = $this->getRequest();
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = (int)$this->getRequest()->getParam('id');
        /** @var \Amasty\PDFCustom\Model\Template $template */
        $template = $this->templateFactory->create();
        if ($id) {
            $template->load($id);
        }
        if (!$template->getId() && $id) {
            $this->messageManager->addErrorMessage(__('This PDF template no longer exists.'));

            return $resultRedirect->setPath('amasty_pdf/*/');
        }

        try {
            $template->setTemplateCode(
                $request->getParam('template_code')
            )->setTemplateText(
                $request->getParam('template_text')
            )->setTemplateStyles(
                $request->getParam('template_styles')
            )->setModifiedAt(
                $this->dateTime->gmtDate()
            )->setOrigTemplateCode(
                $request->getParam('orig_template_code')
            )->setOrigTemplateVariables(
                $request->getParam('orig_template_variables')
            )->setPlaceForUse(
                $request->getParam('place_for_use')
            )->setStoreIds(
                $this->arrayToStringConvert($request->getParam('store_ids'))
            )->setCustomerGroupIds(
                $this->arrayToStringConvert($request->getParam('customer_group_ids'))
            );

            $template->save();
            $this->session->setFormData(false);
            $this->messageManager->addSuccessMessage(__('You saved the PDF template.'));

            return $resultRedirect->setPath('amasty_pdf/*');
        } catch (\Exception $e) {
            $this->session->setData(
                'email_template_form_data',
                $request->getParams()
            );
            $this->messageManager->addErrorMessage($e->getMessage());
            $resultForward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
            $resultForward->forward('new');

            return $resultForward;
        }
    }

    /**
     * @param array $value
     * @return string
     */
    private function arrayToStringConvert($value)
    {
        if (!is_array($value) || count($value) == 0) {
            return '';
        }
        $value = implode(',', $value);

        return $value;
    }
}
