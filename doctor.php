<?php  
include 'connectDB.php'; // Connexion à la base de données  
$messages = []; // Pour les messages de retour  

// Gérer l'ajout et la mise à jour d'un médecin  
if ($_SERVER['REQUEST_METHOD'] === 'POST') {  
    // Récupérer les données du formulaire  
    $id = $_POST['id'] ?? null;  
    $fname = $_POST['prenom'] ?? '';  
    $lname = $_POST['nom'] ?? '';  
    $phone = $_POST['telephone'] ?? '';  
    $age = $_POST['age'] ?? null; // Nouvel ajout pour l'âge  
    $specialty = $_POST['specialty'] ?? '';  

    if ($id) {  
        // Mise à jour d'un médecin existant  
        $stmt = $pdo->prepare("UPDATE doctor SET doc_fname = ?, doc_lname = ?, doc_phone = ?, doc_age = ?, speciality = ? WHERE doc_id = ?");  
        if ($stmt->execute([$fname, $lname, $phone, $age, $specialty, $id])) {  
            $messages[] = "Médecin mis à jour avec succès!";  
        } else {  
            $messages[] = "Erreur lors de la mise à jour du médecin.";  
        }  
    } else {  
        // Ajout d'un nouveau médecin  
        $stmt = $pdo->prepare("INSERT INTO doctor (doc_fname, doc_lname, doc_phone, doc_age, speciality) VALUES (?, ?, ?, ?, ?)");  
        if ($stmt->execute([$fname, $lname, $phone, $age, $specialty])) {  
            $messages[] = "Médecin ajouté avec succès!";  
        } else {  
            $messages[] = "Erreur lors de l'ajout du médecin.";  
        }  
    }  
}  

// Charger un médecin pour l'édition  
if (isset($_GET['id'])) {  
    $id = $_GET['id'];  
    $stmt = $pdo->prepare("SELECT * FROM doctor WHERE doc_id = ?");  
    $stmt->execute([$id]);  
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);  
}  

// Récupérer la liste des médecins  
$stmt = $pdo->query("SELECT * FROM doctor");  
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);  
?>  

<!DOCTYPE html>  
<html lang="fr">  
<head>  
    <meta charset="UTF-8">  
    <title>Gestion des Médecins</title>  
    <link rel="stylesheet" href="style.css"> <!-- Votre feuille de style -->  
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">  
</head>  
<body>  
    <div class="container">  
        <header>  
            <h1><i class="fas fa-user-md"></i> Ajouter ou Modifier un Médecin</h1>  
        </header>  
        <main>  
            <?php if (!empty($messages)): ?>  
                <div class="messages">  
                    <?php foreach ($messages as $message): ?>  
                        <p><?php echo htmlspecialchars($message); ?></p>  
                    <?php endforeach; ?>  
                </div>  
            <?php endif; ?>  

            <form method="POST" class="form">  
                <div class="form-group">  
                    <label>ID (laisser vide pour ajouter un nouveau médecin) :</label>  
                    <input type="number" name="id" value="<?php echo isset($doctor) ? htmlspecialchars($doctor['doc_id']) : ''; ?>" placeholder="ID du médecin">  
                </div>  
                <div class="form-group">  
                    <label>Prénom :</label>  
                    <input type="text" name="prenom" value="<?php echo isset($doctor) ? htmlspecialchars($doctor['doc_fname']) : ''; ?>" required>  
                </div>  
                <div class="form-group">  
                    <label>Nom :</label>  
                    <input type="text" name="nom" value="<?php echo isset($doctor) ? htmlspecialchars($doctor['doc_lname']) : ''; ?>" required>  
                </div>  
                <div class="form-group">  
                    <label>Téléphone :</label>  
                    <input type="text" name="telephone" value="<?php echo isset($doctor) ? htmlspecialchars($doctor['doc_phone']) : ''; ?>" required>  
                </div>  
                <div class="form-group">  
                    <label>Âge :</label>  
                    <input type="number" name="age" value="<?php echo isset($doctor) ? htmlspecialchars($doctor['doc_age']) : ''; ?>" required min="0">  
                </div>  
                <div class="form-group">  
                    <label>Spécialité :</label>  
                    <input type="text" name="specialty" value="<?php echo isset($doctor) ? htmlspecialchars($doctor['speciality']) : ''; ?>" required>  
                </div>  
                <div class="form-actions">  
                    <button type="submit" class="btn btn-save"><i class="fas fa-save"></i> Enregistrer</button>  
                    <a href="index.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Retour</a>  
                </div>  
            </form>  

            <div class="patient-list">  
                <h2>Liste des Médecins</h2>  
                <table>  
                    <thead>  
                        <tr>  
                            <th>ID</th>  
                            <th>Prénom</th>  
                            <th>Nom</th>  
                            <th>Téléphone</th>  
                            <th>Âge</th>  
                            <th>Spécialité</th>  
                            <th>Actions</th>  
                        </tr>  
                    </thead>  
                    <tbody>  
                        <?php foreach ($doctors as $doctor): ?>  
                            <tr>  
                                <td><?php echo htmlspecialchars($doctor['doc_id']); ?></td>  
                                <td><?php echo htmlspecialchars($doctor['doc_fname']); ?></td>  
                                <td><?php echo htmlspecialchars($doctor['doc_lname']); ?></td>  
                                <td><?php echo htmlspecialchars($doctor['doc_phone']); ?></td>  
                                <td><?php echo htmlspecialchars($doctor['doc_age']); ?></td>  
                                <td><?php echo htmlspecialchars($doctor['speciality']); ?></td>  
                                <td>  
                                    <a href="?id=<?php echo $doctor['doc_id']; ?>" class="btn">Modifier</a>  
                                </td>  
                            </tr>  
                        <?php endforeach; ?>  
                    </tbody>  
                </table>  
            </div>  
        </main>  
    </div>  
</body>  
</html>