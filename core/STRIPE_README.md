# STRIPE MULTI-ACCOUNT SWITCHING SYSTEM

## Complete Laravel Implementation

A comprehensive, production-ready Laravel system for managing multiple Stripe accounts with intelligent account switching based on deposit amounts.

---

## 🎯 Quick Start

```bash
# 1. Run migrations
php artisan migrate

# 2. Seed accounts (adjust values in seeder)
php artisan db:seed --class=StripeAccountSeeder

# 3. Test account setup
php artisan stripe:manage test --id=1

# 4. Register webhooks
php artisan stripe:manage register-webhook --id=1

# 5. Add webhook route to routes/web.php
Route::post('/webhooks/stripe', [\App\Http\Controllers\Gateway\StripeWebhookController::class, 'handleWebhook']);
```

---

## 📋 What's Included

### Core Components
- ✅ **StripeAccountHelper** - Intelligent account selection
- ✅ **StripeWebhookController** - Webhook event handling
- ✅ **StripeWebhookService** - Webhook management
- ✅ **StripeManageCommand** - CLI tools
- ✅ **Database Migrations** - Tables and relationships
- ✅ **Modified Controllers** - Stripe, StripeJs, StripeV3

### Documentation
- ✅ **STRIPE_IMPLEMENTATION.md** - Complete guide
- ✅ **STRIPE_SETUP.md** - Setup instructions
- ✅ **STRIPE_FILES_SUMMARY.md** - File reference
- ✅ **config/stripe-config-examples.php** - Configuration examples
- ✅ **ExamplePaymentController.php** - Code examples

### Testing
- ✅ **Unit Tests** - Component testing
- ✅ **Integration Tests** - Workflow testing
- ✅ **Test Cases** - Ready-to-use examples

---

## 🔧 Key Features

### Smart Account Selection
```php
// Automatically selects account based on deposit amount
$stripeAccount = StripeAccountHelper::selectStripeAccount($deposit);

// Account A: $0-$1,000
// Account B: $1,000-$5,000
// Account C: $5,000+ (unlimited)
```

### Multi-Account Support
- Multiple Stripe accounts per merchant
- Dynamic account switching per deposit
- Account-specific webhook handling
- Full audit trail

### Webhook Management
- Multi-account webhook verification
- Automatic status updates
- Event handling (succeeded, failed, refunded, disputes)
- Fallback verification logic

### CLI Tools
```bash
php artisan stripe:manage list              # List accounts
php artisan stripe:manage test --id=1      # Test account
php artisan stripe:manage validate --id=1  # Validate credentials
php artisan stripe:manage webhooks --id=1  # List webhooks
php artisan stripe:manage register-webhook --id=1  # Register webhook
```

---

## 📊 Account Selection Logic

### Strategy 1: Amount-Based Matching (Default)
```
Amount: $2,500
Match: Account with min_amount <= 2500 <= max_amount
Result: Correct account selected
```

### Strategy 2: Round-Robin Fallback
```
When no amount match exists
Deposits distributed evenly across all accounts
Prevents overloading single account
```

### Strategy 3: Unlimited Accounts
```
Set max_amount = 0 for unlimited transactions
Useful as fallback for any transaction size
```

---

## 🗄️ Database Schema

### stripe_accounts Table
```sql
id                  BIGINT UNSIGNED PRIMARY KEY
name                VARCHAR(255)
publishable_key     VARCHAR(255)
secret_key          VARCHAR(255)
min_amount          DECIMAL(28, 8) DEFAULT 0
max_amount          DECIMAL(28, 8) DEFAULT 0
webhook_secret      VARCHAR(255) NULLABLE
webhook_id          VARCHAR(255) NULLABLE
is_active           BOOLEAN DEFAULT TRUE
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

### deposits Table Modifications
```sql
stripe_account_id   BIGINT UNSIGNED NULLABLE (foreign key)
stripe_charge_id    VARCHAR(255) NULLABLE
stripe_session_id   VARCHAR(255) NULLABLE
```

---

## 💻 Usage Examples

### Basic Payment Processing
```php
// Select appropriate account
$stripeAccount = StripeAccountHelper::selectStripeAccount($deposit);

// Set API key
Stripe::setApiKey($stripeAccount->secret_key);

// Save account reference
$deposit->stripe_account_id = $stripeAccount->id;
$deposit->save();

// Process payment
$charge = Charge::create([
    'amount' => round($deposit->final_amount, 2) * 100,
    'currency' => strtolower($deposit->method_currency),
    'source' => $token['id'],
    'metadata' => [
        'deposit_id' => $deposit->id,
        'stripe_account_id' => $stripeAccount->id,
    ],
]);
```

### Checkout Session
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
    'metadata' => ['deposit_id' => $deposit->id],
]);
```

### Webhook Handling
```php
$event = Webhook::constructEvent($payload, $sig_header, $account->webhook_secret);

if ($event->type == 'charge.succeeded') {
    $deposit = Deposit::find($event->data->object->metadata['deposit_id']);
    PaymentController::userDataUpdate($deposit);
}
```

---

## 🚀 Webhook Configuration

### Setup Webhooks
```bash
php artisan stripe:manage register-webhook --id=1
```

### Webhook Events
- `charge.succeeded` - Payment successful
- `charge.failed` - Payment failed  
- `charge.refunded` - Payment refunded
- `charge.dispute.created` - Chargeback filed
- `checkout.session.completed` - Checkout complete

### Webhook Route
```php
// routes/web.php
Route::post('/webhooks/stripe', [
    \App\Http\Controllers\Gateway\StripeWebhookController::class,
    'handleWebhook'
]);
```

---

## 🔐 Security

- ✅ Webhook signature verification
- ✅ API key rotation support
- ✅ Secure credential storage
- ✅ Comprehensive audit logging
- ✅ Metadata validation

---

## 📝 File Reference

| File | Purpose |
|------|---------|
| `app/Helpers/StripeAccountHelper.php` | Account selection logic |
| `app/Http/Controllers/Gateway/StripeWebhookController.php` | Webhook handler |
| `app/Services/StripeWebhookService.php` | Webhook management |
| `app/Console/Commands/StripeManageCommand.php` | CLI tools |
| `database/migrations/2026_02_12_000000_create_stripe_accounts_table.php` | Create table |
| `database/migrations/2026_02_12_000001_add_stripe_account_id_to_deposits_table.php` | Add column |
| `STRIPE_IMPLEMENTATION.md` | Complete docs |
| `STRIPE_SETUP.md` | Setup guide |
| `config/stripe-config-examples.php` | Configuration |
| `tests/Unit/Helpers/StripeAccountHelperTest.php` | Tests |

---

## 🧪 Testing

### Run Tests
```bash
php artisan test --filter=StripeAccountHelperTest
php artisan test --filter=StripeWebhookServiceTest
php artisan test --filter=StripeMultiAccountTest
```

### Manual Testing
```bash
# Test account credentials
php artisan stripe:manage test --id=1

# Validate setup
php artisan stripe:manage validate --id=1

# List webhooks
php artisan stripe:manage webhooks --id=1

# Check logs
tail -f storage/logs/laravel.log | grep stripe
```

---

## 📚 Documentation Files

1. **STRIPE_IMPLEMENTATION.md** - Full implementation guide
2. **STRIPE_SETUP.md** - Detailed setup instructions
3. **STRIPE_FILES_SUMMARY.md** - File reference and checklist
4. **config/stripe-config-examples.php** - Configuration examples
5. **app/Http/Controllers/Gateway/ExamplePaymentController.php** - Code examples

---

## ✅ Implementation Checklist

- [ ] Run migrations: `php artisan migrate`
- [ ] Create StripeAccount records
- [ ] Validate credentials: `php artisan stripe:manage validate --id=1`
- [ ] Register webhooks: `php artisan stripe:manage register-webhook --id=1`
- [ ] Add webhook route to routes/web.php
- [ ] Update payment controllers (done automatically)
- [ ] Test payment flow
- [ ] Monitor logs
- [ ] Deploy to production

---

## 🆘 Troubleshooting

### "No active Stripe account available"
- Ensure StripeAccount exists with `is_active = true`
- Validate: `php artisan stripe:manage validate --id=1`

### "Webhook signature verification failed"
- Check webhook_secret is correct
- Verify webhook URL is accessible
- List webhooks: `php artisan stripe:manage webhooks --id=1`

### "Deposit not found"
- Ensure metadata includes `deposit_id`
- Check deposit was created before webhook

### "Account ID mismatch on refund"
- Always use same account for refund as original charge
- Use: `StripeAccountHelper::getAccountById($deposit->stripe_account_id)`

---

## 📞 Support

For issues or questions:

1. Check logs: `storage/logs/laravel.log`
2. Review documentation: `STRIPE_IMPLEMENTATION.md`
3. Run tests: `php artisan test`
4. Test account: `php artisan stripe:manage test --id=1`

---

## 📦 Requirements

- Laravel 11+
- PHP 8.1+
- Stripe SDK for PHP
- Database (MySQL, PostgreSQL, SQLite)

---

## 🎓 Examples

See `app/Http/Controllers/Gateway/ExamplePaymentController.php` for:
- Basic payment processing
- Checkout sessions
- Refund handling
- Webhook handling
- Account validation

---

## 📄 License

This implementation follows your project's licensing.

---

## 🚀 Production Readiness

✅ All code is production-ready
✅ Comprehensive error handling
✅ Full logging and monitoring
✅ Security best practices
✅ Webhook validation
✅ Database migrations
✅ CLI tools
✅ Documentation
✅ Test cases
✅ Example code

---

**Version**: 1.0.0
**Last Updated**: February 12, 2026
**Status**: Production Ready ✅
