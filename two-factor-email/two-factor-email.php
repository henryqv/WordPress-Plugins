<?php
/*
Plugin Name: Two Factor Authentication via Email
Description: Adds a two-factor authentication via email for WordPress login.
Plugin URI:        https://henryquevedo.com/plugins/
Version:           1.0.2
Requires at least: 5.2
Requires PHP:      7.2
Author:            Henry Quevedo
Author URI:        https://henryquevedo.com/
License:           GPL v2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:       two-factor-email
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Iniciar sesión de PHP de forma segura
add_action('init', 'two_factor_auth_start_session', 1);
function two_factor_auth_start_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Interceptar el inicio de sesión
add_filter('wp_authenticate_user', 'two_factor_auth_intercept_login', 10, 2);
function two_factor_auth_intercept_login($user, $password) {
    if (is_wp_error($user)) {
        return $user;
    }

    // Generar código de verificación y guardarlo en el meta del usuario
    $code = wp_rand(100000, 999999);
    update_user_meta($user->ID, 'two_factor_auth_code', $code);

    // Enviar el código al correo del usuario
    wp_mail($user->user_email, 'Your Verification Code', 'Your verification code is: ' . $code);

    // Guardar información en la sesión y redirigir a la página de verificación
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['two_factor_auth_user_id'] = $user->ID;

    wp_clear_auth_cookie(); // Elimina cookies para evitar inicio de sesión automático
    wp_redirect(home_url('/two-factor-auth')); // Redirigir a la página de verificación
    exit;
}

// Crear la página de verificación si no existe
add_action('init', 'two_factor_auth_create_verification_page');
function two_factor_auth_create_verification_page() {
    $slug = 'two-factor-auth';
    if (!get_page_by_path($slug)) {
        wp_insert_post(array(
            'post_title' => 'Two Factor Authentication',
            'post_content' => '[two_factor_auth_form]', // Shortcode para el formulario
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_name' => $slug,
        ));
    }
}

// Shortcode para el formulario de verificación
add_shortcode('two_factor_auth_form', 'two_factor_auth_form_shortcode');
function two_factor_auth_form_shortcode() {
    // Comprobar si se envió el código
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verification_code'])) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $user_id = isset($_SESSION['two_factor_auth_user_id']) ? $_SESSION['two_factor_auth_user_id'] : null;

        if (!$user_id) {
            echo '<p>Error: User session not found. Please try logging in again.</p>';
            return;
        }

        $stored_code = get_user_meta($user_id, 'two_factor_auth_code', true);

        // Validar el código ingresado
        if ($_POST['verification_code'] == $stored_code) {
            wp_set_auth_cookie($user_id); // Crear cookies para iniciar sesión
            unset($_SESSION['two_factor_auth_user_id']); // Limpiar la sesión
            delete_user_meta($user_id, 'two_factor_auth_code'); // Borrar código de verificación
            wp_redirect(home_url());
            exit;
        } else {
            echo '<p>Invalid verification code. Please try again.</p>';
        }
    }

    // Mostrar el formulario de verificación
    ob_start();
    ?>
    <form method="post">
        <p>
            <label for="verification_code">Verification Code:</label>
            <input type="text" name="verification_code" id="verification_code" required>
        </p>
        <p>
            <input type="submit" value="Verify">
        </p>
    </form>
    <?php
    return ob_get_clean();
}

// Proteger la página de verificación
add_action('template_redirect', 'two_factor_auth_protect_verification_page');
function two_factor_auth_protect_verification_page() {
    if (is_page('two-factor-auth')) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $user_id = isset($_SESSION['two_factor_auth_user_id']) ? $_SESSION['two_factor_auth_user_id'] : null;

        // Redirigir si no hay sesión activa
        if (!$user_id) {
            wp_redirect(home_url('/wp-login.php'));
            exit;
        }
    }
}
