<?php

namespace OnitsukaTigerIndo\SizeConverter\Ui\Component\Listing\Columns;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class SizeActions extends Column
{

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /** Url Path */
    const PREFERENCE_URL_PATH_EDIT = 'indosize/index/edit';
    const PREFERENCE_URL_PATH_DELETE = 'indosize/index/delete';

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return void
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $name = $this->getData('name');
                if (isset($item['size_id'])) {
                    $item[$name]['edit'] = [
                        'href' => $this->urlBuilder->getUrl(self::PREFERENCE_URL_PATH_EDIT, ['size_id' => $item['size_id']]),
                        'label' => __('Edit')
                    ];
                    $item[$name]['delete'] = [
                        'href' => $this->urlBuilder->getUrl(self::PREFERENCE_URL_PATH_DELETE, ['size_id' => $item['size_id']]),
                        'label' => __('Delete'),
                        'confirm' => [
                            'message' => __('Are you sure you wan\'t to delete this record?')
                        ]
                    ];
                }
            }
        }
        return $dataSource;
    }
}
