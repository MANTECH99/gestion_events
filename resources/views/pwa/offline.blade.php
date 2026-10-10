<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Billeterie Express</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/images/icon.svg" type="image/svg+xml">
    <meta name="theme-color" content="#1f41f0">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: #f6f7f9;
            color: #343946;
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
            text-align: center;
            padding: 2rem;
        }
        .box { max-width: 26rem; }
        .icon {
            width: 72px; height: 72px; margin: 0 auto 1.5rem;
            border-radius: 20px;
            background: linear-gradient(135deg, #1f41f0, #6d3bf5);
            display: grid; place-items: center; color: #fff; font-size: 2rem;
        }
        h1 { font-size: 1.5rem; margin: 0 0 .5rem; }
        p { color: #667490; line-height: 1.6; margin: 0 0 1.5rem; }
        a {
            display: inline-block; background: #1f41f0; color: #fff; text-decoration: none;
            padding: .75rem 1.5rem; border-radius: .75rem; font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="box">
        <div class="icon">🎟️</div>
        <h1>Vous êtes hors connexion</h1>
        <p>
            La page demandée n'a pas pu être chargée. Vérifiez votre réseau internet
            puis réessayez. Les pages déjà consultées restent accessibles.
        </p>
        <a href="/">Retour à l'accueil</a>
    </div>
</body>
</html>