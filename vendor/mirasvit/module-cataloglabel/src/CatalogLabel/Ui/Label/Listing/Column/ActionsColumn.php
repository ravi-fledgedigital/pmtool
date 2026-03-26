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
 * @package   mirasvit/module-cataloglabel
 * @version   2.5.7
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);


namespace Mirasvit\CatalogLabel\Ui\Label\Listing\Column;


use Magento\Ui\Component\Listing\Columns\Column;


class ActionsColumn extends Column
{
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $item[$this->getData('name')] = [
                    'edit'      => [
                        'href'  => $this->context->getUrl('cataloglabel/label/edit', [
                            'id' => $item['label_id'],
                        ]),
                        'label' => (string)__('Edit'),
                    ],
                    'delete'    => [
                        'href'    => $this->context->getUrl('cataloglabel/label/delete', [
                            'id' => $item['label_id'],
                        ]),
                        'label'   => (string)__('Delete'),
                        'confirm' => [
                            'title'   => (string)__("Delete {$item['name']}"),
                            'message' => (string)__("Are you sure you wan't to delete a label '{$item['name']}'?"),
                        ],
                    ],
                ];
            }
        }

        return $dataSource;
    }
}
