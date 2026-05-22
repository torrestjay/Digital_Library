-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 17, 2025 at 10:43 PM
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
-- Database: `digital_library`
--
CREATE DATABASE IF NOT EXISTS `digital_library` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `digital_library`;

-- --------------------------------------------------------

--
-- Table structure for table `admin_info`
--

CREATE TABLE `admin_info` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `middle_initial` varchar(10) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `birth_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_info`
--

INSERT INTO `admin_info` (`id`, `user_id`, `last_name`, `first_name`, `middle_initial`, `age`, `contact`, `address`, `gender`, `birth_date`) VALUES
(2, 13, 'Cruz', 'Juan', 'D', 35, '09171234567', '123 Admin St.', 'Male', '1990-05-10'),
(3, 20, 'Rizalda', 'Tj', 'L', 30, '09181234567', '456 Admin Rd.', 'Male', '1995-08-15'),
(4, 21, 'Lopez', 'Ana', 'M', 28, '09191234567', '789 Admin Ave.', 'Female', '1997-03-22'),
(5, 22, 'Smith', 'John', 'B', 42, '09201234567', '234 Main St.', 'Male', '1983-11-05'),
(6, 23, 'Garcia', 'Maria', 'C', 37, '09211234567', '345 Main Rd.', 'Female', '1988-07-09'),
(7, 24, 'Lee', 'Kevin', 'R', 29, '09221234567', '567 East Ave.', 'Male', '1996-01-19'),
(8, 25, 'Kim', 'Jisoo', 'Y', 32, '09231234567', '101 West Blvd.', 'Female', '1993-06-17'),
(9, 26, 'Park', 'Min', 'K', 40, '09241234567', '202 North Rd.', 'Male', '1985-04-12'),
(10, 27, 'Choi', 'Eun', 'S', 34, '09251234567', '303 South Rd.', 'Female', '1991-09-25'),
(11, 28, 'Reyes', 'Carlos', 'T', 45, '09261234567', '404 Central St.', 'Male', '1980-12-03');

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `views` int(11) DEFAULT 0,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `availability` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`id`, `title`, `author`, `category`, `cover_image`, `views`, `description`, `created_at`, `availability`) VALUES
(24, 'Harry Potter and the Sorcerer\'s Stone', 'J.K. Rowling', 'Fantasy', 'harry.jpg', 34000, 'The first book in the Harry Potter series, introducing the wizarding world and the adventures of Harry Potter.a', '2025-06-12 01:45:47', 1),
(25, 'The Great Gatsby', 'F. Scott Fitzgerald', 'Fiction', 'gatsby.jpg', 15400, 'A classic novel about the American dream and the enigmatic Jay Gatsby.', '2025-06-12 01:45:47', 1),
(26, 'The Night Circus', 'Erin Morgenstern', 'Fantasy', 'night.jpg', 17600, 'A magical competition between two young illusionists at a mysterious circus.', '2025-06-12 01:45:47', 1),
(27, 'Runaway', 'Alice Munro', 'Literary Fiction', 'runaway.jpg', 18500, 'A collection of stories about women and the choices they make in life and love.', '2025-06-12 01:45:47', 2),
(28, 'Emilia: Finding My Forever', 'Enzo & Elianna', 'Romance', 'emilia.jpg', 25000, 'Emilia is a mixture of Enzo and Elianna—strong, fearless, and headstrong in the face of love and adversity.', '2025-06-12 01:45:47', 2),
(29, 'Mickey Mouse Adventures', 'Walt Disney', 'Children', 'mickeymouse.jpg', 22000, 'A delightful collection of Mickey Mouse stories for kids and the young at heart.', '2025-06-12 01:50:58', 2),
(30, 'The Care and Keeping of You', 'Valorie Schaefer', 'Health', 'thecare.jpg', 14500, 'A popular guide for young girls on understanding and caring for their bodies.', '2025-06-12 01:50:58', 1),
(31, 'The Secret', 'Rhonda Byrne', 'Self-help', 'thesecret.jpg', 48000, 'A book about the law of attraction and the power of positive thinking.', '2025-06-12 01:50:58', 1),
(32, 'The Police Car Adventure', 'Jane Smith', 'Children', 'thepolicecar.jpg', 9500, 'A thrilling children\'s story following a brave little police car on a mission.', '2025-06-12 01:50:58', 1),
(33, 'Imperfect', 'Colleen Hoover', 'Romance', 'imperfect.jpg', 20000, 'A moving love story that explores pain, healing, and second chances.', '2025-06-12 01:50:58', 1),
(34, 'Make Your Bed', 'William H. McRaven', 'Motivational', 'make.jpg', 30000, 'Simple life lessons from a Navy SEAL that can change your life and maybe the world.', '2025-06-12 01:50:58', 1),
(38, 'Jas and Jud', 'Jaspher Baldicanas', 'Romance', 'WIN_20250614_10_18_05_Pro.jpg', 0, 'Jas and Jud sitting on a tree K - I - S - S - I - N - G', '2025-06-15 17:02:54', 1),
(39, 'Harrypotta', 'jk', 'Sci-fi', 'localhost_8012_JobConnext_JobConnext%20-%20Official_GuessPortal_LandingPage.php.png', 0, 'Maganda', '2025-06-17 16:36:24', 1),
(40, 'harry', 'jk', 'fantasy', '874cf704a2e723eb64ae776bf948281a.jpg', 0, 'maganda', '2025-06-17 16:37:45', 1),
(41, 'harry', 'jk', 'fantasy', '874cf704a2e723eb64ae776bf948281a.jpg', 0, 'magandaaa', '2025-06-17 16:39:07', 1),
(42, 'harry roque', 'jk', 'fantasy', '68519c96099db.jpg', 0, 'maganda', '2025-06-17 16:49:26', 1),
(43, 'ASD', 'sdf', 'Sci-fi', '6851a0cb70881.jpg', 0, 'asd', '2025-06-17 17:07:23', 1),
(44, 'hahha', 'okay', 'whaha', '6851cc62d6473.jpg', 0, 'oonA', '2025-06-17 20:13:22', 1);

-- --------------------------------------------------------

--
-- Table structure for table `borrowed_books`
--

CREATE TABLE `borrowed_books` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `borrow_date` date NOT NULL,
  `due_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `status` enum('pending','borrowed','rejected','returned') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `borrowed_books`
--

INSERT INTO `borrowed_books` (`id`, `user_id`, `book_id`, `borrow_date`, `due_date`, `return_date`, `status`) VALUES
(19, 23, 34, '2025-06-17', '2025-06-24', '2025-06-18', 'borrowed'),
(20, 23, 26, '2025-06-17', '2025-06-24', '2025-06-18', 'pending'),
(21, 23, 27, '2025-06-17', '2025-06-24', '2025-06-18', 'pending'),
(22, 23, 28, '2025-06-17', '2025-06-24', '2025-06-18', 'pending'),
(23, 23, 29, '2025-06-17', '2025-06-24', '2025-06-18', 'pending'),
(24, 23, 30, '2025-06-17', '2025-06-24', NULL, 'pending'),
(31, 23, 33, '2025-06-17', '2025-06-24', NULL, 'pending'),
(32, 23, 38, '2025-06-17', '2025-06-24', NULL, 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `fantasy`
--

CREATE TABLE `fantasy` (
  `book_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `publication_date` date DEFAULT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `availability_status` varchar(50) DEFAULT NULL,
  `location_library` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fantasy`
--

INSERT INTO `fantasy` (`book_id`, `title`, `author`, `publication_date`, `isbn`, `description`, `availability_status`, `location_library`) VALUES
(1, 'The Dragon\'s Heir', 'Mira Khan', '2023-03-15', '9781234567890', 'A young prince discovers his destiny among dragons.', 'Available', 'Fantasy Shelf A1'),
(2, 'Mystic Realms', 'L. Fenn', '2022-10-12', '9781234567891', 'A journey across hidden worlds filled with magic.', 'Available', 'Fantasy Shelf A2'),
(3, 'Shadowlight', 'J. Arden', '2024-01-22', '9781234567892', 'A war between light and shadow threatens reality.', 'Available', 'Fantasy Shelf A3'),
(4, 'The Crystal Key', 'Emma Gold', '2021-07-01', '9781234567893', 'A magical key opens the path to ancient power.', 'Available', 'Fantasy Shelf A4'),
(5, 'Winds of Elara', 'H. Storm', '2020-12-25', '9781234567894', 'Elara’s winds hide secrets of the old gods.', 'Borrowed', 'Fantasy Shelf B1'),
(6, 'Grimoire of Fate', 'A. Thorn', '2023-06-11', '9781234567895', 'A magical book decides the fate of the world.', 'Available', 'Fantasy Shelf B2'),
(7, 'Legend of the Phoenix', 'R. Blaze', '2019-05-08', '9781234567896', 'A reborn warrior returns to right past wrongs.', 'Borrowed', 'Fantasy Shelf C1'),
(8, 'Eldermoon', 'T. Rook', '2022-08-20', '9781234567897', 'The moon holds secrets that could change everything.', 'Available', 'Fantasy Shelf C2'),
(9, 'Sorcery & Silk', 'M. Chen', '2021-11-14', '9781234567898', 'A tailor discovers a thread that weaves magic.', 'Available', 'Fantasy Shelf D1'),
(10, 'Hearts of Iron', 'K. Wells', '2023-09-03', '9781234567899', 'Magical armor and betrayal shape a kingdom’s future.', 'Borrowed', 'Fantasy Shelf D2');

-- --------------------------------------------------------

--
-- Table structure for table `featured_books`
--

CREATE TABLE `featured_books` (
  `id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `featured_position` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `featured_books`
--

INSERT INTO `featured_books` (`id`, `book_id`, `featured_position`, `created_at`) VALUES
(4, 24, 'homepage', '2025-06-12 16:46:42');

-- --------------------------------------------------------

--
-- Table structure for table `horror`
--

CREATE TABLE `horror` (
  `book_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `publication_date` date DEFAULT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `availability_status` varchar(50) DEFAULT NULL,
  `location_library` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `horror`
--

INSERT INTO `horror` (`book_id`, `title`, `author`, `publication_date`, `isbn`, `description`, `availability_status`, `location_library`) VALUES
(1, 'Whispers in the Walls', 'L. Morgan', '2021-10-13', '9782345678901', 'Voices echo in the dark halls of an abandoned asylum.', 'Available', 'Horror Shelf A1'),
(2, 'The Hollow Eyes', 'R. Smith', '2022-01-01', '9782345678902', 'Eyes that never sleep haunt a cursed family.', 'Borrowed', 'Horror Shelf A2'),
(3, 'The Red Room', 'K. Hart', '2020-09-23', '9782345678903', 'An artist’s new studio reveals a murderous past.', 'Available', 'Horror Shelf B1'),
(4, 'Chill', 'M. Snow', '2023-05-06', '9782345678904', 'A winter cabin turns into a nightmare vacation.', 'Available', 'Horror Shelf B2'),
(5, 'Behind the Mirror', 'N. James', '2019-04-17', '9782345678905', 'Reflections show something more than just you.', 'Available', 'Horror Shelf C1'),
(6, 'The Silence Below', 'E. Vaughn', '2022-11-30', '9782345678906', 'A town’s underground tunnel system hides evil.', 'Borrowed', 'Horror Shelf C2'),
(7, 'Harvest', 'J. Kroll', '2021-08-08', '9782345678907', 'Every harvest season, someone disappears.', 'Available', 'Horror Shelf D1'),
(8, 'Flesh and Fog', 'D. Wren', '2023-07-19', '9782345678908', 'The fog brings something alive...', 'Available', 'Horror Shelf D2'),
(9, 'Dead Air', 'S. Lee', '2020-06-01', '9782345678909', 'A haunted radio station transmits terror.', 'Available', 'Horror Shelf E1'),
(10, 'Eyes in the Woods', 'A. Holloway', '2024-02-28', '9782345678910', 'The forest watches... and remembers.', 'Available', 'Horror Shelf E2');

-- --------------------------------------------------------

--
-- Table structure for table `reading_history`
--

CREATE TABLE `reading_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `read_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `romance`
--

CREATE TABLE `romance` (
  `book_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `publication_date` date DEFAULT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `availability_status` varchar(50) DEFAULT NULL,
  `location_library` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `romance`
--

INSERT INTO `romance` (`book_id`, `title`, `author`, `publication_date`, `isbn`, `description`, `availability_status`, `location_library`) VALUES
(1, 'Love in Bloom', 'Emma Chase', '2021-03-14', '9783456789012', 'Two hearts meet among cherry blossoms.', 'Available', 'Romance Shelf A1'),
(2, 'Midnight Letters', 'A. Moon', '2020-06-10', '9783456789013', 'Secret letters reignite a long-lost love.', 'Borrowed', 'Romance Shelf A2'),
(3, 'A Second Chance', 'D. Heart', '2023-11-22', '9783456789014', 'Old lovers reunite under unexpected circumstances.', 'Available', 'Romance Shelf B1'),
(4, 'The Cafe on 9th', 'T. Clark', '2022-02-01', '9783456789015', 'Love brews strong in a small-town cafe.', 'Available', 'Romance Shelf B2'),
(5, 'The Promise Ring', 'J. Star', '2019-07-30', '9783456789016', 'A ring, a promise, and a destiny fulfilled.', 'Available', 'Romance Shelf C1'),
(6, 'After Sunset', 'H. Rose', '2024-01-12', '9783456789017', 'Beachside love finds new beginnings.', 'Available', 'Romance Shelf C2'),
(7, 'Hearts Apart', 'R. Vale', '2020-10-10', '9783456789018', 'Distance tests the strength of two hearts.', 'Borrowed', 'Romance Shelf D1'),
(8, 'Dancing Rain', 'F. Bell', '2021-12-24', '9783456789019', 'A dance in the rain changes everything.', 'Available', 'Romance Shelf D2'),
(9, 'Letters to Lila', 'E. Wright', '2022-08-19', '9783456789020', 'A lifetime of letters leads to a confession.', 'Available', 'Romance Shelf E1'),
(10, 'Call Me Home', 'K. Hope', '2023-05-15', '9783456789021', 'He leaves, she stays. Will love survive?', 'Available', 'Romance Shelf E2');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','admin') DEFAULT 'student',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Juan Dela Cruz', 'juan@example.com', '123456', 'student', '2025-06-11 16:31:48'),
(2, 'Tj', 'torrestj62@gmail.com', 'tjay123', 'student', '2025-06-11 10:29:08'),
(3, 'ayoko', 'anghirapnaman@gmail.com', 'haynaku123', 'student', '2025-06-11 10:43:17'),
(4, 'tj', 'tjay@gmail.com', 'tjay123', 'student', '2025-06-11 10:54:26'),
(7, 'Tj', 'torrestj@gmail.com', '1234', 'student', '2025-06-11 15:09:07'),
(8, 'tj', 'tj12@gmail.com', '12345', 'student', '2025-06-11 15:10:10'),
(9, 'Tj', 'tjay13@gmail.com', '1234', 'student', '2025-06-11 15:21:59'),
(10, 'tj', 'torrestjay12', '12345', 'student', '2025-06-14 02:31:59'),
(11, 'Teodoro', 'Teodoro12@gmail.com', '12345', 'student', '2025-06-14 02:37:21'),
(12, 'Daniel', 'Daniel@gmail.com', 'dj1234', 'student', '2025-06-15 02:18:22'),
(13, 'Admin User', 'admin@example.com', 'admin123', 'admin', '2025-06-15 02:33:50'),
(14, 'Student User', 'student@example.com', 'student123', 'student', '2025-06-15 02:33:50'),
(15, 'Tj', 'torres@gmail.com', '12345', 'student', '2025-06-15 02:36:29'),
(16, 'Teodoro', 'Teodoro@gmail.com', '12345', 'student', '2025-06-15 02:38:03'),
(17, 'john jaspher', 'jaspherbaldicanas@gmail.com', 'asdasdasd', 'student', '2025-06-15 09:37:12'),
(18, 'TJ', 'tj123@gmail.com', '12345', 'student', '2025-06-15 09:45:23'),
(19, 'tj', 'tj124@gmail.com', '12345', 'student', '2025-06-15 14:27:09'),
(20, 'Tj rizalda', 'admin@gmail.com', '$2y$10$hwa9s7mBWILyc41PeCgvBudTt.syMBLkDvLRtwRIfCA7.T19xqjYi', 'admin', '2025-06-15 14:30:42'),
(21, 'asdsdasd', 'haha@gmail.com', 'Ok@yp0oo', 'student', '2025-06-17 13:37:21'),
(22, 'aksjdhasd', 'hahaha@gmail.com', 'Ok@yp0oo', 'student', '2025-06-17 13:43:03'),
(23, 'okayokay', 'okayokay@gmail.com', '$2y$10$Vi95gUt/pMgPQqxKT5aUqOhT1QTVvlobuOsqgqa.PhDPaosf2WKm2', 'student', '2025-06-17 13:46:53'),
(24, 'Meko@_Neko123', 'Meko@Neko010', '$2y$10$pkDDbSDKNxFGNRjPnnDuEuCo01/rqxMIjTzZbsr7lbB1eAL8duuAm', 'student', '2025-06-17 14:31:35'),
(25, 'haha', 'okay@gmail.com', '$2y$10$v8hzFGY.WxMz4MJS41wq/eI05aySotEeiDboKDPqNz7GXXdp3DOne', 'student', '2025-06-17 20:18:05');

-- --------------------------------------------------------

--
-- Table structure for table `user_information`
--

CREATE TABLE `user_information` (
  `id` int(55) NOT NULL,
  `user_id` int(55) NOT NULL,
  `name` varchar(55) NOT NULL,
  `lastname` varchar(55) NOT NULL,
  `age` int(100) NOT NULL,
  `contact_number` int(100) NOT NULL,
  `address` text NOT NULL,
  `gender` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_information`
--

INSERT INTO `user_information` (`id`, `user_id`, `name`, `lastname`, `age`, `contact_number`, `address`, `gender`) VALUES
(0, 1, 'Juan', 'Dela Cruz', 20, 912345678, '123 Sampaloc, Manila', 'Male'),
(0, 2, 'Tj', 'Torres', 21, 912345679, '456 Makati, Metro Manila', 'Male'),
(0, 3, 'Ayoko', 'Naman', 22, 912345680, '789 Pasig City', 'Female'),
(0, 4, 'Tj', 'Reyes', 23, 912345681, 'Pateros, Metro Manila', 'Male'),
(0, 7, 'Tj', 'Garcia', 24, 912345682, 'Las Piñas, Metro Manila', 'Male'),
(0, 8, 'Tj', 'Tan', 25, 912345683, 'Bacoor, Cavite', 'Male'),
(0, 9, 'Tj', 'De Vera', 19, 912345684, 'San Juan, Manila', 'Male'),
(0, 10, 'Tj', 'Salvador', 20, 912345685, 'Malabon City', 'Male'),
(0, 11, 'Teodoro', 'Santos', 21, 912345686, 'Quezon City', 'Male'),
(0, 12, 'Daniel', 'Silva', 22, 912345687, 'Taguig City', 'Male');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_info`
--
ALTER TABLE `admin_info`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `borrowed_books`
--
ALTER TABLE `borrowed_books`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `fantasy`
--
ALTER TABLE `fantasy`
  ADD PRIMARY KEY (`book_id`);

--
-- Indexes for table `featured_books`
--
ALTER TABLE `featured_books`
  ADD PRIMARY KEY (`id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `horror`
--
ALTER TABLE `horror`
  ADD PRIMARY KEY (`book_id`);

--
-- Indexes for table `reading_history`
--
ALTER TABLE `reading_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `romance`
--
ALTER TABLE `romance`
  ADD PRIMARY KEY (`book_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_info`
--
ALTER TABLE `admin_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `borrowed_books`
--
ALTER TABLE `borrowed_books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `fantasy`
--
ALTER TABLE `fantasy`
  MODIFY `book_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `featured_books`
--
ALTER TABLE `featured_books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `horror`
--
ALTER TABLE `horror`
  MODIFY `book_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `reading_history`
--
ALTER TABLE `reading_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `romance`
--
ALTER TABLE `romance`
  MODIFY `book_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
