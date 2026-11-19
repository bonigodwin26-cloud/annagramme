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
    $confirmation = is_string($_POST['confirmation'] ?? null) ? $_POST['confirmation'] : '';

    if (!preg_match('/^[\p{L}\p{N}_-]{3,32}$/u', $pseudo)) {
        $erreur = 'Le pseudonyme doit contenir de 3 à 32 lettres, chiffres, tirets ou caractères _.';
    } elseif (strlen($motDePasse) < 8) {
        $erreur = 'Le mot de passe doit contenir au moins 8 caractères.';
    } elseif ($motDePasse !== $confirmation) {
        $erreur = 'Les deux mots de passe ne correspondent pas.';
    } elseif ($pdo instanceof PDO) {
        try {
            $creation = $pdo->prepare('INSERT INTO utilisateurs (pseudo, mot_de_passe) VALUES (:pseudo, :mot_de_passe)');
            $creation->execute([
                'pseudo' => $pseudo,
                'mot_de_passe' => password_hash($motDePasse, PASSWORD_DEFAULT),
            ]);

            $utilisateurId = (int) $pdo->lastInsertId();
            $ancienneSession = session_id();
            $pdo->prepare('UPDATE verifications SET user_id = :user_id WHERE user_id IS NULL AND session_id = :session_id')
                ->execute(['user_id' => $utilisateurId, 'session_id' => $ancienneSession]);
            session_regenerate_id(true);
            $_SESSION['user_id'] = $utilisateurId;
            $_SESSION['pseudo'] = $pseudo;
            header('Location: interface.php');
            exit;
        } catch (PDOException $exception) {
            $erreur = $exception->getCode() === '23000'
                ? 'Ce pseudonyme est déjà utilisé.'
                : 'Impossible de créer le compte. Réessayez.';
        }
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Créer un compte - Anagramme</title>
    <link rel="stylesheet" href="style.css?v=7">
</head>
<body class="page-auth">
<div class="cadre cadre-auth">
<header class="entete"><a class="auth-marque marque" href="interface.php">Anagramme</a></header>
<main class="auth-box auth-inscription">
    <h1 class="visuellement-cache">Créer un compte</h1>
    <form method="post" action="">
        <label for="pseudo">Pseudo</label>
        <input id="pseudo" name="pseudo" type="text" maxlength="32" autocomplete="username" value="<?= h($pseudo) ?>" required>
        <label for="mot_de_passe">Mot de passe</label>
        <input id="mot_de_passe" name="mot_de_passe" type="password" minlength="8" autocomplete="new-password" required>
        <label for="confirmation">Confirmer le mot de passe</label>
        <input id="confirmation" name="confirmation" type="password" minlength="8" autocomplete="new-password" required>
        <?php if ($erreur !== ''): ?><p class="message message-erreur" role="alert"><?= h($erreur) ?></p><?php endif; ?>
        <button type="submit">S’inscrire</button>
    </form>
    <p class="auth-lien">Avez-vous un compte? <a href="connexion.php">Se connecter</a></p>
</main>
</div>
</body>
</html>
