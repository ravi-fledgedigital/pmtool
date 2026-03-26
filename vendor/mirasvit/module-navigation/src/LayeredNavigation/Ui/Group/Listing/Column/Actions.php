<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-navigation
 * @version   2.9.34
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);


namespace Mirasvit\LayeredNavigation\Ui\Group\Listing\Column;


use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Mirasvit\LayeredNavigation\Api\Data\GroupInterface;

class Actions extends Column
{
    const GROUPED_OPTION_PATH_EDIT   = 'layered_navigation/group/edit';
    const GROUPED_OPTION_PATH_DELETE = 'layered_navigation/group/delete';

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

    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $name = $this->getData('name');
                if (isset($item[GroupInterface::ID])) {
                    $item[$name] = [
                        'edit'      => [
                            'href'  => $this->urlBuilder->getUrl(self::GROUPED_OPTION_PATH_EDIT, [
                                GroupInterface::ID => $item[GroupInterface::ID],
                            ]),
                            'label' => __('Edit'),
                        ],
                        'delete'    => [
                            'href'  => $this->urlBuilder->getUrl(self::GROUPED_OPTION_PATH_DELETE, [
                                GroupInterface::ID => $item[GroupInterface::ID],
                            ]),
                            'label' => __('Delete'),
                        ]
                    ];
                }
            }
        } else {
            $dataSource['data']['items'] = [];
        }

        return $dataSource;
    }
}
