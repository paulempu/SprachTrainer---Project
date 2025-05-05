<?php
session_start();

if (!isset($_POST['username']) || !isset($_POST['password'])) {
    $_SESSION['err'] = "Login: Benutzername oder Passwort fehlt";
    header("Location: error2.php");
    exit();
}

$user = trim($_POST['username']);
$pass = trim($_POST['password']);

if (empty($user) || empty($pass)) {
    $_SESSION['err'] = "Login: Benutzername oder Passwort ist leer";
    header("Location: error2.php");
    exit();
}

$servername = "localhost";
$dbuser = "root";
$dbpass = "";
$dbname = "benuter-sprachtrainer";

// Verbindung herstellen
$conn = new mysqli($servername, $dbuser, $dbpass, $dbname);
if ($conn->connect_error) {
    $_SESSION['err'] = $conn->connect_error;
    header("Location: error2.php");
    exit();
}

// Benutzer abfragen
$sql = "SELECT username, password FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user);
$stmt->execute();
$result = $stmt->get_result();

if ($stmt->error) {
    $_SESSION['err'] = $stmt->error;
    header("Location: error2.php");
    $conn->close();
    exit();
}

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    // Passwort prüfen (angenommen, es wurde mit password_hash gespeichert)
    if (password_verify($pass, $row['password'])) {
        $_SESSION['username'] = $row['username'];
        header("Location: success2.php");
    } else {
        $_SESSION['err'] = "Login fehlgeschlagen (falsches Passwort)";
        header("Location: formular2.php");
    }
} else {
    $_SESSION['err'] = "Benutzername nicht gefunden";
    header("Location: formular2.php");
}

$stmt->close();
$conn->close();
?>
