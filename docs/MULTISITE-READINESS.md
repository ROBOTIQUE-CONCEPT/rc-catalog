# RC Catalog — Multisite public boundary

RC Catalog `1.7.0-alpha2` est le plugin de projection/publication du site `www`.

## Position active

```text
www
└── RC Catalog
    ├── WooCommerce public projection
    ├── robot-model public projection (temporairement aussi source technique)
    ├── SEO / Polylang integration
    └── public lead-form projection + REST proxy

my
├── RC Leads
├── future RC Products
├── future RC Assets
└── future business modules
```

## Règles appliquées

- Catalog dépend uniquement de Core parmi les plugins RC.
- L'ERP passe exclusivement par Core.
- Le rendu public n'effectue pas de GET ERP.
- Les formulaires publics sont materialisés localement sur `www`; leur source métier est RC Leads sur `my`.
- Les soumissions transitent par le REST interne signé Core.
- Identité produit = ERP source + external ID.
- Identité modèle robot = `public_id`.
- Identité contrôleur = `uid`.
- Les IDs WordPress/SQL locaux ne sont jamais un contrat inter-site.

## Exception temporaire

Le référentiel technique modèles/contrôleurs reste encore dans Catalog sur `www` jusqu'à l'extraction de RC Assets après stabilisation du réseau. Cette exception ne doit pas être étendue à de nouveaux domaines métier.

## Interdits

- GET live vers `my` au rendu normal d'une page publique ;
- synchronisation bidirectionnelle Woo ;
- dépendance Catalog → Leads / Assets / Products ;
- logique métier déplacée dans le thème.
