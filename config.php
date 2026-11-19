<?php
declare(strict_types=1);

function obtenirConnexion(): PDO
{
    return new PDO(
        'mysql:host=127.0.0.1;dbname=anagramme_db;charset=utf8mb4',
        'root',
        '',
        [
            PDO::ATTR_TIMEOUT => 3,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}
