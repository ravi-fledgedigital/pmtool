<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/
declare(strict_types=1);

namespace OnitsukaTiger\OrderAttribute\Setup\Patch\Data;

use OnitsukaTiger\OrderAttribute\Model\Attribute\Attribute;
use OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\Attribute as AttributeResource;
use OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\Collection;
use OnitsukaTiger\OrderAttribute\Model\ResourceModel\Entity\Entity as EntityResource;
use Magento\Eav\Model\Config;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddEntityType implements DataPatchInterface
{
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        EavSetupFactory $eavSetupFactory,
        Config $config
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->config = $config;
    }

    public function apply(): self
    {
        /** @var \Magento\Eav\Setup\EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create();
        $eavSetup->addEntityType(
            EntityResource::ENTITY_TYPE_CODE,
            [
                'entity_model' => EntityResource::class,
                'attribute_model' => Attribute::class,
                'table' => EntityResource::TABLE_NAME,
                'entity_attribute_collection' => Collection::class,
                'additional_attribute_table' => AttributeResource::TABLE_NAME
            ]
        );

        $this->config->clear();

        return $this;
    }

    public function getAliases(): array
    {
        return [];
    }

    public static function getDependencies(): array
    {
        return [];
    }
}
