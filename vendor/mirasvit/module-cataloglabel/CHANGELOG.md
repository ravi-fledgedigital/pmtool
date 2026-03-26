# Change Log
## 2.5.7
*(2026-03-11)*

#### Fixed
* Fixed [discount_percent] showing catalog rule percentage instead of actual discount for configurable products with special-priced children

---


## 2.5.6
*(2026-01-30)*

#### Fixed
* Fixed the issue with decimal attributes displaying with trailing zeros on labels

---


## 2.5.5
*(2026-01-02)*

#### Fixed
* Fixed the DateMalformedStringException for empty date attributes

---


## 2.5.4
*(2025-12-22)*

#### Features
* Compatibility with Hyvä 1.4

---


## 2.5.3
*(2025-12-19)*

#### Fixed
* Fixed the issue with labels overlapping the dropdown in admin grid

---


## 2.5.2
*(2025-12-03)*

#### Fixed
* Fixed the issue with the badge displaying

---


## 2.5.1
*(2025-07-10)*

#### Fixed
* Fixed the issue with applying product variables when they are not used in labels

---


## 2.5.0
*(2025-06-23)*

#### Improvements
* WCAG 2.2 AA compliance

---


## 2.4.10
*(2025-05-20)*

#### Fixed
* Fixed the issue where the stock_qty variable was doubling results

---


## 2.4.9
*(2025-05-07)*

#### Improvements
* Added a calendar date picker for attributes with the "date" input type
* Retrieve stock quantity from all inventory sources when using the [stock_qty] variable (MSI enabled)

---


## 2.4.8
*(2025-04-24)*

#### Improvements
* Added support for HTML tags in the "Title" field

---


## 2.4.7
*(2025-04-14)*

#### Fixed
* Compatibility with Magento 2.4.8

---


## 2.4.6
*(2025-04-04)*

#### Fixed
* Fixed the issue where the "Quantity is 0" condition was skipping products with zero quantity

---


## 2.4.5
*(2024-12-27)*

#### Fixed
* Fixed the issue with displaying product variables on a label without a template

---


## 2.4.4
*(2024-12-13)*

#### Fixed
* Fixed the issue with the [attr|attributeCode] variables if they were added multiple times

---


## 2.4.3
*(2024-12-10)*

#### Fixed
* Fixed the issue with template preview when custom styles are applied

---


## 2.4.2
*(2024-11-22)*

#### Fixed
* Fixed the issue with discount calculation when catalog prices include tax
* Fixed the issue with product variables on labels for child products of configurable products

---


## 2.4.1
*(2024-11-14)*

#### Fixed
* Fixed the issue with incorrect discount value in labels

---


## 2.4.0
*(2024-11-06)*

#### Improvements
* Improved and optimized reindexing time

---


## 2.3.11
*(2024-10-24)*

#### Fixed
* Fixed the issue when the "Apply labels for configurable products if one of the child products meets the conditions" configuration is enabled (TypeError: count(): Argument [#1]() ($value) must be of type Countable|array, null given in mirasvit/module-cataloglabel/src/CatalogLabel/Block/Product/Label/Placeholder.php:172)

---


## 2.3.10
*(2024-10-22)*

#### Improvements
* Improved the "Apply labels for configurable products if one of the child products meets the conditions" configuration

---


## 2.3.9
*(2024-10-11)*

#### Improvements
* Added ability to display labels for configurable products if one of the child products meets the conditions

#### Fixed
* Fixed the issue with mass actions when applying a filter (all records are changed/deleted)
* Fixed the issue with the "Price - Final Price" condition when taxes are enabled in the store

---


## 2.3.8
*(2024-08-08)*

#### Features
* Added ability to set the "Final Price" condition in a label rule

---


## 2.3.7
*(2024-06-07)*

#### Improvements
* Reindex improvements

#### Fixed
* Fixed the label positioning issue for products with multiple images on product view page
* Fixed the issue with getting a discount percentage when the product price is set in a different currency

---


## 2.3.6
*(2024-05-23)*

#### Fixed
* Fixed the issue with double POST requests on saving labels (Firefox)
* Fixed the issue with the mst_productlabel_label_rule_product table (indexing performed for all stores instead of only specified stores)

---


## 2.3.5
*(2024-04-11)*

#### Fixed
* Unnecessary empty placeholders when the 'Apply labels for child products of configurable products' option is disabled

---


## 2.3.4
*(2024-03-21)*

#### Fixed
* Fixed the issue with the "Customer Groups" configuration on product labels
* Fixed the issue with CSS styles from label templates

---


## 2.3.3
*(2024-03-14)*

#### Improvements
* Security improvements

---


## 2.3.2
*(2024-03-05)*

#### Fixed
* Fixed the issue with labels not displaying on the homepage

---


## 2.3.1
*(2024-02-23)*

#### Improvements
* Ability to apply labels for simple products of configurable product

---


## 2.3.0
*(2024-02-12)*

#### Features
* Added the ability to hide labels on specified pages

#### Fixed
* Fixed the issue with labels on configurable products (the condition “Percent Discount” is set, and the “Display Product Prices In Catalog” setting is set to “Including Tax”)

---


## 2.2.8
*(2023-12-22)*

#### Fixed
* Fixed the "Percent Discount" condition (multi-store)

---


## 2.2.7
*(2023-12-08)*

#### Improvements
* Run the "cataloglabel" cron every day at 00:45
* Apply the "cataloglabel" cron only if the product label index is set to "Update by Schedule". Use the "mirasvit:label:emulate:cron" command to run the cron regardless

---


## 2.2.6
*(2023-12-01)*

#### Features
* Added the "Active From" and "Active To" columns to the "Manage Labels" admin grid

#### Improvements
* Optimized product label indexing

#### Fixed
* Fixed the issue with the label conditions applying (multiple stores)

---


## 2.2.5
*(2023-11-16)*

#### Fixed
* Fixed the issue with labels on bundle products that have discounts

---


## 2.2.4
*(2023-10-27)*

#### Improvements
* Prevent the overlay of product label preview on the admin panel

---


## 2.2.3
*(2023-10-23)*

#### Fixed
* Fixed the type error when using a product attribute in a label (some cases)

---


## 2.2.2
*(2023-10-04)*

#### Fixed
* Fixed error on labels listing when incorrect styles added to label

---


## 2.2.1
*(2023-09-07)*

#### Features
* Added the ability to set the date format for the "Date" and "Date and Time" attribute types

#### Fixed
* Fixed the issue with the "Active From" and "Active To" label settings

---


## 2.2.0
*(2023-08-08)*

#### Fixed
* Fixed the issue with conditions by tier price

---


## 2.1.9
*(2023-07-31)*

#### Fixed
* Fixed the issue with reindexing labels on Magento EE (giftcard products)

---


## 2.1.8
*(2023-07-11)*

#### Fixed
* Fixed the issue with saving labels (PHP8)

---


## 2.1.7
*(2023-07-06)*

#### Improvements
* Changed the type of the column 'style' in the table 'mst_productlabel_label_display' to allow saving large CSS

---


## 2.1.6
*(2023-06-30)*

#### Fixed
* Issue with error on Label edit page for labels of the type 'Attribute'

---


## 2.1.5
*(2023-06-22)*

#### Fixed
* Fixed the issue with error 'Notice: Undefined offset: 1' when labels not indexed yet

---


## 2.1.4
*(2023-06-15)*

#### Fixed
* The issue with the Save button after changing the Appearance field on the Label edit page

---


## 2.1.3
*(2023-06-09)*

#### Improvements
* Ability to apply label manually when Product Labels index is set to 'Update by Schedule'

---


## 2.1.2
*(2023-06-08)*

#### Fixed
* Issue with Labels relative URLs in multistore when store code is added to store URL

---


## 2.1.1
*(2023-06-05)*

#### Fixed
* indexer 'Update by Schedule'

---


## 2.1.0
*(2023-05-30)*

#### Fixed
* variable for label URL in templates

---


## 2.0.9
*(2023-05-22)*

#### Fixed
* Fixed the issue with labels' discount variable value (some cases)

---


## 2.0.8
*(2023-04-21)*

#### Fixed
* Fixed the issue with attribute variable not always returning correct value

---


## 2.0.7
*(2023-04-20)*

#### Improvements
* SVG images in labels

#### Fixed
* Fixed the issue with cron tasks (Cron Job cataloglabel has an error: Argument 1 passed to Mirasvit\CatalogLabel\Model\Observer::apply() must be of the type bool, object given)

---


## 2.0.6
*(2023-04-18)*

#### Fixed
* Fixed the issue with error when placeholder is deleted but its code is used for manual labels

---


## 2.0.5
*(2023-04-12)*

#### Fixed
* Fixed the issue with stock condition (multistock)
* Fixed the issue with labels for products which not present in the default store
* Fixed the issue with lebels' Visible In setting not saved

---


## 2.0.4
*(2023-04-10)*

#### Fixed
* Fixed the issue with uploading label images

---


## 2.0.3
*(2023-03-28)*

#### Fixed
* Fixed the issue with errors related to incorrect styles

---


## 2.0.2
*(2023-03-20)*

#### Fixed
* Console command return value
* PHP 8.2 compatibility

---


## 2.0.1
*(2023-03-17)*

#### Fixed
* Composer conflict with old hyva compatibility module

---


## 2.0.0
*(2023-03-15)*

#### Features
* Templates for labels
* Positioning labels through placeholders
* Manually positioned placeholders

#### Improvements
* Module structure changed
* New DB tables
* Labels output logic changed
* "Show labels in custom themes" code changed

#### Fixed
* Several small bugs fixed

#### Other
* Labels created in previous versions of the extension will be migrated to the new version automatically but may require additional adjustments
* Old "Show labels in custom themes" code should not produce errors but no longer shows labels. Check the user manual for updated code.

---


## 1.3.15
*(2023-02-15)*

#### Fixed
* Fixed the issue with discount rule

---


## 1.3.14
*(2023-02-08)*

#### Fixed
* Fixed the issue with the discount rule for configurable products

---


## 1.3.13
*(2023-02-06)*

#### Fixed
* Fixed the issue with Labels massAction change status

---


## 1.3.12
*(2023-02-01)*

#### Fixed
* Fixed the issue with error after deleting labels

---


## 1.3.11
*(2023-01-31)*

#### Fixed
* Fixed the issue with stock quantity condition

---


## 1.3.10
*(2023-01-30)*

#### Improvements
* Added support of Magento 2.4.6

#### Fixed
* Fixed the issue with product quantity condition

---


## 1.3.9
*(2023-01-20)*

#### Fixed
* Fixed the issue with the condition 'Set as New'

---


## 1.3.8
*(2023-01-17)*

#### Improvements
* New variables \[special_price_dl\], \[new_days\], \[attr|code\]

---


## 1.3.7
*(2023-01-16)*

#### Fixed
* Fixed the issue with stock status rule
* Fixed the issue with new table columns not added in the database during the upgrade of the module

---


## 1.3.6
*(2023-01-11)*

#### Fixed
* Fixed the issue with labels affecting products sorting order
* Fixed the issue with error while trying to edit labels of the type rule (PHP8.1, Magento_PricePermissions)
* Fixed reindexing issue

---


## 1.3.5
*(2023-01-09)*

#### Improvements
* Reindexing improved
* Small code changes
* Removed unnecessary empty labels in frontend

---


## 1.3.4
*(2022-12-26)*

#### Fixed
* Fixed the issue with the error while creating a product

---


## 1.3.3
*(2022-12-16)*

#### Fixed
* PHP8.1 compatibility issue

---


## 1.3.2
*(2022-12-16)*

#### Improvements
* Small code changes.

---


## 1.3.1
*(2022-12-13)*

#### Features
* Variables in label title and description.

---


## 1.3.0
*(2022-12-05)*

#### Improvements
* Code quality improved. 
* Admin UI updated.
* Reindex performance improved.
* Additional console command for reindex.

#### Fixed
* Fixed the issue with duplicated labels on product listings

---


## 1.2.9
*(2022-11-15)*

#### Fixed
* Fixed the issue with reindex on large stores

---


## 1.2.8
*(2022-11-02)*

#### Fixed
* Fixed the issue with the error 'Division by zero'

---


## 1.2.7
*(2022-10-20)*

#### Fixed
* Fixed the issue with the error on reindex (preg_split(): Passing null to parameter #3 () of type int is deprecated)

---


## 1.2.6
*(2022-10-14)*

#### Fixed
* Fixed the issue with the error 'Deprecated Functionality: preg_match_all(): Passing null to parameter #2 of type string is deprecated' (PHP8.1)

---


## 1.2.5
*(2022-10-12)*

#### Fixed
* Fixed the issue with percent discount rule for configurable products

---


## 1.2.4
*(2022-09-19)*

#### Fixed
* Strpos error on format text function
* Added composite index
* Indexing optimization (performance improvement)

---


## 1.2.3
*(2022-08-22)*

#### Fixed
* Corrected getImageType call
* Indexing issue (duplicates in index)

---


## 1.2.2
*(2022-07-28)*

#### Fixed
* Performance issues

#### Improvements
* Added the mst_cataloglabel index to handle Product Labels
* Cronjob for Label Rules indexing is running once a day now
* Label Rules indexing can be forced by running mst_cataloglabel reindex

---


## 1.2.1
*(2022-06-20)*

#### Improvements
* remove db_schema_whitelist.json

---


## 1.2.0
*(2022-05-23)*

#### Improvements
* Migrate to declarative schema

---


## 1.1.26
*(2021-12-28)*

#### Fixed
* Display labels on the catalog page

---

## 1.1.25
*(2021-11-26)*

#### Fixed
* getDisplays issue

---

## 1.1.24
*(2021-11-25)*

#### Improvements
* Speed up the getDisplays() method

---

## 1.1.23
*(2021-08-31)*

#### Improvements
* Added "Is Salable" product rule

---

## 1.1.22
*(2021-03-02)*

#### Fixed
* Apply persent discount rule issue

---

## 1.1.21
*(2021-03-02)*

#### Fixed
* Re-saving dates problem

---

## 1.1.20
*(2020-10-19)*

#### Fixed
* Small spelling fixes

---

## 1.1.19
*(2020-07-30)*

#### Improvements
* Support of Magento 2.4

#### Fixed
* missing product in ImageBuilder

---



### 1.1.18
*(2020-06-18)* 

#### Fixed
* Missing products when attribute rule enabled



### 1.1.17
*(2020-05-27)* 

#### Fixed
* Unable to save image
* Cannot instantiate abstract class issue
* Multiple labels display issue; added out of stock option t rules
* Invalid template file error in system.log



### 1.1.16
*(2020-03-17)* 

#### Fixed
*  Wrong rule processing when multistore inventory enabled


## 1.1.15
*(2020-01-20)*

#### Feature
* Display labels without images

---



## 1.1.14
*(2019-05-29)*

#### Fixed
* Position issue on product list

---



## 1.1.13
*(2019-01-23)*

#### Fixed
* M2.3. Product Label does not show on catalog page

---


## 1.1.12
*(2019-01-03)*

#### Fixed
* M2.1. Solved compilation issue

---


## 1.1.11
*(2018-11-29)*

#### Improvements
* M2.3 support

---

## 1.1.11
*(2018-11-29)*

#### Improvements
* M2.3 support

---


## 1.1.10
*(2018-11-28)*

#### Fixed
* support of magento 2.3

---


## 1.1.9
*(2018-08-16)*

#### Fixed
* Installation issue with area code

---



## 1.1.8
*(2018-07-18)*

#### Fixed
* Fixed an issue with not clickable link for Label on Product List page

---

## 1.1.7
*(2018-07-13)*

#### Fixed
* Fixed Percent Discount

---

## 1.1.6
*(2018-07-09)*

#### Fixed
* Properly display labels based on Percent Discount condition

---

### 1.1.5
*(2018-06-02)*

#### Fixed
* Fixed Percent Discount if Advanced Pricing->Special Price is given in percent

---

### 1.1.4
*(2018-06-13)*

#### Fixed
* Fixed an issue with unexpected label on product page

---

### 1.1.3
*(2018-05-31)*

#### Fixed
* Fixed an issue related to the rule cancellation while cron starts

---

### 1.1.2
*(2018-05-21)*

#### Fixed
* Fixed Attribute Gallery styles

---

### 1.1.1
*(2018-04-26)*

#### Fixed
* Fix big amount of memory usage
* Fixed Percent Discount rule for bundle products

---

### 1.1.0
*(2018-03-16)*

#### Improvements
* Flush dependent pages cache after new product creating

---

### 1.0.16
*(2018-03-09)*

#### Fixed
* Fixed an error "Fatal error: Uncaught Error: Call to undefined method Magento\CatalogRule\Model\ResourceModel\Rule::calcProductPriceRule()"

---

### 1.0.15
*(2018-01-29)*

#### Fixed
* Fixed bottom label position for products that have a single image with no thumbnails displayed.

---

### 1.0.14
*(2017-10-17)*

#### Fixed
* Magento 2.2 compatibility

---

### 1.0.13
*(2017-10-17)*

#### Fixed
* Magento 2.2 compatibility

---

### 1.0.11
*(2017-06-20)*

#### Documentation
* Online User Manual updated

---

### 1.0.10
*(2017-05-18)*

#### Fixed
* Fixed usability issue: "Style" field made adjustable for "Manage Labels" Admin Panel page
* Fixed "Notice: Undefined variable: percent" for some stores

---

### 1.0.9
*(2017-03-29)*

#### Improvements
* Added possibility to define additional CSS styles for labels from Admin Panel

#### Fixed
* Fixed design issue - labels removed from shopping cart page

---

### 1.0.8
*(2017-01-12)*

#### Fixed
* Fixed design issue

---

### 1.0.7
*(2016-12-27)*

#### Improvement
* Added product attribute "Set as New" to label conditions

---

### 1.0.6
*(2016-12-06)*

#### Fixes
* Fixed cron errors

---

### 1.0.5
*(2016-10-18)*

#### Improvement
* Updated docs

---

### 1.0.4
*(2016-09-05)*

#### Fixes
* Fixed an issue when badge description is breaking product list view

---

### 1.0.3
*(2016-06-30)*

#### Fixes
* Fixed an issue when cataloglabel is displaying in minicart after product was added to cart from product list

---

### 1.0.2
*(2016-06-30)*

#### Fixes
* Compatibility to Magento 2.1

---

### 1.0.0
*(2016-02-17)*

* Initial release
