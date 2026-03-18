# phpBMS - Data Models

**Date:** 2026-03-17

## Overview

phpBMS uses MySQL 8.0 with MyISAM storage engine (no foreign key constraints enforced by DB — referential integrity is maintained by application code). All tables use `utf8_unicode_ci` collation.

**Key conventions:**
- Every table has an `id` INT AUTO_INCREMENT PRIMARY KEY
- System audit columns: `createdby` (INT), `creationdate` (DATETIME), `modifiedby` (INT), `modifieddate` (TIMESTAMP)
- Soft-delete via `inactive` TINYINT rather than hard deletes

---

## Base Module Tables

### `users`
User accounts for application login.

| Column | Type | Null | Notes |
|---|---|---|---|
| id | int unsigned | NO PK | Auto-increment |
| login | varchar(32) | NO | Unique username |
| firstname | varchar(64) | YES | |
| lastname | varchar(64) | YES | |
| email | varchar(128) | YES | |
| phone | varchar(32) | YES | |
| department | varchar(64) | YES | |
| employeenumber | varchar(32) | YES | |
| admin | tinyint | NO | 1=admin (bypasses all role checks) |
| password | varchar(255) | YES | bcrypt hash via password_hash() |
| revoked | tinyint | NO | 1=login disabled |
| portalaccess | tinyint | NO | 1=client portal access |
| lastlogin | datetime | YES | |
| createdby/modifiedby | int | | FK→users.id |
| creationdate/modifieddate | datetime/timestamp | | |

### `roles`
Role definitions for RBAC.

| Column | Type | Notes |
|---|---|---|
| id | int PK | |
| name | varchar(64) | Role name |
| description | text | |

### `rolestousers`
Many-to-many: users ↔ roles.

| Column | Type | Notes |
|---|---|---|
| userid | int | FK→users.id |
| roleid | int | FK→roles.id |

### `menu`
Hierarchical navigation menu.

| Column | Type | Notes |
|---|---|---|
| id | int PK | |
| name | varchar(64) | Display label |
| link | varchar(255) | URL path |
| parentid | int | Self-reference (0=top-level) |
| displayorder | int | Sort order |
| roleid | int | Required role to see this item |

### `notes`
Notes, tasks, events, and system messages.

| Column | Type | Notes |
|---|---|---|
| id | int PK | |
| type | enum(NT,TS,EV,SY) | Note/Task/Event/System |
| subject | varchar(128) | |
| content | text | |
| assignedtoid | int | FK→users.id |
| attachedid | int | Record this note belongs to |
| attachedtabledefid | int | FK→tabledefs.id |
| parentid | int | Self-reference for note threads |
| importance | int | Priority level |
| status | varchar(32) | |
| startdate/enddate | date | |
| starttime/endtime | time | |
| completed | tinyint | |
| private | tinyint | |
| repeating | tinyint | |
| repeattype | varchar(32) | Daily/Weekly/Monthly/Yearly |
| repeatuntil | date | |
| repeatevery | int | Frequency |
| repeattimes | int | Max repetitions |

### `tabledefs`
Metadata for every searchable/editable entity in the UI.

| Column | Type | Notes |
|---|---|---|
| id | int PK | Referenced as `tabledefid` everywhere |
| displayname | varchar(64) | Human name shown in UI |
| maintable | varchar(64) | Database table name |
| querytable | varchar(255) | SQL fragment for search query |
| editfile | varchar(255) | PHP file path for add/edit |
| editroleid | int | Role required to edit |
| addroleid | int | Role required to add |
| searchroleid | int | Role required to search |
| moduleid | int | FK→modules.id |
| defaultwhereclause | text | Default filter SQL |
| defaultsortorder | varchar(128) | Default ORDER BY |

### `tablecolumns`
Column display config for search results.

| Column | Type | Notes |
|---|---|---|
| tabledefid | int | FK→tabledefs.id |
| name | varchar(64) | Column header label |
| column | varchar(128) | SQL expression |
| format | enum | date/time/currency/boolean/datetime/filelink/noencoding/bbcode |
| displayorder | int | |
| roleid | int | Role required to see column |

### `tablefindoptions`
Quick-filter presets shown above search results.

### `tablegroupings`
Result grouping configuration.

### `tablesearchablefields`
Fields available in the advanced search form.

### `tableoptions`
Custom action buttons in search results.

### `usersearches`
Saved searches and sorts per user.

| Column | Type | Notes |
|---|---|---|
| tabledefid | int | FK→tabledefs.id |
| userid | int | FK→users.id |
| type | enum(SCH,SRT) | Search or Sort |
| name | varchar(64) | Saved search name |
| search | text | Serialized search criteria |

### `modules`
Installed module registry.

| Column | Type | Notes |
|---|---|---|
| id | int PK | |
| displayname | varchar(64) | |
| name | varchar(64) | Directory name |
| version | varchar(32) | |

### `reports`
Report definitions.

| Column | Type | Notes |
|---|---|---|
| id | int PK | |
| name | varchar(64) | |
| reportfile | varchar(255) | PHP file path |
| tabledefid | int | FK→tabledefs.id |
| roleid | int | Required role |

### `settings`
Application configuration loaded into PHP constants.

| Column | Type | Notes |
|---|---|---|
| name | varchar(64) PK | Constant name |
| value | text | Constant value |

### `choices`
Dropdown list values for `inputChoiceList` fields.

| Column | Type | Notes |
|---|---|---|
| id | int PK | |
| listname | varchar(64) | Groups choices by purpose |
| thevalue | varchar(64) | Display/store value |

### `log`
Audit and error log.

| Column | Type | Notes |
|---|---|---|
| id | int PK | |
| type | varchar(25) | Log category |
| userid | int | FK→users.id |
| ip | varchar(45) | Client IP |
| value | text | Log message |
| stamp | timestamp | Auto-set |

---

## BMS Module Tables

### `clients`
Customer and prospect records.

| Column | Type | Notes |
|---|---|---|
| id | int PK | |
| type | enum(prospect,client) | Prospects can be promoted to clients |
| firstname/lastname | varchar(64) | |
| company | varchar(128) | |
| inactive | tinyint | Soft-delete |
| category | varchar(64) | |
| homephone/workphone/mobilephone/fax/otherphone | varchar(32) | |
| email | varchar(128) | |
| webaddress | varchar(128) | |
| address/address2/city/state/postalcode/country | varchar | Primary address |
| salesmanagerid | int | FK→users.id |
| leadsource | varchar(64) | |
| paymentmethodid | int | FK→paymentmethods.id |
| shippingmethodid | int | FK→shippingmethods.id |
| discountid | int | FK→discounts.id |
| taxareaid | int | FK→taxareas.id |
| hascredit | tinyint unsigned | 1=credit limit applies |
| creditlimit | double | |
| username/password | varchar | Client portal login |

### `addresses`
Reusable address book (shared via `addresstorecord`).

| Column | Type | Notes |
|---|---|---|
| id | int PK | |
| title | varchar(64) | Address label |
| shiptoname | varchar(128) | |
| address/address2/city/state/postalcode/country | varchar | |
| phone/email | varchar | |
| notes | text | |

### `addresstorecord`
Links addresses to any record type.

| Column | Type | Notes |
|---|---|---|
| tabledefid | int | FK→tabledefs.id (which entity type) |
| recordid | int | ID in that entity's table |
| addressid | int | FK→addresses.id |
| defaultshipto | tinyint unsigned | 1=default ship-to |
| primary | tinyint unsigned | 1=primary address |

### `invoices`
Quotes, orders, invoices, and voided documents.

| Column | Type | Notes |
|---|---|---|
| id | int PK | Auto-starts at 1000 |
| clientid | int | FK→clients.id |
| type | enum(Quote,Order,Invoice,VOID) | Document lifecycle stage |
| statusid | int unsigned | FK→invoicestatuses.id |
| orderdate/invoicedate/requireddate | date | |
| assignedtoid | int unsigned | FK→users.id |
| ponumber | varchar(32) | Customer PO number |
| discountid | int | FK→discounts.id |
| discountamount | double | Calculated discount |
| taxareaid | int | FK→taxareas.id |
| taxpercentage | double | Tax rate at time of invoice |
| totaltni | double | Total non-taxable items |
| totaltaxable | double | Taxable subtotal |
| tax | double | Tax amount |
| shippingmethodid | int unsigned | FK→shippingmethods.id |
| totalweight | double | |
| shipping | double | Shipping cost |
| totalcost | double | Grand total |
| amountpaid | double | |
| paymentmethodid | int unsigned | FK→paymentmethods.id |
| ccnumber/ccexpiration/ccverification | varchar | CC fields (nullable) |
| bankname/checkno | varchar | Check fields (nullable) |
| routingnumber/accountnumber | int unsigned | Bank routing/account (nullable) |
| transactionid | varchar(64) | Payment processor transaction ID |
| shipping address fields (5) | varchar | Copied from client at invoice time |
| billingaddressid/shiptoaddressid | int | FK→addresses.id |
| shiptosameasbilling | tinyint unsigned | |
| weborder | tinyint | |
| printinstructions/specialinstructions | text | |

**Indexes:** clientid

### `lineitems`
Individual line items within invoices.

| Column | Type | Notes |
|---|---|---|
| id | int PK | |
| invoiceid | int | FK→invoices.id |
| productid | int | FK→products.id |
| displayorder | int | Sort order on invoice |
| quantity | double | |
| unitcost | double | Cost to business |
| unitprice | double | Price charged to client |
| unitweight | double | |
| memo | text | Line item note |
| taxable | tinyint | 1=included in tax calculation |

**Indexes:** invoiceid, productid

### `products`
Product catalog.

| Column | Type | Notes |
|---|---|---|
| id | int PK | |
| categoryid | int | FK→productcategories.id |
| partnumber | varchar(32) | Unique |
| partname | varchar(128) | |
| description | text | |
| unitprice | double | |
| unitcost | double | |
| weight | double | |
| webenabled | tinyint | Show on web storefront |
| thumbnail | mediumblob | Product image (stored in DB) |
| keywords | varchar(255) | Search keywords |

### `productcategories`
Product grouping.

| Column | Type | Notes |
|---|---|---|
| id | int PK | |
| name | varchar(64) | |
| inactive | tinyint | |
| webenabled | tinyint | |
| webdisplayname | varchar(64) | |

### `prerequisites`
Product dependency relationships.

| Column | Type | Notes |
|---|---|---|
| id | int PK | |
| parentid | int | FK→products.id |
| childid | int | FK→products.id (prerequisite) |

### `discounts`
Discount rules.

| Column | Type | Notes |
|---|---|---|
| id | int PK | |
| name | varchar(64) | |
| type | enum(percent,amount) | |
| value | double | Percent or fixed amount |
| inactive | tinyint | |

### `taxareas`
Tax jurisdictions.

| Column | Type | Notes |
|---|---|---|
| id | int PK | |
| name | varchar(64) | |
| percentage | double | Combined tax rate |
| description | text | |

### `shippingmethods`
Available shipping options.

| Column | Type | Notes |
|---|---|---|
| id | int PK | |
| name | varchar(64) | |
| description | text | |
| carride | varchar(32) | Carrier code (for UPS lookup) |

### `paymentmethods`
Payment type definitions.

| Column | Type | Notes |
|---|---|---|
| id | int PK | |
| name | varchar(64) | |
| type | enum(draft,charge,receivable,other) | Determines which form fields are shown |
| onlineprocess | tinyint | 1=has online processing |
| processscript | varchar(128) | PHP script for online processing |
| inactive | tinyint | |
| priority | int | Display order |

### `invoicestatuses`
Workflow states for invoices.

| Column | Type | Notes |
|---|---|---|
| id | int unsigned PK | |
| name | varchar(64) | E.g. "Pending", "Shipped" |
| color | varchar(7) | Hex color for UI |
| setreadytopost | tinyint unsigned | Auto-set readytopost when status applied |
| invoicedefault | tinyint unsigned | Default status for new invoices |
| defaultassignedtoid | int unsigned | Default assignee |
| inactive | tinyint unsigned | |
| priority | int unsigned | Display order |

### `aritems`
Accounts Receivable items.

| Column | Type | Notes |
|---|---|---|
| id | int unsigned PK | |
| clientid | int unsigned | FK→clients.id |
| type | enum(invoice,credit,service charge) | |
| status | enum(open,closed) | |
| itemdate | date | |
| relatedid | int unsigned | FK→invoices.id (for invoice type) |
| amount | double | Original amount |
| paid | double | Amount paid so far |
| aged1/aged2/aged3 | tinyint unsigned | Aging bucket flags |
| title | varchar(128) | Description |
| posted | tinyint unsigned | 1=posted to accounting |

### `receipts`
Payment receipts.

| Column | Type | Notes |
|---|---|---|
| id | int unsigned PK | |
| clientid | int unsigned | FK→clients.id |
| amount | double | Total receipt amount |
| receiptdate | date | |
| status | enum(open,collected) | |
| readytopost | tinyint unsigned | |
| posted | tinyint unsigned | |
| paymentmethodid | int unsigned | FK→paymentmethods.id |
| ccnumber/ccexpiration/ccverification | varchar | CC fields (nullable) |
| bankname/checkno | varchar | Check fields (nullable) |
| routingnumber/accountnumber | int unsigned | Bank fields (nullable) |
| transactionid | varchar(64) | |
| paymentother | varchar(128) | FK→choices listname="receiptother" |
| memo | text | |

### `receiptitems`
Links receipts to AR items (how a receipt is applied).

| Column | Type | Notes |
|---|---|---|
| id | int PK | |
| receiptid | int | FK→receipts.id |
| aritemid | int | FK→aritems.id |
| applied | double | Amount applied to this AR item |
| discount | double | Discount taken |
| taxadjustment | double | Tax adjustment |

### `clientemailprojects`
Email campaign templates.

| Column | Type | Notes |
|---|---|---|
| id | int PK | |
| name | varchar(64) | |
| userid | int | FK→users.id (owner) |
| emailto | varchar(9) | Which client email field to use |
| emailfrom | varchar(128) | |
| subject | varchar(128) | |
| body | text | Email body template |
| lastrun | timestamp | Last campaign run time |

---

## Recurring Invoices Module Tables

### `recurringinvoices`
Recurring invoice schedules.

| Column | Type | Notes |
|---|---|---|
| id | int PK | |
| invoiceid | int | FK→invoices.id (template invoice) |
| name | varchar(64) | Schedule name |
| type | varchar(45) | Daily/Weekly/Monthly/Yearly |
| until | date | Run until this date |
| every | int unsigned | Frequency (every N periods) |
| times | int unsigned | Max repetitions |
| eachlist | varchar(128) | Days of week (for weekly type) |
| ontheday | int unsigned | Day of month |
| ontheweek | int unsigned | Week of month |
| statusid | int | FK→invoicestatuses.id (for generated invoices) |
| assignedtoid | int | FK→users.id |
| notificationroleid | int | FK→roles.id |
| firstrepeat/lastrepeat | date | Calculated dates |
| timesrepeated | int | Count of invocations |
| includepaymenttype | tinyint | Copy payment method to generated invoice |
| includepaymentdetails | tinyint | Copy CC/bank details |

---

## Entity Relationship Summary

```
users ◄──────── rolestousers ────────► roles
  ▲
  │ (createdby/modifiedby on every table)

clients ──────► invoices ──────► lineitems ──────► products
    │               │                                   │
    │               └──► aritems ◄──── receiptitems ◄── receipts
    │
    └──► addresstorecord ──────► addresses

invoices ──────► invoicestatuses
         ──────► discounts
         ──────► taxareas
         ──────► shippingmethods
         ──────► paymentmethods

tabledefs ──────► tablecolumns
          ──────► tablegroupings
          ──────► tablefindoptions
          ──────► tablesearchablefields
          ──────► tableoptions
          ──────► usersearches
          ──────► reports
          ──────► addresstorecord (via tabledefid)
          ──────► notes (via attachedtabledefid)
```

---
_Generated using BMAD Method `document-project` workflow — 2026-03-17_
