# Migration RC Robot Models → RC Catalog 1.6.0-alpha7

RC Catalog reprend directement les tables techniques historiques de l'ancien plugin `rc-robot-models`. Il ne faut donc ni exporter ni recopier les données techniques.

## Avant déploiement

1. Sauvegarder la base WordPress.
2. Vérifier que RC Core `>= 0.5.0-alpha14`, WooCommerce et Polylang Pro sont actifs.
3. Désactiver le plugin autonome `RC Robot Models` avant de valider l'intégration Catalog.

L'ancien `uninstall.php` conserve les données par défaut. Par prudence, ne supprimez pas le plugin autonome avant validation complète.

## Déploiement

1. Installer / mettre à jour RC Catalog `1.6.0-alpha7`.
2. Ouvrir une page d'administration avec un compte disposant de `manage_woocommerce`.
3. RC Catalog met à niveau les tables existantes avec `dbDelta` et crée `rc_robot_model_erp_products`.
4. La migration historique marque / famille / série reste conservatrice : `product_brand` est rapproché par slug et Catalog ne crée jamais silencieusement une marque absente.
5. Les anciennes relations techniques basées sur un produit Woo sont migrées vers une relation ERP lorsqu'un `wprc_product_erp_id` existe sur le produit concerné. L'ancienne table n'est pas supprimée automatiquement.
6. Les anciennes colonnes de maintenance devenues inutiles peuvent subsister physiquement sur une base mise à niveau ; elles ne sont plus lues ni écrites par RC Catalog.

## Après migration

Contrôler en priorité :

- marque / famille / série de quelques modèles FR et EN ;
- unicité de l'entité technique entre traductions ;
- axes, galerie, contrôleurs et applications ;
- recherche ERP des pièces / consommables ;
- vidanges, courroies et groupes d'équilibrage avec leur nouveau format ;
- projection des specs d'un modèle sur un produit Woo Robot ;
- permaliens publics des modèles.

Une fois la reprise validée, l'ancien plugin peut rester désactivé puis être retiré des fichiers. Ne jamais activer une option d'uninstall destructrice de ses tables historiques avant validation.
