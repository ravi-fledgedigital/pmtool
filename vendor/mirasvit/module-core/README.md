Module can contain:
- Features which are used by our other modules (License, Menu, CompatibilityService)

Please discuss with dev team in our slack before adding any new features to this module.

#### Include wont-awesome in extension
Add to the layout file the next line ```<update handle="mstcore_fontawesome"/>``` like here

```xml
<?xml version="1.0"?>
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
    <update handle="mstcore_fontawesome"/>
    
    ...
</page>
```
