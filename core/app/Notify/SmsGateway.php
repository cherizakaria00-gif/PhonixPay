<?php

namespace App\Notify;

use App\Lib\CurlRequest;
use MessageBird\Client as MessageBirdClient;
use MessageBird\Objects\Message;
use App\Notify\Textmagic\Services\TextmagicRestClient;
use Twilio\Rest\Client;
use Vonage\Client as NexmoClient;
use Vonage\Client\Credentials\Basic;
use Vonage\SMS\Message\SMS;

class SmsGateway{

    /**
     * the number where the sms will send
     *
     * @var string
     */
    public $to;

    /**
     * the name where from the sms will send
     *
     * @var string
     */
    public $from;


    /**
     * the message which will be send
     *
     * @var string
     */
    public $message;


    /**
     * the configuration of sms gateway
     *
     * @var object
     */
    public $config;

	public function clickatell()
	{
		$message = urlencode($this->message);
		$api_key = $this->config->clickatell->api_key;
		@file_get_contents("https://platform.clickatell.com/messages/http/send?apiKey=$api_key&to=$this->to&content=$message");
	}

	public function infobip(){
		$infobipConfig = $this->config->infobip ?? null;
		$apiKey = trim($infobipConfig->api_key ?? '');
		$baseUrl = rtrim(trim($infobipConfig->base_url ?? 'https://api.infobip.com'), '/');
		$senderId = trim($infobipConfig->sender_id ?? $this->from ?? '');

		if (!$apiKey) {
			throw new \Exception('Infobip API key is missing');
		}

		if (!$senderId) {
			throw new \Exception('Infobip sender ID is missing');
		}

		$payload = [
			'messages' => [
				[
					'destinations' => [
						[
							'to' => (string) $this->to,
						],
					],
					'from' => $senderId,
					'text' => (string) $this->message,
				],
			],
		];

		$ch = curl_init($baseUrl . '/sms/2/text/advanced');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Authorization: App ' . $apiKey,
			'Content-Type: application/json',
			'Accept: application/json',
		]);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

		$result = curl_exec($ch);
		$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$curlError = curl_error($ch);
		curl_close($ch);

		if ($result === false || $curlError) {
			throw new \Exception('Infobip cURL error: ' . $curlError);
		}

		if ($httpCode < 200 || $httpCode >= 300) {
			$errorMessage = 'HTTP ' . $httpCode;
			$response = json_decode($result, true);

			if (json_last_error() === JSON_ERROR_NONE && is_array($response)) {
				$serviceError = $response['requestError']['serviceException']['text'] ?? null;
				$requestError = $response['requestError']['text'] ?? null;
				$detail = $serviceError ?: $requestError;
				if ($detail) {
					$errorMessage .= ' - ' . $detail;
				}
			}

			throw new \Exception('Infobip request failed: ' . $errorMessage);
		}
	}

	public function messageBird(){
		$MessageBird = new MessageBirdClient($this->config->message_bird->api_key);
	  	$Message = new Message();
	  	$Message->originator = $this->from;
	  	$Message->recipients = array($this->to);
	  	$Message->body = $this->message;
	  	$MessageBird->messages->create($Message);
	}

	public function nexmo(){
		$basic  = new Basic($this->config->nexmo->api_key, $this->config->nexmo->api_secret);
		$client = new NexmoClient($basic);
		$response = $client->sms()->send(
		    new SMS($this->to, $this->from, $this->message)
		);
		 $response->current();
	}

	public function smsBroadcast(){
		$message = urlencode($this->message);
		@file_get_contents("https://api.smsbroadcast.com.au/api-adv.php?username=".$this->config->sms_broadcast->username."&password=".$this->config->sms_broadcast->password."&to=$this->to&from=$this->from&message=$message&ref=112233&maxsplit=5&delay=15");
	}

	public function twilio(){
		$account_sid = $this->config->twilio->account_sid;
		$auth_token = $this->config->twilio->auth_token;
		$twilio_number = $this->config->twilio->from;

		$client = new Client($account_sid, $auth_token);
		$client->messages->create(
		    '+'.$this->to,
		    array(
		        'from' => $twilio_number,
		        'body' => $this->message
		    )
		);
	}

	public function textMagic(){
        $client = new TextmagicRestClient($this->config->text_magic->username, $this->config->text_magic->apiv2_key);
        $client->messages->create(
            array(
                'text' => $this->message,
                'phones' => $this->to
            )
        );
	}

	public function custom(){
		$credential = $this->config->custom;
		$method = $credential->method;
		$shortCodes = [
			'{{message}}'=>$this->message,
			'{{number}}'=>$this->to,
		];
		$body = array_combine($credential->body->name,$credential->body->value);
		foreach ($body as $key => $value) {
			$bodyData = str_replace($value,@$shortCodes[$value] ?? $value ,$value);
			$body[$key] = $bodyData;
		}
		$header = array_combine($credential->headers->name,$credential->headers->value);
		if ($method == 'get') {
			$credential->url = $credential->url.'?'.http_build_query($body);
			CurlRequest::curlContent($credential->url,$header);
		}else{
			CurlRequest::curlPostContent($credential->url,$body,$header);
		}
	}
}
