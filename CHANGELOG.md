# Changelog

## 1.7.0-alpha2 — 2026-09-13

### Multisite public projection

- Requires RC Core `>= 0.6.0-alpha2`.
- RC Catalog now boots only on the Core-configured public site in Multisite.
- Moves public RC lead-form rendering, block/shortcode assets and AJAX transport from RC Leads to RC Catalog.
- Materializes lead-form definitions locally on `www`; normal frontend rendering performs no live request to `my`.
- Synchronizes form definitions from `my` through the signed Core internal API, hourly or via WP-CLI.
- Proxies public lead submissions to RC Leads on `my` through signed internal REST while preserving visitor IP, referer, UTM and product context.
- Adds `wp rc catalog sync-lead-forms` for deterministic projection refresh during cutover/deployment.
- Keeps legacy shortcode/block identifiers and AJAX action names so existing pages and browser code require no content migration.

### Compatibility

- No Catalog database schema change.
- Existing products, robot models, URLs and Polylang relations are untouched.
- RC Leads is no longer a PHP/runtime dependency of the public site after cutover.

## 1.7.0-alpha1 — 2026-09-12

### RC Core 0.6 lifecycle / Multisite readiness

- Requires RC Core `>= 0.6.0-alpha1`.
- Registers RC Catalog through the canonical Core `ModuleInterface` / `ModuleRegistry` lifecycle.
- Moves the Catalog autoloader to plugin-load time so Core can discover Catalog during its module registration phase.
- Declares Catalog-owned `rc_catalog_*` capabilities through the Core capability registry while preserving all historical WooCommerce authorization fallbacks during migration.
- Removes the duplicate generic Catalog request-context implementation; generic runtime context now comes from Core and Catalog keeps only WooCommerce-specific query predicates.
- Removes a dead direct WordPress object-cache branch from product permalink rewriting; Catalog no longer implements a parallel cache mechanism there.
- Adds explicit ERP source metadata next to the external product ID on product saves, producing a stable `source + external ID` upstream identity suitable for Axonaut → Odoo and `my` → `www` projections.
- Adds stable robot-model `public_id` and controller `uid` snapshots next to current local numeric IDs on product saves. Existing products remain fully compatible through repository fallbacks; no bulk migration is performed.
- Adds `CatalogProduct::identity()`, `externalSource()`, `robotModelPublicId()` and `robotControllerUid()` as additive public projection primitives.
- Keeps `rc_catalog()`, all current CPTs, product metadata, URLs, Polylang relations and theme-facing APIs backward-compatible.
- Keeps the historical `wprc()` and `rc_catalog_bootstrap()` helpers as deprecated compatibility shims.
- No database schema migration and no functional split of products / robot models is performed in this release.

## 1.6.0-alpha8 — 2026-09-12

### Runtime cleanup

- Removes the historical WooCommerce product-save pipeline and every remote-hydration/computed-sync step.
- Replaces it with a deterministic local `ProductSaveHandler` that persists only Catalog-owned fields and validates robot model/controller compatibility.
- Removes robot/cabinet JSON payload hydration at runtime; model/controller data resolve from the Catalog repositories.
- Removes optional-product relations, admin UI and assets from the runtime.
- Removes the completed robot taxonomy migrator and legacy process-taxonomy fallback.
- Removes the obsolete product-relations table from new installations without destructively dropping an existing table.
- Removes synchronous ERP reads from product editor rendering and from product/robot-model saves. ERP providers are now queried only by authenticated admin AJAX searches.
- Stores lightweight local labels alongside external ERP IDs so existing selections remain readable without a remote request.
- Keeps Woo product local fields, Robot Model ERP relations, shipping ERP references, ERP description formatter, Catalog Mode, SEO and Polylang content relationships.

## 1.6.0-alpha7 — 2026-09-11

### Translation catalog ownership cleanup

- Moves the 127 Catalog-owned RCONCEPT technical keys into `resources/translations/catalog.php`.
- Registers the Catalog dictionary through the RC Core translation API; Catalog no longer owns any Polylang string registration/rendering implementation.
- Requires RC Core `>= 0.5.0-alpha14`.
- Removes every legacy string-translation compatibility dependency; only canonical technical keys and Core `[{placeholder}]` rendering remain.
- Direct Polylang calls in Catalog remain limited to multilingual posts, terms, canonicalization and rewrites.

## 1.6.0-alpha6 — 2026-09-11

### Translation ownership

- Removes the Catalog-owned Polylang string registry and legacy placeholder renderer.
- Requires RC Core `>= 0.5.0-alpha13`, which now owns all `RCONCEPT_*` string registration and rendering.
- Migrates Catalog SEO, breadcrumbs, product titles, stock labels and schema labels to canonical technical translation keys.
- Extends the Catalog product placeholder provider with runtime, location country, process and formatted last-update values needed by legacy translations during migration.
- Keeps direct Polylang usage only for multilingual content relationships (posts, terms, canonicalization and rewrites), not string translation.

## 1.6.0-alpha5 — 2026-09-11

### Robot-model public projection

- Adds atomic `robot_model_*` placeholders to the generic RC Core placeholder engine for Blocksy Content Blocks and editorial content.
- Adds Catalog facade helpers for model brand, family/series classification, software pages and published Robot products linked to a model.
- Keeps complex collections as structured PHP data; no HTML is emitted by placeholder providers.
- Available Robot products are resolved in the current Polylang language and exclude products explicitly marked out of stock.

## 1.6.0-alpha4 — 2026-09-11

- Fix robot-model single permalinks returning 404 with Polylang directory URLs.
- Generate deterministic final single-model rules after Polylang/WooCommerce rewrite processing, with literal language prefixes and stable capture indexes.
- Move the automatic rewrite flush from `init` to late `wp_loaded` so Polylang has finished preparing its rewrite layer before rules are persisted.
- Keep deep canonical URLs (`brand/family/series/model`) and short fallback URLs resolvable without constraining the query by taxonomy terms.

## 1.6.0-alpha3 — 2026-09-11

### Frontend stability

- Fixes the anonymous-frontend fatal error caused by loading `WC_Settings_Page` through public Catalog helpers.
- Splits persisted setting access into front-safe `CatalogOptions` / `ErpOptions` classes; WooCommerce settings-page classes remain admin-only.
- Restricts WooCommerce settings-page registration to admin requests.

### Robot-model permalinks

- Fixes robot-model single 404s in Polylang directory mode by registering both native and language-prefixed rewrite rules.
- Uses the CPT query var for deterministic single-model resolution and bumps the rewrite version for a one-time rules flush.

### Robot-model admin UI

- Groups technical, compatibility, ERP-product and maintenance editors into a compact tabbed metabox.
- Removes the technical status selector; publication lifecycle is owned by the CPT post status.
- Fixes axis units to degrees / degrees-per-second and removes editable unit fields.
- Removes the balancing-group reference field.
- Keeps the classic WYSIWYG, excerpt, featured image and shared gallery.

### Content placeholders

- Adds `product_robot_yom`, `product_robot_payload`, `product_robot_reach` and `product_robot_repeatability`.
- Robot specifications resolve from the selected Catalog robot model rather than duplicated product snapshots.

## 1.6.0-alpha2 — 2026-09-11

### Robot-model editing

- Forces the public `rc_robot_model` CPT to use the classic WordPress WYSIWYG editor instead of Gutenberg.
- Keeps the excerpt, featured image and shared technical gallery.
- Removes the maintenance source/documentation profile from the UI and application layer.

### Maintenance model

- Lubrication now keeps only Point, ERP product, Quantity and Unit; valid points are A1–A7 and A5/A6.
- Belt data now uses Section (`wrist` / `motor`), Axis, ERP product, Nominal tension, Delta and Unit.
- Balancing groups drop maximum pressure and notes while keeping label/reference, ERP product, minimum/nominal pressure and unit.
- Technical consumables no longer require WooCommerce products.

### ERP technical relations

- Adds `rc_robot_model_erp_products`, storing `ERP source + external product ID` with a lightweight name/reference snapshot.
- Parts, components, services, lubricants, belts and balancers can reference ERP-only products without creating noise in `wp_posts`.
- Admin searches still go exclusively through RC Core `ProductProviderInterface`; no direct Axonaut/Odoo HTTP integration is introduced.
- Existing alpha1 Woo-linked technical relations are migrated opportunistically when their Woo product has an ERP ID.

### Catalog projection

- Robot Woo products now project payload, reach, mass, repeatability, IP ratings, axis ranges/velocities and applications from the selected RC Catalog robot model.
- Selected controller names are projected from the private controller repository.
- `CatalogProduct` exposes model, controller, applications, software pages, gallery, maintenance and ERP technical relations to the theme.
- Brand software relations are resolved from the canonical Polylang `product_brand` term so translated products reuse the same configured software set.
- Application-page resolution now prefers the configured Applications parent; the historical process taxonomy is no longer registered by Catalog.
- ERP description templates gain `{brand.software}` and `{robot_model.applications}`.

## 1.6.0-alpha1 — 2026-09-11

### Robot models

- Integrates the former `RC Robot Models` technical foundation into RC Catalog.
- Keeps the public `rc_robot_model` CPT for SEO/editorial content.
- Adds hierarchical `rc_robot_family` taxonomy for family / series.
- Reuses WooCommerce `product_brand` for manufacturers.
- Preserves historical `rc_robot_model_*` tables and shared Polylang technical entities.
- Adds private robot-controller storage and model/controller compatibility.
- Adds application-page relations, product/component/service relations and shared media.
- Adds balancing-group maintenance data alongside lubrication and belt data.
- Adds a conservative one-time migration from legacy `rc_robot_catalog` classification.

### WooCommerce admin

- Robot products can select a technical model and a compatible controller in General.
- Shipping panel can select ERP shipping products through RC Core providers.
- Adds `Description Axonaut` helper panel with per-product template override and preview.
- Adds native WooCommerce settings pages `Catalogue` and `ERP`.

### Polylang

- Default-language WordPress/WooCommerce IDs are canonical for content relations.
- Product, application and maintenance relations are projected to the requested language at read time.

### Architecture / security

- No direct ERP API calls from RC Catalog.
- Controller UIDs use the generic RC Core UID generator.
- Server-side validation covers brand, family/series, controller compatibility and configured application/software page scopes.
- Existing strict WooCommerce catalog mode remains unchanged.
