<?php
/*
Plugin Name: Custom Feed Generaotr for google
Plugin URI: https://github.com/chmuzamil/google-remarketing-feed-plugin
Description: This plugin generates a CSV feed for Google Remarketing.
Version: 1.0
Author: Muzamil Chaudhery
Author URI: https://chaudhery.com/

*/

require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
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

// Enqueue JavaScript for AJAX handling
add_action('admin_footer', 'my_admin_footer_script');
function my_admin_footer_script() {
    ?>
    <script>
		function toggleCategoryCheckboxes() {
                var categoryCheckboxes = document.getElementById('category-checkboxes');
                var allProductsRadio = document.querySelector('input[name="product_display"][value="all"]');
                categoryCheckboxes.style.display = allProductsRadio.checked ? 'none' : 'block';
            }

            document.querySelectorAll('input[name="product_display"]').forEach(function (radio) {
                radio.addEventListener('change', toggleCategoryCheckboxes);
            });

            // Call the function on page load
            toggleCategoryCheckboxes();
   document.getElementById('generate-feed-button').addEventListener('click', function() {
	   var selectedCategories = Array.from(document.querySelectorAll('input[name="product_category[]"]:checked')).map(checkbox => checkbox.value);
        var outputFilename = document.getElementById('output-filename').value.trim();
    document.getElementById('progress-bar').style.width = '0%';
    document.getElementById('progress-bar-container').style.display = 'block';
    document.getElementById('success-message').style.display = 'none';
	document.getElementById('csv-download').style.display = 'none';
            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'generate_feed',
                    _ajax_nonce: '<?php echo wp_create_nonce('generate_feed_nonce'); ?>',
					category: selectedCategories,
					output_filename: outputFilename,
                },
                success: function(response) {
					var productCount = response.data.productCount;
					var generatedFileName = response.data.generatedFileName;
    // Simulate progress
    var progress = 0;
    var interval = setInterval(function() {
        progress += 20;
        document.getElementById('progress-bar').style.width = progress + '%';
        if (progress >= 100) {
            clearInterval(interval);
            document.getElementById('success-message').innerHTML = 'Feeds generated successfully for ' + productCount + ' products!';
            document.getElementById('success-message').style.display = 'block';
			// Display CSV download link and location URL
                            var downloadLink = document.getElementById('csv-download-link');
 							var csvLocationUrl = document.getElementById('csv-location-url');
    						var userProvidedFileName = document.getElementById('output-filename').value.trim();
  							var finalFileName = userProvidedFileName ? userProvidedFileName : generatedFileName;
						    downloadLink.href = '<?php echo content_url('/uploads/'); ?>' + finalFileName + '.csv'; // Include .csv extension
    						csvLocationUrl.textContent = downloadLink.href;
                            document.getElementById('csv-download').style.display = 'block';

                            // Copy URL functionality
                            document.getElementById('copy-csv-location').addEventListener('click', function () {
                                var copyText = csvLocationUrl;
                                var textArea = document.createElement("textarea");
                                textArea.value = copyText.textContent;
                                document.body.appendChild(textArea);
                                textArea.select();
                                document.execCommand('copy');
                                document.body.removeChild(textArea);
                                alert("URL copied to clipboard!");
                            });
                        }
                    }, 500);
                },
                error: function(jqXHR, textStatus, errorThrown) {
    console.error(textStatus + ': ' + errorThrown);
    document.getElementById('success-message').innerHTML = 'Error generating feeds. Please try again.';
    document.getElementById('success-message').style.display = 'block';
},
				});
        });
    </script>
<style>
        #progress-bar-container {
			border: 1px solid #135e96;
  			border-radius: 20px;
    		padding: 4px;
			margin:20px 40px;
            text-align: center;
			width: 400px;
        }
	#success-message{
		margin: 0 40px;
  		text-align: center;
  		width: 400px;
		font-size:20px;
		line-height:1;
		font-weight:500;
		margin:0 40px;
	}
        #progress-bar {
            width: 0%;
            height: 20px;
            background-color: #135e96;
			border-radius: 20px
        }
    </style>
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

