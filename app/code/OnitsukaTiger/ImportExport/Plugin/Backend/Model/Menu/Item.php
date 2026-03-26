<?php

namespace OnitsukaTiger\ImportExport\Plugin\Backend\Model\Menu;

use Magento\Backend\Model\Menu\Item as NativeItem;

class Item
{
    /**
     * @param NativeItem $subject
     * @param $url
     *
     * @return string
     */
    public function afterGetUrl(NativeItem $subject, $url)
    {
        $id = $subject->getId();
        /* we can't add guide link into item object - find link again */
        if (strpos($id, 'OnitsukaTiger_ImportExport') !== false) {
            $url = '';
        }
        return $url;
    }
}
