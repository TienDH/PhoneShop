# PhoneShop on Render

## Confirmed on the deployed site (2026-10-08)

- `GET /login` returns 200, but form actions and redirects use HTTP on an HTTPS site.
- Posting the supplied demo login directly over HTTPS succeeds; the HTTPS admin dashboard returns 200.
- GHN provinces, districts and wards return data over HTTPS. A read-only fee request also returned 200 / success / 86900 VND for the selected test destination. Shipment creation has NOT been tested.
- An unsigned MoMo callback returns 400 as expected. It is not a valid payment test.
- No production order, shipment or payment was created during these checks.

These observations describe the site before the HTTPS proxy fix. Redeploy the current source and verify the running service after updating its configuration.

## Render Environment

Set these in the web service's Environment page, not just the local `.env`:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://phoneshop-bwkm.onrender.com
TRUSTED_PROXIES=*
LOG_CHANNEL=stderr
LOG_LEVEL=info
SESSION_DRIVER=cookie
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
QUEUE_CONNECTION=sync
INTEGRATION_DIAGNOSTICS=true
SEED_ADMIN_ON_DEPLOY=false
SEED_USER_ON_DEPLOY=false
```

Keep the existing `APP_KEY` stable across deployments. Do not regenerate it at startup.
Cookie sessions can exceed browser size limits with large carts. Database sessions are preferable for persistent production carts, but require a sessions-table migration before enabling them. File sessions disappear when Render restarts.

The startup script clears old config/views, runs additive migrations, creates the storage link, and optionally prints configuration diagnostics. It does NOT run `migrate:fresh`, reset data, or seed the known demo admin password on every restart. An existing admin account is not changed. Rotate the demo password before using real customer data.

## Demo customer login

`UsersSeeder` creates one customer with `role=user` and a pre-verified email so the demo account can log in without sending a verification email. It never changes an existing account's password, role, profile or verification status. Normal customer registration still requires email verification.

For local/testing, the default email is `user@phoneshop.com` and the default password is `12345678`. Create only this account with:

```sh
php artisan db:seed --class=UsersSeeder
```

On Render, add these variables and redeploy the source containing this seeder:

```dotenv
SEED_USER_ON_DEPLOY=true
SEED_USER_EMAIL=user@phoneshop.com
SEED_USER_PASSWORD=YOUR_PRIVATE_PASSWORD_AT_LEAST_8_CHARACTERS
```

Log in using `SEED_USER_EMAIL` and the private password you set. Production and other non-local environments reject a missing password, a password shorter than 8 characters, or the local demo password `12345678`. After the first successful deployment, set `SEED_USER_ON_DEPLOY=false` and remove `SEED_USER_PASSWORD` from Render; the saved account remains available. Change its password from the account page when appropriate. Do not put the private password in Git or chat.

Do not run the entire `DatabaseSeeder` against an existing deployment just to create a customer: it also runs the other demo data seeders, including `AdminUserSeeder`. Use only `UsersSeeder` or the opt-in startup flag above. If the configured email already exists, log in with that account's current password or choose a different demo email; seeding does not reset it.

## Email on Free instances

Render Free blocks outbound SMTP ports 25, 465 and 587. Gmail SMTP on port 587 will not work there even with a correct App Password. Use a mail provider's HTTPS API, or a paid instance that permits SMTP.

This project includes a Brevo HTTPS API transport compatible with Laravel 8 / SwiftMailer. It sends the existing registration verification, resend and password-reset notifications without changing their signed links or tokens. No additional package is needed.

```dotenv
MAIL_MAILER=brevo
BREVO_API_KEY=YOUR_BREVO_API_KEY
MAIL_FROM_ADDRESS=YOUR_VERIFIED_SENDER_ADDRESS
MAIL_FROM_NAME="TDH Phone"
```

1. In Brevo, enable transactional email for your account and create an **API key** under SMTP & API. An SMTP key/password is not an API key.
2. Add and verify the exact sender used in `MAIL_FROM_ADDRESS`. Prefer a domain you own and authenticate it in Brevo for production; you cannot authenticate a public domain such as `gmail.com`.
3. Add the four variables above to Render and redeploy. Keep `APP_URL` set to your public HTTPS URL and `QUEUE_CONNECTION=sync` unless a separate queue worker is running.
4. Test registration, resend and forgotten password with an inbox you control. Inspect Brevo's transactional logs for accepted, delivered, blocked or bounced messages; an API `messageId` means accepted, not guaranteed inbox delivery.

`MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION` and `MAILGUN_*` are not used by the Brevo mailer. Keep the API key only in Render Environment or your private local `.env`, never in Git. Do not disable TLS certificate verification. Requests have bounded timeouts, do not follow redirects, and are not automatically retried because a timed-out request may already have been accepted.

HTTP 401: check the API key. HTTP 403: check account activation, permissions and any authorized-IP restrictions in Brevo. HTTP 400: check the sender and message validity. HTTP 429: check quota/rate limits. Render logs include the status but not provider response bodies, API keys or email content. Connection failures and missing message IDs require checking Brevo logs before resending.

The transport preserves HTML/plain text alternatives, To/CC/BCC, one Reply-To and regular file attachments. Inline CID attachments are explicitly rejected; the current auth notifications do not use them. Existing SMTP, Mailgun and other mailers remain available. Never use `MAIL_MAILER=log` as proof of delivery: that driver writes messages to logs instead of sending them. No real Brevo credentials or sender have been configured or tested by these code changes.

## MoMo

```dotenv
MOMO_ENDPOINT=https://test-payment.momo.vn/v2/gateway/api/create
MOMO_PARTNER_CODE=YOUR_SANDBOX_PARTNER_CODE
MOMO_ACCESS_KEY=YOUR_SANDBOX_ACCESS_KEY
MOMO_SECRET_KEY=YOUR_SANDBOX_SECRET_KEY
MOMO_VERIFY_SSL=true
MOMO_REQUEST_TYPE=captureWallet
MOMO_REDIRECT_URL=https://phoneshop-bwkm.onrender.com/payment/momo/callback
MOMO_IPN_URL=https://phoneshop-bwkm.onrender.com/payment/momo/ipn
```

Use a matching sandbox credential set; do not mix sandbox and production. No callback URL may point to localhost. The callback is GET; IPN is POST and only its path is exempt from CSRF. Missing/invalid signatures must remain rejected. Do not disable signature verification or mark an order paid from a redirect alone.

MoMo requires IPN acknowledgement within 15 seconds. Render Free may take about a minute to wake from sleep, so it is unsuitable for reliable production payment notifications. Test real sandbox callbacks and IPNs before considering payments operational. MoMo create failures now log HTTP status, result code and provider message without credentials.

## GHN

```dotenv
GHN_BASE_URL=https://dev-online-gateway.ghn.vn/shiip/public-api
GHN_TOKEN=YOUR_SANDBOX_GHN_TOKEN
GHN_SHOP_ID=YOUR_SANDBOX_SHOP_ID
GHN_FROM_DISTRICT_ID=YOUR_SANDBOX_WAREHOUSE_DISTRICT_ID
GHN_VERIFY_SSL=true
GHN_DEFAULT_WEIGHT=200
```

For production use `https://online-gateway.ghn.vn/shiip/public-api` with a production token, shop and warehouse. Successful province lookup does not verify the shop or warehouse. If fee calculation fails, inspect `[GHN] available-services failed` and `[GHN] calculateFee failed` in Render Logs. The checkout frontend's address requests also require the HTTPS proxy fix to avoid browser mixed-content blocking.

## Redeploy and Verify

1. Commit/push the reviewed source changes to the branch used by Render.
2. Set the environment variables in Render and choose Save, rebuild, and deploy. Saving without deploying does not update the running container.
3. Check deploy logs for the `integrations:check` table; it prints missing variable names, never secret values. Free services do not provide dashboard/SSH shell access, which is why startup diagnostics are available.
4. Open `/login`. Its action must be `https://phoneshop-bwkm.onrender.com/login`; then test login and session retention.
5. Test registration/resend and password reset with an email address you control. Verify delivery in the provider dashboard and follow the signed HTTPS link.
6. Test GHN address selection and fee quote. Shipment creation must be tested separately using a sandbox order.
7. Create a sandbox MoMo payment; verify matching order amount, signature, callback/IPN, and one-time settlement. Do not use a production charge for diagnostics.

On local or a paid service with shell access, configuration checks and optional read-only GHN probes are available:

```sh
php artisan integrations:check
php artisan integrations:check --probe-ghn
php artisan integrations:check --probe-ghn --district=YOUR_DISTRICT --ward=YOUR_WARD
php artisan test --filter='UsersSeederTest|BrevoMailTest|RenderDeploymentTest|CheckoutOrdersTest|MomoPaymentTest|StoreManagementTest|ProductVariantsTest'
```

Uploaded files in Render's local filesystem are ephemeral even though Aiven database records persist. Use external object storage or a paid persistent disk before storing real product uploads.

## Official References

- [Render Free limitations](https://render.com/docs/free)
- [Render environment variables](https://render.com/docs/configure-environment-variables)
- [Laravel 8 trusted proxies](https://laravel.com/docs/8.x/requests#configuring-trusted-proxies)
- [Laravel 8 API mail drivers](https://laravel.com/docs/8.x/mail#driver-prerequisites)
- [Brevo transactional email API](https://developers.brevo.com/reference/send-transac-email)
- [Brevo domain authentication](https://help.brevo.com/hc/en-us/articles/12163873383186-Authenticate-your-domain-with-Brevo-Brevo-code-DKIM-DMARC)
- [MoMo payment notifications](https://developers.momo.vn/v3/vi/docs/payment/api/result-handling/notification/)
- [GHN shipment creation](https://developer.ghn.vn/vi/docs/order/create)
