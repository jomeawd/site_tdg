<?php
include 'data.php';

// Fonction pour récupérer les serveurs
function getServeurs() {
    $pdo = getPDO();
    $query = "
        SELECT 
            s.ser_id,
            s.ser_nom,
            s.ser_description,
            s.ser_remarque,
            s.ser_periode_arret,
            s.ser_collect_automatique,
            s.ser_date_creation,
            s.ser_date_modification,
            r.res_nom as responsable,
            f.fonc_nom as fonction,
            s.ser_res_id,
            s.ser_fonc_id
        FROM Serveur s
        LEFT JOIN Responsable r ON s.ser_res_id = r.res_id
        LEFT JOIN Fonction f ON s.ser_fonc_id = f.fonc_id
        ORDER BY s.ser_nom
    ";
    
    return $pdo->query($query)->fetchAll();
}

// Fonction pour ajouter un serveur
function ajouterServeur($nom, $ser_description, $ser_remarque, $ser_periode_arret, $collectAuto, $res_id, $fonc_id) {
    $pdo = getPDO();
    $ser_date_creation = date('Y-m-d H:i:s');
    
    $query = "INSERT INTO Serveur (ser_nom, ser_description, ser_remarque, ser_periode_arret, ser_collect_automatique, ser_date_creation, ser_date_modification, ser_res_id, ser_fonc_id) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($query);
    return $stmt->execute([$nom, $ser_description, $ser_remarque, $ser_periode_arret, $collectAuto, $ser_date_creation, $ser_date_creation, $res_id, $fonc_id]);
}

// Fonction pour modifier un serveur
function modifierServeur($ser_id, $nom, $ser_description, $ser_remarque, $ser_periode_arret, $collectAuto, $res_id, $fonc_id) {
    $pdo = getPDO();
    $ser_date_modification = date('Y-m-d H:i:s');
    
    $query = "UPDATE Serveur SET ser_nom = ?, ser_description = ?, ser_remarque = ?, ser_periode_arret = ?, ser_collect_automatique = ?, ser_date_modification = ?, ser_res_id = ?, ser_fonc_id = ? WHERE ser_id = ?";
    
    $stmt = $pdo->prepare($query);
    return $stmt->execute([$nom, $ser_description, $ser_remarque, $ser_periode_arret, $collectAuto, $ser_date_modification, $res_id, $fonc_id, $ser_id]);
}

// Fonction pour supprimer un serveur
function supprimerServeur($ser_id) {
    $pdo = getPDO();
    
    $checkQuery = "SELECT COUNT(*) FROM Tache WHERE tac_ser_id = ?";
    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->execute([$ser_id]);
    
    if ($checkStmt->fetchColumn() > 0) {
        supprimerTachesServeur($ser_id);
    }
    
    $query = "DELETE FROM Serveur WHERE ser_id = ?";
    $stmt = $pdo->prepare($query);
    return $stmt->execute([$ser_id]);
}

function peutSupprimerServeur($ser_id) {
    try {
        $pdo = getPDO();
        $query = "SELECT ser_collect_automatique FROM serveur WHERE ser_id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$ser_id]);

        $collecteAuto = $stmt->fetchColumn();

        if ($collecteAuto == 1) {
            return false;
        } else {
            return true;
        }

    } catch (PDOException $e) {
        error_log("Erreur PDO dans peutSupprimerServeur: " . $e->getMessage());
        return false; 
    }
}

function supprimerTachesServeur($ser_id) {
    $pdo = getPDO();

    try {
        $stmt = $pdo->prepare("SELECT tac_id FROM Tache WHERE tac_ser_id = ?");
        $stmt->execute([$ser_id]);
        $taches = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($taches as $tac_id) {
            supprimerTache($tac_id);
        }

        return true;
    } catch (PDOException $e) {
        error_log("Erreur PDO dans supprimerTachesServeur: " . $e->getMessage());
        echo "Erreur PDO: " . $e->getMessage(); // pour debug temporaire
        return false;
    }
}


function nombreTachesServeur($ser_id) {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Tache WHERE tac_ser_id = ?");
        $stmt->execute([$ser_id]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Erreur PDO dans nombreTachesServeur: " . $e->getMessage());
        return 0;
    }
}

