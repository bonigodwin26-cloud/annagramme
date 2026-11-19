<?php
declare(strict_types=1);

session_start();
require __DIR__ . '/config.php';

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

function echapper(string $valeur): string
{
    return htmlspecialchars($valeur, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$mot1 = '';
$mot2 = '';
$erreur = '';
$resultat = null;
$historique = [];
$pdo = null;
$erreurBase = '';

try {
    $pdo = obtenirConnexion();
} catch (PDOException $exception) {
    $erreurBase = 'La base de données est indisponible. Vérifiez sa configuration et importez database.sql.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mot1 = is_string($_POST['string1'] ?? null) ? trim($_POST['string1']) : '';
    $mot2 = is_string($_POST['string2'] ?? null) ? trim($_POST['string2']) : '';

    if ($mot1 === '' || $mot2 === '') {
        $erreur = 'Veuillez remplir les deux champs.';
    } elseif (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $mot1 . $mot2)) {
        $erreur = 'Les champs contiennent des caractères non autorisés.';
    } else {
        $resultat = sontAnagrammes($mot1, $mot2);

        if ($pdo instanceof PDO) {
            try {
                $utilisateurId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
                $insertion = $pdo->prepare(
                    'INSERT INTO verifications (user_id, session_id, chaine_1, chaine_2, resultat) '
                    . 'VALUES (:user_id, :session_id, :chaine_1, :chaine_2, :resultat)'
                );
                $insertion->execute([
                    'user_id' => $utilisateurId,
                    'session_id' => session_id(),
                    'chaine_1' => $mot1,
                    'chaine_2' => $mot2,
                    'resultat' => $resultat ? 1 : 0,
                ]);
            } catch (PDOException $exception) {
                $erreurBase = 'La vérification a été faite, mais elle n’a pas pu être enregistrée.';
            }
        }
    }
}

if ($pdo instanceof PDO) {
    try {
        if (isset($_SESSION['user_id'])) {
            $requete = $pdo->prepare(
                'SELECT id, chaine_1, chaine_2, resultat, date_verification '
                . 'FROM verifications WHERE user_id = :user_id ORDER BY date_verification DESC, id DESC LIMIT 20'
            );
            $requete->execute(['user_id' => (int) $_SESSION['user_id']]);
        } else {
            $requete = $pdo->prepare(
                'SELECT id, chaine_1, chaine_2, resultat, date_verification '
                . 'FROM verifications WHERE user_id IS NULL AND session_id = :session_id '
                . 'ORDER BY date_verification DESC, id DESC LIMIT 20'
            );
            $requete->execute(['session_id' => session_id()]);
        }
        $historique = $requete->fetchAll();
    } catch (PDOException $exception) {
        $erreurBase = 'Impossible de charger l’historique. Vérifiez que database.sql a bien été importé.';
    }
}

$pseudo = is_string($_SESSION['pseudo'] ?? null) ? $_SESSION['pseudo'] : 'Visiteur';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= isset($_SESSION['user_id']) ? 'Anagramme - Historique' : 'Anagramme - Vérificateur' ?></title>
    <link rel="stylesheet" href="style.css?v=22">
</head>
<body>
<div class="cadre <?= isset($_SESSION['user_id']) ? 'cadre-dashboard' : 'cadre-accueil' ?>">
    <header class="entete">
        <span class="marque">Anagramme</span>
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="profil"><span class="avatar" aria-hidden="true"></span><span><?= echapper($pseudo) ?></span></div>
        <?php else: ?>
            <a class="connexion" href="connexion.php">Se connecter</a>
        <?php endif; ?>
    </header>

    <main class="contenu <?= isset($_SESSION['user_id']) ? 'contenu-dashboard' : 'contenu-accueil' ?>">
        <section class="carte<?= $resultat === true ? ' resultat-succes' : ($resultat === false ? ' resultat-echec' : '') ?>" aria-label="Vérificateur d’anagrammes">
            <form method="post" action="" autocomplete="off">
                <label for="string1">String 1</label>
                <label for="string2">String 2</label>
                <div class="champ-saisie">
                    <input id="string1" name="string1" type="text" maxlength="2000" autocomplete="off" placeholder=" " value="<?= echapper($mot1) ?>" required
                           class="<?= $resultat === null ? '' : ($resultat ? 'champ-ok' : 'champ-erreur') ?>">
                    <button class="effacer-champ" type="button" data-cible="string1" aria-label="Effacer String 1" title="Effacer String 1"><img src="assets/input-icon-<?= $mot1 === '' ? 'gray' : 'dark' ?>.png" alt=""></button>
                </div>
                <div class="champ-saisie">
                    <input id="string2" name="string2" type="text" maxlength="2000" autocomplete="off" placeholder=" " value="<?= echapper($mot2) ?>" required
                           class="<?= $resultat === null ? '' : ($resultat ? 'champ-ok' : 'champ-erreur') ?>">
                    <button class="effacer-champ" type="button" data-cible="string2" aria-label="Effacer String 2" title="Effacer String 2"><img src="assets/input-icon-<?= $mot2 === '' ? 'gray' : 'dark' ?>.png" alt=""></button>
                </div>
                <button type="submit">Soumettre</button>
                <?php if ($erreur !== ''): ?>
                    <div class="message message-erreur" role="alert"><?= echapper($erreur) ?></div>
                <?php endif; ?>
                <?php if ($erreurBase !== ''): ?>
                    <div class="message message-erreur" role="status"><?= echapper($erreurBase) ?></div>
                <?php endif; ?>
            </form>
        </section>

        <?php if (isset($_SESSION['user_id'])): ?>
        <section class="historique" aria-labelledby="titre-historique">
            <h1 id="titre-historique">Historique des vérifications</h1>
            <div class="tableau-defilement">
                <table>
                    <thead>
                        <tr><th scope="col" aria-label="Numéro"></th><th scope="col">String 1</th><th scope="col">String 2</th><th scope="col">Date</th><th scope="col">Heure</th></tr>
                    </thead>
                    <tbody>
                    <?php if ($historique === []): ?>
                        <tr><td class="vide" colspan="5">Aucune vérification enregistrée.</td></tr>
                    <?php else: ?>
                        <?php foreach ($historique as $index => $ligne): ?>
                            <tr class="<?= (bool) $ligne['resultat'] ? 'ligne-succes' : 'ligne-echec' ?>" aria-label="<?= (bool) $ligne['resultat'] ? 'Anagrammes' : 'Pas des anagrammes' ?>" title="Vérification du <?= echapper(date('d/m/Y H:i', strtotime($ligne['date_verification']))) ?>">
                                <td><?= $index + 1 ?></td>
                                <td><?= echapper($ligne['chaine_1']) ?></td>
                                <td><?= echapper($ligne['chaine_2']) ?></td>
                                <td><?= echapper(date('d/m/Y', strtotime($ligne['date_verification']))) ?></td>
                                <td><?= echapper(date('H:i', strtotime($ligne['date_verification']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <?php if (isset($_SESSION['user_id'])): ?>
        <a class="deconnexion" href="deconnexion.php" aria-label="Se déconnecter" title="Se déconnecter">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 4H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h5M14 16l4-4-4-4M8 12h10"/></svg>
        </a>
    <?php endif; ?>
</div>
<script>
document.querySelectorAll('.effacer-champ').forEach((bouton) => {
    const champ = document.getElementById(bouton.dataset.cible);
    const icone = bouton.querySelector('img');
    const actualiserIcone = () => {
        icone.src = champ.value === '' ? 'assets/input-icon-gray.png' : 'assets/input-icon-dark.png';
    };

    champ.addEventListener('input', actualiserIcone);
    bouton.addEventListener('click', () => {
        if (!champ) return;
        champ.value = '';
        actualiserIcone();
        champ.classList.remove('champ-ok', 'champ-erreur');
        const carte = bouton.closest('.carte');
        if (carte) carte.classList.remove('resultat-succes', 'resultat-echec');
        champ.dispatchEvent(new Event('input', { bubbles: true }));
        champ.focus();
    });
});
</script>
</body>
</html>
