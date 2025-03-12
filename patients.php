<?php 
include 'connectDB.php'; // Connexion à la base de données

// Initialisation des messages
$messages = [];
$errors = [];

// Ajout d'un nouveau patient
if (isset($_POST['add_patient'])) { 
    // Vérification de la présence de la clé 'pat_id' dans le formulaire
    $pat_id = isset($_POST['pat_id']) ? trim($_POST['pat_id']) : '';
    $prenom = isset($_POST['pat_fname']) ? trim($_POST['pat_fname']) : '';
    $nom = isset($_POST['pat_lname']) ? trim($_POST['pat_lname']) : '';
    $telephone = isset($_POST['pat_phone']) ? trim($_POST['pat_phone']) : '';
    $age = isset($_POST['pat_age']) ? trim($_POST['pat_age']) : '';
    $sexe = isset($_POST['sexe']) ? trim($_POST['sexe']) : '';

    if (empty($pat_id) || empty($prenom) || empty($nom) || empty($telephone) || empty($age) || empty($sexe)) {
        $errors[] = "Tous les champs sont requis.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM patient WHERE pat_id = ?");
        $stmt->execute([$pat_id]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "L'ID du patient existe déjà.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO patient (pat_id, pat_fname, pat_lname, pat_phone, pat_age, sexe) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$pat_id, $prenom, $nom, $telephone, $age, $sexe])) {
                $messages[] = "Patient ajouté avec succès.";
            } else {
                $errors[] = "Erreur lors de l'ajout du patient.";
            }
        }
    }
}

// Mise à jour d'un patient
if (isset($_POST['update_patient'])) { 
    // Vérification de la présence de la clé 'pat_id' dans le formulaire
    $pat_id = isset($_POST['pat_id']) ? trim($_POST['pat_id']) : '';
    $prenom = isset($_POST['pat_fname']) ? trim($_POST['pat_fname']) : '';
    $nom = isset($_POST['pat_lname']) ? trim($_POST['pat_lname']) : '';
    $telephone = isset($_POST['pat_phone']) ? trim($_POST['pat_phone']) : '';
    $age = isset($_POST['pat_age']) ? trim($_POST['pat_age']) : '';
    $sexe = isset($_POST['sexe']) ? trim($_POST['sexe']) : '';

    if (empty($prenom) || empty($nom) || empty($telephone) || empty($age) || empty($sexe)) {
        $errors[] = "Tous les champs sont requis.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM patient WHERE pat_id = ?");
        $stmt->execute([$pat_id]);
        if ($stmt->rowCount() == 0) {
            $errors[] = "Le patient avec cet ID n'existe pas.";
        } else {
            $stmt = $pdo->prepare("UPDATE patient SET pat_fname = ?, pat_lname = ?, pat_phone = ?, pat_age = ?, sexe = ? WHERE pat_id = ?");
            if ($stmt->execute([$prenom, $nom, $telephone, $age, $sexe, $pat_id])) {
                $messages[] = "Patient modifié avec succès.";
            } else {
                $errors[] = "Erreur lors de la modification du patient.";
            }
        }
    }
}

// Suppression d'un patient
if (isset($_POST['delete_patient'])) {
    $pat_id = trim($_POST['delete_patient']);  // Récupère l'ID du patient à supprimer

    // Vérifier si l'ID du patient existe
    if (!empty($pat_id)) {
        $stmt = $pdo->prepare("SELECT * FROM patient WHERE pat_id = ?");
        $stmt->execute([$pat_id]);
        
        if ($stmt->rowCount() > 0) {
            // Le patient existe, vérifier s'il est lié à d'autres tables
            $linked_tables = [
                'rendez_vous' => 'pat_id',
                'consultation' => 'pat_id',
                'ordonnance' => 'pat_id',
                'dossier' => 'pat_id',
                'analyse_medicale' => 'pat_id',
                'facture' => 'pat_id',
                'hospitaliser' => 'pat_id'
            ];

            $linked = false;
            foreach ($linked_tables as $table => $column) {
                $stmt = $pdo->prepare("SELECT * FROM $table WHERE $column = ?");
                $stmt->execute([$pat_id]);
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
                echo '<p style="margin-top: 20px;">Ce patient est lié à d\'autres enregistrements </p>';  
                
                // Liste des tables liées (ajout des tables liées ici)  
                echo '<ul style="list-style-type: none; padding-left: 0; margin: 10px 0; font-size: 1.1em;">';   
                echo '</ul>';  
                
                // Message de confirmation avant les boutons  
                echo '<p style="margin-top: 20px;">Souhaitez-vous supprimer tous les enregistrements associés ?</p>';  
                
                // Formulaire de confirmation  
                echo '<form method="POST" style="margin-top: 20px;">';  
                echo '<input type="hidden" name="pat_id" value="' . htmlspecialchars($pat_id) . '">';  
                
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
            }else {
                // Si le patient n'est lié à aucune autre table, supprimer directement
                $stmt = $pdo->prepare("DELETE FROM patient WHERE pat_id = ?");
                if ($stmt->execute([$pat_id])) {
                    $messages[] = "Patient supprimé avec succès.";
                } else {
                    $errors[] = "Erreur lors de la suppression du patient.";
                }
            }
        } else {
            $errors[] = "Le patient avec cet ID n'existe pas.";
        }
    } else {
        $errors[] = "L'ID du patient est requis pour la suppression.";
    }
}

// Traitement de la confirmation de suppression
if (isset($_POST['confirm_delete'])) {
    $pat_id = trim($_POST['pat_id']);
    $delete_related = $_POST['delete_related']; // Récupère si l'utilisateur veut supprimer les enregistrements associés

    if ($delete_related === 'yes') {
        // Si l'utilisateur veut supprimer tous les enregistrements associés
        $linked_tables = [
            'rendez_vous' => 'pat_id',
            'consultation' => 'pat_id',
            'ordonnance' => 'pat_id',
            'dossier' => 'pat_id',
            'analyse_medicale' => 'pat_id',
            'facture' => 'pat_id',
            'hospitaliser' => 'pat_id'
        ];

        // Supprimer les enregistrements associés dans toutes les tables
        foreach ($linked_tables as $table => $column) {
            $stmt = $pdo->prepare("DELETE FROM $table WHERE $column = ?");
            $stmt->execute([$pat_id]);
        }

        // Ensuite, supprimer le patient de la table principale
        $stmt = $pdo->prepare("DELETE FROM patient WHERE pat_id = ?");
        if ($stmt->execute([$pat_id])) {
            $messages[] = "Patient et tous ses enregistrements associés ont été supprimés avec succès.";
        } else {
            $errors[] = "Erreur lors de la suppression du patient.";
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
    $allowed_columns = ['pat_id', 'pat_fname', 'pat_lname', 'pat_phone', 'pat_age', 'sexe'];

    // Si l'option 'All' est choisie, chercher sur toutes les colonnes
    if ($search_type === 'all') {
        // Recherche sur toutes les colonnes en combinant des conditions pour chaque colonne
        $stmt = $pdo->query("SELECT * FROM patient");
    } elseif (in_array($search_type, $allowed_columns)) {
        // Si la colonne est un champ VARCHAR, utiliser LIKE pour une recherche partielle
        if (in_array($search_type, ['pat_fname', 'pat_lname', 'pat_phone', 'sexe'])) {
            $stmt = $pdo->prepare("SELECT * FROM patient WHERE $search_type LIKE ?");
            $stmt->execute(["$search_value%"]);
        } else {
            // Pour les colonnes non VARCHAR (par exemple, 'pat_id', 'pat_age'), utiliser une recherche exacte
            $stmt = $pdo->prepare("SELECT * FROM patient WHERE $search_type = ?");
            $stmt->execute([$search_value]);
        }
    } else {
        // Si la colonne spécifiée n'est pas valide, afficher tous les patients
        $stmt = $pdo->query("SELECT * FROM patient");
    }
} else {
    // Si aucun critère de recherche n'est défini, afficher tous les patients
    $stmt = $pdo->query("SELECT * FROM patient");
}

// Récupérer les résultats de la requête
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        <a href="patient.php"><i class="fas fa-user-injured"></i> Gérer Patients</a>
        <a href="dossier.php"><i class="fas fa-file-medical"></i> Gérer Les Dossiers</a>
        <a href="hospitaliser.php"><i class="fas fa-hospital"></i> Gérer Hospitalisations</a>
        <a href="consultation.php"><i class="fas fa-file-invoice"></i> Gérer les consultations</a>
        <a href="analyse.php"><i class="fas fa-vials"></i> Gérer les analyses</a>
        <a href="rendez_vous.php"><i class="fas fa-file-invoice"></i> Gérer les Rendez-vous</a>
    </div>
    <div class="container mt-5">
        <h1 class="text-center"><i class="fas fa-users"></i> Gestion des Patients</h1>
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
        <!-- Formulaire d'ajout d'un patient -->
        <form method="POST" class="mb-4">
            <div class="row g-3">
                <div class="col-md-2">
                    <input type="text" name="pat_id" class="form-control" placeholder="ID Patient" required>
                </div>
                <div class="col-md-2">
                    <input type="text" name="pat_fname" class="form-control" placeholder="Prénom" required>
                </div>
                <div class="col-md-2">
                    <input type="text" name="pat_lname" class="form-control" placeholder="Nom" required>
                </div>
                <div class="col-md-2">
                    <input type="text" name="pat_phone" class="form-control" placeholder="Téléphone" required>
                </div>
                <div class="col-md-2">
                    <input type="number" name="pat_age" class="form-control" placeholder="Âge" required>
                </div>
                <div class="col-md-2">
                    <select name="sexe" class="form-control" required>
                        <option value="">Sélectionner Sexe</option>
                        <option value="M">Masculin</option>
                        <option value="F">Féminin</option>
                    </select>
                </div>
                <button type="submit" name="add_patient" class="btn btn-primary w-100 d-flex justify-content-center align-items-center">
                    <i class="fas fa-plus me-2"></i> Ajouter
                </button>
            </div>
        </form>
        <!-- Formulaire de recherche pour un patient -->
        <form method="GET" class="mb-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <select name="search_type" class="form-select" required>
                        <option value="all">Tous</option>
                        <option value="pat_id">ID Patient</option>
                        <option value="pat_fname">Prénom</option>
                        <option value="pat_lname">Nom</option>
                        <option value="pat_phone">Téléphone</option>
                        <option value="pat_age">Âge</option>
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
        <table id="patientTable" class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Prénom</th>
                    <th>Nom</th>
                    <th>Téléphone</th>
                    <th>Âge</th>
                    <th>Sexe</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($patients as $patient): ?>
                <tr>
                    <td><?= htmlspecialchars($patient['pat_id']); ?></td>
                    <td><?= htmlspecialchars($patient['pat_fname']); ?></td>
                    <td><?= htmlspecialchars($patient['pat_lname']); ?></td>
                    <td><?= htmlspecialchars($patient['pat_phone']); ?></td>
                    <td><?= htmlspecialchars($patient['pat_age']); ?></td>
                    <td><?= htmlspecialchars($patient['sexe']); ?></td>
                    <td>
                        <div class="d-flex">
                            <!-- Bouton Modifier -->
                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal"
                                    data-id="<?= htmlspecialchars($patient['pat_id']); ?>"
                                    data-fname="<?= htmlspecialchars($patient['pat_fname']); ?>"
                                    data-lname="<?= htmlspecialchars($patient['pat_lname']); ?>"
                                    data-phone="<?= htmlspecialchars($patient['pat_phone']); ?>"
                                    data-age="<?= htmlspecialchars($patient['pat_age']); ?>"
                                    data-sexe="<?= htmlspecialchars($patient['sexe']); ?>">
                                <i class="fas fa-edit"></i> Modifier
                            </button>

                            <!-- Bouton Supprimer -->
                            <form method="POST">
                                <input type="hidden" name="delete_patient" value="<?= $patient['pat_id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">
                                    Supprimer
                                </button>
                            </form>
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
                        <h5 class="modal-title">Modifier le Patient</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <!-- ID caché -->
                            <input type="hidden" name="pat_id" id="edit_id">
                            <!-- Prénom -->
                            <div class="mb-3">
                                <label for="edit_fname" class="form-label">Prénom</label>
                                <input type="text" name="pat_fname" id="edit_fname" class="form-control" required>
                            </div>
                            <!-- Nom -->
                            <div class="mb-3">
                                <label for="edit_lname" class="form-label">Nom</label>
                                <input type="text" name="pat_lname" id="edit_lname" class="form-control" required>
                            </div>
                            <!-- Téléphone -->
                            <div class="mb-3">
                                <label for="edit_phone" class="form-label">Téléphone</label>
                                <input type="text" name="pat_phone" id="edit_phone" class="form-control" required>
                            </div>
                            <!-- Âge -->
                            <div class="mb-3">
                                <label for="edit_age" class="form-label">Âge</label>
                                <input type="number" name="pat_age" id="edit_age" class="form-control" required>
                            </div>
                            <!-- Sexe -->
                            <div class="mb-3">
                                <label for="edit_sexe" class="form-label">Sexe</label>
                                <select name="sexe" id="edit_sexe" class="form-select" required>
                                    <option value="M">Homme</option>
                                    <option value="F">Femme</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="update_patient" class="btn btn-primary">Enregistrer</button>
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
                $('#patientTable').DataTable({
                    searching: false // Désactive la barre de recherche pour la table
                });
                    // Ajouter le formulaire dans le body et le soumettre
                    $('body').append(form);
                    form.submit();
                });
        // Remplir le modal avec les informations du patient
            $('#editModal').on('show.bs.modal', function (event) {
                const button = $(event.relatedTarget); // Le bouton qui déclenche l'ouverture du modal
                $('#edit_id').val(button.data('id')); // ID du patient
                $('#edit_fname').val(button.data('fname')); // Prénom
                $('#edit_lname').val(button.data('lname')); // Nom
                $('#edit_phone').val(button.data('phone')); // Téléphone
                $('#edit_age').val(button.data('age')); // Âge
                $('#edit_sexe').val(button.data('sexe')); // Sexe (Homme/Femme)
            });
        </script>
</body>
</html>