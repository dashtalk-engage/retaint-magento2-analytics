# Retaint Analytics for Magento 2

Retaint provides ecommerce reporting with actionable insights. The extension exposes
secure, read-only JSON feeds (orders, products, customers, inventory, refunds, abandoned
carts and more) that the Retaint collector at `https://datapulse.retaint.com` polls on a
schedule.

> **How it works:** this is a *pull* integration. The module does **not** run cron jobs or
> call out to Retaint for data. Instead it publishes authenticated endpoints on your store,
> and the Retaint collector fetches from them. The only outbound call the module makes is
> the optional one-click **Connect** handshake described below.

## Requirements

- Magento Open Source / Adobe Commerce **2.4.4 – 2.4.8** (every currently-supported release)
- PHP **8.1, 8.2, 8.3, or 8.4**

The module installs on whichever PHP version your Magento release supports — PHP 8.1 (2.4.4+)
through PHP 8.4 (recommended for 2.4.8).

## Installation

### Quick install (recommended)

Copy this module folder to your Magento server, then run the bundled installer from
inside it. It detects your Magento root and deploy mode, installs the module, and prints
your Store URL, Secret Key, and a self-check:

```bash
./install.sh /path/to/magento      # or run with no argument from inside <magento>/app/code/Retaint/Analytics
```

### Manual install

```bash
composer require retaint/magento2-analytics
bin/magento module:enable Retaint_Analytics
bin/magento setup:upgrade
bin/magento setup:di:compile        # production mode only
bin/magento cache:flush
```

On install the module generates a unique **Secret Key** for your store and enables the feeds.

## Connect your store

Go to **Stores → Configuration → Retaint → Analytics → Retaint Settings**.

- **One click (recommended):** press **Connect to Retaint**. The store sends its URL and
  Secret Key to Retaint and polling begins automatically.
- **Manual:** copy **Your Secure Store URL** and **Your Secret Key** into your Retaint
  dashboard.

## Authentication

Every data endpoint requires the store's Secret Key, sent in the request header:

```
X-Retaint-Token: <your secret key>
```

Requests without a valid token receive `HTTP 401`. The token is compared in constant time
(`hash_equals`) and is never written to logs.

## Endpoints

All endpoints are `GET` under `/retaint/module/`:

| Endpoint | Returns |
|---|---|
| `/retaint/module/orders` | orders |
| `/retaint/module/orderitems` | order line items |
| `/retaint/module/products` | products |
| `/retaint/module/categories` | categories |
| `/retaint/module/customers` | customers |
| `/retaint/module/inventory` | stock levels |
| `/retaint/module/refunds` | credit memos |
| `/retaint/module/refunditems` | refunded line items |
| `/retaint/module/abandonedcarts` | abandoned carts |
| `/retaint/module/subscribers` | newsletter subscribers |
| `/retaint/module/productalerts` | product alert sign-ups |
| `/retaint/module/stores` | store views |
| `/retaint/module/extensions` | installed extensions |
| `/retaint/module/version` | module/platform versions (unauthenticated handshake) |

Common query parameters: `page_size`, `page_num`, `start_date`, `end_date`,
`updated_start_date`, `updated_end_date`, `sort_dir`, `id`.

## Support

Questions or help: [support@retaint.com](mailto:support@retaint.com) · <https://retaint.com>

## License

[OSL-3.0](https://opensource.org/licenses/OSL-3.0)
