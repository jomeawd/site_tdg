<?php 
include 'dataConfig.php';

$pdo = getPDO();
$criticites = $pdo->query("SELECT cri_id, cri_libelle, cri_description FROM Criticite ORDER BY cri_libelle")->fetchAll();
$responsables = $pdo->query("SELECT res_id, res_nom FROM Responsable ORDER BY res_nom")->fetchAll();
$fonctions = $pdo->query("SELECT fonc_id, fonc_nom FROM Fonction ORDER BY fonc_nom")->fetchAll();

// Traitement POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $type = $_POST['type'] ?? '';

   if ($type == 'criticite') {
        $id = $_POST['id'] ?? null;
        $nom = $_POST['nom'] ?? '';
        $description = $_POST['description'] ?? '';

        if ($action == 'ajouter' && ajouterCriticite($nom, $description)) {
            header('Location: config.php?msg=add_success&type=criticite');
            exit;
        }
        if ($action == 'modifier' && modifierCriticite($id, $nom, $description)) {
            header('Location: config.php?msg=edit_success&type=criticite');
            exit;
        }
        if ($action == 'supprimer' && supprimerCriticite($id)) {
            header('Location: config.php?msg=delete_success&type=criticite');
            exit;
        }

        header("Location: config.php?msg={$action}_error&type=criticite");
        exit;
    }

    if ($type == 'responsable') {
        $id = $_POST['id'] ?? null;
        $nom = $_POST['nom'] ?? '';

        if ($action == 'ajouter' && ajouterResponsable($nom)) {
            header('Location: config.php?msg=add_success&type=responsable');
            exit;
        }
        if ($action == 'modifier' && modifierResponsable($id, $nom)) {
            header('Location: config.php?msg=edit_success&type=responsable');
            exit;
        }
        if ($action == 'supprimer' && supprimerResponsable($id)) {
            header('Location: config.php?msg=delete_success&type=responsable');
            exit;
        }

        header("Location: config.php?msg={$action}_error&type=responsable");
        exit;
    }

    if ($type == 'fonction') {
        $id = $_POST['id'] ?? null;
        $nom = $_POST['nom'] ?? '';

        if ($action == 'ajouter' && ajouterFonction($nom)) {
            header('Location: config.php?msg=add_success&type=fonction');
            exit;
        }
        if ($action == 'modifier' && modifierFonction($id, $nom)) {
            header('Location: config.php?msg=edit_success&type=fonction');
            exit;
        }
        if ($action == 'supprimer' && supprimerFonction($id)) {
            header('Location: config.php?msg=delete_success&type=fonction');
            exit;
        }

        header("Location: config.php?msg={$action}_error&type=fonction");
        exit;
    }
}

$msg = $_GET['msg'] ?? '';
$msgType = $_GET['type'] ?? '';
$alertClass = '';
$alertMessage = '';

$typeLabels = [
    'criticite' => 'la criticité',
    'responsable' => 'le responsable',
    'fonction' => 'la fonction'
];

$typeLabel = $typeLabels[$msgType] ?? 'l\'élément';

switch($msg) {
    case 'add_success':
        $alertClass = 'is-success';
        $alertMessage = ucfirst($typeLabel) . ' a été ajouté(e) avec succès.';
        break;
    case 'add_error':
        $alertClass = 'is-danger';
        $alertMessage = 'Erreur lors de l\'ajout de ' . $typeLabel . '.';
        break;
    case 'edit_success':
        $alertClass = 'is-success';
        $alertMessage = ucfirst($typeLabel) . ' a été modifié(e) avec succès.';
        break;
    case 'edit_error':
        $alertClass = 'is-danger';
        $alertMessage = 'Erreur lors de la modification de ' . $typeLabel . '.';
        break;
    case 'delete_success':
        $alertClass = 'is-success';
        $alertMessage = ucfirst($typeLabel) . ' a été supprimé(e) avec succès.';
        break;
    case 'delete_error':
        $alertClass = 'is-danger';
        $alertMessage = 'Erreur lors de la suppression de ' . $typeLabel . '. Vérifiez qu\'aucun élément n\'y est associé.';
        break;
}


?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Configuration</title>
    <link rel="stylesheet" href="/bulma/css/bulma.min.css" />
    <link rel="stylesheet" href="/fontawesome/css/all.min.css" />
    <link rel="stylesheet" href="style.css" />
</head>
<body>
<nav class="navbar is-primary" role="navigation" aria-label="main navigation">
    <div class="navbar-brand">
        <a class="navbar-item" href="taches.php">
            <strong>Tour de guet - Surveillance des tâches planifiées</strong>
        </a>
        <a role="button" class="navbar-burger" aria-label="menu" aria-expanded="false" data-target="mainNavbar">
            <span aria-hidden="true"></span><span aria-hidden="true"></span><span aria-hidden="true"></span>
        </a>
    </div>
    <div id="mainNavbar" class="navbar-menu">
        <div class="navbar-start">
            <a class="navbar-item" href="taches.php"><i class="fas fa-tasks mr-1"></i>Tâches</a>
            <a class="navbar-item" href="serveurs.php"><i class="fas fa-server mr-1"></i>Serveurs</a>
            <a class="navbar-item" href="index.php"><i class="fas fa-calendar-check mr-1"></i>État des planifications</a>
            <a class="navbar-item is-active" href="#"><i class="fas fa-cog mr-1"></i>Configuration</a>
        </div>
    </div>
</nav>

<section class="section" style="padding: 1.5rem 1rem;">
    <div class="container is-fluid">
        <h1 class="title">Configuration</h1>

        <?php if (!empty($alertMessage)): ?>
            <div class="notification <?= $alertClass ?> is-light">
                <button class="delete"></button>
                <?= htmlspecialchars($alertMessage) ?>
            </div>
        <?php endif; ?>

        <div class="tabs is-boxed">
            <ul>
                <li class="is-active" data-tab="criticites">
                    <a><span class="icon is-small"><i class="fas fa-exclamation-triangle"></i></span><span>Criticités</span></a>
                </li>
                <li data-tab="responsables">
                    <a><span class="icon is-small"><i class="fas fa-users"></i></span><span>Responsables</span></a>
                </li>
                <li data-tab="fonctions">
                    <a><span class="icon is-small"><i class="fas fa-briefcase"></i></span><span>Fonctions</span></a>
                </li>
            </ul>
        </div>

        <!-- Criticites -->
        <div class="content-section is-active" id="criticites">
            <div class="buttons mb-3">
                <button class="button is-success is-small" onclick="openAddModal('criticite')">
                    <span class="icon is-small"><i class="fas fa-plus"></i></span>
                    <span>Ajouter</span>
                </button>
            </div>

            <div class="table-container">
                <table class="table is-striped is-hoverable is-fullwidth is-narrow">
                    <thead>
					<tr>
						<th>Nom</th>
						<th>Description</th>
						<th>Actions</th>
					</tr>
					</thead>
                    <tbody>
                        <?php foreach ($criticites as $c): ?>
                            <tr>
                                <td><?= htmlspecialchars($c['cri_libelle']) ?></td>
                                <td><?= htmlspecialchars($c['cri_description']) ?></td>
                                <td>
                                    <div class="buttons are-small is-centered">
                                        <button class="button jaune" title="Modifier"
                                            onclick='openEditModal("criticite", <?= $c["cri_id"] ?>, <?= json_encode($c["cri_libelle"]) ?>, <?= json_encode($c["cri_description"]) ?>)'>
                                            <i class="fas fa-edit"></i>
                                        </button>
										<?php if (peutSupprimerCriticite($c["cri_id"])): ?>
                                        <button class="button rouge" title="Supprimer"
                                            onclick='openDeleteModal("criticite", <?= $c["cri_id"] ?>, <?= json_encode($c["cri_libelle"]) ?>)'>
                                            <i class="fas fa-trash"></i>
                                        </button>
										<?php else: ?>
                                        <button class="button is-danger" title="Suppression impossible" disabled>
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

        <!-- Responsables -->
        <div class="content-section" id="responsables">
            <div class="buttons mb-3">
                <button class="button is-success is-small" onclick="openAddModal('responsable')">
                    <span class="icon is-small"><i class="fas fa-plus"></i></span>
                    <span>Ajouter</span>
                </button>
            </div>

            <div class="table-container">
                <table class="table is-striped is-hoverable is-fullwidth is-narrow">
                    <thead>
					<tr>
						<th>Nom</th>
						<th>Actions</th>
					</tr>
					</thead>
                    <tbody>
                        <?php foreach ($responsables as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars($r['res_nom']) ?></td>
                                <td>
                                    <div class="buttons are-small is-centered">
                                        <button class="button jaune" title="Modifier"
                                            onclick='openEditModal("responsable", <?= $r["res_id"] ?>, <?= json_encode($r["res_nom"]) ?>)'>
                                            <i class="fas fa-edit"></i>
                                        </button>
										<?php if (peutSupprimerResponsable($r["res_id"])): ?>
                                        <button class="button rouge" title="Supprimer"
                                            onclick='openDeleteModal("responsable", <?= $r["res_id"] ?>, <?= json_encode($r["res_nom"]) ?>)'>
                                            <i class="fas fa-trash"></i>
                                        </button>
										<?php else: ?>
										<button class="button is-danger" title="Suppression impossible" disabled>
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

        <!-- Fonctions -->
        <div class="content-section" id="fonctions">
            <div class="buttons mb-3">
                <button class="button is-success is-small" onclick="openAddModal('fonction')">
                    <span class="icon is-small"><i class="fas fa-plus"></i></span>
                    <span>Ajouter</span>
                </button>
            </div>

            <div class="table-container">
                <table class="table is-striped is-hoverable is-fullwidth is-narrow">
                    <thead>
					<tr>
						<th>Nom</th>
						<th>Actions</th>
					</tr>
					</thead>
                    <tbody>
                        <?php foreach ($fonctions as $f): ?>
                            <tr>
                                <td><?= htmlspecialchars($f['fonc_nom']) ?></td>
                                <td>
                                    <div class="buttons are-small is-centered">
                                        <button class="button jaune" title="Modifier"
                                            onclick='openEditModal("fonction", <?= $f["fonc_id"] ?>, <?= json_encode($f["fonc_nom"]) ?>)'>
                                            <i class="fas fa-edit"></i>
                                        </button>
										<?php if (peutSupprimerFonctions($f["fonc_id"])): ?>
                                        <button class="button rouge" title="Supprimer"
                                            onclick='openDeleteModal("fonction", <?= $f["fonc_id"] ?>, <?= json_encode($f["fonc_nom"]) ?>)'>
                                            <i class="fas fa-trash"></i>
                                        </button>
										<?php else: ?>
										<button class="button is-danger" title="Suppression impossible" disabled>
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

<!-- MODAL AJOUT -->
<div class="modal" id="addModal">
    <div class="modal-background"></div>
    <div class="modal-card">
        <header class="modal-card-head">
            <p class="modal-card-title" id="addModalTitle">Ajouter</p>
            <button class="delete" aria-label="close" onclick="closeModal('addModal')"></button>
        </header>
        <form method="POST" id="addForm" action="config.php">
            <section class="modal-card-body">
                <input type="hidden" name="action" value="ajouter" />
                <input type="hidden" name="type" id="addType" value="" />
                <div class="field">
                    <label class="label" for="addNom">Nom</label>
                    <div class="control">
                        <input class="input" type="text" id="addNom" name="nom" required />
                    </div>
                </div>
                <div class="field" id="addDescriptionField">
                    <label class="label" for="addDescription">Description</label>
                    <div class="control">
                        <textarea class="textarea" id="addDescription" name="description" rows="3"></textarea>
                    </div>
                </div>
            </section>
            <footer class="modal-card-foot">
                <button class="button is-success" type="submit">Ajouter</button>
                <button class="button" type="button" onclick="closeModal('addModal')">Annuler</button>
            </footer>
        </form>
    </div>
</div>

<!-- MODAL MODIFIER -->
<div class="modal" id="editModal">
    <div class="modal-background"></div>
    <div class="modal-card">
        <header class="modal-card-head">
            <p class="modal-card-title" id="editModalTitle">Modifier</p>
            <button class="delete" aria-label="close" onclick="closeModal('editModal')"></button>
        </header>
        <form method="POST" id="editForm" action="config.php">
            <section class="modal-card-body">
                <input type="hidden" name="action" value="modifier" />
                <input type="hidden" name="type" id="editType" value="" />
                <input type="hidden" name="id" id="editId" value="" />
                <div class="field">
                    <label class="label" for="editNom">Nom</label>
                    <div class="control">
                        <input class="input" type="text" id="editNom" name="nom" required />
                    </div>
                </div>
                <div class="field" id="editDescriptionField">
                    <label class="label" for="editDescription">Description</label>
                    <div class="control">
                        <textarea class="textarea" id="editDescription" name="description" rows="3"></textarea>
                    </div>
                </div>
            </section>
            <footer class="modal-card-foot">
                <button class="button is-success" type="submit">Modifier</button>
                <button class="button" type="button" onclick="closeModal('editModal')">Annuler</button>
            </footer>
        </form>
    </div>
</div>

<!-- MODAL SUPPRIMER -->
<div class="modal" id="deleteModal">
    <div class="modal-background"></div>
    <div class="modal-card">
        <header class="modal-card-head">
            <p class="modal-card-title">Supprimer</p>
            <button class="delete" aria-label="close" onclick="closeModal('deleteModal')"></button>
        </header>
        <form method="POST" id="deleteForm" action="config.php">
            <section class="modal-card-body">
                <input type="hidden" name="action" value="supprimer" />
                <input type="hidden" name="type" id="deleteType" value="" />
                <input type="hidden" name="id" id="deleteId" value="" />
                <p id="deleteMessage">Êtes-vous sûr ? Cette action est irréversible.</p>
            </section>
            <footer class="modal-card-foot">
                <button class="button rouge" type="submit">Supprimer</button>
                <button class="button" type="button" onclick="closeModal('deleteModal')">Annuler</button>
            </footer>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Gestion fermeture notification
    document.querySelectorAll('.notification .delete').forEach(($delete) => {
        const $notification = $delete.parentNode;
        $delete.addEventListener('click', () => {
            $notification.style.display = 'none';
        });
    });

    // Onglets
    const tabs = document.querySelectorAll('.tabs ul li');
    const contents = document.querySelectorAll('.content-section');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('is-active'));
            contents.forEach(c => c.classList.remove('is-active'));
            tab.classList.add('is-active');
            const target = tab.getAttribute('data-tab');
            document.getElementById(target).classList.add('is-active');
        });
    });
});

// Fonctions modals
function closeModal(id) {
    document.getElementById(id).classList.remove('is-active');
}

function openAddModal(type) {
    document.getElementById('addType').value = type;
    document.getElementById('addModalTitle').textContent = 'Ajouter ' + label(type);
    document.getElementById('addForm').reset();
    toggleDescriptionField('add', type);
    document.getElementById('addModal').classList.add('is-active');
}

function openEditModal(type, id, nom, description = '') {
    document.getElementById('editType').value = type;
    document.getElementById('editId').value = id;
    document.getElementById('editNom').value = nom;
    document.getElementById('editDescription').value = description || '';
    document.getElementById('editModalTitle').textContent = 'Modifier ' + label(type);
    toggleDescriptionField('edit', type);
    document.getElementById('editModal').classList.add('is-active');
}

function openDeleteModal(type, id, nom) {
    document.getElementById('deleteType').value = type;
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteMessage').textContent = 'Êtes-vous sûr de vouloir supprimer "' + nom + '" ? Cette action est irréversible.';
    document.getElementById('deleteModal').classList.add('is-active');
}

function toggleDescriptionField(prefix, type) {
    const descField = document.getElementById(prefix + 'DescriptionField');
    if(type === 'criticite') {
        descField.classList.remove('is-hidden');
    } else {
        descField.classList.add('is-hidden');
    }
}

function label(type) {
    switch(type) {
        case 'criticite': return 'une criticité';
        case 'responsable': return 'un responsable';
        case 'fonction': return 'une fonction';
        default: return 'un élément';
    }
}
</script>

<style>
.content-section {
    display: none;
}
.content-section.is-active {
    display: block;
}
</style>

</body>
</html>
