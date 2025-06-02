-- EngliFy Database Setup Script für MySQL
-- Für SQLite wird die Datenbank automatisch über database.php erstellt

-- Datenbank erstellen (nur für MySQL)
CREATE DATABASE IF NOT EXISTS englify_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE englify_db;

-- Benutzer Tabelle
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username)
);

-- Vokabeln Tabelle
CREATE TABLE IF NOT EXISTS vocabulary (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    english VARCHAR(255) NOT NULL,
    german VARCHAR(255) NOT NULL,
    pronunciation VARCHAR(255) NULL,
    english_example TEXT NULL,
    german_example TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_english (english),
    INDEX idx_german (german)
);

-- Benutzer Statistiken
CREATE TABLE IF NOT EXISTS user_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    correct_answers INT DEFAULT 0,
    wrong_answers INT DEFAULT 0,
    study_sessions INT DEFAULT 0,
    last_study_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_stats (user_id)
);

-- Sessions Tabelle
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_session_id (session_id),
    INDEX idx_user_session (user_id)
);

-- Lernsitzungen Tabelle (Optional für detaillierte Statistiken)
CREATE TABLE IF NOT EXISTS study_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    duration_minutes INT DEFAULT 0,
    questions_answered INT DEFAULT 0,
    correct_answers INT DEFAULT 0,
    session_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_study (user_id),
    INDEX idx_session_date (session_date)
);

-- Demo-Benutzer erstellen
INSERT IGNORE INTO users (username, password, name, email) VALUES 
('demo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo Benutzer', 'demo@englify.com');

-- Demo-Vokabeln für Demo-User
SET @demo_user_id = (SELECT id FROM users WHERE username = 'demo');

INSERT IGNORE INTO vocabulary (user_id, english, german, pronunciation, english_example, german_example) VALUES 
(@demo_user_id, 'influence', 'beeinflussen', '[ˈɪnfluəns]', 'Social media can influence our opinions', 'Soziale Medien können unsere Meinungen beeinflussen'),
(@demo_user_id, 'environment', 'Umwelt', '[ɪnˈvaɪrənmənt]', 'We must protect our environment', 'Wir müssen unsere Umwelt schützen'),
(@demo_user_id, 'responsibility', 'Verantwortung', '[rɪˌspɒnsəˈbɪləti]', 'Taking responsibility is important', 'Verantwortung zu übernehmen ist wichtig'),
(@demo_user_id, 'development', 'Entwicklung', '[dɪˈveləpmənt]', 'The development of technology is rapid', 'Die Entwicklung der Technologie ist rasant'),
(@demo_user_id, 'challenge', 'Herausforderung', '[ˈtʃælɪndʒ]', 'This is a real challenge for us', 'Das ist eine echte Herausforderung für uns'),
(@demo_user_id, 'opportunity', 'Gelegenheit', '[ˌɒpəˈtjuːnəti]', 'This is a great opportunity', 'Das ist eine großartige Gelegenheit'),
(@demo_user_id, 'important', 'wichtig', '[ɪmˈpɔːtənt]', 'Education is very important', 'Bildung ist sehr wichtig'),
(@demo_user_id, 'necessary', 'notwendig', '[ˈnesəsəri]', 'It is necessary to study', 'Es ist notwendig zu lernen');

-- Demo-Statistiken
INSERT IGNORE INTO user_stats (user_id, correct_answers, wrong_answers, study_sessions) VALUES 
(@demo_user_id, 15, 3, 2);

-- Cleanup alte Sessions (kann als Cron-Job ausgeführt werden)
-- DELETE FROM user_sessions WHERE last_active < DATE_SUB(NOW(), INTERVAL 7 DAY);

-- Indexes für bessere Performance
CREATE INDEX IF NOT EXISTS idx_vocabulary_search ON vocabulary(english, german);
CREATE INDEX IF NOT EXISTS idx_sessions_cleanup ON user_sessions(last_active);