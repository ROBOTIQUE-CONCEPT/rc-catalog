# RC Catalog 1.7.0-alpha2 — validation

## Prérequis

- RC Core `0.6.0-alpha2` network-active.
- `www` configuré comme site public dans Core.
- `my` configuré comme site métier.
- RC Leads `0.3.0-alpha2` actif sur `my` avant la synchronisation finale.

## Isolation

- [ ] Catalog fonctionne sur `www`.
- [ ] Catalog n'est pas nécessaire sur `my`.
- [ ] Aucun namespace RC Leads n'est référencé.
- [ ] Aucun appel HTTP direct n'existe hors du client REST Core.

## Projection des formulaires

Après migration Leads et activation sur `my` :

```bash
wp rc catalog sync-lead-forms --url=https://www.robotiqueconcept.com --path=/path/to/wordpress
```

- [ ] la commande termine avec succès pour FR/EN ;
- [ ] les shortcodes `wprc_form` existants rendent leurs formulaires ;
- [ ] le bloc historique `wprc-leads/form` reste rendu ;
- [ ] le rendu d'une page publique ne déclenche pas de GET live vers `my` lorsque la projection existe ;
- [ ] le cron `rc_catalog_sync_lead_forms` est planifié.

## Soumissions

- [ ] formulaire contact : crée l'opportunité sur `my` ;
- [ ] formulaire maintenance : crée l'opportunité sur `my` ;
- [ ] formulaire produit : crée l'opportunité sur `my` ;
- [ ] contexte produit, UTM, referer, IP visiteur et user-agent sont conservés ;
- [ ] Turnstile public reste validé sur `www` avant envoi interne ;
- [ ] une erreur REST n'écrit pas localement une opportunité sur `www`.

## Régression catalogue

- [ ] produits/archives/taxonomies inchangés ;
- [ ] modèles robots FR/EN inchangés ;
- [ ] admin Catalog inchangé ;
- [ ] aucune migration DB ;
- [ ] aucune URL modifiée.
