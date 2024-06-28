<?php
/*
Plugin Name:       SEO Personalizado
Description:       Un plugin de WordPress para SEO que permite cambiar el título de las páginas, agregar una meta descripción, elegir si se desea agregar los meta noindex y nofollow, y un campo de texto para agregar schemas.
Plugin URI:        https://henryquevedo.com/plugins/
Version:           1.0.0
Requires at least: 5.2
Requires PHP:      7.2
Author:            Henry Quevedo
Author URI:        https://henryquevedo.com/
License:           GPL v2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:       seo-personalizado
*/

function guardar_meta_box_seo_personalizado($post_id) {
    if (!isset($_POST['seo_personalizado_nonce']) || !wp_verify_nonce($_POST['seo_personalizado_nonce'], basename(__FILE__))) {
        return $post_id;
    }

    $post_type = get_post_type($post_id);
    $tipos = ['post', 'page', 'portfolio'];
    if (!in_array($post_type, $tipos) || !current_user_can('edit_post', $post_id)) {
        return $post_id;
    }

    $nuevo_titulo = isset($_POST['seo_personalizado_title']) ? sanitize_text_field($_POST['seo_personalizado_title']) : '';
    $nueva_meta_descripcion = isset($_POST['seo_personalizado_meta_description']) ? sanitize_textarea_field($_POST['seo_personalizado_meta_description']) : '';
    $nuevo_noindex = isset($_POST['seo_personalizado_noindex']) ? 'on' : '';
    $nuevo_nofollow = isset($_POST['seo_personalizado_nofollow']) ? 'on' : '';
    $nuevo_schema = isset($_POST['seo_personalizado_schema']) ? sanitize_textarea_field($_POST['seo_personalizado_schema']) : '';
    $nuevo_canonical = isset($_POST['seo_personalizado_canonical']) ? esc_url_raw($_POST['seo_personalizado_canonical']) : '';

    update_post_meta($post_id, 'seo_personalizado_title', $nuevo_titulo);
    update_post_meta($post_id, 'seo_personalizado_meta_description', $nueva_meta_descripcion);
    update_post_meta($post_id, 'seo_personalizado_noindex', $nuevo_noindex);
    update_post_meta($post_id, 'seo_personalizado_nofollow', $nuevo_nofollow);
    update_post_meta($post_id, 'seo_personalizado_schema', $nuevo_schema);
    update_post_meta($post_id, 'seo_personalizado_canonical', $nuevo_canonical);
}

add_action('save_post', 'guardar_meta_box_seo_personalizado');

function agregar_meta_boxes_seo_personalizado() {
    $tipos = ['post', 'page', 'portfolio'];

    foreach ($tipos as $tipo) {
        add_meta_box(
            'seo_personalizado_meta_box',
            'SEO Personalizado',
            'mostrar_meta_box_seo_personalizado',
            $tipo,
            'normal',
            'high'
        );
    }
}

add_action('add_meta_boxes', 'agregar_meta_boxes_seo_personalizado');

function mostrar_meta_box_seo_personalizado($post) {
    wp_nonce_field(basename(__FILE__), 'seo_personalizado_nonce');
    $title = get_post_meta($post->ID, 'seo_personalizado_title', true);
    $meta_description = get_post_meta($post->ID, 'seo_personalizado_meta_description', true);
    $noindex = get_post_meta($post->ID, 'seo_personalizado_noindex', true);
    $nofollow = get_post_meta($post->ID, 'seo_personalizado_nofollow', true);
    $schema = get_post_meta($post->ID, 'seo_personalizado_schema', true);
    $canonical = get_post_meta($post->ID, 'seo_personalizado_canonical', true);
    ?>
    <div class="seo-personalizado-meta-box">
        <p>
            <label for="seo_personalizado_title">Título de la página:</label>
            <input type="text" name="seo_personalizado_title" value="<?php echo esc_attr($title); ?>" />
        </p>
        <p>
            <label for="seo_personalizado_meta_description">Meta Descripción:</label>
            <textarea name="seo_personalizado_meta_description"><?php echo esc_textarea($meta_description); ?></textarea>
        </p>
        <p>
            <label for="seo_personalizado_noindex">
                <input type="checkbox" name="seo_personalizado_noindex" <?php checked($noindex, 'on'); ?> />
                Agregar meta noindex
            </label>
        </p>
        <p>
            <label for="seo_personalizado_nofollow">
                <input type="checkbox" name="seo_personalizado_nofollow" <?php checked($nofollow, 'on'); ?> />
                Agregar meta nofollow
            </label>
        </p>
        <p>
            <label for="seo_personalizado_schema">Schemas:</label>
            <textarea name="seo_personalizado_schema"><?php echo esc_textarea($schema); ?></textarea>
        </p>
        <p>
            <label for="seo_personalizado_canonical">URL Canonical:</label>
            <input type="text" name="seo_personalizado_canonical" value="<?php echo esc_url($canonical); ?>" />
        </p>
    </div>
    <?php
}

function seo_personalizado_modificar_titulo($title) {
    if (is_singular()) {
        global $post;
        $custom_title = get_post_meta($post->ID, 'seo_personalizado_title', true);
        if ($custom_title) {
            return $custom_title;
        }
    }
    return $title;
}

add_filter('pre_get_document_title', 'seo_personalizado_modificar_titulo');

function seo_personalizado_agregar_meta_tags() {
    if (is_singular()) {
        global $post;
        $meta_description = get_post_meta($post->ID, 'seo_personalizado_meta_description', true);
        $noindex = get_post_meta($post->ID, 'seo_personalizado_noindex', true);
        $nofollow = get_post_meta($post->ID, 'seo_personalizado_nofollow', true);
        $schema = get_post_meta($post->ID, 'seo_personalizado_schema', true);
        $canonical = get_post_meta($post->ID, 'seo_personalizado_canonical', true);

        if ($meta_description) {
            echo '<meta name="description" content="' . esc_attr($meta_description) . '" />';
        }

        if ($noindex === 'on') {
            echo '<meta name="robots" content="noindex" />';
        }

        if ($nofollow === 'on') {
            echo '<meta name="robots" content="nofollow" />';
        }

        if ($schema) {
            echo '<script type="application/ld+json">' . wp_kses_post($schema) . '</script>';
        }

        if ($canonical) {
            echo '<link rel="canonical" href="' . esc_url($canonical) . '" />';
        }
    }
}

add_action('wp_head', 'seo_personalizado_agregar_meta_tags');

// Registrar y encolar el archivo CSS en el admin de WordPress
function seo_personalizado_admin_styles() {
    wp_enqueue_style('seo-personalizado-styles', plugin_dir_url(__FILE__) . 'css/seo-personalizado.css');
}

add_action('admin_enqueue_scripts', 'seo_personalizado_admin_styles');
?>
