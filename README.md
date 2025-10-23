# 📘 PCR (Performance Commitment and Review) System  

## 🏫 Overview  
The **PCR System** (Performance Commitment and Review) is a web-based platform developed for **Philippine Countryville College, Inc.** It allows administrators and faculty to manage performance evaluations, submissions, and ratings digitally.  

This system simplifies the performance evaluation workflow by allowing users to create, submit, review, and rate PCR forms. It ensures transparency, accuracy, and faster report generation for institutional performance monitoring.  

---

## ⚙️ Features  

### 👩‍💼 Admin Panel  
- Manage users (Faculty and Staff)  
- View, approve, and rate PCR submissions  
- Generate reports per department (BSBA, BSIT, BS-CRIME)  
- View analytics and statistics with charts  
- Manage announcements and set deadlines  
- Recycle bin with restore and permanent delete options  
- View and download generated PDF reports  
- Activity logs and notifications system  
- Responsive modern UI with dark/light mode toggle  

### 👨‍🏫 Faculty Panel  
- Submit, edit, and delete PCR forms  
- Track submission status (Pending, Reviewed, Approved)  
- View submission history and total submissions  
- View announcements and deadlines  
- Generate personal PCR reports in PDF  
- Manage profile and change password  
- Access recycle bin for deleted submissions  
- Responsive dashboard with dark/light mode toggle  

---

## 🧰 Technologies Used  
- **Frontend:** HTML5, CSS3, Bootstrap 5  
- **Backend:** PHP 8  
- **Database:** MySQL  
- **PDF Generation:** TCPDF  
- **JavaScript Libraries:** Chart.js for analytics and stats  
- **Email System:** PHPMailer  
- **Authentication:** PHP Session-based Login  
- **UI Design:** Glassmorphism & Soft Modern UI with Responsive Layout  

---

## 🗄️ Database Structure  
**Database Name:** `apps_pcc_db`  

Main Tables:  
| Table Name | Description |
|-------------|-------------|
| `users` | Stores user details, roles (admin, faculty, staff), and login credentials |
| `ipcr_forms` | Stores each submitted IPCR form by faculty |
| `ipcr_entries` | Contains performance indicators or commitments |
| `ratings` | Stores admin ratings, comments, and average performance score |
| `logs` | Tracks user activities (login, submission, updates) |
| `notifications` | Handles announcements and system notifications |

---

## 📂 Project Folder Structure  

📁 **PCC_PCR_SYSTEM/**  
├── 📁 asset/  
│   └── *(images, CSS, JS, icons, and other resources)*  
│  
├── 📁 dashboard/  
│   ├── 📄 about.php  
│   ├── 📄 add_announcement.php  
│   ├── 📄 admin_dashboard.php  → with sidebar & topbar  
│   ├── 📄 announcements.php    → with sidebar & topbar  
│   ├── 📄 deadline_settings.php → with sidebar & topbar  
│   ├── 📄 department_reports.php → with sidebar & topbar  
│   ├── 📄 manage_users.php     → with sidebar & topbar  
│   ├── 📄 recycle_bin.php      → with sidebar & topbar  
│   ├── 📄 status_report.php    → with sidebar & topbar  
│   ├── 📄 view_ipcr_submissions.php → with sidebar & topbar  
│   ├── 📄 view_analytics.php   → charts and statistics  
│   ├── 📄 rate_form.php / rate_staff.php  
│   ├── 📄 editprofile.php / update_profile.php  
│   ├── 📄 generate_pdf.php / generate_submission_pdf.php  
│   ├── 📄 generate_code_registration.php  
│   ├── 📄 notifications.php  
│   ├── 📄 restore_submission.php / delete_ipcr.php  
│   └── *(other admin-related PHP files)*  
│  
├── 📁 faculty/  
│   ├── 📄 faculty_dashboard.php → with sidebar & topbar  
│   ├── 📄 my_submissions.php    → with sidebar & topbar  
│   ├── 📄 submit_ipcr.php       → with sidebar & topbar  
│   ├── 📄 view_announcements.php → with sidebar & topbar  
│   ├── 📄 recycle_bin.php       → with sidebar & topbar  
│   ├── 📄 My_Account.php / Change_Password.php  
│   ├── 📄 My_Approved_IPCRs.php / My_Pending_Reviews.php  
│   ├── 📄 My_Total_Submissions.php  
│   ├── 📄 generate_pdf.php / generatePDF.php  
│   ├── 📄 edit_submission.php / delete_submission.php  
│   ├── 📄 restore_submission.php / move_to_recycle_bin.php  
│   └── *(other faculty-related PHP files)*  
│  
├── 📁 db/  
│   ├── 🗄️ apps_pcc_db.sql  
│   └── 📄 connection.php  
│  
├── 📁 libs/  
├── 📁 pdfs/  
├── 📁 PHPMailer/  
├── 📁 tcpdf/  
├── 📁 TCPDF - main/  
├── 📁 uploads/  
├── 📁 webfonts/  
│  
├── 📄 index.php (Landing Page)  
├── 📄 LearnMore.php  
├── 📄 login.php  
├── 📄 login_check.php  
├── 📄 login_process.php  
├── 📄 logout.php  
└── 📄 register.php  

---

## 🚀 How to Run the Project  

1. **Clone or download** this repository.  
2. **Move** the project folder to your XAMPP `htdocs` directory.  
   ```bash
   C:\xampp\htdocs\PCC_PCR_SYSTEM
