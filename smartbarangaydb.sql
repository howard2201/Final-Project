-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 07, 2025 at 11:24 AM
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
-- Database: `smartbarangaydb`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
-- Default admin account: email: admin@gmail.com, password: admin12345
--

INSERT INTO `admins` (`id`, `full_name`, `email`, `password`, `created_at`) VALUES
(1, 'Admin', 'admin@gmail.com', '$2y$10$n8WWffc7EUVMvnpJaiTKbOsYMhomiJ4hjY6MV4KNmWNYAMGoZt/Gm', '2024-01-01 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `id` int(11) NOT NULL,
  `resident_id` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `id_file` varchar(255) NOT NULL,
  `residency_file` varchar(255) NOT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `residents`
--

CREATE TABLE `residents` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `resident_password` varchar(255) NOT NULL,
  `id_file` LONGBLOB NOT NULL,
  `proof_file` LONGBLOB NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `residents` and 'admins`
--

INSERT INTO `residents` (`id`, `full_name`, `email`, `resident_password`, `id_file`, `proof_file`, `created_at`) VALUES
(1, 'Kevin Baniel R. Guieb', 'Nog@gmail.com', '$2y$10$hvD4p0NlkaCbyZan.NVLeOGLCqu5cKVVCEF83Fo9NMRDCk9Gpycpy', 'Screenshot 2025-11-05 134604.png', 'Screenshot 2025-11-05 134604.png', '2025-11-05 15:09:41');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resident_id` (`resident_id`);

--
-- Indexes for table `residents`
--
ALTER TABLE `residents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `residents`
--
ALTER TABLE `residents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `requests`
--
ALTER TABLE `requests`
  ADD CONSTRAINT `requests_ibfk_1` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;


-- ========================================
-- STORED PROCEDURES
-- ========================================

-- ========================================
-- RESIDENT PROCEDURES
-- ========================================

-- Check if email exists for residents
DELIMITER $$
CREATE PROCEDURE `checkResidentEmailExists` (
    IN p_email VARCHAR(255)
)
BEGIN
    SELECT id FROM residents WHERE email = p_email;
END$$
DELIMITER ;

-- Register new resident
DELIMITER $$
CREATE PROCEDURE `registerResident` (
    IN p_full_name VARCHAR(255),
    IN p_email VARCHAR(255),
    IN p_password VARCHAR(255),
    IN p_id_file LONGBLOB,
    IN p_proof_file LONGBLOB
)
BEGIN
    INSERT INTO residents (full_name, email, resident_password, id_file, proof_file)
    VALUES (p_full_name, p_email, p_password, p_id_file, p_proof_file);
END$$
DELIMITER ;

-- Login resident
DELIMITER $$
CREATE PROCEDURE `loginResident` (
    IN p_email VARCHAR(255)
)
BEGIN
    SELECT * FROM residents WHERE email = p_email;
END$$
DELIMITER ;

-- Get requests by resident ID
DELIMITER $$
CREATE PROCEDURE `getResidentRequests` (
    IN p_resident_id INT
)
BEGIN
    SELECT * FROM requests WHERE resident_id = p_resident_id ORDER BY created_at DESC;
END$$
DELIMITER ;

-- ========================================
-- ADMIN PROCEDURES
-- ========================================

-- Login admin
DELIMITER $$
CREATE PROCEDURE `loginAdmin` (
    IN p_email VARCHAR(255)
)
BEGIN
    SELECT * FROM admins WHERE email = p_email;
END$$
DELIMITER ;

-- Get all requests with resident names
DELIMITER $$
CREATE PROCEDURE `getAllRequestsWithResidents` ()
BEGIN
    SELECT r.*, res.full_name
    FROM requests r
    JOIN residents res ON r.resident_id = res.id
    ORDER BY r.created_at DESC;
END$$
DELIMITER ;

-- Update request status
DELIMITER $$
CREATE PROCEDURE `updateRequestStatus` (
    IN p_status VARCHAR(50),
    IN p_request_id INT
)
BEGIN
    UPDATE requests SET status = p_status WHERE id = p_request_id;
END$$
DELIMITER ;

-- ========================================
-- REQUEST PROCEDURES
-- ========================================

-- Create new request
DELIMITER $$
CREATE PROCEDURE `createRequest` (
    IN p_resident_id INT,
    IN p_type VARCHAR(255),
    IN p_details TEXT,
    IN p_id_file VARCHAR(255),
    IN p_residency_file VARCHAR(255)
)
BEGIN
    INSERT INTO requests (resident_id, type, details, id_file, residency_file, status, created_at)
    VALUES (p_resident_id, p_type, p_details, p_id_file, p_residency_file, 'Pending', NOW());
    SELECT LAST_INSERT_ID() AS last_id;
END$$
DELIMITER ;

-- Get requests by resident
DELIMITER $$
CREATE PROCEDURE `getRequestsByResident` (
    IN p_resident_id INT
)
BEGIN
    SELECT * FROM requests WHERE resident_id = p_resident_id ORDER BY id DESC;
END$$
DELIMITER ;

-- Get all requests
DELIMITER $$
CREATE PROCEDURE `getAllRequests` ()
BEGIN
    SELECT r.*, res.full_name
    FROM requests r
    JOIN residents res ON r.resident_id = res.id
    ORDER BY r.id DESC;
END$$
DELIMITER ;

-- ========================================
-- ANNOUNCEMENT PROCEDURES
-- ========================================

-- Get all announcements
DELIMITER $$
CREATE PROCEDURE `getAllAnnouncements` ()
BEGIN
    SELECT * FROM announcements ORDER BY created_at DESC;
END$$
DELIMITER ;


