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

namespace Mirasvit\CatalogLabel\Model\Label\Rule\Action;

use Magento\Framework\View\Asset\Repository;
use Magento\Framework\View\LayoutInterface;
use Magento\Rule\Model\ActionFactory;

class Collection extends \Magento\Rule\Model\Action\Collection
{
    public function __construct(
        Repository $assetRepo,
        LayoutInterface $layout,
        ActionFactory $actionFactory,
        array $data = []
    ) {
        parent::__construct($assetRepo, $layout, $actionFactory, $data);
        $this->setType('cataloglabel/label_rule_action_collection');
        $this->setType('\\Mirasvit\\CatalogLabel\\Model\\Label\\Rule\\Action\\Collection');
    }

    public function getNewChildSelectOptions(): array
    {
        $actions = parent::getNewChildSelectOptions();
        $actions = array_merge_recursive($actions, [
            [
                'value' => '\\Mirasvit\\CatalogLabel\\Model\\Label\\Rule\\Action\\Product',
                'label' => (string)__('Update the Product'), ],
        ]);

        return $actions;
    }
}
