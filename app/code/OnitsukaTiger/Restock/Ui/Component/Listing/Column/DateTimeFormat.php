<?php

namespace OnitsukaTiger\Restock\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class DateTimeFormat extends Column
{
    /**
     * datetime construct
     *
     * @param ContextInterface $contextInterface
     * @param UiComponentFactory $componentFactory
     */
    public function __construct(
        ContextInterface $contextInterface,
        UiComponentFactory $componentFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($contextInterface, $componentFactory, $components, $data);
    }

    /**
     * prepare data source for date formate
     *
     * @return $dataSource
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $date = '';
                if ($item[$this->getData('name')] != 0) {
                    $date = new \DateTime($item[$this->getData('name')].' +00'); 
                    $date->setTimezone(new \DateTimeZone('Asia/Singapore')); 
                    $date = $date->format('Y-m-d H:i:s');
                }
                $item[$this->getData('name')] = $date;
            }
        }
        return $dataSource;
    }
}