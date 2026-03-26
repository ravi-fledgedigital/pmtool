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

namespace Mirasvit\LandingPage\Model\ResourceModel\Filter;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'filter_id';

    protected $_eventPrefix = 'mst_landing_page_filter_collection';

    protected $_eventObject = 'landing_page_filters_collection';

    protected function _construct()
    {
        $this->_init('Mirasvit\LandingPage\Model\Filter', 'Mirasvit\LandingPage\Model\ResourceModel\Filter');
    }

}

