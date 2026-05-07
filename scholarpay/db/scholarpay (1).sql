-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 07, 2026 at 04:22 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `scholarpay`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
(1, 1, 'LOGIN', 'User logged in', '::1', '2026-05-06 14:16:38'),
(2, 1, 'LOGOUT', 'User logged out', '::1', '2026-05-06 21:30:30'),
(3, 1, 'LOGOUT', 'User logged out', '::1', '2026-05-06 21:30:30'),
(4, 2, 'LOGIN', 'User logged in', '::1', '2026-05-07 00:59:34'),
(5, 2, 'LOGOUT', 'User logged out', '::1', '2026-05-07 01:06:31'),
(6, 1, 'LOGIN', 'User logged in', '::1', '2026-05-07 01:06:43'),
(7, 1, 'LOGOUT', 'User logged out', '::1', '2026-05-07 01:36:45'),
(8, 1, 'LOGIN', 'User logged in', '::1', '2026-05-07 04:14:19'),
(9, 1, 'DISBURSE', 'Disbursed 5 USDC to student 8 from scholarship 1. Tx: 58c74f5c279cea5ec4f2bdea38c3192baee306985943b06fca522a6982757f4f', '::1', '2026-05-07 04:15:02');

-- --------------------------------------------------------

--
-- Table structure for table `disbursements`
--

CREATE TABLE `disbursements` (
  `id` int(11) NOT NULL,
  `scholarship_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `amount` decimal(15,7) NOT NULL COMMENT 'USDC amount disbursed',
  `purpose` varchar(200) NOT NULL,
  `stellar_tx_hash` varchar(100) DEFAULT NULL COMMENT 'On-chain transaction hash',
  `ledger_sequence` bigint(20) DEFAULT NULL COMMENT 'Stellar ledger sequence number',
  `status` enum('pending','confirmed','failed') NOT NULL DEFAULT 'pending',
  `disbursed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `disbursements`
--

INSERT INTO `disbursements` (`id`, `scholarship_id`, `student_id`, `admin_id`, `amount`, `purpose`, `stellar_tx_hash`, `ledger_sequence`, `status`, `disbursed_at`) VALUES
(1, 1, 8, 1, 50000000.0000000, 'food', '58c74f5c279cea5ec4f2bdea38c3192baee306985943b06fca522a6982757f4f', 52334849, 'confirmed', '2026-05-07 04:15:02');

-- --------------------------------------------------------

--
-- Table structure for table `scholarships`
--

CREATE TABLE `scholarships` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `total_amount` decimal(15,7) NOT NULL COMMENT 'Amount in USDC (7 decimals)',
  `remaining_amount` decimal(15,7) NOT NULL,
  `token_address` varchar(60) DEFAULT NULL COMMENT 'USDC contract address on Stellar',
  `status` enum('active','inactive','depleted') NOT NULL DEFAULT 'active',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `scholarships`
--

INSERT INTO `scholarships` (`id`, `name`, `description`, `total_amount`, `remaining_amount`, `token_address`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'STEM Global Grant 2025', 'For students pursuing Science, Technology, Engineering, and Mathematics degrees.', 99999999.9999999, 49999999.9999999, 'CA2EY3LL6EI3ARMSRPSIUDG5K5YFS6T5367Z46OAP6FIHJFYDX5H6JUU', 'active', 1, '2026-05-07 01:10:11', '2026-05-07 04:15:02'),
(2, 'Emergency Relief Fund', 'Immediate financial assistance for students affected by emergencies or disasters.', 99999999.9999999, 99999999.9999999, 'CA2EY3LL6EI3ARMSRPSIUDG5K5YFS6T5367Z46OAP6FIHJFYDX5H6JUU', 'active', 1, '2026-05-07 01:10:11', '2026-05-07 01:10:11'),
(3, 'Women in Tech Scholarship', 'Supporting women pursuing technology and engineering careers in Southeast Asia.', 99999999.9999999, 99999999.9999999, 'CA2EY3LL6EI3ARMSRPSIUDG5K5YFS6T5367Z46OAP6FIHJFYDX5H6JUU', 'active', 1, '2026-05-07 01:10:11', '2026-05-07 01:10:11'),
(4, 'Vocational Excellence Award', 'For students enrolled in vocational or technical-vocational programs.', 99999999.9999999, 99999999.9999999, 'CA2EY3LL6EI3ARMSRPSIUDG5K5YFS6T5367Z46OAP6FIHJFYDX5H6JUU', 'active', 1, '2026-05-07 01:10:11', '2026-05-07 01:10:11');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','student') NOT NULL DEFAULT 'student',
  `stellar_address` varchar(60) DEFAULT NULL COMMENT 'Stellar wallet public key',
  `institution` varchar(200) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `stellar_address`, `institution`, `created_at`, `updated_at`) VALUES
(1, 'Admin User', 'admin@scholarpay.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, 'ScholarPay Foundation', '2026-05-06 01:48:04', '2026-05-06 01:48:04'),
(2, 'Maria Santos', 'student@scholarpay.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'GBSZ2NFPQZJRRLSQ6TA5D5GNJNKBQVQVMWFP6LRGWUQZGFHK7ZFDXQ', 'University of the Philippines', '2026-05-06 01:48:04', '2026-05-06 01:48:04'),
(3, 'Juan dela Cruz', 'juan.delacruz@scholarpay.org', '$2y$10$TKh8H1.PFbuSwvrbsIwjde731mdMsFDzmrFJGSEEBdOj4KRGMUI4a', 'student', 'GBSZ2NFPQZJRRLSQ6TA5D5GNJNKBQVQVMWFP6LRGWUQZGFHK7ZFDXQ', 'Polytechnic University of the Philippines', '2026-05-07 01:10:11', '2026-05-07 01:10:11'),
(4, 'Ana Reyes', 'ana.reyes@scholarpay.org', '$2y$10$TKh8H1.PFbuSwvrbsIwjde731mdMsFDzmrFJGSEEBdOj4KRGMUI4a', 'student', 'GCEZWKCA5VLDNRLN3RPRJMRZOX3Z6G5CHCGGEWODE8XCNZW7BWAARRP', 'University of Santo Tomas', '2026-05-07 01:10:11', '2026-05-07 01:10:11'),
(5, 'Carlos Mendoza', 'carlos.mendoza@scholarpay.org', '$2y$10$TKh8H1.PFbuSwvrbsIwjde731mdMsFDzmrFJGSEEBdOj4KRGMUI4a', 'student', 'GDQJUTQYK2MQX2DGUIOVNRZQ2JGENG7WTZOUQCDOKPJRRFPF4HCZMCO', 'De La Salle University', '2026-05-07 01:10:11', '2026-05-07 01:10:11'),
(6, 'Sofia Ramos', 'sofia.ramos@scholarpay.org', '$2y$10$TKh8H1.PFbuSwvrbsIwjde731mdMsFDzmrFJGSEEBdOj4KRGMUI4a', 'student', 'GDRXE2BQUC3AZNPVFSCEZ76NJ3WWL25FYFK6RIGPZF6YKIDZKMAGWOS', 'Ateneo de Manila University', '2026-05-07 01:10:11', '2026-05-07 01:10:11'),
(7, 'Miguel Torres', 'miguel.torres@scholarpay.org', '$2y$10$TKh8H1.PFbuSwvrbsIwjde731mdMsFDzmrFJGSEEBdOj4KRGMUI4a', 'student', 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN', 'University of the Philippines Diliman', '2026-05-07 01:10:11', '2026-05-07 01:10:11'),
(8, 'Isabella Flores', 'isabella.flores@scholarpay.org', '$2y$10$TKh8H1.PFbuSwvrbsIwjde731mdMsFDzmrFJGSEEBdOj4KRGMUI4a', 'student', 'GALAXYVAULT3XYZSTUDENTDEMO0000000000000000000000AAABBBCCC', 'Far Eastern University', '2026-05-07 01:10:11', '2026-05-07 01:10:11'),
(9, 'Rafael Santos', 'rafael.santos@scholarpay.org', '$2y$10$TKh8H1.PFbuSwvrbsIwjde731mdMsFDzmrFJGSEEBdOj4KRGMUI4a', 'student', 'GBVZP3BKJT6BKRWBXKWN3AQFKPLXXOIOLOQGX3EKRMHVXHFQFCQZ2T', 'Mapua University', '2026-05-07 01:10:11', '2026-05-07 01:10:11'),
(10, 'Chloe Villanueva', 'chloe.villanueva@scholarpay.org', '$2y$10$TKh8H1.PFbuSwvrbsIwjde731mdMsFDzmrFJGSEEBdOj4KRGMUI4a', 'student', 'GC2ADYAIPKYQRJSFQ7OPVVTXRPWNXEJF3RMBYD6JRJQBKN6LDKUYWBN', 'National University', '2026-05-07 01:10:11', '2026-05-07 01:10:11'),
(11, 'Daniel Garcia', 'daniel.garcia@scholarpay.org', '$2y$10$TKh8H1.PFbuSwvrbsIwjde731mdMsFDzmrFJGSEEBdOj4KRGMUI4a', 'student', 'GDYM6MAFMSNKZS5SQHKQPLNZSEQBEMLK57JNFKXQL57KKFTCG3B3NBU', 'University of San Carlos', '2026-05-07 01:10:11', '2026-05-07 01:10:11'),
(12, 'Gabrielle Lim', 'gabrielle.lim@scholarpay.org', '$2y$10$TKh8H1.PFbuSwvrbsIwjde731mdMsFDzmrFJGSEEBdOj4KRGMUI4a', 'student', 'GCFXHS4GXL6BVUCXBWXGTITROWLVYRF65H65TCKP6ICR27KKBM56VX5', 'Adamson University', '2026-05-07 01:10:11', '2026-05-07 01:10:11');

-- --------------------------------------------------------

--
-- Table structure for table `whitelisted_wallets`
--

CREATE TABLE `whitelisted_wallets` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `stellar_address` varchar(60) NOT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `whitelisted_by` int(11) NOT NULL,
  `whitelisted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `disbursements`
--
ALTER TABLE `disbursements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `scholarship_id` (`scholarship_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `scholarships`
--
ALTER TABLE `scholarships`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `whitelisted_wallets`
--
ALTER TABLE `whitelisted_wallets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `whitelisted_by` (`whitelisted_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `disbursements`
--
ALTER TABLE `disbursements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `scholarships`
--
ALTER TABLE `scholarships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `whitelisted_wallets`
--
ALTER TABLE `whitelisted_wallets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `disbursements`
--
ALTER TABLE `disbursements`
  ADD CONSTRAINT `disbursements_ibfk_1` FOREIGN KEY (`scholarship_id`) REFERENCES `scholarships` (`id`),
  ADD CONSTRAINT `disbursements_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `disbursements_ibfk_3` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `scholarships`
--
ALTER TABLE `scholarships`
  ADD CONSTRAINT `scholarships_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `whitelisted_wallets`
--
ALTER TABLE `whitelisted_wallets`
  ADD CONSTRAINT `whitelisted_wallets_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `whitelisted_wallets_ibfk_2` FOREIGN KEY (`whitelisted_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
