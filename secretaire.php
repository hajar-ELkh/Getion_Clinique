<?php 
include 'connectDB.php'; // Connexion à la base de données

// Initialisation des messages
$messages = [];
$errors = [];

// Ajout d'une nouvelle secrétaire
if (isset($_POST['add_secretaire'])) { 
    $sec_id = trim($_POST['sec_id']);
    $prenom = trim($_POST['sec_fname']);
    $nom = trim($_POST['sec_lname']);
    $telephone = trim($_POST['sec_phone']);
    $sexe = trim($_POST['sexe']);

    if (empty($sec_id) || empty($prenom) || empty($nom) || empty($telephone) || empty($sexe)) {
        $errors[] = "Tous les champs sont requis.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM secretaire WHERE sec_id = ?");
        $stmt->execute([$sec_id]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "L'ID du secrétaire existe déjà.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO secretaire (sec_id, sec_fname, sec_lname, sec_phone, sexe) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$sec_id, $prenom, $nom, $telephone, $sexe])) {
                $messages[] = "Secrétaire ajouté avec succès.";
            } else {
                $errors[] = "Erreur lors de l'ajout du secrétaire.";
            }
        }
    }
}
if (isset($_POST['update_secretaire'])) {
    $sec_id = trim($_POST['sec_id']);
    $prenom = trim($_POST['sec_fname']);
    $nom = trim($_POST['sec_lname']);
    $telephone = trim($_POST['sec_phone']);
    $sexe = trim($_POST['sexe']);

    if (empty($prenom) || empty($nom) || empty($telephone) || empty($sexe)) {
        $errors[] = "Tous les champs sont requis.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM secretaire WHERE sec_id = ?");
        $stmt->execute([$sec_id]);
        if ($stmt->rowCount() == 0) {
            $errors[] = "Le secrétaire avec cet ID n'existe pas.";
        } else {
            $stmt = $pdo->prepare("UPDATE secretaire SET sec_fname = ?, sec_lname = ?, sec_phone = ?, sexe = ? WHERE sec_id = ?");
            if ($stmt->execute([$prenom, $nom, $telephone, $sexe, $sec_id])) {
                $messages[] = "Secrétaire modifié avec succès.";
            } else {
                $errors[] = "Erreur lors de la modification du secrétaire.";
            }
        }
    }
}
// Suppression d'une secrétaire
if (isset($_POST['delete_secretaire'])) {
    $sec_id = trim($_POST['delete_secretaire']); // Récupère l'ID de la secrétaire à supprimer

    // Vérifier si l'ID de la secrétaire existe
    if (!empty($sec_id)) {
        $stmt = $pdo->prepare("SELECT * FROM secretaire WHERE sec_id = ?");
        $stmt->execute([$sec_id]);

        if ($stmt->rowCount() > 0) {
            // La secrétaire existe, vérifier s'il est lié à d'autres tables
            $stmt = $pdo->prepare("SELECT * FROM rendez_vous WHERE sec_id = ?");
            $stmt->execute([$sec_id]);

            $linked = $stmt->rowCount() > 0;

            if ($linked) {
                // Ouvrir la modal
                echo '<div class="modal" style="display: flex; justify-content: center; align-items: center; height: 100vh; position: fixed; width: 100%; top: 0; left: 0; background: rgba(0, 0, 0, 0.5); z-index: 999;">';

                // Contenu de l'alerte
                echo '<div class="alert" style="padding: 40px; background: rgb(195, 203, 210); color: black; border-radius: 12px; width: 80%; max-width: 700px; min-width: 400px; height: auto; max-height: 80%; overflow-y: auto; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3); font-size: 16px; word-wrap: break-word; line-height: 1.5;">';

                echo '<p style="margin-top: 20px;">Cette secrétaire est liée à des rendez-vous.</p>';
                echo '<p style="margin-top: 20px;">Souhaitez-vous supprimer tous les rendez-vous associés ?</p>';

                // Formulaire de confirmation
                echo '<form method="POST" style="margin-top: 20px;">';
                echo '<input type="hidden" name="sec_id" value="' . htmlspecialchars($sec_id) . '">';

                // Options Oui / Non
                echo '<div style="margin-bottom: 20px; display: flex; justify-content: space-around; align-items: center;">';
                echo '<div style="display: flex; align-items: center;">';
                echo '<input type="radio" name="delete_related" value="yes" id="delete-yes" required>';
                echo '<label for="delete-yes" style="margin-left: 10px;"> Oui</label>';
                echo '</div>';
                echo '<div style="display: flex; align-items: center;">';
                echo '<input type="radio" name="delete_related" value="no" id="delete-no" required>';
                echo '<label for="delete-no" style="margin-left: 10px;"> Non</label>';
                echo '</div>';
                echo '</div>';

                // Boutons
                echo '<div style="display: flex; justify-content: space-between; margin-top: 20px;">';
                echo '<input type="submit" name="confirm_delete" value="Confirmer" class="btn btn-danger" style="padding: 10px 20px; font-size: 1em; background-color: red; color: white; border: none; border-radius: 5px; cursor: pointer;">';
                echo '<button type="button" class="btn btn-secondary" onclick="this.closest(\'.modal\').style.display=\'none\'" style="padding: 10px 20px; font-size: 1em; background-color: gray; color: white; border: none; border-radius: 5px; cursor: pointer;">Annuler</button>';
                echo '</div>';
                echo '</form>';
                echo '</div>'; // Fin de la boîte d'alerte
                echo '</div>'; // Fin de la modal
            } else {
                // Si non lié, suppression directe
                $stmt = $pdo->prepare("DELETE FROM secretaire WHERE sec_id = ?");
                if ($stmt->execute([$sec_id])) {
                    $messages[] = "Secrétaire supprimée avec succès.";
                } else {
                    $errors[] = "Erreur lors de la suppression de la secrétaire.";
                }
            }
        } else {
            $errors[] = "La secrétaire avec cet ID n'existe pas.";
        }
    } else {
        $errors[] = "L'ID de la secrétaire est requis pour la suppression.";
    }
}

// Traitement de la confirmation de suppression
if (isset($_POST['confirm_delete'])) {
    $sec_id = trim($_POST['sec_id']);
    $delete_related = $_POST['delete_related'];

    if ($delete_related === 'yes') {
        // Supprimer les rendez-vous associés
        $stmt = $pdo->prepare("DELETE FROM rendez_vous WHERE sec_id = ?");
        $stmt->execute([$sec_id]);

        // Supprimer la secrétaire
        $stmt = $pdo->prepare("DELETE FROM secretaire WHERE sec_id = ?");
        if ($stmt->execute([$sec_id])) {
            $messages[] = "Secrétaire et ses rendez-vous associés supprimés avec succès.";
        } else {
            $errors[] = "Erreur lors de la suppression de la secrétaire.";
        }
    } else {
        $messages[] = "Aucun rendez-vous associé n'a été supprimé.";
    }
}

// Vérification si les paramètres de recherche sont présents
if (isset($_GET['search_type']) && isset($_GET['search_value'])) {
    $search_type = trim($_GET['search_type']); // Type de recherche (colonne)
    $search_value = trim($_GET['search_value']); // Valeur de recherche

    // Liste des colonnes valides pour la recherche
    $allowed_columns = ['sec_id', 'sec_fname', 'sec_lname', 'sec_phone', 'sexe'];

    // Si l'option 'All' est choisie, chercher sur toutes les colonnes
    if ($search_type === 'all') {
        // Recherche sur toutes les colonnes en combinant des conditions pour chaque colonne
        $stmt = $pdo->query("SELECT * FROM secretaire");
    }
    elseif (in_array($search_type, $allowed_columns)) {
        // Si la colonne est un champ VARCHAR, utiliser LIKE pour une recherche partielle
        if (in_array($search_type, ['sec_fname', 'sec_lname', 'sec_phone', 'sexe'])) {
            $stmt = $pdo->prepare("SELECT * FROM secretaire WHERE $search_type LIKE ?");
            $stmt->execute(["$search_value%"]);
        } else {
            // Pour les colonnes non VARCHAR, utiliser une recherche exacte
            $stmt = $pdo->prepare("SELECT * FROM secretaire WHERE $search_type = ?");
            $stmt->execute([$search_value]);
        }
    } else {
        // Si la colonne spécifiée n'est pas valide, afficher tous les secrétaires
        $stmt = $pdo->query("SELECT * FROM secretaire");
    }
} else {
    // Si aucun critère de recherche n'est défini, afficher tous les secrétaires
    $stmt = $pdo->query("SELECT * FROM secretaire");
}

// Récupérer les résultats de la requête
// Récupération des secrétaires après la recherche
$secretaire = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Secretaire</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://cdn.jsdelivr.net/npm/font-awesome@6.0.0/css/all.min.css" rel="stylesheet">
<style>  
        body {  
            font-family: Arial, sans-serif;  
            background: url('img/secretaire.jpg') no-repeat center center fixed; /* Ajustez le chemin de l'image ici */  
            background-size: cover;  
            margin: 0;  
            padding: 0;  
        }  
        .container {  
            max-width: 1200px;  
            margin: 20px auto;  
            background: rgba(255, 255, 255, 0.9);  
            padding: 20px;  
            border-radius: 8px;  
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);  
        }  
        h1 {  
            text-align: center;  
            color: #007bff;  
        }  
        .messages,  
        .errors {  
            margin: 10px 0;  
            padding: 10px;  
            border-radius: 5px;  
        }  
        .messages {  
            background-color: #d4edda;  
            color:rgb(21, 52, 87);  
        }  
        .errors {  
            background-color: #f8d7da;  
            color: #721c24;  
        }  
        table {  
            width: 100%;  
            border-collapse: collapse;  
            margin: 20px 0;  
        }  
        table th, table td {  
            border: 1px solid #ddd;  
            padding: 8px;  
            text-align: center;  
        }  
        table th {  
            background-color:rgb(0, 60, 255);  
            color: black;  
        }  
        .form-group {  
            margin-bottom: 15px;  
        }  
        .form-group label {  
            display: block;  
            margin-bottom: 5px;  
        }  
        .form-group input {  
            width: 100%;  
            padding: 8px;  
            border: 1px solid #ddd;  
            border-radius: 5px;  
        }  
        .form-actions {  
            display: flex;  
            justify-content: flex-start;  
        }  
        .btn {  
            padding: 8px 12px;  
            border: none;  
            border-radius: 5px;  
            color: white;  
            cursor: pointer;  
            text-decoration: none;  
            display: flex;  
            align-items: center;  
            margin-right: 5px; /* Pour espacer les boutons */  
        }  
        .btn-save {  
            background-color:rgb(40, 89, 167);  
        }  
        .btn-save:hover {  
            background-color:rgb(33, 47, 136);  
        }  
        .btn-delete {  
            background-color: #dc3545;  
        }  
        .btn-delete:hover {  
            background-color: #c82333;  
        }  
        .btn-edit {  
            background-color: #ffc107;  
            color: black;  
        }  
        .btn-edit:hover {  
            background-color: #e0a800;  
        }  
        .actions {  
            display: flex;  
            justify-content: center;  
            align-items: center;  
        }  
        .menu-toggle {  
            font-size: 24px;  
            cursor: pointer;  
            float: right;  
            margin: 10px;  
        }  
        .menu {  
            display: none;  
            position: absolute;  
            top: 60px;  
            right: 20px;  
            background-color: white;  
            border: 1px solid #ccc;  
            border-radius: 5px;  
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);  
            z-index: 10;  
        }  
        .menu a {  
            display: block;  
            padding: 10px 15px;  
            text-decoration: none;  
            color: #007bff;  
            border-bottom: 1px solid #ddd;  
        }  
        .menu a:last-child {  
            border-bottom: none;  
        }  
        .menu a:hover {  
            background-color: #f8f9fa;  
        }  
        .search-container {  
            display: flex;  
            justify-content: flex-end;  
            margin-bottom: 20px;  
        }  
        .search-container input {  
            width: 300px;  
            padding: 8px;  
            border: 1px solid #ddd;  
            border-radius: 5px;  
        } 
        .navbar {
            background-color:rgb(29, 111, 198); /* Couleur bleue plus profonde */
            padding: 12px;
            text-align: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .navbar a {
            color: white;
            text-decoration: none;
            margin-right: 25px;
            font-size: 18px;
            font-weight: 600;
            display: inline-block;
            transition: background-color 0.3s, color 0.3s;
        }

        .navbar a:hover {
            background-color: rgba(255, 255, 255, 0.2);
            color: #ffd700; /* Couleur dorée pour l'effet de survol */
            border-radius: 5px;
        }

        .navbar a i {
            margin-right: 8px; /* Espace entre l'icône et le texte */
        }

        /* Pour la dernière ancre, éviter le margin-right supplémentaire */
        .navbar a:last-child {
            margin-right: 0;
        } 
    </style>  
</head>
<body>
<div class="navbar">
        <a href="interface.html"><i class="fas fa-home"></i> Accueil</a>
        <a href="secretaire.php"><i class="fas fa-users"></i> Gérer Secretaires</a>
        <a href="rendez_vous.php"><i class="fas fa-file-invoice"></i> Gérer les Rendez-vous</a>
    </div>
    <div class="container mt-5">
        <h1 class="text-center"><i class="fas fa-user-tie"></i> Gestion des Secretaires</h1>

        <!-- Messages -->
        <?php if (!empty($messages)): ?>
            <div class="alert alert-success">
                <ul>
                    <?php foreach ($messages as $message): ?>
                        <li><?= htmlspecialchars($message); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <!-- Formulaire d'ajout d'un secrétaire -->
                <form method="POST" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <input type="number" name="sec_id" class="form-control" placeholder="ID Secrétaire" required>
                        </div>
                        <div class="col-md-2">
                            <input type="text" name="sec_fname" class="form-control" placeholder="Prénom" required>
                        </div>
                        <div class="col-md-2">
                            <input type="text" name="sec_lname" class="form-control" placeholder="Nom" required>
                        </div>
                        <div class="col-md-2">
                            <input type="text" name="sec_phone" class="form-control" placeholder="Téléphone" required>
                        </div>
                        <div class="col-md-2">
                            <select name="sexe" class="form-control" required>
                                <option value="" disabled selected>Sexe</option>
                                <option value="M">Masculin</option>
                                <option value="F">Féminin</option>
                            </select>
                        </div>
                        <button type="submit" name="add_secretaire" class="btn btn-primary w-100 d-flex justify-content-center align-items-center">
                            <i class="fas fa-plus me-2"></i> Ajouter
                        </button>
                    </div>
                </form>

                <!-- Formulaire de recherche d'un secrétaire -->
                <form method="GET" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <select name="search_type" class="form-select" required>
                                <option value="all">All</option>
                                <option value="sec_id">ID</option>
                                <option value="sec_fname">Prénom</option>
                                <option value="sec_lname">Nom</option>
                                <option value="sec_phone">Téléphone</option>
                                <option value="sexe">Sexe</option>
                                
                            </select>
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="search_value" class="form-control" placeholder="Valeur à rechercher" required>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">Rechercher</button>
                        </div>
                    </div>
                </form>
        <table id="secretaireTable" class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Prénom</th>
                        <th>Nom</th>
                        <th>Téléphone</th>
                        <th>Sexe</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($secretaire as $sec): ?>
                    <tr>
                        <td><?= htmlspecialchars($sec['sec_id']); ?></td>
                        <td><?= htmlspecialchars($sec['sec_fname']); ?></td>
                        <td><?= htmlspecialchars($sec['sec_lname']); ?></td>
                        <td><?= htmlspecialchars($sec['sec_phone']); ?></td>
                        <td><?= htmlspecialchars($sec['sexe']); ?></td>
                        <td>
                        <div class="d-flex">
                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal"
                                    data-id="<?= $sec['sec_id']; ?>"
                                    data-fname="<?= $sec['sec_fname']; ?>"
                                    data-lname="<?= $sec['sec_lname']; ?>"
                                    data-phone="<?= $sec['sec_phone']; ?>"
                                    data-sexe="<?= $sec['sexe']; ?>">
                                <i class="fas fa-edit"></i> Modifier
                            </button>

                            <button class="btn btn-danger btn-sm" onclick="deleteSecretaire('<?= $sec['sec_id']; ?>')">
                                <i class="fas fa-trash"></i> Supprimer
                            </button>
                        </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
        </table>
    </div>
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier la Secrétaire</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="sec_id" id="edit_id">
                        <div class="mb-3">
                            <label for="edit_fname" class="form-label">Prénom</label>
                            <input type="text" name="sec_fname" id="edit_fname" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_lname" class="form-label">Nom</label>
                            <input type="text" name="sec_lname" id="edit_lname" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_phone" class="form-label">Téléphone</label>
                            <input type="text" name="sec_phone" id="edit_phone" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_sexe" class="form-label">Sexe</label>
                            <select name="sexe" id="edit_sexe" class="form-select" required>
                                <option value="M">Masculin</option>
                                <option value="F">Féminin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="update_secretaire" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#secretaireTable').DataTable({
                searching: false // Désactive la barre de recherche pour la table des secrétaires
            });
        });
        function deleteSecretaire(id) {
            if (confirm("Êtes-vous sûr de vouloir supprimer cette secrétaire ?")) {
                $('<form method="POST"><input type="hidden" name="delete_secretaire" value="' + id + '"></form>').appendTo('body').submit();
            }
        }

        $('#editModal').on('show.bs.modal', function (event) {
            const button = $(event.relatedTarget);
            $('#edit_id').val(button.data('id'));
            $('#edit_fname').val(button.data('fname'));
            $('#edit_lname').val(button.data('lname'));
            $('#edit_phone').val(button.data('phone'));
            $('#edit_sexe').val(button.data('sexe'));  // Ajouter la gestion du sexe dans le modal
        });
    </script>
</body>
</html>