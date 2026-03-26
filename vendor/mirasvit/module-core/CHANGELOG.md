# Change Log
## 1.7.2
*(2026-03-13)*

#### Improvements
* New AI models added for OpenAI, Claude, Gemini

---


## 1.7.1
*(2026-03-06)*

#### Fixed
* Magento 2.4.9 compatibility

---


## 1.7.0
*(2026-01-21)*

#### Improvements
* GeoLocation service with autoupdates for GeoLite2-City database
* Translations added

---


## 1.6.11
*(2026-01-05)*

#### Improvements
* Added latest AI models (Open AI: GPT-5.1, GPT-5.2, GPT-5.2 Pro; Antropic: Claude Haiku 4.5, Claude Sonnet 4.5, Claude Opus 4.5; Google: Gemini 3 Flash, Gemini 3 Pro)

---


## 1.6.10
*(2025-11-10)*

#### Improvements
* Added getCsvItems function to Io.php

---


## 1.6.9
*(2025-10-10)*

#### Fixed
* Fixed the issue with multiple submenus displayed in Mirasvit menu

---


## 1.6.8
*(2025-10-10)*

#### Fixed
* Fixed the issue with the "Settings" links in the Mirasvit menu

---


## 1.6.7
*(2025-10-07)*

#### Improvements
* Reusable AI provider info block

---


## 1.6.6
*(2025-10-07)*

#### Improvements
* Hyva commerce admin theme compatibility

---


## 1.6.5
*(2025-10-02)*

#### Improvements
* License check improved

---


## 1.6.4
*(2025-10-02)*

#### Fixed
* Fixed the issue with validating ai service when api key is not set but passed through the request object

---


## 1.6.3
*(2025-09-29)*

#### Improvements
* Hyva Commerce admin theme compatibility

---


## 1.6.2
*(2025-09-24)*

#### Improvements
* AI settings improved

---


## 1.6.1
*(2025-09-22)*

#### Improvements
* AI infrastructure improved

---


## 1.6.0
*(2025-09-16)*

#### Improvements
* AI service with support of OpenAI, Claude, Gemini 

---


## 1.5.1
*(2025-08-18)*

#### Improvements
* Position of the "Comment" module

---


## 1.5.0
*(2025-07-16)*

#### Improvements
* WCAG 2.2 AA compliance

---


## 1.4.50
*(2025-07-16)*

#### Improvements
* Added functions to Io.php

---


## 1.4.49
*(2025-07-11)*

#### Fixed
* Replace referenceContainer with referenceBlock for head.additional in layout XML

---


## 1.4.48
*(2025-06-26)*

#### Improvements
* Added functions to Io.php

---


## 1.4.47
*(2025-02-03)*

#### Fixed
* Bottom manual link breaks Magento footer styles

---

## 1.4.46
*(2025-01-30)*

#### Fixed
* Bottom manual link breaks Magento footer styles

---

## 1.4.45
*(2024-12-27)*

#### Fixed
* Fixed the issue with a double slash in the resized image URL when the image subdirectory is not defined
* Move down "Get Support" item in Layered Navigation menu

---


## 1.4.44
*(2024-12-03)*

#### Fixed
* Do not show license error for submodules

---


## 1.4.43
*(2024-10-24)*

#### Fixed
* Compatibility with Magento 2.4.8

---


## 1.4.42
*(2024-08-29)*

#### Improvements
* Font Awesome fonts by layout update

---


## 1.4.41
*(2024-08-20)*

#### Improvements
* small changes for Magento marketplace

---


## 1.4.40
*(2024-08-09)*

#### Fixed
* Fixed the issue with the Io helper (main.EMERGENCY: TypeError: Mirasvit\Core\Helper\Io::filePutContents(): Return value must be of type int, true returned)

---


## 1.4.39
*(2024-08-08)*

#### Fixed
* Compatibility with CSP

---


## 1.4.38
*(2024-07-24)*

#### Improvements
* Added readDirectory and isReadable functions to Io.php

#### Fixed
* Fixed data displaying when "Today" is selected in the quick data bar

---


## 1.4.37
*(2024-06-17)*

#### Fixed
* Fixed file locking issue on shared NFS disk volumes

---


## 1.4.36
*(2024-05-22)*

#### Fixed
* Fixed the issue with the "Sitemap" and "Settings" links in the Mirasvit menu

---


## 1.4.35
*(2024-04-18)*

#### Improvements
* Added addHour and subHour in Date class

---


## 1.4.34
*(2024-04-10)*

#### Improvements
* Added Io.php

---


## 1.4.33
*(2024-03-06)*

#### Improvements
* SecureOutputService

---


## 1.4.32
*(2024-02-26)*

#### Fixed
* XSS vulnerability in dropdown menu

---


## 1.4.31
*(2023-12-19)*

#### Fixed
* Prevent errors in the admin panel caused by long menu items' names (over 50 characters)

---


## 1.4.30
*(2023-12-07)*

#### Improvements
* Extension Information bar

#### Fixed
* displaying module version installed in the app folder from the version.json

---



## 1.4.29
*(2023-12-04)*

#### Fixed
* feature request

---

## 1.4.28
*(2023-11-13)*

#### Fixed
* feature request

---


## 1.4.27
*(2023-11-09)*

#### Fixed
* Issue with Mirasvit Menu URLs (front name with hyphens)

---


## 1.4.26
*(2023-11-08)*

#### Improvements
* Added interface to request new features

#### Fixed
* Issue with Mirasvit Menu URLs (duplicated area front name)

---


## 1.4.24
*(2023-09-02)*

#### Fixed
* KB paging doesnt work if product url suffix=/

---

## 1.4.23
*(2023-08-01)*

#### Fixed
* Ignore license for SearchExtended

---



## 1.4.22
*(2023-07-25)*

#### Fixed
* Url does not include store code if suffix is included into URLs in the store config and is not added to current url

---

## 1.4.21
*(2023-07-19)*

#### Improvements
* Package List (Updates) + License Validation

---



## 1.4.20
*(2023-07-12)*

#### Fixed
* License validation issue

---

## 1.4.19
*(2023-07-10)*

#### Fixed
* Fixed the issue with config menu items when admin area front name is the same as config route (admin)

---


## 1.4.18
*(2023-06-29)*

#### Fixed
* Issue with undefined array key, if extension was installed manually (i.e. without composer)

---


## 1.4.17
*(2023-06-12)*

#### Fixed
* Issue with validation

---


## 1.4.16
*(2023-05-19)*

#### Fixed
* Conflick with cron checker and 3rd-party cron management extensions
* General error: 1525 Incorrect TIMESTAMP value when refresh statistic in reports ([#177]())

---



## 1.4.15
*(2023-05-09)*

#### Fixed
* Compatibility with Magento 2.4.5
* General error: 1525 Incorrect TIMESTAMP value when refresh statistic in reports

---

## 1.4.14
*(2023-03-10)*

#### Fixed
* Fixed the issue with Mirasvit menu in stores with custom admin URL

---


## 1.4.13
*(2023-02-08)*

#### Fixed
* Compatible with PHP < 8.0

---



### 1.4.12
*(2023-01-24)* 

* Support of 2.4.6


## 1.4.8
*(2022-09-16)*

#### Fixed
* Passing null to parameter #3 ($limit) of type int is deprecated

---

## 1.4.7
*(2022-08-23)*

#### Fixed
* quickbar additional filters(helpdesk)

---


## 1.4.6
*(2022-08-10)*

#### Fixed
* php8

---


## 1.4.5
*(2022-07-04)*

#### Fixed
* Helpdesk quickbar

---


## 1.4.4
*(2022-06-20)*

#### Improvements
* remove db_schema_whitelist.json

---


## 1.4.3
*(2022-06-09)*

#### Fixed
* RMA quickbar

---


## 1.4.2
*(2022-06-07)*

#### Fixed
* PHP8.1 compatibility issue

---



## 1.4.1
*(2022-05-19)*

#### Fixed
* Integrity constraint violation during setup:upgrade

---

## 1.4.0
*(2022-05-10)*

#### Improvements
* switch to declarative schema

---


## 1.3.4
*(2022-04-28)*

#### Fixed
* Compatibility with PHP 8

---


## 1.3.3
*(2022-02-21)*

#### Fixed
* Fixed the issue with saving configurations in store/website scope

---


## 1.3.2
*(2022-01-20)*

#### Fixed
* Compatibility with Magento 2.1
* Compatibility with PHP 8

---


## 1.3.1
*(2021-11-24)*

#### Improvements
* Date Range for Quick Data Bar

---


## 1.3.0
*(2021-11-22)*

#### Improvements
* UI Element: Quick Data Bar
* Introduced PHP strict types
* Compatibility changes: PHP >= 7.1

---


## 1.2.125
*(2021-08-11)*

#### Fixed
* Phpstan for Magento 2.4.3

---


## 1.2.124
*(2021-05-20)*

#### Improvements
* Add target to menu links

---


## 1.2.123
*(2021-05-19)*

#### Improvements
* Do not show cron message on login form after admin session destroyed

#### Fixed
* Fixed the issue with error "Base table or view not found: 1146 Table 'adminnotification_inbox' doesn't exist"

---


## 1.2.122
*(2021-04-19)*

#### Fix
* Incorrect doc links

---


## 1.2.121
*(2021-04-15)*

#### Improvements
* Mass delete schedule records

---


## 1.2.120
*(2021-01-21)*

#### Improvements
* Display actual module status (enabled/disabled) in the Developer tab

---


## 1.2.119
*(2021-01-14)*

#### Improvements
* CSS Styles

---

## 1.2.118
*(2020-12-22)*

#### Improvements
* Modules Interface

#### Fixed
* CSP for fontawesome

---


## 1.2.115
*(2020-12-08)*

#### Fixed
* Add striptags before additional css output

---


## 1.2.114
*(2020-09-17)*

#### Improvements
* Cron notifications

---


## 1.2.113
*(2020-08-27)*

#### Fixed
* Fixed brace parsing for RMA & Helpdesk
* Adjust search menu

---

## 1.2.112
*(2020-08-14)*

#### Improvements
* Compatibility with Magento 2.4. Added SerializeService.

---


## 1.2.111
*(2020-07-29)*

#### Improvements
* Compatibility with Magento 2.4

---


## 1.2.110
*(2020-04-29)*

#### Fixed
* Compatibility with Magneto 2.3.5 (error in TemplateRenderingPlugin)

---


## 1.2.109
*(2020-03-03)*

#### Fixed
* issue with cron error message when only one job can be in cron group history lifetime

---


## 1.2.108
*(2020-02-11)*

#### Fixed
* Link in cron error message
* Cataloglabel backward compatibility (cron helper)

---

## 1.2.107
*(2020-02-05)*

#### Fixed
* Cron error message

---


## 1.2.106
*(2019-12-18)*

#### Improvements
* Magento Version detection time

---


## 1.2.105
*(2019-12-16)*

#### Improvements
* Update license

---


## 1.2.104
*(2019-12-09)*

#### Fixed
* Issue with multiple cron messages after POST and GET with redirect

---


## 1.2.103
*(2019-12-05)*

#### Fixed
* Issue with "Product URL Suffix" for store view
* Issue with "FontAwesome"

---


## 1.2.102
*(2019-11-29)*

#### Fixed
* Issue with multiple cron alerts

---


## 1.2.101
*(2019-11-19)*

#### Fixed
* Issue with undefined index: instance

---


## 1.2.100
*(2019-11-19)*

#### Fixed
* Undefined index: instance

---


## 1.2.100
*(2019-11-19)*

#### Improvements
* Cron jobs checker

---

## 1.2.99
*(2019-10-17)*

#### Fixed
* Issue with redirect to dashboard
* Admin menu display issue mirasvit/module-fraud-check

---


## 1.2.98
*(2019-10-15)*

#### Fixed
* Menu issue (Magento 2.3.3)

---


## 1.2.97
*(2019-09-16)*

#### Fixed
* Replace unserialize to json_encode

---


## 1.2.96
*(2019-09-02)*

#### Fixed
* Marketplace EQP

---


## 1.2.95
*(2019-08-16)*

#### Fixed
* Marketplace EQP

---


## 1.2.94
*(2019-08-14)*

#### Fixed
* EQP

---


## 1.2.90
*(2019-06-19)*

#### Fixed
* Minification issue with moment.js

---


## 1.2.89
*(2019-04-30)*

#### Fixed
* Issue with url rewrites

---



## 1.2.88
*(2019-04-17)*

#### Fixed
* KB Category url leads to 404

---


## 1.2.87
*(2019-04-02)*

#### Improvements
* Added success message to extensions validator tool
* Comment for Menu option (visibility conditions)

---


## 1.2.86
*(2019-03-07)*

#### Fixed
* moved emails to RMA and Helpdesk

---


## 1.2.84
*(2019-03-04)*

#### Fixed
* issue with undefined index "module"

---


## 1.2.83
*(2019-02-22)*

#### Fixed
* Issue with menu

---


## 1.2.82
*(2019-02-21)*

#### Fixed
* Issue with menu

---


## 1.2.81
*(2019-02-13)*

#### Fixed
* Attachment sending for m2.3.0 (since version 1.2.77)

---


## 1.2.77
*(2019-02-12)*

#### Improvements
* Menu

#### Fixed
* Missing file error on static-content deploy

---


## 1.2.76
*(2019-01-07)*

#### Improvements
* Ability to include css styles directly in frontend (force applying extensions' css)

---


## 1.2.75
*(2018-11-29)*

#### Fixed
* Compatibility with Magento 2.3

---


## 1.2.74
*(2018-10-24)*

#### Fixed
* Cycling redirect if KB url already contains url suffix

---


## 1.2.73
*(2018-10-16)*

#### Improvements
* Removed feedback button

---


## 1.2.72
*(2018-09-24)*

#### Fixed
* Issue with manaul service

---


## 1.2.71
*(2018-09-21)*

#### Improvements
* Validator

#### Fixed
* Issue with console validator

---


## 1.2.69
*(2018-08-31)*

#### Improvements
* Added Compatibility Service

#### Fixed
* Possible issue interface initialization during setup
* Issue with developer section

---


## 1.2.68
*(2018-06-26)*

#### Improvements
* Styles for menu header on module pages

---

## 1.2.67
*(2018-06-20)*

#### Improvements
* License error message

---



## 1.2.66
*(2018-06-20)*

#### Fixed
* PhraseCollector

---


## 1.2.65
*(2018-06-14)*

#### Fixed
* Performance issue on frontend

---


## 1.2.64
*(2018-05-23)*

#### Fixed
* PhraseCollector

---



## 1.2.63
*(2018-05-17)*

#### Fixed
* Error during module update 'Duplicate entry'

#### Improvements
* Added command to collect phrases only from frontend

---


## 1.2.62
*(2018-04-23)*

#### Fixed
* Additional reset in mail transport builder
* Solve namespace conflict with other modules mirasvit/module-feed[#33](../../issues/33)

---


## 1.2.61
*(2018-03-09)*

#### Improvements
* [#13](../../issues/13)

---


#### Improvements
* Ability to add additional CSS Styles

---


## 1.2.60
*(2018-02-22)*

#### Fixed
* Redirect of cms page to page with product url suffix

---


## 1.2.59
*(2018-02-20)*

#### Improvements
* Meta modules

---


## 1.2.58
*(2018-02-19)*

#### Fixed
* Redirect loop for all 404-pages

---


## 1.2.56
*(2018-02-19)*

#### Fixed
* Layout issue with our menu

---


## 1.2.55
*(2018-02-19)*

#### Fixed
* Redirect from url without suffix to url with suffix

---


## 1.2.54
*(2018-02-15)*

#### Fixed
* Multi installation. "Autoload error: Module 'Mirasvit_Core' ..."

---


## 1.2.52
*(2018-02-12)*

#### Fixed
* Issue when the same page returns for url with suffix and without it

---


## 1.2.51
*(2018-02-06)*

#### Fixed
* Fixed incorrect cron info

---


## 1.2.50
*(2018-02-02)*

#### Fixed
* License

---


## 1.2.49
*(2018-01-15)*

#### Improvements
* Feedback

#### Fixed
* Cron expression util

---


## 1.2.48
*(2017-12-29)*

#### Fixed
* Added license exception for Mirasvit_Profiler

---


## 1.2.47
*(2017-12-26)*

#### Fixed
* removed license block

---


### 1.2.46
*(2017-12-15)*

#### Improvements
* Added YamlService for parse/dump yaml

---

### 1.2.45
*(2017-12-14)*

#### Features
* Mirasvit Extensions Validator ([#9](../../issues/9))

---

### 1.2.44
*(2017-13-05)*

#### Fixed
* PHP 5.6. compatibility of composer/manual installation

### 1.2.43
*(2017-12-05)*

#### Fixed
* Issue with composer/manual installation

---

### 1.2.42
*(2017-10-19)*

#### Improvements
* Added store to url rewrite

---

### 1.2.40
*(2017-10-19)*

#### Fixed
* Javascript errors

---

### 1.2.39
*(2017-10-06)*

#### Fixed
* Compatibility with php 5.5.9

---

### 1.2.38
*(2017-09-26)*

#### Fixed
* M2.2.0

---

### 1.2.37
*(2017-09-19)*

#### Fixed
* Escape variables during parse process

---

### 1.2.36
*(2017-09-11)*

#### Fixed
* Use function

---

### 1.2.35
*(2017-09-04)*

#### Improvements
* Manuals

---

### 1.2.34
*(2017-08-15)*

#### Fixed
* Changed mirasvit.com to lc.mirasvit.com

---

### 1.2.33
*(2017-08-14)*

#### Fixed
* HTTP to HTTPS (license)

---

### 1.2.32
*(2017-08-11)*

#### Fixed
* Styles for grids

---

### 1.2.31
*(2017-07-18)*

#### Fixed
* License
* Disabled check for mirasvit.com

---

### 1.2.30
*(2017-07-14)*

#### Improvements
* License

---

### 1.2.29
*(2017-07-11)*

#### Improvements
* License

---

### 1.2.28
*(2017-07-03)*

#### Fixed
* License
* Issue with getting latest module versions

---

### 1.2.27
*(2017-05-10)*

#### Improvements
* Moved attachments from helpdesk and rma to core

---

### 1.2.26
*(2017-04-11)*

#### Fixed
* Fixed an issue with moment.js

---

### 1.2.25
*(2017-04-05)*

#### Improvements
* Developer information

---

### 1.2.24
*(2017-03-24)*

#### Improvements
* Added utils to highlight regular expressions and cron jobs

---

### 1.2.23
*(2017-03-23)*

#### Improvements
* Highlighting for cron expression input

---

### 1.2.22
*(2017-03-16)*

#### Fixed
* EE detection

---

### 1.2.21
*(2017-01-20)*

#### Fixed
* License system

---

### 1.2.20
*(2017-01-10)*

#### Fixed
* License validation

---

### 1.2.19
*(2017-01-06)*

#### Improvements
* Added modules information

---

### 1.2.18
*(2017-01-05)*

#### Fixed
* License exception for Mirasvit_Blog

---

### 1.2.17
*(2016-12-29)*

#### Fixed
* License

---

### 1.2.16
*(2016-12-27)*

#### Improvements
* License system

---

### 1.2.15
*(2016-12-23)*

#### Improvements
* Added logo icon

---

### 1.2.14
*(2016-11-21)*

#### Improvements
* Compatibility with M 2.2.0

---

### 1.2.13
*(2016-10-18)*

#### Improvements
* Ability to include/exclude styles with font-awesome in frontend

---

### 1.2.12
*(2016-10-12)*

#### Improvements
* Use the same font-awesome.min.css for all extensions

---

### 1.2.11
*(2016-08-04)*

#### Fixed
* Correctly display Mirasvit company's logo

---

### 1.2.9
*(2016-07-07)*

#### Improvements
* Display mirasvit logo at the system menu

---

### 1.2.8
*(2016-06-24)*

#### Fixed
* Compatibility with Magento 2.1

---

### 1.2.7
*(2016-05-31)*

#### Fixed
* Fixed an issue related with di compilation

---

### 1.2.5, 1.2.6
*(2016-05-18)*

#### Fixed
* Moved font awesome styles to core (only for backend)

---

### 1.2.4
*(2016-05-03)*

#### Improvements
* Notification Feed

#### Fixed
* Fixed an issue with doubled slash in scaled image url
* Changed feed url

---

### 1.2.3
*(2016-03-16)*

#### Fixed
* Fixed an issue with image placeholder

---

### 1.2.0
*(2016-02-11)*

#### Fixed
* Fixed an issue with menu alignment on small devices
