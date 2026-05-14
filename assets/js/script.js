jQuery(document).ready(function($) {
    var selectedServices = {};
    var currentCarPrice = 0;
    
    // Load extra services
    function loadServices() {
        $.post(dcbm_ajax.ajax_url, {
            action: 'dcbm_get_services',
            nonce: dcbm_ajax.nonce
        }, function(response) {
            if(response.success) {
                var html = '';
                $.each(response.data, function(i, service) {
                    html += '<div class="dcbm-service-item">';
                    html += '<label><input type="checkbox" class="service-check" data-id="'+service.id+'" data-price="'+service.price+'" data-type="'+service.price_type+'"> ';
                    html += service.icon + ' ' + service.name;
                    html += '</label>';
                    html += '<span>' + (service.price_type == 'paid' ? 'PKR ' + service.price : 'Free') + '</span>';
                    html += '</div>';
                });
                $('#dcbm-services-list').html(html);
                
                // Bind change event to service checkboxes
                $('.service-check').on('change', function() {
                    var id = $(this).data('id');
                    var price = $(this).data('price');
                    var type = $(this).data('type');
                    
                    if($(this).is(':checked')) {
                        selectedServices[id] = type == 'paid' ? parseFloat(price) : 0;
                    } else {
                        delete selectedServices[id];
                    }
                    calculateTotal();
                });
            }
        });
    }
    
    // Calculate total price
    function calculateTotal() {
        var pickupDate = $('#pickup_date').val();
        var returnDate = $('#return_date').val();
        
        if(pickupDate && returnDate) {
            var days = Math.ceil((new Date(returnDate) - new Date(pickupDate)) / (1000 * 60 * 60 * 24));
            var carRent = currentCarPrice * days;
            var servicesTotal = 0;
            
            for(var id in selectedServices) {
                servicesTotal += selectedServices[id];
            }
            
            var total = carRent + servicesTotal;
            
            $('#display-car-rent').text('PKR ' + carRent.toLocaleString());
            $('#display-services-total').text('PKR ' + servicesTotal.toLocaleString());
            $('#display-total').text('PKR ' + total.toLocaleString());
            $('#total_days').val(days);
            $('#services_total').val(servicesTotal);
            $('#total_amount').val(total);
            $('#selected_services').val(JSON.stringify(selectedServices));
        }
    }
    
    // Date change events
    $('#pickup_date, #return_date').on('change', calculateTotal);
    
    // Open booking modal
    $(document).on('click', '.book-now-btn', function() {
        currentCarPrice = parseFloat($(this).data('price'));
        $('#booking_car_id').val($(this).data('id'));
        $('#booking_car_price').val(currentCarPrice);
        $('#dcbm-modal').fadeIn(300);
        $('body').css('overflow', 'hidden');
        loadServices();
    });
    
    // Close modal
    $('.dcbm-modal-close').on('click', function() {
        $('#dcbm-modal').fadeOut(300);
        $('body').css('overflow', 'auto');
        $('#dcbm-booking-form')[0].reset();
        selectedServices = {};
        $('#dcbm-services-list').empty();
    });
    
    // Close modal when clicking outside
    $(window).on('click', function(e) {
        if($(e.target).is('#dcbm-modal')) {
            $('#dcbm-modal').fadeOut(300);
            $('body').css('overflow', 'auto');
            $('#dcbm-booking-form')[0].reset();
            selectedServices = {};
            $('#dcbm-services-list').empty();
        }
    });
    
    // Submit booking form
    $('#dcbm-booking-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = {
            action: 'dcbm_submit_booking',
            nonce: dcbm_ajax.nonce,
            car_id: $('#booking_car_id').val(),
            name: $('#cust_name').val(),
            phone: $('#cust_phone').val(),
            email: $('#cust_email').val(),
            pickup_date: $('#pickup_date').val(),
            return_date: $('#return_date').val(),
            pickup_location: $('#pickup_location').val(),
            days: $('#total_days').val(),
            car_price: currentCarPrice,
            services_total: $('#services_total').val(),
            total: $('#total_amount').val(),
            services: $('#selected_services').val()
        };
        
        // Validate required fields
        if(!formData.name || !formData.phone || !formData.email || !formData.pickup_date || !formData.return_date || !formData.pickup_location) {
            alert('Please fill in all required fields.');
            return;
        }
        
        var submitBtn = $('.dcbm-submit-btn');
        submitBtn.prop('disabled', true).text('Submitting...');
        
        $.post(dcbm_ajax.ajax_url, formData, function(response) {
            if(response.success) {
                alert(response.data.message);
                $('#dcbm-modal').fadeOut(300);
                $('#dcbm-booking-form')[0].reset();
                selectedServices = {};
                $('#dcbm-services-list').empty();
                $('body').css('overflow', 'auto');
            } else {
                alert(response.data);
            }
        }).fail(function() {
            alert('An error occurred. Please try again.');
        }).always(function() {
            submitBtn.prop('disabled', false).text('Submit Booking');
        });
    });
    
    // Filter cars
    $('#dcbm-filter-btn').on('click', function() {
        var search = $('#dcbm-search').val();
        var category = $('#dcbm-category').val();
        var transmission = $('#dcbm-transmission').val();
        
        $.post(dcbm_ajax.ajax_url, {
            action: 'dcbm_filter_cars',
            nonce: dcbm_ajax.nonce,
            search: search,
            category: category,
            transmission: transmission
        }, function(response) {
            $('#dcbm-cars-grid').html(response);
        });
    });
    
    // Trigger search on Enter key
    $('#dcbm-search').on('keypress', function(e) {
        if(e.which == 13) {
            $('#dcbm-filter-btn').click();
        }
    });
});