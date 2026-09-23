<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

/**
 * Stable RCONCEPT translation keys owned by this module.
 *
 * Keys are technical identifiers. French fallback copy is used only when
 * Polylang has no translation for the registered technical source.
 *
 * @return array<string,array{fallback:string,multiline:bool}>
 */
return [
    'seo_meta_title_product_robot' => [
        'fallback' => 'Robot d’occasion [{product_brand}] [{product_robot_mechanical_unit}] ([{product_robot_electrical_unit}])',
        'multiline' => true,
    ],
    'seo_meta_title_product_cell' => [
        'fallback' => 'Cellule robotisée d’occasion [{product_brand}] - [{product_process}]',
        'multiline' => true,
    ],
    'seo_meta_title_product_spare' => [
        'fallback' => 'Pièce détachée - [{product_brand}] [{product_reference}] - [{product_designation}]',
        'multiline' => true,
    ],
    'seo_meta_title_product_manipulator' => [
        'fallback' => 'Manipulateur d’occasion',
        'multiline' => true,
    ],
    'seo_meta_description_product_robot' => [
        'fallback' => 'Découvrez ce robot d’occasion [{product_brand}] [{product_robot_mechanical_unit}] [{product_robot_electrical_unit}]. Garanti 12 mois, c’est la solution idéale pour automatiser vos applications à moindre coût !',
        'multiline' => true,
    ],
    'seo_meta_description_product_cell' => [
        'fallback' => 'Découvrez cette cellule robotisée d’occasion – [{product_process}] ([{product_robot_yom}]). Transfert et mise en service disponibles. Une solution idéale pour automatiser vos applications à moindre coût !',
        'multiline' => true,
    ],
    'seo_meta_description_product_spare' => [
        'fallback' => 'Pièce [{product_reference}] ([{product_designation}]) pour robots industriels [{product_brand}]. Réparez ou maintenez le fonctionnement fiable de vos robots [{product_brand}] à coûts maîtrisés !',
        'multiline' => true,
    ],
    'seo_meta_description_product_manipulator' => [
        'fallback' => '',
        'multiline' => true,
    ],
    'seo_breadcrumb_single_product_robot' => [
        'fallback' => '[{product_robot_mechanical_unit}] - Année [{product_robot_yom}]',
        'multiline' => true,
    ],
    'seo_breadcrumb_single_product_cell' => [
        'fallback' => '[{product_brand}] - Année [{product_robot_yom}]',
        'multiline' => true,
    ],
    'seo_breadcrumb_single_product_spare' => [
        'fallback' => '[{product_reference}] - [{product_designation}]',
        'multiline' => true,
    ],
    'seo_breadcrumb_single_product_manipulator' => [
        'fallback' => '[{product_name}] - [{product_robot_yom}]',
        'multiline' => true,
    ],
    'product_loop_title_robot' => [
        'fallback' => '[{product_brand}]<br/>[{product_robot_mechanical_unit}]<br/>[{product_robot_electrical_unit}]',
        'multiline' => true,
    ],
    'product_loop_title_spare' => [
        'fallback' => '[{product_brand}]<br/>[{product_designation}]',
        'multiline' => true,
    ],
    'product_loop_title_cell' => [
        'fallback' => '[{product_designation}]',
        'multiline' => true,
    ],
    'product_loop_title_manipulator' => [
        'fallback' => '[{product_designation}]',
        'multiline' => true,
    ],
    'product_loop_subtitle_robot' => [
        'fallback' => '[{product_robot_reach}] mm · [{product_robot_payload}] kg<br />
[{product_robot_yom}] · [{product_robot_runtime}] heures',
        'multiline' => true,
    ],
    'product_loop_subtitle_spare' => [
        'fallback' => '[{product_reference}]',
        'multiline' => true,
    ],
    'product_loop_subtitle_cell' => [
        'fallback' => '[{product_designation}]',
        'multiline' => true,
    ],
    'product_loop_subtitle_manipulator' => [
        'fallback' => '[{product_designation}]',
        'multiline' => true,
    ],
    'product_single_title_robot' => [
        'fallback' => '[{product_brand}] <br>[{product_robot_mechanical_unit}] <br/>[{product_robot_electrical_unit}]',
        'multiline' => true,
    ],
    'product_single_title_spare' => [
        'fallback' => '[{product_brand}]<br>[{product_reference}]<br>[{product_designation}]',
        'multiline' => true,
    ],
    'product_single_title_cell' => [
        'fallback' => '[{product_designation}]',
        'multiline' => true,
    ],
    'product_single_title_manipulator' => [
        'fallback' => '[{product_brand}]<br>[{product_designation}]',
        'multiline' => true,
    ],
    'product_tab_label_additional_information' => [
        'fallback' => 'Informations',
        'multiline' => true,
    ],
    'product_tab_label_video_presentation' => [
        'fallback' => 'Vidéo',
        'multiline' => true,
    ],
    'product_tab_label_technical_specs' => [
        'fallback' => 'Données techniques',
        'multiline' => true,
    ],
    'product_tab_label_olp_softwares' => [
        'fallback' => 'Logiciels OLP',
        'multiline' => true,
    ],
    'product_tab_label_processes' => [
        'fallback' => 'Applications',
        'multiline' => true,
    ],
    'product_tab_section_title_additional_information_about' => [
        'fallback' => 'À propos',
        'multiline' => true,
    ],
    'product_tab_section_title_additional_information_description' => [
        'fallback' => 'Description',
        'multiline' => true,
    ],
    'product_tab_section_title_additional_information_extra_service' => [
        'fallback' => 'Services complémentaires',
        'multiline' => true,
    ],
    'product_tab_section_title_additional_information_dimensions' => [
        'fallback' => 'Dimensions',
        'multiline' => true,
    ],
    'product_tab_section_title_additional_information_customs' => [
        'fallback' => 'Douane',
        'multiline' => true,
    ],
    'product_tab_section_title_technical_specs_description' => [
        'fallback' => 'Présentation du robot industriel [{product_brand}] [{product_robot_mechanical_unit}]',
        'multiline' => true,
    ],
    'product_tab_section_title_technical_specs_robot' => [
        'fallback' => 'Données techniques',
        'multiline' => true,
    ],
    'product_tab_section_title_technical_specs_ranges' => [
        'fallback' => 'Amplitudes',
        'multiline' => true,
    ],
    'product_tab_section_title_technical_specs_velocities' => [
        'fallback' => 'Vitesses',
        'multiline' => true,
    ],
    'product_tab_section_title_olp_softwares' => [
        'fallback' => 'Logiciels de programmation hors ligne pour robots [{product_brand}]',
        'multiline' => true,
    ],
    'product_tab_section_title_processes' => [
        'fallback' => 'Applications possibles avec le robot [{product_brand}] [{product_robot_mechanical_unit}]',
        'multiline' => true,
    ],
    'product_tab_text_video_presentation_intro_external' => [
        'fallback' => 'Vidéo de présentation de ce robot d’occasion [{product_brand}] [{product_robot_mechanical_unit}] [{product_robot_electrical_unit}] en fonctionnement, enregistrée lors des essais réalisés sur le site du vendeur.',
        'multiline' => true,
    ],
    'product_tab_text_video_presentation_intro_internal' => [
        'fallback' => 'Vidéo de présentation de ce robot d’occasion [{product_brand}] [{product_robot_mechanical_unit}] [{product_robot_electrical_unit}] en fonctionnement, enregistrée lors des essais réalisés dans nos locaux.',
        'multiline' => true,
    ],
    'product_tab_text_video_presentation_no_data' => [
        'fallback' => 'Nous publierons prochainement une vidéo de ce robot',
        'multiline' => true,
    ],
    'product_tab_text_technical_specs_description' => [
        'fallback' => 'Aucune description disponible pour ce robot...',
        'multiline' => true,
    ],
    'product_tab_text_olp_softwares' => [
        'fallback' => 'Les logiciels de programmation hors ligne (OLP) permettent de préparer et de valider un programme robot sans immobiliser la cellule de production. À partir d’un modèle numérique du robot et de son environnement (outils, périphériques, pièces), ces solutions permettent de concevoir les trajectoires, vérifier la portée et les collisions, estimer les temps de cycle, puis transférer le résultat vers le contrôleur pour la mise en service et les réglages finaux. Selon la marque et la version du logiciel, elles peuvent également intégrer des fonctions de simulation, de calibration, de gestion de projet et parfois d’optimisation des trajectoires.',
        'multiline' => true,
    ],
    'product_tab_text_olp_softwares_no_data' => [
        'fallback' => 'Aucun logiciel disponible pour les robots [{product_brand}]',
        'multiline' => true,
    ],
    'product_tab_text_ranges_no_data' => [
        'fallback' => 'Aucune amplitude renseignée',
        'multiline' => true,
    ],
    'product_tab_text_velocities_no_data' => [
        'fallback' => 'Aucune vitesse renseignée',
        'multiline' => true,
    ],
    'product_tab_text_processes' => [
        'fallback' => 'Les robots industriels peuvent être utilisés pour de nombreuses applications selon leur conception et leur configuration. La compatibilité dépend principalement de critères tels que la charge utile, la portée, le nombre d’axes, la précision/répétabilité, la vitesse, les options du contrôleur et l’environnement de travail (outillage, sécurité, périphériques, vision, etc.). L’adéquation finale à votre application doit être validée au cas par cas, selon les contraintes du procédé et les exigences d’intégration.',
        'multiline' => true,
    ],
    'product_tab_text_processes_no_data' => [
        'fallback' => 'Aucune application n’a encore été renseignée pour ce modèle...',
        'multiline' => true,
    ],
    'product_meta_label_brand' => [
        'fallback' => 'Marque',
        'multiline' => true,
    ],
    'product_meta_label_runtime' => [
        'fallback' => 'Heures de fonctionnement',
        'multiline' => true,
    ],
    'product_meta_label_runtime_unit' => [
        'fallback' => 'heure(s)',
        'multiline' => true,
    ],
    'product_meta_label_location' => [
        'fallback' => 'Localisation',
        'multiline' => true,
    ],
    'product_meta_label_robot_model' => [
        'fallback' => 'Mécanique',
        'multiline' => true,
    ],
    'product_meta_label_cabinet_model' => [
        'fallback' => 'Contrôleur',
        'multiline' => true,
    ],
    'product_meta_label_robot_yom' => [
        'fallback' => 'Année',
        'multiline' => true,
    ],
    'product_meta_label_robot_condition' => [
        'fallback' => 'État',
        'multiline' => true,
    ],
    'product_meta_label_robot_software' => [
        'fallback' => 'Version logicielle',
        'multiline' => true,
    ],
    'product_meta_label_robot_cables' => [
        'fallback' => 'Longueur de faisceau',
        'multiline' => true,
    ],
    'product_meta_label_designation' => [
        'fallback' => 'Désignation',
        'multiline' => true,
    ],
    'product_meta_label_reference' => [
        'fallback' => 'Référence',
        'multiline' => true,
    ],
    'product_meta_label_family' => [
        'fallback' => 'Catégorie',
        'multiline' => true,
    ],
    'product_meta_label_height' => [
        'fallback' => 'Hauteur',
        'multiline' => true,
    ],
    'product_meta_label_width' => [
        'fallback' => 'Largeur',
        'multiline' => true,
    ],
    'product_meta_label_length' => [
        'fallback' => 'Longueur',
        'multiline' => true,
    ],
    'product_meta_label_weight' => [
        'fallback' => 'Poids',
        'multiline' => true,
    ],
    'product_meta_label_sh_code' => [
        'fallback' => 'Code SH',
        'multiline' => true,
    ],
    'product_meta_label_origin_country' => [
        'fallback' => 'Pays d’origine',
        'multiline' => true,
    ],
    'product_meta_label_reach' => [
        'fallback' => 'Portée',
        'multiline' => true,
    ],
    'product_meta_label_payload' => [
        'fallback' => 'Charge utile',
        'multiline' => true,
    ],
    'product_meta_label_process' => [
        'fallback' => 'Application',
        'multiline' => true,
    ],
    'product_meta_label_updated_at' => [
        'fallback' => 'Dernière mise à jour : [{product_last_updated_at}]',
        'multiline' => true,
    ],
    'product_technical_label_brand_name' => [
        'fallback' => 'Marque',
        'multiline' => true,
    ],
    'product_technical_label_robot_model_name' => [
        'fallback' => 'Mécanique',
        'multiline' => true,
    ],
    'product_technical_label_cabinet_model_name' => [
        'fallback' => 'Contrôleur',
        'multiline' => true,
    ],
    'product_technical_label_yom' => [
        'fallback' => 'Année',
        'multiline' => true,
    ],
    'product_technical_label_runtime' => [
        'fallback' => 'Heures de fonctionnement',
        'multiline' => true,
    ],
    'product_technical_label_software_version' => [
        'fallback' => 'Version logicielle',
        'multiline' => true,
    ],
    'product_technical_label_cable_length' => [
        'fallback' => 'Longueur de faisceau',
        'multiline' => true,
    ],
    'product_technical_label_location_country' => [
        'fallback' => 'Localisation',
        'multiline' => true,
    ],
    'product_technical_label_height' => [
        'fallback' => 'Hauteur',
        'multiline' => true,
    ],
    'product_technical_label_width' => [
        'fallback' => 'Largeur',
        'multiline' => true,
    ],
    'product_technical_label_length' => [
        'fallback' => 'Longueur',
        'multiline' => true,
    ],
    'product_technical_label_weight' => [
        'fallback' => 'Poids',
        'multiline' => true,
    ],
    'product_technical_label_payload' => [
        'fallback' => 'Charge utile nominale',
        'multiline' => true,
    ],
    'product_technical_label_reach' => [
        'fallback' => 'Portée',
        'multiline' => true,
    ],
    'product_technical_label_repeatability' => [
        'fallback' => 'Répétabilité (ISO 9283)',
        'multiline' => true,
    ],
    'product_technical_label_ip_grade_base' => [
        'fallback' => 'Indice de protection (IEC 60529)',
        'multiline' => true,
    ],
    'product_technical_label_ip_grade_wrist' => [
        'fallback' => 'Indice de protection, poignet (IEC 60529)',
        'multiline' => true,
    ],
    'product_value_unknown' => [
        'fallback' => 'Inconnu',
        'multiline' => true,
    ],
    'product_technical_axis' => [
        'fallback' => 'Axe %s',
        'multiline' => true,
    ],
    'product_technical_payload' => [
        'fallback' => 'Charge utile',
        'multiline' => true,
    ],
    'product_technical_reach' => [
        'fallback' => 'Portée',
        'multiline' => true,
    ],
    'product_technical_repeatability' => [
        'fallback' => 'Répétabilité (ISO 9283)',
        'multiline' => true,
    ],
    'product_technical_mass' => [
        'fallback' => 'Masse du bras',
        'multiline' => true,
    ],
    'product_technical_ip_grade_base' => [
        'fallback' => 'Indice de protection (IEC 60529)',
        'multiline' => true,
    ],
    'product_technical_ip_grade_wrist' => [
        'fallback' => 'Indice de protection, poignet (IEC 60529)',
        'multiline' => true,
    ],
    'product_value_no_data' => [
        'fallback' => 'Aucune donnée',
        'multiline' => true,
    ],
    'product_value_not_specified' => [
        'fallback' => 'Non spécifié',
        'multiline' => true,
    ],
    'product_extra_services_presentation' => [
        'fallback' => 'En complément de la fourniture de ce robot [{product_brand}] [{product_robot_mechanical_unit}] [{product_robot_electrical_unit}], Robotique Concept peut vous proposer sur demande les prestations suivantes :',
        'multiline' => true,
    ],
    'product_extra_services_disclaimer' => [
        'fallback' => 'N’hésitez pas à nous contacter pour échanger sur votre projet d’automatisation. Nous vous proposerons une solution [{product_brand}] adaptée à vos besoins !',
        'multiline' => true,
    ],
    'product_extra_service_warranty' => [
        'fallback' => 'Extension de garantie à 18 ou 24 mois',
        'multiline' => true,
    ],
    'product_extra_service_shipping' => [
        'fallback' => 'Livraison sur site (France, UE, Monde)',
        'multiline' => true,
    ],
    'product_extra_service_customization' => [
        'fallback' => 'Mise à niveau ou modification des options logicielles et matérielles',
        'multiline' => true,
    ],
    'product_extra_service_programming' => [
        'fallback' => 'Conception d’application & programmation',
        'multiline' => true,
    ],
    'product_extra_service_preventive_maintenance' => [
        'fallback' => 'Maintenance préventive ponctuelle',
        'multiline' => true,
    ],
    'product_extra_service_contract_maintenance' => [
        'fallback' => 'Maintenance préventive sous contrat (1, 2 ou 3 ans)',
        'multiline' => true,
    ],
    'product_extra_service_support' => [
        'fallback' => 'Support technique & assistance au dépannage (téléphone & sur site)',
        'multiline' => true,
    ],
    'product_extra_service_transfer' => [
        'fallback' => 'Transfert, installation & mise en service sur site',
        'multiline' => true,
    ],
    'product_stock_status_instock' => [
        'fallback' => 'Disponible immédiatement !',
        'multiline' => true,
    ],
    'product_stock_status_outofstock' => [
        'fallback' => 'Indisponible ou vendu...',
        'multiline' => true,
    ],
    'product_stock_status_onbackorder' => [
        'fallback' => 'Disponible sur demande',
        'multiline' => true,
    ],
    'product_disclaimer_global_shop_affiliate' => [
        'fallback' => 'Robotique Concept n’est ni distributeur officiel ni représentant agréé des produits présentés sur son site. Les articles proposés proviennent du programme de démontage et de reconditionnement de Robotique Concept. Ils peuvent également provenir de surstocks (produits neufs) acquis par Robotique Concept.',
        'multiline' => true,
    ],
    'product_disclaimer_brand_shop_affiliate' => [
        'fallback' => 'Robotique Concept n’est ni distributeur officiel ni représentant agréé des produits [{product_brand}] présentés sur son site. Les articles proposés proviennent du programme de démontage et de reconditionnement de Robotique Concept. Ils peuvent également provenir de surstocks (produits neufs) acquis par Robotique Concept.',
        'multiline' => true,
    ],
    'product_disclaimer_robot_external' => [
        'fallback' => 'Ce robot industriel d’occasion n’est pas vendu par <b>Robotique Concept</b> et sera expédié directement depuis les locaux du vendeur ([{product_location_country}]). Notre garantie s’applique à compter de la date d’expédition. La disponibilité des équipements d’occasion sera confirmée sur demande. Aucune réservation de matériel n’est possible sans versement d’un acompte.',
        'multiline' => true,
    ],
    'product_disclaimer_robot_internal' => [
        'fallback' => 'Ce robot industriel d’occasion est vendu par <b>Robotique Concept</b> et sera expédié directement depuis nos locaux (France). Notre garantie s’applique à compter de la date d’expédition. La disponibilité des équipements d’occasion sera confirmée sur demande. Aucune réservation de matériel n’est possible sans versement d’un acompte.',
        'multiline' => true,
    ],
    'product_disclaimer_spare' => [
        'fallback' => 'Vous avez un doute sur la compatibilité de cette pièce <b>[{product_reference}]</b> avec votre robot [{product_brand}] ? Notre équipe est à votre disposition pour vous accompagner et vous orienter vers une solution adaptée !',
        'multiline' => true,
    ],
    'product_disclaimer_cell_external' => [
        'fallback' => 'Cette cellule robotisée d’occasion n’est pas vendue par <b>Robotique Concept</b> et sera expédiée directement depuis les locaux du vendeur ([{product_location_country}]). Notre garantie ne s’applique pas à cet équipement. La disponibilité des équipements d’occasion sera confirmée sur demande. Aucune réservation de matériel n’est possible sans versement d’un acompte.',
        'multiline' => true,
    ],
    'product_disclaimer_cell_internal' => [
        'fallback' => 'Cette cellule robotisée d’occasion est vendue par <b>Robotique Concept</b> et sera expédiée directement depuis nos locaux (France). Notre garantie s’applique à compter de la date d’expédition. La disponibilité des équipements d’occasion sera confirmée sur demande. Aucune réservation de matériel n’est possible sans versement d’un acompte.',
        'multiline' => true,
    ],
    'product_disclaimer_manipulator_external' => [
        'fallback' => 'Ce manipulateur industriel d’occasion n’est pas vendu par <b>Robotique Concept</b> et sera expédié directement depuis les locaux du vendeur ([{product_location_country}]). Notre garantie ne s’applique pas à cet équipement. La disponibilité des équipements d’occasion sera confirmée sur demande. Aucune réservation de matériel n’est possible sans versement d’un acompte.',
        'multiline' => true,
    ],
    'product_disclaimer_manipulator_internal' => [
        'fallback' => 'Ce manipulateur industriel d’occasion est vendu par <b>Robotique Concept</b> et sera expédié directement depuis nos locaux (France). Notre garantie s’applique à compter de la date d’expédition. La disponibilité des équipements d’occasion sera confirmée sur demande. Aucune réservation de matériel n’est possible sans versement d’un acompte.',
        'multiline' => true,
    ],
    'product_disclaimer_robot_description' => [
        'fallback' => 'Robot d’occasion <b>[{product_brand}]</b> <b>[{product_robot_mechanical_unit}]</b>, fabriqué en <b>[{product_robot_yom}]</b>. Ce robot est vendu complet avec son contrôleur d’origine <b>[{product_robot_electrical_unit}]</b>, son pupitre de programmation et sa déclaration d’incorporation. Il bénéficie de notre <b>garantie pièces standard de 12 mois</b>.',
        'multiline' => true,
    ],
    'product_disclaimer_robot_cta' => [
        'fallback' => 'Rigoureusement reconditionné et testé dans notre atelier, ce robot industriel [{product_brand}] [{product_robot_mechanical_unit}], associé à son contrôleur [{product_robot_electrical_unit}], s’intégrera efficacement à votre environnement de production et vous apportera fiabilité, performance et sérénité au quotidien. Avant livraison, nous réalisons une révision complète ainsi qu’une série de tests fonctionnels afin de garantir un fonctionnement stable et son adéquation à l’application prévue.',
        'multiline' => true,
    ],
    'product_disclaimer_robot_technical' => [
        'fallback' => 'Caractéristiques techniques du robot industriel [{product_brand}] [{product_robot_mechanical_unit}] selon la documentation du constructeur.',
        'multiline' => true,
    ],
    'product_disclaimer_olp_softwares_licence' => [
        'fallback' => 'Nous ne sommes pas autorisés à distribuer des licences de logiciels de simulation. Nous vous invitons donc à contacter directement [{product_brand}] pour toute demande concernant ce type de solution numérique.',
        'multiline' => true,
    ],
    'product_disclaimer_tab_processes' => [
        'fallback' => 'Robotique Concept n’est pas intégrateur de systèmes robotisés. Nous travaillons toutefois avec un réseau d’intégrateurs partenaires répartis sur le territoire afin de vous accompagner dans la mise en œuvre de vos applications robotisées.',
        'multiline' => true,
    ],
];
