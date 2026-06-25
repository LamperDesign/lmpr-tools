<?php
/**
 * GeneratePress child theme functions and definitions.
 *
 * Add your custom PHP in this file.
 * Only edit this file if you have direct access to it on your server (to fix errors if they happen).
 */

// load stylesheet in backend
add_action( 'enqueue_block_editor_assets', function() {
    wp_enqueue_style( 'my-block-editor-styles', get_stylesheet_directory_uri() . "/style.css", false,'1.0', 'all' );
} );

add_action( 'admin_notices', function () {
    if ( ! current_user_can( 'manage_options' ) ) return;

    // Stap 1: staat de plugin in $transient->checked?
    $transient = get_site_transient( 'update_plugins' );
    $slug      = 'lmpr-tools/lmpr-tools.php';

    echo '<div class="notice notice-info"><p>';
    echo '<strong>LMPR debug:</strong><br>';
    echo 'In checked: ' . ( isset( $transient->checked[ $slug ] ) ? $transient->checked[ $slug ] : 'NIET GEVONDEN' ) . '<br>';

    // Stap 2: wat geeft de GitHub API terug?
    $response = wp_remote_get(
        'https://api.github.com/repos/lamperdesign/lmpr-tools/releases/latest',
        [ 'headers' => [ 'User-Agent' => 'WordPress' ] ]
    );
    $data = json_decode( wp_remote_retrieve_body( $response ) );
    echo 'GitHub tag: ' . ( $data->tag_name ?? 'NIET GEVONDEN' ) . '<br>';

    // Stap 3: zit er al een response in de transient?
    echo 'In response: ' . ( isset( $transient->response[ $slug ] ) ? $transient->response[ $slug ]->new_version : 'geen' );
    echo '</p></div>';
} );

add_action( 'init', function () {
    delete_transient( 'lmpr_tools_github_update' );
    delete_site_transient( 'update_plugins' );
} );
