# Legacy support — Magento 2.3+ / PHP 7.1+

The module's PHP code runs unchanged on **Magento 2.3.x through 2.4.8** and **PHP 7.1
through 8.4**. There are no PHP-8-only language features and every Magento API it uses
(`DataPatchInterface`, `Http*ActionInterface`, `WriterInterface`, `Math\Random`,
`Curl`, `StockStateInterface`) exists from Magento 2.3.0 onward.

The only thing that differs by audience is the **`composer.json` version constraints** —
and that is purely about distribution, not about the code.

## Two distribution channels (same code)

| Channel | Manifest | Supports | Marketplace? |
|---|---|---|---|
| **Default / Marketplace** | `composer.json` | Magento 2.4.4–2.4.8, PHP 8.1–8.4 | ✅ EQP-eligible |
| **Legacy** | `composer.legacy.json` | Magento 2.3.0–2.4.8, PHP 7.1–8.4 | ❌ not EQP-eligible (by design) |

**Why two manifests?** Adobe's Extension Quality Program requires a published package to
declare PHP 8.1+ / Magento 2.4.4+. Declaring PHP 7.1 in the same package would make it
ineligible for the Marketplace. So the Marketplace build keeps the modern floor, and
legacy clients use the wider manifest through a non-Marketplace channel.

> Note: PHP 8.0 is intentionally excluded from both manifests — Magento never officially
> supported PHP 8.0 (2.4.0–2.4.3 ran 7.3/7.4; 2.4.4 jumped straight to 8.1).

## How a legacy (2.3+) client installs

### Option A — app/code drop-in (simplest, no composer)

The bundled `install.sh` copies the module into `app/code` and ignores `composer.json`
entirely, so **no manifest swap is needed** — it just works on 2.3+ / PHP 7.1+:

```bash
./install.sh /path/to/magento
```

### Option B — composer

Use the legacy manifest as the package's `composer.json` for this channel (e.g. on a
dedicated `legacy-2.3` branch/tag, or in a private repository):

```bash
cp composer.legacy.json composer.json   # legacy branch only — do NOT do this on the Marketplace build
composer require retaint/magento2-analytics
bin/magento module:enable Retaint_Analytics
bin/magento setup:upgrade
bin/magento cache:flush
```

## Maintaining both

Keep the two manifests in sync whenever dependencies change — only the `php` and
`magento/framework` constraints should ever differ between them. Everything else
(module deps, autoload, name) must stay identical.

## Security caveat to share with clients

Magento 2.3.x is **end-of-life** (no security patches since September 2022). Where a
client cannot upgrade, prefer the app/code install and make sure the store itself is
behind up-to-date infrastructure. The module's per-store token auth (`X-Retaint-Token`,
constant-time comparison) protects the feed endpoints regardless of platform version.
