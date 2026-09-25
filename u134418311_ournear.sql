-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 19, 2026 at 02:11 PM
-- Server version: 11.8.9-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u134418311_ournear`
--

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `mobile` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `current_location` varchar(255) NOT NULL,
  `experience` varchar(255) NOT NULL,
  `qualification` varchar(255) NOT NULL,
  `resume_path` varchar(255) NOT NULL,
  `additional_information` text DEFAULT NULL,
  `consent` tinyint(1) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'New',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `internal_notes` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `job_id`, `full_name`, `mobile`, `email`, `current_location`, `experience`, `qualification`, `resume_path`, `additional_information`, `consent`, `status`, `created_at`, `updated_at`, `internal_notes`, `user_id`) VALUES
(1, 1, 'venkat  Narayana Reddy', '85008582492', 'pvnrengineer@gmail.com', 'hyderabad', '12', 'gsdg', 'be5bf430-36c4-463d-9f5e-aa1652c5423b.pdf', NULL, 1, 'Withdrawn', '2026-08-30 17:28:43', '2026-08-31 05:24:56', '[]', NULL),
(2, 2, 'ALBY A S', '8547366587', 'albyaneesh203@gmail.com', 'Thiruvananthapuram, kerala', '0', 'Msc Geology', '8ae58da2-6702-495f-9d53-8bdb9d9fd2f0.pdf', NULL, 1, 'New', '2026-08-31 05:30:44', '2026-08-31 05:30:44', '[]', NULL),
(3, 2, 'ANUJA C P', '6282952169', 'anujacp14@gmail.com', 'Thiruvananthapuram, kerala', '0', 'MSC GEOLOGY', '17143b94-a227-41b1-9251-d0655bdaa5cb.pdf', NULL, 1, 'New', '2026-08-31 05:32:04', '2026-08-31 05:32:04', '[]', NULL),
(4, 8, 'Asish kumar Sahoo', '07064206311', 'sahooasish786@gmail.com', 'LKP-B/20 Bandhabahal Jharsuguda 768211', 'Worked in BGR mining infra limited as shift incharge from 2019 to 2024 now at kalinga commercial company till date.', 'B-tech in mining engeneering', '851b773e-5762-40b8-92f8-226a3db8026d.pdf', 'I had obtained 2nd class manager certificate through DGMS.', 1, 'New', '2026-08-31 05:37:55', '2026-08-31 05:37:55', '[]', NULL),
(5, 2, 'FAYIZAH KP', '7356664035', 'fayizahfas@gmail.com', 'Kozhikode, Kerala, India', '0', 'Msc. Applied geology', '0d72820b-ec19-4eb0-8e19-935da490a4af.pdf', 'M.Sc. Applied Geology graduate with practical exposure in hydrogeology, geological field mapping, GIS and mining-related geology. Completed internship with the Ground Water Department, Idukki, Kerala, involving hydrogeological field investigations, groundwater exploration, well investigations, water-level monitoring, discharge measurements and groundwater resource assessment. Also completed geological mapping fieldwork in the Chitradurga Schist Belt, Karnataka.\r\n\r\nProficient in ArcGIS 10.8, QGIS, remote sensing and geological data analysis. Academic project: Flood Susceptibility Mapping Using GIS and AHP – Chalakudy River Basin.\r\n\r\nI am a fresher and highly interested in starting my career as a Graduate Engineer Trainee (GET)/Geology professional, particularly in mining, exploration, geological mapping, hydrogeology or related field operations. I am willing to relocate and work in field-based locations.', 1, 'New', '2026-08-31 12:12:15', '2026-08-31 12:12:15', '[]', NULL),
(6, 12, 'FAYIZAH KP', '7356664035', 'fayizahfas@gmail.com', 'Kozhikode, Kerala, India', '0', 'Msc. Applied geology', 'c4ec226d-ffa8-483a-b9c0-f19a880736ef.pdf', 'M.Sc. Applied Geology graduate with practical exposure in hydrogeology, geological field mapping, GIS and mining-related geology. Completed internship with the Ground Water Department, Idukki, Kerala, involving hydrogeological field investigations, groundwater exploration, well investigations, water-level monitoring, discharge measurements and groundwater resource assessment. Also completed geological mapping fieldwork in the Chitradurga Schist Belt, Karnataka.\r\n\r\nProficient in ArcGIS 10.8, QGIS, ArcGIS Pro, remote sensing and geological data analysis. Academic project: Flood Susceptibility Mapping Using GIS and AHP – Chalakudy River Basin.\r\n\r\nI am a fresher and highly interested in starting my career as a Mining Trainee /Geology professional, particularly in mining, exploration, geological mapping, hydrogeology or related field operations. I am willing to relocate and work in field-based locations.', 1, 'New', '2026-08-31 12:51:36', '2026-08-31 12:51:36', '[]', NULL),
(7, 13, 'Pranay Kumar GOLI', '+46722217936', 'pranaykumar60@gmail.com', 'Europe _sweden', '14', 'Btech civil engineering', '7b88467b-670f-43ca-a00c-d335c13d5009.pdf', NULL, 1, 'New', '2026-08-31 13:52:45', '2026-08-31 13:52:45', '[]', NULL),
(8, 11, 'SK AMIRUL ISLAM', '9749668683', 'surveyoramirul@gmail.com', 'Kolkata', '13', '12th & Survey', 'f7ee56aa-a26e-4187-97b6-cadc1fabfbdc.pdf', NULL, 1, 'New', '2026-08-31 14:03:56', '2026-08-31 14:03:56', '[]', NULL),
(9, 15, 'Pranay Kumar Goli', '+46722217936', 'pranaykumar60@gmail.com', 'Sweden Europe', '14', 'Btech  civil Engineering', '3cab9de8-1eca-44e8-9223-63fce866ac7b.pdf', NULL, 1, 'Reviewing', '2026-08-31 14:07:50', '2026-08-31 14:10:31', '[]', NULL),
(10, 21, 'Tanaya Ranjan Panigrahi', '9438321721', 'rikun87@gmail.com', 'Hisar, Haryana', '20 Years', 'Diploma in civil', 'd0b05346-e9d9-4b83-a943-eecfa7cc493a.pdf', 'Deputy Manager Survey', 1, 'Reviewing', '2026-08-31 16:18:06', '2026-09-01 13:46:31', '[]', NULL),
(11, 12, 'Sunita Behera', '09937969651', 'sonysunita950@gmail.com', 'Jajpur, Odisha', 'Fresher', 'Msc', '4334d224-7712-417d-8fb4-3063ce531c62.pdf', NULL, 1, 'Rejected', '2026-09-01 05:05:03', '2026-09-04 06:52:25', '[]', NULL),
(12, 29, 'A.Maninder', '8688541842', 'maniankarla13@gmail.com', 'Hyderabad', 'Had internship for 1 year at vasavi group', 'Graduated - civil engineering', '0742aabb-3e88-4def-8c04-9913f789e68f.docx', NULL, 1, 'New', '2026-09-01 13:16:16', '2026-09-01 13:16:16', '[]', 5),
(13, 18, 'chikram buchi ramulu', '9966740705', 'ram.chikram@gmail.com', 'Karnataka', '8 years experiance', 'Diploma in civil engineering', '3a3df447-1c93-4c95-9617-b1af6d512f97.pdf', 'Iam Buchi Ramulu\r\nDiploma in civil engineering,8 experiance in Highways and RE walls', 1, 'Shortlisted', '2026-09-02 13:35:42', '2026-09-04 06:42:50', '[]', NULL),
(14, 29, 'Trail aplication', '9999999999', 'trailemai@gmail.com', 'Chennai', '3', 'Dec', 'c4c9739c-48ef-41d4-8c68-eba1949bcee0.pdf', 'TRAIL', 1, 'Rejected', '2026-09-03 04:28:31', '2026-09-04 06:42:34', '[]', NULL),
(15, 7, 'Mukesh Padhi', '7008273991', 'mukeshpadhi@ymail.com', 'Bhubaneswar', '10 years on Govt. Liaisioning and Land Acquisition', 'MBA Finance', '11f92689-3ca3-4fbd-b95e-63b9738cdc1e.docx', NULL, 1, 'Rejected', '2026-09-03 05:49:51', '2026-09-04 06:42:12', '[]', NULL),
(16, 29, 'Mandla vinod Kumar', '7569992951', 'chinnu756999@gmail.com', 'Anantapur', '4', 'Diploma in civil engineering', 'c7ea38eb-7c6a-4220-bba8-85a3ca667287.pdf', 'I have experience in layout of plots and development works like roads, side canals in layout', 1, 'New', '2026-09-04 11:47:51', '2026-09-04 11:47:51', '[]', NULL),
(17, 18, 'Rajkumar Dhakad', '08516877359', 'rajdhakad07@gmail.com', 'Navi Mumbai', '1.2 yrs', 'M.Tech (Transportation Engineering and management)', 'ec52cc55-2580-4ad5-9358-e3f9fb262857.pdf', NULL, 1, 'New', '2026-09-04 14:39:36', '2026-09-04 14:39:36', '[]', NULL),
(18, 29, 'Paila harish', '8008314066', 'pailaharish2000@gmail.com', 'Vijaywada', '4 years in highway projects', 'Btech', '65a75215-9d61-4906-80d3-11ff216eaeb1.pdf', 'Good knowledge in laying Dbm,WMM,gsb', 1, 'New', '2026-09-04 18:33:45', '2026-09-04 18:33:45', '[]', NULL),
(19, 18, 'Paila harish', '08008314066', 'pailaharish2000@gmail.com', 'Vijaywada', '4 years in highway projects', 'Btech', '22e4b90c-acda-46a1-93e1-a953086af77f.pdf', 'good knowledge in laying Dbm WMM gsb subgrade', 1, 'New', '2026-09-04 18:35:39', '2026-09-04 18:35:39', '[]', NULL),
(20, 29, 'Bandham kiran', '6305919174', 'kiranbandam27@gmail.com', 'Gooty', '2', 'B.tech', '33912250-62d3-47af-a7e9-a49b962fed93.pdf', 'I have created a tailored cover letter for Bandham Kiran highlighting his experience as a Civil Site Engineer.\r\n​Key Highlights Featured in the Cover Letter:\r\n​Contact & Identification Header: Cleanly structured header with candidate details, contact information, and current location.\r\n​Core Infrastructure Experience: Direct focus on his work with V North Constructions on the NHAI Bharatmala Pariyojana Phase-I project, emphasizing structural execution (PSC/RCC girders, bridges, LVUP, box culverts, pile foundations) and technical quality compliance (RFIs, BBS, concrete quantity estimations, Auto Level measurements).\r\n​Renewable Energy Civil Experience: Highlight of his recent work with SWRE Power Pvt. Ltd. on the 435 MW Solar Power Project in Gooty (MMS tracker execution, pile casting, ICR foundations, IDT pads, OSHA safety, and workforce leadership).\r\n​Technical Software & Academic Background: Mentions of his B.Tech in Civil Engineering (JNTUA) along with technical software proficiency in AutoCAD, STAAD Pro, Revit, and MS Excel.\r\n​Formatting: Single-page A4 PDF layout with color accents, proper margins, and readable typography.', 1, 'New', '2026-09-05 01:44:55', '2026-09-05 01:44:55', '[]', NULL),
(21, 29, 'Aman Kumar', '08986425066', 'amansatsangi703@gmail.com', 'Delhi', '3 years', 'Diploma in civil engineering', 'b2ba2978-8a72-4078-a7c7-0dff8c8dd16d.pdf', 'Notice Period: Immediate Joiner\r\nPlease find my resume attached. Kindly consider my profile for any suitable vacancy.', 1, 'New', '2026-09-05 03:03:18', '2026-09-05 03:03:18', '[]', NULL),
(22, 13, 'HARISH AKULA', '8978651547', 'hari_harish06@yahoo.com', 'Qatar', '16', 'Masters in Civil Engineering', '06fede45-890e-4a7e-9f3f-f2d55764d940.docx', NULL, 1, 'New', '2026-09-05 09:51:16', '2026-09-05 09:51:16', '[]', NULL),
(23, 19, 'Khan farazuddin', '7387989636', 'khanfaraz7788@gmail.com', 'Aurangabad', '4', 'BE(CIVIL)', '329c7b30-16a1-44b1-8c96-7ec1d694212e.pdf', NULL, 1, 'New', '2026-09-05 16:32:55', '2026-09-05 16:32:55', '[]', NULL),
(24, 19, 'G Dhananjaneyulu', '8096562114', 'gaddamdhananjaneyulu@gmail.com', 'Nandyala', '7.8', 'B tech civil', '01a78db1-c190-4563-a8a0-874855d30565.pdf', NULL, 1, 'New', '2026-09-09 03:26:56', '2026-09-09 03:26:56', '[]', NULL),
(25, 15, 'SHAIK SHAMIULLAH', '7893698640', 'samishaik.samiulla@gmail.com', 'Hyderabad', '9', 'B. Tech in civil engineering', '8cb1bbfd-8863-4564-805b-038e3a6d3dd6.docx', NULL, 1, 'New', '2026-09-09 06:07:30', '2026-09-09 06:07:30', '[]', NULL),
(26, 29, 'Pawan Yadav', '9555400265', 'pawanyadav86825@gmail.com', 'Deoria', 'One year', 'Btech Civil Engineering', '5f572056-6470-480f-b84f-ca3dcedd71ef.pdf', 'Dear Sir\r\nI have been Qualified a Btech Civil Engineer in 2024 \r\nBasic knowledge of Quantity Estimation, BOQ and Civil Billing.\r\nKnowledge of RCC, concrete work and construction activities.\r\nFamiliar with reading and understanding civil engineering drawings.\r\nGood communication, teamwork and problem-solving skills.\r\nQuick learner with a positive attitude.\r\nWilling to work at project/site locations.\r\nAble to work effectively under pressure and meet project deadlines.\r\nReady to learn new technologies and improve technical skills.', 1, 'New', '2026-09-09 08:11:49', '2026-09-09 08:11:49', '[]', NULL),
(27, 29, 'Bayyanna Sneha', '8688380621', 'sneha121305@gmail.com', 'Hyderabad', '11 months', 'Btech in Civil engineering', '577e9d97-e77d-4966-a67d-dcad8afca0a9.pdf', NULL, 1, 'New', '2026-09-09 09:00:30', '2026-09-09 09:00:30', '[]', NULL),
(28, 22, 'Bayyanna Sneha', '8688380621', 'sneha121305@gmail.com', 'Hyderabad', '11 months', 'Btech In Civil engineering', '8b23b8e1-1ec4-47e7-8fd9-21c4b1b48e95.pdf', NULL, 1, 'New', '2026-09-09 09:03:14', '2026-09-09 09:03:14', '[]', NULL),
(29, 19, 'Bayyanna Sneha', '8688380621', 'sneha121305@gmail.com', 'Hyderabad', '11 months', 'Btech in Civil engineering', '0cacdc50-e89a-4fa9-881f-faa7fd61a918.pdf', NULL, 1, 'New', '2026-09-09 09:04:41', '2026-09-09 09:04:41', '[]', NULL),
(30, 18, 'Bayyanna Sneha', '8688380621', 'sneha121305@gmail.com', 'Hyderabad', '11 months', 'Btech in Civil engineering', '64605372-cf97-4cf0-b3a8-d5dee801d750.pdf', NULL, 1, 'New', '2026-09-09 09:05:39', '2026-09-09 09:05:39', '[]', NULL),
(31, 22, 'Rohit Kumar Mahato', '8797781838', 'rohitkumarmatho65@gmail.com', 'Warangal', '4 year', 'Diploma civil', 'd21bbf4e-5e6c-49c6-abd7-2b9d99e8da79.pdf', 'Total station and auto level ,basic auto cadd', 1, 'New', '2026-09-09 11:44:57', '2026-09-09 11:44:57', '[]', NULL),
(32, 31, 'Siva sankar Orsu', '8886070061', 'sivasankarorsu114@gmail.com', 'Hyderabad', '9 years', 'B.tech Civil engineering', 'f30ff82c-80be-49b9-a7c5-5abd5e4b147e.pdf', NULL, 1, 'New', '2026-09-09 13:16:14', '2026-09-09 13:16:14', '[]', NULL),
(33, 22, 'Shaik saddam hussain', '8074353164', 'saddamshaik2002@gmail.com', 'Amaravathi Development Corporation Limited', '1year', 'Bsc Electronics and Survey form national academy of construction Hyderabad', '3e6b0293-df0d-4aad-8bb1-2891138183fc.docx', NULL, 1, 'Reviewing', '2026-09-09 19:01:20', '2026-09-10 06:30:12', '[]', NULL),
(34, 15, 'kalyan swain', '918888055761', 'kalyanswain25@gmail.com', 'AP (Vijayawada)', '15', 'B-Tech', 'd860ade4-da09-41da-95cf-5c1278a7108c.pdf', NULL, 1, 'New', '2026-09-10 00:34:32', '2026-09-10 00:34:32', '[]', NULL),
(35, 13, 'Nalla Durga rao', '9682157053', 'durga.rao364@gmail.com', 'Hyderabad', '14 years', 'Diploma in civil engineering', '4f022b82-c554-4002-a1f7-8bdecaf27c2f.pdf', NULL, 1, 'New', '2026-09-10 02:56:07', '2026-09-10 02:56:07', '[]', NULL),
(36, 29, 'ROYCILL RICHARD SOOSAI', '09880954341', 'richardroycill8@gmail.com', 'brlgavi', '2 years', 'B.Tech Civil', '8d7a480f-0b03-4cf9-a6e2-07f8166eddea.pdf', 'I am writing to express my interest in the position of Site Engineer / Civil Engineer within your esteemed organization.\r\n\r\nI hold a Bachelor\'s degree in Civil Engineering and have completed one year of hands-on experience in the execution and supervision of commercial, industrial, and infrastructure projects. During this period, I have had the opportunity to work on notable projects, including the Verge Reality Project (Equitas Bank), Chennai, Kothur Logistics Park Kothur, Hyderabad, and I am currently involved in the execution of civil works at the Mayora Infra Project, Wargal Secunderabad.\r\n\r\nMy experience includes RCC works, industrial flooring, waffle wall construction, foundations, column pedestals, concreting, quantity verification, Bar Bending Schedule (BBS), DPR preparation, QC inspections, labour coordination, and ensuring compliance with safety and quality standards. Working in fast-paced project environments has strengthened my technical knowledge, problem-solving abilities, and leadership skills.\r\n\r\nI am now seeking an opportunity where I can contribute my practical site experience while continuing to grow professionally as a Civil Engineer. I am confident that my commitment to quality, execution excellence, and teamwork will make me a valuable addition to your organization.\r\n\r\nPlease find my resume attached for your kind consideration. I would be grateful for the opportunity to discuss how my experience aligns with your organization\'s requirements.\r\n\r\nThank you for your time and consideration. I look forward to hearing from you.\r\n\r\nKind regards,\r\n\r\nRoycill R. Soosai\r\nSite Engineer\r\n📞 +91 9880954341\r\n📧 richardroycill8@gmail.com', 1, 'New', '2026-09-10 04:07:55', '2026-09-10 04:07:55', '[]', NULL),
(37, 20, 'sayed Mohammed mujtaba', '919382205186', 'mujtabasayed143@gmail.com', 'Zaheerabad', '13+', 'H.S', 'b634cae3-e58b-4632-a49c-750425343c37.pdf', NULL, 1, 'New', '2026-09-10 05:15:22', '2026-09-10 05:15:22', '[]', NULL),
(38, 13, 'Mohan Sai goli', '9642345121', 'mail2mohansai@gmail.com', 'Khammam', '12+', 'Diploma in civil engineering', 'ac63422f-dd84-48c4-8ea7-902356e0aa47.pdf', 'I\'m a diploma civil with 12 years 4 months experience in Highway Construction, please find my data and refer me', 1, 'New', '2026-09-10 06:07:12', '2026-09-10 06:07:12', '[]', NULL),
(39, 22, 'RajaRamesh Ch', '7989225473', 'rajaramesh883@gmail.com', 'Kandukur', '8 years', 'Bsc (maths)', 'e938faa8-e9a3-4c21-9f31-a56cd688d15d.pdf', NULL, 1, 'New', '2026-09-10 08:24:16', '2026-09-10 08:24:16', '[]', NULL),
(40, 28, 'Vinesh Mali', '8446024622', 'malivinesh19@gmail.com', 'Gulbarga', '10', 'Bsc Intermediate', 'c91cf6fd-3dc7-4480-a2d5-ee123de1bf36.pdf', NULL, 1, 'New', '2026-09-10 08:46:26', '2026-09-10 08:46:26', '[]', NULL),
(41, 20, 'Dasari Mohan Rao', '9550719229', 'dasarimohanrao2@gmail.com', 'Amaravathi Vijayawada', '5', 'ITI', '15ab15fc-cbc3-4b1a-a380-70ef12aeded0.pdf', NULL, 1, 'New', '2026-09-10 09:38:48', '2026-09-10 09:38:48', '[]', NULL),
(42, 13, 'vydyandh Tallam', '8886212213', 'vydhu.t@gmail.com', 'Warangal', '12', 'M tech', '00156309-18ca-4439-8a5b-96df4f88b32d.pdf', NULL, 1, 'New', '2026-09-10 09:47:28', '2026-09-10 09:47:28', '[]', NULL),
(43, 29, 'Mahmed Ismail', '8088874848', 'mdismail851774@gmail.com', 'Gulbarga Karnataka', '5 yrs', 'Diploma in civil engineering', '6490ada2-985e-4768-a6cd-baf7588d983b.pdf', NULL, 1, 'New', '2026-09-10 11:38:48', '2026-09-10 11:38:48', '[]', NULL),
(44, 20, 'Mahmed Ismail', '8088874848', 'mdismail851774@gmail.com', 'Gulbarga Karnataka', '5 yrs', 'Diploma in civil engineering', '9114907c-f468-424b-bc0f-2b4ccaec0fab.pdf', NULL, 1, 'New', '2026-09-10 11:40:45', '2026-09-10 11:40:45', '[]', NULL),
(45, 28, 'Mahmed Ismail', '8088874848', 'mdismail851774@gmail.com', 'Gulbarga Karnataka', '5 yrs', 'Diploma in civil engineering', '00df06e9-de38-4b75-9d71-bfb635eb3ca1.pdf', NULL, 1, 'New', '2026-09-10 11:41:36', '2026-09-10 11:41:36', '[]', NULL),
(46, 15, 'CHIGURUPATI SHANTI MANOHA', '9676562558', 'manoha93.ch@gmail.com', 'TANZANIA', '13', 'DIPLOMA CIVIL', '7434add5-baff-41f4-9ee7-2494621d6fe6.docx', NULL, 1, 'New', '2026-09-10 12:14:55', '2026-09-10 12:14:55', '[]', NULL),
(47, 26, 'Manikanta Nunnagoppala', '7815831650', 'manikantanunnagoppala@gmail.com', 'Shadnagar', '4.5 years', 'B.Sc(Maths , Statistics, Computer science)', '1e18a0a8-95b8-46b2-857c-e78bc2824447.pdf', 'I have total 4.5 years experience in Construction, Infra and industrial projects. I am flexible to work any where in india', 1, 'New', '2026-09-10 12:17:29', '2026-09-10 12:17:29', '[]', NULL),
(48, 29, 'JAYANTH Rk', '09380188029', 'jayanthgowda897@gmail.com', 'Bengaluru', '1.3', 'BE in civil engineering', 'f9c0f8ae-b6f7-46b1-87f5-dd961fdd705f.pdf', NULL, 1, 'New', '2026-09-10 14:11:28', '2026-09-10 14:11:28', '[]', NULL),
(49, 19, 'Surendra Prajapati', '8378896903', 'surendraprajapati404@gmail.com', 'Vijaywada', '9 years', 'DIPLOMA IN CIVIL ENGINEERING', 'b3b5e164-9479-4bd1-bfeb-34043fae5bd0.pdf', NULL, 1, 'New', '2026-09-10 14:12:15', '2026-09-10 14:12:15', '[]', NULL),
(50, 29, 'Gunta Dinesh', '8919989825', 'dineshkumargunta1998@gmail.com', 'Visakhapatnam', '5years in highways', 'Diploma', 'efb0b715-9565-40ac-ae5f-7b03500c899c.pdf', 'If any requirement please tell me sir', 1, 'New', '2026-09-10 16:30:35', '2026-09-10 16:30:35', '[]', NULL),
(51, 16, 'Gunta Dinesh Kumar', '8919989825', 'dineshkumargunta1998@gmail.com', 'Vizianagaram', '5 years in highway engineer', 'Diploma', '6ab7ebe5-30ee-4c8e-825e-dc7534926450.pdf', NULL, 1, 'New', '2026-09-10 16:33:22', '2026-09-10 16:33:22', '[]', NULL),
(52, 18, 'Dinesh Gunta', '8919989825', 'dineshkumargunta1998@gmail.com', 'Bobbili', '5 years in highway engineer', 'Diploma', 'c63b4dfb-c506-4929-b85d-cdf1d8d25004.pdf', NULL, 1, 'New', '2026-09-10 16:36:38', '2026-09-10 16:36:38', '[]', NULL),
(53, 15, 'Vinjamuri SHEKAR', '7569950805', 'nagashekar455@gmail.com', 'Rajahmundry', '14', 'B.tech', '1914c2a0-8af2-40b6-85f3-8e24d37e1b6b.docx', NULL, 1, 'New', '2026-09-10 17:11:16', '2026-09-10 17:11:16', '[]', NULL),
(54, 29, 'Mohd irfan ahmed', '6281899073', 'mdirfaan7@gmail.com', 'narayanpet', '4 years', 'B-tech', '196c8811-17c9-496c-9648-ead0f827314d.docx', 'Applying for the post of site engineer', 1, 'New', '2026-09-11 03:59:02', '2026-09-11 03:59:02', '[]', NULL),
(55, 29, 'Mritunjay Upadhyay', '8106592944', 'mritunjayupadhyaya1234567@gmail.com', 'Bihar', '09 year', 'B.A history honrs', '98dbd8e7-c6e9-4222-b3ee-d55f6c0991aa.docx', 'Highway formen I have 09 experience', 1, 'New', '2026-09-11 04:30:45', '2026-09-11 04:30:45', '[]', NULL),
(56, 22, 'Suman Mondal', '+919007855437', 'sumanmondal03632@gmail.com', 'Bangalore', '7 year', 'Surveyor ITI', 'a03d548a-03be-43ca-b00c-8dab85d8aad6.pdf', NULL, 1, 'New', '2026-09-11 05:35:22', '2026-09-11 05:35:22', '[]', NULL),
(57, 13, 'ramesh ram', '9493932448', 'ramesh.ramu.48@gmail.com', 'hyderabad', '8', 'B.Tech', '7e95becc-51fd-4df8-b042-c455e35c26d7.pdf', 'I have worked has civil site engineer in roads project for 8 years , having an ability to do multiple site  works', 1, 'New', '2026-09-11 06:19:55', '2026-09-11 06:19:55', '[]', NULL),
(58, 21, 'Mukunda Mahananda', '9556777566', 'mukun.sbp1989@gmail.com', 'Dahej, Gujarat', '10+', 'Diploma in civil', '289e3951-0b07-4aee-a1f0-15354b84add8.pdf', 'With 10+ years of professional experience   in construction, oil & gas, refinery, water block package,pipeline, railway, power plant, and Infrastructure & Industrial projects, I have strong expertise in Topographic Survey, Layout Setting, Alignment, Route Survey, Pipeline & HDD Survey, Total Station, DGPS, Auto Level, GPS, and AutoCAD.\r\n\r\nI have successfully worked on projects for reputed clients including Engineers India Limited (EIL), Reliance Industries Limited (RIL), Petronet LNG, IOCL, GAIL, BCPL, IGGL, NTPC, and Indian Railways, delivering accurate survey execution while maintaining quality and safety standards.', 1, 'New', '2026-09-11 06:38:48', '2026-09-11 06:38:48', '[]', NULL),
(59, 10, 'Susanth Mahapatro', '07989831699', 'susanthmahapatro8@gmail.com', 'Ichchapuram', '8', 'Batch', '02d129ae-9ef9-4921-a081-165f71afef27.pdf', NULL, 1, 'New', '2026-09-11 06:51:16', '2026-09-11 06:51:16', '[]', NULL),
(60, 17, 'Jitender Kumar', '9990509320', 'jitu0045@gmail.com', 'Hyderabad Samsabad', '9 years', 'Diploma in civil engineer', '1fb7f7ef-8bcb-4745-8360-3b2ad2db44ff.pdf', 'Dear sir/Ma\'am \r\n\r\n For your reference I have attached my CV for the position of sectore expert/Bridge - major bridge, flyover. ROB structure, Viaducts (construction of bridge infrastructure, Elevated section. Highways) having productive experience of project exc. site excavation and coordination like shuttering, steel, & concrete work preparing bar bending schedule for well foundation, pile foundation & sub structure, super structure, launching psc girder , PSC Girder Stressing, etc work \r\n\r\n Higher qualifications - Diploma in civil ( Structure engineer) \r\n                     \r\n Total Experience - 9 years \r\nCurrent designation:- Sr. Structure engineer \r\nCurrent company:- Vishwa Samundar Pvt Ltd \r\nPresent working location - Hyderabad road projects \r\nNative place - Bihar \r\n\r\nLooking forward for your reference \r\nPositive response & very thankful to you\r\n\r\nName - jitender Kumar\r\nContact no. 9990509320', 1, 'New', '2026-09-11 08:51:05', '2026-09-11 08:51:05', '[]', NULL),
(61, 29, 'rajavardhan reddy', '9533142388', 'rajavardhan355@gmail.com', 'Mahabubnagar', '6', 'BTech mechanical', 'a0086db5-bbf9-46c6-bb57-09a3e7b8e78a.pdf', NULL, 1, 'New', '2026-09-11 10:07:13', '2026-09-11 10:07:13', '[]', NULL),
(62, 21, 'Ram', '7416629530', 'mattaparthirambabu418@gmail.com', 'Hyderabad', '26', 'Diploma', '8a5bde85-f73d-4b9b-9637-4a3afaa3b5ae.docx', NULL, 1, 'New', '2026-09-11 11:52:55', '2026-09-11 11:52:55', '[]', NULL),
(63, 17, 'THEAGALA RAJU', '8008060444', 'raju.raju.chinna95@gmail.com', 'Hanumakonda', '15', 'Diploma', 'bdfec1e5-9074-471f-95f1-e8c4fdf0d6c3.pdf', NULL, 1, 'New', '2026-09-11 13:31:49', '2026-09-11 13:31:49', '[]', NULL),
(64, 16, 'THEAGALA RAJU', '8008060444', 'raju.raju.chinna95@gmail.com', 'Hanumakonda', '15', 'Diploma', 'ae5f7826-c2dc-40b4-ad08-a1d5f0354618.pdf', NULL, 1, 'New', '2026-09-11 13:33:24', '2026-09-11 13:33:24', '[]', NULL),
(65, 19, 'Sekhar Babu', '6300439752', 'sekhar1761.ks@gmail.com', 'Vijayawada', '6', 'Btech', 'eeca4bb0-3b26-48bd-a929-02f401ac7486.pdf', 'My self Sekhar Working as structure Engineer with the experience of 6 years', 1, 'New', '2026-09-11 13:46:20', '2026-09-11 13:46:20', '[]', NULL),
(66, 21, 'Ajit singh', '7080950027', 'ajitsingh95.survey@gmail.com', 'Lucknow', '9 year', '3 year Diploma in civil Engineering', '6887f63d-6f20-454f-b368-d88fa4bf19a4.pdf', 'AUTO cadd \r\nDGPS\r\nTOTAL STATION\r\nQUANTITY', 1, 'New', '2026-09-11 15:22:27', '2026-09-11 15:22:27', '[]', NULL),
(67, 26, 'Ganesh Namepalli', '6302550159', 'ganeshcat2006@gmail.com', 'Hyderabad', '25', 'Auto mobile engineer', '9f7a2e0c-39ee-4d97-889e-1d5f13ce4b67.pdf', NULL, 1, 'New', '2026-09-11 16:43:49', '2026-09-11 16:43:49', '[]', NULL),
(68, 11, 'MOHAMMAD ZIAUDDIN', '8985552572', 'ziauddin2207@gmail.com', 'Ongole', '21years', 'B-Tec', '78fd6a3e-0b37-4349-979a-ea6a3d8d8cca.pdf', 'Ok', 1, 'New', '2026-09-11 18:30:55', '2026-09-11 18:30:55', '[]', NULL),
(69, 28, 'Mahesh kumar Emani', '9542375553', 'emanimaheshkumar@gmail.com', 'Nellore', '12', 'Master of Business Administration', '1f08cdc8-a52f-43fc-b34c-4ed9f37ababa.pdf', NULL, 1, 'New', '2026-09-11 19:13:04', '2026-09-11 19:13:04', '[]', NULL),
(70, 29, 'Tarun Dwarapureddi', '9121517822', 'tarunvictory88@gmail.com', 'Vizag', '3 years', 'B-tech', 'b7a444ca-aa69-4af4-b0cf-2161db66fda6.pdf', 'Intrested in road projects', 1, 'New', '2026-09-12 11:09:27', '2026-09-12 11:09:27', '[]', NULL),
(71, 26, 'BABURAM DOLUI', '8328918324', 'doluibaburam1987@gmail.com', 'ODISHA', '12 YEARS', 'B.A.', '4c009ea4-087c-4b9d-bd88-cdbaa41df20a.pdf', 'Dedicated Stores Incharge with 12 years of experience in managing store operations, inventory control, inward/outward material, stock verification, and documentation. Proficient in maintaining accurate stock records, coordinating with purchase and site teams, and ensuring timely material availability for project execution.', 1, 'New', '2026-09-14 13:50:16', '2026-09-14 13:50:16', '[]', NULL),
(72, 21, 'Ajit Singh', '7080950027', 'aaajit8795@gmail.com', 'Lucknow', '9 year', 'Diploma In civil engineering', '38157cc2-e5ee-48b8-8cfa-790b7c94a147.pdf', NULL, 1, 'New', '2026-09-18 07:00:04', '2026-09-18 07:00:04', '[]', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `application_interviews`
--

CREATE TABLE `application_interviews` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `scheduled_at` datetime NOT NULL,
  `mode` varchar(255) NOT NULL,
  `location_or_link` varchar(255) DEFAULT NULL,
  `interviewer_name` varchar(255) NOT NULL,
  `outcome` varchar(255) NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` text NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('ourneartalent-cache-00b35190e69b9fcf9e4efa3b14aca236', 'i:1;', 1788272176),
('ourneartalent-cache-00b35190e69b9fcf9e4efa3b14aca236:timer', 'i:1788272176;', 1788272176),
('ourneartalent-cache-017df476e8c7d688b3afd23799c0a7c4', 'i:1;', 1788106467),
('ourneartalent-cache-017df476e8c7d688b3afd23799c0a7c4:timer', 'i:1788106467;', 1788106467),
('ourneartalent-cache-021abb381f763a2c76a081d9cad3e2e5', 'i:1;', 1789093244),
('ourneartalent-cache-021abb381f763a2c76a081d9cad3e2e5:timer', 'i:1789093244;', 1789093244),
('ourneartalent-cache-023a792d72c6d027b621f34f2f4275bc', 'i:1;', 1788116991),
('ourneartalent-cache-023a792d72c6d027b621f34f2f4275bc:timer', 'i:1788116991;', 1788116991),
('ourneartalent-cache-0338fc192e43e736b40a0d5acbf91da3', 'i:1;', 1789037248),
('ourneartalent-cache-0338fc192e43e736b40a0d5acbf91da3:timer', 'i:1789037248;', 1789037248),
('ourneartalent-cache-03cf981fe26a3cef1256628e897d5929', 'i:1;', 1789397415),
('ourneartalent-cache-03cf981fe26a3cef1256628e897d5929:timer', 'i:1789397415;', 1789397415),
('ourneartalent-cache-05fcf04562217e0cdd961be61adf5e59', 'i:2;', 1789119877),
('ourneartalent-cache-05fcf04562217e0cdd961be61adf5e59:timer', 'i:1789119877;', 1789119877),
('ourneartalent-cache-088ec9d08616b11a59775134dc3649dd', 'i:3;', 1789061435),
('ourneartalent-cache-088ec9d08616b11a59775134dc3649dd:timer', 'i:1789061435;', 1789061435),
('ourneartalent-cache-090e131c2c5e6693b66fb27fffbd24f7', 'i:1;', 1789012287),
('ourneartalent-cache-090e131c2c5e6693b66fb27fffbd24f7:timer', 'i:1789012287;', 1789012287),
('ourneartalent-cache-09da4906091779d6b33a1019e8644c84', 'i:3;', 1789137082),
('ourneartalent-cache-09da4906091779d6b33a1019e8644c84:timer', 'i:1789137082;', 1789137082),
('ourneartalent-cache-0bd2360b7bc18ad3eb2daa5f6077bb78', 'i:1;', 1789093244),
('ourneartalent-cache-0bd2360b7bc18ad3eb2daa5f6077bb78:timer', 'i:1789093244;', 1789093244),
('ourneartalent-cache-0c1de4f48477df4b2493ca51c668708a', 'i:1;', 1788525699),
('ourneartalent-cache-0c1de4f48477df4b2493ca51c668708a:timer', 'i:1788525699;', 1788525699),
('ourneartalent-cache-0c663526dbdbf7dd3bf174bc2ef36b04', 'i:1;', 1789020922),
('ourneartalent-cache-0c663526dbdbf7dd3bf174bc2ef36b04:timer', 'i:1789020922;', 1789020922),
('ourneartalent-cache-0cac08313deb62124e57d3cd91027e71', 'i:1;', 1789066061),
('ourneartalent-cache-0cac08313deb62124e57d3cd91027e71:timer', 'i:1789066061;', 1789066061),
('ourneartalent-cache-0cafdb463270a997a932443c23b7b025', 'i:1;', 1789092577),
('ourneartalent-cache-0cafdb463270a997a932443c23b7b025:timer', 'i:1789092577;', 1789092577),
('ourneartalent-cache-0cb2a0eb6b827bd9788ed5e4fe2612fc', 'i:3;', 1788919734),
('ourneartalent-cache-0cb2a0eb6b827bd9788ed5e4fe2612fc:timer', 'i:1788919734;', 1788919734),
('ourneartalent-cache-0de1bd1a9f16947e4b0126b68c638ed4', 'i:1;', 1789037248),
('ourneartalent-cache-0de1bd1a9f16947e4b0126b68c638ed4:timer', 'i:1789037248;', 1789037248),
('ourneartalent-cache-0e4e13cfa300292706fb0b9fa9a69cf7', 'i:1;', 1788108923),
('ourneartalent-cache-0e4e13cfa300292706fb0b9fa9a69cf7:timer', 'i:1788108923;', 1788108923),
('ourneartalent-cache-13afa31c20ffa24afd6d7b52e3e7d5d6', 'i:1;', 1789214967),
('ourneartalent-cache-13afa31c20ffa24afd6d7b52e3e7d5d6:timer', 'i:1789214967;', 1789214967),
('ourneartalent-cache-13bf48398b7eb908250e2d77e6fb3b49', 'i:2;', 1788957883),
('ourneartalent-cache-13bf48398b7eb908250e2d77e6fb3b49:timer', 'i:1788957883;', 1788957883),
('ourneartalent-cache-197a869bf07508a3904ebcf372b659e7', 'i:2;', 1788108631),
('ourneartalent-cache-197a869bf07508a3904ebcf372b659e7:timer', 'i:1788108631;', 1788108631),
('ourneartalent-cache-19d5b6f402346fcc66e434b2a4a73c01', 'i:1;', 1789032256),
('ourneartalent-cache-19d5b6f402346fcc66e434b2a4a73c01:timer', 'i:1789032256;', 1789032256),
('ourneartalent-cache-1d87ffb21fbe02f89d7c9501d37a8048', 'i:1;', 1789124833),
('ourneartalent-cache-1d87ffb21fbe02f89d7c9501d37a8048:timer', 'i:1789124833;', 1789124833),
('ourneartalent-cache-1e8f24d90bf519da44ce0abcde81b0ce', 'i:2;', 1789023989),
('ourneartalent-cache-1e8f24d90bf519da44ce0abcde81b0ce:timer', 'i:1789023989;', 1789023989),
('ourneartalent-cache-1f386a3e477c9f27e4e126213d3d2654', 'i:1;', 1789397085),
('ourneartalent-cache-1f386a3e477c9f27e4e126213d3d2654:timer', 'i:1789397085;', 1789397085),
('ourneartalent-cache-22e110224271eca511eb904261812845', 'i:1;', 1788244738),
('ourneartalent-cache-22e110224271eca511eb904261812845:timer', 'i:1788244738;', 1788244738),
('ourneartalent-cache-23b9d019132904ca0b5226e19410a5cb', 'i:2;', 1788550425),
('ourneartalent-cache-23b9d019132904ca0b5226e19410a5cb:timer', 'i:1788550425;', 1788550425),
('ourneartalent-cache-255e604faee7aea56a5f4b067b6c2f19', 'i:1;', 1788984080),
('ourneartalent-cache-255e604faee7aea56a5f4b067b6c2f19:timer', 'i:1788984080;', 1788984080),
('ourneartalent-cache-289710fcf3e214ec1abf8b23d5c90251', 'i:3;', 1789043928),
('ourneartalent-cache-289710fcf3e214ec1abf8b23d5c90251:timer', 'i:1789043928;', 1789043928),
('ourneartalent-cache-2b233e461902d3559704eb51ac302217', 'i:2;', 1788187965),
('ourneartalent-cache-2b233e461902d3559704eb51ac302217:timer', 'i:1788187965;', 1788187965),
('ourneartalent-cache-2e035e0000d84005f53d6f7d90c673e6', 'i:1;', 1789053087),
('ourneartalent-cache-2e035e0000d84005f53d6f7d90c673e6:timer', 'i:1789053087;', 1789053087),
('ourneartalent-cache-2f31b9ce864f54a7aa290a63109d7b63', 'i:1;', 1788526071),
('ourneartalent-cache-2f31b9ce864f54a7aa290a63109d7b63:timer', 'i:1788526071;', 1788526071),
('ourneartalent-cache-2f45790d960e893fdc53795a22095c4f', 'i:1;', 1788106355),
('ourneartalent-cache-2f45790d960e893fdc53795a22095c4f:timer', 'i:1788106355;', 1788106355),
('ourneartalent-cache-3336bcbb81fd72a4523a7b2f503e51a5', 'i:1;', 1789131175),
('ourneartalent-cache-3336bcbb81fd72a4523a7b2f503e51a5:timer', 'i:1789131175;', 1789131175),
('ourneartalent-cache-336ed188f1ba8af9eae156c5a80529ad', 'i:1;', 1789151059),
('ourneartalent-cache-336ed188f1ba8af9eae156c5a80529ad:timer', 'i:1789151059;', 1789151059),
('ourneartalent-cache-3418d0c5b754da1c17e97dbddc45178f', 'i:2;', 1788114515),
('ourneartalent-cache-3418d0c5b754da1c17e97dbddc45178f:timer', 'i:1788114515;', 1788114515),
('ourneartalent-cache-35b014f34593333c7754077ccbd3e343', 'i:1;', 1789157584),
('ourneartalent-cache-35b014f34593333c7754077ccbd3e343:timer', 'i:1789157584;', 1789157584),
('ourneartalent-cache-38178de0ea4dbb07ae9faef36dab8c14', 'i:2;', 1789007703),
('ourneartalent-cache-38178de0ea4dbb07ae9faef36dab8c14:timer', 'i:1789007703;', 1789007703),
('ourneartalent-cache-38b445fb265631b9dc46ecadeb4a9713', 'i:2;', 1789046233),
('ourneartalent-cache-38b445fb265631b9dc46ecadeb4a9713:timer', 'i:1789046233;', 1789046233),
('ourneartalent-cache-3b296afed55ed0d301049c6a60864149', 'i:3;', 1789104566),
('ourneartalent-cache-3b296afed55ed0d301049c6a60864149:timer', 'i:1789104566;', 1789104566),
('ourneartalent-cache-3c296a612f3fd4c73febe05a37f0d3f4', 'i:1;', 1788196686),
('ourneartalent-cache-3c296a612f3fd4c73febe05a37f0d3f4:timer', 'i:1788196686;', 1788196686),
('ourneartalent-cache-3d0f34beacceeb5665a01798fad0f2f3', 'i:1;', 1789016875),
('ourneartalent-cache-3d0f34beacceeb5665a01798fad0f2f3:timer', 'i:1789016875;', 1789016875),
('ourneartalent-cache-452b019067b7f0a5a6338c97b211ab2f', 'i:3;', 1789104566),
('ourneartalent-cache-452b019067b7f0a5a6338c97b211ab2f:timer', 'i:1789104566;', 1789104566),
('ourneartalent-cache-4932fa4ef03ee2e536aabf68e066f970', 'i:1;', 1788258524),
('ourneartalent-cache-4932fa4ef03ee2e536aabf68e066f970:timer', 'i:1788258524;', 1788258524),
('ourneartalent-cache-4bcf987e9e324a12f4975d5a7ba93e22', 'i:1;', 1789053087),
('ourneartalent-cache-4bcf987e9e324a12f4975d5a7ba93e22:timer', 'i:1789053087;', 1789053087),
('ourneartalent-cache-4cba8d38b652f830e3d3f2bc9e1284ce', 'i:1;', 1789120265),
('ourneartalent-cache-4cba8d38b652f830e3d3f2bc9e1284ce:timer', 'i:1789120265;', 1789120265),
('ourneartalent-cache-4ce6773bdc47b11e4291eb9769bb6ac7', 'i:2;', 1789003635),
('ourneartalent-cache-4ce6773bdc47b11e4291eb9769bb6ac7:timer', 'i:1789003635;', 1789003635),
('ourneartalent-cache-4de4a86eb1b4f1795444d312043699bc', 'i:1;', 1788196686),
('ourneartalent-cache-4de4a86eb1b4f1795444d312043699bc:timer', 'i:1788196686;', 1788196686),
('ourneartalent-cache-4e48dacb9261b525c26711604a74ae6b', 'i:1;', 1788113209),
('ourneartalent-cache-4e48dacb9261b525c26711604a74ae6b:timer', 'i:1788113209;', 1788113209),
('ourneartalent-cache-4f09f1aa47750551b3212e052f231bd1', 'i:2;', 1789137972),
('ourneartalent-cache-4f09f1aa47750551b3212e052f231bd1:timer', 'i:1789137972;', 1789137972),
('ourneartalent-cache-4f99f9fdc2e8ce77e363c04286738828', 'i:1;', 1789050335),
('ourneartalent-cache-4f99f9fdc2e8ce77e363c04286738828:timer', 'i:1789050335;', 1789050335),
('ourneartalent-cache-51758bf53ada300d5b71b042c7a5af05', 'i:1;', 1788963373),
('ourneartalent-cache-51758bf53ada300d5b71b042c7a5af05:timer', 'i:1788963373;', 1788963373),
('ourneartalent-cache-521ee1db59a9497269c9458aca094d4f', 'i:1;', 1788576295),
('ourneartalent-cache-521ee1db59a9497269c9458aca094d4f:timer', 'i:1788576295;', 1788576295),
('ourneartalent-cache-52f8e1322a558724fe87ee8dd696f803', 'i:1;', 1789112328),
('ourneartalent-cache-52f8e1322a558724fe87ee8dd696f803:timer', 'i:1789112328;', 1789112328),
('ourneartalent-cache-5778eba2240ce60f41f398c4cc1758c3', 'i:1;', 1789016875),
('ourneartalent-cache-5778eba2240ce60f41f398c4cc1758c3:timer', 'i:1789016875;', 1789016875),
('ourneartalent-cache-59d4be042864572db16bd97bc92eb273', 'i:1;', 1789151059),
('ourneartalent-cache-59d4be042864572db16bd97bc92eb273:timer', 'i:1789151059;', 1789151059),
('ourneartalent-cache-5ce3ac335a8e6b6a65edd7ea220c1a2c', 'i:2;', 1788116635),
('ourneartalent-cache-5ce3ac335a8e6b6a65edd7ea220c1a2c:timer', 'i:1788116635;', 1788116635),
('ourneartalent-cache-60956932f68d11406c5d5af91d410a83', 'i:1;', 1788359742),
('ourneartalent-cache-60956932f68d11406c5d5af91d410a83:timer', 'i:1788359742;', 1788359742),
('ourneartalent-cache-60b06fbf8e9e5cbf97313f68a73e263d', 'i:2;', 1789119877),
('ourneartalent-cache-60b06fbf8e9e5cbf97313f68a73e263d:timer', 'i:1789119877;', 1789119877),
('ourneartalent-cache-63aea52954afe6a92acd59b5d358763f', 'i:1;', 1788346202),
('ourneartalent-cache-63aea52954afe6a92acd59b5d358763f:timer', 'i:1788346202;', 1788346202),
('ourneartalent-cache-65383ccf4de2655d7f8bd9961673bd70', 'i:2;', 1789108469),
('ourneartalent-cache-65383ccf4de2655d7f8bd9961673bd70:timer', 'i:1789108469;', 1789108469),
('ourneartalent-cache-656ab5c89d7c1b6a88f07839d66de823', 'i:1;', 1788185565),
('ourneartalent-cache-656ab5c89d7c1b6a88f07839d66de823:timer', 'i:1788185565;', 1788185565),
('ourneartalent-cache-666b5240f4c0c02e172735f330a9ca31', 'i:1;', 1789143747),
('ourneartalent-cache-666b5240f4c0c02e172735f330a9ca31:timer', 'i:1789143747;', 1789143747),
('ourneartalent-cache-66c2ef0ab49bbfa2207369e38ff8f0aa', 'i:1;', 1788106484),
('ourneartalent-cache-66c2ef0ab49bbfa2207369e38ff8f0aa:timer', 'i:1788106484;', 1788106484),
('ourneartalent-cache-6950dc3e39a55b6e4cf513c9f4985192', 'i:1;', 1789113076),
('ourneartalent-cache-6950dc3e39a55b6e4cf513c9f4985192:timer', 'i:1789113076;', 1789113076),
('ourneartalent-cache-6a7edc91d5caeb2891f09bbc47f4df2e', 'i:1;', 1788185541),
('ourneartalent-cache-6a7edc91d5caeb2891f09bbc47f4df2e:timer', 'i:1788185541;', 1788185541),
('ourneartalent-cache-6a82e1933a76b19771f5b2f5683d6acb', 'i:2;', 1788342720),
('ourneartalent-cache-6a82e1933a76b19771f5b2f5683d6acb:timer', 'i:1788342720;', 1788342720),
('ourneartalent-cache-6ca9778c791c3099fe44718bf1e8411c', 'i:1;', 1788629575),
('ourneartalent-cache-6ca9778c791c3099fe44718bf1e8411c:timer', 'i:1788629575;', 1788629575),
('ourneartalent-cache-6eb6846e9ee533416a0d24b6f5071e8b', 'i:1;', 1788188636),
('ourneartalent-cache-6eb6846e9ee533416a0d24b6f5071e8b:timer', 'i:1788188636;', 1788188636),
('ourneartalent-cache-6fe3e57ac8c7f02a16ab35f0876607cd', 'i:1;', 1788536376),
('ourneartalent-cache-6fe3e57ac8c7f02a16ab35f0876607cd:timer', 'i:1788536376;', 1788536376),
('ourneartalent-cache-709c5aeac8963e18425c30fac9c1d8d9', 'i:1;', 1788984080),
('ourneartalent-cache-709c5aeac8963e18425c30fac9c1d8d9:timer', 'i:1788984080;', 1788984080),
('ourneartalent-cache-7138813f14c448c2cd7f3573624a4a66', 'i:1;', 1789092577),
('ourneartalent-cache-7138813f14c448c2cd7f3573624a4a66:timer', 'i:1789092577;', 1789092577),
('ourneartalent-cache-727b736a3fe78f86733c48d702a243b3', 'i:1;', 1788937650),
('ourneartalent-cache-727b736a3fe78f86733c48d702a243b3:timer', 'i:1788937650;', 1788937650),
('ourneartalent-cache-7310f4a4167fe4291400d36cb6c073b8', 'i:2;', 1789012567),
('ourneartalent-cache-7310f4a4167fe4291400d36cb6c073b8:timer', 'i:1789012567;', 1789012567),
('ourneartalent-cache-7563351ebfb1ca0ddbfa1dc80d9977ec', 'i:1;', 1789124833),
('ourneartalent-cache-7563351ebfb1ca0ddbfa1dc80d9977ec:timer', 'i:1789124833;', 1789124833),
('ourneartalent-cache-78c314778e8e7135de279d83eb997fea', 'i:1;', 1789143747),
('ourneartalent-cache-78c314778e8e7135de279d83eb997fea:timer', 'i:1789143747;', 1789143747),
('ourneartalent-cache-790ba537c5a448e7c32480756e9c7f9b', 'i:3;', 1789043928),
('ourneartalent-cache-790ba537c5a448e7c32480756e9c7f9b:timer', 'i:1789043928;', 1789043928),
('ourneartalent-cache-7a64a774d6f4496a1b5e3e29b8829950', 'i:3;', 1788181904),
('ourneartalent-cache-7a64a774d6f4496a1b5e3e29b8829950:timer', 'i:1788181904;', 1788181904),
('ourneartalent-cache-7b3f5427e2f0751176e841f87b40486a', 'i:1;', 1789102742),
('ourneartalent-cache-7b3f5427e2f0751176e841f87b40486a:timer', 'i:1789102742;', 1789102742),
('ourneartalent-cache-7b895f4e8232110fb4d48dfefce454fd', 'i:1;', 1789397085),
('ourneartalent-cache-7b895f4e8232110fb4d48dfefce454fd:timer', 'i:1789397085;', 1789397085),
('ourneartalent-cache-7c51597d9ca506d71b17865ff58be7d1', 'i:1;', 1789151495),
('ourneartalent-cache-7c51597d9ca506d71b17865ff58be7d1:timer', 'i:1789151495;', 1789151495),
('ourneartalent-cache-7de5e400ce4ac7f6ff4ac4b57d1350b7', 'i:1;', 1788945109),
('ourneartalent-cache-7de5e400ce4ac7f6ff4ac4b57d1350b7:timer', 'i:1788945109;', 1788945109),
('ourneartalent-cache-7e750c3d2310f1962f787ea268dd961d', 'i:1;', 1788994875),
('ourneartalent-cache-7e750c3d2310f1962f787ea268dd961d:timer', 'i:1788994875;', 1788994875),
('ourneartalent-cache-8081d2ffce6d275c3c13be472379b386', 'i:1;', 1789063876),
('ourneartalent-cache-8081d2ffce6d275c3c13be472379b386:timer', 'i:1789063876;', 1789063876),
('ourneartalent-cache-814d4377db25362d4b06967352b12f93', 'i:1;', 1788157844),
('ourneartalent-cache-814d4377db25362d4b06967352b12f93:timer', 'i:1788157844;', 1788157844),
('ourneartalent-cache-8659aa2c57c68524513c99871cb0116c', 'i:1;', 1788928016),
('ourneartalent-cache-8659aa2c57c68524513c99871cb0116c:timer', 'i:1788928016;', 1788928016),
('ourneartalent-cache-8912301f42e70b09cc91fe3f9554cdbd', 'i:2;', 1789137972),
('ourneartalent-cache-8912301f42e70b09cc91fe3f9554cdbd:timer', 'i:1789137972;', 1789137972),
('ourneartalent-cache-8b48eec2c4c1cae895b427576aa15e42', 'i:5;', 1788969852),
('ourneartalent-cache-8b48eec2c4c1cae895b427576aa15e42:timer', 'i:1788969852;', 1788969852),
('ourneartalent-cache-8b7280aef65136ed35d94b0b28c279a6', 'i:1;', 1789046095),
('ourneartalent-cache-8b7280aef65136ed35d94b0b28c279a6:timer', 'i:1789046095;', 1789046095),
('ourneartalent-cache-8c70baac141c5748b917ddbeddf825db', 'i:1;', 1788157924),
('ourneartalent-cache-8c70baac141c5748b917ddbeddf825db:timer', 'i:1788157924;', 1788157924),
('ourneartalent-cache-91eefa1aafd2df841b332a21b87939f5', 'i:1;', 1789718404),
('ourneartalent-cache-91eefa1aafd2df841b332a21b87939f5:timer', 'i:1789718404;', 1789718404),
('ourneartalent-cache-938f354eff902f7d864d4b7044f4fe2e', 'i:1;', 1789214967),
('ourneartalent-cache-938f354eff902f7d864d4b7044f4fe2e:timer', 'i:1789214967;', 1789214967),
('ourneartalent-cache-9447d6670e7027bf846e3cd7c9e30a29', 'i:1;', 1788536376),
('ourneartalent-cache-9447d6670e7027bf846e3cd7c9e30a29:timer', 'i:1788536376;', 1788536376),
('ourneartalent-cache-946a383d904879e43d8e6b960e2d9a51', 'i:1;', 1788525699),
('ourneartalent-cache-946a383d904879e43d8e6b960e2d9a51:timer', 'i:1788525699;', 1788525699),
('ourneartalent-cache-94abaf94dd54b31dc9fd084f31e01064', 'i:2;', 1788187965),
('ourneartalent-cache-94abaf94dd54b31dc9fd084f31e01064:timer', 'i:1788187965;', 1788187965),
('ourneartalent-cache-9550793bfa719cb89fecd71f88581228', 'i:1;', 1789120265),
('ourneartalent-cache-9550793bfa719cb89fecd71f88581228:timer', 'i:1789120265;', 1789120265),
('ourneartalent-cache-95ca5037477473aaf7e446268b7aefac', 'i:1;', 1789020922),
('ourneartalent-cache-95ca5037477473aaf7e446268b7aefac:timer', 'i:1789020922;', 1789020922),
('ourneartalent-cache-9714c380dc4ad3676f956af2c0a9fb18', 'i:1;', 1789157584),
('ourneartalent-cache-9714c380dc4ad3676f956af2c0a9fb18:timer', 'i:1789157584;', 1789157584),
('ourneartalent-cache-9826ae8a56ab58432de99475ef1f08fa', 'i:1;', 1788526071),
('ourneartalent-cache-9826ae8a56ab58432de99475ef1f08fa:timer', 'i:1788526071;', 1788526071),
('ourneartalent-cache-9974421c638f642f712568f88d89d191', 'i:1;', 1788945109),
('ourneartalent-cache-9974421c638f642f712568f88d89d191:timer', 'i:1788945109;', 1788945109),
('ourneartalent-cache-9ab563debe1a12b956cbbc98b3782423', 'i:2;', 1788242684),
('ourneartalent-cache-9ab563debe1a12b956cbbc98b3782423:timer', 'i:1788242684;', 1788242684),
('ourneartalent-cache-9b18d57dc3600814ac8ce9341568842a', 'i:2;', 1788413259),
('ourneartalent-cache-9b18d57dc3600814ac8ce9341568842a:timer', 'i:1788413259;', 1788413259),
('ourneartalent-cache-9bed9b2ce5cb909bc0f0f628f57341ab', 'i:1;', 1788968841),
('ourneartalent-cache-9bed9b2ce5cb909bc0f0f628f57341ab:timer', 'i:1788968841;', 1788968841),
('ourneartalent-cache-9bef095064843b4d4a5cc734d526d3fb', 'i:1;', 1788183730),
('ourneartalent-cache-9bef095064843b4d4a5cc734d526d3fb:timer', 'i:1788183730;', 1788183730),
('ourneartalent-cache-9dcb41b7674029764fe0f075217eca93', 'i:1;', 1788605476),
('ourneartalent-cache-9dcb41b7674029764fe0f075217eca93:timer', 'i:1788605476;', 1788605476),
('ourneartalent-cache-9de4949eac8dd394cf18f2b3d0d4fddc', 'i:5;', 1788948030),
('ourneartalent-cache-9de4949eac8dd394cf18f2b3d0d4fddc:timer', 'i:1788948030;', 1788948030),
('ourneartalent-cache-9ef1a37028b9cad4a4689ec567e187f0', 'i:2;', 1789036728),
('ourneartalent-cache-9ef1a37028b9cad4a4689ec567e187f0:timer', 'i:1789036728;', 1789036728),
('ourneartalent-cache-9ef535a9f0510791416766acfb46854e', 'i:1;', 1788418191),
('ourneartalent-cache-9ef535a9f0510791416766acfb46854e:timer', 'i:1788418191;', 1788418191),
('ourneartalent-cache-9f074fcd17ce67c7a8306916bed7782e', 'i:1;', 1788188636),
('ourneartalent-cache-9f074fcd17ce67c7a8306916bed7782e:timer', 'i:1788188636;', 1788188636),
('ourneartalent-cache-9f4b26fc9db6b6342e02cda288d5b553', 'i:1;', 1789148629),
('ourneartalent-cache-9f4b26fc9db6b6342e02cda288d5b553:timer', 'i:1789148629;', 1789148629),
('ourneartalent-cache-9fe696c55d5e9c8be69174cdc7b1a401', 'i:1;', 1789397415),
('ourneartalent-cache-9fe696c55d5e9c8be69174cdc7b1a401:timer', 'i:1789397415;', 1789397415),
('ourneartalent-cache-a0ccea62864c4bfb16c21a345a7d9d20', 'i:1;', 1788267277),
('ourneartalent-cache-a0ccea62864c4bfb16c21a345a7d9d20:timer', 'i:1788267277;', 1788267277),
('ourneartalent-cache-a0ec56f35aa6666702deab918d22ab81', 'i:1;', 1788359742),
('ourneartalent-cache-a0ec56f35aa6666702deab918d22ab81:timer', 'i:1788359742;', 1788359742),
('ourneartalent-cache-a184ab4edb52899c9f64f9cab0c87f4e', 'i:1;', 1788629575),
('ourneartalent-cache-a184ab4edb52899c9f64f9cab0c87f4e:timer', 'i:1788629575;', 1788629575),
('ourneartalent-cache-a4151be1826471790cefc03b5e319326', 'i:1;', 1788346202),
('ourneartalent-cache-a4151be1826471790cefc03b5e319326:timer', 'i:1788346202;', 1788346202),
('ourneartalent-cache-a46d0a9363d0a3486f1c29a2b83101bb', 'i:1;', 1788272176),
('ourneartalent-cache-a46d0a9363d0a3486f1c29a2b83101bb:timer', 'i:1788272176;', 1788272176),
('ourneartalent-cache-ab1ba3439bcbafc9f77676b9b80296f1', 'i:1;', 1789009531),
('ourneartalent-cache-ab1ba3439bcbafc9f77676b9b80296f1:timer', 'i:1789009531;', 1789009531),
('ourneartalent-cache-ac26bdcdb308aef4dfe9faaafd6d8c8f', 'i:2;', 1789046233),
('ourneartalent-cache-ac26bdcdb308aef4dfe9faaafd6d8c8f:timer', 'i:1789046233;', 1789046233),
('ourneartalent-cache-ac32310244a7e95e5efcaf99b552f4b7', 'i:5;', 1788969852),
('ourneartalent-cache-ac32310244a7e95e5efcaf99b552f4b7:timer', 'i:1788969852;', 1788969852),
('ourneartalent-cache-ad488b7c1651af7c3a5961a742b73afd', 'i:1;', 1788244752),
('ourneartalent-cache-ad488b7c1651af7c3a5961a742b73afd:timer', 'i:1788244752;', 1788244752),
('ourneartalent-cache-admin@ourneartalent.local|106.192.29.152', 'i:2;', 1788108632),
('ourneartalent-cache-admin@ourneartalent.local|106.192.29.152:timer', 'i:1788108632;', 1788108632),
('ourneartalent-cache-b00739384fef1e1859c83faa58719426', 'i:2;', 1789155044),
('ourneartalent-cache-b00739384fef1e1859c83faa58719426:timer', 'i:1789155044;', 1789155044),
('ourneartalent-cache-b1176af952343280a9e7f6cf1eaad91a', 'i:2;', 1788413259),
('ourneartalent-cache-b1176af952343280a9e7f6cf1eaad91a:timer', 'i:1788413259;', 1788413259),
('ourneartalent-cache-b34723a8641d4f2c519b90ab19847c74', 'i:2;', 1788116635),
('ourneartalent-cache-b34723a8641d4f2c519b90ab19847c74:timer', 'i:1788116635;', 1788116635),
('ourneartalent-cache-b40187adae2e40c0e03aac52b31e73cd', 'i:1;', 1789112328),
('ourneartalent-cache-b40187adae2e40c0e03aac52b31e73cd:timer', 'i:1789112328;', 1789112328),
('ourneartalent-cache-b4ee3d431af7fd288d6c861cc0b46199', 'i:1;', 1789012287),
('ourneartalent-cache-b4ee3d431af7fd288d6c861cc0b46199:timer', 'i:1789012287;', 1789012287),
('ourneartalent-cache-b7fd7e1bfc4ffbc4f15a688e15555993', 'i:3;', 1788181904),
('ourneartalent-cache-b7fd7e1bfc4ffbc4f15a688e15555993:timer', 'i:1788181904;', 1788181904),
('ourneartalent-cache-b86fb193a3b942c47ac85b62384094d8', 'i:2;', 1789023989),
('ourneartalent-cache-b86fb193a3b942c47ac85b62384094d8:timer', 'i:1789023989;', 1789023989),
('ourneartalent-cache-ba97c956c20e86bbffde2fb510d51cd7', 'i:1;', 1788157924),
('ourneartalent-cache-ba97c956c20e86bbffde2fb510d51cd7:timer', 'i:1788157924;', 1788157924),
('ourneartalent-cache-bc1e7da6b20af654add36ff51db25af2', 'i:1;', 1788580998),
('ourneartalent-cache-bc1e7da6b20af654add36ff51db25af2:timer', 'i:1788580998;', 1788580998),
('ourneartalent-cache-bd5ae34be3357ffb083cd3a3c85629b7', 'i:1;', 1788937650),
('ourneartalent-cache-bd5ae34be3357ffb083cd3a3c85629b7:timer', 'i:1788937650;', 1788937650),
('ourneartalent-cache-be3852198d98ef2aabf431197b796a33', 'i:1;', 1788116991),
('ourneartalent-cache-be3852198d98ef2aabf431197b796a33:timer', 'i:1788116991;', 1788116991),
('ourneartalent-cache-bf50947a010b7025a0bd2ce30dca51a5', 'i:1;', 1788994875),
('ourneartalent-cache-bf50947a010b7025a0bd2ce30dca51a5:timer', 'i:1788994875;', 1788994875),
('ourneartalent-cache-c026f0852478452dcd97ea2c5d6cbf82', 'i:1;', 1788963373),
('ourneartalent-cache-c026f0852478452dcd97ea2c5d6cbf82:timer', 'i:1788963373;', 1788963373),
('ourneartalent-cache-c06501fd9af360a78c4e8caeafca7a19', 'i:1;', 1789131175),
('ourneartalent-cache-c06501fd9af360a78c4e8caeafca7a19:timer', 'i:1789131175;', 1789131175),
('ourneartalent-cache-c0dbf6508dc65d47f4a36a862ca284a1', 'i:2;', 1789003635),
('ourneartalent-cache-c0dbf6508dc65d47f4a36a862ca284a1:timer', 'i:1789003635;', 1789003635),
('ourneartalent-cache-c0ff09a822307c6b5412e737030af711', 'i:1;', 1789148629),
('ourneartalent-cache-c0ff09a822307c6b5412e737030af711:timer', 'i:1789148629;', 1789148629),
('ourneartalent-cache-c25f6e3d8da8ed971474c44808e2d753', 'i:1;', 1788108935),
('ourneartalent-cache-c25f6e3d8da8ed971474c44808e2d753:timer', 'i:1788108935;', 1788108935),
('ourneartalent-cache-c6bdf5f7d36b3f00bf5abc7d6a571ac8', 'i:2;', 1789110975),
('ourneartalent-cache-c6bdf5f7d36b3f00bf5abc7d6a571ac8:timer', 'i:1789110975;', 1789110975),
('ourneartalent-cache-c91bdbbccb73a29d37fa68413b865394', 'i:1;', 1788418191),
('ourneartalent-cache-c91bdbbccb73a29d37fa68413b865394:timer', 'i:1788418191;', 1788418191),
('ourneartalent-cache-c9441957896dc170a8b98f490c39aa2f', 'i:1;', 1789033585),
('ourneartalent-cache-c9441957896dc170a8b98f490c39aa2f:timer', 'i:1789033585;', 1789033585),
('ourneartalent-cache-cdac9fb2eb03795c7c24f2b70bd96851', 'i:2;', 1789012567),
('ourneartalent-cache-cdac9fb2eb03795c7c24f2b70bd96851:timer', 'i:1789012567;', 1789012567),
('ourneartalent-cache-cfbad696b35af1703ab7f3313088458e', 'i:2;', 1788242684),
('ourneartalent-cache-cfbad696b35af1703ab7f3313088458e:timer', 'i:1788242683;', 1788242684),
('ourneartalent-cache-d089c1cdbbd33adb163983a33a66da55', 'i:1;', 1788157844),
('ourneartalent-cache-d089c1cdbbd33adb163983a33a66da55:timer', 'i:1788157844;', 1788157844),
('ourneartalent-cache-d1a13005bfc4bf76642e1d49b99ba3d8', 'i:1;', 1789151495),
('ourneartalent-cache-d1a13005bfc4bf76642e1d49b99ba3d8:timer', 'i:1789151495;', 1789151495),
('ourneartalent-cache-d201abe5947eb9def025c8ba97eb4432', 'i:1;', 1788158275),
('ourneartalent-cache-d201abe5947eb9def025c8ba97eb4432:timer', 'i:1788158275;', 1788158275),
('ourneartalent-cache-d802b53999a6796c4ce3e679e98d3693', 'i:2;', 1789108469),
('ourneartalent-cache-d802b53999a6796c4ce3e679e98d3693:timer', 'i:1789108469;', 1789108469),
('ourneartalent-cache-d890130f16ae839ba75d2d54472e2fef', 'i:2;', 1788114515),
('ourneartalent-cache-d890130f16ae839ba75d2d54472e2fef:timer', 'i:1788114515;', 1788114515),
('ourneartalent-cache-d9c5bbef2bf39ec5e17669357241d2f4', 'i:3;', 1789137082),
('ourneartalent-cache-d9c5bbef2bf39ec5e17669357241d2f4:timer', 'i:1789137082;', 1789137082),
('ourneartalent-cache-dace2e4c235f4ebecce0e3f1ecf4441d', 'i:1;', 1788576295),
('ourneartalent-cache-dace2e4c235f4ebecce0e3f1ecf4441d:timer', 'i:1788576295;', 1788576295),
('ourneartalent-cache-db89e4e6b7a0135a70dbe907662f3214', 'i:2;', 1789155044),
('ourneartalent-cache-db89e4e6b7a0135a70dbe907662f3214:timer', 'i:1789155044;', 1789155044),
('ourneartalent-cache-dc231fe19f6dc51dcea135a294d28a8f', 'i:1;', 1789053135),
('ourneartalent-cache-dc231fe19f6dc51dcea135a294d28a8f:timer', 'i:1789053135;', 1789053135),
('ourneartalent-cache-dc29d102cc638522b0dfd00c2c089cd3', 'i:1;', 1788158275),
('ourneartalent-cache-dc29d102cc638522b0dfd00c2c089cd3:timer', 'i:1788158275;', 1788158275),
('ourneartalent-cache-dcdaafc0999d32e29abd379b6562c85f', 'i:3;', 1788994707),
('ourneartalent-cache-dcdaafc0999d32e29abd379b6562c85f:timer', 'i:1788994707;', 1788994707),
('ourneartalent-cache-ddebf152b24ec305a32cf7841375e0ae', 'i:1;', 1789053135),
('ourneartalent-cache-ddebf152b24ec305a32cf7841375e0ae:timer', 'i:1789053135;', 1789053135),
('ourneartalent-cache-de695290bd35f37f495700f3799861d1', 'i:1;', 1789046095),
('ourneartalent-cache-de695290bd35f37f495700f3799861d1:timer', 'i:1789046095;', 1789046095),
('ourneartalent-cache-de700fd019c1ae5d420b2c8132d15eea', 'i:2;', 1788550425),
('ourneartalent-cache-de700fd019c1ae5d420b2c8132d15eea:timer', 'i:1788550425;', 1788550425),
('ourneartalent-cache-e1182ec3eb7978f5e86dbe3dacaf0657', 'i:1;', 1789009531),
('ourneartalent-cache-e1182ec3eb7978f5e86dbe3dacaf0657:timer', 'i:1789009531;', 1789009531),
('ourneartalent-cache-e1d8bfb4c81ced5265a8e4dd724a45d8', 'i:1;', 1789113076),
('ourneartalent-cache-e1d8bfb4c81ced5265a8e4dd724a45d8:timer', 'i:1789113076;', 1789113076),
('ourneartalent-cache-e3361227cac7c676c29fa1ea495f6850', 'i:1;', 1788605476),
('ourneartalent-cache-e3361227cac7c676c29fa1ea495f6850:timer', 'i:1788605476;', 1788605476),
('ourneartalent-cache-e3b9a23264f23f3fc5ee046d86dd8c2a', 'i:1;', 1788580998),
('ourneartalent-cache-e3b9a23264f23f3fc5ee046d86dd8c2a:timer', 'i:1788580998;', 1788580998),
('ourneartalent-cache-e769b3f243b109db88b2b2d918fcf7c5', 'i:1;', 1789718404),
('ourneartalent-cache-e769b3f243b109db88b2b2d918fcf7c5:timer', 'i:1789718404;', 1789718404),
('ourneartalent-cache-e76b4e38987bbb7e14ce91f93c233e5c', 'i:1;', 1788258524),
('ourneartalent-cache-e76b4e38987bbb7e14ce91f93c233e5c:timer', 'i:1788258524;', 1788258524),
('ourneartalent-cache-e78896e63debd5d6d50e1d8f061c4f02', 'i:2;', 1788957883),
('ourneartalent-cache-e78896e63debd5d6d50e1d8f061c4f02:timer', 'i:1788957883;', 1788957883),
('ourneartalent-cache-ea4a69fd4fd89696b777cb1eedeb4c79', 'i:3;', 1789061435),
('ourneartalent-cache-ea4a69fd4fd89696b777cb1eedeb4c79:timer', 'i:1789061435;', 1789061435),
('ourneartalent-cache-ece64a00b728519830255debafb6ca3f', 'i:1;', 1789033585),
('ourneartalent-cache-ece64a00b728519830255debafb6ca3f:timer', 'i:1789033585;', 1789033585),
('ourneartalent-cache-ef17bb738d0e5a0574e4cafeae6d4153', 'i:1;', 1788928016),
('ourneartalent-cache-ef17bb738d0e5a0574e4cafeae6d4153:timer', 'i:1788928016;', 1788928016),
('ourneartalent-cache-f339ebd90aee0dafdbf891240a5180c7', 'i:1;', 1789050335),
('ourneartalent-cache-f339ebd90aee0dafdbf891240a5180c7:timer', 'i:1789050335;', 1789050335),
('ourneartalent-cache-f503d178e1f62dc151a2fe8087c52db2', 'i:5;', 1788948030),
('ourneartalent-cache-f503d178e1f62dc151a2fe8087c52db2:timer', 'i:1788948030;', 1788948030),
('ourneartalent-cache-f6d9be7eca9f6fea210bb4563c0c51c9', 'i:1;', 1788968841),
('ourneartalent-cache-f6d9be7eca9f6fea210bb4563c0c51c9:timer', 'i:1788968841;', 1788968841),
('ourneartalent-cache-f85e2175edc1ba87f91b984682b2056c', 'i:3;', 1788994707),
('ourneartalent-cache-f85e2175edc1ba87f91b984682b2056c:timer', 'i:1788994707;', 1788994707),
('ourneartalent-cache-f96fc4fd48185887899fcb545b099a4f', 'i:2;', 1789110975),
('ourneartalent-cache-f96fc4fd48185887899fcb545b099a4f:timer', 'i:1789110975;', 1789110975),
('ourneartalent-cache-fa42bca245d0afc02470fad6acbfe41a', 'i:1;', 1789032256),
('ourneartalent-cache-fa42bca245d0afc02470fad6acbfe41a:timer', 'i:1789032256;', 1789032256),
('ourneartalent-cache-fa449b34f2ce0151961e52de24f58718', 'i:1;', 1789102742),
('ourneartalent-cache-fa449b34f2ce0151961e52de24f58718:timer', 'i:1789102742;', 1789102742),
('ourneartalent-cache-fb55d0199df5a6fecb8f1b7ed5f21ac6', 'i:1;', 1789063876),
('ourneartalent-cache-fb55d0199df5a6fecb8f1b7ed5f21ac6:timer', 'i:1789063876;', 1789063876),
('ourneartalent-cache-fc670d15ad4b922875dc499eef9e0bc3', 'i:2;', 1789036728),
('ourneartalent-cache-fc670d15ad4b922875dc499eef9e0bc3:timer', 'i:1789036728;', 1789036728),
('ourneartalent-cache-pranaykumar60@gmail.com|95.203.39.121', 'i:1;', 1788185541),
('ourneartalent-cache-pranaykumar60@gmail.com|95.203.39.121:timer', 'i:1788185541;', 1788185541),
('ourneartalent-cache-test@example|106.192.17.137', 'i:1;', 1788108923),
('ourneartalent-cache-test@example|106.192.17.137:timer', 'i:1788108923;', 1788108923),
('ourneartalent-cache-venkatadmin09@ournear.com|77.111.246.9', 'i:2;', 1789007704),
('ourneartalent-cache-venkatadmin09@ournear.com|77.111.246.9:timer', 'i:1789007704;', 1789007704);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `candidate_profiles`
--

CREATE TABLE `candidate_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `mobile` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `experience` varchar(255) DEFAULT NULL,
  `qualification` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `candidate_profiles`
--

INSERT INTO `candidate_profiles` (`id`, `user_id`, `mobile`, `location`, `experience`, `qualification`, `created_at`, `updated_at`) VALUES
(1, 2, NULL, NULL, NULL, NULL, '2026-08-30 16:12:16', '2026-08-30 16:12:16'),
(2, 4, NULL, NULL, NULL, NULL, '2026-08-31 14:11:45', '2026-08-31 14:11:45'),
(3, 5, NULL, NULL, NULL, NULL, '2026-09-01 12:53:37', '2026-09-01 12:53:37'),
(4, 6, '7258856722', 'Gujrat', '5 years', 'Mechanical diploma', '2026-09-02 09:51:25', '2026-09-02 09:51:46'),
(5, 7, '+916300648416', 'Nellipudi', '5', 'Diploma', '2026-09-09 02:08:05', '2026-09-09 02:08:29'),
(6, 8, NULL, NULL, NULL, NULL, '2026-09-10 18:46:41', '2026-09-10 18:46:41');

-- --------------------------------------------------------

--
-- Table structure for table `contact_enquiries`
--

CREATE TABLE `contact_enquiries` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `mobile_number` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `interested_in` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'New',
  `internal_notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contact_enquiries`
--

INSERT INTO `contact_enquiries` (`id`, `full_name`, `mobile_number`, `email`, `interested_in`, `message`, `status`, `internal_notes`, `created_at`, `updated_at`) VALUES
(1, 'venkat  Narayana Reddy', '85008582492', 'pvnrengineer@gmail.com', 'Recruitment', 'd', 'New', '[]', '2026-08-30 18:09:51', '2026-08-30 18:09:51'),
(2, 'Arman Ali', '07258856722', 'armanali8454133@gmail.com', 'Recruitment', 'Application for safety officer', 'New', '[]', '2026-09-02 09:50:02', '2026-09-02 09:50:02'),
(3, 'Mahesh Perneedi', '9848249292', 'maheshperneedi@gmail.com', 'Recruitment', 'Any vacancy in structure DPM', 'New', '[]', '2026-09-11 17:31:35', '2026-09-11 17:31:35');

-- --------------------------------------------------------

--
-- Table structure for table `content_items`
--

CREATE TABLE `content_items` (
  `id` int(11) NOT NULL,
  `group_key` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `bullets` text DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `media_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `content_items`
--

INSERT INTO `content_items` (`id`, `group_key`, `type`, `title`, `description`, `icon`, `image_path`, `bullets`, `sort_order`, `is_active`, `created_at`, `updated_at`, `media_id`) VALUES
(1, 'industries', 'industry', 'Mining', 'Operational and project roles in mining environments.', 'industry', NULL, '[]', 0, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(2, 'industries', 'industry', 'Infrastructure', 'Civil, utilities, and infrastructure project support.', 'industry', NULL, '[]', 1, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(3, 'industries', 'industry', 'Construction', 'Site, trade, and project construction workforce.', 'industry', NULL, '[]', 2, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(4, 'core_services', 'service', 'Manpower Supply', 'Reliable people for permanent, contract, and project roles.', 'people', NULL, '[\"Screened candidates\",\"Role-fit matching\"]', 0, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(5, 'core_services', 'service', 'Providing Trainers', 'Trainers who can build capability in the workplace.', 'trainer', NULL, '[\"Practical delivery\",\"Site-ready methods\"]', 1, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(6, 'core_services', 'service', 'Competency Assessment', 'Clear evaluation of skills against the work required.', 'assess', NULL, '[\"Structured checks\",\"Evidence-based outcomes\"]', 2, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(7, 'core_services', 'service', 'Training & Development', 'Training that matches real job requirements.', 'train', NULL, '[\"Job-focused modules\",\"Measurable progress\"]', 3, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(8, 'core_services', 'service', 'Project Support & Management', 'Support that keeps workforce delivery on track.', 'project', NULL, '[\"Planning support\",\"On-site coordination\"]', 4, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(9, 'core_services', 'service', 'Safety & Productivity Improvement', 'Safer work and better output through people and process.', 'safety', NULL, '[\"Compliance focus\",\"Productivity habits\"]', 5, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(10, 'benefits', 'benefit', 'Verified & Reliable Workforce', 'People who have been checked against the role.', 'check', NULL, '[]', 0, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(11, 'benefits', 'benefit', 'Skilled & Job-Ready People', 'Capability that can start work with confidence.', 'skill', NULL, '[]', 1, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(12, 'benefits', 'benefit', 'Rigorous Evaluation & Competency Assessment', 'Assessment before deployment, not after problems appear.', 'evaluate', NULL, '[]', 2, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(13, 'benefits', 'benefit', 'Safety & Compliance Focus', 'A practical emphasis on safe, compliant work.', 'safety', NULL, '[]', 3, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(14, 'benefits', 'benefit', 'Faster Workforce Deployment', 'A clear process that reduces delay.', 'speed', NULL, '[]', 4, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(15, 'benefits', 'benefit', 'Productivity Improvement', 'People and training that support output.', 'output', NULL, '[]', 5, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(16, 'benefits', 'benefit', 'Long-Term Project Support', 'We stay involved beyond the first placement.', 'support', NULL, '[]', 6, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(17, 'benefits', 'benefit', 'Cost-Effective Workforce Solutions', 'The right people and training without unnecessary waste.', 'value', NULL, '[]', 7, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(18, 'home_what_we_do', 'card', 'Right People', 'Candidates who fit the role, the team, and the work.', 'people', NULL, '[]', 0, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(19, 'home_what_we_do', 'card', 'Right Training', 'Capability building that supports real workplace performance.', 'train', NULL, '[]', 1, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(20, 'home_what_we_do', 'card', 'Project Support', 'Practical support so delivery stays on track.', 'project', NULL, '[]', 2, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(21, 'home_how_we_work', 'card', 'Understand', 'We listen to the role, the team, and the result you need.', NULL, NULL, '[]', 0, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(22, 'home_how_we_work', 'card', 'Evaluate', 'We assess people against the work, not just a job title.', NULL, NULL, '[]', 1, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(23, 'home_how_we_work', 'card', 'Train', 'We close skill gaps with practical, job-focused training.', NULL, NULL, '[]', 2, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(24, 'home_how_we_work', 'card', 'Deploy', 'We place people ready to contribute on day one.', NULL, NULL, '[]', 3, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(25, 'home_how_we_work', 'card', 'Support', 'We stay involved so the placement holds.', NULL, NULL, '[]', 4, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(26, 'home_commitment', 'card', 'Clear communication', 'Employers and candidates know where things stand.', NULL, NULL, '[]', 0, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(27, 'home_commitment', 'card', 'Dependable delivery', 'We stay accountable to the agreed result.', NULL, NULL, '[]', 1, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(28, 'home_commitment', 'card', 'Quality of match', 'We measure success by fit, not volume.', NULL, NULL, '[]', 2, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(29, 'about_who_we_are', 'card', 'Industry Focused', 'We understand operational and project environments.', NULL, NULL, '[]', 0, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(30, 'about_who_we_are', 'card', 'Experienced Team', 'Practical experience in people, training, and delivery.', NULL, NULL, '[]', 1, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(31, 'about_who_we_are', 'card', 'Results Driven', 'We stay focused on the outcome, not just filling a vacancy.', NULL, NULL, '[]', 2, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(32, 'about_what_we_do', 'card', 'Right People', 'Recruitment and supply of capable workers, staff, and professionals.', NULL, NULL, '[\"Workforce supply\",\"Role matching\"]', 0, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(33, 'about_what_we_do', 'card', 'Right Training', 'Training and competency support that matches workplace needs.', NULL, NULL, '[\"Trainer supply\",\"Competency assessment\"]', 1, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(34, 'about_approach', 'card', 'People', 'Find and place the right people for the work.', NULL, NULL, '[]', 0, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(35, 'about_approach', 'card', 'Training', 'Build the capability the role actually requires.', NULL, NULL, '[]', 1, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(36, 'about_approach', 'card', 'Performance', 'Support delivery, safety, and productivity after placement.', NULL, NULL, '[]', 2, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(37, 'about_commitment', 'card', 'Honest advice', 'We say what we can deliver.', NULL, NULL, '[]', 0, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(38, 'about_commitment', 'card', 'Follow-through', 'We stay with the assignment after go-live.', NULL, NULL, '[]', 1, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(39, 'services_workforce', 'card', 'Workforce & Labour', NULL, NULL, NULL, '[]', 0, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(40, 'services_workforce', 'card', 'Equipment Operators', NULL, NULL, NULL, '[]', 1, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(41, 'services_workforce', 'card', 'Drivers', NULL, NULL, NULL, '[]', 2, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(42, 'services_workforce', 'card', 'Technical & Maintenance', NULL, NULL, NULL, '[]', 3, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(43, 'services_workforce', 'card', 'Supervisory & Site Staff', NULL, NULL, NULL, '[]', 4, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(44, 'services_workforce', 'card', 'Engineering & Professionals', NULL, NULL, NULL, '[]', 5, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(45, 'services_workforce', 'card', 'Management & Support', NULL, NULL, NULL, '[]', 6, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(46, 'services_training', 'card', 'Induction and workplace basics', 'Core training so people can start safely.', NULL, NULL, '[]', 0, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(47, 'services_training', 'card', 'Trade and technical skills', 'Training aligned to the tools and tasks on site.', NULL, NULL, '[]', 1, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(48, 'services_training', 'card', 'Supervisory capability', 'Support for team leaders and site supervisors.', NULL, NULL, '[]', 2, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(49, 'services_training', 'card', 'Safety and compliance', 'Practical training that supports safe delivery.', NULL, NULL, '[]', 3, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(50, 'services_delivery_process', 'card', 'Brief', 'Understand the role, site, and result.', NULL, NULL, '[]', 0, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(51, 'services_delivery_process', 'card', 'Source', 'Identify people who can do the work.', NULL, NULL, '[]', 1, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(52, 'services_delivery_process', 'card', 'Evaluate', 'Check capability before deployment.', NULL, NULL, '[]', 2, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(53, 'services_delivery_process', 'card', 'Deploy', 'Place people with a clear handover.', NULL, NULL, '[]', 3, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(54, 'services_delivery_process', 'card', 'Review', 'Follow up on fit and performance.', NULL, NULL, '[]', 4, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(55, 'services_competency_process', 'card', 'Assess', 'Establish current capability.', NULL, NULL, '[]', 0, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(56, 'services_competency_process', 'card', 'Provide Training', 'Close identified gaps.', NULL, NULL, '[]', 1, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(57, 'services_competency_process', 'card', 'Test Competency', 'Confirm people can do the work.', NULL, NULL, '[]', 2, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(58, 'services_competency_process', 'card', 'Report', 'Share clear outcomes with the employer.', NULL, NULL, '[]', 3, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(59, 'services_competency_process', 'card', 'Improve', 'Adjust training and deployment from evidence.', NULL, NULL, '[]', 4, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(60, 'services_project_support', 'card', 'Plan', 'Define workforce and training needs.', NULL, NULL, '[]', 0, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(61, 'services_project_support', 'card', 'Staff', 'Supply the people the project requires.', NULL, NULL, '[]', 1, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(62, 'services_project_support', 'card', 'Enable', 'Train and assess for the work ahead.', NULL, NULL, '[]', 2, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(63, 'services_project_support', 'card', 'Manage', 'Keep delivery, safety, and communication in view.', NULL, NULL, '[]', 3, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(64, 'services_project_support', 'card', 'Sustain', 'Support replacements and ongoing performance.', NULL, NULL, '[]', 4, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(65, 'employer_offerings', 'card', 'Workforce Supply', 'People for permanent, contract, and project needs.', NULL, NULL, '[]', 0, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(66, 'employer_offerings', 'card', 'Trainers & Training', 'Trainers and programmes matched to the workplace.', NULL, NULL, '[]', 1, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(67, 'employer_offerings', 'card', 'Competency Assessment', 'Evidence that people can perform the role.', NULL, NULL, '[]', 2, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(68, 'employer_offerings', 'card', 'Workforce Deployment', 'A structured path from brief to start date.', NULL, NULL, '[]', 3, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(69, 'employer_offerings', 'card', 'Safety & Productivity', 'Support that protects people and output.', NULL, NULL, '[]', 4, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(70, 'employer_offerings', 'card', 'Continuous Support', 'Follow-up after people are on site.', NULL, NULL, '[]', 5, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(71, 'employer_how_we_deliver', 'card', 'Understand the brief', NULL, NULL, NULL, '[]', 0, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(72, 'employer_how_we_deliver', 'card', 'Define the role and outcome', NULL, NULL, NULL, '[]', 1, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(73, 'employer_how_we_deliver', 'card', 'Source suitable people', NULL, NULL, NULL, '[]', 2, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(74, 'employer_how_we_deliver', 'card', 'Evaluate capability', NULL, NULL, NULL, '[]', 3, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(75, 'employer_how_we_deliver', 'card', 'Train where needed', NULL, NULL, NULL, '[]', 4, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(76, 'employer_how_we_deliver', 'card', 'Deploy to site or workplace', NULL, NULL, NULL, '[]', 5, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(77, 'employer_how_we_deliver', 'card', 'Support the placement', NULL, NULL, NULL, '[]', 6, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(78, 'employer_how_we_deliver', 'card', 'Review and improve', NULL, NULL, NULL, '[]', 7, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(79, 'employer_workforce', 'card', 'Workforce & Labour', NULL, NULL, NULL, '[]', 0, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(80, 'employer_workforce', 'card', 'Operators and drivers', NULL, NULL, NULL, '[]', 1, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(81, 'employer_workforce', 'card', 'Technical and maintenance', NULL, NULL, NULL, '[]', 2, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(82, 'employer_workforce', 'card', 'Supervisory staff', NULL, NULL, NULL, '[]', 3, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(83, 'employer_workforce', 'card', 'Professional and management support', NULL, NULL, NULL, '[]', 4, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employer_enquiries`
--

CREATE TABLE `employer_enquiries` (
  `id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `contact_person` varchar(255) NOT NULL,
  `mobile_number` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `industry` varchar(255) NOT NULL,
  `project_location` varchar(255) NOT NULL,
  `workforce_type` varchar(255) NOT NULL,
  `number_of_people` int(11) NOT NULL,
  `required_skills` text NOT NULL,
  `expected_start_date` date DEFAULT NULL,
  `training_required` tinyint(1) NOT NULL DEFAULT 0,
  `additional_requirements` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'New',
  `internal_notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employer_enquiries`
--

INSERT INTO `employer_enquiries` (`id`, `company_name`, `contact_person`, `mobile_number`, `email`, `industry`, `project_location`, `workforce_type`, `number_of_people`, `required_skills`, `expected_start_date`, `training_required`, `additional_requirements`, `status`, `internal_notes`, `created_at`, `updated_at`, `user_id`) VALUES
(1, 'Individual', 'venkat  Narayana Reddy', '85008582492', 'pvnrengineer@gmail.com', 'Mining', 'hyd', 'Permanent', 1, 'fc', '2026-09-02', 1, NULL, 'In Progress', '[{\"body\":\"dd\",\"author_id\":1,\"author_name\":\"ounear\",\"created_at\":\"2026-08-30T18:07:51+00:00\"}]', '2026-08-30 18:06:39', '2026-08-30 18:07:51', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employer_profiles`
--

CREATE TABLE `employer_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `mobile_number` varchar(255) DEFAULT NULL,
  `industry` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employer_profiles`
--

INSERT INTO `employer_profiles` (`id`, `user_id`, `company_name`, `contact_person`, `mobile_number`, `industry`, `created_at`, `updated_at`) VALUES
(1, 3, 'Individual', 'venkat  Narayana Reddy', '85008582492', NULL, '2026-08-30 16:13:44', '2026-08-30 16:14:10');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` int(11) NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` text NOT NULL,
  `exception` text NOT NULL,
  `failed_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` int(11) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` text NOT NULL,
  `attempts` int(11) NOT NULL,
  `reserved_at` int(11) DEFAULT NULL,
  `available_at` int(11) NOT NULL,
  `created_at` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`id`, `queue`, `payload`, `attempts`, `reserved_at`, `available_at`, `created_at`) VALUES
(1, 'default', '{\"uuid\":\"8ab57b72-433d-485d-8f44-e13d1e54460f\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:22:\\\"pvnrengineer@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1788110923,\"delay\":null}', 0, NULL, 1788110923, 1788110923),
(2, 'default', '{\"uuid\":\"235bf3d5-72b7-45ed-b91e-956f5cad7958\",\"displayName\":\"App\\\\Mail\\\\EmployerEnquirySubmittedEmployerMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:45:\\\"App\\\\Mail\\\\EmployerEnquirySubmittedEmployerMail\\\":3:{s:7:\\\"enquiry\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:26:\\\"App\\\\Models\\\\EmployerEnquiry\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:22:\\\"pvnrengineer@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1788113199,\"delay\":null}', 0, NULL, 1788113199, 1788113199),
(3, 'default', '{\"uuid\":\"81ba4a5b-b80b-43e1-bebb-dc6a9bd0d663\",\"displayName\":\"App\\\\Mail\\\\ContactEnquirySubmittedSubmitterMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:45:\\\"App\\\\Mail\\\\ContactEnquirySubmittedSubmitterMail\\\":3:{s:7:\\\"enquiry\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:25:\\\"App\\\\Models\\\\ContactEnquiry\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:22:\\\"pvnrengineer@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1788113391,\"delay\":null}', 0, NULL, 1788113391, 1788113391),
(4, 'default', '{\"uuid\":\"d42caa99-5d5d-4262-87ba-66074cc5f720\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:2;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:23:\\\"albyaneesh203@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1788154244,\"delay\":null}', 0, NULL, 1788154244, 1788154244),
(5, 'default', '{\"uuid\":\"c8a5a4b0-0914-4ddb-8b84-002a058b4d0a\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:3;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:19:\\\"anujacp14@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1788154324,\"delay\":null}', 0, NULL, 1788154324, 1788154324),
(6, 'default', '{\"uuid\":\"d097abfb-81cb-4bb3-94ac-7b057acab045\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:4;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:23:\\\"sahooasish786@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1788154675,\"delay\":null}', 0, NULL, 1788154675, 1788154675),
(7, 'default', '{\"uuid\":\"76d51d53-f46b-4b31-8785-f619631841b8\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:5;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:20:\\\"fayizahfas@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1788178335,\"delay\":null}', 0, NULL, 1788178335, 1788178335),
(8, 'default', '{\"uuid\":\"680d578b-e195-4f22-94cd-3fc74f46bd02\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:6;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:20:\\\"fayizahfas@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1788180697,\"delay\":null}', 0, NULL, 1788180697, 1788180697),
(9, 'default', '{\"uuid\":\"beb44d60-d832-406f-a51c-c61dee3652ae\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:7;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:23:\\\"pranaykumar60@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1788184365,\"delay\":null}', 0, NULL, 1788184365, 1788184365),
(10, 'default', '{\"uuid\":\"ceb996ce-9c3e-4fe8-9878-b7d9c444e2eb\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:8;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:24:\\\"surveyoramirul@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1788185036,\"delay\":null}', 0, NULL, 1788185036, 1788185036),
(11, 'default', '{\"uuid\":\"a8701708-7730-4d56-984d-370a7dc0141b\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:9;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:23:\\\"pranaykumar60@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1788185270,\"delay\":null}', 0, NULL, 1788185270, 1788185270),
(12, 'default', '{\"uuid\":\"0828abd2-53e3-4f89-b244-3a41972f7e78\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:10;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:17:\\\"rikun87@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1788193086,\"delay\":null}', 0, NULL, 1788193086, 1788193086),
(13, 'default', '{\"uuid\":\"c0845f79-7ab0-4a88-bcac-394791ec2f6d\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:11;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:23:\\\"sonysunita950@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1788239103,\"delay\":null}', 0, NULL, 1788239103, 1788239103),
(14, 'default', '{\"uuid\":\"659e8d84-59ce-48ec-94a7-55d976a0ee4f\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:12;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:23:\\\"maniankarla13@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1788268576,\"delay\":null}', 0, NULL, 1788268576, 1788268576),
(15, 'default', '{\"uuid\":\"cbfa16b4-43a2-446e-b1e3-8bd02e035c23\",\"displayName\":\"App\\\\Mail\\\\ContactEnquirySubmittedSubmitterMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:45:\\\"App\\\\Mail\\\\ContactEnquirySubmittedSubmitterMail\\\":3:{s:7:\\\"enquiry\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:25:\\\"App\\\\Models\\\\ContactEnquiry\\\";s:2:\\\"id\\\";i:2;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:25:\\\"armanali8454133@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1788342602,\"delay\":null}', 0, NULL, 1788342602, 1788342602),
(16, 'default', '{\"uuid\":\"e1bd5b86-1fcd-4544-a864-81e2bca5e228\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:13;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:21:\\\"ram.chikram@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1788356142,\"delay\":null}', 0, NULL, 1788356142, 1788356142),
(17, 'default', '{\"uuid\":\"4c6d6f8d-3892-43ec-9e8f-d8873fe3ccb7\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:14;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:19:\\\"trailemai@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1788409711,\"delay\":null}', 0, NULL, 1788409711, 1788409711),
(18, 'default', '{\"uuid\":\"c2642e4d-d992-475e-86b6-de5795c97bd4\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:15;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:21:\\\"mukeshpadhi@ymail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1788414591,\"delay\":null}', 0, NULL, 1788414591, 1788414591),
(19, 'default', '{\"uuid\":\"c3dde366-3527-4e71-a3da-c0a2005b55ce\",\"displayName\":\"App\\\\Mail\\\\ApplicationRejectedMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:32:\\\"App\\\\Mail\\\\ApplicationRejectedMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:15;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:21:\\\"mukeshpadhi@ymail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1788504132,\"delay\":null}', 0, NULL, 1788504132, 1788504132),
(20, 'default', '{\"uuid\":\"a2e85001-6c10-439b-bdda-6380b5a487ca\",\"displayName\":\"App\\\\Mail\\\\ApplicationRejectedMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:32:\\\"App\\\\Mail\\\\ApplicationRejectedMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:14;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:19:\\\"trailemai@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1788504154,\"delay\":null}', 0, NULL, 1788504154, 1788504154),
(21, 'default', '{\"uuid\":\"d582eb48-9680-4c97-8a0b-eee5afcce86e\",\"displayName\":\"App\\\\Mail\\\\ApplicationRejectedMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:32:\\\"App\\\\Mail\\\\ApplicationRejectedMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:11;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:23:\\\"sonysunita950@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1788504745,\"delay\":null}', 0, NULL, 1788504745, 1788504745),
(22, 'default', '{\"uuid\":\"4c97a10f-b043-467f-8c8c-d3bcd28cd102\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:16;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:22:\\\"chinnu756999@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1788522471,\"delay\":null}', 0, NULL, 1788522471, 1788522471),
(23, 'default', '{\"uuid\":\"117bb264-b809-4a6a-868b-311e354c2734\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:17;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:21:\\\"rajdhakad07@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1788532776,\"delay\":null}', 0, NULL, 1788532776, 1788532776),
(24, 'default', '{\"uuid\":\"4ff31547-574d-41e2-80f6-b8a861e762a7\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:18;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:25:\\\"pailaharish2000@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1788546825,\"delay\":null}', 0, NULL, 1788546825, 1788546825),
(25, 'default', '{\"uuid\":\"038d08b1-4a75-4007-970a-2f4df1039de2\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:19;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:25:\\\"pailaharish2000@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1788546939,\"delay\":null}', 0, NULL, 1788546939, 1788546939),
(26, 'default', '{\"uuid\":\"be81c8c3-884a-408d-9149-485f6f470c98\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:20;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:23:\\\"kiranbandam27@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1788572695,\"delay\":null}', 0, NULL, 1788572695, 1788572695),
(27, 'default', '{\"uuid\":\"3f6f309c-dea7-44cc-9ac9-dab142462314\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:21;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:25:\\\"amansatsangi703@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1788577398,\"delay\":null}', 0, NULL, 1788577398, 1788577398),
(28, 'default', '{\"uuid\":\"e65cb84a-0c78-40f8-ae4f-ecfd6562ab81\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:22;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:23:\\\"hari_harish06@yahoo.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1788601876,\"delay\":null}', 0, NULL, 1788601876, 1788601876),
(29, 'default', '{\"uuid\":\"25e7643f-c493-4069-93c3-d4187f131b9e\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:23;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:23:\\\"khanfaraz7788@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1788625975,\"delay\":null}', 0, NULL, 1788625975, 1788625975),
(30, 'default', '{\"uuid\":\"bdbcb06e-67fa-433e-a7de-54fb3cd015d5\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:24;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:30:\\\"gaddamdhananjaneyulu@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1788924416,\"delay\":null}', 0, NULL, 1788924416, 1788924416),
(31, 'default', '{\"uuid\":\"7098a844-3bcb-4d86-8b85-a201b3eb0233\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:25;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:28:\\\"samishaik.samiulla@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1788934050,\"delay\":null}', 0, NULL, 1788934050, 1788934050),
(32, 'default', '{\"uuid\":\"6e3801e8-96a4-45a6-9264-80800c22b940\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:26;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:25:\\\"pawanyadav86825@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1788941509,\"delay\":null}', 0, NULL, 1788941509, 1788941509),
(33, 'default', '{\"uuid\":\"d1921709-6a55-4f63-b7f4-3d8971b81e73\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:27;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:21:\\\"sneha121305@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1788944430,\"delay\":null}', 0, NULL, 1788944430, 1788944430);
INSERT INTO `jobs` (`id`, `queue`, `payload`, `attempts`, `reserved_at`, `available_at`, `created_at`) VALUES
(34, 'default', '{\"uuid\":\"1f2fc7de-321b-4f23-b955-855766687315\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:28;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:21:\\\"sneha121305@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1788944594,\"delay\":null}', 0, NULL, 1788944594, 1788944594),
(35, 'default', '{\"uuid\":\"4c1e2b9d-9436-478b-8e96-81bfb40fa393\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:29;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:21:\\\"sneha121305@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1788944681,\"delay\":null}', 0, NULL, 1788944681, 1788944681),
(36, 'default', '{\"uuid\":\"a901fa52-fd28-4a5c-bed5-47eb13bcfe82\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:30;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:21:\\\"sneha121305@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1788944739,\"delay\":null}', 0, NULL, 1788944739, 1788944739),
(37, 'default', '{\"uuid\":\"78b81e75-c957-42e0-b90d-370bbf737af8\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:31;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:27:\\\"rohitkumarmatho65@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1788954297,\"delay\":null}', 0, NULL, 1788954297, 1788954297),
(38, 'default', '{\"uuid\":\"f7398efe-2460-4d89-baf6-b0fbbc9f494f\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:32;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:27:\\\"sivasankarorsu114@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1788959774,\"delay\":null}', 0, NULL, 1788959774, 1788959774),
(39, 'default', '{\"uuid\":\"7f5b72ba-f9f2-456f-83b2-6643545c000f\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:33;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:25:\\\"saddamshaik2002@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1788980480,\"delay\":null}', 0, NULL, 1788980480, 1788980480),
(40, 'default', '{\"uuid\":\"9f10aa73-8f59-4716-9fcf-855b14726b4e\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:34;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:23:\\\"kalyanswain25@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789000472,\"delay\":null}', 0, NULL, 1789000472, 1789000472),
(41, 'default', '{\"uuid\":\"1c1fbd57-8f5d-49f1-aa7f-a91a4405cfac\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:35;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:22:\\\"durga.rao364@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789008967,\"delay\":null}', 0, NULL, 1789008967, 1789008967),
(42, 'default', '{\"uuid\":\"7a015ab5-f7ae-4ae0-8b2d-bb43fb56e810\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:36;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:25:\\\"richardroycill8@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789013275,\"delay\":null}', 0, NULL, 1789013275, 1789013275),
(43, 'default', '{\"uuid\":\"90416c12-83b1-4e1c-8645-7551d892f918\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:37;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:25:\\\"mujtabasayed143@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789017322,\"delay\":null}', 0, NULL, 1789017322, 1789017322),
(44, 'default', '{\"uuid\":\"df303f86-0f42-4fa0-a952-b702e6034f4c\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:38;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:23:\\\"mail2mohansai@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789020432,\"delay\":null}', 0, NULL, 1789020432, 1789020432),
(45, 'default', '{\"uuid\":\"a57e8713-5b01-47a3-b55d-816c5103460b\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:39;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:23:\\\"rajaramesh883@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789028656,\"delay\":null}', 0, NULL, 1789028656, 1789028656),
(46, 'default', '{\"uuid\":\"78bdf02d-6c21-4249-8c7b-3acc972f109f\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:40;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:22:\\\"malivinesh19@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789029986,\"delay\":null}', 0, NULL, 1789029986, 1789029986),
(47, 'default', '{\"uuid\":\"356829fb-ee1f-4c74-a83b-f7f184314ac8\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:41;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:25:\\\"dasarimohanrao2@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789033128,\"delay\":null}', 0, NULL, 1789033128, 1789033128),
(48, 'default', '{\"uuid\":\"111b20ce-d9ad-41db-9f65-1f2aa97b5eb9\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:42;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:17:\\\"vydhu.t@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789033648,\"delay\":null}', 0, NULL, 1789033648, 1789033648),
(49, 'default', '{\"uuid\":\"24e0d7a9-b9f9-4a87-afb4-f393b57f4746\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:43;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:24:\\\"mdismail851774@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789040328,\"delay\":null}', 0, NULL, 1789040328, 1789040328),
(50, 'default', '{\"uuid\":\"28021cb2-9fb4-4d67-9d5f-789a4fc68315\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:44;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:24:\\\"mdismail851774@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789040445,\"delay\":null}', 0, NULL, 1789040445, 1789040445),
(51, 'default', '{\"uuid\":\"403f5a46-fe68-4aff-9cde-0be64cd89308\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:45;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:24:\\\"mdismail851774@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789040496,\"delay\":null}', 0, NULL, 1789040496, 1789040496),
(52, 'default', '{\"uuid\":\"ef08fa6d-ae21-440e-9ac4-0655a7f6ac13\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:46;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:21:\\\"manoha93.ch@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789042496,\"delay\":null}', 0, NULL, 1789042496, 1789042496),
(53, 'default', '{\"uuid\":\"f95a4949-b24e-4122-96fc-8d18eeb0f11b\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:47;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:31:\\\"manikantanunnagoppala@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789042649,\"delay\":null}', 0, NULL, 1789042649, 1789042649),
(54, 'default', '{\"uuid\":\"29448972-0cfc-4f97-bb88-e49c6617f540\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:48;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:25:\\\"jayanthgowda897@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789049488,\"delay\":null}', 0, NULL, 1789049488, 1789049488),
(55, 'default', '{\"uuid\":\"49d16022-3919-4139-a140-c17c9144c6db\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:49;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:30:\\\"surendraprajapati404@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789049535,\"delay\":null}', 0, NULL, 1789049535, 1789049535),
(56, 'default', '{\"uuid\":\"b24175d9-c1f0-4890-9feb-8fe13c3d7e96\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:50;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:30:\\\"dineshkumargunta1998@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789057835,\"delay\":null}', 0, NULL, 1789057835, 1789057835),
(57, 'default', '{\"uuid\":\"39c6d4ba-79e5-462f-b1c4-07ee63643fd3\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:51;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:30:\\\"dineshkumargunta1998@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789058002,\"delay\":null}', 0, NULL, 1789058002, 1789058002),
(58, 'default', '{\"uuid\":\"664bd3ad-8faf-4e50-9b5f-558b3d41e5eb\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:52;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:30:\\\"dineshkumargunta1998@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789058198,\"delay\":null}', 0, NULL, 1789058198, 1789058198),
(59, 'default', '{\"uuid\":\"54a50e88-e396-46c1-ad48-37e71746d3a2\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:53;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:23:\\\"nagashekar455@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789060276,\"delay\":null}', 0, NULL, 1789060276, 1789060276),
(60, 'default', '{\"uuid\":\"ebfc6d40-09c3-41dc-976f-25f453bf92f7\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:54;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:19:\\\"mdirfaan7@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789099142,\"delay\":null}', 0, NULL, 1789099142, 1789099142),
(61, 'default', '{\"uuid\":\"963531df-6391-4624-b5ef-0e093c213419\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:55;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:35:\\\"mritunjayupadhyaya1234567@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789101045,\"delay\":null}', 0, NULL, 1789101045, 1789101045),
(62, 'default', '{\"uuid\":\"13c72408-918c-4bd9-b6a1-b702ce9e0f02\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:56;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:26:\\\"sumanmondal03632@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789104922,\"delay\":null}', 0, NULL, 1789104922, 1789104922),
(63, 'default', '{\"uuid\":\"c2feab07-dbb9-4b06-b855-28c6db881d9f\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:57;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:24:\\\"ramesh.ramu.48@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789107595,\"delay\":null}', 0, NULL, 1789107595, 1789107595),
(64, 'default', '{\"uuid\":\"f3f47499-f9ac-4e34-9985-5c4cc4d3c161\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:58;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:23:\\\"mukun.sbp1989@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789108728,\"delay\":null}', 0, NULL, 1789108728, 1789108728),
(65, 'default', '{\"uuid\":\"aa545fae-2641-43b3-8d80-74470fe941ae\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:59;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:27:\\\"susanthmahapatro8@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789109476,\"delay\":null}', 0, NULL, 1789109476, 1789109476),
(66, 'default', '{\"uuid\":\"4d3d4118-122e-498e-bc75-ef84b8ca5102\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:60;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:18:\\\"jitu0045@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789116665,\"delay\":null}', 0, NULL, 1789116665, 1789116665);
INSERT INTO `jobs` (`id`, `queue`, `payload`, `attempts`, `reserved_at`, `available_at`, `created_at`) VALUES
(67, 'default', '{\"uuid\":\"6ffa12ca-b209-4ab0-a367-e6f4bada4055\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:61;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:24:\\\"rajavardhan355@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789121233,\"delay\":null}', 0, NULL, 1789121233, 1789121233),
(68, 'default', '{\"uuid\":\"bbe5a571-c59e-47ab-aa3f-c542708ecbc5\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:62;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:31:\\\"mattaparthirambabu418@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789127575,\"delay\":null}', 0, NULL, 1789127575, 1789127575),
(69, 'default', '{\"uuid\":\"b18c9ec8-7923-4df9-8d5c-75efa8a94cb9\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:63;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:28:\\\"raju.raju.chinna95@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789133509,\"delay\":null}', 0, NULL, 1789133509, 1789133509),
(70, 'default', '{\"uuid\":\"b8bd4dc9-20ff-4a38-bc59-b490696cb41a\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:64;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:28:\\\"raju.raju.chinna95@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789133604,\"delay\":null}', 0, NULL, 1789133604, 1789133604),
(71, 'default', '{\"uuid\":\"d9fc1262-bb2e-4dee-845b-79bd1cfa09b4\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:65;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:23:\\\"sekhar1761.ks@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789134380,\"delay\":null}', 0, NULL, 1789134380, 1789134380),
(72, 'default', '{\"uuid\":\"5f37b2eb-360a-4b7b-b3a3-c95768de3253\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:66;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:28:\\\"ajitsingh95.survey@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789140147,\"delay\":null}', 0, NULL, 1789140147, 1789140147),
(73, 'default', '{\"uuid\":\"676ad8bb-ee60-4587-88f7-59719eb73cab\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:67;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:23:\\\"ganeshcat2006@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789145029,\"delay\":null}', 0, NULL, 1789145029, 1789145029),
(74, 'default', '{\"uuid\":\"3e3bd249-d600-4c4c-925e-f371ee59790c\",\"displayName\":\"App\\\\Mail\\\\ContactEnquirySubmittedSubmitterMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:45:\\\"App\\\\Mail\\\\ContactEnquirySubmittedSubmitterMail\\\":3:{s:7:\\\"enquiry\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:25:\\\"App\\\\Models\\\\ContactEnquiry\\\";s:2:\\\"id\\\";i:3;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:24:\\\"maheshperneedi@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789147895,\"delay\":null}', 0, NULL, 1789147895, 1789147895),
(75, 'default', '{\"uuid\":\"b7bd42b4-96a2-4442-b7d0-278ceb563c49\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:68;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:22:\\\"ziauddin2207@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789151455,\"delay\":null}', 0, NULL, 1789151455, 1789151455),
(76, 'default', '{\"uuid\":\"9aec1d63-9824-440c-9fa8-9dbfdd568b5d\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:69;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:26:\\\"emanimaheshkumar@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789153984,\"delay\":null}', 0, NULL, 1789153984, 1789153984),
(77, 'default', '{\"uuid\":\"bef8e0f6-5173-44fa-bce1-0bddd15a4de2\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:70;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:24:\\\"tarunvictory88@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789211367,\"delay\":null}', 0, NULL, 1789211367, 1789211367),
(78, 'default', '{\"uuid\":\"1a3b8a42-b67c-4dfb-95aa-85e6db32de70\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:71;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:26:\\\"doluibaburam1987@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789393816,\"delay\":null}', 0, NULL, 1789393816, 1789393816),
(79, 'default', '{\"uuid\":\"236a2bd9-da2c-4ead-9592-126f30d44358\",\"displayName\":\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:42:\\\"App\\\\Mail\\\\ApplicationSubmittedApplicantMail\\\":3:{s:11:\\\"application\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\Application\\\";s:2:\\\"id\\\";i:72;s:9:\\\"relations\\\";a:1:{i:0;s:3:\\\"job\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:20:\\\"aaajit8795@gmail.com\\\";}}s:6:\\\"mailer\\\";s:3:\\\"log\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1789714804,\"delay\":null}', 0, NULL, 1789714804, 1789714804);

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` text NOT NULL,
  `options` text DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_listings`
--

CREATE TABLE `job_listings` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `project` varchar(255) NOT NULL,
  `industry` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `employment_type` varchar(255) NOT NULL,
  `experience` varchar(255) NOT NULL,
  `salary` varchar(255) DEFAULT NULL,
  `vacancy` int(11) NOT NULL DEFAULT 1,
  `posted_date` date NOT NULL,
  `closing_date` date DEFAULT NULL,
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `publishing_status` varchar(255) NOT NULL DEFAULT 'draft',
  `description` text NOT NULL,
  `responsibilities` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `what_we_offer` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `job_listings`
--

INSERT INTO `job_listings` (`id`, `title`, `slug`, `project`, `industry`, `category`, `location`, `employment_type`, `experience`, `salary`, `vacancy`, `posted_date`, `closing_date`, `featured`, `active`, `publishing_status`, `description`, `responsibilities`, `requirements`, `what_we_offer`, `created_at`, `updated_at`) VALUES
(1, 'Manager Geology', 'manager-geology', 'Not Mentioned', 'Mining', 'Mining', 'Odisha', 'Full-time', '10+ years', NULL, 1, '2026-08-30', '2026-09-30', 1, 1, 'published', 'Plan and execute exploration programs\nInterpret geological data and reports\nPrepare resource estimation and geological models\nEnsure compliance with standards', 'Strong knowledge of geological work and geological mapping\nExperience in mineral exploration and reserve estimation\nKnowledge of borehole planning and geological data management\nAbility to handle geological investigations and prepare technical reports\nExperience in mine geology and coordination with mining operations\nGood analytical and technical reporting skills', 'Department: Geology\nQualification: M.Sc. in Geology\nExperience: 10+ Years', NULL, '2026-08-30 17:27:49', '2026-09-01 09:39:24'),
(2, 'Fresher/GET Geology', 'fresherget-geology', 'NOT MENTIONED', 'Mining', 'Mining', 'Odisha', 'Full-time', 'Fresher', NULL, 1, '2026-08-30', '2026-09-30', 0, 1, 'published', 'Basic knowledge of geology and geological field activities\nWillingness to work at mining project/site\nAbility to assist in geological mapping and exploration activities\nBasic understanding of geological data collection and documentation\nGood learning ability and willingness to work in field conditions\nGood communication and teamwork skills', NULL, 'Department: Geology\nQualification: B.Sc. / M.Sc. in Geology / GET\nExperience: Fresher', NULL, '2026-08-30 17:34:04', '2026-09-01 09:39:40'),
(3, 'AGM  Environment & Forest', 'agm-environment-forest', 'NOT MENTIONED', 'Mining', 'Mining', 'Odisha', 'Full-time', '15+ Years', NULL, 1, '2026-08-30', NULL, 0, 1, 'draft', 'Strong experience in EC/FC compliance and environmental monitoring\nKnowledge of forest clearance and Stage-I / Stage-II compliance\nAbility to prepare and process Forest Clearance proposals\nKnowledge of pollution control requirements and environmental regulations\nCoordination with regulatory authorities and government departments\nExperience in handling environmental and forest related statutory matters', NULL, 'Department: Environment & Forest\nQualification: Environmental Engineering', NULL, '2026-08-30 17:36:05', '2026-09-01 09:39:50'),
(4, 'MANAGER  GOVERNMENT OFFICE LIAISON', 'manager-government-office-liaison', 'NOT MENTIONED', 'Mining', 'Mining', 'Odisha', 'Full-time', '10+ Years', NULL, 1, '2026-08-30', '2026-09-30', 1, 1, 'published', 'Strong liaison and coordination with government departments\nKnowledge of statutory approvals and government procedures\nFollow up on mining lease, EC, FC and other statutory approvals\nExperience in government office coordination and documentation\nGood communication, negotiation and follow-up skills', NULL, 'Department: Government Office Liaison\nQualification: Any Graduate with good computer knowledge', NULL, '2026-08-30 17:38:32', '2026-09-01 09:40:00'),
(5, 'EXECUTIVE / OFFICER  GOVERNMENT OFFICE LIAISON', 'executive-officer-government-office-liaison', 'NOT MENTIONED', 'Mining', 'Mining', 'Odisha', 'Full-time', '5+ Years', NULL, 1, '2026-08-30', '2026-09-30', 0, 1, 'published', 'Government office liaison and follow-up activities\nCoordination with government departments and concerned authorities\nAssistance in obtaining statutory approvals and permissions\nFollow-up of mining lease, EC, FC and other government related matters\nGood documentation and computer skills\nStrong communication and coordination abilities', 'Department: Government Office Liaison\nQualification: Any Graduate with good computer knowledge', NULL, NULL, '2026-08-30 17:40:06', '2026-09-01 09:40:20'),
(6, 'MANAGER  LAND LIAISON', 'manager-land-liaison', 'NOT MENTIONED', 'Mining', 'Mining', 'Odisha', 'Full-time', '10+ Years', NULL, 1, '2026-08-30', NULL, 1, 1, 'published', 'Experience in government & private land identification and acquisition\nKnowledge of land acquisition and land related documentation\nCoordination with government authorities for land related matters\nHandling compensation and payment related cases\nAbility to follow up on land acquisition and disbursement matters\nGood negotiation, communication and stakeholder management skills', NULL, 'Department: Land Liaison\nQualification: Any Graduate with good computer knowledge', NULL, '2026-08-30 17:41:34', '2026-09-01 09:40:32'),
(7, 'JUNIOR OFFICER  LAND LIAISON', 'junior-officer-land-liaison', 'NOT MENTIONED', 'Mining', 'Mining', 'Odisha', 'Full-time', '5+ Years', NULL, 1, '2026-08-30', '2026-09-30', 0, 1, 'published', 'Knowledge of land related documentation and identification\nAssistance in land acquisition and liaison activities\nCoordination with landowners and government authorities\nAssistance in compensation and payment related cases\nGood documentation and computer skills\nStrong follow-up and communication abilities', NULL, 'Department: Land Liaison\nQualification: Any Graduate with good computer knowledge', NULL, '2026-08-30 17:42:55', '2026-09-01 09:40:41'),
(8, 'ASSISTANT MANAGER  GIS & IT (TECHNICAL)', 'assistant-manager-gis-it-technical', 'NOT MENTIONED', 'Mining', 'Mining', 'Odisha', 'Full-time', '6–8 Years', NULL, 1, '2026-08-30', '2026-09-30', 1, 1, 'published', 'Good knowledge of GIS software and databases\nPreparation and maintenance of maps and GIS related data\nExperience in land use mapping and other technical mapping activities\nKnowledge of GPS, survey data and spatial data processing\nKnowledge of computers, networking and IT systems\nAbility to identify opportunities for automation and digitization\nGood technical troubleshooting and data management skills', NULL, 'Department: GIS & IT\nQualification: Diploma in IT', NULL, '2026-08-30 17:44:20', '2026-09-01 09:40:52'),
(9, 'EXECUTIVE  MINING / GEOLOGY', 'executive-mining-geology', 'NOT MENTIONED', 'Mining', 'Mining', 'Odisha', 'Full-time', '5–6 Years', NULL, 1, '2026-08-30', '2026-09-30', 0, 1, 'published', 'Good knowledge of MS Office and official work documentation\nAbility to prepare reports and maintain mining/geology related records\nAbility to handle official correspondence and documentation\nKnowledge of computer applications and data management\nGood English communication and drafting skills\nKnowledge of Odia language is required / preferred', NULL, 'Department: Mining / Geology\nQualification: Any Graduate with PGDCA', NULL, '2026-08-30 17:46:33', '2026-09-01 09:41:14'),
(10, 'SR. SURVEYOR', 'sr-surveyor', 'NOT MENTIONED', 'Mining', 'Mining', 'Odisha, different locations', 'Full-time', '5-10 years', NULL, 3, '2026-08-30', '2026-09-30', 1, 1, 'published', 'Perform survey operations\nSupport EC & FC compliance\nWork with revenue maps and land records\nCoordinate statutory and regulatory requirements\nManage survey data and reporting\nWork with modern survey equipment and mining software', 'Software:\n\nMaptek Point Studio\nCivil 3D\nAuto CAD\nGeovia Minex\n\nInstruments:\n\nLeica TS-10\nLeica DGPS GS14\nLeica DGPS GS16\nLeica DGPS GS18\n\nScanners:\n\nTLS (Terrestrial Laser Scanner – Maptek', 'Skills & Competence:\n\nStrong knowledge of Open Cast Coal & Metal Mining Operations\nGood understanding of EC & FC statutory requirements\nAbility to read and correlate revenue maps with mining plans\nGood analytical, communication and documentation skills\nProficiency in modern survey equipment and mining software\n\nMandatory Requirement:\nDGMS Certificate of Competency for Mine Survey is advantage for Sr. Surveyor.', NULL, '2026-08-30 17:47:57', '2026-08-30 17:59:16'),
(11, 'MANAGER  SURVEY', 'manager-survey', 'Head Office', 'Mining', 'Mining', 'Visakhapatnam', 'Full-time', '10-15 years', NULL, 1, '2026-08-30', '2026-09-30', 1, 1, 'published', 'Manage and oversee survey operations\nCoordinate survey activities and field requirements\nWork with survey data and mapping\nCoordinate revenue maps and land records\nSupport EC & FC compliance\nHandle statutory and regulatory coordination\nManage survey data, documentation and reporting', 'Software:\nMaptek Point Studio\nCivil 3D\nAuto CAD\nGeovia Minex\n\nInstruments:\n\nLeica TS-10\nLeica DGPS GS14\nLeica DGPS GS16\nLeica DGPS GS18\n\nScanners:\nTLS (Terrestrial Laser Scanner – Maptek)', 'Skills & Competence:\nStrong knowledge of Open Cast Coal & Metal Mining Operations\nGood understanding of EC & FC statutory requirements\nAbility to read and correlate revenue maps with mining plans\nGood analytical, communication and documentation skills\nProficiency in modern survey equipment and mining software\n\nMandatory Requirement:\nDGMS Certificate of Competency for Mine Survey is mandatory', NULL, '2026-08-30 17:49:10', '2026-09-01 09:39:04'),
(12, 'GET (MINING)', 'get-mining', 'NOT MENTIONED', 'Mining', 'Mining', 'Odisha', 'Full-time', 'Fresher / GET', NULL, 2, '2026-08-30', '2026-09-30', 1, 1, 'published', 'Support survey operations\nAssist with mining survey activities\nWork with survey and mining-related data\nAssist with Auto CAD-related work\nSupport preparation and maintenance of survey data and maps\nAssist in statutory and regulatory coordination\nSupport data management and reporting', NULL, 'Department: Mining / Production\nQualification: Graduate in Mining', NULL, '2026-08-30 17:50:24', '2026-08-30 17:54:25'),
(13, 'DPM  Highways', 'dpm-highways', 'HAM ROAD PROJECT', 'Infrastructure', 'Project Management', 'Mahabubnagar / Sangareddy', 'Full-time', '12–15 Years', '80K', 2, '2026-08-31', '2026-09-30', 1, 1, 'draft', 'Lead highway project execution and coordinate with IE/NHAI. Manage earthwork, GSB, WMM, DBM, BC, drainage and other highway works. Monitor project progress, support billing activities, coordinate subcontractors and ensure timely execution of project activities.', 'Lead highway execution activities.\nCoordinate with IE/NHAI.\nManage earthwork and highway construction activities.\nOversee GSB, WMM, DBM and BC works.\nCoordinate drainage works.\nMonitor project progress.\nSupport billing activities.\nCoordinate and manage subcontractors.', 'B.E./B.Tech Civil; M.E./M.Tech preferred', NULL, '2026-08-31 12:30:45', '2026-09-11 10:11:39'),
(14, 'DPM Structures', 'dpm-structures', 'HAM ROAD PROJECT', 'Infrastructure', 'Project Management', 'Mahabubnagar / Sangareddy', 'Full-time', '12–15 Years', '80K', 2, '2026-08-31', '2026-09-30', 1, 1, 'draft', 'Lead and manage structural works including bridges, culverts and retaining walls. Supervise reinforcement, shuttering and concreting activities, coordinate with consultants and ensure proper execution of structural works as per project requirements.', 'Execute bridge works.\nExecute culvert works.\nManage retaining wall works.\nOversee reinforcement activities.\nManage shuttering/formwork activities.\nSupervise concreting works.\nCoordinate with consultants.\nManage structural construction activities', 'B.E./B.Tech Civil; M.E./M.Tech preferred', NULL, '2026-08-31 12:32:11', '2026-09-11 10:11:35'),
(15, 'DPM  QA QC', 'dpm-quality-control', 'HAM ROAD PROJECT', 'Infrastructure', 'QA QC', 'Mahabubnagar / Sangareddy', 'Full-time', '10–15 Years', '80K', 1, '2026-08-31', '2026-09-30', 1, 1, 'draft', 'Lead quality-control activities for the project and implement the Quality Assurance Plan (QAP). Review and approve test reports, conduct quality audits, ensure compliance with MoRTH/IRC requirements and oversee laboratory operations', 'Implement the Quality Assurance Plan (QAP).\nApprove test reports.\nConduct quality audits.\nEnsure MoRTH/IRC compliance.\nManage laboratory operations.', 'B.E./B.Tech Civil; M.E./M.Tech preferred', NULL, '2026-08-31 12:33:38', '2026-09-11 10:10:11'),
(16, 'Senior Highway Engineer', 'highway-sr-engineer', 'HAM ROAD PROJECT', 'Infrastructure', 'Civil Engineer', 'Mahabubnagar / Sangareddy', 'Full-time', '8–12 Years', '55K-60K', 6, '2026-08-31', '2026-09-30', 1, 1, 'published', 'Supervise highway construction activities across assigned stretches. Monitor site productivity, coordinate site teams, verify quantities and measurements, and ensure proper execution of highway works including road and associated infrastructure works.', 'Supervise highway stretches.\nMonitor productivity.\nVerify quantities.\nCoordinate site teams.\nMonitor project execution.', 'B.E./B.Tech Civil', NULL, '2026-08-31 12:35:06', '2026-09-09 13:26:51'),
(17, 'Senior Structural Engineer', 'structures-sr-engineer', 'HAM ROAD PROJECT', 'Infrastructure', 'Civil Engineer', 'Mahabubnagar / Sangareddy', 'Full-time', '8–12 Years', '55K-60K', 6, '2026-08-31', '2026-09-30', 1, 1, 'published', 'Supervise bridge and culvert construction activities, including reinforcement, formwork and concreting. Coordinate structural site activities, conduct inspections and maintain required quality and project documentation.', 'Supervise bridge works.\nSupervise culvert works.\nMonitor reinforcement works.\nMonitor formwork activities.\nSupervise concreting.\nConduct inspections.\nMaintain quality documentation.', 'B.E./B.Tech Civil; M.E./M.Tech preferred', NULL, '2026-08-31 12:36:19', '2026-09-09 13:27:02'),
(18, 'Highway Engineer', 'highway-engineers', 'HAM ROAD PROJECT', 'Infrastructure', 'Civil Engineer', 'Mahabubnagar / Sangareddy', 'Full-time', '4-8 Years', '40K - 45K', 6, '2026-08-31', '2026-09-30', 1, 1, 'published', 'Execute day-to-day highway construction works and coordinate with site teams. Monitor machinery and construction activities, coordinate survey requirements, check levels and maintain quantity and measurement records.', 'Execute road works.\nMonitor machinery.\nCoordinate surveys.\nCheck levels.\nPrepare quantity records.', 'B.E./B.Tech Civil', NULL, '2026-08-31 12:37:34', '2026-09-09 13:27:10'),
(19, 'Structural Engineer', 'structures-engineers', 'HAM ROAD PROJECT', 'Infrastructure', 'Civil Engineer', 'Mahabubnagar / Sangareddy', 'Full-time', '4-8 Years', '40K - 45K', 5, '2026-08-31', '2026-09-30', 1, 1, 'published', 'Execute structural construction activities including bridges, culverts and related structures. Prepare and check Bar Bending Schedules (BBS), monitor reinforcement and concreting activities, follow up on cube tests and maintain inspection records.', 'Execute structural works.\nPrepare BBS.\nMonitor cube tests.\nMonitor curing activities.\nMaintain inspection records.', 'B.E./B.Tech Civil; M.E./M.Tech preferred', NULL, '2026-08-31 12:38:39', '2026-09-09 13:27:22'),
(20, 'Supervisor', 'jr-engineer-supervisors', 'HAM ROAD PROJECT', 'Infrastructure', 'Civil Engineer', 'Mahabubnagar / Sangareddy', 'Full-time', '2–5 Years', '25K -35K', 10, '2026-08-31', '2026-09-30', 1, 1, 'published', 'Manage and supervise day-to-day site execution activities. Coordinate labour, machinery and construction activities, monitor quantities and measurements, maintain site records and reports, and ensure work is carried out according to project requirements.', 'Carry out daily site supervision and coordinate labour and site activities. Monitor machinery and work progress, assist with measurements and quantity recording, support quality checks and prepare daily progress reports (DPR).\n\nPrepare DPRs.', 'B.E./B.Tech/Diploma Civil', NULL, '2026-08-31 12:40:15', '2026-09-09 13:27:33'),
(21, 'Senior Surveyor', 'sr-survey-with-autocad', 'HAM ROAD PROJECT', 'Infrastructure', 'Surveying', 'Mahabubnagar / Sangareddy', 'Full-time', '6–10 Years', '60K-65K', 3, '2026-08-31', '2026-09-30', 1, 1, 'published', 'Lead surveying activities for highway projects. Establish survey control points, conduct setting-out and level checks, coordinate field surveys, prepare/verify survey data and support quantity calculations and as-built survey requirements.', 'Lead survey activities.\nEstablish control points.\nPrepare AutoCAD drawings.\nConduct setting-out activities.\nConduct as-built surveys.', 'B.E./B.Tech Civil; Diploma Survey preferred', NULL, '2026-08-31 12:45:08', '2026-09-09 13:27:48'),
(22, 'Surveyor', 'surveyor', 'Surveying', 'Infrastructure', 'Infrastructure', 'Mahabubnagar / Sangareddy', 'Full-time', '2–5 Years', '35K - 40K', 6, '2026-08-31', '2026-09-30', 1, 1, 'published', 'Conduct topographical and construction surveys for highway projects. Carry out centerline marking, cross-section surveys, setting-out and level checks, and support quantity verification and other site surveying activities.', 'Conduct topographical surveys.\nCarry out centerline marking.\nConduct cross-section surveys.\nCarry out setting-out activities.\nVerify quantities.', 'B.E./B.Tech Civil; Diploma Survey preferred', NULL, '2026-08-31 12:46:28', '2026-09-09 13:27:59'),
(23, 'Biling / QS', 'bill-planning', 'HAM ROAD PROJECT', 'Infrastructure', 'Quantity Surveyor', 'Mahabubnagar / Sangareddy', 'Full-time', '4–8 Years', '55K-65K', 2, '2026-08-31', '2026-09-30', 1, 1, 'published', 'Handle quantity surveying, estimation and billing activities for the project. Prepare and verify quantities, review BOQs, support IPC/RA billing, maintain measurement records, carry out quantity reconciliation and assist with project cost control.', 'Prepare IPCs.\nPrepare RA Bills.\nCarry out quantity reconciliation.\nPrepare planning schedules.\nPrepare progress reports.', 'B.E./B.Tech Civil; QS preferred', NULL, '2026-08-31 12:47:36', '2026-09-09 13:28:11'),
(24, 'QC Material Engineer', 'qc-material-engineer-lab-technicians', 'HAM ROAD PROJECT', 'Infrastructure', 'QA QC', 'Mahabubnagar / Sangareddy', 'Full-time', '3–8 Years', '40K - 50K', 2, '2026-08-31', '2026-09-30', 0, 1, 'published', 'Manage material testing and laboratory activities for the project. Supervise testing of soil, aggregates, concrete and bitumen, maintain laboratory records, monitor material quality and support overall quality assurance activities.', 'Conduct soil testing.\nConduct aggregate testing.\nConduct concrete testing.\nConduct bitumen testing.\nMaintain laboratory records.\nPrepare quality reports.', 'B.E./B.Tech Civil; Materials/Geology preferred', NULL, '2026-08-31 12:48:39', '2026-09-01 11:31:41'),
(25, 'Safety Officer', 'safety-officer', 'HAM ROAD PROJECT', 'Infrastructure', 'Health & Safety', 'Mahabubnagar / Sangareddy', 'Full-time', '3–8 Years', '35K - 45K', 4, '2026-08-31', '2026-09-30', 0, 1, 'published', 'The Safety professional will be responsible for implementing and monitoring site safety practices, conducting toolbox talks and inspections, ensuring PPE compliance, maintaining incident reports and supporting emergency preparedness.', 'Conduct toolbox talks.\nMonitor PPE compliance.\nConduct safety inspections.\nMaintain incident reporting.\nSupport emergency preparedness.', 'Diploma/B.E. Safety; NEBOSH/IOSH preferred', NULL, '2026-08-31 12:49:33', '2026-09-01 11:31:28'),
(26, 'Store supervisors/ Incharges', 'store-supervisors-incharges', 'HAM ROAD PROJECT', 'Infrastructure', 'Store & Inventory', 'Mahabubnagar / Sangareddy', 'Full-time', '3–8 Years', '30K-35K', 4, '2026-08-31', '2026-09-30', 0, 1, 'published', 'Manage overall site-store operations, including inventory management, material receipt and issue, stock registers and material requirement tracking. Coordinate with project/site teams to ensure timely availability and proper control of materials.', 'Inventory management; \nmaterial issue; \nstock registers; \nfuel records;\nmaterial requirement tracking.', '12th/Any Graduation; Computer knowledge preferred', NULL, '2026-08-31 12:52:32', '2026-09-01 11:25:33'),
(27, 'Accountant / Admin', 'accountant-admin', 'HAM ROAD PROJECT', 'Infrastructure', 'Administration', 'Mahabubnagar / Sangareddy', 'Full-time', '3–8 Years', '30K - 35K', 4, '2026-08-31', '2026-09-30', 0, 1, 'published', 'Handle project accounting and financial administration activities. Manage accounts, payments and reconciliations, maintain financial records and support statutory compliance and other project accounting requirements.', 'Payroll; \nvendor payments; \nattendance; \noffice administration; \nHR coordination.', 'B.Com/M.Com; Tally/GST preferred', NULL, '2026-08-31 12:53:28', '2026-09-01 11:25:11'),
(28, 'Document Controller', 'document-controller', 'HAM ROAD PROJECT', 'Infrastructure', 'Administration', 'Mahabubnagar / Sangareddy', 'Full-time', '3–6 Years', '25K -32K', 3, '2026-08-31', '2026-09-30', 1, 1, 'published', 'The Document Controller will be responsible for maintaining project documentation, licensing records, approvals, correspondence and other project-related records in an organized and controlled manner.', 'Maintain project documentation.\nMaintain licensing documents.\nMaintain project records.\nMaintain approvals.\nManage correspondence.\nMaintain document registers and records.', 'Any Graduation; Computer knowledge preferred', NULL, '2026-08-31 12:54:17', '2026-09-09 13:29:32'),
(29, 'Site Engineer', 'site-engineer', 'HAM ROAD PROJECT', 'Infrastructure', 'Civil Engineer', 'Mahabubnagar / Sangareddy', 'Full-time', '2–5 Years', '30K-35K', 16, '2026-09-01', '2026-10-30', 1, 1, 'published', 'Manage and supervise day-to-day site execution activities. Coordinate labour, machinery and construction activities, monitor quantities and measurements, maintain site records and reports, and ensure work is carried out according to project requirements.', 'Daily site supervision; labour management; measurements; machinery coordination; quality checks; DPR preparation.', 'B.E./B.Tech/Diploma Civil', NULL, '2026-09-01 09:50:21', '2026-09-09 13:29:46'),
(30, 'Survey Helpers', 'survey-helpers', 'HAM ROAD PROJECT', 'Infrastructure', 'Surveying', 'Mahabubnagar / Sangareddy', 'Full-time', '1-2 Years', '12K -18K', 12, '2026-09-01', '2026-09-30', 1, 1, 'published', 'Assist surveyors and survey engineers with field surveying activities. Support setting-out, measurements, data collection and other survey-related site activities as required.', '\"Assisting in survey works\nSetting out\nData collection\nSupporting survey team in field activities\"', '10th/12th; Survey experience preferred', NULL, '2026-09-01 09:53:44', '2026-09-09 13:30:03'),
(31, 'Planning / QS', 'planning-qs', 'HAM ROAD PROJECT', 'Infrastructure', 'Quantity Surveyor', 'Mahabubnagar / Sangareddy', 'Full-time', '4-8 Years', '50K -65K', 3, '2026-09-01', '2026-09-30', 1, 1, 'published', 'Prepare and monitor project planning schedules and baseline programmes. Coordinate resource planning, track project progress, prepare progress reports and MIS reports, and support project management with accurate planning and performance information.', '\"Baseline planning\nResource planning\nProgress tracking\nMIS reporting\"', 'B.E./B.Tech Civil; MS Project/Primavera preferred', NULL, '2026-09-01 10:17:40', '2026-09-09 13:30:12'),
(32, 'Lab Technician', 'lab-technician', 'HAM ROAD PROJECT', 'Infrastructure', 'QA QC', 'Mahabubnagar / Sangareddy', 'Full-time', '2-5 Years', '25K', 4, '2026-09-01', '2026-09-30', 0, 1, 'published', 'Conduct field and laboratory tests related to construction materials and works. Assist with testing of soil, aggregates, concrete and bitumen, maintain laboratory records and ensure test results and documentation are properly maintained.', '\"Material testing\nLaboratory management\nQuality assurance\nMonitoring material quality and test results\"', 'ITI/Diploma Lab Technician or 12th with lab experience', NULL, '2026-09-01 10:20:05', '2026-09-01 11:23:11'),
(33, 'Heavy Vehicle Driver – Volvo & Benz (Mining)', 'heavy-vehicle-driver-volvo-benz-mining', 'Mining Operations – Volvo & Benz', 'Mining', 'Driver / Heavy Vehicle Driver', 'Near Chandrapur, Maharashtra', 'Full-time', '5 years', '25k', 100, '2026-09-05', '2026-10-09', 1, 1, 'published', 'OurNear Talent is hiring Heavy Vehicle Drivers for mining operations near Chandrapur, Maharashtra.\n\nImmediate requirement for experienced Volvo & Benz drivers.\n\nMore than 100 vacancies are available for suitable candidates with heavy vehicle and mining driving experience.\n\nThis is a full-time employment opportunity for experienced drivers looking for stable employment and long-term career opportunities in the mining sector.', '• Operate Volvo and Benz heavy vehicles safely within mining operations.\n• Follow all site safety rules, traffic regulations and operational procedures.\n• Perform vehicle inspections before and after each shift.\n• Transport materials as instructed by the site and mining operations team.\n• Maintain safe driving practices at all times.\n• Report vehicle faults, breakdowns or safety issues immediately.\n• Maintain cleanliness and basic care of the assigned vehicle.\n• Follow instructions from supervisors and site management.\n• Ensure timely and safe completion of assigned trips.', '• Minimum 5 years of professional driving experience.\n• Valid Heavy Motor Vehicle (HMV) Driving Licence is mandatory.\n• Previous mining experience is mandatory.\n• Experience operating heavy vehicles in mining or similar industrial environments preferred.\n• Good knowledge of vehicle safety and basic operating procedures.\n• Ability to follow site safety instructions and supervisor directions.\n• Physically fit to work in a mining environment.\n• Willingness to work full-time and follow site/shift schedules.', NULL, '2026-09-05 18:15:46', '2026-09-05 18:15:46'),
(34, 'HR / Administration Executive', 'hr-administration-executive', 'Ournear Talent Office', 'Manpower Services', 'Human Resources', 'Hyderabad, Dilsukhnagar', 'Full-time', '0-2 years', '15k - 20k', 1, '2026-09-08', '2026-09-16', 1, 1, 'published', 'We are looking for a motivated and organized HR & Administration Executive to join our team in Dilsukhnagar, Hyderabad.\nThe ideal candidate should have good communication skills, basic knowledge of HR and recruitment processes, and the ability to manage administrative and employee-related activities efficiently.\nFreshers with the right skills and a willingness to learn are welcome to apply.', 'Support day-to-day HR and administrative activities\nSource and screen candidates for open positions\nCoordinate interviews and follow up with candidates\nMaintain employee records and documentation\nManage attendance and leave records\nAssist with onboarding and joining formalities\nCoordinate internal HR communications\nMaintain basic HR reports and records using MS Excel\nSupport office administration and coordination activities\nHandle multiple tasks and ensure timely completion', 'HR & Recruitment basics\nCandidate sourcing and screening\nInterview coordination\nEmployee documentation\nAttendance & leave management\nBasic HRMS knowledge\nMS Excel / MS Office\nGood verbal and written communication\nStrong coordination skills\nTime management and multitasking\nPositive attitude and willingness to learn', NULL, '2026-09-08 12:32:59', '2026-09-08 12:32:59'),
(35, 'Recruitment Executive', 'recruitment-executive', 'Ournear Talent Office', 'Manpower Services', 'Recruitment', 'Hyderabad, Dilsukhnagar', 'Full-time', '0-2 years', '15k - 20k', 1, '2026-09-08', '2026-09-23', 1, 1, 'published', 'We are looking for a Recruitment Executive to support candidate sourcing, screening, interview coordination and recruitment operations.', 'Candidate sourcing and screening\nInitial candidate communication and follow-up\nInterview scheduling and coordination\nCandidate database management\nCandidate relationship management\nFollow-up until interview, selection and joining\nSupport recruitment documentation and reporting\nManage multiple vacancies and candidate pipelines\nSupport basic business-development activities', 'Good verbal and written communication\nLanguages: Telugu, Hindi & English\nCandidate sourcing and screening\nStrong follow-up and coordination skills\nMS Office – Word, Excel, PowerPoint & Outlook\nAdobe Acrobat / PDF handling\nBasic knowledge of AI tools\nGood computer and internet skills\nGood convincing and interpersonal skills\nAbility to multitask\nWillingness to learn and take responsibility', NULL, '2026-09-08 13:50:25', '2026-09-08 13:50:25'),
(36, 'Content Creator', 'content-creator', 'OurNear', 'Advertising & Media', 'Content Creation', 'Hyderabad', 'Contract', 'Fresher / Entry Level – Experience Not Required,  your talent matters', 'Revenue sharing', 10, '2026-09-12', '2026-12-31', 1, 1, 'published', 'OurNear is building a new generation of creators who want to create meaningful content, work with a team, gain real experience and build their own professional identity.\n\nWe are looking for new and upcoming creators who are ready to start, learn, create and grow with OurNear.\n\nYou do not need to be an established creator, influencer or have thousands of followers. What matters is your ability to understand an idea, bring it to life and create successful, engaging content.\n\nThis opportunity is not limited to one type of business, one subject or one format. Creativity can be used for businesses, products, services, people, places, stories, ideas, social issues, awareness, campaigns and many other meaningful projects.\n\nYou will work with the OurNear team on real projects and content opportunities. OurNear provides the platform, opportunities, guidance, collaboration and support. You bring your own equipment, effort, skills and commitment.\n\nThis is an opportunity to start building your work, your portfolio, your experience and your identity with OurNear.', '• Create engaging and professional photos, videos and digital content.\n• Understand project ideas and convert them into successful content.\n• Develop creative concepts, story ideas and content approaches.\n• Shoot, record and edit content using your own setup.\n• Create content across different subjects, themes, formats and projects.\n• Work collaboratively with the OurNear team.\n• Follow OurNear project and brand guidelines.\n• Submit completed content to the OurNear team for review.\n• Make improvements or revisions based on feedback.\n• Contribute to real campaigns and creative projects.\n• Maintain quality, originality and professionalism in your work.\n• Build a strong portfolio through real project experience.\n• Continuously learn, improve and develop your content skills.', '• Freshers and new creators are welcome.\n• No established creator status or large follower base is required.\n• Interest in content creation, photography, videography or digital media.\n• Ability to turn ideas into engaging and meaningful content.\n• Good communication and willingness to collaborate.\n• Willingness to learn, accept feedback and improve.\n• Responsible and professional approach toward assigned projects.\n• Ability to work with the OurNear team.\n• Creativity with a focus on creating effective and successful content.\n• Your own quality smartphone and/or camera.\n• Your own laptop or PC for editing.\n• Your own internet/data connection.\n• Basic editing software/tools suitable for your work.\n• Your own basic accessories/equipment required for content creation.\n• Commitment to bring your own setup, time and effort.', '• Opportunity to work with the growing OurNear brand.\n• Real creative projects and content opportunities.\n• Platform to showcase your work.\n• Guidance and support from the OurNear team.\n• Collaborative team environment.\n• Feedback and opportunities to improve your skills.\n• Real-world content creation experience.\n• Opportunity to build your professional portfolio.\n• Recognition for quality work and contribution.\n• Opportunity to develop your own professional identity.\n• Project-based earning opportunities where applicable.\n• Opportunity to grow with OurNear as the platform expands.\n• Exposure to different ideas, subjects, projects and creative possibilities.\n\nOurNear provides the opportunity, platform, guidance and support.\n\nYou must provide your own setup, equipment, internet, effort and commitment.\n\nTogether, we create meaningful content, real impact and bigger opportunities.', '2026-09-12 20:22:02', '2026-09-12 20:22:02');

-- --------------------------------------------------------

--
-- Table structure for table `media_assets`
--

CREATE TABLE `media_assets` (
  `id` int(11) NOT NULL,
  `disk` varchar(255) NOT NULL DEFAULT 'public',
  `path` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime_type` varchar(255) NOT NULL,
  `extension` varchar(255) NOT NULL,
  `size_bytes` int(11) NOT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `media_assets`
--

INSERT INTO `media_assets` (`id`, `disk`, `path`, `original_name`, `mime_type`, `extension`, `size_bytes`, `width`, `height`, `created_at`, `updated_at`) VALUES
(1, 'public', 'media/32bada4d-198d-4754-927a-45b2801240d8.jpeg', 'WhatsApp Image 2026-08-27 at 6.53.51 PM.jpeg', 'image/jpeg', 'jpeg', 20695, 1086, 362, '2026-08-30 17:06:16', '2026-08-30 17:06:16'),
(2, 'public', 'media/804da0ed-11d9-440a-91a2-796e6b45b8df.png', 'OurNearTalent_Outlook_Signature_Compact.png', 'image/png', 'png', 72920, 609, 170, '2026-09-01 05:52:31', '2026-09-01 05:52:31');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(11) NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_08_26_120000_create_job_listings_table', 1),
(5, '2026_08_26_120001_create_applications_table', 1),
(6, '2026_08_26_140000_create_employer_enquiries_table', 1),
(7, '2026_08_26_140001_create_contact_enquiries_table', 1),
(8, '2026_08_26_160000_create_website_content_tables', 1),
(9, '2026_08_27_100000_create_media_and_social_tables', 1),
(10, '2026_08_27_110000_create_website_settings_and_page_seo', 1),
(11, '2026_08_27_120000_add_recruitment_operations_columns', 1),
(12, '2026_08_27_130000_add_account_roles_and_profiles', 1),
(13, '2026_08_27_140000_create_recruitment_operations_tables', 1),
(14, '2026_08_27_150000_set_users_role_default_to_candidate', 1),
(15, '2026_08_27_160000_add_branding_to_website_settings', 1);

-- --------------------------------------------------------

--
-- Table structure for table `page_section_items`
--

CREATE TABLE `page_section_items` (
  `id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `content_item_id` int(11) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `page_section_items`
--

INSERT INTO `page_section_items` (`id`, `section_id`, `content_item_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 2, 18, 0, '2026-08-30 15:27:58', '2026-08-30 15:27:58'),
(2, 2, 19, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58'),
(3, 2, 20, 2, '2026-08-30 15:27:58', '2026-08-30 15:27:58'),
(4, 3, 4, 0, '2026-08-30 15:27:58', '2026-08-30 15:27:58'),
(5, 3, 5, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58'),
(6, 3, 6, 2, '2026-08-30 15:27:58', '2026-08-30 15:27:58'),
(7, 3, 7, 3, '2026-08-30 15:27:58', '2026-08-30 15:27:58'),
(8, 3, 8, 4, '2026-08-30 15:27:58', '2026-08-30 15:27:58'),
(9, 3, 9, 5, '2026-08-30 15:27:58', '2026-08-30 15:27:58'),
(10, 4, 1, 0, '2026-08-30 15:27:58', '2026-08-30 15:27:58'),
(11, 4, 2, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58'),
(12, 4, 3, 2, '2026-08-30 15:27:58', '2026-08-30 15:27:58'),
(13, 5, 21, 0, '2026-08-30 15:27:58', '2026-08-30 15:27:58'),
(14, 5, 22, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58'),
(15, 5, 23, 2, '2026-08-30 15:27:58', '2026-08-30 15:27:58'),
(16, 5, 24, 3, '2026-08-30 15:27:58', '2026-08-30 15:27:58'),
(17, 5, 25, 4, '2026-08-30 15:27:58', '2026-08-30 15:27:58'),
(18, 6, 10, 0, '2026-08-30 15:27:58', '2026-08-30 15:27:58'),
(19, 6, 11, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58'),
(20, 6, 12, 2, '2026-08-30 15:27:58', '2026-08-30 15:27:58'),
(21, 6, 13, 3, '2026-08-30 15:27:58', '2026-08-30 15:27:58'),
(22, 6, 14, 4, '2026-08-30 15:27:58', '2026-08-30 15:27:58'),
(23, 6, 15, 5, '2026-08-30 15:27:58', '2026-08-30 15:27:58'),
(24, 6, 16, 6, '2026-08-30 15:27:58', '2026-08-30 15:27:58'),
(25, 6, 17, 7, '2026-08-30 15:27:58', '2026-08-30 15:27:58'),
(26, 7, 26, 0, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(27, 7, 27, 1, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(28, 7, 28, 2, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(29, 9, 29, 0, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(30, 9, 30, 1, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(31, 9, 31, 2, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(32, 10, 32, 0, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(33, 10, 33, 1, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(34, 11, 34, 0, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(35, 11, 35, 1, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(36, 11, 36, 2, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(37, 12, 1, 0, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(38, 12, 2, 1, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(39, 12, 3, 2, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(40, 13, 10, 0, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(41, 13, 11, 1, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(42, 13, 12, 2, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(43, 13, 13, 3, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(44, 13, 14, 4, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(45, 13, 15, 5, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(46, 13, 16, 6, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(47, 13, 17, 7, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(48, 14, 37, 0, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(49, 14, 38, 1, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(50, 16, 4, 0, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(51, 16, 5, 1, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(52, 16, 6, 2, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(53, 16, 7, 3, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(54, 16, 8, 4, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(55, 16, 9, 5, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(56, 17, 39, 0, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(57, 17, 40, 1, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(58, 17, 41, 2, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(59, 17, 42, 3, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(60, 17, 43, 4, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(61, 17, 44, 5, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(62, 17, 45, 6, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(63, 18, 46, 0, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(64, 18, 47, 1, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(65, 18, 48, 2, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(66, 18, 49, 3, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(67, 19, 50, 0, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(68, 19, 51, 1, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(69, 19, 52, 2, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(70, 19, 53, 3, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(71, 19, 54, 4, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(72, 20, 55, 0, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(73, 20, 56, 1, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(74, 20, 57, 2, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(75, 20, 58, 3, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(76, 20, 59, 4, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(77, 21, 60, 0, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(78, 21, 61, 1, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(79, 21, 62, 2, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(80, 21, 63, 3, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(81, 21, 64, 4, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(82, 22, 1, 0, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(83, 22, 2, 1, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(84, 22, 3, 2, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(85, 23, 10, 0, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(86, 23, 11, 1, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(87, 23, 12, 2, '2026-08-30 15:27:59', '2026-08-30 15:27:59'),
(88, 23, 13, 3, '2026-08-30 15:28:00', '2026-08-30 15:28:00'),
(89, 23, 14, 4, '2026-08-30 15:28:00', '2026-08-30 15:28:00'),
(90, 23, 15, 5, '2026-08-30 15:28:00', '2026-08-30 15:28:00'),
(91, 23, 16, 6, '2026-08-30 15:28:00', '2026-08-30 15:28:00'),
(92, 23, 17, 7, '2026-08-30 15:28:00', '2026-08-30 15:28:00'),
(93, 26, 65, 0, '2026-08-30 15:28:00', '2026-08-30 15:28:00'),
(94, 26, 66, 1, '2026-08-30 15:28:00', '2026-08-30 15:28:00'),
(95, 26, 67, 2, '2026-08-30 15:28:00', '2026-08-30 15:28:00'),
(96, 26, 68, 3, '2026-08-30 15:28:00', '2026-08-30 15:28:00'),
(97, 26, 69, 4, '2026-08-30 15:28:00', '2026-08-30 15:28:00'),
(98, 26, 70, 5, '2026-08-30 15:28:00', '2026-08-30 15:28:00'),
(99, 27, 71, 0, '2026-08-30 15:28:00', '2026-08-30 15:28:00'),
(100, 27, 72, 1, '2026-08-30 15:28:00', '2026-08-30 15:28:00'),
(101, 27, 73, 2, '2026-08-30 15:28:00', '2026-08-30 15:28:00'),
(102, 27, 74, 3, '2026-08-30 15:28:00', '2026-08-30 15:28:00'),
(103, 27, 75, 4, '2026-08-30 15:28:00', '2026-08-30 15:28:00'),
(104, 27, 76, 5, '2026-08-30 15:28:00', '2026-08-30 15:28:00'),
(105, 27, 77, 6, '2026-08-30 15:28:00', '2026-08-30 15:28:00'),
(106, 27, 78, 7, '2026-08-30 15:28:00', '2026-08-30 15:28:00'),
(107, 28, 10, 0, '2026-08-30 15:28:00', '2026-08-30 15:28:00'),
(108, 28, 11, 1, '2026-08-30 15:28:00', '2026-08-30 15:28:00'),
(109, 28, 12, 2, '2026-08-30 15:28:00', '2026-08-30 15:28:00'),
(110, 28, 13, 3, '2026-08-30 15:28:00', '2026-08-30 15:28:00'),
(111, 28, 14, 4, '2026-08-30 15:28:00', '2026-08-30 15:28:00'),
(112, 28, 15, 5, '2026-08-30 15:28:00', '2026-08-30 15:28:00'),
(113, 28, 16, 6, '2026-08-30 15:28:00', '2026-08-30 15:28:00'),
(114, 28, 17, 7, '2026-08-30 15:28:00', '2026-08-30 15:28:00'),
(115, 29, 79, 0, '2026-08-30 15:28:00', '2026-08-30 15:28:00'),
(116, 29, 80, 1, '2026-08-30 15:28:00', '2026-08-30 15:28:00'),
(117, 29, 81, 2, '2026-08-30 15:28:00', '2026-08-30 15:28:00'),
(118, 29, 82, 3, '2026-08-30 15:28:00', '2026-08-30 15:28:00'),
(119, 29, 83, 4, '2026-08-30 15:28:00', '2026-08-30 15:28:00'),
(120, 30, 1, 0, '2026-08-30 15:28:00', '2026-08-30 15:28:00'),
(121, 30, 2, 1, '2026-08-30 15:28:00', '2026-08-30 15:28:00'),
(122, 30, 3, 2, '2026-08-30 15:28:00', '2026-08-30 15:28:00');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recruitment_activities`
--

CREATE TABLE `recruitment_activities` (
  `id` int(11) NOT NULL,
  `application_id` int(11) DEFAULT NULL,
  `employer_enquiry_id` int(11) DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  `body` text DEFAULT NULL,
  `from_status` varchar(255) DEFAULT NULL,
  `to_status` varchar(255) DEFAULT NULL,
  `actor_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `recruitment_activities`
--

INSERT INTO `recruitment_activities` (`id`, `application_id`, `employer_enquiry_id`, `type`, `body`, `from_status`, `to_status`, `actor_id`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'status_changed', NULL, 'New', 'Reviewing', 1, '2026-08-30 18:08:21', '2026-08-30 18:08:21'),
(2, 1, NULL, 'status_changed', NULL, 'Reviewing', 'Withdrawn', 1, '2026-08-31 05:24:56', '2026-08-31 05:24:56'),
(3, 9, NULL, 'status_changed', NULL, 'New', 'Reviewing', 1, '2026-08-31 14:10:31', '2026-08-31 14:10:31'),
(4, 10, NULL, 'status_changed', NULL, 'New', 'Reviewing', 1, '2026-09-01 13:46:31', '2026-09-01 13:46:31'),
(5, 13, NULL, 'status_changed', NULL, 'New', 'Reviewing', 1, '2026-09-02 16:29:18', '2026-09-02 16:29:18'),
(6, 15, NULL, 'status_changed', NULL, 'New', 'Rejected', 1, '2026-09-04 06:42:12', '2026-09-04 06:42:12'),
(7, 14, NULL, 'status_changed', NULL, 'New', 'Rejected', 1, '2026-09-04 06:42:34', '2026-09-04 06:42:34'),
(8, 13, NULL, 'status_changed', NULL, 'Reviewing', 'Shortlisted', 1, '2026-09-04 06:42:50', '2026-09-04 06:42:50'),
(9, 11, NULL, 'status_changed', NULL, 'New', 'Rejected', 1, '2026-09-04 06:52:25', '2026-09-04 06:52:25'),
(10, 33, NULL, 'status_changed', NULL, 'New', 'Reviewing', 1, '2026-09-10 06:30:12', '2026-09-10 06:30:12');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` text NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('0Do1UxaDDFfGpRQcVkk5tpdzA2jNX8U5OsoQinDE', NULL, '173.252.95.50', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiT0lEQ1N1NnI2R2JqcTFHSllMaEwzSHdFRW14NnB6WlJ1U2dzREw3dCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTk6Imh0dHBzOi8vb3VybmVhci5jb20iO3M6NToicm91dGUiO3M6NDoiaG9tZSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1789817176),
('4GEOP56BRWvwj8CnVwLaPMrSD1jntdLyd7OTH8MB', NULL, '173.252.82.57', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiTXJWTnViWFZ5R3hxNFIydjFyRjNjV0h6ZnlMcThTaWpKWDlxNVg5cSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTk6Imh0dHBzOi8vb3VybmVhci5jb20iO3M6NToicm91dGUiO3M6NDoiaG9tZSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1789817194),
('542bAWitwowPH68D0LqxBzjYQBtZjhrhp22U1tLc', NULL, '173.252.82.19', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiZ3M1SExlVU5wa1NqalpPYm9ZdW55a1RnU1hrZ3BMcEdUd2tyUkJMMyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTk6Imh0dHBzOi8vb3VybmVhci5jb20iO3M6NToicm91dGUiO3M6NDoiaG9tZSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1789817194),
('a7sqqeNRtEUhYRlDeU3sbd9hsdSfT8BuUcV66Iev', NULL, '216.73.216.101', 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; ClaudeBot/1.0; +claudebot@anthropic.com)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiVHIwTlp1cTZYekpSUzNHaEhPZFNMTlBjRmEzeXVRZ3g1S3RxTzhQdyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHBzOi8vb3VybmVhci5jb20vc2l0ZW1hcC54bWwiO3M6NToicm91dGUiO3M6Nzoic2l0ZW1hcCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1789821208),
('aqeriaJvgWd8ftMj1kv0OxvOmzaPgby9x6EDosmk', NULL, '173.252.95.59', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiazUycEJ3bXlQSGQzMVpRV3BuZEpEM2Y3N3Z3QmdDbjZiU2dpZGI3WCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTk6Imh0dHBzOi8vb3VybmVhci5jb20iO3M6NToicm91dGUiO3M6NDoiaG9tZSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1789824220),
('CwNYLKHQCEM8ve9uU8fHwSHEC5szwfR22KXXG1ns', 1, '49.43.225.193', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiT0xpekc3UW1VeTdQN1A0ZGc0OHN3MWhmOVlMd1lXYXFkd2h2REV2cSI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTtzOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjk6Il9wcmV2aW91cyI7YToyOntzOjM6InVybCI7czozODoiaHR0cHM6Ly9vdXJuZWFyLmNvbS9hZG1pbi9hcHBsaWNhdGlvbnMiO3M6NToicm91dGUiO3M6MjQ6ImFkbWluLmFwcGxpY2F0aW9ucy5pbmRleCI7fX0=', 1789813937),
('D5DEPsvbOTrJPi2CvaOo5HRB7s7Uod75bId5RCW3', NULL, '173.252.82.10', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoib01STWlMcEdFVk8wQUVxVEJCNkNGUUNxeHlUS0I2YlBPQnA1OGVxQyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTk6Imh0dHBzOi8vb3VybmVhci5jb20iO3M6NToicm91dGUiO3M6NDoiaG9tZSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1789817193),
('e4BALuneUjRf2KbJhqLPtZUlltX4MvP4GfeKt0oj', NULL, '69.63.184.39', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiZ2tBMDk1MzVjc3hFam5LWER0VFcwYmNBYk94ajhRQ0ttWGJydERpayI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTk6Imh0dHBzOi8vb3VybmVhci5jb20iO3M6NToicm91dGUiO3M6NDoiaG9tZSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1789824048),
('FvsW0JxytH4EKw52l1RQQScxUkUIpQpMU9lBfTiD', NULL, '173.252.82.34', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoidWV6UmV6eU1HVUJnc3VQdUNiSjV5aUVCOWpWbnRJV1pqMDNMYXhPVSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTk6Imh0dHBzOi8vb3VybmVhci5jb20iO3M6NToicm91dGUiO3M6NDoiaG9tZSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1789817193),
('FyY0u63N9MtGLPMH33g7iBKoyVU1eXMlqNkvxjAM', NULL, '216.73.216.101', 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; ClaudeBot/1.0; +claudebot@anthropic.com)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiMGlyM0FjRnA4SlNkVHk5Tmt4b3NMNndGaUtvRmhrVmwyUGxYYVAyRSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHBzOi8vb3VybmVhci5jb20vc2l0ZW1hcC54bWwiO3M6NToicm91dGUiO3M6Nzoic2l0ZW1hcCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1789815413),
('IkA7UVNkzWzG5anxSObbRDLc0NPYRTLuxlpXfgRo', NULL, '112.202.41.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiSHpNQ1dEVVlhY0hOTVpURXJqRlJ6d1o3TE1tNUVnWko2QXNIcW5aRSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTk6Imh0dHBzOi8vb3VybmVhci5jb20iO3M6NToicm91dGUiO3M6NDoiaG9tZSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1789817084),
('M4kl3TUzRdUn4U47ICgvwFZkwWJ5XGW3ckAhDLgn', NULL, '173.252.69.27', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoidExBY2VHMnN2SVhROVFCTmJTSHNUR2hrZmpHeW5RREtLOGxIUk9XVCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTk6Imh0dHBzOi8vb3VybmVhci5jb20iO3M6NToicm91dGUiO3M6NDoiaG9tZSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1789817176),
('meM8FfeR7bEjncWQpSm8fDmalD67pnB3MmSNRjFm', NULL, '173.252.82.113', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoialBHdTQ1bGtmb2hSSVFVUDBwTm5XZmluMWY2N1JGQ1YyM0dsUjlveSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTk6Imh0dHBzOi8vb3VybmVhci5jb20iO3M6NToicm91dGUiO3M6NDoiaG9tZSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1789817193),
('Mn4tsRB7kP7TucI5Sh1xkaCfAFIZA1o4aVnT11iD', NULL, '173.252.82.48', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiMThEb2xSYTExaUxnTmJ0N3ZudWlBTmJXdDNyMWM2ZWhFQW9GSktoSCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTk6Imh0dHBzOi8vb3VybmVhci5jb20iO3M6NToicm91dGUiO3M6NDoiaG9tZSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1789817177),
('nONpyVzbuQMHaucyXG5kQbSPQy0m9VityyhaSMPu', NULL, '173.252.95.14', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiQ1c4N1N1cVh0T1phZkhHQ3BMbXhQQWE2Vk1PN1Y4eUVFWVJ6bmhTOSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTk6Imh0dHBzOi8vb3VybmVhci5jb20iO3M6NToicm91dGUiO3M6NDoiaG9tZSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1789824220),
('Sc8u461bt6M13a1CPMb0uUKLrdPINiTtkqwGeeS4', NULL, '173.252.82.46', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoicFVzTzJpWWNkUlZwVTZJdXZBOXFEMTQ3ams3dVBkOHJSWEVST3lIcCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTk6Imh0dHBzOi8vb3VybmVhci5jb20iO3M6NToicm91dGUiO3M6NDoiaG9tZSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1789817177),
('T19jf16IgNmMKnrETxKywbqZ1cOGLTE2ixxrTmM0', NULL, '122.183.41.234', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiNG9PWjFXeDZzZTA0RklhZ1U2NlFpVFVleXZKclQ4QTRxYWlRRHJ4SSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTk6Imh0dHBzOi8vb3VybmVhci5jb20iO3M6NToicm91dGUiO3M6NDoiaG9tZSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1789817101),
('UFIOQAiol56j1onX1kBIxECjExufPPxM8SRaOL3j', NULL, '69.63.184.25', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiUmt2YWZQMUIyb0dEUnBCN2pqTE5QWjB0aGhTZ0dUZnNYcjlZREpKWSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTk6Imh0dHBzOi8vb3VybmVhci5jb20iO3M6NToicm91dGUiO3M6NDoiaG9tZSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1789824087),
('vcQ23aYLQN75FfkzJBIANxiXP3nSUPoL8W26tVbu', NULL, '173.252.82.3', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiUnlXdmFRZjVQaUkyWWl5NllIczFXSU9scFFSVGVURGtLQnM3a243dyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTk6Imh0dHBzOi8vb3VybmVhci5jb20iO3M6NToicm91dGUiO3M6NDoiaG9tZSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1789817194),
('watCN6L7JpRBOcZVUp2sySvakbeV5hGp87xqzTr8', NULL, '69.63.184.116', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiVVV1S0FYZXFQZWlid0dRR1kyaERYWmZPUkFDbmR6ekJPM2VIRnFzaSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTk6Imh0dHBzOi8vb3VybmVhci5jb20iO3M6NToicm91dGUiO3M6NDoiaG9tZSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1789817176),
('YBqIJJ0CTeNbgrWh4qZH9MfkcxTFl0QkG009Byhg', NULL, '54.152.198.76', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_1) AppleWebKit/600.2.5 (KHTML, like Gecko) Version/8.0.2 Safari/600.2.5 (Gort)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiVkhXRVc1elBmbjV6NlVqOXhqRWNvZzJQWVlHR1M4bjhUUjJtZnEwQSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzA6Imh0dHBzOi8vb3VybmVhci5jb20vcm9ib3RzLnR4dCI7czo1OiJyb3V0ZSI7czo2OiJyb2JvdHMiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1789824086);

-- --------------------------------------------------------

--
-- Table structure for table `social_links`
--

CREATE TABLE `social_links` (
  `id` int(11) NOT NULL,
  `platform` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `social_links`
--

INSERT INTO `social_links` (`id`, `platform`, `label`, `url`, `is_enabled`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'linkedin', 'LinkedIn', NULL, 0, 0, '2026-08-30 16:07:12', '2026-08-30 16:07:12'),
(2, 'facebook', 'Facebook', NULL, 0, 1, '2026-08-30 16:07:12', '2026-08-30 16:07:12'),
(3, 'instagram', 'Instagram', NULL, 0, 2, '2026-08-30 16:07:12', '2026-08-30 16:07:12'),
(4, 'youtube', 'YouTube', NULL, 0, 3, '2026-08-30 16:07:12', '2026-08-30 16:07:12'),
(5, 'whatsapp', 'WhatsApp', NULL, 0, 4, '2026-08-30 16:07:12', '2026-08-30 16:07:12'),
(6, 'telegram', 'Telegram', NULL, 0, 5, '2026-08-30 16:07:12', '2026-08-30 16:07:12');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'candidate'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `role`) VALUES
(1, 'ounear', 'venkatadmin09@ournear.com', '2026-08-30 15:27:57', '$2y$12$WOYKmiJqmLQ9GQOEGulvwO9Jb4/NRpij8RuSqCfSi2YTTUeG5lgg.', 'wuSRhPqoMPkgIrS5LrKaszSQx1ZMfw2myEgX7Umleo0eVFdJteIUjiORh7Qn', '2026-08-30 15:27:57', '2026-08-30 16:59:24', 'admin'),
(2, 'venkat  Narayana Reddy', 'pvnrengineer@gmail.com', NULL, '$2y$12$MDEucgT7pRNhj1/Oo3aDmeUDY4RsJEo8DrUp4UrXRmeEHSh9LCR8O', NULL, '2026-08-30 16:12:16', '2026-08-30 16:12:16', 'candidate'),
(3, 'venkat reddy', 'venkat.venkat48@gmail.com', NULL, '$2y$12$CpP18U9qp5/Gj7rP3han0uZTZIWgSfXCxEmxhT3IuZqRB2v7RUmQy', NULL, '2026-08-30 16:13:44', '2026-08-30 16:13:44', 'employer'),
(4, 'Pranay Kumar', 'pranaykumar60@gmail.com', NULL, '$2y$12$23elfZ6ts1IvviGu.2lSdOMb1WLZPk1Jiqk8UAAfyF6vomTR1BjLq', NULL, '2026-08-31 14:11:45', '2026-08-31 14:11:45', 'candidate'),
(5, 'A.Maninder', 'maniankarla13@gmail.com', NULL, '$2y$12$sDdfivg/71OpSz9Pbf2ite0v2lfKGji624pLNM156cvRxIe.eFjju', NULL, '2026-09-01 12:53:37', '2026-09-01 12:53:37', 'candidate'),
(6, 'Arman Ali', 'armanali8454133@gmail.com', NULL, '$2y$12$Tzh.BvMqp3ab9gD1zzC.C.yxO0u2nkTpyePWHD1NeJb7muHXBwrgi', NULL, '2026-09-02 09:51:25', '2026-09-02 09:51:25', 'candidate'),
(7, 'Dadi Veera venkata ramana', 'veerababu130297@gmail.com', NULL, '$2y$12$zQGKggym9IwWJhSpLGoJ6.TXoCdJWtm3RzfCUXvgkLfuB10SaEGnW', NULL, '2026-09-09 02:08:05', '2026-09-09 02:08:05', 'candidate'),
(8, 'Pankaj Kumar', 'pankajkumar1994199419@gmail.com', NULL, '$2y$12$UiLX1XYHbeGc8iBR87Idvuew0B9FkSsQzJpQgdRSAtzJz24Vh5WWe', NULL, '2026-09-10 18:46:41', '2026-09-10 18:46:41', 'candidate');

-- --------------------------------------------------------

--
-- Table structure for table `website_pages`
--

CREATE TABLE `website_pages` (
  `id` int(11) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `seo_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `website_pages`
--

INSERT INTO `website_pages` (`id`, `slug`, `title`, `created_at`, `updated_at`, `seo_title`, `meta_description`) VALUES
(1, 'home', 'Home', '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL, NULL),
(2, 'about', 'About Us', '2026-08-30 15:27:59', '2026-08-30 15:27:59', NULL, NULL),
(3, 'services', 'Services', '2026-08-30 15:27:59', '2026-08-30 15:27:59', NULL, NULL),
(4, 'for-employers', 'For Employers', '2026-08-30 15:28:00', '2026-08-30 15:28:00', NULL, NULL),
(5, 'contact', 'Contact Us', '2026-08-30 15:28:00', '2026-08-30 15:28:00', NULL, NULL),
(6, 'privacy-policy', 'Privacy Policy', '2026-08-30 15:28:00', '2026-08-30 15:28:00', NULL, NULL),
(7, 'terms-and-conditions', 'Terms & Conditions', '2026-08-30 15:28:00', '2026-08-30 15:28:00', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `website_sections`
--

CREATE TABLE `website_sections` (
  `id` int(11) NOT NULL,
  `page_id` int(11) NOT NULL,
  `section_key` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL,
  `heading` varchar(255) DEFAULT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `body` text DEFAULT NULL,
  `primary_button_label` varchar(255) DEFAULT NULL,
  `secondary_button_label` varchar(255) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `extra` text DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `media_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `website_sections`
--

INSERT INTO `website_sections` (`id`, `page_id`, `section_key`, `label`, `heading`, `subtitle`, `body`, `primary_button_label`, `secondary_button_label`, `image_path`, `extra`, `sort_order`, `is_active`, `created_at`, `updated_at`, `media_id`) VALUES
(1, 1, 'hero', 'Hero Section', 'RIGHT PEOPLE.\nRIGHT TRAINING.\nBETTER FUTURE.', NULL, 'OurNear Talent supplies capable people, practical training, and project support so employers can deliver with confidence.', 'Find Jobs', 'For Employers', 'media/32bada4d-198d-4754-927a-45b2801240d8.jpeg', NULL, 0, 1, '2026-08-30 15:27:58', '2026-08-30 17:09:52', 1),
(2, 1, 'what_we_do', 'What We Do', 'What We Do', NULL, 'We connect the right people with the right work, and support them with the right training.', NULL, NULL, NULL, NULL, 1, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(3, 1, 'core_services', 'Core Services', 'Core Services', NULL, 'Workforce, training, and project support designed around delivery.', NULL, NULL, NULL, NULL, 2, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(4, 1, 'industries', 'Industries', 'Industries We Serve', NULL, 'Practical talent support for demanding operational environments.', NULL, NULL, NULL, NULL, 3, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(5, 1, 'how_we_work', 'How We Work', 'How We Work', NULL, 'A clear process from understanding the need to ongoing support.', NULL, NULL, NULL, NULL, 4, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(6, 1, 'why_choose_us', 'Why Choose Us', 'Why Choose OurNear Talent', NULL, 'We measure success by the quality of the match and the result delivered.', NULL, NULL, NULL, NULL, 5, 1, '2026-08-30 15:27:58', '2026-08-30 15:27:58', NULL),
(7, 1, 'our_commitment', 'Our Commitment', 'Our Commitment', NULL, 'Clear communication, dependable delivery, and accountability to the result.', NULL, NULL, NULL, NULL, 6, 1, '2026-08-30 15:27:59', '2026-08-30 17:09:52', NULL),
(8, 2, 'hero', 'About Hero / Our Story', 'Our Story', NULL, 'OurNear Talent exists to put the right people, with the right training, into work that delivers the right results.', NULL, NULL, '', NULL, 0, 1, '2026-08-30 15:27:59', '2026-08-30 15:27:59', NULL),
(9, 2, 'who_we_are', 'Who We Are', 'Who We Are', NULL, 'A talent partner for employers who need capable people and for candidates who want a clear next step.', NULL, NULL, NULL, NULL, 1, 1, '2026-08-30 15:27:59', '2026-08-30 15:27:59', NULL),
(10, 2, 'what_we_do', 'What We Do', 'What We Do', NULL, 'We supply people, training, and project support as one connected service.', NULL, NULL, NULL, NULL, 2, 1, '2026-08-30 15:27:59', '2026-08-30 15:27:59', NULL),
(11, 2, 'our_approach', 'Our Approach', 'Our Approach', NULL, 'People, training, and performance working together.', NULL, NULL, NULL, NULL, 3, 1, '2026-08-30 15:27:59', '2026-08-30 15:27:59', NULL),
(12, 2, 'industries', 'Industries We Serve', 'Industries We Serve', NULL, 'We support operational and project environments where capability matters.', NULL, NULL, NULL, NULL, 4, 1, '2026-08-30 15:27:59', '2026-08-30 15:27:59', NULL),
(13, 2, 'why_choose_us', 'Why Choose OurNear Talent', 'Why Choose OurNear Talent', NULL, 'Reliable people, practical training, and delivery that holds up on site.', NULL, NULL, NULL, NULL, 5, 1, '2026-08-30 15:27:59', '2026-08-30 15:27:59', NULL),
(14, 2, 'our_commitment', 'Our Commitment', 'Our Commitment', NULL, 'Honest advice, dependable follow-through, and respect for every employer and candidate.', NULL, NULL, '', NULL, 6, 1, '2026-08-30 15:27:59', '2026-08-30 15:27:59', NULL),
(15, 3, 'hero', 'Services Hero', 'Services', 'People. Training. Delivery.', 'Recruitment, training, competency, and project support designed around the result you need.', NULL, NULL, '', NULL, 0, 1, '2026-08-30 15:27:59', '2026-08-30 15:27:59', NULL),
(16, 3, 'core_services', 'Core Services', 'Core Services', NULL, 'Six connected services that help employers staff, train, and deliver.', NULL, NULL, NULL, NULL, 1, 1, '2026-08-30 15:27:59', '2026-08-30 15:27:59', NULL),
(17, 3, 'workforce', 'Workers, Staff & Professionals', 'Workers, Staff & Professionals We Provide', NULL, 'From site labour to professional and management support.', NULL, NULL, NULL, NULL, 2, 1, '2026-08-30 15:27:59', '2026-08-30 15:27:59', NULL),
(18, 3, 'training', 'Training for Workers & Staff', 'Training for Workers & Staff', NULL, 'Practical training aligned to the work people actually do.', NULL, NULL, NULL, NULL, 3, 1, '2026-08-30 15:27:59', '2026-08-30 15:27:59', NULL),
(19, 3, 'delivery_process', 'Employee Evaluation & Delivery', 'Employee Evaluation & Delivery Process', NULL, 'A structured path from requirement to deployment.', NULL, NULL, NULL, NULL, 4, 1, '2026-08-30 15:27:59', '2026-08-30 15:27:59', NULL),
(20, 3, 'competency_process', 'Training & Competency Management', 'Training & Competency Management Process', NULL, 'Assess, train, test, report, and improve.', NULL, NULL, NULL, NULL, 5, 1, '2026-08-30 15:27:59', '2026-08-30 15:27:59', NULL),
(21, 3, 'project_support', 'Project Support & Management', 'Project Support & Management', NULL, 'End-to-end support so projects stay staffed and productive.', NULL, NULL, NULL, NULL, 6, 1, '2026-08-30 15:27:59', '2026-08-30 15:27:59', NULL),
(22, 3, 'industries', 'Industries', 'Industries We Serve', NULL, 'Operational sectors where the right people and training make a measurable difference.', NULL, NULL, NULL, NULL, 7, 1, '2026-08-30 15:27:59', '2026-08-30 15:27:59', NULL),
(23, 3, 'why_choose_us', 'Why Choose Us', 'Why Choose Us', NULL, 'A delivery-focused approach to workforce and training.', NULL, NULL, NULL, NULL, 8, 1, '2026-08-30 15:27:59', '2026-08-30 15:27:59', NULL),
(24, 3, 'cta', 'CTA Section', 'Talk to OurNear Talent', NULL, 'Tell us about the role, the team, or the training outcome you need.', 'Contact us', NULL, '', NULL, 9, 1, '2026-08-30 15:28:00', '2026-08-30 15:28:00', NULL),
(25, 4, 'hero', 'Hero', 'For Employers', NULL, 'Hire with confidence. We help you find the right people and support them with the right training so you get the right results.', NULL, NULL, '', NULL, 0, 1, '2026-08-30 15:28:00', '2026-08-30 15:28:00', NULL),
(26, 4, 'what_we_provide', 'What We Provide Employers', 'What We Provide Employers', NULL, 'Workforce, training, assessment, and ongoing support.', NULL, NULL, NULL, NULL, 1, 1, '2026-08-30 15:28:00', '2026-08-30 15:28:00', NULL),
(27, 4, 'how_we_deliver', 'How We Deliver', 'How We Deliver', NULL, 'A practical process from brief to follow-up.', NULL, NULL, NULL, NULL, 2, 1, '2026-08-30 15:28:00', '2026-08-30 15:28:00', NULL),
(28, 4, 'what_employers_get', 'What Employers Get', 'What Employers Get', NULL, 'Clear matching, job-ready people, and accountable delivery.', NULL, NULL, NULL, NULL, 3, 1, '2026-08-30 15:28:00', '2026-08-30 15:28:00', NULL),
(29, 4, 'workforce', 'Workforce We Can Provide', 'Workforce We Can Provide', NULL, 'Flexible supply across labour, technical, supervisory, and professional roles.', NULL, NULL, NULL, NULL, 4, 1, '2026-08-30 15:28:00', '2026-08-30 15:28:00', NULL),
(30, 4, 'industries', 'Industries We Serve', 'Industries We Serve', NULL, 'Support for projects and operations that need capable people quickly.', NULL, NULL, NULL, NULL, 5, 1, '2026-08-30 15:28:00', '2026-08-30 15:28:00', NULL),
(31, 4, 'get_in_touch', 'Get In Touch', 'Get In Touch', NULL, 'Submit a workforce or project request and we will follow up.', NULL, NULL, NULL, NULL, 6, 1, '2026-08-30 15:28:00', '2026-08-30 15:28:00', NULL),
(32, 5, 'hero', 'Hero', 'Contact Us', NULL, 'Reach OurNear Talent about jobs, training, or employer support.', NULL, NULL, NULL, NULL, 0, 1, '2026-08-30 15:28:00', '2026-08-30 17:12:40', NULL),
(33, 5, 'details', 'Contact Information', 'How to reach us', NULL, 'Message, or send an enquiry. We will point you to the right next step.', NULL, NULL, NULL, '{\"phone\":\"Office phone \\u2014 to be published\",\"whatsapp\":\"+91-7008046416\",\"email\":\"Office email \\u2014 to be published\",\"address\":\"Office address \\u2014 to be published\",\"hours\":\"Monday to Saturday, 9:00am \\u2013 6:00pm\",\"help\":\"Use the form for recruitment, training, or general enquiries.\"}', 1, 1, '2026-08-30 15:28:00', '2026-09-01 04:03:25', NULL),
(34, 6, 'content', 'Privacy Policy', 'Privacy Policy', NULL, 'PRIVACY POLICY\n\nOurNear Talent respects the privacy of candidates, employers and visitors to ournear.com.\n\nThis Privacy Policy explains how we collect, use, disclose, store and protect personal information when you use our Platform.\n\nIndia\'s Digital Personal Data Protection framework requires clear information about processing and provides rights around consent and withdrawal where consent is the applicable basis.\n\n1. Information We Collect\n\nDepending on how you use OurNearTalent, we may collect:\n\nA. Account Information\nName\nEmail address\nMobile number\nPassword/account credentials\nLocation\nProfile information\nB. Employment Information\nResume/CV\nEducation\nWork experience\nCurrent/previous employer\nDesignation\nSkills\nCertifications\nLicences\nProfessional qualifications\nNotice period\nCurrent/expected compensation\nPreferred location\nEmployment preferences\nC. Application Information\n\nWhen you apply for a job, we may collect:\n\nJob applied for\nApplication details\nResume\nScreening responses\nInterview information\nRecruiter notes\nCandidate status\nEmployer feedback relating to recruitment\nD. Employer Information\n\nEmployers may provide:\n\nCompany name\nContact information\nJob requirements\nJob descriptions\nRecruitment requirements\nCompany information\nAuthorized representative details\nE. Technical Information\n\nWe may automatically collect certain technical information such as:\n\nIP address\nBrowser type\nDevice information\nOperating system\nWebsite usage information\nLogin information\nCookies and similar technologies\nPages visited\nApproximate location derived from technical information where applicable\n2. How We Use Personal Information\n\nWe may process personal information for purposes including:\n\nRecruitment\nProcessing job applications;\nScreening candidates;\nConducting preliminary interviews;\nShortlisting candidates;\nSharing relevant candidate profiles with employers;\nCoordinating interviews;\nCommunicating recruitment updates;\nSupporting joining processes.\nPlatform Operations\nCreating and managing accounts;\nProviding Platform functionality;\nMaintaining security;\nPreventing fraud;\nTroubleshooting;\nImproving Platform performance.\nCommunications\n\nWe may contact users regarding:\n\nApplications;\nJob opportunities;\nRecruitment status;\nInterviews;\nAccount activity;\nImportant Platform notices;\nService updates.\n\nWhere required, marketing communications will be subject to applicable consent/opt-out requirements.\n\n3. Candidate Consent\n\nWhere consent is the applicable legal basis for processing personal data, OurNearTalent will seek consent in an appropriate manner.\n\nConsent should be:\n\nClear;\nSpecific;\nInformed; and\nGiven through affirmative action.\n\nThe DPDP Act expressly provides that consent must be free, specific, informed, unconditional and unambiguous, and provides for withdrawal of consent where consent is the processing basis.\n\nFor example, when submitting a job application, a candidate may be presented with a statement such as:\n\n“I agree that OurNearTalent may process my information for recruitment purposes and share relevant information with employers for consideration for suitable employment opportunities.”\n\n4. Candidate Profile Sharing\n\nWhen a candidate applies for a job, relevant candidate information may be shared with:\n\nThe employer offering the vacancy;\nAuthorized recruitment personnel;\nRecruitment partners where necessary and appropriately authorized;\nService providers supporting recruitment operations.\n\nWe will seek to limit sharing to information reasonably relevant to recruitment and the applicable purpose.\n\n5. Recruitment Database\n\nWhere permitted by applicable law and based on the applicable processing basis, OurNearTalent may retain candidate information in its recruitment database to:\n\nConsider candidates for future opportunities;\nContact candidates about relevant vacancies;\nMaintain recruitment records;\nImprove recruitment services.\n\nCandidates may contact us regarding their personal information and applicable rights.\n\n6. Employers and Third Parties\n\nWe may share information with third parties where reasonably necessary to provide the Services, including:\n\nEmployers;\nHosting providers;\nCloud service providers;\nEmail/SMS/communication providers;\nRecruitment software providers;\nAnalytics providers;\nPayment providers;\nSecurity providers;\nProfessional advisers;\nGovernment or regulatory authorities where legally required.\n\nWe expect service providers processing personal information on our behalf to maintain appropriate confidentiality and security measures.\n\n7. Payments\n\nIf OurNearTalent offers paid services, payment information may be processed by third-party payment service providers.\n\nWhere appropriate, OurNearTalent may not directly store complete payment-card information.\n\nPayment providers may process information according to their own privacy policies.\n\n8. Cookies\n\nOurNearTalent may use cookies and similar technologies to:\n\nKeep users logged in;\nRemember preferences;\nImprove website performance;\nUnderstand website usage;\nMaintain security;\nMeasure Platform performance.\n\nUsers may control certain cookies through their browser settings, although disabling cookies may affect some Platform functionality.\n\n9. Data Security\n\nWe use reasonable technical and organizational measures designed to protect personal information against:\n\nUnauthorized access;\nUnauthorized disclosure;\nLoss;\nMisuse;\nAlteration; and\nDestruction.\n\nHowever, no Internet transmission or electronic storage system can be guaranteed to be completely secure.\n\nUsers should protect their account credentials and notify us promptly if they suspect unauthorized access.\n\n10. Data Retention\n\nWe retain personal information only for as long as reasonably necessary for:\n\nProviding the Services;\nRecruitment purposes;\nMaintaining recruitment records;\nFulfilling contractual obligations;\nComplying with legal/regulatory requirements;\nResolving disputes;\nPreventing fraud; and\nEnforcing agreements.\n\nWhen personal information is no longer required, we may delete, anonymize or otherwise dispose of it in accordance with applicable law and our retention practices.\n\n11. Your Privacy Rights\n\nSubject to applicable law and the circumstances of processing, individuals may have rights relating to their personal information, including rights concerning:\n\nAccess to information;\nCorrection/update of inaccurate information;\nWithdrawal of consent where consent is the legal basis;\nErasure/deletion where applicable;\nGrievance/complaint;\nOther rights provided under applicable data-protection law.\n\nThe DPDP Act provides, among other things, for withdrawal of consent with comparable ease where consent is the basis of processing.\n\n12. Children\'s Data\n\nOurNearTalent is primarily intended for employment and recruitment services for individuals legally able to participate in employment opportunities.\n\nWe do not knowingly seek personal information from children for recruitment purposes.\n\nIf we become aware that personal information has been collected from a child contrary to applicable law, we will take appropriate steps as required by law.\n\n13. Sensitive Information\n\nCandidates should not upload unnecessary sensitive personal information unless it is specifically requested for a legitimate recruitment or legal purpose.\n\nExamples include:\n\nPasswords;\nBanking passwords;\nCredit/debit card PINs;\nAuthentication codes;\nUnnecessary medical information;\nOther highly sensitive information unrelated to recruitment.\n\nOurNearTalent will never ask candidates to share account passwords or OTPs for job application purposes.\n\n14. Fraud and Fake Job Warnings\n\nOurNearTalent will not guarantee employment in exchange for an undisclosed or unauthorized payment.\n\nCandidates should be cautious of anyone claiming to represent OurNearTalent and requesting:\n\nOTPs;\nBanking passwords;\nUPI PINs;\nATM PINs;\nUnauthorized payments;\nPersonal financial credentials.\n\nIf you suspect a fraudulent recruitment communication, contact us through the official contact details listed on ournear.com.\n\n15. Employer Data\n\nInformation provided by employers may include confidential business information and recruitment requirements.\n\nOurNearTalent will use employer information for legitimate business and recruitment purposes and will take reasonable measures to protect confidential information.\n\n16. International Data Processing\n\nSome technology or service providers may process information from locations outside India.\n\nWhere personal information is transferred or processed outside India, OurNearTalent will take steps required by applicable law.\n\n17. Data Breach / Security Incidents\n\nIf a personal-data breach occurs, OurNearTalent will take appropriate steps to investigate, contain and respond to the incident and provide notifications where required by applicable law.\n\n18. Third-Party Websites\n\nOurNearTalent may contain links to third-party websites.\n\nThis Privacy Policy does not govern third-party websites.\n\nUsers should review the privacy policies of those websites before submitting information.\n\n19. Changes to Privacy Policy\n\nWe may update this Privacy Policy periodically.\n\nThe updated version will be published on this page with the revised Last Updated date.\n\nWhere required by applicable law, we will provide appropriate notice regarding material changes.', NULL, NULL, NULL, NULL, 0, 1, '2026-08-30 15:28:00', '2026-09-01 04:01:49', NULL),
(35, 7, 'content', 'Terms & Conditions', 'Terms & Conditions', NULL, 'TERMS & CONDITIONS / USER AGREEMENT\n\nWelcome to OurNearTalent (“OurNearTalent”, “we”, “us”, “our”).\n\nOurNearTalent operates the website ournear.com and related recruitment, talent sourcing, job-listing and workforce services (collectively, the “Platform” or “Services”).\n\nBy accessing, registering on, or using our Platform, you agree to these Terms & Conditions (“Terms”). If you do not agree with these Terms, please do not use the Platform.\n\n1. About OurNearTalent\n\nOurNearTalent is a recruitment and talent platform intended to connect:\n\nJob seekers and candidates;\nEmployers and organisations;\nRecruitment requirements and suitable candidates; and\nWhere applicable, workforce recruitment, staffing and training services.\n\nOurNearTalent may publish vacancies received from employers and may assist employers with candidate sourcing, screening, shortlisting, interview coordination and related recruitment services.\n\n2. Eligibility\n\nYou must provide accurate information when using the Platform.\n\nBy using OurNearTalent, you represent that:\n\nThe information provided by you is accurate and current;\nYou have the legal capacity to use the Platform;\nYou will not impersonate another person;\nYou will not create false or misleading profiles;\nYou will not misuse another person\'s information or account.\n\nIf you are applying for a job on behalf of another person, you must have their authorization.\n\n3. Candidate Responsibilities\n\nCandidates are responsible for ensuring that all information submitted to OurNearTalent is accurate.\n\nThis includes:\n\nName;\nContact details;\nEducation;\nExperience;\nEmployment history;\nSkills;\nCertifications;\nLicences;\nSalary information;\nResume/CV;\nOther information submitted during recruitment.\n\nCandidates must not:\n\nSubmit false qualifications;\nSubmit fake experience;\nUpload fraudulent documents;\nMisrepresent employment history;\nApply using another person\'s identity;\nSubmit documents belonging to another person without authorization.\n\nOurNearTalent may reject, suspend or remove an application/profile where information appears inaccurate, fraudulent, misleading or otherwise unsuitable.\n\n4. Job Listings\n\nJob vacancies may be:\n\nPosted directly by OurNearTalent;\nPosted on behalf of employers;\nSourced from employer-provided requirements; or\nPublished through authorised recruitment arrangements.\n\nWhile we make reasonable efforts to maintain accurate job information, OurNearTalent does not guarantee that every job listing will remain available, accurate, unchanged or open for applications at all times.\n\nEmployers may:\n\nClose vacancies;\nChange requirements;\nChange salary;\nChange the number of openings;\nChange the location;\nDelay recruitment;\nCancel recruitment; or\nSelect candidates independently.\n5. Recruitment and Shortlisting\n\nSubmitting an application does not guarantee:\n\nScreening;\nShortlisting;\nInterview;\nSelection;\nEmployment;\nSalary;\nJoining; or\nPlacement.\n\nOurNearTalent may screen candidates according to the requirements provided by the relevant employer.\n\nThe final decision regarding employment normally rests with the employer unless a separate staffing/deployment arrangement applies.\n\n6. Candidate Screening\n\nOurNearTalent may conduct:\n\nResume screening;\nQualification screening;\nExperience screening;\nTelephone screening;\nPreliminary interviews;\nSkill-based screening;\nBasic document review; and\nOther recruitment-related assessments.\n\nScreening by OurNearTalent does not constitute a guarantee of employment or final selection.\n\nWhere an employer conducts its own interview, assessment or verification, the employer remains responsible for its final selection decision.\n\n7. Sharing Candidate Profiles with Employers\n\nWhen you apply for a vacancy, you authorize OurNearTalent to use and share relevant application information with the employer or potential employer for recruitment purposes, subject to our Privacy Policy and applicable law.\n\nInformation shared may include, where relevant:\n\nName;\nContact details;\nResume;\nEducation;\nWork experience;\nSkills;\nCertifications;\nProfessional information;\nInterview/screening information; and\nOther information reasonably necessary for recruitment.\n\nWe will not intentionally share information unrelated to the recruitment purpose unless required or permitted by applicable law or otherwise authorized.\n\n8. Employer Responsibilities\n\nEmployers using OurNearTalent are responsible for providing accurate information about their vacancies.\n\nEmployers must not:\n\nPublish fraudulent vacancies;\nMislead candidates;\nRequest unlawful payments;\nCollect unnecessary personal information;\nDiscriminate unlawfully;\nMisrepresent salary or employment conditions;\nUse candidate information for unrelated purposes; or\nMisuse information obtained through OurNearTalent.\n\nEmployers are responsible for their employment decisions and employment relationship with selected candidates, except where OurNearTalent provides a separate manpower/staffing service under a written agreement.\n\n9. No Guarantee of Employment\n\nOurNearTalent is a recruitment and talent-sourcing platform.\n\nUnless specifically stated under a written employment or staffing agreement:\n\nOurNearTalent does not guarantee that any candidate will obtain employment or that any employer will fill a vacancy through the Platform.\n\n10. Recruitment Fees / Candidate Charges\n\nCertain OurNearTalent services may be free to candidates, while other services may involve charges where clearly disclosed.\n\nIf any fee is applicable:\n\nThe applicable fee will be communicated before payment;\nThe purpose of the fee will be disclosed;\nCandidates should review the applicable terms before making payment.\n\nOurNearTalent does not guarantee employment in exchange for any payment.\n\nAny recruitment-related payment must not be interpreted as a guarantee of selection or employment.\n\n11. Prohibited Activities\n\nUsers must not use OurNearTalent to:\n\nCommit fraud;\nUpload malicious software;\nAttempt unauthorized access;\nScrape or copy Platform data without authorization;\nCreate fake accounts;\nMisuse candidate/employer information;\nUpload unlawful content;\nUpload defamatory or abusive content;\nInfringe intellectual-property rights;\nPublish fraudulent jobs;\nMisrepresent identity;\nHarass other users;\nCircumvent Platform security;\nInterfere with Platform operations; or\nUse the Platform for any unlawful purpose.\n\nWe may suspend or terminate accounts that violate these Terms.\n\n12. Resume and Content Ownership\n\nCandidates retain ownership of their resumes and information submitted by them, subject to the rights necessary for OurNearTalent to operate the Services.\n\nBy submitting content to OurNearTalent, you grant us a non-exclusive right to use, store, reproduce and process that content as reasonably necessary to:\n\nProvide recruitment services;\nScreen candidates;\nShare profiles with relevant employers;\nOperate the Platform;\nMaintain records;\nImprove the Services; and\nComply with applicable law.\n\nYou must have the necessary rights to submit any content you upload.\n\n13. Intellectual Property\n\nThe OurNearTalent name, logo, website design, software, text, graphics, branding, databases, content and other Platform materials are owned by or licensed to OurNearTalent unless otherwise stated.\n\nYou may not copy, reproduce, modify, distribute, sell or commercially exploit OurNearTalent intellectual property without written permission.\n\n14. Third-Party Links\n\nOur Platform may contain links to third-party websites, services or resources.\n\nOurNearTalent is not responsible for:\n\nThird-party content;\nPrivacy practices;\nAvailability;\nSecurity;\nAccuracy; or\nTransactions conducted through third-party services.\n\nUsers should review the applicable third-party terms and privacy policies.\n\n15. Platform Availability\n\nWe will make reasonable efforts to maintain the availability of the Platform.\n\nHowever, we do not guarantee uninterrupted or error-free operation.\n\nThe Platform may occasionally be unavailable because of:\n\nMaintenance;\nTechnical failures;\nHosting issues;\nNetwork problems;\nSecurity incidents;\nUpdates;\nForce majeure events; or\nOther circumstances outside our reasonable control.\n16. Account Suspension or Termination\n\nWe may suspend or terminate access to an account where we reasonably believe that the user:\n\nViolated these Terms;\nProvided false information;\nEngaged in fraudulent activity;\nMisused the Platform;\nCreated a security risk;\nViolated another person\'s rights; or\nViolated applicable law.\n\nUsers may request account closure by contacting us.\n\n17. Limitation of Liability\n\nTo the extent permitted by applicable law, OurNearTalent will not be responsible for:\n\nAn employer\'s hiring decision;\nA candidate\'s employment performance;\nEmployer cancellation of vacancies;\nCandidate withdrawal;\nCandidate failure to join;\nEmployer failure to hire;\nChanges to job requirements;\nLoss caused by inaccurate information supplied by users;\nThird-party website issues; or\nIndirect or consequential losses arising from use of the Platform.\n\nNothing in these Terms excludes liability that cannot lawfully be excluded under applicable law.\n\n18. Indemnification\n\nTo the extent permitted by applicable law, users agree to indemnify and hold OurNearTalent harmless from claims, losses, liabilities or expenses arising from:\n\nTheir violation of these Terms;\nFalse or misleading information supplied by them;\nTheir unlawful use of the Platform;\nTheir infringement of another person\'s rights; or\nTheir misuse of Platform services.\n19. Privacy\n\nUse of personal information submitted through OurNearTalent is governed by our Privacy Policy.\n\nOur Privacy Policy explains how we collect, use, store, share and protect personal information.\n\n20. Grievance / Complaints\n\nUsers may contact OurNearTalent regarding:\n\nAccount issues;\nPrivacy concerns;\nJob listing complaints;\nMisuse of personal information;\nFraudulent job listings;\nPlatform abuse; or\nOther complaints relating to our Services.\nWe will process complaints in accordance with applicable law.\n\n21. Changes to These Terms\n\nWe may update these Terms from time to time.\n\nUpdated Terms will be published on this page with a revised Last Updated date.\n\nContinued use of the Platform after an update may constitute acceptance of the revised Terms, subject to applicable law.\n\n22. Governing Law and Jurisdiction\n\nThese Terms shall be governed by the laws of India.\n\nSubject to applicable law, disputes shall be subject to the jurisdiction of the competent courts at [City, Telangana, India].', NULL, NULL, NULL, NULL, 0, 1, '2026-08-30 15:28:00', '2026-09-01 02:59:29', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `website_settings`
--

CREATE TABLE `website_settings` (
  `id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `office_phone` varchar(255) DEFAULT NULL,
  `office_email` varchar(255) DEFAULT NULL,
  `website_url` varchar(255) DEFAULT NULL,
  `office_address` varchar(255) DEFAULT NULL,
  `copyright_text` varchar(255) DEFAULT NULL,
  `default_seo_title` varchar(255) DEFAULT NULL,
  `default_meta_description` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `notification_emails` text DEFAULT NULL,
  `lock_key` varchar(255) DEFAULT NULL,
  `website_name` varchar(255) NOT NULL DEFAULT 'OurNear Talent',
  `logo_path` varchar(255) DEFAULT NULL,
  `icon_path` varchar(255) DEFAULT NULL,
  `favicon_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `website_settings`
--

INSERT INTO `website_settings` (`id`, `company_name`, `office_phone`, `office_email`, `website_url`, `office_address`, `copyright_text`, `default_seo_title`, `default_meta_description`, `created_at`, `updated_at`, `notification_emails`, `lock_key`, `website_name`, `logo_path`, `icon_path`, `favicon_path`) VALUES
(1, 'OurNear Talent', '+91-7008046416', 'talent@ournear.com', NULL, 'Gayathri Nagar, Hayathnagar_Khalsa, Hyderabad, Telangana 500070', '© 2026 OurNear. All rights reserved.', 'OurNear Talent', 'OurNear Talent supplies capable people, practical training, and project support so employers can deliver with confidence.', '2026-08-30 16:07:12', '2026-09-01 04:03:02', '[]', 'default', 'OurNear Talent', NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `applications_job_id_email_unique` (`job_id`,`email`),
  ADD KEY `fk_applications_user_id` (`user_id`),
  ADD KEY `applications_created_at_index` (`created_at`),
  ADD KEY `applications_current_location_index` (`current_location`),
  ADD KEY `applications_email_index` (`email`),
  ADD KEY `applications_experience_index` (`experience`),
  ADD KEY `applications_status_index` (`status`);

--
-- Indexes for table `application_interviews`
--
ALTER TABLE `application_interviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `application_interviews_application_id_unique` (`application_id`),
  ADD KEY `fk_application_interviews_created_by` (`created_by`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `candidate_profiles`
--
ALTER TABLE `candidate_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `candidate_profiles_user_id_unique` (`user_id`);

--
-- Indexes for table `contact_enquiries`
--
ALTER TABLE `contact_enquiries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contact_enquiries_created_at_index` (`created_at`),
  ADD KEY `contact_enquiries_email_index` (`email`),
  ADD KEY `contact_enquiries_interested_in_index` (`interested_in`),
  ADD KEY `contact_enquiries_status_index` (`status`);

--
-- Indexes for table `content_items`
--
ALTER TABLE `content_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_content_items_media_id` (`media_id`),
  ADD KEY `content_items_group_key_sort_order_index` (`group_key`,`sort_order`),
  ADD KEY `content_items_type_index` (`type`);

--
-- Indexes for table `employer_enquiries`
--
ALTER TABLE `employer_enquiries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_employer_enquiries_user_id` (`user_id`),
  ADD KEY `employer_enquiries_created_at_index` (`created_at`),
  ADD KEY `employer_enquiries_email_index` (`email`),
  ADD KEY `employer_enquiries_industry_index` (`industry`),
  ADD KEY `employer_enquiries_project_location_index` (`project_location`),
  ADD KEY `employer_enquiries_status_index` (`status`),
  ADD KEY `employer_enquiries_workforce_type_index` (`workforce_type`);

--
-- Indexes for table `employer_profiles`
--
ALTER TABLE `employer_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employer_profiles_user_id_unique` (`user_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `job_listings`
--
ALTER TABLE `job_listings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `job_listings_slug_unique` (`slug`),
  ADD KEY `job_listings_active_index` (`active`),
  ADD KEY `job_listings_active_publishing_status_closing_date_index` (`active`,`publishing_status`,`closing_date`),
  ADD KEY `job_listings_category_index` (`category`),
  ADD KEY `job_listings_closing_date_index` (`closing_date`),
  ADD KEY `job_listings_employment_type_index` (`employment_type`),
  ADD KEY `job_listings_experience_index` (`experience`),
  ADD KEY `job_listings_featured_index` (`featured`),
  ADD KEY `job_listings_industry_index` (`industry`),
  ADD KEY `job_listings_location_index` (`location`),
  ADD KEY `job_listings_posted_date_index` (`posted_date`),
  ADD KEY `job_listings_publishing_status_index` (`publishing_status`);

--
-- Indexes for table `media_assets`
--
ALTER TABLE `media_assets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `media_assets_created_at_index` (`created_at`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `page_section_items`
--
ALTER TABLE `page_section_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `page_section_items_section_id_content_item_id_unique` (`section_id`,`content_item_id`),
  ADD KEY `fk_page_section_items_content_item_id` (`content_item_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `recruitment_activities`
--
ALTER TABLE `recruitment_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_recruitment_activities_actor_id` (`actor_id`),
  ADD KEY `fk_recruitment_activities_employer_enquiry_id` (`employer_enquiry_id`),
  ADD KEY `fk_recruitment_activities_application_id` (`application_id`),
  ADD KEY `recruitment_activities_created_at_index` (`created_at`),
  ADD KEY `recruitment_activities_type_index` (`type`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`),
  ADD KEY `sessions_user_id_index` (`user_id`);

--
-- Indexes for table `social_links`
--
ALTER TABLE `social_links`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `social_links_platform_unique` (`platform`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_role_index` (`role`);

--
-- Indexes for table `website_pages`
--
ALTER TABLE `website_pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `website_pages_slug_unique` (`slug`);

--
-- Indexes for table `website_sections`
--
ALTER TABLE `website_sections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `website_sections_page_id_section_key_unique` (`page_id`,`section_key`),
  ADD KEY `fk_website_sections_media_id` (`media_id`),
  ADD KEY `website_sections_page_id_sort_order_index` (`page_id`,`sort_order`);

--
-- Indexes for table `website_settings`
--
ALTER TABLE `website_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `website_settings_lock_key_unique` (`lock_key`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `application_interviews`
--
ALTER TABLE `application_interviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `candidate_profiles`
--
ALTER TABLE `candidate_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `contact_enquiries`
--
ALTER TABLE `contact_enquiries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `content_items`
--
ALTER TABLE `content_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `employer_enquiries`
--
ALTER TABLE `employer_enquiries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `employer_profiles`
--
ALTER TABLE `employer_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `job_listings`
--
ALTER TABLE `job_listings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `media_assets`
--
ALTER TABLE `media_assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `page_section_items`
--
ALTER TABLE `page_section_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=123;

--
-- AUTO_INCREMENT for table `recruitment_activities`
--
ALTER TABLE `recruitment_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `social_links`
--
ALTER TABLE `social_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `website_pages`
--
ALTER TABLE `website_pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `website_sections`
--
ALTER TABLE `website_sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `website_settings`
--
ALTER TABLE `website_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `fk_applications_job_id` FOREIGN KEY (`job_id`) REFERENCES `job_listings` (`id`),
  ADD CONSTRAINT `fk_applications_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `application_interviews`
--
ALTER TABLE `application_interviews`
  ADD CONSTRAINT `fk_application_interviews_application_id` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_application_interviews_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `candidate_profiles`
--
ALTER TABLE `candidate_profiles`
  ADD CONSTRAINT `fk_candidate_profiles_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `content_items`
--
ALTER TABLE `content_items`
  ADD CONSTRAINT `fk_content_items_media_id` FOREIGN KEY (`media_id`) REFERENCES `media_assets` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `employer_enquiries`
--
ALTER TABLE `employer_enquiries`
  ADD CONSTRAINT `fk_employer_enquiries_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `employer_profiles`
--
ALTER TABLE `employer_profiles`
  ADD CONSTRAINT `fk_employer_profiles_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `page_section_items`
--
ALTER TABLE `page_section_items`
  ADD CONSTRAINT `fk_page_section_items_content_item_id` FOREIGN KEY (`content_item_id`) REFERENCES `content_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_page_section_items_section_id` FOREIGN KEY (`section_id`) REFERENCES `website_sections` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `recruitment_activities`
--
ALTER TABLE `recruitment_activities`
  ADD CONSTRAINT `fk_recruitment_activities_actor_id` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_recruitment_activities_application_id` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_recruitment_activities_employer_enquiry_id` FOREIGN KEY (`employer_enquiry_id`) REFERENCES `employer_enquiries` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `website_sections`
--
ALTER TABLE `website_sections`
  ADD CONSTRAINT `fk_website_sections_media_id` FOREIGN KEY (`media_id`) REFERENCES `media_assets` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_website_sections_page_id` FOREIGN KEY (`page_id`) REFERENCES `website_pages` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
