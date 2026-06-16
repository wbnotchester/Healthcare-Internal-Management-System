# 🏥 Healthcare Internal Management System

Secure internal management platform for healthcare organizations, designed to support administrative staff and healthcare professionals through role-based access control, multi-factor authentication and patient management workflows.

---

## ✨ Features

* 🔐 Multi-Factor Authentication (MFA)
* 👥 Role-Based Access Control (RBAC)
* 🩺 Patient Management
* 📅 Consultation Management
* 👤 User Management
* 📝 Activity Logging
* 💾 Backup & Restore
* 🛡️ SQL Injection Protection

---

## 👨‍⚕️ User Roles & Permissions

| Functionality           | Administrator | Doctor     | Receptionist |
| ----------------------- | ------------- | ---------- | ------------ |
| User Management         | ✅             | ❌          | ❌            |
| Patient Management      | ✅             | ✅          | ✅            |
| Consultation Management | ✅             | ✅          | ✅            |
| Backup Management       | ✅             | ❌          | ❌            |
| Administrative Settings | ✅             | ❌          | ❌            |
| Activity Logs           | ✅             | ❌          | ❌            |
| MFA Required            | ✅ Mandatory   | ⚪ Optional | ⚪ Optional   |

---

## 🔒 Security Controls

| Control                            | Status |
| ---------------------------------- | ------ |
| Multi-Factor Authentication (TOTP) | ✅      |
| Role-Based Access Control (RBAC)   | ✅      |
| Secure Authentication              | ✅      |
| Activity Logging                   | ✅      |
| Backup & Recovery                  | ✅      |
| Session Management                 | ✅      |
| SQL Injection Mitigation           | ✅      |

---

## 🛠️ Technology Stack

**Backend**

* PHP

**Database**

* MySQL

**Frontend**

* HTML
* CSS
* JavaScript

**Security**

* Google Authenticator (TOTP)
* RBAC
* Session Authentication

---

## 📂 Project Structure

```text
dashboard.php
patients.php
consultations.php
users.php
backup.php
restore_backup.php
account.php
login.php

includes/
 ├── auth.php
 ├── db.php
 ├── mfa.php
 └── googleAuthenticator.php

backups/
logs/
```

---

## 🚀 Planned Improvements

* Modern dashboard redesign
* Audit log interface
* Dark mode
* Advanced patient search
* Pagination
* Responsive design improvements

---

## 📸 Screenshots

*To be added*

---

## 🎓 Background

This project was originally developed as part of a Secure Software Systems academic group project. The design, implementation, and maintenance of the application, including both the backend and frontend components, were carried out by me. The project has since been extended and maintained as a portfolio application demonstrating secure full-stack web development practices.
