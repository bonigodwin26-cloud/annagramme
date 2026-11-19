<?php
declare(strict_types=1);

session_start();
require __DIR__ . '/config.php';

function h(string $valeur): string
{
    return htmlspecialchars($valeur, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (isset($_SESSION['user_id'])) {
    header('Location: interface.php');
    exit;
}

$pseudo = '';
$erreur = '';
$pdo = null;

try {
    $pdo = obtenirConnexion();
} catch (PDOException $exception) {
    $erreur = 'Connexion à la base de données impossible.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pseudo = is_string($_POST['pseudo'] ?? null) ? trim($_POST['pseudo']) : '';
    $motDePasse = is_string($_POST['mot_de_passe'] ?? null) ? $_POST['mot_de_passe'] : '';

    if ($pseudo === '' || $motDePasse === '') {
        $erreur = 'Saisissez votre pseudonyme et votre mot de passe.';
    } elseif ($pdo instanceof PDO) {
        try {
            $requete = $pdo->prepare('SELECT id, pseudo, mot_de_passe FROM utilisateurs WHERE pseudo = :pseudo LIMIT 1');
            $requete->execute(['pseudo' => $pseudo]);
            $utilisateur = $requete->fetch();

            if ($utilisateur && password_verify($motDePasse, $utilisateur['mot_de_passe'])) {
                $ancienneSession = session_id();
                $pdo->prepare('UPDATE verifications SET user_id = :user_id WHERE user_id IS NULL AND session_id = :session_id')
                    ->execute(['user_id' => $utilisateur['id'], 'session_id' => $ancienneSession]);
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $utilisateur['id'];
                $_SESSION['pseudo'] = $utilisateur['pseudo'];
                header('Location: interface.php');
                exit;
            }

            $erreur = 'Pseudonyme ou mot de passe incorrect.';
        } catch (PDOException $exception) {
            $erreur = 'Impossible de vérifier vos identifiants. Réessayez.';
        }
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Se connecter - Anagramme</title>
    <link rel="stylesheet" href="style.css?v=7">
</head>
<body class="page-auth">
<div class="cadre cadre-auth">
<header class="entete"><a class="auth-marque marque" href="interface.php">Anagramme</a></header>
<main class="auth-box auth-connexion">
    <h1 class="visuellement-cache">Se connecter</h1>
    <form method="post" action="">
        <label for="pseudo">Pseudo</label>
        <input id="pseudo" name="pseudo" type="text" maxlength="100" autocomplete="username" value="<?= h($pseudo) ?>" required>
        <label for="mot_de_passe">Mot de passe</label>
        <input id="mot_de_passe" name="mot_de_passe" type="password" autocomplete="current-password" required>
        <?php if ($erreur !== ''): ?><p class="message message-erreur" role="alert"><?= h($erreur) ?></p><?php endif; ?>
        <button type="submit">Se connecter</button>
    </form>
    <p class="auth-lien">Avez-vous un compte? <a href="inscription.php">S’inscrire</a></p>
</main>
</div>
</body>
</html>
