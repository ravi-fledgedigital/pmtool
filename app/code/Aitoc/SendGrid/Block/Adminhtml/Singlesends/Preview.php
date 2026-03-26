<?php
/**
 * @author Aitoc Team
 * @copyright Copyright (c) 2022 Aitoc (https://www.aitoc.com)
 * @package Aitoc_SendGrid
 */


namespace Aitoc\SendGrid\Block\Adminhtml\Singlesends;

class Preview extends \Magento\Backend\Block\Widget
{
    /**
     * @var \Aitoc\SendGrid\Model\ApiWork
     */
    protected $apiWork;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Aitoc\SendGrid\Model\ApiWork $apiWork
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Aitoc\SendGrid\Model\ApiWork $apiWork,
        array $data = []
    ) {
        $this->apiWork = $apiWork;
        parent::__construct($context, $data);
    }

    /**
     * Get the Email Template content as Html from SendGrid API
     *
     * @return mixed|string
     */
    protected function _toHtml()
    {
        $singleSend = $this->getCurrentSingleSend();
        $html = '';
        if ($singleSend) {
            if (isset($singleSend['email_config']['html_content'])) {
                $html = $singleSend['email_config']['html_content'];
            }
        }

        return $html;
    }

    /**
     * Get the Current Send Grid Information through SendGrid API
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCurrentSingleSend()
    {
        return $this->apiWork->getSingleSendById($this->getRequest()->getParam('id'));
    }
}
