<?php
/*
Plugin Name: SMTP Setup
Plugin URI: https://henryquevedo.com/
Description: Configurador SMTP para envíos de correos desde WordPress.
Version: 1.1
Author: Henry Quevedo  
Author URI: https://henryquevedo.com/
License: GPL2
*/


// Función para agregar la página de configuración SMTP en el admin de WordPress
function smtp_admin_menu() {
    add_options_page(
        'Configuración SMTP',
        'Configuración SMTP',
        'manage_options',
        'smtp-settings',
        'smtp_settings_page'
    );
}
add_action('admin_menu', 'smtp_admin_menu');

// Función que muestra el formulario de configuración SMTP en la página de ajustes
function smtp_settings_page() {
    if (isset($_POST['save_smtp_settings'])) {
        update_option('smtp_host', sanitize_text_field($_POST['smtp_host']));
        update_option('smtp_port', sanitize_text_field($_POST['smtp_port']));
        update_option('smtp_user', sanitize_text_field($_POST['smtp_user']));
        update_option('smtp_password', sanitize_text_field($_POST['smtp_password']));
        update_option('smtp_ssl', isset($_POST['smtp_ssl']) ? '1' : '0');
        update_option('smtp_from_name', sanitize_text_field($_POST['smtp_from_name'])); // Guardar nombre remitente
        echo '<div class="updated"><p>Configuración SMTP guardada.</p></div>';
    }

    if (isset($_POST['send_test_email'])) {
        $to = sanitize_email($_POST['test_email']);
        $subject = 'Correo de Prueba';
        $message = 'Este es un correo de prueba enviado desde la configuración SMTP de WordPress.';
        
        // Asegurar que el remitente coincide con el usuario SMTP
        $from_email = get_option('smtp_user');
        $from_name = get_option('smtp_from_name') ? get_option('smtp_from_name') : 'WordPress';
        $headers = array('Content-Type: text/html; charset=UTF-8', 'From: ' . $from_name . ' <' . $from_email . '>');

        try {
            if (!wp_mail($to, $subject, $message, $headers)) {
                throw new Exception('El correo no se pudo enviar.');
            }
            echo '<div class="updated"><p>Correo de prueba enviado exitosamente a ' . esc_html($to) . '.</p></div>';
        } catch (Exception $e) {
            error_log('Error al enviar el correo de prueba: ' . $e->getMessage());
            echo '<div class="error"><p>Error al enviar el correo de prueba: ' . esc_html($e->getMessage()) . '. Por favor verifica la configuración SMTP.</p></div>';
        }
    }
    
    ?>
    <div class="wrap">
        <h2>Configuración SMTP</h2>
        <form method="POST" action="">
            <table class="form-table">
                <tr>
                    <th scope="row">Servidor SMTP</th>
                    <td><input type="text" name="smtp_host" value="<?php echo esc_attr(get_option('smtp_host')); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row">Puerto SMTP</th>
                    <td><input type="text" name="smtp_port" value="<?php echo esc_attr(get_option('smtp_port')); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row">Usuario SMTP</th>
                    <td><input type="text" name="smtp_user" value="<?php echo esc_attr(get_option('smtp_user')); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row">Contraseña SMTP</th>
                    <td><input type="password" name="smtp_password" value="<?php echo esc_attr(get_option('smtp_password')); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row">Usar SSL</th>
                    <td><input type="checkbox" name="smtp_ssl" <?php checked(get_option('smtp_ssl'), '1'); ?> /></td>
                </tr>
                <tr>
                    <th scope="row">Nombre del Remitente</th>
                    <td><input type="text" name="smtp_from_name" value="<?php echo esc_attr(get_option('smtp_from_name')); ?>" class="regular-text" /></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="save_smtp_settings" class="button-primary" value="Guardar Configuración SMTP" />
            </p>
        </form>

        <h2>Enviar un correo de prueba</h2>
        <form method="POST" action="">
            <table class="form-table">
                <tr>
                    <th scope="row">Correo Electrónico de Prueba</th>
                    <td><input type="email" name="test_email" value="" class="regular-text" required /></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="send_test_email" class="button-secondary" value="Enviar Correo de Prueba" />
            </p>
        </form>
    </div>
    <?php
}

// Configurar PHPMailer con los ajustes SMTP guardados
function smtp_mail($phpmailer) {
    $phpmailer->isSMTP();
    $phpmailer->Host = get_option('smtp_host');
    $phpmailer->SMTPAuth = true;
    $phpmailer->Username = get_option('smtp_user');
    $phpmailer->Password = get_option('smtp_password');
    $phpmailer->Port = get_option('smtp_port');
    $phpmailer->SMTPDebug = 2;
    $phpmailer->Debugoutput = function($str, $level) {
        error_log("SMTP Debug: $str");
    };

    if (get_option('smtp_ssl')) {
        $phpmailer->SMTPSecure = 'ssl';
    } else {
        $phpmailer->SMTPSecure = '';
    }

    // Usar el nombre de remitente configurado
    $from_name = get_option('smtp_from_name') ? get_option('smtp_from_name') : 'WordPress';
    $phpmailer->setFrom(get_option('smtp_user'), $from_name); // Nombre personalizado
}
add_action('phpmailer_init', 'smtp_mail');

// Forzar el correo de remitente a ser el mismo que el autenticado en SMTP
add_filter('wp_mail_from', function($original_email_address) {
    return get_option('smtp_user');
});

// Opcional: Nombre del remitente para coincidir con el usuario autenticado
add_filter('wp_mail_from_name', function($original_name) {
    return get_option('smtp_from_name') ? get_option('smtp_from_name') : 'WordPress';
});
