-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th5 25, 2026 lúc 09:49 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `ielts_web`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chat_ai`
--

CREATE TABLE `chat_ai` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `response` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `chat_ai`
--

INSERT INTO `chat_ai` (`id`, `user_id`, `message`, `response`, `created_at`) VALUES
(1, 1, 'Cách học từ vựng IELTS hiệu quả?', 'Bạn nên học theo chủ đề và ôn lại mỗi ngày.', '2026-04-17 15:13:02'),
(2, 1, 'Band 6 cần làm gì?', 'Bạn cần đạt khoảng 5.5-6.5 ở mỗi kỹ năng.', '2026-04-17 15:13:02'),
(3, 2, 'Làm sao cải thiện Listening?', 'Hãy nghe podcast và luyện đề Cambridge mỗi ngày.', '2026-04-17 15:13:02'),
(4, 2, 'Speaking nên luyện thế nào?', 'Bạn nên luyện nói mỗi ngày và ghi âm lại để cải thiện.', '2026-04-17 15:13:02');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `reply_message` longtext DEFAULT NULL,
  `replied_at` datetime DEFAULT NULL,
  `user_seen_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `subject`, `message`, `is_read`, `reply_message`, `replied_at`, `user_seen_at`, `created_at`) VALUES
(1, 'An', 'an@gmail.com', 'Web chạy rất mượt ạ', 'ManifestdacnA', 0, 'oke ạ', '2026-05-24 20:49:57', '2026-05-24 20:54:29', '2026-05-10 16:02:14'),
(2, 'An', 'an@gmail.com', 'Web chạy rất mượt ạ', 'kkk', 0, NULL, NULL, NULL, '2026-05-12 08:26:03');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `price` int(11) DEFAULT NULL,
  `students` int(11) DEFAULT NULL,
  `skill_id` int(11) DEFAULT NULL,
  `band` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `courses`
--

INSERT INTO `courses` (`id`, `title`, `description`, `image`, `price`, `students`, `skill_id`, `band`) VALUES
(1, 'Listening cơ bản', 'Luyện nghe từ cơ bản → nâng cao', 'course-1.jpg', 500000, 120, 1, '0-3.5'),
(2, 'Reading chiến thuật', 'Luyện đọc hiểu chiến thuật', 'course-2.jpg', 550000, 80, 2, '3.5-4.5'),
(3, 'Writing chi tiết', 'Task 1 & Task 2 chi tiết', 'course-3.jpg', 650000, 95, 3, '4.5-5.5'),
(4, 'Speaking AI', 'Luyện nói cùng AI', 'my-speaking.jpg', 600000, 60, 4, '5.5-6.0');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `practices`
--

CREATE TABLE `practices` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `skill_id` int(11) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `level` varchar(20) DEFAULT NULL,
  `source` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `practices`
--

INSERT INTO `practices` (`id`, `title`, `skill_id`, `type`, `created_at`, `level`, `source`) VALUES
(1, 'Từ vựng cơ bản', 1, 'vocabulary', '2026-04-17 14:56:49', 'beginner', 'British Council - LearnEnglish'),
(2, 'Nghe số cơ bản', 1, 'listening', '2026-04-17 14:56:49', 'beginner', 'British Council - LearnEnglish'),
(3, 'Đọc câu đơn', 2, 'reading', '2026-04-17 14:56:49', 'beginner', 'British Council - LearnEnglish'),
(4, 'Ngữ pháp hiện tại đơn', 4, 'grammar', '2026-04-17 14:56:49', 'beginner', 'British Council - LearnEnglish');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `practice_questions`
--

CREATE TABLE `practice_questions` (
  `id` int(11) NOT NULL,
  `practice_id` int(11) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `option_a` varchar(255) DEFAULT NULL,
  `option_b` varchar(255) DEFAULT NULL,
  `option_c` varchar(255) DEFAULT NULL,
  `option_d` varchar(255) DEFAULT NULL,
  `correct_answer` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `practice_results`
--

CREATE TABLE `practice_results` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `practice_id` int(11) DEFAULT NULL,
  `score` float DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `skill` varchar(50) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `option_a` varchar(255) DEFAULT NULL,
  `option_b` varchar(255) DEFAULT NULL,
  `option_c` varchar(255) DEFAULT NULL,
  `option_d` varchar(255) DEFAULT NULL,
  `correct_answer` char(1) DEFAULT NULL,
  `level` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `source` varchar(255) DEFAULT NULL,
  `audio` varchar(255) DEFAULT NULL,
  `passage` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `questions`
--

INSERT INTO `questions` (`id`, `skill`, `content`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_answer`, `level`, `created_at`, `source`, `audio`, `passage`) VALUES
(1, '1', 'What time does the train leave?', '7:30', '8:00', '8:30', '9:00', 'B', 'beginner', '2026-04-26 14:21:53', 'British Council - Listening A1', NULL, NULL),
(2, '2', 'What is the main idea of the passage?', 'Travel tips', 'Healthy food', 'Daily routine', 'School life', 'C', 'beginner', '2026-04-26 14:22:04', 'British Council - Reading A1', NULL, 'Tom is a student. He goes to school every day. He likes reading books and playing football after school.'),
(3, '4', 'She ___ to school every day.', 'go', 'goes', 'going', 'gone', 'B', 'beginner', '2026-04-26 14:22:13', 'British Council - Grammar A1', NULL, NULL),
(4, '4', 'She ___ to school every day.', 'go', 'goes', 'going', 'gone', 'B', 'beginner', '2026-04-26 14:23:08', 'British Council - Grammar A1', NULL, NULL),
(5, '3', 'How do you introduce yourself?', 'My name is Anna', 'I am fine thank you', 'I like pizza', 'Goodbye', 'A', 'beginner', '2026-04-26 14:23:19', 'British Council - Speaking A1', NULL, NULL),
(6, '1', 'What time does the bus leave?', '7:00', '7:30', '8:00', '8:30', 'B', 'beginner', '2026-04-26 14:24:39', 'British Council', NULL, NULL),
(7, '1', 'What number do you hear: fifteen?', '50', '15', '14', '16', 'B', 'beginner', '2026-04-26 14:24:39', 'British Council', NULL, NULL),
(8, '1', 'Where is the man going?', 'School', 'Office', 'Park', 'Home', 'A', 'beginner', '2026-04-26 14:24:39', 'British Council', NULL, NULL),
(9, '2', 'Choose the correct sentence:', 'She go to school', 'She goes to school', 'She going to school', 'She gone to school', 'B', 'beginner', '2026-04-26 14:24:39', 'British Council', NULL, 'Tom is a student. He goes to school every day. He likes reading books and playing football after school.'),
(10, '2', 'The cat is ___ the table.', 'on', 'in', 'at', 'to', 'A', 'beginner', '2026-04-26 14:24:39', 'British Council', NULL, 'Tom is a student. He goes to school every day. He likes reading books and playing football after school.'),
(11, '2', 'What is the opposite of \"big\"?', 'small', 'tall', 'long', 'fast', 'A', 'beginner', '2026-04-26 14:24:39', 'British Council', NULL, 'Tom is a student. He goes to school every day. He likes reading books and playing football after school.'),
(12, '3', 'How do you introduce yourself?', 'My name is John', 'I am fine', 'I like food', 'Goodbye', 'A', 'beginner', '2026-04-26 14:24:39', 'British Council', NULL, NULL),
(13, '3', 'What do you say when you meet someone?', 'Hello', 'Bye', 'Sleep', 'Eat', 'A', 'beginner', '2026-04-26 14:24:39', 'British Council', NULL, NULL),
(14, '3', 'How do you ask about age?', 'How old are you?', 'What is your name?', 'Where are you?', 'What do you do?', 'A', 'beginner', '2026-04-26 14:24:39', 'British Council', NULL, NULL),
(15, '4', 'She ___ to school every day.', 'go', 'goes', 'going', 'gone', 'B', 'beginner', '2026-04-26 14:24:39', 'British Council', NULL, NULL),
(16, '4', 'They ___ football now.', 'play', 'plays', 'are playing', 'played', 'C', 'beginner', '2026-04-26 14:24:39', 'British Council', NULL, NULL),
(17, '4', 'I ___ a student.', 'is', 'am', 'are', 'be', 'B', 'beginner', '2026-04-26 14:24:39', 'British Council', NULL, NULL),
(18, '1', 'What time does the class start?', '7:00', '7:30', '8:00', '8:30', 'C', 'beginner', '2026-04-26 14:26:04', 'British Council', NULL, NULL),
(19, '1', 'What number do you hear: twenty?', '12', '20', '30', '40', 'B', 'beginner', '2026-04-26 14:26:04', 'British Council', NULL, NULL),
(20, '1', 'Where is the woman?', 'School', 'Office', 'Park', 'Shop', 'D', 'beginner', '2026-04-26 14:26:04', 'British Council', NULL, NULL),
(21, '1', 'What day is mentioned?', 'Monday', 'Tuesday', 'Friday', 'Sunday', 'A', 'beginner', '2026-04-26 14:26:04', 'British Council', NULL, NULL),
(22, '1', 'How much is the ticket?', '$5', '$10', '$15', '$20', 'B', 'beginner', '2026-04-26 14:26:04', 'British Council', NULL, NULL),
(23, '1', 'What is the man buying?', 'Book', 'Pen', 'Bag', 'Phone', 'A', 'beginner', '2026-04-26 14:26:04', 'British Council', NULL, NULL),
(24, '1', 'What time is it?', '6:00', '6:30', '7:00', '7:30', 'D', 'beginner', '2026-04-26 14:26:04', 'British Council', NULL, NULL),
(25, '1', 'Where are they going?', 'Beach', 'Mall', 'School', 'Park', 'B', 'beginner', '2026-04-26 14:26:04', 'British Council', NULL, NULL),
(26, '1', 'What color is the bag?', 'Red', 'Blue', 'Green', 'Black', 'A', 'beginner', '2026-04-26 14:26:04', 'British Council', NULL, NULL),
(27, '1', 'What is her job?', 'Teacher', 'Doctor', 'Nurse', 'Driver', 'A', 'beginner', '2026-04-26 14:26:04', 'British Council', NULL, NULL),
(28, '1', 'What number: thirteen?', '30', '13', '14', '31', 'B', 'beginner', '2026-04-26 14:26:04', 'British Council', NULL, NULL),
(29, '1', 'Where is the cat?', 'Under table', 'On table', 'In box', 'Near door', 'B', 'beginner', '2026-04-26 14:26:04', 'British Council', NULL, NULL),
(30, '1', 'What is the weather?', 'Sunny', 'Rainy', 'Cloudy', 'Windy', 'A', 'beginner', '2026-04-26 14:26:04', 'British Council', NULL, NULL),
(31, '1', 'What time does the shop close?', '5', '6', '7', '8', 'C', 'beginner', '2026-04-26 14:26:04', 'British Council', NULL, NULL),
(32, '1', 'What is the price?', '$2', '$3', '$4', '$5', 'D', 'beginner', '2026-04-26 14:26:04', 'British Council', NULL, NULL),
(33, '2', 'She ___ to school.', 'go', 'goes', 'going', 'gone', 'B', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, 'Tom is a student. He goes to school every day. He likes reading books and playing football after school.'),
(34, '2', 'The dog is ___ the table.', 'on', 'in', 'at', 'to', 'A', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, 'Tom is a student. He goes to school every day. He likes reading books and playing football after school.'),
(35, '2', 'Opposite of \"big\"?', 'small', 'long', 'fast', 'tall', 'A', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, 'Tom is a student. He goes to school every day. He likes reading books and playing football after school.'),
(36, '2', 'They ___ football.', 'play', 'plays', 'playing', 'played', 'A', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, 'Tom is a student. He goes to school every day. He likes reading books and playing football after school.'),
(37, '2', 'Choose correct:', 'He eat', 'He eats', 'He eating', 'He eaten', 'B', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, 'Tom is a student. He goes to school every day. He likes reading books and playing football after school.'),
(38, '2', 'The sun ___ in the east.', 'rise', 'rises', 'rising', 'rose', 'B', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, 'Tom is a student. He goes to school every day. He likes reading books and playing football after school.'),
(39, '2', 'I ___ a book.', 'read', 'reads', 'reading', 'readed', 'A', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, 'Tom is a student. He goes to school every day. He likes reading books and playing football after school.'),
(40, '2', 'We ___ English.', 'learn', 'learns', 'learning', 'learned', 'A', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, 'Tom is a student. He goes to school every day. He likes reading books and playing football after school.'),
(41, '2', 'She ___ coffee.', 'drink', 'drinks', 'drinking', 'drank', 'B', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, 'Tom is a student. He goes to school every day. He likes reading books and playing football after school.'),
(42, '2', 'Birds can ___', 'fly', 'swim', 'run', 'jump', 'A', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, 'Tom is a student. He goes to school every day. He likes reading books and playing football after school.'),
(43, '2', 'Fish can ___', 'fly', 'walk', 'swim', 'jump', 'C', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, 'Tom is a student. He goes to school every day. He likes reading books and playing football after school.'),
(44, '2', 'He ___ happy.', 'is', 'are', 'am', 'be', 'A', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, 'Tom is a student. He goes to school every day. He likes reading books and playing football after school.'),
(45, '2', 'They ___ students.', 'is', 'are', 'am', 'be', 'B', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, 'Tom is a student. He goes to school every day. He likes reading books and playing football after school.'),
(46, '2', 'I ___ tired.', 'is', 'are', 'am', 'be', 'C', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, 'Tom is a student. He goes to school every day. He likes reading books and playing football after school.'),
(47, '2', 'We ___ ready.', 'is', 'are', 'am', 'be', 'B', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, 'Tom is a student. He goes to school every day. He likes reading books and playing football after school.'),
(48, '3', 'How do you greet?', 'Hello', 'Bye', 'Sleep', 'Eat', 'A', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, NULL),
(49, '3', 'Introduce yourself', 'My name is Anna', 'I am fine', 'I like food', 'Bye', 'A', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, NULL),
(50, '3', 'Ask age?', 'How old are you?', 'Where are you?', 'What do you do?', 'Why?', 'A', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, NULL),
(51, '3', 'Ask name?', 'What is your name?', 'How are you?', 'Where go?', 'Why?', 'A', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, NULL),
(52, '3', 'Reply greeting', 'I am fine', 'Banana', 'Car', 'Dog', 'A', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, NULL),
(53, '3', 'Say goodbye', 'Goodbye', 'Hello', 'Eat', 'Sleep', 'A', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, NULL),
(54, '3', 'Ask job', 'What do you do?', 'Where are you?', 'How old?', 'Why?', 'A', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, NULL),
(55, '3', 'Ask place', 'Where are you from?', 'How old?', 'Name?', 'Why?', 'A', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, NULL),
(56, '3', 'Agree', 'Yes', 'No', 'Maybe', 'Why', 'A', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, NULL),
(57, '3', 'Disagree', 'No', 'Yes', 'Ok', 'Why', 'A', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, NULL),
(58, '3', 'Thank someone', 'Thank you', 'Sorry', 'Hello', 'Bye', 'A', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, NULL),
(59, '3', 'Apologize', 'Sorry', 'Hello', 'Bye', 'Thanks', 'A', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, NULL),
(60, '3', 'Ask time', 'What time is it?', 'Where?', 'Why?', 'How?', 'A', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, NULL),
(61, '3', 'Ask help', 'Can you help me?', 'Bye', 'Eat', 'Sleep', 'A', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, NULL),
(62, '3', 'Say welcome', 'You are welcome', 'Sorry', 'Hello', 'Bye', 'A', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, NULL),
(63, '4', 'She ___ a student.', 'is', 'are', 'am', 'be', 'A', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, NULL),
(64, '4', 'I ___ a teacher.', 'is', 'are', 'am', 'be', 'C', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, NULL),
(65, '4', 'They ___ playing.', 'is', 'are', 'am', 'be', 'B', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, NULL),
(66, '4', 'He ___ running.', 'is', 'are', 'am', 'be', 'A', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, NULL),
(67, '4', 'We ___ learning.', 'is', 'are', 'am', 'be', 'B', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, NULL),
(68, '4', 'She ___ eating.', 'is', 'are', 'am', 'be', 'A', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, NULL),
(69, '4', 'I ___ going.', 'is', 'are', 'am', 'be', 'C', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, NULL),
(70, '4', 'They ___ happy.', 'is', 'are', 'am', 'be', 'B', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, NULL),
(71, '4', 'He ___ tall.', 'is', 'are', 'am', 'be', 'A', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, NULL),
(72, '4', 'We ___ friends.', 'is', 'are', 'am', 'be', 'B', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, NULL),
(73, '4', 'I ___ tired.', 'is', 'are', 'am', 'be', 'C', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, NULL),
(74, '4', 'She ___ busy.', 'is', 'are', 'am', 'be', 'A', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, NULL),
(75, '4', 'They ___ ready.', 'is', 'are', 'am', 'be', 'B', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, NULL),
(76, '4', 'He ___ strong.', 'is', 'are', 'am', 'be', 'A', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, NULL),
(77, '4', 'We ___ late.', 'is', 'are', 'am', 'be', 'B', 'beginner', '0000-00-00 00:00:00', '2026-04-26 21:26:04', NULL, NULL),
(78, 'listening', 'What number do you hear?', '15', '50', '13', '30', '1', 'beginner', '2026-04-26 14:27:18', NULL, 'audio/listen1.mp3', NULL),
(79, 'listening', 'What is the speaker talking about?', 'Travel', 'Food', 'Study', 'Work', 'S', 'beginner', '2026-04-26 14:27:18', NULL, 'audio/listen2.mp3', NULL),
(80, 'listening', 'Where is the conversation happening?', 'At school', 'At a restaurant', 'At home', 'At airport', 'A', 'beginner', '2026-04-26 14:27:18', NULL, 'audio/listen3.mp3', NULL),
(81, '2', 'What does Tom do after school?', 'He sleeps', 'He plays football', 'He watches TV', 'He eats', 'B', 'beginner', '2026-04-27 03:51:37', NULL, NULL, 'Tom is a student. He goes to school every day. He likes reading books and playing football after school.');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `results`
--

CREATE TABLE `results` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `test_id` int(11) DEFAULT NULL,
  `score` float DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `site_posts`
--

CREATE TABLE `site_posts` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `excerpt` text DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `status` enum('draft','published') NOT NULL DEFAULT 'draft',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `skills`
--

CREATE TABLE `skills` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `skills`
--

INSERT INTO `skills` (`id`, `name`) VALUES
(1, 'Listening'),
(2, 'Reading'),
(3, 'Writing'),
(4, 'Speaking'),
(5, 'Listening'),
(6, 'Reading'),
(7, 'Speaking'),
(8, 'Writing');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `skill_uploads`
--

CREATE TABLE `skill_uploads` (
  `id` int(11) NOT NULL,
  `skill` varchar(50) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `audio_filename` varchar(255) DEFAULT NULL,
  `audio_original_name` varchar(255) DEFAULT NULL,
  `audio_mime` varchar(100) DEFAULT NULL,
  `audio_size` int(11) DEFAULT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `mime` varchar(100) DEFAULT NULL,
  `size` int(11) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `skill_uploads`
--

INSERT INTO `skill_uploads` (`id`, `skill`, `title`, `description`, `filename`, `audio_filename`, `audio_original_name`, `audio_mime`, `audio_size`, `original_name`, `mime`, `size`, `uploaded_by`, `created_at`) VALUES
(1, 'reading', 'Reading B1 - A Healthy Lifestyle', 'Practice reading test about health and daily habits', '20260524_165744_8547832233d4_reading_b1_healthy_lifestyle.json', NULL, NULL, NULL, NULL, 'reading_b1_healthy_lifestyle.json', 'application/json', 2014, 13, '2026-05-24 21:57:44'),
(2, 'reading', 'Reading B1 - The London Eye', 'Diagnostic reading test for B1 level', '20260524_165946_feefeb91ee14_reading_b1_london_eye.json', NULL, NULL, NULL, NULL, 'reading_b1_london_eye.json', 'application/json', 1764, 13, '2026-05-24 21:59:46'),
(4, 'writing', 'Writing Task 1 - Email to a Friend', 'Write an informal email about your holiday.', '20260525_091059_6f3c88c34223_writing_task1_email_friend.json', NULL, NULL, NULL, NULL, 'writing_task1_email_friend.json', 'application/json', 412, 1, '2026-05-25 14:10:59'),
(5, 'speaking', 'Speaking B1 - Personal Information', 'Speaking practice about personal information and daily life.', '20260525_094802_8a1dc8f44f40_speaking_b1_personal_info.json', NULL, NULL, NULL, NULL, 'speaking_b1_personal_info.json', 'application/json', 696, 1, '2026-05-25 14:48:02');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `speaking_submissions`
--

CREATE TABLE `speaking_submissions` (
  `id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `answer_text` text DEFAULT NULL,
  `audio_filename` varchar(255) DEFAULT NULL,
  `audio_original_name` varchar(255) DEFAULT NULL,
  `audio_mime` varchar(100) DEFAULT NULL,
  `audio_size` int(11) DEFAULT NULL,
  `score` decimal(4,2) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `status` enum('submitted','graded') DEFAULT 'submitted',
  `created_at` datetime DEFAULT current_timestamp(),
  `submitted_at` datetime DEFAULT current_timestamp(),
  `graded_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `student_questions`
--

CREATE TABLE `student_questions` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `question_content` text NOT NULL,
  `answer_content` text DEFAULT NULL,
  `status` enum('pending','answered') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `answered_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Đang đổ dữ liệu cho bảng `student_questions`
--

INSERT INTO `student_questions` (`id`, `student_id`, `teacher_id`, `title`, `question_content`, `answer_content`, `status`, `created_at`, `answered_at`) VALUES
(1, 2, 13, 'Em chưa hiểu bài Reading', 'Cô ơi em chưa hiểu cách chọn đáp án đúng trong bài Reading ạ.', NULL, 'pending', '2026-05-25 12:23:23', NULL),
(2, 8, 13, 'Thắc mắc bài Listening', 'Em nghe không kịp phần điền từ thì nên luyện như thế nào ạ?', NULL, 'pending', '2026-05-25 12:23:23', NULL),
(3, 9, 13, 'Hỏi về Writing Task 1', 'Cô cho em hỏi mở bài Writing Task 1 nên paraphrase như thế nào ạ?', 'Em nên đổi cấu trúc câu và thay từ đồng nghĩa, không copy y nguyên đề bài nhé.', 'answered', '2026-05-25 12:23:23', '2026-05-25 12:23:44');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `study_sessions`
--

CREATE TABLE `study_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `skill` varchar(32) NOT NULL,
  `activity_type` varchar(64) NOT NULL,
  `score` int(11) DEFAULT NULL,
  `max_score` int(11) DEFAULT NULL,
  `band_score` decimal(3,1) DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `study_sessions`
--

INSERT INTO `study_sessions` (`id`, `user_id`, `skill`, `activity_type`, `score`, `max_score`, `band_score`, `duration_minutes`, `created_at`) VALUES
(1, 1, 'reading', 'diagnostic_test', 2, 5, 4.0, 20, '2026-05-11 01:46:02'),
(2, 15, 'reading', 'diagnostic_test', 1, 5, 2.0, 20, '2026-05-12 15:22:53'),
(3, 1, 'reading', 'diagnostic_test', 1, 5, 2.0, 20, '2026-05-12 16:18:32'),
(4, 2, 'reading', 'json_test', 2, 5, 4.5, 20, '2026-05-24 21:58:29'),
(5, 2, 'reading', 'json_test', 2, 5, 4.5, 20, '2026-05-24 22:00:16'),
(6, 2, 'reading', 'reading_test', 1, 5, 3.0, 20, '2026-05-25 14:30:21');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `teacher_students`
--

CREATE TABLE `teacher_students` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Đang đổ dữ liệu cho bảng `teacher_students`
--

INSERT INTO `teacher_students` (`id`, `teacher_id`, `student_id`, `created_at`) VALUES
(1, 13, 2, '2026-05-25 12:23:07'),
(2, 13, 8, '2026-05-25 12:23:07'),
(3, 13, 9, '2026-05-25 12:23:07'),
(4, 13, 10, '2026-05-25 12:23:07'),
(5, 13, 15, '2026-05-25 12:23:07');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tests`
--

CREATE TABLE `tests` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `skill` varchar(50) DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `test_attempts`
--

CREATE TABLE `test_attempts` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `skill` varchar(50) NOT NULL,
  `test_id` int(11) NOT NULL,
  `test_title` varchar(255) NOT NULL,
  `score` int(11) NOT NULL DEFAULT 0,
  `total_questions` int(11) NOT NULL DEFAULT 0,
  `band_score` decimal(4,2) DEFAULT NULL,
  `submitted_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Đang đổ dữ liệu cho bảng `test_attempts`
--

INSERT INTO `test_attempts` (`id`, `student_id`, `skill`, `test_id`, `test_title`, `score`, `total_questions`, `band_score`, `submitted_at`) VALUES
(1, 2, 'reading', 2, 'Reading B1 - The London Eye', 1, 5, 3.00, '2026-05-25 14:30:21');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `test_attempt_answers`
--

CREATE TABLE `test_attempt_answers` (
  `id` int(11) NOT NULL,
  `attempt_id` int(11) NOT NULL,
  `question_index` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `selected_answer` int(11) DEFAULT NULL,
  `correct_answer` int(11) NOT NULL,
  `selected_text` text DEFAULT NULL,
  `correct_text` text DEFAULT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0,
  `explanation` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Đang đổ dữ liệu cho bảng `test_attempt_answers`
--

INSERT INTO `test_attempt_answers` (`id`, `attempt_id`, `question_index`, `question_text`, `selected_answer`, `correct_answer`, `selected_text`, `correct_text`, `is_correct`, `explanation`) VALUES
(1, 1, 0, 'Where is the London Eye located?', 3, 0, 'Next to Oxford University', 'On the South Bank of the River Thames', 0, ''),
(2, 1, 1, 'When was the London Eye opened to the public?', 0, 1, 'March 9, 1999', 'March 9, 2000', 0, ''),
(3, 1, 2, 'How tall is the London Eye?', 2, 2, '135 metres', '135 metres', 1, ''),
(4, 1, 3, 'How many passenger capsules does it have?', 0, 2, '25', '32', 0, ''),
(5, 1, 4, 'How long does a complete rotation take?', 3, 2, 'About 60 minutes', 'About 30 minutes', 0, '');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `test_questions`
--

CREATE TABLE `test_questions` (
  `test_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `username` varchar(50) DEFAULT NULL,
  `role` int(11) DEFAULT 0 COMMENT '1:admin, 2:người học , 3: giảng viên\r\n',
  `phone` varchar(15) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `created_at`, `username`, `role`, `phone`, `avatar`) VALUES
(1, 'Trúc', 'truc@gmail.com', '123456', '2026-04-17 14:49:29', 'admin', 1, '0912345678', 'img/avatars/1_1778729137.jpg'),
(2, 'An', 'an@gmail.com', '123456', '2026-04-17 14:49:29', 'user', 2, '0912345678', 'img/avatars/2_1778343790.jpg'),
(8, 'Phương', 'phuong@gmail.com', '123456', '2026-04-17 14:49:29', NULL, 2, '0912345678', 'default.jpg'),
(9, 'Quân', 'quan@gmail.com', '123456', '2026-04-17 14:49:29', NULL, 2, '0912345678', 'default.jpg'),
(10, 'Trang', 'trang@gmail.com', '123456', '2026-04-17 14:49:29', NULL, 2, '0912345678', 'default.jpg'),
(13, 'Thanh Trúc', 'truc@gmail.com', '745', '2026-05-07 17:56:37', 'giangvien', 3, NULL, NULL),
(14, 'Trần Nga', 'ngatran@gmail.com', '123', '2026-05-07 17:58:35', NULL, 3, NULL, NULL),
(15, 'Trúc Phan', 'trup@gmai.com', '$2y$10$vQsEdLgsrHYK73mki1gFwuYBVx5fD7if.CmeirziXQ6sC8wjQyHtS', '2026-05-12 08:22:01', 'trucphan', 2, '098765432', '');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `user_progress`
--

CREATE TABLE `user_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `level` varchar(20) DEFAULT NULL,
  `completed` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `user_streaks`
--

CREATE TABLE `user_streaks` (
  `user_id` int(11) NOT NULL,
  `current_streak` int(11) NOT NULL DEFAULT 0,
  `best_streak` int(11) NOT NULL DEFAULT 0,
  `last_active_date` date DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `user_streaks`
--

INSERT INTO `user_streaks` (`user_id`, `current_streak`, `best_streak`, `last_active_date`, `updated_at`) VALUES
(1, 1, 1, '2026-05-12', '2026-05-12 16:18:32'),
(2, 2, 2, '2026-05-25', '2026-05-25 14:30:21'),
(15, 1, 1, '2026-05-12', '2026-05-12 15:22:53');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `writing_submissions`
--

CREATE TABLE `writing_submissions` (
  `id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `answer_text` text NOT NULL,
  `word_count` int(11) NOT NULL DEFAULT 0,
  `score` decimal(4,2) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `status` enum('submitted','graded') DEFAULT 'submitted',
  `created_at` datetime DEFAULT current_timestamp(),
  `submitted_at` datetime DEFAULT current_timestamp(),
  `graded_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `chat_ai`
--
ALTER TABLE `chat_ai`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `skill_id` (`skill_id`);

--
-- Chỉ mục cho bảng `practices`
--
ALTER TABLE `practices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `skill_id` (`skill_id`);

--
-- Chỉ mục cho bảng `practice_questions`
--
ALTER TABLE `practice_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `practice_id` (`practice_id`);

--
-- Chỉ mục cho bảng `practice_results`
--
ALTER TABLE `practice_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `practice_id` (`practice_id`);

--
-- Chỉ mục cho bảng `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `results`
--
ALTER TABLE `results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `test_id` (`test_id`);

--
-- Chỉ mục cho bảng `site_posts`
--
ALTER TABLE `site_posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Chỉ mục cho bảng `skills`
--
ALTER TABLE `skills`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `skill_uploads`
--
ALTER TABLE `skill_uploads`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `speaking_submissions`
--
ALTER TABLE `speaking_submissions`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `student_questions`
--
ALTER TABLE `student_questions`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `study_sessions`
--
ALTER TABLE `study_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_study_sessions_user_created` (`user_id`,`created_at`);

--
-- Chỉ mục cho bảng `teacher_students`
--
ALTER TABLE `teacher_students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_teacher_student` (`teacher_id`,`student_id`);

--
-- Chỉ mục cho bảng `tests`
--
ALTER TABLE `tests`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `test_attempts`
--
ALTER TABLE `test_attempts`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `test_attempt_answers`
--
ALTER TABLE `test_attempt_answers`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `test_questions`
--
ALTER TABLE `test_questions`
  ADD PRIMARY KEY (`test_id`,`question_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `user_progress`
--
ALTER TABLE `user_progress`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `user_streaks`
--
ALTER TABLE `user_streaks`
  ADD PRIMARY KEY (`user_id`);

--
-- Chỉ mục cho bảng `writing_submissions`
--
ALTER TABLE `writing_submissions`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `chat_ai`
--
ALTER TABLE `chat_ai`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `practices`
--
ALTER TABLE `practices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `practice_questions`
--
ALTER TABLE `practice_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `practice_results`
--
ALTER TABLE `practice_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT cho bảng `results`
--
ALTER TABLE `results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `site_posts`
--
ALTER TABLE `site_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `skills`
--
ALTER TABLE `skills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `skill_uploads`
--
ALTER TABLE `skill_uploads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `speaking_submissions`
--
ALTER TABLE `speaking_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `student_questions`
--
ALTER TABLE `student_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `study_sessions`
--
ALTER TABLE `study_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `teacher_students`
--
ALTER TABLE `teacher_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `tests`
--
ALTER TABLE `tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `test_attempts`
--
ALTER TABLE `test_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `test_attempt_answers`
--
ALTER TABLE `test_attempt_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT cho bảng `user_progress`
--
ALTER TABLE `user_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `writing_submissions`
--
ALTER TABLE `writing_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `chat_ai`
--
ALTER TABLE `chat_ai`
  ADD CONSTRAINT `chat_ai_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`);

--
-- Các ràng buộc cho bảng `practices`
--
ALTER TABLE `practices`
  ADD CONSTRAINT `practices_ibfk_1` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`);

--
-- Các ràng buộc cho bảng `practice_questions`
--
ALTER TABLE `practice_questions`
  ADD CONSTRAINT `practice_questions_ibfk_1` FOREIGN KEY (`practice_id`) REFERENCES `practices` (`id`);

--
-- Các ràng buộc cho bảng `practice_results`
--
ALTER TABLE `practice_results`
  ADD CONSTRAINT `practice_results_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `practice_results_ibfk_2` FOREIGN KEY (`practice_id`) REFERENCES `practices` (`id`);

--
-- Các ràng buộc cho bảng `results`
--
ALTER TABLE `results`
  ADD CONSTRAINT `results_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `results_ibfk_2` FOREIGN KEY (`test_id`) REFERENCES `tests` (`id`);

--
-- Các ràng buộc cho bảng `study_sessions`
--
ALTER TABLE `study_sessions`
  ADD CONSTRAINT `fk_study_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `test_questions`
--
ALTER TABLE `test_questions`
  ADD CONSTRAINT `test_questions_ibfk_1` FOREIGN KEY (`test_id`) REFERENCES `tests` (`id`),
  ADD CONSTRAINT `test_questions_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`);

--
-- Các ràng buộc cho bảng `user_streaks`
--
ALTER TABLE `user_streaks`
  ADD CONSTRAINT `fk_user_streaks_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
