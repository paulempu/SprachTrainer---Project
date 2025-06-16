<?php
/**
 * Englify - Benutzer-Login
 * Eingeloggte Benutzer werden zur Dashboard weitergeleitet
 */

require_once 'includes/session.php';
require_once 'config/database.php';

// Wenn bereits eingeloggt, zur Dashboard weiterleiten
redirectIfLoggedIn();

$error = '';

// Login-Verarbeitung
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login']); // Kann Benutzername oder E-Mail sein
    $password = $_POST['password'];
    
    if (empty($login) || empty($password)) {
        $error = 'Bitte alle Felder ausfüllen.';
    } else {
        try {
            // Benutzer suchen (sowohl per Benutzername als auch E-Mail)
            $stmt = executeQuery(
                "SELECT id, username, password_hash FROM users WHERE username = ? OR email = ?",
                [$login, $login]
            );
            
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Login erfolgreich - Session setzen
                loginUser($user['id'], $user['username']);
                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Ungültige Anmeldedaten.';
            }
        } catch (Exception $e) {
            $error = 'Fehler beim Login: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Englify</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <nav class="nav">
                <a href="index.php" class="logo">📚 Englify</a>
                <ul class="nav-links">
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Registrieren</a></li>
                </ul>
            </nav>
        </header>

        <!-- Login-Formular -->
        <div class="auth-card">
            <div class="card">
                <h2 class="text-center mb-2">Anmelden</h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="login">Benutzername oder E-Mail:</label>
                        <input type="text" id="login" name="login" 
                               value="<?= htmlspecialchars($_POST['login'] ?? '') ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Passwort:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        Einloggen
                    </button>
                </form>
                
                <div class="text-center mt-2">
                    <p>Noch kein Konto? <a href="register.php">Hier registrieren</a></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Demo-Hinweis -->
    <div class="container">
        <div class="card text-center" style="max-width: 400px; margin: 20px auto; background: rgba(255, 255, 255, 0.9);">
            <h3>Demo-Zugang</h3>
            <p>Benutzername: <strong>demo</strong><br>
               Passwort: <strong>password</strong></p>
            <small style="color: #666;">
                (Falls du die Beispiel-Daten installiert hast)
            </small>
        </div>
    </div>
</body>
</html>