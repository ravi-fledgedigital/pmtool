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
 * @package   mirasvit/module-landing-page
 * @version   1.1.0
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\LandingPage\Ui\Page\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;
use Mirasvit\LandingPage\Api\Data\PageInterface;

class ActionColumn extends Column
{
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $item[$this->getData('name')] = [
                    'edit'   => [
                        'href'  => $this->context->getUrl('mst_landing/page/edit', [
                            PageInterface::PAGE_ID => $item[PageInterface::PAGE_ID],
                        ]),
                        'label' => __('Edit'),
                    ],
                    'delete' => [
                        'href'    => $this->context->getUrl('mst_landing/page/delete', [
                            PageInterface::PAGE_ID => $item[PageInterface::PAGE_ID],
                        ]),
                        'label'   => __('Delete'),
                        'confirm' => [
                            'title'   => __('Delete the record with ID "' . $item[PageInterface::PAGE_ID] . '"'),
                            'message' => __('Are you sure you want to delete the record with ID "' . $item[PageInterface::PAGE_ID] . '"?'),
                        ],
                    ],
                ];
            }
        }

        return $dataSource;
    }
}
