jQuery(document).ready(function($) {
    // Update booking status
    $('.update-status').on('click', function(e) {
        e.preventDefault();
        const bookingId = $(this).data('id');
        const status = $(this).data('status');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'dcbm_update_booking_status',
                booking_id: bookingId,
                status: status,
                nonce: dcbm_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Failed to update status');
                }
            }
        });
    });
    
    // Gallery image upload for car meta box
    $('#dcbm_add_gallery_images').on('click', function(e) {
        e.preventDefault();
        
        let frame = wp.media({
            title: 'Select Gallery Images',
            multiple: true,
            library: { type: 'image' },
            button: { text: 'Add to Gallery' }
        });
        
        frame.on('select', function() {
            let selection = frame.state().get('selection');
            selection.each(function(attachment) {
                let imageId = attachment.id;
                let imageUrl = attachment.attributes.sizes.thumbnail.url;
                
                let imageHtml = `
                    <div class="dcbm-gallery-image">
                        <img src="${imageUrl}" />
                        <input type="hidden" name="dcbm_gallery_images[]" value="${imageId}" />
                        <button type="button" class="button dcbm-remove-gallery-image">Remove</button>
                    </div>
                `;
                
                $('.dcbm-gallery-images').append(imageHtml);
            });
        });
        
        frame.open();
    });
    
    // Remove gallery image
    $(document).on('click', '.dcbm-remove-gallery-image', function() {
        $(this).closest('.dcbm-gallery-image').remove();
    });
});