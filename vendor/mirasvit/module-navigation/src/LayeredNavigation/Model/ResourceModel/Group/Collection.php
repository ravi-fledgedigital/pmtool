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


namespace Mirasvit\LayeredNavigation\Model\ResourceModel\Group;


use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Mirasvit\LayeredNavigation\Api\Data\GroupInterface;

class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(
            \Mirasvit\LayeredNavigation\Model\Group::class,
            \Mirasvit\LayeredNavigation\Model\ResourceModel\Group::class
        );

        $this->_idFieldName = GroupInterface::ID;
    }
}
