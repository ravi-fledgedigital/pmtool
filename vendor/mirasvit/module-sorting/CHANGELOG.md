## 1.4.5
*(2026-03-05)*

#### Fixed
* Fixed an error "Undefined array key" in the debug mode

---

## 1.4.4
*(2026-02-13)*

#### Fixed
* Added MSI support for "Number of Child Products in Stock" factor

---

## 1.4.3
*(2026-02-05)*

#### Fixed
* Search relevance not working in GraphQL requests on Magento 2.4.8+

---

## 1.4.2
*(2026-01-29)*

#### Fixed
* GraphQL request compatibility with Magento 2.4.8+

---

## 1.4.1
*(2026-01-13)*

#### Fixed
* Compatibility with magento 2.4.1

---

## 1.4.0
*(2025-12-17)*

#### Features
* Added the ability to pin specified products to the category top

---

## 1.3.40
*(2025-12-16)*

#### Fixed
* Warning: Undefined variable $options (compatibility with Search Autocomplete Fast Mode)

---

## 1.3.39
*(2025-12-04)*

#### Fixed
* Console error "Uncaught TypeError: Cannot read properties of undefined (reading value)"
* Added compatibility for PageBuilder product listing sorting with SmileElasticSuite

---

## 1.3.38
*(2025-11-19)*

#### Improvements
* Added sorting options to PageBuilder product listings

---

## 1.3.37
*(2025-11-13)*

#### Features
* Search Autocomplete Fast Mode and Advanced Sorting compatibility

#### Fixed
* Issue related to Popularity factor on multistore

---

## 1.3.36
*(2025-10-13)*

#### Fixed
* Reduced the frequency of reindexing and subsequent cache invalidation via cron

---

## 1.3.35
*(2025-10-03)*

#### Fixed
* Added possibility to get NULL in FormulaFactor

---

## 1.3.34
*(2025-09-22)*

#### Fixed
* Implemented GraphQL compatibility with Smile Elasticsuite
* Refactoring

---

## 1.3.33
*(2025-08-26)*

#### Improvements
* Added the ability to use entity_id field in FormulaFactor

---

## 1.3.32
*(2025-07-29)*

#### Fixed
* AttributeFactor: increased mapping range for attributes with a large number of options

---

## 1.3.31
*(2025-07-17)*

#### Improvements
* Added new ranking factor Review Count

---

## 1.3.30
*(2025-06-19)*

#### Fixed
* Empty Bestseller factor scores for downloadable and virtual products

---

## 1.3.29
*(2025-06-17)*

#### Fixed
* Removed catalog_product_index_price from mview.xml subscriptions to prevent unnecessary full reindexing after product imports

---

## 1.3.28
*(2025-06-13)*

#### Fixed
* Discount Factor issue on multistore
* Set fallback sort order for products with equal scores in preview
* Issue related to Popularity factor
* Issue related to the Formula factor if it uses the value of another factor
* Issue related to Bestseller factor; Child In Stock factor issue on enterprise edition

---

## 1.3.27
*(2025-04-09)*

#### Improvements
* Search Ultimate compatibility

---

## 1.3.26
*(2025-03-18)*

#### Fixed
* Issue related to StockQtyFactor on MSI

---

## 1.3.25
*(2025-01-23)*

#### Fixed
* Compatibility with smileElasticsuite

---

## 1.3.24
*(2025-01-06)*

#### Fixed
* Toolbar works incorrectly if "Remember Category Pagination" is enabled
* Added error handling for product impression tracker
* Fixed StockQtyFactor error "Unknown column stock.quantity"

---

## 1.3.23
*(2024-11-04)*

#### Fixed
* Issue related to debug mode

---

## 1.3.22
*(2024-10-25)*

#### Fixed
* Livesearch compatibility

---

## 1.3.21
*(2024-06-26)*

#### Fixed
* Added hyva compatibility for js

---

## 1.3.20
*(2024-06-10)*

#### Fixed
* Elasticsearch8 compatibility

---

## 1.3.19
*(2024-05-24)*

#### Fixed
* Issues related to DiscountFactor
* StockQtyFactor on multistock

---

## 1.3.18
*(2024-05-07)*

#### Fixed
* Issue related to fallback criterion

---

## 1.3.17
*(2024-04-22)*

#### Fixed
* Adding a fallback sort order for products with the same sort metrics
* Stock status factor perfomance issue

---

## 1.3.16
*(2024-02-26)*

#### Fixed
* Compatibility with phpoffice/phpspreadsheet version > 1.19
* Attribute validation on formula factor

---

## 1.3.15
*(2023-10-19)*

#### Fixed
* default criterion relevance on search result page

---

## 1.3.14
*(2023-10-18)*

#### Fixed
* Rating sorting on multistore environment

---

## 1.3.13
*(2023-10-17)*

#### Fixed
* issue with natural sorting in alphanumeric factor

---

## 1.3.12
*(2023-10-10)*

#### Fixed
* Compatibility with amasty-elasticsearch 2.0.*

---

## 1.3.11
*(2023-07-25)*

#### Fixed
* reindex issue with enterprice edition
* adding storeId to bestseller factor query

---

## 1.3.10
*(2023-07-20)*

#### Improvements
* Smile ElasticSuite compatibility

#### Fixed
* adding storeId to bestseller factor query

---

## 1.3.9
*(2023-07-05)*

#### Fixed
* Fixed the issue with graphQL search requests

---

## 1.3.8
*(2023-06-22)*

#### Fixed
* Compatibility issue with Opensearch 2.5 on Magento Commerce
* Fixed the issue with sorting by price when catalog price rules created for particular customer groups

---

## 1.3.7
*(2023-06-12)*

#### Fixed
* Fixed Rankin Factor, issue of deprecated dynamic property

---

## 1.3.6
*(2023-05-30)*

#### Fixed
* Fixed the issue with 'default for search' criterion not used as default sorting option on search result pages

---

## 1.3.5
*(2023-05-29)*

#### Fixed
* Memory issue during reindex when Magento_LiveSearch enabled

---

## 1.3.4
*(2023-04-27)*

#### Fixed
* Translate sorting options labels (GraphQL)

---

## 1.3.3
*(2023-04-24)*

#### Fixed
* Issue with alphanumeric factor

---

## 1.3.2
*(2023-04-20)*

#### Fixed
* Issue with stock factor when the Magento_InventorySales module disabled and its tables not present in the database

---

## 1.3.1
*(2023-04-05)*

#### Improvements
* Replace default sorting options with sorting criteria (GraphQL)

---

## 1.3.0
*(2023-03-31)*

#### Fixed
* Fixed the issue with stock factor not working with Magento_Inventory module enabled

---

## 1.2.17
*(2023-03-15)*

#### Improvements
* Score calculation for the discount factor

#### Fixed
* PHP 8.2

---

## 1.2.16
*(2023-01-10)*

#### Fixed
* Fixed the issue with error while reindexing formula factor (double quotes in product data)

---

## 1.2.15
*(2022-12-29)*

#### Fixed
* Fixed the issue with error on REST API calls (Unknown column 'sorting_global' in 'order clause')

---

## 1.2.14
*(2022-12-26)*

#### Fixed
* Fixed the issue with saving formula factor (PHP8)

---

## 1.2.13
*(2022-10-20)*

#### Improvements
* Use default criterion for sort if sorting code is null (GraphQl)

#### Fixed
* Fixed the issue with sorting in graphQL
* Fixed the issue with error related to not configured alphanumeric factor

---

## 1.2.12
*(2022-09-29)*

#### Improvements
* Smile ElasticSuite compatibility

---

## 1.2.11
*(2022-09-07)*

#### Fixed
* Fixed the issue with preview (PHP8.1)

---

## 1.2.10
*(2022-09-02)*

#### Improvements
* Do not use factors that not indexed yet

---

## 1.2.9
*(2022-09-01)*

#### Improvements
* Sorting debug per product

---

## 1.2.8
*(2022-08-26)*

#### Fixed
* Sorting on checkout

---

## 1.2.7
*(2022-08-18)*

#### Fixed
* Do not sort products on checkout pages

---

## 1.2.6
*(2022-08-15)*

#### Improvements
* Properly sort by position in widgets or CMS blocks which are placed not in category pages (if products are added from specified categories)

#### Fixed
* Fixed the issue with records in the tables Ranking Factors and Sorting Criteria not displayed properly (Magento 2.4.5)

---

## 1.2.5
*(2022-08-05)*

#### Features
* New ranking factor "Number of Child Products in Stock"

---

## 1.2.4
*(2022-08-02)*

#### Fixed
* Fixed the issue with error 'Argument 2 passed to Mirasvit\Sorting\Plugin\Frontend\DebugPlugin::afterGetProductPrice must be of type string, null given' (some cases)
* Console command return value

---

## 1.2.3
*(2022-07-20)*

#### Improvements
* Compatibility with Magento LiveSearch

---

## 1.2.2
*(2022-06-20)*

#### Improvements
* remove db_schema_whitelist.json

---

## 1.2.1
*(2022-06-16)*

#### Improvements
* Ability to set 'Apply sorting for all collection' config per store/website

---

## 1.2.0
*(2022-06-07)*

#### Improvements
* Migrate to declarative schema

---

## 1.1.26
*(2022-05-27)*

#### Fixed
* php72 support

---

## 1.1.25
*(2022-05-26)*

#### Fixed
* Composer requirements

---

## 1.1.24
*(2022-05-24)*

#### Features
* New ranking factor "Formula"

#### Fixed
* Magento 2.4.4 compatibility (backend forms)

---

## 1.1.23
*(2022-05-18)*

#### Fixed
* Fixed the issue with incorrect sorting score calculation (some cases)
* Fixed the issue with error in debug mode

---

## 1.1.22
*(2022-04-28)*

#### Fixed
* Default sorting criteria if not configured in the module

---

## 1.1.21
*(2022-04-21)*

#### Fixed
* Fixed the issue with auto_increment field in index table

---

## 1.1.20
*(2022-03-30)*

#### Improvements
* statistics cleanup

---

## 1.1.19
*(2022-03-29)*

#### Fixed
* Issue with tracking criterion popularity

---

## 1.1.18
*(2022-03-17)*

#### Fixed
* Minimal price in debug data

---

## 1.1.17
*(2022-03-10)*

#### Fixed
* Fixed the issue with data in debug table
* Fixed the issue with GET graphQL requests

---

## 1.1.16
*(2022-02-18)*

#### Fixed
* Fixed the issue with wrong criteria processing in GraphQL
* Profit Factor "Division by Zero" indexing issue

---

## 1.1.15
*(2022-01-04)*

#### Improvements
* Added GraphQL sorting compatibility

#### Fixed
* Stock Status ranking factor indexing issue with a single stock
* Correct ranking factor data retrieving

---

## 1.1.14
*(2021-11-23)*

#### Improvements
* New Ranking Factor: Backorders
* New Feature: Popularity (number of usages) of sorting criterias

#### Fixed
* Amasty Elasticsearch compatibility
* Relevance sort dirrection issue after add position criteria

---

## 1.1.13
*(2021-10-29)*

#### Fixed
* Stock status ranking factor issue
* Fixed the issue with sorting by position

---

## 1.1.12
*(2021-09-20)*

#### Fixed
* Out of stock ranking factor missing products
* Missing scores while indexing
---

## 1.1.11
*(2021-08-13)*

#### Improvements
* Alphanumeric sorting on multistore environment

---

## 1.1.10
*(2021-08-13)*

#### Improvements
* Added alphanumeric ranking factor
* Added "New From Date" to date ranking factor

---

## 1.1.9
*(2021-08-06)*

#### Fixed
* SQL error with unknown sorting direction

---

## 1.1.8
*(2021-07-20)*

#### Fixed
* Error on attribute set API request
* Improve StockQtyFactor to apply child products qty

---

## 1.1.7
*(2021-05-26)*

#### Fixed
* Criteria reverse order

---
## 1.1.6
*(2021-04-14)*

#### Fixed
* fixed the issue with 'relevance' code for sorting criteria
* indexing issue

---

## 1.1.4
*(2021-04-05)*

#### Fixed
* Sorting by store view (sql)

---

## 1.1.3
*(2021-04-01)*

#### Fixed
* option getlabel issue

---

## 1.1.2
*(2021-03-04)*

#### Improvements
* Ability to define default sort order using native Catalog Configuration options

---

## 1.1.1
*(2021-01-27)*

#### Improvements
* Default sort direction

#### Fixed
* Issue with selecting image attribute (non-default entity type id)

---

## 1.1.0
*(2020-12-30)*

#### Improvements
* Reindex sorting indexes after product change

#### Fixed
* Fixed the issue with the error: Deprecated Functionality: Array and string offset access syntax with curly braces is deprecated


---

## 1.0.58
*(2020-12-22)*

#### Fixed
* Issue with REAS API (Magento 2.3)

---

## 1.0.57
*(2020-12-07)*

#### Improvements
* Set direction for widgets

---

## 1.0.56
*(2020-12-04)*

#### Fixed
* Issue with applying sorting on custom collections
* Sorting for REST API

---

## 1.0.54
*(2020-11-27)*

#### Improvements
* Developer Mode flag

#### Fixed
* Issue with types

---

## 1.0.53
*(2020-11-24)*

#### Fixed
* Compatibility with Olegnax_LayeredNavigation
* Minor fixes

---

## 1.0.51
*(2020-11-23)*

#### Improvements
* Removed compatibility with Magento 2.1, 2.2 (PHP 7.1+)
* Improved module performance 

---

## 1.0.50
*(2020-10-12)*

#### Improvements
* Quantity in the sorting preview

---

## 1.0.49
*(2020-10-08)*

#### Features
* New ranking factor "Stock Quantity"

---

## 1.0.48
*(2020-09-23)*

#### Features
* Pre-ready sorting criteria

#### Fixed
* Score on preview page

---

## 1.0.47
*(2020-09-09)*

#### Improvements
* Ranking Factor Precision

---

## 1.0.46
*(2020-08-19)*
 
#### Refactor
* Improved module structure

---

## 1.0.45
*(2020-08-11)*

#### Fixed
* Compatibility issue with ElasticSearch

---

## 1.0.44
*(2020-07-29)*

#### Improvements
* Support of Magento 2.4

---

## 1.0.43
*(2020-06-30)*

#### Fixed
* Issue with default sorting not applied on brand pages (Mirasvit_Brand module).

---

## 1.0.42
*(2020-05-15)*

#### Fixed
* Issue with sorting by position attribute when Use Flat Catalog Product enabled
* Issue with sorting collection at the Advanced Product Feed module

#### Features
* NO_SORT flag for product collections in custom blocks.
* Ability to disable sorting for custom blocks in the configurations.

---


## 1.0.41
*(2020-04-14)*

#### Fixed
* Issue with sorting by attribute with the type boolean/dropdown ("You cannot define a correlation name '...' more than once").
* Issue with inactive criteria used for sorting in custom blocks

---


## 1.0.40
*(2020-03-27)*

#### Fixed
* Issue with widgets with random products order (Cannot use object of type Zend_Db_Expr as array. Affects only 1.0.39)

---

## 1.0.39
*(2020-03-26)*

#### Fixed
* Issue with sorting in widgets when elasticsearch is used

---

## 1.0.38
*(2020-03-23)*

#### Fixed
* Issue with default Magento order for attributes (duplicated fields in ORDER BY clause)

---


## 1.0.37
*(2020-03-16)*

#### Features
* New ranking factor "New products" based on "Set product as new from ... to ..." attribute

---


## 1.0.36
*(2020-02-28)*

#### Fixed
* Error in some cases. Cannot use object of type Zend_Db_Expr as array. Affects only 1.0.35

---

## 1.0.35
*(2020-02-27)*

#### Improvements
* Discount ranking factor calculation for configurable/bundle/grouped products

#### Fixed
* Sorting by name (only M2.3.4)
* subconditions for sort by attribute

---


## 1.0.34
*(2020-02-21)*

#### Fixed
* Apply sorting only on frontend

---

### 1.0.33
*(2020-02-12)* 

* Improve debug widget
* Added custom sorting fix
* Fixed calculation of price factor (for complex product types)

----

## 1.0.32
*(2020-02-06)*

#### Fixed
* Default sorting field
* Missing positionOrderApplied variable when global sorting applied
* Wrong product position when position sort order applied
* Bestseller score factor indexing issue
* Unable to apply sort by name

---

## 1.0.29
*(2019-12-30)*

#### Improvements
* Code refactoring

#### Fixed
*  Sort by name issue

---

## 1.0.28
*(2019-11-25)*

#### Fixed
* Elasticsearch compatibility

---

## 1.0.27
*(2019-11-21)*

#### Improvements
* Admin styles

---

## 1.0.26
*(2019-11-07)*

#### Fixed
* Disable sorting of products collection in CLI mode (prevent possible indexation issue)
* Issue during indexation Discount Ranking Factor
* Unexpected indexer dependency

---

## 1.0.25
*(2019-10-10)*

#### Fixed
* Compatibility with native ES

---

## 1.0.24
*(2019-09-02)*

#### Fixed
* Compatibility with EE Elasticsearch

---

## 1.0.23
*(2019-07-25)*

#### Improvements
* Debug mode

#### Fixed
* Possible issue with second join (temporary table)
* Popularity factor

---

## 1.0.22
*(2019-05-20)*

#### Fixed
* Issue with sorting if criteria/factor is empty
* Issue with applying custom sorting for category

---

## 1.0.21
*(2019-05-16)*

#### Improvements
* integration with Autocomplete
* Rating Factor. Use also number of ratings

---

## 1.0.20
*(2019-04-17)*

#### Improvements
* Conditions column in criteria listing
* Enabled Inline Editor
* Search results sorting

---

## 1.0.19
*(2019-04-10)*

#### Improvements
* Inline debug interface (&debug=sorting)

---

## 1.0.18
*(2019-04-08)*

#### Fixed
* Compatibility issue with Magento Enterprise

---

## 1.0.17
*(2019-04-01)*

#### Improvements
* Ability to use native "Position" as sorting attribute

---

## 1.0.16
*(2019-03-27)*

#### Improvements
* Ability to defined limits

#### Fixed
* Error during indexation of sorting index if an attribute is not set in a rating factor
* By default sort direction is set to the direction of a default criterion, even when custom direction used

---

## 1.0.15
*(2019-03-19)*

#### Fixed
* Compatibility with Magento 2.1.x

---

## 1.0.14
*(2019-03-18)*

#### Fixed
* Attribute sort direction does not change
* Saving sorting code

---

## 1.0.13
*(2019-03-11)*

#### Improvements
* Popularity Factor
* Discount Factor
* Preview interface

---

## 1.0.12
*(2019-03-07)*

#### Improvements
* Ability to use sorting for catalog widget "Catalog Products List"

#### Fixed
* Sort direction does not change

---

## 1.0.11
*(2019-03-04)*

#### Improvements
* Changed sorting interface

---

## 1.0.10
*(2019-02-22)*

#### Improvements
* Add module translation file mirasvit/module-navigation[#79]()

---

## 1.0.9
*(2019-02-11)*

#### Fixed
* System settings page not loaded after adding a new attribute to sorting

---

## 1.0.8
*(2019-01-10)*

#### Fixed
* Conflict with Mirasvit Search module: searching products fails with error (since 1.0.7)

---

## 1.0.7
*(2019-01-09)*

#### Features
* Push 'out of stock' products to the end of a list

#### Documentation
* Info about new settings

---

## 1.0.7
*(2019-01-08)*

#### Features
* Push 'out of stock' products to the end of a list 

---

## 1.0.6
*(2018-12-27)*

#### Fixed
* Compatibility with Magento 2.1

---

## 1.0.5
*(2018-12-11)*

#### Fixed
* Error during reindex mst_sorting by cron

---

## 1.0.4
*(2018-12-07)*

#### Features
* Show configurable products at top of the list #3

---

## 1.0.3
*(2018-12-06)*

#### Fixed
* Error during reindexing due to discount criterion

---

## 1.0.2
*(2018-12-06)*

#### Fixed
* Error during reindex sorting index

---

## 1.0.1
*(2018-12-05)*

#### Improvements
* Compatibility with M2.1

#### Documentation
* Added module docs
