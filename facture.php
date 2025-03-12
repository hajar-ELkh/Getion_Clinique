<?php 
include 'connectDB.php'; // Connexion à la base de données
$errors = [];
$messages = [];

if (isset($_POST['add_facture'])) {
    $id_fac = trim($_POST['id_fac']);
    $montant = trim($_POST['montant']);
    $date_pay = trim($_POST['date_pay']);
    $pat_id = trim($_POST['pat_id']);
    $com_id = trim($_POST['com_id']);

    // Vérification des champs
    if (empty($id_fac) || empty($montant) || empty($date_pay) || empty($pat_id) || empty($com_id)) {
        $errors[] = "Tous les champs sont requis.";
    } else {
        // Vérification si le patient existe
        try {
            $stmt = $pdo->prepare("SELECT * FROM patient WHERE pat_id = ?");
            $stmt->execute([$pat_id]);

            if ($stmt->rowCount() == 0) {
                $errors[] = "Le patient avec l'ID spécifié n'existe pas.";
            } else {
                // Le patient existe, récupérer ses informations
                $patient = $stmt->fetch(PDO::FETCH_ASSOC);

                // Vérification si le comptable existe
                $stmt_comptable = $pdo->prepare("SELECT * FROM comptable WHERE com_id = ?");
                $stmt_comptable->execute([$com_id]);

                if ($stmt_comptable->rowCount() == 0) {
                    $errors[] = "Le comptable avec l'ID spécifié n'existe pas.";
                } else {
                    // Le comptable existe, récupérer ses informations
                    $comptable = $stmt_comptable->fetch(PDO::FETCH_ASSOC);

                    // Vérification si l'ID de la facture existe déjà
                    $stmt_facture = $pdo->prepare("SELECT * FROM facture WHERE id_fac = ?");
                    $stmt_facture->execute([$id_fac]);

                    if ($stmt_facture->rowCount() > 0) {
                        $errors[] = "L'ID de la facture existe déjà.";
                    } else {
                        // Insérer la nouvelle facture
                        $stmt_insert = $pdo->prepare("INSERT INTO facture (id_fac, montant, date_pay, pat_id, com_id) VALUES (?, ?, ?, ?, ?)");
                        $inserted = $stmt_insert->execute([$id_fac, $montant, $date_pay, $pat_id, $com_id]);

                        if ($inserted) {
                            $messages[] = "Facture ajoutée avec succès.";
                        } else {
                            $errors[] = "Erreur lors de l'ajout de la facture.";
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // Afficher l'erreur si une exception se produit pendant l'exécution
            $errors[] = "Erreur de base de données : " . $e->getMessage();
        }
    }
}

if (isset($_POST['update_facture'])) { 
    $id_fac = trim($_POST['id_fac']);
    $montant = trim($_POST['montant']);
    $date_pay = trim($_POST['date_pay']);
    $pat_id = trim($_POST['pat_id']);
    $com_id = trim($_POST['com_id']);

    // Vérification des champs
    if (empty($montant) || empty($date_pay) || empty($pat_id) || empty($com_id)) {
        $errors[] = "Tous les champs sont requis.";
    } else {
        // Vérification si la facture existe
        $stmt = $pdo->prepare("SELECT * FROM facture WHERE id_fac = ?");
        $stmt->execute([$id_fac]);
        if ($stmt->rowCount() == 0) {
            $errors[] = "La facture avec cet ID n'existe pas.";
        } else {
            // Vérification si le patient existe
            $stmt_patient = $pdo->prepare("SELECT * FROM patient WHERE pat_id = ?");
            $stmt_patient->execute([$pat_id]);
            if ($stmt_patient->rowCount() == 0) {
                $errors[] = "Le patient avec cet ID n'existe pas.";
            } else {
                // Vérification si le comptable existe
                $stmt_comptable = $pdo->prepare("SELECT * FROM comptable WHERE com_id = ?");
                $stmt_comptable->execute([$com_id]);
                if ($stmt_comptable->rowCount() == 0) {
                    $errors[] = "Le comptable avec cet ID n'existe pas.";
                } else {
                    // Mettre à jour la facture
                    $stmt_update = $pdo->prepare("UPDATE facture SET montant = ?, date_pay = ?, pat_id = ?, com_id = ? WHERE id_fac = ?");
                    if ($stmt_update->execute([$montant, $date_pay, $pat_id, $com_id, $id_fac])) {
                        $messages[] = "Facture modifiée avec succès.";
                    } else {
                        $errors[] = "Erreur lors de la modification de la facture.";
                    }
                }
            }
        }
    }
}



// Suppression d'une facture
if (isset($_POST['delete_facture'])) {
    $id_fac = trim($_POST['delete_facture']);
    $stmt = $pdo->prepare("DELETE FROM facture WHERE id_fac = ?");
    if ($stmt->execute([$id_fac])) {
        $messages[] = "Facture supprimée avec succès.";
    } else {
        $errors[] = "Erreur lors de la suppression de la facture.";
    }
}

// Vérification si les paramètres de recherche sont présents
if (isset($_GET['search_type']) && isset($_GET['search_value'])) {
    $search_type = trim($_GET['search_type']); // Type de recherche (colonne)
    $search_value = trim($_GET['search_value']); // Valeur de recherche

    // Liste des colonnes valides pour la recherche
    $allowed_columns = ['id_fac', 'montant', 'date_pay', 'pat_id', 'com_id'];

    // Si l'option 'All' est choisie, chercher sur toutes les colonnes
    if ($search_type === 'all') {
        // Recherche sur toutes les colonnes en combinant des conditions pour chaque colonne
        $stmt = $pdo->query("SELECT * FROM facture");
    }
    elseif (in_array($search_type, $allowed_columns)) {
        // Si la colonne est un champ VARCHAR, utiliser LIKE pour une recherche partielle
        if (in_array($search_type, ['montant', 'date_pay', 'pat_id', 'com_id'])) {
            $stmt = $pdo->prepare("SELECT * FROM facture WHERE $search_type LIKE ?");
            $stmt->execute(["$search_value%"]);
        } else {
            // Pour les colonnes non VARCHAR, utiliser une recherche exacte
            $stmt = $pdo->prepare("SELECT * FROM facture WHERE $search_type = ?");
            $stmt->execute([$search_value]);
        }
    } else {
        // Si la colonne spécifiée n'est pas valide, afficher toutes les factures
        $stmt = $pdo->query("SELECT * FROM facture");
    }
} else {
    // Si aucun critère de recherche n'est défini, afficher toutes les factures
    $stmt = $pdo->query("SELECT * FROM facture");
}

// Récupérer les résultats de la requête
$factures = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <a href="facture.php"><i class="fas fa-file-invoice"></i> Gérer les factures</a>
    
</div>
<div class="container mt-5">
    <h1 class="text-center"><i class="fas fa-file-invoice"></i> Gestion des Factures</h1>

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
        <form method="POST" class="mb-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <input type="text" name="id_fac" class="form-control" placeholder="ID Facture" required>
                </div>
                <div class="col-md-3">
                    <input type="number" name="montant" class="form-control" placeholder="Montant" required>
                </div>
                <div class="col-md-3">
                    <input type="date" name="date_pay" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <input type="text" name="pat_id" class="form-control" placeholder="ID Patient" required>
                </div>
                <div class="col-md-3">
                    <input type="text" name="com_id" class="form-control" placeholder="ID Comptable" required>
                </div>
                <div class="col-md-12">
                    <button type="submit" name="add_facture" class="btn btn-primary w-100 d-flex justify-content-center align-items-center">
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
                        <option value="id_fac">ID Facture</option>
                        <option value="montant">Montant</option>
                        <option value="date_pay">Date de Paiement</option>
                        <option value="pat_id">ID Patient</option>
                        <option value="com_id">ID Comptable</option>
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
        <!-- Tableau des factures -->
        <table id="factureTable" class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>ID Facture</th>
                    <th>Montant</th>
                    <th>Date de Paiement</th>
                    <th>ID Patient</th>
                    <th>ID Comptable</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($factures as $facture): ?>
                    <tr>
                    <tr><td><?= htmlspecialchars($facture['id_fac'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= htmlspecialchars($facture['montant'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= htmlspecialchars($facture['date_pay'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= htmlspecialchars($facture['pat_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= htmlspecialchars($facture['com_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>


                        <td>
                            <div class="d-flex">
                                <!-- Bouton pour modifier -->
                                <button class="btn btn-warning btn-sm me-2" data-bs-toggle="modal" data-bs-target="#editModal" 
                                        data-id="<?= $facture['id_fac']; ?>" 
                                        data-montant="<?= $facture['montant']; ?>" 
                                        data-date_pay="<?= $facture['date_pay']; ?>" 
                                        data-pat_id="<?= $facture['pat_id']; ?>" 
                                        data-com_id="<?= $facture['com_id']; ?>">
                                    <i class="fas fa-edit"></i> Modifier
                                </button>

                                <!-- Bouton pour supprimer -->
                                <form method="POST" action="" class="d-inline">
                                    <button type="submit" name="delete_facture" value="<?= $facture['id_fac']; ?>" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i> Supprimer
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="modal fade" id="editModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Modifier la Facture</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="id_fac" id="edit_id_fac">
                            <div class="mb-3">
                                <label for="edit_montant" class="form-label">Montant</label>
                                <input type="number" step="0.01" name="montant" id="edit_montant" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_date_pay" class="form-label">Date de Paiement</label>
                                <input type="date" name="date_pay" id="edit_date_pay" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_pat_id" class="form-label">ID Patient</label>
                                <input type="text" name="pat_id" id="edit_pat_id" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_com_id" class="form-label">ID Comptable</label>
                                <input type="text" name="com_id" id="edit_com_id" class="form-control" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="update_facture" class="btn btn-primary">Enregistrer</button>
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
        $('#factureTable').DataTable({
            searching: false // Désactive la barre de recherche pour la table des factures
        });
    });

    function deleteFacture(id) {
        if (confirm("Êtes-vous sûr de vouloir supprimer cette facture ?")) {
            $('<form method="POST"><input type="hidden" name="delete_facture" value="' + id + '"></form>').appendTo('body').submit();
        }
    }

    $('#editModal').on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        $('#edit_id_fac').val(button.data('id'));
        $('#edit_montant').val(button.data('montant'));
        $('#edit_date_pay').val(button.data('date_pay'));
        $('#edit_pat_id').val(button.data('pat_id'));
        $('#edit_com_id').val(button.data('com_id'));
    });
</script>
</body>
</html>