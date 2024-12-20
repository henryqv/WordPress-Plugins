<?php
/**
 * Plugin Name: Envío de Correos Masivos Básicos
 * Description: Plugin para enviar correos masivos personalizados desde un archivo CSV.
 * Version: 1.6
 * Author: Henry Quevedo
 * Author URI: https://henryquevedo.com
 */

if (!defined('ABSPATH')) {
    exit; // Evitar acceso directo
}

function correo_masivo_admin_menu() {
    add_menu_page(
        'Correo Masivo',
        'Correo Masivo',
        'manage_options',
        'correo-masivo',
        'correo_masivo_pagina_admin',
        'dashicons-email-alt',
        20
    );
}
add_action('admin_menu', 'correo_masivo_admin_menu');

function correo_masivo_pagina_admin() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nombre_remitente = sanitize_text_field($_POST['nombre_remitente']);
        $correo_remitente = sanitize_email($_POST['correo_remitente']);
        $contenido_html = wp_kses_post(stripslashes($_POST['contenido_html']));
        $asunto = sanitize_text_field($_POST['asunto']);

        if (isset($_FILES['archivo_csv']) && $_FILES['archivo_csv']['error'] === UPLOAD_ERR_OK) {
            $ruta_csv = wp_upload_dir()['path'] . '/' . $_FILES['archivo_csv']['name'];
            move_uploaded_file($_FILES['archivo_csv']['tmp_name'], $ruta_csv);

            $destinatarios = correo_masivo_leer_csv($ruta_csv);

            if (!empty($destinatarios)) {
                $resultado = correo_masivo_enviar_correos($destinatarios, $contenido_html, $asunto, $nombre_remitente, $correo_remitente);
                echo "<div class='updated'><p>$resultado</p></div>";
            } else {
                echo "<div class='error'><p>No se encontraron destinatarios en el archivo CSV.</p></div>";
            }
        } else {
            echo "<div class='error'><p>Error al subir el archivo CSV.</p></div>";
        }
    }

    ?>
    <div class="wrap">
        <h1>Correo Masivo</h1>
        <form method="POST" enctype="multipart/form-data">
            <table class="form-table">
                <tr>
                    <th><label for="nombre_remitente">Nombre del Remitente</label></th>
                    <td><input type="text" id="nombre_remitente" name="nombre_remitente" required class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="correo_remitente">Correo del Remitente</label></th>
                    <td><input type="email" id="correo_remitente" name="correo_remitente" required class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="asunto">Asunto del Correo</label></th>
                    <td><input type="text" id="asunto" name="asunto" required class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="contenido_html">Contenido HTML</label></th>
                    <td>
                        <?php
                        $editor_id = 'contenido_html';
                        $settings = [
                            'textarea_name' => 'contenido_html',
                            'media_buttons' => true,
                            'teeny'         => false,
                            'textarea_rows' => 10,
                            'quicktags'     => true,
                        ];
                        wp_editor('', $editor_id, $settings);
                        ?>
                        <p>Usa etiquetas como <code>{nombre}</code>, <code>{correo}</code>, o cualquier columna definida en el CSV.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="archivo_csv">Archivo CSV</label></th>
                    <td>
                        <input type="file" id="archivo_csv" name="archivo_csv" required>
                        <p>Debes subir un CSV delimitado por comas.</p>
                        </td>
                </tr>
            </table>
            <p>
                <input type="submit" value="Enviar Correos" class="button button-primary">
            </p>
        </form>
        <small>Desarrollado por <a href="https://www.henryquevedo.com/" target="_blank">Henry Quevedo</a></small>
    </div>
    <?php
}

function correo_masivo_leer_csv($ruta_csv) {
    $destinatarios = [];
    $encabezados = [];

    if (($handle = fopen($ruta_csv, 'r')) !== false) {
        $fila = 0;
        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            if ($fila === 0) {
                $encabezados = array_map('trim', $data);
            } else {
                $filaDatos = [];
                foreach ($data as $indice => $valor) {
                    $encabezado = $encabezados[$indice] ?? 'columna_' . $indice;
                    $filaDatos[trim($encabezado)] = trim($valor);
                }
                $destinatarios[] = $filaDatos;
            }
            $fila++;
        }
        fclose($handle);
    }

    return $destinatarios;
}

function correo_masivo_enviar_correos($destinatarios, $contenido_html, $asunto, $nombre_remitente, $correo_remitente) {
    $enviados = 0;

    foreach ($destinatarios as $destinatario) {
        $mail = wp_mail(
            $destinatario['correo'], 
            $asunto, 
            correo_masivo_personalizar_contenido($contenido_html, $destinatario), 
            [
                'From: ' . $nombre_remitente . ' <' . $correo_remitente . '>',
                'Content-Type: text/html; charset=UTF-8'
            ]
        );

        if ($mail) {
            $enviados++;
        } else {
            error_log("Error enviando a {$destinatario['correo']}");
        }
    }

    return "$enviados correos enviados correctamente.";
}

function correo_masivo_personalizar_contenido($contenido_html, $destinatario) {
    foreach ($destinatario as $clave => $valor) {
        $contenido_html = str_replace('{' . $clave . '}', $valor, $contenido_html);
    }
    return $contenido_html;
}
?>
