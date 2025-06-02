# 🇬🇧 EngliFy - Englisch Vokabeltrainer

Ein moderner, webbasierter Vokabeltrainer mit **PHP Backend**, **AJAX Frontend** und **echter Datenbank**!

## 🚀 Features

- ✅ **Vollständiges Login-System** mit Registrierung
- 💾 **Echte Datenbank** (SQLite oder MySQL)
- 📚 **Benutzerspezifische Vokabeln** 
- 🔄 **AJAX-basierte API** ohne Seitenreload
- 📊 **Detaillierte Statistiken & Fortschritt**
- 🎯 **Interaktive Karteikarten** mit Flip-Animation
- 📱 **Responsive Design** für alle Geräte
- 🔐 **Session-Management & Sicherheit**

## 📁 Dateistruktur

```
📁 EngliFy/
├── 📄 index.html              # Frontend (HTML/CSS/JS + AJAX)
├── 📄 .htaccess               # Apache Konfiguration
├── 📄 README.md               # Diese Anleitung
├── 📄 setup.sql               # MySQL Setup (optional)
├── 📁 api/
│   ├── 📄 database.php        # Datenbank-Klasse
│   ├── 📄 auth.php            # Login/Register API
│   ├── 📄 vocabulary.php      # Vokabel CRUD API
│   └── 📄 stats.php           # Statistik API
└── 📄 englify.db              # SQLite Datenbank (wird automatisch erstellt)
```

## 🛠️ Installation

### Voraussetzungen
- **Webserver** (Apache/Nginx) mit PHP 7.4+
- **PHP Extensions:** PDO, PDO_SQLite (oder PDO_MySQL)
- **Optional:** MySQL/MariaDB (falls SQLite nicht gewünscht)

### Schritt 1: Dateien hochladen
```bash
# Alle Dateien in dein Webserver-Verzeichnis kopieren
/var/www/html/englify/    # Oder dein Webroot
```

### Schritt 2: Berechtigungen setzen
```bash
chmod 755 api/
chmod 644 api/*.php
chmod 666 .                # Für SQLite Datei-Erstellung
```

### Schritt 3: Webserver konfigurieren

**Apache (.htaccess bereits vorhanden):**
```apache
# Stelle sicher, dass mod_rewrite aktiviert ist
sudo a2enmod rewrite
sudo systemctl restart apache2
```

**Nginx (nginx.conf hinzufügen):**
```nginx
location /api/ {
    try_files $uri $uri/ @api;
}

location @api {
    rewrite ^/api/(.*)$ /api/$1.php last;
}
```

### Schritt 4: Datenbank wählen

**Option A: SQLite (Empfohlen - Einfach)**
- ✅ **Automatisch:** Datenbank wird beim ersten Aufruf erstellt
- ✅ **Keine Konfiguration** nötig
- ✅ **Portabel:** Eine Datei = komplette DB

**Option B: MySQL**
```bash
# 1. MySQL Datenbank erstellen
mysql -u root -p < setup.sql

# 2. In database.php MySQL-Verbindung konfigurieren:
# $this->pdo = new PDO("mysql:host=localhost;dbname=englify_db", $user, $pass);
```

## 🎯 Verwendung

### 1. Website öffnen
```
http://localhost/englify/
# oder
http://deine-domain.com/
```

### 2. Demo-Zugang
- **Benutzername:** `demo`
- **Passwort:** `demo123`

### 3. Neuen Account erstellen
- Klicke auf "Registrieren"
- Fülle alle Felder aus
- Nach Registrierung automatisches Login

### 4. Vokabeln hinzufügen
- Gehe zu "Vokabeltrainer" → "Hinzufügen"
- Englisches Wort + Deutsche Übersetzung eingeben
- Optional: Aussprache und Beispielsätze

### 5. Lernen
- "Vokabeltrainer" → "Lernen"
- Richtung wählen (DE→EN oder EN→DE)
- Karte anklicken zum Umdrehen
- "Richtig" oder "Falsch" wählen

## 🔧 API Dokumentation

### Authentication API (`/api/auth.php`)

**Login:**
```javascript
POST /api/auth.php
{
    "action": "login",
    "username": "demo",
    "password": "demo123"
}
```

**Register:**
```javascript
POST /api/auth.php
{
    "action": "register",
    "name": "Max Mustermann",
    "username": "max",
    "email": "max@example.com",
    "password": "sicherespasswort"
}
```

**Check Session:**
```javascript
GET /api/auth.php?action=check_session
```

### Vocabulary API (`/api/vocabulary.php`)

**Alle Vokabeln laden:**
```javascript
GET /api/vocabulary.php?action=get_all
```

**Vokabel hinzufügen:**
```javascript
POST /api/vocabulary.php
{
    "action": "add",
    "english": "environment",
    "german": "Umwelt",
    "pronunciation": "[ɪnˈvaɪrənmənt]",
    "english_example": "We protect the environment",
    "german_example": "Wir schützen die Umwelt"
}
```

**Vokabel löschen:**
```javascript
POST /api/vocabulary.php
{
    "action": "delete",
    "id": 123
}
```

### Statistics API (`/api/stats.php`)

**Statistiken laden:**
```javascript
GET /api/stats.php?action=get_stats
```

**Statistiken aktualisieren:**
```javascript
POST /api/stats.php
{
    "action": "update_stats",
    "correct_answers": 1,
    "wrong_answers": 0,
    "study_sessions": 0
}
```

## 🐛 Troubleshooting

### Problem: "Datenbankverbindung fehlgeschlagen"
**Lösung:**
```bash
# SQLite Berechtigung prüfen
ls -la englify.db
chmod 666 englify.db
chmod 777 .    # Verzeichnis beschreibbar machen
```

### Problem: "CORS Fehler"
**Lösung:**
- `.htaccess` Datei prüfen
- Apache `mod_headers` aktivieren: `sudo a2enmod headers`

### Problem: "API nicht gefunden"
**Lösung:**
- Apache `mod_rewrite` aktivieren: `sudo a2enmod rewrite`
- Virtual Host `AllowOverride All` setzen

### Problem: "Session Fehler"
**Lösung:**
```bash
# PHP Session-Verzeichnis beschreibbar machen
sudo chmod 777 /var/lib/php/sessions
# oder in php.ini session.save_path anpassen
```

## 📊 Logs & Debugging

**PHP Error Log checken:**
```bash
tail -f /var/log/apache2/error.log
# oder
tail -f /var/log/php_errors.log
```

**Browser Console öffnen:**
- F12 → Console Tab
- Schau nach AJAX-Fehlern

## 🔒 Sicherheit

- ✅ **Passwort-Hashing** mit PHP `password_hash()`
- ✅ **SQL-Injection Schutz** durch Prepared Statements
- ✅ **Session-Management** mit automatischem Cleanup
- ✅ **CORS-Header** richtig konfiguriert
- ✅ **File-Access Protection** via .htaccess

## 🚀 Deployment (Produktiv)

### Shared Hosting
1. Dateien via FTP hochladen
2. SQLite wird automatisch erstellt
3. Domain aufrufen → Fertig!

### VPS/Dedicated Server
```bash
# 1. Repository clonen
git clone <dein-repo> /var/www/html/englify

# 2. Apache Virtual Host
sudo nano /etc/apache2/sites-available/englify.conf

# 3. SSL-Zertifikat (Let's Encrypt)
sudo certbot --apache -d deine-domain.com

# 4. Firewall
sudo ufw allow 80
sudo ufw allow 443
```

## 🎓 Für dein Schulprojekt

**Präsentations-Punkte:**
- 📚 **Full-Stack Entwicklung:** Frontend + Backend + Database
- 🔄 **AJAX/API-Architektur:** Moderne Webtechnologie 
- 💾 **Datenbank-Design:** Normalisierte Tabellen, Relationen
- 🔐 **Security:** Hashing, Sessions, SQL-Injection-Schutz
- 📱 **Responsive Design:** Mobile-First Ansatz
- 🛠️ **PHP OOP:** Klassen, Exception Handling, PDO

**Code-Qualität:**
- ✅ Saubere Trennung Frontend/Backend
- ✅ Ausführliche Kommentare
- ✅ Error Handling & Logging
- ✅ Konsistente Code-Struktur

## 📝 Lizenz

Dieses Projekt ist für Bildungszwecke erstellt und frei verwendbar.

---

**Erstellt mit ❤️ für dein Schulprojekt!** 🎓

Bei Fragen: Schau in die Browser-Console und PHP-Logs! 🐛