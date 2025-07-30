<?php
include 'data.php';

function ajouterCriticite($nom, $description) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO Criticite (cri_libelle, cri_description) VALUES (?, ?)");
    return $stmt->execute([$nom, $description]);
}

function modifierCriticite($id, $nom, $description) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE Criticite SET cri_libelle = ?, cri_description = ? WHERE cri_id = ?");
    return $stmt->execute([$nom, $description, $id]);
}

function supprimerCriticite($id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM Criticite WHERE cri_id = ?");
    return $stmt->execute([$id]);
}

function peutSupprimerCriticite($cri_id) {
    try {
        $pdo = getPDO();
        $query = "SELECT COUNT(*) FROM Tache WHERE tac_cri_id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$cri_id]);
        return $stmt->fetchColumn() == 0;
    } catch (PDOException $e) {
        error_log("Erreur PDO dans peutSupprimerCriticite: " . $e->getMessage());
        return false;
    }
}

function ajouterResponsable($nom) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO Responsable (res_nom) VALUES (?)");
    return $stmt->execute([$nom]);
}

function modifierResponsable($id, $nom) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE Responsable SET res_nom = ? WHERE res_id = ?");
    return $stmt->execute([$nom, $id]);
}

function supprimerResponsable($id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM Responsable WHERE res_id = ?");
    return $stmt->execute([$id]);
}

function peutSupprimerResponsable($res_id) {
    try {
        $pdo = getPDO();
        $query = "SELECT COUNT(*) FROM Tache WHERE tac_res_id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$res_id]);
        return $stmt->fetchColumn() == 0;
    } catch (PDOException $e) {
        error_log("Erreur PDO dans peutSupprimerResponsable: " . $e->getMessage());
        return false;
    }
}

function ajouterFonction($nom) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO Fonction (fonc_nom) VALUES (?)");
    return $stmt->execute([$nom]);
}

function modifierFonction($id, $nom) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE Fonction SET fonc_nom = ? WHERE fonc_id = ?");
    return $stmt->execute([$nom, $id]);
}

function supprimerFonction($id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM Fonction WHERE fonc_id = ?");
    return $stmt->execute([$id]);
}
function peutSupprimerFonctions($fonc_id) {
    try {
        $pdo = getPDO();
        $query = "SELECT COUNT(*) FROM serveur WHERE ser_fonc_id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$fonc_id]);
        return $stmt->fetchColumn() == 0;
    } catch (PDOException $e) {
        error_log("Erreur PDO dans peutSupprimerFonctions: " . $e->getMessage());
        return false;
    }
}
