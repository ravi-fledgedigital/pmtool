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


namespace Mirasvit\LayeredNavigation\Helper;


class ArrayHelper
{
    public static function insertIntoPosition(array $parent, array $insert, ?int $position = null): array
    {
        if (is_null($position)) {
            $position = count($parent);
        }

        $position--;

        if ($position < 0) {
            $position = 0;
        }

        $formatted = [];

        foreach ($parent as $key => $value) {
            $formatted[(string)$key] = $value;
        }

        $parent = $formatted;

        if ($position <= 0) {
            return $insert + $parent;
        }

        $start = array_slice($parent, 0, $position, true);
        $end   = array_slice($parent, $position, NULL, true);

        return $start + $insert + $end;
    }
}
