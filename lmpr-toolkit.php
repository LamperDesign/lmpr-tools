<?php
/*
Plugin Name: Functiebeheer
Description: Schakel functionaliteiten in/uit via tabbladen in het admin menu.
Version: 1.3
Author: Lamper Design
*/

// Voeg admin menu toe
add_action('admin_menu', function () {
    add_menu_page(
        'Functiebeheer',
        'Functiebeheer',
        'manage_options',
        'functiebeheer',
        'render_functiebeheer_page'
    );
}, 99);

// Admin styling
add_action('admin_head', function () {
    $screen = get_current_screen();
    if ($screen && $screen->id === 'toplevel_page_functiebeheer') {
        echo '<style>
            .functiebeheer-field {
                padding-bottom: 15px;
                border-bottom: 1px dashed #00000038;
                margin-bottom: 0;
            }
            .functiebeheer-field:last-of-type {
                border-bottom: none;
            }
            .postbox {
                background: #fff;
                border: 1px solid #ccd0d4;
                margin-top: 20px;
            }
            .postbox .hndle {
                padding: 10px;
                margin: 0;
                font-weight: bold;
                background: #f6f7f7;
                border-bottom: 1px solid #ccd0d4;
            }
            .postbox .inside {
                padding: 10px;
            }
            .wrap .widefat {
            width: fit-content;
            min-width: 50%;
            }
            .wrap .widefat th {
            font-weight:500;
    }
        </style>';
    }
});

// Render admin pagina
function render_functiebeheer_page() {
    $has_woocommerce = class_exists('WooCommerce');
    $has_facetwp = defined('FACETWP_VERSION');

    $allowed_tabs = ['algemeen'];
    if ($has_woocommerce) {
        $allowed_tabs[] = 'woocommerce_tweaks';
        $allowed_tabs[] = 'woocommerce_info';
    }
    if ($has_facetwp) {
        $allowed_tabs[] = 'facetwp';
    }
    $allowed_tabs[] = 'links';

    $active_tab = $_GET['tab'] ?? 'algemeen';
    if (!in_array($active_tab, $allowed_tabs)) {
        $active_tab = 'algemeen';
    }
    ?>
    <div class="wrap">
        <h1>Functiebeheer</h1>
        <h2 class="nav-tab-wrapper">
    <?php
    // Mapping van tab keys naar zichtbare namen
    $tab_names = [
        'algemeen' => 'Algemeen',
        'woocommerce_tweaks' => 'WooCommerce tweaks',
        'woocommerce_info' => 'WooCommerce Info',
        'facetwp' => 'FacetWP',
        'links' => 'Nuttige links'
    ];
    ?>
    <?php foreach ($allowed_tabs as $tab): ?>
        <a href="?page=functiebeheer&tab=<?= esc_attr($tab) ?>" class="nav-tab <?= $active_tab === $tab ? 'nav-tab-active' : '' ?>">
            <?= $tab_names[$tab] ?? ucwords(str_replace('_', ' ', $tab)) ?>
        </a>
    <?php endforeach; ?>
</h2>

        <?php if ($active_tab === 'woocommerce_info'): ?>
            <div class="postbox">
                <h2 class="hndle"><span>WooCommerce Informatie</span></h2>
                <div class="inside">
                    <?php functiebeheer_show_woocommerce_info(); ?>
                </div>
            </div>

            <?php elseif ($active_tab === 'facetwp'): ?>
                <div class="postbox">
                    <h2 class="hndle"><span>FacetWP Instellingen</span></h2>
                    <div class="inside">
                        <form method="post" action="options.php">
                            <?php
                            settings_fields('functiebeheer_facetwp');
                            do_settings_sections('functiebeheer_facetwp');
                            submit_button('Instellingen opslaan');
                            ?>
                        </form>
                    </div>
                </div>

                <div class="postbox">
                    <h2 class="hndle"><span>FacetWP Index beheer</span></h2>
                    <div class="inside">
                        <p>Laatst geïndexeerd: 
                            <?= get_option('functiebeheer_facetwp_last_indexed') ? date('Y-m-d H:i:s', get_option('functiebeheer_facetwp_last_indexed')) : 'Nog nooit'; ?>
                        </p>
                        <form method="post">
                            <?php submit_button('FacetWP index opnieuw opbouwen', 'primary', 'functiebeheer_facetwp_index_now'); ?>
                        </form>
                    </div>
                </div>


        <?php elseif ($active_tab === 'links'): ?>
            <div class="postbox">
                <h2 class="hndle"><span>Nuttige links</span></h2>
                <div class="inside">
                    <ul>
                        <li><a href="https://www.businessbloomer.com/woocommerce-visual-hook-guide-single-product-page/" target="_blank">WooCommerce Visual Hook Guide (Business Bloomer)</a></li>
                        <li><a href="https://support.lamper-design.nl" target="_blank">Lamper Design Support Portal</a></li>
                    </ul>
                </div>
            </div>

        <?php else: ?>
            <form method="post" action="options.php">
                <?php
                settings_fields('functiebeheer_' . $active_tab);
                do_settings_sections('functiebeheer_' . $active_tab);
                submit_button();
                ?>
            </form>
        <?php endif; ?>

        

      
    </div>
    <?php
}

// Helper voor checkbox met tooltip
function functiebeheer_checkbox($option_group, $option_name, $label, $tooltip = '') {
    $options = get_option($option_group);
    $value = $options[$option_name] ?? false;
    echo '<div class="functiebeheer-field">';
    echo '<label><input type="checkbox" name="'.$option_group.'['.$option_name.']" value="1"' . checked(1, $value, false) . ' /> '.$label.'</label>';
    if ($tooltip) {
        echo '<p class="description">'.$tooltip.'</p>';
    }
    echo '</div>';
}

// Helper voor tekstveld met tooltip
function functiebeheer_textfield($option_group, $option_name, $placeholder = '', $tooltip = '') {
    $options = get_option($option_group);
    $value = $options[$option_name] ?? '';
    echo '<div class="functiebeheer-field">';
    echo '<input type="text" name="'.$option_group.'['.$option_name.']" value="'.esc_attr($value).'" class="regular-text" placeholder="'.esc_attr($placeholder).'" />';
    if ($tooltip) {
        echo '<p class="description">'.$tooltip.'</p>';
    }
    echo '</div>';
}

function functiebeheer_show_woocommerce_info() {

    // Toon of prijzen inclusief of exclusief BTW worden weergegeven
$prices_include_tax = get_option('woocommerce_prices_include_tax') === 'yes';

echo '<h3>Prijzen weergegeven <span><a href="'.admin_url('admin.php?page=wc-settings&tab=tax').'" target="_blank" style="text-decoration:none;font-size:0.9em;">(bewerk)</a></span></h3>';
echo '<p>Prijzen worden momenteel <strong>' . ($prices_include_tax ? 'inclusief BTW' : 'exclusief BTW') . '</strong> weergegeven in de webshop.</p>';


     // Producten overzicht
     echo '<h3>Producten <span><a href="'.admin_url('edit.php?post_type=product').'" target="_blank" style="text-decoration:none;font-size:0.9em;">(bekijk producten)</a></span></h3>';

        $total_products = wp_count_posts('product')->publish;
        $no_stock_query = new WP_Query([
            'post_type' => 'product',
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => '_stock_status',
                    'value' => 'outofstock'
                ]
            ],
            'fields' => 'ids',
            'nopaging' => true,
        ]);
        $no_stock_total = $no_stock_query->found_posts;

    echo '<table class="widefat"><thead><tr><th>Metric</th><th>Aantal</th></tr></thead><tbody>';
    echo '<tr><td>Aantal producten totaal</td><td>'.esc_html($total_products).'</td></tr>';
    echo '<tr><td>Aantal producten niet op voorraad</td><td>'.esc_html($no_stock_total).'</td></tr>';
    echo '</tbody></table>';

    // Bestellingen overzicht
    // Bereken eerste dag van de maand
    $first_day = date('Y-m-01 00:00:00');
    $now = current_time('mysql');

    // Query: alle orders deze maand ophalen
    $orders_this_month = wc_get_orders([
        'limit' => -1,
        'status' => ['wc-completed', 'wc-cancelled', 'wc-refunded'],
        'date_created' => $first_day . '...' . $now,
        'return' => 'ids',
    ]);

    // Initialiseer tellers
    $status_counts = [
        'completed' => ['count' => 0, 'total' => 0],
        'cancelled' => ['count' => 0, 'total' => 0],
        'refunded' => ['count' => 0, 'total' => 0],
        'total' => ['count' => 0, 'total' => 0],
    ];

    // Tellen per status
    foreach ($orders_this_month as $order_id) {
        $order = wc_get_order($order_id);
        $status = $order->get_status();
        $total = $order->get_total();

        $status_counts['total']['count']++;
        $status_counts['total']['total'] += $total;

        if (isset($status_counts[$status])) {
            $status_counts[$status]['count']++;
            $status_counts[$status]['total'] += $total;
        }
    }

    // Output tabel
    echo '<h3>Bestellingen deze maand</h3>';
    echo '<table class="widefat"><thead><tr><th>Status</th><th>Aantal</th><th>Totaalwaarde</th></tr></thead><tbody>';
    echo '<tr><td>Totaal</td><td>' . esc_html($status_counts['total']['count']) . '</td><td>' . wc_price($status_counts['total']['total']) . '</td></tr>';
    echo '<tr><td>Voltooid</td><td>' . esc_html($status_counts['completed']['count']) . '</td><td>' . wc_price($status_counts['completed']['total']) . '</td></tr>';
    echo '<tr><td>Geannuleerd</td><td>' . esc_html($status_counts['cancelled']['count']) . '</td><td>' . wc_price($status_counts['cancelled']['total']) . '</td></tr>';
    echo '<tr><td>Terugbetaald</td><td>' . esc_html($status_counts['refunded']['count']) . '</td><td>' . wc_price($status_counts['refunded']['total']) . '</td></tr>';
    echo '</tbody></table>';


    echo '<h3>Bestellingen totaal<span> <a href="'.admin_url('edit.php?post_type=shop_order').'" target="_blank" style="text-decoration:none;font-size:0.9em;">(bekijk bestellingen)</a></span></h3>';
    $order_counts = wp_count_posts('shop_order');

    $total_orders = array_sum((array) $order_counts);
    $total_completed = $order_counts->{'wc-completed'} ?? 0;
    $total_cancelled = $order_counts->{'wc-cancelled'} ?? 0;
    $total_refunded = $order_counts->{'wc-refunded'} ?? 0;
    
    echo '<table class="widefat"><thead><tr><th>Status</th><th>Aantal</th></tr></thead><tbody>';
    echo '<tr><td>Totaal</td><td>' . esc_html($total_orders) . '</td></tr>';
    echo '<tr><td>Voltooid</td><td>' . esc_html($total_completed) . '</td></tr>';
    echo '<tr><td>Geannuleerd</td><td>' . esc_html($total_cancelled) . '</td></tr>';
    echo '<tr><td>Terugbetaald</td><td>' . esc_html($total_refunded) . '</td></tr>';
    echo '</tbody></table>';        

      // Zijn algemene voorwaarden ingesteld?
      $terms_page_id = get_option('woocommerce_terms_page_id');
      $terms_enabled = $terms_page_id && $terms_page_id != 0;
  
      echo '<h3>Algemene voorwaarden <span><a href="'.admin_url('admin.php?page=wc-settings&tab=checkout').'" target="_blank" style="text-decoration:none;font-size:0.9em;">(bewerk)</a></span></h3>';
      if ($terms_enabled) {
          $terms_page_link = get_edit_post_link($terms_page_id);
          echo '<p>Algemene voorwaarden zijn ingesteld: <a href="'.esc_url($terms_page_link).'">'.get_the_title($terms_page_id).'</a></p>';
      } else {
          echo '<p>Algemene voorwaarden zijn <strong>niet ingesteld</strong>.</p>';
      }

    // Overzicht BTW tarieven  
    $tax_classes = WC_Tax::get_tax_classes();
    array_unshift($tax_classes, 'standard');

    echo '<h3>BTW tarieven <span><a href="'.admin_url('admin.php?page=wc-settings&tab=tax').'" target="_blank" style="text-decoration:none;font-size:0.9em;">(bewerk)</a></span></h3>';
    echo '<table class="widefat"><thead><tr><th>BTW klasse</th><th>Land</th><th>Stad</th><th>Postcode</th><th>Percentage</th></tr></thead><tbody>';

    $has_rates = false;

    foreach ($tax_classes as $tax_class) {
        $rates = WC_Tax::get_rates_for_tax_class($tax_class);
        foreach ($rates as $rate) {
            $has_rates = true;
            echo '<tr>';
            echo '<td>'.esc_html($tax_class ?: 'standard').'</td>';
            echo '<td>'.esc_html($rate->tax_rate_country === '*' || $rate->tax_rate_country === '' ? 'Overige' : $rate->tax_rate_country).'</td>';
            echo '<td>'.esc_html($rate->tax_rate_city ?? '').'</td>';
            echo '<td>'.esc_html($rate->tax_rate_postcode ?? '').'</td>';
            echo '<td>'.($rate->tax_rate == '0.0000' ? 'Geen BTW' : esc_html(rtrim($rate->tax_rate, '0.') . '%')).'</td>';
            echo '</tr>';
        }
    }

    if (!$has_rates) {
        echo '<tr><td colspan="5">Geen BTW tarieven ingesteld.</td></tr>';
    }

    echo '</tbody></table>';

    echo '<h3>Verzendzones en methodes <span><a href="'.admin_url('admin.php?page=wc-settings&tab=shipping').'" target="_blank" style="text-decoration:none;font-size:0.9em;">(bewerk)</a></span></h3>';
    echo '<table class="widefat"><thead><tr><th>Zone naam</th><th>Landen</th><th>Methode</th><th>Naam</th><th>Kosten / Voorwaarden</th></tr></thead><tbody>';
    
    $zones = WC_Shipping_Zones::get_zones();
    foreach ($zones as $zone_data) {
        $zone = new WC_Shipping_Zone($zone_data['zone_id']);
        $zone_name = $zone->get_zone_name();
        $zone_locations = $zone->get_zone_locations();
        $countries = array_map(fn($loc) => $loc->code, $zone_locations);
        $countries_str = !empty($countries) ? implode(', ', $countries) : 'Overige';
    
        $methods = $zone->get_shipping_methods();
        foreach ($methods as $method) {
            $method_title = $method->get_method_title();
            $instance_id = $method->get_instance_id();
            $option_name = 'woocommerce_' . $method->id . '_' . $instance_id . '_settings';
            $settings = get_option($option_name);
    
            $method_name = isset($settings['title']) ? $settings['title'] : '-';
    
            $cost_display = '-';
    
            if ($method->id === 'flat_rate' && isset($settings['cost'])) {
                $cost = floatval($settings['cost']);
                $cost_display = $cost <= 0 ? 'Gratis' : wc_price($cost);
            }
            elseif ($method->id === 'free_shipping') {
                $condition = $settings['requires'] ?? 'n/a';
                if ($condition === 'min_amount') {
                    $min_amount = $settings['min_amount'] ?? '';
                    $cost_display = 'Gratis verzending vanaf €' . number_format((float)$min_amount, 2, ',', '.');
                } elseif ($condition === 'coupon') {
                    $cost_display = 'Gratis verzending met kortingscoupon';
                } elseif ($condition === 'either') {
                    $min_amount = $settings['min_amount'] ?? '';
                    $cost_display = 'Gratis verzending vanaf €' . number_format((float)$min_amount, 2, ',', '.') . ' of kortingscoupon';
                } else {
                    $cost_display = 'Gratis verzending';
                }
            } else {
                $cost_display = 'n.v.t.';
            }
    
            echo '<tr>';
            echo '<td>'.esc_html($zone_name).'</td>';
            echo '<td>'.esc_html($countries_str).'</td>';
            echo '<td>'.esc_html($method_title).'</td>';
            echo '<td>'.esc_html($method_name).'</td>';
            echo '<td>'.wp_kses_post($cost_display).'</td>';
            echo '</tr>';
        }
    }
    
    // Rest van de wereld zone
    $default_zone = new WC_Shipping_Zone(0);
    $methods = $default_zone->get_shipping_methods();
    foreach ($methods as $method) {
        $method_title = $method->get_method_title();
        $instance_id = $method->get_instance_id();
        $option_name = 'woocommerce_' . $method->id . '_' . $instance_id . '_settings';
        $settings = get_option($option_name);
    
        $method_name = isset($settings['title']) ? $settings['title'] : '-';
    
        $cost_display = '-';
    
        if ($method->id === 'flat_rate' && isset($settings['cost'])) {
            $cost = floatval($settings['cost']);
            $cost_display = $cost <= 0 ? 'Gratis' : wc_price($cost);
        }
        elseif ($method->id === 'free_shipping') {
            $condition = $settings['requires'] ?? 'n/a';
            if ($condition === 'min_amount') {
                $min_amount = $settings['min_amount'] ?? '';
                $cost_display = 'Gratis verzending vanaf €' . number_format((float)$min_amount, 2, ',', '.');
            } elseif ($condition === 'coupon') {
                $cost_display = 'Gratis verzending met kortingscoupon';
            } elseif ($condition === 'either') {
                $min_amount = $settings['min_amount'] ?? '';
                $cost_display = 'Gratis verzending vanaf €' . number_format((float)$min_amount, 2, ',', '.') . ' of kortingscoupon';
            } else {
                $cost_display = 'Gratis verzending';
            }
        } else {
            $cost_display = 'n.v.t.';
        }
    
        echo '<tr>';
        echo '<td>Overige</td>';
        echo '<td>Alle landen</td>';
        echo '<td>'.esc_html($method_title).'</td>';
        echo '<td>'.esc_html($method_name).'</td>';
        echo '<td>'.wp_kses_post($cost_display).'</td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
    

    // Betaalmethodes
    echo '<h3>Betaalmethodes <span><a href="'.admin_url('admin.php?page=wc-settings&tab=checkout').'" target="_blank" style="text-decoration:none;font-size:0.9em;">(bewerk)</a></span></h3>';
    $gateways = WC()->payment_gateways->payment_gateways();

    if (!empty($gateways)) {
        echo '<table class="widefat"><thead><tr><th>Titel</th><th>Status</th></tr></thead><tbody>';
        foreach ($gateways as $gateway) {
            echo '<tr>';
            echo '<td>'.esc_html($gateway->get_title()).'</td>';
            echo '<td>'.esc_html($gateway->enabled === 'yes' ? 'Actief' : 'Uitgeschakeld').'</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>Geen betaalmethodes gevonden.</p>';
    }

// Coupons
    echo '<h3>Actieve kortingscodes <span><a href="'.admin_url('edit.php?post_type=shop_coupon').'" target="_blank" style="text-decoration:none;font-size:0.9em;">(bewerk)</a></span></h3>';
    $coupons = get_posts([
        'post_type' => 'shop_coupon',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
    ]);
    
    if (!empty($coupons)) {
        echo '<table class="widefat"><thead><tr><th>Code</th><th>Type</th><th>Waarde</th><th>Min. bedrag</th><th>Vervaldatum</th></tr></thead><tbody>';
        foreach ($coupons as $post) {
            $coupon = new WC_Coupon($post->ID);
            $discount_type = $coupon->get_discount_type();
            $amount = $coupon->get_amount();
            $min_amount = $coupon->get_minimum_amount();
            $expiry_date = $coupon->get_date_expires();
        
            echo '<tr>';
            echo '<td>'.esc_html($coupon->get_code()).'</td>';
            echo '<td>'.esc_html(ucfirst(str_replace('_', ' ', $discount_type))).'</td>';
            echo '<td>'.esc_html(wc_price($amount)).'</td>';
            echo '<td>'.esc_html(!empty($min_amount) ? wc_price($min_amount) : '-').'</td>';
            echo '<td>'.esc_html($expiry_date ? $expiry_date->date_i18n('Y-m-d') : '-').'</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    } else {
        echo '<p>Geen actieve kortingscodes gevonden.</p>';
    }

    // E-mail instellingen WooCommerce
    echo '<h3>E-mail afzenderinstellingen <span><a href="'.admin_url('admin.php?page=wc-settings&tab=email').'" target="_blank" style="text-decoration:none;font-size:0.9em;">(bewerk)</a></span></h3>';

        $from_name = get_option('woocommerce_email_from_name', get_bloginfo('name'));
        $from_address = get_option('woocommerce_email_from_address', get_option('admin_email'));

        echo '<table class="widefat"><thead><tr><th>Afzender naam</th><th>Afzender e-mailadres</th></tr></thead><tbody>';
        echo '<tr>';
        echo '<td>'.esc_html($from_name).'</td>';
        echo '<td>'.esc_html($from_address).'</td>';
        echo '</tr>';
        echo '</tbody></table>';

    // E-mails verzonden naar
    echo '<h3>Bestelnotificatie ontvangers <span><a href="'.admin_url('admin.php?page=wc-settings&tab=email').'" target="_blank" style="text-decoration:none;font-size:0.9em;">(bewerk)</a></span></h3>';

    $new_order_email = get_option('woocommerce_new_order_recipient', get_option('admin_email'));
    $cancelled_order_email = get_option('woocommerce_cancelled_order_recipient', get_option('admin_email'));
    $failed_order_email = get_option('woocommerce_failed_order_recipient', get_option('admin_email'));

    echo '<table class="widefat"><thead><tr><th>Type mail</th><th>Wordt verzonden naar</th></tr></thead><tbody>';
    echo '<tr><td>Nieuwe bestelling</td><td>'.esc_html($new_order_email).'</td></tr>';
    echo '<tr><td>Geannuleerde bestelling</td><td>'.esc_html($cancelled_order_email).'</td></tr>';
    echo '<tr><td>Mislukte bestelling</td><td>'.esc_html($failed_order_email).'</td></tr>';
    echo '</tbody></table>';

}

// Registreer instellingen en velden
add_action('admin_init', function () {
    if (isset($_POST['functiebeheer_facetwp_index_now'])) {
        if (defined('FACETWP_VERSION') && class_exists('FacetWP')) {
            FWP()->indexer->index();
            update_option('functiebeheer_facetwp_last_indexed', current_time('timestamp'));
            add_action('admin_notices', function () {
                echo '<div class="notice notice-success is-dismissible"><p>FacetWP index is opnieuw opgebouwd.</p></div>';
            });
        } else {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error is-dismissible"><p>FacetWP is niet actief of niet gevonden.</p></div>';
            });
        }
    }
});


add_action('admin_init', function () {
    // ALGEMEEN
    register_setting('functiebeheer_algemeen', 'functiebeheer_algemeen');
    add_settings_section('algemeen_section', '', null, 'functiebeheer_algemeen');

    add_settings_field('algemeen_swiper_aan', 'Swiper CDN laden', fn() =>
        functiebeheer_checkbox('functiebeheer_algemeen', 'algemeen_swiper_aan', 'Swiper CSS & JS via CDN laden', 'Laadt de Swiper bestanden vanaf de officiële CDN. Nodig voor sliders.')
    , 'functiebeheer_algemeen', 'algemeen_section');

    add_settings_field('algemeen_logo_alt_actief', 'Logo alt tekst aanpassen', fn() =>
        functiebeheer_checkbox('functiebeheer_algemeen', 'algemeen_logo_alt_actief', 'Activeer aangepaste alt-tekst voor site logo', 'Als ingeschakeld, wordt de alt-tekst van het logo vervangen door je eigen tekst.')
    , 'functiebeheer_algemeen', 'algemeen_section');

    add_settings_field('algemeen_logo_alt_tekst', 'Logo alt tekst', function () {
        $options = get_option('functiebeheer_algemeen');
        $is_active = $options['algemeen_logo_alt_actief'] ?? false;
        if ($is_active) {
            functiebeheer_textfield('functiebeheer_algemeen', 'algemeen_logo_alt_tekst', 'Bijv: Mijn Bedrijfsnaam logo', 'De alt-tekst die getoond wordt bij het logo.');
        } else {
            echo '<em>Activeer eerst "Logo alt tekst aanpassen" om dit veld te bewerken.</em>';
        }
    }, 'functiebeheer_algemeen', 'algemeen_section');

    add_settings_field('algemeen_logo_offcanvas', 'Logo in Offcanvas panel', fn() =>
        functiebeheer_checkbox('functiebeheer_algemeen', 'algemeen_logo_offcanvas', 'Voeg site logo toe aan Offcanvas panel', 'Toont het site logo ook in het offcanvas menu (bij GeneratePress).')
    , 'functiebeheer_algemeen', 'algemeen_section');

    add_settings_field('algemeen_gp_nav_shortcode', 'GeneratePress menu shortcode', fn() =>
    functiebeheer_checkbox(
        'functiebeheer_algemeen',
        'algemeen_gp_nav_shortcode',
        'Activeer [gp_nav] shortcode',
        'Voegt de shortcode [gp_nav] toe waarmee je het GeneratePress menu in content of widgets kunt tonen.'
    )
    , 'functiebeheer_algemeen', 'algemeen_section');


    // WOOCOMMERCE TWEAKS
    if (class_exists('WooCommerce')) {
        register_setting('functiebeheer_woocommerce_tweaks', 'functiebeheer_woocommerce_tweaks');
        add_settings_section('woocommerce_tweaks_section', '', null, 'functiebeheer_woocommerce_tweaks');

        add_settings_field('woocommerce_verberg_nullen', 'Decimalen productprijzen', fn() =>
            functiebeheer_checkbox('functiebeheer_woocommerce_tweaks', 'woocommerce_verberg_nullen', 'Verwijder ,00 bij productprijzen', 'Verwijderd decimalen, bijv. €10,00 wordt €10.')
        , 'functiebeheer_woocommerce_tweaks', 'woocommerce_tweaks_section');

        add_settings_field('woocommerce_disable_password_strength', 'Wachtwoordsterkte', fn() =>
            functiebeheer_checkbox('functiebeheer_woocommerce_tweaks', 'woocommerce_disable_password_strength', 'Verwijder standaard wachtwoordsterkte', 'Schakelt de WooCommerce wachtwoordsterkte meter uit op de checkout.')
        , 'functiebeheer_woocommerce_tweaks', 'woocommerce_tweaks_section');

        add_settings_field('woocommerce_disable_wc_sorting_pagination', 'Paginering & sorting', fn() =>
            functiebeheer_checkbox('functiebeheer_woocommerce_tweaks', 'woocommerce_disable_wc_sorting_pagination', 'Schakel WooCommerce paginering & sortering uit', 'Verwijdert de standaard sorteeropties en paginatie uit de shop.')
        , 'functiebeheer_woocommerce_tweaks', 'woocommerce_tweaks_section');

        add_settings_field('woocommerce_custom_translations', 'Custom WooCommerce vertalingen (JSON)', function () {
            $options = get_option('functiebeheer_woocommerce_tweaks');
            $value = $options['woocommerce_custom_translations'] ?? '';
            echo '<textarea name="functiebeheer_woocommerce_tweaks[woocommerce_custom_translations]" rows="10" cols="70" placeholder=\'{"Op voorraad": "Op voorraad, direct leverbaar"}\'>' . esc_textarea($value) . '</textarea>';
            echo '<p class="description">Voer een JSON-array in met vertalingen. Sleutels zijn originele teksten, waarden zijn de vervangingen.</p>';
        }, 'functiebeheer_woocommerce_tweaks', 'woocommerce_tweaks_section');
    }

    // FACETWP
    if (defined('FACETWP_VERSION')) {
        register_setting('functiebeheer_facetwp', 'functiebeheer_facetwp');
        add_settings_section('facetwp_section', '', null, 'functiebeheer_facetwp');

        add_settings_field('facetwp_add_labels', 'FacetWP labels', fn() =>
            functiebeheer_checkbox('functiebeheer_facetwp', 'facetwp_add_labels', 'Toon labels boven FacetWP facetten', 'Voegt automatisch een h3 label boven elk facet toe.')
            ,  'functiebeheer_facetwp', 'facetwp_section');
    }
});

function init_algemeen() {
    $algemeen = get_option('functiebeheer_algemeen');

    $hooks = [
        'algemeen_swiper_aan' => function () {
            add_action('wp_enqueue_scripts', function () {
                wp_enqueue_style('swiper-style', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css');
                wp_enqueue_script('swiper-script', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', [], '', true);
            });
        },
        'algemeen_logo_alt_actief' => function () use ($algemeen) {
            add_filter('generate_logo_title', function () use ($algemeen) {
                return !empty($algemeen['algemeen_logo_alt_tekst']) ? $algemeen['algemeen_logo_alt_tekst'] : 'Standaard alt-tekst';
            });
        },
        'algemeen_logo_offcanvas' => function () {
            add_action('generate_inside_slideout_navigation', 'generate_construct_logo', -10);
        },
        'algemeen_gp_nav_shortcode' => function () {
            add_shortcode('gp_nav', function ($atts) {
                ob_start();
                if (function_exists('generate_navigation_position')) {
                    generate_navigation_position();
                } else {
                    echo 'GeneratePress navigatie functie niet gevonden.';
                }
                return ob_get_clean();
            });
        }
    ];

    foreach ($hooks as $key => $callback) {
        if (!empty($algemeen[$key])) {
            $callback();
        }
    }
}

// Woo tweaks hier
function init_woocommerce() {
    if (!class_exists('WooCommerce')) return;

    $woocommerce = get_option('functiebeheer_woocommerce_tweaks');

    $hooks = [
        'woocommerce_verberg_nullen' => function () {
            add_filter('woocommerce_price_trim_zeros', fn() => true, 10, 1);
        },
        'woocommerce_disable_password_strength' => function () {
            add_action('wp_enqueue_scripts', function () {
                wp_dequeue_script('wc-password-strength-meter');
            }, 100);
        },
        'woocommerce_disable_wc_sorting_pagination' => function () {
            remove_action('woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30);
            remove_action('woocommerce_after_shop_loop', 'woocommerce_pagination', 10);
        },
        'woocommerce_custom_translations' => function () use ($woocommerce) {
            $json = json_decode($woocommerce['woocommerce_custom_translations'] ?? '', true);
            if (is_array($json)) {
                add_filter('gettext', function ($translated_text, $text, $domain) use ($json) {
                    return $json[$translated_text] ?? $translated_text;
                }, 20, 3);
            }
        }
    ];

    foreach ($hooks as $key => $callback) {
        if (!empty($woocommerce[$key])) {
            $callback();
        }
    }
}

// FacetWP tweaks
function init_facetwp() {
    if (!defined('FACETWP_VERSION')) return;

    $facetwp = get_option('functiebeheer_facetwp');

    $hooks = [

        'facetwp_add_labels' => function () {
            add_action('wp_footer', function () {
                ?>
                <script>
                  (function($) {
                    $(document).on('facetwp-loaded', function() {
                      $('.facetwp-facet').each(function() {
                        var facet = $(this);
                        var facet_name = facet.attr('data-name');
                        var facet_type = facet.attr('data-type');
                        var facet_label = FWP.settings.labels[facet_name];
                        if (facet_type !== 'pager' && facet_type !== 'sort') {
                          if (('undefined' === typeof FWP.settings.num_choices[facet_name] ||
                            ('undefined' !== typeof FWP.settings.num_choices[facet_name] && FWP.settings.num_choices[facet_name] > 0)) && $('.facet-label[data-for="' + facet_name + '"]').length < 1) {
                            facet.before('<h3 class="facet-label" data-for="' + facet_name + '">' + facet_label + '</h3>');
                          } else if ('undefined' !== typeof FWP.settings.num_choices[facet_name] && !FWP.settings.num_choices[facet_name] > 0) {
                            $('.facet-label[data-for="' + facet_name + '"]').remove();
                          }
                        }
                      });
                    });
                  })(jQuery);
                </script>
                <?php
            }, 100);
        }
    ];

    foreach ($hooks as $key => $callback) {
        if (!empty($facetwp[$key])) {
            $callback();
        }
    }
}




add_action('init', function () {
    init_algemeen();
    init_woocommerce();
    init_facetwp();
});


// INIT functies blijven hetzelfde (init_algemeen, init_woocommerce, init_facetwp)
