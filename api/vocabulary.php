<?php
/**
 * EngliFy Vocabulary API
 * Handles CRUD operations for vocabulary
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
            case 'get_all':
                handleGetAllVocabulary($db, $userId);
                break;
                
            case 'get_by_id':
                $id = $_GET['id'] ?? '';
                handleGetVocabularyById($db, $userId, $id);
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
            case 'add':
                handleAddVocabulary($db, $userId, $input);
                break;
                
            case 'update':
                handleUpdateVocabulary($db, $userId, $input);
                break;
                
            case 'delete':
                handleDeleteVocabulary($db, $userId, $input);
                break;
                
            case 'clear_all':
                handleClearAllVocabulary($db, $userId);
                break;
                
            default:
                sendError('Invalid action');
        }
    }
    
} catch (Exception $e) {
    error_log("Vocabulary API Error: " . $e->getMessage());
    sendError('Server error occurred');
}

/**
 * Alle Vokabeln des Benutzers abrufen
 */
function handleGetAllVocabulary($db, $userId) {
    try {
        $vocabulary = $db->getUserVocabulary($userId);
        
        sendSuccess([
            'vocabulary' => $vocabulary,
            'count' => count($vocabulary)
        ]);
        
    } catch (Exception $e) {
        error_log("Get vocabulary error: " . $e->getMessage());
        sendError('Fehler beim Laden der Vokabeln');
    }
}

/**
 * Einzelne Vokabel abrufen
 */
function handleGetVocabularyById($db, $userId, $vocabularyId) {
    if (empty($vocabularyId) || !is_numeric($vocabularyId)) {
        sendError('Ungültige Vokabel-ID');
    }
    
    try {
        $pdo = $db->getConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM vocabulary 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$vocabularyId, $userId]);
        $vocabulary = $stmt->fetch();
        
        if ($vocabulary) {
            sendSuccess(['vocabulary' => $vocabulary]);
        } else {
            sendError('Vokabel nicht gefunden', 404);
        }
        
    } catch (Exception $e) {
        error_log("Get vocabulary by ID error: " . $e->getMessage());
        sendError('Fehler beim Laden der Vokabel');
    }
}

/**
 * Neue Vokabel hinzufügen
 */
function handleAddVocabulary($db, $userId, $input) {
    // Eingabe validieren
    $english = trim($input['english'] ?? '');
    $german = trim($input['german'] ?? '');
    $pronunciation = trim($input['pronunciation'] ?? '') ?: null;
    $englishExample = trim($input['english_example'] ?? '') ?: null;
    $germanExample = trim($input['german_example'] ?? '') ?: null;
    
    if (empty($english) || empty($german)) {
        sendError('Englisches Wort und deutsche Übersetzung sind erforderlich');
    }
    
    // Länge validieren
    if (strlen($english) > 255 || strlen($german) > 255) {
        sendError('Wörter dürfen maximal 255 Zeichen lang sein');
    }
    
    if ($pronunciation && strlen($pronunciation) > 255) {
        sendError('Aussprache darf maximal 255 Zeichen lang sein');
    }
    
    try {
        $pdo = $db->getConnection();
        
        // Prüfen ob Vokabel bereits existiert
        $stmt = $pdo->prepare("
            SELECT id FROM vocabulary 
            WHERE user_id = ? AND (english = ? OR german = ?)
        ");
        $stmt->execute([$userId, $english, $german]);
        
        if ($stmt->fetch()) {
            sendError('Diese Vokabel existiert bereits');
        }
        
        // Vokabel hinzufügen
        if ($db->addVocabulary($userId, $english, $german, $pronunciation, $englishExample, $germanExample)) {
            $vocabularyId = $pdo->lastInsertId();
            
            // Neue Vokabel zurückgeben
            $stmt = $pdo->prepare("SELECT * FROM vocabulary WHERE id = ?");
            $stmt->execute([$vocabularyId]);
            $newVocabulary = $stmt->fetch();
            
            error_log("📝 Neue Vokabel '{$english}' für User {$userId} hinzugefügt");
            
            sendSuccess([
                'vocabulary' => $newVocabulary,
                'message' => 'Vokabel erfolgreich hinzugefügt'
            ]);
        } else {
            sendError('Fehler beim Speichern der Vokabel');
        }
        
    } catch (Exception $e) {
        error_log("Add vocabulary error: " . $e->getMessage());
        sendError('Fehler beim Hinzufügen der Vokabel');
    }
}

/**
 * Vokabel aktualisieren
 */
function handleUpdateVocabulary($db, $userId, $input) {
    $vocabularyId = $input['id'] ?? '';
    $english = trim($input['english'] ?? '');
    $german = trim($input['german'] ?? '');
    $pronunciation = trim($input['pronunciation'] ?? '') ?: null;
    $englishExample = trim($input['english_example'] ?? '') ?: null;
    $germanExample = trim($input['german_example'] ?? '') ?: null;
    
    if (empty($vocabularyId) || !is_numeric($vocabularyId)) {
        sendError('Ungültige Vokabel-ID');
    }
    
    if (empty($english) || empty($german)) {
        sendError('Englisches Wort und deutsche Übersetzung sind erforderlich');
    }
    
    try {
        $pdo = $db->getConnection();
        
        // Prüfen ob Vokabel dem Benutzer gehört
        $stmt = $pdo->prepare("SELECT id FROM vocabulary WHERE id = ? AND user_id = ?");
        $stmt->execute([$vocabularyId, $userId]);
        
        if (!$stmt->fetch()) {
            sendError('Vokabel nicht gefunden', 404);
        }
        
        // Vokabel aktualisieren
        $stmt = $pdo->prepare("
            UPDATE vocabulary 
            SET english = ?, german = ?, pronunciation = ?, 
                english_example = ?, german_example = ?
            WHERE id = ? AND user_id = ?
        ");
        
        if ($stmt->execute([$english, $german, $pronunciation, $englishExample, $germanExample, $vocabularyId, $userId])) {
            error_log("📝 Vokabel ID {$vocabularyId} für User {$userId} aktualisiert");
            
            sendSuccess(['message' => 'Vokabel erfolgreich aktualisiert']);
        } else {
            sendError('Fehler beim Aktualisieren der Vokabel');
        }
        
    } catch (Exception $e) {
        error_log("Update vocabulary error: " . $e->getMessage());
        sendError('Fehler beim Aktualisieren der Vokabel');
    }
}

/**
 * Vokabel löschen
 */
function handleDeleteVocabulary($db, $userId, $input) {
    $vocabularyId = $input['id'] ?? '';
    
    if (empty($vocabularyId) || !is_numeric($vocabularyId)) {
        sendError('Ungültige Vokabel-ID');
    }
    
    try {
        if ($db->deleteVocabulary($vocabularyId, $userId)) {
            error_log("🗑️ Vokabel ID {$vocabularyId} für User {$userId} gelöscht");
            sendSuccess(['message' => 'Vokabel erfolgreich gelöscht']);
        } else {
            sendError('Vokabel nicht gefunden oder bereits gelöscht', 404);
        }
        
    } catch (Exception $e) {
        error_log("Delete vocabulary error: " . $e->getMessage());
        sendError('Fehler beim Löschen der Vokabel');
    }
}

/**
 * Alle Vokabeln des Benutzers löschen
 */
function handleClearAllVocabulary($db, $userId) {
    try {
        if ($db->clearUserVocabulary($userId)) {
            error_log("🧹 Alle Vokabeln für User {$userId} gelöscht");
            sendSuccess(['message' => 'Alle Vokabeln erfolgreich gelöscht']);
        } else {
            sendError('Fehler beim Löschen der Vokabeln');
        }
        
    } catch (Exception $e) {
        error_log("Clear all vocabulary error: " . $e->getMessage());
        sendError('Fehler beim Löschen aller Vokabeln');
    }
}

// Hilfsfunktionen sind bereits in database.php definiert
?>