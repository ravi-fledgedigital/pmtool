## 2.9.34
*(2026-03-13)*

#### Fixed
* Clear Filter Block missing in Ajax mode when only horizontal filters are configured
* Fixed "ValueError: min(): Argument [#1]() ($value) must contain at least one element"
* Fixed an issue related to the brand module when the magento setting "Use Flat Catalog Product" is enabled

---

## 2.9.33
*(2026-03-05)*

#### Improvements
* Added new option "Label (naturally)" to the "Sort Options by" attribute setting

---

## 2.9.32
*(2026-03-03)*

#### Fixed
* AddBrandNamePlugin::afterProductAttribute(): Argument [#2]() ($result) must be of type string, null given

---

## 2.9.31
*(2026-02-26)*

#### Improvements
* Added the ability to add brand name to the product name on the product listing

#### Fixed
* Quick navigation settings did not work at the store_view level

---

## 2.9.30
*(2026-02-18)*

#### Fixed
* Fixed "Column min_price in where clause is ambiguous" error on category pages when price sorting (Mirasvit_Sorting module) and "Use price including tax" setting are both enabled
* Fixed duplicate item ID error on Grouped Options grid page

---

## 2.9.29
*(2026-02-11)*

#### Fixed
* Incorrect behavior of grouped options when attribute setting "Use in Layered Navigation" is set to "Filterable (no results)"

---

## 2.9.28
*(2026-01-22)*

#### Fixed
* Brand banner is not shown

---

## 2.9.27
*(2026-01-16)*

#### Improvements
* Added the ability to show brand name as a link before the product title on the product page

#### Fixed
* Issue related to "Show Not Configured Brands" setting
* Added validation for "Group Code" field in Grouped Options

---

## 2.9.26
*(2025-12-29)*

#### Fixed
* Activate swatches on product listing when SeoFilter module is enabled

---

## 2.9.25
*(2025-12-17)*

#### Fixed
* Improved performance by adding in-memory caching to reduce redundant database queries
* Performance issue with horizontal bar filter position check causing repeated database queries on category pages with many filterable attributes
* Grouped option selection on product listing for swatch attribute

---

## 2.9.24
*(2025-12-01)*

#### Fixed
* Toolbar actions issue when "Remember Category Pagination" setting is enabled

---

## 2.9.23
*(2025-11-27)*

#### Fixed
* Pagination with full page reload when "Pagination Style" is set to "Disabled"

---

## 2.9.22
*(2025-11-25)*

#### Fixed
* Increased category facet aggregation limit to 3000

---

## 2.9.21
*(2025-11-18)*

#### Fixed
* Issue related to "Rating Filter" when ajax mode is enabled
* Fixed "number_format_exception" elasticsearch exception when entering a wrong filter value

---

## 2.9.20
*(2025-11-11)*

#### Fixed
* Issue related to the attribute config setting "Categories Visibility Mode"

---

## 2.9.19
*(2025-11-05)*

#### Improvements
* Added store-level support for Brand Page URL key
* Added a setting to control the minimum number of brand products required to display the "More From Brand" block
* Replaced the standard multi-select with a jstree category tree for the "Categories" field in the attribute config editing form

#### Fixed
* CSP compatibility
* Fixed filtering by attribute and title fields in the grouped options grid

---

## 2.9.18
*(2025-10-16)*

#### Fixed
* Issue related to the apply button position
* Filters Manager: scroll to form header when edit button is clicked
* Filters Manager: improved attribute position saving performance

---

## 2.9.17
*(2025-10-02)*

#### Fixed
* Incorrect admin ajax url generation for "Filters Manager" feature when "Add Secret Key to URLs" setting is enabled

---

## 2.9.16
*(2025-09-30)*

#### Features
* New feature - Filters Manager

---

## 2.9.15
*(2025-09-26)*

#### Fixed
* Optimized brand logo retrieval by using getAttributeRawValue() instead of $product->load() for better performance

---

## 2.9.14
*(2025-09-22)*

#### Improvements
* Added page title to the All Brands page

#### Fixed
* Issue related to the Brand Page edit form
* Corrected label display logic for "Apply Button", ensured singular/plural labels show based on product count
* Issue related to "Load More" button on magento 2.4.7+ when "Remember Category Pagination" setting is enabled

---

## 2.9.13
*(2025-09-16)*

#### Fixed
* Displaying "Apply Button" in instantly mode if the "Apply Filters by Button Click on Mobile" setting is enabled
* Applied SEO URL support for all numeric sliders

---

## 2.9.12
*(2025-09-08)*

#### Fixed
* Improved meta title fallback logic for brand pages
* Fixed sizeToggle text for proper translation and click response

---

## 2.9.11
*(2025-08-21)*

#### Fixed
* Compatibility with Mirasvit_CacheWarmer "Forbid cache flushing" setting to ensure data consistency during AJAX pagination

---

## 2.9.10
*(2025-08-19)*

#### Improvements
* Added mass action delete functionality to the Brand Pages grid
* Added "Product Count" column to the Brand Pages admin grid

#### Fixed
* Increased brand attribute aggregation size from 500 to 3000 in search facets
* WYSIWYG field labels were missing on brand edit page when Page Builder was globally disabled

---

## 2.9.9
*(2025-08-12)*

#### Fixed
* Changed brand_short_description form element from textarea to WYSIWYG editor

---

## 2.9.8
*(2025-08-11)*

#### Improvements
* Add Store Switcher support for brand edit page

---

## 2.9.7
*(2025-08-04)*

#### Fixed
* Error on product page if brand attribute is not selected

---

## 2.9.6
*(2025-08-04)*

#### Improvements
* Added ability to hide the "No" option in the "On Sale" filter

---

## 2.9.5
*(2025-07-30)*

#### Improvements
* Added swipe support for quick navigation on mobile devices

#### Fixed
* Compatibility issue with WYSIWYG editor when Page Builder is disabled in Magento settings
* Optimized brand loading for retrieving brand logos on product listing pages
* An error may appear in the DecimalFilter.php if the SEO filter module is disabled
* Removed rounding of boundary values for the custom numeric filter in range mode

---

## 2.9.4
*(2025-07-16)*

#### Fixed
* Improved the performance of the Mirasvit_QuickNavigation module
* Hide the apply button when all filters are collapsed

---

## 2.9.3
*(2025-07-03)*

#### Improvements
* Different page scroll modes after applying filters

---

## 2.9.2
*(2025-06-24)*

#### Improvements
* Added Value Template for decimal attributes in From-To display mode

---

## 2.9.1
*(2025-06-19)*

#### Improvements
* Make search box display in filters conditional by option count

---

## 2.9.0
*(2025-06-17)*

#### Improvements
* Improve WCAG 2.2 AA compliance for navigation module
* Added option to display price slider values including taxes based on admin configuration

---

## 2.8.20
*(2025-05-29)*

#### Fixed
* Fixed brand logo preview on brands grid
* Wrong sorting for grouped options
* The remove image button for the option in the attribute settings is inactive if it is overlapped by the option image.
* LayeredNavigationLiveSearch refactoring

---

## 2.8.19
*(2025-05-14)*

#### Fixed
* Some filter options are not visible when "Hide Inactive Filter Options" setting is active

---

## 2.8.18
*(2025-05-06)*

#### Fixed
* Filter alphabetical index compatibility with Hebrew

---

## 2.8.17
*(2025-05-02)*

#### Fixed
* Setting "Show Brand Description" is not visible in brand settings

---

## 2.8.16
*(2025-04-30)*

#### Fixed
* Issue related to the routing of brand pages

---

## 2.8.15
*(2025-04-29)*

#### Improvements
* Added ability to use category-based links in the category filter

---

## 2.8.14
*(2025-04-25)*

#### Improvements
* Added the ability to use extra filters in GraphQl

#### Fixed
* Filter expander compatibility with Blank related themes

---

## 2.8.13
*(2025-04-16)*

#### Fixed
* Disabled attributeConfig caching on attribute save

---

## 2.8.12
*(2025-04-10)*

#### Fixed
* PHP8.4 compatibility

---

## 2.8.11
*(2025-04-08)*

#### Improvements
* Added ability to modify Grouped Option seo friendly url

---

## 2.8.10
*(2025-04-07)*

#### Improvements
* Added ability to hide clear button with active options count for applied filters

#### Fixed
* Price slider issue on arabic locale

---

## 2.8.9
*(2025-03-31)*

#### Improvements
* Added compatibility with new seo-filter formats

#### Fixed
* Added language function to OnSale and NewFilter options

---

## 2.8.8
*(2025-03-05)*

#### Fixed
* All Brands Page setting is hidden on store view level

---

## 2.8.7
*(2025-02-27)*

#### Fixed
* Missed "Filter Appearance" setting on store view level

---

## 2.8.6
*(2025-02-27)*

#### Fixed
* Issue related to search filter

---

## 2.8.5
*(2025-02-25)*

#### Fixed
* Compatibility with Landing Page Redirect feature

---

## 2.8.4
*(2025-02-07)*

#### Fixed
* Livesearch compatibility with Price Slider
* Compatibility with Klevu_FrontendSearch module

---

## 2.8.3
*(2025-01-27)*

#### Improvements
* Added the abiblity to set rel=nofollow to links with multiple filters applied

---

## 2.8.2
*(2025-01-14)*

#### Fixed
* Issue related to price-slider seo-friendly url
* Added file uploader compatibility on magento 2.4.7

---

## 2.8.1
*(2025-01-06)*

#### Fixed
* Issue related to changing of product list mode in magento-2.4.7

---

## 2.8.0
*(2024-12-27)*

#### Improvements
* Refactoring of the settings menu

---

## 2.7.39
*(2024-12-24)*

#### Fixed
* Issue related to brand canonical link if "Brand Page URL" is set to "Long url"

---

## 2.7.38
*(2024-12-23)*

#### Fixed
* Product list mode could not be changed if "Remember Category Pagination" is enabled

---

## 2.7.37
*(2024-12-17)*

#### Fixed
* Added limit for filter combinations in quick navigation sequence
* Horizontal filters are not shown if category "Anchor" is set to "No"

---

## 2.7.36
*(2024-12-13)*

#### Improvements
* Added ability to select attributes for product attribute linking feature

#### Fixed
* Issue related to horizontal filters in confirmation mode

---

## 2.7.35
*(2024-12-05)*

#### Fixed
* Issue related to Brand pages on magento 2.4.3

---

## 2.7.34
*(2024-11-26)*

#### Fixed
* Issue related to custom price filter alias

---

## 2.7.33
*(2024-11-21)*

#### Improvements
* Added possibility to sort filter options by product counts

#### Fixed
* Fixed the issue with filtering brand pages by ID in the Brand Pages grid

---

## 2.7.32
*(2024-11-13)*

#### Features
* Brands menu item modes - link and popup

#### Fixed
* The issue with incorrect URLs when switching store views (Brands)

---

## 2.7.31
*(2024-11-04)*

#### Improvements
* Removed the "m-brand-seo-compatibility" plugin

---

## 2.7.30
*(2024-10-30)*

#### Fixed
* Performance improvement of quicknavigation queries
* Added compatibility with Amasty Seo module
* Issue related to Brand page routing

---

## 2.7.29
*(2024-10-22)*

#### Fixed
* Issue related to alphabetical index with cyrillic symbols
* Lipscore compatibility

---

## 2.7.28
*(2024-10-02)*

#### Fixed
* Added rel attributes to clear filter links

---

## 2.7.27
*(2024-09-30)*

#### Improvements
* Cleanup old sequences (used filter's combinations) by cron
* Added seo description field to brand page

#### Fixed
* Issue related to input checkbox

---

## 2.7.26
*(2024-09-25)*

#### Improvements
* Show option count if filter label is longer than sidebar width
* Added ability to precalculate filter options counts in by_button_click mode

#### Fixed
* Issue related to livesearch synchronization

---

## 2.7.25
*(2024-09-19)*

#### Fixed
* Issue related to Livesearch compatibility

---

## 2.7.24
*(2024-09-19)*

#### Fixed
* Livesearch compatibility
* Scroll-bar duplication in infinite scroll mode

---

## 2.7.23
*(2024-09-13)*

#### Features
* Sticky Sidebar

#### Fixed
* Issue related to brand url suffix
* Font-awesome loaders are replaced with svg

---

## 2.7.22
*(2024-08-27)*

#### Fixed
* Fixed backend validation for attributes with Input Validation for Store Owner set as Integer Number
* Issue related to Brand stores display mode

---

## 2.7.21
*(2024-08-05)*

#### Fixed
* Issue related to horizontal filters

---

## 2.7.20
*(2024-08-02)*

#### Fixed
* Added possibility to hide unuseful additional filters

---

## 2.7.19
*(2024-07-12)*

#### Fixed
* Issue related to backend type of decimal filter
* Issue related to stock status filter

---

	# Change Log
## 2.7.18
*(2024-07-03)*

#### Fixed
* Issue related to grouped options image

---


## 2.7.17
*(2024-06-21)*

#### Fixed
* Issue related to AddBrandLogoPlugin

---


## 2.7.16
*(2024-06-20)*

#### Improvements
* Added new pagination mode - load more button with default pagination

---


## 2.7.15
*(2024-06-12)*

#### Fixed
* Filter multiselect AND logic issue

---


## 2.7.14
*(2024-06-12)*

#### Fixed
* All products page was added to widget layout update list

---


## 2.7.13
*(2024-05-29)*

#### Improvements
* Added PageBuilder for Brand descriprion field

#### Fixed
* Label render process optimization

---


## 2.7.12
*(2024-05-21)*

#### Fixed
* Issue related to price filter url with prefix
* Breadcrumbs error on product page

---


## 2.7.11
*(2024-04-23)*

#### Fixed
* Price slider issue on brand page
* Issue related to editing of an attribute config
* Adding index to mst_quick_navigation_sequence table
* Attribute Linking issue

---


## 2.7.10
*(2024-04-04)*

#### Fixed
* Issue with indexer performance on enterprise edition when livesearch module is disabled
* Convert brand urlkey to lowercase

---


## 2.7.9
*(2024-03-26)*

#### Fixed
* Reducing the number of requests to mst_navigation_grouped_option table

---


## 2.7.8
*(2024-03-21)*

#### Fixed
* Fixed scroll step of quick navigation slider

---


## 2.7.7
*(2024-03-13)*

#### Improvements
* Adding the ability to disable scroll page to top on filter applying

---


## 2.7.6
*(2024-03-05)*

#### Fixed
* Adding a possibility to use search-box in filter if attribute has text swatch type
* Issue with price filter in slider mode on landing page

---


## 2.7.5
*(2024-02-27)*

#### Fixed
* Sorting of swatch type filter options
* Optimization of attribute_config getting pocess

---


## 2.7.4
*(2024-02-21)*

#### Fixed
* Brand module routing optimization

---


## 2.7.3
*(2024-02-19)*

#### Fixed
* Issue with category filter using Livesearch

---


## 2.7.2
*(2024-02-02)*

#### Fixed
* Fixed issue related with displaying of not configured brands

---


## 2.7.1
*(2024-01-31)*

#### Fixed
* Brand page is redirected to 404 if url is not valid
* Adding attribute caching

---


## 2.7.0
*(2024-01-29)*

#### Features
* New feature - Landing Pages

#### Fixed
* Filter expander issue
* Brand Page visibility per storeView
* Fix brand attribute scope label
* Fixed the issue with Search filter (compatibility with Mirasvit_SeoFilter)

---


## 2.6.16
*(2023-12-26)*

#### Fixed
* Brand page issue on magento2.4.3

---


## 2.6.15
*(2023-12-21)*

#### Features
* Brand SEO data per store view

#### Improvements
* Adding ability to hide unuseful filter option

#### Fixed
* Change type of config field from text to mediumtext in mst_navigation_attribute_config table

---


## 2.6.14
*(2023-12-06)*

#### Features
* Ability to set link to the brand page in Product Attribute Linking

#### Improvements
* Scrolling to top when filter is applied

#### Fixed
* Price filter input auto-zooming on iphone
* Brand title on brand list page

---


## 2.6.13
*(2023-11-22)*

#### Fixed
* Stock Filter not visible after applying
* Brands grid sorting issue
* Issue related with option duplication on category filter

---


## 2.6.12
*(2023-10-27)*

#### Fixed
* Display proper brand store label on All Brands Page
* Fixed the issue with filters in Brand Page grid
* Adding TrustPilot and Yotpo widgets compatibility

---


## 2.6.11
*(2023-10-20)*

#### Improvements
* Quick Navigation performance

#### Fixed
* Fixed the issue with the slider not being draggable on mobile

---


## 2.6.10
*(2023-10-10)*

#### Fixed
* Urls-with-slash-suffix

---


## 2.6.9
*(2023-10-09)*

#### Fixed
* Filter apply buton position on mobile

---


## 2.6.8
*(2023-10-09)*

#### Fixed
* jquery-mouse-ui compatibility

---


## 2.6.7
*(2023-10-09)*

#### Fixed
* error occurs when labels of onSale and search filters are empty
* Fixed the compatibility issue with Category search in Mirasvit Search Ultimate

---


## 2.6.6
*(2023-08-22)*

#### Fixed
* Brand logo on product page

---


## 2.6.5
*(2023-08-04)*

#### Fixed
* Fixed the issue with meta data for not configured Brand pages

---


## 2.6.4
*(2023-08-02)*

#### Fixed
* Fixed the issue with apply button not hidden on mobile after filters applied

---


## 2.6.3
*(2023-08-01)*

#### Fixed
* Adding product wishlist button on brand page

---


## 2.6.2
*(2023-07-20)*

#### Fixed
* Don't show nested categories if Show Nested Categories is No

---


## 2.6.1
*(2023-07-05)*

#### Fixed
* Fixed the issue with errors on front pages with brands slider when brand attribute option is deleted but brand page for that attribute option still exists

---


## 2.6.0
*(2023-06-29)*

#### Fixed
* Fixed the issue with Brand pages and All Products page in Magento EE with Magento_LiveSearch

---


## 2.5.9
*(2023-06-28)*

#### Fixed
* issue related to ajax responses (load more button)

---


## 2.5.8
*(2023-06-26)*

#### Fixed
* Ability to hide assigned products in brand page edit form

---


## 2.5.7
*(2023-06-19)*

#### Fixed
* adding custom attribute to exception
* adding custom attribute exception
* issue with price filter rendering

---


## 2.5.6
*(2023-06-13)*

#### Fixed
* The issue with price filter rendering

---

## 2.5.5
*(2023-06-07)*

#### Fixed
* The issue with the Brand edit page (PHP8.2)

---


## 2.5.4
*(2023-06-05)*

#### Fixed
* Fixed issue with stock filter during reindex (multistore)

---


## 2.5.3
*(2023-05-31)*

#### Fixed
* Issue with category filter displayed when multiselect disabled and category filter already selected

---


## 2.5.2
*(2023-05-29)*

#### Fixed
* Issue with memory on reindex (Magento_LiveSearch)
* Issue with incorrect count for category filter

---


## 2.5.1
*(2023-05-15)*

#### Improvements
* Do not display options without products (Category filter, show nested categories, search box)
* Category tree display improved
* Search filter suggestions for category filter when nested categories used
* Collapsible category filter

---


## 2.5.0
*(2023-05-09)*

#### Features
* Display modes for Brand pages and the ability to insert CMS blocks on brand pages (similar to category pages)

---


## 2.4.9
*(2023-05-08)*

#### Fixed
* Fixed the issue with error on product page (Brands, some cases)

---


## 2.4.8
*(2023-04-27)*

#### Fixed
* Fixed the issue with unnecessary redirects (Attribute Linking feature)

---


## 2.4.7
*(2023-04-26)*

#### Fixed
* Fixed the issue with changing sorting order on the search result page in Ajax mode

---


## 2.4.6
*(2023-04-20)*

#### Fixed
* Fixed the issue with unable to upload SVG images (Brand Page)

---


## 2.4.5
*(2023-04-13)*

#### Fixed
* Fixed the issue with error on product page (Attribute Linking feature)

---


## 2.4.4
*(2023-04-11)*

#### Fixed
* Fixed the issue with attribute linking feature (errors on the product page)
* Fixed the issue with stock filter after changing product's stock status (multistock)

---


## 2.4.3
*(2023-04-07)*

#### Fixed
* Fixed the issue with positions for Grouped Options
* Compatibility of multiselect AND logic with Magento_OpenSearch
* Do not allow browsers to cache ajax responses

---


## 2.4.2
*(2023-04-06)*

#### Improvements
* Ability to specify title, description, and short description of brand pages separately for each store

---


## 2.4.1
*(2023-03-31)*

#### Fixed
* Fixed the issue with error: "Warning: Undefined array key 'filter'" (GraphQl)
* Fixed the issue with conflict between custom attributes and additional filters

---


## 2.4.0
*(2023-03-29)*

#### Fixed
* PHP8.2 compatibility

---


## 2.3.9
*(2023-03-22)*

#### Fixed
* Fixed the issue with the Search filter on AMP pages (Search filter will not be displayed on AMP pages)

---


## 2.3.8
*(2023-03-16)*

#### Fixed
* Fixed the issue with ajax scroll loader (infinity mode)

---


## 2.3.7
*(2023-03-13)*

#### Fixed
* Fixed the issue with unable to add products to the cart after ajax calls

---


## 2.3.6
*(2023-03-13)*

#### Features
* Ability to use instant mode on desktop and 'By button click' mode on mobile

---


## 2.3.5
*(2023-03-07)*

#### Fixed
* Fixed the issue with slider filters in confirmation (apply button) ajax mode

---


## 2.3.4
*(2023-03-07)*

#### Fixed
* Fixed the issue with in-page products order difference between normal page and ajax response (some cases)

---


## 2.3.3
*(2023-02-23)*

#### Fixed
* Fixed the issue with slider filter when Mirasvit_SeoFilter module enabled and the filter has custom alias

---


## 2.3.2
*(2023-02-22)*

#### Improvements
* Ability to assign products to the brand from the Brand Page edit page
* Ability to create new brand (attribute option) from the Brand Page edit page

#### Fixed
* Fixed the issue with Ajax scroll in some custom themes

---


## 2.3.1
*(2023-02-20)*

#### Fixed
* Fixed the issue with error when filtering by Category on search results (Magento 2.4.5, Display Out of Stock products)

---


## 2.3.0
*(2023-01-26)*

#### Fixed
* Fixed the issue with error on search result page after filtering by category when the option 'Display Out Of Stock Products' enabled (Magento 2.4.5)

---


## 2.2.37
*(2023-01-11)*

#### Fixed
* Fixed the issue with not all categories present in attribute configurations (Categories Visibility Mode)

---


## 2.2.36
*(2023-01-06)*

#### Improvements
* Filter options styling

---


## 2.2.35
*(2023-01-04)*

#### Fixed
* Fixed the issue with removing price filter (ranges)

---


## 2.2.34
*(2022-12-30)*

#### Fixed
* Fixed the issue with price filter (ranges)

---


## 2.2.33
*(2022-12-29)*

#### Fixed
* Fixed the issue with error in browser's console (filterOptions.closest(...) is null)

---


## 2.2.32
*(2022-12-09)*

#### Fixed
* Fixed the issue with pagination not working on search results with Mirasvit Search Ultimate when horizontal filters are present

---


## 2.2.31
*(2022-12-08)*

#### Fixed
* Fixed the issue with 'Show opened filters' config not working as expected

---


## 2.2.30
*(2022-11-24)*

#### Improvements
* Translation support

#### Fixed
* Fixed the issue with custom swatches

---


## 2.2.29
*(2022-11-03)*

#### Fixed
* Fixed the issue with multiple alphabetical indexes

---


## 2.2.28
*(2022-11-02)*

#### Features
* Alphabetical index for filter options

---


## 2.2.27
*(2022-10-21)*

#### Features
* Clear link for all selected options per attribute

---


## 2.2.26
*(2022-10-20)*

#### Features
* Product attribute linking

#### Improvements
* Checked filter options counter and opened filters state

#### Fixed
* Fixed the issue with insecure URLs for brands in the sitemap (with Mirasvit_SeoSitmap module)

---


## 2.2.25
*(2022-09-29)*

#### Improvements
* Performance improvement on building category filter

#### Fixed
* PHP8.1 compatibility issue

---


## 2.2.24
*(2022-09-27)*

#### Improvements
* Added ability to set a step for a slider filter

---


## 2.2.23
*(2022-09-21)*

#### Fixed
* Fixed the issue with price slider (compatibility with Amasty_ElasticSearch)
* Fixad the issue with ajax scroll (unveil is not a function)

---


## 2.2.22
*(2022-09-14)*

#### Fixed
* Fixed the issue with the error when the filter with swatch is applied (PHP8.1)

---


## 2.2.21
*(2022-09-06)*

#### Improvements
* Correct max price in filter if max price in range filter is set to 0

#### Fixed
* Fixed the issue with error 'Call to a member function getTooltip() on null'

---


## 2.2.20
*(2022-09-05)*

#### Fixed
* Fixed the issue with attribute tooltips not displayed

---


## 2.2.19
*(2022-09-02)*

#### Improvements
* Search filter

#### Fixed
* Duplicated paging parameter in scroll ajax calls
* Do not load product collection by brand if More From Brand config disabled

---


## 2.2.18
*(2022-08-29)*

#### Improvements
* Show navigation toolbar when JavaScript disabled in the browser

#### Fixed
* Fixed the issue with price filter not displayed (multiselect, some cases)

---


## 2.2.17
*(2022-08-10)*

#### Improvements
* Breadcrumbs
* Not display brands info in product listings and product view pages for not configured or disabled brands

---


## 2.2.16
*(2022-08-03)*

#### Improvements
* Ability to define multiselect logic (OR/AND) per attribute

#### Fixed
* Properly trigger content update on ajax scroll

---


## 2.2.15
*(2022-08-02)*

#### Improvements
* Merge all filters if horizontal filters hidden (mobile view)

#### Fixed
* Fixed the issue with error 'Undefined offset 0' on the all brands page when brands not configured or all disabled

---


## 2.2.14
*(2022-07-27)*

#### Fixed
* Error during setup:di:compile (Interface ‘Magento\LiveSearchAdapter\Model\Aggregation\BucketHandlerInterface’ not found)

---


## 2.2.13
*(2022-07-27)*

#### Improvements
* Search filter appearance
* Magento LiveSearch compatibility (Mirasvit_LayeredNavigationLiveSearch submodule added)

---


## 2.2.12
*(2022-07-05)*

#### Fixed
* Fixed the issue with error on search result page (Magento_LiveSearch compatibility)

---


## 2.2.11
*(2022-07-04)*

#### Improvements
* Quick Navigation performance improved

#### Fixed
* PHP8.1 compatibility issue

---


## 2.2.10
*(2022-06-23)*

#### Fixed
* Fixed the issue with isShowAllCategories setting (Mirasvit_Brand, Mirasvit_AllProducts) doesn't work

---


## 2.2.9
*(2022-06-21)*

#### Fixed
* Fixed the issue with duplicated products when the Load More button clicked multiple times
* Fixed the issue with updating labels of the progress bar

---


## 2.2.8
*(2022-06-20)*

#### Improvements
* remove db_schema_whitelist.json

---


## 2.2.7
*(2022-06-16)*

#### Fixed
* Fixed the issue with filters applied immediately with mode 'By Button Click' (Firefox only)

---


## 2.2.6
*(2022-06-15)*

#### Fixed
* Fixed the issue with scroll not working after switching view mode (ajax)

---


## 2.2.5
*(2022-06-13)*

#### Improvements
* Translations for brands' descriptions and meta

---


## 2.2.4
*(2022-06-13)*

#### Fixed
* Fixed the issue with duplicated buttons and progress bar
* Fixed the issue with button label in 'Infinity Scroll + Load More Button' mode

---


## 2.2.3
*(2022-06-08)*

#### Fixed
* Fixed the issue with brand slider images don't have alt attribute

---


## 2.2.2
*(2022-06-01)*

#### Fixed
* Fixed the issue with options for Yes/No filters

---


## 2.2.1
*(2022-05-27)*

#### Fixed
* Fixed the issue with price filter not applied (ajax, ranges, by button click)

---


## 2.2.0
*(2022-05-25)*

#### Improvements
* Migrate to declarative schema

---


## 2.1.34
*(2022-05-20)*

#### Fixed
* rel attribute for swatch links

---


## 2.1.33
*(2022-05-17)*

#### Fixed
* Fixed the issue with reload on ajax mode due to error '.unveil is not a function'

---


## 2.1.32
*(2022-05-11)*

#### Fixed
* Fixed the issue with product swatch images (custom navigation swatches)

---


## 2.1.31
*(2022-04-28)*

#### Fixed
* fixed the issue with unnecessary reloads
* fixed the issue with progress bar when products per page value changed

---


## 2.1.30
*(2022-04-27)*

#### Features
* Scroll progress bar

#### Fixed
* Fixed the issue with Catalog Search reindex when additional filters enabled (some cases)
* Fixed the issue with pages not being reloaded sometimes after clicking browser's back button (ajax mode)
* Load n more products with correct number for last page
* Fixed the issue with error 'explode() expects parameter 2 to be string, int given'
* Magento2.4.4 price slider compatibility

---


## 2.1.29
*(2022-04-15)*

#### Fixed
* Minor fix in Brand module

---


## 2.1.28
*(2022-04-11)*

#### Improvements
* Brand router stability (with SEO filter enabled)

#### Fixed
* Fixed the issue with not able to save 'Use Category Url Suffix' in Brand configs
* Fixed the issue with All Brands pages (brands without products)

---


## 2.1.27
*(2022-04-06)*

#### Fixed
* SEO friendly range filter URL format issue

---


## 2.1.26
*(2022-03-28)*

#### Fixed
* Fixed a few small issues with Brand module

---


### 2.1.25
*(2022-03-25)* 

* Support of PHP 8.1

## 2.1.24
*(2022-03-21)*

#### Fixed
* Fixed the issue with category tree in category filter
* Swatches compatibility with some themes

---


## 2.1.23
*(2022-03-09)*

#### Fixed
* Fixed the issue with canonical URLs and URL suffix (Brand pages)
* Fixed the issue with alt attributes for brand logo images
* All Brands page mobile view

---


## 2.1.22
*(2022-02-23)*

#### Fixed
* Change slider currency

---


## 2.1.21
*(2022-02-21)*

#### Fixed
* Fixed the issue with only first attribute shown in quick navigation
* Fixed the issue with navigation toolbar when changing products per page value
* Fixed the issue with ajax mode Apply by button click
* Fixed the issue with options in horizontal navigation
* Fixed the issue with incorrect quick navigation view

---


## 2.1.20
*(2022-01-17)*

#### Fixed 
* "Hide brands with empty products collection" function

---


## 2.1.18
*(2022-01-13)*

#### Improvements
* Add option to hide brands without results

---


## 2.1.17
*(2022-01-04)*

#### Improvements
* Currency in price slider filter
* Quick navigation RTL support
* Brand pages in widgets

---


## 2.1.16
*(2021-12-15)*

#### Fixed
* Issue with 'Whole width image' checkbox unchecked after attribute save
* Filters display issue when "Anchor" option set to "No"
* Correct "Shop By" button functionality in Firefox

---


## 2.1.15
*(2021-11-16)*

#### Fixed
* Fixed the issue with the possition of grouped options
* Fixed the issue with slider filter (Warning: strpos(): Empty needle)

---


## 2.1.14
*(2021-11-03)*

#### Fixed
* Fixed the issue with brand pages (Magento 2.4.3 compatibility)

---


## 2.1.13
*(2021-11-02)*

#### Fixed
* Issue with "More..." functionality

---


## 2.1.12
*(2021-10-29)*

#### Improvements
* Ajax loading progress-bar

---


## 2.1.11
*(2021-10-28)*

#### Fixed
* Fixed the issue with slider filter

---


## 2.1.10
*(2021-10-26)*

#### Improvements
* Ability to add tooltips with a short descriptions for each attribute in the layered navigation

#### Fixed
* more from brand MSI issue

---


## 2.1.9
*(2021-08-31)*

#### Improvements
* Price slider filter redirects to 404
* Change "Filter Item Display Mode" on multiselect change
---


## 2.1.8
*(2021-08-19)*

#### Improvements
* Optional multiselect per attribute

---


## 2.1.7
*(2021-08-12)*

#### Fixed
* Type error on price slider prepare data
* Additional filters functionality improvements
* Magento 2.4.3 compatibility

---


## 2.1.6
*(2021-07-20)*

#### Fixed
* Limit for opened filters
* Horizontal nav styles
* Swatch renderer pull all swatch options
* Brand meta service issue
* Get correct price faceted data for slider

---


## 2.1.5
*(2021-07-01)*

#### Features
* Additional pagination modes

#### Fixed
* Leave single canonical url for brand page

---


## 2.1.4
*(2021-06-17)*

#### Fixed
* Fixed the issue with disabling search filter as fulltext search

---


## 2.1.3
*(2021-06-14)*

#### Fixed
* Fixed issues with search filter + horizontal navigation
* Missing custom swatch label

---


## 2.1.2
*(2021-06-09)*

#### Fixed
* Fixed the issue with styles (search filter + horizontal filters)

---


## 2.1.1
*(2021-06-09)*

#### Features
* Search filter

#### Improvements
* Rating filter label in Quick Navigation

---


## 2.1.0
*(2021-05-31)*

#### Fixed
* swatch rendering issue
* label image rendering issue
* brand filtering issue with url suffix applied

#### Features
* Grouped options

---


## 2.0.14
*(2021-05-14)*

#### Fixed
* Apply button issue on mobile

---


## 2.0.13
*(2021-05-13)*

#### Fixed
* Issue with price filter (ranges)
* Fixed the issue with not able to deselect filter options in apply button mode
* apply button styles issue on mobile

---


## 2.0.12
*(2021-04-26)*

#### Fixed
* Keywords on the Brand Page

---


## 2.0.11
*(2021-04-23)*

#### Fixed
* Issue with urls on the brand page

---


## 2.0.10
*(2021-04-21)*

#### Fixed
* Remove redundant .00 from price (numeric) slider

---


## 2.0.9
*(2021-04-19)*

#### Improvements
* Added swatches to the brand page (product listing)

#### Fixed
* Issue with checkbox
* conflicts with Advanced SEO Suite with the sitemap generation
* Spell Correction indexing issue

---


## 2.0.8
*(2021-04-13)*

#### Fixed
* Issue with sorting on the search page
* Hide horizontal bar if it disabled
* Blank theme compatibility

---


## 2.0.7
*(2021-03-23)*

#### Fixed
* Brand Slider widget (pass params)
* Brand URLs (store views)

---


## 2.0.6
*(2021-03-22)*

#### Fixed
* New SEO Filters version
 
---

## 2.0.5
*(2021-03-10)*

#### Fixed
* The issue with filter by category (only with enabled Flat Categories)
* Include theme-compatibility.js in any case

---


## 2.0.4
*(2021-03-03)*

#### Improvements
* Changed interface to place attribute to horizontal filter (both positions horizontal/vertical are possible now at the same time)

#### Fixed
* Issue with quick filters

---


## 2.0.3
*(2021-02-26)*

#### Improvements
* Brands
* Ability to set follow/nofollow for layered navigation links

#### Fixed
* Issue with Ajax Scroll

---


## 2.0.2
*(2021-02-19)*

#### Improvements
* Brands

#### Fixed
* Search box for categories
* Issue with attribute edit page

---


## 2.0.1
*(2021-02-12)*

#### Fixed
* Issue with attribute edit page

---


## 2.0.0
*(2021-02-08)*

#### Improvements
* Code refactoring (v2.x - for Magento 2.4+, v1.x - for Magento 2.1-2.3)
* Improved performance

---


## 1.1.0
*(2020-12-15)*

#### Fixed
* Fixed the issue with permissions in the admin menu
* Fixed the issue with applying seo template in ajax mode

---


## 1.0.118
*(2020-12-02)*

#### Fixed
* Price slider issue

---


## 1.0.117
*(2020-12-01)*

#### Fixed
* ES stock filter
* Issue with brand url
* Quick navigation preparation issue

---


## 1.0.116
*(2020-11-26)*

#### Fixed
* apply translations to search box
* SM image lazyload support

---


## 1.0.115
*(2020-11-19)*

#### Fixed
* add catalog_category_view_type_default layout support
* apply links limit to category filter
* unable to delete price filter

---


## 1.0.114
*(2020-11-10)*

#### Fixed
* Magento 2.4.1 compatibility
* Missing filters on brand page issue

---


## 1.0.113
*(2020-10-05)*

#### Fixed
* SSU M24 compatibility (mysql and sphinx engines dont support layered navigation)

---


## 1.0.112
*(2020-09-29)*

#### Fixed
* Fix ajax widget call
* Hide quick navigation items with empty results
* Filters processing issue
* Nested filters after ajax apply (frontend issue)

---


## 1.0.111
*(2020-09-17)*

#### Fixed
* Horizontal navigation word-break
* Incorrect categories filter on search results page

---


## 1.0.110
*(2020-09-10)*

#### Fixed
* On sale, stock filter

---


## 1.0.109
*(2020-09-09)*

#### Fixed
* Rating filter issue
* Issue with sorting by relevance on search result page
* Display mode for price filter issue

---


## 1.0.108
*(2020-09-04)*

#### Fixed
* Price filter url

---


## 1.0.107
*(2020-09-04)*

#### Features
* Filter applying mode. Filter can be applied by button click

---


## 1.0.106
*(2020-09-01)*

#### Improvements
* Improve filter predict logic

#### Fixed
* Prevent price filters below zero
* Provide correct image size keeping aspect ratio
* Unable to clear multiple selected filter

---



## 1.0.105
*(2020-08-21)*

#### Features
* Quick Navigation Filters / Predicted Filters

#### Improvements
* Overlay styles

---

## 1.0.104
*(2020-08-19)*

#### Fixed
* Save brand image (compatibility with Magento 2.4)
* Issue with attribute tab in backend (Magento 2.4)
---


## 1.0.103
*(2020-08-13)*

#### Features
* Seo-friendly urls for brand and all products pages

#### Fixed
* Compatibility issue with Elasticsearch 5.x (Magento 2.4)

---


## 1.0.102
*(2020-08-11)*

#### Improvements
* Compatibility with Magento 2.4

---


## 1.0.101
*(2020-07-20)*

#### Fixed
* Additional sidebar ajax update issue
* Add visibility filter to request

---


## 1.0.100
*(2020-06-18)*

#### Fixed
* Undefined index label
* Sidebar content doesn't update properly
* Breadcrumbs content update issue

---


## 1.0.99
*(2020-05-27)*

#### Fixed
* Update content issue
* OnSale filter missing products
* Category filter issue

---


## 1.0.97
*(2020-03-23)*

#### Fixed
* Ability to use Slider for decimal attributes (Input validation option)

---


## 1.0.96
*(2020-03-20)*

#### Fixed
* Unable to apply links limit
* MGS theme compatibility fix
* Possible error: LESS file is empty .. horizontal_hide.css
* Multistore brand sitemap generation issue
* Too small brand thumbnails

---


## 1.0.95
*(2020-03-13)*

#### Fixed
* Issue with apply price filter for max price
* missing category filter on search results page

---


## 1.0.94
*(2020-03-12)*

#### Improvements
* Category tree filter
* Checkbox styles

---


## 1.0.93
*(2020-03-10)*

#### Improvements
* Code Refactoring

---


## 1.0.92
*(2020-02-26)*

#### Fixed
* unable to save brand

---


## 1.0.91
*(2020-02-24)*

#### Fixed
* Error on empty search results

#### Improvements
* Value format for Slider

---


## 1.0.89
*(2020-02-11)*

#### Fixed
* SEO2 process module brand alternates

---


## 1.0.88
*(2020-02-10)*

#### Fixed
* Missing layered navigation block

---


## 1.0.87
*(2020-01-06)*

#### Fixed
* jQuery UI fallback compat issue

---


## 1.0.86
*(2020-01-06)*

#### Fixed
* Collapsible-fix.js M2.3.2 incompatibility
* Sorting issue
* Swatch renderer issue
* Price filter issue
* MGS theme compatibility
* Brand update issue

---


## 1.0.85
*(2019-12-16)*

#### Fixed
* Price filter miss min price products with cents
* Additional filters dont work without mirasvit search
* Show opened filters option don't work

---


## 1.0.84
*(2019-12-10)*

#### Fixed
* Ajax Paging and Sorting issues

---


## 1.0.83
*(2019-12-09)*

#### Improvements
* Add multiselect option to swatches

#### Fixed
* Missing "Shopping by" section on mobile
* Unable to change sort direction

---


## 1.0.82
*(2019-12-02)*

#### Fixed
* Checkbox filter click returns raw JSON

---


## 1.0.81
*(2019-11-25)*

#### Fixed
* Clean selected filters
* Missing nested categories in filter list

---


## 1.0.80
*(2019-11-13)*

#### Fixed
* Issue with category filter on search results page (elasticsearch 6+)

---


## 1.0.79
*(2019-11-13)*

#### Improvements
* Add category and product url suffix validator

---


## 1.0.78
*(2019-11-08)*

#### Improvements
* Ability to sort attribute options alphabetically

---


## 1.0.77
*(2019-11-05)*

#### Improvements
* Ability to use multi-select for Decimal filters
* Ability to split selected filter option (filter clear block)

---


## 1.0.76
*(2019-10-28)*

#### Improved
* Code refactoring

#### Fixed
* Price filter multi select

---


## 1.0.75
*(2019-10-21)*

#### Features
* display different swatch for category and product page

#### Fixed
* ES compatibility

---


## 1.0.73
*(2019-08-22)*

#### Improvements
* Ability to use .svg for brand logos

---


## 1.0.72
*(2019-08-21)*

#### Improvements
* Ability to display nested categories in filter

---


## 1.0.71
*(2019-05-24)*

#### Fixed
* Issue with save attribute

---


## 1.0.70
*(2019-05-23)*

#### Fixed
* Upgrade issue (All parts of a PRIMARY KEY must be NOT NULL)

---


## 1.0.69
*(2019-05-22)*

#### Improvements
* Ability to display/hide particular filters by category

#### Fixed
* Issue with Customer Group ID in price filter

---


## 1.0.68
*(2019-04-15)*

#### Fixed
* JS error on edit brand page

---


## 1.0.67
*(2019-04-11)*

#### Improvements
* All Brands Page

---


## 1.0.66
*(2019-03-28)*

#### Improvements
* Performance issue loading css styles

---

### 1.0.65
*(2019-03-18)* 

* Refactoring


## 1.0.64
*(2019-03-14)*

#### Fixed
* Price slider filter is not properly displayed in IE11

---

## 1.0.63
*(2019-03-06)*

#### Fixed
* White layer appears during using toolbar and hides catalog

---

## 1.0.62
*(2019-03-04)*

#### Fixed
* Rating filter displayed multiple times across different filters
* Properly set additional filters position

---

## 1.0.61
*(2019-03-01)*

#### Fixed
* Error during saving product from admin panel

---

## 1.0.60
*(2019-02-28)*

#### Improvements
* Integrate Layered Navigation with Elastic Search Engine provided by Mirasvit Search

---

## 1.0.59
*(2019-02-22)*

#### Improvements
* Add translation file

---

## 1.0.58
*(2019-02-19)*

#### Fixed
* Error during performing compilation command

---

## 1.0.57
*(2019-02-14)*

#### Fixed
* Solve error during DI compilation

---

## 1.0.56
*(2019-02-13)*

#### Fixed
* Error 'The attribute model is not defined.'

---

## 1.0.55
*(2019-02-07)*

#### Features
* SEO for layered navigation: robots meta header and canonical URLs

#### Fixed
* Error in logs regarding non-numeric value in price filter
* Fix error in browser's console regarding absent css file

---

## 1.0.54
*(2019-01-11)*

#### Fixed
* Clear all filters button does not work in some cases

---

## 1.0.53
*(2019-01-10)*

#### Fixed
* Style issue with 'Shop By' button #50
* Error in browser's developer toolbar regarding absence of the stylesheet file #50

---

## 1.0.52
*(2019-01-09)*

#### Fixed
* Cannot upload logo image for brand page in M2.3

---


## 1.0.51
*(2019-01-09)*

#### Fixed
* Error 'Attribute does not exist' occurs when opening CMS pages without preliminary setting the brand attribute
* Compatibility with Magento 2.1.7 and lower

---

## 1.0.50
*(2018-12-20)*

#### Fixed
* Category page gives error when price calculation step set to 'Automatic (equalize product counts)' option

---

## 1.0.49
*(2018-12-05)*

#### Features
* Added Smart Sorting module

#### Fixed
* Errors during di compilation
* Brand pages show all brand products (since 1.0.48)

#### Documentation
* Layered Navigation troubleshoot
* Scroll and Sorting modules documentation

---

## 1.0.48
*(2018-11-29)*

#### Improvements
* M2.3 support
* Center brand labels in slider for IE

#### Fixed
* Brand page is not opened

---


## 1.0.47
*(2018-11-23)*

#### Fixed
* Error displaying brand slider

---

## 1.0.46
*(2018-11-19)*

#### Improvements
* Display horizontal filters with mobile themes
* **Center 'Add to Cart' button after catalog update**
    Trigger "amscroll" event after catalog update,
    JS script listens for this event to center the buttons

#### Fixed
* Swatch options' labels of type text are not visible (since 1.0.45)
* **Problem with auto-generated brand URLs**
    whitespaces are not replaced with hyphen sign

---

## 1.0.45
*(2018-11-09)*

#### Features
* Ability to set Brand URL suffix

#### Fixed
* Brand logo is not visible in product list
* **Filter options missing for swatch filters** When swatch type is not set for the attribute the filter options for that attribute are not visible
* **Checkbox-styled filters are not clickable** When option Display options set to Checkbox and Ajax is not enabled the filter options do not react on user clicks and as a result filtering is not performed.

#### Documentation
* update installation instruction

---

## 1.0.44
*(2018-11-02)*

#### Fixed
* **On Sale filter shows wrong products**
    On Sale filter ignores Special Price From and To dates
    and as a result shows products that are no longer on sale.

#### Feature
* Ajax Infinite Scroll

---

## 1.0.43
*(2018-10-24)*

#### Fixed
* Wrong SEO-friendly filter URL when category URL suffix is set to slash - /

---

## 1.0.42
*(2018-10-23)*

#### Fixed
* Product URLs are not SEO-friendly on brand page when 'Use Categories Path for Product URLs' is enabled

---

## 1.0.41
*(2018-10-11)*

#### Fixed
* Pagination does not work on search page, when search query composed from 2 words

#### Documentation
* Instruction for module disabling

---

## 1.0.40
*(2018-09-28)*

#### Fixed
* Multiple filter options marked as checked when option ID exists as the substring in another option
* JS Error: filters do not work

---

## 1.0.39
*(2018-09-19)*

#### Fixed
* Brand page returns 404 when trailing slash is used in the brand's page URL

---

## 1.0.38
*(2018-09-18)*

#### Fixed
* Issue with slider

---

## 1.0.36
*(2018-09-14)*

#### Fixed
* Swatch multiselector

---

## 1.0.35
*(2018-09-14)*

#### Fixed
* issues with js

---

## 1.0.33
*(2018-09-12)*

#### Fixed
* LOF after filtration

---

## 1.0.32
*(2018-09-11)*

#### Improvements
* Float filters

#### Fixed
* Lof Ajax

---

## 1.0.31
*(2018-09-06)*

#### Improvements
* Lof Ajax

---

## 1.0.30
*(2018-08-30)*

#### Improvements
* Show all categories in filter (for brand and all products page)

---

## 1.0.29
*(2018-08-28)*

#### Fixed
* Lof_AjaxScroll compatibility

---

## 1.0.28
*(2018-08-23)*

#### Fixed
* Fixed conflict with Aheadworks Product Questions

---

## 1.0.27
*(2018-08-17)*

#### Fixed
* Fixed "Notice: Undefined variable: filtersWithoutSuffix in .../LayeredNavigation/Service/SeoFilterUrlService.php on line 292"

---

## 1.0.26
*(2018-08-16)*

#### Fixed
* Fixed notice

---

## 1.0.25
*(2018-08-15)*

#### Fixed
* Fixed frontend style

---

## 1.0.24
*(2018-08-15)*

#### Feature
* Brand slider
* More from this brand block
* Brand logo and tooltip on product and category page

---

## 1.0.23
*(2018-07-20)*

#### Fixed
* bug: Compatibility with SEO

---

## 1.0.22
*(2018-07-19)*

#### Fixed
* Style fix

---

## 1.0.21
*(2018-07-19)*

#### Feature
* All products page

---

## 1.0.20
*(2018-07-16)*

#### Fixed
* Fix default title
* Compatibility with SEOFilter version 1.0.5

#### Feature
* Ability add banner to brand page

---

## 1.0.19
*(2018-07-04)*

#### Fixed
* Fixed incorrect items count in navigation for Elasticsearch (magento ee, Elasticsearch, for some stores)

---

## 1.0.18
*(2018-06-27)*

#### Fixed
* Ability use catalog.leftnav for horizontal navigation (need for some stores)

---

## 1.0.17
*(2018-06-22)*

#### Fixed
* Fixed brand images style

---

## 1.0.16
*(2018-06-21)*

#### Fixed
* Fixed an issue when only 10 items in navigation ( for Elasticsearch 1.7.x )

---

## 1.0.15
*(2018-06-14)*

#### Fixed
* Elasticsearch compatibility if multiselect enabled (magento ee)

---

## 1.0.14
*(2018-06-06)*

#### Fixed
* Fix brand composer

---

## 1.0.13
*(2018-06-06)*

#### Documentation
* docs: Documentation improvement

#### Feature
* Brands

---

## 1.0.12
*(2018-05-23)*

#### Fixed
* Fixed incorrect urls for additional filters in navigation
* Fixed an issue with "%2C" in url without ajax if slider enabled

---

## 1.0.11
*(2018-05-17)*

#### Fixed
* Multi filter issue + issue with price slider (if from is 0)

---

## 1.0.10
*(2018-05-08)*

#### Fixed
* Fixed error if search elastic work in mysql mode

---

## 1.0.9
*(2018-05-08)*

#### Fixed
* Fixed issue with "pub" folder in additional css path

---

## 1.0.8
*(2018-05-04)*

#### Fixed
* Compatibility with SearchElastic

---

## 1.0.7
*(2018-04-30)*

#### Fixed
* Fixed %2C symbol in pager url

---

## 1.0.6
*(2018-04-30)*

#### Improvements
* Redirect to correct url if js error

---

## 1.0.5
*(2018-04-30)*

#### Fixed
* Fixed filter disappearance when click on ajax paging

---

## 1.0.4
*(2018-04-18)*

#### Improvements
* Magento 2.1 compatibility

---

## 1.0.3
*(2018-04-18)*

#### Improvements
* Ability use scroll for navigation links

---

## 1.0.2
*(2018-04-12)*

#### Fixed
* Fixed style issue for Safari browser

---

## 1.0.1
*(2018-04-06)*

#### Documentation
* Added documentation

---

## 1.0.0
*(2018-04-03)*

* Initial release

---
