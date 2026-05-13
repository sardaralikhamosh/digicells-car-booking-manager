jQuery(document).ready(function($) {
    // Global variables
    let selectedCarId = null;
    let selectedCarPrice = 0;
    let selectedServices = {};
    
    // Open booking modal
    $('.book-now-btn').on('click', function() {
        selectedCarId = $(this).data('car-id');
        selectedCarPrice = parseFloat($(this).data('car-price'));
        
        // Set car ID in form
        $('#dcbm-booking-form input[name="car_id"]').val(selectedCarId);
        
        // Load extra services
        loadExtraServices(selectedCarId);
        
        // Show modal
        $('#dcbm-booking-modal').fadeIn(300);
        $('body').css('overflow', 'hidden');
        
        // Calculate initial price
        calculateTotalPrice();
    });
    
    // Close modal
    $('.dcbm-modal-close, .dcbm-modal-overlay').on('click', function() {
        $('#dcbm-booking-modal').fadeOut(300);
        $('body').css('overflow', 'auto');
        resetForm();
    });
    
    // Load extra services via AJAX
    function loadExtraServices(carId) {
        $.ajax({
            url: dcbm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'dcbm_get_extra_services',
                car_id: carId,
                nonce: dcbm_ajax.nonce
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    renderServices(response.data);
                }
            }
        });
    }
    
    function renderServices(services) {
        let html = '';
        if (services.length === 0) {
            html = '<p>No extra services available.</p>';
        } else {
            services.forEach(function(service) {
                html += `
                    <div class="dcbm-service-item">
                        <label class="dcbm-service-info">
                            <input type="checkbox" class="service-checkbox" data-service-id="${service.id}" data-service-price="${service.price}" data-price-type="${service.price_type}">
                            <span class="dcbm-service-icon">${service.icon}</span>
                            <span>${service.name}</span>
                        </label>
                        <span class="dcbm-service-price">${service.price_type === 'paid' ? 'PKR ' + service.price : 'Free'}</span>
                    </div>
                `;
            });
        }
        $('#dcbm-extra-services-list').html(html);
        
        // Attach change event to checkboxes
        $('.service-checkbox').on('change', function() {
            const serviceId = $(this).data('service-id');
            const servicePrice = $(this).data('service-price');
            const priceType = $(this).data('price-type');
            
            if ($(this).is(':checked')) {
                selectedServices[serviceId] = priceType === 'paid' ? parseFloat(servicePrice) : 0;
            } else {
                delete selectedServices[serviceId];
            }
            calculateTotalPrice();
        });
    }
    
    // Calculate total price dynamically
    function calculateTotalPrice() {
        const pickupDate = $('#pickup_date').val();
        const returnDate = $('#return_date').val();
        
        if (pickupDate && returnDate) {
            const days = Math.ceil((new Date(returnDate) - new Date(pickupDate)) / (1000 * 60 * 60 * 24));
            const carRent = selectedCarPrice * days;
            
            let servicesTotal = 0;
            for (let id in selectedServices) {
                servicesTotal += selectedServices[id];
            }
            
            const total = carRent + servicesTotal;
            
            // Update display
            $('#dcbm-car-rent').text('PKR ' + carRent.toLocaleString());
            $('#dcbm-services-total').text('PKR ' + servicesTotal.toLocaleString());
            $('#dcbm-total-amount').text('PKR ' + total.toLocaleString());
            $('#total_amount').val(total);
            $('#number_of_days').val(days);
        }
    }
    
    // Date change event
    $('#pickup_date, #return_date').on('change', function() {
        calculateTotalPrice();
    });
    
    // Form submission
    $('#dcbm-booking-form').on('submit', function(e) {
        e.preventDefault();
        
        // Validate form
        if (!validateForm()) {
            return false;
        }
        
        // Prepare form data
        const formData = {
            action: 'dcbm_submit_booking',
            car_id: selectedCarId,
            name: $('#customer_name').val(),
            phone: $('#customer_phone').val(),
            email: $('#customer_email').val(),
            pickup_date: $('#pickup_date').val(),
            return_date: $('#return_date').val(),
            pickup_location: $('#pickup_location').val(),
            number_of_days: $('#number_of_days').val(),
            car_rental_price: parseFloat($('#dcbm-car-rent').text().replace('PKR ', '').replace(/,/g, '')),
            extra_services_total: parseFloat($('#dcbm-services-total').text().replace('PKR ', '').replace(/,/g, '')),
            total_amount: $('#total_amount').val(),
            selected_services: JSON.stringify(selectedServices),
            nonce: dcbm_ajax.nonce
        };
        
        // Show loading state
        const submitBtn = $(this).find('.dcbm-submit-btn');
        submitBtn.prop('disabled', true).text(dcbm_ajax.loading_text);
        
        $.ajax({
            url: dcbm_ajax.ajax_url,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Close modal
                    $('#dcbm-booking-modal').fadeOut(300);
                    
                    // Show success popup
                    showSuccessPopup(response.data.message);
                    
                    // Reset form
                    resetForm();
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert(dcbm_ajax.error_text);
            },
            complete: function() {
                submitBtn.prop('disabled', false).text('Submit Booking');
            }
        });
    });
    
    function validateForm() {
        let isValid = true;
        const requiredFields = ['#customer_name', '#customer_phone', '#customer_email', '#pickup_date', '#return_date', '#pickup_location'];
        
        requiredFields.forEach(function(field) {
            if (!$(field).val()) {
                $(field).css('border-color', '#ef4444');
                isValid = false;
            } else {
                $(field).css('border-color', '#e2e8f0');
            }
        });
        
        // Validate email
        const email = $('#customer_email').val();
        if (email && !isValidEmail(email)) {
            $('#customer_email').css('border-color', '#ef4444');
            isValid = false;
        }
        
        if (!isValid) {
            alert('Please fill in all required fields correctly.');
        }
        
        return isValid;
    }
    
    function isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }
    
    function showSuccessPopup(message) {
        const popup = `
            <div class="dcbm-success-popup" id="dcbm-success-popup">
                <div class="dcbm-success-content">
                    <div class="dcbm-success-icon">✓</div>
                    <h3 class="dcbm-success-title">Booking Submitted!</h3>
                    <p class="dcbm-success-message">${message}</p>
                    <button class="dcbm-btn dcbm-btn-primary" onclick="closeSuccessPopup()">Close</button>
                </div>
            </div>
        `;
        
        $('body').append(popup);
        $('body').css('overflow', 'hidden');
        
        // Auto close after 5 seconds
        setTimeout(function() {
            closeSuccessPopup();
        }, 5000);
    }
    
    window.closeSuccessPopup = function() {
        $('#dcbm-success-popup').fadeOut(300, function() {
            $(this).remove();
            $('body').css('overflow', 'auto');
        });
    };
    
    function resetForm() {
        $('#dcbm-booking-form')[0].reset();
        selectedServices = {};
        $('.service-checkbox').prop('checked', false);
        calculateTotalPrice();
    }
    
    // AJAX Filtering for car listing
    $('#dcbm-search-form, .dcbm-filter-group select').on('change keyup', function() {
        const formData = $('#dcbm-search-form').serialize();
        formData += '&action=dcbm_filter_cars&nonce=' + dcbm_ajax.nonce;
        
        $.ajax({
            url: dcbm_ajax.ajax_url,
            type: 'POST',
            data: formData,
            beforeSend: function() {
                $('.dcbm-cars-grid').html('<div class="dcbm-loading">Loading...</div>');
            },
            success: function(response) {
                $('.dcbm-cars-grid').html(response);
                // Re-attach book now button events
                $('.book-now-btn').on('click', function() {
                    selectedCarId = $(this).data('car-id');
                    selectedCarPrice = parseFloat($(this).data('car-price'));
                    $('#dcbm-booking-form input[name="car_id"]').val(selectedCarId);
                    loadExtraServices(selectedCarId);
                    $('#dcbm-booking-modal').fadeIn(300);
                    $('body').css('overflow', 'hidden');
                });
            }
        });
    });
});