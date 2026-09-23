# RC Catalog 1.7.0-alpha2

Couche catalogue durable de Robotique Concept pour WordPress / WooCommerce.

- WordPress : `>= 6.8`
- PHP : `>= 8.1`
- Namespace : `WPRC\Catalog`
- Dépendances obligatoires : `RC Core >= 0.6.0-alpha2`, WooCommerce, Polylang Pro
- Langue canonique : langue Polylang par défaut (FR sur l'instance Robotique Concept)

## Architecture 1.7

RC Catalog est désormais un module officiel du SDK RC Core :

```text
RC Catalog → RC Core
```

Il ne dépend d’aucun autre module métier. Son lifecycle, ses capabilities, son contexte runtime générique, ses traductions, placeholders, accès ERP, cache et logging utilisent les primitives Core.

La version 1.7-alpha2 finalise son rôle Multisite public : RC Catalog reste la couche de projection de `www`, tandis que RC Leads vit sur `my`. Les futurs domaines produits/assets seront progressivement pilotés depuis `my` sans déplacer la logique de projection dans le thème.

Les identités destinées à franchir cette future frontière sont stables :

```text
Produit ERP : source + external_id
Modèle robot : public_id
Contrôleur : uid
```

Les IDs WordPress, term IDs et IDs SQL locaux ne doivent pas devenir des contrats inter-sites.

## Responsabilités

RC Catalog porte les données et règles métier qui doivent survivre à un changement de thème ou d'ERP :

- types de produits WooCommerce et métadonnées métier ;
- mode catalogue WooCommerce strict ;
- accès ERP exclusivement via les contracts/providers de RC Core ;
- référentiel technique des modèles de robots ;
- contrôleurs robots privés et compatibilités ;
- relations modèles ↔ pages et modèles ↔ produits techniques ERP ;
- données techniques et de maintenance ;
- intégrations Polylang et Yoast ;
- API PHP stable consommable par le thème.

Le rendu frontend reste la responsabilité du thème. RC Catalog ne contient aucun template public spécifique aux modèles de robots.

## Modèles de robots

Le CPT public `rc_robot_model` fournit le contenu SEO du modèle. Gutenberg est désactivé pour ce CPT : l'édition utilise le WYSIWYG classique WordPress.

Le CPT supporte :

- titre ;
- éditeur classique ;
- extrait ;
- image mise en avant ;
- galerie technique partagée ;
- révisions.

La marque utilise `product_brand`. Famille / série utilisent la taxonomie hiérarchique `rc_robot_family`.

Les traductions Polylang d'un modèle partagent une seule entité technique contenant notamment charge, portée, masse, répétabilité, structure, IP, axes A1…A7, contrôleurs, applications et maintenance.

## Applications et logiciels

`WooCommerce > Réglages > Catalogue` configure :

- la page parente des Applications robotisées ;
- la page parente des Logiciels.

Les applications d'un modèle sont des relations vers les pages descendantes du parent Applications. Les logiciels sont associés aux termes `product_brand` et sont donc disponibles pour tous les modèles/produits de cette marque.

Les relations de contenu sont stockées avec l'ID de la langue Polylang par défaut (FR), puis projetées vers la langue demandée.

## Contrôleurs

Les contrôleurs ne sont pas publics et vivent dans la table `rc_robot_controllers`. Chaque contrôleur appartient à une marque `product_brand`; la compatibilité modèle ↔ contrôleur est N:N.

Administration : `Produits > Contrôleurs`.

## Pièces, composants, services et consommables techniques

Ces entités ne nécessitent plus de produit WooCommerce.

RC Catalog stocke une relation ERP dans `rc_robot_model_erp_products` :

```text
model_id
+ erp_source
+ external_product_id
+ relation_type
+ product_name / product_reference (snapshot d'affichage)
```

La source et l'identifiant externe sont la relation métier. Le snapshot permet de conserver un libellé lisible sans faire dépendre le frontend d'un appel ERP.

Les recherches dans l'admin passent exclusivement par `ProductProviderInterface` / `ProviderRegistry` de RC Core. Catalog ne connaît ni le client HTTP Axonaut ni le futur client Odoo.

Les accès ERP sont déclenchés uniquement par les endpoints AJAX authentifiés de recherche. Le chargement d’un éditeur et la sauvegarde d’un produit ou d’un modèle ne déclenchent aucun GET ERP ; les libellés sélectionnés sont conservés comme snapshots locaux.

## Maintenance

### Vidanges / lubrification

- Point : A1 à A7 ou A5/A6 ;
- produit ERP ;
- quantité ;
- unité (`L` / `cm³`).

### Courroies

- Section : Poignet / Moteur ;
- axe ;
- produit ERP ;
- tension nominale ;
- Delta ;
- unité.

### Groupes d'équilibrage

- libellé ;
- produit ERP ;
- pression mini ;
- pression nominale ;
- unité.

La notion de source/documentation générale de maintenance est abandonnée.

## Produit WooCommerce Robot

Dans l'onglet Général, un produit Robot sélectionne :

- un modèle mécanique ;
- un contrôleur compatible.

Le produit ne duplique pas les caractéristiques techniques. Les getters historiques du type `WC_Product_Robot` projettent désormais les données du modèle Catalog afin de maintenir le frontend existant pendant sa migration progressive.

Sont notamment projetés depuis le modèle : charge, portée, masse, répétabilité, IP, amplitudes, vitesses et applications.

## API publique pour le thème

```php
$product = rc_catalog()->product($productId);

$model        = $product?->robotModel();
$controller   = $product?->robotController();
$applications = $product?->robotApplications() ?? [];
$software     = $product?->robotSoftwarePages() ?? [];
$gallery      = $product?->robotGalleryAttachmentIds() ?? [];
$maintenance  = $product?->robotMaintenance() ?? [];
$components   = $product?->robotErpProducts('component') ?? [];
```

L'accès direct aux tables privées depuis le thème est à proscrire.

## Réglages ERP et Description Axonaut

`WooCommerce > Réglages > ERP` définit les templates de description par type de produit. Les placeholders disponibles incluent notamment :

```text
{product.reference}
{brand.name}
{brand.software}
{robot_model.name}
{robot_model.payload}
{robot_model.reach}
{robot_model.applications}
{controller.name}
```

L'onglet produit `Description Axonaut` reste un outil de formatage/aperçu. Aucun envoi direct vers l'ERP n'est réalisé dans cette version.

## Expédition

Les forfaits transport sont eux aussi des relations ERP (`source + external ID`) :

- Robot : un forfait transport ;
- Pièce : transport standard + express.

## Mode catalogue strict

WooCommerce reste non transactionnel : produits non achetables, panier / checkout neutralisés, coupons inutilisés et session panier évitée sur le catalogue public. L'espace Mon compte reste natif WooCommerce.


## Public lead-form projection (Multisite)

On the public site, Catalog now owns the presentation/transport adapter for forms whose business definition and submissions belong to RC Leads on `my`.

```text
my / RC-Leads
   ↓ signed GET (sync only)
www local form projection
   ↓ normal local render
visitor
   ↓ AJAX www
RC-Catalog
   ↓ signed POST
my / RC-Leads
```

Normal public rendering reads only the local projection option. Use:

```bash
wp rc catalog sync-lead-forms --url=https://www.robotiqueconcept.com --path=/path/to/wordpress
```

The legacy shortcode `wprc_form`, block `wprc-leads/form` and AJAX action remain stable to avoid content migrations.
