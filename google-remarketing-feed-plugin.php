<?php
/*
Plugin Name: Custom Feed Generaotr for google
Description: This plugin generates a CSV feed for Google Remarketing.
Version: 1.0
Author: Muzamil Chaudhery
Author URI: https://chaudhery.com/
*/

// Create the settings page and trigger button
      function my_google_remarketing_settings_page() {
      add_options_page('Google Remarketing Feed', 'Google Remarketing Feed', 'manage_options', 'my-settings-page', 'my_google_remarketing_settings_page_html');
      }
      add_action('admin_menu', 'my_google_remarketing_settings_page');

      function my_google_remarketing_settings_page_html() {
      ?>
    	<div class="wrap">
      <h1>Google Remarketing Feed Settings</h1>
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
    document.getElementById('generate-feed-button').addEventListener('click', function() {
    document.getElementById('progress-bar').style.width = '0%';
    document.getElementById('progress-bar-container').style.display = 'block';
    document.getElementById('success-message').style.display = 'none';
  	document.getElementById('csv-download').style.display = 'none';
            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'generate_feed',
                    _ajax_nonce: '<?php echo wp_create_nonce('generate_feed_nonce'); ?>'
                },
                success: function(response) {
					var productCount = response.data.productCount;
                  
// Simulate progress for progressbar
            var progress = 0;
            var interval = setInterval(function() {
            progress += 10;
            document.getElementById('progress-bar').style.width = progress + '%';
            if (progress >= 100) {
            clearInterval(interval);
            document.getElementById('success-message').innerHTML = 'Feeds generated successfully for ' + productCount + ' products!';
            document.getElementById('success-message').style.display = 'block';
		
// Display CSV download link and location URL
            var downloadLink = document.getElementById('csv-download-link');
            var csvLocationUrl = document.getElementById('csv-location-url');
            downloadLink.href = '<?php echo content_url('/uploads/google_remarketing_feeds.csv'); ?>';
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

        <!-- CSS Styling for the plugin seeting page  //-->
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

// Function to generate the CSV product feeds in batches currently 100 (Defined Below) products at a time.
function generate_google_remarketing_feed() {
    $products_per_page = 100; // Define here how many products you want to add to csv file in 1 operation.
    $paged = 1;
    $all_products = array();

    do {
        $products = wc_get_products(array(
            'status' => 'publish',           // Only publish products will be added to csv file
            'limit' => $products_per_page,
            'page' => $paged,
        ));

        if (!empty($products)) {
            $all_products = array_merge($all_products, $products);
            $paged++;
        } else {
            break; // No more products to retrieve
        }

    } while (true);

  // csv_header includes the coloumns Headers of the CSV file you can add or remove the Coloumns in the CSV by editing the csv_header array
  
  $csv_header = array(
        'ID',
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

    foreach ($all_products as $product) {
        $product_data = array(
            $product->get_id(),               // Product ID
            $product->get_name(),            // Product Title
			      $product->get_description(),     // Product Description
            $product->get_permalink(),       // Product URL / Link
            $product->get_image_id() ? wp_get_attachment_url($product->get_image_id()) : '',   // product featured Image
            $product->get_regular_price(),   // Product Regular Price
            $product->get_sale_price() ? $product->get_sale_price() : '',   // Product Sale price
            wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'names'))[0],   // Product Category
            $product->get_stock_status(), // Product Avaialibilty ie Instock , out of stock 
        );

        $csv_data[] = $product_data;
    }

    $filename = 'google_remarketing_feeds.csv'; // CSV File name to be 
    $file_path = WP_CONTENT_DIR . '/uploads/' . $filename;    // File Location

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
    $productCount = generate_google_remarketing_feed();
    wp_send_json_success(array('productCount' => $productCount));
    wp_die();
}
