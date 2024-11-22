<?php
/*
 * Plugin Name:       WordPress Custom Hooks
 * Plugin URI:        https://henryquevedo.com/plugins/wpcustomhooks/
 * Description:       Plugin para agregar funciones personalizadas a los hooks de WordPress.
 * Version:           1.0.1
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Henry Quevedo
 * Author URI:        https://henryquevedo.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wpcustomhooks
 */

// Add admin menu
function custom_hooks_plugin_menu() {
    add_menu_page('Custom Hooks', 'Custom Hooks', 'manage_options', 'custom-hooks', 'custom_hooks_page');
}
add_action('admin_menu', 'custom_hooks_plugin_menu');

// Display custom hooks page
function custom_hooks_page() {
    ?>
    <div class="wrap">
        <h2>Custom Hooks</h2>
        <form method="post" action="options.php">
            <?php settings_fields('custom_hooks_group'); ?>
            <?php do_settings_sections('custom_hooks_group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Selecciona el tipo de Hook:</th>
                    <td>
                        <select id="hook_type_select">
                            <option value="" disabled selected>Selecciona el tipo de Hook</option>
                            <option value="admin_hooks">Hooks de Administración</option>
                            <option value="page_load_hooks">Hooks de Carga de Página</option>
                            <option value="login_hooks">Hooks de Inicio de Sesión</option>
                            <option value="post_hooks">Hooks de Publicación</option>
                            <option value="comment_hooks">Hooks de Comentarios</option>
                            <option value="media_hooks">Hooks de Medios</option>
                            <!-- Añade más opciones de tipo de hook aquí -->
                        </select>
                    </td>
                </tr>
                <tr valign="top" id="hook_select_row" style="display:none;">
                    <th scope="row">Selecciona el Hook:</th>
                    <td>
                        <select id="hook_select">
                            <option value="" disabled selected>Selecciona el Hook</option>
                        </select>
                    </td>
                </tr>
                <tr valign="top" id="hook_description_row" style="display:none;">
                    <th scope="row">Descripción del Hook:</th>
                    <td><p id="hook_description"></p></td>
                </tr>
                <tr valign="top" id="hook_code_row" style="display:none;">
                    <th scope="row">Código del Hook:</th>
                    <td><textarea id="hook_code" rows="5" cols="50"></textarea></td>
                </tr>
            </table>
            <input type="submit" name="submit" id="submit" class="button button-primary" value="Guardar Hooks">
        </form>
    </div>

    <script>
        jQuery(document).ready(function($) {
            $('#hook_type_select').change(function() {
                var hookType = $(this).val();
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_hooks',
                        hook_type: hookType
                    },
                    success: function(response) {
                        $('#hook_select').html(response);
                        $('#hook_select_row, #hook_description_row, #hook_code_row').show();
                    }
                });
            });

            $('#hook_select').change(function() {
                var selectedHook = $(this).val();
                var hookDescription = $(this).find('option:selected').data('description');
                $('#hook_description').text(hookDescription);
            });
        });
    </script>
    <?php
}

// Register settings
function custom_hooks_register_settings() {
    register_setting('custom_hooks_group', 'custom_hooks', 'sanitize_text_field');
}
add_action('admin_init', 'custom_hooks_register_settings');

// Enqueue scripts and styles
function custom_hooks_enqueue_scripts() {
    wp_enqueue_script('custom-hooks-admin-script', plugin_dir_url(__FILE__) . 'admin-script.js', array('jquery'), '1.0', true);
}
add_action('admin_enqueue_scripts', 'custom_hooks_enqueue_scripts');

// AJAX handler to get hooks based on hook type
function custom_hooks_get_hooks() {
    $hookType = $_POST['hook_type'];
    $hooks = array();
    
    // Populate $hooks array based on hook type
    if ($hookType === 'admin_hooks') {
        $hooks = array(
            'admin_init' => 'Se ejecuta después de que WordPress ha terminado de cargar, pero antes de que se envíe cualquier salida al navegador.',
            'admin_menu' => 'Se utiliza para agregar elementos al menú de administración.',
            'admin_bar_menu' => 'Se ejecuta para agregar elementos al menú de la barra de administración (admin bar).',
            'in_admin_header' => 'Se ejecuta dentro del encabezado del área de administración.',
            'admin_action_{action_name}' => 'Se ejecuta cuando se llama a una acción del área de administración.',
            'admin_post_{action_name}' => 'Se ejecuta cuando se llama a una acción POST del área de administración.',
            'admin_post_nopriv_{action_name}' => 'Se ejecuta cuando se llama a una acción POST del área de administración por un usuario no autenticado.',
            'wp_ajax_{action_name}' => 'Se ejecuta cuando se llama a una acción AJAX específica.',
            'wp_ajax_nopriv_{action_name}' => 'Se ejecuta cuando se llama a una acción AJAX específica, pero por un usuario no autenticado.',
            'load-{page_hook}' => 'Se ejecuta cuando se carga una página específica del administrador.',
            'admin_notices' => 'Se utiliza para mostrar avisos en el área de administración.',
            // Añade más hooks de administración aquí
        );
    } elseif ($hookType === 'page_load_hooks') {
        $hooks = array(
            'init' => 'Se ejecuta después de que WordPress ha terminado de cargar, pero antes de que se envíe cualquier salida al navegador.',
            'wp_loaded' => 'Se ejecuta después de que WordPress ha terminado de cargar completamente, incluyendo plugins y temas.',
            'template_redirect' => 'Se ejecuta justo antes de que WordPress determine qué plantilla de página utilizar.',
            'get_header' => 'Se ejecuta cuando WordPress llama al encabezado de la plantilla.',
            'wp_enqueue_scripts' => 'Se utiliza para encolar scripts y estilos en el frontend del sitio.',
            'wp_footer' => 'Se ejecuta antes de cerrar la etiqueta <footer> en la parte inferior del frontend.',
            'wp_head' => 'Se ejecuta antes de cerrar la etiqueta <head> en la parte superior del frontend.',
            'loop_start' => 'Se ejecuta al inicio del loop de WordPress.',
            'loop_end' => 'Se ejecuta al final del loop de WordPress.',
            // Añade más hooks de carga de página aquí
        );
    } elseif ($hookType === 'login_hooks') {
        $hooks = array(
            'wp_login' => 'Se ejecuta cuando un usuario inicia sesión en WordPress.',
            'wp_logout' => 'Se ejecuta cuando un usuario cierra sesión en WordPress.',
            'login_form' => 'Se ejecuta al mostrar el formulario de inicio de sesión.',
            'login_redirect' => 'Se ejecuta al redirigir después del inicio de sesión.',
            'authenticate' => 'Se ejecuta durante la autenticación del usuario.',
            'wp_login_failed' => 'Se ejecuta cuando falla un intento de inicio de sesión.',
            'wp_authenticate_user' => 'Se ejecuta cuando se autentica un usuario.',
            'wp_authenticate_email_password' => 'Se ejecuta cuando se autentica un usuario por email y contraseña.',
            // Añade más hooks de inicio de sesión aquí
        );
    } elseif ($hookType === 'post_hooks') {
        $hooks = array(
            'publish_post' => 'Se ejecuta después de que se ha publicado una publicación.',
            'edit_post' => 'Se ejecuta después de que se ha editado una publicación.',
            'delete_post' => 'Se ejecuta después de que se ha eliminado una publicación.',
            'save_post' => 'Se ejecuta al guardar una publicación.',
            'wp_insert_post' => 'Se ejecuta al insertar una nueva publicación en la base de datos.',
            'transition_post_status' => 'Se ejecuta cuando cambia el estado de una publicación.',
            'publish_future_post' => 'Se ejecuta cuando se publica una publicación programada.',
            'pre_post_update' => 'Se ejecuta antes de actualizar una publicación.',
            // Añade más hooks de publicación aquí
        );
    } elseif ($hookType === 'comment_hooks') {
        $hooks = array(
            'comment_post' => 'Se ejecuta después de que se ha publicado un comentario.',
            'edit_comment' => 'Se ejecuta después de que se ha editado un comentario.',
            'delete_comment' => 'Se ejecuta después de que se ha eliminado un comentario.',
            'wp_insert_comment' => 'Se ejecuta al insertar un nuevo comentario en la base de datos.',
            'pre_comment_approved' => 'Se ejecuta justo antes de aprobar un comentario.',
            'comment_approved_' => 'Se ejecuta cuando se aprueba un comentario.',
            'comment_unapproved_' => 'Se ejecuta cuando se desaprueba un comentario.',
            // Añade más hooks de comentarios aquí
        );
    } elseif ($hookType === 'media_hooks') {
        $hooks = array(
            'add_attachment' => 'Se ejecuta después de que se ha agregado un archivo adjunto.',
            'delete_attachment' => 'Se ejecuta después de que se ha eliminado un archivo adjunto.',
            'edit_attachment' => 'Se ejecuta después de que se ha editado un archivo adjunto.',
            'wp_generate_attachment_metadata' => 'Se ejecuta al generar los metadatos de un archivo adjunto.',
            'attachment_fields_to_save' => 'Se ejecuta al guardar los campos de un archivo adjunto.',
            'intermediate_image_sizes' => 'Se utiliza para definir los tamaños de imagen intermedios.',
            'wp_get_attachment_image_attributes' => 'Se utiliza para filtrar los atributos de la imagen adjunta.',
            'image_send_to_editor' => 'Se ejecuta al enviar una imagen al editor.',
            // Añade más hooks de medios aquí
        );
    }
    
    // Output the hooks
    foreach ($hooks as $hook => $description) {
        echo "<option value='$hook' data-description='$description'>$hook</option>";
    }
    
    die();
}
add_action('wp_ajax_get_hooks', 'custom_hooks_get_hooks');
?>
