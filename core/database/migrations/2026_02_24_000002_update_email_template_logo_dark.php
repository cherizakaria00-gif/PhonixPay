<?php

use App\Models\GeneralSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('general_settings')) {
            return;
        }

        $general = GeneralSetting::first();
        if (!$general || !$general->email_template) {
            return;
        }

        $template = $general->email_template;
        $template = str_replace(
            'https://phonixpay.com/assets/images/logoIcon/logo.png',
            'https://phonixpay.com/assets/images/logoIcon/logo_dark.png',
            $template
        );

        $general->email_template = $template;
        $general->save();
    }

    public function down(): void
    {
        // Intentionally left empty to avoid destructive template rollback.
    }
};
