<?php
include 'connectDB.php'; // Connexion à la base de données
$errors = [];
$messages = [];

if (isset($_POST['add_analyse_med'])) {
    $id_analyse_med = trim($_POST['id_analyse_med']);
    $date_analyse_med = trim($_POST['date_analyse_med']);
    $desc_analyse_med = trim($_POST['desc_analyse_med']);
    $pat_id = trim($_POST['pat_id']);
    $tech_labo_id = trim($_POST['tech_labo_id']);
    $doc_id = trim($_POST['doc_id']);

    // Vérification des champs
    if (empty($id_analyse_med) || empty($date_analyse_med) || empty($desc_analyse_med) || empty($pat_id) || empty($tech_labo_id) || empty($doc_id)) {
        $errors[] = "Tous les champs sont requis.";
    } else {
        try {
            // Vérification si le patient existe
            $stmt_patient = $pdo->prepare("SELECT * FROM patient WHERE pat_id = ?");
            $stmt_patient->execute([$pat_id]);

            if ($stmt_patient->rowCount() == 0) {
                $errors[] = "Le patient avec l'ID spécifié n'existe pas.";
            } else {
                // Vérification si le technicien de laboratoire existe
                $stmt_tech = $pdo->prepare("SELECT * FROM technicien_labo WHERE tech_labo_id = ?");
                $stmt_tech->execute([$tech_labo_id]);

                if ($stmt_tech->rowCount() == 0) {
                    $errors[] = "Le technicien de laboratoire avec l'ID spécifié n'existe pas.";
                } else {
                    // Vérification si le docteur existe
                    $stmt_doc = $pdo->prepare("SELECT * FROM doctor WHERE doc_id = ?");
                    $stmt_doc->execute([$doc_id]);

                    if ($stmt_doc->rowCount() == 0) {
                        $errors[] = "Le docteur avec l'ID spécifié n'existe pas.";
                    } else {
                        // Vérification si l'ID de l'analyse existe déjà
                        $stmt_analyse = $pdo->prepare("SELECT * FROM analyse_medicale WHERE id_analyse_med = ?");
                        $stmt_analyse->execute([$id_analyse_med]);

                        if ($stmt_analyse->rowCount() > 0) {
                            $errors[] = "L'ID de l'analyse existe déjà.";
                        } else {
                            // Insérer la nouvelle analyse médicale
                            $stmt_insert = $pdo->prepare("INSERT INTO analyse_medicale (id_analyse_med, date_analyse_med, desc_analyse_med, pat_id, tech_labo_id, doc_id) VALUES (?, ?, ?, ?, ?, ?)");
                            $inserted = $stmt_insert->execute([$id_analyse_med, $date_analyse_med, $desc_analyse_med, $pat_id, $tech_labo_id, $doc_id]);

                            if ($inserted) {
                                $messages[] = "Analyse médicale ajoutée avec succès.";
                            } else {
                                $errors[] = "Erreur lors de l'ajout de l'analyse médicale.";
                            }
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
if (isset($_POST['update_analyse_med'])) { 
    $id_analyse_med = trim($_POST['id_analyse_med']);
    $date_analyse_med = trim($_POST['date_analyse_med']);
    $desc_analyse_med = trim($_POST['desc_analyse_med']);
    $pat_id = trim($_POST['pat_id']);
    $tech_labo_id = trim($_POST['tech_labo_id']);
    $doc_id = trim($_POST['doc_id']);

    if (empty($date_analyse_med) || empty($desc_analyse_med) || empty($pat_id) || empty($tech_labo_id) || empty($doc_id)) {
        $errors[] = "Tous les champs sont requis.";
    } else {
        // Vérifier si l'analyse médicale existe
        $stmt = $pdo->prepare("SELECT * FROM analyse_medicale WHERE id_analyse_med = ?");
        $stmt->execute([$id_analyse_med]);

        if ($stmt->rowCount() == 0) {
            $errors[] = "L'analyse médicale avec cet ID n'existe pas.";
        } else {
            // Vérifier si le patient existe
            $stmt_patient = $pdo->prepare("SELECT * FROM patient WHERE pat_id = ?");
            $stmt_patient->execute([$pat_id]);
            if ($stmt_patient->rowCount() == 0) {
                $errors[] = "Le patient avec cet ID n'existe pas.";
            } else {
                // Vérifier si le technicien de laboratoire existe
                $stmt_tech = $pdo->prepare("SELECT * FROM technicien_labo WHERE tech_labo_id = ?");
                $stmt_tech->execute([$tech_labo_id]);
                if ($stmt_tech->rowCount() == 0) {
                    $errors[] = "Le technicien de laboratoire avec cet ID n'existe pas.";
                } else {
                    // Vérifier si le docteur existe
                    $stmt_doc = $pdo->prepare("SELECT * FROM doctor WHERE doc_id = ?");
                    $stmt_doc->execute([$doc_id]);
                    if ($stmt_doc->rowCount() == 0) {
                        $errors[] = "Le docteur avec cet ID n'existe pas.";
                    } else {
                        // Mettre à jour l'analyse médicale
                        $stmt_update = $pdo->prepare("UPDATE analyse_medicale SET date_analyse_med = ?, desc_analyse_med = ?, pat_id = ?, tech_labo_id = ?, doc_id = ? WHERE id_analyse_med = ?");
                        if ($stmt_update->execute([$date_analyse_med, $desc_analyse_med, $pat_id, $tech_labo_id, $doc_id, $id_analyse_med])) {
                            $messages[] = "Analyse médicale modifiée avec succès.";
                        } else {
                            $errors[] = "Erreur lors de la modification de l'analyse médicale.";
                        }
                    }
                }
            }
        }
    }
}
// Suppression d'une analyse médicale
if (isset($_POST['delete_analyse_med'])) {
    $id_analyse_med = trim($_POST['delete_analyse_med']);

    // Vérifier si l'analyse médicale existe
    $stmt_check = $pdo->prepare("SELECT * FROM analyse_medicale WHERE id_analyse_med = ?");
    $stmt_check->execute([$id_analyse_med]);

    if ($stmt_check->rowCount() == 0) {
        $errors[] = "L'analyse médicale avec cet ID n'existe pas.";
    } else {
        // Supprimer l'analyse médicale
        $stmt_delete = $pdo->prepare("DELETE FROM analyse_medicale WHERE id_analyse_med = ?");
        if ($stmt_delete->execute([$id_analyse_med])) {
            $messages[] = "Analyse médicale supprimée avec succès.";
        } else {
            $errors[] = "Erreur lors de la suppression de l'analyse médicale.";
        }
    }
}
// Vérification si les paramètres de recherche sont présents
if (isset($_GET['search_type']) && isset($_GET['search_value'])) {
    $search_type = trim($_GET['search_type']); // Type de recherche (colonne)
    $search_value = trim($_GET['search_value']); // Valeur de recherche

    // Liste des colonnes valides pour la recherche
    $allowed_columns = ['id_analyse_med', 'date_analyse_med', 'desc_analyse_med', 'pat_id', 'tech_labo_id', 'doc_id'];

    // Si l'option 'All' est choisie, chercher sur toutes les colonnes
    if ($search_type === 'all') {
        // Recherche sur toutes les colonnes en combinant des conditions pour chaque colonne
        $stmt = $pdo->query("SELECT * FROM analyse_medicale");
    }
    elseif (in_array($search_type, $allowed_columns)) {
        // Si la colonne est un champ VARCHAR ou texte, utiliser LIKE pour une recherche partielle
        if (in_array($search_type, ['desc_analyse_med', 'date_analyse_med', 'pat_id', 'tech_labo_id', 'doc_id'])) {
            $stmt = $pdo->prepare("SELECT * FROM analyse_medicale WHERE $search_type LIKE ?");
            $stmt->execute(["$search_value%"]);
        } else {
            // Pour les colonnes non VARCHAR, utiliser une recherche exacte
            $stmt = $pdo->prepare("SELECT * FROM analyse_medicale WHERE $search_type = ?");
            $stmt->execute([$search_value]);
        }
    } else {
        // Si la colonne spécifiée n'est pas valide, afficher toutes les analyses médicales
        $stmt = $pdo->query("SELECT * FROM analyse_medicale");
    }
} else {
    // Si aucun critère de recherche n'est défini, afficher toutes les analyses médicales
    $stmt = $pdo->query("SELECT * FROM analyse_medicale");
}

// Récupérer les résultats de la requête
$analyses = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            background: url('img/analyse.jpg') no-repeat center center fixed; /* Ajustez le chemin de l'image ici */  
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
        <a href="doctor.php"><i class="fas fa-user-md"></i> Gérer les docteurs</a> 
        <a href="analyse_med.php"><i class="fas fa-vials"></i> Gérer les analyses</a>  
        <a href="patient.php"><i class="fas fa-procedures"></i> Gérer les patients</a>  
        <a href="technicien_labo.php"><i class="fas fa-flask"></i> Gérer les techniciens de laboratoire</a>  
    </div>
<div class="container mt-5">
    <h1 class="text-center"><i class="fas fa-vials"></i> Gestion des Analyses Médicales</h1>

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
                <input type="text" name="id_analyse_med" class="form-control" placeholder="ID Analyse Médicale" required>
            </div>
            <div class="col-md-3">
                <input type="date" name="date_analyse_med" class="form-control" required>
            </div>
            <div class="col-md-3">
                <input type="text" name="desc_analyse_med" class="form-control" placeholder="Description Analyse" required>
            </div>
            <div class="col-md-3">
                <input type="text" name="pat_id" class="form-control" placeholder="ID Patient" required>
            </div>
            <div class="col-md-3">
                <input type="text" name="tech_labo_id" class="form-control" placeholder="ID Technicien Labo" required>
            </div>
            <div class="col-md-3">
                <input type="text" name="doc_id" class="form-control" placeholder="ID Docteur" required>
            </div>
            <div class="col-md-12">
                <button type="submit" name="add_analyse_med" class="btn btn-primary w-100 d-flex justify-content-center align-items-center">
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
                    <option value="id_analyse_med">ID Analyse Médicale</option>
                    <option value="date_analyse_med">Date d'Analyse</option>
                    <option value="desc_analyse_med">Description Analyse</option>
                    <option value="pat_id">ID Patient</option>
                    <option value="tech_labo_id">ID Technicien Labo</option>
                    <option value="doc_id">ID Docteur</option>
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
    <!-- Tableau des analyses médicales -->
<table id="analyseTable" class="table table-striped table-hover">
    <thead>
        <tr>
            <th>ID Analyse Médicale</th>
            <th>Date d'Analyse</th>
            <th>Description</th>
            <th>ID Patient</th>
            <th>ID Technicien Labo</th>
            <th>ID Docteur</th>
            <th>Actions</th>
        </tr>
    </thead>
        <tbody>
            <?php foreach ($analyses as $analyse): ?>
                <tr>
                    <td><?= htmlspecialchars($analyse['id_analyse_med']); ?></td>
                    <td><?= htmlspecialchars($analyse['date_analyse_med']); ?></td>
                    <td><?= htmlspecialchars($analyse['desc_analyse_med']); ?></td>
                    <td><?= htmlspecialchars($analyse['pat_id']); ?></td>
                    <td><?= htmlspecialchars($analyse['tech_labo_id']); ?></td>
                    <td><?= htmlspecialchars($analyse['doc_id']); ?></td>
                    <td>
                        <div class="d-flex">
                            <!-- Bouton pour modifier -->
                            <button class="btn btn-warning btn-sm me-2" data-bs-toggle="modal" data-bs-target="#editModal" 
                                    data-id="<?= $analyse['id_analyse_med']; ?>" 
                                    data-date_analyse_med="<?= $analyse['date_analyse_med']; ?>" 
                                    data-desc_analyse_med="<?= $analyse['desc_analyse_med']; ?>" 
                                    data-pat_id="<?= $analyse['pat_id']; ?>" 
                                    data-tech_labo_id="<?= $analyse['tech_labo_id']; ?>" 
                                    data-doc_id="<?= $analyse['doc_id']; ?>">
                                <i class="fas fa-edit"></i> Modifier
                            </button>

                            <!-- Bouton pour supprimer -->
                            <form method="POST" action="" class="d-inline">
                                <button type="submit" name="delete_analyse_med" value="<?= $analyse['id_analyse_med']; ?>" class="btn btn-danger btn-sm">
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
                    <h5 class="modal-title">Modifier l'Analyse Médicale</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="id_analyse_med" id="edit_id_analyse_med">
                        <div class="mb-3">
                            <label for="edit_date_analyse_med" class="form-label">Date d'Analyse</label>
                            <input type="date" name="date_analyse_med" id="edit_date_analyse_med" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_desc_analyse_med" class="form-label">Description</label>
                            <textarea name="desc_analyse_med" id="edit_desc_analyse_med" class="form-control" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_pat_id" class="form-label">ID Patient</label>
                            <input type="text" name="pat_id" id="edit_pat_id" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_tech_labo_id" class="form-label">ID Technicien Labo</label>
                            <input type="text" name="tech_labo_id" id="edit_tech_labo_id" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_doc_id" class="form-label">ID Docteur</label>
                            <input type="text" name="doc_id" id="edit_doc_id" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="update_analyse_med" class="btn btn-primary">Enregistrer</button>
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
            $('#analyseTable').DataTable({
                searching: false // Désactive la barre de recherche pour la table des analyses médicales
            });
        });

        function deleteAnalyse(id) {
            if (confirm("Êtes-vous sûr de vouloir supprimer cette analyse médicale ?")) {
                $('<form method="POST"><input type="hidden" name="delete_analyse_med" value="' + id + '"></form>').appendTo('body').submit();
            }
        }

        $('#editModal').on('show.bs.modal', function (event) {
            const button = $(event.relatedTarget);
            $('#edit_id_analyse_med').val(button.data('id'));
            $('#edit_date_analyse_med').val(button.data('date_analyse_med'));
            $('#edit_desc_analyse_med').val(button.data('desc_analyse_med'));
            $('#edit_pat_id').val(button.data('pat_id'));
            $('#edit_tech_labo_id').val(button.data('tech_labo_id'));
            $('#edit_doc_id').val(button.data('doc_id'));
        });
    </script>
</body>
</html>