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
 * @package   mirasvit/module-sorting
 * @version   1.4.5
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\Sorting\Model\Product\Indexer\Fulltext\Datasource;

/** mp comment start **/
if (interface_exists(\Smile\ElasticsuiteCore\Api\Index\DatasourceInterface::class)) {
    interface SortingDataInterface extends \Smile\ElasticsuiteCore\Api\Index\DatasourceInterface
    {
    }
} else {
/** mp comment end **/
    interface SortingDataInterface
    {
    }
/** mp comment start **/
}
/** mp comment end **/
