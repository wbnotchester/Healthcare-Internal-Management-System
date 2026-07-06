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
| Passwod Definition Safe Mode       | ✅      |
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
account.php
login.php

includes/
 ├── audit.php
 ├── auth.php
 ├── db.php
 ├── mfa.php
 └── googleAuthenticator.php

backups/
```

---

## 🚀 Planned Improvements

* Audit log interface
* Advanced patient search
* Pagination
* Responsive design improvements

---

## 📸 Screenshots

<img width="200" height="200" alt="HIMS-01" src="https://github.com/user-attachments/assets/978e20ae-0d86-4baa-9d1f-e8870b10e5dc" />
<img width="200" height="200" alt="HIMS-02" src="https://github.com/user-attachments/assets/29583e54-cfc4-49a1-a2ce-e328a6da4a7f" />
<img width="200" height="200" alt="HIMS-03" src="https://github.com/user-attachments/assets/df772259-fcb2-4553-a9c5-e7d1a361e159" />




---

## 🎓 Background

This project was originally developed as part of a Secure Software Systems academic group project. The design, implementation, and maintenance of the application, including both the backend and frontend components, were carried out by me. The project has since been extended and maintained as a portfolio application demonstrating secure full-stack web development practices.
