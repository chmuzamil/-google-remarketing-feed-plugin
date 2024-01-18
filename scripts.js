// scripts.js
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
        url: ajax_object.ajax_url,
        type: 'POST',
        data: {
            action: 'generate_feed',
            _ajax_nonce: ajax_object.generate_feed_nonce,
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
                    
                    downloadLink.href = ajax_object.content_url + '/uploads/' + finalFileName + '.csv'; // Include .csv extension
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

