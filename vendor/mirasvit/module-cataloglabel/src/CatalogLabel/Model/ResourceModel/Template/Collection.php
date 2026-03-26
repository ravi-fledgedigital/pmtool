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


namespace Mirasvit\CatalogLabel\Model\ResourceModel\Template;


use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\Option\ArrayInterface;

class Collection extends AbstractCollection implements ArrayInterface
{
    /**
     * @var string
     */
    protected $_idFieldName = 'template_id';//@codingStandardsIgnoreLine

    protected function _construct()
    {
        $this->_init(
            'Mirasvit\CatalogLabel\Model\Template',
            'Mirasvit\CatalogLabel\Model\ResourceModel\Template'
        );
    }

    public function toOptionArray(): array
    {
        return $this->_toOptionArray('template_id', 'name');
    }
}
