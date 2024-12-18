<?php
/**
 * Plugin Name: TextFlare Animations
 * Description: A WordPress plugin for animating text groups with configurable options.
 * Version: 1.0.1
 * Author: AJK Software
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'TEXTFLARE_VERSION', '1.0.1' );
define( 'TEXTFLARE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TEXTFLARE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Activation hook to create or update the database table
register_activation_hook( __FILE__, 'textflare_create_or_update_table' );
function textflare_create_or_update_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'textflare_config';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        animation_id VARCHAR(50) NOT NULL,
        duration INT NOT NULL DEFAULT 1000,
        delay INT NOT NULL DEFAULT 1000,
        style_config LONGTEXT NOT NULL,
        text_list LONGTEXT NOT NULL,
        height INT NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    if ($wpdb->last_error) {
        error_log('Table creation or update error: ' . $wpdb->last_error);
    } else {
        error_log('Table created or updated successfully.');
    }
}

// Enqueue scripts and styles for Vue.js in admin
add_action( 'admin_enqueue_scripts', 'textflare_enqueue_admin_scripts' );
function textflare_enqueue_admin_scripts( $hook ) {
    if ( $hook !== 'toplevel_page_textflare-settings' ) {
        return;
    }

    wp_enqueue_script( 'textflare-vue', 'https://cdn.jsdelivr.net/npm/vue@2/dist/vue.js', [], '2.6.14', true );
    wp_enqueue_script( 'textflare-admin-js', TEXTFLARE_PLUGIN_URL . 'admin.js', [ 'textflare-vue' ], TEXTFLARE_VERSION, true );
    wp_enqueue_style( 'textflare-admin-css', TEXTFLARE_PLUGIN_URL . 'admin.css', [], TEXTFLARE_VERSION );

    wp_localize_script( 'textflare-admin-js', 'textflareAjax', [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'template_url' => TEXTFLARE_PLUGIN_URL . 'admin-template.html'
    ] );
}

// Enqueue frontend styles and scripts
add_action( 'wp_enqueue_scripts', 'textflare_enqueue_frontend_assets' );
function textflare_enqueue_frontend_assets() {
    wp_enqueue_style( 'textflare-frontend-css', TEXTFLARE_PLUGIN_URL . 'admin.css', [], TEXTFLARE_VERSION );
}

// Add menu page for plugin settings
add_action( 'admin_menu', 'textflare_register_menu' );
function textflare_register_menu() {
    add_menu_page( 
        'TextFlare Animations', 
        'TextFlare', 
        'manage_options', 
        'textflare-settings', 
        'textflare_render_admin_page', 
        'dashicons-art', 
        80 
    );
}

// Render admin page
function textflare_render_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'textflare_config';
    $configs = $wpdb->get_results( "SELECT * FROM $table_name", ARRAY_A );
    ?>
    <div id="textflare-app" data-configs='<?php echo json_encode( $configs ); ?>'></div>
    <?php
}

// AJAX endpoint for saving configuration
add_action( 'wp_ajax_textflare_save_config', 'textflare_save_config' );
function textflare_save_config() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'textflare_config';
    $data = json_decode( file_get_contents( 'php://input' ), true );

    $animation_id = sanitize_text_field( $data['animationId'] );
    $duration = intval( $data['duration'] );
    $delay = intval( $data['delay'] );
    $style_config = json_encode( $data['styleConfig'] );
    $text_list = json_encode( $data['textList'] );
    $height = intval( $data['height'] );

    $wpdb->insert( $table_name, [
        'animation_id' => $animation_id,
        'duration' => $duration,
        'delay' => $delay,
        'style_config' => $style_config,
        'text_list' => $text_list,
        'height' => $height,
    ] );

    if ($wpdb->last_error) {
        wp_send_json_error( 'Error saving configuration.' );
    } else {
        wp_send_json_success( 'Configuration saved successfully.' );
    }
}

// AJAX endpoint for fetching configurations
add_action( 'wp_ajax_textflare_get_configs', 'textflare_get_configs' );
function textflare_get_configs() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'textflare_config';
    $configs = $wpdb->get_results( "SELECT * FROM $table_name", ARRAY_A );

    if ($configs) {
        foreach ($configs as &$config) {
            $config['styleConfig'] = json_decode($config['style_config'], true);
            $config['textList'] = json_decode($config['text_list'], true);
        }
        wp_send_json_success( [ 'configs' => $configs ] );
    } else {
        wp_send_json_error( 'Error fetching configurations.' );
    }
}

// AJAX endpoint for deleting configuration
add_action( 'wp_ajax_textflare_delete_config', 'textflare_delete_config' );
function textflare_delete_config() {
    global $wpdb;

    $data = json_decode( file_get_contents( 'php://input' ), true );
    $id = intval( $data['id'] );

    $table_name = $wpdb->prefix . 'textflare_config';
    $deleted = $wpdb->delete( $table_name, [ 'id' => $id ], [ '%d' ] );

    if ($deleted) {
        wp_send_json_success( 'Configuration deleted successfully.' );
    } else {
        wp_send_json_error( 'Error deleting configuration.' );
    }
}

// AJAX endpoint for updating configuration
add_action( 'wp_ajax_textflare_update_config', 'textflare_update_config' );
function textflare_update_config() {
    global $wpdb;

    $data = json_decode( file_get_contents( 'php://input' ), true );

    $id = intval( $data['id'] );
    $animation_id = sanitize_text_field( $data['animationId'] );
    $duration = intval( $data['duration'] );
    $delay = intval( $data['delay'] );
    $style_config = json_encode( $data['styleConfig'] );
    $text_list = json_encode( $data['textList'] );
    $height = intval( $data['height'] );

    $table_name = $wpdb->prefix . 'textflare_config';
    $updated = $wpdb->update( $table_name, [
        'animation_id' => $animation_id,
        'duration' => $duration,
        'delay' => $delay,
        'style_config' => $style_config,
        'text_list' => $text_list,
        'height' => $height,
    ], [ 'id' => $id ], [ '%s', '%d', '%d', '%s', '%s', '%d' ], [ '%d' ] );

    if ($updated !== false) {
        wp_send_json_success( 'Configuration updated successfully.' );
    } else {
        wp_send_json_error( 'Error updating configuration.' );
    }
}

// Function to generate HTML for the shortcode
function textflare_render_shortcode( $atts ) {
    global $wpdb;

    $atts = shortcode_atts( [
        'id' => 0,
    ], $atts, 'textflare' );

    $id = intval( $atts['id'] );
    if ( $id <= 0 ) {
        return 'Invalid configuration ID.';
    }

    $table_name = $wpdb->prefix . 'textflare_config';
    $config = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id ), ARRAY_A );

    if ( ! $config ) {
        return 'Configuration not found.';
    }

    $style_config = json_decode( $config['style_config'], true );
    $text_list = json_decode( $config['text_list'], true );

    ob_start();
    ?>
    <div class="textflare-animation" style="color: <?php echo esc_attr( $style_config['textColor'] ); ?>; font-family: <?php echo esc_attr( $style_config['fontFamily'] ); ?>; background-color: <?php echo esc_attr( $style_config['backgroundColor'] ); ?>; text-align: <?php echo esc_attr( $style_config['textAlign'] ?? 'center' ); ?>; height: <?php echo esc_attr( $config['height'] ); ?>px;">
        <div class="textflare-item" style="--<?php echo esc_attr( $config['animation_id'] ); ?>-duration: <?php echo esc_attr( $config['delay'] ); ?>ms; font-size: <?php echo esc_attr( $style_config['fontSize'] ); ?>px;">
            <?php echo esc_html( $text_list[0] ); ?>
        </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const textList = <?php echo json_encode( $text_list ); ?>;
            const duration = <?php echo esc_attr( $config['duration'] ); ?>;
            const delay = <?php echo esc_attr( $config['delay'] ); ?>;
            const animationClass = "<?php echo esc_attr( $config['animation_id'] ); ?>";
            const textElement = document.querySelector(".textflare-item");
            textElement.classList.add(animationClass);
            let index = 0;

            setInterval(() => {
                index = (index + 1) % textList.length;
                textElement.textContent = textList[index];
            }, duration + delay);
        });
    </script>
    <?php
    return ob_get_clean();
}

// Register the shortcode
add_shortcode( 'textflare', 'textflare_render_shortcode' );
