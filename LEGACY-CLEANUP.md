# RC Catalog — état du nettoyage legacy

La traduction de chaînes RCONCEPT n'est plus implémentée dans RC Catalog.

- RC Core fournit l'interface d'enregistrement, de traduction et de rendu.
- RC Catalog ne conserve qu'un manifeste de clés métier dans `resources/translations/catalog.php`.
- Les placeholders éditoriaux `[{...}]` sont résolus exclusivement par le moteur de RC Core.
- Les anciens helpers `wprc__()` / `wprc_e()`, les alias de clés Polylang et la normalisation de l'ancien format `{placeholder}` ont été supprimés.
- Les appels Polylang encore présents dans Catalog concernent uniquement les relations multilingues de contenus, termes, taxonomies et rewrites.

Le legacy Gutenberg `wprcConditions`, `wprcTextHotswap` et `{@product:...}` reste hors du runtime. Aucun mécanisme de compatibilité n'est maintenu pour ces anciens formats.
