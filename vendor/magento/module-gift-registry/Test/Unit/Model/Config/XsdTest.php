<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Test\Unit\Model\Config;

use Magento\Framework\Config\Dom;
use Magento\Framework\Config\Dom\UrnResolver;
use PHPUnit\Framework\TestCase;

class XsdTest extends TestCase
{
    /**
     * File path for xsd
     *
     * @var string
     */
    protected $_xsdFilePath;

    protected function setUp(): void
    {
        if (!function_exists('libxml_set_external_entity_loader')) {
            $this->markTestSkipped('Skipped on HHVM. Will be fixed in MAGETWO-45033');
        }
        $urnResolver = new UrnResolver();
        $this->_xsdFilePath = $urnResolver->getRealPath('urn:magento:module:Magento_GiftRegistry:etc/giftregistry.xsd');
    }

    /**
     * Tests different cases with invalid xml files
     *
     * @dataProvider invalidXmlFileDataProvider
     * @param string $xmlFile
     * @param array $expectedErrors
     */
    public function testInvalidXmlFile($xmlFile, $expectedErrors)
    {
        $dom = new \DOMDocument();
        $dom->load(__DIR__ . '/../_files/' . $xmlFile);

        libxml_use_internal_errors(true);
        $errorMessages = Dom::validateDomDocument($dom, $this->_xsdFilePath);
        libxml_use_internal_errors(false);

        $this->assertEquals($errorMessages, $expectedErrors);
    }

    /**
     * Tests valid xml file
     */
    public function testValidXmlFile()
    {
        $dom = new \DOMDocument();
        $dom->load(__DIR__ . '/../_files/config_valid.xml');

        libxml_use_internal_errors(true);
        $errorMessages = Dom::validateDomDocument($dom, $this->_xsdFilePath);
        libxml_use_internal_errors(false);

        $this->assertEmpty($errorMessages);
    }

    /**
     * @return array
     */
    public function invalidXmlFileDataProvider()
    {
        return [
            [
                'config_invalid_attribute_group.xml',
                [
                    "Element 'attribute_group': Duplicate key-sequence ['registry'] in unique identity-constraint " .
                    "'uniqueAttributeGroupName'.\nLine: 17\nThe xml was: \n12:        <label translate=\"true\">" .
                    "Gift Registry Details</label>\n13:    </attribute_group>\n14:    <attribute_group " .
                    "name=\"registry\" sort_order=\"10\" visible=\"true\">         " .
                    "<!-- Duplicate attribute_group name -->\n15:        <label translate=\"true\">" .
                    "Gift Registry Details</label>\n16:    </attribute_group>\n17:    <registry>\n18:        " .
                    "<static_attribute name=\"event_date\">\n19:            <label translate=\"true\">" .
                    "Event Date</label>\n20:        </static_attribute>\n21:    </registry>\n"
                ],
            ],
            [
                'config_invalid_attribute_type.xml',
                [
                   "Element 'attribute_type': Duplicate key-sequence ['text'] in unique identity-constraint " .
                   "'uniqueAttributeTypeName'.\nLine: 12\nThe xml was: \n7:<config " .
                   "xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" " .
                   "xsi:noNamespaceSchemaLocation=\"urn:magento:module:Magento_GiftRegistry:etc/giftregistry.xsd\">\n" .
                   "8:    <attribute_type name=\"text\">\n9:        <label translate=\"true\">Text</label>\n" .
                   "10:    </attribute_type>\n11:    <attribute_type name=\"text\">" .
                   "<!-- Duplicate attribute_type name -->\n12:        <label translate=\"true\">Text</label>\n" .
                   "13:    </attribute_type>\n14:    <registry>\n15:        <static_attribute name=\"event_date\">\n" .
                   "16:            <label translate=\"true\">Event Date</label>\n"
                ]
            ],
            [
                'config_invalid_static_attribute.xml',
                [
                    "Element 'static_attribute': Duplicate key-sequence ['event_date'] in unique " .
                    "identity-constraint 'uniqueStaticAttributeName'.\nLine: 20\nThe xml was: \n" .
                    "15:        <static_attribute name=\"event_date\">\n16:            <label " .
                    "translate=\"true\">Event Date</label>\n17:        </static_attribute>\n18:        " .
                    "<static_attribute name=\"event_date\"> <!-- Duplicate static_attribute name -->\n" .
                    "19:            <label translate=\"true\">Event Date</label>\n20:        " .
                    "</static_attribute>\n21:    </registry>\n22:    <registrant>\n23:        " .
                    "<static_attribute name=\"role\" group=\"registrant\">\n24:            <label " .
                    "translate=\"true\">Role</label>\n"
                ]
            ],
            [
                'config_invalid_custom_attribute.xml',
                [
                    "Element 'custom_attribute': Duplicate key-sequence ['custom_event_data'] in unique " .
                    "identity-constraint 'uniqueCustomAttributeName'.\nLine: 23\nThe xml was: \n18:        " .
                    "<custom_attribute name=\"custom_event_data\">\n19:            <label translate=\"true\">" .
                    "Event Data</label>\n20:        </custom_attribute>\n21:        <custom_attribute " .
                    "name=\"custom_event_data\"><!-- Duplicate static_attribute name -->\n22:            " .
                    "<label translate=\"true\">Event Data</label>\n23:        </custom_attribute>\n24:    " .
                    "</registry>\n25:    <registrant>\n26:        <static_attribute name=\"role\" " .
                    "group=\"registrant\">\n27:            <label translate=\"true\">Role</label>\n"
                ]
            ]
        ];
    }
}
