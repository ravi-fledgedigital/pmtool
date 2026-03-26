<?php

namespace OnitsukaTiger\CustomStoreLocator\Block\Adminhtml\Grid;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\UrlInterface;

class SaveButton implements ButtonProviderInterface
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    public function __construct(
        Context $context
    ) {
        $this->context = $context;
        $this->urlBuilder = $context->getUrlBuilder();
    }

    public function getButtonData()
    {
        if (!$this->context->getAuthorization()->isAllowed('OnitsukaTiger_CustomStoreLocator::add_row')) {
            return [];
        }

        return [
            'label' => __('Save'),
            'class' => 'save primary',
            'on_click' => sprintf("location.href = '%s';", $this->getSaveUrl()),
            'data_attribute' => [
                'mage-init' => ['button' => ['event' => 'save']],
                'form-role' => 'save',
            ],
            'sort_order' => 10,
        ];
    }

    protected function getSaveUrl()
    {
        return $this->urlBuilder->getUrl('storelocator/grid/save'); // update controller route if needed
    }
}
