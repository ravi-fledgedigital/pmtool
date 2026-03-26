<?php

declare(strict_types=1);

namespace OnitsukaTiger\RestockReports\Ui\Component\Columns;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use OnitsukaTiger\RestockReports\Controller\Adminhtml\Restock\Download;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

class RestockReportActions extends Column
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * ExportGridActions constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $name = $this->getData('name');
                if (isset($item['download_restock'])) {
                    $item[$name]['view'] = [
                        'href' => $this->urlBuilder->getUrl(
                            Download::URL,
                            ['_query' => ['filename' => $item['download_restock']]]
                        ),
                        'label' => __('Download')
                    ];
                }
            }
        }
        return $dataSource;
    }
}
