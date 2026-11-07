# Internal security review — 28 Oct 2026

This is an **internal, automated and self-review**, not an independent penetration test.
An independent review is still required before production acceptance (owner responsibility).
Baseline: OWASP ASVS 4.0 Level 2 (selected controls below).

Updated 31 Oct 2026: error pages, request IDs, extra headers, session cookie defaults, security.txt.

## Verified by automated tests (`backend/tests`, 240 passing)
| Area | Control | Test |
|---|---|---|
| Authentication | Staff: password + TOTP, replay-blocked codes, rate limits, no default credentials, one-time bootstrap link | `Foundation/StaffAuthTest`, `OwnerBootstrapTest` |
| | Customers: OTP stored as HMAC, single use, 5-try lockout, per-destination and IP limits, no account enumeration | `Accounts/CustomerOtpTest` |
| Sessions/tokens | Suspension revokes sessions and API tokens; per-device 90-day tokens; logout invalidates | `AuthorizationTest`, `ApiV1Test` |
| Access control | Default-deny role matrix; step-up for sensitive changes; maker/checker refunds; owner-only provider and payment settings | `AuthorizationTest`, `PaymentsTest`, `ProvidersAdminTest` |
| | **Route audit:** every `/admin` route has auth + staff (MFA) middleware; sensitive mutations have step-up; every account/checkout route requires a customer; customer API needs token + ability | `Security/RouteAuditTest` |
| IDOR | Other users' birth profiles, orders, receipts, palm readings and photos return 404 on web and API; ULID references, no sequential IDs | `BirthProfileAndTimeTest`, `StoreAndPalmTest`, `PaymentsTest` |
| CSRF | All web mutations protected; the only exceptions are the three signed provider webhooks (`/webhooks/razorpay`, `/webhooks/razorpayx`, `/webhooks/courier-tracking`) | `RouteAuditTest` |
| Payments | Server-side signature + amount verification, webhook HMAC, event replay protection, live mode off by default, idempotent orders, integer paise, double-entry ledger invariants, concurrent wallet spends | `PaymentsTest`, `WalletConcurrencyTest` |
| Inventory | Stock reserved under row lock (no overselling), released on cancel or expiry | `StoreAndPalmTest` |
| Uploads | Images decoded and re-encoded by GD (EXIF/GPS stripped, scripts/polyglots rejected); size and dimension limits; palm photos on the private disk, streamed only to the owner or MFA staff with `reports.fulfil`, viewing audited, auto-deleted after the retention period | `StoreAndPalmTest` |
| SSRF | SMTP host must resolve to public IPs (private, loopback, link-local, CGNAT and reserved ranges blocked) | `ProvidersAdminTest` |
| Secrets | Provider secrets encrypted at rest, masked in UI, never written to audit or logs | `AuditAndSettingsTest`, `ProvidersAdminTest` |
| Logging | PII and secret redaction; hash-chained tamper-evident audit log | `AuditAndSettingsTest`, `AuditConcurrencyTest` |
| Headers | CSP with nonce and no `unsafe-eval`, frame-ancestors none, `upgrade-insecure-requests` on TLS, nosniff, HSTS on TLS, no-store on private pages, `Cross-Origin-Resource-Policy: same-site`, `X-Permitted-Cross-Domain-Policies: none`, restrictive Permissions-Policy, no `X-Powered-By` | `PlatformTest`, `ErrorPagesTest` |
| Error handling | Branded 401/403/404/413/419/429/500/503 pages plus 4xx/5xx fallbacks; no exception text, stack or SQL is shown; "Go back" only to same-site referrers (no open redirect); API errors stay JSON | `ErrorPagesTest` |
| Traceability | Every request gets an `X-Request-Id` (incoming value kept only if it is a plain 8–64 character token), added to all log lines; 5xx pages show it as a support reference | `ErrorPagesTest` |
| Session cookies | `Secure` by default when `APP_ENV=production`, HttpOnly, SameSite=Lax; production template encrypts session data (`SESSION_ENCRYPT=true`) | `config/session.php`, `deploy/.env.production.example` |
| Disclosure | `/.well-known/security.txt` (RFC 9116) with the contact email from settings | `ErrorPagesTest` |
| CSV export | Formula-injection neutralised | `WebsiteAndCatalogueTest` |

## Scans run
- `composer audit`: no advisories. `npm audit`: 0 vulnerabilities.
- **gitleaks 8.30.1, full git history (all commits):** no leaks. Test fixtures are allow-listed in `.gitleaks.toml`.
  The working-tree scan flags only `backend/.env` and `mobile/android/key.properties`. Both are git-ignored and **have never been committed** (checked with `git log --all`).
- Browser accessibility (axe-core) checks on all main pages: 0 WCAG A/AA violations.

## Known gaps (not done)
- Independent penetration test.
- Container image scanning, because Docker is not available here.
- Rate-limit storage uses the database cache in development. Production uses Redis (`deploy/.env.production.example`).
- Customer MFA is not offered; customers sign in with OTP only.
- HSTS has no `preload` flag; add it only after the owner confirms every subdomain is HTTPS-only.
- Maintenance page: run `php artisan down --render="errors::503"` so the branded page is served while the app is down.
