<?php

namespace App\Notify;

use App\Notify\NotifyProcess;
use App\Notify\Notifiable;
use Illuminate\Support\Facades\Log;

class Push extends NotifyProcess implements Notifiable{

    /**
    * Device Id of receiver
    *
    * @var array
    */
	public $deviceId;

    public $redirectUrl;

    public $pushImage;


    /**
    * Assign value to properties
    *
    * @return void
    */
	public function __construct(){
		$this->statusField = 'push_status';
		$this->body = 'push_body';
		$this->globalTemplate = 'push_template';
		$this->notifyConfig = 'firebase_config';
	}


    public function redirectForApp($getTemplateName){

        $screens = [
            'TRANSACTIONS'   => ['BAL_ADD', 'BAL_SUB', 'DEPOSIT_COMPLETE', 'DEPOSIT_REJECT'],
            'PAYOUT'         => ['WITHDRAW_APPROVE', 'WITHDRAW_REJECT', 'WITHDRAW_REQUEST', 'INSUFFICIENT_WITHDRAW_BALANCE'],
            'PROFILE'        => ['KYC_APPROVE', 'KYC_REJECT'],
            'SUPPORT_DETAIL' => ['ADMIN_SUPPORT_REPLY'],
            'NOTIFICATIONS'  => ['DEFAULT'],
        ];

        foreach($screens as $screen => $array){
            if(in_array($getTemplateName ,$array)){
                return $screen;
            }
        }

        return 'NOTIFICATIONS';
    }


    /**
    * Send notification
    *
    * @return void|bool
    */
    public function send(){
        $message = $this->getMessage();
        if (gs('pn') && $message) {
            try {
                Log::info('FCM send invoked', [
                    'template' => $this->templateName ?? null,
                    'user_id' => $this->user->id ?? null,
                    'token_count' => is_array($this->toAddress ?? null) ? count($this->toAddress) : 0,
                ]);

                if (empty($this->toAddress)) {
                    Log::warning('FCM send skipped: no device tokens found', [
                        'template' => $this->templateName ?? null,
                        'user_id' => $this->user->id ?? null,
                    ]);
                    return false;
                }

                $credentialsFilePath = $this->resolvePushConfigPath();
                $projectId = gs('firebase_config')->projectId ?? null;

                if (!$credentialsFilePath || !is_file($credentialsFilePath) || !$projectId) {
                    Log::warning('FCM send skipped: missing Firebase config', [
                        'template' => $this->templateName ?? null,
                        'user_id' => $this->user->id ?? null,
                        'credentials_exists' => $credentialsFilePath ? is_file($credentialsFilePath) : false,
                        'credentials_path' => $credentialsFilePath,
                        'project_id' => $projectId,
                    ]);
                    return false;
                }

                $client = new \Google_Client();
                $client->setAuthConfig($credentialsFilePath);
                $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
                try {
                    $client->fetchAccessTokenWithAssertion();
                    $token = $client->getAccessToken();
                } catch (\Throwable $e) {
                    // Keep technical detail in server logs, but do not create admin notification noise.
                    Log::error('FCM token generation failed', [
                        'template' => $this->templateName ?? null,
                        'user_id' => $this->user->id ?? null,
                        'message' => $e->getMessage(),
                    ]);
                    return false;
                }

                if (empty($token['access_token'])) {
                    Log::error('FCM token generation failed: empty access token', [
                        'template' => $this->templateName ?? null,
                        'user_id' => $this->user->id ?? null,
                    ]);
                    return false;
                }

                $access_token = $token['access_token'];
                $headers = [
                    "Authorization: Bearer $access_token",
                    'Content-Type: application/json'
                ];

                $data['notification'] = [
                    'body'  => $message,
                    'title' => $this->getTitle(),
                ];

                if (!empty($this->pushImage)) {
                    $data['notification']['image'] = asset(getFilePath('push')).'/'.$this->pushImage;
                }

                $data['android'] = [
                    'priority' => 'high',
                    'notification' => [
                        'channel_id'              => 'flujipay-default',
                        'sound'                   => 'default',
                        'default_sound'           => true,
                        'default_vibrate_timings' => true,
                        'visibility'              => 'PUBLIC',
                        'notification_priority'   => 'PRIORITY_HIGH',
                    ],
                ];

                $data['data'] = [
                    'icon'             => siteFavicon(),
                    'click_action'     => $this->redirectUrl,
                    'app_click_action' => $this->redirectForApp($this->templateName),
                    'transaction_id'   => (string) ($this->shortCodes['trx'] ?? ''),
                    'ticket_id'        => (string) ($this->shortCodes['ticket_id'] ?? ''),
                    'payment_link_id'  => (string) ($this->shortCodes['payment_link_id'] ?? ''),
                ];

                $fcmUrl = 'https://fcm.googleapis.com/v1/projects/'.$projectId.'/messages:send';
                Log::info('FCM configuration ready', [
                    'project_id' => $projectId,
                    'template' => $this->templateName ?? null,
                    'user_id' => $this->user->id ?? null,
                ]);

                foreach ($this->toAddress as $toAddress) {
                    $data['token'] = $toAddress;
                    $payloadData['message'] = $data;
                    $payload = json_encode($payloadData);
                    $maskedToken = strlen($toAddress) > 16 ? substr($toAddress, 0, 8).'...'.substr($toAddress, -8) : $toAddress;

                    Log::info('FCM sending to token', [
                        'user_id' => $this->user->id ?? null,
                        'template' => $this->templateName ?? null,
                        'token' => $maskedToken,
                        'payload' => $payloadData,
                    ]);

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $fcmUrl);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

                    $result   = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $curlErr  = curl_error($ch);
                    curl_close($ch);

                    if ($curlErr) {
                        Log::error('FCM curl error', [
                            'user_id' => $this->user->id ?? null,
                            'template' => $this->templateName ?? null,
                            'token' => $maskedToken,
                            'error' => $curlErr,
                        ]);
                        error_log("FCM curl error (token: {$toAddress}): {$curlErr}");
                        continue;
                    }

                    Log::info('FCM delivery response', [
                        'user_id' => $this->user->id ?? null,
                        'template' => $this->templateName ?? null,
                        'token' => $maskedToken,
                        'http_code' => $httpCode,
                        'result' => $result,
                    ]);

                    if ($httpCode !== 200) {
                        Log::warning('FCM delivery failed', [
                            'user_id' => $this->user->id ?? null,
                            'template' => $this->templateName ?? null,
                            'token' => $maskedToken,
                            'http_code' => $httpCode,
                            'result' => $result,
                        ]);
                        error_log("FCM delivery failed HTTP {$httpCode} (token: {$toAddress}): {$result}");

                        $decoded = json_decode($result, true);
                        $fcmStatus = $decoded['error']['status'] ?? '';
                        if ($httpCode === 404 || in_array($fcmStatus, ['NOT_FOUND', 'UNREGISTERED'])) {
                            \App\Models\DeviceToken::where('token', $toAddress)->delete();
                        }
                    }
                }
            } catch(\Exception $e){
                Log::error('FCM send exception', [
                    'template' => $this->templateName ?? null,
                    'user_id' => $this->user->id ?? null,
                    'message' => $e->getMessage(),
                ]);
                return false;
            }
        }

    }



    /**
    * Configure some properties
    *
    * @return void
    */
	public function prevConfiguration(){
		if ($this->user) {
            $this->deviceId = $this->user->deviceTokens()->pluck('token')->toArray();
			$this->receiverName = $this->user->fullname;
		}
		$this->toAddress = $this->deviceId;
	}

    private function getTitle(){
        return $this->replaceTemplateShortCode($this->template->push_title ?? gs('push_title'));
    }

    /**
     * Resolve push config absolute path across web/cli contexts.
     */
    private function resolvePushConfigPath(): ?string
    {
        $relative = trim(getFilePath('pushConfig'), '/').'/push_config.json';

        $candidates = [
            $relative,
            base_path($relative),
            public_path($relative),
            dirname(base_path()).'/'.$relative,
            ($_SERVER['DOCUMENT_ROOT'] ?? '').'/'.$relative,
        ];

        foreach ($candidates as $candidate) {
            if (!$candidate) {
                continue;
            }
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
