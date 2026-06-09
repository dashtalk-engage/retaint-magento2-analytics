# Retaint Analytics — Magento 2 Module Rebuild Design

**Date:** 2026-06-09
**Status:** Approved (design phase)
**Author:** Retaint (with Claude Code)

## Background

This repository currently contains `Wigzo_Service`, a Magento 2 module that exposes
store data (orders, products, customers, etc.) as JSON feed endpoints. An external
collector service polls those endpoints on a schedule and pulls the data — the module
itself runs **no cron jobs and makes no outbound calls** (a common misconception). The
collector has moved from Wigzo to Retaint, served at `https://datapulse.retaint.com`.

### How the system actually works (pull/feed model)

1. The module registers a frontend route (`wigzo`, becoming `retaint`).
2. Controllers under `Controller/Module/` return entity data as JSON when their URLs
   are hit (e.g. `https://<store>/retaint/module/orders`).
3. A per-store secret token authenticates the caller.
4. The collector (`datapulse.retaint.com`) is configured with the store's URL + token
   and polls the endpoints. The collector URL does **not** live in the plugin except for
   the new one-click onboarding handshake.

### Problems with the current code

- **🔴 Live security hole:** commit `2e134d1` commented out all token authentication
  (`Controller/Module.php:58-73` always returns `true`). Every endpoint — including
  `/customers` and `/orders` — is currently world-readable with no auth, leaking PII.
- **🔴 Won't install on modern Magento:** `composer.json` requires PHP `~5.5|~5.6|~7.0|~7.1`.
- **🔴 Deprecated setup:** `Setup/InstallData.php` uses the removed `InstallDataInterface`
  pattern, does raw SQL inserts into `core_config_data` and `url_rewrite`, and reads
  `$_SERVER` directly — all rejected by Magento Marketplace's EQP review.
- **🔴 Missing ACL:** `system.xml` references resource `Wigzo_Service::wigzo_service` with
  no `acl.xml` defining it.
- **🟡 Stale branding:** Wigzo logo, copy, and `support@wigzo.com` throughout.
- **🟡 Fragile routing:** `url_rewrite` rows inserted at install to make multi-word
  endpoints resolve — breaks on case-sensitive (Linux) filesystems.

## Goals

1. Stop the live data leak.
2. Rebuild as a fresh, Marketplace-ready (EQP-compliant) module branded **Retaint**.
3. Simplify onboarding with a one-click "Connect to Retaint" admin action.

## Non-goals

- Converting the feed endpoints to Magento Web API / REST (possible future; out of scope).
- Migrating existing Wigzo installs in place (full rename = treated as a new module).
- Building the collector-side onboarding receiver on `datapulse.retaint.com` (external
  dependency; see Risks).

## Decisions (locked)

| Decision | Choice |
|---|---|
| Strategy | **Approach A** — fresh module, port `Model/Types/*` extraction logic |
| Auth model | Per-store random token, re-enabled |
| Rename depth | Full rename — fresh `Retaint_Analytics` module |
| Install simplification | One-click "Connect to Retaint" admin onboarding |
| Marketplace | Hard requirement (EQP-compliant) |
| Live store exposed now? | **Yes** — urgent security patch on existing module first |
| License | **OSL-3.0** |
| Support email | `support@retaint.com` |
| Logo | `https://retaint.com/wp-content/uploads/2026/05/cropped-retaint-logo-4444.png` |
| Collector URL (onboarding) | `https://datapulse.retaint.com` (configurable) |

## Module identity

| Thing | Value |
|---|---|
| Module | `Retaint_Analytics` |
| Namespace | `Retaint\Analytics` |
| Composer package | `retaint/magento2-analytics` |
| Frontend route | `/retaint/...` |
| Config namespace | `retaint_settings/general/*` |
| ACL resource | `Retaint_Analytics::config` |
| Auth header | `X-Retaint-Token` |

## Architecture

### 1. Endpoints & routing

Drop the `url_rewrite` hack entirely. Name controller classes to match Magento's standard
router resolution (upper-case first letter of each lowercased path segment) and use
no-underscore paths:

```
/retaint/module/orders          -> Controller/Module/Orders
/retaint/module/orderitems      -> Controller/Module/Orderitems
/retaint/module/products        -> Controller/Module/Products
/retaint/module/categories      -> Controller/Module/Categories
/retaint/module/customers       -> Controller/Module/Customers
/retaint/module/inventory       -> Controller/Module/Inventory
/retaint/module/refunds         -> Controller/Module/Refunds
/retaint/module/refunditems     -> Controller/Module/Refunditems
/retaint/module/abandonedcarts  -> Controller/Module/Abandonedcarts
/retaint/module/subscribers     -> Controller/Module/Subscribers
/retaint/module/productalerts   -> Controller/Module/Productalerts
/retaint/module/stores          -> Controller/Module/Stores
/retaint/module/extensions      -> Controller/Module/Extensions
/retaint/module/version         -> Controller/Module/Version
```

The collector (`datapulse.retaint.com`) is updated to call these new paths.

### 2. Authentication

- Abstract base `Controller/Module.php` exposes `isAuthorized()` and `isEnabled()`.
- `isAuthorized()`:
  - reads token via `$this->getRequest()->getHeader('X-Retaint-Token')` (no `$_SERVER`),
  - returns `false` if empty,
  - compares to stored token with `hash_equals($stored, $provided)` (constant-time).
- Each controller returns HTTP 401 `{"error": "..."}` when `!isAuthorized() || !isEnabled()`.

### 3. Setup — data patch

`Setup/Patch/Data/InitializeSettings.php implements DataPatchInterface`:
- generate token with `Magento\Framework\Math\Random::getRandomString(32)`,
- persist `retaint_settings/general/enabled = 1` and `.../security_token` via
  `Magento\Framework\App\Config\Storage\WriterInterface`,
- no URL rewrites.

`etc/acl.xml` defines `Retaint_Analytics::config` under
`Magento_Backend::stores > Magento_Config::config`.

### 4. One-click "Connect to Retaint"

- `system.xml` (`retaint_settings/general`) renders a **Connect to Retaint** button via
  `Block/Adminhtml/Connect.php` (extends config field, outputs button + JS).
- Button AJAX-POSTs to `Controller/Adminhtml/Connect/Register` (ACL-guarded).
- The controller gathers `{ store_base_url, security_token, platform: "magento2",
  module_version, store_name }` and POSTs to the collector onboarding endpoint using
  `Magento\Framework\HTTP\Client\Curl`.
- Collector endpoint is a config field `retaint_settings/general/collector_url`,
  default `https://datapulse.retaint.com`.
- On success: write `connection_status=connected` + `connected_at`; show green status.
  On failure: surface the error; manual Store URL + Secret Key fields remain as fallback.

### 5. Admin config (`system.xml`) layout

- Group `info`: Retaint logo + support copy (`Block/Adminhtml/Info.php`).
- Group `general`:
  - `enabled` (Yes/No)
  - `siteurl` — "Your Secure Store URL" (label block)
  - `secret` — "Your Secret Key" (label block showing token)
  - `collector_url` — collector base URL (default `https://datapulse.retaint.com`)
  - Connect button + connection status

### 6. Branding

- `Block/Adminhtml/Info.php`: Retaint logo
  (`https://retaint.com/wp-content/uploads/2026/05/cropped-retaint-logo-4444.png`),
  Retaint copy, `support@retaint.com`.
- All labels/tab/section/comments → "Retaint".
- README rewritten for Retaint install + Connect flow.

### 7. Marketplace (EQP) compliance

- `composer.json`: name `retaint/magento2-analytics`, `type: magento2-module`,
  `license: OSL-3.0`, `require: php ~8.1.0||~8.2.0||~8.3.0`, declare `magento/framework`.
- OSL-3.0 copyright header in every PHP file.
- `registration.php`, `etc/module.xml` (with `<sequence>` on `Magento_Store`), `etc/acl.xml`.
- Passes `phpcs --standard=Magento2`: no `$_SERVER`, no raw SQL, no direct `ObjectManager`,
  no insecure `rand()`/`date()` usage.

## Component map (what each unit does)

| Unit | Responsibility | Depends on |
|---|---|---|
| `Controller/Module.php` | Auth + request param parsing (abstract base) | `Helper/Data` |
| `Controller/Module/*` | One per entity; load data, return JSON | base, `Model/Types/*` |
| `Controller/Adminhtml/Connect/Register` | Onboarding handshake | `Curl`, `WriterInterface` |
| `Model/Types/*` | Extract a given entity from Magento (ported) | Magento collections |
| `Helper/Data` | Config access, logging, pagination, date utils | `ScopeConfig`, `StoreManager` |
| `Block/Adminhtml/Info` | Render branded info panel | — |
| `Block/Adminhtml/StoreUrl` / `SecretKey` | Render store URL / token labels | `StoreManager` / `Helper` |
| `Block/Adminhtml/Connect` | Render Connect button + status | — |
| `Setup/Patch/Data/InitializeSettings` | Seed token + enabled on install | `Random`, `WriterInterface` |

## Testing

- **Unit:** token comparison (valid/invalid/missing); helper methods.
- **Integration (Magento test framework):** each endpoint returns JSON with a valid
  `X-Retaint-Token` and **401** without; data patch writes config; Connect controller
  posts payload and records status (collector mocked).
- **Manual:** curl each endpoint ±token; click Connect; confirm config persisted;
  `phpcs --standard=Magento2` clean.

## Rollout / build order

0. **Urgent security patch (standalone, existing module):** re-enable token auth in the
   live `Wigzo_Service` to stop the data leak. Ship before the rebuild.
1. Scaffold `Retaint_Analytics`: registration, module.xml, acl.xml, composer.json.
2. Port `Model/Types/*` and controllers; implement auth in the base controller.
3. Data patch + config (`system.xml`) + branding blocks.
4. Connect onboarding controller + button + status.
5. Compliance pass (`phpcs`), tests, README.

## Risks & external dependencies

- **Collector onboarding receiver:** one-click Connect needs `datapulse.retaint.com` to
  accept the onboarding POST. Until that exists, Connect surfaces an error and merchants
  use the manual copy-paste fallback. The plugin is fully functional either way.
- **Collector path update:** `datapulse.retaint.com` must poll the new `/retaint/module/*`
  paths (and send `X-Retaint-Token`) instead of the old `/wigzo/...` paths.
- **EQP review latency:** Adobe Marketplace review can take time and may request changes;
  building to standard up front minimizes round-trips.
