jQuery(document).ready(function($) {
    var activeCarId = null, activeCarPrice = 0, selectedServices = {}, activeCarTitle = '';
    var currentSearchPage = 1, totalSearchPages = 1;
    var currentListingPage = 1, listingLoading = false;

    // Load extra services
    function loadExtraServices() {
        $.ajax({
            url: dcbm_ajax.ajax_url,
            type: 'POST',
            data: { action: 'dcbm_get_extra_services', nonce: dcbm_ajax.nonce },
            success: function(r) {
                if (r.success) renderServices(r.data);
            }
        });
    }

    function renderServices(services) {
        var html = '';
        $.each(services, function(i, s) {
            html += `<div class="dcbm-service-item">
                        <label><input type="checkbox" class="service-checkbox" data-id="${s.id}" data-price="${s.price}" data-model="${s.price_model}"> <span>${s.icon}</span> ${s.name}</label>
                        <span>${s.price_type === 'paid' ? 'PKR ' + s.price : 'Free'} (${s.price_model === 'per_day' ? 'per day' : 'flat'})</span>
                     </div>`;
        });
        $('#dcbm-extra-services-list').html(html);
        $('.service-checkbox').on('change', function() {
            var id = $(this).data('id'), price = parseFloat($(this).data('price')), model = $(this).data('model');
            if ($(this).is(':checked')) {
                selectedServices[id] = { price: price, model: model };
            } else {
                delete selectedServices[id];
            }
            calculateTotal();
        });
    }

    function calculateTotal() {
        var days = parseInt($('#number_of_days').val()) || 1;
        var carRent = activeCarPrice * days;
        var servicesTotal = 0;
        for (var id in selectedServices) {
            var svc = selectedServices[id];
            if (svc.model === 'per_day') servicesTotal += svc.price * days;
            else servicesTotal += svc.price;
        }
        var total = carRent + servicesTotal;
        $('#dcbm-car-rent').text('PKR ' + carRent.toLocaleString());
        $('#dcbm-services-total').text('PKR ' + servicesTotal.toLocaleString());
        $('#dcbm-total-amount').text('PKR ' + total.toLocaleString());
        return total;
    }

    // Advanced Search with Load More
    function performSearch(page) {
        var formData = {
            action: 'dcbm_advanced_search',
            nonce: dcbm_ajax.nonce,
            location_id: $('#adv_location_id').val(),
            paged: page
        };
        $.ajax({
            url: dcbm_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(r) {
                if (r.success) {
                    if (page === 1) $('#dcbm-advanced-results').html(r.data.html);
                    else $('#dcbm-advanced-results').append(r.data.html);
                    currentSearchPage = r.data.current_page;
                    totalSearchPages = r.data.total_pages;
                    if (currentSearchPage >= totalSearchPages) $('#dcbm-load-more-btn').hide();
                    else $('#dcbm-load-more-btn').show();
                }
            }
        });
    }

    // Initial load (default 4 listings)
    function loadInitialResults() {
        currentSearchPage = 1;
        performSearch(1);
    }

    $('#dcbm-advanced-search-form').on('submit', function(e) {
        e.preventDefault();
        currentSearchPage = 1;
        performSearch(1);
    });

    $(document).on('click', '#dcbm-load-more-btn', function() {
        if (currentSearchPage < totalSearchPages) {
            currentSearchPage++;
            performSearch(currentSearchPage);
        }
    });

    // Simple Listing Load More
    function loadListing(page) {
        if (listingLoading) return;
        listingLoading = true;
        $.ajax({
            url: dcbm_ajax.ajax_url,
            type: 'POST',
            data: { action: 'dcbm_load_more_listing', nonce: dcbm_ajax.nonce, paged: page },
            success: function(r) {
                if (r.success && r.data.html) {
                    $('#dcbm-listing-grid').append(r.data.html);
                    currentListingPage = page;
                    listingLoading = false;
                    if (r.data.html.trim() === '') $('#dcbm-listing-load-more').hide();
                } else {
                    $('#dcbm-listing-load-more').hide();
                    listingLoading = false;
                }
            },
            error: function() { listingLoading = false; }
        });
    }

    if ($('#dcbm-listing-grid').length) {
        loadListing(1);
        $(document).on('click', '#dcbm-listing-load-more', function() {
            loadListing(currentListingPage + 1);
        });
    }

    // Booking Modal
    $(document).on('click', '.book-now', function(e) {
        e.preventDefault();
        activeCarId = $(this).data('id');
        activeCarPrice = $(this).data('price');
        activeCarTitle = $(this).closest('.dcbm-car-card').find('h3').text() || 'Car';
        if ($('.dcbm-single-car h1').length) activeCarTitle = $('.dcbm-single-car h1').text();
        $('#modal_car_id').val(activeCarId);
        $('#car_model_display').val(activeCarTitle);
        $('#pickup_location').val($('#adv_location_id option:selected').text());
        $('#pickup_date').val($('#adv_pickup_date').val());
        $('#number_of_days').val($('#adv_days').val() || 1);
        loadExtraServices();
        calculateTotal();
        $('#dcbm-booking-modal').fadeIn(300);
        $('body').css('overflow', 'hidden');
    });

    $('.dcbm-modal-close, #dcbm-booking-modal').on('click', function(e) {
        if (e.target === this) {
            $('#dcbm-booking-modal').fadeOut(300);
            $('body').css('overflow', 'auto');
        }
    });

    $('#number_of_days').on('input', calculateTotal);
    $(document).on('change', '.service-checkbox', calculateTotal);

    $('#dcbm-booking-form').on('submit', function(e) {
        e.preventDefault();
        var isValid = true;
        $('#dcbm-booking-form input[required]').each(function() { if (!$(this).val()) isValid = false; });
        if (!isValid) { alert('Please fill all required fields.'); return false; }
        var driver_needed = $('#driver_needed').is(':checked') ? 'yes' : 'no';
        var days = parseInt($('#number_of_days').val());
        var carRent = activeCarPrice * days;
        var servicesTotal = 0;
        for (var id in selectedServices) {
            var svc = selectedServices[id];
            servicesTotal += (svc.model === 'per_day') ? svc.price * days : svc.price;
        }
        var total = carRent + servicesTotal;
        var formData = {
            action: 'dcbm_submit_booking',
            car_id: activeCarId,
            name: $('#customer_name').val(),
            phone: $('#customer_phone').val(),
            email: $('#customer_email').val(),
            pickup_date: $('#pickup_date').val(),
            number_of_days: days,
            pickup_location: $('#pickup_location').val(),
            destination: $('#destination').val(),
            driver_needed: driver_needed,
            car_rental_price: carRent,
            extra_services_total: servicesTotal,
            total_amount: total,
            selected_services: JSON.stringify(selectedServices),
            nonce: dcbm_ajax.nonce
        };
        var btn = $(this).find('.dcbm-submit-btn');
        btn.prop('disabled', true).text('Submitting...');
        $.ajax({
            url: dcbm_ajax.ajax_url,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(r) {
                if (r.success) {
                    $('#dcbm-booking-modal').fadeOut(300);
                    alert(r.data.message);
                    $('#dcbm-booking-form')[0].reset();
                    selectedServices = {};
                } else {
                    alert('Error: ' + r.data);
                }
            },
            error: function(xhr, status, error) {
                alert('An error occurred: ' + error);
            },
            complete: function() { btn.prop('disabled', false).text('Submit Booking Request'); }
        });
    });

    // Trigger initial load for advanced search shortcode
    if ($('#dcbm-advanced-search-form').length) {
        loadInitialResults();
    }
        // Show Price toggle
    $(document).on('click', '.dcbm-show-price-btn', function() {
        var $btn = $(this);
        var $wrapper = $btn.closest('.dcbm-car-price-wrapper');
        var $priceDisplay = $wrapper.find('.dcbm-price-display');
        $btn.hide();
        $priceDisplay.show();
    });
});