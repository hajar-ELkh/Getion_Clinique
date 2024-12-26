<?php 
include 'connectDB.php';

$id = $_GET['id'] ?? '';

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
    // Update patient details
    $id = $_POST['id'];
    $prenom = $_POST['prenom'];
    $nom = $_POST['nom'];
    $telephone = $_POST['telephone'];
    $age = $_POST['age'];
    $sexe = $_POST['sexe'];

    $stmt = $pdo->prepare("UPDATE patient SET pat_fname = ?, pat_lname = ?, pat_phone = ?, pat_age = ?, sexe = ? WHERE pat_id = ?");
    $stmt->execute([$prenom, $nom, $telephone, $age, $sexe, $id]);

    header('Location: index.php');
    exit;
} else {
    echo "Patient non trouvé.";
}

$stmt = $pdo->prepare("SELECT * FROM patient WHERE pat_id = ?");
$stmt->execute([$id]);
$patient = $stmt->fetch();

if (!$patient) {
    echo "Patient non trouvé.";
    exit;
}

// Afficher les détails du patient pour la mise à jour
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un Patient</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: url('img/img1.jpg') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
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
        header i {
            color: blue; /* Change the icon color to blue */
        }
        .form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            font-weight: bold;
            margin-bottom: 5px;
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
            display: inline-flex;
            align-items: center;
            gap: 5px;
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
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-user-edit"></i> Modifier un Patient</h1>
        </header>
        <main>
            <form method="POST" class="form">
                <div class="form-group">
                    <label>ID :</label>
                    <input type="text" name="id" value="<?= htmlspecialchars($patient['pat_id']) ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Prénom :</label>
                    <input type="text" name="prenom" value="<?= htmlspecialchars($patient['pat_fname']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Nom :</label>
                    <input type="text" name="nom" value="<?= htmlspecialchars($patient['pat_lname']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Téléphone :</label>
                    <input type="text" name="telephone" value="<?= htmlspecialchars($patient['pat_phone']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Âge :</label>
                    <input type="number" name="age" value="<?= htmlspecialchars($patient['pat_age']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Sexe :</label>
                    <select name="sexe" required>
                        <option value="H" <?= $patient['sexe'] === 'H' ? 'selected' : '' ?>>Homme</option>
                        <option value="F" <?= $patient['sexe'] === 'F' ? 'selected' : '' ?>>Femme</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-save"><i class="fas fa-save"></i> Enregistrer</button>
                    <a href="index.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Retour</a>
                </div>
            </form>
        </main>
    </div>
</body>
</html>
