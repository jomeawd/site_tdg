<?php
include 'data.php';

// Traitement des actions POST AVANT toute sortie HTML
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Ajouter une tâche
    if ($action == 'ajouter') {
        $nom = $_POST['nom'] ?? '';
        $description = $_POST['description'] ?? '';
        $ser_id = $_POST['ser_id'] ?? null;
        $cri_id = $_POST['cri_id'] ?? null;
        $tac_remarque = $_POST['tac_remarque'] ?? '';
        $responsable = $_POST['responsable'] ?? '';
        $compte_creation = "utilisateur_" . date('YmdHis');
        $relance = isset($_POST['tac_relance_necessaire']) ? 1 : 0;
        $replanification = isset($_POST['tac_possibilite_replanification']) ? 1 : 0;
        $collecteAuto = isset($_POST['tac_collecte_automatique']) ? 1 : 0;
        $tac_active = 1;
        $execution_max = $_POST['tac_execution_autorisation_maximale'] ?? '';
        
        // Planification
        $planification = $_POST['plan_repetition'] ?? '';
        $dateDebut = $_POST['plan_date_debut'] ?? '';
        $dateFin = $_POST['plan_date_fin'] ?? '';
        $heureExec = $_POST['plan_heure_execution'] ?? '';
        $intervalle = $_POST['plan_intervalle'] ?? '';

        // Action
        $actionData = [
            'act_type' => $_POST['act_type'] ?? null,
            'act_command' => $_POST['act_command'] ?? '',
            'act_argument' => $_POST['act_argument'] ?? null,
            'act_working_directory' => $_POST['act_working_directory'] ?? null,
            'act_expediteur' => $_POST['act_expediteur'] ?? null,
            'act_destinataire' => $_POST['act_destinataire'] ?? null,
            'act_objet' => $_POST['act_objet'] ?? null,
            'act_texte' => $_POST['act_texte'] ?? null,
            'act_titre' => $_POST['act_titre'] ?? null,
            'act_message' => $_POST['act_message'] ?? null
        ];
        
        if (ajouterTache($nom, $description, $ser_id, $cri_id, $tac_remarque, $responsable, $compte_creation, $relance, $replanification, $collecteAuto, $tac_active, $execution_max, $dateDebut, $dateFin, $heureExec, $intervalle, $planification, $actionData)) {
            header('Location: taches.php?msg=add_success');
            exit;
        } else {
            header('Location: taches.php?msg=add_error');
            exit;
        }
    }
    
    // Modifier une tâche
    if ($action == 'modifier') {
        $tac_id = $_POST['tac_id'] ?? null;
        
        // Initialiser les variables par défaut
        $dateDebut = '';
        $dateFin = '';
        $heureExec = '';
        $intervalle = '';
        $actionData = [];
        
        // Récupérer les informations actuelles de la tâche pour vérifier la collecte automatique
        $taches = getTaches(); // Récupérer les tâches ici
        $tacheActuelle = null;
        foreach ($taches as $t) {
            if ($t['id'] == $tac_id) {
                $tacheActuelle = $t;
                break;
            }
        }
        
        if (!$tacheActuelle) {
            header('Location: taches.php?msg=edit_error');
            exit;
        }
        
        // Vérifier si la collecte automatique est activée
        $collecteAuto = $tacheActuelle['collecte'] ?? 0;
        
        if ($collecteAuto == 1) {
            // Si collecte automatique = OUI, seuls certains champs peuvent être modifiés
            $cri_id = $_POST['cri_id'] !== '' ? $_POST['cri_id'] : null;
            $tac_remarque = $_POST['tac_remarque'] ?? '';
            $relance = isset($_POST['tac_relance_necessaire']) ? 1 : 0;
            $replanification = isset($_POST['tac_possibilite_replanification']) ? 1 : 0;
            $responsable = $_POST['res_id'] ?? null;
            
            // Conserver les valeurs actuelles pour les autres champs
            $nom = $tacheActuelle['nom'];
            $description = $tacheActuelle['description'];
            $ser_id = $tacheActuelle['ser_id'];
            $planification = $tacheActuelle['planification'] ?? ''; 
            $compteExecution = $tacheActuelle['compte_execution'];
            $tac_active = $tacheActuelle['tac_active'];
            $autorisation = $tacheActuelle['execution_max'];
            $compteCreation = $tacheActuelle['compte_creation'];
            
            // Conserver les valeurs de planification existantes
            $dateDebut = $tacheActuelle['plan_date_debut'] ?? '';
            $dateFin = $tacheActuelle['plan_date_fin'] ?? '';
            $heureExec = $tacheActuelle['plan_heure_execution'] ?? '';
            $intervalle = $tacheActuelle['plan_intervalle'] ?? '';
            
            // Conserver les données d'action existantes
            $actionData = [
                'act_type' => $tacheActuelle['act_type'] ?? null,
                'act_command' => $tacheActuelle['act_command'] ?? '',
                'act_argument' => $tacheActuelle['act_argument'] ?? null,
                'act_working_directory' => $tacheActuelle['act_working_directory'] ?? null,
                'act_expediteur' => $tacheActuelle['act_expediteur'] ?? null,
                'act_destinataire' => $tacheActuelle['act_destinataire'] ?? null,
                'act_objet' => $tacheActuelle['act_objet'] ?? null,
                'act_texte' => $tacheActuelle['act_texte'] ?? null,
                'act_titre' => $tacheActuelle['act_titre'] ?? null,
                'act_message' => $tacheActuelle['act_message'] ?? null
            ];
            
        } else {
            // Si collecte automatique = NON
            $nom = $_POST['nom'] ?? '';
            $description = $_POST['description'] ?? '';
            $ser_id = $_POST['ser_id'] ?? null;
            $cri_id = $_POST['cri_id'] !== '' ? $_POST['cri_id'] : null;
            $tac_remarque = $_POST['tac_remarque'] ?? '';
            $relance = isset($_POST['tac_relance_necessaire']) ? 1 : 0;
            $replanification = isset($_POST['tac_possibilite_replanification']) ? 1 : 0;
            $compteExecution = $_POST['tac_compte_utilisateur_execution'] ?? '';
            $tac_active = isset($_POST['tac_active']) ? 1 : 0;
            $autorisation = $_POST['tac_execution_autorisation_maximale'] ?? '';
            $responsable = $_POST['res_id'] ?? null;
            $compteCreation = $_POST['tac_compte_utilisateur_creation'] ?? '';
            
            $planification = $_POST['plan_repetition'] ?? '';
            $dateDebut = $_POST['plan_date_debut'] ?? '';
            $dateFin = $_POST['plan_date_fin'] ?? '';
            $heureExec = $_POST['plan_heure_execution'] ?? '';
            $intervalle = $_POST['plan_intervalle'] ?? '';

            // Action
            $actionData = [
                'act_type' => $_POST['act_type'] ?? 'script',
                'act_command' => $_POST['act_command'] ?? '',
                'act_argument' => $_POST['act_argument'] ?? null,
                'act_working_directory' => $_POST['act_working_directory'] ?? null,
                'act_expediteur' => $_POST['act_expediteur'] ?? null,
                'act_destinataire' => $_POST['act_destinataire'] ?? null,
                'act_objet' => $_POST['act_objet'] ?? null,
                'act_texte' => $_POST['act_texte'] ?? null,
                'act_titre' => $_POST['act_titre'] ?? null,
                'act_message' => $_POST['act_message'] ?? null
            ];
        }

        if (modifierTache($tac_id, $nom, $description, $ser_id, $planification, $cri_id, $tac_remarque, $responsable, $relance, $replanification, $compteExecution, $tac_active, $autorisation, $dateDebut, $dateFin, $heureExec, $intervalle, $actionData)) {
            header('Location: taches.php?msg=edit_success');
            exit;
        } else {
            header('Location: taches.php?msg=edit_error');
            exit;
        }
    }
    
    // Supprimer une tâche
    if ($action == 'supprimer') {
        $tac_id = $_POST['tac_id'] ?? null;
        
        if (supprimerTache($tac_id)) {
            header('Location: taches.php?msg=delete_success');
            exit;
        } else {
            header('Location: taches.php?msg=delete_error');
            exit;
        }
    }
}

// APRÈS le traitement POST, récupérer les données pour l'affichage
$dateDebut = $_GET['dateDebut'] ?? null;
$dateFin = $_GET['dateFin'] ?? null;
$ser_id = $_GET['serveur'] ?? null;
$taches = getTaches($dateDebut, $dateFin, $ser_id);

// Récupérer les alertes de messages
$msg = $_GET['msg'] ?? '';
$alertClass = '';
$alertMessage = '';

switch($msg) {
    case 'add_success':
        $alertClass = 'is-success';
        $alertMessage = 'La tâche a été ajoutée avec succès.';
        break;
    case 'add_error':
        $alertClass = 'is-danger';
        $alertMessage = 'Erreur lors de l\'ajout de la tâche.';
        break;
    case 'edit_success':
        $alertClass = 'is-success';
        $alertMessage = 'La tâche a été modifiée avec succès.';
        break;
    case 'edit_error':
        $alertClass = 'is-danger';
        $alertMessage = 'Erreur lors de la modification de la tâche.';
        break;
    case 'delete_success':
        $alertClass = 'is-success';
        $alertMessage = 'La tâche a été supprimée avec succès.';
        break;
    case 'delete_error':
        $alertClass = 'is-danger';
        $alertMessage = 'Erreur lors de la suppression de la tâche.';
        break;
}

// Récupérer les données pour les formulaires
$pdo = getPDO();
$serveurs = $pdo->query("SELECT ser_id, ser_nom FROM Serveur ORDER BY ser_nom")->fetchAll();
$criticites = $pdo->query("SELECT cri_id, cri_libelle FROM Criticite ORDER BY cri_id")->fetchAll();
$responsables = $pdo->query("SELECT res_id, res_nom FROM Responsable ORDER BY res_nom")->fetchAll();

function formatAction($tache) {
    if (empty($tache['act_type'])) {
        return 'Aucune action définie';
    }

    switch ($tache['act_type']) {
        case 'Exec':
            $parts = [];
            if (!empty($tache['act_command'])) $parts[] = "Commande : " . $tache['act_command'];
            if (!empty($tache['act_argument'])) $parts[] = "Arguments : " . $tache['act_argument'];
            if (!empty($tache['act_working_directory'])) $parts[] = "Répertoire : " . $tache['act_working_directory'];
            return "Exécution<br>" . implode('<br>', $parts);

        case 'SendEmail':
            $parts = [];
            if (!empty($tache['act_expediteur'])) $parts[] = "De : " . $tache['act_expediteur'];
            if (!empty($tache['act_destinataire'])) $parts[] = "À : " . $tache['act_destinataire'];
            if (!empty($tache['act_objet'])) $parts[] = "Objet : " . $tache['act_objet'];
            if (!empty($tache['act_texte'])) $parts[] = "Texte : " . $tache['act_texte'];
            return "Email<br>" . implode('<br>', $parts);

        case 'ShowMessage':
            $parts = [];
            if (!empty($tache['act_titre'])) $parts[] = "Titre : " . $tache['act_titre'];
            if (!empty($tache['act_message'])) $parts[] = "Message : " . $tache['act_message'];
            return "Notification<br>" . implode('<br>', $parts);

        default:
            return "Type d'action inconnu";
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tâches</title>
    <link rel="stylesheet" href="/bulma/css/bulma.min.css">
    <link rel="stylesheet" href="/fontawesome/css/all.min.css">
	<link rel="stylesheet" href="style.css">
    <script src="d3/d3.v7.min.js"></script>

</head>
<body>

<!-- Navbar -->
<nav class="navbar is-primary" role="navigation" aria-label="main navigation">
    <div class="navbar-brand">
        <a class="navbar-item" href="#">
            <strong>Tour de guet - Surveillance des tâches planifiées</strong>
        </a>
        <a role="button" class="navbar-burger" aria-label="menu" aria-expanded="false" data-target="mainNavbar">
            <span aria-hidden="true"></span>
            <span aria-hidden="true"></span>
            <span aria-hidden="true"></span>
        </a>
    </div>
    <div id="mainNavbar" class="navbar-menu">
        <div class="navbar-start">
            <a class="navbar-item is-active" href="#"><i class="fas fa-tasks mr-1"></i>Tâches</a>
            <a class="navbar-item" href="serveurs.php"><i class="fas fa-server mr-1"></i>Serveurs</a>
            <a class="navbar-item" href="index.php"><i class="fas fa-calendar-check mr-1"></i>État des planifications</a>
            <a class="navbar-item" href="config.php"><i class="fas fa-cog mr-1"></i>Configuration</a>
        </div>
    </div>
</nav>

<section class="section" style="padding: 1.5rem 1rem;">
    <div class="container is-fluid">
        <h1 class="title">Tâches planifiées</h1>

        <?php if (!empty($alertMessage)): ?>
        <div class="notification <?= $alertClass ?> is-light">
            <button class="delete"></button>
            <?= $alertMessage ?>
        </div>
        <?php endif; ?>

        <!-- Filtres date -->
        <div class="calendar-container box">
            <div class="columns is-mobile is-vcentered">
                <div class="column is-narrow">
                    <h2 class="subtitle">Période :</h2>
                </div>
                <div class="column">
                    <div class="field-body is-flex">
                        <div class="field has-addons">
                            <p class="control is-expanded">
                                <input class="input is-small" type="date" id="startDate">
                            </p>
                            <p class="control">
                                <a class="button is-static is-small">à</a>
                            </p>
                            <p class="control is-expanded">
                                <input class="input is-small" type="date" id="endDate">
                            </p>
                            <p class="control">
                                <button class="button is-primary is-small" id="applyDateFilter">
                                    <span class="icon is-small"><i class="fas fa-filter"></i></span>
                                    <span>Filtrer</span>
                                </button>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
		<!-- Options de tri -->
		<div class="box">
			<div class="columns is-mobile is-vcentered">
				<div class="column is-narrow">
					<h2 class="subtitle">Tri :</h2>
				</div>
				<div class="column">
					<div class="field is-grouped">
						<div class="control">
							<div class="select is-small">
								<select id="sortBy">
									<option value="serveur">Serveur</option>
									<option value="nom">Nom</option>
									<option value="criticite">Criticité</option>
								</select>
							</div>
						</div>
						<div class="control">
							<div class="select is-small">
								<select id="sortOrder">
									<option value="asc">Croissant</option>
									<option value="desc">Décroissant</option>
								</select>
							</div>
						</div>
						<div class="control">
							<button class="button is-primary is-small" id="applySortBtn">
								<span class="icon is-small"><i class="fas fa-sort"></i></span>
								<span>Trier</span>
							</button>
						</div>
					</div>
				</div>
			</div>
		</div>

        <!-- Tableau des tâches -->
        <div class="content-section" id="tasksSection">
            <div class="buttons mb-3">
                <button class="button is-success is-small" id="btnAddTask">
                    <span class="icon is-small"><i class="fas fa-plus"></i></span>
                    <span>Ajouter</span>
                </button>
            </div>

            <div class="table-container">
                <table class="table is-striped is-hoverable is-fullwidth is-narrow" id="tasksTable">
                    <thead>
                        <tr>
                            <th class="col-id">Id</th>
                            <th class="col-description">Description</th>
                            <th class="col-planification">Planification</th>
                            <th class="col-criticite">Criticité</th>
                            <th>Détails</th>
                            <th>Statistiques</th>
                            <th>Exécution</th>
                            <th>Maintenance</th>
                            <th class="col-actions-taches">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($taches as $tache): ?>
                            <?php
                                $criticite = strtolower($tache['criticite'] ?? '');
                                $class = match($criticite) {
                                    'haute' => 'criticite-high',
                                    'moyenne' => 'criticite-medium',
                                    'faible' => 'criticite-low',
                                    default => ''
                                };
                            ?>
                            <tr>
                                <td class="col-id"><?= htmlspecialchars($tache['id'] ?? '') ?></td>
                                
                                <td class="col-description">
									<p><strong>Nom de la tâche : </strong><?= htmlspecialchars($tache['nom']) ?></p>
									<p><strong>Description : </strong><?= htmlspecialchars($tache['description']) ?></p>
									<p><strong>Remarque : </strong><?= htmlspecialchars($tache['tac_remarque']) ?></p>
								</td>
                                <td class="col-planification">
									<p><strong>Serveur : </strong><?= htmlspecialchars($tache['serveur']) ?></p>
									<p><strong>Fréquence : </strong>
										<?= htmlspecialchars($tache['planification']) ?>
										<?= $tache['plan_date_debut'] ? 'du ' . date('d/m/Y', strtotime($tache['plan_date_debut'])) : '' ?>
										<?= $tache['plan_date_fin'] ? ' au ' . date('d/m/Y', strtotime($tache['plan_date_fin'])) : '' ?>
										<?= $tache['plan_heure_execution'] ? ' à ' . date('H:i', strtotime($tache['plan_heure_execution'])) : '' ?>
										<?= $tache['plan_intervalle'] ? ' (toutes les ' . htmlspecialchars($tache['plan_intervalle']) . ')' : '' ?>
									</p>
								</td>
                                <td class="col-criticite <?= $class ?>"><?= htmlspecialchars($tache['criticite']) ?></td>
                                <td class="details-cell">
                                    <p><strong>Date de création : </strong><?= htmlspecialchars(date('Y-m-d', strtotime($tache['date_creation']))) ?></p>
                                    <p><strong>Compte utilisateur de création : </strong><?= htmlspecialchars($tache['compte_creation']) ?></p>
                                    <p><strong>Date de mise à jour : </strong><?= htmlspecialchars(date('Y-m-d', strtotime($tache['date_modification']))) ?></p> 
                                    <p><strong>Collecte automatique : </strong><span class="tag is-<?= $tache['collecte'] ? 'success' : 'danger' ?>"><?= $tache['collecte'] ? 'OUI' : 'NON' ?></span></p>
                                </td>
                                <td class="details-cell">
                                    <p><strong>Taux de pannes : </strong><?= htmlspecialchars($tache['taux_pannes']) ?>%</p>
                                    <p><strong>Durée minimum mensuelle : </strong><?= htmlspecialchars($tache['min_duree']) ?></p>
                                    <p><strong>Durée moyenne mensuelle : </strong><?= htmlspecialchars($tache['moyenne_duree']) ?></p>
                                    <p><strong>Durée maximum mensuelle : </strong><?= htmlspecialchars($tache['max_duree']) ?></p>
                                </td>
                                <td class="details-cell">
                                    <p><strong>Active : </strong><span class="tag is-<?= $tache['tac_active'] ? 'success' : 'danger' ?>"><?= $tache['tac_active'] ? 'OUI' : 'NON' ?></span></p>
                                    <p><strong>Compte d'exécution : </strong><?= htmlspecialchars($tache['compte_execution']) ?></p>
                                    <p><strong>Auth. max : </strong><?= htmlspecialchars($tache['execution_max']) ?></p>
                                    <p><strong>Actions : </strong><?= formatAction($tache) ?></p>
                                </td>
                                <td class="details-cell">
                                    <p><strong>Possibilité de replanification : </strong><span class="tag is-<?= $tache['possibilite'] ? 'success' : 'danger' ?>"><?= $tache['possibilite'] ? 'OUI' : 'NON' ?></span></p>
                                    <p><strong>Relance nécessaire : </strong><span class="tag is-<?= $tache['relance'] ? 'success' : 'danger' ?>"><?= $tache['relance'] ? 'OUI' : 'NON' ?></span></p>
                                    <p><strong>Responsable : </strong><?= htmlspecialchars($tache['responsable']) ?></p>
                                </td>
                                <td class="col-actions-taches">
                                    <div class="buttons are-small is-centered">
                                        <button class="button is-info" title="Historique" onclick="showHistorique(<?= $tache['id'] ?>)"><i class="fas fa-history"></i></button>
                                        <button class="button is-link" title="Dépendances" onclick="showDependances(<?= $tache['id'] ?>)"><i class="fas fa-project-diagram"></i></button>
                                        <button class="button jaune edit-task" 
											title="Modifier" 
											data-id="<?= $tache['id'] ?>" 
											data-nom="<?= htmlspecialchars($tache['nom']) ?>" 
											data-description="<?= htmlspecialchars($tache['description']) ?>" 
											data-tac_remarque="<?= htmlspecialchars($tache['tac_remarque'] ?? '') ?>" 
											data-serveur="<?= $tache['ser_id'] ?? '' ?>"
											data-criticite="<?= $tache['cri_id'] ?? '' ?>"
											data-responsable="<?= $tache['res_id'] ?? '' ?>"
											data-compte-execution="<?= htmlspecialchars($tache['compte_execution'] ?? '') ?>"
											data-compte-creation="<?= htmlspecialchars($tache['compte_creation'] ?? '') ?>"
											data-tac_active="<?= $tache['tac_active'] ?? '0' ?>"
											data-relance="<?= $tache['relance'] ?? '0' ?>"
											data-replanif="<?= $tache['possibilite'] ?? '0' ?>"
											data-autorisation="<?= htmlspecialchars($tache['execution_max'] ?? '') ?>"
											data-collecte="<?= $tache['collecte'] ?? '0' ?>"
											data-plan_repetition="<?= $tache['planification'] ?? '' ?>"
											data-plan_date_debut="<?= htmlspecialchars($tache['plan_date_debut'] ?? '') ?>"
											data-plan_date_fin="<?= htmlspecialchars($tache['plan_date_fin'] ?? '') ?>"
											data-plan_heure_execution="<?= htmlspecialchars($tache['plan_heure_execution'] ?? '') ?>"
											data-plan_intervalle="<?= htmlspecialchars($tache['plan_intervalle'] ?? '') ?>"
											data-act_type="<?= $tache['act_type'] ?? '' ?>"
											data-act_command="<?= htmlspecialchars($tache['act_command'] ?? '') ?>"
											data-act_argument="<?= htmlspecialchars($tache['act_argument'] ?? '') ?>"
											data-act_working_directory="<?= htmlspecialchars($tache['act_working_directory'] ?? '') ?>"
											data-act_expediteur="<?= htmlspecialchars($tache['act_expediteur'] ?? '') ?>"
											data-act_destinataire="<?= htmlspecialchars($tache['act_destinataire'] ?? '') ?>"
											data-act_objet="<?= htmlspecialchars($tache['act_objet'] ?? '') ?>"
											data-act_texte="<?= htmlspecialchars($tache['act_texte'] ?? '') ?>"
											data-act_titre="<?= htmlspecialchars($tache['act_titre'] ?? '') ?>"
											data-act_message="<?= htmlspecialchars($tache['act_message'] ?? '') ?>"
										>
											<i class="fas fa-edit"></i>
										</button>
                                        <button class="button rouge delete-task" title="Supprimer" data-id="<?= $tache['id'] ?>"><i class="fas fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <div class="content has-text-centered">
        <p>
            <strong>Gestion des Tâches</strong> - Version 1.0<br>
            ©Sunroad Equipment.<br>
            2025 - 2027
        </p>
    </div>
</footer>

<!-- Modal d'ajout de tâche -->
<div class="modal" id="addTaskModal">
    <div class="modal-background"></div>
    <div class="modal-card">
        <header class="modal-card-head">
            <p class="modal-card-title">Ajouter une tâche</p>
            <button class="delete close-modal" aria-label="close"></button>
        </header>
        <form id="addTaskForm" method="POST" action="taches.php">
            <input type="hidden" name="action" value="ajouter">
            <section class="modal-card-body">
                
                <!-- Nom -->
                <div class="field">
                    <label class="label">Nom</label>
                    <div class="control">
                        <input class="input" type="text" name="nom" required>
                    </div>
                </div>

                <!-- Description -->
                <div class="field">
                    <label class="label">Description</label>
                    <div class="control">
                        <textarea class="textarea" name="description" required></textarea>
                    </div>
                </div>

                <!-- Serveur -->
                <div class="field">
                    <label class="label">Serveur</label>
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select name="ser_id" required>
                                <option value="">Sélectionnez un serveur</option>
                                <?php foreach ($serveurs as $serveur): ?>
                                    <option value="<?= $serveur['ser_id'] ?>">
                                        <?= htmlspecialchars($serveur['ser_nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Planification -->
                <div class="box">
                    <p class="title is-6">Planification</p>

                    <div class="field">
                        <label class="label">Répétition</label>
                        <div class="control">
                            <div class="select is-fullwidth">
                                <select name="plan_repetition" required>
                                    <option value="">Sélectionnez une répétition</option>
                                    <option value="ponctuel">Ponctuel</option>
                                    <option value="quotidien">Quotidien</option>
                                    <option value="hebdomadaire">Hebdomadaire</option>
                                    <option value="mensuel">Mensuel</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="field">
                        <label class="label">Date de début</label>
                        <div class="control">
                            <input class="input" type="date" name="plan_date_debut" required>
                        </div>
                    </div>

                    <div class="field">
                        <label class="label">Date de fin</label>
                        <div class="control">
                            <input class="input" type="date" name="plan_date_fin">
                        </div>
                    </div>

                    <div class="field">
                        <label class="label">Heure d'exécution</label>
                        <div class="control">
                            <input class="input" type="time" name="plan_heure_execution" required>
                        </div>
                    </div>

                    <div class="field">
                        <label class="label">Intervalle</label>
                        <div class="control">
                            <input class="input" type="number" name="plan_intervalle" min="1">
                        </div>
                    </div>
                </div>

				<!-- Action -->
				<div class="box">
					<p class="title is-6">Action</p>

					<!-- Type d'action -->
					<div class="field">
						<label class="label">Type d'action</label>
						<div class="control">
							<div class="select is-fullwidth">
								<select name="act_type" required>
									<option value="">Sélectionnez un type</option>
									<option value="Exec">Exécution de script</option>
									<option value="SendEmail">Envoyer un e-mail</option>
									<option value="ShowMessage">Afficher un message</option>
								</select>
							</div>
						</div>
					</div>

					<!-- Commande ou Script -->
					<div class="field action-field action-exec">
						<label class="label">Commande / Script</label>
						<div class="control">
							<textarea class="textarea" name="act_command" placeholder="Exemple : /usr/bin/monscript.sh"></textarea>
						</div>
					</div>

					<!-- Argument -->
					<div class="field action-field action-exec">
						<label class="label">Argument</label>
						<div class="control">
							<input class="input" type="text" name="act_argument" placeholder="Exemple : --verbose">
						</div>
					</div>

					<!-- Répertoire de travail -->
					<div class="field action-field action-exec">
						<label class="label">Répertoire de travail</label>
						<div class="control">
							<input class="input" type="text" name="act_working_directory" placeholder="/var/scripts">
						</div>
					</div>

					<!-- Email (si type = SendEmail) -->
					<div class="field action-field action-email">
						<label class="label">Expéditeur</label>
						<div class="control">
							<input class="input" type="email" name="act_expediteur" placeholder="expediteur@example.com">
						</div>
					</div>

					<div class="field action-field action-email">
						<label class="label">Destinataire</label>
						<div class="control">
							<input class="input" type="email" name="act_destinataire" placeholder="destinataire@example.com">
						</div>
					</div>

					<div class="field action-field action-email">
						<label class="label">Objet</label>
						<div class="control">
							<input class="input" type="text" name="act_objet" placeholder="Objet de l'e-mail">
						</div>
					</div>

					<div class="field action-field action-email">
						<label class="label">Contenu texte</label>
						<div class="control">
							<textarea class="textarea" name="act_texte" placeholder="Contenu du message ou corps de l'e-mail"></textarea>
						</div>
					</div>

					<!-- Autres -->


					<div class="field action-field action-message">
						<label class="label">Titre du message</label>
						<div class="control">
							<input class="input" type="text" name="act_titre" placeholder="Titre">
						</div>
					</div>

					<div class="field action-field action-message">
						<label class="label">Message à afficher</label>
						<div class="control">
							<textarea class="textarea" name="act_message" placeholder="Message à afficher"></textarea>
						</div>
					</div>
				</div>


                <!-- Criticité -->
                <div class="field">
                    <label class="label">Criticité</label>
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select name="cri_id">
                                <option value="">Sélectionnez une criticité</option>
                                <?php foreach ($criticites as $criticite): ?>
                                    <option value="<?= $criticite['cri_id'] ?>"><?= htmlspecialchars($criticite['cri_libelle']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Remarque -->
                <div class="field">
                    <label class="label">Remarque</label>
                    <div class="control">
                        <textarea class="textarea" name="tac_remarque"></textarea>
                    </div>
                </div>

                <!-- Responsable -->
                <div class="field">
                    <label class="label">Responsable</label>
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select name="responsable" required>
                                <option value="">Sélectionnez un responsable</option>
                                <?php foreach ($responsables as $r): ?>
                                    <option value="<?= $r['res_id'] ?>"><?= htmlspecialchars($r['res_nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Cases à cocher -->
                <div class="field">
                    <label class="checkbox">
                        <input type="checkbox" name="tac_relance_necessaire">
                        <strong>Relance nécessaire</strong>
                    </label>
                </div>

                <div class="field">
                    <label class="checkbox">
                        <input type="checkbox" name="tac_possibilite_replanification">
                        <strong>Possibilité de replanification</strong>
                    </label>
                </div>

                <div class="field">
                    <label class="checkbox">
                        <input type="checkbox" name="tac_collecte_automatique">
                        <strong>Collecte automatique</strong>
                    </label>
                </div>

            </section>

            <footer class="modal-card-foot is-justify-content-flex-end">
                <button type="submit" class="button is-success">Ajouter</button>
                <button type="button" class="button close-modal">Annuler</button>
            </footer>
        </form>
    </div>
</div>


<!-- Modal de modification de tâche -->
<div class="modal" id="editTaskModal">
    <div class="modal-background"></div>
    <div class="modal-card">
        <header class="modal-card-head">
            <p class="modal-card-title" id="editModalTitle">Modifier la tâche</p>
            <button class="delete close-modal" aria-label="close"></button>
        </header>
        <form id="editTaskForm" method="POST" action="taches.php">
            <input type="hidden" name="action" value="modifier">
            <input type="hidden" name="tac_id" id="editTaskId">
            <section class="modal-card-body">

                <!-- Alerte pour les tâches en collecte automatique -->
                <div class="notification is-warning is-light" id="collecteAutoAlert" style="display: none;">
                    <strong>Attention :</strong> Cette tâche est en collecte automatique. Seuls certains champs peuvent être modifiés : Criticité, Remarque, Relance nécessaire, Replanification et Responsable.
                </div>

                <div class="field" id="editFieldNom">
                    <label class="label">Nom</label>
                    <div class="control">
                        <input class="input" type="text" name="nom" id="editTaskName" required>
                    </div>
                </div>

                <div class="field" id="editFieldDescription">
                    <label class="label">Description</label>
                    <div class="control">
                        <textarea class="textarea" name="description" id="editTaskDescription"></textarea>
                    </div>
                </div>

                <div class="field" id="editFieldServeur">
                    <label class="label">Serveur</label>
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select name="ser_id" id="editTaskServer" required>
                                <?php foreach ($serveurs as $serveur): ?>
                                    <option value="<?= $serveur['ser_id'] ?>"><?= htmlspecialchars($serveur['ser_nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

				
                <div class="field">
                    <label class="label">Criticité</label>
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select name="cri_id" id="editTaskCriticite">
                                <option value="">Sélectionnez une criticité</option>
                                <?php foreach ($criticites as $criticite): ?>
                                    <option value="<?= $criticite['cri_id'] ?>"><?= htmlspecialchars($criticite['cri_libelle']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="field">
                    <label class="label">Responsable</label>
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select name="res_id" id="editTaskResponsable">
                                <option value="">Sélectionnez un responsable</option>
                                <?php foreach ($responsables as $resp): ?>
                                    <option value="<?= $resp['res_id'] ?>"><?= htmlspecialchars($resp['res_nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="field" id="editFieldCompteCreation">
                    <label class="label">Compte utilisateur (création)</label>
                    <div class="control">
                        <input class="input" type="text" name="tac_compte_utilisateur_creation" id="editTaskCompteCreation">
                    </div>
                </div>

                <div class="field" id="editFieldCompteExecution">
                    <label class="label">Compte utilisateur (exécution)</label>
                    <div class="control">
                        <input class="input" type="text" name="tac_compte_utilisateur_execution" id="editTaskCompteExecution">
                    </div>
                </div>

                <div class="field">
                    <label class="label">Remarque</label>
                    <div class="control">
                        <textarea class="textarea" name="tac_remarque" id="editTaskRemark"></textarea>
                    </div>
                </div>
				
                <div class="field" id="editFieldAutorisation">
                    <label class="label">Autorisation maximale d'exécution</label>
                    <div class="control">
                        <input class="input" type="text" name="tac_execution_autorisation_maximale" id="editTaskAutorisation">
                    </div>
                </div>
				<!-- Planification -->
				<div class="box" id="editFiledPlanificationBox">
					<p class="title is-6">Planification</p>

					<div class="field">
						<label class="label">Répétition</label>
						<div class="control">
							<div class="select is-fullwidth">
								<select name="plan_repetition" id="editPlanRepetition">
									<option value="">Sélectionnez une répétition</option>
									<option value="ponctuel">Ponctuel</option>
									<option value="quotidien">Quotidien</option>
									<option value="hebdomadaire">Hebdomadaire</option>
									<option value="mensuel">Mensuel</option>
								</select>
							</div>
						</div>
					</div>

					<div class="field">
						<label class="label">Date de début</label>
						<div class="control">
							<input class="input" type="date" name="plan_date_debut" id="editPlanDateDebut">
						</div>
					</div>

					<div class="field">
						<label class="label">Date de fin</label>
						<div class="control">
							<input class="input" type="date" name="plan_date_fin" id="editPlanDateFin">
						</div>
					</div>

					<div class="field">
						<label class="label">Heure d'exécution</label>
						<div class="control">
							<input class="input" type="time" name="plan_heure_execution" id="editPlanHeureExecution">
						</div>
					</div>

					<div class="field">
						<label class="label">Intervalle</label>
						<div class="control">
							<input class="input" type="number" name="plan_intervalle" id="editPlanIntervalle" min="1">
						</div>
					</div>
				</div>

				<!-- Action -->
				<div class="box" id="editFieldActionBox">
					<p class="title is-6">Action</p>

					<div class="field">
						<label class="label">Type d'action</label>
						<div class="control">
							<div class="select is-fullwidth">
								<select name="act_type" id="editActType">
									<option value="">Sélectionnez un type</option>
									<option value="Exec">Exécution de script</option>
									<option value="SendEmail">Envoyer un e-mail</option>
									<option value="ShowMessage">Afficher un message</option>
								</select>
							</div>
						</div>
					</div>

					<div class="field action-field action-exec">
						<label class="label">Commande / Script</label>
						<div class="control">
							<textarea class="textarea" name="act_command" id="editActCommand" placeholder="Exemple : /usr/bin/monscript.sh"></textarea>
						</div>
					</div>

					<div class="field action-field action-exec">
						<label class="label">Argument</label>
						<div class="control">
							<input class="input" type="text" name="act_argument" id="editActArgument" placeholder="Exemple : --verbose">
						</div>
					</div>

					<div class="field action-field action-exec">
						<label class="label">Répertoire de travail</label>
						<div class="control">
							<input class="input" type="text" name="act_working_directory" id="editActWorkingDirectory" placeholder="/var/scripts">
						</div>
					</div>

					<div class="field action-field action-email">
						<label class="label">Expéditeur</label>
						<div class="control">
							<input class="input" type="email" name="act_expediteur" id="editActExpediteur" placeholder="expediteur@example.com">
						</div>
					</div>

					<div class="field action-field action-email">
						<label class="label">Destinataire</label>
						<div class="control">
							<input class="input" type="email" name="act_destinataire" id="editActDestinataire" placeholder="destinataire@example.com">
						</div>
					</div>

					<div class="field action-field action-email">
						<label class="label">Objet</label>
						<div class="control">
							<input class="input" type="text" name="act_objet" id="editActObjet" placeholder="Objet de l'e-mail">
						</div>
					</div>

					<div class="field action-field action-email">
						<label class="label">Contenu texte</label>
						<div class="control">
							<textarea class="textarea" name="act_texte" id="editActTexte" placeholder="Contenu du message ou corps de l'e-mail"></textarea>
						</div>
					</div>



					<div class="field action-field action-message">
						<label class="label">Titre du message</label>
						<div class="control">
							<input class="input" type="text" name="act_titre" id="editActTitre" placeholder="Titre">
						</div>
					</div>

					<div class="field action-field action-message">
						<label class="label">Message à afficher</label>
						<div class="control">
							<textarea class="textarea" name="act_message" id="editActMessage" placeholder="Message à afficher"></textarea>
						</div>
					</div>
				</div>

                <!-- Champs booléens -->
                <div class="field is-grouped is-grouped-multiline">
					<div class="control">
						<label class="checkbox">
							<input type="checkbox" name="tac_relance_necessaire" value="1" id="editTaskRelance">
							<strong>Relance nécessaire</strong>
						</label>
					</div>

					<div class="control">
						<label class="checkbox">
							<input type="checkbox" name="tac_possibilite_replanification" value="1" id="editTaskReplanif">
							<strong>Replanification possible</strong>
						</label>
					</div>

					<div class="control" id="editFieldtac_active">
						<label class="checkbox">
							<input type="checkbox" name="tac_active" value="1" id="editTasktac_active">
							<strong>Active</strong>
						</label>
					</div>
                </div>

            </section>
            <footer class="modal-card-foot is-justify-content-flex-end">
                <button type="submit" class="button jaune">Modifier</button>
                <button type="button" class="button close-modal">Annuler</button>
            </footer>
        </form>
    </div>
</div>
<!-- Modal de suppression -->
<div class="modal" id="deleteTaskModal">
    <div class="modal-background"></div>
    <div class="modal-card">
        <header class="modal-card-head">
            <p class="modal-card-title">Confirmer la suppression</p>
            <button class="delete close-modal" aria-label="close"></button>
        </header>
        <form id="deleteTaskForm" method="POST" action="taches.php">
            <input type="hidden" name="action" value="supprimer">
            <input type="hidden" name="tac_id" id="deleteTaskId">
            <section class="modal-card-body">
                <p>Êtes-vous sûr de vouloir supprimer cette tâche ? Cette action est irréversible.</p>
            </section>
            <footer class="modal-card-foot is-justify-content-flex-end">
                <button type="submit" class="button is-danger">Supprimer</button>
                <button type="button" class="button close-modal">Annuler</button>
            </footer>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const navbarBurgers = document.querySelectorAll('.navbar-burger');
    navbarBurgers.forEach(el => {
        el.addEventListener('click', () => {
            const target = document.getElementById(el.dataset.target);
            el.classList.toggle('is-active');
            target.classList.toggle('is-active');
        });
    });

    // Fermer les notifications
    document.querySelectorAll('.notification .delete').forEach(deleteButton => {
        deleteButton.addEventListener('click', () => {
            deleteButton.parentNode.remove();
        });
    });

    // Ouvrir modal d'ajout de tâche
    document.getElementById("btnAddTask").addEventListener("click", () => {
        document.getElementById("addTaskModal").classList.add("is-active");
    });

    // Fonction pour gérer l'affichage des champs selon la collecte automatique
    function toggleFieldsBasedOnCollecte(collecteAuto) {
        const fieldsToHide = [
            'editFieldNom',
            'editFieldDescription', 
            'editFieldServeur',
            'editFieldCompteCreation',
            'editFieldCompteExecution',
            'editFieldAutorisation',
            'editFieldtac_active',
			'editFiledPlanificationBox',
			'editFieldActionBox'
        ];
        
        const alert = document.getElementById('collecteAutoAlert');
        
        if (collecteAuto == '1') {
            // Collecte automatique = OUI : masquer certains champs
            fieldsToHide.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.style.display = 'none';
                }
            });
            alert.style.display = 'block';
        } else {
            // Collecte automatique = NON : afficher tous les champs
            fieldsToHide.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.style.display = 'block';
                }
            });
            alert.style.display = 'none';
        }
    }

	document.querySelectorAll(".edit-task").forEach(button => {
		button.addEventListener("click", () => {
			const id = button.getAttribute("data-id");
			const nom = button.getAttribute("data-nom");
			const description = button.getAttribute("data-description");
			const tac_remarque = button.getAttribute("data-tac_remarque");
			const serveur = button.getAttribute("data-serveur");
			const criticite = button.getAttribute("data-criticite");
			const responsable = button.getAttribute("data-responsable");
			// Planification
			const plan_repetition = button.getAttribute("data-plan_repetition");
			const plan_date_debut = button.getAttribute("data-plan_date_debut");
			const plan_date_fin = button.getAttribute("data-plan_date_fin");
			const plan_heure_execution = button.getAttribute("data-plan_heure_execution");
			const plan_intervalle = button.getAttribute("data-plan_intervalle");
			// Action
			const act_type = button.getAttribute("data-act_type");
			const act_command = button.getAttribute("data-act_command");
			const act_argument = button.getAttribute("data-act_argument");
			const act_working_directory = button.getAttribute("data-act_working_directory");
			const act_expediteur = button.getAttribute("data-act_expediteur");
			const act_destinataire = button.getAttribute("data-act_destinataire");
			const act_objet = button.getAttribute("data-act_objet");
			const act_texte = button.getAttribute("data-act_texte");
			const act_titre = button.getAttribute("data-act_titre");
			const act_message = button.getAttribute("data-act_message");
			const compteExecution = button.getAttribute("data-compte-execution");
			const compteCreation = button.getAttribute("data-compte-creation");
			const tac_active = button.getAttribute("data-tac_active");
			const relance = button.getAttribute("data-relance");
			const replanif = button.getAttribute("data-replanif");
			const autorisation = button.getAttribute("data-autorisation");
			const collecte = button.getAttribute("data-collecte");

			// Remplir les champs du formulaire
			document.getElementById("editTaskId").value = id;
			document.getElementById("editTaskName").value = nom;
			document.getElementById("editTaskDescription").value = description;
			document.getElementById("editTaskRemark").value = tac_remarque || '';
			document.getElementById("editTaskServer").value = serveur || '';
			document.getElementById("editTaskCriticite").value = criticite || '';
			document.getElementById("editTaskResponsable").value = responsable || '';
			document.getElementById("editTaskCompteExecution").value = compteExecution || '';
			document.getElementById("editTaskCompteCreation").value = compteCreation || '';
			document.getElementById("editTaskAutorisation").value = autorisation || '';
			document.getElementById("editTasktac_active").checked = (tac_active == '1');
			document.getElementById("editTaskRelance").checked = (relance == '1');
			document.getElementById("editTaskReplanif").checked = (replanif == '1');

			// Planification
			document.getElementById("editPlanRepetition").value = plan_repetition || '';
			document.getElementById("editPlanDateDebut").value = plan_date_debut || '';
			document.getElementById("editPlanDateFin").value = plan_date_fin || '';
			document.getElementById("editPlanHeureExecution").value = plan_heure_execution || '';
			document.getElementById("editPlanIntervalle").value = plan_intervalle || '';

			// Action
			document.getElementById("editActType").value = act_type || '';
			document.getElementById("editActCommand").value = act_command || '';
			document.getElementById("editActArgument").value = act_argument || '';
			document.getElementById("editActWorkingDirectory").value = act_working_directory || '';
			document.getElementById("editActExpediteur").value = act_expediteur || '';
			document.getElementById("editActDestinataire").value = act_destinataire || '';
			document.getElementById("editActObjet").value = act_objet || '';
			document.getElementById("editActTexte").value = act_texte || '';
			document.getElementById("editActTitre").value = act_titre || '';
			document.getElementById("editActMessage").value = act_message || '';

			document.getElementById("editModalTitle").textContent = `Modifier la tâche : ${id}`;

			// Gérer l'affichage des champs selon la collecte automatique
			toggleFieldsBasedOnCollecte(collecte);

			document.getElementById("editTaskModal").classList.add("is-active");
		});
	});


    // Ouvrir modal de suppression
    document.querySelectorAll(".delete-task").forEach(button => {
        button.addEventListener("click", () => {
            const id = button.getAttribute("data-id");
            document.getElementById("deleteTaskId").value = id;
            document.getElementById("deleteTaskModal").classList.add("is-active");
        });
    });
    
    // Filtrage par période
    document.getElementById("applyDateFilter").addEventListener("click", () => {
        const startDate = document.getElementById("startDate").value;
        const endDate = document.getElementById("endDate").value;
        
        if (!startDate && !endDate) {
            alert("Veuillez sélectionner au moins une date de début ou de fin.");
            return;
        }
        
        if (startDate && endDate && startDate > endDate) {
            alert("La date de début doit être antérieure à la date de fin.");
            return;
        }
        
        // Construire l'URL avec les paramètres de date
        let url = "taches.php?";
        const params = [];
        
        if (startDate) {
            params.push(`dateDebut=${encodeURIComponent(startDate)}`);
        }
        
        if (endDate) {
            params.push(`dateFin=${encodeURIComponent(endDate)}`);
        }
        
        url += params.join('&');
        
        // Rediriger vers la page avec le filtre appliqué
        window.location.href = url;
    });

    // Bouton pour réinitialiser le filtre
    const resetFilterBtn = document.createElement('button');
    resetFilterBtn.className = 'button is-light is-small';
    resetFilterBtn.innerHTML = '<span class="icon is-small"><i class="fas fa-times"></i></span><span>Réinitialiser</span>';
    resetFilterBtn.addEventListener('click', () => {
        window.location.href = 'taches.php';
    });
    
    // Ajouter le bouton de réinitialisation après le bouton de filtrage
    document.getElementById("applyDateFilter").parentNode.appendChild(resetFilterBtn);

    // Pré-remplir les champs de date si des paramètres sont présents dans l'URL
    const urlParams = new URLSearchParams(window.location.search);
    const dateDebut = urlParams.get('dateDebut');
    const dateFin = urlParams.get('dateFin');
    
    if (dateDebut) {
        document.getElementById("startDate").value = dateDebut;
    }
    
    if (dateFin) {
        document.getElementById("endDate").value = dateFin;
    }

    // Fermer les modals
    document.querySelectorAll(".close-modal").forEach(button => {
        button.addEventListener("click", (e) => {
            e.preventDefault();
            document.querySelectorAll(".modal").forEach(modal => {
                modal.classList.remove("is-active");
            });
        });
    });

    // Fonctions temporaires pour les autres boutons
    window.showHistorique = function(taskId) {
        alert(`Affichage de l'historique pour la tâche ID: ${taskId}.`);
    };
    
    window.showDependances = function(taskId) {
        alert(`Affichage des dépendances pour la tâche ID: ${taskId}.`);
    };
	// Fonction de tri des lignes du tableau
	function sortTable(sortBy, sortOrder) {
		const tbody = document.querySelector('#tasksTable tbody');
		const rows = Array.from(tbody.querySelectorAll('tr'));
		
		rows.sort((a, b) => {
			let valueA, valueB;
			
			if (sortBy == 'nom') {
				// Le nom est dans la colonne description, première ligne du paragraphe
				const descCell = a.querySelector('.col-description');
				const descCellB = b.querySelector('.col-description');
				if (descCell && descCellB) {
					// Extraire le nom de la tâche depuis "Nom de la tâche : ..."
					const nomTextA = descCell.querySelector('p:first-child').textContent;
					const nomTextB = descCellB.querySelector('p:first-child').textContent;
					valueA = nomTextA.replace('Nom de la tâche : ', '').trim().toLowerCase();
					valueB = nomTextB.replace('Nom de la tâche : ', '').trim().toLowerCase();
				} else {
					valueA = '';
					valueB = '';
				}
			} else if (sortBy == 'serveur') {
				// Le serveur est dans la colonne planification, première ligne du paragraphe
				const planCell = a.querySelector('.col-planification');
				const planCellB = b.querySelector('.col-planification');
				if (planCell && planCellB) {
					// Extraire le serveur depuis "Serveur : ..."
					const serveurTextA = planCell.querySelector('p:first-child').textContent;
					const serveurTextB = planCellB.querySelector('p:first-child').textContent;
					valueA = serveurTextA.replace('Serveur : ', '').trim().toLowerCase();
					valueB = serveurTextB.replace('Serveur : ', '').trim().toLowerCase();
				} else {
					valueA = '';
					valueB = '';
				}
			} else if (sortBy == 'criticite') {
				const critCell = a.querySelector('.col-criticite');
				const critCellB = b.querySelector('.col-criticite');
				if (critCell && critCellB) {
					valueA = critCell.textContent.trim().toLowerCase();
					valueB = critCellB.textContent.trim().toLowerCase();
					
					// Ordre spécial pour la criticité : Haute > Moyenne > Faible
					const criticiteOrder = { 'haute': 3, 'moyenne': 2, 'faible': 1 };
					valueA = criticiteOrder[valueA] || 0;
					valueB = criticiteOrder[valueB] || 0;
				} else {
					valueA = 0;
					valueB = 0;
				}
			}
			
			if (sortOrder == 'desc') {
				return valueB > valueA ? 1 : valueB < valueA ? -1 : 0;
			} else {
				return valueA > valueB ? 1 : valueA < valueB ? -1 : 0;
			}
		});
		
		// Réorganiser les lignes dans le tableau
		rows.forEach(row => tbody.appendChild(row));
	}

	// Gestionnaire pour le bouton de tri
	document.getElementById("applySortBtn").addEventListener("click", () => {
		const sortBy = document.getElementById("sortBy").value;
		const sortOrder = document.getElementById("sortOrder").value;
		sortTable(sortBy, sortOrder);
	});

	// Fonction générique pour mettre à jour la visibilité des champs d'action selon le type sélectionné
	function updateFieldsVisibility(container, actionType) {
		const allFields = container.querySelectorAll('.action-field');
		allFields.forEach(field => field.style.display = 'none');

		const typeClassMap = {
			Exec: 'exec',
			SendEmail: 'email',
			ShowMessage: 'message'
		};

		const typeClass = typeClassMap[actionType];
		if (typeClass) {
			const matchingFields = container.querySelectorAll(`.action-${typeClass}`);
			matchingFields.forEach(el => el.style.display = '');
		}
	}


	function initActionTypeToggle(container) {
		const select = container.querySelector('select[name="act_type"]');
		if (!select) return;

		const handleChange = () => {
			updateFieldsVisibility(container, select.value);
		};

		select.addEventListener('change', handleChange);
		handleChange();
	}

	// Initialisation pour la page principale (hors modal)
	const globalSelect = document.querySelector('select[name="act_type"]');
	if (globalSelect) {
		globalSelect.addEventListener('change', () => {
			updateFieldsVisibility(document, globalSelect.value);
		});
		updateFieldsVisibility(document, globalSelect.value);
	}

	initActionTypeToggle(document.getElementById('editTaskModal'));

	sortTable('serveur', 'asc');



});
</script>

</body>
</html>