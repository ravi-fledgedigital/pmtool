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


namespace Mirasvit\LayeredNavigationLiveSearch\Model\Aggregation;


use Magento\LiveSearchAdapter\Model\Aggregation\BucketHandlerInterface;

/** mp comment start **/
$path = \Magento\Framework\App\ObjectManager::getInstance()->get('\Magento\Framework\Filesystem\DirectoryList')->getRoot();
$path1 = $path . '/vendor/magento/module-live-search-adapter/Model/Aggregation/BucketHandlerInterface.php';
$path2 = $path . '/app/code/Magento/LiveSearchAdapter/Model/Aggregation/BucketHandlerInterface.php';


if (file_exists($path1) || file_exists($path2)) {
    /** mp comment end **/
    interface AttributeBucketHandlerInterface extends BucketHandlerInterface
    {

    }
    /** mp comment start **/
} else {
    interface AttributeBucketHandlerInterface
    {

    }
}
/** mp comment end **/
