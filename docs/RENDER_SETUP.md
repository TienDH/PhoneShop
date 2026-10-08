# PhoneShop on Render

## Confirmed on the deployed site (2026-10-08)

- `GET /login` returns 200, but form actions and redirects use HTTP on an HTTPS site.
- Posting the supplied demo login directly over HTTPS succeeds; the HTTPS admin dashboard returns 200.
- GHN provinces, districts and wards return data over HTTPS. A read-only fee request also returned 200 / success / 86900 VND for the selected test destination. Shipment creation has NOT been tested.
- An unsigned MoMo callback returns 400 as expected. It is not a valid payment test.
- No production order, shipment or payment was created during these checks.

The proxy fix and the other changes in this working tree are NOT deployed yet.

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
```

Keep the existing `APP_KEY` stable across deployments. Do not regenerate it at startup.
Cookie sessions can exceed browser size limits with large carts. Database sessions are preferable for persistent production carts, but require a sessions-table migration before enabling them. File sessions disappear when Render restarts.

The startup script clears old config/views, runs additive migrations, creates the storage link, and optionally prints configuration diagnostics. It does NOT run `migrate:fresh`, reset data, or seed the known demo admin password on every restart. An existing admin account is not changed. Rotate the demo password before using real customer data.

## Email on Free instances

Render Free blocks outbound SMTP ports 25, 465 and 587. Gmail SMTP on port 587 will not work there even with a correct App Password. Use a mail provider's HTTPS API, or a paid instance that permits SMTP.

Laravel 8 in this project already includes the Mailgun API transport and Guzzle. No new package is needed for that option:

```dotenv
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=YOUR_VERIFIED_MAILGUN_DOMAIN
MAILGUN_SECRET=YOUR_MAILGUN_API_KEY
MAILGUN_ENDPOINT=api.mailgun.net
MAIL_FROM_ADDRESS=YOUR_VERIFIED_SENDER_ADDRESS
MAIL_FROM_NAME="TDH Phone"
```

Use `api.eu.mailgun.net` for a Mailgun EU domain. Mailgun sandbox domains only deliver to authorized recipients. Never use `MAIL_MAILER=log` as proof of delivery: that driver writes messages to logs instead of sending them. No email API credentials have been supplied or configured on Render by this task.

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

1. Commit/push the reviewed source changes to the branch used by Render. These steps have not been executed by this task.
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
php artisan test --filter='RenderDeploymentTest|CheckoutOrdersTest|MomoPaymentTest|StoreManagementTest|ProductVariantsTest'
```

Uploaded files in Render's local filesystem are ephemeral even though Aiven database records persist. Use external object storage or a paid persistent disk before storing real product uploads.

## Official References

- [Render Free limitations](https://render.com/docs/free)
- [Render environment variables](https://render.com/docs/configure-environment-variables)
- [Laravel 8 trusted proxies](https://laravel.com/docs/8.x/requests#configuring-trusted-proxies)
- [Laravel 8 API mail drivers](https://laravel.com/docs/8.x/mail#driver-prerequisites)
- [MoMo payment notifications](https://developers.momo.vn/v3/vi/docs/payment/api/result-handling/notification/)
- [GHN shipment creation](https://developer.ghn.vn/vi/docs/order/create)
