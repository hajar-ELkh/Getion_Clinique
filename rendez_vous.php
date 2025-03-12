<?php
include 'connectDB.php'; // Connexion à la base de données

// Initialisation des messages
$messages = [];
$errors = [];

// Fonction pour valider l'existence d'une entité
function validateExistence($pdo, $table, $column, $value) {
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE $column = ?");
    $stmt->execute([$value]);
    return $stmt->rowCount() > 0;
}

// Ajout d'un rendez-vous si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_rendez_vous'])) {
    $id_RD = $_POST['id_RD'];
    $date_RD = $_POST['date_RD'];
    $horaire = $_POST['horaire'];
    $pat_id = $_POST['pat_id'];
    $sec_id = $_POST['sec_id'];
    $doc_id = $_POST['doc_id'];

    if (empty($date_RD) || empty($horaire) || empty($pat_id) || empty($sec_id) || empty($doc_id)) {
        $errors[] = "Tous les champs sont requis pour ajouter un rendez-vous.";
    } elseif (!validateExistence($pdo, 'patient', 'pat_id', $pat_id)) {
        $errors[] = "Le patient avec cet ID n'existe pas.";
    } elseif (!validateExistence($pdo, 'secretaire', 'sec_id', $sec_id)) {
        $errors[] = "Le secrétaire avec cet ID n'existe pas.";
    } elseif (!validateExistence($pdo, 'doctor', 'doc_id', $doc_id)) {
        $errors[] = "Le docteur avec cet ID n'existe pas.";
    } else {
        // Vérification si l'id_RD existe déjà
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM rendez_vous WHERE id_RD = ?");
        $stmtCheck->execute([$id_RD]);
        $existingIdCount = $stmtCheck->fetchColumn();

        if ($existingIdCount > 0) {
            $errors[] = "L'ID de rendez-vous existe déjà. Veuillez utiliser un autre ID.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO rendez_vous (id_RD, date_RD, horaire, pat_id, sec_id, doc_id) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$id_RD, $date_RD, $horaire, $pat_id, $sec_id, $doc_id])) {
            $messages[] = "Rendez-vous ajouté avec succès.";
        } else {
            $errors[] = "Erreur lors de l'ajout du rendez-vous.";
        }
    }
}
}


// Mise à jour d'un rendez-vous
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_rendez_vous'])) {
    $id_RD = $_POST['id_RD'];
    $date_RD = $_POST['date_RD'];
    $horaire = $_POST['horaire'];
    $pat_id = $_POST['pat_id'];
    $sec_id = $_POST['sec_id'];
    $doc_id = $_POST['doc_id'];

    if (empty($date_RD) || empty($horaire) || empty($pat_id) || empty($sec_id) || empty($doc_id)) {
        $errors[] = "Tous les champs sont requis pour mettre à jour le rendez-vous.";
    } elseif (!validateExistence($pdo, 'rendez_vous', 'id_RD', $id_RD)) {
        $errors[] = "Le rendez-vous avec cet ID n'existe pas.";
    } elseif (!validateExistence($pdo, 'patient', 'pat_id', $pat_id)) {
        $errors[] = "Le patient avec cet ID n'existe pas.";
    } elseif (!validateExistence($pdo, 'secretaire', 'sec_id', $sec_id)) {
        $errors[] = "Le secrétaire avec cet ID n'existe pas.";
    } elseif (!validateExistence($pdo, 'doctor', 'doc_id', $doc_id)) {
        $errors[] = "Le docteur avec cet ID n'existe pas.";
    } else {
        $stmt = $pdo->prepare("UPDATE rendez_vous SET date_RD = ?, horaire = ?, pat_id = ?, sec_id = ?, doc_id = ? WHERE id_RD = ?");
        if ($stmt->execute([$date_RD, $horaire, $pat_id, $sec_id, $doc_id, $id_RD])) {
            $messages[] = "Rendez-vous mis à jour avec succès.";
        } else {
            $errors[] = "Erreur lors de la mise à jour du rendez-vous.";
        }
    }
}

// Suppression d'un rendez-vous
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_rendez_vous'])) {
    $id_RD = $_POST['delete_rendez_vous'];
    if (!validateExistence($pdo, 'rendez_vous', 'id_RD', $id_RD)) {
        $errors[] = "Le rendez-vous avec cet ID n'existe pas.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM rendez_vous WHERE id_RD = ?");
        if ($stmt->execute([$id_RD])) {
            $messages[] = "Rendez-vous supprimé avec succès.";
        } else {
            $errors[] = "Erreur lors de la suppression du rendez-vous.";
        }
    }
}

$query = "
    SELECT 
        rd.id_RD, rd.date_RD, rd.horaire, rd.pat_id, rd.sec_id, rd.doc_id, 
        d.doc_fname, d.doc_lname 
    FROM 
        rendez_vous rd
    LEFT JOIN 
        doctor d ON rd.doc_id = d.doc_id
";

if (isset($_GET['search_type']) && isset($_GET['search_value'])) {
    $search_type = trim($_GET['search_type']); // Type de recherche
    $search_value = trim($_GET['search_value']); // Valeur de recherche

    $allowed_columns = ['id_RD', 'date_RD', 'horaire', 'pat_id', 'sec_id', 'doc_id', 'doc_fname', 'doc_lname'];

    if ($search_type === 'all' && strtolower($search_value) === 'all') {
        // Si "Tous" est choisi et "all" est saisi, afficher tous les résultats
        $stmt = $pdo->query($query);
    } elseif ($search_type === 'all') {
        $query .= " WHERE 
            rd.id_RD = :search_value OR 
            rd.date_RD LIKE :search_partial_value OR 
            rd.horaire LIKE :search_partial_value OR 
            rd.pat_id = :search_value OR 
            rd.sec_id = :search_value OR 
            d.doc_fname LIKE :search_partial_value OR 
            d.doc_lname LIKE :search_partial_value";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'search_value' => $search_value, 
            'search_partial_value' => '%' . $search_value . '%'
        ]);
    } elseif (in_array($search_type, $allowed_columns)) {
        if (in_array($search_type, ['id_RD', 'pat_id', 'sec_id', 'doc_id'])) {
            // Recherche exacte pour les IDs
            $query .= " WHERE rd.$search_type = :search_value";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['search_value' => $search_value]);
        } elseif (in_array($search_type, ['doc_fname', 'doc_lname'])) {
            // Recherche partielle pour les noms/prénoms
            $query .= " WHERE d.$search_type LIKE :search_partial_value";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['search_partial_value' => '%' . $search_value . '%']);
        } else {
            // Recherche partielle pour les autres colonnes
            $query .= " WHERE rd.$search_type LIKE :search_partial_value";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['search_partial_value' => '%' . $search_value . '%']);
        }
    } else {
        $stmt = $pdo->query($query);
    }
} else {
    $stmt = $pdo->query($query);
}

$rendez_vous = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des docteurs pour le formulaire
$doctors = $pdo->query("SELECT doc_id, doc_fname, doc_lname FROM doctor")->fetchAll(PDO::FETCH_ASSOC);
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
            background: url('img/tec.jpg') no-repeat center center fixed; /* Ajustez le chemin de l'image ici */  
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
        #confirmationMessage {
            position: fixed; /* Pour le rendre flottant */
            bottom: 20px; /* Le positionner en bas de la page */
            left: 50%;
            transform: translateX(-50%); /* Centrer horizontalement */
            z-index: 9999; /* S'assurer qu'il est au-dessus des autres éléments */
            width: 80%; /* Ajustez la largeur selon vos besoins */
            max-width: 600px; /* Largeur maximale */
        }

        .alert {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header {
            border-bottom: 2px solid #ffc107;
        }

        .modal-body p {
            font-size: 1rem;
            margin-bottom: 1rem;
        }

        .form-check-label {
            margin-left: 0.5rem;
        }

        .modal-footer .btn {
            min-width: 100px;
        }

    </style>  
</head>
<body>
    <div class="navbar">
        <a href="interface.html"><i class="fas fa-home"></i> Accueil</a>
        <a href="patients.php"><i class="fas fa-user-injured"></i> Gérer Patients</a>
        <a href="doctor.php"><i class="fas fa-file-medicen"></i> Gérer Doctor</a>
        <a href="secretaire.php"><i class="fas fa-secretaire"></i> Gérer secretaire</a>
    </div>
    <div class="container mt-5">
        <h1 class="text-center"><i class="fas fa-users"></i> Gestion des rendez-VOUS</h1>
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
        <form method="POST" class="mb-4">
    <div class="row g-3">
        <div class="col-md-3">
            <input type="text" id="id_RD" name="id_RD" class="form-control" placeholder="ID Rendez-vous" required>
        </div>
        <div class="col-md-3">
            <input type="date" id="date_RD" name="date_RD" class="form-control" required>
        </div>
        <div class="col-md-3">
            <input type="time" id="horaire" name="horaire" class="form-control" required>
        </div>
        <div class="col-md-3">
            <input type="text" id="pat_id" name="pat_id" class="form-control" placeholder="ID Patient" required>
        </div>
        <div class="col-md-3">
            <input type="text" id="sec_id" name="sec_id" class="form-control" placeholder="ID Secrétaire" required>
        </div>
        <div class="col-md-3">
            <select id="doc_id" name="doc_id" class="form-control" required>
                <option value="" disabled selected>Choisir un Docteur</option>
                <?php foreach ($doctors as $doctor): ?>
                    <option value="<?= $doctor['doc_id'] ?>"><?= $doctor['doc_fname'] ?> <?= $doctor['doc_lname'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-12">
            <button type="submit" name="add_rendez_vous" class="btn btn-primary w-100 d-flex justify-content-center align-items-center">
                <i class="fas fa-plus me-2"></i> Ajouter Rendez-vous
            </button>
        </div>
    </div>
</form>


        <!-- Formulaire de recherche pour un rendez vous -->
        <form method="GET" class="mb-4">
            <div class="row g-3">
                <div class="col-md-3">
                <select name="search_type" class="form-select" required>
                    <option value="all">Tous</option>
                    <option value="id_RD">ID Rendez-vous</option>
                    <option value="date_RD">Date</option>
                    <option value="horaire">Horaire</option>
                    <option value="pat_id">ID Patient</option>
                    <option value="sec_id">ID Secrétaire</option>
                    <option value="doc_id">ID Docteur</option>
                    <option value="doc_fname">Nom du Docteur</option>
                    <option value="doc_lname">Prénom du Docteur</option>
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
        <table id="rendezVousTable" class="table table-striped table-hover">
    <thead>
        <tr>
            <th>ID Rendez-Vous</th>
            <th>Date</th>
            <th>Horaire</th>
            <th>ID Patient</th>
            <th>ID Secrétaire</th>
            <th>ID Docteur</th>
            <th>Nom du Docteur</th>
            <th>Prénom du Docteur</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rendez_vous as $rdv): ?>
            <tr>
                <td><?= htmlspecialchars($rdv['id_RD']); ?></td>
                <td><?= htmlspecialchars($rdv['date_RD']); ?></td>
                <td><?= htmlspecialchars($rdv['horaire']); ?></td>
                <td><?= htmlspecialchars($rdv['pat_id']); ?></td>
                <td><?= htmlspecialchars($rdv['sec_id']); ?></td>
                <td><?= htmlspecialchars($rdv['doc_id']); ?></td>
                <td><?= htmlspecialchars($rdv['doc_fname']); ?></td>
                <td><?= htmlspecialchars($rdv['doc_lname']); ?></td>
                <td>
                    <div class="d-flex">
                        <!-- Bouton pour modifier -->
                        <button class="btn btn-warning btn-sm me-2" data-bs-toggle="modal" data-bs-target="#editModal" 
                                data-id="<?= $rdv['id_RD']; ?>" 
                                data-date_rd="<?= $rdv['date_RD']; ?>" 
                                data-horaire="<?= $rdv['horaire']; ?>" 
                                data-pat_id="<?= $rdv['pat_id']; ?>" 
                                data-sec_id="<?= $rdv['sec_id']; ?>" 
                                data-doc_id="<?= $rdv['doc_id']; ?>" 
                                data-doc_fname="<?= htmlspecialchars($rdv['doc_fname']); ?>" 
                                data-doc_lname="<?= htmlspecialchars($rdv['doc_lname']); ?>">
                            <i class="fas fa-edit"></i> Modifier
                        </button>

                        <!-- Bouton pour supprimer -->
                        <form method="POST" action="" class="d-inline">
                            <button type="submit" name="delete_rendez_vous" value="<?= $rdv['id_RD']; ?>" class="btn btn-danger btn-sm">
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
                    <h5 class="modal-title">Modifier le Rendez-Vous</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="id_RD" id="edit_id_RD">
                        <div class="mb-3">
                            <label for="edit_date_RD" class="form-label">Date</label>
                            <input type="date" name="date_RD" id="edit_date_RD" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_horaire" class="form-label">Horaire</label>
                            <input type="time" name="horaire" id="edit_horaire" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_pat_id" class="form-label">ID Patient</label>
                            <input type="text" name="pat_id" id="edit_pat_id" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_sec_id" class="form-label">ID Secrétaire</label>
                            <input type="text" name="sec_id" id="edit_sec_id" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_doc_id" class="form-label">ID Docteur</label>
                            <input type="text" name="doc_id" id="edit_doc_id" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="update_rendez_vous" class="btn btn-primary">Enregistrer</button>
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
            $('#rendezVousTable').DataTable({
                searching: false // Désactive la barre de recherche pour la table des rendez-vous
            });
        });

        $('#editModal').on('show.bs.modal', function (event) {
            const button = $(event.relatedTarget);
            $('#edit_id_RD').val(button.data('id'));
            $('#edit_date_RD').val(button.data('date_rd'));
            $('#edit_horaire').val(button.data('horaire'));
            $('#edit_pat_id').val(button.data('pat_id'));
            $('#edit_sec_id').val(button.data('sec_id'));
            $('#edit_doc_id').val(button.data('doc_id'));
        });
        function deleteRendezVous(id) {
        if (confirm("Êtes-vous sûr de vouloir supprimer ce rendez-vous ?")) {
            $('<form method="POST"><input type="hidden" name="delete_rendez_vous" value="' + id + '"></form>').appendTo('body').submit();
        }
    }
    </script>
</div>
<html>