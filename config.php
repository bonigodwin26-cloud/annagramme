<?php
declare(strict_types=1);

function obtenirConnexion(): PDO
{
    $hote = '127.0.0.1';
    $base = 'anagramme_db';
    $utilisateur = 'root';
    $motDePasse = '';

    return new PDO(
        "mysql:host={$hote};dbname={$base};charset=utf8mb4",
        $utilisateur,
        $motDePasse,
        [
            PDO::ATTR_TIMEOUT => 3,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}
