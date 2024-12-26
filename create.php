<?php  
// Inclure la connexion à la base de données  
include 'connectDB.php';  

// Initialiser un tableau pour les messages  
$messages = [];  

// Traitement du formulaire pour Ajouter ou Mettre à Jour  
if ($_SERVER['REQUEST_METHOD'] === 'POST') {  
    $id = $_POST['id'] ?? null; // Récupérer l'ID si disponible, sinon nul  
    $prenom = $_POST['prenom'] ?? '';  
    $nom = $_POST['nom'] ?? '';  
    $telephone = $_POST['telephone'] ?? '';  
    $age = $_POST['age'] ?? '';  
    $sexe = $_POST['sexe'] ?? '';  

    // Si l'ID est renseigné, on met à jour le patient  
    if ($id) {  
        $stmt = $pdo->prepare("UPDATE patient SET pat_fname = ?, pat_lname = ?, pat_phone = ?, pat_age = ?, sexe = ? WHERE pat_id = ?");  
        if ($stmt->execute([$prenom, $nom, $telephone, $age, $sexe, $id])) {  
            $messages[] = "Patient mis à jour avec succès!";  
        } else {  
            $messages[] = "Erreur lors de la mise à jour du patient.";  
        }  
    } else {  
        // Sinon, on insère un nouveau patient  
        $stmt = $pdo->prepare("INSERT INTO patient (pat_fname, pat_lname, pat_phone, pat_age, sexe) VALUES (?, ?, ?, ?, ?)");  
        if ($stmt->execute([$prenom, $nom, $telephone, $age, $sexe])) {  
            $messages[] = "Patient ajouté avec succès!";  
        } else {  
            $messages[] = "Erreur lors de l'ajout du patient.";  
        }  
    }  
}  

// Si un ID est renseigné dans l'URL, on charge les données du patient pour les éditer  
if (isset($_GET['id'])) {  
    $id = $_GET['id'];  
    $stmt = $pdo->prepare("SELECT * FROM patient WHERE pat_id = ?");  
    $stmt->execute([$id]);  
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);  
}  

// Requête pour récupérer la liste des patients  
$stmt = $pdo->prepare("SELECT * FROM patient");  
$stmt->execute();  
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);  
?>  

<!DOCTYPE html>  
<html lang="fr">  
<head>  
    <meta charset="UTF-8">  
    <meta name="viewport" content="width=device-width, initial-scale=1.0">  
    <title>Ajouter ou Modifier un Patient</title>  
    <link rel="stylesheet" href="style.css">  
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">  
    <style>  
        /* Styles de base */  
        body {  
            font-family: Arial, sans-serif;  
            background: url('img/img2.jpg') no-repeat center center fixed;  
            background-size: cover;  
            margin: 0;  
            padding: 0;  
            display: flex;  
            justify-content: center;  
            align-items: flex-start;  
            height: 100vh;  
            overflow: auto;  
        }  
        .container {  
            max-width: 600px;  
            width: 100%;  
            background: rgba(255, 255, 255, 0.9);  
            border-radius: 8px;  
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);  
            padding: 20px;  
        }  
        header {  
            text-align: center;  
            margin-bottom: 20px;  
        }  
        header h1 {  
            color: #333;  
        }  
        .form {  
            display: flex;  
            flex-direction: column;  
            gap: 15px;  
        }  
        .form-group label {  
            font-weight: bold;  
        }  
        .form-group input, .form-group select {  
            padding: 10px;  
            font-size: 16px;  
            border: 1px solid #ccc;  
            border-radius: 5px;  
            width: 100%;  
            box-sizing: border-box;  
        }  
        .form-group input:focus, .form-group select:focus {  
            outline: none;  
            border-color: #007BFF;  
        }  
        .form-actions {  
            display: flex;  
            justify-content: space-between;  
        }  
        .btn {  
            padding: 10px 15px;  
            font-size: 16px;  
            color: #fff;  
            text-decoration: none;  
            border: none;  
            border-radius: 5px;  
            cursor: pointer;  
            transition: background 0.3s;  
        }  
        .btn-save {  
            background-color: #28a745;  
        }  
        .btn-save:hover {  
            background-color: #218838;  
        }  
        .btn-back {  
            background-color: #007BFF;  
        }  
        .btn-back:hover {  
            background-color: #0056b3;  
        }  
        .messages {  
            margin-bottom: 20px;  
            color: green;  
        }  
        .patient-list {  
            margin-top: 20px;  
        }  
        .patient-list table {  
            width: 100%;  
            border-collapse: collapse;  
        }  
        .patient-list th, .patient-list td {  
            padding: 8px;  
            text-align: left;  
            border: 1px solid #ddd;  
        }  
        .patient-list th {  
            background-color: #f2f2f2;  
        }  
    </style>  
</head>  
<body>  
    <div class="container">  
        <header>  
            <h1><i class="fas fa-user-plus"></i> Ajouter ou Modifier un Patient</h1>  
        </header>  
        <main>  
            <!-- Affichage des messages -->  
            <?php if (!empty($messages)): ?>  
                <div class="messages">  
                    <?php foreach ($messages as $message): ?>  
                        <p><?php echo htmlspecialchars($message); ?></p>  
                    <?php endforeach; ?>  
                </div>  
            <?php endif; ?>  

            <form method="POST" class="form">  
                <div class="form-group">  
                    <label>ID (laisser vide pour un nouvel ajout) :</label>  
                    <input type="number" name="id" value="<?php echo isset($patient) ? htmlspecialchars($patient['pat_id']) : ''; ?>" placeholder="ID du patient" min="1">  
                </div>  
                <div class="form-group">  
                    <label>Prénom :</label>  
                    <input type="text" name="prenom" value="<?php echo isset($patient) ? htmlspecialchars($patient['pat_fname']) : ''; ?>" required>  
                </div>  
                <div class="form-group">  
                    <label>Nom :</label>  
                    <input type="text" name="nom" value="<?php echo isset($patient) ? htmlspecialchars($patient['pat_lname']) : ''; ?>" required>  
                </div>  
                <div class="form-group">  
                    <label>Téléphone :</label>  
                    <input type="text" name="telephone" value="<?php echo isset($patient) ? htmlspecialchars($patient['pat_phone']) : ''; ?>" required>  
                </div>  
                <div class="form-group">  
                    <label>Âge :</label>  
                    <input type="number" name="age" value="<?php echo isset($patient) ? htmlspecialchars($patient['pat_age']) : ''; ?>" required min="1">  
                </div>  
                <div class="form-group">  
                    <label>Sexe :</label>  
                    <select name="sexe" required>  
                        <option value="H" <?php echo (isset($patient) && $patient['sexe'] === 'H') ? 'selected' : ''; ?>>Homme</option>  
                        <option value="F" <?php echo (isset($patient) && $patient['sexe'] === 'F') ? 'selected' : ''; ?>>Femme</option>  
                    </select>  
                </div>  
                <div class="form-actions">  
                    <button type="submit" class="btn btn-save"><i class="fas fa-save"></i> Enregistrer</button>  
                    <a href="index.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Retour</a>  
                </div>  
            </form>  

            <!-- Affichage des patients -->  
            <div class="patient-list">  
                <h2>Liste des Patients</h2>  
                <table>  
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
                                <td><?php echo htmlspecialchars($patient['pat_id']); ?></td>  
                                <td><?php echo htmlspecialchars($patient['pat_fname']); ?></td>  
                                <td><?php echo htmlspecialchars($patient['pat_lname']); ?></td>  
                                <td><?php echo htmlspecialchars($patient['pat_phone']); ?></td>  
                                <td><?php echo htmlspecialchars($patient['pat_age']); ?></td>  
                                <td><?php echo htmlspecialchars($patient['sexe']); ?></td>  
                                <td>  
                                    <a href="?id=<?php echo $patient['pat_id']; ?>" class="btn">Modifier</a>  
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