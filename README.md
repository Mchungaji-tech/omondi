# Beacon Gospel Centre — Full-Stack Procedural PHP Project

A complete, production-ready **Procedural PHP + MySQL** full-stack web application for **Beacon Gospel Centre, Eldoret** (Rev. Samuel K. Langat).

---

## 🌟 Key Highlights & Architecture

- **Pure Procedural PHP**: Built strictly using procedural paradigms (`mysqli_*`, procedural session handlers, modular includes, prepared statements, and procedural helper functions). No complex OOP frameworks or third-party overhead.
- **Relational MySQL Database**: 17 structured database tables with referential integrity, indexes, and comprehensive default seed data.
- **Security & Integrity**:
  - Parameterized prepared SQL statements across all database transactions.
  - Password hashing with Bcrypt (`password_hash` / `password_verify`).
  - Multi-Factor Authentication (TOTP authenticator simulation & QR enrollment).
  - Brute-force lockout protection (5 failed attempts locks for 5 minutes).
  - Cross-Site Request Forgery (CSRF) token validation.
  - Comprehensive audit trail recording all administrative and public actions.
- **Rich Editorial Aesthetics**: Curated paper palette (`#F7F2E9`, `#EFE6D6`), rich burgundy/wine (`#8C1D2F`), gold accents (`#C99B3F`), and typography (`Anton`, `Newsreader`, `IBM Plex Mono`).

---

## 📁 Directory Structure

```
c:\xampp\htdocs\Morris\
├── config/
│   ├── config.php              # Global configuration, constants, session init
│   ├── db.php                  # Procedural MySQLi connection
│   └── functions.php           # Procedural helpers, sanitizers, CSRF, TOTP, auth
│
├── includes/
│   ├── header.php              # Public site header, navigation, live clock & mobile menu
│   └── footer.php              # Public site footer & church contact info
│
├── assets/
│   ├── css/
│   │   ├── main.css            # Public portal CSS design system & responsive layout
│   │   └── admin.css           # Admin dashboard & console styles
│   └── js/
│       ├── main.js             # Public audio player, live chat polling, timers, AJAX forms
│       └── admin.js            # Admin modal handlers, QR canvas, TOTP live ticker
│
├── api/
│   ├── chat.php                # Procedural API for reading & posting live stream chat
│   ├── prayer.php              # Procedural API for submitting prayer requests
│   ├── invite.php              # Procedural API for submitting ministry invitations
│   └── pledge.php              # Procedural API for submitting project partner pledges
│
├── admin/
│   ├── includes/
│   │   ├── admin_header.php    # Admin sidebar navigation & auth enforcement
│   │   └── admin_footer.php    # Admin modal container & script includes
│   ├── login.php               # Admin login with MFA (TOTP / QR) & lockout protection
│   ├── logout.php              # Admin session termination & audit log
│   ├── index.php               # Dashboard overview (metrics, live stream toggle, audit logs)
│   ├── profile.php             # Pastor profile & hero banner manager
│   ├── giving.php              # Giving details manager (M-Pesa, Bank, SWIFT)
│   ├── sermons.php             # Sermon library CRUD manager
│   ├── live.php                # Live stream control, schedule & chat moderation
│   ├── ministries.php          # Ministries showcase CRUD manager
│   ├── schedule.php            # Pastor's weekly rhythm schedule CRUD
│   ├── gallery.php             # Field photos gallery CRUD manager
│   ├── testimonies.php         # Flock testimonies CRUD manager
│   ├── events.php              # Upcoming events calendar CRUD manager
│   ├── programs.php            # Support programmes & fund goals manager
│   ├── projects.php            # Capital projects & proposal budget line manager
│   ├── commitments.php         # Partner pledges & commitment registry
│   ├── prayers.php             # Confidential prayer request inbox & status manager
│   ├── invites.php             # Preaching invitations inbox & confirmation manager
│   ├── security.php            # Security policies, MFA settings & audit trail viewer
│   └── proposal_view.php       # Printable & interactive partner proposal viewer
│
├── schema.sql                  # Database schema definition & initial seed data
├── install.php                 # Automated one-click installer script
├── proposal.php                # Public partner proposal viewer & pledge form
├── index.php                   # Public dynamic homepage
└── README.md                   # Documentation
```

---

## 🚀 Quick Setup & Installation

### Option 1: Web Installer (Recommended)
1. Open your browser and navigate to:
   ```
   http://localhost/Morris/install.php
   ```
2. Click **"Initialize Database & Seed Data ✦"**.
3. The installer will automatically create the `bgc_church` database, run all table schemas, seed initial records, and set up the admin account.

### Option 2: CLI Command
Run in PowerShell / Terminal:
```bash
c:\xampp\php\php.exe c:\xampp\htdocs\Morris\install.php
```

---

## 🔐 Default Administrator Credentials

- **Login URL**: `http://localhost/Morris/admin/login.php`
- **Username**: `admin` (or `office@revlangat.or.ke`)
- **Password**: `Admin@2026!`
- **MFA Method**: Step 2 displays a live simulated TOTP code or allows one-click approval via QR code enrollment.

---

## 💻 Public URLs & Features

- **Public Homepage**: `http://localhost/Morris/`
  - Dynamic sermon library with interactive audio player & category filters
  - Live stream broadcast viewer with real-time dynamic chat and live viewers counter
  - Ministry showcase and field moments photo gallery
  - Weekly pastoral rhythm schedule
  - Online prayer request form with unique reference code generation (`PRAY-2026-XXXX`)
  - Preaching invitation booking form (`INV-2026-XXXX`)
  - Support programmes with M-Pesa account codes & instant copy buttons
- **Partner Proposals**: `http://localhost/Morris/proposal.php`
  - Line-item budget breakdowns, project timelines, and accountability framework
  - Online partnership commitment & pledge submission (`PROP-2026-XXXX`)
