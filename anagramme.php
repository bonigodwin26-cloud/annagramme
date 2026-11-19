<?php
declare(strict_types=1);

/**
 * Compare the character frequencies in two strings.
 * Expected time: O(n + m); auxiliary space: O(k), where k is the number
 * of distinct bytes in the inputs. Comparison is case-sensitive and keeps spaces.
 */
function sontAnagrammes(string $mot1, string $mot2): bool
{
    if ($mot1 === '' || $mot2 === '' || strlen($mot1) !== strlen($mot2)) {
        return false;
    }

    $frequences1 = array_count_values(str_split($mot1));
    $frequences2 = array_count_values(str_split($mot2));

    ksort($frequences1);
    ksort($frequences2);

    return $frequences1 === $frequences2;
}

function e(string $valeur): string
{
    return htmlspecialchars($valeur, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$mot1 = '';
$mot2 = '';
$erreur = '';
$resultat = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mot1 = is_string($_POST['string1'] ?? null) ? trim($_POST['string1']) : '';
    $mot2 = is_string($_POST['string2'] ?? null) ? trim($_POST['string2']) : '';

    if ($mot1 === '' || $mot2 === '') {
        $erreur = 'Veuillez remplir les deux champs.';
    } elseif (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $mot1 . $mot2)) {
        $erreur = 'Les champs contiennent des caractères de contrôle non autorisés.';
    } else {
        $resultat = sontAnagrammes($mot1, $mot2);
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vérificateur d’anagrammes</title>
    <style>
        :root { color-scheme: light; font-family: system-ui, sans-serif; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #f3f6fb; color: #172033; }
        main { width: min(92vw, 520px); box-sizing: border-box; padding: 2rem; background: white; border-radius: 16px; box-shadow: 0 12px 36px #17203318; }
        h1 { margin-top: 0; font-size: 1.7rem; }
        p { color: #596579; }
        label { display: block; margin: 1.1rem 0 .4rem; font-weight: 650; }
        input { box-sizing: border-box; width: 100%; padding: .8rem .9rem; border: 2px solid #cbd3df; border-radius: 8px; font: inherit; }
        input:focus { outline: 3px solid #2563eb30; border-color: #2563eb; }
        input.ok { border-color: #16834a; }
        input.non { border-color: #c43131; }
        button { width: 100%; margin-top: 1.4rem; padding: .85rem; border: 0; border-radius: 8px; background: #2457d6; color: white; font: inherit; font-weight: 700; cursor: pointer; }
        button:hover { background: #1948bd; }
        .message { margin-top: 1.2rem; padding: .9rem 1rem; border-radius: 8px; }
        .succes { color: #116337; background: #e9f8ef; }
        .echec, .erreur { color: #9e2525; background: #fff0f0; }
    </style>
</head>
<body>
<main>
    <h1>Vérificateur d’anagrammes</h1>
    <p>Saisissez deux chaînes pour comparer leurs caractères et leurs occurrences.</p>

    <form method="post" action="">
        <label for="string1">String 1</label>
        <input id="string1" name="string1" type="text" value="<?= e($mot1) ?>" required
               class="<?= $resultat === null ? '' : ($resultat ? 'ok' : 'non') ?>">

        <label for="string2">String 2</label>
        <input id="string2" name="string2" type="text" value="<?= e($mot2) ?>" required
               class="<?= $resultat === null ? '' : ($resultat ? 'ok' : 'non') ?>">

        <button type="submit">Soumettre</button>
    </form>

    <?php if ($erreur !== ''): ?>
        <div class="message erreur" role="alert"><?= e($erreur) ?></div>
    <?php elseif ($resultat === true): ?>
        <div class="message succes" role="status">Ces deux chaînes sont des anagrammes.</div>
    <?php elseif ($resultat === false): ?>
        <div class="message echec" role="status">Ces deux chaînes ne sont pas des anagrammes.</div>
    <?php endif; ?>
</main>
</body>
</html>
