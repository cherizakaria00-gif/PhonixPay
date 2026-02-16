<?php

namespace App\Console\Commands;

use App\Helpers\StripeAccountHelper;
use App\Models\StripeAccount;
use App\Services\StripeWebhookService;
use Illuminate\Console\Command;
use Stripe\Stripe;

/**
 * Stripe Account Management Command
 * php artisan stripe:manage
 */
class StripeManageCommand extends Command
{
    protected $signature = 'stripe:manage {action} {--id=}';
    protected $description = 'Manage Stripe accounts and webhooks';

    public function handle()
    {
        $action = $this->argument('action');
        $accountId = $this->option('id');

        switch ($action) {
            case 'list':
                $this->listAccounts();
                break;

            case 'validate':
                $this->validateAccount($accountId);
                break;

            case 'webhooks':
                $this->listWebhooks($accountId);
                break;

            case 'register-webhook':
                $this->registerWebhook($accountId);
                break;

            case 'test':
                $this->testAccount($accountId);
                break;

            default:
                $this->error("Unknown action: {$action}");
                $this->showHelp();
        }
    }

    private function listAccounts()
    {
        $accounts = StripeAccount::all();

        if ($accounts->isEmpty()) {
            $this->info('No Stripe accounts found.');
            return;
        }

        $this->table(
            ['ID', 'Name', 'Status', 'Min Amount', 'Max Amount'],
            $accounts->map(function ($account) {
                return [
                    $account->id,
                    $account->name,
                    $account->is_active ? 'Active' : 'Inactive',
                    $account->min_amount,
                    $account->max_amount ?: 'Unlimited',
                ];
            })->toArray()
        );
    }

    private function validateAccount($accountId)
    {
        if (!$accountId) {
            $this->error('Please provide account ID with --id option');
            return;
        }

        $account = StripeAccount::find($accountId);

        if (!$account) {
            $this->error("Account {$accountId} not found.");
            return;
        }

        $this->info("Validating account: {$account->name}...");

        if (StripeAccountHelper::validateCredentials($account)) {
            $this->info('✓ Account credentials are valid');
        } else {
            $this->error('✗ Account credentials are invalid');
        }
    }

    private function listWebhooks($accountId)
    {
        if (!$accountId) {
            $this->error('Please provide account ID with --id option');
            return;
        }

        $account = StripeAccount::find($accountId);

        if (!$account) {
            $this->error("Account {$accountId} not found.");
            return;
        }

        $this->info("Listing webhooks for: {$account->name}");

        $webhooks = StripeWebhookService::listWebhooks($account);

        if (empty($webhooks)) {
            $this->info('No webhooks found for this account.');
            return;
        }

        $this->table(
            ['ID', 'URL', 'Status', 'Events'],
            array_map(function ($webhook) {
                return [
                    substr($webhook['id'], 0, 20) . '...',
                    $webhook['url'],
                    $webhook['status'],
                    implode(', ', array_slice($webhook['events'], 0, 2)) . '...',
                ];
            }, $webhooks)
        );
    }

    private function registerWebhook($accountId)
    {
        if (!$accountId) {
            $accountId = $this->ask('Enter Stripe account ID');
        }

        $account = StripeAccount::find($accountId);

        if (!$account) {
            $this->error("Account {$accountId} not found.");
            return;
        }

        $url = $this->ask('Enter webhook URL', route('webhooks.stripe'));

        $this->info("Registering webhook for: {$account->name}");

        $result = StripeWebhookService::registerWebhook($account, $url);

        if ($result) {
            $this->info('✓ Webhook registered successfully');
            $this->table(['Property', 'Value'], [
                ['ID', $result['id']],
                ['Secret', $result['secret']],
                ['URL', $result['url']],
            ]);
        } else {
            $this->error('✗ Failed to register webhook');
        }
    }

    private function testAccount($accountId)
    {
        if (!$accountId) {
            $this->error('Please provide account ID with --id option');
            return;
        }

        $account = StripeAccount::find($accountId);

        if (!$account) {
            $this->error("Account {$accountId} not found.");
            return;
        }

        $this->info("Testing account: {$account->name}...");

        Stripe::setApiKey($account->secret_key);

        try {
            $account_info = \Stripe\Account::retrieve();

            $this->info('✓ Account test successful');
            $this->table(['Property', 'Value'], [
                ['Account ID', $account_info->id],
                ['Display Name', $account_info->settings->dashboard->display_name],
                ['Country', $account_info->country],
                ['Type', $account_info->type],
            ]);
        } catch (\Exception $e) {
            $this->error('✗ Account test failed: ' . $e->getMessage());
        }
    }

    private function showHelp()
    {
        $this->info('Available actions:');
        $this->info('  list               - List all Stripe accounts');
        $this->info('  validate --id=ID   - Validate account credentials');
        $this->info('  webhooks --id=ID   - List webhooks for account');
        $this->info('  register-webhook --id=ID  - Register webhook for account');
        $this->info('  test --id=ID       - Test account connection');
    }
}
