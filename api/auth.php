<?php
/**
 * EngliFy Authentication API
 * Handles Login, Registration, and Session Management
 * Optimiert für InfinityFree MySQL
 * 
 * @author EngliFy Team
 * @version 2.0
 */

// CORS Headers für Frontend-Zugriff
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Error Reporting für Debugging (nur für Development)
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

require_once 'database.php';

// Session starten
session_start();

try {
    $db = Database::getInstance();
    
    // GET Requests
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'check_session':
                handleCheckSession($db);
                break;
                
            default:
                sendError('Invalid action');
        }
    }
    
    // POST Requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if ($input === null) {
            sendError('Invalid JSON input');
        }
        
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'login':
                handleLogin($db, $input);
                break;
                
            case 'register':
                handleRegister($db, $input);
                break;
                
            case 'logout':
                handleLogout($db);
                break;
                
            default:
                sendError('Invalid action');
        }
    }
    
} catch (Exception $e) {
    error_log("Auth API Error: " . $e->getMessage());
    sendError('Server error occurred');
}

/**
 * Überprüft die aktuelle Session
 */
function handleCheckSession($db) {
    $sessionId = session_id();
    
    if (empty($sessionId)) {
        sendError('No session');
    }
    
    try {
        $user = $db->getSessionUser($sessionId);
        
        if ($user) {
            // Session-Aktivität aktualisieren
            $db->updateSessionActivity($sessionId);
            
            sendSuccess([
                'user' => [
                    'id' => (int)$user['id'],
                    'username' => $user['username'],
                    'name' => $user['name'],
                    'email' => $user['email']
                ]
            ]);
        } else {
            sendError('Invalid session');
        }
    } catch (Exception $e) {
        error_log("Check session error: " . $e->getMessage());
        sendError('Session check failed');
    }
}

/**
 * Benutzer-Login
 */
function handleLogin($db, $input) {
    // Eingabe validieren
    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        sendError('Benutzername und Passwort sind erforderlich');
    }
    
    if (strlen($username) > 50) {
        sendError('Benutzername zu lang');
    }
    
    try {
        // Benutzer suchen
        $user = $db->getUserByUsername($username);
        
        if (!$user) {
            sendError('Ungültige Anmeldedaten');
        }
        
        // Passwort überprüfen
        if (!password_verify($password, $user['password'])) {
            sendError('Ungültige Anmeldedaten');
        }
        
        // Session erstellen
        $sessionId = session_id();
        if (empty($sessionId)) {
            session_start();
            $sessionId = session_id();
        }
        
        if ($db->createSession($sessionId, $user['id'])) {
            error_log("✅ Benutzer {$username} erfolgreich angemeldet (InfinityFree)");
            
            sendSuccess([
                'user' => [
                    'id' => (int)$user['id'],
                    'username' => $user['username'],
                    'name' => $user['name'],
                    'email' => $user['email']
                ]
            ]);
        } else {
            sendError('Session konnte nicht erstellt werden');
        }
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        sendError('Login fehlgeschlagen');
    }
}

/**
 * Benutzer-Registrierung
 */
function handleRegister($db, $input) {
    // Eingabe validieren
    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';
    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    
    if (empty($username) || empty($password) || empty($name) || empty($email)) {
        sendError('Alle Felder sind erforderlich');
    }
    
    // Benutzername validieren
    if (strlen($username) < 3) {
        sendError('Benutzername muss mindestens 3 Zeichen lang sein');
    }
    
    if (strlen($username) > 50) {
        sendError('Benutzername zu lang (max. 50 Zeichen)');
    }
    
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        sendError('Benutzername darf nur Buchstaben, Zahlen und _ enthalten');
    }
    
    // Passwort validieren
    if (strlen($password) < 6) {
        sendError('Passwort muss mindestens 6 Zeichen lang sein');
    }
    
    if (strlen($password) > 128) {
        sendError('Passwort zu lang');
    }
    
    // Name validieren
    if (strlen($name) > 100) {
        sendError('Name zu lang (max. 100 Zeichen)');
    }
    
    // E-Mail validieren
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendError('Ungültige E-Mail-Adresse');
    }
    
    if (strlen($email) > 100) {
        sendError('E-Mail zu lang (max. 100 Zeichen)');
    }
    
    try {
        // Prüfen ob Benutzername bereits existiert
        if ($db->getUserByUsername($username)) {
            sendError('Benutzername bereits vergeben');
        }
        
        // Benutzer erstellen
        if ($db->createUser($username, $password, $name, $email)) {
            error_log("✅ Neuer Benutzer {$username} registriert (InfinityFree)");
            
            sendSuccess([
                'message' => 'Registrierung erfolgreich'
            ]);
        } else {
            sendError('Registrierung fehlgeschlagen');
        }
    } catch (Exception $e) {
        error_log("Registrierung Error: " . $e->getMessage());
        
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            sendError('Benutzername bereits vergeben');
        } else {
            sendError('Registrierung fehlgeschlagen');
        }
    }
}

/**
 * Benutzer-Logout
 */
function handleLogout($db) {
    $sessionId = session_id();
    
    if (!empty($sessionId)) {
        try {
            $db->deleteSession($sessionId);
        } catch (Exception $e) {
            error_log("Logout error: " . $e->getMessage());
        }
        
        // PHP Session zerstören
        session_unset();
        session_destroy();
        
        error_log("👋 Benutzer erfolgreich abgemeldet (InfinityFree)");
    }
    
    sendSuccess(['message' => 'Erfolgreich abgemeldet']);
}

// Hilfsfunktionen sind bereits in database.php definiert
?>