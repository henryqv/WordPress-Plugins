<?php
/*
Plugin Name: Redirection of non-logged users
Plugin URI: https://henryquevedo.com/
Description: Redirige a los usuarios no autenticados a una URL específica configurable desde el administrador.
Version: 1.1
Author: Henry Quevedo  
Author URI: https://henryquevedo.com/
License: GPL2
*/

// Añadir el menú en el administrador
function add_redirection_menu() {
    add_menu_page(
        'Redirection of Non-Logged Users',
        'Redirection of Non-Logged Users',
        'manage_options',
        'redirection-settings',
        'redirection_settings_page',
        'dashicons-privacy',
        90
    );
}
add_action('admin_menu', 'add_redirection_menu');

// Crear la página de configuración
function redirection_settings_page() {
    ?>
    <div class="wrap">
        <h1>Redirection of Non-Logged Users</h1>
        <?php
        if (isset($_GET['settings-updated'])) {
            add_settings_error('redirection_messages', 'redirection_message', 'Cambios guardados', 'updated');
        }
        settings_errors('redirection_messages');
        ?>
        <form method="post" action="options.php">
            <?php
            settings_fields('redirection-settings-group');
            do_settings_sections('redirection-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Registrar la configuración
function redirection_settings_init() {
    register_setting('redirection-settings-group', 'redirection_url');

    add_settings_section(
        'redirection_settings_section',
        'Redirection Settings',
        'redirection_settings_section_callback',
        'redirection-settings'
    );

    add_settings_field(
        'redirection_url_field',
        'Redirection URL',
        'redirection_url_field_callback',
        'redirection-settings',
        'redirection_settings_section'
    );
}
add_action('admin_init', 'redirection_settings_init');

function redirection_settings_section_callback() {
    echo 'Introduce la URL a la que los usuarios no autenticados serán redirigidos.';
}

function redirection_url_field_callback() {
    $url = esc_url(get_option('redirection_url'));
    echo '<input type="url" name="redirection_url" value="' . $url . '" class="regular-text">';
}

// Función de redirección
function check_user_logged_in() {
    if (!is_user_logged_in()) {
        $redirection_url = esc_url(get_option('redirection_url'));
        if ($redirection_url) {
            wp_redirect($redirection_url);
            exit;
        }
    }
}
add_action('template_redirect', 'check_user_logged_in');
?>