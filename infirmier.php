<?php
include 'connectDB.php'; // Connexion à la base de données

$messages = [];
$errors = [];

// Ajout d'un nouvel infirmier
if (isset($_POST['add_infirmier'])) {
    $inf_id = trim($_POST['inf_id']);
    $prenom = trim($_POST['inf_fname']);
    $nom = trim($_POST['inf_lname']);
    $telephone = trim($_POST['inf_phone']);

    if (empty($inf_id) || empty($prenom) || empty($nom) || empty($telephone)) {
        $errors[] = "Tous les champs sont requis.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM infirmier WHERE inf_id = ?");
        $stmt->execute([$inf_id]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "L'ID de l'infirmier existe déjà.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO infirmier (inf_id, inf_fname, inf_lname, inf_phone) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$inf_id, $prenom, $nom, $telephone])) {
                $messages[] = "Infirmier ajouté avec succès.";
            } else {
                $errors[] = "Erreur lors de l'ajout de l'infirmier.";
            }
        }
    }
}

// Mise à jour d'un infirmier
if (isset($_POST['update_infirmier'])) {
    $inf_id = trim($_POST['inf_id']);
    $prenom = trim($_POST['inf_fname']);
    $nom = trim($_POST['inf_lname']);
    $telephone = trim($_POST['inf_phone']);

    if (empty($prenom) || empty($nom) || empty($telephone)) {
        $errors[] = "Tous les champs sont requis.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM infirmier WHERE inf_id = ?");
        $stmt->execute([$inf_id]);
        if ($stmt->rowCount() == 0) {
            $errors[] = "L'infirmier avec cet ID n'existe pas.";
        } else {
            $stmt = $pdo->prepare("UPDATE infirmier SET inf_fname = ?, inf_lname = ?, inf_phone = ? WHERE inf_id = ?");
            if ($stmt->execute([$prenom, $nom, $telephone, $inf_id])) {
                $messages[] = "Infirmier modifié avec succès.";
            } else {
                $errors[] = "Erreur lors de la modification de l'infirmier.";
            }
        }
    }
}
// Suppression d'une infirmier
if (isset($_POST['delete_infirmier'])) {
    $inf_id = trim($_POST['delete_infirmier']);  // Récupère l'ID d'infirmier à supprimer

    // Vérifier si l'ID du'infirmier existe
    if (!empty($inf_id)) {
        $stmt = $pdo->prepare("SELECT * FROM infirmier WHERE inf_id = ?");
        $stmt->execute([$inf_id]);
        
        if ($stmt->rowCount() > 0) {
            // L'infirmier existe, vérifier s'il est lié à d'autres tables
            $linked_tables = [
                'surveiller' => 'inf_id',
            ];

            $linked = false;
            foreach ($linked_tables as $table => $column) {
                $stmt = $pdo->prepare("SELECT * FROM $table WHERE $column = ?");
                $stmt->execute([$inf_id]);
                if ($stmt->rowCount() > 0) {
                    $linked = true;
                    break;
                }
            }

            if ($linked) {
                // Ouvrir la modal
                echo '<div class="modal" style="display: flex; justify-content: center; align-items: center; height: 100vh; position: fixed; width: 100%; top: 0; left: 0; background: rgba(0, 0, 0, 0.5); z-index: 999;">';

                // Contenu de l'alerte
                echo '<div class="alert" style="padding: 40px; background: rgb(195, 203, 210); color: black; border-radius: 12px; width: 80%; max-width: 700px; min-width: 400px; height: auto; max-height: 80%; overflow-y: auto; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3); font-size: 16px; word-wrap: break-word; line-height: 1.5;">';

                // Message d'avertissement
                echo '<p style="margin-top: 20px;">Ce médecin est lié à d\'autres enregistrements.</p>';

                // Message de confirmation avant les boutons
                echo '<p style="margin-top: 20px;">Souhaitez-vous supprimer tous les enregistrements associés ?</p>';

                // Formulaire de confirmation
                echo '<form method="POST" style="margin-top: 20px;">';
                echo '<input type="hidden" name="doc_id" value="' . htmlspecialchars($inf_id) . '">';

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

                // Boutons "Confirmer" et "Annuler"
                echo '<div style="display: flex; justify-content: space-between; margin-top: 20px;">';

                // Bouton "Confirmer"
                echo '<div>';
                echo '<input type="submit" name="confirm_delete" value="Confirmer" class="btn btn-danger" style="padding: 10px 20px; font-size: 1em; background-color: red; color: white; border: none; border-radius: 5px; cursor: pointer;">';
                echo '</div>';

                // Bouton "Annuler"
                echo '<div>';
                echo '<button type="button" class="btn btn-secondary" onclick="this.closest(\'.modal\').style.display=\'none\'" style="padding: 10px 20px; font-size: 1em; background-color: gray; color: white; border: none; border-radius: 5px; cursor: pointer;">Annuler</button>';
                echo '</div>';
                echo '</div>';

                // Fermeture de la boîte d'alerte et de la modal
                echo '</form>';
                echo '</div>';  // Fin du conteneur alert
                echo '</div>';  // Fin du conteneur modal
            } else {
                // Si le médecin n'est lié à aucune autre table, supprimer directement
                $stmt = $pdo->prepare("DELETE FROM infirmier WHERE inf_id = ?");
                if ($stmt->execute([$inf_id])) {
                    $messages[] = "Infirmier supprimé avec succès.";
                } else {
                    $errors[] = "Erreur lors de la suppression du infirmier.";
                }
            }
        } else {
            $errors[] = "L'infirmier avec cet ID n'existe pas.";
        }
    } else {
        $errors[] = "L'ID d'infirmier est requis pour la suppression.";
    }
}

// Traitement de la confirmation de suppression
if (isset($_POST['confirm_delete'])) {
    $inf_id = trim($_POST['inf_id']);
    $delete_related = $_POST['delete_related']; // Récupère si l'utilisateur veut supprimer les enregistrements associés

    if ($delete_related === 'yes') {
        // Si l'utilisateur veut supprimer tous les enregistrements associés
        $linked_tables = [
            'surveiller' => 'inf_id',
        ];

        // Supprimer les enregistrements associés dans toutes les tables
        foreach ($linked_tables as $table => $column) {
            $stmt = $pdo->prepare("DELETE FROM $table WHERE $column = ?");
            $stmt->execute([$inf_id]);
        }

        // Ensuite, supprimer l'infirmier de la table principale
        $stmt = $pdo->prepare("DELETE FROM infirmier WHERE inf_id = ?");
        if ($stmt->execute([$inf_id])) {
            $messages[] = "infirmier et tous ses enregistrements associés ont été supprimés avec succès.";
        } else {
            $errors[] = "Erreur lors de la suppression d'infirmier.";
        }
    } else {
        // Si l'utilisateur ne veut pas supprimer les enregistrements associés, rien ne change
        $messages[] = "Aucun enregistrement associé n'a été supprimé.";
    }
}

// Vérification si les paramètres de recherche sont présents
if (isset($_GET['search_type']) && isset($_GET['search_value'])) {
    $search_type = trim($_GET['search_type']); // Type de recherche (colonne)
    $search_value = trim($_GET['search_value']); // Valeur de recherche

    // Liste des colonnes valides pour la recherche
    $allowed_columns = ['inf_id', 'inf_fname', 'inf_lname', 'inf_phone'];

    // Si l'option 'All' est choisie, chercher sur toutes les colonnes
    if ($search_type === 'all') {
        // Recherche sur toutes les colonnes en combinant des conditions pour chaque colonne
        $stmt = $pdo->query("SELECT * FROM infirmier");
    } elseif (in_array($search_type, $allowed_columns)) {
        // Si la colonne est un champ VARCHAR, utiliser LIKE pour une recherche partielle
        if (in_array($search_type, ['inf_fname', 'inf_lname', 'inf_phone'])) {
            $stmt = $pdo->prepare("SELECT * FROM infirmier WHERE $search_type LIKE ?");
            $stmt->execute(["$search_value%"]);
        } else {
            // Pour les colonnes non VARCHAR, utiliser une recherche exacte
            $stmt = $pdo->prepare("SELECT * FROM infirmier WHERE $search_type = ?");
            $stmt->execute([$search_value]);
        }
    } else {
        // Si la colonne spécifiée n'est pas valide, afficher tous les infirmiers
        $stmt = $pdo->query("SELECT * FROM infirmier");
    }
} else {
    // Si aucun critère de recherche n'est défini, afficher tous les infirmiers
    $stmt = $pdo->query("SELECT * FROM infirmier");
}
// Récupérer les résultats de la requête pour les infirmiers
$infirmiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        <a href="infirmier.php"><i class="fas fa-user-nurse"></i> Gérer Infirmiers</a>
        <a href="survieller.php"><i class="fas fa-heartbeat"></i> Gérer Surveillances</a>
        <a href="chambre.php"><i class="fas fa-bed"></i> Gérer Chambres</a>
    </div>
    <div class="container mt-5">
        <h1 class="text-center"><i class="fas fa-user-nurse"></i> Gestion des Infirmiers</h1>

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

        <!-- Erreurs -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <!-- Formulaire d'ajout d'un infirmier -->
        <form method="POST" class="mb-4">
            <div class="row g-3">
                <div class="col-md-2">
                    <input type="text" name="inf_id" class="form-control" placeholder="ID Infirmier" required>
                </div>
                <div class="col-md-2">
                    <input type="text" name="inf_fname" class="form-control" placeholder="Prénom" required>
                </div>
                <div class="col-md-2">
                    <input type="text" name="inf_lname" class="form-control" placeholder="Nom" required>
                </div>
                <div class="col-md-2">
                    <input type="text" name="inf_phone" class="form-control" placeholder="Téléphone" required>
                </div>
                <button type="submit" name="add_infirmier" class="btn btn-primary w-100 d-flex justify-content-center align-items-center">
                    <i class="fas fa-plus me-2"></i> Ajouter
                </button>
            </div>
        </form>
        <!-- Formulaire de recherche des infirmiers -->
        <form method="GET" class="mb-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <select name="search_type" class="form-select" required>
                        <option value="all">Tous</option>
                        <option value="inf_id">ID</option>
                        <option value="inf_fname">Prénom</option>
                        <option value="inf_lname">Nom</option>
                        <option value="inf_phone">Téléphone</option>
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
        <table id="infirmierTable" class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Prénom</th>
                    <th>Nom</th>
                    <th>Téléphone</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($infirmiers as $inf): ?>
                <tr>
                    <td><?= htmlspecialchars($inf['inf_id']); ?></td>
                    <td><?= htmlspecialchars($inf['inf_fname']); ?></td>
                    <td><?= htmlspecialchars($inf['inf_lname']); ?></td>
                    <td><?= htmlspecialchars($inf['inf_phone']); ?></td>
                    <td>
                        <div class="d-flex">
                            <!-- Bouton Modifier -->
                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal"
                                    data-id="<?= htmlspecialchars($inf['inf_id']); ?>"
                                    data-fname="<?= htmlspecialchars($inf['inf_fname']); ?>"
                                    data-lname="<?= htmlspecialchars($inf['inf_lname']); ?>"
                                    data-phone="<?= htmlspecialchars($inf['inf_phone']); ?>">
                                <i class="fas fa-edit"></i> Modifier
                            </button>

                            <!-- Bouton Supprimer -->
                            <button class="btn btn-danger btn-sm" onclick="deleteInfirmier('<?= htmlspecialchars($inf['inf_id']); ?>')">
                                <i class="fas fa-trash"></i> Supprimer
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <!-- Modal de modification -->
        <div class="modal fade" id="editModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Modifier l'Infirmier</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <!-- ID caché -->
                            <input type="hidden" name="inf_id" id="edit_id">
                            <!-- Prénom -->
                            <div class="mb-3">
                                <label for="edit_fname" class="form-label">Prénom</label>
                                <input type="text" name="inf_fname" id="edit_fname" class="form-control" required>
                            </div>
                            <!-- Nom -->
                            <div class="mb-3">
                                <label for="edit_lname" class="form-label">Nom</label>
                                <input type="text" name="inf_lname" id="edit_lname" class="form-control" required>
                            </div>
                            <!-- Téléphone -->
                            <div class="mb-3">
                                <label for="edit_phone" class="form-label">Téléphone</label>
                                <input type="text" name="inf_phone" id="edit_phone" class="form-control" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="update_infirmier" class="btn btn-primary">Enregistrer</button>
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
                // Initialiser la table DataTable
                $('#infirmierTable').DataTable({
                    searching: false // Désactive la barre de recherche pour la table
                });
            });

            // Fonction de suppression pour les infirmiers
            function deleteInfirmier(id) {
                if (confirm("Êtes-vous sûr de vouloir supprimer cet infirmier ?")) {
                    $('<form method="POST"><input type="hidden" name="delete_infirmier" value="' + id + '"></form>').appendTo('body').submit();
                }
            }

            // Remplir le modal avec les informations de l'infirmier
            $('#editModal').on('show.bs.modal', function (event) {
                const button = $(event.relatedTarget); // Le bouton qui déclenche l'ouverture du modal
                $('#edit_id').val(button.data('id')); // ID de l'infirmier
                $('#edit_fname').val(button.data('fname')); // Prénom
                $('#edit_lname').val(button.data('lname')); // Nom
                $('#edit_phone').val(button.data('phone')); // Téléphone
            });
        </script>
    </div> 
</body>
</html>