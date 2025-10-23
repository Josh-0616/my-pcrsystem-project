# 📘 PCR Performance Commitment and Review System  

## 🏫 Overview  
The **PCR System** (Performance Commitment and Review) is a web-based platform developed for **Philippine Countryville College, Inc.** It allows administrators and faculty to manage performance evaluations, submissions, and ratings digitally.  

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
📁 **PCC_PCR_SYSTEM/**  
├── 📁 asset/  
├── 📁 dashboard/  
│   ├── 📄 about.php  
│   ├── 📄 admin_dashboard.php  → with sidebar & topbar  
│   ├── 📄 manage_users.php  
│   ├── 📄 announcements.php  
│   ├── 📄 recycle_bin.php  
│   ├── 📄 status_report.php  
│   ├── 📄 department_reports.php  
│   ├── 📄 view_ipcr_submissions.php  
│   └── (other admin-related files)  
│  
├── 📁 faculty/  
│   ├── 📄 faculty_dashboard.php  
│   ├── 📄 my_submissions.php  
│   ├── 📄 recycle_bin.php  
│   ├── 📄 submit_ipcr.php  
│   ├── 📄 view_announcements.php  
│   └── (other faculty-related files)  
│  
├── 📁 db/  
│   ├── 🗄️ apps_pcc_db.sql  
│   └── 📄 connection.php  
│  
├── 📁 tcpdf/  
├── 📁 uploads/  
├── 📁 pdfs/  
├── 📁 PHPMailer/  
│  
├── 📄 landing.php  
├── 📄 LearnMore.php  
├── 📄 login.php  
├── 📄 register.php  
├── 📄 logout.php  
└── 📄 login_process.php  

---

## 🚀 How to Run the Project  

1. Clone or download this repository.  
2. Move the folder to your `htdocs` directory (for XAMPP).  
3. Import the database file `apps_pcc_db.sql` located inside the `/db` folder.  
4. Open your browser and go to:  
