Berikut prompt yang lebih sederhana, tetapi tetap menghasilkan dashboard yang modern dan aman.

# Prompt AI - Laravel Dashboard, Login & Security

Bertindaklah sebagai **Senior Laravel Developer**.

Buat sebuah **Admin Dashboard** menggunakan **Laravel 13** dengan tampilan modern, bersih, responsif, dan mengikuti best practice Laravel.

## Teknologi

* Laravel 13
* PHP 8.4+
* Blade Template
* Tailwind CSS
* Alpine.js
* Vite
* Laravel Breeze (Authentication)

## Design

Gunakan desain minimalis seperti:

* Vercel
* Linear
* GitHub
* Stripe Dashboard

Karakteristik:

* Clean
* Modern
* Responsive
* Dark & Light Mode
* Soft Shadow
* Rounded Corner
* Smooth Animation

## Halaman

### Login

* Email
* Password
* Remember Me
* Show/Hide Password
* Forgot Password
* Login Validation
* Loading Button
* Error Message
* Responsive Design

### Dashboard

Tampilkan:

* Welcome Card
* 4 Statistic Cards
* Recent Activity
* Quick Actions

## Layout

* Sidebar
* Top Navbar
* Breadcrumb
* User Profile Dropdown
* Logout

## Security

Ikuti standar keamanan Laravel:

* Authentication menggunakan Laravel Breeze
* Authorization menggunakan Middleware
* CSRF Protection
* XSS Protection
* SQL Injection Protection (Eloquent/Query Builder)
* Password Hashing
* Form Request Validation
* Session Authentication
* Secure Cookies
* Rate Limiting pada Login
* Authorization untuk setiap halaman admin
* Jangan pernah hardcode password atau secret key

## UI Components

Gunakan komponen yang dapat digunakan kembali:

* Button
* Card
* Input
* Table
* Badge
* Alert
* Modal
* Dropdown

## Loading

Gunakan:

* Skeleton Loading
* Loading Button

Hindari spinner fullscreen kecuali benar-benar diperlukan.

## Coding Standard

* Clean Code
* SOLID Principle
* DRY
* Reusable Blade Components
* Resource Controller
* Form Request Validation
* Named Route
* Eloquent ORM
* Ikuti Laravel Best Practice

## Output

Berikan implementasi secara bertahap beserta kode lengkap, meliputi:

1. Struktur folder.
2. Layout utama.
3. Halaman Login.
4. Halaman Dashboard.
5. Routing.
6. Controller.
7. Blade Components.
8. CSS/Tailwind.
9. Middleware & Security.
10. Langkah instalasi hingga aplikasi dapat dijalankan.

Pastikan seluruh kode siap digunakan pada Laravel 13 tanpa memerlukan perubahan besar.

Jika proyek ini akan berkembang menjadi **Billing ISP**, buat struktur dashboard yang mudah dikembangkan sehingga modul seperti **Customer**, **Router**, **PPP Secret**, **PPP Profile**, **Invoice**, dan **Payment** dapat ditambahkan di kemudian hari tanpa perlu mengubah arsitektur utama.
