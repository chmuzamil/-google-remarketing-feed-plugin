<?php
/*
Plugin Name: Custom Feed Generaotr for google
Plugin URI: https://github.com/chmuzamil/google-remarketing-feed-plugin
Description: This plugin generates a CSV feed for Google Remarketing.
Version: 1.1
Author: Muzamil Chaudhery
Author URI: https://chaudhery.com/

*/

add_action('admin_enqueue_scripts', 'enqueue_custom_styles');

function enqueue_custom_styles() {
    wp_enqueue_style('custom-styles', plugin_dir_url(__FILE__) . 'styles.css');
}
require_once plugin_dir_path(__FILE__) . 'plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/chmuzamil/google-remarketing-feed-plugin/',
    __FILE__,
    'google-remarketing-feed-plugin'
);

// Create the settings page and trigger button
function my_google_remarketing_settings_page() {
    add_options_page('Google Remarketing Feed', 'Google Remarketing Feed', 'manage_options', 'my-settings-page', 'my_google_remarketing_settings_page_html');
}
add_action('admin_menu', 'my_google_remarketing_settings_page');

function my_google_remarketing_settings_page_html() {
	$product_categories = get_terms('product_cat', array('hide_empty' => true));
	$output_file_name = get_option('output_file_name', 'google_remarketing_feeds.csv'); // Default value if not set

    ?>
	<div class="wrap">
        <h1>Google Remarketing Feed Settings</h1>

        <h3>Select Products:</h3>
        <label>
            <input type="radio" name="product_display" value="all" checked>
            All Products
        </label>
        <label>
            <input type="radio" name="product_display" value="by_category">
            Products by Categories
        </label>

        <div id="category-checkboxes">
            <h4>Select Product Categories:</h4>
            <?php
            foreach ($product_categories as $category) {
                echo '<input type="checkbox" name="product_category[]" value="' . esc_attr($category->slug) . '">' . esc_html($category->name) . '<br>';
            }
            ?>
        </div><div id="filename">
		<p>Enter the file name for generated CSV file</p>
		<label for="output-filename">Output File Name :</label>
		<input type="text" id="output-filename" name="output_filename" value="google_feeds"></div>
    <button type="button" style="margin:20px 40px;" class="button button-primary" id="generate-feed-button">Generate Feeds</button>

	<div id="progress-bar-container" style="display: none;">
        <div id="progress-bar"></div>
    </div>
	<div id="success-message" style="display: none;"></div>
		<div id="csv-download" style="display: none;">
            <h3>Download CSV:</h3>
            <p><a id="csv-download-link" href="#" download>Download CSV File</a></p>
			<h3>CSV Location URL:</h3>
            <p><span id="csv-location-url"></span>
            <button type="button" id="copy-csv-location" class="button">Copy URL</button>
            </p>
        </div>
		</div>
    <?php
}

<?php
}

// Function to generate the CSV feed
	function generate_google_remarketing_feed($categories = array(), $outputFilename = 'google_remarketing_feeds') {
    $products_per_page = 200;
    $paged = 1;
    $all_products = array();

    do {
        $args = array(
            'status' => 'publish',
            'limit' => $products_per_page,
            'page' => $paged,
        );

        // Add category filter if a category is selected
        if (!empty($categories)) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'product_cat',
                'field' => 'slug',
                'terms' => $categories,
            ),
        );
    }
        $products = wc_get_products($args);

        if (!empty($products)) {
            $all_products = array_merge($all_products, $products);
            $paged++;
        } else {
            break; // No more products to retrieve
        }

    } while (true);


    $csv_header = array(
        'ID',
		'ID2',
        'Item title',
        'Item description',
        'Final URL',
        'Image URL',
        'Price',
        'Sale price',
        'Item category',
        'status',
    );

    $csv_data = array();
	$sequential_id = 1;
    foreach ($all_products as $product) {
        $product_data = array(
			$sequential_id++,
            $product->get_id(),
            $product->get_name(),
			$product->get_name(),
          //  $product->get_description(),
            $product->get_permalink(),
            $product->get_image_id() ? wp_get_attachment_url($product->get_image_id()) : '',
            $product->get_regular_price(),
            $product->get_sale_price() ? $product->get_sale_price() : '',
            wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'names'))[0],
            $product->get_stock_status(),
        );

        $csv_data[] = $product_data;
    }

    $filename = sanitize_file_name(trim($outputFilename)) . '.csv';
    $file_path = WP_CONTENT_DIR . '/uploads/' . $filename;

    $fp = fopen($file_path, 'w');
    fputcsv($fp, $csv_header);
    foreach ($csv_data as $row) {
        fputcsv($fp, $row);
    }
    fclose($fp);

    return count($csv_data);
}

// Handle AJAX requests to generate the feed
add_action('wp_ajax_generate_feed', 'generate_feed_ajax');
function generate_feed_ajax() {
    check_ajax_referer('generate_feed_nonce', '_ajax_nonce'); // Security check
    $categories = isset($_POST['category']) ? array_map('sanitize_text_field', $_POST['category']) : array();
    $outputFilename = isset($_POST['output_filename']) ? sanitize_text_field($_POST['output_filename']) : 'google_feeds';
    $productCount = generate_google_remarketing_feed($categories, $outputFilename);
    wp_send_json_success(array('productCount' => $productCount));
    wp_die();
}

