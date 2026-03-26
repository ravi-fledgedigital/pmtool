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


namespace Mirasvit\CatalogLabel\Model\ResourceModel;


use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Mirasvit\CatalogLabel\Api\Data\IndexInterface;


class Index extends AbstractDb
{
    public function __construct(
        Context $context,
        ?string $resourcePrefix = null
    ) {
        parent::__construct($context, $resourcePrefix);
    }

    protected function _construct()
    {
        $this->_init(IndexInterface::TABLE_NAME, 'id');
    }
}
