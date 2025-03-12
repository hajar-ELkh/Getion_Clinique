<?php 
// Connexion à la base de données
function connectDB() {
    $host = 'localhost';
    $db = 'gestion_clinique';
    $user = 'root';
    $pass = '.......';
    try {
        $conn = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch (PDOException $e) {
        die("Erreur de connexion : " . $e->getMessage());
    }
}

// Fonction pour ajouter une hospitalisation
function ajouterHospitalisation($pat_id, $num_lit, $salle_id, $date_entree, $date_sortie) {
    $conn = connectDB();
    $errors = [];

    // Vérification si le patient existe
    $stmt_patient = $conn->prepare("SELECT * FROM patient WHERE pat_id = ?");
    $stmt_patient->execute([$pat_id]);
    if ($stmt_patient->rowCount() == 0) {
        $errors[] = "Le patient avec l'ID spécifié n'existe pas.";
    }

    // Vérification si la salle existe
    $stmt_salle = $conn->prepare("SELECT * FROM chambre WHERE salle_id = ?");
    $stmt_salle->execute([$salle_id]);
    if ($stmt_salle->rowCount() == 0) {
        $errors[] = "La salle avec l'ID spécifié n'existe pas.";
    }

    if (empty($errors)) {
        try {
            $sql = "INSERT INTO hospitaliser (pat_id, num_lit, salle_id, date_entree, date_sortie) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$pat_id, $num_lit, $salle_id, $date_entree, $date_sortie]);
            return "Hospitalisation ajoutée avec succès.";
        } catch (Exception $e) {
            return "Erreur lors de l'ajout de l'hospitalisation : " . $e->getMessage();
        }
    } else {
        return $errors;
    }
}

// Fonction pour modifier une hospitalisation
function modifierHospitalisation($pat_id, $new_pat_id, $num_lit, $salle_id, $date_entree, $date_sortie) {
    $conn = connectDB();
    $errors = [];

    // Vérification si l'hospitalisation existe
    $stmt = $conn->prepare("SELECT * FROM hospitaliser WHERE pat_id = ? AND num_lit = ?");
    $stmt->execute([$pat_id, $num_lit]);
    if ($stmt->rowCount() == 0) {
        $errors[] = "L'hospitalisation n'existe pas.";
    }

    // Vérification si le nouveau patient existe
    if (!empty($new_pat_id)) {
        $stmt_patient = $conn->prepare("SELECT * FROM patient WHERE pat_id = ?");
        $stmt_patient->execute([$new_pat_id]);
        if ($stmt_patient->rowCount() == 0) {
            $errors[] = "Le patient avec le nouvel ID n'existe pas.";
        }
    }

    if (empty($errors)) {
        try {
            // Si un nouvel ID patient est fourni, on met à jour la table hospitaliser
            $sql = "UPDATE hospitaliser SET pat_id = ?, salle_id = ?, date_entree = ?, date_sortie = ? WHERE pat_id = ? AND num_lit = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$new_pat_id ?: $pat_id, $salle_id, $date_entree, $date_sortie, $pat_id, $num_lit]);

            return "Hospitalisation modifiée avec succès.";
        } catch (Exception $e) {
            return "Erreur lors de la modification : " . $e->getMessage();
        }
    } else {
        return $errors;
    }
}

// Fonction pour supprimer une hospitalisation
function supprimerHospitalisation($pat_id, $num_lit) {
    $conn = connectDB();
    $errors = [];

    // Vérification si l'hospitalisation existe
    $stmt = $conn->prepare("SELECT * FROM hospitaliser WHERE pat_id = ? AND num_lit = ?");
    $stmt->execute([$pat_id, $num_lit]);
    if ($stmt->rowCount() == 0) {
        $errors[] = "L'hospitalisation spécifiée n'existe pas.";
    }

    if (empty($errors)) {
        try {
            $sql = "DELETE FROM hospitaliser WHERE pat_id = ? AND num_lit = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$pat_id, $num_lit]);

            return "Hospitalisation supprimée avec succès.";
        } catch (Exception $e) {
            return "Erreur lors de la suppression : " . $e->getMessage();
        }
    } else {
        return $errors;
    }
}

// Affichage des hospitalisations
function afficherHospitalisations() {
    $conn = connectDB();
    $sql = "
        SELECT 
            h.pat_id, h.num_lit, h.salle_id, h.date_entree, h.date_sortie, 
            p.pat_fname, p.pat_lname, p.pat_phone, p.pat_age, p.sexe, 
            c.num_salle, c.type_chambre 
        FROM hospitaliser h
        JOIN patient p ON h.pat_id = p.pat_id
        JOIN chambre c ON h.salle_id = c.salle_id
    ";
    $stmt = $conn->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_hospitalisation'])) {
        $pat_id = $_POST['pat_id'];
        $num_lit = $_POST['num_lit'];
        $salle_id = $_POST['salle_id'];
        $date_entree = $_POST['date_entree'];
        $date_sortie = $_POST['date_sortie'];

        $result = ajouterHospitalisation($pat_id, $num_lit, $salle_id, $date_entree, $date_sortie);
        
        if (is_array($result)) {
            foreach ($result as $error) {
                echo "<p style='color: red;'>$error</p>";
            }
        } else {
            echo "<p style='color: green;'>$result</p>";
        }
    }

    if (isset($_POST['modify_hospitalisation'])) {
        $pat_id = $_POST['modify_pat_id'];
        $new_pat_id = $_POST['modify_new_pat_id'];  // Nouvel ID de patient
        $num_lit = $_POST['modify_num_lit'];
        $salle_id = $_POST['modify_salle_id'];
        $date_entree = $_POST['modify_date_entree'];
        $date_sortie = $_POST['modify_date_sortie'];
    
        // Modifier l'hospitalisation avec la possibilité de changer l'ID du patient
        $result = modifierHospitalisation($pat_id, $new_pat_id, $num_lit, $salle_id, $date_entree, $date_sortie);
        echo "<p style='color: green;'>$result</p>";
    }

    if (isset($_POST['delete_hospitalisation'])) {
        $pat_id = $_POST['delete_pat_id'];
        $num_lit = $_POST['delete_num_lit'];

        $result = supprimerHospitalisation($pat_id, $num_lit);
        echo "<p style='color: green;'>$result</p>";
    }
}

$hospitalisations = afficherHospitalisations();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Hospitalisations</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Reset default styles */
        body, html {
             
            font-family: Arial, sans-serif;  
            background: url('img/analyse.jpg') no-repeat center center fixed; /* Ajustez le chemin de l'image ici */  
            background-size: cover;  
            margin: 0;  
            padding: 0;   
            font-family: 'Arial', sans-serif;
        }
        

        /* Container for the form and table */
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }

        /* Form Styles */
        form {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 40px;
        }

        form label {
            font-weight: bold;
            margin-bottom: 8px;
            display: inline-block;
        }

        form input[type="number"], form input[type="text"], form input[type="date"] {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        form button {
            background-color:rgb(17, 38, 133);
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }

        form button:hover {
            background-color:rgb(29, 31, 151);
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
            background-color: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        table th, table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        table th {
            background-color:rgb(46, 68, 167);
            color: white;
        }

        table tr:hover {
            background-color: #f1f1f1;
        }

        /* Button Styles for Actions */
        .modify-btn, .delete-btn {
            background-color:rgb(13, 54, 202);
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .delete-btn {
            background-color: #f44336;
        }

        .modify-btn:hover, .delete-btn:hover {
            background-color:rgb(40, 63, 192);
        }

        .delete-btn:hover {
            background-color: #e53935;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
            padding-top: 60px;
        }

        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 500px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .modal-content h2 {
            margin-bottom: 20px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover, .close:focus {
            color: black;
        }

        .modal button {
            background-color:rgb(13, 80, 197);
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            margin-top: 10px;
        }

        .modal button:hover {
            background-color:rgb(13, 93, 155);
        }

        #cancelDelete {
            background-color: #e53935;
            margin-top: 10px;
        }

        #cancelDelete:hover {
            background-color: #f44336;
        }
        /* Conteneur pour les boutons dans les modals */
.modal-buttons {
    display: flex;
    gap: 10px; /* Espacement entre les boutons */
    justify-content: center; /* Centrer les boutons */
}

.modal-buttons button {
    padding: 10px 20px;
    font-size: 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.modal-buttons button:hover {
    opacity: 0.8;
}

#closeModifyModal, #cancelDelete {
    background-color: #e53935;
    color: white;
}

#closeModifyModal:hover, #cancelDelete:hover {
    background-color: #f44336;
}

button[type="submit"] {
    background-color:rgb(27, 56, 105);
    color: white;
}

button[type="submit"]:hover {
    background-color:rgb(23, 42, 149);
}
/* Alignement horizontal des boutons */
.modal-buttons {
    display: flex;
    justify-content: space-between;
    gap: 15px; /* Espacement entre les boutons */
    margin-top: 20px;
}

.modal-buttons button {
    flex: 1;
    padding: 10px 15px;
    font-size: 16px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.modal-buttons .confirm-btn {
    background-color:rgb(27, 51, 162);
    color: white;
}

.modal-buttons .confirm-btn:hover {
    background-color:rgb(27, 48, 153);
}

.modal-buttons .cancel-btn {
    background-color: #f44336;
    color: white;
}

.modal-buttons .cancel-btn:hover {
    background-color: #e53935;
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
            <a href="patient.php"><i class="fas fa-user-injured"></i> Gérer Patients</a>
            <a href="chambre.php"><i class="fas fa-file-medical"></i> Gérer chambre</a>
            <a href="hospitaliser.php"><i class="fas fa-hospital"></i> Gérer Hospitalisations</a>
        </div>
    <div class="container mt-5">
        <h1 class="text-center"><i class="fas fa-hospital"></i> Gérer Hospitalisation</h1>
        <h1>Ajouter une Hospitalisation</h1>
        <form method="POST" action="">
            <label for="pat_id">Patient ID :</label>
            <input type="number" id="pat_id" name="pat_id" required><br>

            <label for="num_lit">Numéro de Lit :</label>
            <input type="text" id="num_lit" name="num_lit" required><br>

            <label for="salle_id">Salle ID :</label>
            <input type="number" id="salle_id" name="salle_id" required><br>

            <label for="date_entree">Date d'Entrée :</label>
            <input type="date" id="date_entree" name="date_entree" required><br>

            <label for="date_sortie">Date de Sortie :</label>
            <input type="date" id="date_sortie" name="date_sortie"><br>

            <button type="submit" name="add_hospitalisation">Ajouter</button>
        </form>

        <h1>Liste des Hospitalisations</h1>
        <table>
            <thead>
                <tr>
                    <th>Patient ID</th>
                    <th>Nom du Patient</th>
                    <th>Numéro de Lit</th>
                    <th>Salle ID</th>
                    <th>Numéro de Salle</th>
                    <th>Type de Chambre</th>
                    <th>Date d'Entrée</th>
                    <th>Date de Sortie</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($hospitalisations as $hosp) : ?>
                    <tr>
                    <tr> <td><?= htmlspecialchars($hosp['pat_id']?? '', ENT_QUOTES, 'UTF-8'); ?></td>
<td><?= htmlspecialchars($hosp['pat_fname'] . " " . $hosp['pat_lname']?? '', ENT_QUOTES, 'UTF-8'); ?></td>
<td><?= htmlspecialchars($hosp['num_lit']?? '', ENT_QUOTES, 'UTF-8'); ?></td>
<td><?= htmlspecialchars($hosp['salle_id']?? '', ENT_QUOTES, 'UTF-8'); ?></td>
<td><?= htmlspecialchars($hosp['num_salle']?? '', ENT_QUOTES, 'UTF-8'); ?></td>
<td><?= htmlspecialchars($hosp['type_chambre']?? '', ENT_QUOTES, 'UTF-8'); ?></td>
<td><?= htmlspecialchars($hosp['date_entree'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
<td><?= htmlspecialchars($hosp['date_sortie'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                 <td>
                 <div class="d-flex">
                            <button class="modify-btn" data-pat_id="<?= $hosp['pat_id'] ?>" data-num_lit="<?= $hosp['num_lit'] ?>" data-salle_id="<?= $hosp['salle_id'] ?>" data-date_entree="<?= $hosp['date_entree'] ?>" data-date_sortie="<?= $hosp['date_sortie'] ?>">Modifier</button>
                            <button class="delete-btn" data-pat_id="<?= $hosp['pat_id'] ?>" data-num_lit="<?= $hosp['num_lit'] ?>">Supprimer</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Modal pour modifier -->
        <!-- Modal pour modifier -->
<!-- Modal pour modifier -->
<div id="modifyModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeModifyModal">&times;</span>
        <h2>Modifier l'Hospitalisation</h2>
        <form method="POST" id="modifyForm">
            <input type="hidden" id="modify_pat_id" name="modify_pat_id">
            <input type="hidden" id="modify_num_lit" name="modify_num_lit">
            <label for="modify_new_pat_id">Nouveau Patient ID (Optionnel) :</label>
            <input type="number" id="modify_new_pat_id" name="modify_new_pat_id"><br>

            <label for="modify_salle_id">Salle ID :</label>
            <input type="number" id="modify_salle_id" name="modify_salle_id" required><br>

            <label for="modify_date_entree">Date d'Entrée :</label>
            <input type="date" id="modify_date_entree" name="modify_date_entree" required><br>

            <label for="modify_date_sortie">Date de Sortie :</label>
            <input type="date" id="modify_date_sortie" name="modify_date_sortie"><br>

            <div class="modal-buttons">
                <button type="submit" name="modify_hospitalisation" class="confirm-btn">Modifier</button>
                <button type="button" id="cancelModifyModal" class="cancel-btn">Annuler</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal pour supprimer -->
<div id="deleteConfirmModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeDeleteModal">&times;</span>
        <h2>Êtes-vous sûr de vouloir supprimer cette hospitalisation ?</h2>
        <form method="POST" id="deleteForm">
            <input type="hidden" id="delete_pat_id" name="delete_pat_id">
            <input type="hidden" id="delete_num_lit" name="delete_num_lit">
            <div class="modal-buttons">
                <button type="submit" name="delete_hospitalisation" class="confirm-btn">Oui, Supprimer</button>
                <button type="button" id="cancelDelete" class="cancel-btn">Non, Annuler</button>
            </div>
        </form>
    </div>
</div>

    <script>
        $(document).ready(function() {
            $('.modify-btn').click(function() {
                var pat_id = $(this).data('pat_id');
                var num_lit = $(this).data('num_lit');
                var salle_id = $(this).data('salle_id');
                var date_entree = $(this).data('date_entree');
                var date_sortie = $(this).data('date_sortie');

                // Remplir les champs de la modale
                $('#modify_pat_id').val(pat_id);
                $('#modify_num_lit').val(num_lit);
                $('#modify_new_pat_id').val(pat_id); // Mettre l'ID actuel dans le champ optionnel
                $('#modify_salle_id').val(salle_id);
                $('#modify_date_entree').val(date_entree);
                $('#modify_date_sortie').val(date_sortie);

                $('#modifyModal').show();
            });

            // Fermer la modale de modification
            $('#closeModifyModal').click(function() {
                $('#modifyModal').hide();
            });

            // Ouvrir la modale de suppression
            $('.delete-btn').click(function() {
                var pat_id = $(this).data('pat_id');
                var num_lit = $(this).data('num_lit');

                $('#delete_pat_id').val(pat_id);
                $('#delete_num_lit').val(num_lit);

                $('#deleteConfirmModal').show();
            });

            // Fermer la modale de suppression
            $('#closeDeleteModal').click(function() {
                $('#deleteConfirmModal').hide();
            });

            // Annuler la suppression
            $('#cancelDelete').click(function() {
                $('#deleteConfirmModal').hide();
            });
        });
    </script>
</body>
</html>