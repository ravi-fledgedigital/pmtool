<?php

namespace OnitsukaTiger\Rma\Block\Widget\Grid\Column\Renderer;

use Magento\Framework\DataObject;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\User\Model\ResourceModel\User\CollectionFactory;
use Magento\Backend\Block\Context;

class Manager extends AbstractRenderer
{
    /**
     * @var CollectionFactory
     */
    private $managerCollectionFactory;

    public function __construct(
        Context $context,
        CollectionFactory $managerCollectionFactory,
        array $data = [])
    {
        $this->managerCollectionFactory = $managerCollectionFactory;
        parent::__construct($context, $data);
    }

    /**
     * @param DataObject $row
     * @return array|mixed|string|void|null
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $value = $row->getData($this->getColumn()->getIndex());

        $result = [0 => __('Unassigned')];
        $managerCollection = $this->managerCollectionFactory->create();
        $managerCollection->addFieldToFilter('main_table.is_active', 1)//TODO is_active?
        ->addFieldToSelect(['user_id', 'username']);

        foreach ($managerCollection->getData() as $manager) {
            $result[$manager['user_id']] = $manager['username'];
        }

        return $result[$value];
    }
}
