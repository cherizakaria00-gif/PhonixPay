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

        $pages = [
            [
                'slug' => 'privacy-policy',
                'title' => 'Privacy Policy',
                'details' => <<<'HTML'
<p>PhonixPay (“PhonixPay”, “we”, “us”, “our”) provides payment services at phonixpay.com. This Privacy Policy explains how we collect, use, share, and protect information when you use our services.</p>

<h4>Information We Collect</h4>
<ul>
    <li><strong>Account and verification data:</strong> name, email, phone number, business details, identification and KYC documents.</li>
    <li><strong>Transaction data:</strong> payment amounts, currencies, references, chargebacks, and settlement information.</li>
    <li><strong>Device and usage data:</strong> IP address, browser type, device identifiers, and activity logs.</li>
    <li><strong>Cookies and similar technologies:</strong> to enable core functionality, security, and analytics.</li>
</ul>

<h4>How We Use Information</h4>
<ul>
    <li>Provide, operate, and improve the PhonixPay services.</li>
    <li>Verify identity, detect fraud, and comply with legal obligations.</li>
    <li>Process payments, resolve disputes, and manage chargebacks.</li>
    <li>Provide customer support and communicate service updates.</li>
</ul>

<h4>How We Share Information</h4>
<ul>
    <li>With payment processors, banking partners, and technical vendors to deliver services.</li>
    <li>With regulators, law enforcement, or courts when required by law.</li>
    <li>With business partners only when necessary to complete a transaction.</li>
</ul>

<h4>Data Retention</h4>
<p>We retain information as long as needed to provide services, meet legal and regulatory requirements, and resolve disputes.</p>

<h4>Security</h4>
<p>We use administrative, technical, and physical safeguards to protect data. No system is 100% secure, but we continuously monitor and improve our controls.</p>

<h4>International Transfers</h4>
<p>Your information may be transferred to and processed in countries where we or our partners operate, consistent with applicable law.</p>

<h4>Your Rights</h4>
<p>Depending on your jurisdiction, you may have rights to access, correct, or delete your personal data, or object to certain processing.</p>

<h4>Contact Us</h4>
<p>If you have questions about this Privacy Policy, contact PhonixPay support via phonixpay.com.</p>
HTML,
            ],
            [
                'slug' => 'terms-of-service',
                'title' => 'Terms of Service',
                'details' => <<<'HTML'
<p>These Terms of Service govern your use of PhonixPay and the services available at phonixpay.com. By using our services, you agree to these terms.</p>

<h4>Eligibility</h4>
<p>You must be legally able to enter into a contract and comply with all applicable laws to use PhonixPay.</p>

<h4>Account and Verification</h4>
<p>You agree to provide accurate information and complete any required verification. We may suspend accounts that fail verification or violate these terms.</p>

<h4>Payment Services</h4>
<p>PhonixPay enables merchants to accept payments from customers. We may update or discontinue features to maintain security and compliance.</p>

<h4>Fees and Charges</h4>
<p>Applicable fees are disclosed in your dashboard or agreement. You are responsible for fees, refunds, and chargebacks as described in our documentation.</p>

<h4>Chargebacks and Disputes</h4>
<p>Chargebacks are handled according to card network rules and applicable law. You agree to cooperate in dispute resolution and provide supporting documentation.</p>

<h4>Prohibited Activities</h4>
<ul>
    <li>Illegal or fraudulent transactions.</li>
    <li>Attempts to bypass KYC/AML controls.</li>
    <li>Use that violates sanctions, regulations, or payment network rules.</li>
</ul>

<h4>Compliance</h4>
<p>You must comply with AML, sanctions, and consumer protection laws. PhonixPay may request additional documentation to meet regulatory requirements.</p>

<h4>Intellectual Property</h4>
<p>All platform content, branding, and software are owned by PhonixPay or its licensors.</p>

<h4>Termination</h4>
<p>We may suspend or terminate access for violations or risk concerns. You may close your account at any time, subject to settlement of outstanding obligations.</p>

<h4>Disclaimers and Limitation of Liability</h4>
<p>Services are provided “as is” to the extent permitted by law. PhonixPay is not liable for indirect or consequential damages.</p>

<h4>Governing Law</h4>
<p>These terms are governed by applicable laws in the jurisdictions where PhonixPay operates.</p>
HTML,
            ],
            [
                'slug' => 'terms-of-condition',
                'title' => 'Terms & Conditions',
                'details' => <<<'HTML'
<p>These Terms & Conditions apply to your access and use of the PhonixPay website and any related content at phonixpay.com.</p>

<h4>Website Use</h4>
<p>You may access and use this website for lawful purposes only. You agree not to misuse or attempt to disrupt our services.</p>

<h4>Content and Accuracy</h4>
<p>We aim to keep content accurate and current, but we do not guarantee completeness or uninterrupted access.</p>

<h4>Third-Party Links</h4>
<p>Our website may contain links to third-party sites. PhonixPay is not responsible for their content or practices.</p>

<h4>Updates</h4>
<p>We may update these Terms & Conditions from time to time. Continued use of the website constitutes acceptance of updates.</p>

<h4>Contact</h4>
<p>For questions about these Terms & Conditions, visit phonixpay.com and contact our support team.</p>
HTML,
            ],
            [
                'slug' => 'aml-policy',
                'title' => 'AML Policy',
                'details' => <<<'HTML'
<p>PhonixPay maintains a robust Anti-Money Laundering (AML) program designed to prevent the misuse of our services for illicit activities.</p>

<h4>Customer Due Diligence (CDD)</h4>
<p>We verify the identity of customers and merchants through KYC checks, including identity and business verification.</p>

<h4>Transaction Monitoring</h4>
<p>We monitor transactions to detect suspicious activity, unusual patterns, and potential fraud.</p>

<h4>Sanctions and PEP Screening</h4>
<p>We screen against sanctions lists and politically exposed persons (PEP) databases where required.</p>

<h4>Reporting and Recordkeeping</h4>
<p>We maintain records and cooperate with authorities as required by law.</p>

<h4>Ongoing Compliance</h4>
<p>Our AML policies are reviewed and updated regularly to align with regulatory requirements and industry standards.</p>

<h4>Contact</h4>
<p>For AML inquiries, contact PhonixPay via phonixpay.com.</p>
HTML,
            ],
            [
                'slug' => 'pci-dss',
                'title' => 'PCI DSS Compliance',
                'details' => <<<'HTML'
<p>PhonixPay is committed to protecting cardholder data and maintaining strong security practices aligned with the PCI DSS (Payment Card Industry Data Security Standard).</p>

<h4>Secure Processing</h4>
<p>Card data is processed using PCI DSS-compliant service providers and secure encryption protocols.</p>

<h4>Data Minimization</h4>
<p>PhonixPay does not store full card numbers or sensitive authentication data on our servers.</p>

<h4>Security Controls</h4>
<p>We implement access controls, monitoring, and vulnerability management to protect payment data and infrastructure.</p>

<h4>Ongoing Assurance</h4>
<p>We review and enhance our security controls on an ongoing basis to meet evolving threats and compliance requirements.</p>

<h4>Contact</h4>
<p>For PCI DSS compliance inquiries, contact PhonixPay via phonixpay.com.</p>
HTML,
            ],
        ];

        foreach ($pages as $page) {
            $slug = $page['slug'];
            $record = Frontend::where('data_keys', 'policy_pages.element')
                ->where('slug', $slug)
                ->where('tempname', $template)
                ->first();

            if (!$record) {
                $record = new Frontend();
                $record->data_keys = 'policy_pages.element';
                $record->slug = $slug;
                $record->tempname = $template;
            }

            $record->data_values = [
                'title' => $page['title'],
                'details' => $page['details'],
            ];
            $record->save();
        }
    }

    public function down(): void
    {
        // Intentionally left empty to avoid destructive changes to legal content.
    }
};
