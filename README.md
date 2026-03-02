# NeptuneWare CRM

Multi-tenant CRM built with Laravel. Each workspace (tenant) runs under:

`/t/{tenant}`

NeptuneWare CRM includes leads, contacts, companies, deals, quotes, sales orders, invoices, payments, credit notes, statements, exports, and tenant branding (logo).

---

## Table of contents

- [Requirements](#requirements)
- [Local setup](#local-setup)
- [Environment variables](#environment-variables)
- [Multi-tenant routing](#multi-tenant-routing)
- [Roles & permissions](#roles--permissions)
- [Storage (Cloudflare R2 / S3)](#storage-cloudflare-r2--s3)
- [PDF generation](#pdf-generation)
- [Captcha (Cloudflare Turnstile)](#captcha-cloudflare-turnstile)
- [Billing (Paystack)](#billing-paystack)
- [Seeds & bootstrapping](#seeds--bootstrapping)
- [Deployment notes](#deployment-notes)
- [Troubleshooting](#troubleshooting)
- [License](#license)

---

## Requirements

- PHP 8.2+
- Composer
- MySQL 8+
- Node.js 18+ (for Vite assets)
- Redis (optional, if you switch cache/queue drivers)

---

## Local setup

```bash
git clone <repo-url>
cd neptunewarecrm

composer install
cp .env.example .env
php artisan key:generate

# DB
php artisan migrate

# Seed baseline data (roles, countries, default pipeline templates, etc.)
php artisan db:seed

# Frontend assets
npm install
npm run dev

php artisan serve
