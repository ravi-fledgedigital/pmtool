<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\RmaGraphQl\Model\Formatter;

use Magento\EavGraphQl\Model\GetAttributeValueComposite;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\GraphQl\Query\EnumLookup;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Rma\Api\Data\ItemInterface;
use Magento\Rma\Api\RmaAttributesManagementInterface;
use Magento\Rma\Model\Item;

/**
 * Rma item formatter
 */
class RmaItem
{
    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @var EnumLookup
     */
    private $enumLookup;

    /**
     * @var string
     */
    private $rmaItemStatusEnum = 'ReturnItemStatus';

    /**
     * @var CustomAttribute
     */
    private $customAttributeFormatter;

    /**
     * @var array
     */
    private $systemAttributes = [
        Item::ENTITY_ID,
        Item::RMA_ENTITY_ID,
        Item::ORDER_ITEM_ID,
        Item::QTY_REQUESTED,
        Item::QTY_AUTHORIZED,
        Item::QTY_RETURNED,
        Item::QTY_APPROVED,
        Item::STATUS,
        'extension_attributes'
    ];
    
    /**
     * @var GetAttributeValueComposite
     */
    private GetAttributeValueComposite $getAttributeValueComposite;

    /**
     * @param Uid $idEncoder
     * @param EnumLookup $enumLookup
     * @param CustomAttribute $customAttributeFormatter
     * @param GetAttributeValueComposite $getAttributeValueComposite
     */
    public function __construct(
        Uid $idEncoder,
        EnumLookup $enumLookup,
        CustomAttribute $customAttributeFormatter,
        GetAttributevalueComposite $getAttributeValueComposite
    ) {
        $this->idEncoder = $idEncoder;
        $this->enumLookup = $enumLookup;
        $this->customAttributeFormatter = $customAttributeFormatter;
        $this->getAttributeValueComposite = $getAttributeValueComposite;
    }

    /**
     * Format RMA item according to the GraphQL schema
     *
     * @param ItemInterface $item
     * @return array
     * @throws RuntimeException
     */
    public function format(ItemInterface $item): array
    {
        $customAttributes = [];
        $customAttributesCodesValues = [];

        foreach ($item->getData() as $attributeCode => $value) {
            if (in_array($attributeCode, $this->systemAttributes, true)) {
                continue;
            }
            $attribute = $item->getAttribute($attributeCode);
            if (isset($attribute) && $attribute->getIsVisible()) {
                $customAttributes[] = $this->customAttributeFormatter->format($attribute, $value);
                $customAttributesCodesValues[] = [
                    'attribute_code' => $attribute->getAttributeCode(),
                    'value' => $value
                ];
            }
        }

        $customAttributesV2 = array_map(
            function (array $customAttribute) {
                return $this->getAttributeValueComposite->execute(
                    RmaAttributesManagementInterface::ENTITY_TYPE,
                    [
                        'attribute_code' => $customAttribute['attribute_code'],
                        'value' => $customAttribute['value']
                    ]
                );
            },
            $customAttributesCodesValues
        );
        usort($customAttributesV2, function (array $a, array $b) {
            return $a['sort_order'] <=> $b['sort_order'];
        });

        return [
            'uid' => $this->idEncoder->encode((string)$item->getEntityId()),
            'custom_attributes' => $customAttributes,
            'custom_attributesV2' => $customAttributesV2,
            'request_quantity' => (float)$item->getQtyRequested(),
            'quantity' => (float)$item->getQtyAuthorized(),
            'status' => $this->enumLookup->getEnumValueFromField($this->rmaItemStatusEnum, $item->getStatus()),
            'model' => $item
        ];
    }
}
