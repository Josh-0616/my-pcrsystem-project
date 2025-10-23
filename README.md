# 📘 PCR Performance Commitment and Review System  

## 🏫 Overview  
The **PCR System** (Performance Commitment and Review) is a web-based platform developed for **Philippine Countryville College, Inc.**  
It allows administrators and faculty members to manage performance evaluations, submissions, and ratings digitally.  

This system streamlines the evaluation process by allowing users to submit PCR forms, track performance, and generate reports efficiently.

---

## ⚙️ Features  

### 👩‍💼 Admin Panel  
- Manage users (faculty and staff)  
- View, approve, and rate PCR submissions  
- Generate reports per department  
- View analytics and performance statistics  
- Manage announcements and deadlines  
- Access recycle bin and restore deleted submissions  
- Dark/Light mode toggle for better UI  

### 👨‍🏫 Faculty Panel  
- Submit and edit PCR forms  
- Track submission history and total submissions  
- View status (Pending, Reviewed, Approved)  
- Access announcements and deadlines  
- Generate PCR reports in PDF  
- Manage personal profile and change password  
- Recycle bin for deleted submissions  
- Responsive design with dark/light mode  

---

## 🧰 Technologies Used  
- **Frontend:** HTML5, CSS3, Bootstrap 5  
- **Backend:** PHP 8  
- **Database:** MySQL  
- **PDF Generation:** TCPDF  
- **Charts:** Chart.js for analytics visualization  
- **Authentication:** PHP Session-based Login System  
- **UI Design:** Glassmorphism + Soft Modern Responsive Design  

---

## 🗄️ Database Structure  
**Database Name:** `apps_pcc_db`  

| Table Name | Description |
|-------------|-------------|
| `activity_logs` | Logs of user activities and actions. |
| `admindashboard_notification` | Notifications specific to admin dashboard updates. |
| `announcements` | Stores announcements created by the admin. |
| `announcement_reads` | Tracks which users have read specific announcements. |
| `codes_registration` | Stores registration verification codes for new users. |
| `deleted_submissions` | Archive for permanently deleted submissions. |
| `facultydeleted_submissions` | Records deleted submissions from faculty side. |
| `faqs` | Frequently Asked Questions content for users. |
| `ipcr_details` | Details of IPCR entries for each submission. |
| `ipcr_forms` | Main table for submitted IPCR forms. |
| `ipcr_metrics` | Stores performance indicators and metrics. |
| `ipcr_settings` | System settings related to IPCR configurations. |
| `notifications` | General notification system for all users. |
| `opcr_submissions` | OPCR form submissions for administrators/staff. |
| `password_resets` | Temporary tokens for password reset requests. |
| `pccopcr_submissions` | Stores PCC-level OPCR submissions. |
| `registration_codes` | Verification codes for user registration. |
| `saveipcr_submissions` | Draft or saved IPCR submissions not yet finalized. |
| `users` | User accounts table (admin, faculty, staff). |
| `user_profiles` | Stores profile information for each user. |

📊 **Total Tables:** 21  

---

## 📂 Project Folder Structure  
📁 **PCC_PCR_SYSTEM/**  
├── 📁 **asset/**  
├── 📁 **dashboard/**  
│   ├── 📄 about.php  
│   ├── 📄 add_announcement.php  
│   ├── 📄 admin_dashboard.php  ---> naay sidebar ug topbar  
│   ├── 📄 admin_view_profile.php  
│   ├── 📄 announcements.php  ---> naay sidebar ug topbar  
│   ├── 📄 Change_Password.php  
│   ├── 📄 deadline_settings.php  ---> naay sidebar ug topbar  
│   ├── 📄 delete_announcement.php  
│   ├── 📄 delete_ipcr.php  
│   ├── 📄 department_reports.php  ---> naay sidebar ug topbar  
│   ├── 📄 editprofile.php  
│   ├── 📄 empty_recycle_bin.php  
│   ├── 📄 generate.php  
│   ├── 📄 generate_code_registration.php  
│   ├── 📄 generate_pdf.php  
│   ├── 📄 generate_submission_pdf.php  
│   ├── 📄 get_faculty_profile.php  
│   ├── 📄 manage_users.php  ---> naay sidebar ug topbar  
│   ├── 📄 mark_announcement_read.php  
│   ├── 📄 mark_read.php  
│   ├── 📄 my_account.php  
│   ├── 📄 my_ipcr.php  
│   ├── 📄 my_submissions.php  
│   ├── 📄 notifications.php  
│   ├── 📄 permanent_delete.php  
│   ├── 📄 process_change_password.php  
│   ├── 📄 profile.php  
│   ├── 📄 rate_form.php  
│   ├── 📄 rate_staff.php  
│   ├── 📄 recycle_bin.php  ---> naay sidebar ug topbar  
│   ├── 📄 restore_submission.php  
│   ├── 📄 save_rating.php  
│   ├── 📄 status_report.php  ---> naay sidebar ug topbar  
│   ├── 📄 submit_ipcr.php  
│   ├── 📄 update_ipcr.php  
│   ├── 📄 update_profile.php  
│   ├── 📄 view_analytics.php  
│   ├── 📄 view_faculty_profile.php  
│   ├── 📄 view_ipcr_submissions.php  ---> naay sidebar ug topbar  
│   ├── 📄 view_submission.php  
│   └── 📄 viewprofile.php  
│
├── 📁 **db/**  
│   ├── 🗄️ apps_pcc_db.sql  
│   └── 📄 connection.php  
│
├── 📁 **faculty/**  
│   ├── 📄 about.php  
│   ├── 📄 Change_Password.php  
│   ├── 📄 delete_submission.php  
│   ├── 📄 edit_submission.php  
│   ├── 📄 faculty_announcements.php  
│   ├── 📄 faculty_dashboard.php  ---> naay sidebar ug topbar  
│   ├── 📄 generate_pdf.php  
│   ├── 📄 generatePDF.php  ---> naay sidebar ug topbar  
│   ├── 📄 move_to_recycle_bin.php  
│   ├── 📄 My_Account.php  
│   ├── 📄 My_Approved_IPCRs.php  
│   ├── 📄 My_Pending_Reviews.php  
│   ├── 📄 my_submissions.php  ---> naay sidebar ug topbar  
│   ├── 📄 My_Total_Submissions.php  
│   ├── 📄 recycle_bin.php  ---> naay sidebar ug topbar  
│   ├── 📄 restore_submission.php  
│   ├── 📄 save_ipcr.php  
│   ├── 📄 submit_ipcr.php  ---> naay sidebar ug topbar  
│   ├── 📄 view_announcements.php  ---> naay sidebar ug topbar  
│   ├── 📄 view_profile.php  
│   └── 📄 view_submission.php  
├── 📁 **libs/**  
├── 📁 **pdfs/**  
├── 📁 **PHPMailer/**  
├── 📁 **tcpdf/**  
├── 📁 **TCPDF - main/**  
├── 📁 **uploads/**  
├── 📁 **webfonts/**  
├── 📄 landing.php  
├── 📄 LearnMore.php  
├── 📄 login.php  
├── 📄 login_check.php  
├── 📄 login_process.php  
├── 📄 logout.php  
└── 📄 register.php  

---

## 🚀 Landing Page  
The system starts with **landing.php** as the default homepage.  
It includes the system logo, title, and navigation buttons for **Sign In** and **Create Account**.  
Upon login, users are redirected to their respective dashboards (Admin or Faculty).

---

## 👨‍💻 Developers  
Developed as part of a **Capstone Project** for **Philippine Countryville College, Inc.**  
Created by **Group 3** — Bachelor of Science in Information Technology (BSIT).

---

✅ **Status:** Fully Functional  
✅ **Database:** `apps_pcc_db` (21 tables)  
✅ **PHP Version:** 8.x  
✅ **Last Updated:** October 2025

