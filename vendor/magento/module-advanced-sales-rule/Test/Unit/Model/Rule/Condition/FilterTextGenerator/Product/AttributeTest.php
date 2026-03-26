<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedSalesRule\Test\Unit\Model\Rule\Condition\FilterTextGenerator\Product;

use Magento\AdvancedSalesRule\Model\Rule\Condition\FilterTextGenerator\Product\Attribute;

class AttributeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * test: generateFilterText()
     *
     * situation: if param is not an Address from a Quote, the generated filter text should be empty
     */
    public function testForEmptyGenerateFilterText()
    {
        $config = $this->createMock(\Magento\Eav\Model\Config::class);
        $localeFormat = $this->createMock(\Magento\Framework\Locale\FormatInterface::class);
        $filterTextGenerator = new Attribute(['attribute' => 'kiwi'], $config, $localeFormat);
        $param = new \Magento\Framework\DataObject();
        $filterText = $filterTextGenerator->generateFilterText($param);
        $this->assertEmpty($filterText, "Expected 'filterText' to be empty");
    }

    /**
     * test: generateFilterText()
     *
     * situation: typical usage
     */
    public function testGenerateFilterText()
    {
        $attrCode = 'kiwi';
        $attrValues = ['bird', 'fruit', 'shoe polish', 'bird'];

        $attribute = $this->createMock(\Magento\Eav\Model\Entity\Attribute\AbstractAttribute::class);
        $attribute->method('getBackendType')->willReturn('text');
        $config = $this->createMock(\Magento\Eav\Model\Config::class);
        $config->expects($this->once())->method('getAttribute')->willReturn($attribute);
        $localeFormat = $this->createMock(\Magento\Framework\Locale\FormatInterface::class);

        $filterTextGenerator = new Attribute(['attribute' => $attrCode], $config, $localeFormat);

        /** @var \Magento\Quote\Model\Quote\Address|\PHPUnit\Framework\MockObject\MockObject $quoteAddress */
        $quoteAddress = $this->buildQuoteAddress($attrCode, $attrValues);

        $filterText = $filterTextGenerator->generateFilterText($quoteAddress);
        $this->verifyResults($filterText, $attrCode, $attrValues);
    }

    /**
     * Tests a case when the attribute backend type is a decimal.
     */
    public function testGenerateFilterTextWithDecimal()
    {
        $decimalValue = '1.01000';
        $attrCode = 'kiwi';
        $attrValues = [$decimalValue];

        $attribute = $this->createMock(\Magento\Eav\Model\Entity\Attribute\AbstractAttribute::class);
        $attribute->method('getBackendType')->willReturn('decimal');
        $config = $this->createMock(\Magento\Eav\Model\Config::class);
        $config->expects($this->once())->method('getAttribute')->willReturn($attribute);
        $localeFormat = $this->createMock(\Magento\Framework\Locale\FormatInterface::class);
        $localeFormat->expects($this->atLeastOnce())->method('getNumber')->willReturn((float)$decimalValue);

        $filterTextGenerator = new Attribute(['attribute' => $attrCode], $config, $localeFormat);

        /** @var \Magento\Quote\Model\Quote\Address|\PHPUnit\Framework\MockObject\MockObject $quoteAddress */
        $quoteAddress = $this->buildQuoteAddress($attrCode, $attrValues);

        $filterText = $filterTextGenerator->generateFilterText($quoteAddress);
        $this->verifyResults($filterText, $attrCode, $attrValues);
    }

    // --- helpers ------------------------------------------------------------

    protected function verifyResults(array $filterText, $attrCode, array $attrValues)
    {
        // gather all the unique attribute values
        $uniqueAttrValues = [];
        foreach ($attrValues as $value) {
            if (!in_array($value, $uniqueAttrValues)) {
                $uniqueAttrValues[] = $value;
            }
        }

        // verify all the attribute combinations are present
        $missingAttrs = [];
        foreach ($uniqueAttrValues as $value) {
            $token = $attrCode . ':' . ($this->isDecimal($value) ? (float)$value : $value);
            if (!$this->findMe($token, $filterText)) {
                $missingAttrs[] = $token;
            }
        }
        if (sizeof($missingAttrs)) {
            $this->fail("'filterText' is missing the following attributes: " . print_r($missingAttrs, true));
        }

        // verify same size of the unique attribute values array and the results array
        $this->assertEquals(
            sizeof($uniqueAttrValues),
            sizeof($filterText),
            "Expected size of 'uniqueAttrValues' to be the same as 'filterText'"
        );
    }

    protected function findMe($needle, array $haystack)
    {
        foreach ($haystack as $entry) {
            if (strpos($entry, (string)$needle) !== false) {
                return true;
            }
        }
        return false;
    }

    // this will also add some additional "don't care about these" items
    protected function buildQuoteAddress($attrCode, array $attrValues)
    {
        $items = [];

        // build the valid items
        foreach ($attrValues as $value) {
            $items[] = $this->buildItem($attrCode, $value);
        }

        // build some "don't care about these" items that have the following values: {null, array, object}
        $items[] = $this->buildItem($attrCode, null);
        $items[] = $this->buildItem($attrCode, ['some', 'random', 'array']);
        $items[] = $this->buildItem($attrCode, new \Magento\Framework\DataObject());

        $className = \Magento\Quote\Model\Quote\Address::class;
        $quoteAddress = $this->createPartialMock($className, ['getAllItems']);
        $quoteAddress->expects($this->once())->method('getAllItems')->willReturn($items);

        return $quoteAddress;
    }

    protected function buildItem($attrCode, $value)
    {
        $className = \Magento\Catalog\Model\Product::class;
        $product = $this->createPartialMock($className, ['getData', 'hasData', 'load']);
        $product->expects($this->never())->method('load');
        $product->expects($this->any())->method('getData')->with($attrCode)->willReturn($value);

        $className = \Magento\Quote\Model\Quote\Item\AbstractItem::class;
        $item = $this->getMockForAbstractClass($className, [], '', false, false, true, ['getProduct']);
        $item->expects($this->once())->method('getProduct')->willReturn($product);

        return $item;
    }

    /**
     * Is decimal string.
     *
     * @param string $val
     * @return bool
     */
    private function isDecimal(string $val)
    {
        return is_numeric($val) && floor((float)$val) != $val;
    }
}
