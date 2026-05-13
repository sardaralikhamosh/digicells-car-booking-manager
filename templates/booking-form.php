<!-- Booking Modal -->
<div id="dcbm-booking-modal" class="dcbm-modal" style="display: none;">
    <div class="dcbm-modal-content">
        <div class="dcbm-modal-header">
            <h2>Book This Car</h2>
            <button class="dcbm-modal-close">&times;</button>
        </div>
        <div class="dcbm-modal-body">
            <form id="dcbm-booking-form">
                <input type="hidden" name="car_id" value="">
                <input type="hidden" name="number_of_days" id="number_of_days" value="">
                <input type="hidden" name="total_amount" id="total_amount" value="">
                
                <h3>Customer Information</h3>
                <div class="dcbm-form-row">
                    <div class="dcbm-form-group">
                        <label>Full Name *</label>
                        <input type="text" id="customer_name" required>
                    </div>
                    <div class="dcbm-form-group">
                        <label>Phone Number *</label>
                        <input type="tel" id="customer_phone" required>
                    </div>
                </div>
                <div class="dcbm-form-group">
                    <label>Email Address *</label>
                    <input type="email" id="customer_email" required>
                </div>
                
                <h3 style="margin-top: 25px;">Booking Information</h3>
                <div class="dcbm-form-row">
                    <div class="dcbm-form-group">
                        <label>Pickup Date *</label>
                        <input type="date" id="pickup_date" required>
                    </div>
                    <div class="dcbm-form-group">
                        <label>Return Date *</label>
                        <input type="date" id="return_date" required>
                    </div>
                </div>
                <div class="dcbm-form-group">
                    <label>Pickup Location *</label>
                    <input type="text" id="pickup_location" required placeholder="Enter pickup location">
                </div>
                
                <h3 style="margin-top: 25px;">Extra Services</h3>
                <div id="dcbm-extra-services-list" class="dcbm-services-list">
                    <!-- Services will be loaded via AJAX -->
                </div>
                
                <div class="dcbm-price-summary">
                    <div class="dcbm-price-row">
                        <span>Car Rent</span>
                        <span id="dcbm-car-rent">PKR 0</span>
                    </div>
                    <div class="dcbm-price-row">
                        <span>Extra Services</span>
                        <span id="dcbm-services-total">PKR 0</span>
                    </div>
                    <div class="dcbm-price-row dcbm-price-total">
                        <span>Total Amount</span>
                        <span id="dcbm-total-amount">PKR 0</span>
                    </div>
                </div>
                
                <button type="submit" class="dcbm-submit-btn">Submit Booking Request</button>
            </form>
        </div>
    </div>
</div>