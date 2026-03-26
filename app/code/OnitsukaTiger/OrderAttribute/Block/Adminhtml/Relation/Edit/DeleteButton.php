<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Block\Adminhtml\Relation\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry
    ) {
        parent::__construct($context, $registry);
        $this->request = $context->getRequest();
    }

    /**
     * @return array
     */
    public function getButtonData()
    {
        $data = [];

        if ($relationId = $this->request->getParam('relation_id')) {
            $data = [
                'label' => __('Delete Relation'),
                'class' => 'delete',
                'on_click' => 'deleteConfirm("' . __('Are you sure you want to do this?') . '", "'
                    . $this->urlBuilder->getUrl('*/*/delete', ['relation_id' => $relationId]) . '")',
                'sort_order' => 20,
            ];
        }

        return $data;
    }
}
