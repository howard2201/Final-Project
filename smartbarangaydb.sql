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

-- attendance
CREATE TABLE attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  time_in DATETIME DEFAULT NULL,
  time_out DATETIME DEFAULT NULL
);

INSERT INTO attendance (name, time_in, time_out) VALUES
('John Doe', '2025-11-07 08:00:00', '2025-11-07 17:00:00'),
('Jane Smith', '2025-11-07 09:30:00', '2025-11-07 18:30:00'),
('Bob Johnson', '2025-11-07 10:15:00', '2025-11-07 19:15:00');

--
-- Dumping data for table `admins`
-- Default admin account: email: admin@gmail.com, password: admin12345
-- Password is hashed using SHA-256
--

INSERT INTO `admins` (`id`, `full_name`, `email`, `password`, `created_at`) VALUES
(1, 'Admin', 'admin@gmail.com', '41e5653fc7aeb894026d6bb7b2db7f65902b454945fa8fd65a6327047b5277fb', '2024-01-01 00:00:00');

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
  `approval_status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `rejection_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `residents` and 'admins`
-- Sample resident password: resident123 (hashed using SHA-256)
--

INSERT INTO `residents` (`id`, `full_name`, `email`, `resident_password`, `id_file`, `proof_file`, `approval_status`, `created_at`) VALUES
(1, 'Kevin Baniel R. Guieb', 'Nog@gmail.com', '6b3a55e0261b0304143f805a24924d0c1c44524821305f31d9277843b8a10f4e', 'Screenshot 2025-11-05 134604.png', 'Screenshot 2025-11-05 134604.png', 'Approved', '2025-11-05 15:09:41');

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

-- Get all pending residents for admin approval
DELIMITER $$
CREATE PROCEDURE `getPendingResidents` ()
BEGIN
    SELECT id, full_name, email, id_file, proof_file, created_at, approval_status
    FROM residents
    WHERE approval_status = 'Pending'
    ORDER BY created_at DESC;
END$$
DELIMITER ;

-- Update resident approval status
DELIMITER $$
CREATE PROCEDURE `updateResidentApprovalStatus` (
    IN p_resident_id INT,
    IN p_status VARCHAR(50)
)
BEGIN
    IF p_status = 'Rejected' THEN
        UPDATE residents
        SET approval_status = p_status, rejection_date = NOW()
        WHERE id = p_resident_id;
    ELSE
        UPDATE residents
        SET approval_status = p_status, rejection_date = NULL
        WHERE id = p_resident_id;
    END IF;
END$$
DELIMITER ;

-- Get all residents with their approval status
DELIMITER $$
CREATE PROCEDURE `getAllResidentsWithStatus` ()
BEGIN
    SELECT id, full_name, email, approval_status, created_at
    FROM residents
    ORDER BY created_at DESC;
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

-- Create new announcement
DELIMITER $$
CREATE PROCEDURE `createAnnouncement` (
    IN p_title VARCHAR(255),
    IN p_body TEXT
)
BEGIN
    INSERT INTO announcements (title, body, created_at)
    VALUES (p_title, p_body, NOW());
    SELECT LAST_INSERT_ID() AS last_id;
END$$
DELIMITER ;

-- Delete announcement
DELIMITER $$
CREATE PROCEDURE `deleteAnnouncement` (
    IN p_announcement_id INT
)
BEGIN
    DELETE FROM announcements WHERE id = p_announcement_id;
END$$
DELIMITER ;

-- Get rejected residents with deletion countdown
DELIMITER $$
CREATE PROCEDURE `getRejectedResidentsForCleanup` ()
BEGIN
    SELECT id, full_name, email, rejection_date,
           DATEDIFF(DATE_ADD(rejection_date, INTERVAL 10 DAY), NOW()) AS days_remaining
    FROM residents
    WHERE approval_status = 'Rejected'
    AND rejection_date IS NOT NULL
    AND DATEDIFF(NOW(), rejection_date) >= 10;
END$$
DELIMITER ;

-- Delete rejected residents after 10 days
DELIMITER $$
CREATE PROCEDURE `deleteExpiredRejectedResidents` ()
BEGIN
    DELETE FROM residents
    WHERE approval_status = 'Rejected'
    AND rejection_date IS NOT NULL
    AND DATEDIFF(NOW(), rejection_date) >= 10;
END$$
DELIMITER ;

-- Get resident by ID
DELIMITER $$
CREATE PROCEDURE `getResidentById` (
    IN p_resident_id INT
)
BEGIN
    SELECT id, full_name, email, approval_status, rejection_date, created_at
    FROM residents
    WHERE id = p_resident_id;
END$$
DELIMITER ;

-- Get admin by ID
DELIMITER $$
CREATE PROCEDURE `getAdminById` (
    IN p_admin_id INT
)
BEGIN
    SELECT id, full_name, email
    FROM admins
    WHERE id = p_admin_id;
END$$
DELIMITER ;

-- Get resident file
DELIMITER $$
CREATE PROCEDURE `getResidentFile` (
    IN p_resident_id INT,
    IN p_file_type VARCHAR(10)
)
BEGIN
    IF p_file_type = 'id' THEN
        SELECT id_file, full_name FROM residents WHERE id = p_resident_id;
    ELSE
        SELECT proof_file, full_name FROM residents WHERE id = p_resident_id;
    END IF;
END$$
DELIMITER ;

-- ========================================
-- ATTENDANCE PROCEDURES
-- ========================================

-- Get last attendance record for a person
DELIMITER $$
CREATE PROCEDURE `getLastAttendance` (
    IN p_name VARCHAR(255)
)
BEGIN
    SELECT id, time_in, time_out
    FROM attendance
    WHERE name = p_name
    ORDER BY id DESC
    LIMIT 1;
END$$
DELIMITER ;

-- Create new attendance check-in
DELIMITER $$
CREATE PROCEDURE `createAttendanceCheckIn` (
    IN p_name VARCHAR(255),
    IN p_time_in DATETIME
)
BEGIN
    INSERT INTO attendance (name, time_in)
    VALUES (p_name, p_time_in);
END$$
DELIMITER ;

-- Update attendance check-out
DELIMITER $$
CREATE PROCEDURE `updateAttendanceCheckOut` (
    IN p_attendance_id INT,
    IN p_time_out DATETIME
)
BEGIN
    UPDATE attendance
    SET time_out = p_time_out
    WHERE id = p_attendance_id;
END$$
DELIMITER ;


