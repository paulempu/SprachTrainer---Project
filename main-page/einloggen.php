<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Einloggen - EngliFy.com</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .login-form {
            max-width: 400px;
            margin: 3rem auto;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 16px rgba(58, 95, 153, 0.2);
        }

        .login-form h2 {
            color: #1a356e;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .login-form .form-control:focus {
            border-color: #3a5f99;
            box-shadow: 0 0 0 0.25rem rgba(58, 95, 153, 0.25);
        }

        .login-form .btn-primary {
            background-color: #3a5f99;
            border-color: #3a5f99;
            width: 100%;
            padding: 0.6rem;
            font-weight: 500;
        }

        .login-form .btn-primary:hover {
            background-color: #1a356e;
            border-color: #1a356e;
        }

        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
            color: #6c757d;
        }

        .login-footer a {
            color: #3a5f99;
            text-decoration: none;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <main class="container">
        <div class="login-form">
            <h2>Anmelden</h2>
            <form action="einloggen-verarbeiten.php" method="post">
                <div class="mb-3">
                    <label for="username" class="form-label">Benutzername</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Passwort</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Angemeldet bleiben</label>
                </div>
                <button type="submit" class="btn btn-primary">Einloggen</button>
            </form>
            <div class="login-footer">
                <p>Zurück zu: <a href="main-page.html">EngliFy.com</a></p>
                <p>Noch kein Konto? <a href="registrieren.php">Jetzt registrieren</a></p>
            </div>
        </div>
    </main>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>
