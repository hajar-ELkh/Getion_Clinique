<?php 
include 'connectDB.php'; // Connexion à la base de données

// Mise à jour automatique de l'état des chambres
try {
    // Met à jour les chambres pour les hospitalisations terminées (ou si aucune hospitalisation)
    $stmt = $pdo->prepare("
        UPDATE chambre c
        LEFT JOIN hospitaliser h ON c.salle_id = h.salle_id
        SET c.etat = CASE
            WHEN h.date_sortie IS NULL OR h.date_sortie > NOW() THEN 'occupée'
            ELSE 'libre'
        END
    ");
    $stmt->execute();
} catch (Exception $e) {
    $errors[] = "Erreur lors de la mise à jour des états des chambres : " . $e->getMessage();
}


// Initialisation des messages et erreurs
$messages = [];
$errors = [];


// Ajout d'une nouvelle chambre
if (isset($_POST['add_chambre'])) { 
    // Vérification de la présence des champs dans le formulaire
    $salle_id = isset($_POST['salle_id']) ? trim($_POST['salle_id']) : '';
    $num_salle = isset($_POST['num_salle']) ? trim($_POST['num_salle']) : '';
    $nbr_lit = isset($_POST['nbr_lit']) ? trim($_POST['nbr_lit']) : '';
    $type_chambre = isset($_POST['type_chambre']) ? trim($_POST['type_chambre']) : '';
    $etat = isset($_POST['etat']) ? trim($_POST['etat']) : '';

    if (empty($salle_id) || empty($num_salle) || empty($nbr_lit) || empty($type_chambre) || empty($etat)) {
        $errors[] = "Tous les champs sont requis.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM chambre WHERE salle_id = ?");
        $stmt->execute([$salle_id]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "L'ID de la chambre existe déjà.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO chambre (salle_id, num_salle, nbr_lit, type_chambre, etat) VALUES (?, ?, ?, ?,?)");
            if ($stmt->execute([$salle_id, $num_salle, $nbr_lit, $type_chambre,$etat])) {
                $messages[] = "Chambre ajoutée avec succès.";
            } else {
                $errors[] = "Erreur lors de l'ajout de la chambre.";
            }
        }
    }
}
// Mise à jour d'une chambre
if (isset($_POST['update_chambre'])) { 
    // Vérification de la présence des champs dans le formulaire
    $salle_id = isset($_POST['salle_id']) ? trim($_POST['salle_id']) : '';
    $num_salle = isset($_POST['num_salle']) ? trim($_POST['num_salle']) : '';
    $nbr_lit = isset($_POST['nbr_lit']) ? trim($_POST['nbr_lit']) : '';
    $type_chambre = isset($_POST['type_chambre']) ? trim($_POST['type_chambre']) : '';
    $etat = isset($_POST['etat']) ? trim($_POST['etat']) : '';

    if (empty($salle_id) || empty($num_salle) || empty($nbr_lit) || empty($type_chambre) || empty($etat)) {
        $errors[] = "Tous les champs sont requis.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM chambre WHERE salle_id = ?");
        $stmt->execute([$salle_id]);
        if ($stmt->rowCount() == 0) {
            $errors[] = "La chambre avec cet ID n'existe pas.";
        } else {
            $stmt = $pdo->prepare("UPDATE chambre SET num_salle = ?, nbr_lit = ?, type_chambre = ?, etat = ? WHERE salle_id = ?");
            if ($stmt->execute([$num_salle, $nbr_lit, $type_chambre, $etat ,$salle_id])) {
                $messages[] = "Chambre modifiée avec succès.";
            } else {
                $errors[] = "Erreur lors de la modification de la chambre.";
            }
        }
    }
}
// Suppression d'une chambre
if (isset($_POST['delete_chambre'])) {
    $salle_id = trim($_POST['delete_chambre']);  // Récupère l'ID de la chambre à supprimer

    // Vérifier si l'ID de la chambre existe
    if (!empty($salle_id)) {
        $stmt = $pdo->prepare("SELECT * FROM chambre WHERE salle_id = ?");
        $stmt->execute([$salle_id]);
        
        if ($stmt->rowCount() > 0) {
            // La chambre existe, vérifier si elle est liée à d'autres tables
            $linked_tables = [
                'hospitaliser' => 'salle_id',
                'surveiller' => 'salle_id'
            ];

            $linked = false;
            foreach ($linked_tables as $table => $column) {
                $stmt = $pdo->prepare("SELECT * FROM $table WHERE $column = ?");
                $stmt->execute([$salle_id]);
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
                echo '<p style="margin-top: 20px;">Cette chambre est liée à d\'autres enregistrements </p>';  
                
                // Liste des tables liées (ajout des tables liées ici)  
                echo '<ul style="list-style-type: none; padding-left: 0; margin: 10px 0; font-size: 1.1em;">';   
                echo '</ul>';  
                
                // Message de confirmation avant les boutons  
                echo '<p style="margin-top: 20px;">Souhaitez-vous supprimer tous les enregistrements associés ?</p>';  
                
                // Formulaire de confirmation  
                echo '<form method="POST" style="margin-top: 20px;">';  
                echo '<input type="hidden" name="salle_id" value="' . htmlspecialchars($salle_id) . '">';  
                
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
                // Si la chambre n'est liée à aucune autre table, supprimer directement
                $stmt = $pdo->prepare("DELETE FROM chambre WHERE salle_id = ?");
                if ($stmt->execute([$salle_id])) {
                    $messages[] = "Chambre supprimée avec succès.";
                } else {
                    $errors[] = "Erreur lors de la suppression de la chambre.";
                }
            }
        } else {
            $errors[] = "La chambre avec cet ID n'existe pas.";
        }
    } else {
        $errors[] = "L'ID de la chambre est requis pour la suppression.";
    }
}

// Traitement de la confirmation de suppression
if (isset($_POST['confirm_delete'])) {
    $salle_id = trim($_POST['salle_id']);
    $delete_related = $_POST['delete_related']; // Récupère si l'utilisateur veut supprimer les enregistrements associés

    if ($delete_related === 'yes') {
        // Si l'utilisateur veut supprimer tous les enregistrements associés
        $linked_tables = [
            'hospitaliser' => 'salle_id',
            'surveiller' => 'salle_id'
        ];

        // Supprimer les enregistrements associés dans toutes les tables
        foreach ($linked_tables as $table => $column) {
            $stmt = $pdo->prepare("DELETE FROM $table WHERE $column = ?");
            $stmt->execute([$salle_id]);
        }

        // Ensuite, supprimer la chambre de la table principale
        $stmt = $pdo->prepare("DELETE FROM chambre WHERE salle_id = ?");
        if ($stmt->execute([$salle_id])) {
            $messages[] = "Chambre et tous ses enregistrements associés ont été supprimés avec succès.";
        } else {
            $errors[] = "Erreur lors de la suppression de la chambre.";
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
    $allowed_columns = ['salle_id', 'num_salle', 'nbr_lit', 'type_chambre', 'etat'];

    // Si l'option 'All' est choisie, chercher sur toutes les colonnes
    if ($search_type === 'all') {
        // Recherche sur toutes les colonnes en combinant des conditions pour chaque colonne
        $stmt = $pdo->query("SELECT * FROM chambre");
    } elseif (in_array($search_type, $allowed_columns)) {
        // Si la colonne est un champ VARCHAR, utiliser LIKE pour une recherche partielle
        if (in_array($search_type, ['num_salle', 'type_chambre', 'etat'])) {
            $stmt = $pdo->prepare("SELECT * FROM chambre WHERE $search_type LIKE ?");
            $stmt->execute(["$search_value%"]);
        } else {
            // Pour les colonnes non VARCHAR (par exemple, 'salle_id', 'nbr_lit'), utiliser une recherche exacte
            $stmt = $pdo->prepare("SELECT * FROM chambre WHERE $search_type = ?");
            $stmt->execute([$search_value]);
        }
    } else {
        // Si la colonne spécifiée n'est pas valide, afficher toutes les chambres
        $stmt = $pdo->query("SELECT * FROM chambre");
    }
} else {
    // Si aucun critère de recherche n'est défini, afficher toutes les chambres
    $stmt = $pdo->query("SELECT * FROM chambre");
}

// Récupérer les résultats de la requête
$chambres = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Chambres</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;  
            background: url('img/1.jpg') no-repeat center center fixed; /* Ajustez le chemin de l'image ici */  
            background-size: cover;  
            margin: 0;  
            padding: 0;   
            font-family: 'Arial', sans-serif;
        }

        .navbar {
            background-color: #004aad;
            padding: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .navbar a {
            color: white;
            text-decoration: none;
            margin: 0 15px;
            font-size: 1.2rem;
            font-weight: bold;
            transition: color 0.3s;
        }

        .navbar a:hover {
            color: #ffd700;
        }

        .container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            margin-top: 2rem;
        }

        h1 {
            color: #004aad;
            text-align: center;
            margin-bottom: 2rem;
        }

        .form-group label {
            font-weight: bold;
        }

        .btn-primary {
            background-color: #004aad;
            border: none;
        }

        .btn-primary:hover {
            background-color: #003080;
        }

        table {
            margin-top: 2rem;
        }

        table th {
            background-color: #004aad;
            color: white;
        }

        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .modal-header {
            background-color: #004aad;
            color: white;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="interface.html"><i class="fas fa-home"></i> Accueil</a>
        <a href="patient.php"><i class="fas fa-user-injured"></i> Gérer Patients</a>
        <a href="survieller.php"><i class="fas fa-user-injured"></i> Gérer Les Surveillances</a>
        <a href="infirmier.php"><i class="fas fa-bed"></i> Gérer Infirmiers</a>
        <a href="hospitaliser.php"><i class="fas fa-hospital"></i> Gérer Hospitalisations</a>
        <a href="chambre.php"><i class="fas fa-bed"></i> Gérer Chambres</a>
    </div>

    <div class="container">
        <h1><i class="fas fa-bed"></i> Gestion des Chambres</h1>

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

        <!-- Formulaire d'ajout -->
        <form method="POST" class="mb-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <input type="text" name="salle_id" class="form-control" placeholder="ID Salle" required>
                </div>
                <div class="col-md-3">
                    <input type="text" name="num_salle" class="form-control" placeholder="Numéro de Salle" required>
                </div>
                <div class="col-md-3">
                    <input type="number" name="nbr_lit" class="form-control" placeholder="Nombre de Lits" required>
                </div>
                <div class="col-md-3">
                    <input type="text" name="type_chambre" class="form-control" placeholder="Type de Chambre" required>
                </div>
                <div class="col-md-3">
                    <input type="text" name="etat" class="form-control" placeholder="Etat" required>
                </div>
                <div class="col-md-12">
                    <button type="submit" name="add_chambre" class="btn btn-primary w-100">
                        <i class="fas fa-plus"></i> Ajouter
                    </button>
                </div>
            </div>
        </form>

        <!-- Formulaire de recherche -->
        <form method="GET" class="mb-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <select name="search_type" class="form-select" required>
                        <option value="all">Tous</option>
                        <option value="salle_id">ID Salle</option>
                        <option value="num_salle">Numéro de Salle</option>
                        <option value="nbr_lit">Nombre de Lits</option>
                        <option value="type_chambre">Type de Chambre</option>
                        <option value="etat">Etat</option>
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

        <!-- Tableau des chambres -->
        <table id="chambreTable" class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>ID Salle</th>
                    <th>Numéro de Salle</th>
                    <th>Nombre de Lits</th>
                    <th>Type de Chambre</th>
                    <th>Etat</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($chambres as $chambre): ?>
                    <tr>
                        <td><?= htmlspecialchars($chambre['salle_id']); ?></td>
                        <td><?= htmlspecialchars($chambre['num_salle']); ?></td>
                        <td><?= htmlspecialchars($chambre['nbr_lit']); ?></td>
                        <td><?= htmlspecialchars($chambre['type_chambre']); ?></td>
                        <td><?= htmlspecialchars($chambre['etat']); ?></td>
                        <td>
                            <div class="d-flex">
                                <button class="btn btn-warning btn-sm me-2" data-bs-toggle="modal" data-bs-target="#editModal"
                                        data-id="<?= $chambre['salle_id']; ?>"
                                        data-num_salle="<?= $chambre['num_salle']; ?>"
                                        data-nbr_lit="<?= $chambre['nbr_lit']; ?>"
                                        data-type_chambre="<?= $chambre['type_chambre']; ?>"
                                        data-etat="<?= $chambre['etat']; ?>">
                                    <i class="fas fa-edit"></i> Modifier
                                </button>
                                <form method="POST" action="" class="d-inline">
                                    <button type="submit" name="delete_chambre" value="<?= $chambre['salle_id']; ?>" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i> Supprimer
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Modal d'édition -->
        <div class="modal fade" id="editModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Modifier la Chambre</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="salle_id" id="edit_salle_id">
                            <div class="mb-3">
                                <label for="edit_num_salle" class="form-label">Numéro de Salle</label>
                                <input type="text" name="num_salle" id="edit_num_salle" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_nbr_lit" class="form-label">Nombre de Lits</label>
                                <input type="number" name="nbr_lit" id="edit_nbr_lit" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_type_chambre" class="form-label">Type de Chambre</label>
                                <input type="text" name="type_chambre" id="edit_type_chambre" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_etat" class="form-label">Etat</label>
                                <input type="text" name="etat" id="edit_etat" class="form-control" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="update_chambre" class="btn btn-primary">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#comptableTable').DataTable({
                searching: false // Désactive la barre de recherche
            });
        });

        $('#editModal').on('show.bs.modal', function (event) {
            const button = $(event.relatedTarget);
            $('#edit_salle_id').val(button.data('id'));
            $('#edit_num_salle').val(button.data('num_salle'));
            $('#edit_nbr_lit').val(button.data('nbr_lit'));
            $('#edit_type_chambre').val(button.data('type_chambre'));
            $('#edit_etat').val(button.data('etat'));
        });
    </script>
</body>
</html>