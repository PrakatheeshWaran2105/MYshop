# KGF Mens Wear

A modern PHP + MySQL e-commerce starter with reusable UI components, responsive navigation, authentication, product listing, session cart, checkout order creation and an admin dashboard starter.

## Requirements

- XAMPP or PHP 8+
- MySQL
- Composer

## Installation

1. Copy the folder to:

   `C:\xampp\htdocs\kgf-mens-wear`

2. Start Apache and MySQL in XAMPP.

3. Open phpMyAdmin and import:

   `database/kgf_mens_wear.sql`

4. Copy:

   `.env.example` to `.env`

5. Open PowerShell inside the project folder and run:

   `composer install`

6. Visit:

   `http://localhost/kgf-mens-wear/`

## Main pages

- Home: `/index.php`
- Shop: `/shop.php`
- Product: `/product.php?id=1`
- Cart: `/cart.php`
- Register: `/register.php`
- Login: `/login.php`
- Checkout: `/checkout.php`
- Admin login: `/admin/login.php`

## Advanced PHP libraries included

- PHPMailer: OTP, password reset, welcome and order emails
- Guzzle: payment, shipping, AI and other APIs
- Dotenv: secure environment configuration
- Dompdf: invoice PDF generation
- Intervention Image: product image resize and optimization

## Recommended next modules

1. Admin product CRUD
2. Razorpay payment order creation and signature verification
3. PHPMailer welcome and order confirmation
4. Wishlist table and AJAX actions
5. Product reviews
6. Coupons
7. Order tracking
8. PDF invoice
9. Image upload and resizing
10. Security testing and deployment
