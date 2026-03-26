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

namespace Mirasvit\LandingPage\Model\ResourceModel\Page;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Exception\LocalizedException;
use Mirasvit\LandingPage\Api\Data\PageInterface;
use Mirasvit\LandingPage\Api\Data\PageStoreInterface;

class Store extends AbstractDb
{
    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
    }

    protected function _construct()
    {
        $this->_init(PageStoreInterface::TABLE_NAME, PageStoreInterface::ID);
    }

    /**
     * @throws LocalizedException
     */
    public function loadByPageAndStore(PageStoreInterface $object, int $pageId, int $storeId): self
    {
        $connection = $this->getConnection();
        if ($connection) {
            $select = $connection->select()
                ->from($this->getMainTable())
                ->where(PageStoreInterface::PAGE_ID . '=?', $pageId)
                ->where(PageStoreInterface::STORE_ID . '=?', $storeId);

            $data = $connection->fetchRow($select);

            if ($data) {
                $object->setData($data);
            }
        }

        return $this;
    }
}
