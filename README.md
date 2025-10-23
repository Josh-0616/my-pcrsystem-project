# 📘 PCR Performance Commitment and Review System  

## 🏫 Overview  
The **PCR System** (Performance Commitment and Review) is a web-based platform developed for **Philippine Countryville College, Inc.** It allows administrators, faculty to manage performance evaluations, submissions, and ratings digitally.  

This system streamlines the evaluation process by allowing users to submit PCR forms, track performance, and generate reports efficiently.  

---

## ⚙️ Features  

### 👩‍💼 Admin Panel  
- Manage users (faculty)  
- View, approve, and rate PCR submissions  
- Generate reports per department  
- View analytics and statistics  
- Manage announcements and deadlines  
- Recycle bin and restore options  
- Dark/Light mode toggle  

### 👨‍🏫 Faculty Panel  
- Submit and edit PCR forms  
- View submission history and total submissions  
- Track PCR status (Pending, Reviewed, Approved)  
- View announcements and deadlines  
- Generate personal PCR reports in PDF  
- Manage account and change password  
- Recycle bin for deleted submissions  
- Dark/Light mode toggle  

---

## 🧰 Technologies Used  
- **Frontend:** HTML5, CSS3, Bootstrap 5  
- **Backend:** PHP 8  
- **Database:** MySQL  
- **PDF Generation:** TCPDF 
- **JavaScript:** Chart.js for analytics  
- **Authentication:** PHP Session-based Login
- **UI Design:** Glassmorphism & Soft Modern UI with Responsive Design  

---

## 🗄️ Database Structure  
**Database Name:** `apps_pcc_db`  

Main tables:
- `users` — stores account details and roles (admin, faculty, staff)  
- `ipcr_forms` — stores submitted IPCR form data  
- `ipcr_entries` — stores PCR performance indicators  
- `ratings` — stores admin ratings and remarks  
- `logs` — activity logs  
- `notifications` — announcement and alert system  

---

## 📂 Project Folder Structure  
📁 PCC_PCR_SYSTEM/
├── 📁 asset/
├── 📁 dashboard/
│   ├── 📄about.php
│   ├── 📄add_announcement.php
│   ├── 📄admin_dashboard.php  ---> naay sidebar ug topbar
│   ├── 📄admin_view_profile.php
│   ├── 📄announcements.php    ---> naay sidebar ug topbar 
│   ├── 📄Change_Password.php
│   ├── 📄deadline_settings.php     ---> naay sidebar ug topbar 
│   ├── 📄delete_announcement.php
│   ├── 📄delete_ipcr.php
│   ├── 📄department_reports.php   ---> naay sidebar ug topbar 
│   ├── 📄editprofile.php
│   ├── 📄empty_recycle_bin.php
│   ├── 📄generate.php
│   ├── 📄generate_code_registration.php
│   ├── 📄generate_pdf.php
│   ├── 📄generate_submission_pdf.php
│   ├── 📄get_faculty_profile.php
│   ├── 📄manage_users.php  ---> naay sidebar ug topbar
│   ├── 📄mark_announcement_read.php
│   ├── 📄mark_read.php
│   ├── 📄my_account.php
│   ├── 📄my_ipcr.php
│   ├── 📄my_submissions.php
│   ├── 📄notifications.php
│   ├── 📄permanent_delete.php 
│   ├── 📄process_change_password.php 
│   ├── 📄profile.php
│   ├── 📄rate_form.php 
│   ├── 📄rate_staff.php 
│   ├── 📄recycle_bin.php    ---> naay sidebar ug topbar
│   ├── 📄restore_submission.php
│   ├── 📄save_rating.php 
│   ├── 📄status_report.php  ---> naay sidebar ug topbar 
│   ├── 📄submit_ipcr.php 
│   ├── 📄update_ipcr.php 
│   ├── 📄update_profile.php 
│   ├── 📄view_analytics.php 
│   ├── 📄view_faculty_profile.php
│   ├── 📄view_ipcr_submissions.php    ---> naay sidebar ug topbar 
│   ├── 📄view_submission.php 
│   └── 📄viewprofile.php
│
├── 📁 db/
│   ├── 🗄️ apps_pcc_db.sql
│   └── 📄 connection.php
│
├── 📁 faculty/
│   ├── 📄 about.php
│   ├── 📄Change_Password.php
│   ├── 📄delete_submission.php 
│   ├── 📄 edit_submission.php
│   ├── 📄faculty_announcements.php
│   ├── 📄faculty_dashboard.php   ---> naay sidebar ug topbar 
│   ├── 📄generate_pdf.php 
│   ├── 📄generatePDF.php  ---> naay sidebar ug topbar
│   ├── 📄move_to_recycle_bin.php 
│   ├── 📄My_Account.php
│   ├── 📄My_Approved_IPCRs.php 
│   ├── 📄My_Pending_Reviews.php 
│   ├── 📄my_submissions.php   ---> naay sidebar ug topbar
│   ├── 📄My_Total_Submissions.php 
│   ├── 📄recycle_bin.php    ---> naay sidebar ug topbar
│   ├── 📄restore_submission.php
│   ├── 📄save_ipcr.php 
│   ├── 📄submit_ipcr.php  ---> naay sidebar ug topbar
│   ├── 📄view_announcements.php   ---> naay sidebar ug topbar
│   ├── 📄view_profile.php 
│   └── 📄view_submission.php 
│
├── 📁 libs/
├── 📁 pdfs/
├── 📁 PHPMailer/
├── 📁 tcpdf/
├── 📁 TCPDF - main/
├── 📁 uploads/
├── 📁 webfonts/
│
├── 📄 landing.php
├── 📄 LearnMore.php
├── 📄 login.php
├── 📄 login_check.php
├── 📄 login_process.php
├── 📄 logout.php
└── 📄 register.php




