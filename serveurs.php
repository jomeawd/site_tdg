<?php 
include 'dataServ.php';
$serveurs = getServeurs();

// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Ajouter un serveur
    if ($action == 'ajouter') {
        $nom = $_POST['nom'] ?? '';
        $ser_description = $_POST['ser_description'] ?? '';
        $ser_remarque = $_POST['ser_remarque'] ?? '';
        $ser_periode_arret = $_POST['ser_periode_arret'] ?? '';
        $collectAuto = isset($_POST['ser_collect_automatique']) ? 1 : 0;
        $res_id = $_POST['res_id'] ?? null;
        $fonc_id = $_POST['fonc_id'] ?? null;
        
        if (ajouterServeur($nom, $ser_description, $ser_remarque, $ser_periode_arret, $collectAuto, $res_id, $fonc_id)) {
            header('Location: serveurs.php?msg=add_success');
            exit;
        } else {
            header('Location: serveurs.php?msg=add_error');
            exit;
        }
    }
    
	// Modifier un serveur
	if ($action == 'modifier') {
		$ser_id = $_POST['ser_id'] ?? null;
		
		// Récupérer les informations actuelles du serveur pour vérifier la collecte automatique
		$serveurActuel = null;
		foreach ($serveurs as $s) {
			if ($s['ser_id'] == $ser_id) {
				$serveurActuel = $s;
				break;
			}
		}
		
		if (!$serveurActuel) {
			header('Location: serveurs.php?msg=edit_error');
			exit;
		}
		
		// Vérifier si la collecte automatique est activée
		$collecteAuto = $serveurActuel['ser_collect_automatique'] ?? 0;
		
		if ($collecteAuto == 1) {
			// Si collecte automatique = OUI, seuls certains champs peuvent être modifiés
			$ser_remarque = $_POST['ser_remarque'] ?? '';
			$res_id = $_POST['res_id'] ?? null;
			$fonc_id = $_POST['fonc_id'] ?? null;
			
			// Conserver les valeurs actuelles pour les autres champs
			$nom = $serveurActuel['ser_nom'];
			$ser_description = $serveurActuel['ser_description'];
			$ser_periode_arret = $serveurActuel['ser_periode_arret'];

			// Debug pour vérifier les valeurs
			error_log("Collecte auto - Remarque: " . $ser_remarque . ", Responsable: " . $res_id);
			
		} else {
			// Si collecte automatique = NON, tous les champs peuvent être modifiés
			$nom = $_POST['nom'] ?? '';
			$ser_description = $_POST['ser_description'] ?? '';
			$ser_remarque = $_POST['ser_remarque'] ?? '';
			$ser_periode_arret = $_POST['ser_periode_arret'] ?? '';
			$res_id = $_POST['res_id'] ?? null;
			$fonc_id = $_POST['fonc_id'] ?? null;
			
			// Debug pour vérifier les valeurs
			error_log("Collecte manuelle - Nom: " . $nom . ", Description: " . $ser_description);
		}
		
		if (modifierServeur($ser_id, $nom, $ser_description, $ser_remarque, $ser_periode_arret, $collecteAuto, $res_id, $fonc_id)) {
			header('Location: serveurs.php?msg=edit_success');
			exit;
		} else {
			header('Location: serveurs.php?msg=edit_error');
			exit;
		}
	}
    
    // Supprimer un serveur
    if ($action == 'supprimer') {
        $ser_id = $_POST['ser_id'] ?? null;
        
        if (supprimerServeur($ser_id)) {
            header('Location: serveurs.php?msg=delete_success');
            exit;
        } else {
            header('Location: serveurs.php?msg=delete_error');
            exit;
        }
    }
}

// Récupérer les alertes de messages
$msg = $_GET['msg'] ?? '';
$alertClass = '';
$alertMessage = '';

switch($msg) {
    case 'add_success':
        $alertClass = 'is-success';
        $alertMessage = 'Le serveur a été ajouté avec succès.';
        break;
    case 'add_error':
        $alertClass = 'is-danger';
        $alertMessage = 'Erreur lors de l\'ajout du serveur.';
        break;
    case 'edit_success':
        $alertClass = 'is-success';
        $alertMessage = 'Le serveur a été modifié avec succès.';
        break;
    case 'edit_error':
        $alertClass = 'is-danger';
        $alertMessage = 'Erreur lors de la modification du serveur.';
        break;
    case 'delete_success':
        $alertClass = 'is-success';
        $alertMessage = 'Le serveur a été supprimé avec succès.';
        break;
    case 'delete_error':
        $alertClass = 'is-danger';
        $alertMessage = 'Erreur lors de la suppression du serveur. Vérifiez qu\'aucune tâche en collecte automatique n\'est associée.';
        break;
}

// Récupérer les données pour les formulaires
$pdo = getPDO();
$responsables = $pdo->query("SELECT res_id, res_nom FROM Responsable ORDER BY res_nom")->fetchAll();
$fonctions = $pdo->query("SELECT fonc_id, fonc_nom FROM Fonction ORDER BY fonc_nom")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Serveurs</title>
    <link rel="stylesheet" href="/bulma/css/bulma.min.css">
    <link rel="stylesheet" href="/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar is-primary" role="navigation" aria-label="main navigation">
    <div class="navbar-brand">
        <a class="navbar-item" href="taches.php">
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
            <a class="navbar-item" href="taches.php"><i class="fas fa-tasks mr-1"></i>Tâches</a>
            <a class="navbar-item is-active" href="#"><i class="fas fa-server mr-1"></i>Serveurs</a>
            <a class="navbar-item" href="index.php"><i class="fas fa-calendar-check mr-1"></i>État des planifications</a>
            <a class="navbar-item" href="config.php"><i class="fas fa-cog mr-1"></i>Configuration</a>
        </div>
    </div>
</nav>

<section class="section" style="padding: 1.5rem 1rem;">
    <div class="container is-fluid">
        <h1 class="title">Gestion des serveurs</h1>

        <?php if (!empty($alertMessage)): ?>
        <div class="notification <?= $alertClass ?> is-light">
            <button class="delete"></button>
            <?= $alertMessage ?>
        </div>
        <?php endif; ?>

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
                                    <option value="nom">Nom</option>
                                    <option value="fonction">Fonction</option>
                                    <option value="responsable">Responsable</option>
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

        <!-- Tableau des serveurs -->
        <div class="content-section" id="serversSection">
            <div class="buttons mb-3">
                <button class="button is-success is-small" id="btnAddServer">
                    <span class="icon is-small"><i class="fas fa-plus"></i></span>
                    <span>Ajouter</span>
                </button>
            </div>

            <div class="table-container">
                <table class="table is-striped is-hoverable is-fullwidth is-narrow" id="serversTable">
                    <thead>
                        <tr>
                            <th class="col-nom">Nom</th>
                            <th class="col-ser_description">Description</th>
                            <th class="col-date">Date de création</th>
                            <th class="col-date">Date de modification</th>
                            <th class="col-fonction">Fonction</th>
                            <th class="col-periode">Période d'arrêt</th>
                            <th class="col-collecte">Collecte automatique</th>
                            <th class="col-responsable">Responsable</th>
                            <th class="col-ser_remarque">Remarque</th>
                            <th class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($serveurs as $serveur): ?>
                            <tr>
                                <td class="col-nom" title="<?= htmlspecialchars($serveur['ser_nom']) ?>"><?= htmlspecialchars($serveur['ser_nom']) ?></td>
                                <td class="col-ser_description" title="<?= htmlspecialchars($serveur['ser_description']) ?>"><?= htmlspecialchars($serveur['ser_description']) ?></td>
                                <td class="col-date"><?= htmlspecialchars(date('Y-m-d', strtotime($serveur['ser_date_creation']))) ?></td>
                                <td class="col-date"><?= htmlspecialchars(date('Y-m-d', strtotime($serveur['ser_date_modification']))) ?></td>
                                <td class="col-fonction"><?= htmlspecialchars($serveur['fonction'] ?? '') ?></td>
                                <td class="col-periode"><?= htmlspecialchars($serveur['ser_periode_arret']) ?></td>
                                <td class="col-collecte">
                                    <span class="tag is-<?= $serveur['ser_collect_automatique'] ? 'success' : 'danger' ?>">
                                        <?= $serveur['ser_collect_automatique'] ? 'OUI' : 'NON' ?>
                                    </span>
                                </td>
                                <td class="col-responsable"><?= htmlspecialchars($serveur['responsable'] ?? '') ?></td>
                                <td class="col-ser_remarque" title="<?= htmlspecialchars($serveur['ser_remarque']) ?>"><?= htmlspecialchars($serveur['ser_remarque']) ?></td>
                                <td class="col-actions">
                                    <div class="buttons are-small is-centered">
                                        <button class="button is-info" title="Historique d'exécution" onclick="showHistorique(<?= $serveur['ser_id'] ?>)">
                                            <i class="fas fa-history"></i>
                                        </button>
                                        <button class="button is-link" title="Dépendances" onclick="showDependances(<?= $serveur['ser_id'] ?>)">
                                            <i class="fas fa-project-diagram"></i>
                                        </button>
                                        <button class="button is-primary" title="Afficher les tâches" onclick="showTaches(<?= $serveur['ser_id'] ?>)">
                                            <i class="fas fa-tasks"></i>
                                        </button>
                                        <button class="button jaune edit-server"
                                            title="Modifier" 
                                            data-id="<?= $serveur['ser_id'] ?>" 
                                            data-nom="<?= htmlspecialchars($serveur['ser_nom']) ?>" 
                                            data-ser_description="<?= htmlspecialchars($serveur['ser_description']) ?>" 
                                            data-ser_remarque="<?= htmlspecialchars($serveur['ser_remarque'] ?? '') ?>" 
                                            data-periode="<?= htmlspecialchars($serveur['ser_periode_arret'] ?? '') ?>"
                                            data-collecte="<?= $serveur['ser_collect_automatique'] ?? '0' ?>"
                                            data-responsable="<?= $serveur['ser_res_id'] ?? '' ?>"
                                            data-fonction="<?= $serveur['ser_fonc_id'] ?? '' ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if (peutSupprimerServeur($serveur['ser_id'])): $nbTaches = nombreTachesServeur($serveur['ser_id']);?>
                                        <button class="button rouge delete-server" title="Supprimer" data-id="<?= $serveur['ser_id'] ?> " data-nb-taches="<?= $nbTaches ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php else: ?>
                                        <button class="button is-danger" title="Suppression impossible (tâches en collecte automatique associées)" disabled>
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
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

<!-- Modal d'ajout de serveur -->
<div class="modal" id="addServerModal">
    <div class="modal-background"></div>
    <div class="modal-card">
        <header class="modal-card-head">
            <p class="modal-card-title">Ajouter</p>
            <button class="delete close-modal" aria-label="close"></button>
        </header>
        <form id="addServerForm" method="POST" action="serveurs.php">
            <input type="hidden" name="action" value="ajouter">
            <section class="modal-card-body">
                <div class="field">
                    <label class="label">Nom</label>
                    <div class="control">
                        <input class="input" type="text" name="nom" required>
                    </div>
                </div>
                <div class="field">
                    <label class="label">Description</label>
                    <div class="control">
                        <textarea class="textarea" name="ser_description" required></textarea>
                    </div>
                </div>
                <div class="field">
                    <label class="label">Fonction</label>
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select name="fonc_id" required>
                                <option value="">Sélectionnez une fonction</option>
                                <?php foreach ($fonctions as $fonction): ?>
                                    <option value="<?= $fonction['fonc_id'] ?>"><?= htmlspecialchars($fonction['fonc_nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="field">
                    <label class="label">Période d'arrêt</label>
                    <div class="control">
                        <input class="input" type="text" name="ser_periode_arret">
                    </div>
                </div>
                <div class="field">
                    <label class="label">Responsable</label>
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select name="res_id" required>
                                <option value="">Sélectionnez un responsable</option>
                                <?php foreach ($responsables as $resp): ?>
                                    <option value="<?= $resp['res_id'] ?>"><?= htmlspecialchars($resp['res_nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="field">
                    <label class="label">Remarque</label>
                    <div class="control">
                        <textarea class="textarea" name="ser_remarque"></textarea>
                    </div>
                </div>
                <div class="field">
                    <label class="checkbox">
                        <input type="checkbox" name="ser_collect_automatique"> <strong>Collecte automatique</strong>
                    </label>
                </div>
            </section>
            <footer class="modal-card-foot">
                <button type="submit" class="button is-success">Ajouter</button>
                <button type="button" class="button close-modal">Annuler</button>
            </footer>
        </form>
    </div>
</div>

<!-- Modal de modification de serveur -->
<div class="modal" id="editServerModal">
    <div class="modal-background"></div>
    <div class="modal-card">
        <header class="modal-card-head">
            <p class="modal-card-title" id="editModalTitle">Modifier le serveur</p>
            <button class="delete close-modal" aria-label="close"></button>
        </header>
        <form id="editServerForm" method="POST" action="serveurs.php">
            <input type="hidden" name="action" value="modifier">
            <input type="hidden" name="ser_id" id="editServerId">
            <section class="modal-card-body">
            
                <!-- Alerte pour les serveurs en collecte automatique -->
                <div class="notification is-warning is-light" id="collecteAutoAlert" style="display: none;">
                    <strong>Attention :</strong> Ce serveur est en collecte automatique. Seuls certains champs peuvent être modifiés : Remarque et Responsable.
                </div>
                
                <div class="field" id="field-nom">
                    <label class="label">Nom</label>
                    <div class="control">
                        <input class="input" type="text" name="nom" id="editServerName" required>
                    </div>
                </div>
                <div class="field" id="field-description">
                    <label class="label">Description</label>
                    <div class="control">
                        <textarea class="textarea" name="ser_description" id="editServerser_description" ></textarea>
                    </div>
                </div>
                <div class="field" id="field-fonction">
                    <label class="label">Fonction</label>
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select name="fonc_id" id="editServerFonction" required>
                                <option value="">Sélectionnez une fonction</option>
                                <?php foreach ($fonctions as $fonction): ?>
                                    <option value="<?= $fonction['fonc_id'] ?>"><?= htmlspecialchars($fonction['fonc_nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="field" id="field-periode">
                    <label class="label">Période d'arrêt</label>
                    <div class="control">
                        <input class="input" type="text" name="ser_periode_arret" id="editServerPeriode">
                    </div>
                </div>
                <div class="field">
                    <label class="label">Responsable</label>
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select name="res_id" id="editServerResponsable" required>
                                <option value="">Sélectionnez un responsable</option>
                                <?php foreach ($responsables as $resp): ?>
                                    <option value="<?= $resp['res_id'] ?>"><?= htmlspecialchars($resp['res_nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="field" id="field-remarque">
                    <label class="label">Remarque</label>
                    <div class="control">
                        <textarea class="textarea" name="ser_remarque" id="editServerser_remarque"></textarea>
                    </div>
                </div>
                <div class="field" id="field-collecte">
                    <label class="checkbox">
                        <input type="checkbox" name="ser_collect_automatique" id="editServerCollecte" disabled> <strong>Collecte automatique (non modifiable)</strong>
                    </label>
                </div>
            </section>
            <footer class="modal-card-foot">
                <button type="submit" class="button jaune">Modifier</button>
                <button type="button" class="button close-modal">Annuler</button>
            </footer>
        </form>
    </div>
</div>

<!-- Modal de suppression -->
<div class="modal" id="deleteServerModal">
    <div class="modal-background"></div>
    <div class="modal-card">
        <header class="modal-card-head">
            <p class="modal-card-title">Confirmer la suppression</p>
            <button class="delete close-modal" aria-label="close"></button>
        </header>
        <form id="deleteServerForm" method="POST" action="serveurs.php">
            <input type="hidden" name="action" value="supprimer">
            <input type="hidden" name="ser_id" id="deleteServerId">
            <section class="modal-card-body">
                <p>Êtes-vous sûr de vouloir supprimer ce serveur ? Cette action est irréversible.</p>
            </section>
            <footer class="modal-card-foot">
                <button type="submit" class="button rouge">Supprimer</button>
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

    // Ouvrir modal d'ajout de serveur
    document.getElementById("btnAddServer").addEventListener("click", () => {
        document.getElementById("addServerModal").classList.add("is-active");
    });
    
	// Fonction pour masquer/afficher les champs selon la collecte automatique
	function toggleFieldsBasedOnCollecte(collecteAuto) {
		const fieldsToHide = [
			'field-nom',
			'field-description', 
			'field-periode',
			'field-collecte'
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

	// Modifier aussi la partie qui ouvre le modal de modification
	document.querySelectorAll(".edit-server").forEach(button => {
		button.addEventListener("click", () => {
			const id = button.getAttribute("data-id");
			const nom = button.getAttribute("data-nom");
			const ser_description = button.getAttribute("data-ser_description");
			const ser_remarque = button.getAttribute("data-ser_remarque");
			const periode = button.getAttribute("data-periode");
			const collecte = button.getAttribute("data-collecte");
			const responsable = button.getAttribute("data-responsable");
			const fonction = button.getAttribute("data-fonction");
			
			// Remplir les champs du formulaire
			document.getElementById("editServerId").value = id;
			document.getElementById("editServerName").value = nom;
			document.getElementById("editServerser_description").value = ser_description;
			document.getElementById("editServerser_remarque").value = ser_remarque || '';
			document.getElementById("editServerPeriode").value = periode || '';
			document.getElementById("editServerResponsable").value = responsable || '';
			document.getElementById("editServerFonction").value = fonction || '';
			document.getElementById("editServerCollecte").checked = (collecte == '1');
			
			document.getElementById("editModalTitle").textContent = `Modifier le serveur : ${nom}`;
			
			// Appliquer la logique de désactivation des champs APRÈS avoir rempli les valeurs
			toggleFieldsBasedOnCollecte(collecte);
			
			document.getElementById("editServerModal").classList.add("is-active");
		});
	});
    document.querySelectorAll(".delete-server").forEach(button => {
        button.addEventListener("click", () => {
            const id = button.dataset.id;
            const nbTaches = parseInt(button.dataset.nbTaches, 10);
            const modal = document.getElementById("deleteServerModal");
            const input = document.getElementById("deleteServerId");

            if (nbTaches > 0) {
                if (!confirm(`Attention, ce serveur contient ${nbTaches} tâche(s). Voulez-vous vraiment supprimer le serveur ET ses tâches ?`)) {
                    return; // L'utilisateur a annulé
                }
            }

            if (input && modal && id) {
                input.value = id;
                modal.classList.add("is-active");
            } else {
                console.error("Élément modal ou input non trouvé, ou id manquant");
            }
        });
    });

    // Fermer les modals
    document.querySelectorAll(".close-modal").forEach(button => {
        button.addEventListener("click", (e) => {
            e.preventDefault();
            document.querySelectorAll(".modal").forEach(modal => {
                modal.classList.remove("is-active");
            });
        });
    });

    // Fonction de tri des lignes du tableau
    function sortTable(sortBy, sortOrder) {
        const tbody = document.querySelector('#serversTable tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        rows.sort((a, b) => {
            let valueA, valueB;
            
            if (sortBy == 'nom') {
                valueA = a.querySelector('.col-nom').textContent.trim().toLowerCase();
                valueB = b.querySelector('.col-nom').textContent.trim().toLowerCase();
            } else if (sortBy == 'fonction') {
                valueA = a.querySelector('.col-fonction').textContent.trim().toLowerCase();
                valueB = b.querySelector('.col-fonction').textContent.trim().toLowerCase();
            } else if (sortBy == 'responsable') {
                valueA = a.querySelector('.col-responsable').textContent.trim().toLowerCase();
                valueB = b.querySelector('.col-responsable').textContent.trim().toLowerCase();
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

    // Appliquer le tri par défaut au chargement de la page (par nom)
    sortTable('nom', 'asc');

    // Fonctions pour les autres boutons
    window.showHistorique = function(serverId) {
        // Rediriger vers la page d'historique du serveur
        window.location.href = `historique_serveur.php?ser_id=${serverId}`;
    };
    
    window.showDependances = function(serverId) {
        alert(`Affichage des dépendances pour le serveur ID: ${serverId}.`);
    };
    
    window.showTaches = function(serverId) {
        window.location.href = `taches.php?serveur=${serverId}`;
    };
});
</script>

</body>
</html>