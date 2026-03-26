## 1.3.57
*(2026-03-03)*

#### Fixed
* Added a validation for grouped options during the rewrite actualize process by the "mirasvit:seo-filter:rewrites  --actualize" command

---

## 1.3.56
*(2026-01-21)*

#### Fixed
* TypeError: preg_match(): Argument [#2]() ($subject) must be of type string, int given in FilterParamsService.php
* Duplicate alias suffix generation when saving grouped options

---

## 1.3.55
*(2025-12-29)*

#### Fixed
* Fixed an issue with swatch selection on the product listing when a swatch filter is applied

---

## 1.3.54
*(2025-12-17)*

#### Fixed
* Optimized memory usage for large catalogs
* Numeric filter aliases being misinterpreted as price ranges with dash separator

---

## 1.3.53
*(2025-12-15)*

#### Improvements
* Get URL path

---

## 1.3.52
*(2025-11-26)*

#### Fixed
* Issue related to the breadcrumbs URL for the filtered category page

---

## 1.3.51
*(2025-11-11)*

#### Fixed
* Ensures search_term is preserved when filters are applied on the Landing Page

---

## 1.3.50
*(2025-10-15)*

#### Fixed
* Page not found when using short slash format and filter prefix at the same time

---

## 1.3.49
*(2025-09-19)*

#### Fixed
* Prevented alias creation for attributes that are not filterable
* Optimize alias fetching by caching null responses
* SeoFilter alias combination limitations

---

## 1.3.48
*(2025-09-02)*

#### Fixed
* Issue related to short slash format

---

## 1.3.47
*(2025-08-29)*

#### Fixed
* Issue related to the canonical URL generation in Advanced SEO Suite

---

## 1.3.46
*(2025-08-26)*

#### Fixed
* Sanitized option field in mst_seo_filter_rewrite to prevent injections

---

## 1.3.45
*(2025-08-15)*

#### Fixed
* The "Enable SEO URL" setting cannot be changed on the product attribute edit page

---

## 1.3.44
*(2025-08-12)*

#### Fixed
* Added SEO friendly aliases for OnSale filter

---

## 1.3.43
*(2025-07-17)*

#### Improvements
* Compatibility with Mirasvit_LandingPage UrlSuffix

---

## 1.3.42
*(2025-05-06)*

#### Improvements
* Added new option for console command (--actualize) - removing rewrites for non-existent options

---

## 1.3.41
*(2025-04-17)*

#### Improvements
* Adde new short format "category/option1_option2_option3"

---

## 1.3.40
*(2025-04-14)*

#### Fixed
* PHP8.4 compatibility

---

## 1.3.39
*(2025-04-09)*

#### Fixed
* Class "Mirasvit\LayeredNavigation\Repository\GroupRepository" does not exist

---

## 1.3.38
*(2025-04-08)*

#### Improvements
* Added ability to modify Grouped Option seo frienfly url

---

## 1.3.37
*(2025-04-03)*

#### Fixed
* 404 error if custom suffix is used on the Brand Page

---

## 1.3.36
*(2025-03-31)*

#### Improvements
* Added new formats for seo filters

#### Fixed
* Fixed an error that occurs when Mirasvit_LayeredNavigation is turned off

---

## 1.3.35
*(2025-03-11)*

#### Fixed
* Incorrect filters order in canonical URL
* Show warning if attribute alias already exists

---

## 1.3.34
*(2025-02-17)*

#### Fixed
* Incorrect url when using text atribute with slider display mode
* Added compatibility with amasty_quickorder
* Removing "ь" symbol when generating aliases

---

## 1.3.33
*(2025-01-23)*

#### Fixed
* Compatibility with Mirasvit Landing Page

---

## 1.3.32
*(2025-01-21)*

#### Fixed
* Issue related to Landing page url matching
* Removing apostrophe symbol when generating alias

---

## 1.3.31
*(2025-01-14)*

#### Features
* Added ability to enable or disable SEO filter url for specific attribute

---

## 1.3.30
*(2024-12-27)*

#### Improvements
* Settings menu refactoring

#### Fixed
* Issue related to brand page filtering
* Display attribute code in alias duplication warning message

---

## 1.3.29
*(2024-12-19)*

#### Fixed
* Issue related to brand page filtering
* Display attribute code in alias duplication warning message

---

## 1.3.28
*(2024-11-26)*

#### Fixed
* Issue related to custom price filter alias

---

## 1.3.27
*(2024-11-22)*

#### Fixed
* Fixed checking for alias existence when saving an attribute

---

## 1.3.26
*(2024-11-05)*

#### Features
* Added compatibility with the Mirasvit_Seo module (displaying alternative URLs with SEO-friendly filters)

---

## 1.3.25
*(2024-10-07)*

#### Fixed
* Page not found when dash separator is used and one alias contains part of another alias

---

## 1.3.24
*(2024-09-26)*

#### Fixed
* Issue related to alias autogeneration per store view
* Issue related to brand url matching process

---

## 1.3.23
*(2024-09-12)*

#### Fixed
* Issue related to landing page
* 404 error when category url with applied filter option contains landing page url

---

## 1.3.22
*(2024-06-12)*

#### Improvements
* Added hyphen as a separator in complex filter names

#### Fixed
* Price filter issue

---

## 1.3.21
*(2024-05-29)*

#### Fixed
* Inactive Landing Page is visible

---

## 1.3.20
*(2024-04-26)*

#### Fixed
* Issue related to url prefix

---

## 1.3.19
*(2024-04-15)*

#### Fixed
* Landing page url contains filter rewrite

---

## 1.3.18
*(2024-03-20)*

#### Fixed
* Adjustments for fetching rewrite URLs with filters
* Issue with rewrite alias containing dash on Brand page

---

## 1.3.17
*(2024-02-26)*

#### Fixed
* Avoiding execution of some useless sql queries

---

## 1.3.16
*(2024-02-21)*

#### Fixed
* Routing process optimization

---

## 1.3.15
*(2024-02-20)*

#### Fixed
* Optimization of attribute rewrite getting process

---

## 1.3.14
*(2024-01-29)*

#### Features
* Compatibility with Mirasvit_LandingPage

---

## 1.3.13
*(2024-01-26)*

#### Fixed
* Excluding unnecessary queries when getting rewrites
* Fixed issue when category rewrite ends with slash

---

## 1.3.12
*(2024-01-16)*

#### Fixed
* Rewrite table size optimization
* Category search compatibility

---

## 1.3.11
*(2024-01-12)*

#### Fixed
* Rewrite is not set to cache if attribute has slider display_mode

---

## 1.3.10
*(2024-01-12)*

#### Fixed
* Issue related with cache key

---

## 1.3.9
*(2023-11-22)*

#### Fixed
* Issue related with excluding some symbols from alias

---

## 1.3.8
*(2023-11-20)*

#### Fixed
* Issue with slash suffix
* Automatic generation of a rewrite if one already exists

---

## 1.3.7
*(2023-11-02)*

#### Fixed
* Issue rewrite already exists
* Adding ability to have categories with the same name on multistore

---

## 1.3.6
*(2023-10-12)*

#### Fixed
* Url slash suffix issue

---

## 1.3.5
*(2023-07-27)*

#### Improvements
* Small improvement

---

## 1.3.4
*(2023-06-19)*

#### Fixed
* adding custom attribute to exception

---

## 1.3.3
*(2023-05-22)*

#### Fixed
* Issue with unexpected responses for product view pages' requests (product URL key similar to filters aliases)

---

## 1.3.2
*(2023-04-04)*

#### Fixed
* Fixed the issue with custom attributes when attribute and its option have the same aliases

---

## 1.3.1
*(2023-03-31)*

#### Fixed
* Fixed the issue with additional filters provided by Mirasvit_LayeredNavigation (URL Format: Long)

---

## 1.3.0
*(2023-03-22)*

#### Improvements
* Console command for generating/removing filters rewrites
* Category filter user-friendly URLs

---

## 1.2.10
*(2022-12-20)*

#### Fixed
* Fixed the issue with unnecessary suffixes in attribute aliases

---

## 1.2.9
*(2022-11-01)*

#### Fixed
* Fixed the issue with filtering products in multistore

---

## 1.2.8
*(2022-10-21)*

#### Improvements
* Clear link for all options per attribute (Mirasvit_LayeredNavigation)

---

## 1.2.7
*(2022-10-20)*

#### Improvements
* friendly URLs for Product Attribute Linking (Mirasvit_LayeredNavigation)

---

## 1.2.6
*(2022-09-29)*

#### Fixed
* Fixed the issue with SeoFilters not working when categories don't have rewrites

---

## 1.2.5
*(2022-06-20)*

#### Improvements
* remove db_schema_whitelist.json

---

## 1.2.4
*(2022-06-13)*

#### Fixed
* Fixed the issue with unable to save custom attribute aliases (multistore)
* Fixed the issue with brand pages (Mirasvit_Brand) when some options' aliases are equal to some brands' pages URL keys

---

## 1.2.3
*(2022-06-01)*

#### Fixed
* Aliases for additional Sale and New filters

---

## 1.2.2
*(2022-05-27)*

#### Fixed
* Fixed the issue with unable to save custom aliases (multistore)

---

## 1.2.1
*(2022-05-24)*

#### Fixed
* Performance issue (router)

---

## 1.2.0
*(2022-05-12)*

#### Improvements
* Switch to declarative DB schema

---

## 1.1.24
*(2022-04-11)*

#### Fixed
* Fixed issues with filters on Brand View pages (Mirasvit_Brand module)

---

## 1.1.23
*(2022-04-06)*

#### Fixed
* Fixed the issue with changing attribute alias after each attribute save

---

## 1.1.22
*(2022-02-23)*

#### Fixed
* Fixed the issue with 404 page when filters applied (same category paths but different ids for different stores in rewrites)

---

## 1.1.21
*(2022-02-18)*

#### Fixed
* Fixed the issue with slider filter and float values

---

## 1.1.20
*(2022-02-11)*

#### Fixed
* Fixed the issue with error 'Call to a member function getRequestPath() on null'
* Fixed the issue with error 'strpos(): Empty needle'

---

## 1.1.19
*(2022-02-02)*

#### Fixed
* Fixed the issue with 404 page on applied filters when filter alias include category path
* Fixed the issue with empty SEO filter rewrites for options in arabic

---

## 1.1.18
*(2022-01-18)*

#### Fixed
* Fixed the issue with 404 pages when filters applied on subcategory pages (some cases)

---

## 1.1.17
*(2022-01-04)*

#### Fixed
* Incorrect category base URL in filters issue (some cases)
* Fixed the issue with filter when attribute code is the same as category URL key (long URL)
* Fixed the issue with price slider (long URL)
* Fixed the issue with empty option aliases for attribute options with empty labels
* Matching the wrong category brings 404 error (5 weeks ago)

---

## 1.1.16
*(2021-11-22)*

#### Fixed
* Fixed the issue with multiselect and long URL format
* Fixed the issue with unable to remove selected filters from the filter clear block with Group options by attribute format "[x] Attribute: Option, Option" (Layered Navigation)

---

## 1.1.15
*(2021-11-11)*

#### Fixed
* Not allow saving custom option alias if identical alias already exists

---

## 1.1.14
*(2021-10-28)*

#### Fixed
* Fixed the issue with custom aliases with more then one '-' symbol
* Fixed the issue with slider filter

---

## 1.1.13
*(2021-10-15)*

#### Fixed
* Fixed the issue with custom aliases contains "-" symbol (partly duplicated aliases)

---

## 1.1.12
*(2021-08-31)*

#### Fixed
* Price slider filter redirects to 404

---

## 1.1.11
*(2021-08-19)*

#### Improvements
* Optional multiselect per attribute

---

## 1.1.10
*(2021-07-20)*

#### Fixed
* SEO filter prefix leads to 404
* Decimal filters (slider, from-to) with long seo rewrite issue

---

## 1.1.9
*(2021-07-06)*

#### Fixed
* Issue with price filter

---

## 1.1.8
*(2021-06-23)*

#### Fixed
* 404 on incorrect URLs (typos in category path or filters)
* Fixed the issue with Layered Navigation additional filters

---

## 1.1.7
*(2021-05-31)*

#### Features
* friendly URLs for Layered Navigation Grouped Options feature

#### Fixed
* Fixed the issue with custom alias with '-' symbol

---

## 1.1.6
*(2021-05-13)*

#### Fixed
* Match filters with "-"

---

## 1.1.5
*(2021-04-23)*

#### Fixed
* Issue with brand page urls
* Issue with filter by category

---

## 1.1.4
*(2021-04-21)*

#### Improve
* URL mode (multi-store)

---

## 1.1.3
*(2021-04-13)*

#### Fixed
* Filter by price. Notice: Array to string conversion
* Brand page getClearUrl issue

---

## 1.1.2
*(2021-04-06)*

#### Fixed
* Issue with loading url rewrites

---

## 1.1.1

*(2021-03-23)*

#### Fixed

* Issue with disabled category
* Remove pagination from friendly filter URL

---

## 1.1.0

*(2021-03-22)*

#### Improve

* New URL mode for SEO friendly filters (attr1/opt1-opt2/attr2/opt3-opt4.html)
* Removed compatibility for Magento 2.1, 2.2

---

## 1.0.29

*(2020-12-01)*

#### Fixed

* Issue with brand url

---

## 1.0.28

*(2020-11-19)*

#### Fixed

* unable to apply filter on brand page

---

## 1.0.27

*(2020-09-08)*

#### Fixed

* filter urls

---

## 1.0.26

*(2020-09-04)*

#### Features

* Support applying mode

--

## 1.0.25

*(2020-08-20)*

#### Refactor

* Improved module structure

---

## 1.0.24

*(2020-08-12)*

#### Features

* Seo-friendly urls for brand and all products pages

#### Fixed

* isMultiselectEnabled returns wrong value
* call to missing build_query function ([#21]())
* second click should clear filter

---

## 1.0.23

*(2020-07-29)*

#### Improvements

* Compatibility with Magento 2.4

---

## 1.0.22

*(2020-03-23)*

#### Fixed

* Issue with category filter url after apply other filters

---

## 1.0.21

*(2020-03-12)*

#### Fixed

* Use GET for category url (in the filters), if multi select is enabled

---

## 1.0.20

*(2020-03-10)*

#### Fixed

* Extra rewrite for weight filter (-)

---

## 1.0.19

*(2020-03-05)*

#### Improved

* Removing special symbols from friendly url: ™℠®©

---

## 1.0.18

*(2020-02-20)*

#### Features

* custom separator for SEO filters

#### Fixed

* Issue with multi filter
* unable to show and apply nested filter (price)

---

## 1.0.14

*(2019-10-28)*

#### Improved

* Code refactoring

#### Fixed

* error "include_once" statement detected

---

## 1.0.13

*(2019-08-09)*

#### Fixed

* Minor refactoring for pass eqp tests

---

## 1.0.12

*(2019-06-04)*

#### Fixed

* Empty url for Yes/No values
* Pagination issue with FishPig WordPress module
* Possible issue with request_path = .html in url_rewrite table

---

## 1.0.11

*(2018-11-29)*

#### Fixed

* Compatibility with Magento 2.3

---

## 1.0.10

*(2018-11-15)*

#### Fixed

* Issue with "The attribute model is not defined"

---

## 1.0.8

*(2018-10-24)*

#### Fixed

* Properly retrieve filter string
* Issue with rewrite attribute url, if our module is disabled

---

## 1.0.7

*(2018-08-17)*

#### Fixed

* Fixed incorrect urls if category with the same url exist

---

## 1.0.6

*(2018-07-17)*

#### Fixed

* Delete incorrect index

---

## 1.0.5

*(2018-07-16)*

#### Fixed

* Fixed an error "SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails..."

---

## 1.0.4

*(2018-05-29)*

#### Fixed

* Fixed an error: "Notice: Undefined property: Mirasvit\SeoFilter\Plugin\SwatchAttributeFilterMultiselectPlugin::$objectManager"

---

## 1.0.3

*(2018-05-23)*

#### Fixed

* Fix compilation error "Class Mirasvit\LayeredNavigation\Api\Service\SeoFilterUrlServiceInterface does not exist"

---

## 1.0.2

*(2018-05-23)*

#### Fixed

* Fixed incorrect urls for additional filters in Layered Navigation

---

## 1.0.1

*(2018-05-17)*

#### Fixed

* Issue with stock status

---

## 1.0.0

*(2018-04-03)*

* Initial release
