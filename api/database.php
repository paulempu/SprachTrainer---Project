<?php
/**
 * EngliFy Database Connection & Setup
 * MySQL Database für InfinityFree
 * 
 * @author EngliFy Team
 * @version 2.0
 */

class Database {
    private static $instance = null;
    private $pdo;
    
    // InfinityFree Datenbank-Konfiguration
    private $host = 'sql103.infinityfree.com';
    private $dbname = 'if0_38963104_sprachtrainer';
    private $username = 'if0_38963104';
    private $password = 'Gritscher';
    
    private function __construct() {
        try {
            // MySQL Datenbank verbinden
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
            $this->pdo = new PDO($dsn, $this->username, $this->password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            error_log("📚 EngliFy MySQL Datenbank erfolgreich verbunden!");
            
        } catch (PDOException $e) {
            error_log("❌ MySQL Datenbank Fehler: " . $e->getMessage());
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
    
    public function cleanupOldSessions($maxAgeDays = 7) {
        $stmt = $this->pdo->prepare("
            DELETE FROM user_sessions 
            WHERE last_active < DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        return $stmt->execute([$maxAgeDays]);
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
        // Prüfen ob Statistiken existieren
        $stats = $this->getUserStats($userId);
        
        // Statistiken aktualisieren
        $stmt = $this->pdo->prepare("
            UPDATE user_stats 
            SET correct_answers = correct_answers + ?,
                wrong_answers = wrong_answers + ?,
                study_sessions = study_sessions + ?,
                last_study_date = CURRENT_TIMESTAMP
            WHERE user_id = ?
        ");
        return $stmt->execute([$correctAnswers, $wrongAnswers, $studySessions, $userId]);
    }
    
    public function resetUserStats($userId) {
        $stmt = $this->pdo->prepare("
            UPDATE user_stats 
            SET correct_answers = 0, wrong_answers = 0, study_sessions = 0,
                last_study_date = NULL
            WHERE user_id = ?
        ");
        return $stmt->execute([$userId]);
    }
    
    // Lernsitzungen aufzeichnen
    public function recordStudySession($userId, $duration, $questionsAnswered, $correctAnswers, $sessionType = 'flashcard') {
        $stmt = $this->pdo->prepare("
            INSERT INTO study_sessions (user_id, duration_minutes, questions_answered, correct_answers, session_type)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$userId, $duration, $questionsAnswered, $correctAnswers, $sessionType]);
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
    
    // Test-Verbindung
    public function testConnection() {
        try {
            $stmt = $this->pdo->query("SELECT 1");
            return true;
        } catch (Exception $e) {
            return false;
        }
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