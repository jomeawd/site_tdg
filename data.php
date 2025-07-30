<?php
// data.php

function getPDO() {
    $host = 'localhost';
    $db   = 'gestion_taches_et_serveurs';
    $user = 'root';
    $pass = 'eqjbeop669BqTngza5AQ';

    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ];

    return new PDO($dsn, $user, $pass, $options);
}

function getTaches($dateDebut = null, $dateFin = null, $ser_id = null) {
    $pdo = getPDO();

    $sql = "
        SELECT 
            t.tac_id AS id,
            t.tac_nom AS nom,
            t.tac_description AS description,
            t.tac_ser_id AS ser_id,
            s.ser_nom AS serveur,
			p.plan_repetition AS planification,
			p.plan_date_debut,
			p.plan_date_fin,
			p.plan_heure_execution,
			p.plan_intervalle,
            c.cri_libelle AS criticite,
	        a.act_id,
			a.act_type,
			a.act_command,
			a.act_argument,
			a.act_working_directory,
			a.act_expediteur,
			a.act_destinataire,
			a.act_objet,
			a.act_texte,
			a.act_serveur,
			a.act_titre,
			a.act_message,
            t.tac_remarque AS tac_remarque,
            t.tac_compte_utilisateur_creation AS compte_creation,
            t.tac_date_creation AS date_creation,
            t.tac_date_modification AS date_modification,
            t.tac_compte_utilisateur_execution AS compte_execution,
            t.tac_taux_pannes AS taux_pannes,
            t.tac_active AS tac_active,
            t.tac_possibilite_replanification AS possibilite,
            t.tac_relance_necessaire AS relance,
            t.tac_collecte_automatique AS collecte,
            t.tac_execution_autorisation_maximale AS execution_max,
            r.res_nom AS responsable,
            t.tac_cri_id AS cri_id,
            t.tac_res_id AS res_id,
            se.exe_min_duree AS min_duree,
            se.exe_moyenne_duree AS moyenne_duree,
            se.exe_max_duree AS max_duree
        FROM Tache t
        LEFT JOIN serveur s ON t.tac_ser_id = s.ser_id
        LEFT JOIN responsable r ON t.tac_res_id = r.res_id
        LEFT JOIN criticite c ON t.tac_cri_id = c.cri_id
        LEFT JOIN planification p ON t.tac_id = p.plan_tac_id
        LEFT JOIN statistique_execution se ON t.tac_id = se.exe_tac_id
	    LEFT JOIN Composer cmp ON t.tac_id = cmp.cmp_tac_id
		LEFT JOIN Action a ON cmp.cmp_act_id = a.act_id
    ";

    $params = [];
    $conditions = [];
    
    // Filtrage par serveur
    if ($ser_id) {
        $conditions[] = "t.tac_ser_id = ?";
        $params[] = $ser_id;
    }
    
    // Filtrage par dates
    if ($dateDebut) {
        $conditions[] = "DATE(t.tac_date_creation) >= ?";
        $params[] = $dateDebut;
    }
    
    if ($dateFin) {
        $conditions[] = "DATE(t.tac_date_creation) <= ?";
        $params[] = $dateFin;
    }
    
    // Ajouter les conditions WHERE si nécessaire
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }
    
    $sql .= " GROUP BY t.tac_id ORDER BY t.tac_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function ajouterTache($nom, $description, $ser_id, $cri_id, $tac_remarque, $responsable, $compte_creation, $relance, $replanification, $collecteAuto, $tac_active, $executionMax, $dateDebut, $dateFin, $heureExec, $intervalle, $planification, $action = [])
{
    $pdo = getPDO();

    // Convertir les champs vides en NULL
    $cri_id = ($cri_id == '') ? null : $cri_id;

    // Insertion dans la table Tache
    $sql = "INSERT INTO Tache (
        tac_nom, tac_description, tac_ser_id, tac_cri_id, tac_remarque, tac_res_id,
        tac_compte_utilisateur_creation, tac_compte_utilisateur_execution, tac_taux_pannes,
        tac_date_creation, tac_date_modification,
        tac_relance_necessaire, tac_possibilite_replanification,
        tac_collecte_automatique, tac_active, tac_execution_autorisation_maximale
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, '', '', NOW(), NOW(), ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    $successTache = $stmt->execute([
        $nom,
        $description,
        $ser_id,
        $cri_id,
        $tac_remarque,
        $responsable,
        $compte_creation,
        $relance,
        $replanification,
        $collecteAuto,
        $tac_active,
        $executionMax
    ]);
	if (!empty($dateDebut)) {
		$dateDebut = date('Y-m-d H:i:s', strtotime($dateDebut . ' ' . $heureExec));
	} else {
		$dateDebut = null;
	}
	if (!empty($dateFin)) {
		$dateFin = date('Y-m-d H:i:s', strtotime($dateFin . ' ' . $heureExec));
	} else {
		$dateFin = null;
	}
	if (!empty($heureExec)) {
		$heureExec = date('H:i:s', strtotime($heureExec));
	} else {
		$heureExec = null;
	}

    if ($successTache) {
        $tac_id = $pdo->lastInsertId();

        // Insertion dans Planification
        $sqlPlanif = "INSERT INTO Planification (
            plan_tac_id, plan_date_debut, plan_date_fin, 
            plan_heure_execution, plan_intervalle, plan_repetition
        ) VALUES (?, ?, ?, ?, ?, ?)";

        $stmtPlanif = $pdo->prepare($sqlPlanif);
        $successPlanif = $stmtPlanif->execute([
            $tac_id,
            $dateDebut,
            $dateFin,
            $heureExec,
            $intervalle,
            $planification
        ]);

        // Insertion de l'action (toujours, même si vide)
        $sqlAction = "INSERT INTO Action (
            act_type, act_command, act_argument, act_working_directory,
            act_expediteur, act_destinataire, act_objet, act_texte,
            act_serveur, act_titre, act_message
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmtAction = $pdo->prepare($sqlAction);
        $stmtAction->execute([
            $action['act_type'] ?? null,
            $action['act_command'] ?? null,
            $action['act_argument'] ?? null,
            $action['act_working_directory'] ?? null,
            $action['act_expediteur'] ?? null,
            $action['act_destinataire'] ?? null,
            $action['act_objet'] ?? null,
            $action['act_texte'] ?? null,
            $action['act_serveur'] ?? null,
            $action['act_titre'] ?? null,
            $action['act_message'] ?? null
        ]);

        $act_id = $pdo->lastInsertId();

        // Insertion dans Composer
        $sqlComposer = "INSERT INTO Composer (cmp_tac_id, cmp_act_id) VALUES (?, ?)";
        $stmtComposer = $pdo->prepare($sqlComposer);
        $stmtComposer->execute([$tac_id, $act_id]);

        return $successPlanif;
    }

    return false;
}


function supprimerTache($tac_id) {
    $pdo = getPDO();

    $stmt = $pdo->prepare("SELECT cmp_act_id FROM Composer WHERE cmp_tac_id = ?");
    $stmt->execute([$tac_id]);
    $actions = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $pdo->prepare("DELETE FROM Composer WHERE cmp_tac_id = ?");
    $stmt->execute([$tac_id]);

    if (!empty($actions)) {
        $in = str_repeat('?,', count($actions) - 1) . '?';
        $stmt = $pdo->prepare("DELETE FROM Action WHERE act_id IN ($in)");
        $stmt->execute($actions);
    }

    $tables = [
        'Planification' => 'plan_tac_id',
        'Historique' => 'histo_tac_id',
        'statistique_execution' => 'exe_tac_id',
        'dependance_tache' => 'idTache'
    ];

    foreach ($tables as $table => $column) {
        $stmt = $pdo->prepare("DELETE FROM $table WHERE $column = ?");
        $stmt->execute([$tac_id]);
    }

    $sql = "DELETE FROM Tache WHERE tac_id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$tac_id]);
}



function modifierTache($tac_id, $nom, $description, $ser_id, $planification, $cri_id, $tac_remarque, $responsable, $relance, $replanification, $compteExecution, $tac_active, $autorisation, $dateDebut, $dateFin, $heureExec, $intervalle, $action)
{
    $pdo = getPDO();

    // Mise à jour dans la table Tache
    $sql = "UPDATE Tache SET
                tac_nom = ?,
                tac_description = ?,
                tac_ser_id = ?,
                tac_cri_id = ?,
                tac_remarque = ?,
                tac_res_id = ?,
                tac_relance_necessaire = ?,
                tac_possibilite_replanification = ?,
                tac_compte_utilisateur_execution = ?,
                tac_active = ?,
                tac_execution_autorisation_maximale = ?,
                tac_date_modification = NOW()
            WHERE tac_id = ?";

    $stmt = $pdo->prepare($sql);
    $successTache = $stmt->execute([
        $nom, $description, $ser_id, $cri_id ?: null, $tac_remarque, $responsable ?: null,
        $relance, $replanification, $compteExecution,
        $tac_active, $autorisation, $tac_id
    ]);
	if (!empty($dateDebut)) {
		$dateDebut = date('Y-m-d H:i:s', strtotime($dateDebut . ' ' . $heureExec));
	} else {
		$dateDebut = null;
	}
	if (!empty($dateFin)) {
		$dateFin = date('Y-m-d H:i:s', strtotime($dateFin . ' ' . $heureExec));
	} else {
		$dateFin = null;
	}
	if (!empty($heureExec)) {
		$heureExec = date('H:i:s', strtotime($heureExec));
	} else {
		$heureExec = null;
	}

    // Mise à jour dans Planification
    $sqlPlanif = "UPDATE Planification SET 
        plan_date_debut = ?, 
        plan_date_fin = ?, 
        plan_heure_execution = ?, 
        plan_intervalle = ?, 
        plan_repetition = ?
    WHERE plan_tac_id = ?";

    $stmtPlanif = $pdo->prepare($sqlPlanif);
    $successPlanif = $stmtPlanif->execute([
        $dateDebut,
        $dateFin,
        $heureExec,
        $intervalle,
        $planification,
        $tac_id
    ]);

    // Supprimer l’action existante liée à cette tâche (si elle existe)
    $sqlGetActId = "SELECT cmp_act_id FROM Composer WHERE cmp_tac_id = ?";
    $stmtGetActId = $pdo->prepare($sqlGetActId);
    $stmtGetActId->execute([$tac_id]);
    $oldActId = $stmtGetActId->fetchColumn();

    if ($oldActId) {
        $pdo->prepare("DELETE FROM Action WHERE act_id = ?")->execute([$oldActId]);
        $pdo->prepare("DELETE FROM Composer WHERE cmp_tac_id = ?")->execute([$tac_id]);
    }

    // Insertion de la nouvelle action (même vide)
    $sqlAction = "INSERT INTO Action (
        act_type, act_command, act_argument, act_working_directory,
        act_expediteur, act_destinataire, act_objet, act_texte,
        act_serveur, act_titre, act_message
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmtAction = $pdo->prepare($sqlAction);
    $stmtAction->execute([
        $action['act_type'] ?? '',
        $action['act_command'] ?? null,
        $action['act_argument'] ?? null,
        $action['act_working_directory'] ?? null,
        $action['act_expediteur'] ?? null,
        $action['act_destinataire'] ?? null,
        $action['act_objet'] ?? null,
        $action['act_texte'] ?? null,
        $action['act_serveur'] ?? null,
        $action['act_titre'] ?? null,
        $action['act_message'] ?? null
    ]);

    $act_id = $pdo->lastInsertId();

    // Insertion dans Composer
    $stmtComposer = $pdo->prepare("INSERT INTO Composer (cmp_tac_id, cmp_act_id) VALUES (?, ?)");
    $stmtComposer->execute([$tac_id, $act_id]);

    return $successTache && $successPlanif;
}





