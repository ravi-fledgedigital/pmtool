1.6.0
==============
* Features:
    * The NetSuite multi-select custom field support was added to the product import.

* Bugfixes:
    * Wrong totals issue during export order to Netsuite was fixed.
    * The undefined shipping method issue was fixed. When track number exists but the shipping method not selected for the item fulfillment.
    * The import invoice issue was fixed. When an invoice item does not have the description.

1.6.1
==============
* Bugfixes:
    * Duplicate order item issue was fixed. When importing order data from Netsuite.
    * Incorrect order status update issue was fixed.

1.7.0
==============
* Features:
    * Export invoices from Magento 2 to NetSuite.
    * Export shipments from Magento 2 to NetSuite
    * Export credit memos from Magento 2 to NetSuite
    * Export Advanced Pricing from Magento 2 to NetSuite at Product Level
    * Added import Source and Source Qty (MSI)
    * Added export Source and Source Qty (MSI)

* Bugfixes:
    * Added custom fields update
    * Replaced upsert on adding and updating when exporting products
    * Fixed issue with merging existing in Magento order item data and the Netsuite order item data
    * Fixed issue with export customers and products

1.7.1
==============
* Bugfixes:
    * Fixed order update issue. Added custom logic to the infoBuyRequest update.

1.8.0
==============
* Features:
    * Added possibility to specify Netsuite API credentials to the export job page.
    * Added import of credit memos, import of cash sale, and import of cash refund.

1.9.0
==============
* Features:
    * Added import of price levels as customer groups
    * Added possibility to specify Netsuite Lead Source to the exporting order
    * Added Netsuite request handler 
    
* Bugfixes
    * Fixed an issue with importing other prices
    * Fixed an issue with importing tier prices
    * Fixed an issue where, after importing invoices, it was impossible to create a credit memo
    * Fixed issue with duplicate order items
    * Fixed an issue with mapping of a region code when order importing
    * Added logging of successful or not export to NS of some entities
    * Fixed problems with payment and shipping mappings
    * Fixed an issue with Map Attributes mapping (no attributes in the Import Attribute column)
    * Fixed an error when updating products
    * Fixed problem with missing some arguments in the category import class constructor
    * Fixed an issue with importing shipments
