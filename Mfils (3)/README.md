# 🪡 SilkRoute MLM – Saree Business Referral System

A complete PHP + MySQL multi-level marketing system for a saree business supporting **7-level referral commissions**.

---

## 📁 File Structure

```
mlm_saree/
├── index.php             ← Entry point (redirects)
├── login.php             ← Login page
├── register.php          ← Registration with referral code
├── logout.php            ← Session destroy
├── dashboard.php         ← User dashboard with stats
├── shop.php              ← Product catalogue + purchase trigger
├── network.php           ← Referral tree visualization
├── commissions.php       ← Commission transaction history
├── wallet.php            ← Wallet balance & credits
├── schema.sql            ← Full database schema + seed data
└── includes/
    ├── config.php        ← DB credentials, constants
    ├── functions.php     ← All business logic (PDO)
    ├── header.php        ← Global HTML header + CSS
    └── footer.php        ← Global HTML footer
```

---

## 🚀 Setup Instructions

### 1. Database
```sql
-- Import schema in phpMyAdmin or terminal:
mysql -u root -p < schema.sql
```

### 2. Configure DB credentials
Edit `includes/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'mlm_saree');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
define('APP_URL',  'http://localhost/mlm_saree');
```

### 3. Deploy
Place the `mlm_saree/` folder in your web root (e.g., `htdocs/` or `www/`).

### 4. Open in browser
```
http://localhost/mlm_saree/
```

---

## 🔐 Demo Accounts

All accounts use password: **`password`**

| Username   | Email                    | Level    |
|------------|--------------------------|----------|
| jayashree  | jayashree@example.com    | Root     |
| sritam     | sritam@example.com       | Level 1  |
| rashmita   | rashmita@example.com     | Level 2  |
| user3      | user3@example.com        | Level 3  |
| user4      | user4@example.com        | Level 4  |
| user5      | user5@example.com        | Level 5  |
| user6      | user6@example.com        | Level 6  |
| user7      | user7@example.com        | Level 7  |

---

## 💸 Commission Structure

When `user7` buys a ₹4,500 Kanjivaram saree:

| Level | Beneficiary | Rate  | Commission |
|-------|-------------|-------|-----------|
| 1     | user6       | 10%   | ₹450.00   |
| 2     | user5       |  5%   | ₹225.00   |
| 3     | user4       |  3%   | ₹135.00   |
| 4     | user3       |  2%   | ₹90.00    |
| 5     | rashmita    |  1%   | ₹45.00    |
| 6     | sritam      | 0.5%  | ₹22.50    |
| 7     | jayashree   | 0.5%  | ₹22.50    |

**Total commissions distributed: ₹990.00 (22%)**

---

## ✨ Features

- **User Registration** – with optional referral link (`?ref=CODE`)
- **Login / Logout** – PHP sessions with bcrypt passwords
- **Dashboard** – wallet, total commissions, network size, level breakdown
- **Shop** – product catalogue; buying triggers automatic commission distribution
- **Network Tree** – recursive visual tree of all downline members up to 7 levels
- **Commissions Page** – paginated transaction history with level badges
- **Wallet Page** – balance + credit history

---

## 🛠 Tech Stack

- **Backend**: PHP 7.4+ (PDO, procedural style)
- **Database**: MySQL 5.7+ / MariaDB
- **Frontend**: Pure HTML/CSS (no frameworks), Google Fonts
- **Auth**: PHP sessions + `password_hash()` / `password_verify()`

---

## 🔒 Security Notes (for production)

1. Move `includes/config.php` above web root or protect with `.htaccess`
2. Use HTTPS
3. Add CSRF tokens to forms
4. Rate-limit login attempts
5. Validate and sanitize all user inputs (already using PDO prepared statements)
