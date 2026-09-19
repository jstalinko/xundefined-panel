# 🛒 Xundefined Digital Products Store - Telegram Bot

Telegram Bot interface built with **Node.js**, **npm**, and **Telegraf**, integrated with the **Laravel** API backend to sell digital products (website scripts).

---

## 🚀 Features

- **Auto Registration (`/start` or new chat)**:
  - Intercepts incoming messages/callbacks.
  - Automatically queries the Laravel backend at `${APP_URL}/api/telegram/init`.
  - Creates the user in the database if new, or fetches current user details & balance if already registered.
  - Displays user profile summary (Name, Telegram ID, Username, Role, Balance, Orders count, Registered domains).
- **Navigation Menus**:
  - **🛍️ Products**: Browse available website scripts, view details, categories, versions, live preview links, and purchase.
  - **📥 Download**: Instant access to purchased scripts, downloadable archive links, and unique license keys.
  - **📋 Orders**: Complete purchase history with order numbers, amount, status, and dates.
  - **📊 Activities**: Real-time audit log of account activities (registration, purchases, domain bindings, top-ups).
  - **🌐 Domains**: Manage and bind authorized domains to website script licenses (`/adddomain example.com` or inline button).
  - **👤 Profile**: Comprehensive profile statistics, member date, total spent, and balance.
  - **💳 Balance**: Check balance, view payment guides (Crypto, PayPal, Bank), and test top-up.

---

## 📁 Directory Structure

```
xundefined-panel/
├── .env                              <-- Laravel .env (reads APP_URL, DB, TELEGRAM_BOT_TOKEN)
├── app/
│   ├── Http/Controllers/
│   │   └── TelegramApiController.php <-- API endpoints for bot
│   └── Models/
│       ├── User.php
│       ├── Product.php
│       ├── Order.php
│       ├── Domain.php
│       └── Activity.php
├── routes/
│   └── api.php                       <-- /api/telegram/* routes
└── telegram-bot/
    ├── package.json
    ├── .env.example
    ├── test-integration.js            <-- End-to-end integration test
    └── src/
        ├── index.js                  <-- Bot entrypoint & event routing
        ├── config.js                 <-- Loads ../.env & APP_URL resolution
        ├── api.js                    <-- Axios client communicating with Laravel API
        ├── keyboards.js              <-- Persistent reply & inline keyboards
        ├── middleware/
        │   └── auth.js               <-- Auto-registration middleware
        └── handlers/
            ├── start.js              <-- /start handler & account summary
            ├── products.js           <-- Catalog & purchase flow
            ├── download.js           <-- Purchased script downloads & licenses
            ├── orders.js             <-- Order history
            ├── activities.js         <-- Activity logs
            ├── domains.js            <-- Domain license management
            ├── profile.js            <-- Profile details
            └── balance.js            <-- Balance & deposit instructions
```

---

## ⚙️ Configuration & Setup

### 1. Set your Telegram Bot Token
You can put your Telegram Bot Token from [@BotFather](https://t.me/botfather) in either:
- The parent Laravel `.env` file (`../.env`):
  ```env
  TELEGRAM_BOT_TOKEN=123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ
  ```
- Or in `telegram-bot/.env`:
  ```env
  BOT_TOKEN=123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ
  ```

### 2. Run Laravel Backend
Ensure your Laravel server is running:
```bash
php artisan serve --port=8000
```
*(The bot automatically reads `APP_URL` from `../.env`, which defaults to `http://localhost:8000`)*.

### 3. Run the Telegram Bot
Inside `telegram-bot/`:
```bash
npm start
```
Or for development with auto-reload:
```bash
npm run dev
```

### 4. Run Integration Tests
To test the complete API flow between Node.js and Laravel without needing a live Telegram connection:
```bash
node test-integration.js
```

---

## 📡 API Endpoints Implemented in Laravel

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/telegram/init` | Auto-registers or initializes Telegram user & returns balance |
| `GET` | `/api/telegram/products` | Lists active digital website script products |
| `GET` | `/api/telegram/products/{id}` | Fetches detailed product information |
| `POST` | `/api/telegram/orders/buy` | Deducts balance and purchases digital script with license key |
| `GET` | `/api/telegram/downloads` | Lists purchased script downloads and licenses |
| `GET` | `/api/telegram/orders` | Retrieves user's complete order history |
| `GET` | `/api/telegram/activities` | Retrieves audit trail of recent activities |
| `GET` | `/api/telegram/domains` | Lists user's authorized website domains |
| `POST` | `/api/telegram/domains/add` | Binds a new domain to script licenses |
| `POST` | `/api/telegram/domains/delete` | Removes domain authorization |
| `GET` | `/api/telegram/profile` | Returns full user stats (orders, spent, domains, balance) |
| `GET` | `/api/telegram/balance` | Returns balance info and top-up guide |
| `POST` | `/api/telegram/balance/topup-demo` | Adds demo balance for instant testing |
