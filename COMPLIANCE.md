# Marketplace (EQP) Compliance Status

Honest snapshot of where `Retaint_Analytics` stands against the Adobe Commerce
Marketplace Extension Quality Program (EQP), so nothing is a surprise at submission.

## Done

- ✅ Per-store token authentication re-enabled on every data endpoint
  (`X-Retaint-Token` header, constant-time `hash_equals`, no token logging).
- ✅ PHP `~8.1 || ~8.2 || ~8.3` and `magento/framework` declared in `composer.json`.
- ✅ Deprecated `InstallData` + raw SQL replaced with a `DataPatchInterface` patch using
  `WriterInterface` and `Magento\Framework\Math\Random`.
- ✅ No `$_SERVER` superglobals (request headers read via the framework request).
- ✅ `etc/acl.xml` defines `Retaint_Analytics::config` (previously referenced but missing).
- ✅ `url_rewrite` table inserts removed; controllers named to resolve via the standard
  router on case-sensitive filesystems.
- ✅ OSL-3.0 copyright header on every PHP/XML file.
- ✅ Admin controller is ACL-guarded and uses the framework HTTP client.
- ✅ `registration.php`, `module.xml` (with `<sequence>`), `config.xml`, `system.xml`.
- ✅ Integration tests for endpoint authentication.
- ✅ **No direct `ObjectManager` usage.** All 9 `Model/Types/*` classes that used
  `ObjectManagerInterface` now use constructor-injected factories
  (`InventoryItemFactory`, `OrderFactory`, `OrderItemFactory`, `CustomerFactory`,
  `ProductFactory`, `\Magento\Catalog\Model\ProductFactory`,
  `\Magento\ConfigurableProduct\Model\Product\Type\ConfigurableFactory`,
  EAV attribute `CollectionFactory`) or a directly-injected service
  (`StockStateInterface`). Unused `objectManager` deps in `Order`/`OrderItem` were dropped.
- ✅ Fixed a latent bug in `Model/Types/Product.php`: `catch (Exception $e)` →
  `catch (\Exception $e)` (the unqualified name resolved to a non-existent class in the
  namespace, so it never caught anything).

## Remaining before submission

- ⏳ **Run the Magento coding standard:** `phpcs --standard=Magento2 .` and fix findings
  (cannot be run in this bare repo — requires `magento/magento-coding-standard`).
- ⏳ **Run PHP compatibility:** `phpcs --standard=PHPCompatibility --runtime-set testVersion 8.1-8.3`.
- ⏳ **Full OSL-3.0 license text** in `LICENSE.txt` (currently a notice + canonical link).
- ⏳ **Run the integration tests** inside a real Magento test environment (cannot run here).
- ⏳ **Collector side:** `https://datapulse.retaint.com` must (a) poll the new
  `/retaint/module/*` paths sending `X-Retaint-Token`, and (b) expose the `/connect`
  onboarding receiver for the one-click button.

## Cannot be verified in this repository

This folder is the module source only — there is no Magento framework / `vendor/` here,
so only `php -l` (syntax) and `xmllint` (XML well-formedness) have been run, both clean.
phpcs-Magento2, DI compilation, and integration tests require installation into a Magento
project.
