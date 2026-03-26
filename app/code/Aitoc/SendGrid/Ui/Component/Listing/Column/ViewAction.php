<?php
/**
 * @author Aitoc Team
 * @copyright Copyright (c) 2022 Aitoc (https://www.aitoc.com)
 * @package Aitoc_SendGrid
 */


namespace Aitoc\SendGrid\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

class ViewAction extends Column
{
    const PREVIEW_ULR_PATH = 'aitoc_sendgrid/singlesends/preview';
    const DUPLICATE_URL_PATH = 'aitoc_sendgrid/singlesends/duplicate';
    const DELETE_LOG_URL_PATH = 'aitoc_sendgrid/singlesends/delete';

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

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
                if (isset($item['id'])) {
                    $urlEntityParamName = $this->getData('config/urlEntityParamName') ?: 'id';
                    $item[$this->getData('name')] = [
                        'preview' => [
                            'href' => $this->context->getUrl(
                                self::PREVIEW_ULR_PATH,
                                [
                                    $urlEntityParamName => $item['id']
                                ]
                            ),
                            'target' => '_blank',
                            'label' => __('Preview'),
                            'popup' => true,
                        ],
                        'delete' => [
                            'href'    => $this->urlBuilder->getUrl(
                                self::DELETE_LOG_URL_PATH,
                                ['id' => $item['id']]
                            ),
                            'label'   => __('Delete'),
                            'confirm' => [
                                'title'   => __('Delete'),
                                'message' => __('Are you sure you want to delete?')
                            ]
                        ],
                        'duplicate' => [
                            'href'    => $this->urlBuilder->getUrl(
                                self::DUPLICATE_URL_PATH,
                                ['id' => $item['id']]
                            ),
                            'label'   => __('Duplicate'),
                            'confirm' => [
                                'title'   => __('Duplicate'),
                                'message' => __('Are you sure you want to duplicate?')
                            ]
                        ],
                    ];
                }
            }
        }

        return $dataSource;
    }
}
