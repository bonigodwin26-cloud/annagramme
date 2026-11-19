<?php
declare(strict_types=1);

session_start();
require __DIR__ . '/config.php';

function sontAnagrammes(string $chaine1, string $chaine2): bool
{
    if ($chaine1 === '' || $chaine2 === '' || strlen($chaine1) !== strlen($chaine2)) {
        return false;
    }

    $caracteres1 = str_split($chaine1);
    $caracteres2 = str_split($chaine2);
    sort($caracteres1);
    sort($caracteres2);
    return $caracteres1 === $caracteres2;
}

function echapper(string $valeur): string
{
    return htmlspecialchars($valeur, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$chaine1 = '';
$chaine2 = '';
$erreur = '';
$erreurBase = '';
$resultat = null;
$historique = [];
$pdo = null;

try {
    $pdo = obtenirConnexion();
} catch (PDOException $exception) {
    $erreurBase = 'Base de données indisponible. Importez database.sql dans phpMyAdmin.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $chaine1 = is_string($_POST['string1'] ?? null) ? trim($_POST['string1']) : '';
    $chaine2 = is_string($_POST['string2'] ?? null) ? trim($_POST['string2']) : '';

    if ($chaine1 === '' || $chaine2 === '') {
        $erreur = 'Veuillez remplir les deux champs.';
    } elseif (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $chaine1 . $chaine2)) {
        $erreur = 'Les champs contiennent des caractères non autorisés.';
    } else {
        $resultat = sontAnagrammes($chaine1, $chaine2);
        if ($pdo instanceof PDO) {
            try {
                $ajout = $pdo->prepare(
                    'INSERT INTO verifications (session_id, chaine_1, chaine_2, resultat) '
                    . 'VALUES (:session_id, :chaine_1, :chaine_2, :resultat)'
                );
                $ajout->execute([
                    'session_id' => session_id(),
                    'chaine_1' => $chaine1,
                    'chaine_2' => $chaine2,
                    'resultat' => $resultat ? 1 : 0,
                ]);
            } catch (PDOException $exception) {
                $erreurBase = 'La vérification n’a pas pu être enregistrée.';
            }
        }
    }
}

if ($pdo instanceof PDO) {
    try {
        $liste = $pdo->prepare(
            'SELECT chaine_1, chaine_2, resultat, date_verification FROM verifications '
            . 'WHERE session_id = :session_id ORDER BY date_verification DESC, id DESC LIMIT 20'
        );
        $liste->execute(['session_id' => session_id()]);
        $historique = $liste->fetchAll();
    } catch (PDOException $exception) {
        $erreurBase = 'Impossible de charger l’historique. Vérifiez le script database.sql.';
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Anagramme - Historique</title>
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body>
<div class="cadre">
    <header><span>Anagramme</span><span class="nom-visiteur">Historique</span></header>
    <main class="contenu">
        <section class="carte<?= $resultat === true ? ' resultat-succes' : ($resultat === false ? ' resultat-echec' : '') ?>">
            <form method="post" action="">
                <label for="string1">String 1</label>
                <label for="string2">String 2</label>
                <input id="string1" name="string1" type="text" value="<?= echapper($chaine1) ?>" required
                       class="<?= $resultat === null ? '' : ($resultat ? 'champ-ok' : 'champ-erreur') ?>">
                <input id="string2" name="string2" type="text" value="<?= echapper($chaine2) ?>" required
                       class="<?= $resultat === null ? '' : ($resultat ? 'champ-ok' : 'champ-erreur') ?>">
                <button type="submit">Soumettre</button>
                <?php if ($erreur !== ''): ?><p class="message" role="alert"><?= echapper($erreur) ?></p><?php endif; ?>
                <?php if ($erreurBase !== ''): ?><p class="message" role="status"><?= echapper($erreurBase) ?></p><?php endif; ?>
            </form>
        </section>
        <section class="historique" aria-label="Historique des vérifications">
            <table>
                <thead><tr><th></th><th>String 1</th><th>String 2</th></tr></thead>
                <tbody>
                <?php if ($historique === []): ?>
                    <tr><td colspan="3" class="vide">Aucune vérification enregistrée.</td></tr>
                <?php else: ?>
                    <?php foreach ($historique as $numero => $ligne): ?>
                        <tr class="<?= (bool) $ligne['resultat'] ? 'ligne-succes' : 'ligne-echec' ?>"
                            aria-label="<?= (bool) $ligne['resultat'] ? 'Anagrammes' : 'Pas des anagrammes' ?>"
                            title="Vérification du <?= echapper(date('d/m/Y H:i', strtotime($ligne['date_verification']))) ?>">
                            <td><?= $numero ?></td><td><?= echapper($ligne['chaine_1']) ?></td><td><?= echapper($ligne['chaine_2']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>
</body>
</html>
