-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- ホスト: 127.0.0.1
-- 生成日時: 2025-11-17 09:28:29
-- サーバのバージョン： 10.4.32-MariaDB
-- PHP のバージョン: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- データベース: `famisapo`
--

-- --------------------------------------------------------

--
-- テーブルの構造 `activity_reports`
--

CREATE TABLE `activity_reports` (
  `id` int(11) NOT NULL,
  `member_id` varchar(50) NOT NULL,
  `member_name` varchar(100) NOT NULL,
  `child_name` varchar(100) DEFAULT NULL,
  `child_age` int(11) DEFAULT NULL,
  `work_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `person_count` int(11) NOT NULL DEFAULT 1,
  `transport_fee` int(11) NOT NULL DEFAULT 0,
  `meal_fee` int(11) NOT NULL DEFAULT 0,
  `reward_fee` int(11) NOT NULL DEFAULT 0,
  `place` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `total_hours` decimal(5,2) DEFAULT NULL,
  `total_reward` int(11) DEFAULT NULL,
  `status` enum('draft','submitted') NOT NULL DEFAULT 'submitted',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `activity_reports`
--

INSERT INTO `activity_reports` (`id`, `member_id`, `member_name`, `child_name`, `child_age`, `work_date`, `start_time`, `end_time`, `person_count`, `transport_fee`, `meal_fee`, `reward_fee`, `place`, `note`, `total_hours`, `total_reward`, `status`, `created_at`, `updated_at`) VALUES
(1, '3', '南流 花子', '流山 花子', 5, '2025-11-20', '10:00:00', '13:00:00', 1, 0, 0, 1500, '千葉県流山市平和台1-1-1', '特に問題なく活動終了。', 3.00, 1500, 'submitted', '2025-11-16 09:09:18', '2025-11-16 09:09:18'),
(2, '4', '大鷹 一郎', '江戸川 太一', 6, '2025-11-22', '09:00:00', '12:00:00', 1, 200, 0, 2000, '千葉県流山市江戸川台西1-1-1', '送迎と預かりを実施。雨天で移動に注意。', 3.00, 2200, 'submitted', '2025-11-16 09:09:18', '2025-11-16 09:09:18');

-- --------------------------------------------------------

--
-- テーブルの構造 `children`
--

CREATE TABLE `children` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name_kana` varchar(50) DEFAULT NULL,
  `first_name_kana` varchar(50) DEFAULT NULL,
  `sex` enum('female','male','other') DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `age` tinyint(3) UNSIGNED DEFAULT NULL,
  `school` varchar(120) DEFAULT NULL,
  `allergy_flag` tinyint(1) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `children`
--

INSERT INTO `children` (`id`, `member_id`, `last_name`, `first_name`, `last_name_kana`, `first_name_kana`, `sex`, `birthday`, `age`, `school`, `allergy_flag`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, '流山', '花子', 'ナガレヤマ', 'ハナコ', 'female', '2020-04-01', 5, '流山市立〇〇保育所', 0, NULL, '2025-11-16 00:09:18', '2025-11-16 00:09:18'),
(2, 2, '江戸川', '太一', 'エドガワ', 'タイチ', 'male', '2019-10-10', 6, '江戸川台小学校', 1, '卵アレルギー', '2025-11-16 00:09:18', '2025-11-16 00:09:18'),
(3, 17, '加固', '壱成', 'カコ', 'イッセイ', 'female', '2018-07-17', 7, 'aa', 1, NULL, '2025-11-16 15:31:55', '2025-11-16 15:31:55');

-- --------------------------------------------------------

--
-- テーブルの構造 `confirmed_matches`
--

CREATE TABLE `confirmed_matches` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `score` float NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `confirmed_matches`
--

INSERT INTO `confirmed_matches` (`id`, `request_id`, `provider_id`, `score`, `created_at`) VALUES
(1, 2, 3, 0.94705, '2025-11-16 22:26:30'),
(2, 1, 1, 0.177458, '2025-11-16 23:48:53'),
(3, 1, 2, 0.152285, '2025-11-16 23:48:54'),
(4, 1, 3, 0.12737, '2025-11-16 23:48:54'),
(5, 5, 1, 0.138897, '2025-11-17 00:35:46'),
(6, 5, 8, 0.663083, '2025-11-17 00:35:47'),
(7, 5, 3, 0.874448, '2025-11-17 00:35:47');

-- --------------------------------------------------------

--
-- テーブルの構造 `emergency_contacts`
--

CREATE TABLE `emergency_contacts` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `relation` varchar(50) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `emergency_contacts`
--

INSERT INTO `emergency_contacts` (`id`, `member_id`, `name`, `relation`, `phone`, `created_at`, `updated_at`) VALUES
(1, 1, '流山次郎', '父', '047-000-0000', '2025-11-16 00:09:18', '2025-11-16 00:09:18'),
(2, 2, '江戸川健', '夫', '047-100-1000', '2025-11-16 00:09:18', '2025-11-16 00:09:18'),
(3, 17, 'aa', '父', '11111111', '2025-11-16 15:31:55', '2025-11-16 15:31:55');

-- --------------------------------------------------------

--
-- テーブルの構造 `members`
--

CREATE TABLE `members` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name_kana` varchar(50) DEFAULT NULL,
  `first_name_kana` varchar(50) DEFAULT NULL,
  `sex` enum('female','male','other') DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `age` tinyint(3) UNSIGNED DEFAULT NULL,
  `employment` enum('employee','fulltime','parttime','self','jobless','other') DEFAULT NULL,
  `workplace` varchar(120) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `address1` varchar(200) DEFAULT NULL,
  `phone_home` varchar(20) DEFAULT NULL,
  `phone_mobile` varchar(20) DEFAULT NULL,
  `contact_email` varchar(191) DEFAULT NULL,
  `has_spouse` tinyint(1) DEFAULT NULL,
  `num_children` tinyint(3) UNSIGNED DEFAULT 0,
  `cohabit_relation` varchar(120) DEFAULT NULL,
  `cohabit_others` varchar(200) DEFAULT NULL,
  `is_user` tinyint(1) NOT NULL DEFAULT 0,
  `is_provider` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `lat` double DEFAULT NULL,
  `lon` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `members`
--

INSERT INTO `members` (`id`, `user_id`, `last_name`, `first_name`, `last_name_kana`, `first_name_kana`, `sex`, `birthday`, `age`, `employment`, `workplace`, `postal_code`, `address1`, `phone_home`, `phone_mobile`, `contact_email`, `has_spouse`, `num_children`, `cohabit_relation`, `cohabit_others`, `is_user`, `is_provider`, `created_at`, `updated_at`, `lat`, `lon`) VALUES
(1, 1, '流山', '太郎', 'ナガレヤマ', 'タロウ', 'male', '1990-04-01', 35, 'employee', '流山市内勤務', '270-0192', '千葉県流山市平和台1-1-1', NULL, '08000000001', 'user01@example.com', 1, 1, '配偶者・子ども', '', 1, 0, '2025-11-16 00:09:18', '2025-11-16 00:09:18', 35.856703, 139.902646),
(2, 2, '江戸川', '美咲', 'エドガワ', 'ミサキ', 'female', '1992-08-11', 33, '', 'なし', '270-0115', '千葉県流山市江戸川台西1-1-1', NULL, '08000000002', 'user02@example.com', 1, 2, '配偶者・子ども', '', 1, 0, '2025-11-16 00:09:18', '2025-11-16 00:09:18', 35.8939, 139.9337),
(3, 3, '南流', '花子', 'ミナミナガレ', 'ハナコ', 'female', '1985-06-15', 40, 'employee', '市内勤務', '270-0163', '千葉県流山市南流山1-1-1', NULL, '08000000003', 'pro01@example.com', 1, 0, NULL, NULL, 0, 1, '2025-11-16 00:09:18', '2025-11-16 00:09:18', 35.838289, 139.903517),
(4, 4, '大鷹', '一郎', 'オオタカ', 'イチロウ', 'male', '1980-03-10', 45, 'employee', 'おおたかの森SC', '270-0139', '千葉県流山市おおたかの森西1-1-1', NULL, '08000000004', 'pro02@example.com', 1, 1, NULL, NULL, 0, 1, '2025-11-16 00:09:18', '2025-11-16 00:09:18', 35.8721, 139.9254),
(5, 5, '初石', '亮介', 'ハツイシ', 'リョウスケ', 'male', '1988-12-01', 36, 'employee', '初石中央', '270-0121', '千葉県流山市西初石1-1-1', NULL, '08000000005', 'pro03@example.com', 0, 0, NULL, NULL, 0, 1, '2025-11-16 00:09:18', '2025-11-16 00:09:18', 35.882991, 139.926122),
(17, 29, 'aa', 'aa', 'aa', 'aa', 'male', '1981-06-07', 44, 'employee', 'ああ', '2530002', '神奈川県茅ケ崎市高田5-6-3ラフォーレ湘南102', '0238239240', '09013323239', 'iii@iii', 1, 1, NULL, NULL, 1, 1, '2025-11-16 15:31:55', '2025-11-16 15:31:55', NULL, NULL);

-- --------------------------------------------------------

--
-- テーブルの構造 `m_activities`
--

CREATE TABLE `m_activities` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `m_activities`
--

INSERT INTO `m_activities` (`id`, `name`) VALUES
(7, 'その他'),
(1, '乳幼児・学童の預かり'),
(2, '保育所等への送迎'),
(5, '外出時の付き添い'),
(4, '産前・産後のサポート'),
(6, '買い物支援'),
(3, '軽度の病児の預かり');

-- --------------------------------------------------------

--
-- テーブルの構造 `m_license_types`
--

CREATE TABLE `m_license_types` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `m_license_types`
--

INSERT INTO `m_license_types` (`id`, `name`) VALUES
(5, 'その他'),
(1, '保育士・幼稚園教諭'),
(2, '小学校教諭'),
(3, '看護・保健師等'),
(4, '自動車免許');

-- --------------------------------------------------------

--
-- テーブルの構造 `provider_activities`
--

CREATE TABLE `provider_activities` (
  `provider_id` int(11) NOT NULL,
  `activity_id` tinyint(3) UNSIGNED NOT NULL,
  `other_text` varchar(120) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `provider_activities`
--

INSERT INTO `provider_activities` (`provider_id`, `activity_id`, `other_text`) VALUES
(1, 1, NULL),
(1, 2, NULL),
(2, 1, NULL),
(2, 3, NULL),
(3, 1, NULL),
(3, 7, '買い物つきそい可'),
(8, 1, NULL),
(8, 2, NULL),
(8, 3, NULL);

-- --------------------------------------------------------

--
-- テーブルの構造 `provider_availability`
--

CREATE TABLE `provider_availability` (
  `id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `weekday` tinyint(3) UNSIGNED NOT NULL,
  `period` enum('am','pm','night') NOT NULL,
  `state` enum('OK','MAYBE','NG') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `provider_availability`
--

INSERT INTO `provider_availability` (`id`, `provider_id`, `weekday`, `period`, `state`) VALUES
(1, 1, 1, 'am', 'OK'),
(2, 1, 1, 'pm', 'OK'),
(3, 1, 2, 'am', 'OK'),
(4, 1, 2, 'pm', 'MAYBE'),
(5, 1, 3, 'am', 'OK'),
(6, 1, 3, 'pm', 'NG'),
(7, 2, 3, 'am', 'OK'),
(8, 2, 3, 'pm', 'OK'),
(9, 2, 4, 'am', 'NG'),
(10, 2, 4, 'pm', 'OK'),
(11, 2, 5, 'am', 'MAYBE'),
(12, 2, 5, 'pm', 'OK'),
(13, 3, 5, 'am', 'OK'),
(14, 3, 5, 'pm', 'OK'),
(15, 3, 6, 'am', 'OK'),
(16, 3, 6, 'pm', 'OK'),
(17, 3, 7, 'am', 'MAYBE'),
(18, 3, 7, 'pm', 'OK'),
(19, 8, 1, 'am', 'OK'),
(20, 8, 1, 'pm', 'MAYBE'),
(21, 8, 2, 'am', 'NG'),
(22, 8, 2, 'pm', 'OK'),
(23, 8, 3, 'am', 'OK'),
(24, 8, 3, 'pm', 'OK'),
(25, 8, 4, 'am', 'NG'),
(26, 8, 4, 'pm', 'NG'),
(27, 8, 5, 'am', 'NG'),
(28, 8, 5, 'pm', 'OK'),
(29, 8, 6, 'am', 'NG'),
(30, 8, 6, 'pm', 'OK'),
(31, 8, 7, 'am', 'OK'),
(32, 8, 7, 'pm', 'OK');

-- --------------------------------------------------------

--
-- テーブルの構造 `provider_licenses`
--

CREATE TABLE `provider_licenses` (
  `provider_id` int(11) NOT NULL,
  `license_id` tinyint(3) UNSIGNED NOT NULL,
  `other_text` varchar(120) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `provider_licenses`
--

INSERT INTO `provider_licenses` (`provider_id`, `license_id`, `other_text`) VALUES
(1, 1, NULL),
(1, 4, NULL),
(2, 3, NULL),
(3, 4, NULL),
(8, 1, NULL),
(8, 2, NULL),
(8, 3, NULL);

-- --------------------------------------------------------

--
-- テーブルの構造 `provider_profiles`
--

CREATE TABLE `provider_profiles` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `car_allowed` tinyint(1) DEFAULT 0,
  `insurance_status` enum('none','have') DEFAULT 'none',
  `pets_notes` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `provider_profiles`
--

INSERT INTO `provider_profiles` (`id`, `member_id`, `car_allowed`, `insurance_status`, `pets_notes`, `remarks`, `created_at`, `updated_at`) VALUES
(1, 3, 1, 'have', 'ペット不可', '車での送迎可', '2025-11-16 00:09:18', '2025-11-16 00:09:18'),
(2, 4, 0, 'none', 'ペット相談可', '近隣のみ対応', '2025-11-16 00:09:18', '2025-11-16 00:09:18'),
(3, 5, 1, 'have', 'ペット可', '柔軟に対応可', '2025-11-16 00:09:18', '2025-11-16 00:09:18'),
(8, 17, 1, 'have', 'aa', 'aa', '2025-11-16 15:31:55', '2025-11-16 15:31:55');

-- --------------------------------------------------------

--
-- テーブルの構造 `support_requests`
--

CREATE TABLE `support_requests` (
  `id` int(11) NOT NULL,
  `requester_member_id` int(11) DEFAULT NULL,
  `request_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `address` varchar(255) NOT NULL,
  `detail` text NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `lat` double DEFAULT NULL,
  `lon` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `support_requests`
--

INSERT INTO `support_requests` (`id`, `requester_member_id`, `request_date`, `start_time`, `end_time`, `address`, `detail`, `note`, `created_at`, `lat`, `lon`) VALUES
(1, 1, '2025-11-20', '10:00:00', '13:00:00', '千葉県流山市平和台1-1-1', '自宅で子ども1人の預かり希望', '特記事項なし', '2025-11-16 09:09:18', 35.856703, 139.902646),
(2, 2, '2025-11-22', '09:00:00', '12:00:00', '千葉県流山市江戸川台西1-1-1', '保育園への送迎とその後の自宅預かり', '雨天時も依頼予定', '2025-11-16 09:09:18', 35.8939, 139.9337),
(5, 1, '2025-11-21', '13:32:00', '16:32:00', '千葉県流山市平和台1-1-1', '兄弟2人を見てほしい', NULL, '2025-11-17 00:33:24', 35.856703, 139.902646);

-- --------------------------------------------------------

--
-- テーブルの構造 `temporary_matches`
--

CREATE TABLE `temporary_matches` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `score` float NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `temporary_matches`
--

INSERT INTO `temporary_matches` (`id`, `request_id`, `provider_id`, `score`, `created_at`) VALUES
(5, 5, 2, 0.905526, '2025-11-17 00:35:23');

-- --------------------------------------------------------

--
-- テーブルの構造 `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `login_id` varchar(64) NOT NULL,
  `email` varchar(191) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_staff` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `users`
--

INSERT INTO `users` (`id`, `login_id`, `email`, `password_hash`, `is_staff`, `created_at`, `updated_at`) VALUES
(1, 'user_taro', 'user_taro@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, '2025-11-16 00:09:18', '2025-11-16 00:09:18'),
(2, 'user_misaki', 'user_misaki@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, '2025-11-16 00:09:18', '2025-11-16 00:09:18'),
(3, 'pro_minami', 'pro_minami@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, '2025-11-16 00:09:18', '2025-11-16 00:09:18'),
(4, 'pro_ootaka', 'pro_ootaka@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, '2025-11-16 00:09:18', '2025-11-16 00:09:18'),
(5, 'pro_hatsuishi', 'pro_hatsuishi@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, '2025-11-16 00:09:18', '2025-11-16 00:09:18'),
(6, 'staff_admin', 'staff_admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, '2025-11-16 00:09:18', '2025-11-16 00:09:18'),
(29, 'pro10', 'iii@iii', '$2y$10$s8u8sC7TBD06I2PYIjLKQ.Y9bSGiiXcp8SCNmulElHsEvP1q0/3k6', 0, '2025-11-16 15:31:54', '2025-11-16 15:31:54');

-- --------------------------------------------------------

--
-- ビュー用の代替構造 `v_provider_profile_text`
-- (実際のビューを参照するには下にあります)
--
CREATE TABLE `v_provider_profile_text` (
`provider_id` int(11)
,`profile_text` mediumtext
);

-- --------------------------------------------------------

--
-- ビュー用の構造 `v_provider_profile_text`
--
DROP TABLE IF EXISTS `v_provider_profile_text`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_provider_profile_text`  AS SELECT `pp`.`id` AS `provider_id`, concat_ws(' ',`m`.`last_name`,`m`.`first_name`,`m`.`address1`,`m`.`employment`,`m`.`workplace`,group_concat(distinct `act`.`name` separator ' '),group_concat(distinct `lic`.`name` separator ' '),`pp`.`pets_notes`,`pp`.`remarks`) AS `profile_text` FROM (((((`provider_profiles` `pp` join `members` `m` on(`m`.`id` = `pp`.`member_id`)) left join `provider_activities` `pa` on(`pa`.`provider_id` = `pp`.`id`)) left join `m_activities` `act` on(`act`.`id` = `pa`.`activity_id`)) left join `provider_licenses` `pl` on(`pl`.`provider_id` = `pp`.`id`)) left join `m_license_types` `lic` on(`lic`.`id` = `pl`.`license_id`)) GROUP BY `pp`.`id` ;

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `activity_reports`
--
ALTER TABLE `activity_reports`
  ADD PRIMARY KEY (`id`);

--
-- テーブルのインデックス `children`
--
ALTER TABLE `children`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_children_member` (`member_id`);

--
-- テーブルのインデックス `confirmed_matches`
--
ALTER TABLE `confirmed_matches`
  ADD PRIMARY KEY (`id`);

--
-- テーブルのインデックス `emergency_contacts`
--
ALTER TABLE `emergency_contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_emc_member` (`member_id`);

--
-- テーブルのインデックス `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- テーブルのインデックス `m_activities`
--
ALTER TABLE `m_activities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- テーブルのインデックス `m_license_types`
--
ALTER TABLE `m_license_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- テーブルのインデックス `provider_activities`
--
ALTER TABLE `provider_activities`
  ADD PRIMARY KEY (`provider_id`,`activity_id`),
  ADD KEY `idx_pa_provider` (`provider_id`),
  ADD KEY `idx_pa_activity` (`activity_id`);

--
-- テーブルのインデックス `provider_availability`
--
ALTER TABLE `provider_availability`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_provider_slot` (`provider_id`,`weekday`,`period`),
  ADD KEY `idx_pav_provider` (`provider_id`);

--
-- テーブルのインデックス `provider_licenses`
--
ALTER TABLE `provider_licenses`
  ADD PRIMARY KEY (`provider_id`,`license_id`),
  ADD KEY `idx_pl_provider` (`provider_id`),
  ADD KEY `idx_pl_license` (`license_id`);

--
-- テーブルのインデックス `provider_profiles`
--
ALTER TABLE `provider_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `member_id` (`member_id`);

--
-- テーブルのインデックス `support_requests`
--
ALTER TABLE `support_requests`
  ADD PRIMARY KEY (`id`);

--
-- テーブルのインデックス `temporary_matches`
--
ALTER TABLE `temporary_matches`
  ADD PRIMARY KEY (`id`);

--
-- テーブルのインデックス `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_login` (`login_id`),
  ADD UNIQUE KEY `uq_users_email` (`email`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `activity_reports`
--
ALTER TABLE `activity_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- テーブルの AUTO_INCREMENT `children`
--
ALTER TABLE `children`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- テーブルの AUTO_INCREMENT `confirmed_matches`
--
ALTER TABLE `confirmed_matches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- テーブルの AUTO_INCREMENT `emergency_contacts`
--
ALTER TABLE `emergency_contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- テーブルの AUTO_INCREMENT `members`
--
ALTER TABLE `members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- テーブルの AUTO_INCREMENT `m_activities`
--
ALTER TABLE `m_activities`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- テーブルの AUTO_INCREMENT `m_license_types`
--
ALTER TABLE `m_license_types`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- テーブルの AUTO_INCREMENT `provider_availability`
--
ALTER TABLE `provider_availability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- テーブルの AUTO_INCREMENT `provider_profiles`
--
ALTER TABLE `provider_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- テーブルの AUTO_INCREMENT `support_requests`
--
ALTER TABLE `support_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- テーブルの AUTO_INCREMENT `temporary_matches`
--
ALTER TABLE `temporary_matches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- テーブルの AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- ダンプしたテーブルの制約
--

--
-- テーブルの制約 `children`
--
ALTER TABLE `children`
  ADD CONSTRAINT `fk_children_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `emergency_contacts`
--
ALTER TABLE `emergency_contacts`
  ADD CONSTRAINT `fk_emc_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `members`
--
ALTER TABLE `members`
  ADD CONSTRAINT `fk_members_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- テーブルの制約 `provider_activities`
--
ALTER TABLE `provider_activities`
  ADD CONSTRAINT `fk_pa_activity` FOREIGN KEY (`activity_id`) REFERENCES `m_activities` (`id`),
  ADD CONSTRAINT `fk_pa_provider` FOREIGN KEY (`provider_id`) REFERENCES `provider_profiles` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `provider_availability`
--
ALTER TABLE `provider_availability`
  ADD CONSTRAINT `fk_pav_provider` FOREIGN KEY (`provider_id`) REFERENCES `provider_profiles` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `provider_licenses`
--
ALTER TABLE `provider_licenses`
  ADD CONSTRAINT `fk_pl_license` FOREIGN KEY (`license_id`) REFERENCES `m_license_types` (`id`),
  ADD CONSTRAINT `fk_pl_provider` FOREIGN KEY (`provider_id`) REFERENCES `provider_profiles` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `provider_profiles`
--
ALTER TABLE `provider_profiles`
  ADD CONSTRAINT `fk_provider_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
