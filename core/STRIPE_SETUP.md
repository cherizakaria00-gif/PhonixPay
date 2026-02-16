<?php

/**
 * STRIPE MULTI-ACCOUNT SWITCHING SYSTEM
 * 
 * Implementation Guide and Configuration
 */

/**
 * DATABASE SCHEMA
 * ===============
 * 
 * stripe_accounts table:
 * - id (primary key)
 * - name (string) - Account friendly name
 * - publishable_key (string) - Stripe publishable key
 * - secret_key (string) - Stripe secret key
 * - min_amount (decimal) - Minimum deposit amount for this account
 * - max_amount (decimal) - Maximum deposit amount (0 = unlimited)
 * - webhook_secret (string, nullable) - Webhook signing secret
 * - webhook_id (string, nullable) - Stripe webhook endpoint ID
 * - is_active (boolean) - Account status
 * - created_at (timestamp)
 * - updated_at (timestamp)
 * 
 * deposits table modifications:
 * - stripe_account_id (foreign key to stripe_accounts)
 * - stripe_charge_id (nullable)
 * - stripe_session_id (nullable)
 */

/**
 * USAGE EXAMPLES
 * ==============
 */

// 1. SELECT STRIPE ACCOUNT FOR A DEPOSIT
// In your payment controller:
$deposit = Deposit::find(1);
$stripeAccount = \App\Helpers\StripeAccountHelper::selectStripeAccount($deposit);

// 2. SET API KEY FOR TRANSACTION
\Stripe\Stripe::setApiKey($stripeAccount->secret_key);

// 3. SAVE ACCOUNT ID TO DEPOSIT
$deposit->update(['stripe_account_id' => $stripeAccount->id]);

// 4. CREATE CHARGE WITH THE ACCOUNT
$charge = \Stripe\Charge::create([
    'card' => $token['id'],
    'currency' => strtolower($deposit->method_currency),
    'amount' => $cents,
    'metadata' => [
        'deposit_id' => $deposit->id,
        'trx' => $deposit->trx,
    ],
]);

/**
 * ACCOUNT SELECTION LOGIC
 * =======================
 * 
 * The system uses the following priority:
 * 
 * 1. AMOUNT-BASED MATCHING
 *    - Finds account where: min_amount <= deposit_amount <= max_amount
 *    - If multiple matches, uses the one with highest min_amount
 *    - Example: Account A (100-1000), Account B (1000-5000)
 *    - Amount 1500 → Account B
 * 
 * 2. UNLIMITED ACCOUNTS
 *    - If max_amount is 0, that account handles unlimited transactions
 *    - Can be used as fallback for any amount
 * 
 * 3. ROUND-ROBIN FALLBACK
 *    - If no amount range matches, uses round-robin distribution
 *    - Distributes load evenly across all active accounts
 *    - Ensures no single account gets overwhelmed
 */

/**
 * WEBHOOK CONFIGURATION
 * =====================
 */

// Register webhooks for each account via command:
// php artisan stripe:manage register-webhook --id=1

// Or manually using StripeWebhookService:
$account = StripeAccount::find(1);
$result = \App\Services\StripeWebhookService::registerWebhook(
    $account,
    'https://yourdomain.com/webhooks/stripe',
    [
        'charge.succeeded',
        'charge.failed',
        'charge.refunded',
        'charge.dispute.created',
        'checkout.session.completed',
    ]
);

// Add webhook route in routes/web.php:
Route::post('/webhooks/stripe', [\App\Http\Controllers\Gateway\StripeWebhookController::class, 'handleWebhook']);

/**
 * ARTISAN COMMANDS
 * ================
 */

// List all accounts:
// php artisan stripe:manage list

// Validate account credentials:
// php artisan stripe:manage validate --id=1

// List webhooks for account:
// php artisan stripe:manage webhooks --id=1

// Register webhook:
// php artisan stripe:manage register-webhook --id=1

// Test account:
// php artisan stripe:manage test --id=1

/**
 * MODEL RELATIONSHIPS
 * ===================
 */

// Deposit model should have:
// public function stripeAccount()
// {
//     return $this->belongsTo(StripeAccount::class);
// }

// StripeAccount model methods:
// - scopeActive($query) - Get only active accounts
// - statusBadge() - Get status badge HTML

/**
 * ERROR HANDLING
 * ==============
 */

// All operations are logged. Check logs in:
// storage/logs/laravel.log

// Common errors and solutions:

// 1. "No active Stripe account available"
//    - Ensure at least one StripeAccount exists and is_active = true
//    - Validate credentials with: php artisan stripe:manage validate --id=1

// 2. "Webhook signature verification failed"
//    - Check webhook_secret is correctly stored
//    - Ensure webhook URL is publicly accessible
//    - Test with: php artisan stripe:manage test --id=1

// 3. "Deposit not found"
//    - Verify deposit ID exists in database
//    - Check metadata includes deposit_id

/**
 * IMPLEMENTATION CHECKLIST
 * ========================
 */

// 1. ✓ Create stripe_accounts table (migration exists)
// 2. ✓ Add stripe_account_id to deposits (migration exists)
// 3. ✓ Create StripeAccountHelper class (helps with selection)
// 4. ✓ Update Stripe payment controllers (StripeJs, StripeV3)
// 5. ✓ Create StripeWebhookController (handles webhooks)
// 6. ✓ Create StripeWebhookService (manages webhooks)
// 7. ✓ Create StripeManageCommand (CLI tool)
// 8. Add webhook route to routes/web.php
// 9. Update .env with webhook URL if needed
// 10. Run migrations: php artisan migrate
// 11. Populate stripe_accounts via admin panel
// 12. Register webhooks for each account
// 13. Test with: php artisan stripe:manage test --id=1

/**
 * SECURITY NOTES
 * ==============
 */

// 1. Keep webhook secrets safe - store securely in database
// 2. Verify webhook signatures using webhook_secret
// 3. Use environment variables for sensitive data:
//    STRIPE_SECRET_KEY, STRIPE_PUBLISHABLE_KEY, STRIPE_WEBHOOK_SECRET
// 4. Log all payment operations for audit trail
// 5. Rate limit webhook endpoints
// 6. Use HTTPS for all webhook URLs
// 7. Validate all user input before processing

/**
 * TESTING
 * =======
 */

// Test payment flow:
// 1. Create test Stripe account
// 2. Add to database via admin panel
// 3. Create test deposit
// 4. Verify selectStripeAccount returns correct account
// 5. Process payment and check stripe_account_id is saved
// 6. Verify webhook endpoint receives events
// 7. Check deposit status updates on webhook

/**
 * MONITORING
 * ==========
 */

// Monitor these metrics:
// - Payment success rate per account
// - Failed webhooks count
// - Processing time per account
// - Active accounts vs inactive
// - Deposit distribution across accounts

// Check logs for issues:
// tail -f storage/logs/laravel.log | grep -i stripe

/**
 * MIGRATION GUIDE FROM SINGLE TO MULTI-ACCOUNT
 * ==============================================
 */

// If migrating from single account:
// 1. Create new stripe_accounts table
// 2. Export existing gateway parameters
// 3. Insert into stripe_accounts with amount ranges
// 4. Add stripe_account_id to all existing deposits
// 5. Update payment controllers to use helper
// 6. Deploy webhooks for all accounts
// 7. Monitor for 48 hours
// 8. Clean up legacy gateway_parameter data
