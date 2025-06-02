<?php
/**
 * EngliFy Database Connection & Setup
 * SQLite Database für einfache Verwendung ohne MySQL Server
 * 
 * @author EngliFy Team
 * @version 1.0
 */

class Database {
    private static $instance = null;
    private $pdo;
    private $dbFile = 'englify.db';
    
    private function __construct() {
        try {
            // SQLite Datenbank erstellen/verbinden
            $this->pdo = new PDO("sqlite:" . $this->dbFile);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Tabellen erstellen falls nicht vorhanden
            $this->createTables();
            $this->createDemoData();
            
            error_log("📚 EngliFy Datenbank erfolgreich verbunden!");
            
        } catch (PDOException $e) {
            error_log("❌ Datenbank Fehler: " . $e->getMessage());
            throw new Exception("Datenbankverbindung fehlgeschlagen");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    private function createTables() {
        $queries = [
            // Benutzer Tabelle
            "CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(50) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            
            // Vokabeln Tabelle
            "CREATE TABLE IF NOT EXISTS vocabulary (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                english VARCHAR(255) NOT NULL,
                german VARCHAR(255) NOT NULL,
                pronunciation VARCHAR(255),
                english_example TEXT,
                german_example TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )",
            
            // Benutzer Statistiken
            "CREATE TABLE IF NOT EXISTS user_stats (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER UNIQUE NOT NULL,
                correct_answers INTEGER DEFAULT 0,
                wrong_answers INTEGER DEFAULT 0,
                study_sessions INTEGER DEFAULT 0,
                last_study_date DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )",
            
            // Sessions Tabelle
            "CREATE TABLE IF NOT EXISTS user_sessions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                session_id VARCHAR(255) UNIQUE NOT NULL,
                user_id INTEGER NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_active DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )"
        ];
        
        foreach ($queries as $query) {
            $this->pdo->exec($query);
        }
        
        error_log("📋 Datenbank-Tabellen erstellt/überprüft");
    }
    
    private function createDemoData() {
        // Prüfen ob Demo-User bereits existiert
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute(['demo']);
        
        if ($stmt->rowCount() == 0) {
            try {
                $this->pdo->beginTransaction();
                
                // Demo-Benutzer erstellen
                $stmt = $this->pdo->prepare("
                    INSERT INTO users (username, password, name, email) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    'demo',
                    password_hash('demo123', PASSWORD_DEFAULT),
                    'Demo Benutzer',
                    'demo@englify.com'
                ]);
                
                $userId = $this->pdo->lastInsertId();
                
                // Demo-Vokabeln erstellen
                $demoVocabulary = [
                    ['influence', 'beeinflussen', '[ˈɪnfluəns]', 'Social media can influence our opinions', 'Soziale Medien können unsere Meinungen beeinflussen'],
                    ['environment', 'Umwelt', '[ɪnˈvaɪrənmənt]', 'We must protect our environment', 'Wir müssen unsere Umwelt schützen'],
                    ['responsibility', 'Verantwortung', '[rɪˌspɒnsəˈbɪləti]', 'Taking responsibility is important', 'Verantwortung zu übernehmen ist wichtig'],
                    ['development', 'Entwicklung', '[dɪˈveləpmənt]', 'The development of technology is rapid', 'Die Entwicklung der Technologie ist rasant'],
                    ['challenge', 'Herausforderung', '[ˈtʃælɪndʒ]', 'This is a real challenge for us', 'Das ist eine echte Herausforderung für uns'],
                    ['opportunity', 'Gelegenheit', '[ˌɒpəˈtjuːnəti]', 'This is a great opportunity', 'Das ist eine großartige Gelegenheit'],
                    ['important', 'wichtig', '[ɪmˈpɔːtənt]', 'Education is very important', 'Bildung ist sehr wichtig'],
                    ['necessary', 'notwendig', '[ˈnesəsəri]', 'It is necessary to study', 'Es ist notwendig zu lernen']
                ];
                
                $stmt = $this->pdo->prepare("
                    INSERT INTO vocabulary (user_id, english, german, pronunciation, english_example, german_example) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                foreach ($demoVocabulary as $vocab) {
                    $stmt->execute(array_merge([$userId], $vocab));
                }
                
                // Demo-Statistiken erstellen
                $stmt = $this->pdo->prepare("
                    INSERT INTO user_stats (user_id, correct_answers, wrong_answers, study_sessions) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$userId, 15, 3, 2]);
                
                $this->pdo->commit();
                error_log("👤 Demo-Benutzer mit Beispieldaten erstellt");
                
            } catch (Exception $e) {
                $this->pdo->rollBack();
                error_log("❌ Fehler beim Erstellen der Demo-Daten: " . $e->getMessage());
            }
        }
    }
    
    // Helper-Methoden
    public function getUserByUsername($username) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }
    
    public function getUserById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function createUser($username, $password, $name, $email) {
        $stmt = $this->pdo->prepare("
            INSERT INTO users (username, password, name, email) 
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([
            $username,
            password_hash($password, PASSWORD_DEFAULT),
            $name,
            $email
        ]);
    }
    
    public function createSession($sessionId, $userId) {
        // Alte Sessions des Benutzers löschen
        $stmt = $this->pdo->prepare("DELETE FROM user_sessions WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        // Neue Session erstellen
        $stmt = $this->pdo->prepare("
            INSERT INTO user_sessions (session_id, user_id) 
            VALUES (?, ?)
        ");
        return $stmt->execute([$sessionId, $userId]);
    }
    
    public function getSessionUser($sessionId) {
        $stmt = $this->pdo->prepare("
            SELECT u.* FROM users u 
            JOIN user_sessions s ON u.id = s.user_id 
            WHERE s.session_id = ?
        ");
        $stmt->execute([$sessionId]);
        return $stmt->fetch();
    }
    
    public function updateSessionActivity($sessionId) {
        $stmt = $this->pdo->prepare("
            UPDATE user_sessions 
            SET last_active = CURRENT_TIMESTAMP 
            WHERE session_id = ?
        ");
        return $stmt->execute([$sessionId]);
    }
    
    public function deleteSession($sessionId) {
        $stmt = $this->pdo->prepare("DELETE FROM user_sessions WHERE session_id = ?");
        return $stmt->execute([$sessionId]);
    }
    
    public function cleanupOldSessions($maxAge = '7 days') {
        $stmt = $this->pdo->prepare("
            DELETE FROM user_sessions 
            WHERE last_active < datetime('now', '-' || ? || ' days')
        ");
        return $stmt->execute([7]);
    }
    
    // Vokabel-Methoden
    public function getUserVocabulary($userId) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM vocabulary 
            WHERE user_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    public function addVocabulary($userId, $english, $german, $pronunciation = null, $englishExample = null, $germanExample = null) {
        $stmt = $this->pdo->prepare("
            INSERT INTO vocabulary (user_id, english, german, pronunciation, english_example, german_example) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$userId, $english, $german, $pronunciation, $englishExample, $germanExample]);
    }
    
    public function deleteVocabulary($vocabularyId, $userId) {
        $stmt = $this->pdo->prepare("
            DELETE FROM vocabulary 
            WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([$vocabularyId, $userId]);
    }
    
    public function clearUserVocabulary($userId) {
        $stmt = $this->pdo->prepare("DELETE FROM vocabulary WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }
    
    // Statistik-Methoden
    public function getUserStats($userId) {
        $stmt = $this->pdo->prepare("SELECT * FROM user_stats WHERE user_id = ?");
        $stmt->execute([$userId]);
        $stats = $stmt->fetch();
        
        if (!$stats) {
            // Statistiken erstellen falls nicht vorhanden
            $stmt = $this->pdo->prepare("
                INSERT INTO user_stats (user_id, correct_answers, wrong_answers, study_sessions) 
                VALUES (?, 0, 0, 0)
            ");
            $stmt->execute([$userId]);
            return $this->getUserStats($userId);
        }
        
        return $stats;
    }
    
    public function updateUserStats($userId, $correctAnswers = 0, $wrongAnswers = 0, $studySessions = 0) {
        $stmt = $this->pdo->prepare("
            INSERT OR REPLACE INTO user_stats 
            (user_id, correct_answers, wrong_answers, study_sessions, last_study_date) 
            VALUES (
                ?, 
                COALESCE((SELECT correct_answers FROM user_stats WHERE user_id = ?), 0) + ?,
                COALESCE((SELECT wrong_answers FROM user_stats WHERE user_id = ?), 0) + ?,
                COALESCE((SELECT study_sessions FROM user_stats WHERE user_id = ?), 0) + ?,
                CURRENT_TIMESTAMP
            )
        ");
        return $stmt->execute([$userId, $userId, $correctAnswers, $userId, $wrongAnswers, $userId, $studySessions]);
    }
    
    public function resetUserStats($userId) {
        $stmt = $this->pdo->prepare("
            UPDATE user_stats 
            SET correct_answers = 0, wrong_answers = 0, study_sessions = 0 
            WHERE user_id = ?
        ");
        return $stmt->execute([$userId]);
    }
    
    // Debug/Admin Methoden
    public function getDbStats() {
        $stats = [];
        
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM users");
        $stats['total_users'] = $stmt->fetch()['count'];
        
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM vocabulary");
        $stats['total_vocabulary'] = $stmt->fetch()['count'];
        
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM user_sessions");
        $stats['active_sessions'] = $stmt->fetch()['count'];
        
        return $stats;
    }
}

// Helper-Funktion für JSON-Responses
function sendJsonResponse($data, $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Helper-Funktion für Fehler-Responses
function sendError($message, $httpCode = 400) {
    sendJsonResponse(['success' => false, 'error' => $message], $httpCode);
}

// Helper-Funktion für Erfolg-Responses
function sendSuccess($data = []) {
    sendJsonResponse(array_merge(['success' => true], $data));
}

// Session Helper
function getSessionId() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    return session_id();
}
?>