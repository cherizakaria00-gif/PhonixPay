<?php

use App\Models\Frontend;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('frontends')) {
            return;
        }

        $template = 'basic';
        try {
            $template = activeTemplateName() ?: 'basic';
        } catch (\Throwable $e) {
            $template = 'basic';
        }

        $details = <<<'HTML'
<p>PCI DSS (Payment Card Industry Data Security Standard) is the global security standard created by the major card brands to protect cardholder data. Any business that stores, processes, or transmits card data must comply with PCI DSS requirements.</p>

<h4>Why PCI DSS Matters</h4>
<ul>
    <li><strong>Protects cardholder data:</strong> reduces the risk of theft, fraud, and data breaches.</li>
    <li><strong>Builds trust:</strong> shows customers you take security seriously.</li>
    <li><strong>Required by card networks:</strong> compliance is mandatory for merchants and payment service providers.</li>
</ul>

<h4>How Fluji Supports Compliance</h4>
<ul>
    <li><strong>Secure processing:</strong> payments are processed using PCI DSS-compliant infrastructure.</li>
    <li><strong>Data minimization:</strong> we avoid storing sensitive authentication data.</li>
    <li><strong>Encryption and access controls:</strong> strict controls protect data in transit and at rest.</li>
    <li><strong>Monitoring and testing:</strong> continuous monitoring and regular security reviews.</li>
</ul>

<h4>Merchant Responsibility</h4>
<p>If you handle card data directly, you are responsible for maintaining PCI DSS compliance for your environment. We strongly recommend using hosted checkout or tokenized payment flows to reduce your compliance scope.</p>

<h4>Contact</h4>
<p>For PCI DSS compliance questions, contact Fluji support via flujipay.com.</p>
HTML;

        $record = Frontend::where('data_keys', 'policy_pages.element')
            ->where('slug', 'pci-dss-compliance')
            ->where('tempname', $template)
            ->first();

        if (!$record) {
            $record = Frontend::where('data_keys', 'policy_pages.element')
                ->where('slug', 'pci-dss')
                ->where('tempname', $template)
                ->first();

            if ($record) {
                $record->slug = 'pci-dss-compliance';
            } else {
                $record = new Frontend();
                $record->data_keys = 'policy_pages.element';
                $record->slug = 'pci-dss-compliance';
                $record->tempname = $template;
            }
        }

        $record->data_values = [
            'title' => 'PCI DSS Compliance',
            'details' => $details,
        ];
        $record->save();
    }

    public function down(): void
    {
        // Intentionally left empty to avoid destructive changes to legal content.
    }
};
