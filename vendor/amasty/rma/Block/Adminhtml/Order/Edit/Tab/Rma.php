<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Block\Adminhtml\Order\Edit\Tab;

use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\View\Element\Text\ListText;

class Rma extends ListText implements TabInterface
{
    /**
     * @var \Magento\Framework\AuthorizationInterface
     */
    protected $authorization;

    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Magento\Framework\AuthorizationInterface $authorization,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->authorization = $authorization;
    }

    /**
     * Return Tab label
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('RMA');
    }

    /**
     * Return Tab title
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('RMA');
    }

    /**
     * Can show tab in tabs
     *
     * @return boolean
     */
    public function canShowTab()
    {
        return $this->authorization->isAllowed('Amasty_Rma::request_view');
    }

    /**
     * Tab is hidden
     *
     * @return boolean
     */
    public function isHidden()
    {
        return false;
    }
}
