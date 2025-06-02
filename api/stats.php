<?php
/**
 * EngliFy Statistics API
 * Handles user statistics and learning progress
 * 
 * @author EngliFy Team
 * @version 1.0
 */

// CORS Headers für Frontend-Zugriff
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once 'database.php';

// Session starten
session_start();

try {
    $db = Database::getInstance();
    
    // Benutzer-Authentifizierung prüfen
    $sessionId = session_id();
    $user = $db->getSessionUser($sessionId);
    
    if (!$user) {
        sendError('Nicht angemeldet', 401);
    }
    
    $userId = $user['id'];
    
    // GET Requests
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'get_stats':
                handleGetStats($db, $userId);
                break;
                
            case 'get_detailed_stats':
                handleGetDetailedStats($db, $userId);
                break;
                
            default:
                sendError('Invalid action');
        }
    }
    
    // POST Requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'update_stats':
                handleUpdateStats($db, $userId, $input);
                break;
                
            case 'reset_stats':
                handleResetStats($db, $userId);
                break;
                
            case 'record_study_session':
                handleRecordStudySession($db, $userId, $input);
                break;
                
            default:
                sendError('Invalid action');
        }
    }
    
} catch (Exception $e) {
    error_log("Stats API Error: " . $e->getMessage());
    sendError('Server error occurred');
}

/**
 * Benutzer-Statistiken abrufen
 */
function handleGetStats($db, $userId) {
    try {
        $stats = $db->getUserStats($userId);
        
        // Zusätzliche berechnete Statistiken
        $totalAnswers = $stats['correct_answers'] + $stats['wrong_answers'];
        $accuracy = $totalAnswers > 0 ? round(($stats['correct_answers'] / $totalAnswers) * 100, 1) : 0;
        
        // Vokabeln zählen
        $pdo = $db->getConnection();
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM vocabulary WHERE user_id = ?");
        $stmt->execute([$userId]);
        $vocabCount = $stmt->fetch()['count'];
        
        sendSuccess([
            'stats' => [
                'correct_answers' => (int)$stats['correct_answers'],
                'wrong_answers' => (int)$stats['wrong_answers'],
                'study_sessions' => (int)$stats['study_sessions'],
                'total_answers' => $totalAnswers,
                'accuracy' => $accuracy,
                'vocabulary_count' => (int)$vocabCount,
                'last_study_date' => $stats['last_study_date']
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("Get stats error: " . $e->getMessage());
        sendError('Fehler beim Laden der Statistiken');
    }
}

/**
 * Detaillierte Statistiken abrufen
 */
function handleGetDetailedStats($db, $userId) {
    try {
        $pdo = $db->getConnection();
        
        // Basis-Statistiken
        $stats = $db->getUserStats($userId);
        
        // Vokabel-Statistiken
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_vocabulary,
                COUNT(CASE WHEN created_at >= date('now', '-7 days') THEN 1 END) as added_this_week,
                COUNT(CASE WHEN created_at >= date('now', '-30 days') THEN 1 END) as added_this_month
            FROM vocabulary 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $vocabStats = $stmt->fetch();
        
        // Lernverlauf der letzten 30 Tage (vereinfacht)
        $stmt = $pdo->prepare("
            SELECT 
                date(last_study_date) as study_date,
                study_sessions
            FROM user_stats 
            WHERE user_id = ? AND last_study_date >= date('now', '-30 days')
            ORDER BY last_study_date DESC
        ");
        $stmt->execute([$userId]);
        $studyHistory = $stmt->fetchAll();
        
        // Erfolgsrate berechnen
        $totalAnswers = $stats['correct_answers'] + $stats['wrong_answers'];
        $accuracy = $totalAnswers > 0 ? round(($stats['correct_answers'] / $totalAnswers) * 100, 1) : 0;
        
        sendSuccess([
            'detailed_stats' => [
                'basic' => [
                    'correct_answers' => (int)$stats['correct_answers'],
                    'wrong_answers' => (int)$stats['wrong_answers'],
                    'study_sessions' => (int)$stats['study_sessions'],
                    'accuracy' => $accuracy,
                    'last_study_date' => $stats['last_study_date']
                ],
                'vocabulary' => [
                    'total' => (int)$vocabStats['total_vocabulary'],
                    'added_this_week' => (int)$vocabStats['added_this_week'],
                    'added_this_month' => (int)$vocabStats['added_this_month']
                ],
                'study_history' => $studyHistory,
                'achievements' => calculateAchievements($stats, $vocabStats)
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("Get detailed stats error: " . $e->getMessage());
        sendError('Fehler beim Laden der detaillierten Statistiken');
    }
}

/**
 * Statistiken aktualisieren
 */
function handleUpdateStats($db, $userId, $input) {
    $correctAnswers = (int)($input['correct_answers'] ?? 0);
    $wrongAnswers = (int)($input['wrong_answers'] ?? 0);
    $studySessions = (int)($input['study_sessions'] ?? 0);
    
    // Validierung
    if ($correctAnswers < 0 || $wrongAnswers < 0 || $studySessions < 0) {
        sendError('Statistik-Werte müssen positiv sein');
    }
    
    try {
        if ($db->updateUserStats($userId, $correctAnswers, $wrongAnswers, $studySessions)) {
            error_log("📊 Statistiken für User {$userId} aktualisiert: +{$correctAnswers} richtig, +{$wrongAnswers} falsch, +{$studySessions} Sessions");
            
            // Aktualisierte Statistiken zurückgeben
            $stats = $db->getUserStats($userId);
            sendSuccess([
                'message' => 'Statistiken erfolgreich aktualisiert',
                'stats' => [
                    'correct_answers' => (int)$stats['correct_answers'],
                    'wrong_answers' => (int)$stats['wrong_answers'],
                    'study_sessions' => (int)$stats['study_sessions']
                ]
            ]);
        } else {
            sendError('Fehler beim Aktualisieren der Statistiken');
        }
        
    } catch (Exception $e) {
        error_log("Update stats error: " . $e->getMessage());
        sendError('Fehler beim Aktualisieren der Statistiken');
    }
}

/**
 * Statistiken zurücksetzen
 */
function handleResetStats($db, $userId) {
    try {
        if ($db->resetUserStats($userId)) {
            error_log("🔄 Statistiken für User {$userId} zurückgesetzt");
            
            sendSuccess([
                'message' => 'Statistiken erfolgreich zurückgesetzt',
                'stats' => [
                    'correct_answers' => 0,
                    'wrong_answers' => 0,
                    'study_sessions' => 0
                ]
            ]);
        } else {
            sendError('Fehler beim Zurücksetzen der Statistiken');
        }
        
    } catch (Exception $e) {
        error_log("Reset stats error: " . $e->getMessage());
        sendError('Fehler beim Zurücksetzen der Statistiken');
    }
}

/**
 * Lernsitzung aufzeichnen
 */
function handleRecordStudySession($db, $userId, $input) {
    $duration = (int)($input['duration'] ?? 0); // in Minuten
    $questionsAnswered = (int)($input['questions_answered'] ?? 0);
    $correctAnswers = (int)($input['correct_answers'] ?? 0);
    $wrongAnswers = $questionsAnswered - $correctAnswers;
    
    try {
        // Statistiken aktualisieren
        $db->updateUserStats($userId, $correctAnswers, $wrongAnswers, 1);
        
        // Optional: Detaillierte Session-Daten speichern
        $pdo = $db->getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO study_sessions (user_id, duration_minutes, questions_answered, correct_answers, session_date)
            VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        
        // Tabelle erstellen falls nicht vorhanden
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS study_sessions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                duration_minutes INTEGER DEFAULT 0,
                questions_answered INTEGER DEFAULT 0,
                correct_answers INTEGER DEFAULT 0,
                session_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        
        $stmt->execute([$userId, $duration, $questionsAnswered, $correctAnswers]);
        
        error_log("📚 Lernsitzung für User {$userId} aufgezeichnet: {$duration}min, {$questionsAnswered} Fragen, {$correctAnswers} richtig");
        
        sendSuccess([
            'message' => 'Lernsitzung erfolgreich aufgezeichnet',
            'session_id' => $pdo->lastInsertId()
        ]);
        
    } catch (Exception $e) {
        error_log("Record study session error: " . $e->getMessage());
        sendError('Fehler beim Aufzeichnen der Lernsitzung');
    }
}

/**
 * Achievements/Erfolge berechnen
 */
function calculateAchievements($stats, $vocabStats) {
    $achievements = [];
    
    // Vokabel-Achievements
    if ($vocabStats['total_vocabulary'] >= 10) {
        $achievements[] = ['name' => 'Vokabel-Sammler', 'description' => '10 Vokabeln gesammelt', 'icon' => '📚'];
    }
    if ($vocabStats['total_vocabulary'] >= 50) {
        $achievements[] = ['name' => 'Wortschatz-Meister', 'description' => '50 Vokabeln gesammelt', 'icon' => '🎓'];
    }
    if ($vocabStats['total_vocabulary'] >= 100) {
        $achievements[] = ['name' => 'Vokabel-Experte', 'description' => '100 Vokabeln gesammelt', 'icon' => '🏆'];
    }
    
    // Antworten-Achievements
    if ($stats['correct_answers'] >= 50) {
        $achievements[] = ['name' => 'Erste Erfolge', 'description' => '50 richtige Antworten', 'icon' => '✅'];
    }
    if ($stats['correct_answers'] >= 200) {
        $achievements[] = ['name' => 'Lern-Champion', 'description' => '200 richtige Antworten', 'icon' => '🏅'];
    }
    if ($stats['correct_answers'] >= 500) {
        $achievements[] = ['name' => 'Vokabel-Guru', 'description' => '500 richtige Antworten', 'icon' => '🌟'];
    }
    
    // Session-Achievements
    if ($stats['study_sessions'] >= 5) {
        $achievements[] = ['name' => 'Fleißiger Lerner', 'description' => '5 Lernsitzungen', 'icon' => '📖'];
    }
    if ($stats['study_sessions'] >= 20) {
        $achievements[] = ['name' => 'Ausdauer-Held', 'description' => '20 Lernsitzungen', 'icon' => '💪'];
    }
    
    return $achievements;
}

// Hilfsfunktionen sind bereits in database.php definiert
?>