# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Shopware 5 payment plugin (Mond1SWR5) integrating Mondu BNPL (Buy Now, Pay Later) payment methods: Invoice, SEPA Direct Debit, Installment, and Pay Now. All methods are restricted to Germany (DE).

- **Plugin name**: `Mond1SWR5`
- **Namespace**: `Mond1SWR5`
- **PHP**: ^7.1 || ^8.0
- **Shopware**: >=5.6.4

## Development Environment

```bash
# Start local Shopware 5 via Docker (dockware/dev:5.7.16)
docker-compose up

# Plugin is mounted at /var/www/html/custom/plugins/Mond1SWR5
# Admin: demo/demo, MySQL: shopware/shopware (port 3306)
# Requires .env file with BNPL_MERCHANT_API_TOKEN
```

## CLI Commands (run inside Shopware container)

```bash
php bin/console sw:plugin:install Mond1SWR5
php bin/console sw:plugin:activate Mond1SWR5
php bin/console sw:cache:clear

# Validate API credentials and register webhooks
php bin/console sw:Mond1SWR5:validate

# Activate Mondu payment methods
php bin/console sw:Mond1SWR5:activate:payment

# Activate Mondu in shipping cost configurations
php bin/console sw:Mond1SWR5:activate:shipment:cost
```

## Building a Release

```bash
./releaser.sh -v X.X.X   # Produces Mondu-X.X.X.zip for Shopware plugin manager
```

## Architecture

### Plugin Lifecycle

`Mond1SWR5.php` — main plugin class extending `Shopware\Components\Plugin`. Delegates install/update/uninstall/activate/deactivate to bootstrap classes (`Bootstrap/PaymentMethods.php`, `Bootstrap/Attributes/OrderAttributes.php`). Uses a Symfony DI compiler pass (`Compiler/FileLoggerPass.php`) for file-based logging.

### Service Layer (Resources/services.xml)

All services use Symfony DI with autowiring. Key services:

- **MonduClient** (`Components/MonduApi/Service/MonduClient.php`) — Guzzle-based HTTP client for Mondu REST API. Sandbox: `api.demo.mondu.ai/api/v1`, Production: `api.mondu.ai/api/v1`
- **ConfigService** (`Components/PluginConfig/Service/ConfigService.php`) — reads plugin config (API token, sandbox mode, webhook secret, B2B toggle, cron mode, etc.)
- **SessionService** (`Services/SessionService.php`) — manages checkout session data
- **WebhookService** (`Services/Webhook/WebhookService.php`) — routes incoming webhooks to handlers
- **PaymentStatusService** (`Services/PaymentStatusService.php`) — maps Mondu states to Shopware payment statuses

### Checkout Flow

`Controllers/Frontend/Mondu.php` handles the payment flow:
1. `indexAction()` — validates selected payment method
2. `directAction()` — creates a Mondu order via API, redirects to Mondu checkout widget
3. Callback actions: `successAction()`, `cancelAction()`, `declineAction()`

### Webhook System

`Controllers/Frontend/MonduWebhook.php` receives webhooks, validated via HMAC-SHA256 (`X-Mondu-Signature` header). Handlers in `Services/Webhook/Handlers/`: OrderPending, OrderConfirmed, OrderCanceled, OrderDeclined, InvoicePaid.

### Order Attributes

Custom attributes stored on Shopware orders (defined in `Bootstrap/Attributes/OrderAttributes.php`):
`mondu_reference_id`, `mondu_state`, `mondu_invoice_iban`, `mondu_external_invoice_number`, `mondu_duration`, `mondu_payment_method`, `mondu_merchant_company_name`, `mondu_authorized_net_term`

### Payment Method Registration

`Enum/PaymentMethods.php` defines all four payment methods with constants, mappings between Mondu API names and local Shopware names, and configuration (descriptions, templates, country restrictions).

### Extensibility

`Services/OrderServices/AbstractOrderAdditionalCostsService` can be decorated to add custom fees/costs to orders (see `Services/OrderServices/README.md` for the decorator pattern).

### Cron

`Subscriber/Cron/OrderStatusCron.php` — runs every 3600s when `mondu/mode/cron` is enabled. Auto-creates invoices and cancels orders based on Shopware order state transitions.

### Frontend Templates

Smarty templates in `Resources/views/` for checkout payment selection, backend admin overview (`mondu_overview`), and invoice document extensions.

## Configuration Keys (Resources/config.xml)

- `mondu/credentials/api_token` — API authentication
- `mondu/credentials/webhook_secret` — webhook signature validation
- `mondu/mode/sandbox` — sandbox/production toggle
- `mondu/mode/b2b` — filter to B2B customers only
- `mondu/mode/cron` — enable automatic order processing
- `mondu/mode/extend_invoice_template` — extend invoice PDF with Mondu payment details
- `mondu/mode/invoice_create_state` — Shopware order state that triggers invoice creation
- `mondu/mode/validate_invoice` — require invoice document before shipping to Mondu

## Testing

No automated test suite exists. Testing is done manually via the Docker environment and the Mondu sandbox API.