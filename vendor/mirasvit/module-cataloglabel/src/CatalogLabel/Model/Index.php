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

namespace Mirasvit\CatalogLabel\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Mirasvit\CatalogLabel\Api\Data\IndexInterface;
use Mirasvit\CatalogLabel\Model;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Index extends AbstractModel implements IdentityInterface, IndexInterface
{
    /**
     * @var string
     */
    protected $_cacheTag = 'cataloglabel_index';
    /**
     * @var string
     */
    protected $_eventPrefix = 'cataloglabel_index';

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->context            = $context;
        $this->registry           = $registry;
        $this->resource           = $resource;
        $this->resourceCollection = $resourceCollection;

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        $this->_init('Mirasvit\CatalogLabel\Model\ResourceModel\Index');
    }

    public function getIdentities(): array
    {
        return [self::CACHE_TAG.'_'.$this->getId()];
    }
}
