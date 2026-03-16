<?php

use App\Constants\Status;
use App\Models\NotificationTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('notification_templates')) {
            return;
        }

        $columns = Schema::getColumnListing('notification_templates');

        $templates = [
            [
                'name' => 'KYC Approved',
                'act' => 'KYC_APPROVED',
                'subject' => 'Your KYC has been approved',
                'email_body' => 'Hello {{fullname}},' . PHP_EOL
                    . 'Your KYC verification has been approved.' . PHP_EOL
                    . 'Fixed charge: {{payment_fixed_charge}}' . PHP_EOL
                    . 'Percent charge: {{payment_percent_charge}}' . PHP_EOL
                    . 'You can continue with setup fee activation.',
                'sms_body' => 'KYC approved. Fixed {{payment_fixed_charge}}, Percent {{payment_percent_charge}}.',
                'push_title' => 'KYC approved',
                'push_body' => 'Your KYC is approved. Continue setup fee activation.',
                'shortcodes' => json_encode([
                    'payment_fixed_charge' => 'Merchant fixed charge',
                    'payment_percent_charge' => 'Merchant percent charge',
                ]),
            ],
            [
                'name' => 'Account Approved',
                'act' => 'ACCOUNT_APPROVED',
                'subject' => 'Your merchant account is now active',
                'email_body' => 'Hello {{fullname}},' . PHP_EOL
                    . 'Your setup fee has been approved and your merchant account is now active.' . PHP_EOL
                    . 'Setup fee amount: {{setup_fee_amount}} USDT' . PHP_EOL
                    . 'Status: {{account_status}}',
                'sms_body' => 'Account active. Setup fee approved: {{setup_fee_amount}} USDT.',
                'push_title' => 'Account approved',
                'push_body' => 'Your merchant account is now active.',
                'shortcodes' => json_encode([
                    'setup_fee_amount' => 'Approved setup fee amount in USDT',
                    'account_status' => 'Account status text',
                ]),
            ],
            [
                'name' => 'New Transaction',
                'act' => 'NEW_TRANSACTION',
                'subject' => 'New transaction received',
                'email_body' => 'A new transaction has been received.' . PHP_EOL
                    . 'Gateway: {{method_name}}' . PHP_EOL
                    . 'Amount: {{amount}} {{method_currency}}' . PHP_EOL
                    . 'Charge: {{charge}}' . PHP_EOL
                    . 'Reference: {{trx}}' . PHP_EOL
                    . 'Current balance: {{post_balance}}',
                'sms_body' => 'New transaction {{trx}} amount {{amount}} {{method_currency}}.',
                'push_title' => 'New transaction',
                'push_body' => 'Transaction {{trx}} received: {{amount}} {{method_currency}}.',
                'shortcodes' => json_encode([
                    'method_name' => 'Payment method name',
                    'method_currency' => 'Method currency',
                    'amount' => 'Amount in system currency',
                    'charge' => 'Gateway charge',
                    'trx' => 'Transaction reference',
                    'post_balance' => 'Current account balance',
                ]),
            ],
            [
                'name' => 'New Update',
                'act' => 'NEW_UPDATE',
                'subject' => 'A new account update is available',
                'email_body' => 'Hello {{fullname}},' . PHP_EOL
                    . '{{update_title}}' . PHP_EOL
                    . '{{update_message}}' . PHP_EOL
                    . 'Updated at: {{updated_at}}',
                'sms_body' => '{{update_title}} - {{update_message}}',
                'push_title' => 'New update',
                'push_body' => '{{update_title}}',
                'shortcodes' => json_encode([
                    'update_title' => 'Update title',
                    'update_message' => 'Update details',
                    'updated_at' => 'Update datetime',
                ]),
            ],
        ];

        foreach ($templates as $templateData) {
            if (NotificationTemplate::where('act', $templateData['act'])->exists()) {
                continue;
            }

            $payload = [];
            foreach ($templateData as $column => $value) {
                if (in_array($column, $columns, true)) {
                    $payload[$column] = $value;
                }
            }

            if (in_array('email_status', $columns, true)) {
                $payload['email_status'] = Status::ENABLE;
            }
            if (in_array('sms_status', $columns, true)) {
                $payload['sms_status'] = Status::DISABLE;
            }
            if (in_array('push_status', $columns, true)) {
                $payload['push_status'] = Status::ENABLE;
            }
            if (in_array('created_at', $columns, true)) {
                $payload['created_at'] = now();
            }
            if (in_array('updated_at', $columns, true)) {
                $payload['updated_at'] = now();
            }

            if (!empty($payload)) {
                $template = new NotificationTemplate();
                foreach ($payload as $key => $value) {
                    $template->{$key} = $value;
                }
                $template->save();
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('notification_templates')) {
            return;
        }

        NotificationTemplate::query()
            ->whereIn('act', ['KYC_APPROVED', 'ACCOUNT_APPROVED', 'NEW_TRANSACTION', 'NEW_UPDATE'])
            ->delete();
    }
};

