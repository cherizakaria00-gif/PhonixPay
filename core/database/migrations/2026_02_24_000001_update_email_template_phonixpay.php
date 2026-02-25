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

        $template = <<<'HTML'
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<!--[if !mso]><!-->
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<!--<![endif]-->
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title></title>
<style type="text/css">
.ReadMsgBody { width: 100%; background-color: #f1f5f9; }
.ExternalClass { width: 100%; background-color: #f1f5f9; }
.ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div { line-height: 100%; }
html { width: 100%; }
body { -webkit-text-size-adjust: none; -ms-text-size-adjust: none; margin: 0; padding: 0; }
table { border-spacing: 0; table-layout: fixed; margin: 0 auto; border-collapse: collapse; }
table table table { table-layout: auto; }
.yshortcuts a { border-bottom: none !important; }
img:hover { opacity: 0.95 !important; }
a { color: #1d4ed8; text-decoration: none; }
.textbutton a { font-family: 'Open Sans', Arial, sans-serif !important; }
.btn-link a { color: #FFFFFF !important; }

@media only screen and (max-width: 480px) {
  body { width: auto !important; }
  *[class="table-inner"] { width: 90% !important; text-align: center !important; }
  *[class="table-full"] { width: 100% !important; text-align: center !important; }
  img[class="img1"] { width: 100% !important; height: auto !important; }
}
</style>

<table bgcolor="#f1f5f9" width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
  <tbody>
    <tr><td height="50"></td></tr>
    <tr>
      <td align="center" style="text-align:center;vertical-align:top;font-size:0;">
        <table align="center" border="0" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td align="center" width="600">
                <table class="table-inner" width="95%" border="0" align="center" cellpadding="0" cellspacing="0">
                  <tbody>
                    <tr>
                      <td bgcolor="#1d4ed8" style="border-top-left-radius:10px; border-top-right-radius:10px;text-align:center;vertical-align:top;font-size:0;" align="center">
                        <table width="90%" border="0" align="center" cellpadding="0" cellspacing="0">
                          <tbody>
                            <tr><td height="18"></td></tr>
                            <tr>
                              <td align="center" style="font-family: 'Open Sans', Arial, sans-serif; color:#FFFFFF; font-size:16px; font-weight: bold;">FlujiPay System Notification</td>
                            </tr>
                            <tr><td height="18"></td></tr>
                          </tbody>
                        </table>
                      </td>
                    </tr>
                  </tbody>
                </table>

                <table class="table-inner" width="95%" border="0" cellspacing="0" cellpadding="0">
                  <tbody>
                    <tr>
                      <td bgcolor="#FFFFFF" align="center" style="text-align:center;vertical-align:top;font-size:0;">
                        <table align="center" width="90%" border="0" cellspacing="0" cellpadding="0">
                          <tbody>
                            <tr><td height="32"></td></tr>
                            <tr>
                              <td align="center" style="vertical-align:top;font-size:0;">
                                <a href="https://flujipay.com">
                                  <img style="display:block; line-height:0px; font-size:0px; border:0px; height:48px;" src="https://flujipay.com/assets/images/logoIcon/logo.png" alt="FlujiPay">
                                </a>
                              </td>
                            </tr>
                            <tr><td height="26"></td></tr>
                            <tr>
                              <td align="center" style="font-family: 'Open Sans', Arial, sans-serif; font-size: 22px;color:#0f172a;font-weight: bold;">Hello {{fullname}} ({{username}})</td>
                            </tr>
                            <tr>
                              <td align="center" style="text-align:center;vertical-align:top;font-size:0;">
                                <table width="40" border="0" align="center" cellpadding="0" cellspacing="0">
                                  <tbody><tr><td height="18" style="border-bottom:3px solid #1d4ed8;"></td></tr></tbody>
                                </table>
                              </td>
                            </tr>
                            <tr><td height="16"></td></tr>
                            <tr>
                              <td align="left" style="font-family: 'Open Sans', Arial, sans-serif; color:#475569; font-size:16px; line-height: 28px;">{{message}}</td>
                            </tr>
                            <tr><td height="36"></td></tr>
                          </tbody>
                        </table>
                      </td>
                    </tr>
                    <tr>
                      <td height="45" align="center" bgcolor="#f8fafc" style="border-bottom-left-radius:10px;border-bottom-right-radius:10px;">
                        <table align="center" width="90%" border="0" cellspacing="0" cellpadding="0">
                          <tbody>
                            <tr><td height="10"></td></tr>
                            <tr>
                              <td class="preference-link" align="center" style="font-family: 'Open Sans', Arial, sans-serif; color:#94a3b8; font-size:13px;">
                                © {{site_name}} · All Rights Reserved · <a href="https://flujipay.com">flujipay.com</a>
                              </td>
                            </tr>
                            <tr><td height="10"></td></tr>
                          </tbody>
                        </table>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </td>
            </tr>
          </tbody>
        </table>
      </td>
    </tr>
    <tr><td height="60"></td></tr>
  </tbody>
</table>
HTML;

        $general = GeneralSetting::first();
        if ($general) {
            $general->email_template = $template;
            $general->save();
        }
    }

    public function down(): void
    {
        // Intentionally left empty to avoid destructive template rollback.
    }
};
