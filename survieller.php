<?php
// Connexion à la base de données
function connectDB() {
    $host = 'localhost';
    $db = 'gestion_clinique';
    $user = 'root';
    $pass = '....';
    try {
        $conn = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch (PDOException $e) {
        die("Erreur de connexion : " . $e->getMessage());
    }
}

// Fonction pour ajouter une surveillance
function ajouterSurveillance($inf_id, $salle_id) {
    $conn = connectDB();

    // Vérification si l'infirmier et la salle existent
    $stmt_infirmier = $conn->prepare("SELECT COUNT(*) FROM infirmier WHERE inf_id = ?");
    $stmt_infirmier->execute([$inf_id]);

    $stmt_salle = $conn->prepare("SELECT COUNT(*) FROM chambre WHERE salle_id = ?");
    $stmt_salle->execute([$salle_id]);

    if ($stmt_infirmier->fetchColumn() == 0) {
        return "L'infirmier avec l'ID $inf_id n'existe pas.";
    }

    if ($stmt_salle->fetchColumn() == 0) {
        return "La salle avec l'ID $salle_id n'existe pas.";
    }

    // Ajout de la surveillance
    try {
        $sql = "INSERT INTO surveiller (inf_id, salle_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$inf_id, $salle_id]);
        return "Surveillance ajoutée avec succès.";
    } catch (PDOException $e) {
        return "Erreur lors de l'ajout : " . $e->getMessage();
    }
}

function modifiersurveillance($inf_id, $salle_id) {
    $conn = connectDB();
    try {
        $sql = "UPDATE surveiller SET salle_id = ? WHERE inf_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$salle_id, $inf_id]);
        return "Surveillance modifiée avec succès.";
    } catch (PDOException $e) {
        return "Erreur lors de la modification : " . $e->getMessage();
    }
}


// Fonction pour supprimer une surveillance
function supprimerSurveillance($inf_id, $salle_id) {
    $conn = connectDB();

    // Vérification si la surveillance existe
    $stmt = $conn->prepare("SELECT * FROM surveiller WHERE inf_id = ? AND salle_id = ?");
    $stmt->execute([$inf_id, $salle_id]);
    if ($stmt->rowCount() == 0) {
        return "La surveillance spécifiée n'existe pas.";
    }

    // Suppression de la surveillance
    try {
        $sql = "DELETE FROM surveiller WHERE inf_id = ? AND salle_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$inf_id, $salle_id]);
        return "Surveillance supprimée avec succès.";
    } catch (Exception $e) {
        return "Erreur lors de la suppression : " . $e->getMessage();
    }
}

// Fonction pour afficher toutes les surveillances
function afficherSurveillances() {
    $conn = connectDB();
    $sql = "
    SELECT 
        surveiller.salle_id,
        surveiller.inf_id,
        chambre.num_salle,
        chambre.nbr_lit,
        chambre.type_chambre,
        infirmier.inf_fname,
        infirmier.inf_lname,
        infirmier.inf_phone
    FROM surveiller
    JOIN chambre ON surveiller.salle_id = chambre.salle_id
    JOIN infirmier ON surveiller.inf_id = infirmier.inf_id
    ";
    $stmt = $conn->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_surveillance'])) {
        $inf_id = $_POST['inf_id'];
        $salle_id = $_POST['salle_id'];

        $result = ajouterSurveillance($inf_id, $salle_id);
        echo "<p style='color: green;'>$result</p>";
    }

    if (isset($_POST['modify_surveillance'])) {
        $inf_id = $_POST['modify_inf_id'];
        $salle_id = $_POST['modify_salle_id'];

        $result = modifierSurveillance($inf_id, $salle_id);
        echo "<p style='color: green;'>$result</p>";
    }

    if (isset($_POST['delete_surveillance'])) {
        $inf_id = $_POST['delete_inf_id'];
        $salle_id = $_POST['delete_salle_id'];

        $result = supprimerSurveillance($inf_id, $salle_id);
        echo "<p style='color: green;'>$result</p>";
    }
}



$surveillance = affichersurveillances();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des surveillances</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
</head>
<body>
    <div class="navbar bg-light p-2">
        <a href="interface.html" class="btn btn-primary"><i class="fas fa-home"></i> Accueil</a>
    </div>

    <div class="container mt-4">
        <h1>Ajouter une surveillance</h1>
        <form method="POST" action="">
            <div class="mb-3">
                <label for="inf_id" class="form-label">Infirmier ID :</label>
                <input type="number" id="inf_id" name="inf_id" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="salle_id" class="form-label">Salle ID :</label>
                <input type="number" id="salle_id" name="salle_id" class="form-control" required>
            </div>
            <button type="submit" name="add_surveillance" class="btn btn-success">Ajouter</button>
        </form>

        <h1 class="mt-5">Liste des surveillances</h1>
        <table id="surveillanceTable" class="table table-bordered">
            <thead>
                <tr>
                    <th>Infirmier ID</th>
                    <th>Nom de l'infirmier</th>
                    <th>Numéro de Lit</th>
                    <th>Salle ID</th>
                    <th>Numéro de Salle</th>
                    <th>Type de Chambre</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($surveillance as $surv) : ?>
                    <tr>
                        <td><?= htmlspecialchars($surv['inf_id']) ?></td>
                        <td><?= htmlspecialchars($surv['inf_fname'] . " " . $surv['inf_lname']) ?></td>
                        <td><?= htmlspecialchars($surv['nbr_lit']) ?></td>
                        <td><?= htmlspecialchars($surv['salle_id']) ?></td>
                        <td><?= htmlspecialchars($surv['num_salle']) ?></td>
                        <td><?= htmlspecialchars($surv['type_chambre']) ?></td>
                        <td>
                            <!-- Bouton Modifier -->
                            <button 
                                class="btn btn-primary modify-btn" 
                                data-bs-toggle="modal" 
                                data-bs-target="#modifyModal"
                                data-inf_id="<?= $surv['inf_id'] ?>" 
                                data-salle_id="<?= $surv['salle_id'] ?>" 
                            >
                                Modifier
                            </button>
                            <!-- Bouton Supprimer -->
                            <button 
                                class="btn btn-danger delete-btn" 
                                data-bs-toggle="modal" 
                                data-bs-target="#deleteConfirmModal"
                                data-inf_id="<?= $surv['inf_id'] ?>" 
                                data-salle_id="<?= $surv['salle_id'] ?>"
                            >
                                Supprimer
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal Modifier -->
    <div id="modifyModal" class="modal fade" tabindex="-1" aria-labelledby="modifyModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modifyModalLabel">Modifier la surveillance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                    <label for="modify_inf_id" class="form-label">Nouvelle Infirmier ID :</label>
                        <input type="number" id="modify_inf_id" name="modify_inf_id" required>
                        <div class="mb-3">
                            <label for="modify_salle_id" class="form-label">Nouvelle Salle ID :</label>
                            <input type="number" id="modify_salle_id" name="modify_salle_id" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        <button type="submit" name="modify_surveillance" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Supprimer -->
    <div id="deleteConfirmModal" class="modal fade" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteConfirmModalLabel">Confirmer la suppression</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <p>Êtes-vous sûr de vouloir supprimer cette surveillance ?</p>
                        <input type="hidden" id="delete_inf_id" name="delete_inf_id">
                        <input type="hidden" id="delete_salle_id" name="delete_salle_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" name="delete_surveillance" class="btn btn-danger">Confirmer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            // Initialisation de DataTable
            $('#surveillanceTable').DataTable();

            // Remplissage des données pour le modal Modifier
            $('.modify-btn').on('click', function () {
                const infId = $(this).data('inf_id');
                const salleId = $(this).data('salle_id');
                $('#modify_inf_id').val(infId);
                $('#modify_salle_id').val(salleId);
            });

            // Remplissage des données pour le modal Supprimer
            $('.delete-btn').on('click', function () {
                const infId = $(this).data('inf_id');
                const salleId = $(this).data('salle_id');
                $('#delete_inf_id').val(infId);
                $('#delete_salle_id').val(salleId);
            });
        });
    </script>
</body>
</html>
