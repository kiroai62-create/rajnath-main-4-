# Astro Rajnath Jha — complete platform

Website, admin panel, mobile API and Android app for **astrorajnathjha.com**.
This repository contains only the files needed to run and rebuild the platform.

| Folder | What it is |
|---|---|
| `backend/` | Website, customer account, **admin panel** and API `/api/v1` (Laravel 13, PHP 8.4) |
| `engine/` | Swiss Ephemeris calculator used for kundli, panchang, gochar and rashifal (built automatically) |
| `deploy/` | Server setup: Docker Compose (Caddy with free HTTPS, PHP, MariaDB, Redis, queue, scheduler), install, update, backup and restore scripts |
| `mobile/` | Android app source (Flutter) |
| `android-release/` | **Signed app 1.9.0** (`.apk` to install, `.aab` for Google Play) and the **encrypted signing key** |
| `docs/` | Server, email/SMS, LiveKit and release guides; API and calculation notes; security notes |

## Put the website on AWS (EC2)

1. **Create the server.** AWS › EC2 › Launch instance:
   - Ubuntu Server 24.04 LTS, type **t3.medium** or larger (2 vCPU, 4 GB RAM), 40 GB disk.
   - Security group: allow **22** (SSH, your IP only), **80** and **443** (everyone).
   - For in-app video calls also allow TCP **7881** and UDP **7882**.
   - Attach an **Elastic IP** so the address never changes.
2. **Point the domain.** At your domain registrar add A records `astrorajnathjha.com` and `www` → the Elastic IP
   (and `rtc` if you use in-app calls). Wait until they resolve.
3. **Install Docker** on the server:
   ```
   sudo apt update && sudo apt install -y docker.io docker-compose-v2 unzip curl
   sudo usermod -aG docker $USER && newgrp docker
   ```
4. **Upload the code.** On GitHub: **Code › Download ZIP**. Copy it to the server and unpack:
   ```
   scp rajnath-main.zip ubuntu@<elastic-ip>:~
   ssh ubuntu@<elastic-ip>
   unzip rajnath-main.zip && cd rajnath-main
   chmod +x deploy/*.sh engine/build.sh
   ```
5. **Install (one command).** It creates the passwords, builds everything, sets up the database with all
   services, store, articles, reviews, gallery and office hours, and starts the site with HTTPS:
   ```
   deploy/install.sh astrorajnathjha.com owner@your-email.com "Acharya Dr. Rajnath Jha"
   ```
   It prints a one-time link (valid 30 minutes) to create the owner login with an authenticator app.
   **Save a copy of `deploy/.env.production`** (it holds `APP_KEY`, which encrypts every key you enter in the admin).
6. **Enter the keys in the admin** (`https://astrorajnathjha.com/admin`):
   - Email & SMS (SMTP, Twilio / Fast2SMS) → press *Send test*
   - Payments setup (Razorpay keys, GSTIN, Shiprocket, RazorpayX)
   - AI assistant (Groq / Gemini / Cloudflare / OpenAI key) and switch it on
   - Push notifications (Firebase), Calling (LiveKit / Zoom / Google Meet)
7. **Before going live:** publish the Refund and Shipping policies (Admin › Legal pages), set real stock
   (Admin › Store), and check the office hours (Admin › Bookings).
8. **Daily backup:** add the cron line printed at the end of the install (`deploy/backup.sh`); copy
   `/opt/arj-backups` off the server regularly (for example to S3).

**Updating later:** download the new ZIP, unpack it over the old folder (keep `deploy/.env.production`), then run
`deploy/deploy.sh`. It backs up, rebuilds, migrates and restarts. More detail: `docs/ops/vps-deployment.md`.

## Android app

- **Install on a phone:** `android-release/astro-rajnath-jha-1.9.0-release.apk` (remove any older build first).
- **Google Play:** upload `android-release/astro-rajnath-jha-1.9.0-release.aab` and turn on *Play App Signing*.
- **Signing key:** `android-release/signing-key-ENCRYPTED.zip` (AES-256). Open it with 7-Zip or WinRAR using the
  password given to the owner privately. Inside: `arj-release-2026-11.jks`, `store-password.txt`, `key.properties`.
  Keep a copy in a password manager and on a pen drive — without it the app can never be updated on Play.
  Certificate SHA-1 `1B:D2:05:9F:AF:C2:05:3B:E5:7A:4D:D6:8D:95:F8:D9:97:41:FA:0A`,
  SHA-256 `EE:08:3B:83:FA:4F:66:86:10:8E:55:6F:19:9D:80:8A:C2:D5:92:17:69:A7:74:0B:D4:96:A7:09:E1:04:79:38` (add both in Firebase).
- **Rebuild:** put `key.properties` (from the zip, with the correct `storeFile` path) in `mobile/android/`, then
  ```
  cd mobile
  flutter build apk --release --dart-define=API_BASE=https://astrorajnathjha.com/api/v1
  flutter build appbundle --release --dart-define=API_BASE=https://astrorajnathjha.com/api/v1
  ```

## Developer setup (optional)
```
engine/build.sh
cd backend && composer install && npm ci && npm run build && cp .env.example .env && php artisan key:generate
php artisan migrate --seed && php artisan test
```

## Licence
AGPL-3.0-or-later (Swiss Ephemeris is used under its AGPL option). The website footer links to the source code
(Admin › Settings › Source code link); keep it pointing to a copy of this code if this repository is removed.
