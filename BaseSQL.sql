CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(120) NOT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    role ENUM('Administrador', 'Doutor', 'Recepcionista') NOT NULL,
    mfa_enabled TINYINT(1) DEFAULT 0,
    mfa_secret VARCHAR(255) DEFAULT NULL,
    failed_attempts INT DEFAULT 0,
    is_locked TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    must_change_password TINYINT(1) DEFAULT 1,
    activation_token VARCHAR(64) DEFAULT NULL,

    must_reset_mfa TINYINT(1) NOT NULL DEFAULT 0,
    last_mfa_reset DATETIME NULL,
    last_login TIMESTAMP NULL,
    password_changed_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role ENUM('Administrador', 'Doutor', 'Recepcionista') NOT NULL UNIQUE,
    can_create_patient BOOLEAN DEFAULT 0,
    can_delete_patient BOOLEAN DEFAULT 0,
    can_create_diagnosis BOOLEAN DEFAULT 0,
    can_prescribe BOOLEAN DEFAULT 0,
    can_manage_consultation BOOLEAN DEFAULT 0
);

CREATE TABLE patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    birthdate DATE NOT NULL,
    gender ENUM('Masculino','Feminino','Não Binário') NOT NULL,
    citizen_card VARCHAR(20) NOT NULL UNIQUE,
    health_number VARCHAR(20) NOT NULL UNIQUE,
    social_security VARCHAR(20),
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE consultations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    created_by INT,
    diagnosis TEXT,
    notes TEXT,
    consultation_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Agendada', 'Pendente', 'Executada', 'Cancelada') DEFAULT 'Agendada',

    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE prescriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    consultation_id INT NOT NULL,
    prescribed_by INT NOT NULL,
    medication_name VARCHAR(150) NOT NULL,
    dosage VARCHAR(100),
    frequency VARCHAR(100),
    duration VARCHAR(100),
    instructions TEXT,
    prescribed_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (consultation_id) REFERENCES consultations(id) ON DELETE CASCADE,
    FOREIGN KEY (prescribed_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    consultation_id INT NOT NULL,
    requested_by INT NOT NULL,
    exam_type VARCHAR(100) NOT NULL,
    reason TEXT,
    status ENUM('Pedido', 'Agendado', 'Completo','Cancelado') DEFAULT 'pedido',
    requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (consultation_id) REFERENCES consultations(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE exam_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    result TEXT,
    attachment VARCHAR(255),
    completed_at DATETIME,

    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);

CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    username VARCHAR(50) NULL,
    severity ENUM( 'INFO', 'WARNING', 'CRITICAL') DEFAULT 'INFO',
    log_type ENUM('LOGIN', 'MFA', 'PATIENT', 'APPOINTMENT', 'EXAM', 'USER', 'SYSTEM') NOT NULL,

    action VARCHAR(100) NOT NULL,
    entity_name VARCHAR(100),
    entity_id INT NULL,
    details TEXT,

    happened_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE backups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_name VARCHAR(255) NOT NULL UNIQUE,
    file_path VARCHAR(500) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by INT NOT NULL,
    file_size BIGINT NOT NULL,
    sha256 CHAR(64) NOT NULL,
    total_patients INT NOT NULL,
    total_consultations INT NOT NULL,
    total_records INT NOT NULL,
    encryption_algorithm VARCHAR(50) NOT NULL DEFAULT 'AES-256-CBC',
    status ENUM('AVAILABLE','CORRUPTED','DELETED') DEFAULT 'AVAILABLE',
    last_restore_at DATETIME DEFAULT NULL,
    last_restore_by INT DEFAULT NULL,
    
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (last_restore_by) REFERENCES users(id)
);