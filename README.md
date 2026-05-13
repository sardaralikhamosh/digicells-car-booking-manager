Digicells Car Booking Manager is a professional, feature-rich WordPress plugin for car rental and booking management businesses. Perfect for car rental agencies, taxi services, and vehicle fleet management.

✅ Complete car inventory management<br>
✅ Real-time price calculation with extra services<br>
✅ AJAX-powered booking system<br>
✅ Email notifications (admin + customer)<br>
✅ Responsive design matching Hamari Booking branding<br>
✅ SEO-friendly structure<br>
✅ Elementor compatible<br>

Built with WordPress coding standards, OOP PHP, and modern web technologies.


---

# 🚗 Digicells Car Booking Manager

[![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/yourusername/digicells-car-booking-manager)
[![WordPress](https://img.shields.io/badge/WordPress-5.0+-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4+-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-GPLv2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

A professional, feature-rich car rental and booking management system for WordPress. Perfect for car rental agencies, taxi services, and vehicle fleet management businesses.

## 📋 Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start Guide](#quick-start-guide)
- [Shortcodes](#shortcodes)
- [Admin Guide](#admin-guide)
- [Frontend Guide](#frontend-guide)
- [API & Hooks](#api--hooks)
- [FAQ](#faq)
- [Support](#support)
- [Changelog](#changelog)
- [Credits](#credits)

## ✨ Features

### Core Features
- **Complete Car Management** - Add, edit, and manage unlimited cars with detailed specifications
- **Car Categories** - SUV, Sedan, Luxury, Economy, Van, 4x4 and custom categories
- **Extra Services Management** - Add services like Driver, Fuel, GPS, Child Seat with pricing
- **Booking System** - Professional booking form with real-time price calculation
- **Admin Dashboard** - Complete booking management with status updates
- **Email Notifications** - Automatic emails to admin and customers
- **AJAX Filtering** - Filter cars by category, transmission, capacity without page reload
- **Responsive Design** - Fully responsive and mobile-friendly interface
- **SEO Friendly** - Optimized for search engines with proper schema

### Technical Features
- ✅ WordPress Coding Standards
- ✅ OOP PHP Architecture
- ✅ AJAX-powered interactions
- ✅ Nonce Security & Validation
- ✅ SQL Injection Protection
- ✅ XSS Prevention
- ✅ REST API Ready Structure
- ✅ Optimized Database Queries
- ✅ Separate CSS/JS files
- ✅ Translation Ready

### Shortcodes
- `[digicells_car_listing]` - Main car listing with filters
- `[digicells_featured_cars]` - Featured cars display
- `[digicells_car_search]` - Search form only

## 📋 Requirements

| Requirement | Minimum Version |
|-------------|----------------|
| WordPress | 5.0+ |
| PHP | 7.4+ |
| MySQL | 5.6+ |
| Memory Limit | 128MB+ |

### Recommended
- PHP 8.0 or higher
- WordPress 6.0 or higher
- HTTPS enabled website

## 🚀 Installation

### Method 1: WordPress Admin (via ZIP)

1. Download the plugin ZIP file from GitHub
2. Go to **WordPress Admin → Plugins → Add New**
3. Click **Upload Plugin** button
4. Select the downloaded ZIP file
5. Click **Install Now** button
6. After installation, click **Activate Plugin**

### Method 2: FTP Installation

1. Extract the plugin ZIP file
2. Upload the `digicells-car-booking-manager` folder to `/wp-content/plugins/`
3. Go to **WordPress Admin → Plugins**
4. Find **Digicells Car Booking Manager** in the list
5. Click **Activate**

### Method 3: Direct GitHub Download

```bash
cd /wp-content/plugins/
git clone https://github.com/yourusername/digicells-car-booking-manager.git
```

Then activate from WordPress admin.

## 🔧 Quick Start Guide

### Step 1: Configure Plugin Settings

After activation, the plugin creates:
- Custom post type "Cars"
- Database tables for bookings and services
- Default car categories
- Default extra services

### Step 2: Add Car Categories

1. Go to **Cars → Categories**
2. Add categories like: SUV, Sedan, Luxury, Economy, Van, 4x4
3. Click **Add New Category**

### Step 3: Add Your First Car

1. Navigate to **Cars → Add New Car**
2. Fill in all car details:
   - **Basic Information**: Car name, model, manufacturer, registration year
   - **Specifications**: Transmission, fuel type, passenger capacity, features
   - **Pricing**: Daily rates, security deposit, extra km charges
   - **Location**: Pickup/drop-off locations, city
   - **Gallery**: Upload car images
3. Set **Availability** status
4. Click **Publish**

### Step 4: Add Extra Services

1. Go to **Cars → Extra Services**
2. Add services like:
   - Driver (Paid: PKR 1,000)
   - Fuel (Paid: PKR 2,000)
   - Child Seat (Paid: PKR 500)
   - Meal (Free)
3. Enable/disable services as needed

### Step 5: Add Shortcodes to Pages

Create a new page and add the shortcode:

```html
[digicells_car_listing]
```

Optional with parameters:

```html
[digicells_car_listing per_page="12" show_filter="yes"]
```

### Step 6: Test Booking System

1. Visit the page with the car listing
2. Click **Book Now** on any available car
3. Fill out the booking form with:
   - Customer information
   - Pickup/return dates
   - Select extra services
4. Submit the form
5. Check email for confirmation

## 📖 Shortcodes

### Main Car Listing
```
[digicells_car_listing]
```

**Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| per_page | integer | 12 | Number of cars per page |
| show_filter | yes/no | yes | Show/hide filter section |

**Example:**
```
[digicells_car_listing per_page="6" show_filter="no"]
```

### Featured Cars
```
[digicells_featured_cars]
```

**Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| per_page | integer | 6 | Number of featured cars to show |

**Example:**
```
[digicells_featured_cars per_page="4"]
```

### Search Form Only
```
[digicells_car_search]
```

This displays only the search/filter form without the car grid.

## 🛠️ Admin Guide

### Managing Cars

**Add New Car:**
1. Cars → Add New Car
2. Complete all meta box sections
3. Set featured image and gallery images
4. Publish

**Edit Existing Car:**
1. Cars → All Cars
2. Hover over car title
3. Click **Edit**

**Delete Car:**
1. Cars → All Cars
2. Hover over car title
3. Click **Trash**

### Managing Bookings

**View Bookings:**
1. Cars → Bookings
2. See all customer bookings in table
3. Use search and filters to find specific bookings

**Update Booking Status:**
| Status | Description |
|--------|-------------|
| Pending | New booking awaiting review |
| Approved | Booking confirmed |
| Rejected | Booking declined |
| Completed | Rental completed |

**To update status:**
1. Click the action link (Approve/Reject/Complete)
2. Status updates instantly via AJAX

### Managing Extra Services

**Add Service:**
1. Cars → Extra Services
2. Fill in service details:
   - Service Name (e.g., "GPS Navigation")
   - Service Icon (emoji or text)
   - Price Type (Free/Paid)
   - Price (if paid)
   - Status (Enabled/Disabled)
3. Click **Add Service**

**Edit/Delete Service:**
- Edit: Click the service name or use quick edit
- Delete: Click **Delete** button next to the service

## 🎨 Frontend Guide

### Car Listing Page

The car listing displays:
- Car image (featured image)
- Car name and model
- Transmission type
- Passenger capacity
- Pickup location
- Daily price
- Availability badge
- Book Now button

### Filtering Options

Users can filter cars by:
- **Search** - Type car name or keyword
- **Category** - SUV, Sedan, Luxury, etc.
- **Transmission** - Automatic or Manual

### Single Car Page

Each car has a dedicated page showing:
- Full image gallery with slider
- Complete specifications
- Pricing details
- Features list
- Location with Google Maps link
- Book Now button

### Booking Process

1. **Select Car** - Click Book Now on desired car
2. **Fill Information** - Complete customer details
3. **Select Dates** - Pickup and return dates
4. **Add Services** - Choose extra services (optional)
5. **Review Price** - Real-time price calculation
6. **Submit** - Complete booking request

### Price Calculation Formula

```
Total Price = (Daily Rate × Number of Days) + Extra Services Total
```

## 🔌 API & Hooks

### Actions

```php
// After booking is created
do_action('dcbm_after_booking_created', $booking_id, $booking_data);

// Before sending emails
do_action('dcbm_before_send_emails', $booking_id, $booking_data);

// After car status update
do_action('dcbm_car_status_updated', $car_id, $old_status, $new_status);
```

### Filters

```php
// Modify car query arguments
add_filter('dcbm_car_query_args', function($args) {
    $args['posts_per_page'] = 20;
    return $args;
});

// Modify booking email subject
add_filter('dcbm_email_subject', function($subject, $type) {
    return 'Custom: ' . $subject;
}, 10, 2);

// Modify price calculation
add_filter('dcbm_calculate_total_price', function($total, $car_price, $days, $services) {
    return $total;
}, 10, 4);
```

### Database Tables

```sql
-- Bookings table
wp_dcbm_bookings

-- Extra services table
wp_dcbm_extra_services
```

## ❓ FAQ

### Q: Is this plugin compatible with Elementor?
**A:** Yes! The plugin is built to work with Elementor and other page builders. You can use the shortcode blocks.

### Q: Can I customize the email templates?
**A:** Yes, email templates are located in `includes/class-email-handler.php`. You can override them by copying to your theme.

### Q: How do I change the currency?
**A:** Currency is set to PKR by default. You can modify it in the frontend CSS and email templates.

### Q: Can customers cancel their bookings?
**A:** Currently, cancellations are handled by admin. Contact support for cancellation requests.

### Q: Does it support multiple pickup locations?
**A:** Yes, each car can have its own pickup location set in the car settings.

### Q: How are extra services calculated?
**A:** Extra services are added to the total price when selected. Free services don't affect price.

### Q: Is the plugin mobile responsive?
**A:** Yes, fully responsive and tested on all mobile devices.

### Q: Can I translate the plugin?
**A:** Yes, the plugin is translation-ready. Use Poedit to translate the .pot file.

## 🐛 Troubleshooting

### Common Issues & Solutions

**Issue:** Shortcode not displaying anything
**Solution:** 
- Check if plugin is activated
- Ensure you have published cars
- Clear WordPress cache

**Issue:** Book Now button not working
**Solution:**
- Check browser console for errors
- Ensure jQuery is loaded
- Clear browser cache

**Issue:** Emails not sending
**Solution:**
- Configure WP Mail SMTP plugin
- Check spam folder
- Verify email settings in WordPress

**Issue:** Images not displaying
**Solution:**
- Regenerate thumbnails
- Check file permissions
- Verify featured image is set

## 📞 Support

### Getting Help

- **Documentation:** [Read full documentation](#)
- **Issues:** [GitHub Issues](https://github.com/sardaralikhamosh/digicells-car-booking-manager/issues)
- **Email:** support@hamaribooking.com

### Contributing

We welcome contributions! Please:

1. Fork the repository
2. Create a feature branch
3. Submit a pull request

### Reporting Security Issues

Please report security vulnerabilities to: security@hamaribooking.com

## 📝 Changelog

### Version 1.0.0 (2024-01-15)

**Initial Release**
- ✅ Custom post type for cars with complete meta boxes
- ✅ Car categories taxonomy with default terms
- ✅ Extra services management system
- ✅ Frontend car listing shortcode with AJAX filters
- ✅ Single car page with gallery and details
- ✅ Booking system with modal form
- ✅ Real-time price calculation
- ✅ Admin booking management dashboard
- ✅ Email notifications for admin and customers
- ✅ Responsive design matching Hamari Booking
- ✅ Complete documentation

### Upcoming Features (Roadmap)

**Version 1.1.0**
- Online payment integration (Stripe, JazzCash, EasyPaisa)
- Availability calendar
- Seasonal pricing

**Version 1.2.0**
- Driver management system
- Multi-vendor support
- Customer accounts and booking history

**Version 1.3.0**
- Reviews and ratings system
- Promo codes and discounts
- Advanced reporting

## 🙏 Credits

**Developer:** Digicells  
**Design:** Hamari Booking Team  
**Icons:** FontAwesome, Emoji  
**Fonts:** Google Fonts (Poppins)

## 📄 License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## ⭐ Support the Project

If you find this plugin useful, please:
- Star the repository on GitHub ⭐
- Share with others in the community
- Contribute to the project

---

**Developed with ❤️ by Digicells for Hamari Booking**

*Last Updated: January 2024*
```

---

# GitHub Repository Information

## Repository Settings

### Repository Name
```
digicells-car-booking-manager
```

### Repository Title
```
Digicells Car Booking Manager - Professional Car Rental WordPress Plugin
```

### Repository Description (Short - 350 chars max)
```
🚗 Complete car rental and booking management WordPress plugin. Features car inventory, booking system, price calculator, extra services, email notifications, and admin dashboard. Perfect for car rental agencies.
```

### Website
```
https://hamaribooking.com
```

### Topics (Tags)
```
wordpress-plugin, car-rental, booking-system, car-booking, wordpress, php, ajax, responsive, seo-friendly, elementor-compatible, car-management, rental-management
```

### Repository Visibility
```
Public
```

### Default Branch
```
main
```

### .gitignore Template
```
WordPress
```

### License
```
GPL-2.0 License
```

---

## GitHub Workflow File (Optional - GitHub Actions)

Create `.github/workflows/deploy.yml`:

```yaml
name: Deploy to WordPress.org

on:
  push:
    tags:
      - '*'

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      
      - name: Build Plugin
        run: |
          zip -r digicells-car-booking-manager.zip . -x "*.git*" ".github/*" "*.md"
      
      - name: Create Release
        uses: softprops/action-gh-release@v1
        with:
          files: digicells-car-booking-manager.zip
          generate_release_notes: true
```

---

## README Badges (Add to README.md title)

Replace the badges section in README.md with:

```markdown
[![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/yourusername/digicells-car-booking-manager/releases)
[![WordPress](https://img.shields.io/badge/WordPress-5.0+-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4+-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-GPLv2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Downloads](https://img.shields.io/badge/downloads-1k+-brightgreen.svg)](https://github.com/yourusername/digicells-car-booking-manager)
[![Stars](https://img.shields.io/github/stars/yourusername/digicells-car-booking-manager)](https://github.com/yourusername/digicells-car-booking-manager/stargazers)
[![Issues](https://img.shields.io/github/issues/yourusername/digicells-car-booking-manager)](https://github.com/yourusername/digicells-car-booking-manager/issues)
```

---

## Screenshots (Add to GitHub README)

Create a `screenshots` folder with these images:

```
screenshots/
├── 1-admin-dashboard.png
├── 2-add-car.png
├── 3-car-listing.png
├── 4-single-car.png
├── 5-booking-modal.png
├── 6-admin-bookings.png
├── 7-extra-services.png
└── 8-email-notification.png
```

### Screenshot Captions

1. **Admin Dashboard** - Complete car inventory management
2. **Add New Car** - Detailed car information and specifications
3. **Frontend Car Listing** - Professional grid layout with filters
4. **Single Car Page** - Complete car details with gallery
5. **Booking Modal** - Customer information and price calculation
6. **Admin Bookings** - Manage all customer bookings
7. **Extra Services** - Manage add-on services
8. **Email Notifications** - Automatic booking confirmations

---

## GitHub Pages Documentation Site

Create `docs/index.md` for GitHub Pages:

```markdown
# Digicells Car Booking Manager Documentation

Welcome to the official documentation for Digicells Car Booking Manager WordPress plugin.

## Quick Navigation

- [Installation Guide](installation.md)
- [User Manual](user-manual.md)
- [Developer Guide](developer-guide.md)
- [API Reference](api-reference.md)
- [FAQ](faq.md)

## Getting Started

1. [Install the plugin](installation.md)
2. [Add your first car](user-manual.md#adding-a-car)
3. [Configure extra services](user-manual.md#extra-services)
4. [Display cars using shortcodes](user-manual.md#shortcodes)
5. [Manage bookings](user-manual.md#managing-bookings)

## Support

- [Report an issue](https://github.com/yourusername/digicells-car-booking-manager/issues)
- [Email support](mailto:support@hamaribooking.com)
```

---

## Funding (GitHub Sponsors)

Add `.github/FUNDING.yml`:

```yaml
github: [yourusername]
custom: ['https://hamaribooking.com/donate']
```

---

## Code of Conduct

Create `CODE_OF_CONDUCT.md`:

```markdown
# Contributor Covenant Code of Conduct

## Our Pledge

We as members, contributors, and leaders pledge to make participation in our
community a harassment-free experience for everyone...

[Full Code of Conduct text here]
```

---

## Contributing Guide

Create `CONTRIBUTING.md`:

```markdown
# Contributing to Digicells Car Booking Manager

We love your input! We want to make contributing as easy and transparent as possible.

## Development Process

1. Fork the repo
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## Coding Standards

- Follow WordPress Coding Standards
- Use OOP PHP
- Document all functions
- Add proper error handling
- Test with WordPress 5.0+

## Reporting Bugs

- Use GitHub Issues
- Describe the issue in detail
- Include WordPress version and PHP version
- Add screenshots if applicable
```

---

## GitHub Repository Checklist

Before publishing your repository, ensure you have:

- [x] README.md file with complete documentation
- [x] .gitignore file
- [x] LICENSE file (GPL-2.0)
- [x] Plugin files in root directory
- [x] Proper file structure
- [x] Screenshots folder with images
- [x] Code of conduct
- [x] Contributing guidelines
- [x] Issues templates
- [x] Pull request template
- [x] Funding configuration
- [x] GitHub Pages documentation (optional)

---

## Commands to Push to GitHub

```bash
# Initialize git repository
cd digicells-car-booking-manager
git init

# Add all files
git add .

# Create initial commit
git commit -m "Initial commit: Digicells Car Booking Manager v1.0.0"

# Add remote repository
git remote add origin https://github.com/yourusername/digicells-car-booking-manager.git

# Push to GitHub
git branch -M main
git push -u origin main

# Create and push tag for version
git tag -a v1.0.0 -m "Version 1.0.0 - Initial Release"
git push origin v1.0.0
```

---

This complete documentation package gives you a professional presence on GitHub with proper documentation, help resources, and community guidelines!
