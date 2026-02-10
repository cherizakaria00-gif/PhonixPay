ALTER TABLE `gateways` ADD UNIQUE(`code`);

-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 15, 2023 at 01:40 PM
-- Server version: 10.4.24-MariaDB
-- PHP Version: 8.1.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `laramin`
--

-- --------------------------------------------------------

--
-- Table structure for table `update_logs`
--

CREATE TABLE `update_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `version` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `update_log` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `update_logs`
--
ALTER TABLE `update_logs`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `update_logs`
--
ALTER TABLE `update_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

ALTER TABLE `general_settings` ADD `system_customized` TINYINT(1) NOT NULL DEFAULT '0' AFTER `system_info`;

ALTER TABLE `frontends` ADD `tempname` VARCHAR(40) NULL DEFAULT NULL AFTER `data_values`;

CREATE TABLE `cron_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(40) DEFAULT NULL,
  `alias` varchar(40) DEFAULT NULL,
  `action` text DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `cron_schedule_id` int(11) NOT NULL DEFAULT 0,
  `next_run` datetime DEFAULT NULL,
  `last_run` datetime DEFAULT NULL,
  `is_running` tinyint(1) NOT NULL DEFAULT 1,
  `is_default` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cron_job_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cron_job_id` int(11) NOT NULL DEFAULT 0,
  `start_at` datetime DEFAULT NULL,
  `end_at` datetime DEFAULT NULL,
  `duration` int(11) NOT NULL DEFAULT 0,
  `error` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cron_schedules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(40) DEFAULT NULL,
  `interval` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


ALTER TABLE `cron_jobs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cron_job_logs`
--
ALTER TABLE `cron_job_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cron_schedules`
--
ALTER TABLE `cron_schedules`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `cron_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `cron_job_logs`
--
ALTER TABLE `cron_job_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cron_schedules`
--
ALTER TABLE `cron_schedules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `deposits` CHANGE `final_amo` `final_amount` DECIMAL(28,8) NOT NULL DEFAULT '0.00000000';

ALTER TABLE `personal_access_tokens` ADD `expires_at` TIMESTAMP NULL DEFAULT NULL AFTER `last_used_at`;

ALTER TABLE `general_settings` ADD `socialite_credentials` TEXT AFTER `active_template`;

UPDATE `general_settings` SET `socialite_credentials` = '{\"google\":{\"client_id\":\"------------\",\"client_secret\":\"-------------\",\"status\":1},\"facebook\":{\"client_id\":\"------\",\"client_secret\":\"------\",\"status\":1},\"linkedin\":{\"client_id\":\"-----\",\"client_secret\":\"-----\",\"status\":1}}' WHERE `general_settings`.`id` = 1;

ALTER TABLE `general_settings` ADD `firebase_config` TEXT AFTER `sms_config`;

ALTER TABLE `general_settings` ADD `pn` TINYINT(1) NOT NULL DEFAULT '1' AFTER `sn`;

ALTER TABLE `general_settings` CHANGE `sms_body` `sms_template` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;

ALTER TABLE `general_settings` ADD `push_template` VARCHAR(255) NULL DEFAULT NULL AFTER `sms_from`;

ALTER TABLE `notification_templates` ADD `push_body` TEXT AFTER `sms_body`, ADD `push_status` TINYINT(1) NOT NULL DEFAULT '0' AFTER `push_body`;

ALTER TABLE `notification_templates` CHANGE `shortcodes` `shortcodes` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER `push_body`, CHANGE `email_status` `email_status` TINYINT(1) NOT NULL DEFAULT '1' AFTER `shortcodes`, CHANGE `sms_status` `sms_status` TINYINT(1) NOT NULL DEFAULT '1' AFTER `email_status`;

CREATE TABLE `device_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `is_app` tinyint(1) NOT NULL DEFAULT 0,
  `token` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `device_tokens`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `device_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;


ALTER TABLE `notification_logs` ADD `image` VARCHAR(255) NULL DEFAULT NULL AFTER `notification_type`;

ALTER TABLE `frontends` ADD `slug` VARCHAR(255) NULL DEFAULT NULL AFTER `tempname`;
ALTER TABLE `frontends` ADD `seo_content` LONGTEXT AFTER `data_values`;


UPDATE `extensions` SET `script` = '<script async src=\"https://www.googletagmanager.com/gtag/js?id={{measurement_id}}\"></script>\n                <script>\n                  window.dataLayer = window.dataLayer || [];\n                  function gtag(){dataLayer.push(arguments);}\n                  gtag(\"js\", new Date());\n                \n                  gtag(\"config\", \"{{measurement_id}}\");\n                </script>' WHERE `extensions`.`act` = 'google-analytics';

UPDATE `extensions` SET `shortcode` = '{\"measurement_id\":{\"title\":\"Measurement ID\",\"value\":\"------\"}}' WHERE `extensions`.`act` = 'google-analytics';

INSERT INTO `gateways` (`id`, `form_id`, `code`, `name`, `alias`, `status`, `gateway_parameters`, `supported_currencies`, `crypto`, `extra`, `description`, `created_at`, `updated_at`) VALUES (NULL, '0', '510', 'Binance', 'Binance', '1', '{\"api_key\":{\"title\":\"API Key\",\"global\":true,\"value\":\"tsu3tjiq0oqfbtmlbevoeraxhfbp3brejnm9txhjxcp4to29ujvakvfl1ibsn3ja\"},\"secret_key\":{\"title\":\"Secret Key\",\"global\":true,\"value\":\"jzngq4t04ltw8d4iqpi7admfl8tvnpehxnmi34id1zvfaenbwwvsvw7llw3zdko8\"},\"merchant_id\":{\"title\":\"Merchant ID\",\"global\":true,\"value\":\"231129033\"}}', '{\"BTC\":\"Bitcoin\",\"USD\":\"USD\",\"BNB\":\"BNB\"}', '1', '', NULL, NULL, '2023-02-14 11:08:04');


INSERT INTO `gateways` (`id`, `form_id`, `code`, `name`, `alias`, `status`, `gateway_parameters`, `supported_currencies`, `crypto`, `extra`, `description`, `created_at`, `updated_at`) VALUES (NULL, '0', '124', 'SslCommerz', 'SslCommerz', '1', '{\"store_id\": {\"title\": \"Store ID\",\"global\": true,\"value\": \"---------\"},\"store_password\": {\"title\": \"Store Password\",\"global\": true,\"value\": \"----------\"}}', '{\"BDT\":\"BDT\",\"USD\":\"USD\",\"EUR\":\"EUR\",\"SGD\":\"SGD\",\"INR\":\"INR\",\"MYR\":\"MYR\"}', '0', NULL, NULL, NULL, '2023-05-06 13:43:01');

INSERT INTO `gateways` (`id`, `form_id`, `code`, `name`, `alias`, `status`, `gateway_parameters`, `supported_currencies`, `crypto`, `extra`, `description`, `created_at`, `updated_at`) VALUES (NULL, '0', '125', 'Aamarpay', 'Aamarpay', '1', '{\"store_id\": {\"title\": \"Store ID\",\"global\": true,\"value\": \"---------\"},\"signature_key\": {\"title\": \"Signature Key\",\"global\": true,\"value\": \"----------\"}}', '{\"BDT\":\"BDT\"}', '0', NULL, NULL, NULL, '2023-05-06 13:43:01');


UPDATE `gateways` SET `extra` = '{\"cron\":{\"title\": \"Cron Job URL\",\"value\":\"ipn.Binance\"}}' WHERE `gateways`.`alias` = 'Binance';

ALTER TABLE `general_settings` ADD `paginate_number` INT NOT NULL DEFAULT '0' AFTER `system_customized`;

ALTER TABLE `cron_jobs` CHANGE `is_default` `is_default` TINYINT(1) NOT NULL DEFAULT '1';

ALTER TABLE `cron_job_logs` CHANGE `cron_job_id` `cron_job_id` INT UNSIGNED NOT NULL DEFAULT '0';

ALTER TABLE `users` ADD `provider` VARCHAR(255) NULL DEFAULT NULL AFTER `remember_token`;

ALTER TABLE `deposits` ADD `success_url` VARCHAR(255) NULL DEFAULT NULL AFTER `admin_feedback`, ADD `failed_url` VARCHAR(255) NULL DEFAULT NULL AFTER `success_url`;


UPDATE `notification_templates` SET `name` = 'KYC Rejected' WHERE `notification_templates`.`act` = 'KYC_REJECT';

ALTER TABLE `general_settings` ADD `currency_format` INT NOT NULL DEFAULT '0' COMMENT '1=>Both\r\n2=>Text Only\r\n3=>Symbol Only' AFTER `paginate_number`;

ALTER TABLE `notification_templates` ADD `email_sent_from_name` VARCHAR(40) NULL DEFAULT NULL AFTER `email_status`, ADD `email_sent_from_address` VARCHAR(40) NULL DEFAULT NULL AFTER `email_sent_from_name`;

ALTER TABLE `notification_templates` ADD `sms_sent_from` VARCHAR(40) NULL DEFAULT NULL AFTER `sms_status`;

ALTER TABLE `support_attachments` CHANGE `support_message_id` `support_message_id` INT UNSIGNED NOT NULL DEFAULT '0';

ALTER TABLE `users` ADD `kyc_rejection_reason` VARCHAR(255) NULL DEFAULT NULL AFTER `kyc_data`;

UPDATE `notification_templates` SET `shortcodes` = '{\"reason\":\"Rejection Reason\"}' WHERE `notification_templates`.`act` = 'KYC_REJECT';

DELETE FROM `gateway_currencies` WHERE `gateway_currencies`.`method_code` = 108;
DELETE FROM `gateways` WHERE `gateways`.`code` = 108;

ALTER TABLE `gateways` ADD `image` VARCHAR(255) NULL DEFAULT NULL AFTER `alias`;

ALTER TABLE `general_settings` ADD `in_app_payment` TINYINT(1) NOT NULL DEFAULT '1' AFTER `force_ssl`;

ALTER TABLE `gateway_currencies` DROP `image`;

ALTER TABLE `notification_templates` CHANGE `subj` `subject` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;

ALTER TABLE `notification_templates` ADD `push_title` VARCHAR(255) NULL DEFAULT NULL AFTER `subject`;

ALTER TABLE `general_settings` ADD `push_title` VARCHAR(255) NULL DEFAULT NULL AFTER `sms_from`;

ALTER TABLE `general_settings` ADD `email_from_name` VARCHAR(255) NULL DEFAULT NULL AFTER `email_from`;

ALTER TABLE `deposits` ADD `last_cron` INT NULL DEFAULT '0' AFTER `failed_url`;

DELETE FROM `gateway_currencies` WHERE `gateway_currencies`.`gateway_alias` = 'NowPaymentsCheckout';

UPDATE `gateways` SET `supported_currencies` = '{\"USD\":\"USD\",\"EUR\":\"EUR\"}' WHERE `gateways`.`alias` = 'NowPaymentsCheckout';


ALTER TABLE `general_settings` ADD `available_version` VARCHAR(40) NULL DEFAULT '1.0' AFTER `last_cron`; 

ALTER TABLE `general_settings` DROP `system_info`;

ALTER TABLE `users` CHANGE `username` `username` VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;

ALTER TABLE `pages` ADD `seo_content` TEXT NULL DEFAULT NULL AFTER `secs`;

ALTER TABLE `general_settings` CHANGE `currency_format` `currency_format` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '1=>Both\r\n2=>Text Only\r\n3=>Symbol Only';

ALTER TABLE `general_settings` CHANGE `paginate_number` `paginate_number` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `users` CHANGE `address` `addressss` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'contains full address';

ALTER TABLE `users` ADD `country_name` VARCHAR(255) NULL DEFAULT NULL AFTER `password`, ADD `dial_code` INT NOT NULL DEFAULT '0' AFTER `country_name`, ADD `city` VARCHAR(255) NULL DEFAULT NULL AFTER `dial_code`, ADD `state` VARCHAR(255) NULL DEFAULT NULL AFTER `city`, ADD `zip` VARCHAR(255) NULL DEFAULT NULL AFTER `state`, ADD `address` TEXT NULL DEFAULT NULL AFTER `zip`;

ALTER TABLE `users` CHANGE `dial_code` `dial_code` VARCHAR(40) NOT NULL DEFAULT '0';
ALTER TABLE `users` CHANGE `dial_code` `dial_code` VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;


ALTER TABLE `users` CHANGE `dial_code` `dial_code` VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER `email`;

UPDATE `gateways` SET `crypto` = '0' WHERE `gateways`.`alias` = 'TwoCheckout';

UPDATE `users`
SET 
    `country_name` = JSON_UNQUOTE(JSON_EXTRACT(`addressss`, '$.country')),
    `zip` = JSON_UNQUOTE(JSON_EXTRACT(`addressss`, '$.zip')),
    `city` = JSON_UNQUOTE(JSON_EXTRACT(`addressss`, '$.city')),
    `state` = JSON_UNQUOTE(JSON_EXTRACT(`addressss`, '$.state')),
    `address` = JSON_UNQUOTE(JSON_EXTRACT(`addressss`, '$.address'));

ALTER TABLE `users` DROP `addressss`;

UPDATE `extensions` SET `support` = 'fb_com.png' WHERE `extensions`.`act` = 'fb-comment';
ALTER TABLE `users` ADD `provider_id` VARCHAR(255) NULL DEFAULT NULL AFTER `provider`;
ALTER TABLE `notification_logs` ADD `user_read` TINYINT NOT NULL DEFAULT '0' AFTER `image`;

ALTER TABLE `users` CHANGE `provider` `provider` VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;

ALTER TABLE `general_settings` ADD `country_list` LONGTEXT NULL DEFAULT NULL AFTER `socialite_credentials`;

    UPDATE `general_settings` SET `country_list` = '{\n  \"AF\": {\n    \"country\": \"Afghanistan\",\n    \"dial_code\": \"93\"\n  },\n  \"AX\": {\n    \"country\": \"Aland Islands\",\n    \"dial_code\": \"358\"\n  },\n  \"AL\": {\n    \"country\": \"Albania\",\n    \"dial_code\": \"355\"\n  },\n  \"DZ\": {\n    \"country\": \"Algeria\",\n    \"dial_code\": \"213\"\n  },\n  \"AS\": {\n    \"country\": \"AmericanSamoa\",\n    \"dial_code\": \"1684\"\n  },\n  \"AD\": {\n    \"country\": \"Andorra\",\n    \"dial_code\": \"376\"\n  },\n  \"AO\": {\n    \"country\": \"Angola\",\n    \"dial_code\": \"244\"\n  },\n  \"AI\": {\n    \"country\": \"Anguilla\",\n    \"dial_code\": \"1264\"\n  },\n  \"AQ\": {\n    \"country\": \"Antarctica\",\n    \"dial_code\": \"672\"\n  },\n  \"AG\": {\n    \"country\": \"Antigua and Barbuda\",\n    \"dial_code\": \"1268\"\n  },\n  \"AR\": {\n    \"country\": \"Argentina\",\n    \"dial_code\": \"54\"\n  },\n  \"AM\": {\n    \"country\": \"Armenia\",\n    \"dial_code\": \"374\"\n  },\n  \"AW\": {\n    \"country\": \"Aruba\",\n    \"dial_code\": \"297\"\n  },\n  \"AU\": {\n    \"country\": \"Australia\",\n    \"dial_code\": \"61\"\n  },\n  \"AT\": {\n    \"country\": \"Austria\",\n    \"dial_code\": \"43\"\n  },\n  \"AZ\": {\n    \"country\": \"Azerbaijan\",\n    \"dial_code\": \"994\"\n  },\n  \"BS\": {\n    \"country\": \"Bahamas\",\n    \"dial_code\": \"1242\"\n  },\n  \"BH\": {\n    \"country\": \"Bahrain\",\n    \"dial_code\": \"973\"\n  },\n  \"BD\": {\n    \"country\": \"Bangladesh\",\n    \"dial_code\": \"880\"\n  },\n  \"BB\": {\n    \"country\": \"Barbados\",\n    \"dial_code\": \"1246\"\n  },\n  \"BY\": {\n    \"country\": \"Belarus\",\n    \"dial_code\": \"375\"\n  },\n  \"BE\": {\n    \"country\": \"Belgium\",\n    \"dial_code\": \"32\"\n  },\n  \"BZ\": {\n    \"country\": \"Belize\",\n    \"dial_code\": \"501\"\n  },\n  \"BJ\": {\n    \"country\": \"Benin\",\n    \"dial_code\": \"229\"\n  },\n  \"BM\": {\n    \"country\": \"Bermuda\",\n    \"dial_code\": \"1441\"\n  },\n  \"BT\": {\n    \"country\": \"Bhutan\",\n    \"dial_code\": \"975\"\n  },\n  \"BO\": {\n    \"country\": \"Plurinational State of Bolivia\",\n    \"dial_code\": \"591\"\n  },\n  \"BA\": {\n    \"country\": \"Bosnia and Herzegovina\",\n    \"dial_code\": \"387\"\n  },\n  \"BW\": {\n    \"country\": \"Botswana\",\n    \"dial_code\": \"267\"\n  },\n  \"BR\": {\n    \"country\": \"Brazil\",\n    \"dial_code\": \"55\"\n  },\n  \"IO\": {\n    \"country\": \"British Indian Ocean Territory\",\n    \"dial_code\": \"246\"\n  },\n  \"BN\": {\n    \"country\": \"Brunei Darussalam\",\n    \"dial_code\": \"673\"\n  },\n  \"BG\": {\n    \"country\": \"Bulgaria\",\n    \"dial_code\": \"359\"\n  },\n  \"BF\": {\n    \"country\": \"Burkina Faso\",\n    \"dial_code\": \"226\"\n  },\n  \"BI\": {\n    \"country\": \"Burundi\",\n    \"dial_code\": \"257\"\n  },\n  \"KH\": {\n    \"country\": \"Cambodia\",\n    \"dial_code\": \"855\"\n  },\n  \"CM\": {\n    \"country\": \"Cameroon\",\n    \"dial_code\": \"237\"\n  },\n  \"CA\": {\n    \"country\": \"Canada\",\n    \"dial_code\": \"1\"\n  },\n  \"CV\": {\n    \"country\": \"Cape Verde\",\n    \"dial_code\": \"238\"\n  },\n  \"KY\": {\n    \"country\": \"Cayman Islands\",\n    \"dial_code\": \" 345\"\n  },\n  \"CF\": {\n    \"country\": \"Central African Republic\",\n    \"dial_code\": \"236\"\n  },\n  \"TD\": {\n    \"country\": \"Chad\",\n    \"dial_code\": \"235\"\n  },\n  \"CL\": {\n    \"country\": \"Chile\",\n    \"dial_code\": \"56\"\n  },\n  \"CN\": {\n    \"country\": \"China\",\n    \"dial_code\": \"86\"\n  },\n  \"CX\": {\n    \"country\": \"Christmas Island\",\n    \"dial_code\": \"61\"\n  },\n  \"CC\": {\n    \"country\": \"Cocos (Keeling) Islands\",\n    \"dial_code\": \"61\"\n  },\n  \"CO\": {\n    \"country\": \"Colombia\",\n    \"dial_code\": \"57\"\n  },\n  \"KM\": {\n    \"country\": \"Comoros\",\n    \"dial_code\": \"269\"\n  },\n  \"CG\": {\n    \"country\": \"Congo\",\n    \"dial_code\": \"242\"\n  },\n  \"CD\": {\n    \"country\": \"The Democratic Republic of the Congo\",\n    \"dial_code\": \"243\"\n  },\n  \"CK\": {\n    \"country\": \"Cook Islands\",\n    \"dial_code\": \"682\"\n  },\n  \"CR\": {\n    \"country\": \"Costa Rica\",\n    \"dial_code\": \"506\"\n  },\n  \"CI\": {\n    \"country\": \"Cote d\'Ivoire\",\n    \"dial_code\": \"225\"\n  },\n  \"HR\": {\n    \"country\": \"Croatia\",\n    \"dial_code\": \"385\"\n  },\n  \"CU\": {\n    \"country\": \"Cuba\",\n    \"dial_code\": \"53\"\n  },\n  \"CY\": {\n    \"country\": \"Cyprus\",\n    \"dial_code\": \"357\"\n  },\n  \"CZ\": {\n    \"country\": \"Czech Republic\",\n    \"dial_code\": \"420\"\n  },\n  \"DK\": {\n    \"country\": \"Denmark\",\n    \"dial_code\": \"45\"\n  },\n  \"DJ\": {\n    \"country\": \"Djibouti\",\n    \"dial_code\": \"253\"\n  },\n  \"DM\": {\n    \"country\": \"Dominica\",\n    \"dial_code\": \"1767\"\n  },\n  \"DO\": {\n    \"country\": \"Dominican Republic\",\n    \"dial_code\": \"1849\"\n  },\n  \"EC\": {\n    \"country\": \"Ecuador\",\n    \"dial_code\": \"593\"\n  },\n  \"EG\": {\n    \"country\": \"Egypt\",\n    \"dial_code\": \"20\"\n  },\n  \"SV\": {\n    \"country\": \"El Salvador\",\n    \"dial_code\": \"503\"\n  },\n  \"GQ\": {\n    \"country\": \"Equatorial Guinea\",\n    \"dial_code\": \"240\"\n  },\n  \"ER\": {\n    \"country\": \"Eritrea\",\n    \"dial_code\": \"291\"\n  },\n  \"EE\": {\n    \"country\": \"Estonia\",\n    \"dial_code\": \"372\"\n  },\n  \"ET\": {\n    \"country\": \"Ethiopia\",\n    \"dial_code\": \"251\"\n  },\n  \"FK\": {\n    \"country\": \"Falkland Islands (Malvinas)\",\n    \"dial_code\": \"500\"\n  },\n  \"FO\": {\n    \"country\": \"Faroe Islands\",\n    \"dial_code\": \"298\"\n  },\n  \"FJ\": {\n    \"country\": \"Fiji\",\n    \"dial_code\": \"679\"\n  },\n  \"FI\": {\n    \"country\": \"Finland\",\n    \"dial_code\": \"358\"\n  },\n  \"FR\": {\n    \"country\": \"France\",\n    \"dial_code\": \"33\"\n  },\n  \"GF\": {\n    \"country\": \"French Guiana\",\n    \"dial_code\": \"594\"\n  },\n  \"PF\": {\n    \"country\": \"French Polynesia\",\n    \"dial_code\": \"689\"\n  },\n  \"GA\": {\n    \"country\": \"Gabon\",\n    \"dial_code\": \"241\"\n  },\n  \"GM\": {\n    \"country\": \"Gambia\",\n    \"dial_code\": \"220\"\n  },\n  \"GE\": {\n    \"country\": \"Georgia\",\n    \"dial_code\": \"995\"\n  },\n  \"DE\": {\n    \"country\": \"Germany\",\n    \"dial_code\": \"49\"\n  },\n  \"GH\": {\n    \"country\": \"Ghana\",\n    \"dial_code\": \"233\"\n  },\n  \"GI\": {\n    \"country\": \"Gibraltar\",\n    \"dial_code\": \"350\"\n  },\n  \"GR\": {\n    \"country\": \"Greece\",\n    \"dial_code\": \"30\"\n  },\n  \"GL\": {\n    \"country\": \"Greenland\",\n    \"dial_code\": \"299\"\n  },\n  \"GD\": {\n    \"country\": \"Grenada\",\n    \"dial_code\": \"1473\"\n  },\n  \"GP\": {\n    \"country\": \"Guadeloupe\",\n    \"dial_code\": \"590\"\n  },\n  \"GU\": {\n    \"country\": \"Guam\",\n    \"dial_code\": \"1671\"\n  },\n  \"GT\": {\n    \"country\": \"Guatemala\",\n    \"dial_code\": \"502\"\n  },\n  \"GG\": {\n    \"country\": \"Guernsey\",\n    \"dial_code\": \"44\"\n  },\n  \"GN\": {\n    \"country\": \"Guinea\",\n    \"dial_code\": \"224\"\n  },\n  \"GW\": {\n    \"country\": \"Guinea-Bissau\",\n    \"dial_code\": \"245\"\n  },\n  \"GY\": {\n    \"country\": \"Guyana\",\n    \"dial_code\": \"595\"\n  },\n  \"HT\": {\n    \"country\": \"Haiti\",\n    \"dial_code\": \"509\"\n  },\n  \"VA\": {\n    \"country\": \"Holy See (Vatican City State)\",\n    \"dial_code\": \"379\"\n  },\n  \"HN\": {\n    \"country\": \"Honduras\",\n    \"dial_code\": \"504\"\n  },\n  \"HK\": {\n    \"country\": \"Hong Kong\",\n    \"dial_code\": \"852\"\n  },\n  \"HU\": {\n    \"country\": \"Hungary\",\n    \"dial_code\": \"36\"\n  },\n  \"IS\": {\n    \"country\": \"Iceland\",\n    \"dial_code\": \"354\"\n  },\n  \"IN\": {\n    \"country\": \"India\",\n    \"dial_code\": \"91\"\n  },\n  \"ID\": {\n    \"country\": \"Indonesia\",\n    \"dial_code\": \"62\"\n  },\n  \"IR\": {\n    \"country\": \"Iran - Islamic Republic of Persian Gulf\",\n    \"dial_code\": \"98\"\n  },\n  \"IQ\": {\n    \"country\": \"Iraq\",\n    \"dial_code\": \"964\"\n  },\n  \"IE\": {\n    \"country\": \"Ireland\",\n    \"dial_code\": \"353\"\n  },\n  \"IM\": {\n    \"country\": \"Isle of Man\",\n    \"dial_code\": \"44\"\n  },\n  \"IL\": {\n    \"country\": \"Israel\",\n    \"dial_code\": \"972\"\n  },\n  \"IT\": {\n    \"country\": \"Italy\",\n    \"dial_code\": \"39\"\n  },\n  \"JM\": {\n    \"country\": \"Jamaica\",\n    \"dial_code\": \"1876\"\n  },\n  \"JP\": {\n    \"country\": \"Japan\",\n    \"dial_code\": \"81\"\n  },\n  \"JE\": {\n    \"country\": \"Jersey\",\n    \"dial_code\": \"44\"\n  },\n  \"JO\": {\n    \"country\": \"Jordan\",\n    \"dial_code\": \"962\"\n  },\n  \"KZ\": {\n    \"country\": \"Kazakhstan\",\n    \"dial_code\": \"77\"\n  },\n  \"KE\": {\n    \"country\": \"Kenya\",\n    \"dial_code\": \"254\"\n  },\n  \"KI\": {\n    \"country\": \"Kiribati\",\n    \"dial_code\": \"686\"\n  },\n  \"KP\": {\n    \"country\": \"Democratic People\'s Republic of Korea\",\n    \"dial_code\": \"850\"\n  },\n  \"KR\": {\n    \"country\": \"Republic of South Korea\",\n    \"dial_code\": \"82\"\n  },\n  \"KW\": {\n    \"country\": \"Kuwait\",\n    \"dial_code\": \"965\"\n  },\n  \"KG\": {\n    \"country\": \"Kyrgyzstan\",\n    \"dial_code\": \"996\"\n  },\n  \"LA\": {\n    \"country\": \"Laos\",\n    \"dial_code\": \"856\"\n  },\n  \"LV\": {\n    \"country\": \"Latvia\",\n    \"dial_code\": \"371\"\n  },\n  \"LB\": {\n    \"country\": \"Lebanon\",\n    \"dial_code\": \"961\"\n  },\n  \"LS\": {\n    \"country\": \"Lesotho\",\n    \"dial_code\": \"266\"\n  },\n  \"LR\": {\n    \"country\": \"Liberia\",\n    \"dial_code\": \"231\"\n  },\n  \"LY\": {\n    \"country\": \"Libyan Arab Jamahiriya\",\n    \"dial_code\": \"218\"\n  },\n  \"LI\": {\n    \"country\": \"Liechtenstein\",\n    \"dial_code\": \"423\"\n  },\n  \"LT\": {\n    \"country\": \"Lithuania\",\n    \"dial_code\": \"370\"\n  },\n  \"LU\": {\n    \"country\": \"Luxembourg\",\n    \"dial_code\": \"352\"\n  },\n  \"MO\": {\n    \"country\": \"Macao\",\n    \"dial_code\": \"853\"\n  },\n  \"MK\": {\n    \"country\": \"Macedonia\",\n    \"dial_code\": \"389\"\n  },\n  \"MG\": {\n    \"country\": \"Madagascar\",\n    \"dial_code\": \"261\"\n  },\n  \"MW\": {\n    \"country\": \"Malawi\",\n    \"dial_code\": \"265\"\n  },\n  \"MY\": {\n    \"country\": \"Malaysia\",\n    \"dial_code\": \"60\"\n  },\n  \"MV\": {\n    \"country\": \"Maldives\",\n    \"dial_code\": \"960\"\n  },\n  \"ML\": {\n    \"country\": \"Mali\",\n    \"dial_code\": \"223\"\n  },\n  \"MT\": {\n    \"country\": \"Malta\",\n    \"dial_code\": \"356\"\n  },\n  \"MH\": {\n    \"country\": \"Marshall Islands\",\n    \"dial_code\": \"692\"\n  },\n  \"MQ\": {\n    \"country\": \"Martinique\",\n    \"dial_code\": \"596\"\n  },\n  \"MR\": {\n    \"country\": \"Mauritania\",\n    \"dial_code\": \"222\"\n  },\n  \"MU\": {\n    \"country\": \"Mauritius\",\n    \"dial_code\": \"230\"\n  },\n  \"YT\": {\n    \"country\": \"Mayotte\",\n    \"dial_code\": \"262\"\n  },\n  \"MX\": {\n    \"country\": \"Mexico\",\n    \"dial_code\": \"52\"\n  },\n  \"FM\": {\n    \"country\": \"Federated States of Micronesia\",\n    \"dial_code\": \"691\"\n  },\n  \"MD\": {\n    \"country\": \"Moldova\",\n    \"dial_code\": \"373\"\n  },\n  \"MC\": {\n    \"country\": \"Monaco\",\n    \"dial_code\": \"377\"\n  },\n  \"MN\": {\n    \"country\": \"Mongolia\",\n    \"dial_code\": \"976\"\n  },\n  \"ME\": {\n    \"country\": \"Montenegro\",\n    \"dial_code\": \"382\"\n  },\n  \"MS\": {\n    \"country\": \"Montserrat\",\n    \"dial_code\": \"1664\"\n  },\n  \"MA\": {\n    \"country\": \"Morocco\",\n    \"dial_code\": \"212\"\n  },\n  \"MZ\": {\n    \"country\": \"Mozambique\",\n    \"dial_code\": \"258\"\n  },\n  \"MM\": {\n    \"country\": \"Myanmar\",\n    \"dial_code\": \"95\"\n  },\n  \"NA\": {\n    \"country\": \"Namibia\",\n    \"dial_code\": \"264\"\n  },\n  \"NR\": {\n    \"country\": \"Nauru\",\n    \"dial_code\": \"674\"\n  },\n  \"NP\": {\n    \"country\": \"Nepal\",\n    \"dial_code\": \"977\"\n  },\n  \"NL\": {\n    \"country\": \"Netherlands\",\n    \"dial_code\": \"31\"\n  },\n  \"AN\": {\n    \"country\": \"Netherlands Antilles\",\n    \"dial_code\": \"599\"\n  },\n  \"NC\": {\n    \"country\": \"New Caledonia\",\n    \"dial_code\": \"687\"\n  },\n  \"NZ\": {\n    \"country\": \"New Zealand\",\n    \"dial_code\": \"64\"\n  },\n  \"NI\": {\n    \"country\": \"Nicaragua\",\n    \"dial_code\": \"505\"\n  },\n  \"NE\": {\n    \"country\": \"Niger\",\n    \"dial_code\": \"227\"\n  },\n  \"NG\": {\n    \"country\": \"Nigeria\",\n    \"dial_code\": \"234\"\n  },\n  \"NU\": {\n    \"country\": \"Niue\",\n    \"dial_code\": \"683\"\n  },\n  \"NF\": {\n    \"country\": \"Norfolk Island\",\n    \"dial_code\": \"672\"\n  },\n  \"MP\": {\n    \"country\": \"Northern Mariana Islands\",\n    \"dial_code\": \"1670\"\n  },\n  \"NO\": {\n    \"country\": \"Norway\",\n    \"dial_code\": \"47\"\n  },\n  \"OM\": {\n    \"country\": \"Oman\",\n    \"dial_code\": \"968\"\n  },\n  \"PK\": {\n    \"country\": \"Pakistan\",\n    \"dial_code\": \"92\"\n  },\n  \"PW\": {\n    \"country\": \"Palau\",\n    \"dial_code\": \"680\"\n  },\n  \"PS\": {\n    \"country\": \"Palestinian Territory\",\n    \"dial_code\": \"970\"\n  },\n  \"PA\": {\n    \"country\": \"Panama\",\n    \"dial_code\": \"507\"\n  },\n  \"PG\": {\n    \"country\": \"Papua New Guinea\",\n    \"dial_code\": \"675\"\n  },\n  \"PY\": {\n    \"country\": \"Paraguay\",\n    \"dial_code\": \"595\"\n  },\n  \"PE\": {\n    \"country\": \"Peru\",\n    \"dial_code\": \"51\"\n  },\n  \"PH\": {\n    \"country\": \"Philippines\",\n    \"dial_code\": \"63\"\n  },\n  \"PN\": {\n    \"country\": \"Pitcairn\",\n    \"dial_code\": \"872\"\n  },\n  \"PL\": {\n    \"country\": \"Poland\",\n    \"dial_code\": \"48\"\n  },\n  \"PT\": {\n    \"country\": \"Portugal\",\n    \"dial_code\": \"351\"\n  },\n  \"PR\": {\n    \"country\": \"Puerto Rico\",\n    \"dial_code\": \"1939\"\n  },\n  \"QA\": {\n    \"country\": \"Qatar\",\n    \"dial_code\": \"974\"\n  },\n  \"RO\": {\n    \"country\": \"Romania\",\n    \"dial_code\": \"40\"\n  },\n  \"RU\": {\n    \"country\": \"Russia\",\n    \"dial_code\": \"7\"\n  },\n  \"RW\": {\n    \"country\": \"Rwanda\",\n    \"dial_code\": \"250\"\n  },\n  \"RE\": {\n    \"country\": \"Reunion\",\n    \"dial_code\": \"262\"\n  },\n  \"BL\": {\n    \"country\": \"Saint Barthelemy\",\n    \"dial_code\": \"590\"\n  },\n  \"SH\": {\n    \"country\": \"Saint Helena\",\n    \"dial_code\": \"290\"\n  },\n  \"KN\": {\n    \"country\": \"Saint Kitts and Nevis\",\n    \"dial_code\": \"1869\"\n  },\n  \"LC\": {\n    \"country\": \"Saint Lucia\",\n    \"dial_code\": \"1758\"\n  },\n  \"MF\": {\n    \"country\": \"Saint Martin\",\n    \"dial_code\": \"590\"\n  },\n  \"PM\": {\n    \"country\": \"Saint Pierre and Miquelon\",\n    \"dial_code\": \"508\"\n  },\n  \"VC\": {\n    \"country\": \"Saint Vincent and the Grenadines\",\n    \"dial_code\": \"1784\"\n  },\n  \"WS\": {\n    \"country\": \"Samoa\",\n    \"dial_code\": \"685\"\n  },\n  \"SM\": {\n    \"country\": \"San Marino\",\n    \"dial_code\": \"378\"\n  },\n  \"ST\": {\n    \"country\": \"Sao Tome and Principe\",\n    \"dial_code\": \"239\"\n  },\n  \"SA\": {\n    \"country\": \"Saudi Arabia\",\n    \"dial_code\": \"966\"\n  },\n  \"SN\": {\n    \"country\": \"Senegal\",\n    \"dial_code\": \"221\"\n  },\n  \"RS\": {\n    \"country\": \"Serbia\",\n    \"dial_code\": \"381\"\n  },\n  \"SC\": {\n    \"country\": \"Seychelles\",\n    \"dial_code\": \"248\"\n  },\n  \"SL\": {\n    \"country\": \"Sierra Leone\",\n    \"dial_code\": \"232\"\n  },\n  \"SG\": {\n    \"country\": \"Singapore\",\n    \"dial_code\": \"65\"\n  },\n  \"SK\": {\n    \"country\": \"Slovakia\",\n    \"dial_code\": \"421\"\n  },\n  \"SI\": {\n    \"country\": \"Slovenia\",\n    \"dial_code\": \"386\"\n  },\n  \"SB\": {\n    \"country\": \"Solomon Islands\",\n    \"dial_code\": \"677\"\n  },\n  \"SO\": {\n    \"country\": \"Somalia\",\n    \"dial_code\": \"252\"\n  },\n  \"ZA\": {\n    \"country\": \"South Africa\",\n    \"dial_code\": \"27\"\n  },\n  \"SS\": {\n    \"country\": \"South Sudan\",\n    \"dial_code\": \"211\"\n  },\n  \"GS\": {\n    \"country\": \"South Georgia and the South Sandwich Islands\",\n    \"dial_code\": \"500\"\n  },\n  \"ES\": {\n    \"country\": \"Spain\",\n    \"dial_code\": \"34\"\n  },\n  \"LK\": {\n    \"country\": \"Sri Lanka\",\n    \"dial_code\": \"94\"\n  },\n  \"SD\": {\n    \"country\": \"Sudan\",\n    \"dial_code\": \"249\"\n  },\n  \"SR\": {\n    \"country\": \"Suricountry\",\n    \"dial_code\": \"597\"\n  },\n  \"SJ\": {\n    \"country\": \"Svalbard and Jan Mayen\",\n    \"dial_code\": \"47\"\n  },\n  \"SZ\": {\n    \"country\": \"Swaziland\",\n    \"dial_code\": \"268\"\n  },\n  \"SE\": {\n    \"country\": \"Sweden\",\n    \"dial_code\": \"46\"\n  },\n  \"CH\": {\n    \"country\": \"Switzerland\",\n    \"dial_code\": \"41\"\n  },\n  \"SY\": {\n    \"country\": \"Syrian Arab Republic\",\n    \"dial_code\": \"963\"\n  },\n  \"TW\": {\n    \"country\": \"Taiwan\",\n    \"dial_code\": \"886\"\n  },\n  \"TJ\": {\n    \"country\": \"Tajikistan\",\n    \"dial_code\": \"992\"\n  },\n  \"TZ\": {\n    \"country\": \"Tanzania\",\n    \"dial_code\": \"255\"\n  },\n  \"TH\": {\n    \"country\": \"Thailand\",\n    \"dial_code\": \"66\"\n  },\n  \"TL\": {\n    \"country\": \"Timor-Leste\",\n    \"dial_code\": \"670\"\n  },\n  \"TG\": {\n    \"country\": \"Togo\",\n    \"dial_code\": \"228\"\n  },\n  \"TK\": {\n    \"country\": \"Tokelau\",\n    \"dial_code\": \"690\"\n  },\n  \"TO\": {\n    \"country\": \"Tonga\",\n    \"dial_code\": \"676\"\n  },\n  \"TT\": {\n    \"country\": \"Trinidad and Tobago\",\n    \"dial_code\": \"1868\"\n  },\n  \"TN\": {\n    \"country\": \"Tunisia\",\n    \"dial_code\": \"216\"\n  },\n  \"TR\": {\n    \"country\": \"Turkey\",\n    \"dial_code\": \"90\"\n  },\n  \"TM\": {\n    \"country\": \"Turkmenistan\",\n    \"dial_code\": \"993\"\n  },\n  \"TC\": {\n    \"country\": \"Turks and Caicos Islands\",\n    \"dial_code\": \"1649\"\n  },\n  \"TV\": {\n    \"country\": \"Tuvalu\",\n    \"dial_code\": \"688\"\n  },\n  \"UG\": {\n    \"country\": \"Uganda\",\n    \"dial_code\": \"256\"\n  },\n  \"UA\": {\n    \"country\": \"Ukraine\",\n    \"dial_code\": \"380\"\n  },\n  \"AE\": {\n    \"country\": \"United Arab Emirates\",\n    \"dial_code\": \"971\"\n  },\n  \"GB\": {\n    \"country\": \"United Kingdom\",\n    \"dial_code\": \"44\"\n  },\n  \"US\": {\n    \"country\": \"United States\",\n    \"dial_code\": \"1\"\n  },\n  \"UY\": {\n    \"country\": \"Uruguay\",\n    \"dial_code\": \"598\"\n  },\n  \"UZ\": {\n    \"country\": \"Uzbekistan\",\n    \"dial_code\": \"998\"\n  },\n  \"VU\": {\n    \"country\": \"Vanuatu\",\n    \"dial_code\": \"678\"\n  },\n  \"VE\": {\n    \"country\": \"Venezuela\",\n    \"dial_code\": \"58\"\n  },\n  \"VN\": {\n    \"country\": \"Vietnam\",\n    \"dial_code\": \"84\"\n  },\n  \"VG\": {\n    \"country\": \"British Virgin Islands\",\n    \"dial_code\": \"1284\"\n  },\n  \"VI\": {\n    \"country\": \"U.S. Virgin Islands\",\n    \"dial_code\": \"1340\"\n  },\n  \"WF\": {\n    \"country\": \"Wallis and Futuna\",\n    \"dial_code\": \"681\"\n  },\n  \"YE\": {\n    \"country\": \"Yemen\",\n    \"dial_code\": \"967\"\n  },\n  \"ZM\": {\n    \"country\": \"Zambia\",\n    \"dial_code\": \"260\"\n  },\n  \"ZW\": {\n    \"country\": \"Zimbabwe\",\n    \"dial_code\": \"263\"\n  }\n}' WHERE `general_settings`.`id` = 1;

    UPDATE users SET dial_code = ( SELECT JSON_UNQUOTE(JSON_EXTRACT(country_list, CONCAT('$."', users.country_code, '".dial_code'))) FROM general_settings WHERE JSON_CONTAINS_PATH(country_list, 'one', CONCAT('$."', users.country_code, '"')) );

    UPDATE users SET mobile = SUBSTRING(mobile, CHAR_LENGTH(dial_code) + 1);

    ALTER TABLE `general_settings` DROP `country_list`;

    UPDATE frontends
    SET slug = LOWER(REPLACE(REPLACE(REPLACE(
        JSON_UNQUOTE(JSON_EXTRACT(data_values, '$.title')), ' ', '-'), ',', ''), '.', ''))
    WHERE data_keys = 'blog.element';

    UPDATE frontends
    SET slug = LOWER(REPLACE(REPLACE(REPLACE(
        JSON_UNQUOTE(JSON_EXTRACT(data_values, '$.title')), ' ', '-'), ',', ''), '.', ''))
    WHERE data_keys = 'policy_pages.element';

    ALTER TABLE deposits CHANGE COLUMN btc_amo btc_amount varchar(255);

    ALTER TABLE general_settings DROP COLUMN in_app_payment;

    UPDATE frontends SET tempname = 'basic' WHERE data_keys != 'seo.data' and data_keys != 'cookie.data' and data_keys != 'maintenance.data';

    INSERT INTO `frontends` (`id`, `data_keys`, `data_values`, `seo_content`, `tempname`, `slug`, `created_at`, `updated_at`) VALUES
    (107, 'kyc.content', '{\"required\":\"You have to send information to the admin for your merchant verification.\",\"pending\":\"Your merchant information has been submitted for review. Please wait for admin approval.\",\"reject\":\"Kindly examine and furnish accurate information for your review.\"}', NULL, 'basic', '', '2024-07-02 02:48:05', '2024-07-02 02:48:05');

    ALTER TABLE users DROP COLUMN reject_reason; 

    INSERT INTO `cron_jobs` (`id`, `name`, `alias`, `action`, `url`, `cron_schedule_id`, `next_run`, `last_run`, `is_running`, `is_default`, `created_at`, `updated_at`) VALUES
    (1, 'Withdraw Request ', 'withdraw_request ', '[\"App\\\\Http\\\\Controllers\\\\CronController\", \"withdrawRequest\"]', NULL, 1, '2024-07-03 11:16:43', '2024-07-03 11:01:43', 1, 1, '2024-06-30 04:01:24', '2024-07-03 05:01:43');

    UPDATE `general_settings` SET `available_version` = '2.0' WHERE `general_settings`.`id` = 1;

    UPDATE `general_settings` SET `kv` = 1;

    INSERT INTO `cron_schedules` (`id`, `name`, `interval`, `status`, `created_at`, `updated_at`) VALUES
    (1, '15 Minutes', 900, 1, '2024-07-06 01:24:17', '2024-07-06 01:24:17');