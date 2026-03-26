## Release notes

### *SaaSScopes* exporter module

The scoping service is a new functionality created to notify consumers when any element of a scope changes,
whether it is a website or a store view.

This Magento SaaS export extension is part of such service, and it's meant to provide the data collected by the
**ScopesDataExport** module to the Feed Ingestion Service via a json post request.

Currently, all the scopes defined in the monolith such as Websites, Stores, Store Views and Customer Groups
are necessary information for most of the SaaS applications. We envision this service to serve that need
for all SaaS applications that are in production now, and those that are planned for the future.

The overall goals are the following :

* Make it easy for any SaaS application to obtain the latest snapshot of scopes defined in the commerce monolith.
* Decouple SaaS applications from how scopes are defined currently. For example, if we choose to move away
  from the current hierarchy of scopes in the monolith, the scoping service will act as an abstraction layer
  between the monolith and SaaS applications thus ensuring easy scalability in the future.

### Scopes SaaS Data Export Details

There are different aspects of the SaaSScopes module to understand:

* Data is gathered by the *ScopesDataExporter* module into the following tables:
  * *scopes_website_data_exporter*: stores the feed data for the website scopes.
  * *scopes_customergroup_data_exporter*: stores the feed data for the customer group scopes.
* Two cron jobs are created to run every minute for packing the data and send it over the wire 
  to the feed ingestion service:
  * *submit_scopes_website_feed*: packs and sends the website scopes.
  * *submit_scopes_customergroup_feed*: packs and sends the customer groups scopes.
* The current feed ingestion service accepts the feed data for websites scopes as well as for customer groups scopes
  through a single endpoint:
  * https://<feed-ingestion-service-url>/feeds/scopes/v1/{{environmentId}}
* Once the data is sent over to the feed ingestion service, a hashed data is stored in the following tables:
  * *scopes_website_data_submitted_hash*: stores the last time a given feed was sent for website scopes.
  * *scopes_customergroup_data_submitted_hash*: stores the last time a given feed was sent for customer groups scopes.

#### Scopes SaaS Feed Payloads

**Payload for sending website scopes to ingestion service**: 
```javascript
{
  "website": {
    "websiteId": "0",
    "websiteCode": "admin",
    "stores": [
      {
        "storeId": "0",
        "storeCode": "default",
        "storeViews": [
          {
            "storeViewId": "0",
            "storeViewCode": "admin"
          }
        ]
      }
    ]
  },
  "updatedAt": "2022-12-20T12:12:09",
  "deleted": false
}
```

**Payload for sending customer group scopes to ingestion service**:
```javascript
{
  "customerGroup": {
      "customerGroupId": "0",
      "customerGroupCode": "customer-group-0",
      "websites": [
        { "websiteId": "1", "websiteCode": "site-1"},
        { "websiteId": "2",  "websiteCode": "site-2"}
      ]
  },
  "updatedAt": "2022-12-20T12:12:09",
  "deleted": false
}
```
