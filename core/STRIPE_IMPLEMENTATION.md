# Stripe Multi-Account Switching System - Complete Implementation

## Overview

This implementation provides a complete Laravel system for managing and dynamically switching between multiple Stripe accounts based on deposit amounts. The system includes:

- **Database Tables**: `stripe_accounts` and deposit modifications
- **Helper Class**: `StripeAccountHelper` for intelligent account selection
- **Payment Controllers**: Modified Stripe, StripeJs, and StripeV3 controllers
- **Webhook Handler**: `StripeWebhookController` for processing webhook events
- **Services**: `StripeWebhookService` for webhook management
- **CLI Tools**: `StripeManageCommand` for account management
- **Documentation**: Complete setup and usage guide

---

## Database Schema

### stripe_accounts Table

```sql
CREATE TABLE stripe_accounts (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    publishable_key VARCHAR(255) NOT NULL,
    secret_key VARCHAR(255) NOT NULL,
    min_amount DECIMAL(28, 8) DEFAULT 0,
    max_amount DECIMAL(28, 8) DEFAULT 0,
    webhook_secret VARCHAR(255) NULLABLE,
    webhook_id VARCHAR(255) NULLABLE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### deposits Table Modification

```sql
ALTER TABLE deposits ADD COLUMN stripe_account_id BIGINT UNSIGNED NULLABLE AFTER btc_wallet;
ALTER TABLE deposits ADD INDEX idx_stripe_account_id (stripe_account_id);
ALTER TABLE deposits ADD CONSTRAINT fk_stripe_account_id FOREIGN KEY (stripe_account_id) REFERENCES stripe_accounts(id) ON DELETE SET NULL;
```

---

## File Structure

```
app/
├── Console/Commands/
│   └── StripeManageCommand.php          # CLI tool for managing accounts
├── Helpers/
│   └── StripeAccountHelper.php          # Account selection logic
├── Http/Controllers/Gateway/
│   ├── Stripe/ProcessController.php     # Updated with account selection
│   ├── StripeJs/ProcessController.php   # Updated with account selection
│   ├── StripeV3/ProcessController.php   # Updated with account selection
│   ├── StripeWebhookController.php      # Webhook event handler
│   └── ExamplePaymentController.php     # Usage examples
├── Models/
│   ├── StripeAccount.php                # (Already exists)
│   └── Deposit.php                      # (Modified with relationships)
└── Services/
    └── StripeWebhookService.php         # Webhook management service

database/migrations/
├── 2026_02_12_000000_create_stripe_accounts_table.php
├── 2026_02_12_000001_add_stripe_account_id_to_deposits_table.php
└── 2026_02_12_000002_add_webhook_fields_to_stripe_accounts.php

routes/
└── stripe-webhooks.example.php          # Example webhook routes
```

---

## Core Components

### 1. StripeAccountHelper Class

**Purpose**: Select appropriate Stripe account based on deposit amount

**Key Methods**:
- `selectStripeAccount($deposit)` - Main selection logic
- `findAccountByAmountRange($amount)` - Match by amount range
- `selectByRoundRobin()` - Fallback to round-robin
- `getActiveAccounts()` - List all active accounts
- `validateCredentials($account)` - Verify account credentials

**Selection Logic**:
1. First: Find account matching deposit amount range (min_amount <= amount <= max_amount)
2. Second: If no match and max_amount=0, use unlimited account
3. Fallback: Use round-robin distribution across all active accounts

```php
$stripeAccount = StripeAccountHelper::selectStripeAccount($deposit);
if ($stripeAccount) {
    Stripe::setApiKey($stripeAccount->secret_key);
    $deposit->update(['stripe_account_id' => $stripeAccount->id]);
}
```

### 2. Updated Payment Controllers

All Stripe payment controllers (Stripe, StripeJs, StripeV3) have been modified to:
- Call `selectStripeAccount()` before processing payment
- Set Stripe API key from selected account
- Save account ID to deposit
- Include account ID in metadata for webhook reference

### 3. StripeWebhookController

**Purpose**: Handle incoming Stripe webhook events

**Handles**:
- `charge.succeeded` - Payment successful
- `charge.failed` - Payment failed
- `charge.refunded` - Payment refunded
- `charge.dispute.created` - Chargeback filed
- `checkout.session.completed` - Checkout completed

**Key Features**:
- Verifies webhook signature with correct account's secret
- Supports fallback verification if account ID not provided
- Updates deposit status based on event
- Logs all operations for audit trail

### 4. StripeWebhookService

**Purpose**: Manage webhook endpoints across accounts

**Methods**:
- `registerWebhook()` - Register endpoint on Stripe
- `listWebhooks()` - List all endpoints for account
- `deleteWebhook()` - Remove endpoint
- `updateWebhookEvents()` - Modify listened events
- `checkWebhookHealth()` - Test endpoint connectivity

### 5. StripeManageCommand

**Purpose**: CLI tool for account management

**Available Commands**:
```bash
php artisan stripe:manage list                      # List all accounts
php artisan stripe:manage validate --id=1           # Validate account
php artisan stripe:manage webhooks --id=1           # List webhooks
php artisan stripe:manage register-webhook --id=1   # Register webhook
php artisan stripe:manage test --id=1              # Test account
```

---

## Installation Steps

### Step 1: Run Migrations

```bash
php artisan migrate
```

This creates:
- `stripe_accounts` table
- Adds `stripe_account_id` column to `deposits`
- Adds `webhook_secret` and `webhook_id` columns

### Step 2: Populate Stripe Accounts

Via admin panel or database:

```php
StripeAccount::create([
    'name' => 'Account 1',
    'publishable_key' => 'pk_...',
    'secret_key' => 'sk_...',
    'min_amount' => 0,
    'max_amount' => 1000,
    'is_active' => true,
]);

StripeAccount::create([
    'name' => 'Account 2',
    'publishable_key' => 'pk_...',
    'secret_key' => 'sk_...',
    'min_amount' => 1000,
    'max_amount' => 0,  // unlimited
    'is_active' => true,
]);
```

### Step 3: Register Webhooks

```bash
php artisan stripe:manage register-webhook --id=1
php artisan stripe:manage register-webhook --id=2
```

Or use the service:

```php
StripeWebhookService::registerWebhook(
    $account,
    'https://yourdomain.com/webhooks/stripe',
    ['charge.succeeded', 'charge.failed', ...]
);
```

### Step 4: Add Webhook Route

In `routes/web.php`:

```php
Route::post('/webhooks/stripe', [
    \App\Http\Controllers\Gateway\StripeWebhookController::class, 
    'handleWebhook'
]);
```

### Step 5: Test Setup

```bash
php artisan stripe:manage test --id=1
php artisan stripe:manage validate --id=1
```

---

## Usage Examples

### Basic Payment Processing

```php
public function processPayment(Deposit $deposit)
{
    // Step 1: Select account
    $stripeAccount = StripeAccountHelper::selectStripeAccount($deposit);
    
    if (!$stripeAccount) {
        return ['error' => 'No Stripe account available'];
    }

    // Step 2: Set API key
    Stripe::setApiKey($stripeAccount->secret_key);

    // Step 3: Save account ID
    $deposit->stripe_account_id = $stripeAccount->id;
    $deposit->save();

    // Step 4: Process charge
    $charge = Charge::create([
        'amount' => round($deposit->final_amount, 2) * 100,
        'currency' => strtolower($deposit->method_currency),
        'source' => $token['id'],
        'metadata' => [
            'deposit_id' => $deposit->id,
            'trx' => $deposit->trx,
        ],
    ]);

    // Step 5: Update deposit
    PaymentController::userDataUpdate($deposit);
}
```

### Creating Checkout Session

```php
$stripeAccount = StripeAccountHelper::selectStripeAccount($deposit);
Stripe::setApiKey($stripeAccount->secret_key);

$session = \Stripe\Checkout\Session::create([
    'payment_method_types' => ['card'],
    'line_items' => [[
        'price_data' => [
            'unit_amount' => round($deposit->final_amount, 2) * 100,
            'currency' => strtolower($deposit->method_currency),
            'product_data' => ['name' => 'Deposit'],
        ],
        'quantity' => 1,
    ]],
    'mode' => 'payment',
    'success_url' => route('success'),
    'cancel_url' => route('cancel'),
    'metadata' => [
        'deposit_id' => $deposit->id,
        'stripe_account_id' => $stripeAccount->id,
    ],
]);
```

### Handling Refunds

```php
// IMPORTANT: Use the same account that processed original charge
$stripeAccount = StripeAccountHelper::getAccountById($deposit->stripe_account_id);
Stripe::setApiKey($stripeAccount->secret_key);

$refund = \Stripe\Refund::create([
    'charge' => $deposit->stripe_charge_id,
    'amount' => round($refund_amount, 2) * 100,
    'metadata' => ['deposit_id' => $deposit->id],
]);
```

---

## Account Selection Strategy

### Amount-Based Selection

The system matches deposits to accounts using amount ranges:

```
Account A: min = $0,    max = $1,000  → Small transactions
Account B: min = $1,000 max = $5,000  → Medium transactions
Account C: min = $5,000 max = $0      → Large transactions (unlimited)
```

For a $2,500 deposit: Account B is selected
For a $10,000 deposit: Account C is selected
For a $500 deposit: Account A is selected

### Round-Robin Fallback

If no amount match exists, deposits are distributed evenly:

```
Deposit Count Modulo Number of Accounts = Account Index
```

This ensures:
- No single account is overloaded
- Load is balanced across available accounts
- Helps prevent rate limiting

### Best Practices

1. **Always set amount ranges** - Avoid ambiguity
2. **Use unlimited account** - Set max_amount=0 for overflow
3. **Order by min_amount** - Highest priority goes last
4. **Validate credentials** - Before going live
5. **Monitor distribution** - Check logs for balance

---

## Webhook Management

### Webhook Events Handled

```
charge.succeeded         - Payment successful
charge.failed            - Payment failed
charge.refunded          - Payment refunded
charge.dispute.created   - Chargeback filed
checkout.session.completed - Checkout completed
```

### Webhook Signature Verification

The system verifies webhooks using the correct account's webhook secret:

```php
// Automatically selects right account based on metadata
$event = Webhook::constructEvent(
    $payload,
    $sig_header,
    $account->webhook_secret
);
```

### Setting Up Webhooks

```bash
# List existing webhooks
php artisan stripe:manage webhooks --id=1

# Register new webhook
php artisan stripe:manage register-webhook --id=1

# Test webhook endpoint
StripeWebhookService::checkWebhookHealth('https://yourdomain.com/webhooks/stripe');
```

---

## Monitoring & Debugging

### Check Logs

```bash
tail -f storage/logs/laravel.log | grep -i stripe
```

### List All Accounts

```bash
php artisan stripe:manage list
```

### Validate Account

```bash
php artisan stripe:manage validate --id=1
```

### Test Account Connection

```bash
php artisan stripe:manage test --id=1
```

### List Webhooks

```bash
php artisan stripe:manage webhooks --id=1
```

---

## Common Issues & Solutions

### Issue: "No active Stripe account available"

**Solution**:
1. Ensure at least one StripeAccount exists
2. Check `is_active` is set to `true`
3. Validate credentials: `php artisan stripe:manage validate --id=1`

### Issue: "Webhook signature verification failed"

**Solution**:
1. Check webhook_secret is correctly stored
2. Ensure webhook URL is publicly accessible
3. Verify in Stripe dashboard that webhook was sent
4. Check Stripe version matches (2020-03-02)

### Issue: "Deposit not found for deposit_id in metadata"

**Solution**:
1. Ensure metadata includes `deposit_id`
2. Check deposit was created before webhook fired
3. Verify database doesn't have cascading deletes

### Issue: "Account ID mismatch on refund"

**Solution**:
1. ALWAYS use same account for refund as original charge
2. Verify `stripe_account_id` is saved on deposit
3. Use `selectByRoundRobin()` should not be used for refunds

---

## Security Considerations

1. **Never hardcode keys** - Use environment variables
2. **Verify signatures** - Always verify webhook signatures
3. **Use HTTPS** - For webhook URLs only
4. **Log operations** - Audit trail for all transactions
5. **Rate limit webhooks** - Throttle to prevent abuse
6. **Validate metadata** - Never trust user input
7. **Secure storage** - Encrypt keys in database if needed

---

## Migration from Single Account

If upgrading from single account:

```php
// 1. Create accounts in stripe_accounts table
StripeAccount::create([
    'name' => 'Legacy Account',
    'publishable_key' => env('STRIPE_PUBLIC_KEY'),
    'secret_key' => env('STRIPE_SECRET_KEY'),
    'min_amount' => 0,
    'max_amount' => 0,  // unlimited
    'is_active' => true,
]);

// 2. Update existing deposits
Deposit::whereNull('stripe_account_id')->update([
    'stripe_account_id' => 1,  // Legacy account ID
]);

// 3. Register webhooks
php artisan stripe:manage register-webhook --id=1

// 4. Test thoroughly before production
php artisan stripe:manage test --id=1
```

---

## File Locations Reference

| File | Purpose |
|------|---------|
| `app/Helpers/StripeAccountHelper.php` | Account selection logic |
| `app/Http/Controllers/Gateway/StripeWebhookController.php` | Webhook handler |
| `app/Services/StripeWebhookService.php` | Webhook management |
| `app/Console/Commands/StripeManageCommand.php` | CLI tool |
| `app/Models/StripeAccount.php` | Model (already exists) |
| `database/migrations/2026_02_12_000000_create_stripe_accounts_table.php` | Initial migration |
| `database/migrations/2026_02_12_000001_add_stripe_account_id_to_deposits_table.php` | Deposit modification |
| `database/migrations/2026_02_12_000002_add_webhook_fields_to_stripe_accounts.php` | Webhook fields |

---

## Next Steps

1. ✅ Run migrations
2. ✅ Populate stripe_accounts table
3. ✅ Validate account credentials
4. ✅ Register webhooks
5. ✅ Add webhook route
6. ✅ Test payment flow
7. ✅ Monitor logs
8. ✅ Go live

---

## Support & Documentation

For more information, see:
- `STRIPE_SETUP.md` - Detailed setup guide
- `app/Http/Controllers/Gateway/ExamplePaymentController.php` - Usage examples
- Stripe API docs: https://stripe.com/docs/api

---

**Last Updated**: February 12, 2026
**Version**: 1.0.0
