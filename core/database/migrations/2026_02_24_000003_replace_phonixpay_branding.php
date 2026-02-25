<?php

use App\Models\Frontend;
use App\Models\GeneralSetting;
use App\Models\NotificationTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $search = [
        'www.phonixpay.com',
        'phonixpay.com',
        'PhonixPay',
        'PHONIX PAY',
    ];

    private array $replace = [
        'www.flujipay.com',
        'flujipay.com',
        'FlujiPay',
        'FLUJIPAY',
    ];

    public function up(): void
    {
        $this->updateFrontendContent();
        $this->updateNotificationTemplates();
        $this->updateGeneralSettings();
    }

    public function down(): void
    {
        // Intentionally left empty.
    }

    private function updateFrontendContent(): void
    {
        if (!Schema::hasTable('frontends')) {
            return;
        }

        Frontend::query()->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                $changed = false;

                if ($row->data_values) {
                    $newDataValues = $this->replaceRecursive($row->data_values);
                    if ($newDataValues != $row->data_values) {
                        $row->data_values = $newDataValues;
                        $changed = true;
                    }
                }

                if ($row->seo_content) {
                    $newSeoContent = $this->replaceRecursive($row->seo_content);
                    if ($newSeoContent != $row->seo_content) {
                        $row->seo_content = $newSeoContent;
                        $changed = true;
                    }
                }

                $newSlug = $this->replaceString($row->slug);
                if ($newSlug !== $row->slug) {
                    $row->slug = $newSlug;
                    $changed = true;
                }

                if ($changed) {
                    $row->save();
                }
            }
        });
    }

    private function updateNotificationTemplates(): void
    {
        if (!Schema::hasTable('notification_templates')) {
            return;
        }

        NotificationTemplate::query()->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                $changed = false;

                foreach ([
                    'name',
                    'subject',
                    'push_title',
                    'email_body',
                    'sms_body',
                    'push_body',
                    'email_sent_from_name',
                    'email_sent_from_address',
                    'sms_sent_from',
                ] as $field) {
                    $current = $row->{$field};
                    $updated = $this->replaceString($current);
                    if ($updated !== $current) {
                        $row->{$field} = $updated;
                        $changed = true;
                    }
                }

                if ($row->shortcodes) {
                    $updatedShortcodes = $this->replaceRecursive($row->shortcodes);
                    if ($updatedShortcodes != $row->shortcodes) {
                        $row->shortcodes = $updatedShortcodes;
                        $changed = true;
                    }
                }

                if ($changed) {
                    $row->save();
                }
            }
        });
    }

    private function updateGeneralSettings(): void
    {
        if (!Schema::hasTable('general_settings')) {
            return;
        }

        $general = GeneralSetting::first();
        if (!$general) {
            return;
        }

        $changed = false;

        foreach ([
            'site_name',
            'email_from',
            'email_from_name',
            'email_template',
            'sms_template',
            'sms_from',
            'push_title',
            'push_template',
        ] as $field) {
            $current = $general->{$field};
            $updated = $this->replaceString($current);
            if ($updated !== $current) {
                $general->{$field} = $updated;
                $changed = true;
            }
        }

        if ($changed) {
            $general->save();
        }
    }

    private function replaceRecursive($value)
    {
        if (is_string($value)) {
            return $this->replaceString($value);
        }

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->replaceRecursive($item);
            }
            return $value;
        }

        if (is_object($value)) {
            foreach ($value as $key => $item) {
                $value->{$key} = $this->replaceRecursive($item);
            }
            return $value;
        }

        return $value;
    }

    private function replaceString($value)
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }

        return str_replace($this->search, $this->replace, $value);
    }
};
