-- EngliFy Database Setup Script für InfinityFree MySQL
-- Führe dieses Script in phpMyAdmin auf InfinityFree aus
-- Datenbank: if0_38963104_sprachtrainer

-- Benutzer Tabelle
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    INDEX idx_german (german),
    INDEX idx_vocabulary_search (english, german)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sessions Tabelle
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_session_id (session_id),
    INDEX idx_user_session (user_id),
    INDEX idx_sessions_cleanup (last_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lernsitzungen Tabelle (Optional für detaillierte Statistiken)
CREATE TABLE IF NOT EXISTS study_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    duration_minutes INT DEFAULT 0,
    questions_answered INT DEFAULT 0,
    correct_answers INT DEFAULT 0,
    session_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    session_type VARCHAR(50) DEFAULT 'flashcard', -- 'flashcard' oder 'writing'
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_study (user_id),
    INDEX idx_session_date (session_date),
    INDEX idx_session_type (session_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Demo-Benutzer erstellen
INSERT IGNORE INTO users (username, password, name, email) VALUES 
('demo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo Benutzer', 'demo@englify.com');

-- Demo-Vokabeln für Demo-User
SET @demo_user_id = (SELECT id FROM users WHERE username = 'demo');

-- Demo-Vokabeln einfügen
INSERT IGNORE INTO vocabulary (user_id, english, german, pronunciation, english_example, german_example) 
SELECT @demo_user_id, 'influence', 'beeinflussen', '[ˈɪnfluəns]', 'Social media can influence our opinions', 'Soziale Medien können unsere Meinungen beeinflussen'
WHERE @demo_user_id IS NOT NULL;

INSERT IGNORE INTO vocabulary (user_id, english, german, pronunciation, english_example, german_example) 
SELECT @demo_user_id, 'environment', 'Umwelt', '[ɪnˈvaɪrənmənt]', 'We must protect our environment', 'Wir müssen unsere Umwelt schützen'
WHERE @demo_user_id IS NOT NULL;

INSERT IGNORE INTO vocabulary (user_id, english, german, pronunciation, english_example, german_example) 
SELECT @demo_user_id, 'responsibility', 'Verantwortung', '[rɪˌspɒnsəˈbɪləti]', 'Taking responsibility is important', 'Verantwortung zu übernehmen ist wichtig'
WHERE @demo_user_id IS NOT NULL;

INSERT IGNORE INTO vocabulary (user_id, english, german, pronunciation, english_example, german_example) 
SELECT @demo_user_id, 'development', 'Entwicklung', '[dɪˈveləpmənt]', 'The development of technology is rapid', 'Die Entwicklung der Technologie ist rasant'
WHERE @demo_user_id IS NOT NULL;

INSERT IGNORE INTO vocabulary (user_id, english, german, pronunciation, english_example, german_example) 
SELECT @demo_user_id, 'challenge', 'Herausforderung', '[ˈtʃælɪndʒ]', 'This is a real challenge for us', 'Das ist eine echte Herausforderung für uns'
WHERE @demo_user_id IS NOT NULL;

INSERT IGNORE INTO vocabulary (user_id, english, german, pronunciation, english_example, german_example) 
SELECT @demo_user_id, 'opportunity', 'Gelegenheit', '[ˌɒpəˈtjuːnəti]', 'This is a great opportunity', 'Das ist eine großartige Gelegenheit'
WHERE @demo_user_id IS NOT NULL;

INSERT IGNORE INTO vocabulary (user_id, english, german, pronunciation, english_example, german_example) 
SELECT @demo_user_id, 'important', 'wichtig', '[ɪmˈpɔːtənt]', 'Education is very important', 'Bildung ist sehr wichtig'
WHERE @demo_user_id IS NOT NULL;

INSERT IGNORE INTO vocabulary (user_id, english, german, pronunciation, english_example, german_example) 
SELECT @demo_user_id, 'necessary', 'notwendig', '[ˈnesəsəri]', 'It is necessary to study', 'Es ist notwendig zu lernen'
WHERE @demo_user_id IS NOT NULL;

INSERT IGNORE INTO vocabulary (user_id, english, german, pronunciation, english_example, german_example) 
SELECT @demo_user_id, 'knowledge', 'Wissen', '[ˈnɒlɪdʒ]', 'Knowledge is power', 'Wissen ist Macht'
WHERE @demo_user_id IS NOT NULL;

INSERT IGNORE INTO vocabulary (user_id, english, german, pronunciation, english_example, german_example) 
SELECT @demo_user_id, 'experience', 'Erfahrung', '[ɪkˈspɪərɪəns]', 'Experience is the best teacher', 'Erfahrung ist der beste Lehrer'
WHERE @demo_user_id IS NOT NULL;

INSERT IGNORE INTO vocabulary (user_id, english, german, pronunciation, english_example, german_example) 
SELECT @demo_user_id, 'solution', 'Lösung', '[səˈluːʃən]', 'We need to find a solution', 'Wir müssen eine Lösung finden'
WHERE @demo_user_id IS NOT NULL;

INSERT IGNORE INTO vocabulary (user_id, english, german, pronunciation, english_example, german_example) 
SELECT @demo_user_id, 'success', 'Erfolg', '[səkˈses]', 'Success requires hard work', 'Erfolg erfordert harte Arbeit'
WHERE @demo_user_id IS NOT NULL;

INSERT IGNORE INTO vocabulary (user_id, english, german, pronunciation, english_example, german_example) 
SELECT @demo_user_id, 'language', 'Sprache', '[ˈlæŋɡwɪdʒ]', 'Learning a new language is exciting', 'Eine neue Sprache zu lernen ist aufregend'
WHERE @demo_user_id IS NOT NULL;

INSERT IGNORE INTO vocabulary (user_id, english, german, pronunciation, english_example, german_example) 
SELECT @demo_user_id, 'practice', 'üben', '[ˈpræktɪs]', 'Practice makes perfect', 'Übung macht den Meister'
WHERE @demo_user_id IS NOT NULL;

INSERT IGNORE INTO vocabulary (user_id, english, german, pronunciation, english_example, german_example) 
SELECT @demo_user_id, 'understand', 'verstehen', '[ˌʌndərˈstænd]', 'I understand the problem', 'Ich verstehe das Problem'
WHERE @demo_user_id IS NOT NULL;

-- Demo-Statistiken
INSERT IGNORE INTO user_stats (user_id, correct_answers, wrong_answers, study_sessions) 
SELECT @demo_user_id, 25, 5, 3
WHERE @demo_user_id IS NOT NULL;

-- Zusätzliche Indexes für bessere Performance
CREATE INDEX IF NOT EXISTS idx_vocab_created ON vocabulary(created_at);
CREATE INDEX IF NOT EXISTS idx_stats_last_study ON user_stats(last_study_date);

-- Erfolgreiche Installation bestätigen
SELECT 
    'EngliFy Datenbank erfolgreich auf InfinityFree installiert!' as Status,
    COUNT(*) as 'Anzahl Demo-Vokabeln'
FROM vocabulary 
WHERE user_id = @demo_user_id;