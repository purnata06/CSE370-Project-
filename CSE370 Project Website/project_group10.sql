DROP DATABASE IF EXISTS cse370_project;
CREATE DATABASE cse370_project;
USE cse370_project;

-- USERS (main login table)
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    user_name VARCHAR(50) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    gender ENUM('female','male','other'),
    role ENUM('user','partner') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- PATIENT 
CREATE TABLE patient (
    patient_id INT PRIMARY KEY,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- PARTNER 
CREATE TABLE partner (
    partner_id INT PRIMARY KEY,
    relationship_status VARCHAR(50),
    linked_patient_id INT,
    FOREIGN KEY (partner_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (linked_patient_id) REFERENCES patient(patient_id) ON DELETE CASCADE
);

-- PSYCHIATRISTS / SUPPORT 
CREATE TABLE psychiatrists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    specialization VARCHAR(120),
    degree VARCHAR(120),
    history TEXT,
    email VARCHAR(200) UNIQUE,
    mobile VARCHAR(20),
    schedule VARCHAR(120),
    location VARCHAR(200)
);

-- MENTAL HEALTH SUPPORT
CREATE TABLE mental_health_support (
    support_id INT AUTO_INCREMENT PRIMARY KEY,
);

-- BOOKS/ Support
CREATE TABLE books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- CYCLES 
CREATE TABLE cycles (
  cycle_id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NULL,
  cycle_length INT DEFAULT 28,
  prediction_date DATE NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- SYMPTOM LOG
CREATE TABLE symptoms (
  symptom_id INT AUTO_INCREMENT PRIMARY KEY,
  symptom_name VARCHAR(80) NOT NULL,
  description VARCHAR(255) NULL
);

CREATE TABLE symptom_log (
  log_id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  date DATE NOT NULL,
  symptom_id INT NOT NULL,
  severity_level TINYINT CHECK (severity_level BETWEEN 1 AND 5),
  notes TEXT,
  predicted_advice TEXT,
  FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (symptom_id) REFERENCES symptoms(symptom_id) ON DELETE CASCADE
);

-- ACTIVITY / PRODUCTIVITY / MOOD
CREATE TABLE activity_log (
  activity_id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  date DATE NOT NULL,
  activity_description VARCHAR(255),
  mood_description VARCHAR(255),
  productivity_score TINYINT CHECK (productivity_score BETWEEN 1 AND 10),
  FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- REMINDERS
CREATE TABLE reminders (
  reminder_id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  type VARCHAR(100),
  date_time DATETIME NOT NULL,
  `trigger` ENUM(
    'custom',
    'upcoming_cycle',
    'medication',
    'doctor_appointment',
    'therapy_session',
    'mental_health_check-in',
    'exercise',
    'hydration',
    'sleep_schedule',
    'self_care'
  ) DEFAULT 'custom',
  is_sent TINYINT DEFAULT 0,
  FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- COMMUNITY POSTS (anonymous supported)
CREATE TABLE community_posts (
    post_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    content TEXT,
    post_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    anonymous_flag BOOLEAN DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

--Community Comments
CREATE TABLE community_comments (
  comment_id INT AUTO_INCREMENT PRIMARY KEY,
  post_id INT NOT NULL,
  user_id INT NOT NULL,
  content TEXT NOT NULL,
  anonymous_flag TINYINT(1) DEFAULT 1,
  comment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (post_id) REFERENCES community_posts(post_id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- CONTACTS / RECEIVES
CREATE TABLE patient_support_contacts (
  patient_id INT NOT NULL,
  psychiatrist_id INT NOT NULL,
  PRIMARY KEY (patient_id, psychiatrist_id),
  FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (psychiatrist_id) REFERENCES psychiatrists(id) ON DELETE CASCADE
);

-- REPORTS
CREATE TABLE reports (
  report_id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  file_path VARCHAR(255),
  generation_id VARCHAR(64),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE
);




