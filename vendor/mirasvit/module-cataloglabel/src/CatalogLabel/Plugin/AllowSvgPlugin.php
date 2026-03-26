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


namespace Mirasvit\CatalogLabel\Plugin;


use Magento\MediaStorage\Model\File\Validator\NotProtectedExtension;

class AllowSvgPlugin
{
    public function afterGetProtectedFileExtensions(NotProtectedExtension $subject, $result)
    {
        if (is_string($result)) {
            $result = explode(',', $result);
        }

        $idx = array_search('svg', $result);

        if ($idx !== false) {
            unset($result[$idx]);
        }

        return $result;
    }
}
