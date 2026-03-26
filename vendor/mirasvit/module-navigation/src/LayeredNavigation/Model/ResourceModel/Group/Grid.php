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


namespace Mirasvit\LayeredNavigation\Model\ResourceModel\Group;


use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Mirasvit\LayeredNavigation\Api\Data\GroupInterface;
use Mirasvit\LayeredNavigation\Model\Group;
use Psr\Log\LoggerInterface as Logger;

class Grid extends SearchResult
{
    protected $document = Group::class;

    private $eavConfig;

    public function __construct(
        EavConfig $eavConfig,
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        $mainTable = GroupInterface::TABLE_NAME,
        $resourceModel = \Mirasvit\LayeredNavigation\Model\ResourceModel\Group::class
    ) {
        $this->eavConfig = $eavConfig;

        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $mainTable,
            $resourceModel
        );
    }

    protected function _initSelect()
    {
        parent::_initSelect();

        $entityTypeId = $this->eavConfig->getEntityType(ProductAttributeInterface::ENTITY_TYPE_CODE)->getId();

        $this->getSelect()->joinLeft(
            ['ea' => $this->getTable('eav_attribute')],
            'main_table.attribute_code = ea.attribute_code AND ea.entity_type_id = ' . (int)$entityTypeId,
            ['frontend_label']
        );

        $this->addFilterToMap('attribute', 'ea.frontend_label');
        $this->addFilterToMap('labels', 'main_table.title');
        $this->addFilterToMap('position', 'main_table.position');

        return $this;
    }
}
