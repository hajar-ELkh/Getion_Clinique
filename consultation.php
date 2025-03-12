<?php 
include 'connectDB.php'; // Connexion à la base de données
$errors = [];
$messages = [];

if (isset($_POST['add_consultation'])) {
    $id_consul = trim($_POST['id_consul']);
    $date_consul = trim($_POST['date_consul']);
    $desc_consul = trim($_POST['desc_consul']);
    $obser_medecin = trim($_POST['obser_medecin']);
    $pat_id = trim($_POST['pat_id']);
    $doc_id = trim($_POST['doc_id']);

    // Vérification des champs
    if (empty($id_consul) || empty($date_consul) || empty($obser_medecin) || empty($pat_id) || empty($doc_id) || empty($desc_consul)) {
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

                // Vérification si le doctor existe
                $stmt_doctor = $pdo->prepare("SELECT * FROM doctor WHERE doc_id = ?");
                $stmt_doctor->execute([$doc_id]);

                if ($stmt_doctor->rowCount() == 0) {
                    $errors[] = "Le doctor avec l'ID spécifié n'existe pas.";
                } else {
                    // Le doctor existe, récupérer ses informations
                    $doctor = $stmt_doctor->fetch(PDO::FETCH_ASSOC);

                    // Vérification si l'ID de la consultation existe déjà
                    $stmt_consultation = $pdo->prepare("SELECT * FROM consultation WHERE id_consul = ?");
                    $stmt_consultation->execute([$id_consul]);

                    if ($stmt_consultation->rowCount() > 0) {
                        $errors[] = "L'ID de la consultation existe déjà.";
                    } else {
                        // Insérer la nouvelle consultation
                        $stmt_insert = $pdo->prepare("INSERT INTO consultation (id_consul, date_consul, desc_consul, obser_medecin, pat_id, doc_id) VALUES (?, ?, ?, ?, ? ,?)");
                        $inserted = $stmt_insert->execute([$id_consul, $date_consul, $desc_consul ,$obser_medecin, $pat_id, $doc_id]);

                        if ($inserted) {
                            $messages[] = "consultation ajoutée avec succès.";
                        } else {
                            $errors[] = "Erreur lors de l'ajout de la consultation.";
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

if (isset($_POST['update_consultation'])) { 
    $id_consul = trim($_POST['id_consul']);
    $date_consul = trim($_POST['date_consul']);
    $desc_consul = trim($_POST['desc_consul']);
    $obser_medecin = trim($_POST['obser_medecin']);
    $pat_id = trim($_POST['pat_id']);
    $doc_id = trim($_POST['doc_id']);

    if (empty($date_consul) || empty($obser_medecin) || empty($pat_id) || empty($doc_id)) {
        $errors[] = "Tous les champs sont requis.";
    } else {
        // Vérifier si la consultation existe
        $stmt = $pdo->prepare("SELECT * FROM consultation WHERE id_consul = ?");
        $stmt->execute([$id_consul]);

        if ($stmt->rowCount() == 0) {
            $errors[] = "La consultation avec cet ID n'existe pas.";
        } else {
            // Vérifier si le patient existe
            $stmt_patient = $pdo->prepare("SELECT * FROM patient WHERE pat_id = ?");
            $stmt_patient->execute([$pat_id]);
            if ($stmt_patient->rowCount() == 0) {
                $errors[] = "Le patient avec cet ID n'existe pas.";
            } else {
                // Vérifier si le médecin existe
                $stmt_doc = $pdo->prepare("SELECT * FROM doctor WHERE doc_id = ?");
                $stmt_doc->execute([$doc_id]);
                if ($stmt_doc->rowCount() == 0) {
                    $errors[] = "Le docteur avec cet ID n'existe pas.";
                } else {
                    // Mettre à jour la consultation
                    $stmt_update = $pdo->prepare("UPDATE consultation SET date_consul = ?, desc_consul = ?, obser_medecin = ?, pat_id = ?, doc_id = ? WHERE id_consul = ?");
                    if ($stmt_update->execute([$date_consul, $desc_consul, $obser_medecin, $pat_id, $doc_id, $id_consul])) {
                        $messages[] = "Consultation modifiée avec succès.";
                    } else {
                        $errors[] = "Erreur lors de la modification de la consultation.";
                    }
                }
            }
        }
    }
}



// Suppression d'une consultation
if (isset($_POST['delete_consultation'])) {
    $id_consul = trim($_POST['delete_consultation']);
    $stmt = $pdo->prepare("DELETE FROM consultation WHERE id_consul = ?");
    if ($stmt->execute([$id_consul])) {
        $messages[] = "consultation supprimée avec succès.";
    } else {
        $errors[] = "Erreur lors de la suppression de la consultation.";
    }
}

// Vérification si les paramètres de recherche sont présents
if (isset($_GET['search_type']) && isset($_GET['search_value'])) {
    $search_type = trim($_GET['search_type']); // Type de recherche (colonne)
    $search_value = trim($_GET['search_value']); // Valeur de recherche

    // Liste des colonnes valides pour la recherche
    $allowed_columns = ['id_consul', 'date_condul', 'desc_condul' , 'obser_medecin', 'pat_id', 'doc_id'];

    // Si l'option 'All' est choisie, chercher sur toutes les colonnes
    if ($search_type === 'all') {
        // Recherche sur toutes les colonnes en combinant des conditions pour chaque colonne
        $stmt = $pdo->query("SELECT * FROM consultation");
    }
    elseif (in_array($search_type, $allowed_columns)) {
        // Si la colonne est un champ VARCHAR, utiliser LIKE pour une recherche partielle
        if (in_array($search_type, ['date_consul', 'desc_condul' ,'obser_medecin', 'pat_id', 'doc_id'])) {
            $stmt = $pdo->prepare("SELECT * FROM consultation WHERE $search_type LIKE ?");
            $stmt->execute(["$search_value%"]);
        } else {
            // Pour les colonnes non VARCHAR, utiliser une recherche exacte
            $stmt = $pdo->prepare("SELECT * FROM consultation WHERE $search_type = ?");
            $stmt->execute([$search_value]);
        }
    } else {
        // Si la colonne spécifiée n'est pas valide, afficher toutes les consultations
        $stmt = $pdo->query("SELECT * FROM consultation");
    }
} else {
    // Si aucun critère de recherche n'est défini, afficher toutes les consultations
    $stmt = $pdo->query("SELECT * FROM consultation");
}

// Récupérer les résultats de la requête
$consultation= $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <a href="doctor.php"><i class="fas fa-file-invoice"></i> Gérer les doctor</a>
    <a href="consultation.php"><i class="fas fa-file-invoice"></i> Gérer les consultations</a>
    <a href="patient.php"><i class="fas fa-user-injured"></i> Gérer Patients</a>
</div>
<div class="container mt-5">
    <h1 class="text-center"><i class="fas fa-file-invoice"></i> Gestion des consultations</h1>

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
                    <input type="number" name="id_consul" class="form-control" placeholder="ID Consultation" required>
                </div>
                <div class="col-md-3">
                    <input type="date" name="date_consul" class="form-control" placeholder="Date" required>
                </div>
                <div class="col-md-3">
                    <input type="text" name="desc_consul" class="form-control" required>
                </div>
                
                <div class="col-md-3">
                    <input type="text" name="obser_medecin" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <input type="number" name="pat_id" class="form-control" placeholder="ID Patient" required>
                </div>
                <div class="col-md-3">
                    <input type="number" name="doc_id" class="form-control" placeholder="ID Doctor" required>
                </div>
                <div class="col-md-12">
                    <button type="submit" name="add_consultation" class="btn btn-primary w-100 d-flex justify-content-center align-items-center">
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
                        <option value="id_consul">ID consultation</option>
                        <option value="date_consul">Date</option>
                        <option value="desc_consul">Description consultation</option>
                        <option value="obser_medecin">Observation medecin</option>
                        <option value="pat_id">ID Patient</option>
                        <option value="doc_id">ID Doctor</option>
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
        <!-- Tableau des consultations -->
        <table id="consultationTable" class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>ID consultation</th>
                    <th>date</th>
                    <th>desc_consul</th>
                    <th>obser_medecin</th>
                    <th>ID Patient</th>
                    <th>ID Doctor</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($consultation as $consultations): ?>
                    <tr>
                        <td><?= htmlspecialchars($consultations['id_consul']); ?></td>
                        <td><?= htmlspecialchars($consultations['date_consul']); ?></td>
                        <td><?= htmlspecialchars($consultations['desc_consul']); ?></td>
                        <td><?= htmlspecialchars($consultations['obser_medecin']); ?></td>
                        <td><?= htmlspecialchars($consultations['pat_id']); ?></td>
                        <td><?= htmlspecialchars($consultations['doc_id']); ?></td>
                        <td>
                            <div class="d-flex">
                                <!-- Bouton pour modifier -->
                                <button class="btn btn-warning btn-sm me-2" data-bs-toggle="modal" data-bs-target="#editModal" 
                                        data-id_consul="<?= $consultations['id_consul']; ?>" 
                                        data-date_consul="<?= $consultations['date_consul']; ?>" 
                                        data-desc_consul="<?= $consultations['desc_consul']; ?>"
                                        data-obser_medecin="<?= $consultations['obser_medecin']; ?>" 
                                        data-pat_id="<?= $consultations['pat_id']; ?>" 
                                        data-doc_id="<?= $consultations['doc_id']; ?>">
                                    <i class="fas fa-edit"></i> Modifier
                                </button>

                                <!-- Bouton pour supprimer -->
                                <form method="POST" action="" class="d-inline">
                                    <button type="submit" name="delete_consultation" value="<?= $consultations['id_consul']; ?>" class="btn btn-danger btn-sm">
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
                        <h5 class="modal-title">Modifier la consultation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="id_consul" id="edit_id_consul">
                            <div class="mb-3">
                                <label for="edit_date_consul" class="form-label">Date</label>
                                <input type="date" step="0.01" name="date_consul" id="edit_date_consul" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_desc_consul" class="form-label">Description</label>
                                <input type="text" step="0.01" name="desc_consul" id="edit_desc_consul" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_obser_medecin" class="form-label">Observation doctor</label>
                                <input type="text" name="obser_medecin" id="edit_obser_medecin" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_pat_id" class="form-label">ID Patient</label>
                                <input type="text" name="pat_id" id="edit_pat_id" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_doc_id" class="form-label">ID Doctor</label>
                                <input type="text" name="doc_id" id="edit_doc_id" class="form-control" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="update_consultation" class="btn btn-primary">Enregistrer</button>
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
        $('#consultationTable').DataTable({
            searching: false // Désactive la barre de recherche pour la table des consultations
        });
    });

    function deleteConsultation(id) {
        if (confirm("Êtes-vous sûr de vouloir supprimer cette consultation ?")) {
            $('<form method="POST"><input type="hidden" name="delete_consultation" value="' + id + '"></form>').appendTo('body').submit();
        }
    }

    $('#editModal').on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        $('#edit_id_consul').val(button.data('id_consul'));
        $('#edit_date_consul').val(button.data('date_consul'));
        $('#edit_desc_consul').val(button.data('desc_consul'));
        $('#edit_obser_medecin').val(button.data('obser_medecin'));
        $('#edit_pat_id').val(button.data('pat_id'));
        $('#edit_doc_id').val(button.data('doc_id'));
    });
</script>
</body>
</html>