-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: 19 سبتمبر 2026 الساعة 17:36
-- إصدار الخادم: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kids`
--

-- --------------------------------------------------------

--
-- بنية الجدول `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `role` enum('super_admin','admin') NOT NULL DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `admins`
--

INSERT INTO `admins` (`id`, `username`, `password_hash`, `full_name`, `role`, `created_at`) VALUES
(1, 'lama', '$2y$10$zdxwE/Dxvbmf/ZwdSLBqXOHV.rjkXfdyCUfkmxdIycIcuxAkVqpgC', 'Lama', 'super_admin', '2026-03-31 12:02:39');

-- --------------------------------------------------------

--
-- بنية الجدول `animals`
--

CREATE TABLE `animals` (
  `id` int(11) NOT NULL,
  `category` varchar(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `video` varchar(255) DEFAULT NULL,
  `pos_top` varchar(20) DEFAULT '30%',
  `pos_left` varchar(20) DEFAULT '30%',
  `width` varchar(20) DEFAULT '130px',
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by_supervisor` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `animals`
--

INSERT INTO `animals` (`id`, `category`, `name`, `image`, `video`, `pos_top`, `pos_left`, `width`, `sort_order`, `created_at`, `created_by_supervisor`) VALUES
(1, 'wild', 'اسد', 'images/general/animals/items/lion.png', 'images/general/animals/videos/lion.mp4', '31.89%', '30.27%', '210px', 1, '2026-05-22 04:31:40', 0),
(2, 'wild', 'نمر', 'images/general/animals/items/tiger.png', 'images/general/animals/videos/tiger.mp4', '64%', '4%', '220px', 2, '2026-05-22 04:31:40', 0),
(3, 'wild', 'ذئب', 'images/general/animals/items/wolf.png', 'images/general/animals/videos/wolf.mp4', '36%', '5%', '140px', 3, '2026-05-22 04:31:40', 0),
(4, 'wild', 'ثعلب', 'images/general/animals/items/fox.png', 'images/general/animals/videos/fox.mp4', '66%', '48%', '195px', 4, '2026-05-22 04:31:40', 0),
(5, 'wild', 'دب', 'images/general/animals/items/bear.png', 'images/general/animals/videos/bear.mp4', '31%', '75%', '160px', 5, '2026-05-22 04:31:40', 0),
(6, 'wild', 'ثعبان', 'images/general/animals/items/snake.png', 'images/general/animals/videos/snake.mp4', '81%', '30%', '120px', 6, '2026-05-22 04:31:40', 0),
(7, 'wild', 'تمساح', 'images/general/animals/items/crocodile.png', 'images/general/animals/videos/crocodile.mp4', '70%', '72%', '220px', 7, '2026-05-22 04:31:40', 0),
(8, 'wild', 'نسر', 'images/general/animals/items/eagle.png', 'images/general/animals/videos/eagle.mp4', '12%', '12%', '110px', 8, '2026-05-22 04:31:40', 0),
(9, 'wild', 'ضبع', 'images/general/animals/items/hyena.png', 'images/general/animals/videos/hyena.mp4', '36%', '55%', '150px', 9, '2026-05-22 04:31:40', 0),
(10, 'pet', 'عصفور', 'images/general/animals/items/bird.png', 'images/general/animals/videos/bird.mp4', '16.34%', '3.54%', '100px', 1, '2026-05-22 04:31:40', 0),
(11, 'pet', 'فراشة', 'images/general/animals/items/butterfly.png', 'images/general/animals/videos/butterfly.mp4', '13.20%', '59.36%', '55px', 2, '2026-05-22 04:31:40', 0),
(12, 'pet', 'صوص', 'images/general/animals/items/chick.png', 'images/general/animals/videos/chick.mp4', '48.60%', '67.27%', '40px', 3, '2026-05-22 04:31:40', 0),
(13, 'pet', 'أرنب', 'images/general/animals/items/rabbit.png', 'images/general/animals/videos/rabbit.mp4', '52.07%', '85.00%', '60px', 4, '2026-05-22 04:31:40', 0),
(14, 'pet', 'بطة', 'images/general/animals/items/duck.png', 'images/general/animals/videos/duck.mp4', '72.87%', '77.45%', '85px', 5, '2026-05-22 04:31:40', 0),
(15, 'pet', 'دجاجة', 'images/general/animals/items/chicken.png', 'images/general/animals/videos/chicken.mp4', '38.67%', '72.45%', '95px', 6, '2026-05-22 04:31:40', 0),
(16, 'pet', 'قطة', 'images/general/animals/items/cat.png', 'images/general/animals/videos/cat.mp4', '49.47%', '16.45%', '55px', 7, '2026-05-22 04:31:40', 0),
(17, 'pet', 'كلب', 'images/general/animals/items/dog.png', 'images/general/animals/videos/dog.mp4', '50.67%', '4.55%', '80px', 8, '2026-05-22 04:31:40', 0),
(18, 'pet', 'بقرة', 'images/general/animals/items/cow.png', 'images/general/animals/videos/cow.mp4', '72.27%', '2.27%', '230px', 9, '2026-05-22 04:31:40', 0),
(19, 'pet', 'خروف', 'images/general/animals/items/sheep.png', 'images/general/animals/videos/sheep.mp4', '58.40%', '22.91%', '160px', 10, '2026-05-22 04:31:40', 0),
(20, 'pet', 'سلحفاة', 'images/general/animals/items/turtle.png', 'images/general/animals/videos/turtle.mp4', '59.13%', '59.00%', '90px', 11, '2026-05-22 04:31:40', 0),
(21, 'pet', 'حصان', 'images/general/animals/items/horse.png', 'images/general/animals/videos/horse.mp4', '32.80%', '38.27%', '200px', 12, '2026-05-22 04:31:40', 0),
(22, 'pet', 'حمار', 'images/general/animals/items/donkey.png', 'images/general/animals/videos/donkey.mp4', '71.13%', '37.55%', '210px', 13, '2026-05-22 04:31:40', 0);

-- --------------------------------------------------------

--
-- بنية الجدول `arabic_letter_examples`
--

CREATE TABLE `arabic_letter_examples` (
  `id` int(11) NOT NULL,
  `letter_id` int(11) NOT NULL,
  `letter_text` varchar(20) NOT NULL,
  `example_word` varchar(255) DEFAULT NULL,
  `custom_sign` varchar(255) DEFAULT NULL,
  `card_image` varchar(255) DEFAULT NULL,
  `start_word` varchar(100) NOT NULL,
  `start_image` varchar(255) DEFAULT NULL,
  `start_video` varchar(255) DEFAULT NULL,
  `middle_word` varchar(100) NOT NULL,
  `middle_image` varchar(255) DEFAULT NULL,
  `middle_video` varchar(255) DEFAULT NULL,
  `end_word` varchar(100) NOT NULL,
  `end_image` varchar(255) DEFAULT NULL,
  `end_video` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `alone_image` varchar(255) DEFAULT NULL,
  `coloring_image` varchar(255) DEFAULT NULL,
  `created_by_supervisor` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `arabic_letter_examples`
--

INSERT INTO `arabic_letter_examples` (`id`, `letter_id`, `letter_text`, `example_word`, `custom_sign`, `card_image`, `start_word`, `start_image`, `start_video`, `middle_word`, `middle_image`, `middle_video`, `end_word`, `end_image`, `end_video`, `created_at`, `alone_image`, `coloring_image`, `created_by_supervisor`) VALUES
(1, 1, 'أ', NULL, NULL, 'uploads/images/1779046503_6492_1.png', 'اسد', 'uploads/images/1779046503_6492_1.png', 'images/videos/اسد.mp4', 'سماء', 'images/examples/1/middle.png', 'uploads/videos/1779038345_7707_سماء.mp4', 'عصا', 'images/examples/1/end.png', 'images/videos/عصا.mp4', '2026-05-17 16:07:40', NULL, NULL, 0),
(2, 2, 'ب', NULL, NULL, 'images/letters/arabic/2.png', 'بقرة', 'uploads/images/1779035882_8735_2.png', 'images/videos/بقرة.mp4', 'حبل', 'images/examples/2/middle.png', 'images/videos/حبل.mp4', 'قلب', 'images/examples/2/end.png', 'images/videos/قلب.mp4', '2026-05-17 16:07:40', NULL, NULL, 0),
(3, 3, 'ت', NULL, NULL, 'images/examples/3/start.png', 'تفاحة', 'images/examples/3/start.png', 'images/videos/تفاحة.mp4', 'كتاب', 'images/examples/3/middle.png', 'images/videos/كتاب.mp4', 'بنت', 'images/examples/3/end.png', 'images/videos/بنت.mp4', '2026-05-17 16:07:40', NULL, NULL, 0),
(4, 4, 'ث', NULL, NULL, 'images/letters/arabic/4.png', 'ثعبان', 'images/examples/4/start.png', 'images/videos/ثعبان.mp4', 'مثلجات', 'images/examples/4/middle.png', 'images/videos/مثلجات.mp4', 'مثلث', 'images/examples/4/end.png', 'images/videos/مثلث.mp4', '2026-05-17 16:07:40', NULL, NULL, 0),
(5, 5, 'ج', NULL, NULL, 'images/letters/arabic/5.png', 'جمل', 'images/examples/5/start.png', 'images/videos/جمل.mp4', 'شجرة', 'images/examples/5/middle.png', 'images/videos/شجرة.mp4', 'ثلج', 'images/examples/5/end.png', 'images/videos/ثلج.mp4', '2026-05-17 16:07:40', NULL, NULL, 0),
(6, 6, 'ح', NULL, NULL, 'images/letters/arabic/6.png', 'حلوى', 'images/examples/6/start.png', 'images/videos/حلوى.mp4', 'بحر', 'images/examples/6/middle.png', 'images/videos/بحر.mp4', 'تفاح', 'images/examples/6/end.png', 'images/videos/تفاح.mp4', '2026-05-17 16:07:40', NULL, NULL, 0),
(7, 7, 'خ', NULL, NULL, 'images/letters/arabic/7.png', 'خروف', 'images/examples/7/start.png', 'images/videos/خروف.mp4', 'نخلة', 'images/examples/7/middle.png', 'images/videos/نخلة.mp4', 'بطيخ', 'images/examples/7/end.png', 'images/videos/بطيخ.mp4', '2026-05-17 16:07:40', NULL, NULL, 0),
(8, 8, 'د', NULL, NULL, 'images/letters/arabic/8.png', 'دجاجة', 'images/examples/8/start.png', 'images/videos/دجاجة.mp4', 'هدية', 'images/examples/8/middle.png', 'images/videos/هدية.mp4', 'ولد', 'images/examples/8/end.png', 'images/videos/ولد.mp4', '2026-05-17 16:07:40', NULL, NULL, 0),
(9, 9, 'ذ', NULL, NULL, 'images/letters/arabic/9.png', 'ذئب', 'images/examples/9/start.png', 'images/videos/ذئب.mp4', 'حذاء', 'images/examples/9/middle.png', 'images/videos/حذاء.mp4', 'أستاذ', 'images/examples/9/end.png', 'images/videos/أستاذ.mp4', '2026-05-17 16:07:40', NULL, NULL, 0),
(10, 10, 'ر', NULL, NULL, 'images/letters/arabic/10.png', 'رمل', 'images/examples/10/start.png', 'images/videos/رمل.mp4', 'صورة', 'images/examples/10/middle.png', 'images/videos/صورة.mp4', 'قمر', 'images/examples/10/end.png', 'images/videos/قمر.mp4', '2026-05-17 16:07:40', NULL, NULL, 0),
(11, 11, 'ز', NULL, NULL, 'images/letters/arabic/11.png', 'زرافة', 'images/examples/11/start.png', 'images/videos/زرافة.mp4', 'مزهرية', 'images/examples/11/middle.png', 'images/videos/مزهرية.mp4', 'أرز', 'images/examples/11/end.png', 'images/videos/أرز.mp4', '2026-05-17 16:07:40', NULL, NULL, 0),
(12, 12, 'س', NULL, NULL, 'images/letters/arabic/12.png', 'ساعة', 'images/signs/arabic/12.png', 'images/videos/ساعة.mp4', 'مسجد', 'images/examples/12/middle.png', 'images/videos/مسجد.mp4', 'جرس', 'images/examples/12/end.png', 'images/videos/جرس.mp4', '2026-05-17 16:07:40', NULL, NULL, 0),
(13, 13, 'ش', NULL, NULL, 'images/letters/arabic/13.png', 'شمس', 'images/examples/13/start.png', 'images/videos/شمس.mp4', 'فراشة', 'images/examples/13/middle.png', 'images/videos/فراشة.mp4', 'ريش', 'images/examples/13/end.png', 'images/videos/ريش.mp4', '2026-05-17 16:07:40', NULL, NULL, 0),
(14, 14, 'ص', NULL, NULL, 'images/letters/arabic/14.png', 'صندوق', 'images/examples/14/start.png', 'images/videos/صندوق.mp4', 'عصفور', 'images/examples/14/middle.png', 'images/videos/عصفور.mp4', 'قفص', 'images/examples/14/end.png', 'images/videos/قفص.mp4', '2026-05-17 16:07:40', NULL, NULL, 0),
(15, 15, 'ض', NULL, NULL, 'images/letters/arabic/15.png', 'ضفدع', 'images/examples/15/start.png', 'images/videos/ضفدع.mp4', 'مضرب', 'images/examples/15/middle.png', 'images/videos/مضرب.mp4', 'بيض', 'images/examples/15/end.png', 'images/videos/بيض.mp4', '2026-05-17 16:07:40', NULL, NULL, 0),
(16, 16, 'ط', NULL, NULL, 'images/letters/arabic/16.png', 'طفل', 'images/examples/16/start.png', 'images/videos/طفل.mp4', 'بطريق', 'images/examples/16/middle.png', 'images/videos/بطريق.mp4', 'خيط', 'images/examples/16/end.png', 'images/videos/خيط.mp4', '2026-05-17 16:07:40', NULL, NULL, 0),
(17, 17, 'ظ', NULL, NULL, 'images/letters/arabic/17.png', 'ظرف', 'images/examples/17/start.png', 'images/videos/ظرف.mp4', 'مظلة', 'images/examples/17/middle.png', 'images/videos/مظلة.mp4', 'حظ', 'images/examples/17/end.png', 'images/videos/حظ.mp4', '2026-05-17 16:07:40', NULL, NULL, 0),
(18, 18, 'ع', NULL, NULL, 'images/letters/arabic/18.png', 'عصفور', 'images/examples/18/start.png', 'images/videos/عصفور.mp4', 'ملعقة', 'images/examples/18/middle.png', 'images/videos/ملعقة.mp4', 'شمع', 'images/examples/18/end.png', 'images/videos/شمع.mp4', '2026-05-17 16:07:40', NULL, NULL, 0),
(19, 19, 'غ', NULL, NULL, 'images/letters/arabic/19.png', 'غيوم', 'images/examples/19/start.png', 'images/videos/غيوم.mp4', 'مغناطيس', 'images/examples/19/middle.png', 'images/videos/مغناطيس.mp4', 'صمغ', 'images/examples/19/end.png', 'images/videos/صمغ.mp4', '2026-05-17 16:07:40', NULL, NULL, 0),
(20, 20, 'ف', NULL, NULL, 'images/letters/arabic/20.png', 'فراشة', 'images/examples/20/start.png', 'images/videos/فراشة.mp4', 'مفتاح', 'images/examples/20/middle.png', 'images/videos/مفتاح.mp4', 'أنف', 'images/examples/20/end.png', 'images/videos/أنف.mp4', '2026-05-17 16:07:40', NULL, NULL, 0),
(21, 21, 'ق', NULL, NULL, 'images/letters/arabic/21.png', 'قرد', 'images/examples/21/start.png', 'images/videos/قرد.mp4', 'حقل', 'images/examples/21/middle.png', 'images/videos/حقل.mp4', 'طبق', 'images/examples/21/end.png', 'images/videos/طبق.mp4', '2026-05-17 16:07:40', NULL, NULL, 0),
(22, 22, 'ك', NULL, NULL, 'images/letters/arabic/22.png', 'كتاب', 'images/examples/22/start.png', 'images/videos/كتاب.mp4', 'سمكة', 'images/examples/22/middle.png', 'images/videos/سمكة.mp4', 'ديك', 'images/examples/22/end.png', 'images/videos/ديك.mp4', '2026-05-17 16:07:40', NULL, NULL, 0),
(23, 23, 'ل', NULL, NULL, 'images/letters/arabic/23.png', 'ليمون', 'images/examples/23/start.png', 'images/videos/ليمون.mp4', 'حليب', 'images/examples/23/middle.png', 'images/videos/حليب.mp4', 'جمل', 'images/examples/23/end.png', 'images/videos/جمل.mp4', '2026-05-17 16:07:40', NULL, NULL, 0),
(24, 24, 'م', NULL, NULL, 'images/letters/arabic/24.png', 'مروحة', 'images/examples/24/start.png', 'images/videos/مروحة.mp4', 'دمية', 'images/examples/24/middle.png', 'images/videos/دمية.mp4', 'حزام', 'images/examples/24/end.png', 'images/videos/حزام.mp4', '2026-05-17 16:07:40', NULL, NULL, 0),
(25, 25, 'ن', NULL, NULL, 'images/letters/arabic/25.png', 'نحلة', 'images/examples/25/start.png', 'images/videos/نحلة.mp4', 'أرنب', 'images/examples/25/middle.png', 'images/videos/أرنب.mp4', 'عين', 'images/examples/25/end.png', 'images/videos/عين.mp4', '2026-05-17 16:07:40', NULL, NULL, 0),
(26, 26, 'هـ', NULL, NULL, 'images/letters/arabic/26.png', 'هدية', 'images/examples/26/start.png', 'images/videos/هدية.mp4', 'ذهب', 'images/examples/26/middle.png', 'images/videos/ذهب.mp4', 'وجه', 'images/examples/26/end.png', 'images/videos/وجه.mp4', '2026-05-17 16:07:40', NULL, NULL, 0),
(27, 27, 'و', NULL, NULL, 'images/letters/arabic/27.png', 'ورود', 'images/examples/27/start.png', 'images/videos/ورود.mp4', 'موز', 'images/examples/27/middle.png', 'images/videos/موز.mp4', 'جرو', 'images/examples/27/end.png', 'images/videos/جرو.mp4', '2026-05-17 16:07:40', NULL, NULL, 0),
(28, 28, 'ي', NULL, NULL, 'images/letters/arabic/28.png', 'يد', 'images/examples/28/start.png', 'images/videos/يد.mp4', 'بيت', 'images/examples/28/middle.png', 'images/videos/بيت.mp4', 'كرسي', 'images/examples/28/end.png', 'images/videos/كرسي.mp4', '2026-05-17 16:07:40', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- بنية الجدول `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `child_id` int(11) NOT NULL,
  `child_name` varchar(255) NOT NULL,
  `sender` enum('child','admin') NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `child_id`, `child_name`, `sender`, `message`, `is_read`, `created_at`) VALUES
(36, 3, 'MASA', 'child', 'مرحبا', 1, '2026-06-03 07:32:15'),
(37, 3, 'MASA', 'admin', 'مرحبا', 1, '2026-06-03 07:32:28'),
(38, 3, 'MASA', 'admin', 'مرحبا', 1, '2026-06-08 13:32:32'),
(39, 3, 'MASA', 'child', 'السلام عليكم', 0, '2026-09-19 14:13:28');

-- --------------------------------------------------------

--
-- بنية الجدول `children`
--

CREATE TABLE `children` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `gender` enum('male','female') NOT NULL,
  `support_type` enum('hearing_support','visual_support','general_support') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `phone` varchar(10) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `children`
--

INSERT INTO `children` (`id`, `username`, `password_hash`, `gender`, `support_type`, `created_at`, `phone`, `last_login`) VALUES
(1, 'nasma', '$2y$10$dPXkuDHp2.B8aNY6/vhRnuQxUBg.VmA5dZg7B8fr6zGZwJ7PGcC4G', 'female', 'general_support', '2026-03-31 12:16:31', '+970599123', '2026-06-02 11:50:37'),
(2, 'omar', '$2y$10$dxIqFoJsd4AsAylQeu3PMute1xcwnSjMEuM0lQO9x.uATPFnjWKD2', 'male', 'hearing_support', '2026-03-31 13:24:32', '+970599876', NULL),
(3, 'MASA', '$2y$10$0sx33Mhl1OD3XsllYZNXy.p88JLSrpeSCKPcemJiWvkr.2IfRL8Ni', 'female', 'general_support', '2026-04-03 21:52:24', '0598987654', '2026-09-19 18:24:35'),
(11, 'rahaf', '$2y$10$6E3iMTHb8prjXZI9Y9Gp2On6Vv/uu4QGY5gAFqssOwAE2C/FpX3RC', 'female', 'hearing_support', '2026-09-14 20:02:11', '0598561', '2026-09-14 23:02:19'),
(12, 'SEMA', '$2y$10$n1tMgMmBur7A9cSdE2zxQOrrQHnFk/wPBOhhwm7Q6PLXogSK.5psG', 'female', 'hearing_support', '2026-09-19 15:34:05', '0599876543', NULL),
(13, 'LMLM', '$2y$10$drwjRego3eU82PfyzUPP/eSM1GNdSwAqAXdKDledI3hNcVhjC75fW', 'female', 'hearing_support', '2026-09-19 15:35:15', '0597941355', '2026-09-19 18:35:23');

-- --------------------------------------------------------

--
-- بنية الجدول `child_progress`
--

CREATE TABLE `child_progress` (
  `id` int(11) NOT NULL,
  `child_id` int(11) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `completed_lessons` int(11) DEFAULT 0,
  `stars` int(11) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `food_items`
--

CREATE TABLE `food_items` (
  `id` int(11) NOT NULL,
  `category` varchar(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `video` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `food_items`
--

INSERT INTO `food_items` (`id`, `category`, `name`, `image`, `video`, `sort_order`, `created_at`) VALUES
(1, 'vegetable', 'طماطم', 'images/general/vegetables/images/tomato.png', 'images/general/vegetables/videos/tomato.mp4', 1, '2026-05-22 05:00:05'),
(2, 'vegetable', 'خيار', 'images/general/vegetables/images/cucumber.png', 'images/general/vegetables/videos/cucumber.mp4', 2, '2026-05-22 05:00:05'),
(3, 'vegetable', 'جزر', 'images/general/vegetables/images/carrot.png', 'images/general/vegetables/videos/carrot.mp4', 3, '2026-05-22 05:00:05'),
(4, 'vegetable', 'بطاطا', 'images/general/vegetables/images/potato.png', 'images/general/vegetables/videos/potato.mp4', 4, '2026-05-22 05:00:05'),
(5, 'vegetable', 'باذنجان', 'images/general/vegetables/images/eggplant.png', 'images/general/vegetables/videos/eggplant.mp4', 5, '2026-05-22 05:00:05'),
(6, 'vegetable', 'فلفل', 'images/general/vegetables/images/pepper.png', 'images/general/vegetables/videos/pepper.mp4', 6, '2026-05-22 05:00:05'),
(7, 'vegetable', 'بصل', 'images/general/vegetables/images/onion.png', 'images/general/vegetables/videos/onion.mp4', 7, '2026-05-22 05:00:05'),
(8, 'vegetable', 'خس', 'images/general/vegetables/images/lettuce.png', 'images/general/vegetables/videos/lettuce.mp4', 8, '2026-05-22 05:00:05'),
(9, 'vegetable', 'كوسا', 'images/general/vegetables/images/zucchini.png', 'images/general/vegetables/videos/zucchini.mp4', 9, '2026-05-22 05:00:05'),
(10, 'vegetable', 'ثوم', 'images/general/vegetables/images/garlic.png', 'images/general/vegetables/videos/garlic.mp4', 10, '2026-05-22 05:00:05'),
(11, 'vegetable', 'ذرة', 'images/general/vegetables/images/corn.png', 'images/general/vegetables/videos/corn.mp4', 11, '2026-05-22 05:00:05'),
(12, 'vegetable', 'ليمون', 'images/general/vegetables/images/lemon.png', 'images/general/vegetables/videos/lemon.mp4', 12, '2026-05-22 05:00:05'),
(13, 'fruit', 'خوخ', 'images/general/fruits/images/peach.png', 'images/general/fruits/videos/peach.mp4', 1, '2026-05-22 05:00:05'),
(14, 'fruit', 'برتقال', 'images/general/fruits/images/orange.png', 'images/general/fruits/videos/orange.mp4', 2, '2026-05-22 05:00:05'),
(15, 'fruit', 'بطيخ', 'images/general/fruits/images/melon.png', 'images/general/fruits/videos/melon.mp4', 3, '2026-05-22 05:00:05'),
(16, 'fruit', 'مانجا', 'images/general/fruits/images/mango.png', 'images/general/fruits/videos/mango.mp4', 4, '2026-05-22 05:00:05'),
(17, 'fruit', 'كيوي', 'images/general/fruits/images/kiwi.png', 'images/general/fruits/videos/kiwi.mp4', 5, '2026-05-22 05:00:05'),
(18, 'fruit', 'عنب', 'images/general/fruits/images/grape.png', 'images/general/fruits/videos/grape.mp4', 6, '2026-05-22 05:00:05'),
(19, 'fruit', 'تين', 'images/general/fruits/images/fig.png', 'images/general/fruits/videos/fig.mp4', 7, '2026-05-22 05:00:05'),
(20, 'fruit', 'موز', 'images/general/fruits/images/banana.png', 'images/general/fruits/videos/banana.mp4', 8, '2026-05-22 05:00:05'),
(21, 'fruit', 'تفاح', 'images/general/fruits/images/apple.png', 'images/general/fruits/videos/apple.mp4', 9, '2026-05-22 05:00:05'),
(22, 'fruit', 'جوز الهند', 'images/general/fruits/images/coconut.png', 'images/general/fruits/videos/coconut.mp4', 10, '2026-05-22 05:00:05'),
(23, 'fruit', 'فراولة', 'images/general/fruits/images/strawberry.png', 'images/general/fruits/videos/strawberry.mp4', 11, '2026-05-22 05:00:05'),
(24, 'fruit', 'اجاص', 'images/general/fruits/images/pear.png', 'images/general/fruits/videos/pear.mp4', 12, '2026-05-22 05:00:05');

-- --------------------------------------------------------

--
-- بنية الجدول `games`
--

CREATE TABLE `games` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `title_en` varchar(120) NOT NULL DEFAULT '',
  `description` text DEFAULT NULL,
  `description_en` text DEFAULT NULL,
  `game_type` varchar(100) NOT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `url` varchar(255) NOT NULL DEFAULT '',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `game_settings` text NOT NULL DEFAULT '{}',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `games`
--

INSERT INTO `games` (`id`, `title`, `title_en`, `description`, `description_en`, `game_type`, `thumbnail`, `url`, `is_active`, `sort_order`, `game_settings`, `created_at`) VALUES
(1, 'لعبة الذاكرة', 'Memory Game', 'تساعد الطفل على التركيز وتقوية الذاكرة من خلال إيجاد الأزواج المتطابقة.', 'Helps the child improve focus and memory by finding matching pairs.', 'memory', 'uploads/games/thumbs/6a217a03110d57.15416942.png', '../games/game1/memory.php', 1, 1, '{\"pairs\":4,\"card_image\":\"assets\\/images\\/animals\\/dog.png\"}', '2026-05-23 15:05:46'),
(2, 'لعبة الأكواب', 'Cup Game', 'ابحث عن الجبنة تحت الأكواب بعد تبديلها.', 'Find the cheese under the cups after they shuffle.', 'cups', 'uploads/games/thumbs/6a2179f1d4c915.78164837.png', '../games/game2/index.php', 1, 2, '{\"cups\":3,\"swaps\":5,\"swap_speed\":1000}', '2026-05-23 15:05:46'),
(3, 'لعبة المطاردة', 'Chase Game', 'ساعد الفأر على جمع الجبن والوصول إلى الباب والابتعاد عن القط والمصيدة.', 'Help the mouse collect cheese, reach the door, and escape the cat and trap.', 'chase', 'uploads/games/thumbs/6a217a20cd0507.01892595.png', '../games/game3/index.php', 1, 3, '{\"lives\":3,\"player_speed\":7,\"cat_speed\":2}', '2026-05-23 15:05:46');

-- --------------------------------------------------------

--
-- بنية الجدول `game_progress`
--

CREATE TABLE `game_progress` (
  `id` int(11) NOT NULL,
  `child_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `played_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `score` int(11) DEFAULT 0,
  `time_spent` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `game_progress`
--

INSERT INTO `game_progress` (`id`, `child_id`, `game_id`, `played_at`, `score`, `time_spent`) VALUES
(4, 3, 2, '2026-05-31 11:04:42', 0, 0),
(5, 3, 1, '2026-05-31 11:06:56', 0, 0),
(6, 3, 3, '2026-05-31 11:07:47', 0, 0),
(7, 1, 1, '2026-06-02 08:51:05', 0, 0),
(8, 3, 4, '2026-06-03 07:31:25', 0, 0),
(9, 3, 2, '2026-06-04 12:56:11', 0, 0),
(10, 3, 3, '2026-06-04 13:14:13', 0, 0),
(11, 3, 1, '2026-06-04 13:14:39', 0, 0),
(12, 3, 2, '2026-06-04 13:15:03', 0, 0),
(13, 3, 1, '2026-06-04 13:16:28', 0, 0),
(14, 3, 2, '2026-06-04 13:16:58', 0, 0),
(15, 3, 4, '2026-06-04 13:18:48', 0, 0),
(16, 3, 5, '2026-06-04 13:19:04', 0, 0),
(17, 3, 5, '2026-06-04 13:20:00', 0, 0),
(18, 3, 1, '2026-06-04 13:21:53', 0, 0),
(19, 3, 2, '2026-06-04 14:06:20', 0, 0),
(20, 3, 1, '2026-06-04 14:06:23', 0, 0),
(21, 3, 2, '2026-06-04 14:06:44', 0, 0),
(22, 3, 1, '2026-06-04 14:13:35', 0, 0),
(23, 3, 3, '2026-06-04 14:15:33', 0, 0),
(24, 3, 2, '2026-06-04 14:16:30', 0, 0),
(25, 3, 1, '2026-06-04 14:16:33', 0, 0),
(26, 3, 1, '2026-06-04 14:21:17', 0, 0),
(27, 3, 5, '2026-06-04 16:02:35', 0, 0),
(28, 3, 8, '2026-06-04 16:16:26', 0, 0),
(29, 3, 1, '2026-06-04 16:20:33', 0, 0),
(30, 3, 8, '2026-06-04 16:23:13', 0, 0),
(31, 3, 1, '2026-06-04 16:23:37', 0, 0),
(32, 3, 8, '2026-06-04 16:24:52', 0, 0),
(33, 3, 8, '2026-06-04 16:29:30', 0, 0),
(34, 3, 5, '2026-06-04 16:33:45', 0, 0),
(35, 3, 9, '2026-06-04 16:36:05', 0, 0),
(36, 3, 10, '2026-06-04 16:41:43', 0, 0),
(37, 3, 10, '2026-06-04 16:45:52', 0, 0),
(38, 3, 10, '2026-06-04 16:46:38', 0, 0),
(39, 3, 10, '2026-06-04 16:46:52', 0, 0),
(40, 3, 10, '2026-06-04 16:47:06', 0, 0),
(41, 3, 5, '2026-06-04 16:47:20', 0, 0),
(42, 3, 11, '2026-06-04 16:47:54', 0, 0),
(43, 3, 12, '2026-06-04 16:50:39', 0, 0),
(44, 3, 12, '2026-06-04 16:57:45', 0, 0),
(45, 3, 4, '2026-06-04 17:03:15', 0, 0),
(46, 3, 12, '2026-06-04 17:04:32', 0, 0),
(47, 3, 12, '2026-06-04 17:04:50', 0, 0),
(48, 3, 5, '2026-06-04 17:05:11', 0, 0),
(49, 3, 12, '2026-06-04 17:05:14', 0, 0),
(50, 3, 5, '2026-06-04 17:08:33', 0, 0),
(51, 3, 12, '2026-06-04 17:08:48', 0, 0),
(52, 3, 3, '2026-06-04 17:08:52', 0, 0),
(53, 3, 5, '2026-06-04 17:09:32', 0, 0),
(54, 3, 12, '2026-06-04 17:11:45', 0, 0),
(55, 3, 5, '2026-06-04 17:11:52', 0, 0),
(56, 3, 4, '2026-06-04 17:14:14', 0, 0),
(57, 3, 12, '2026-06-04 22:15:16', 0, 0),
(58, 3, 1, '2026-06-07 22:12:44', 0, 0),
(59, 3, 12, '2026-06-07 22:12:56', 0, 0),
(60, 3, 1, '2026-06-08 09:58:02', 0, 0),
(61, 3, 2, '2026-06-08 09:58:21', 0, 0),
(62, 3, 3, '2026-06-08 09:58:38', 0, 0),
(63, 3, 2, '2026-06-08 10:11:09', 0, 0),
(69, 3, 13, '2026-06-08 13:31:44', 0, 0),
(70, 3, 12, '2026-06-08 13:31:51', 0, 0),
(71, 3, 1, '2026-06-09 05:38:18', 0, 0),
(72, 3, 2, '2026-06-09 05:38:49', 0, 0),
(73, 3, 3, '2026-06-09 05:39:10', 0, 0),
(74, 3, 1, '2026-07-26 18:29:04', 0, 0),
(75, 3, 1, '2026-09-14 20:42:27', 0, 0),
(76, 3, 2, '2026-09-14 20:43:31', 0, 0),
(77, 3, 3, '2026-09-14 20:44:10', 0, 0),
(78, 3, 1, '2026-09-19 12:45:17', 0, 0),
(79, 3, 1, '2026-09-19 12:45:25', 0, 0),
(80, 3, 2, '2026-09-19 14:52:07', 0, 0),
(81, 3, 3, '2026-09-19 14:53:10', 0, 0),
(82, 3, 1, '2026-09-19 14:55:05', 0, 0);

-- --------------------------------------------------------

--
-- بنية الجدول `general_sections`
--

CREATE TABLE `general_sections` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `icon` varchar(20) DEFAULT '★',
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `section_video` varchar(255) DEFAULT NULL,
  `icon_file` varchar(255) DEFAULT NULL,
  `sign_video` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `intro_title` varchar(255) DEFAULT NULL,
  `intro_video` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `general_section_cards`
--

CREATE TABLE `general_section_cards` (
  `id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `display_name` varchar(255) NOT NULL,
  `description_text` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `icon_file` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `sign_video` varchar(255) DEFAULT NULL,
  `created_by_supervisor` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `general_section_progress`
--

CREATE TABLE `general_section_progress` (
  `id` int(11) NOT NULL,
  `child_id` int(11) NOT NULL,
  `section_name` varchar(100) NOT NULL,
  `visited_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `general_section_progress`
--

INSERT INTO `general_section_progress` (`id`, `child_id`, `section_name`, `visited_at`) VALUES
(13, 3, 'أيام الأسبوع', '2026-05-31 02:29:42'),
(14, 3, 'الحيوانات', '2026-05-31 13:52:41'),
(15, 3, 'الفصول الأربعة', '2026-05-31 13:54:46'),
(16, 3, 'أيام الأسبوع', '2026-05-31 13:54:58'),
(17, 3, 'الحيوانات', '2026-05-31 15:44:51'),
(18, 3, 'أركان الإسلام', '2026-06-01 12:22:24'),
(19, 3, 'الطقس', '2026-06-01 12:55:27'),
(20, 3, 'أركان الإسلام', '2026-06-01 14:28:08'),
(21, 3, 'الطقس', '2026-06-02 18:04:19'),
(22, 3, 'أركان الإسلام', '2026-06-02 18:16:40');

-- --------------------------------------------------------

--
-- بنية الجدول `help_clicks`
--

CREATE TABLE `help_clicks` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `child_name` varchar(255) DEFAULT NULL,
  `page_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `help_clicks`
--

INSERT INTO `help_clicks` (`id`, `user_id`, `child_name`, `page_url`, `created_at`) VALUES
(1, 3, 'MASA', '/kids/auth/children.php', '2026-06-03 04:42:36'),
(2, 3, 'MASA', '/kids/auth/children.php', '2026-06-03 04:42:40'),
(3, 3, 'MASA', '/kids/subjects/coloring.php', '2026-06-03 07:07:59'),
(4, 3, 'MASA', '/kids/subjects/math/numbers-game.php', '2026-06-03 07:10:01'),
(5, 3, 'MASA', '/kids/subjects/general/general.php', '2026-06-03 07:16:08'),
(6, 3, 'MASA', '/kids/subjects/general/general.php', '2026-06-03 07:27:06'),
(7, 3, 'MASA', '/kids/subjects/english/english.php', '2026-06-04 12:10:51'),
(8, 3, 'MASA', '/kids/subjects/english/english-letters.php', '2026-06-04 12:10:56'),
(9, 3, 'MASA', '/kids/subjects/english/english-numbers.php', '2026-06-04 12:12:44'),
(10, 3, 'MASA', '/kids/subjects/general/general.php', '2026-06-04 12:13:54'),
(11, 3, 'MASA', '/kids/auth/children.php', '2026-06-04 12:56:20'),
(12, 3, 'MASA', '/kids/auth/register.php', '2026-06-04 12:56:35'),
(13, 3, 'MASA', '/kids/subjects/english/english.php', '2026-06-04 13:10:13'),
(14, 3, 'MASA', '/kids/subjects/english/english-letters.php', '2026-06-04 13:10:31'),
(15, 3, 'MASA', '/kids/subjects/coloring.php', '2026-06-04 13:11:01'),
(16, 3, 'MASA', '/kids/subjects/english/english-numbers.php', '2026-06-04 13:11:20'),
(17, 3, 'MASA', '/kids/subjects/english/english-numbers.php', '2026-06-04 13:11:48'),
(18, 3, 'MASA', '/kids/auth/children.php', '2026-06-04 13:19:29'),
(19, 3, 'MASA', '/kids/subjects/general/child-chat.php', '2026-06-04 13:20:35'),
(20, 3, 'MASA', '/kids/subjects/general/general.php', '2026-06-04 13:21:11'),
(21, 3, 'MASA', '/kids/subjects/general/general.php', '2026-06-04 13:21:18'),
(22, 3, 'MASA', '/kids/subjects/general/food.php', '2026-06-04 13:39:04'),
(23, 3, 'MASA', '/kids/subjects/arabic/arabic.php', '2026-06-04 13:46:19'),
(24, 3, 'MASA', '/kids/subjects/arabic/arabic.php', '2026-06-04 13:48:09'),
(25, 3, 'MASA', '/kids/subjects/english/english.php', '2026-06-04 13:54:43'),
(26, 3, 'MASA', '/kids/subjects/english/english.php', '2026-06-04 13:54:50'),
(27, 3, 'MASA', '/kids/subjects/english/english-numbers.php', '2026-06-04 13:55:36'),
(28, 3, 'MASA', '/kids/subjects/math/numbers-game.php', '2026-06-04 14:09:57'),
(29, 3, 'MASA', '/kids/auth/children.php', '2026-06-04 15:23:40'),
(30, 3, 'MASA', '/kids/subjects/general/general.php', '2026-06-04 15:39:59'),
(31, 3, 'MASA', '/kids/index.php', '2026-06-07 21:53:40'),
(32, 3, 'MASA', '/kids/index.php', '2026-06-07 21:55:06'),
(33, 3, 'MASA', '/kids/index.php', '2026-06-07 21:55:55'),
(34, 3, 'MASA', '/kids/index.php', '2026-06-07 21:56:36'),
(35, 3, 'MASA', '/kids/index.php', '2026-06-07 21:58:29'),
(36, 3, 'MASA', '/kids/index.php', '2026-06-07 21:59:16'),
(37, 3, 'MASA', '/kids/index.php', '2026-06-07 21:59:24'),
(38, 3, 'MASA', '/kids/index.php', '2026-06-07 22:00:32'),
(39, 3, 'MASA', '/kids/index.php', '2026-06-07 22:02:14'),
(40, 3, 'MASA', '/kids/index.php', '2026-06-07 22:03:38'),
(41, 3, 'MASA', '/kids/index.php', '2026-06-07 22:05:36'),
(42, 3, 'MASA', '/kids/auth/children.php', '2026-06-08 09:44:27'),
(43, 3, 'MASA', '/kids/auth/children.php', '2026-06-08 09:44:36'),
(44, 3, 'MASA', '/kids/auth/children.php', '2026-06-08 09:45:05'),
(45, 3, 'MASA', '/kids/subjects/coloring.php', '2026-06-08 09:54:37'),
(46, 3, 'MASA', '/kids/subjects/general/general.php', '2026-06-08 10:07:53'),
(47, 3, 'MASA', '/kids/index.php', '2026-06-08 10:08:01'),
(48, 3, 'MASA', '/kids/subjects/general/general.php', '2026-06-08 10:09:44'),
(49, 3, 'MASA', '/kids/subjects/general/general.php', '2026-06-08 10:09:51'),
(50, 3, 'MASA', '/kids/subjects/general/general.php', '2026-06-08 10:09:58'),
(51, 3, 'MASA', '/kids/subjects/general/general.php', '2026-06-08 10:10:06'),
(52, 3, 'MASA', '/kids/subjects/general/general.php', '2026-06-08 10:10:12'),
(53, 3, 'MASA', '/kids/auth/children.php', '2026-06-08 10:10:33'),
(54, 3, 'MASA', '/kids/subjects/arabic/arabic.php', '2026-06-08 10:11:42'),
(55, 3, 'MASA', '/kids/subjects/arabic/arabic.php', '2026-06-08 10:12:03'),
(56, 3, 'MASA', '/kids/subjects/arabic/arabic.php', '2026-06-08 10:12:51'),
(57, 3, 'MASA', '/kids/subjects/arabic/arabic.php', '2026-06-08 10:15:26'),
(58, 3, 'MASA', '/kids/subjects/arabic/arabic.php', '2026-06-08 10:20:16'),
(59, 3, 'MASA', '/kids/subjects/arabic/arabic.php', '2026-06-08 10:20:25'),
(60, 3, 'MASA', '/kids/subjects/english/english.php', '2026-06-08 10:20:38'),
(61, 3, 'MASA', '/kids/subjects/english/english.php', '2026-06-08 10:21:35'),
(62, 3, 'MASA', '/kids/subjects/english/english.php', '2026-06-08 10:21:42'),
(63, 3, 'MASA', '/kids/subjects/english/english-letters.php', '2026-06-08 10:22:31'),
(64, 3, 'MASA', '/kids/subjects/english/english-letters.php', '2026-06-08 10:22:35'),
(65, 3, 'MASA', '/kids/subjects/english/english-letters.php', '2026-06-08 10:22:40'),
(66, 3, 'MASA', '/kids/subjects/english/english-numbers.php', '2026-06-08 10:23:56'),
(67, 3, 'MASA', '/kids/subjects/english/english-numbers.php', '2026-06-08 10:23:59'),
(68, 3, 'MASA', '/kids/subjects/general/general.php', '2026-06-08 10:24:11'),
(69, 3, 'MASA', '/kids/subjects/general/general.php', '2026-06-08 10:26:50'),
(70, 3, 'MASA', '/kids/subjects/general/general.php', '2026-06-08 10:26:52'),
(71, 3, 'MASA', '/kids/subjects/general/food.php', '2026-06-08 10:34:35'),
(72, 3, 'MASA', '/kids/subjects/general/food.php', '2026-06-08 10:35:31'),
(73, 3, 'MASA', '/kids/subjects/general/food.php', '2026-06-08 10:35:47'),
(74, 3, 'MASA', '/kids/subjects/general/food.php', '2026-06-08 10:36:11'),
(75, 3, 'MASA', '/kids/subjects/general/food.php', '2026-06-08 10:38:19'),
(76, 3, 'MASA', '/kids/subjects/general/animals.php', '2026-06-08 10:38:53'),
(77, 3, 'MASA', '/kids/subjects/general/animals.php', '2026-06-08 10:40:01'),
(78, 3, 'MASA', '/kids/subjects/general/animals.php', '2026-06-08 10:41:00'),
(79, 3, 'MASA', '/kids/subjects/general/stories.php', '2026-06-08 10:50:49'),
(80, 3, 'MASA', '/kids/subjects/general/stories.php', '2026-06-08 10:53:32'),
(81, 3, 'MASA', '/kids/subjects/general/stories.php', '2026-06-08 10:57:04'),
(82, 3, 'MASA', '/kids/subjects/general/stories.php', '2026-06-08 10:58:01'),
(83, 3, 'MASA', '/kids/subjects/general/general.php', '2026-06-08 11:09:03'),
(84, 3, 'MASA', '/kids/subjects/general/stories.php', '2026-06-08 11:09:07'),
(85, 3, 'MASA', '/kids/subjects/coloring.php', '2026-06-08 11:09:21'),
(86, 8, 'ali', '/kids/auth/children.php', '2026-06-08 12:20:53'),
(87, 9, 'ola', '/kids/subjects/coloring.php', '2026-06-08 12:42:41'),
(88, 9, 'ola', '/kids/subjects/coloring.php', '2026-06-08 12:43:17'),
(89, 9, 'ola', '/kids/subjects/english/english.php', '2026-06-08 12:47:39'),
(90, 9, 'ola', '/kids/subjects/english/english.php', '2026-06-08 12:48:07'),
(91, 9, 'ola', '/kids/subjects/math/numbers-game.php', '2026-06-08 12:54:16'),
(92, 9, 'ola', '/kids/subjects/general/food.php', '2026-06-08 13:02:01'),
(93, 9, 'ola', '/kids/subjects/general/animals.php', '2026-06-08 13:05:26'),
(94, 10, 'osama', '/kids/auth/children.php', '2026-06-08 14:46:21'),
(95, 10, 'osama', '/kids/subjects/general/general.php', '2026-06-08 14:47:31'),
(96, 10, 'osama', '/kids/subjects/general/animals.php', '2026-06-08 14:51:55'),
(97, 3, 'MASA', '/kids/index.php', '2026-06-08 23:22:42'),
(98, 3, 'MASA', '/kids/subjects/coloring.php', '2026-06-09 05:22:26'),
(99, 3, 'MASA', '/kids/subjects/english/english.php', '2026-06-09 05:24:41'),
(100, 3, 'MASA', '/kids/subjects/general/general.php', '2026-06-09 05:30:53'),
(101, 3, 'MASA', '/kids/subjects/general/food.php', '2026-06-09 05:33:45'),
(102, 3, 'MASA', '/kids/subjects/general/animals.php', '2026-06-09 05:35:42'),
(103, 3, 'MASA', '/kids/subjects/coloring.php', '2026-06-09 07:09:31'),
(104, 3, 'MASA', '/kids/auth/children.php', '2026-07-26 18:19:11'),
(105, 3, 'MASA', '/kids/auth/children.php', '2026-07-26 18:20:12'),
(106, 3, 'MASA', '/kids/auth/account_settings.php', '2026-07-26 18:21:07'),
(107, 3, 'MASA', '/kids/auth/children.php', '2026-07-26 18:24:42'),
(108, 3, 'MASA', '/kids/subjects/arabic/arabic.php', '2026-07-26 18:31:01'),
(109, 3, 'MASA', '/kids/subjects/arabic/arabic.php', '2026-07-26 18:32:07'),
(110, 3, 'MASA', '/kids/subjects/coloring.php', '2026-07-26 18:32:48'),
(111, 3, 'MASA', '/kids/subjects/english/english.php', '2026-07-26 18:42:11'),
(112, 3, 'MASA', '/kids/subjects/english/english.php', '2026-07-26 18:43:39'),
(113, 3, 'MASA', '/kids/subjects/english/english.php', '2026-07-26 18:43:41'),
(114, 3, 'MASA', '/kids/subjects/english/english-letters.php', '2026-07-26 18:43:51'),
(115, 3, 'MASA', '/kids/subjects/english/english-numbers.php', '2026-07-26 18:44:10'),
(116, 3, 'MASA', '/kids/subjects/english/english-numbers.php', '2026-07-26 18:44:26'),
(117, 3, 'MASA', '/kids/subjects/general/general.php', '2026-07-26 18:44:41'),
(118, 3, 'MASA', '/kids/subjects/general/general.php', '2026-07-26 18:45:50'),
(119, 3, 'MASA', '/kids/subjects/general/general.php', '2026-07-26 19:13:48'),
(120, 3, 'MASA', '/kids/subjects/general/food.php', '2026-07-26 19:14:42'),
(121, 3, 'MASA', '/kids/subjects/general/animals.php', '2026-07-26 19:17:41'),
(122, 3, 'MASA', '/kids/subjects/general/general.php', '2026-09-14 15:48:05'),
(123, 3, 'MASA', '/kids/subjects/general/general.php', '2026-09-14 15:52:25'),
(124, 3, 'MASA', '/kids/subjects/general/stories.php', '2026-09-14 15:52:42'),
(125, 3, 'MASA', '/kids/subjects/general/general.php', '2026-09-14 15:56:38'),
(126, 3, 'MASA', '/kids/subjects/general/general.php', '2026-09-14 15:58:27'),
(127, 3, 'MASA', '/kids/subjects/general/general.php', '2026-09-14 16:05:22'),
(128, 3, 'MASA', '/kids/subjects/general/general.php', '2026-09-14 16:25:18'),
(129, 3, 'MASA', '/kids/subjects/general/general.php', '2026-09-14 16:27:59'),
(130, 3, 'MASA', '/kids/subjects/general/general.php', '2026-09-14 16:29:41'),
(131, 3, 'MASA', '/kids/subjects/general/general.php', '2026-09-14 16:30:21'),
(132, 3, 'MASA', '/kids/subjects/general/general.php', '2026-09-14 16:30:35'),
(133, 3, 'MASA', '/kids/subjects/general/general.php', '2026-09-14 16:30:48'),
(134, 3, 'MASA', '/kids/subjects/general/general.php', '2026-09-14 16:30:54'),
(135, 3, 'MASA', '/kids/subjects/general/general.php', '2026-09-14 16:43:17'),
(136, 3, 'MASA', '/kids/subjects/general/general.php', '2026-09-14 16:43:20'),
(137, 3, 'MASA', '/kids/subjects/general/general.php', '2026-09-14 16:46:08'),
(138, 3, 'MASA', '/kids/subjects/general/general.php', '2026-09-14 16:46:50'),
(139, 3, 'MASA', '/kids/subjects/general/general.php', '2026-09-14 16:49:51'),
(140, 3, 'MASA', '/kids/subjects/general/general.php', '2026-09-14 16:53:43'),
(141, 3, 'MASA', '/kids/subjects/general/general.php', '2026-09-14 16:54:28'),
(142, 3, 'MASA', '/kids/subjects/general/general.php', '2026-09-14 16:56:05'),
(143, 3, 'MASA', '/kids/subjects/general/general.php', '2026-09-14 16:58:26'),
(144, 3, 'MASA', '/kids/subjects/general/general.php', '2026-09-14 16:58:33'),
(145, 3, 'MASA', '/kids/subjects/general/general.php', '2026-09-14 16:59:13'),
(146, 3, 'MASA', '/kids/subjects/general/general.php', '2026-09-14 17:00:23'),
(147, 3, 'MASA', '/kids/subjects/general/general.php', '2026-09-14 17:04:09'),
(148, 3, 'MASA', '/kids/subjects/general/general.php', '2026-09-14 17:05:03'),
(149, 3, 'MASA', '/kids/subjects/general/general.php', '2026-09-14 17:05:10'),
(150, 3, 'MASA', '/kids/subjects/general/general.php', '2026-09-14 17:07:25'),
(151, 3, 'MASA', '/kids/subjects/general/food.php', '2026-09-14 17:25:34'),
(152, 3, 'MASA', '/kids/subjects/general/food.php', '2026-09-14 17:25:39'),
(153, 3, 'MASA', '/kids/subjects/general/food.php', '2026-09-14 17:30:39'),
(154, 3, 'MASA', '/kids/subjects/general/food.php', '2026-09-14 17:33:36'),
(155, 3, 'MASA', '/kids/subjects/general/food.php', '2026-09-14 17:34:13'),
(156, 3, 'MASA', '/kids/subjects/general/food.php', '2026-09-14 17:34:18'),
(157, 3, 'MASA', '/kids/subjects/general/animals.php', '2026-09-14 17:39:54'),
(158, 3, 'MASA', '/kids/subjects/general/animals.php', '2026-09-14 17:41:34'),
(159, 3, 'MASA', '/kids/subjects/general/stories.php', '2026-09-14 17:42:34'),
(160, 3, 'MASA', '/kids/subjects/general/stories.php', '2026-09-14 17:47:17'),
(161, 11, 'rahaf', '/kids/auth/register.php', '2026-09-14 20:02:26'),
(162, 11, 'rahaf', '/kids/auth/register.php', '2026-09-14 20:05:08'),
(163, 3, 'MASA', '/kids/auth/children.php', '2026-09-14 20:23:54'),
(164, 3, 'MASA', '/kids/subjects/general/animals.php', '2026-09-14 20:32:55'),
(165, 3, 'MASA', '/kids/auth/account_settings.php', '2026-09-14 20:34:52'),
(166, 3, 'MASA', '/kids/auth/children.php', '2026-09-14 20:42:04'),
(167, 3, 'MASA', '/kids/subjects/arabic/arabic.php', '2026-09-14 20:45:19'),
(168, 3, 'MASA', '/kids/subjects/coloring.php', '2026-09-14 20:45:59'),
(169, 3, 'MASA', '/kids/subjects/math/numbers-game.php', '2026-09-14 20:48:49'),
(170, 3, 'MASA', '/kids/index.php', '2026-09-19 11:33:09'),
(171, 3, 'MASA', '/kids/auth/children.php', '2026-09-19 11:45:52'),
(172, 3, 'MASA', '/kids/auth/children.php', '2026-09-19 11:46:18'),
(173, 3, 'MASA', '/kids/auth/account_settings.php', '2026-09-19 11:47:23'),
(174, 3, 'MASA', '/kids/auth/children.php', '2026-09-19 11:47:30'),
(175, 3, 'MASA', '/kids/auth/account_settings.php', '2026-09-19 11:50:08'),
(176, 3, 'MASA', '/kids/auth/account_settings.php', '2026-09-19 11:53:47'),
(177, 3, 'MASA', '/kids/auth/account_settings.php', '2026-09-19 11:56:38'),
(178, 3, 'MASA', '/kids/auth/account_settings.php', '2026-09-19 11:56:43'),
(179, 3, 'MASA', '/kids/auth/children.php', '2026-09-19 11:58:25'),
(180, 3, 'MASA', '/kids/auth/account_settings.php', '2026-09-19 12:02:21'),
(181, 3, 'MASA', '/kids/auth/account_settings.php', '2026-09-19 12:03:27'),
(182, 3, 'MASA', '/kids/auth/account_settings.php', '2026-09-19 12:04:33'),
(183, 3, 'MASA', '/kids/auth/account_settings.php', '2026-09-19 12:06:29'),
(184, 3, 'MASA', '/kids/auth/account_settings.php', '2026-09-19 12:08:22'),
(185, 3, 'MASA', '/kids/auth/children.php', '2026-09-19 12:08:33'),
(186, 3, 'MASA', '/kids/auth/children.php', '2026-09-19 12:09:58'),
(187, 3, 'MASA', '/kids/auth/children.php', '2026-09-19 12:16:54'),
(188, 3, 'MASA', '/kids/subjects/arabic/arabic.php', '2026-09-19 12:17:31'),
(189, 3, 'MASA', '/kids/subjects/coloring.php', '2026-09-19 12:20:44'),
(190, 3, 'MASA', '/kids/subjects/coloring.php', '2026-09-19 12:29:22'),
(191, 3, 'MASA', '/kids/subjects/coloring.php', '2026-09-19 12:31:01'),
(192, 3, 'MASA', '/kids/subjects/english/english.php', '2026-09-19 12:44:44'),
(193, 3, 'MASA', '/kids/subjects/general/general.php', '2026-09-19 12:44:57'),
(194, 3, 'MASA', '/kids/subjects/english/english.php', '2026-09-19 12:59:41'),
(195, 3, 'MASA', '/kids/subjects/english/english.php', '2026-09-19 13:01:53'),
(196, 3, 'MASA', '/kids/subjects/english/english.php', '2026-09-19 13:02:15'),
(197, 3, 'MASA', '/kids/subjects/english/english.php', '2026-09-19 13:02:22'),
(198, 3, 'MASA', '/kids/subjects/english/english.php', '2026-09-19 13:10:13'),
(199, 3, 'MASA', '/kids/subjects/english/english-letters.php', '2026-09-19 13:10:22'),
(200, 3, 'MASA', '/kids/subjects/general/general.php', '2026-09-19 13:10:50'),
(201, 3, 'MASA', '/kids/subjects/general/stories.php', '2026-09-19 13:15:58'),
(202, 3, 'MASA', '/kids/subjects/general/food.php', '2026-09-19 13:33:57'),
(203, 3, 'MASA', '/kids/subjects/general/animals.php', '2026-09-19 13:35:21'),
(204, 3, 'MASA', '/kids/subjects/general/stories.php', '2026-09-19 14:01:00'),
(205, 3, 'MASA', '/kids/subjects/general/general.php', '2026-09-19 14:02:48'),
(206, 3, 'MASA', '/kids/subjects/arabic/arabic.php', '2026-09-19 14:05:46'),
(207, 3, 'MASA', '/kids/subjects/english/english.php', '2026-09-19 14:06:49'),
(208, 3, 'MASA', '/kids/subjects/general/stories.php', '2026-09-19 14:10:23'),
(209, 3, 'MASA', '/kids/subjects/general/stories.php', '2026-09-19 14:10:29'),
(210, 3, 'MASA', '/kids/subjects/general/stories.php', '2026-09-19 14:10:45'),
(211, 3, 'MASA', '/kids/auth/children.php', '2026-09-19 14:13:45'),
(212, 3, 'MASA', '/kids/subjects/arabic/arabic.php', '2026-09-19 14:13:55'),
(213, 3, 'MASA', '/kids/subjects/arabic/arabic.php', '2026-09-19 14:16:25'),
(214, 3, 'MASA', '/kids/subjects/arabic/arabic.php', '2026-09-19 14:16:31'),
(215, 3, 'MASA', '/kids/subjects/arabic/arabic.php', '2026-09-19 14:16:38'),
(216, 3, 'MASA', '/kids/subjects/english/english.php', '2026-09-19 14:17:30'),
(217, 3, 'MASA', '/kids/subjects/english/english.php', '2026-09-19 14:18:22'),
(218, 3, 'MASA', '/kids/subjects/english/english.php', '2026-09-19 14:19:16'),
(219, 3, 'MASA', '/kids/subjects/english/english-letters.php', '2026-09-19 14:19:21'),
(220, 3, 'MASA', '/kids/subjects/english/english-letters.php', '2026-09-19 14:19:44'),
(221, 3, 'MASA', '/kids/subjects/english/english-letters.php', '2026-09-19 14:21:01'),
(222, 3, 'MASA', '/kids/subjects/english/english-letters.php', '2026-09-19 14:21:26'),
(223, 3, 'MASA', '/kids/subjects/arabic/arabic.php', '2026-09-19 14:21:35'),
(224, 3, 'MASA', '/kids/subjects/english/english.php', '2026-09-19 14:21:46'),
(225, 3, 'MASA', '/kids/subjects/english/english-numbers.php', '2026-09-19 14:21:58'),
(226, 3, 'MASA', '/kids/subjects/english/english-numbers.php', '2026-09-19 14:22:54'),
(227, 3, 'MASA', '/kids/subjects/arabic/arabic.php', '2026-09-19 14:30:06'),
(228, 3, 'MASA', '/kids/subjects/english/english.php', '2026-09-19 14:30:14'),
(229, 3, 'MASA', '/kids/subjects/english/english.php', '2026-09-19 14:30:18'),
(230, 3, 'MASA', '/kids/subjects/english/english-numbers.php', '2026-09-19 14:30:25'),
(231, 3, 'MASA', '/kids/subjects/english/english-letters.php', '2026-09-19 14:30:33'),
(232, 3, 'MASA', '/kids/subjects/english/english.php', '2026-09-19 14:30:43'),
(233, 3, 'MASA', '/kids/subjects/english/english-numbers.php', '2026-09-19 14:30:48'),
(234, 3, 'MASA', '/kids/subjects/english/english-letters.php', '2026-09-19 14:30:57'),
(235, 3, 'MASA', '/kids/subjects/general/general.php', '2026-09-19 14:31:12'),
(236, 3, 'MASA', '/kids/subjects/math/numbers-game.php', '2026-09-19 14:34:46'),
(237, 3, 'MASA', '/kids/auth/children.php', '2026-09-19 14:36:17'),
(238, 3, 'MASA', '/kids/auth/children.php', '2026-09-19 14:39:24'),
(239, 3, 'MASA', '/kids/subjects/math/numbers-game.php', '2026-09-19 14:43:05'),
(240, 3, 'MASA', '/kids/subjects/general/stories.php', '2026-09-19 14:45:44'),
(241, 3, 'MASA', '/kids/subjects/general/general.php', '2026-09-19 14:45:47'),
(242, 3, 'MASA', '/kids/auth/children.php', '2026-09-19 14:46:12'),
(243, 3, 'MASA', '/kids/subjects/general/stories.php', '2026-09-19 14:46:48'),
(244, 3, 'MASA', '/kids/subjects/math/numbers-game.php', '2026-09-19 14:49:02'),
(245, 3, 'MASA', '/kids/auth/account_settings.php', '2026-09-19 15:05:12'),
(246, 3, 'MASA', '/kids/auth/account_settings.php', '2026-09-19 15:32:58');

-- --------------------------------------------------------

--
-- بنية الجدول `lessons`
--

CREATE TABLE `lessons` (
  `id` int(11) NOT NULL,
  `letter_id` int(11) DEFAULT NULL,
  `custom_sign` varchar(255) DEFAULT NULL,
  `subject_name` varchar(100) NOT NULL,
  `lesson_type` varchar(100) NOT NULL,
  `lesson_title` varchar(255) NOT NULL,
  `example_word` varchar(255) DEFAULT NULL,
  `lesson_image` varchar(255) DEFAULT NULL,
  `card_video` varchar(255) DEFAULT NULL,
  `lesson_link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `coloring_image` varchar(255) DEFAULT NULL,
  `created_by_supervisor` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `lessons`
--

INSERT INTO `lessons` (`id`, `letter_id`, `custom_sign`, `subject_name`, `lesson_type`, `lesson_title`, `example_word`, `lesson_image`, `card_video`, `lesson_link`, `created_at`, `coloring_image`, `created_by_supervisor`) VALUES
(3, NULL, NULL, 'اللغة العربية', 'الحروف العربية', 'أ', NULL, 'images/signs/arabic/1.png', NULL, 'subjects/arabic/arabic-letter.php?id=1', '2026-05-17 14:57:44', NULL, 0),
(4, NULL, NULL, 'اللغة العربية', 'الحروف العربية', 'ب', NULL, 'images/signs/arabic/2.png', NULL, 'subjects/arabic/arabic-letter.php?id=2', '2026-05-17 14:57:44', NULL, 0),
(5, NULL, NULL, 'اللغة العربية', 'الحروف العربية', 'ت', NULL, 'images/signs/arabic/3.png', NULL, 'subjects/arabic/arabic-letter.php?id=3', '2026-05-17 14:57:44', NULL, 0),
(6, NULL, NULL, 'اللغة العربية', 'الحروف العربية', 'ث', NULL, 'images/signs/arabic/4.png', NULL, 'subjects/arabic/arabic-letter.php?id=4', '2026-05-17 14:57:44', NULL, 0),
(7, NULL, NULL, 'اللغة العربية', 'الحروف العربية', 'ج', NULL, 'images/signs/arabic/5.png', NULL, 'subjects/arabic/arabic-letter.php?id=5', '2026-05-17 14:57:44', NULL, 0),
(8, NULL, NULL, 'اللغة العربية', 'الحروف العربية', 'ح', NULL, 'images/signs/arabic/6.png', NULL, 'subjects/arabic/arabic-letter.php?id=6', '2026-05-17 14:57:44', NULL, 0),
(9, NULL, NULL, 'اللغة العربية', 'الحروف العربية', 'خ', NULL, 'images/signs/arabic/7.png', NULL, 'subjects/arabic/arabic-letter.php?id=7', '2026-05-17 14:57:44', NULL, 0),
(10, NULL, NULL, 'اللغة العربية', 'الحروف العربية', 'د', NULL, 'images/signs/arabic/8.png', NULL, 'subjects/arabic/arabic-letter.php?id=8', '2026-05-17 14:57:44', NULL, 0),
(11, NULL, NULL, 'اللغة العربية', 'الحروف العربية', 'ذ', NULL, 'images/signs/arabic/9.png', NULL, 'subjects/arabic/arabic-letter.php?id=9', '2026-05-17 14:57:44', NULL, 0),
(12, NULL, NULL, 'اللغة العربية', 'الحروف العربية', 'ر', NULL, 'images/signs/arabic/10.png', NULL, 'subjects/arabic/arabic-letter.php?id=10', '2026-05-17 14:57:44', NULL, 0),
(13, NULL, NULL, 'اللغة العربية', 'الحروف العربية', 'ز', NULL, 'images/signs/arabic/11.png', NULL, 'subjects/arabic/arabic-letter.php?id=11', '2026-05-17 14:57:44', NULL, 0),
(14, NULL, NULL, 'اللغة العربية', 'الحروف العربية', 'س', NULL, 'images/signs/arabic/12.png', NULL, 'subjects/arabic/arabic-letter.php?id=12', '2026-05-17 14:57:44', NULL, 0),
(15, NULL, NULL, 'اللغة العربية', 'الحروف العربية', 'ش', NULL, 'images/signs/arabic/13.png', NULL, 'subjects/arabic/arabic-letter.php?id=13', '2026-05-17 14:57:44', NULL, 0),
(16, NULL, NULL, 'اللغة العربية', 'الحروف العربية', 'ص', NULL, 'images/signs/arabic/14.png', NULL, 'subjects/arabic/arabic-letter.php?id=14', '2026-05-17 14:57:44', NULL, 0),
(17, NULL, NULL, 'اللغة العربية', 'الحروف العربية', 'ض', NULL, 'images/signs/arabic/15.png', NULL, 'subjects/arabic/arabic-letter.php?id=15', '2026-05-17 14:57:44', NULL, 0),
(18, NULL, NULL, 'اللغة العربية', 'الحروف العربية', 'ط', NULL, 'images/signs/arabic/16.png', NULL, 'subjects/arabic/arabic-letter.php?id=16', '2026-05-17 14:57:44', NULL, 0),
(19, NULL, NULL, 'اللغة العربية', 'الحروف العربية', 'ظ', NULL, 'images/signs/arabic/17.png', NULL, 'subjects/arabic/arabic-letter.php?id=17', '2026-05-17 14:57:44', NULL, 0),
(20, NULL, NULL, 'اللغة العربية', 'الحروف العربية', 'ع', NULL, 'images/signs/arabic/18.png', NULL, 'subjects/arabic/arabic-letter.php?id=18', '2026-05-17 14:57:44', NULL, 0),
(21, NULL, NULL, 'اللغة العربية', 'الحروف العربية', 'غ', NULL, 'images/signs/arabic/19.png', NULL, 'subjects/arabic/arabic-letter.php?id=19', '2026-05-17 14:57:44', NULL, 0),
(22, NULL, NULL, 'اللغة العربية', 'الحروف العربية', 'ف', NULL, 'images/signs/arabic/20.png', NULL, 'subjects/arabic/arabic-letter.php?id=20', '2026-05-17 14:57:44', NULL, 0),
(23, NULL, NULL, 'اللغة العربية', 'الحروف العربية', 'ق', NULL, 'images/signs/arabic/21.png', NULL, 'subjects/arabic/arabic-letter.php?id=21', '2026-05-17 14:57:44', NULL, 0),
(24, NULL, NULL, 'اللغة العربية', 'الحروف العربية', 'ك', NULL, 'images/signs/arabic/22.png', NULL, 'subjects/arabic/arabic-letter.php?id=22', '2026-05-17 14:57:44', NULL, 0),
(25, NULL, NULL, 'اللغة العربية', 'الحروف العربية', 'ل', NULL, 'images/signs/arabic/23.png', NULL, 'subjects/arabic/arabic-letter.php?id=23', '2026-05-17 14:57:44', NULL, 0),
(26, NULL, NULL, 'اللغة العربية', 'الحروف العربية', 'م', NULL, 'images/signs/arabic/24.png', NULL, 'subjects/arabic/arabic-letter.php?id=24', '2026-05-17 14:57:44', NULL, 0),
(27, NULL, NULL, 'اللغة العربية', 'الحروف العربية', 'ن', NULL, 'images/signs/arabic/25.png', NULL, 'subjects/arabic/arabic-letter.php?id=25', '2026-05-17 14:57:44', NULL, 0),
(28, NULL, NULL, 'اللغة العربية', 'الحروف العربية', 'ه', NULL, 'images/signs/arabic/26.png', NULL, 'subjects/arabic/arabic-letter.php?id=26', '2026-05-17 14:57:44', NULL, 0),
(29, NULL, NULL, 'اللغة العربية', 'الحروف العربية', 'و', NULL, 'images/signs/arabic/27.png', NULL, 'subjects/arabic/arabic-letter.php?id=27', '2026-05-17 14:57:44', NULL, 0),
(30, NULL, NULL, 'اللغة العربية', 'الحروف العربية', 'ي', NULL, 'images/signs/arabic/28.png', NULL, 'subjects/arabic/arabic-letter.php?id=28', '2026-05-17 14:57:44', NULL, 0),
(31, NULL, NULL, 'اللغة العربية', 'الحروف العربية', 'ى', NULL, 'uploads/images/1779029927_9949_31.png', '', '', '2026-05-17 14:58:47', NULL, 0),
(32, 1, '', 'اللغة الإنجليزية', 'الحروف الإنجليزية', 'A', 'Apple', 'images/letters/english/1.png', 'uploads/videos/1779150610_5783_apple.mp4', 'subjects/english/english-letter.php?id=1', '2026-05-17 14:59:15', 'uploads/images/1779151762_6330_a.png', 0),
(33, 2, '', 'اللغة الإنجليزية', 'الحروف الإنجليزية', 'B', 'Bear', 'uploads/images/1779148461_1330_2.png', 'uploads/videos/1779150633_7000_bear.mp4', 'subjects/english/english-letter.php?id=2', '2026-05-17 14:59:15', 'uploads/images/1779151753_3023_b.png', 0),
(34, 3, '', 'اللغة الإنجليزية', 'الحروف الإنجليزية', 'C', 'Cat', 'uploads/images/1779149928_2386_3.png', 'uploads/videos/1779150655_9211_cat.mp4', 'subjects/english/english-letter.php?id=3', '2026-05-17 14:59:15', 'uploads/images/1779151743_6589_c.png', 0),
(35, 4, '', 'اللغة الإنجليزية', 'الحروف الإنجليزية', 'D', 'Dog', 'images/letters/english/4.png', 'uploads/videos/1779150679_8081_dog.mp4', 'subjects/english/english-letter.php?id=4', '2026-05-17 14:59:15', 'uploads/images/1779151733_1574_d.png', 0),
(36, 5, '', 'اللغة الإنجليزية', 'الحروف الإنجليزية', 'E', 'Egg', 'images/letters/english/5.png', 'uploads/videos/1779150715_5213_egg.mp4', 'subjects/english/english-letter.php?id=5', '2026-05-17 14:59:15', 'uploads/images/1779151720_1574_e.png', 0),
(37, 6, '', 'اللغة الإنجليزية', 'الحروف الإنجليزية', 'F', 'Fish', 'images/letters/english/6.png', '', 'subjects/english/english-letter.php?id=6', '2026-05-17 14:59:15', 'uploads/images/1779151705_1712_f.png', 0),
(38, 7, '', 'اللغة الإنجليزية', 'الحروف الإنجليزية', 'G', 'Girl', 'images/letters/english/7.png', 'uploads/videos/1779150782_8060_girl.mp4', 'subjects/english/english-letter.php?id=7', '2026-05-17 14:59:15', 'uploads/images/1779151691_5056_g.png', 0),
(39, 8, '', 'اللغة الإنجليزية', 'الحروف الإنجليزية', 'H', 'Hat', 'images/letters/english/8.png', 'uploads/videos/1779150808_6964_hat.mp4', 'subjects/english/english-letter.php?id=8', '2026-05-17 14:59:15', 'uploads/images/1779151678_9064_h.png', 0),
(40, 9, '', 'اللغة الإنجليزية', 'الحروف الإنجليزية', 'I', 'Ice', 'images/letters/english/9.png', 'uploads/videos/1779150832_4020_ice.mp4', 'subjects/english/english-letter.php?id=9', '2026-05-17 14:59:15', 'uploads/images/1779151660_7727_i.png', 0),
(41, 10, '', 'اللغة الإنجليزية', 'الحروف الإنجليزية', 'J', 'Jug', 'images/letters/english/10.png', 'uploads/videos/1779150854_6359_jug.mp4', 'subjects/english/english-letter.php?id=10', '2026-05-17 14:59:15', 'uploads/images/1779151648_5541_j.png', 0),
(42, 11, '', 'اللغة الإنجليزية', 'الحروف الإنجليزية', 'K', 'Kite', 'images/letters/english/11.png', 'uploads/videos/1779150875_2401_kite.mp4', 'subjects/english/english-letter.php?id=11', '2026-05-17 14:59:15', 'uploads/images/1779151635_9138_k.png', 0),
(43, 12, '', 'اللغة الإنجليزية', 'الحروف الإنجليزية', 'L', 'Lion', 'images/letters/english/12.png', 'uploads/videos/1779150905_9735_lion.mp4', 'subjects/english/english-letter.php?id=12', '2026-05-17 14:59:15', 'uploads/images/1779151615_8397_l.png', 0),
(44, 13, '', 'اللغة الإنجليزية', 'الحروف الإنجليزية', 'M', 'Monkey', 'images/letters/english/13.png', 'uploads/videos/1779150945_4565_monkey.mp4', 'subjects/english/english-letter.php?id=13', '2026-05-17 14:59:15', 'uploads/images/1779151589_6000_m.png', 0),
(45, 14, '', 'اللغة الإنجليزية', 'الحروف الإنجليزية', 'N', 'Nest', 'images/letters/english/14.png', 'uploads/videos/1779150970_5546_nest.mp4', 'subjects/english/english-letter.php?id=14', '2026-05-17 14:59:15', 'uploads/images/1779151568_6207_n.png', 0),
(46, 15, '', 'اللغة الإنجليزية', 'الحروف الإنجليزية', 'O', 'Orange', 'images/letters/english/15.png', 'uploads/videos/1779150992_7310_orange.mp4', 'subjects/english/english-letter.php?id=15', '2026-05-17 14:59:15', 'uploads/images/1779151551_8108_o.png', 0),
(47, 16, '', 'اللغة الإنجليزية', 'الحروف الإنجليزية', 'P', 'Pen', 'images/letters/english/16.png', 'uploads/videos/1779151016_3752_pen.mp4', 'subjects/english/english-letter.php?id=16', '2026-05-17 14:59:15', 'uploads/images/1779151531_6286_p.png', 0),
(48, 17, '', 'اللغة الإنجليزية', 'الحروف الإنجليزية', 'Q', 'Queen', 'images/letters/english/17.png', 'uploads/videos/1779151043_5356_queen.mp4', 'subjects/english/english-letter.php?id=17', '2026-05-17 14:59:15', 'uploads/images/1779151510_9836_q.png', 0),
(49, 18, '', 'اللغة الإنجليزية', 'الحروف الإنجليزية', 'R', 'Rain', 'images/letters/english/18.png', 'uploads/videos/1779151073_6950_rain.mp4', 'subjects/english/english-letter.php?id=18', '2026-05-17 14:59:15', 'uploads/images/1779151491_5704_r.png', 0),
(50, 19, '', 'اللغة الإنجليزية', 'الحروف الإنجليزية', 'S', 'Sun', 'images/letters/english/19.png', 'uploads/videos/1779151101_6298_sun.mp4', 'subjects/english/english-letter.php?id=19', '2026-05-17 14:59:15', 'uploads/images/1779151472_3142_s.png', 0),
(51, 20, '', 'اللغة الإنجليزية', 'الحروف الإنجليزية', 'T', 'Train', 'images/letters/english/20.png', 'uploads/videos/1779151123_8750_train.mp4', 'subjects/english/english-letter.php?id=20', '2026-05-17 14:59:15', 'uploads/images/1779151447_3054_t.png', 0),
(52, 21, '', 'اللغة الإنجليزية', 'الحروف الإنجليزية', 'U', 'Uniform', 'images/letters/english/21.png', 'uploads/videos/1779151147_6038_uniform.mp4', 'subjects/english/english-letter.php?id=21', '2026-05-17 14:59:15', 'uploads/images/1779151431_4424_u.png', 0),
(53, 22, '', 'اللغة الإنجليزية', 'الحروف الإنجليزية', 'V', 'Van', 'images/letters/english/22.png', 'uploads/videos/1779151183_5100_van.mp4', 'subjects/english/english-letter.php?id=22', '2026-05-17 14:59:15', 'uploads/images/1779151416_9620_v.png', 0),
(54, 23, '', 'اللغة الإنجليزية', 'الحروف الإنجليزية', 'W', 'Water', 'images/letters/english/23.png', 'uploads/videos/1779151207_6215_water.mp4', 'subjects/english/english-letter.php?id=23', '2026-05-17 14:59:15', 'uploads/images/1779151398_9067_w.png', 0),
(55, 24, '', 'اللغة الإنجليزية', 'الحروف الإنجليزية', 'X', 'X-Ray', 'images/letters/english/24.png', 'uploads/videos/1779151231_2116_X-Ray.mp4', 'subjects/english/english-letter.php?id=24', '2026-05-17 14:59:15', 'uploads/images/1779151373_9502_x.png', 0),
(56, 25, '', 'اللغة الإنجليزية', 'الحروف الإنجليزية', 'Y', 'Yellow', 'images/letters/english/25.png', 'uploads/videos/1779151254_6512_yellow.mp4', 'subjects/english/english-letter.php?id=25', '2026-05-17 14:59:15', 'uploads/images/1779151352_3676_y.png', 0),
(57, 26, '', 'اللغة الإنجليزية', 'الحروف الإنجليزية', 'Z', 'Zipper', 'images/letters/english/26.png', 'uploads/videos/1779151283_2521_zipper.mp4', 'subjects/english/english-letter.php?id=26', '2026-05-17 14:59:15', 'uploads/images/1779151336_5381_z.png', 0),
(99, 1, 'images/signs/numbers/1.png', 'الرياضيات', 'الأرقام العربية', '١', 'واحد', 'images/numbers/arabic/1.png', '', 'subjects/math/arabic-number.php?id=1', '2026-05-17 15:07:23', 'images/coloring/number-ar/1.png', 0),
(118, NULL, NULL, 'اللغة العربية', 'الحروف العربية', 'ء', NULL, 'uploads/images/1779041756_5112_30.png', '', '', '2026-05-17 18:15:56', NULL, 0),
(128, 1, NULL, 'اللغة الإنجليزية', 'الأرقام الإنجليزية', '1', 'One', NULL, NULL, NULL, '2026-05-31 00:30:58', NULL, 0),
(129, 2, NULL, 'اللغة الإنجليزية', 'الأرقام الإنجليزية', '2', 'Two', NULL, NULL, NULL, '2026-05-31 00:30:58', NULL, 0),
(130, 3, NULL, 'اللغة الإنجليزية', 'الأرقام الإنجليزية', '3', 'Three', NULL, NULL, NULL, '2026-05-31 00:30:58', NULL, 0),
(131, 4, NULL, 'اللغة الإنجليزية', 'الأرقام الإنجليزية', '4', 'Four', NULL, NULL, NULL, '2026-05-31 00:30:58', NULL, 0),
(132, 5, NULL, 'اللغة الإنجليزية', 'الأرقام الإنجليزية', '5', 'Five', NULL, NULL, NULL, '2026-05-31 00:30:58', NULL, 0),
(133, 6, NULL, 'اللغة الإنجليزية', 'الأرقام الإنجليزية', '6', 'Six', NULL, NULL, NULL, '2026-05-31 00:30:58', NULL, 0),
(134, 7, NULL, 'اللغة الإنجليزية', 'الأرقام الإنجليزية', '7', 'Seven', NULL, NULL, NULL, '2026-05-31 00:30:58', NULL, 0),
(135, 8, NULL, 'اللغة الإنجليزية', 'الأرقام الإنجليزية', '8', 'Eight', NULL, NULL, NULL, '2026-05-31 00:30:58', NULL, 0),
(136, 9, NULL, 'اللغة الإنجليزية', 'الأرقام الإنجليزية', '9', 'Nine', NULL, NULL, NULL, '2026-05-31 00:30:58', NULL, 0),
(137, 10, NULL, 'اللغة الإنجليزية', 'الأرقام الإنجليزية', '10', 'Ten', NULL, NULL, NULL, '2026-05-31 00:30:58', NULL, 0),
(148, 2, NULL, 'الرياضيات', 'الأرقام العربية', '٢', 'اثنان', NULL, NULL, NULL, '2026-05-31 00:36:36', NULL, 0),
(149, 3, NULL, 'الرياضيات', 'الأرقام العربية', '٣', 'ثلاثة', NULL, NULL, NULL, '2026-05-31 00:36:36', NULL, 0),
(150, 4, NULL, 'الرياضيات', 'الأرقام العربية', '٤', 'أربعة', NULL, NULL, NULL, '2026-05-31 00:36:36', NULL, 0),
(151, 5, NULL, 'الرياضيات', 'الأرقام العربية', '٥', 'خمسة', NULL, NULL, NULL, '2026-05-31 00:36:36', NULL, 0),
(152, 6, NULL, 'الرياضيات', 'الأرقام العربية', '٦', 'ستة', NULL, NULL, NULL, '2026-05-31 00:36:36', NULL, 0),
(153, 7, NULL, 'الرياضيات', 'الأرقام العربية', '٧', 'سبعة', NULL, NULL, NULL, '2026-05-31 00:36:36', NULL, 0),
(154, 8, NULL, 'الرياضيات', 'الأرقام العربية', '٨', 'ثمانية', NULL, NULL, NULL, '2026-05-31 00:36:36', NULL, 0),
(155, 9, NULL, 'الرياضيات', 'الأرقام العربية', '٩', 'تسعة', NULL, NULL, NULL, '2026-05-31 00:36:36', NULL, 0),
(156, 10, NULL, 'الرياضيات', 'الأرقام العربية', '١٠', 'عشرة', NULL, NULL, NULL, '2026-05-31 00:36:36', NULL, 0);

-- --------------------------------------------------------

--
-- بنية الجدول `lesson_progress`
--

CREATE TABLE `lesson_progress` (
  `id` int(11) NOT NULL,
  `child_id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `started_at` timestamp NULL DEFAULT NULL,
  `time_spent` int(11) DEFAULT 0 COMMENT 'بالثواني'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `lesson_progress`
--

INSERT INTO `lesson_progress` (`id`, `child_id`, `lesson_id`, `completed_at`, `started_at`, `time_spent`) VALUES
(9, 3, 24, '2026-05-30 23:30:24', NULL, 0),
(10, 3, 23, '2026-05-30 23:30:26', NULL, 0),
(11, 3, 6, '2026-05-30 23:30:27', NULL, 0),
(12, 3, 10, '2026-05-30 23:30:28', NULL, 0),
(13, 3, 25, '2026-05-30 23:30:30', NULL, 0),
(14, 3, 18, '2026-05-30 23:30:32', NULL, 0),
(15, 3, 35, '2026-05-30 23:43:59', NULL, 0),
(16, 3, 35, '2026-05-30 23:44:00', NULL, 0),
(17, 3, 33, '2026-05-30 23:53:31', NULL, 0),
(18, 3, 33, '2026-05-30 23:53:31', NULL, 0),
(19, 3, 2, '2026-05-30 23:54:22', NULL, 0),
(20, 3, 35, '2026-05-30 23:54:34', NULL, 0),
(21, 3, 35, '2026-05-30 23:54:34', NULL, 0),
(22, 3, 35, '2026-05-30 23:58:10', NULL, 0),
(23, 3, 35, '2026-05-30 23:58:11', NULL, 0),
(24, 3, 35, '2026-05-30 23:58:38', NULL, 0),
(25, 3, 35, '2026-05-31 00:00:24', NULL, 0),
(26, 3, 42, '2026-05-31 00:04:53', NULL, 0),
(27, 3, 42, '2026-05-31 00:05:42', NULL, 0),
(28, 3, 42, '2026-05-31 00:06:23', NULL, 0),
(29, 3, 125, '2026-05-31 00:09:58', NULL, 0),
(30, 3, 127, '2026-05-31 00:11:50', NULL, 0),
(31, 3, 167, '2026-05-31 10:28:28', NULL, 0),
(32, 3, 168, '2026-05-31 10:40:35', NULL, 0),
(33, 3, 168, '2026-05-31 10:40:39', NULL, 0),
(34, 3, 168, '2026-05-31 10:41:24', NULL, 0),
(35, 3, 168, '2026-05-31 10:41:30', NULL, 0),
(36, 3, 45, '2026-05-31 10:48:57', NULL, 0),
(37, 3, 169, '2026-05-31 10:51:00', NULL, 0),
(38, 3, 169, '2026-05-31 10:52:11', NULL, 0),
(39, 3, 169, '2026-05-31 10:52:15', NULL, 0),
(40, 3, 1, '2026-05-31 10:59:59', NULL, 0),
(41, 3, 39, '2026-05-31 11:08:14', NULL, 0),
(42, 3, 39, '2026-05-31 11:08:14', NULL, 0),
(43, 3, 134, '2026-05-31 11:09:23', NULL, 0),
(44, 3, 134, '2026-05-31 11:10:42', NULL, 0),
(45, 3, 167, '2026-05-31 11:29:58', NULL, 0),
(46, 3, 167, '2026-05-31 11:30:05', NULL, 0),
(47, 3, 167, '2026-05-31 11:30:09', NULL, 0),
(48, 3, 167, '2026-05-31 11:31:08', NULL, 0),
(49, 3, 167, '2026-05-31 11:31:24', NULL, 0),
(50, 3, 170, '2026-05-31 11:32:13', NULL, 0),
(51, 3, 170, '2026-05-31 11:32:23', NULL, 0),
(52, 3, 170, '2026-05-31 11:35:10', NULL, 0),
(53, 3, 170, '2026-05-31 11:35:11', NULL, 0),
(54, 3, 170, '2026-05-31 11:35:17', NULL, 0),
(55, 3, 170, '2026-05-31 11:35:20', NULL, 0),
(56, 3, 167, '2026-05-31 11:35:28', NULL, 0),
(57, 3, 167, '2026-05-31 11:35:39', NULL, 0),
(58, 3, 167, '2026-05-31 11:51:58', NULL, 0),
(59, 3, 167, '2026-05-31 11:52:02', NULL, 0),
(60, 3, 167, '2026-05-31 11:52:05', NULL, 0),
(61, 3, 167, '2026-05-31 11:52:53', NULL, 0),
(62, 3, 167, '2026-05-31 11:52:54', NULL, 0),
(63, 3, 167, '2026-05-31 11:52:58', NULL, 0),
(64, 3, 167, '2026-05-31 11:53:03', NULL, 0),
(65, 3, 170, '2026-05-31 11:53:54', NULL, 0),
(66, 3, 170, '2026-05-31 11:53:57', NULL, 0),
(67, 3, 170, '2026-05-31 11:54:04', NULL, 0),
(68, 3, 46, '2026-05-31 11:57:09', NULL, 0),
(69, 3, 46, '2026-05-31 11:57:24', NULL, 0),
(70, 3, 46, '2026-05-31 11:57:42', NULL, 0),
(71, 3, 169, '2026-05-31 12:00:36', NULL, 0),
(72, 3, 169, '2026-05-31 12:00:56', NULL, 0),
(73, 3, 169, '2026-05-31 12:01:02', NULL, 0),
(74, 3, 169, '2026-05-31 12:01:05', NULL, 0),
(75, 3, 171, '2026-05-31 12:20:54', NULL, 0),
(76, 3, 171, '2026-05-31 12:20:58', NULL, 0),
(77, 3, 171, '2026-05-31 12:21:08', NULL, 0),
(78, 3, 47, '2026-05-31 12:22:47', NULL, 0),
(79, 3, 169, '2026-05-31 12:23:36', NULL, 0),
(80, 3, 169, '2026-05-31 12:23:36', NULL, 0),
(81, 3, 172, '2026-05-31 12:24:58', NULL, 0),
(82, 3, 172, '2026-05-31 12:25:04', NULL, 0),
(83, 3, 172, '2026-05-31 12:25:06', NULL, 0),
(84, 3, 171, '2026-05-31 12:25:15', NULL, 0),
(85, 3, 171, '2026-05-31 12:25:19', NULL, 0),
(86, 3, 171, '2026-05-31 12:25:23', NULL, 0),
(87, 3, 33, '2026-05-31 13:11:52', NULL, 0),
(88, 3, 33, '2026-05-31 13:11:59', NULL, 0),
(89, 3, 48, '2026-06-01 11:16:40', NULL, 0),
(90, 3, 173, '2026-06-01 11:19:28', NULL, 0),
(91, 3, 173, '2026-06-01 11:19:28', NULL, 0),
(92, 3, 174, '2026-06-01 11:20:39', NULL, 0),
(93, 3, 174, '2026-06-01 11:20:46', NULL, 0),
(94, 3, 174, '2026-06-01 11:20:49', NULL, 0),
(95, 3, 174, '2026-06-01 11:20:59', NULL, 0),
(96, 3, 175, '2026-06-01 11:22:02', NULL, 0),
(97, 3, 175, '2026-06-01 11:22:10', NULL, 0),
(98, 3, 175, '2026-06-01 11:22:13', NULL, 0),
(99, 1, 49, '2026-06-02 08:53:39', NULL, 0),
(100, 1, 49, '2026-06-02 08:53:49', NULL, 0),
(101, 1, 49, '2026-06-02 08:53:53', NULL, 0),
(102, 3, 50, '2026-06-02 15:28:56', NULL, 0),
(103, 3, 50, '2026-06-02 15:29:51', NULL, 0),
(104, 3, 176, '2026-06-02 15:31:54', NULL, 0),
(105, 3, 176, '2026-06-02 15:31:54', NULL, 0),
(106, 3, 177, '2026-06-02 15:33:35', NULL, 0),
(107, 3, 178, '2026-06-02 15:34:46', NULL, 0),
(108, 3, 178, '2026-06-02 15:34:47', NULL, 0),
(109, 3, 178, '2026-06-02 15:37:35', NULL, 0),
(110, 3, 178, '2026-06-02 15:37:38', NULL, 0),
(111, 3, 178, '2026-06-02 15:37:43', NULL, 0),
(112, 3, 178, '2026-06-03 04:57:45', NULL, 0),
(113, 3, 178, '2026-06-03 04:57:49', NULL, 0),
(114, 3, 178, '2026-06-03 04:57:52', NULL, 0),
(115, 3, 27, '2026-06-03 07:07:37', NULL, 0),
(116, 3, 33, '2026-06-03 07:08:40', NULL, 0),
(117, 3, 33, '2026-06-03 07:08:40', NULL, 0),
(118, 3, 33, '2026-06-03 07:09:25', NULL, 0),
(119, 3, 134, '2026-06-03 07:09:35', NULL, 0),
(120, 3, 150, '2026-06-03 07:13:06', NULL, 0),
(121, 3, 150, '2026-06-03 07:13:06', NULL, 0),
(122, 3, 51, '2026-06-03 07:20:56', NULL, 0),
(123, 3, 51, '2026-06-03 07:21:04', NULL, 0),
(124, 3, 51, '2026-06-03 07:21:23', NULL, 0),
(125, 3, 51, '2026-06-03 07:21:38', NULL, 0),
(126, 3, 51, '2026-06-03 07:21:48', NULL, 0),
(127, 3, 179, '2026-06-03 07:23:31', NULL, 0),
(128, 3, 179, '2026-06-03 07:23:58', NULL, 0),
(129, 3, 180, '2026-06-03 07:25:06', NULL, 0),
(130, 3, 180, '2026-06-03 07:25:23', NULL, 0),
(131, 3, 130, '2026-06-04 12:12:51', NULL, 0),
(132, 3, 1, '2026-06-04 13:07:40', NULL, 0),
(133, 3, 27, '2026-06-04 13:09:21', NULL, 0),
(134, 3, 34, '2026-06-04 13:10:45', NULL, 0),
(135, 3, 34, '2026-06-04 13:10:45', NULL, 0),
(136, 3, 34, '2026-06-04 13:10:56', NULL, 0),
(137, 3, 149, '2026-06-04 13:22:09', NULL, 0),
(138, 3, 149, '2026-06-04 13:22:09', NULL, 0),
(139, 3, 28, '2026-06-04 13:23:48', NULL, 0),
(140, 3, 28, '2026-06-04 13:30:13', NULL, 0),
(141, 3, 28, '2026-06-04 13:48:53', NULL, 0),
(142, 3, 28, '2026-06-04 13:52:38', NULL, 0),
(143, 3, 36, '2026-06-04 13:55:05', NULL, 0),
(144, 3, 36, '2026-06-04 13:55:05', NULL, 0),
(145, 3, 129, '2026-06-04 13:57:57', NULL, 0),
(146, 3, 28, '2026-06-04 14:21:24', NULL, 0),
(147, 3, 28, '2026-06-04 15:00:26', NULL, 0),
(148, 3, 34, '2026-06-04 15:00:36', NULL, 0),
(149, 3, 34, '2026-06-04 15:00:36', NULL, 0),
(150, 3, 34, '2026-06-04 15:05:29', NULL, 0),
(151, 3, 27, '2026-06-04 15:05:36', NULL, 0),
(152, 3, 27, '2026-06-04 15:06:17', NULL, 0),
(153, 3, 27, '2026-06-04 15:06:24', NULL, 0),
(154, 3, 27, '2026-06-04 15:06:30', NULL, 0),
(155, 3, 27, '2026-06-04 15:07:25', NULL, 0),
(156, 3, 52, '2026-06-04 15:25:09', NULL, 0),
(157, 3, 52, '2026-06-04 15:25:35', NULL, 0),
(158, 3, 52, '2026-06-04 15:25:46', NULL, 0),
(159, 3, 52, '2026-06-04 15:25:57', NULL, 0),
(160, 3, 181, '2026-06-04 15:27:02', NULL, 0),
(161, 3, 181, '2026-06-04 15:27:43', NULL, 0),
(162, 3, 181, '2026-06-04 15:27:45', NULL, 0),
(163, 3, 181, '2026-06-04 15:27:51', NULL, 0),
(164, 3, 181, '2026-06-04 15:28:22', NULL, 0),
(165, 3, 181, '2026-06-04 15:28:27', NULL, 0),
(166, 3, 182, '2026-06-04 15:29:23', NULL, 0),
(167, 3, 182, '2026-06-04 15:29:24', NULL, 0),
(168, 3, 182, '2026-06-04 15:29:28', NULL, 0),
(169, 3, 182, '2026-06-04 15:29:31', NULL, 0),
(170, 3, 182, '2026-06-04 15:29:46', NULL, 0),
(171, 0, 99, '2026-06-04 18:25:27', NULL, 0),
(172, 3, 1, '2026-06-07 22:10:31', NULL, 0),
(173, 3, 27, '2026-06-07 22:10:37', NULL, 0),
(174, 3, 24, '2026-06-08 09:46:06', NULL, 0),
(175, 3, 24, '2026-06-08 09:54:43', NULL, 0),
(176, 3, 24, '2026-06-08 09:54:55', NULL, 0),
(177, 3, 24, '2026-06-08 09:54:55', NULL, 0),
(178, 3, 24, '2026-06-08 09:54:57', NULL, 0),
(179, 3, 24, '2026-06-08 09:54:59', NULL, 0),
(180, 3, 36, '2026-06-08 09:55:15', NULL, 0),
(181, 3, 36, '2026-06-08 09:55:19', NULL, 0),
(182, 3, 36, '2026-06-08 09:55:42', NULL, 0),
(183, 3, 36, '2026-06-08 09:55:50', NULL, 0),
(184, 3, 36, '2026-06-08 09:55:54', NULL, 0),
(185, 3, 132, '2026-06-08 09:56:00', NULL, 0),
(186, 3, 132, '2026-06-08 09:56:12', NULL, 0),
(187, 3, 132, '2026-06-08 09:56:14', NULL, 0),
(188, 3, 99, '2026-06-08 09:56:22', NULL, 0),
(189, 3, 99, '2026-06-08 09:56:23', NULL, 0),
(190, 3, 99, '2026-06-08 09:56:39', NULL, 0),
(191, 3, 27, '2026-06-08 10:20:31', NULL, 0),
(192, 3, 33, '2026-06-08 10:22:47', NULL, 0),
(193, 3, 33, '2026-06-08 10:22:47', NULL, 0),
(194, 3, 1, '2026-06-08 11:09:15', NULL, 0),
(209, 3, 52, '2026-06-08 13:23:10', NULL, 0),
(210, 3, 52, '2026-06-08 13:23:31', NULL, 0),
(211, 3, 52, '2026-06-08 13:23:38', NULL, 0),
(212, 3, 183, '2026-06-08 13:24:54', NULL, 0),
(213, 3, 183, '2026-06-08 13:25:00', NULL, 0),
(214, 3, 183, '2026-06-08 13:25:04', NULL, 0),
(215, 3, 183, '2026-06-08 13:25:22', NULL, 0),
(216, 3, 27, '2026-06-09 05:20:50', NULL, 0),
(217, 3, 27, '2026-06-09 05:23:06', NULL, 0),
(218, 3, 27, '2026-06-09 05:24:20', NULL, 0),
(219, 3, 33, '2026-06-09 05:24:53', NULL, 0),
(220, 3, 33, '2026-06-09 05:24:53', NULL, 0),
(221, 3, 33, '2026-06-09 05:25:59', NULL, 0),
(222, 3, 33, '2026-06-09 05:26:06', NULL, 0),
(223, 3, 33, '2026-06-09 05:26:10', NULL, 0),
(224, 3, 33, '2026-06-09 05:26:15', NULL, 0),
(225, 3, 33, '2026-06-09 05:28:57', NULL, 0),
(226, 3, 33, '2026-06-09 05:29:02', NULL, 0),
(227, 3, 134, '2026-06-09 05:29:36', NULL, 0),
(228, 3, 134, '2026-06-09 05:30:10', NULL, 0),
(229, 3, 27, '2026-06-09 07:09:05', NULL, 0),
(230, 3, 27, '2026-06-09 07:09:50', NULL, 0),
(231, 3, 27, '2026-06-09 07:10:24', NULL, 0),
(232, 3, 33, '2026-06-09 07:10:46', NULL, 0),
(233, 3, 33, '2026-06-09 07:10:46', NULL, 0),
(234, 3, 33, '2026-06-09 07:10:51', NULL, 0),
(235, 3, 33, '2026-06-09 07:11:06', NULL, 0),
(236, 3, 33, '2026-06-09 07:11:11', NULL, 0),
(237, 3, 131, '2026-06-09 07:11:18', NULL, 0),
(238, 3, 1, '2026-07-26 18:32:41', NULL, 0),
(239, 3, 1, '2026-07-26 18:39:01', NULL, 0),
(240, 3, 1, '2026-07-26 18:41:35', NULL, 0),
(241, 3, 128, '2026-07-26 18:44:18', NULL, 0),
(242, 3, 28, '2026-09-14 20:45:52', NULL, 0),
(243, 3, 148, '2026-09-14 20:48:40', NULL, 0),
(244, 3, 148, '2026-09-14 20:48:40', NULL, 0),
(245, 3, 148, '2026-09-14 20:50:14', NULL, 0),
(246, 3, 34, '2026-09-14 20:50:23', NULL, 0),
(247, 3, 34, '2026-09-14 20:50:23', NULL, 0),
(248, 3, 34, '2026-09-14 20:51:00', NULL, 0),
(249, 3, 1, '2026-09-19 12:20:35', NULL, 0),
(250, 3, 1, '2026-09-19 12:31:10', NULL, 0),
(251, 3, 27, '2026-09-19 12:39:22', NULL, 0),
(252, 3, 27, '2026-09-19 12:44:38', NULL, 0),
(253, 3, 1, '2026-09-19 12:57:01', NULL, 0),
(254, 3, 28, '2026-09-19 14:16:45', NULL, 0),
(255, 3, 28, '2026-09-19 14:17:20', NULL, 0),
(256, 3, 35, '2026-09-19 14:19:48', NULL, 0),
(257, 3, 35, '2026-09-19 14:19:48', NULL, 0),
(258, 3, 35, '2026-09-19 14:20:36', NULL, 0),
(259, 3, 27, '2026-09-19 14:21:39', NULL, 0),
(260, 3, 130, '2026-09-19 14:22:56', NULL, 0),
(261, 3, 130, '2026-09-19 14:23:19', NULL, 0),
(262, 3, 149, '2026-09-19 14:31:57', NULL, 0),
(263, 3, 149, '2026-09-19 14:31:57', NULL, 0),
(264, 3, 150, '2026-09-19 14:34:41', NULL, 0),
(265, 3, 150, '2026-09-19 14:34:42', NULL, 0),
(266, 3, 136, '2026-09-19 14:37:13', NULL, 0),
(267, 3, 131, '2026-09-19 14:38:13', NULL, 0),
(268, 3, 35, '2026-09-19 14:38:34', NULL, 0),
(269, 3, 35, '2026-09-19 14:38:34', NULL, 0),
(270, 3, 35, '2026-09-19 14:39:06', NULL, 0),
(271, 3, 150, '2026-09-19 14:39:17', NULL, 0),
(272, 3, 149, '2026-09-19 14:43:01', NULL, 0),
(273, 3, 149, '2026-09-19 14:43:01', NULL, 0),
(274, 3, 149, '2026-09-19 14:48:58', NULL, 0),
(275, 3, 149, '2026-09-19 14:48:58', NULL, 0);

-- --------------------------------------------------------

--
-- بنية الجدول `progress`
--

CREATE TABLE `progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `child_name` varchar(255) DEFAULT NULL,
  `activity_type` varchar(20) NOT NULL,
  `activity_key` varchar(100) NOT NULL,
  `activity_label` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `progress`
--

INSERT INTO `progress` (`id`, `user_id`, `child_name`, `activity_type`, `activity_key`, `activity_label`, `created_at`) VALUES
(48, 3, 'MASA', 'story', 'story3', 'زراعة البذور', '2026-09-19 14:10:32'),
(49, 3, 'MASA', 'section', 'section-أيام الأسبوع', 'أيام الأسبوع', '2026-09-19 13:32:42'),
(51, 3, 'MASA', 'lesson', 'arabic-letter-24', 'م', '2026-06-08 09:54:59'),
(52, 3, 'MASA', 'lesson', 'arabic-letter-23', 'ل', '2026-05-30 23:30:26'),
(53, 3, 'MASA', 'lesson', 'arabic-letter-6', 'ح', '2026-05-30 23:30:27'),
(54, 3, 'MASA', 'lesson', 'arabic-letter-10', 'ر', '2026-05-30 23:30:28'),
(55, 3, 'MASA', 'lesson', 'arabic-letter-25', 'ن', '2026-05-30 23:30:30'),
(56, 3, 'MASA', 'lesson', 'arabic-letter-18', 'ع', '2026-05-30 23:30:32'),
(57, 3, 'MASA', 'lesson', 'english-letter-4', 'D', '2026-09-19 14:39:06'),
(58, 3, 'MASA', 'lesson', 'english-letter-2', 'B', '2026-06-09 07:11:11'),
(59, 3, 'MASA', 'lesson', 'arabic-number-2', '٢ – اثنان', '2026-09-14 20:50:14'),
(61, 3, 'MASA', 'lesson', 'english-number-4', '4 – Four', '2026-09-19 14:38:13'),
(62, 3, 'MASA', 'lesson', 'arabic-letter-2', 'ب', '2026-05-30 23:54:22'),
(64, 3, 'MASA', 'lesson', 'arabic-number-4', '٤ – أربعة', '2026-09-19 14:39:17'),
(69, 3, 'MASA', 'game', 'english-puzzle-4', 'لعبة البازل', '2026-09-19 14:38:36'),
(73, 3, 'MASA', 'lesson', 'arabic-letter-45', 'كك', '2026-05-31 00:06:23'),
(74, 3, 'MASA', 'game', 'arabic-quiz-45', 'لعبة السمكة', '2026-05-31 00:06:26'),
(78, 3, 'MASA', 'lesson', 'english-letter-55', 'Z', '2026-05-31 00:09:58'),
(79, 3, 'MASA', 'game', 'english-puzzle-55', 'لعبة البازل', '2026-05-31 00:10:01'),
(80, 3, 'MASA', 'lesson', 'english-number-99', '99 – Nine', '2026-05-31 00:11:50'),
(82, 3, 'MASA', 'lesson', 'arabic-number-11', '١١ – احدى عشر', '2026-06-04 15:29:46'),
(87, 3, 'MASA', 'lesson', 'english-number-11', '11 – Eleven', '2026-06-08 13:25:22'),
(91, 3, 'MASA', 'lesson', 'arabic-letter-29', 'ة', '2026-06-03 07:21:48'),
(92, 3, 'MASA', 'game', 'arabic-quiz-29', 'لعبة السمكة', '2026-06-03 07:21:40'),
(93, 3, 'MASA', 'lesson', 'english-letter-27', 'Z', '2026-06-04 15:28:27'),
(94, 3, 'MASA', 'game', 'english-puzzle-27', 'لعبة البازل', '2026-06-04 15:27:49'),
(98, 3, 'MASA', 'section', 'section-الحيوانات', 'الحيوانات', '2026-09-19 14:50:16'),
(100, 3, 'MASA', 'section', 'section-pet', 'الحيوانات الأليفة', '2026-09-19 14:50:18'),
(101, 3, 'MASA', 'section', 'section-الفصول الأربعة', 'الفصول الأربعة', '2026-09-19 13:32:07'),
(105, 3, 'MASA', 'story', 'story1', 'رحلتي إلى المدرسة', '2026-09-19 13:16:02'),
(107, 3, 'MASA', 'lesson', 'arabic-letter-1', 'أ', '2026-09-19 12:57:01'),
(108, 3, 'MASA', 'game', 'arabic-quiz-1', 'لعبة السمكة', '2026-09-19 12:57:47'),
(109, 3, 'MASA', 'game', 'cups', 'لعبة الأكواب', '2026-09-19 14:52:51'),
(111, 3, 'MASA', 'game', 'memory', 'لعبة الذاكرة', '2026-09-19 14:56:32'),
(112, 3, 'MASA', 'game', 'chase', 'لعبة المطاردة', '2026-09-19 14:53:49'),
(113, 3, 'MASA', 'lesson', 'english-letter-8', 'H', '2026-05-31 11:08:14'),
(114, 3, 'MASA', 'game', 'english-puzzle-8', 'لعبة البازل', '2026-05-31 11:08:16'),
(115, 3, 'MASA', 'lesson', 'english-number-7', '7 – Seven', '2026-06-09 05:30:10'),
(117, 3, 'MASA', 'section', 'section-custom-2', 'قسم 2', '2026-05-31 12:02:35'),
(145, 3, 'MASA', 'lesson', 'arabic-letter-35', 'ة', '2026-06-08 13:23:38'),
(147, 3, 'MASA', 'game', 'arabic-quiz-35', 'لعبة السمكة', '2026-06-08 13:23:32'),
(157, 3, 'MASA', 'section', 'section-custom-3', 'قسم 3', '2026-06-01 09:15:40'),
(175, 3, 'MASA', 'story', 'story-12', 'الكرة المفقودة', '2026-05-31 12:42:28'),
(180, 3, 'MASA', 'game', 'english-puzzle-2', 'لعبة البازل', '2026-06-09 07:10:58'),
(183, 3, 'MASA', 'section', 'section-custom-4', 'قسم 4', '2026-06-01 09:16:39'),
(184, 3, 'MASA', 'section', 'section-أركان الإسلام', 'أركان الإسلام', '2026-09-19 13:59:43'),
(192, 3, 'MASA', 'section', 'section-الطقس', 'الطقس', '2026-09-19 13:58:28'),
(207, 3, 'MASA', 'section', 'section-custom-5', 'الأشكال الهندسية', '2026-06-03 04:53:26'),
(212, 1, 'nasma', 'game', 'memory', 'لعبة الذاكرة', '2026-06-02 08:51:19'),
(213, 1, 'nasma', 'lesson', 'arabic-letter-29', 'ة', '2026-06-02 08:53:53'),
(215, 1, 'nasma', 'game', 'arabic-quiz-29', 'لعبة السمكة', '2026-06-02 08:53:51'),
(219, 3, 'MASA', 'section', 'section-custom-6', 'الطقس', '2026-06-02 15:07:14'),
(243, 3, 'MASA', 'lesson', 'arabic-letter-27', 'و', '2026-09-19 14:21:39'),
(244, 3, 'MASA', 'game', 'arabic-quiz-27', 'لعبة السمكة', '2026-09-19 12:44:22'),
(261, 3, 'MASA', 'section', 'section-custom-7', 'الأشكال الهندسية', '2026-06-04 13:21:34'),
(262, 3, 'MASA', 'lesson', 'english-number-3', '3 – Three', '2026-09-19 14:23:19'),
(266, 3, 'MASA', 'lesson', 'english-letter-3', 'C', '2026-09-14 20:51:00'),
(267, 3, 'MASA', 'game', 'english-puzzle-3', 'لعبة البازل', '2026-09-14 20:50:25'),
(272, 3, 'MASA', 'game', 'colors', 'لعبة الألوان', '2026-06-04 13:19:00'),
(273, 3, 'MASA', 'game', 'numbers', 'لعبة الأعداد', '2026-06-04 13:19:21'),
(275, 3, 'MASA', 'lesson', 'arabic-number-3', '٣ – ثلاثة', '2026-09-19 14:48:58'),
(277, 3, 'MASA', 'lesson', 'arabic-letter-28', 'ي', '2026-09-19 14:17:20'),
(283, 3, 'MASA', 'section', 'section-فواكه وخضراوات', 'فواكه وخضراوات', '2026-09-19 13:34:06'),
(284, 3, 'MASA', 'section', 'section-vegetable', 'الخضار', '2026-09-19 13:34:00'),
(286, 3, 'MASA', 'section', 'section-fruit', 'الفواكه', '2026-09-14 17:30:29'),
(290, 3, 'MASA', 'lesson', 'english-letter-5', 'E', '2026-06-08 09:55:54'),
(291, 3, 'MASA', 'game', 'english-puzzle-5', 'لعبة البازل', '2026-06-08 09:55:53'),
(292, 3, 'MASA', 'lesson', 'english-number-2', '2 – Two', '2026-06-04 13:57:57'),
(301, 3, 'MASA', 'game', 'arabic-quiz-28', 'لعبة السمكة', '2026-09-14 20:46:06'),
(347, 3, 'MASA', 'section', 'section-custom-8', 'الأشكال الهندسية', '2026-06-08 11:09:00'),
(355, 0, 'معاينة — hala', 'lesson', 'arabic-number-1', '١ – واحد', '2026-06-04 18:25:27'),
(356, 0, 'معاينة — hala', 'section', 'section-custom-8', 'الأشكال الهندسية', '2026-06-04 18:42:44'),
(357, 0, 'معاينة — hala', 'section', 'section-fruit', 'الفواكه', '2026-06-04 18:26:55'),
(358, 0, 'معاينة — hala', 'section', 'section-أركان الإسلام', 'أركان الإسلام', '2026-06-04 18:42:36'),
(359, 0, 'معاينة — hala', 'section', 'section-wild', 'الحيوانات المفترسة', '2026-06-04 18:30:54'),
(361, 0, 'معاينة — hala', 'section', 'section-أيام الأسبوع', 'أيام الأسبوع', '2026-06-04 18:31:27'),
(362, 0, 'معاينة — hala', 'section', 'section-الطقس', 'الطقس', '2026-06-04 18:40:36'),
(372, 3, 'MASA', 'game', 'arabic-quiz-24', 'لعبة السمكة', '2026-06-08 09:54:56'),
(384, 3, 'MASA', 'lesson', 'english-number-5', '5 – Five', '2026-06-08 09:56:14'),
(387, 3, 'MASA', 'lesson', 'arabic-number-1', '١ – واحد', '2026-06-08 09:56:39'),
(489, 3, 'MASA', 'section', 'section-custom-9', 'الأشكال الهندسية', '2026-06-08 13:28:34'),
(539, 3, 'MASA', 'section', 'section-wild', 'الحيوانات المفترسة', '2026-09-19 14:50:57'),
(545, 3, 'MASA', 'story', 'story2', 'إطعام الحيوانات', '2026-09-19 14:10:26'),
(564, 3, 'MASA', 'lesson', 'english-number-1', '1 – One', '2026-07-26 18:44:18'),
(578, 3, 'MASA', 'story', 'story4', 'أنا أحب الرسم', '2026-07-26 19:28:09'),
(675, 3, 'MASA', 'lesson', 'english-number-9', '9 – Nine', '2026-09-19 14:37:13');

-- --------------------------------------------------------

--
-- بنية الجدول `sign_notifications`
--

CREATE TABLE `sign_notifications` (
  `id` int(11) NOT NULL,
  `code` varchar(100) NOT NULL,
  `message_text` varchar(255) NOT NULL,
  `video_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `sign_notifications`
--

INSERT INTO `sign_notifications` (`id`, `code`, `message_text`, `video_path`) VALUES
(1, 'empty_fields', 'يرجى إدخال اسم المستخدم وكلمة المرور', '../assets/videos/empty-fields.mp4'),
(2, 'user_not_found', 'هذا الحساب غير موجود', '../assets/videos/user-not-found.mp4'),
(3, 'wrong_password', 'كلمة المرور غير صحيحة', '../assets/videos/wrong-password.mp4'),
(4, 'username_required', 'اسم المستخدم مطلوب', '../assets/videos/username.mp4'),
(5, 'username_letters_only', 'اسم المستخدم يجب أن يحتوي على حروف فقط', '../assets/videos/username.mp4'),
(6, 'password_required', 'كلمة المرور مطلوبة', '../assets/videos/password.mp4'),
(7, 'age_invalid', 'العمر يجب أن يكون بين خمسة وأربعة عشر', '../assets/videos/age.mp4'),
(8, 'gender_required', 'يرجى اختيار الجنس', '../assets/videos/gender.mp4'),
(9, 'username_taken', 'هذا الإسم محجوز مسبقاً', '../assets/videos/pre-booked-user.mp4'),
(10, 'general_error', 'حدث خطأ', '../assets/videos/empty-fields.mp4');

-- --------------------------------------------------------

--
-- بنية الجدول `stories`
--

CREATE TABLE `stories` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `cover_image` varchar(255) NOT NULL,
  `sign_video` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `cover_video` varchar(255) DEFAULT NULL,
  `cover_title` varchar(500) DEFAULT NULL,
  `poster_image` varchar(255) DEFAULT NULL,
  `story_link` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_by_supervisor` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `stories`
--

INSERT INTO `stories` (`id`, `title`, `cover_image`, `sign_video`, `created_at`, `cover_video`, `cover_title`, `poster_image`, `story_link`, `sort_order`, `created_by_supervisor`) VALUES
(1, 'رحلتي إلى المدرسة', '', 'assets/videos/story1-sign.mp4', '2026-05-21 22:33:36', NULL, NULL, 'images/general/stories/images/poster/story1.png', 'story1.php', 1, 0),
(2, 'إطعام الحيوانات', '', 'assets/videos/story2-sign.mp4', '2026-05-21 22:33:36', NULL, NULL, 'images/general/stories/images/poster/story2.png', 'story2.php', 2, 0),
(3, 'زراعة البذور', '', 'assets/videos/story3-sign.mp4', '2026-05-21 22:33:36', NULL, NULL, 'images/general/stories/images/poster/story3.png', 'story3.php', 3, 0),
(4, 'أنا أحب الرسم', '', 'assets/videos/story4-sign.mp4', '2026-05-21 22:33:36', NULL, NULL, 'images/general/stories/images/poster/story4.png', 'story4.php', 4, 0);

-- --------------------------------------------------------

--
-- بنية الجدول `story_pages`
--

CREATE TABLE `story_pages` (
  `id` int(11) NOT NULL,
  `story_id` int(11) NOT NULL,
  `page_order` int(11) DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `sign_video` varchar(255) DEFAULT NULL,
  `caption` varchar(1000) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `story_progress`
--

CREATE TABLE `story_progress` (
  `id` int(11) NOT NULL,
  `child_id` int(11) NOT NULL,
  `story_id` int(11) NOT NULL,
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_page` int(11) DEFAULT 1,
  `total_pages` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `story_progress`
--

INSERT INTO `story_progress` (`id`, `child_id`, `story_id`, `completed_at`, `last_page`, `total_pages`) VALUES
(5, 3, 3, '2026-05-30 23:29:20', 1, 1),
(6, 3, 1, '2026-05-31 10:55:16', 1, 1),
(7, 3, 3, '2026-05-31 10:56:01', 1, 1),
(9, 3, 2, '2026-06-09 05:37:19', 1, 1),
(10, 3, 1, '2026-07-26 19:23:25', 1, 1),
(11, 3, 2, '2026-07-26 19:25:28', 1, 1),
(12, 3, 3, '2026-07-26 19:27:13', 1, 1),
(13, 3, 4, '2026-07-26 19:28:09', 1, 1),
(14, 3, 1, '2026-09-19 13:16:02', 1, 1),
(15, 3, 2, '2026-09-19 14:10:26', 1, 1),
(16, 3, 3, '2026-09-19 14:10:32', 1, 1);

-- --------------------------------------------------------

--
-- بنية الجدول `supervisors`
--

CREATE TABLE `supervisors` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `full_name` varchar(200) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `supervisors`
--

INSERT INTO `supervisors` (`id`, `username`, `full_name`, `password_hash`, `is_active`, `created_at`) VALUES
(3, 'mohammad ali', 'محمد علي', '$2y$10$9JCNBA6s3aS/hmayHvx1ru5n7jF3HGG87glTDQYKUUrgvL6p6eURS', 1, '2026-06-01 09:40:38'),
(4, 'hala', 'hala jalil', '$2y$10$BjPaoN63XUjI4.AM5vOfNec25ho1Un.UEO5HOndLPD3O3bzBVvoL.', 1, '2026-06-04 18:24:20');

-- --------------------------------------------------------

--
-- بنية الجدول `supervisor_permissions`
--

CREATE TABLE `supervisor_permissions` (
  `id` int(11) NOT NULL,
  `supervisor_id` int(11) NOT NULL,
  `section` varchar(60) NOT NULL,
  `can_view` tinyint(1) DEFAULT 0,
  `can_add` tinyint(1) DEFAULT 0,
  `can_edit` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `supervisor_permissions`
--

INSERT INTO `supervisor_permissions` (`id`, `supervisor_id`, `section`, `can_view`, `can_add`, `can_edit`, `can_delete`) VALUES
(61, 3, 'arabic_letters', 1, 1, 1, 1),
(62, 3, 'english_letters', 1, 1, 1, 1),
(63, 3, 'arabic_numbers', 1, 1, 1, 1),
(64, 3, 'english_numbers', 1, 1, 1, 1),
(65, 3, 'stories', 1, 1, 1, 1),
(66, 3, 'games', 1, 0, 0, 0),
(67, 3, 'general_culture', 1, 1, 1, 1),
(68, 3, 'gc_animals', 1, 1, 1, 1),
(69, 3, 'gc_weather', 1, 1, 1, 1),
(70, 3, 'gc_seasons', 1, 1, 1, 1),
(71, 3, 'gc_days', 1, 1, 1, 1),
(72, 3, 'gc_food', 1, 1, 1, 1),
(73, 3, 'gc_islam', 1, 1, 1, 1),
(74, 3, 'gc_custom_4', 1, 1, 1, 1),
(75, 3, 'children', 0, 0, 0, 0),
(76, 3, 'reports', 0, 0, 0, 0),
(77, 3, 'chat', 0, 0, 0, 0),
(120, 4, 'arabic_letters', 1, 1, 1, 0),
(121, 4, 'english_letters', 1, 1, 1, 0),
(122, 4, 'arabic_numbers', 1, 0, 0, 1),
(123, 4, 'english_numbers', 1, 0, 0, 1),
(124, 4, 'stories', 1, 1, 1, 0),
(125, 4, 'games', 1, 1, 0, 0),
(126, 4, 'general_culture', 1, 1, 1, 1),
(127, 4, 'gc_weather', 0, 0, 0, 0),
(128, 4, 'gc_seasons', 0, 0, 0, 0),
(129, 4, 'gc_days', 0, 0, 0, 0),
(130, 4, 'gc_islam', 0, 0, 0, 0),
(131, 4, 'gc_custom_8', 0, 0, 0, 0),
(132, 4, 'gc_food', 1, 1, 1, 1),
(133, 4, 'gc_food_vegetable', 1, 0, 0, 0),
(134, 4, 'gc_food_fruit', 1, 0, 0, 0),
(135, 4, 'gc_animals', 1, 1, 1, 1),
(136, 4, 'gc_animals_wild', 1, 0, 0, 0),
(137, 4, 'gc_animals_pet', 1, 0, 0, 0),
(138, 4, 'children', 1, 0, 0, 0),
(139, 4, 'reports', 1, 0, 0, 0),
(140, 4, 'chat', 1, 0, 0, 0);

-- --------------------------------------------------------

--
-- بنية الجدول `type_icons`
--

CREATE TABLE `type_icons` (
  `id` int(11) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `type_name` varchar(100) NOT NULL,
  `icon_file` varchar(500) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `animals`
--
ALTER TABLE `animals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `arabic_letter_examples`
--
ALTER TABLE `arabic_letter_examples`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `children`
--
ALTER TABLE `children`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `child_progress`
--
ALTER TABLE `child_progress`
  ADD PRIMARY KEY (`id`),
  ADD KEY `child_id` (`child_id`);

--
-- Indexes for table `food_items`
--
ALTER TABLE `food_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `game_progress`
--
ALTER TABLE `game_progress`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `general_sections`
--
ALTER TABLE `general_sections`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `general_section_cards`
--
ALTER TABLE `general_section_cards`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `general_section_progress`
--
ALTER TABLE `general_section_progress`
  ADD PRIMARY KEY (`id`),
  ADD KEY `child_id` (`child_id`),
  ADD KEY `section_name` (`section_name`);

--
-- Indexes for table `help_clicks`
--
ALTER TABLE `help_clicks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_date` (`created_at`);

--
-- Indexes for table `lessons`
--
ALTER TABLE `lessons`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lesson_progress`
--
ALTER TABLE `lesson_progress`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `progress`
--
ALTER TABLE `progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_progress` (`user_id`,`activity_type`,`activity_key`);

--
-- Indexes for table `sign_notifications`
--
ALTER TABLE `sign_notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `stories`
--
ALTER TABLE `stories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `story_pages`
--
ALTER TABLE `story_pages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `story_progress`
--
ALTER TABLE `story_progress`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `supervisors`
--
ALTER TABLE `supervisors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `supervisor_permissions`
--
ALTER TABLE `supervisor_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_sup_sec` (`supervisor_id`,`section`);

--
-- Indexes for table `type_icons`
--
ALTER TABLE `type_icons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_subject_type` (`subject`,`type_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `animals`
--
ALTER TABLE `animals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `arabic_letter_examples`
--
ALTER TABLE `arabic_letter_examples`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `children`
--
ALTER TABLE `children`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `child_progress`
--
ALTER TABLE `child_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `food_items`
--
ALTER TABLE `food_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `games`
--
ALTER TABLE `games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `game_progress`
--
ALTER TABLE `game_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `general_sections`
--
ALTER TABLE `general_sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `general_section_cards`
--
ALTER TABLE `general_section_cards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `general_section_progress`
--
ALTER TABLE `general_section_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `help_clicks`
--
ALTER TABLE `help_clicks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=247;

--
-- AUTO_INCREMENT for table `lessons`
--
ALTER TABLE `lessons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=184;

--
-- AUTO_INCREMENT for table `lesson_progress`
--
ALTER TABLE `lesson_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=276;

--
-- AUTO_INCREMENT for table `progress`
--
ALTER TABLE `progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=691;

--
-- AUTO_INCREMENT for table `sign_notifications`
--
ALTER TABLE `sign_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `stories`
--
ALTER TABLE `stories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `story_pages`
--
ALTER TABLE `story_pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `story_progress`
--
ALTER TABLE `story_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `supervisors`
--
ALTER TABLE `supervisors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `supervisor_permissions`
--
ALTER TABLE `supervisor_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=162;

--
-- AUTO_INCREMENT for table `type_icons`
--
ALTER TABLE `type_icons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- قيود الجداول المُلقاة.
--

--
-- قيود الجداول `child_progress`
--
ALTER TABLE `child_progress`
  ADD CONSTRAINT `child_progress_ibfk_1` FOREIGN KEY (`child_id`) REFERENCES `children` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `supervisor_permissions`
--
ALTER TABLE `supervisor_permissions`
  ADD CONSTRAINT `supervisor_permissions_ibfk_1` FOREIGN KEY (`supervisor_id`) REFERENCES `supervisors` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
