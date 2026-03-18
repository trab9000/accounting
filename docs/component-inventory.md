# phpBMS - Component Inventory

**Date:** 2026-03-17

## PHP Core Classes

### `db` — Database Abstraction
**File:** `include/db.php`

| Method | Purpose |
|---|---|
| `__construct($connect, ...)` | Init, optional auto-connect |
| `connect()` | `mysqli_connect`, supports persistent via `p:` prefix |
| `selectSchema($schema)` | `mysqli_select_db` |
| `query($sql)` | Execute SQL, return result or false; logs errors |
| `fetchArray($result)` | `mysqli_fetch_assoc` wrapper |
| `numRows($result)` | Row count |
| `seek($result, $row)` | `mysqli_data_seek` |
| `numFields($result)` | Field count |
| `fieldTable($result, $offset)` | Table name for field at offset |
| `fieldName($result, $offset)` | Field name at offset |
| `tableInfo($tablename)` | `SHOW COLUMNS` → array of field metadata |
| `insertId()` | `mysqli_insert_id` |
| `affectedRows()` | `mysqli_affected_rows` |
| `escape($value)` | `mysqli_real_escape_string` |
| `_normalizeFieldType($typestr)` | Maps MySQL 8 type strings to simplified names (strips ` unsigned`) |
| `_parseFieldLength($typestr)` | Extracts length from `varchar(255)` → 255 |
| `_parseFieldFlags($row)` | Builds flags string: `not_null primary_key auto_increment` |

---

### `appError` — Error Handling
**File:** `include/session.php`

| Method | Purpose |
|---|---|
| `__construct($code, $message, $context, $show, $stop, $log, $format)` | Create and optionally display/log/halt |
| `display()` | Output error in xhtml, json, or plain format |
| `logError()` | Write to `log` table via `phpbmsLog` |

---

### `phpbmsLog` — Audit Logging
**File:** `include/session.php`

| Method | Purpose |
|---|---|
| `__construct($db, $type, $userid, $ip, $value)` | Create log entry |
| `sendLog()` | INSERT into `log` table |

---

### `phpbmsSession` — Session & Settings Management
**File:** `include/session.php`

| Method | Purpose |
|---|---|
| `loadDBSettings()` | Load `settings` table into PHP constants |
| `loadSettings()` | Load defaults from `defaultsettings.php` |
| `startSession()` | `session_start()` with configured session name |
| `verifyAPIlogin($user, $pass)` | HTTP Basic auth for API endpoints |

---

### `phpbms` — Application Class
**File:** `include/common_functions.php`

| Property/Method | Purpose |
|---|---|
| `$db` | Global db instance |
| `$modules` | Array of installed modules |
| `$cssIncludes[]` | CSS files to include in `<head>` |
| `$jsIncludes[]` | JS files to include at page bottom |
| `$topJS[]` | Inline JS for `<head>` |
| `$bottomJS[]` | Inline JS for page bottom |
| `$onload[]` | JS onload handlers |
| `getModules()` | Query `modules` table, populate `$modules` |
| `showCssIncludes()` | Render `<link>` tags |
| `showJsIncludes()` | Render `<script>` tags |
| `displayRights($roleid)` | Check current user has role |

---

### `phpbmsTable` — ORM Base Class
**File:** `include/tables.php`

| Method | Purpose |
|---|---|
| `__construct($db, $tabledefid)` | Load tabledef metadata + column info |
| `getTableInfo()` | Read `tabledefs` row; call `db->tableInfo($maintable)` |
| `getRecord($id)` | SELECT by PK |
| `getDefaults()` | Return default values for new record form |
| `insertRecord($variables, $createdby, $overrideID)` | INSERT; iterates `$this->fields` |
| `updateRecord($variables, $modifiedby)` | UPDATE by id |
| `deleteRecord($id)` | DELETE by PK |
| `processAddEditPage()` | Dispatch: GET→getRecord/getDefaults; POST→insert/update |
| `prepareFieldForSQL($value, $type, $flags)` | Format PHP value as SQL literal |
| `getDefaultByType($type, $forSQL)` | Get empty default for a field type |

**Module classes that extend phpbmsTable:**

| Class | File | Key Overrides |
|---|---|---|
| `users` | `modules/base/include/users.php` | `insertRecord`, `updateRecord` (role assignment) |
| `roles` | `modules/base/include/roles.php` | `insertRecord`, `updateRecord` (user assignment) |
| `clients` | `modules/bms/include/clients.php` | `getDefaults`, `prepareVariables` |
| `invoices` | `modules/bms/include/invoices.php` | `insertRecord`, `updateRecord`, total calculation |
| `products` | `modules/bms/include/products.php` | image handling |
| `receipts` | `modules/bms/include/receipts.php` | `insertRecord`, `updateRecord`, AR item updates |
| `aritems` | `modules/bms/include/aritems.php` | AR balance tracking |
| `discounts` | `modules/bms/include/discounts.php` | |
| `recurringinvoices` | `modules/recurringinvoices/include/recurringinvoices.php` | schedule management |
| `sampletable` | `modules/sample/include/sampletable.php` | template/example |

---

### `phpbmsForm` — Form Rendering
**File:** `include/fields.php`

| Method | Purpose |
|---|---|
| `addField($input)` | Register an input field |
| `jsMerge()` | Merge field JS into `$phpbms->bottomJS` — MUST call before `header.php` |
| `showField($name)` | Render the HTML for a named field |
| `showCreateModify($phpbms, $record)` | Render created/modified audit info |
| `startForm()` / `endForm()` | `<form>` open/close tags |

**Input Field Classes:**

| Class | HTML Output | Key Parameters |
|---|---|---|
| `inputField` | `<input type="text">` | name, value, label, required, validate, size, maxlength |
| `inputTextarea` | `<textarea>` | name, value, label, required, rows, cols |
| `inputBasicList` | `<select>` | name, value, options array |
| `inputCheckBox` | `<input type="checkbox">` | name, value, label |
| `inputDatePicker` | text + calendar widget | name, value, label, required |
| `inputTimePicker` | text + time widget | name, value, label, required |
| `inputSmartSearch` | autocomplete text | db, name, label, value, tableType, required, tabledefid |
| `inputCurrency` | currency text field | name, value, label, required |
| `inputChoiceList` | `<select>` from DB | db, name, value, listname, displayfield |

---

### `displayTable` — Search Results Renderer
**File:** `include/search_class.php`

Renders the list/search view for any entity using `tabledefs` metadata.

| Method | Purpose |
|---|---|
| `__construct($db, $tabledefid)` | Load table def, columns, groupings, find options |
| `getTableDef()` | Load `tabledefs` row |
| `getTableColumns()` | Load `tablecolumns` (filtered by role) |
| `getTableGroupings()` | Load `tablegroupings` |
| `showResultHeader()` | Render column headers |
| `showResultRecords($result)` | Render result rows with formatting |

---

### `printer` — Print/Report Coordinator
**File:** `include/print_class.php`

| Method | Purpose |
|---|---|
| `__construct($db, $tabledefid)` | Load available reports |
| `saveVariables()` | Persist where/sort to session |
| `getSaved()` | Retrieve saved where/sort |
| `donePrinting()` | Clean up after report |

---

### `topMenu` — Navigation Menu
**File:** `include/menu_class.php`

| Method | Purpose |
|---|---|
| `__construct($db)` | Load top-level menu items from `menu` table |
| `getSubItems($parentid)` | Recursive sub-menu query |
| `display()` | Render `<ul>` navigation HTML |

---

### `phpbmsReport` — Report Base Class
**File:** `report/report_class.php`

| Method | Purpose |
|---|---|
| `__construct($db, $tabledefid)` | Init with db and table context |
| `setupFromPrintScreen()` | Load where/sort from print_class session |
| `assembleSQL()` | Build full SELECT with WHERE + ORDER BY |

**PDF helper classes** (`report/pdfreport_class.php`):

| Class | Purpose |
|---|---|
| `pdfColumn` | Defines a PDF column: title, fieldname, size, format, align |
| `pdfColor` | RGB color (r, g, b) |
| `pdfFont` | Font config (family, style, size) |
| `pdfStyle` | Combined: font + textColor + backgroundColor |

---

### `FPDF` — PDF Generation
**File:** `fpdf/fpdf.php`

Core PDF library. Used by all PDF reports. Key methods: `Cell()`, `MultiCell()`, `Ln()`, `Image()`, `SetFont()`, `SetFillColor()`, `Output()`.

---

## Auxiliary Classes

### `receiptitems`
**File:** `modules/bms/include/receipts.php`

| Method | Purpose |
|---|---|
| `__construct($db)` | |
| `get($receiptid)` | Get AR items for a receipt |
| `show($result, $posted, $id)` | Render receipt items table |
| `set($itemslist, $receiptid, $clientid, $createdby)` | Sync receipt → AR item links |

### `productLookup`
**File:** `modules/bms/invoices_lineitem_ajax.php`

| Method | Purpose |
|---|---|
| `meetsPrereq($productid, $clientid)` | Check product prerequisites for client |

---

## JavaScript Components

### Core (loaded on all pages)

| File | Purpose |
|---|---|
| `common/javascript/common.js` | Currency formatting, date utilities, AJAX helpers |
| `common/javascript/queryfunctions.js` | Search list sorting, column toggling, AJAX result refresh |
| `common/javascript/fields.js` | Form validation: required, integer, email, URL checks |
| `common/javascript/menu.js` | Top navigation hover/expand behavior |
| `common/javascript/moo/moo.fx.js` | MooTools effects (show/hide animations) |
| `common/javascript/moo/prototype.lite.js` | Prototype.js compatibility shim |

### Feature-specific

| File | Purpose |
|---|---|
| `common/javascript/smartsearch.js` | Autocomplete type-ahead; calls `smartsearch.php` |
| `common/javascript/choicelist.js` | Dynamic `<select>` population; calls `choicelist.php` |
| `common/javascript/datepicker.js` | Calendar widget; calls `datepicker.php` |
| `common/javascript/timepicker.js` | Time picker widget; calls `timepicker.php` |
| `common/javascript/print.js` | Report/print selection modal |
| `modules/bms/javascript/invoice.js` | Invoice line item CRUD, total recalculation via AJAX |
| `modules/bms/javascript/receipt.js` | Receipt form: payment type switching, AR item loading |
| `modules/bms/javascript/paymentprocess.js` | Online payment process button handler |

---

## AJAX Endpoints

| File | Purpose | Returns |
|---|---|---|
| `smartsearch.php` | Autocomplete search results | HTML `<option>` list |
| `choicelist.php` | Choice list options | HTML |
| `datepicker.php` | Calendar data | HTML |
| `timepicker.php` | Time slots | HTML |
| `checkunique.php` | Field uniqueness check | text (0/1) |
| `modules/base/notes_ajax.php` | Note CRUD + email | JSON |
| `modules/base/snapshot_ajax.php` | Dashboard data | JSON |
| `modules/bms/invoices_lineitem_ajax.php` | Line item product lookup | JSON |
| `modules/bms/invoices_client_ajax.php` | Client → invoice defaults | JSON |
| `modules/bms/invoices_addresses_ajax.php` | Address list for client | HTML |
| `modules/bms/receipts_aritem_ajax.php` | Open AR items for client | HTML |
| `modules/bms/quickview_ajax.php` | Quick record preview | HTML |

---
_Generated using BMAD Method `document-project` workflow — 2026-03-17_
