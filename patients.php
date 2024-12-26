<?php
include 'connectDB.php';

// Fetch all patients from the database
$stmt = $pdo->query("SELECT * FROM patient");
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Patients</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Liste des Patients</h1>
        </header>
        <main>
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
                    <?php if (!empty($patients)): ?>
                        <?php foreach ($patients as $patient): ?>
                            <tr>
                                <td><?= htmlspecialchars($patient['pat_id']) ?></td>
                                <td><?= htmlspecialchars($patient['pat_fname']) ?></td>
                                <td><?= htmlspecialchars($patient['pat_lname']) ?></td>
                                <td><?= htmlspecialchars($patient['pat_phone']) ?></td>
                                <td><?= htmlspecialchars($patient['pat_age']) ?></td>
                                <td><?= htmlspecialchars($patient['sexe']) ?></td>
                                <td>
                                    <a href="update.php?id=<?= $patient['pat_id'] ?>" class="btn btn-update">Modifier</a>
                                    <a href="delete.php?id=<?= $patient['pat_id'] ?>" class="btn btn-delete" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce patient ?')">Supprimer</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">Aucun patient trouvé.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="actions">
                <a href="create.php" class="btn btn-add">Ajouter un patient</a>
            </div>
        </main>
    </div>
</body>
</html>
