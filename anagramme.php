<?php
declare(strict_types=1);

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
$resultat = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $chaine1 = is_string($_POST['string1'] ?? null) ? trim($_POST['string1']) : '';
    $chaine2 = is_string($_POST['string2'] ?? null) ? trim($_POST['string2']) : '';

    if ($chaine1 === '' || $chaine2 === '') {
        $erreur = 'Veuillez remplir les deux champs.';
    } elseif (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $chaine1 . $chaine2)) {
        $erreur = 'Les champs contiennent des caractères non autorisés.';
    } else {
        $resultat = sontAnagrammes($chaine1, $chaine2);
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Anagramme - Vérificateur</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="cadre">
    <header><span>Anagramme</span></header>
    <main class="carte<?= $resultat === true ? ' resultat-succes' : ($resultat === false ? ' resultat-echec' : '') ?>">
        <form method="post" action="">
            <label for="string1">String 1</label>
            <label for="string2">String 2</label>
            <input id="string1" name="string1" type="text" value="<?= echapper($chaine1) ?>" required
                   class="<?= $resultat === null ? '' : ($resultat ? 'champ-ok' : 'champ-erreur') ?>">
            <input id="string2" name="string2" type="text" value="<?= echapper($chaine2) ?>" required
                   class="<?= $resultat === null ? '' : ($resultat ? 'champ-ok' : 'champ-erreur') ?>">
            <button type="submit">Soumettre</button>
            <?php if ($erreur !== ''): ?>
                <p class="message" role="alert"><?= echapper($erreur) ?></p>
            <?php endif; ?>
        </form>
    </main>
</div>
</body>
</html>
