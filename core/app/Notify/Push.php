<?php

namespace App\Notify;

use App\Notify\NotifyProcess;
use App\Notify\Notifiable;

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
        //get message from parent
        $message = $this->getMessage();
        if (gs('pn') && $message) {
            try {
                $credentialsFilePath = getFilePath('pushConfig').'/push_config.json';
                $client = new \Google_Client();
                $client->setAuthConfig($credentialsFilePath);
                $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
                $client->fetchAccessTokenWithAssertion();
                $token = $client->getAccessToken();
                $access_token = $token['access_token'];
                $headers = [
                    "Authorization: Bearer $access_token",
                    'Content-Type: application/json'
                ];

                $data['notification'] = [
                    'body'  => $message,
                    'title' => $this->getTitle(),
                    'image' => asset(getFilePath('push')).'/'.$this->pushImage,
                ];

                // Android-specific block: high priority wakes the device even when
                // the app is backgrounded or fully closed (Doze-safe delivery).
                // channel_id must match a channel created by the mobile app on first launch.
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
                    // Deep-link IDs for mobile navigation
                    'transaction_id'   => (string) ($this->shortCodes['trx'] ?? ''),
                    'ticket_id'        => (string) ($this->shortCodes['ticket_id'] ?? ''),
                    'payment_link_id'  => (string) ($this->shortCodes['payment_link_id'] ?? ''),
                ];

                $fcmUrl = 'https://fcm.googleapis.com/v1/projects/'.gs('firebase_config')->projectId.'/messages:send';

                foreach ($this->toAddress as $toAddress) {
                    $data['token'] = $toAddress;
                    $payloadData['message'] = $data;
                    $payload = json_encode($payloadData);

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $fcmUrl);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

                    $result   = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $curlErr  = curl_error($ch);
                    curl_close($ch);

                    if ($curlErr) {
                        error_log("FCM curl error (token: {$toAddress}): {$curlErr}");
                        $this->createErrorLog("FCM curl error: {$curlErr}");
                        continue;
                    }

                    if ($httpCode !== 200) {
                        error_log("FCM delivery failed HTTP {$httpCode} (token: {$toAddress}): {$result}");
                        $this->createErrorLog("FCM push failed (HTTP {$httpCode}): {$result}");

                        // Remove token that FCM has marked as unregistered/invalid
                        $decoded = json_decode($result, true);
                        $fcmStatus = $decoded['error']['status'] ?? '';
                        if ($httpCode === 404 || in_array($fcmStatus, ['NOT_FOUND', 'UNREGISTERED'])) {
                            \App\Models\DeviceToken::where('token', $toAddress)->delete();
                        }
                    }
                }
            } catch(\Exception $e){
                $this->createErrorLog($e->getMessage());
                session()->flash('firebase_error',$e->getMessage());
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
}
