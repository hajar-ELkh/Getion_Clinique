<?php
include 'connectDB.php'; // Connexion à la base de données

// Initialisation des messages
$messages = [];
$errors = [];

// Ajout d'un nouveau comptable
if (isset($_POST['add_comptable'])) {
    $com_id = trim($_POST['com_id']);
    $prenom = trim($_POST['prenom']);
    $nom = trim($_POST['nom']);
    $telephone = trim($_POST['telephone']);

    if (empty($com_id) || empty($prenom) || empty($nom) || empty($telephone)) {
        $errors[] = "Tous les champs sont requis.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM comptable WHERE com_id = ?");
        $stmt->execute([$com_id]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "L'ID du comptable existe déjà.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO comptable (com_id, com_fname, com_lname, com_phone) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$com_id, $prenom, $nom, $telephone])) {
                $messages[] = "Comptable ajouté avec succès.";
            } else {
                $errors[] = "Erreur lors de l'ajout du comptable.";
            }
        }
    }
}


// Modification d'un comptable
if (isset($_POST['update_comptable'])) {
    $com_id = trim($_POST['com_id']);
    $prenom = trim($_POST['prenom']);
    $nom = trim($_POST['nom']);
    $telephone = trim($_POST['telephone']);

    if (empty($prenom) || empty($nom) || empty($telephone)) {
        $errors[] = "Tous les champs sont requis.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM comptable WHERE com_id = ?");
        $stmt->execute([$com_id]);
        if ($stmt->rowCount() == 0) {
            $errors[] = "Le comptable avec cet ID n'existe pas.";
        } else {
            $stmt = $pdo->prepare("UPDATE comptable SET com_fname = ?, com_lname = ?, com_phone = ? WHERE com_id = ?");
            if ($stmt->execute([$prenom, $nom, $telephone, $com_id])) {
                $messages[] = "Comptable modifié avec succès.";
            } else {
                $errors[] = "Erreur lors de la modification du comptable.";
            }
        }
    }
}

// Suppression d'un comptable
if (isset($_POST['delete_comptable'])) {
    $com_id = trim($_POST['delete_comptable']);  // Récupère l'ID du comptable à supprimer

    // Vérifier si l'ID du comptable existe
    if (!empty($com_id)) {
        $stmt = $pdo->prepare("SELECT * FROM comptable WHERE com_id = ?");
        $stmt->execute([$com_id]);
        
        if ($stmt->rowCount() > 0) {
            // Le comptable existe, vérifier s'il est lié à la table facture
            $stmt = $pdo->prepare("SELECT * FROM facture WHERE com_id = ?");
            $stmt->execute([$com_id]);
            $linked = $stmt->rowCount() > 0;

            if ($linked) {
                // Ouvrir la modal
                echo '<div class="modal" style="display: flex; justify-content: center; align-items: center; height: 100vh; position: fixed; width: 100%; top: 0; left: 0; background: rgba(0, 0, 0, 0.5); z-index: 999;">';

                // Contenu de l'alerte
                echo '<div class="alert" style="padding: 40px; background: rgb(195, 203, 210); color: black; border-radius: 12px; width: 80%; max-width: 700px; min-width: 400px; height: auto; max-height: 80%; overflow-y: auto; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3); font-size: 16px; word-wrap: break-word; line-height: 1.5;">';

                // Message d'avertissement
                echo '<p style="margin-top: 20px;">Ce comptable est lié à des enregistrements dans la table facture.</p>';
                echo '<p style="margin-top: 20px;">Souhaitez-vous supprimer tous les enregistrements associés ?</p>';

                // Formulaire de confirmation
                echo '<form method="POST" style="margin-top: 20px;">';
                echo '<input type="hidden" name="com_id" value="' . htmlspecialchars($com_id) . '">';
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
                echo '<div style="display: flex; justify-content: space-between; margin-top: 20px;">';
                echo '<div>';
                echo '<input type="submit" name="confirm_delete" value="Confirmer" class="btn btn-danger" style="padding: 10px 20px; background-color: red; color: white; border: none; border-radius: 5px; cursor: pointer;">';
                echo '</div>';
                echo '<div>';
                echo '<button type="button" class="btn btn-secondary" onclick="this.closest(\'.modal\').style.display=\'none\'" style="padding: 10px 20px; background-color: gray; color: white; border: none; border-radius: 5px; cursor: pointer;">Annuler</button>';
                echo '</div>';
                echo '</div>';
                echo '</form>';
                echo '</div>';
                echo '</div>';
            } else {
                // Supprimer directement le comptable s'il n'est lié à aucune facture
                $stmt = $pdo->prepare("DELETE FROM comptable WHERE com_id = ?");
                if ($stmt->execute([$com_id])) {
                    $messages[] = "Comptable supprimé avec succès.";
                } else {
                    $errors[] = "Erreur lors de la suppression du comptable.";
                }
            }
        } else {
            $errors[] = "Le comptable avec cet ID n'existe pas.";
        }
    } else {
        $errors[] = "L'ID du comptable est requis pour la suppression.";
    }
}

// Traitement de la confirmation de suppression
if (isset($_POST['confirm_delete'])) {
    $com_id = trim($_POST['com_id']);
    $delete_related = $_POST['delete_related'];

    if ($delete_related === 'yes') {
        // Supprimer les enregistrements associés dans la table facture
        $stmt = $pdo->prepare("DELETE FROM facture WHERE com_id = ?");
        $stmt->execute([$com_id]);

        // Supprimer le comptable
        $stmt = $pdo->prepare("DELETE FROM comptable WHERE com_id = ?");
        if ($stmt->execute([$com_id])) {
            $messages[] = "Comptable et toutes ses factures associées ont été supprimés avec succès.";
        } else {
            $errors[] = "Erreur lors de la suppression du comptable.";
        }
    } else {
        $messages[] = "Aucune facture associée n'a été supprimée.";
    }
}

// Vérification si les paramètres de recherche sont présents
if (isset($_GET['search_type']) && isset($_GET['search_value'])) {
    $search_type = trim($_GET['search_type']); // Type de recherche (colonne)
    $search_value = trim($_GET['search_value']); // Valeur de recherche

    // Liste des colonnes valides pour la recherche
    $allowed_columns = ['com_id', 'com_fname', 'com_lname', 'com_phone'];

    // Si l'option 'All' est choisie, chercher sur toutes les colonnes
    if ($search_type === 'all') {
        // Recherche sur toutes les colonnes en combinant des conditions pour chaque colonne
        $stmt = $pdo->query("SELECT * FROM comptable");
    }
    elseif (in_array($search_type, $allowed_columns)) {
        // Si la colonne est un champ VARCHAR, utiliser LIKE pour une recherche partielle
        if (in_array($search_type, ['com_fname', 'com_lname', 'com_phone'])) {
            $stmt = $pdo->prepare("SELECT * FROM comptable WHERE $search_type LIKE ?");
            $stmt->execute(["$search_value%"]);
        } else {
            // Pour les colonnes non VARCHAR, utiliser une recherche exacte
            $stmt = $pdo->prepare("SELECT * FROM comptable WHERE $search_type = ?");
            $stmt->execute([$search_value]);
        }
    } else {
        // Si la colonne spécifiée n'est pas valide, afficher tous les comptables
        $stmt = $pdo->query("SELECT * FROM comptable");
    }
} else {
    // Si aucun critère de recherche n'est défini, afficher tous les comptables
    $stmt = $pdo->query("SELECT * FROM comptable");
}

// Récupérer les résultats de la requête
$accountants = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Comptables</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://cdn.jsdelivr.net/npm/font-awesome@6.0.0/css/all.min.css" rel="stylesheet">
<style>  
        body {  
            font-family: Arial, sans-serif;  
            background: url('img/0.jpg') no-repeat center center fixed; /* Ajustez le chemin de l'image ici */  
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
        <a href="comptable.php"><i class="fas fa-users"></i> Gérer Comptable</a>
        <a href="facture.php"><i class="fas fa-file-invoice"></i> Gérer les factures</a>
    </div>
    <div class="container mt-5">
        <h1 class="text-center"><i class="fas fa-user-tie"></i> Gestion des Comptables</h1>

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

        <!-- Formulaire d'ajout -->
        <form method="POST" class="mb-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <input type="text" name="com_id" class="form-control" placeholder="ID" required>
                </div>
                <div class="col-md-3">
                    <input type="text" name="prenom" class="form-control" placeholder="Prénom" required>
                </div>
                <div class="col-md-3">
                    <input type="text" name="nom" class="form-control" placeholder="Nom" required>
                </div>
                <div class="col-md-3">
                    <input type="text" name="telephone" class="form-control" placeholder="Téléphone" required>
                </div>
                <div class="col-md-12">
                <button type="submit" name="add_comptable" class="btn btn-primary w-100 d-flex justify-content-center align-items-center">
                    <i class="fas fa-plus me-2"></i> Ajouter
                </button>

                </div>
            </div>
        </form>
        <form method="GET" class="mb-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <select name="search_type" class="form-select" required>
                        <option value="all">All</option>
                        <option value="com_id">ID</option>
                        <option value="com_fname">Prénom</option>
                        <option value="com_lname">Nom</option>
                        <option value="com_phone">Téléphone</option>
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
        
        <!-- Tableau des comptables -->
        <table id="comptableTable" class="table table-striped table-hover">
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
                <?php foreach ($accountants as $accountant): ?>
                    <tr>
                        <td><?= htmlspecialchars($accountant['com_id']); ?></td>
                        <td><?= htmlspecialchars($accountant['com_fname']); ?></td>
                        <td><?= htmlspecialchars($accountant['com_lname']); ?></td>
                        <td><?= htmlspecialchars($accountant['com_phone']); ?></td>
                        <td>
    <div class="d-flex">
        <!-- Bouton pour modifier -->
        <button class="btn btn-warning btn-sm me-2" data-bs-toggle="modal" data-bs-target="#editModal" data-id="<?= $accountant['com_id']; ?>" data-prenom="<?= $accountant['com_fname']; ?>" data-nom="<?= $accountant['com_lname']; ?>" data-telephone="<?= $accountant['com_phone']; ?>">
            <i class="fas fa-edit"></i> Modifier
        </button>

        <!-- Bouton pour supprimer -->
        <form method="POST" action="" class="d-inline">
            <button type="submit" name="delete_comptable" value="<?= $accountant['com_id']; ?>" class="btn btn-danger btn-sm">
                <i class="fas fa-trash"></i> Supprimer
            </button>
        </form>
    </div>
</td>

                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Modal pour modifier -->
        <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Modifier le comptable</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST">
                            <div class="form-group">
                                <input type="hidden" class="form-control" id="com_id_edit" name="com_id" readonly>
                            </div>
                            <div class="form-group">
                                <label for="prenom_edit">Prénom</label>
                                <input type="text" class="form-control" id="prenom_edit" name="prenom">
                            </div>
                            <div class="form-group">
                                <label for="nom_edit">Nom</label>
                                <input type="text" class="form-control" id="nom_edit" name="nom">
                            </div>
                            <div class="form-group">
                                <label for="telephone_edit">Téléphone</label>
                                <input type="text" class="form-control" id="telephone_edit" name="telephone">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" name="update_comptable" class="btn btn-primary">Sauvegarder</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#comptableTable').DataTable({
                searching: false // Désactive la barre de recherche
            });
        });

        // Fonction pour charger les données dans le modal de modification
        $('#editModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var com_id = button.data('id');
            var prenom = button.data('prenom');
            var nom = button.data('nom');
            var telephone = button.data('telephone');

            var modal = $(this);
            modal.find('#com_id_edit').val(com_id);
            modal.find('#prenom_edit').val(prenom);
            modal.find('#nom_edit').val(nom);
            modal.find('#telephone_edit').val(telephone);
        });
    </script>
</body>
</html>