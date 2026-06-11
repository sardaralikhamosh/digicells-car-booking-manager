jQuery(document).ready(function($) {
    var activeCarId = null, activeCarPrice = 0, selectedServices = {}, activeCarTitle = '';

    // Helper: Load extra services
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
        if (services.length === 0) {
            html = '<p>No extra services available.</p>';
        } else {
            $.each(services, function(i, s) {
                html += `<div class="dcbm-service-item">
                            <label>
                                <input type="checkbox" class="service-checkbox" data-id="${s.id}" data-price="${s.price}" data-type="${s.price_type}">
                                <span>${s.icon}</span> <span>${s.name}</span>
                            </label>
                            <span>${s.price_type === 'paid' ? 'PKR ' + s.price : 'Free'}</span>
                         </div>`;
            });
        }
        $('#dcbm-extra-services-list').html(html);
        $('.service-checkbox').on('change', function() {
            var id = $(this).data('id'), price = $(this).data('price'), type = $(this).data('type');
            if ($(this).is(':checked')) {
                selectedServices[id] = type === 'paid' ? parseFloat(price) : 0;
            } else {
                delete selectedServices[id];
            }
            calculateTotal();
        });
    }

    function calculateTotal() {
        var pickup = $('#pickup_date').val(),
            picktime = $('#pickup_time').val(),
            ret = $('#return_date').val(),
            rettime = $('#return_time').val();
        if (pickup && picktime && ret && rettime) {
            var start = new Date(pickup + 'T' + picktime),
                end = new Date(ret + 'T' + rettime),
                days = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
            if (days < 1) days = 1;
            var carRent = activeCarPrice * days,
                servicesTotal = 0;
            for (var id in selectedServices) servicesTotal += selectedServices[id];
            var total = carRent + servicesTotal;
            $('#dcbm-car-rent').text('PKR ' + carRent.toLocaleString());
            $('#dcbm-services-total').text('PKR ' + servicesTotal.toLocaleString());
            $('#dcbm-total-amount').text('PKR ' + total.toLocaleString());
            $('#total_amount').val(total);
            $('#number_of_days').val(days);
        }
    }

    function resetForm() {
        $('#dcbm-booking-form')[0].reset();
        selectedServices = {};
        $('.service-checkbox').prop('checked', false);
        calculateTotal();
        $('body').css('overflow', 'auto');
    }

    // Pagination handler
    function attachPaginationHandlers() {
        $('.dcbm-page-btn').off('click').on('click', function() {
            var page = $(this).data('page');
            var formData = {
                action: 'dcbm_advanced_search',
                nonce: dcbm_ajax.nonce,
                pickup_location: $('#adv_pickup_location').val(),
                pickup_date: $('#adv_pickup_date').val(),
                pickup_time: $('#adv_pickup_time').val(),
                return_date: $('#adv_return_date').val(),
                return_time: $('#adv_return_time').val(),
                seats: '',
                auto_only: false,
                unlimited_miles_only: false,
                paged: page
            };
            $('#dcbm-advanced-results').html('<div style="text-align:center;padding:50px;">Loading...</div>');
            $.ajax({
                url: dcbm_ajax.ajax_url,
                type: 'POST',
                data: formData,
                success: function(r) {
                    $('#dcbm-advanced-results').html(r);
                    attachPaginationHandlers();
                }
            });
        });
    }

    // Advanced search form submit
    $('#dcbm-advanced-search-form').on('submit', function(e) {
        e.preventDefault();
        var formData = {
            action: 'dcbm_advanced_search',
            nonce: dcbm_ajax.nonce,
            pickup_location: $('#adv_pickup_location').val(),
            pickup_date: $('#adv_pickup_date').val(),
            pickup_time: $('#adv_pickup_time').val(),
            return_date: $('#adv_return_date').val(),
            return_time: $('#adv_return_time').val(),
            seats: '',
            auto_only: false,
            unlimited_miles_only: false,
            paged: 1
        };
        $('#dcbm-advanced-results').html('<div style="text-align:center;padding:50px;">Searching...</div>');
        $.ajax({
            url: dcbm_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(r) {
                $('#dcbm-advanced-results').html(r);
                attachPaginationHandlers();
            },
            error: function() {
                $('#dcbm-advanced-results').html('<div style="text-align:center;padding:50px;">Error loading results.</div>');
            }
        });
    });

    // Auto-load initial results
    function loadInitialResults() {
        var urlParams = new URLSearchParams(window.location.search);
        var hasSearchParams = urlParams.has('pickup_date') && urlParams.has('return_date');

        if (!hasSearchParams) {
            var today = new Date().toISOString().split('T')[0];
            var nextWeek = new Date(Date.now() + 7 * 86400000).toISOString().split('T')[0];
            $('#adv_pickup_date').val(today);
            $('#adv_return_date').val(nextWeek);
            $('#adv_pickup_time').val('10:00');
            $('#adv_return_time').val('10:00');
            $('#dcbm-advanced-search-form').submit();
        } else {
            $('#adv_pickup_location').val(urlParams.get('pickup_location') || '');
            $('#adv_pickup_date').val(urlParams.get('pickup_date') || '');
            $('#adv_pickup_time').val(urlParams.get('pickup_time') || '10:00');
            $('#adv_return_date').val(urlParams.get('return_date') || '');
            $('#adv_return_time').val(urlParams.get('return_time') || '10:00');
            if (urlParams.get('driver_age') === 'on') $('#driver_age').prop('checked', true);
            $('#dcbm-advanced-search-form').submit();
        }
    }

    if ($('#dcbm-advanced-search-form').length) {
        loadInitialResults();
    }

    // Booking modal - open when clicking "Book Now" buttons
    $(document).on('click', '.book-now', function(e) {
        e.preventDefault();
        activeCarId = $(this).data('id');
        activeCarPrice = $(this).data('price');
        activeCarTitle = $(this).closest('.dcbm-car-card').find('h3').text() || $(this).data('title') || 'Car';
        
        // For single car page, get title from page
        if ($('.dcbm-single-car h1').length) {
            activeCarTitle = $('.dcbm-single-car h1').text();
        }
        
        $('#modal_car_id').val(activeCarId);
        $('#car_model_display').val(activeCarTitle);
        
        // Pre-fill form fields from search form
        $('#pickup_location').val($('#adv_pickup_location').val());
        $('#pickup_date').val($('#adv_pickup_date').val());
        $('#pickup_time').val($('#adv_pickup_time').val());
        $('#return_date').val($('#adv_return_date').val());
        $('#return_time').val($('#adv_return_time').val());
        
        loadExtraServices();
        calculateTotal();
        $('#dcbm-booking-modal').fadeIn(300);
        $('body').css('overflow', 'hidden');
    });

    // Close modal
    $('.dcbm-modal-close').on('click', function() {
        $('#dcbm-booking-modal').fadeOut(300);
        $('body').css('overflow', 'auto');
        resetForm();
    });
    $(window).on('click', function(e) {
        if ($(e.target).is('#dcbm-booking-modal')) {
            $('#dcbm-booking-modal').fadeOut(300);
            $('body').css('overflow', 'auto');
            resetForm();
        }
    });

    // Recalculate total when dates/times change
    $('#pickup_date, #pickup_time, #return_date, #return_time').on('change', function() {
        calculateTotal();
    });

    // Form submission
    $('#dcbm-booking-form').on('submit', function(e) {
        e.preventDefault();
        
        var isValid = true;
        $('#dcbm-booking-form input[required], #dcbm-booking-form select[required]').each(function() {
            if (!$(this).val()) {
                $(this).css('border-color', '#ef4444');
                isValid = false;
            } else {
                $(this).css('border-color', '#e2e8f0');
            }
        });
        if (!isValid) {
            alert('Please fill all required fields.');
            return false;
        }
        
        var driver_needed = $('#driver_needed').is(':checked') ? 'yes' : 'no';
        
        var formData = {
            action: 'dcbm_submit_booking',
            car_id: activeCarId,
            name: $('#customer_name').val(),
            phone: $('#customer_phone').val(),
            email: $('#customer_email').val(),
            pickup_date: $('#pickup_date').val(),
            pickup_time: $('#pickup_time').val(),
            return_date: $('#return_date').val(),
            return_time: $('#return_time').val(),
            pickup_location: $('#pickup_location').val(),
            destination: $('#destination').val(),
            driver_needed: driver_needed,
            number_of_days: $('#number_of_days').val(),
            car_rental_price: parseFloat($('#dcbm-car-rent').text().replace('PKR ', '').replace(/,/g, '')),
            extra_services_total: parseFloat($('#dcbm-services-total').text().replace('PKR ', '').replace(/,/g, '')),
            total_amount: $('#total_amount').val(),
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
                    resetForm();
                } else {
                    alert(r.data);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            },
            complete: function() {
                btn.prop('disabled', false).text('Submit Booking Request');
            }
        });
    });
});