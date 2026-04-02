# Dokan Lite REST API Reference

This document provides a comprehensive reference for the Dokan Lite REST API, useful for integrating with the Dokan multi-vendor marketplace.

## Table of Contents

1. [API Architecture](#api-architecture)
2. [Authentication & Authorization](#authentication--authorization)
3. [API Endpoints](#api-endpoints)
   - [Stores API](#stores-api)
   - [Products API](#products-api)
   - [Orders API](#orders-api)
   - [Withdrawals API](#withdrawals-api)
   - [Vendor Dashboard API](#vendor-dashboard-api)
   - [Customers API](#customers-api)
   - [Store Settings API](#store-settings-api)
   - [Product Attributes API](#product-attributes-api)
   - [Admin APIs](#admin-apis)
   - [Commission API](#commission-api)
   - [Data APIs](#data-apis)
   - [Reverse Withdrawal API](#reverse-withdrawal-api)
4. [Request/Response Formats](#requestresponse-formats)
5. [Error Handling](#error-handling)
6. [Common Use Cases](#common-use-cases)
7. [Filters & Hooks](#filters--hooks)

---

## API Architecture

### Namespaces & Versions

Dokan Lite provides multiple API versions:

| Namespace | Description |
|-----------|-------------|
| `dokan/v1` | Primary API namespace (stable) |
| `dokan/v2` | Enhanced API with additional features |
| `dokan/v3` | Latest version with improved data formatting |
| `dokan/v1/admin` | Admin-only endpoints (requires `manage_woocommerce` capability) |

### Base URL Structure

```
https://your-site.com/wp-json/dokan/v1/{endpoint}
https://your-site.com/wp-json/dokan/v2/{endpoint}
https://your-site.com/wp-json/dokan/v3/{endpoint}
```

### API Manager

**Location:** `/wp-content/plugins/dokan-lite/includes/REST/Manager.php`

- Central registry for all REST API controllers
- Automatically loads and registers 24+ controller classes on `rest_api_init` hook
- Supports dynamic filtering via `dokan_rest_api_class_map` hook

### Base Controller Classes

All Dokan REST controllers extend these base classes:

| Class | Purpose | Location |
|-------|---------|----------|
| `DokanRESTController` | Core REST functionality (CRUD operations) | `/includes/REST/DokanRESTController.php` |
| `DokanBaseController` | Base controller with response formatting | `/includes/REST/DokanBaseController.php` |
| `DokanBaseVendorController` | Vendor-specific endpoints | `/includes/REST/DokanBaseVendorController.php` |
| `DokanBaseAdminController` | Admin-only endpoints | `/includes/REST/DokanBaseAdminController.php` |
| `DokanBaseCustomerController` | Customer-specific endpoints | `/includes/REST/DokanBaseCustomerController.php` |

---

## Authentication & Authorization

### Authentication Methods

Dokan supports WordPress native authentication:

1. **Session-based** - For logged-in users (cookies)
2. **JWT Tokens** - JSON Web Tokens (requires plugin)
3. **Application Passwords** - WordPress 5.6+ feature
4. **OAuth** - Third-party OAuth plugins

### Permission System

Dokan uses WordPress capability system:

| Capability | Description |
|------------|-------------|
| `manage_woocommerce` | Admin capability - full access |
| `dokandar` | Vendor/Seller capability |
| `dokan_add_product` | Product creation capability |
| `dokan_edit_product` | Product editing capability |
| `dokan_delete_product` | Product deletion capability |

### Authorization Trait

**Location:** `/wp-content/plugins/dokan-lite/includes/Traits/VendorAuthorizable.php`

The `VendorAuthorizable` trait provides helper methods:

```php
// Check if user has vendor capability
$this->check_permission()

// Check if user can access specific vendor store
$this->can_access_vendor_store($vendor_id, $user_id)

// Get vendor ID for user (vendor or staff)
$this->get_vendor_id_for_user($user_id)

// Validate store ID in requests
$this->validate_store_id()

// Check if user is vendor staff (not owner)
$this->is_staff_only()
```

### Public Endpoints (No Authentication Required)

The following endpoints are publicly accessible:

- `GET /dokan/v1/stores` - List all stores
- `GET /dokan/v1/stores/{id}` - Get single store
- `GET /dokan/v1/stores/{id}/products` - Store products
- `GET /dokan/v1/stores/{id}/reviews` - Store reviews
- `GET /dokan/v1/products` - Product listing
- `POST /dokan/v1/stores/{id}/contact` - Contact store

All other endpoints require authentication and appropriate capabilities.

---

## API Endpoints

### Stores API

**Controller:** `StoreController.php`
**Namespace:** `dokan/v1`

#### List Stores

```
GET /dokan/v1/stores
```

**Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `search` | string | - | Search stores by name/email/login |
| `per_page` | integer | 20 | Items per page (max: 100) |
| `page` | integer | 1 | Page number |
| `orderby` | string | registered | Sort by: registered, name, rating |
| `order` | string | desc | Sort order: asc, desc |
| `status` | string | all | Filter by status: all, active, inactive |
| `featured` | boolean | false | Filter featured stores only |
| `vendor_id` | integer | - | Filter by specific vendor ID |

**Example Request:**

```bash
curl https://your-site.com/wp-json/dokan/v1/stores?per_page=10&featured=true
```

**Example Response:**

```json
[
  {
    "id": 123,
    "store_name": "Example Store",
    "shop_name": "example-store",
    "url": "https://your-site.com/store/example-store",
    "address": {
      "street_1": "123 Main St",
      "street_2": "",
      "city": "New York",
      "zip": "10001",
      "country": "US",
      "state": "NY"
    },
    "avatar": "https://your-site.com/avatar.jpg",
    "avatar_id": 456,
    "banner": "https://your-site.com/banner.jpg",
    "banner_id": 789,
    "gravatar": "https://gravatar.com/avatar/...",
    "phone": "+1234567890",
    "email": "vendor@example.com",
    "social": {
      "fb": "https://facebook.com/page",
      "twitter": "https://twitter.com/handle",
      "instagram": "https://instagram.com/handle"
    },
    "payment": "hidden",
    "show_email": false,
    "enabled": true,
    "registered": "2024-01-15T10:30:00",
    "store_open_close": {
      "enabled": "yes",
      "time": {
        "monday": {"status": "open", "opening_time": "09:00", "closing_time": "17:00"}
      }
    },
    "rating": {
      "rating": 4.5,
      "count": 42
    }
  }
]
```

#### Create Store

```
POST /dokan/v1/stores
```

**Required Capability:** `manage_woocommerce`

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `store_name` | string | Yes | Store display name |
| `email` | string | Yes | Vendor email address |
| `first_name` | string | No | Vendor first name |
| `last_name` | string | No | Vendor last name |
| `phone` | string | No | Store phone number |
| `username` | string | No | Vendor username (auto-generated if not provided) |

#### Get Single Store

```
GET /dokan/v1/stores/{id}
```

**Response:** Single store object (same format as list)

#### Update Store

```
PUT /dokan/v1/stores/{id}
```

**Required Capability:** Store owner or admin

**Parameters:** Same as create, all fields optional

#### Delete Store

```
DELETE /dokan/v1/stores/{id}
```

**Required Capability:** `manage_woocommerce`

#### Get Store Products

```
GET /dokan/v1/stores/{id}/products
```

**Parameters:** Standard WooCommerce product query parameters

#### Get Store Reviews

```
GET /dokan/v1/stores/{id}/reviews
```

**Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `per_page` | integer | 10 | Reviews per page |
| `page` | integer | 1 | Page number |

#### Contact Store

```
POST /dokan/v1/stores/{id}/contact
```

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `name` | string | Yes | Sender name |
| `email` | string | Yes | Sender email |
| `message` | string | Yes | Message content |

**Response:**

```json
{
  "message": "Email sent successfully"
}
```

#### Update Store Status

```
PUT /dokan/v1/stores/{id}/status
```

**Required Capability:** `manage_woocommerce`

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `status` | string | Yes | Store status: active, inactive |

#### Batch Update Stores

```
PUT /dokan/v1/stores/batch
```

**Required Capability:** `manage_woocommerce`

**Parameters:**

```json
{
  "update": [
    {"id": 123, "status": "active"},
    {"id": 456, "status": "inactive"}
  ]
}
```

#### Check Store Availability

```
GET /dokan/v1/stores/check
```

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `slug` | string | Yes | Store slug to check |

**Response:**

```json
{
  "available": true
}
```

---

### Products API

**Controllers:** `ProductController.php`, `ProductControllerV2.php`
**Namespace:** `dokan/v1`, `dokan/v2`

#### List Products

```
GET /dokan/v1/products
```

**Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | integer | 1 | Page number |
| `per_page` | integer | 20 | Products per page |
| `search` | string | - | Search by product name |
| `status` | string | any | Filter by: publish, draft, pending, any |
| `orderby` | string | date | Sort by: date, title, price, popularity |
| `order` | string | desc | Sort order: asc, desc |
| `seller_id` | integer | - | Filter by vendor ID |
| `only_downloadable` | boolean | false | Filter downloadable products only |
| `categories` | array | - | Filter by category IDs |

**Example Request:**

```bash
curl https://your-site.com/wp-json/dokan/v1/products?seller_id=123&status=publish&per_page=20
```

#### Create Product

```
POST /dokan/v1/products
```

**Required Capability:** `dokan_add_product`

**Parameters:**

```json
{
  "name": "Product Name",
  "type": "simple",
  "regular_price": "29.99",
  "sale_price": "19.99",
  "description": "Full product description",
  "short_description": "Brief description",
  "categories": [
    {"id": 123}
  ],
  "tags": [
    {"id": 456}
  ],
  "images": [
    {"src": "https://example.com/image.jpg"}
  ],
  "attributes": [],
  "downloadable": false,
  "virtual": false,
  "manage_stock": true,
  "stock_quantity": 100,
  "stock_status": "instock"
}
```

**Response:** Created product object with ID

#### Get Single Product

```
GET /dokan/v1/products/{id}
```

#### Update Product

```
PUT /dokan/v1/products/{id}
```

**Required Capability:** Product owner or admin

**Parameters:** Same as create, all fields optional

#### Delete Product

```
DELETE /dokan/v1/products/{id}
```

**Required Capability:** Product owner or admin

#### Product Summary

```
GET /dokan/v1/products/summary
```

**Response:**

```json
{
  "total_products": 150,
  "total_published": 120,
  "total_draft": 20,
  "total_pending": 10
}
```

#### Featured Products

```
GET /dokan/v1/products/featured
```

#### Top Rated Products

```
GET /dokan/v1/products/top_rated
```

#### Best Selling Products

```
GET /dokan/v1/products/best_selling
```

#### Latest Products

```
GET /dokan/v1/products/latest
```

#### Multi-step Categories

```
GET /dokan/v1/products/multistep-categories
```

Returns hierarchical category tree for multi-step product creation.

#### Advanced Product Filtering (V2)

```
GET /dokan/v2/products/filter-by-data
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `filter_by` | string | Filter type: featured, top_rated, best_selling |
| `per_page` | integer | Results per page |
| `page` | integer | Page number |

---

### Orders API

**Controllers:** `OrderController.php`, `OrderControllerV2.php`, `OrderControllerV3.php`
**Namespace:** `dokan/v1`, `dokan/v2`, `dokan/v3`

#### List Orders

```
GET /dokan/v1/orders
```

**Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | integer | 1 | Page number |
| `per_page` | integer | 20 | Orders per page |
| `status` | string | any | Filter by order status |
| `customer` | integer | - | Filter by customer ID |
| `product` | integer | - | Filter by product ID |
| `after` | datetime | - | Orders created after date |
| `before` | datetime | - | Orders created before date |

**Example Request:**

```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  https://your-site.com/wp-json/dokan/v1/orders?status=processing
```

#### Get Single Order

```
GET /dokan/v1/orders/{id}
```

**Required Capability:** Order belongs to vendor or user is admin

#### Update Order

```
PUT /dokan/v1/orders/{id}
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `status` | string | Order status (from `wc_get_order_statuses()`) |
| `line_items` | array | Line items to update |
| `shipping_lines` | array | Shipping information |
| `fee_lines` | array | Fee lines |
| `coupon_lines` | array | Applied coupons |

**Example - Update Order Status:**

```json
{
  "status": "completed"
}
```

#### Create Order

```
POST /dokan/v1/orders
```

**Required Capability:** `dokandar`

**Parameters:**

```json
{
  "customer_id": 123,
  "billing": {
    "first_name": "John",
    "last_name": "Doe",
    "address_1": "123 Main St",
    "city": "New York",
    "state": "NY",
    "postcode": "10001",
    "country": "US",
    "email": "john@example.com",
    "phone": "+1234567890"
  },
  "shipping": {
    "first_name": "John",
    "last_name": "Doe",
    "address_1": "123 Main St",
    "city": "New York",
    "state": "NY",
    "postcode": "10001",
    "country": "US"
  },
  "line_items": [
    {
      "product_id": 456,
      "quantity": 2
    }
  ],
  "payment_method": "cod",
  "payment_method_title": "Cash on Delivery",
  "set_paid": false
}
```

#### List Order Notes

```
GET /dokan/v1/orders/{id}/notes
```

#### Create Order Note

```
POST /dokan/v1/orders/{id}/notes
```

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `note` | string | Yes | Note content |
| `customer_note` | boolean | No | Whether note is visible to customer (default: false) |
| `added_by_user` | boolean | No | Added by user flag |

**Example:**

```json
{
  "note": "Order has been shipped",
  "customer_note": true
}
```

#### Get Single Note

```
GET /dokan/v1/orders/{id}/notes/{note_id}
```

#### Update Order Note

```
PUT /dokan/v1/orders/{id}/notes/{note_id}
```

#### Delete Order Note

```
DELETE /dokan/v1/orders/{id}/notes/{note_id}
```

#### Manage Order Downloads (V2/V3)

```
GET /dokan/v2/orders/{id}/downloads
POST /dokan/v2/orders/{id}/downloads
DELETE /dokan/v2/orders/{id}/downloads
```

**Grant Download Permission:**

```json
{
  "download_id": "abc123",
  "product_id": 456
}
```

**Revoke Download Permission:**

```json
{
  "permission_id": 789
}
```

#### Bulk Order Actions (V2)

```
POST /dokan/v2/orders/bulk-actions
```

**Parameters:**

```json
{
  "order_ids": [123, 456, 789],
  "action": "processing"
}
```

Valid actions: Any valid order status from WooCommerce

---

### Withdrawals API

**Controllers:** `WithdrawController.php`, `WithdrawControllerV2.php`
**Namespace:** `dokan/v1`, `dokan/v2`

#### List Withdrawals

```
GET /dokan/v1/withdraw
```

**Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | integer | 1 | Page number |
| `per_page` | integer | 20 | Withdrawals per page |
| `status` | string | - | Filter by: pending, approved, cancelled |
| `ids` | array | - | Filter by withdrawal IDs |
| `is_export` | boolean | false | Export flag |

**Example Response:**

```json
[
  {
    "id": 123,
    "user_id": 456,
    "amount": "500.00",
    "date": "2024-01-15T10:30:00",
    "status": "pending",
    "method": "paypal",
    "note": "",
    "ip": "192.168.1.1"
  }
]
```

#### Create Withdrawal Request

```
POST /dokan/v1/withdraw
```

**Required Capability:** `dokandar`

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `amount` | number | Yes | Withdrawal amount |
| `method` | string | Yes | Payment method (paypal, bank, skrill, etc.) |

**Example:**

```json
{
  "amount": "500.00",
  "method": "paypal"
}
```

**Response:**

```json
{
  "id": 123,
  "amount": "500.00",
  "status": "pending",
  "message": "Withdrawal request submitted successfully"
}
```

#### Get Withdrawal

```
GET /dokan/v1/withdraw/{id}
```

#### Update Withdrawal

```
PUT /dokan/v1/withdraw/{id}
```

**Required Capability:** `manage_woocommerce`

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `status` | string | Withdrawal status: approved, cancelled, pending |
| `note` | string | Admin note |

#### Delete Withdrawal

```
DELETE /dokan/v1/withdraw/{id}
```

**Required Capability:** `manage_woocommerce`

#### Get Vendor Balance

```
GET /dokan/v1/withdraw/balance
```

**Response:**

```json
{
  "current_balance": "1500.00",
  "pending_balance": "500.00",
  "withdraw_limit": "50.00",
  "withdraw_threshold": "0.00"
}
```

#### Available Payment Methods

```
GET /dokan/v1/withdraw/payment_methods
```

**Response:**

```json
{
  "paypal": "PayPal",
  "bank": "Bank Transfer",
  "skrill": "Skrill"
}
```

#### Get Withdrawal Charges

```
GET /dokan/v1/withdraw/charges
```

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `method` | string | Yes | Payment method |

**Response:**

```json
{
  "method": "paypal",
  "charge": "2.5",
  "charge_type": "percentage"
}
```

#### Batch Withdrawal Operations

```
PUT /dokan/v1/withdraw/batch
```

**Required Capability:** `manage_woocommerce`

**Parameters:**

```json
{
  "approve": [123, 456],
  "cancel": [789]
}
```

---

### Vendor Dashboard API

**Controller:** `VendorDashboardController.php`
**Namespace:** `dokan/v1`

#### Get Dashboard Statistics

```
GET /dokan/v1/vendor-dashboard
```

**Response:**

```json
{
  "balance": "1500.00",
  "orders": 42,
  "products": 15,
  "sales": "5000.00",
  "earnings": "500.00",
  "pageviews": 1234,
  "withdraw_limit": "50.00"
}
```

#### Get Vendor Profile

```
GET /dokan/v1/vendor-dashboard/profile
```

**Response:** Complete vendor profile data

#### Get Sales Report

```
GET /dokan/v1/vendor-dashboard/sales
```

**Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `from` | datetime | - | Start date (ISO 8601) |
| `to` | datetime | - | End date (ISO 8601) |
| `filter_range` | boolean | true | Enable date filtering |
| `group_by` | string | day | Group by: day, week, month, year |

**Example:**

```bash
curl https://your-site.com/wp-json/dokan/v1/vendor-dashboard/sales?from=2024-01-01&to=2024-01-31&group_by=day
```

**Response:**

```json
{
  "sales": [
    {
      "date": "2024-01-01",
      "sales": "150.00",
      "orders": 5
    },
    {
      "date": "2024-01-02",
      "sales": "200.00",
      "orders": 8
    }
  ],
  "total_sales": "5000.00",
  "total_orders": 150
}
```

#### Get Products Summary

```
GET /dokan/v1/vendor-dashboard/products
```

**Response:**

```json
{
  "total": 50,
  "publish": 40,
  "draft": 5,
  "pending": 5
}
```

#### Get Orders Summary

```
GET /dokan/v1/vendor-dashboard/orders
```

**Response:**

```json
{
  "total": 150,
  "pending": 10,
  "processing": 20,
  "completed": 110,
  "cancelled": 10
}
```

#### Get User Preferences

```
GET /dokan/v1/vendor-dashboard/preferences
```

**Response:**

```json
{
  "enable_tnc": "on",
  "store_tnc": "Terms and conditions text",
  "show_email": "yes",
  "notification_preferences": {}
}
```

---

### Customers API

**Controller:** `CustomersController.php`
**Namespace:** `dokan/v1`

#### List Customers

```
GET /dokan/v1/customers
```

**Required Capability:** `dokandar`

**Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | integer | 1 | Page number |
| `per_page` | integer | 20 | Customers per page |
| `search` | string | - | Search by name or email |
| `exclude` | string | - | Comma-separated customer IDs to exclude |

**Response:**

```json
[
  {
    "id": 123,
    "email": "customer@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "username": "johndoe",
    "date_created": "2024-01-15T10:30:00",
    "orders_count": 5,
    "total_spent": "500.00",
    "avatar_url": "https://gravatar.com/avatar/..."
  }
]
```

#### Get Single Customer

```
GET /dokan/v1/customers/{id}
```

#### Create Customer

```
POST /dokan/v1/customers
```

**Parameters:**

```json
{
  "email": "customer@example.com",
  "first_name": "John",
  "last_name": "Doe",
  "username": "johndoe",
  "password": "SecurePassword123",
  "billing": {},
  "shipping": {}
}
```

#### Update Customer

```
PUT /dokan/v1/customers/{id}
```

#### Delete Customer

```
DELETE /dokan/v1/customers/{id}
```

#### Search Customers

```
GET /dokan/v1/customers/search
```

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `search` | string | Yes | Search keyword |
| `exclude` | string | No | Comma-separated IDs to exclude |

---

### Store Settings API

**Controllers:** `StoreSettingController.php`, `StoreSettingControllerV2.php`
**Namespace:** `dokan/v1`, `dokan/v2`

#### Get Store Settings

```
GET /dokan/v1/settings
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `vendor_id` | integer | Vendor ID (defaults to current user) |

**Response:**

```json
{
  "store_name": "Example Store",
  "social": {
    "fb": "https://facebook.com/page",
    "twitter": "https://twitter.com/handle"
  },
  "payment": {
    "paypal": {
      "email": "paypal@example.com"
    },
    "bank": {
      "ac_name": "Account Name",
      "ac_number": "1234567890",
      "bank_name": "Bank Name",
      "bank_addr": "Bank Address",
      "routing_number": "987654321"
    }
  },
  "phone": "+1234567890",
  "show_email": "no",
  "address": {},
  "location": "",
  "find_address": "",
  "dokan_category": "",
  "enable_tnc": "off"
}
```

#### Update Store Settings

```
PUT /dokan/v1/settings
```

**Parameters:** Same as response, all fields optional

---

### Product Attributes API

**Controller:** `ProductAttributeController.php`
**Namespace:** `dokan/v1`

#### List Attributes

```
GET /dokan/v1/products/attributes
```

#### Create Attribute

```
POST /dokan/v1/products/attributes
```

**Parameters:**

```json
{
  "name": "Color",
  "slug": "pa_color",
  "type": "select",
  "order_by": "menu_order",
  "has_archives": true
}
```

#### Get Single Attribute

```
GET /dokan/v1/products/attributes/{id}
```

#### Update Attribute

```
PUT /dokan/v1/products/attributes/{id}
```

#### Delete Attribute

```
DELETE /dokan/v1/products/attributes/{id}
```

#### Edit Product Attributes

```
PUT /dokan/v1/products/attributes/edit-product/{id}
```

**Parameters:**

```json
{
  "attributes": [
    {
      "name": "Color",
      "options": ["Red", "Blue", "Green"],
      "visible": true,
      "variation": true
    }
  ]
}
```

#### Set Default Attribute

```
PUT /dokan/v1/products/attributes/set-default/{id}
```

**Parameters:**

```json
{
  "attribute": "pa_color",
  "term": "red"
}
```

---

### Admin APIs

**Controllers:** `AdminReportController.php`, `AdminDashboardController.php`, `AdminMiscController.php`
**Namespace:** `dokan/v1/admin`

#### Sales Summary Report

```
GET /dokan/v1/admin/report/summary
```

**Required Capability:** `manage_woocommerce`

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `from` | datetime | Start date |
| `to` | datetime | End date |
| `seller_id` | integer | Filter by vendor |

**Response:**

```json
{
  "total_sales": "50000.00",
  "total_orders": 500,
  "total_vendors": 25,
  "total_products": 1000,
  "commission_earned": "5000.00",
  "withdrawals": "3000.00"
}
```

#### Detailed Overview Report

```
GET /dokan/v1/admin/report/overview
```

**Parameters:** Same as summary

#### Dashboard Feed

```
GET /dokan/v1/admin/dashboard/feed
```

**Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `items` | integer | 5 | Number of feed items |

**Response:**

```json
[
  {
    "title": "New vendor registered",
    "content": "John Doe has registered as a vendor",
    "date": "2024-01-15T10:30:00"
  }
]
```

#### System Status

```
GET /dokan/v1/admin/dashboard/status
```

**Response:**

```json
{
  "dokan_version": "3.x.x",
  "wordpress_version": "6.x",
  "woocommerce_version": "8.x",
  "php_version": "8.1",
  "mysql_version": "8.0",
  "max_upload_size": "128M",
  "memory_limit": "256M"
}
```

#### Help Documentation

```
GET /dokan/v1/admin/help
```

#### Get Dokan Option

```
GET /dokan/v1/admin/option
```

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `section` | string | No | Option section |
| `option` | string | No | Specific option key |

---

### Commission API

**Controller:** `CommissionControllerV1.php`
**Namespace:** `dokan/v1`

#### Calculate Commission

```
GET /dokan/v1/commission
```

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `product_id` | integer | Yes | Product ID |
| `amount` | number | Yes | Amount to calculate commission on |
| `vendor_id` | integer | Yes | Vendor ID |
| `category_ids` | array | Yes | Product category IDs |
| `context` | string | No | Context: admin or seller (default: seller) |

**Example:**

```bash
curl "https://your-site.com/wp-json/dokan/v1/commission?product_id=123&amount=100&vendor_id=456&category_ids[]=10&category_ids[]=20"
```

**Response:**

```json
{
  "admin_commission": "15.00",
  "vendor_earning": "85.00",
  "commission_type": "percentage",
  "commission_rate": "15"
}
```

---

### Data APIs

**Controllers:** `DokanDataCountriesController.php`, `DokanDataContinentsController.php`
**Namespace:** `dokan/v1/data`

#### List Countries

```
GET /dokan/v1/data/countries
```

**Response:**

```json
[
  {
    "code": "US",
    "name": "United States",
    "states": [
      {"code": "NY", "name": "New York"},
      {"code": "CA", "name": "California"}
    ]
  }
]
```

#### Get Single Country

```
GET /dokan/v1/data/countries/{code}
```

#### List Continents

```
GET /dokan/v1/data/continents
```

**Response:**

```json
[
  {
    "code": "NA",
    "name": "North America",
    "countries": ["US", "CA", "MX"]
  }
]
```

#### Get Single Continent

```
GET /dokan/v1/data/continents/{code}
```

---

### Reverse Withdrawal API

**Controller:** `ReverseWithdrawalController.php`
**Namespace:** `dokan/v1`

#### Get Stores Balance

```
GET /dokan/v1/reverse-withdrawal/stores-balance
```

**Required Capability:** `manage_woocommerce`

**Response:**

```json
[
  {
    "vendor_id": 123,
    "store_name": "Example Store",
    "balance": "-500.00",
    "threshold": "1000.00"
  }
]
```

#### Check Vendor Due Status

```
GET /dokan/v1/reverse-withdrawal/vendor-due-status
```

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `vendor_id` | integer | Yes | Vendor ID to check |

**Response:**

```json
{
  "has_due": true,
  "due_amount": "500.00",
  "threshold": "1000.00"
}
```

#### List Transactions

```
GET /dokan/v1/reverse-withdrawal/transactions
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `vendor_id` | integer | Filter by vendor |
| `per_page` | integer | Transactions per page |
| `page` | integer | Page number |

#### Create Transaction

```
POST /dokan/v1/reverse-withdrawal/transactions
```

**Parameters:**

```json
{
  "vendor_id": 123,
  "trn_id": "order_456",
  "trn_type": "order_commission",
  "debit": "50.00",
  "credit": "0.00",
  "note": "Commission for order #456"
}
```

---

## Request/Response Formats

### Standard Response Format

All successful responses follow this structure:

```json
{
  "id": 123,
  "...data fields": "...",
  "_links": {
    "self": [
      {
        "href": "https://your-site.com/wp-json/dokan/v1/resource/123"
      }
    ],
    "collection": [
      {
        "href": "https://your-site.com/wp-json/dokan/v1/resource"
      }
    ]
  }
}
```

### Collection Response

List endpoints include pagination headers:

**Headers:**

```
X-WP-Total: 150
X-WP-TotalPages: 8
Link: <https://your-site.com/wp-json/dokan/v1/stores?page=2>; rel="next",
      <https://your-site.com/wp-json/dokan/v1/stores?page=8>; rel="last"
```

**Response Body:**

```json
[
  {"id": 1, "...": "..."},
  {"id": 2, "...": "..."}
]
```

### Common Query Parameters

Most collection endpoints accept these parameters:

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `context` | string | view | Context: view, edit |
| `page` | integer | 1 | Current page |
| `per_page` | integer | 20 | Items per page (max: 100) |
| `search` | string | - | Search term |
| `exclude` | array | - | IDs to exclude |
| `include` | array | - | IDs to include |
| `orderby` | string | - | Sort field |
| `order` | string | desc | Sort order: asc, desc |

---

## Error Handling

### Error Response Format

```json
{
  "code": "error_code",
  "message": "Human-readable error message",
  "data": {
    "status": 400,
    "params": {
      "field_name": "Validation error for this field"
    }
  }
}
```

### Common Error Codes

| Code | Status | Description |
|------|--------|-------------|
| `rest_invalid_param` | 400 | Invalid request parameter |
| `rest_missing_callback_param` | 400 | Required parameter missing |
| `dokan_rest_store_not_found` | 404 | Store not found |
| `dokan_rest_product_not_found` | 404 | Product not found |
| `dokan_rest_order_not_found` | 404 | Order not found |
| `dokan_rest_cannot_create` | 403 | Permission denied for create |
| `dokan_rest_cannot_edit` | 403 | Permission denied for edit |
| `dokan_rest_cannot_delete` | 403 | Permission denied for delete |
| `dokan_rest_cannot_view` | 403 | Permission denied for view |
| `no_store_found` | 404 | No vendor store found for user |
| `dokan_pro_permission_failure` | 403 | Requires Dokan Pro |
| `rest_forbidden` | 401 | Authentication required |
| `rest_cookie_invalid_nonce` | 403 | Invalid nonce |

### HTTP Status Codes

| Status | Description |
|--------|-------------|
| `200` | Success (GET, PUT) |
| `201` | Created (POST) |
| `204` | No Content (DELETE) |
| `400` | Bad Request (validation error) |
| `401` | Unauthorized (authentication required) |
| `403` | Forbidden (insufficient permissions) |
| `404` | Not Found |
| `500` | Internal Server Error |

---

## Common Use Cases

### 1. List All Active Stores

```javascript
fetch('https://your-site.com/wp-json/dokan/v1/stores?status=active&per_page=50')
  .then(response => response.json())
  .then(stores => {
    stores.forEach(store => {
      console.log(`${store.store_name}: ${store.url}`);
    });
  });
```

### 2. Get Vendor's Products

```javascript
const vendorId = 123;
fetch(`https://your-site.com/wp-json/dokan/v1/stores/${vendorId}/products`)
  .then(response => response.json())
  .then(products => {
    console.log(`Vendor has ${products.length} products`);
  });
```

### 3. Create Withdrawal Request

```javascript
const withdrawalData = {
  amount: 500,
  method: 'paypal'
};

fetch('https://your-site.com/wp-json/dokan/v1/withdraw', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer YOUR_TOKEN'
  },
  body: JSON.stringify(withdrawalData)
})
.then(response => response.json())
.then(data => {
  console.log('Withdrawal requested:', data);
});
```

### 4. Update Order Status

```javascript
const orderId = 456;

fetch(`https://your-site.com/wp-json/dokan/v1/orders/${orderId}`, {
  method: 'PUT',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer YOUR_TOKEN'
  },
  body: JSON.stringify({ status: 'completed' })
})
.then(response => response.json())
.then(order => {
  console.log('Order updated:', order);
});
```

### 5. Get Vendor Dashboard Stats

```javascript
fetch('https://your-site.com/wp-json/dokan/v1/vendor-dashboard', {
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN'
  }
})
.then(response => response.json())
.then(stats => {
  console.log('Total Sales:', stats.sales);
  console.log('Total Orders:', stats.orders);
  console.log('Balance:', stats.balance);
});
```

### 6. Search Stores

```javascript
const searchTerm = 'electronics';
fetch(`https://your-site.com/wp-json/dokan/v1/stores?search=${searchTerm}`)
  .then(response => response.json())
  .then(stores => {
    console.log(`Found ${stores.length} stores matching "${searchTerm}"`);
  });
```

### 7. Contact Store

```javascript
const storeId = 123;
const contactData = {
  name: 'John Doe',
  email: 'john@example.com',
  message: 'I have a question about your products'
};

fetch(`https://your-site.com/wp-json/dokan/v1/stores/${storeId}/contact`, {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify(contactData)
})
.then(response => response.json())
.then(data => {
  console.log(data.message);
});
```

### 8. Get Sales Report

```javascript
const from = '2024-01-01';
const to = '2024-01-31';

fetch(`https://your-site.com/wp-json/dokan/v1/vendor-dashboard/sales?from=${from}&to=${to}&group_by=day`, {
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN'
  }
})
.then(response => response.json())
.then(report => {
  console.log('Total Sales:', report.total_sales);
  report.sales.forEach(day => {
    console.log(`${day.date}: $${day.sales}`);
  });
});
```

---

## Filters & Hooks

### REST API Filters

Dokan provides numerous filters to customize REST API behavior:

#### Modify Controller Class Map

```php
add_filter( 'dokan_rest_api_class_map', function( $class_map ) {
    // Add custom controller
    $class_map['custom'] = 'My_Custom_Controller';
    return $class_map;
} );
```

#### Customize Store Collection Parameters

```php
add_filter( 'dokan_rest_api_store_collection_params', function( $params ) {
    // Add custom parameter
    $params['custom_param'] = array(
        'description' => 'Custom parameter',
        'type'        => 'string',
        'default'     => '',
    );
    return $params;
} );
```

#### Filter Store Query Arguments

```php
add_filter( 'dokan_rest_get_stores_args', function( $args, $request ) {
    // Modify query args based on request
    if ( $request->get_param( 'custom_param' ) ) {
        $args['meta_query'][] = array(
            'key'   => 'custom_meta',
            'value' => $request->get_param( 'custom_param' ),
        );
    }
    return $args;
}, 10, 2 );
```

#### Filter Store Response Data

```php
add_filter( 'dokan_vendor_to_array', function( $data, $vendor, $context ) {
    // Add custom field to store response
    $data['custom_field'] = get_user_meta( $vendor->get_id(), 'custom_meta', true );
    return $data;
}, 10, 3 );
```

#### Hook on Product Create/Update

```php
add_action( 'dokan_rest_insert_product_object', function( $product, $request, $creating ) {
    if ( $creating ) {
        // Product was just created
        error_log( 'New product created via REST API: ' . $product->get_id() );
    } else {
        // Product was updated
        error_log( 'Product updated via REST API: ' . $product->get_id() );
    }
}, 10, 3 );
```

#### Hook on Order Create/Update

```php
add_action( 'dokan_rest_insert_shop_order_object', function( $order, $request, $creating ) {
    // Perform actions after order create/update via API
    if ( $creating ) {
        // Send custom notification
        do_action( 'my_custom_order_notification', $order );
    }
}, 10, 3 );
```

#### Modify WooCommerce Product Response

```php
add_filter( 'woocommerce_rest_prepare_product_object', function( $response, $product, $request ) {
    // Add vendor info to product response
    $vendor_id = get_post_field( 'post_author', $product->get_id() );
    $vendor = dokan()->vendor->get( $vendor_id );

    $response->data['vendor'] = array(
        'id'         => $vendor->get_id(),
        'store_name' => $vendor->get_shop_name(),
        'store_url'  => $vendor->get_shop_url(),
    );

    return $response;
}, 10, 3 );
```

### Common Hooks

| Hook | Type | Description |
|------|------|-------------|
| `dokan_rest_api_class_map` | filter | Modify REST controller class map |
| `dokan_rest_get_stores_args` | filter | Filter store query arguments |
| `dokan_vendor_to_array` | filter | Customize store response data |
| `dokan_rest_insert_product_object` | action | After product create/update via API |
| `dokan_rest_insert_shop_order_object` | action | After order create/update via API |
| `dokan_rest_api_store_collection_params` | filter | Customize store list parameters |
| `dokan_rest_before_withdraw_insert` | action | Before withdrawal create |
| `dokan_rest_after_withdraw_insert` | action | After withdrawal create |

---

## Additional Resources

### Dokan Plugin Files

- **REST Manager:** `/wp-content/plugins/dokan-lite/includes/REST/Manager.php`
- **Controllers:** `/wp-content/plugins/dokan-lite/includes/REST/*.php`
- **Traits:** `/wp-content/plugins/dokan-lite/includes/Traits/VendorAuthorizable.php`

### Related Documentation

- [WordPress REST API Handbook](https://developer.wordpress.org/rest-api/)
- [WooCommerce REST API](https://woocommerce.github.io/woocommerce-rest-api-docs/)
- [Dokan Documentation](https://dokan.co/docs/)
- [Dokan Developer Documentation](https://github.com/getdokan/dokan)

### Testing Tools

- **Postman** - API testing and documentation
- **Insomnia** - REST client
- **WP-CLI** - Command-line REST testing: `wp rest list-routes`
- **Browser Extensions** - RESTClient, Talend API Tester

### Authentication Plugins

- **JWT Authentication for WP REST API** - Token-based auth
- **Application Passwords** - Built into WordPress 5.6+
- **OAuth 2.0** - Various OAuth plugins available

---

## Notes

- All endpoints require proper authentication unless explicitly marked as public
- Response formats may vary slightly between API versions (v1, v2, v3)
- Some endpoints require Dokan Pro for full functionality
- Rate limiting may be applied by hosting provider or security plugins
- Always validate and sanitize input data
- Use proper error handling for production applications
- Test API calls in a development environment first

---

**Last Updated:** 2026-01-22
**Dokan Lite Version:** 3.x
**WordPress Version:** 6.x+
**WooCommerce Version:** 8.x+