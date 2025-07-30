<?php

$config = [
    'host'     => 'localhost',
    'username' => 'root',
    'password' => 'eqjbeop669BqTngza5AQ',
    'database' => 'gestion_taches_et_serveurs'
];

// Répertoire racine contenant tous les serveurs
$rootDirectory = 'backup_taches_windows_20250429';

// Connexion à la base de données
try 
{
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4",
        $config['username'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    echo "Connexion à la base de données établie.\n";
	
	$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    $tables = [
        'composer',
        'dependance_serveur',
        'dependance_tache',
        'historique',
        'planification',
        'statistique_execution',
        'action',
        'tache',
        'serveur',
        'responsable',
        'fonction',
        'criticite'
    ];
	foreach ($tables as $table) {
        $pdo->exec("DELETE FROM `$table`");
        echo "Données supprimées de la table `$table`.\n";
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "Toutes les données ont été supprimées avec succès.\n";
} 
catch (PDOException $e) 
{
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}