# AGENTS.md — rc-catalog

## Repository purpose

RC Catalog is the durable catalogue and public-projection plugin of the
`www` site (WooCommerce + Polylang Pro + Yoast). It is **not** part of RC
Portal and is not deployed on `my`. It depends only on RC Core
(`RC Catalog → RC Core`) and never on RC Portal or any other RC plugin.

Current version: `1.7.0-alpha2` (`rc-catalog.php` header and the
`RC_CATALOG_VERSION` constant — keep them in agreement). Requires WordPress
`>= 6.8`, PHP `>= 8.1`, RC Core `>= 0.6.0-alpha2`, WooCommerce, Polylang Pro
(`Requires Plugins: rc-core, woocommerce, polylang-pro`). Canonical content
language = the Polylang default language (FR on the Robotique Concept
instance). Repository imported into Git on 2026-09-24 from the deployed
plugin (first commit "Import initial"; the pre-import files, including the
old READMEs, are in it).

Read `rc-core/AGENTS.md` first — the platform rules (ERP through Core only,
capabilities not roles, one source of truth, `www` renders with zero ERP
GET, no `switch_to_blog()`, Core owns cache/logger/translations/
placeholders) apply here unchanged. This file only records what is specific
to Catalog.

## Architecture boundaries

- **Lifecycle.** Catalog is registered through Core's canonical
  `ModuleInterface`/`ModuleRegistry` (`rc_register_module()` on the
  `wprc/core/register_modules` action). This is the **production consumer
  that makes Core's mechanism load-bearing** — Core once removed it as dead
  code and broke `www` (see `rc-core/AGENTS.md`). The Catalog autoloader
  is registered at plugin-load time so Core can discover Catalog during its
  module registration phase; keep it that way.
- **Multisite.** Catalog boots only on the Core-configured public site. It
  is not needed on `my`. It never imports a business-module namespace and
  never calls `wp_remote_*` directly (all cross-site HTTP goes through
  Core's signed internal REST client).
- **Namespace.** Everything under `src/` is `WPRC\Catalog\*`
  (autoloader `PREFIX = 'WPRC\\Catalog\\'`). Note `rc-core/tools/
  architecture-preflight.php` historically guarded `RC\Catalog\` (a
  different spelling), so the "Core must not reference Catalog" check did
  not actually cover this namespace — see the namespace-harmonisation work
  before trusting it.
- **Stable cross-site identities** (never use local WordPress/SQL ids as an
  inter-site contract): ERP product = `source + external_id`; robot model =
  `public_id`; controller = `uid`.
- **Rendering.** The theme owns frontend rendering; Catalog ships no public
  template for robot models and exposes a stable PHP API instead
  (`rc_catalog()->product($id)` → `CatalogProduct`: `robotModel()`,
  `robotController()`, `robotApplications()`, `robotSoftwarePages()`,
  `robotGalleryAttachmentIds()`, `robotMaintenance()`,
  `robotErpProducts($type)`, plus `identity()`, `externalSource()`,
  `robotModelPublicId()`, `robotControllerUid()`). A theme never reads
  Catalog's private tables directly.
- **Temporary exception.** The technical robot-model/controller repository
  still lives in Catalog on `www` until RC Assets is extracted (the
  `maintenance` module of RC Portal already publishes the Core Assets
  contracts). Do not extend this exception to new business domains.
- **Forbidden**: live GET to `my` during normal public rendering; two-way
  Woo synchronisation; dependency Catalog → Leads/Assets/Products/Portal;
  business logic moved into the theme; ERP HTTP outside Core.

## Domain (what Catalog owns)

- WooCommerce product types and business metadata; strict **catalogue
  mode** (products not purchasable, cart/checkout neutralised, coupons
  unused, no cart session on the public catalogue; My Account stays native
  WooCommerce).
- **Robot models** — public CPT `rc_robot_model` (SEO content; Gutenberg
  disabled, classic WYSIWYG; supports title/editor/excerpt/thumbnail/
  revisions; shared technical gallery). Brand = `product_brand`; family/
  series = hierarchical taxonomy `rc_robot_family`. All Polylang
  translations of a model share one technical entity (payload, reach, mass,
  repeatability, structure, IP, axes A1…A7, controllers, applications,
  maintenance). Content relations are stored with the default-language id
  and projected to the requested language.
- **Applications / software.** `WooCommerce > Settings > Catalogue` defines
  the parent pages for Robotic Applications and Software. A model's
  applications are relations to that parent's descendant pages; software
  is attached to `product_brand` terms (available to every model/product of
  the brand).
- **Controllers.** Private (not public), table `rc_robot_controllers`; each
  belongs to a `product_brand`; model ↔ controller compatibility is N:N.
  Admin: `Products > Controllers`.
- **Technical parts/components/services/consumables** no longer need a Woo
  product: relation table `rc_robot_model_erp_products`
  (`model_id + erp_source + external_product_id + relation_type` + display
  snapshots `product_name`/`product_reference`). The relation is the
  business fact; the snapshot keeps the label readable without an ERP call.
  Admin searches go exclusively through `ProductProviderInterface`/
  `ProviderRegistry`; ERP is hit only by authenticated AJAX search
  endpoints — opening an editor or saving a product/model triggers **no**
  ERP GET.
- **Maintenance data.** Oil/lubrication (point A1–A7 or A5/A6, ERP product,
  quantity, unit `L`/`cm³`); belts (section Wrist/Motor, axis, ERP product,
  nominal tension, Delta, unit); balancing groups (label, ERP product, min
  and nominal pressure, unit). The generic "maintenance source/
  documentation" notion is abandoned.
- **Woo Robot product** (`WC_Product_Robot`): the General tab selects a
  mechanical model and a compatible controller; the product never
  duplicates technical specs — the legacy getters project the Catalog model
  data (payload, reach, mass, repeatability, IP, amplitudes, speeds,
  applications) so the existing frontend keeps working during migration.
- **Shipping.** Transport flat rates are ERP relations
  (`source + external ID`): Robot = one transport; Part = standard +
  express.
- **ERP settings / Axonaut description.** `WooCommerce > Settings > ERP`
  defines description templates per product type with placeholders
  (`{product.reference}`, `{brand.name}`, `{brand.software}`,
  `{robot_model.name|payload|reach|applications}`, `{controller.name}`).
  The product tab "Description Axonaut" is a formatting/preview tool only —
  no direct push to the ERP in this version.
- **SEO/i18n integrations.** Polylang and Yoast (`src/Polylang`,
  `src/WPSEO`); Polylang calls remaining in Catalog concern only
  multilingual relations of content/terms/taxonomies/rewrites.
- **Public lead-form projection.** Catalog owns the presentation/transport
  adapter for forms whose definition and submissions belong to RC Leads on
  `my`: `my` → signed GET (sync only) → local form projection option on
  `www` → normal local render → visitor AJAX on `www` → Catalog → signed
  POST → `my`. Normal rendering reads only the local projection; the sync
  runs hourly (cron `rc_catalog_sync_lead_forms`) or via
  `wp rc catalog sync-lead-forms --url=https://www.robotiqueconcept.com`.
  Submissions preserve visitor IP, referer, UTM, user-agent and product
  context; public Turnstile is validated on `www` before the internal send;
  a REST error never writes an opportunity locally. Legacy shortcode
  `wprc_form`, block `wprc-leads/form` and the AJAX action names are frozen
  to avoid content migrations.
- **Translations.** Catalog keeps only a manifest of business keys in
  `resources/translations/catalog.php`; Core does registration/translation/
  rendering. Editorial `[{...}]` placeholders are resolved solely by Core's
  engine. The old `wprc__()`/`wprc_e()` helpers, Polylang key aliases and the
  legacy `{placeholder}` format are gone; the Gutenberg legacy
  `wprcConditions`, `wprcTextHotswap` and `{@product:...}` are out of the
  runtime and get no compatibility layer.
- **Deprecated shims kept:** `wprc()` and `rc_catalog_bootstrap()`.

## Repository map

- `rc-catalog.php` — plugin header/bootstrap, `RC_CATALOG_VERSION`.
- `src/Autoloader.php`, `src/bootstrap.php`, `src/global-functions.php`
  (`rc_catalog()`, deprecated shims), `src/Core/` (Plugin, Installer).
- `src/Database/TableNames.php` — table names (`rc_robot_*` tables).
- `src/RobotModel/` — `Admin/*` editors, `Application/RobotModels`,
  `Persistence/*Repository`, `WordPress/Permalinks`.
- `src/Product/` — `CatalogProduct`, `WordPressProductContextProvider`.
- `src/WooCommerce/` — product types (`Types/WC_Product_Abstract`,
  `WC_Product_Robot`, …), `Product/{CustomFields,Ajax,Search}`, `Settings/*`,
  `Taxonomy/*`.
- `src/ERP/`, `src/PublicApi/`, `src/Helpers/`, `src/Security/Capabilities`,
  `src/Polylang/`, `src/WPSEO/`, `src/LeadForms/` (FormRenderer,
  SubmissionProxy, RemoteFormRepository, SyncCommand, Integration).
- `assets/{admin,js,public}`, `resources/translations/catalog.php`.

## Mandatory rules

- Every ERP access goes through Core contracts (`ProductProviderInterface`
  etc.); no `wp_remote_*` and no ERP client here.
- Capabilities `rc_catalog_*` are declared through Core's capability
  registry while preserving the historical WooCommerce authorization
  fallbacks — do not remove a fallback without a migration decision.
- Every mutation (AJAX, admin post) needs a capability check **and** a
  scoped nonce; output is escaped for its context.
- Database access through WordPress APIs or prepared `$wpdb` statements;
  schema changes are additive (`dbDelta`); legacy technical tables inherited
  from the retired `rc-robot-models` plugin are read/upgraded in place —
  never dropped automatically, and never enable a destructive uninstall
  option for them. Legacy maintenance columns may physically remain on an
  upgraded database; they are neither read nor written.
- The brand mapping is conservative: `product_brand` is matched by slug and
  Catalog never silently creates a missing brand.
- Public rendering must stay free of ERP and of live `my` calls.

## Validation

- `php -l` every changed file.
- From `rc-core`: `php tools/architecture-preflight.php /path/to/rc-core
  /path/to/rc-catalog` (checks no Core → Catalog reference, no direct
  `wp_remote_*`, no direct role mutation, no direct site switching, no
  Module → Module import; mind the namespace-spelling caveat above).
- No PHPUnit suite exists. Manual regression checklist after a release
  touching the public boundary:
  - **Isolation**: Catalog works on `www`; not required on `my`; no RC
    Leads namespace referenced; no direct HTTP outside Core's REST client.
  - **Lead forms**: `sync-lead-forms` succeeds for FR/EN; existing
    `wprc_form` shortcodes and the legacy block render; a public page
    render triggers no live GET to `my` when the projection exists; the
    cron is scheduled.
  - **Submissions**: contact, maintenance and product forms each create an
    opportunity on `my` with product context/UTM/referer/IP/user-agent;
    Turnstile validated on `www`; a REST error writes nothing locally.
  - **Catalogue regression**: products/archives/taxonomies unchanged,
    FR/EN robot models unchanged, Catalog admin unchanged, no DB migration,
    no URL change.
  - **After a technical-model migration**: check brand/family/series on
    FR and EN models; a single technical entity across translations; axes,
    gallery, controllers, applications; ERP search for parts/consumables;
    oil/belts/balancing in the current format; spec projection onto a Woo
    Robot product; public permalinks.

## Versioning and documentation

- Bump the `rc-catalog.php` header and `RC_CATALOG_VERSION` together; add a
  `CHANGELOG.md` entry per release, with a "Compatibility" note (schema,
  URLs, theme-facing APIs, minimum Core version).
- Documentation lives only in this file and `CHANGELOG.md`. The former
  `README.md`, `VALIDATION.md`, `LEGACY-CLEANUP.md`,
  `MIGRATION-ROBOT-MODELS.md` and `docs/MULTISITE-READINESS.md` were merged
  here on 2026-09-24 (the one-off `rc-robot-models` → Catalog migration
  steps of 1.6.0-alpha7 are historical; the essential rules are kept in
  "Mandatory rules").

## Definition of done

- `php -l` clean; Core architecture preflight passes against this repo.
- No new ERP HTTP, no new Catalog → other-RC-plugin dependency, no live call
  to `my` on public render, no unescaped output, no unguarded mutation.
- `CHANGELOG.md` updated; version constants bumped together.
