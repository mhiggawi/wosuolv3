-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Aug 02, 2025 at 03:47 PM
-- Server version: 10.11.10-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u747253029_wosuol`
--

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `bride_name_ar` varchar(255) DEFAULT NULL,
  `bride_name_en` varchar(255) DEFAULT NULL,
  `groom_name_ar` varchar(255) DEFAULT NULL,
  `groom_name_en` varchar(255) DEFAULT NULL,
  `event_date_ar` text DEFAULT NULL,
  `event_date_en` text DEFAULT NULL,
  `venue_ar` varchar(255) DEFAULT NULL,
  `venue_en` varchar(255) DEFAULT NULL,
  `Maps_link` varchar(1024) DEFAULT NULL,
  `event_paragraph_ar` text DEFAULT NULL,
  `event_paragraph_en` text DEFAULT NULL,
  `background_image_url` varchar(1024) DEFAULT NULL,
  `whatsapp_image_url` varchar(1024) DEFAULT NULL COMMENT 'رابط صورة الواتساب للدعوات',
  `qr_card_title_ar` varchar(255) DEFAULT 'بطاقة دخول شخصية',
  `qr_card_title_en` varchar(255) DEFAULT 'Personal Entry Card',
  `qr_show_code_instruction_ar` varchar(255) DEFAULT 'يرجى إبراز الكود للدخول',
  `qr_show_code_instruction_en` varchar(255) DEFAULT 'Please show code to enter',
  `qr_brand_text_ar` varchar(255) DEFAULT 'Wosuol.com',
  `qr_brand_text_en` varchar(255) DEFAULT 'Wosuol.com',
  `qr_website` varchar(255) DEFAULT 'Wosuol.com',
  `n8n_confirm_webhook` varchar(1024) DEFAULT NULL,
  `n8n_initial_invite_webhook` varchar(1024) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `event_name`, `bride_name_ar`, `bride_name_en`, `groom_name_ar`, `groom_name_en`, `event_date_ar`, `event_date_en`, `venue_ar`, `venue_en`, `Maps_link`, `event_paragraph_ar`, `event_paragraph_en`, `background_image_url`, `whatsapp_image_url`, `qr_card_title_ar`, `qr_card_title_en`, `qr_show_code_instruction_ar`, `qr_show_code_instruction_en`, `qr_brand_text_ar`, `qr_brand_text_en`, `qr_website`, `n8n_confirm_webhook`, `n8n_initial_invite_webhook`, `created_at`) VALUES
(8, 'Sam & Sarab Wedding', 'يرجى التحديث من لوحة التحكم', NULL, 'يرجى التحديث من لوحة التحكم', NULL, '28 / 8 / 2025 Thursday', '', 'Papillon Venue Farm', '', 'https://maps.app.goo.gl/TdB5v7vMJyL5tKwS9?g_st=ipc', '', '', './uploads/display_event_8_1753994599.jpg', './uploads/whatsapp_event_8_1754146790.jpg', 'دعوة حفل زفاف', 'Wedding Invitation', 'يرجى إظهار هذا الرمز عند الدخول', 'Please show this code at entrance', 'wosuol.com', 'wosuol.com', 'wosuol.com', 'https://n8n.clouditech-me.com/webhook/confirm-rsvp-qr', 'https://n8n.clouditech-me.com/webhook/send-invitations', '2025-07-31 18:42:57');

-- --------------------------------------------------------

--
-- Stand-in structure for view `event_send_stats`
-- (See below for the actual view)
--
CREATE TABLE `event_send_stats` (
`event_id` int(11)
,`event_name` varchar(255)
,`total_guests` bigint(21)
,`confirmed_guests` decimal(22,0)
,`pending_guests` decimal(22,0)
,`invited_guests` decimal(22,0)
,`last_invitation_time` datetime
,`last_send_success` int(11)
,`last_send_failed` int(11)
);

-- --------------------------------------------------------

--
-- Table structure for table `guests`
--

CREATE TABLE `guests` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `guest_id` varchar(10) NOT NULL,
  `name_ar` varchar(255) NOT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `guests_count` int(11) DEFAULT 1,
  `table_number` varchar(50) DEFAULT NULL,
  `assigned_location` varchar(255) DEFAULT NULL,
  `status` enum('pending','confirmed','canceled') NOT NULL DEFAULT 'pending',
  `invitation_sent` tinyint(1) NOT NULL DEFAULT 0,
  `checkin_status` enum('not_checked_in','checked_in') NOT NULL DEFAULT 'not_checked_in',
  `checkin_time` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_invite_sent` datetime DEFAULT NULL,
  `invite_count` int(11) DEFAULT 0,
  `last_invite_status` enum('sent','failed','pending') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `special_needs` varchar(255) DEFAULT NULL,
  `dietary_restrictions` text DEFAULT NULL,
  `last_updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'آخر تحديث للضيف',
  `sync_status` enum('synced','pending_sync','conflict') DEFAULT 'synced' COMMENT 'حالة المزامنة للعمليات غير المتصلة'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `guests`
--

INSERT INTO `guests` (`id`, `event_id`, `guest_id`, `name_ar`, `name_en`, `phone_number`, `guests_count`, `table_number`, `assigned_location`, `status`, `invitation_sent`, `checkin_status`, `checkin_time`, `created_at`, `last_invite_sent`, `invite_count`, `last_invite_status`, `notes`, `special_needs`, `dietary_restrictions`, `last_updated`, `sync_status`) VALUES
(371, 8, 'dd66', 'MOHAMMAD HIGGAWI', NULL, '962798797794', 1, '2', NULL, 'pending', 0, 'not_checked_in', '2025-08-02 14:13:09', '2025-08-01 23:11:20', NULL, 0, 'pending', NULL, NULL, NULL, '2025-08-02 14:15:14', 'synced'),
(372, 8, '102d', 'HIGGAWI', NULL, '962798797794', 4, '3', 'اهل العروس', 'pending', 0, 'not_checked_in', '2025-08-02 13:49:52', '2025-08-01 23:11:32', '2025-08-02 15:00:09', 2, 'sent', '[2025-08-02 13:50:06] تيست 1', NULL, NULL, '2025-08-02 15:00:09', 'synced'),
(375, 8, '295d', 'حجاوي4', NULL, '962798797794', 1, '0', NULL, 'pending', 0, 'not_checked_in', '2025-08-02 14:00:56', '2025-08-01 23:45:51', NULL, 0, 'pending', NULL, NULL, NULL, '2025-08-02 14:15:38', 'synced'),
(376, 8, 'b1ee', 'Mohammad Hijjawi', NULL, '798797794', 1, '12', NULL, 'pending', 0, 'not_checked_in', NULL, '2025-08-02 00:00:38', NULL, 0, 'pending', NULL, NULL, NULL, '2025-08-02 13:59:38', 'synced');

-- --------------------------------------------------------

--
-- Table structure for table `message_logs`
--

CREATE TABLE `message_logs` (
  `id` int(11) NOT NULL,
  `workflow_id` varchar(255) DEFAULT NULL,
  `action_type` varchar(50) DEFAULT NULL,
  `total_processed` int(11) DEFAULT NULL,
  `success_count` int(11) DEFAULT NULL,
  `failure_count` int(11) DEFAULT NULL,
  `success_rate` decimal(5,2) DEFAULT NULL,
  `event_ids` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `details` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `offline_operations`
--

CREATE TABLE `offline_operations` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `guest_id` varchar(10) NOT NULL,
  `operation_type` enum('checkin','confirm_and_checkin','add_note') NOT NULL,
  `operation_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`operation_data`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','processed','failed') DEFAULT 'pending',
  `error_message` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='تتبع العمليات غير المتزامنة للوضع غير المتصل';

-- --------------------------------------------------------

--
-- Table structure for table `send_results`
--

CREATE TABLE `send_results` (
  `id` int(11) NOT NULL,
  `event_id` int(11) DEFAULT NULL,
  `action_type` varchar(50) NOT NULL,
  `success_count` int(11) DEFAULT 0,
  `failed_count` int(11) DEFAULT 0,
  `total_processed` int(11) DEFAULT 0,
  `target_count` int(11) DEFAULT NULL,
  `response_data` text DEFAULT NULL,
  `http_code` int(11) DEFAULT 0,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `send_results`
--

INSERT INTO `send_results` (`id`, `event_id`, `action_type`, `success_count`, `failed_count`, `total_processed`, `target_count`, `response_data`, `http_code`, `created_at`) VALUES
(58, 8, 'send_selected', 0, 0, 0, 1, 'null', 0, '2025-07-31 18:49:07'),
(59, 8, 'send_event_all', 0, 0, 0, NULL, 'null', 0, '2025-07-31 19:10:31'),
(60, 8, 'send_selected', 0, 0, 0, 1, 'null', 200, '2025-07-31 19:13:36'),
(61, 8, 'send_selected', 0, 0, 0, 1, 'null', 200, '2025-07-31 19:14:47'),
(62, 8, 'send_selected', 0, 1, 1, 1, '{\"success\":true,\"message\":\"\\u062a\\u0645 \\u0625\\u0631\\u0633\\u0627\\u0644 0 \\u0631\\u0633\\u0627\\u0644\\u0629 \\u0628\\u0646\\u062c\\u0627\\u062d \\u0645\\u0646 \\u0623\\u0635\\u0644 1 (0%)\",\"summary\":{\"totalProcessed\":1,\"successCount\":0,\"failureCount\":1,\"successRate\":0,\"eventsAffected\":1,\"eventIds\":[null]},\"details\":{\"successfulSends\":[],\"failedSends\":[[]],\"processedAt\":\"2025-07-31T19:44:08.583Z\"}}', 200, '2025-07-31 19:44:08'),
(63, 8, 'send_event_all', 0, 1, 1, NULL, '{\"success\":true,\"message\":\"\\u062a\\u0645 \\u0625\\u0631\\u0633\\u0627\\u0644 0 \\u0631\\u0633\\u0627\\u0644\\u0629 \\u0628\\u0646\\u062c\\u0627\\u062d \\u0645\\u0646 \\u0623\\u0635\\u0644 1 (0%)\",\"summary\":{\"totalProcessed\":1,\"successCount\":0,\"failureCount\":1,\"successRate\":0,\"eventsAffected\":1,\"eventIds\":[null]},\"details\":{\"successfulSends\":[],\"failedSends\":[[]],\"processedAt\":\"2025-07-31T19:46:18.492Z\"}}', 200, '2025-07-31 19:46:18'),
(64, NULL, 'send_global_all', 0, 1, 1, NULL, '{\"success\":true,\"message\":\"\\u062a\\u0645 \\u0625\\u0631\\u0633\\u0627\\u0644 0 \\u0631\\u0633\\u0627\\u0644\\u0629 \\u0628\\u0646\\u062c\\u0627\\u062d \\u0645\\u0646 \\u0623\\u0635\\u0644 1 (0%)\",\"summary\":{\"totalProcessed\":1,\"successCount\":0,\"failureCount\":1,\"successRate\":0,\"eventsAffected\":1,\"eventIds\":[null]},\"details\":{\"successfulSends\":[],\"failedSends\":[[]],\"processedAt\":\"2025-07-31T19:48:00.818Z\"}}', 200, '2025-07-31 19:48:00'),
(65, 8, 'send_selected', 0, 1, 1, 1, '{\"success\":true,\"message\":\"\\u062a\\u0645 \\u0625\\u0631\\u0633\\u0627\\u0644 0 \\u0631\\u0633\\u0627\\u0644\\u0629 \\u0628\\u0646\\u062c\\u0627\\u062d \\u0645\\u0646 \\u0623\\u0635\\u0644 1 (0%)\",\"summary\":{\"totalProcessed\":1,\"successCount\":0,\"failureCount\":1,\"successRate\":0,\"eventsAffected\":1,\"eventIds\":[null]},\"details\":{\"successfulSends\":[],\"failedSends\":[[]],\"processedAt\":\"2025-07-31T19:49:02.899Z\"}}', 200, '2025-07-31 19:49:02'),
(66, NULL, 'send_global_all', 0, 1, 1, NULL, '{\"success\":true,\"message\":\"\\u062a\\u0645 \\u0625\\u0631\\u0633\\u0627\\u0644 0 \\u0631\\u0633\\u0627\\u0644\\u0629 \\u0628\\u0646\\u062c\\u0627\\u062d \\u0645\\u0646 \\u0623\\u0635\\u0644 1 (0%)\",\"summary\":{\"totalProcessed\":1,\"successCount\":0,\"failureCount\":1,\"successRate\":0,\"eventsAffected\":1,\"eventIds\":[null]},\"details\":{\"successfulSends\":[],\"failedSends\":[[]],\"processedAt\":\"2025-07-31T19:49:13.493Z\"}}', 200, '2025-07-31 19:49:13'),
(67, 8, 'send_event_all', 0, 1, 1, NULL, '{\"success\":true,\"message\":\"\\u062a\\u0645 \\u0625\\u0631\\u0633\\u0627\\u0644 0 \\u0631\\u0633\\u0627\\u0644\\u0629 \\u0628\\u0646\\u062c\\u0627\\u062d \\u0645\\u0646 \\u0623\\u0635\\u0644 1 (0%)\",\"summary\":{\"totalProcessed\":1,\"successCount\":0,\"failureCount\":1,\"successRate\":0,\"eventsAffected\":1,\"eventIds\":[null]},\"details\":{\"successfulSends\":[],\"failedSends\":[[]],\"processedAt\":\"2025-07-31T19:53:35.769Z\"}}', 200, '2025-07-31 19:53:35'),
(68, 8, 'send_event_all', 0, 1, 1, NULL, '{\"success\":true,\"message\":\"\\u062a\\u0645 \\u0625\\u0631\\u0633\\u0627\\u0644 0 \\u0631\\u0633\\u0627\\u0644\\u0629 \\u0628\\u0646\\u062c\\u0627\\u062d \\u0645\\u0646 \\u0623\\u0635\\u0644 1 (0%)\",\"summary\":{\"totalProcessed\":1,\"successCount\":0,\"failureCount\":1,\"successRate\":0,\"eventsAffected\":1,\"eventIds\":[null]},\"details\":{\"successfulSends\":[],\"failedSends\":[[]],\"processedAt\":\"2025-07-31T20:04:58.830Z\"}}', 200, '2025-07-31 20:04:58'),
(69, 8, 'send_selected', 0, 1, 1, 1, '{\"success\":true,\"message\":\"\\u062a\\u0645 \\u0625\\u0631\\u0633\\u0627\\u0644 0 \\u0631\\u0633\\u0627\\u0644\\u0629 \\u0628\\u0646\\u062c\\u0627\\u062d \\u0645\\u0646 \\u0623\\u0635\\u0644 1 (0%)\",\"summary\":{\"totalProcessed\":1,\"successCount\":0,\"failureCount\":1,\"successRate\":0,\"eventsAffected\":1,\"eventIds\":[null]},\"details\":{\"successfulSends\":[],\"failedSends\":[[]],\"processedAt\":\"2025-07-31T20:09:36.919Z\"}}', 200, '2025-07-31 20:09:36'),
(70, 8, 'send_selected', 0, 1, 1, 1, '{\"success\":true,\"message\":\"\\u062a\\u0645 \\u0625\\u0631\\u0633\\u0627\\u0644 0 \\u0631\\u0633\\u0627\\u0644\\u0629 \\u0628\\u0646\\u062c\\u0627\\u062d \\u0645\\u0646 \\u0623\\u0635\\u0644 1 (0%)\",\"summary\":{\"totalProcessed\":1,\"successCount\":0,\"failureCount\":1,\"successRate\":0,\"eventsAffected\":1,\"eventIds\":[null]},\"details\":{\"successfulSends\":[],\"failedSends\":[[]],\"processedAt\":\"2025-07-31T20:32:13.815Z\"}}', 200, '2025-07-31 20:32:13'),
(71, 8, 'send_selected', 0, 1, 1, 1, '{\"success\":true,\"message\":\"\\u062a\\u0645 \\u0625\\u0631\\u0633\\u0627\\u0644 0 \\u0631\\u0633\\u0627\\u0644\\u0629 \\u0628\\u0646\\u062c\\u0627\\u062d \\u0645\\u0646 \\u0623\\u0635\\u0644 1 (0%)\",\"summary\":{\"totalProcessed\":1,\"successCount\":0,\"failureCount\":1,\"successRate\":0,\"eventsAffected\":1,\"eventIds\":[null]},\"details\":{\"successfulSends\":[],\"failedSends\":[[]],\"processedAt\":\"2025-07-31T20:33:21.653Z\"}}', 200, '2025-07-31 20:33:21'),
(72, 8, 'send_selected', 0, 1, 1, 1, '{\"success\":true,\"message\":\"\\u062a\\u0645 \\u0625\\u0631\\u0633\\u0627\\u0644 0 \\u0631\\u0633\\u0627\\u0644\\u0629 \\u0628\\u0646\\u062c\\u0627\\u062d \\u0645\\u0646 \\u0623\\u0635\\u0644 1 (0%)\",\"summary\":{\"totalProcessed\":1,\"successCount\":0,\"failureCount\":1,\"successRate\":0,\"eventsAffected\":1,\"eventIds\":[null]},\"details\":{\"successfulSends\":[],\"failedSends\":[[]],\"processedAt\":\"2025-07-31T20:34:11.039Z\"}}', 200, '2025-07-31 20:34:11'),
(73, 8, 'send_selected', 0, 1, 1, 1, '{\"success\":true,\"message\":\"\\u062a\\u0645 \\u0625\\u0631\\u0633\\u0627\\u0644 0 \\u0631\\u0633\\u0627\\u0644\\u0629 \\u0628\\u0646\\u062c\\u0627\\u062d \\u0645\\u0646 \\u0623\\u0635\\u0644 1 (0%)\",\"summary\":{\"totalProcessed\":1,\"successCount\":0,\"failureCount\":1,\"successRate\":0,\"eventsAffected\":1,\"eventIds\":[null]},\"details\":{\"successfulSends\":[],\"failedSends\":[[]],\"processedAt\":\"2025-07-31T20:34:46.246Z\"}}', 200, '2025-07-31 20:34:46'),
(74, 8, 'send_selected', 0, 1, 1, 1, '{\"success\":true,\"message\":\"\\u062a\\u0645 \\u0625\\u0631\\u0633\\u0627\\u0644 0 \\u0631\\u0633\\u0627\\u0644\\u0629 \\u0628\\u0646\\u062c\\u0627\\u062d \\u0645\\u0646 \\u0623\\u0635\\u0644 1 (0%)\",\"summary\":{\"totalProcessed\":1,\"successCount\":0,\"failureCount\":1,\"successRate\":0,\"eventsAffected\":1,\"eventIds\":[null]},\"details\":{\"successfulSends\":[],\"failedSends\":[[]],\"processedAt\":\"2025-07-31T20:45:59.714Z\"}}', 200, '2025-07-31 20:45:59'),
(75, 8, 'send_selected', 0, 1, 1, 1, '{\"success\":true,\"message\":\"\\u062a\\u0645 \\u0625\\u0631\\u0633\\u0627\\u0644 0 \\u0631\\u0633\\u0627\\u0644\\u0629 \\u0628\\u0646\\u062c\\u0627\\u062d \\u0645\\u0646 \\u0623\\u0635\\u0644 1 (0%)\",\"summary\":{\"totalProcessed\":1,\"successCount\":0,\"failureCount\":1,\"successRate\":0,\"eventsAffected\":1,\"eventIds\":[null]},\"details\":{\"successfulSends\":[],\"failedSends\":[[]],\"processedAt\":\"2025-07-31T20:46:28.831Z\"}}', 200, '2025-07-31 20:46:28'),
(76, 8, 'send_event_all', 0, 1, 1, NULL, '{\"success\":true,\"message\":\"\\u062a\\u0645 \\u0625\\u0631\\u0633\\u0627\\u0644 0 \\u0631\\u0633\\u0627\\u0644\\u0629 \\u0628\\u0646\\u062c\\u0627\\u062d \\u0645\\u0646 \\u0623\\u0635\\u0644 1 (0%)\",\"summary\":{\"totalProcessed\":1,\"successCount\":0,\"failureCount\":1,\"successRate\":0,\"eventsAffected\":1,\"eventIds\":[null]},\"details\":{\"successfulSends\":[],\"failedSends\":[[]],\"processedAt\":\"2025-07-31T20:49:01.966Z\"}}', 200, '2025-07-31 20:49:02'),
(77, 8, 'send_selected', 0, 1, 1, 2, '{\"success\":true,\"message\":\"\\u062a\\u0645 \\u0625\\u0631\\u0633\\u0627\\u0644 0 \\u0631\\u0633\\u0627\\u0644\\u0629 \\u0628\\u0646\\u062c\\u0627\\u062d \\u0645\\u0646 \\u0623\\u0635\\u0644 1 (0%)\",\"summary\":{\"totalProcessed\":1,\"successCount\":0,\"failureCount\":1,\"successRate\":0,\"eventsAffected\":1,\"eventIds\":[null]},\"details\":{\"successfulSends\":[],\"failedSends\":[[]],\"processedAt\":\"2025-07-31T21:15:56.107Z\"}}', 200, '2025-07-31 21:15:56'),
(78, 8, 'send_selected', 0, 1, 1, 1, '{\"success\":true,\"message\":\"\\u062a\\u0645 \\u0625\\u0631\\u0633\\u0627\\u0644 0 \\u0631\\u0633\\u0627\\u0644\\u0629 \\u0628\\u0646\\u062c\\u0627\\u062d \\u0645\\u0646 \\u0623\\u0635\\u0644 1 (0%)\",\"summary\":{\"totalProcessed\":1,\"successCount\":0,\"failureCount\":1,\"successRate\":0,\"eventsAffected\":1,\"eventIds\":[null]},\"details\":{\"successfulSends\":[],\"failedSends\":[[]],\"processedAt\":\"2025-08-01T03:13:48.815Z\"}}', 200, '2025-08-01 03:13:48'),
(79, 8, 'send_selected', 0, 1, 1, 1, '{\"success\":true,\"message\":\"\\u062a\\u0645 \\u0625\\u0631\\u0633\\u0627\\u0644 0 \\u0631\\u0633\\u0627\\u0644\\u0629 \\u0628\\u0646\\u062c\\u0627\\u062d \\u0645\\u0646 \\u0623\\u0635\\u0644 1 (0%)\",\"summary\":{\"totalProcessed\":1,\"successCount\":0,\"failureCount\":1,\"successRate\":0,\"eventsAffected\":1,\"eventIds\":[null]},\"details\":{\"successfulSends\":[],\"failedSends\":[[]],\"processedAt\":\"2025-08-01T07:38:24.275Z\"}}', 200, '2025-08-01 07:38:24'),
(80, 8, 'send_selected', 0, 1, 1, 1, '{\"success\":true,\"message\":\"\\u062a\\u0645 \\u0625\\u0631\\u0633\\u0627\\u0644 0 \\u0631\\u0633\\u0627\\u0644\\u0629 \\u0628\\u0646\\u062c\\u0627\\u062d \\u0645\\u0646 \\u0623\\u0635\\u0644 1 (0%)\",\"summary\":{\"totalProcessed\":1,\"successCount\":0,\"failureCount\":1,\"successRate\":0,\"eventsAffected\":1,\"eventIds\":[null]},\"details\":{\"successfulSends\":[],\"failedSends\":[[]],\"processedAt\":\"2025-08-01T07:38:53.065Z\"}}', 200, '2025-08-01 07:38:53'),
(81, 8, 'send_selected', 0, 1, 1, 1, '{\"success\":true,\"message\":\"\\u062a\\u0645 \\u0625\\u0631\\u0633\\u0627\\u0644 0 \\u0631\\u0633\\u0627\\u0644\\u0629 \\u0628\\u0646\\u062c\\u0627\\u062d \\u0645\\u0646 \\u0623\\u0635\\u0644 1 (0%)\",\"summary\":{\"totalProcessed\":1,\"successCount\":0,\"failureCount\":1,\"successRate\":0,\"eventsAffected\":1,\"eventIds\":[null]},\"details\":{\"successfulSends\":[],\"failedSends\":[[]],\"processedAt\":\"2025-08-02T14:58:44.679Z\"}}', 200, '2025-08-02 14:58:44'),
(82, 8, 'send_selected', 0, 1, 1, 1, '{\"success\":true,\"message\":\"\\u062a\\u0645 \\u0625\\u0631\\u0633\\u0627\\u0644 0 \\u0631\\u0633\\u0627\\u0644\\u0629 \\u0628\\u0646\\u062c\\u0627\\u062d \\u0645\\u0646 \\u0623\\u0635\\u0644 1 (0%)\",\"summary\":{\"totalProcessed\":1,\"successCount\":0,\"failureCount\":1,\"successRate\":0,\"eventsAffected\":1,\"eventIds\":[null]},\"details\":{\"successfulSends\":[],\"failedSends\":[[]],\"processedAt\":\"2025-08-02T15:00:09.279Z\"}}', 200, '2025-08-02 15:00:09');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','viewer','checkin_user') NOT NULL DEFAULT 'viewer',
  `event_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `permissions` varchar(255) DEFAULT NULL,
  `allowed_pages` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `role`, `event_id`, `created_at`, `permissions`, `allowed_pages`) VALUES
(1, 'hijjawi', '$2y$10$vzZddozxIY0zgAItU1DKHO6DARAEtWsslMjDgZuKYE4iWYZ5kAHWm', 'admin', NULL, '2025-07-27 14:11:41', NULL, NULL),
(2, 'user', '$2y$10$DC2PvdfvwMvQe0yT4TZhaeXar.UAB2fxlSNMavoWzGC6JeUCW7AAm', 'checkin_user', 8, '2025-07-27 18:05:50', NULL, NULL),
(3, 'user2', '$2y$10$BUGM0h.ehX2a3aJpDe800OJVW9XiVP8rqROK5vYn07IJAjnE7lsyu', 'viewer', 8, '2025-07-27 18:06:01', NULL, NULL),
(5, 'GG', '$2y$10$nJLii/1J85w73hynruCoCexDXe8b7EnlM4DTHsSz4DwgrMLB2UKWS', 'admin', NULL, '2025-07-30 18:26:57', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `guests`
--
ALTER TABLE `guests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `guest_id` (`guest_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `idx_event_status` (`event_id`,`status`),
  ADD KEY `idx_last_invite` (`last_invite_sent`),
  ADD KEY `idx_guest_search` (`name_ar`,`phone_number`,`table_number`),
  ADD KEY `idx_checkin_date` (`checkin_status`,`checkin_time`);

--
-- Indexes for table `message_logs`
--
ALTER TABLE `message_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `offline_operations`
--
ALTER TABLE `offline_operations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status_created` (`status`,`created_at`),
  ADD KEY `idx_event_guest` (`event_id`,`guest_id`);

--
-- Indexes for table `send_results`
--
ALTER TABLE `send_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `guests`
--
ALTER TABLE `guests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=378;

--
-- AUTO_INCREMENT for table `message_logs`
--
ALTER TABLE `message_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `offline_operations`
--
ALTER TABLE `offline_operations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `send_results`
--
ALTER TABLE `send_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

-- --------------------------------------------------------

--
-- Structure for view `event_send_stats`
--
DROP TABLE IF EXISTS `event_send_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u747253029_dbhijjawi`@`127.0.0.1` SQL SECURITY DEFINER VIEW `event_send_stats`  AS SELECT `e`.`id` AS `event_id`, `e`.`event_name` AS `event_name`, count(`g`.`id`) AS `total_guests`, sum(case when `g`.`status` = 'confirmed' then 1 else 0 end) AS `confirmed_guests`, sum(case when `g`.`status` = 'pending' then 1 else 0 end) AS `pending_guests`, sum(case when `g`.`last_invite_status` = 'sent' then 1 else 0 end) AS `invited_guests`, max(`g`.`last_invite_sent`) AS `last_invitation_time`, coalesce(`sr`.`last_send_success`,0) AS `last_send_success`, coalesce(`sr`.`last_send_failed`,0) AS `last_send_failed` FROM ((`events` `e` left join `guests` `g` on(`e`.`id` = `g`.`event_id`)) left join (select `send_results`.`event_id` AS `event_id`,max(`send_results`.`success_count`) AS `last_send_success`,max(`send_results`.`failed_count`) AS `last_send_failed` from `send_results` where `send_results`.`created_at` >= current_timestamp() - interval 1 day group by `send_results`.`event_id`) `sr` on(`e`.`id` = `sr`.`event_id`)) GROUP BY `e`.`id`, `e`.`event_name` ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `guests`
--
ALTER TABLE `guests`
  ADD CONSTRAINT `guests_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `offline_operations`
--
ALTER TABLE `offline_operations`
  ADD CONSTRAINT `offline_operations_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `send_results`
--
ALTER TABLE `send_results`
  ADD CONSTRAINT `send_results_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
