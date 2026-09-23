<?php


declare(strict_types=1);





namespace WPRC\Catalog\WooCommerce\Taxonomy;

use WPRC\Catalog\Security\Capabilities;





defined('ABSPATH') || exit;








final class ProductCat


{


    public const META_MENU_TITLE = 'category_menu_title';





    public static function render_menu_title_add($taxonomy): void


    {


        $metaKey = self::META_MENU_TITLE;


        $label = esc_html__('Titre dans les menus', 'wprc');


        $description = esc_html__('Saisissez le titre à afficher dans les menus.', 'wprc');


        ?>


        <div class="form-field term-group">


            <label for="<?php echo esc_attr($metaKey); ?>"><?php echo $label; ?></label>


            <input type="text" name="<?php echo esc_attr($metaKey); ?>" id="<?php echo esc_attr($metaKey); ?>" value="" />


            <p class="description"><?php echo $description; ?></p>


        </div>


        <?php


    }





    public static function render_menu_title_edit($term, $taxonomy): void


    {


        $metaKey = self::META_MENU_TITLE;


        $current = (string) get_term_meta((int) $term->term_id, $metaKey, true);


        $label = esc_html__('Titre dans les menus', 'wprc');


        $description = esc_html__('Saisissez le titre à afficher dans les menus.', 'wprc');


        ?>


        <tr class="form-field term-group-wrap">


            <th scope="row">


                <label for="<?php echo esc_attr($metaKey); ?>"><?php echo $label; ?></label>


            </th>


            <td>


                <input type="text" name="<?php echo esc_attr($metaKey); ?>" id="<?php echo esc_attr($metaKey); ?>" value="<?php echo esc_attr($current); ?>" />


                <p class="description"><?php echo $description; ?></p>


            </td>


        </tr>


        <?php


    }





    public static function save_metas($term_id, $tt_id): void


    {


        if (!Capabilities::canManageProductTerms()) {


            return;


        }





        if (!isset($_POST[self::META_MENU_TITLE])) {


            return;


        }





        $termId = absint($term_id);


        if ($termId <= 0) {


            return;


        }





        update_term_meta(


            $termId,


            self::META_MENU_TITLE,


            sanitize_text_field(wp_unslash((string) $_POST[self::META_MENU_TITLE]))


        );


    }


}


