<?php
class DCBM_Email_Handler {
    
    public function send_admin_notification($booking_id, $booking_data) {
        $admin_email = get_option('admin_email');
        $subject = sprintf(__('New Car Booking: %s', 'digicells-cbm'), $booking_data['booking_number']);
        
        $car_title = get_the_title($booking_data['car_id']);
        
        $message = $this->get_admin_email_template($booking_data, $car_title);
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        wp_mail($admin_email, $subject, $message, $headers);
    }
    
    public function send_customer_confirmation($booking_id, $booking_data) {
        $subject = __('Car Booking Confirmation - Hamari Booking', 'digicells-cbm');
        
        $car_title = get_the_title($booking_data['car_id']);
        
        $message = $this->get_customer_email_template($booking_data, $car_title);
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        wp_mail($booking_data['customer_email'], $subject, $message, $headers);
    }
    
    private function get_admin_email_template($booking, $car_title) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: 'Poppins', sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #f68511; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px; background: #f9f9f9; }
                .booking-details { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .detail-row { padding: 10px 0; border-bottom: 1px solid #eee; }
                .label { font-weight: 600; color: #0048a5; }
                .total { font-size: 18px; font-weight: 700; color: #f68511; margin-top: 15px; }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>New Car Booking Request</h2>
                    <p>Booking #: <?php echo $booking['booking_number']; ?></p>
                </div>
                <div class="content">
                    <h3>Customer Information</h3>
                    <div class="booking-details">
                        <div class="detail-row"><span class="label">Name:</span> <?php echo $booking['customer_name']; ?></div>
                        <div class="detail-row"><span class="label">Phone:</span> <?php echo $booking['customer_phone']; ?></div>
                        <div class="detail-row"><span class="label">Email:</span> <?php echo $booking['customer_email']; ?></div>
                    </div>
                    
                    <h3>Car Details</h3>
                    <div class="booking-details">
                        <div class="detail-row"><span class="label">Car:</span> <?php echo $car_title; ?></div>
                        <div class="detail-row"><span class="label">Pickup Date:</span> <?php echo date('F j, Y', strtotime($booking['pickup_date'])); ?></div>
                        <div class="detail-row"><span class="label">Return Date:</span> <?php echo date('F j, Y', strtotime($booking['return_date'])); ?></div>
                        <div class="detail-row"><span class="label">Pickup Location:</span> <?php echo $booking['pickup_location']; ?></div>
                        <div class="detail-row"><span class="label">Number of Days:</span> <?php echo $booking['number_of_days']; ?></div>
                    </div>
                    
                    <h3>Payment Summary</h3>
                    <div class="booking-details">
                        <div class="detail-row"><span class="label">Car Rent:</span> PKR <?php echo number_format($booking['car_rental_price']); ?></div>
                        <?php if ($booking['extra_services_total'] > 0): ?>
                        <div class="detail-row"><span class="label">Extra Services:</span> PKR <?php echo number_format($booking['extra_services_total']); ?></div>
                        <?php endif; ?>
                        <div class="total">Total Amount: PKR <?php echo number_format($booking['total_amount']); ?></div>
                    </div>
                    
                    <p>Please login to the admin dashboard to approve or reject this booking.</p>
                </div>
                <div class="footer">
                    <p>Hamari Booking - Car Rental Service</p>
                    <p>This is an automated message, please do not reply.</p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    private function get_customer_email_template($booking, $car_title) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: 'Poppins', sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #0048a5; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px; background: #f9f9f9; }
                .booking-details { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .detail-row { padding: 10px 0; border-bottom: 1px solid #eee; }
                .label { font-weight: 600; color: #0048a5; }
                .status { display: inline-block; padding: 5px 15px; background: #fff3cd; color: #856404; border-radius: 20px; }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>Booking Confirmation</h2>
                    <p>Thank you for choosing Hamari Booking!</p>
                </div>
                <div class="content">
                    <p>Dear <?php echo $booking['customer_name']; ?>,</p>
                    <p>Your car booking request has been received successfully. Here are your booking details:</p>
                    
                    <div class="booking-details">
                        <div class="detail-row"><span class="label">Booking Number:</span> <?php echo $booking['booking_number']; ?></div>
                        <div class="detail-row"><span class="label">Car:</span> <?php echo $car_title; ?></div>
                        <div class="detail-row"><span class="label">Pickup Date:</span> <?php echo date('F j, Y', strtotime($booking['pickup_date'])); ?></div>
                        <div class="detail-row"><span class="label">Return Date:</span> <?php echo date('F j, Y', strtotime($booking['return_date'])); ?></div>
                        <div class="detail-row"><span class="label">Pickup Location:</span> <?php echo $booking['pickup_location']; ?></div>
                        <div class="detail-row"><span class="label">Total Amount:</span> <strong style="color: #f68511;">PKR <?php echo number_format($booking['total_amount']); ?></strong></div>
                        <div class="detail-row"><span class="label">Status:</span> <span class="status">Pending Confirmation</span></div>
                    </div>
                    
                    <p>Our team will review your booking and contact you within 24 hours to confirm availability.</p>
                    <p>If you have any questions, please contact us at support@hamaribooking.com or call +92 XXX XXXXXXX.</p>
                    
                    <p style="margin-top: 30px;">Warm regards,<br><strong>Hamari Booking Team</strong></p>
                </div>
                <div class="footer">
                    <p>© <?php echo date('Y'); ?> Hamari Booking. All rights reserved.</p>
                    <p>This is a confirmation email, please keep it for your records.</p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}