<?php
/**
 * Script d'importation de tâches XML vers la base de données MySQL
 * 
 * Ce programme analyse récursivement les fichiers XML de tâches planifiées Windows
 * dans une arborescence de répertoires et les insère dans la base de données MySQL.
 */

// Configuration de la base de données
$config = [
    'host'     => 'localhost',
    'username' => 'root',
    'password' => 'eqjbeop669BqTngza5AQ',
    'database' => 'gestion_taches_et_serveurs'
];

// Répertoire racine contenant tous les serveurs
$rootDirectory = 'backup_taches_windows_20250429';

// Connexion à la base de données
try {
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
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}

// Fonction pour obtenir l'ID d'une action (ou la créer si elle n'existe pas)
function getOrCreateAction($pdo, $actionName) {
    if (empty($actionName)) {
        return null;
    }
    
    $stmt = $pdo->prepare("SELECT act_id FROM action WHERE act_texte = ?");
    $stmt->execute([$actionName]);
    $result = $stmt->fetch();
    
    if ($result) {
        return $result['act_id'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO action (act_texte) VALUES (?)");
        $stmt->execute([$actionName]);
        return $pdo->lastInsertId();
    }
}

// Fonction pour obtenir ou créer un serveur
function getOrCreateServeur($pdo, $nomServeur, $description = '', $idResponsable = null, $idFonction = null) {
    if (empty($nomServeur)) {
        return null;
    }
    
    $stmt = $pdo->prepare("SELECT ser_id FROM Serveur WHERE ser_nom = ?");
    $stmt->execute([$nomServeur]);
    $result = $stmt->fetch();
    
    if ($result) {
        return $result['ser_id'];
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO Serveur (
                ser_nom, ser_description, ser_remarque, ser_periode_arret, 
                ser_collect_automatique, ser_date_creation, ser_date_modification, 
                ser_res_id, ser_fonc_id
            ) VALUES (?, ?, '', '', 0, NOW(), NOW(), ?, ?)
        ");
        $stmt->execute([
            $nomServeur, 
            $description, 
            $idResponsable, 
            $idFonction
        ]);
        return $pdo->lastInsertId();
    }
}

// Fonction pour traiter un fichier XML et l'insérer dans la base de données
function processXmlFile($pdo, $xmlFilePath, $serveurNom) {
    echo "Traitement du fichier: $xmlFilePath\n";
    
    // Vérifier si le fichier XML existe
    if (!file_exists($xmlFilePath)) {
        echo "Le fichier XML '$xmlFilePath' n'existe pas.\n";
        return false;
    }
    
    // Charger le fichier XML
    try {
        // Lire le contenu du fichier
        $xmlContent = file_get_contents($xmlFilePath);
        
        // Détecter l'encodage BOM (Byte Order Mark)
        $bom = substr($xmlContent, 0, 3);
        if ($bom === "\xEF\xBB\xBF") {
            // C'est un BOM UTF-8, on le supprime
            $xmlContent = substr($xmlContent, 3);
        }
        
        // Remplacer la déclaration d'encodage UTF-16 par UTF-8
        $xmlContent = preg_replace('/<\?xml[^>]+encoding=["\']UTF-16["\']/i', '<?xml version="1.0" encoding="UTF-8"', $xmlContent);
        
        // Charger le XML
        libxml_use_internal_errors(true);
        $xml = new SimpleXMLElement($xmlContent, LIBXML_NOWARNING, false, 'http://schemas.microsoft.com/windows/2004/02/mit/task');
        
        if (!$xml) {
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                echo "Erreur XML: " . $error->message . "\n";
            }
            libxml_clear_errors();
            return false;
        }
        
    } catch (Exception $e) {
        echo "Erreur lors du chargement du fichier XML {$xmlFilePath}: " . $e->getMessage() . "\n";
        return false;
    }
    
    try {
        $pdo->beginTransaction();

        // Obtenir l'ID du serveur
        $idServeur = getOrCreateServeur($pdo, $serveurNom, "Serveur $serveurNom");
        
        // Extraction des informations sur les actions
        $actions = [];
        if (isset($xml->Actions->Exec)) {
            foreach ($xml->Actions->Exec as $exec) {
                $command = (string)$exec->Command;
                $arguments = (string)$exec->Arguments;
                $actionName = basename($command) . " " . $arguments;
                $actionId = getOrCreateAction($pdo, $actionName);
                $actions[] = $actionId;
            }
        }
        
        // Extraction des informations de la tâche
        $taskName = basename($xmlFilePath, ".xml");
        $description = isset($xml->RegistrationInfo->Description) ? (string)$xml->RegistrationInfo->Description : '';
        
        // Information sur l'utilisateur d'exécution
        $userExec = isset($xml->Principals->Principal->UserId) ? (string)$xml->Principals->Principal->UserId : '';
        
        // Déterminer si la tâche est active
        $active = isset($xml->Settings->Enabled) ? ((string)$xml->Settings->Enabled == 'true') : false;
        
        // Extraction des informations de permissions d'exécution
        $executionAutorisationMaximale = isset($xml->Principals->Principal->RunLevel) ? 
            ((string)$xml->Principals->Principal->RunLevel === 'HighestAvailable') : false;
        
        // Vérifier si la tâche existe déjà pour ce serveur
        $stmt = $pdo->prepare("SELECT tac_id FROM Tache WHERE tac_nom = ? AND tac_ser_id = ?");
        $stmt->execute([$taskName, $idServeur]);
        $existingTask = $stmt->fetch();
        
        if ($existingTask) {
            echo "La tâche '$taskName' existe déjà pour le serveur '$serveurNom' (ID: {$existingTask['idTache']}). Mise à jour...\n";
            
            // Mettre à jour la tâche existante
            $stmt = $pdo->prepare("
                UPDATE Tache SET
                    tac_description = ?, tac_compte_utilisateur_execution = ?,
                    tac_date_modification = NOW(), active = ?, tac_execution_autorisation_maximale = ?
                WHERE idTache = ?
            ");
            
            $stmt->execute([
                $description,
                $userExec,
                $active ? 1 : 0,
                $executionAutorisationMaximale ? 1 : 0,
                $existingTask['idTache']
            ]);
            
            $idTache = $existingTask['idTache'];
            
            // Supprimer les associations d'actions existantes
            $stmt = $pdo->prepare("DELETE FROM Composer WHERE idTache = ?");
            $stmt->execute([$idTache]);
        } else {
            // Insérer la nouvelle tâche
            $stmt = $pdo->prepare("
                INSERT INTO Tache (
                    tac_nom, tac_description, tac_remarque, tac_relance_necessaire, 
                    tac_possibilite_replanification, tac_taux_pannes, tac_compte_utilisateur_creation, 
                    tac_compte_utilisateur_execution, tac_date_creation, tac_date_modification, 
                    tac_collecte_automatique, tac_active, tac_execution_autorisation_maximale, 
                    tac_ser_id, tac_res_id, tac_cri_id
                ) VALUES (
                    ?, ?, '', ?, ?, '', 'SYSTEM', ?, NOW(), NOW(), 
                    ?, ?, ?, ?, NULL, NULL
                )
            ");
            
            // Déterminer si la relance est nécessaire et si la replanification est possible
            $relanceNecessaire = isset($xml->Triggers->CalendarTrigger->Repetition) ? true : false;
            $possibiliteReplanification = $relanceNecessaire;
            
            $stmt->execute([
                $taskName,
                $description,
                $relanceNecessaire ? 1 : 0,
                $possibiliteReplanification ? 1 : 0,
                $userExec,
                1, // collecteAutomatique par défaut à true
                $active ? 1 : 0,
                $executionAutorisationMaximale ? 1 : 0,
                $idServeur
            ]);
            
            $idTache = $pdo->lastInsertId();
        }
        
        // Associer les actions à la tâche
        foreach ($actions as $idAction) {
            $stmt = $pdo->prepare("INSERT INTO Composer (cmp_tac_id, cmp_act_id) VALUES (?, ?)");
            $stmt->execute([$idTache, $idAction]);
        }
        
        // Traiter les informations de planification
        if (isset($xml->Triggers->CalendarTrigger)) {
            // Vérifier si une planification existe déjà pour cette tâche
            $stmt = $pdo->prepare("SELECT plan_id FROM Planification WHERE plan_tac_id = ?");
            $stmt->execute([$idTache]);
            $existingPlan = $stmt->fetch();
            
            $trigger = $xml->Triggers->CalendarTrigger;
            $dateDebut = isset($trigger->StartBoundary) ? (string)$trigger->StartBoundary : null;
            
            // Récupérer l'intervalle de répétition
            $repetition = "Quotidien"; // Valeur par défaut
            if (isset($trigger->ScheduleByDay->DaysInterval)) {
                $daysInterval = (int)$trigger->ScheduleByDay->DaysInterval;
                $repetition = "Tous les $daysInterval jour(s)";
            }
            
            if (isset($trigger->Repetition->Interval)) {
                $interval = (string)$trigger->Repetition->Interval;
                // Convertir les formats PT1H en texte lisible
                if (preg_match('/PT(\d+)H/', $interval, $matches)) {
                    $hours = $matches[1];
                    $repetition .= ", toutes les $hours heure(s)";
                }
            }
            
			if ($existingPlan) {
				// Mettre à jour la planification existante
				$stmt = $pdo->prepare("
					UPDATE Planification SET
						plan_date_debut = ?, plan_repetition = ?, plan_active = ?, plan_date_modification = NOW()
					WHERE plan_id_planification = ?
				");
				
				$stmt->execute([
					$dateDebut,
					$repetition,
					$active ? 1 : 0,
					$existingPlan['idPlanification']
				]);
				
			} else {
				$stmt = $pdo->prepare("
					INSERT INTO Planification (
						plan_date_debut, plan_date_fin, plan_repetition, 
						plan_active, plan_date_creation, plan_date_modification, plan_tac_id
					) VALUES (?, NULL, ?, ?, NOW(), NOW(), ?)
				");
				
				$stmt->execute([
					$dateDebut,
					$repetition,
					$active ? 1 : 0,
					$idTache
				]);
			}

        }
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Erreur lors du traitement du fichier XML {$xmlFilePath}: " . $e->getMessage() . "\n";
        return false;
    }
}

// Fonction récursive pour parcourir les répertoires et traiter les fichiers XML
function processDirectory($pdo, $directory, $currentServeur = null, $parentIsDateDir = false) {
    if (!is_dir($directory)) {
        echo "Le répertoire '$directory' n'existe pas.\n";
        return;
    }

    $items = scandir($directory);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $path = $directory . DIRECTORY_SEPARATOR . $item;

        if (is_dir($path)) {
            // Vérifie si le dossier actuel est un dossier de date (ex: 20250429-010002)
            if (preg_match('/^\d{8}-\d{6}$/', basename($item))) {
                processDirectory($pdo, $path, null, true);
            } elseif ($parentIsDateDir) {
                // Ce dossier est juste après un dossier de date => c’est le nom du serveur
                $nomServeur = $item;
                echo "Serveur détecté: $nomServeur\n";
                processDirectory($pdo, $path, $nomServeur, false);
            } else {
                // Sinon, on continue à parcourir en profondeur
                processDirectory($pdo, $path, $currentServeur, false);
            }

        } elseif (is_file($path) && strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'xml') {
            processXmlFile($pdo, $path, $currentServeur);
        }
    }
}


processDirectory($pdo, $rootDirectory);
echo "Traitement terminé.\n";