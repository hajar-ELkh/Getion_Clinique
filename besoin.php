<?php 
// Inclure la connexion à la base de données
include 'connectDB.php'; // Assurez-vous que le chemin est correct

// Initialisation des variables
$resultats = [];
$besoin = '';
$nature = '';
$valeur = '';
$params = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $besoin = $_POST['besoin'];
    $nature = $_POST['nature'] ?? 'all'; // Par défaut : Tous
    $valeur = $_POST['valeur'] ?? '';
    $lettre = $_POST['lettre'] ?? ''; // Filtre pour les valeurs commençant par une lettre
  // Tableau des paramètres pour la requête préparée

    // Préparation de la requête selon le besoin
    if ($besoin === "patients_hospitalises") {
        $sql = "SELECT p.pat_id, p.pat_fname, p.pat_lname, p.pat_age, p.sexe, c.num_salle, c.nbr_lit
                FROM patient p
                JOIN hospitaliser h ON p.pat_id = h.pat_id
                JOIN chambre c ON h.salle_id = c.salle_id
                WHERE h.date_sortie IS NOT NULL";

        // Ajout du filtre dynamique
        if ($nature !== 'all' && !empty($valeur)) {
            $sql .= " AND $nature LIKE :valeur";
            $params['valeur'] = '%' . $valeur . '%';
        }

    } elseif ($besoin === "rendez_vous_avenir") {
        $sql = "SELECT r.id_RD, r.date_RD, r.horaire, p.pat_fname, p.pat_lname, d.doc_fname, d.doc_lname
                FROM rendez_vous r
                JOIN patient p ON r.pat_id = p.pat_id
                JOIN doctor d ON r.doc_id = d.doc_id
                WHERE r.date_RD > CURRENT_DATE";

        // Ajout du filtre dynamique
        if ($nature !== 'all' && !empty($valeur) && $valeur !== 'tous') {
            $sql .= " AND $nature LIKE :valeur";
            $params['valeur'] = '%' . $valeur . '%';
        }

    } elseif ($besoin === "medecins_consultations") {
        $sql = "SELECT d.doc_id, d.doc_fname, d.doc_lname, d.speciality, COUNT(c.id_consul) AS total_consultations
                FROM doctor d
                LEFT JOIN consultation c ON d.doc_id = c.doc_id
                GROUP BY d.doc_id, d.doc_fname, d.doc_lname, d.speciality";

        // Ajout du filtre dynamique
        if ($nature !== 'all' && !empty($valeur) && $valeur !== 'tous') {
            $sql .= " HAVING $nature LIKE :valeur"; // Utilisation de HAVING pour les colonnes agrégées
            $params['valeur'] = '%' . $valeur . '%';
        }

        // Filtrer pour sélectionner uniquement les valeurs commençant par une lettre donnée
        if (!empty($lettre) && ctype_alpha($lettre)) {
            $sql .= " HAVING $nature LIKE :lettre";
            $params['lettre'] = $lettre . '%';
        }
    }elseif ($besoin === "consultations_details") {
        $sql = "SELECT c.id_consul, c.date_consul, c.desc_consul, p.pat_fname, p.pat_lname, d.doc_fname, d.doc_lname
                                    FROM consultation c
                                    JOIN doctor d ON c.doc_id = d.doc_id
                                    JOIN patient p ON c.pat_id = p.pat_id";

        // Ajout du filtre dynamique uniquement si une valeur spécifique est demandée
        if ($nature !== 'all' && !empty($valeur) && $valeur !== 'tous') {
            $sql .= " AND $nature LIKE :valeur";
        }
    } elseif ($besoin === "factures_impayees") {
        $sql = "SELECT p.pat_id, p.pat_fname, p.pat_lname, f.montant
                FROM facture f
                JOIN patient p ON f.pat_id = p.pat_id
                WHERE f.date_pay IS NULL";
    
        // Ajout du filtre dynamique
        if ($nature !== 'all' && !empty($valeur) && $valeur !== 'tous') {
            $sql .= " AND $nature LIKE :valeur";
            $params['valeur'] = '%' . $valeur . '%';
        }
    
        // Filtrer pour les valeurs commençant par une lettre
        if (!empty($lettre) && ctype_alpha($lettre)) {
            $sql .= " AND $nature LIKE :lettre";
            $params['lettre'] = $lettre . '%';
        }
    
    } elseif ($besoin === "nbr_cons_par_spec_doc") {
        $sql = "SELECT d.doc_fname, d.doc_lname, d.speciality, COUNT(c.id_consul) AS total_consultations
                FROM doctor d 
                JOIN consultation c ON d.doc_id = c.doc_id 
                GROUP BY d.doc_fname, d.doc_lname, d.speciality";
    
        // Ajout du filtre dynamique
        if ($nature !== 'all' && !empty($valeur) && $valeur !== 'tous') {
            $sql .= " HAVING $nature LIKE :valeur"; // HAVING pour les colonnes agrégées
            $params['valeur'] = '%' . $valeur . '%';
        }
    
        // Filtrer pour les valeurs commençant par une lettre
        if (!empty($lettre) && ctype_alpha($lettre)) {
            $sql .= " HAVING $nature LIKE :lettre";
            $params['lettre'] = $lettre . '%';
        }
    
    } elseif ($besoin === "analyses_medicales_details") {
        $sql = "SELECT a.id_analyse_med, a.date_analyse_med, a.desc_analyse_med, 
                       t.tech_labo_fname, t.tech_labo_lname, d.doc_fname, d.doc_lname 
                FROM analyse_medicale a 
                JOIN technicien_labo t ON a.tech_labo_id = t.tech_labo_id 
                JOIN doctor d ON a.doc_id = d.doc_id";
    
        // Ajout du filtre dynamique
        if ($nature !== 'all' && !empty($valeur) && $valeur !== 'tous') {
            $sql .= " AND $nature LIKE :valeur";
            $params['valeur'] = '%' . $valeur . '%';
        }
    
        // Filtrer pour les valeurs commençant par une lettre
        if (!empty($lettre) && ctype_alpha($lettre)) {
            $sql .= " AND $nature LIKE :lettre";
            $params['lettre'] = $lettre . '%';
        }
    
    } elseif ($besoin === "patients_avec_ordonnances_et_medecins") {
        $sql = "SELECT o.ord_id, o.ord_descr, p.pat_fname, p.pat_lname, d.doc_fname, d.doc_lname 
                FROM ordonnance o 
                JOIN patient p ON o.pat_id = p.pat_id 
                JOIN doctor d ON o.doc_id = d.doc_id";
    
        // Ajout du filtre dynamique
        if ($nature !== 'all' && !empty($valeur) && $valeur !== 'tous') {
            $sql .= " AND $nature LIKE :valeur";
            $params['valeur'] = '%' . $valeur . '%';
        }
    
        // Filtrer pour les valeurs commençant par une lettre
        if (!empty($lettre) && ctype_alpha($lettre)) {
            $sql .= " AND $nature LIKE :lettre";
            $params['lettre'] = $lettre . '%';
        }
    }
     elseif ($besoin === "details_hospitalisations") {
        $sql = "SELECT p.pat_id, p.pat_fname, p.pat_lname, c.num_salle, DATEDIFF(CURRENT_DATE, h.date_entree) AS duree_hospitalisation 
                                        FROM hospitaliser h 
                                        JOIN patient p ON h.pat_id = p.pat_id 
                                        JOIN chambre c ON h.salle_id = c.salle_id";

        // Ajout du filtre dynamique uniquement si une valeur spécifique est demandée
        if ($nature !== 'all' && !empty($valeur) && $valeur !== 'tous') {
            $sql .= " AND $nature LIKE :valeur";
            $params['valeur'] = '%' . $valeur . '%';
        }
        // Filtrer pour les valeurs commençant par une lettre
        if (!empty($lettre) && ctype_alpha($lettre)) {
            $sql .= " AND $nature LIKE :lettre";
            $params['lettre'] = $lettre . '%';
        }
    } elseif ($besoin === "analyses_medicales_mois") {
        $sql = "SELECT a.desc_analyse_med, 
        COUNT(CASE WHEN MONTH(a.date_analyse_med) = MONTH(CURRENT_DATE) THEN 1 END) AS total_analyses_mois_en_cours,
        COUNT(CASE WHEN MONTH(a.date_analyse_med) = MONTH(CURRENT_DATE) - 1 THEN 1 END) AS total_analyses_mois_precedent
        FROM analyse_medicale a 
        WHERE MONTH(a.date_analyse_med) = MONTH(CURRENT_DATE) 
            OR MONTH(a.date_analyse_med) = MONTH(CURRENT_DATE) - 1
        GROUP BY a.desc_analyse_med";
        // Ajout du filtre dynamique uniquement si une valeur spécifique est demandée
        if ($nature !== 'all' && !empty($valeur) && $valeur !== 'tous') {
            $sql .= " AND $nature LIKE :valeur";
            $params['valeur'] = '%' . $valeur . '%';
        }
                // Filtrer pour les valeurs commençant par une lettre
                if (!empty($lettre) && ctype_alpha($lettre)) {
                    $sql .= " AND $nature LIKE :lettre";
                    $params['lettre'] = $lettre . '%';
                }
    } elseif ($besoin === "medecins_avec_consultations_mois") {
        $sql = "SELECT 
                    d.doc_id, 
                    d.doc_fname, 
                    d.doc_lname, 
                    COUNT(CASE WHEN MONTH(c.date_consul) = :mois_selectionne THEN c.id_consul END) AS total_consultations
                FROM doctor d
                JOIN consultation c ON d.doc_id = c.doc_id
                WHERE MONTH(c.date_consul) = :mois_selectionne
                GROUP BY d.doc_id, d.doc_fname, d.doc_lname";

        // Ajout du filtre dynamique uniquement si une valeur spécifique est demandée
        if ($nature !== 'all' && !empty($valeur) && $valeur !== 'tous') {
            $sql .= " AND $nature LIKE :valeur";
            $params['valeur'] = '%' . $valeur . '%';
        }        // Filtrer pour les valeurs commençant par une lettre
        if (!empty($lettre) && ctype_alpha($lettre)) {
            $sql .= " AND $nature LIKE :lettre";
            $params['lettre'] = $lettre . '%';
        }
    } elseif ($besoin === "patients_hos_avec_factures") {
        $sql = "SELECT p.pat_id, p.pat_fname, p.pat_lname, h.date_entree, h.date_sortie, f.montant
                                                FROM patient p 
                                                JOIN hospitaliser h ON p.pat_id = h.pat_id
                                                JOIN facture f ON p.pat_id = f.pat_id";

        // Ajout du filtre dynamique uniquement si une valeur spécifique est demandée
        if ($nature !== 'all' && !empty($valeur) && $valeur !== 'tous') {
            $sql .= " AND $nature LIKE :valeur";
            $params['valeur'] = '%' . $valeur . '%';
        }
                // Filtrer pour les valeurs commençant par une lettre
                if (!empty($lettre) && ctype_alpha($lettre)) {
                    $sql .= " AND $nature LIKE :lettre";
                    $params['lettre'] = $lettre . '%';
                }
    } 
    elseif ($besoin === "medecins_sans_consultations_mois") {
        $sql ="SELECT d.doc_id, d.doc_fname, d.doc_lname, d.speciality 
                                    FROM doctor d 
                                    LEFT JOIN consultation c ON d.doc_id = c.doc_id 
                                    WHERE MONTH(c.date_consul) != MONTH(CURRENT_DATE) OR c.date_consul IS NULL";

        // Ajout du filtre dynamique uniquement si une valeur spécifique est demandée
        if ($nature !== 'all' && !empty($valeur) && $valeur !== 'tous') {
            $sql .= " AND $nature LIKE :valeur";
            $params['valeur'] = '%' . $valeur . '%';
        }
                // Filtrer pour les valeurs commençant par une lettre
                if (!empty($lettre) && ctype_alpha($lettre)) {
                    $sql .= " AND $nature LIKE :lettre";
                    $params['lettre'] = $lettre . '%';
                }
    } 
    elseif ($besoin === "patients_par_chambre") {
        $sql = "SELECT c.num_salle, c.nbr_lit, COUNT(h.pat_id) AS nb_patients_hospitalises 
                                FROM chambre c 
                                LEFT JOIN hospitaliser h ON c.salle_id = h.salle_id 
                                WHERE h.date_sortie IS NOT NULL 
                                GROUP BY c.num_salle, c.nbr_lit";

        // Ajout du filtre dynamique uniquement si une valeur spécifique est demandée
        if ($nature !== 'all' && !empty($valeur) && $valeur !== 'tous') {
            $sql .= " AND $nature LIKE :valeur";
            $params['valeur'] = '%' . $valeur . '%';
        }
                // Filtrer pour les valeurs commençant par une lettre
                if (!empty($lettre) && ctype_alpha($lettre)) {
                    $sql .= " AND $nature LIKE :lettre";
                    $params['lettre'] = $lettre . '%';
                }
    } 
    elseif ($besoin === "rendez_vous_par_medecin_specialite") {
        $sql = "SELECT r.id_RD, r.date_RD, r.horaire, p.pat_fname, p.pat_lname, d.doc_fname, d.doc_lname 
                                                FROM rendez_vous r 
                                                JOIN patient p ON r.pat_id = p.pat_id 
                                                JOIN consultation c ON p.pat_id = c.pat_id 
                                                JOIN doctor d ON c.doc_id = d.doc_id";

        // Ajout du filtre dynamique uniquement si une valeur spécifique est demandée
        if ($nature !== 'all' && !empty($valeur) && $valeur !== 'tous') {
            $sql .= " AND $nature LIKE :valeur";
            $params['valeur'] = '%' . $valeur . '%';
        }
                // Filtrer pour les valeurs commençant par une lettre
                if (!empty($lettre) && ctype_alpha($lettre)) {
                    $sql .= " AND $nature LIKE :lettre";
                    $params['lettre'] = $lettre . '%';
                }
    } 
    elseif ($besoin === "patients_par_sexe_et_age") {
        $sql = "SELECT sexe, CASE WHEN pat_age BETWEEN 0 AND 18 THEN '0-18' WHEN pat_age BETWEEN 19 AND 35 THEN '19-35' WHEN pat_age BETWEEN 36 AND 50 THEN '36-50' WHEN pat_age BETWEEN 51 AND 65 THEN '51-65' ELSE '65+' END AS tranche_age, COUNT(*) AS nb_patients 
                                        FROM patient 
                                        GROUP BY sexe, tranche_age";

        // Ajout du filtre dynamique uniquement si une valeur spécifique est demandée
        if ($nature !== 'all' && !empty($valeur) && $valeur !== 'tous') {
            $sql .= " AND $nature LIKE :valeur";
            $params['valeur'] = '%' . $valeur . '%';
        }
                // Filtrer pour les valeurs commençant par une lettre
                if (!empty($lettre) && ctype_alpha($lettre)) {
                    $sql .= " AND $nature LIKE :lettre";
                    $params['lettre'] = $lettre . '%';
                }
    } 
    elseif ($besoin === "ordonnances_avec_medicaments") {
        $sql = "SELECT o.ord_id, o.ord_descr, p.pat_id, p.pat_fname, p.pat_lname ,
                                            FROM ordonnance o 
                                            JOIN patient p ON o.pat_id = p.pat_id"
                                            ;

        // Ajout du filtre dynamique uniquement si une valeur spécifique est demandée
        if ($nature !== 'all' && !empty($valeur) && $valeur !== 'tous') {
            $sql .= " AND $nature LIKE :valeur";
            $params['valeur'] = '%' . $valeur . '%';
        }
                // Filtrer pour les valeurs commençant par une lettre
                if (!empty($lettre) && ctype_alpha($lettre)) {
                    $sql .= " AND $nature LIKE :lettre";
                    $params['lettre'] = $lettre . '%';
                }
    } 
    elseif ($besoin === "ord_med_age_tranche") {
        $sql = "SELECT   
                    o.ord_id,   
                    o.ord_descr,   
                    p.pat_id,   
                    p.pat_fname,   
                    p.pat_lname,  
                    p.pat_age,  
                    CASE   
                        WHEN p.pat_age BETWEEN 51 AND 65 THEN '51-65'  
                        WHEN p.pat_age > 65 THEN '65+'  
                        ELSE 'Under 51'  
                    END AS tranche_age  
                FROM   
                    ordonnance o   
                JOIN   
                    patient p ON o.pat_id = p.pat_id";

        // Ajout du filtre dynamique uniquement si une valeur spécifique est demandée
        if ($nature !== 'all' && !empty($valeur) && $valeur !== 'tous') {
            $sql .= " AND $nature LIKE :valeur";
            $params['valeur'] = '%' . $valeur . '%';
        }
                // Filtrer pour les valeurs commençant par une lettre
                if (!empty($lettre) && ctype_alpha($lettre)) {
                    $sql .= " AND $nature LIKE :lettre";
                    $params['lettre'] = $lettre . '%';
                }
    }
    elseif ($besoin === "details_hospitalisations_et_sorties") {
        $sql = "SELECT p.pat_id, p.pat_fname, p.pat_lname, h.date_entree, h.date_sortie 
                                                FROM hospitaliser h 
                                                JOIN patient p ON h.pat_id = p.pat_id 
                                                WHERE h.date_sortie IS NOT NULL";

        // Ajout du filtre dynamique uniquement si une valeur spécifique est demandée
        if ($nature !== 'all' && !empty($valeur) && $valeur !== 'tous') {
            $sql .= " AND $nature LIKE :valeur";
            $params['valeur'] = '%' . $valeur . '%';
        }
                // Filtrer pour les valeurs commençant par une lettre
                if (!empty($lettre) && ctype_alpha($lettre)) {
                    $sql .= " AND $nature LIKE :lettre";
                    $params['lettre'] = $lettre . '%';
                }
    } 
    elseif ($besoin === "analyses_medicales_par_medecin") {
        $sql = "SELECT d.doc_fname, d.doc_lname, a.id_analyse_med, a.desc_analyse_med, a.date_analyse_med 
                                            FROM analyse_medicale a 
                                            JOIN doctor d ON a.doc_id = d.doc_id 
                                            ORDER BY d.doc_lname, a.date_analyse_med";

        // Ajout du filtre dynamique uniquement si une valeur spécifique est demandée
        if ($nature !== 'all' && !empty($valeur) && $valeur !== 'tous') {
            $sql .= " AND $nature LIKE :valeur";
            $params['valeur'] = '%' . $valeur . '%';
        }
                // Filtrer pour les valeurs commençant par une lettre
                if (!empty($lettre) && ctype_alpha($lettre)) {
                    $sql .= " AND $nature LIKE :lettre";
                    $params['lettre'] = $lettre . '%';
                }
    } elseif ($besoin === "consultations_par_medecin") {
        $sql = "SELECT c.id_consul, c.date_consul, c.desc_consul, p.pat_fname, p.pat_lname 
                                        FROM consultation c 
                                        JOIN patient p ON c.pat_id = p.pat_id";

        // Ajout du filtre dynamique uniquement si une valeur spécifique est demandée
        if ($nature !== 'all' && !empty($valeur) && $valeur !== 'tous') {
            $sql .= " AND $nature LIKE :valeur";
            $params['valeur'] = '%' . $valeur . '%';
        }
                // Filtrer pour les valeurs commençant par une lettre
                if (!empty($lettre) && ctype_alpha($lettre)) {
                    $sql .= " AND $nature LIKE :lettre";
                    $params['lettre'] = $lettre . '%';
                }
    } elseif ($besoin === "consultations_par_medecin_et_specialite") {
        $sql = "SELECT d.speciality, COUNT(c.id_consul) AS total_consultations 
                                                    FROM doctor d 
                                                    JOIN consultation c ON d.doc_id = c.doc_id 
                                                    GROUP BY d.speciality";

        // Ajout du filtre dynamique uniquement si une valeur spécifique est demandée
        if ($nature !== 'all' && !empty($valeur) && $valeur !== 'tous') {
            $sql .= " AND $nature LIKE :valeur";
            $params['valeur'] = '%' . $valeur . '%';
        }        // Filtrer pour les valeurs commençant par une lettre
        if (!empty($lettre) && ctype_alpha($lettre)) {
            $sql .= " AND $nature LIKE :lettre";
            $params['lettre'] = $lettre . '%';
        }
    } 
    else {
        $sql = null;
    }

    // Exécution de la requête si elle est définie
    if ($sql) {
        try {
            $stmt = $pdo->prepare($sql);

            // Liaison des paramètres si un filtre est appliqué
            if ($nature !== 'all' && !empty($valeur) && $valeur !== 'tous') {
                $stmt->bindValue(':valeur', "$valeur%");
            }

            $stmt->execute();
            $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Erreur lors de l'exécution de la requête : " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votre Besoin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script>
        // Script pour afficher les filtres dynamiques
        function afficherFiltres() {
            const besoin = document.getElementById("besoin").value;
            const filtres = document.getElementById("filtres");

            if (besoin === "patients_hospitalises" || besoin === "rendez_vous_avenir" || besoin === "medecins_consultations" || besoin === "consultations_details" || besoin === "factures_impayees" || besoin === "nbr_cons_par_spec_doc" || besoin === "analyses_medicales_details" || besoin === "patients_avec_ordonnances_et_medecins" || besoin === "details_hospitalisations" || besoin === "analyses_medicales_mois" || besoin === "medecins_avec_consultations_mois" || besoin === "patients_hos_avec_factures" || besoin === "medecins_sans_consultations_mois" || besoin === "patients_par_chambre" || besoin === "rendez_vous_par_medecin_specialite" || besoin === "patients_par_sexe_et_age" || besoin === "ordonnances_avec_medicaments" || besoin === "ord_med_age_tranche" || besoin === "details_hospitalisations_et_sorties" || besoin === "analyses_medicales_par_medecin" || besoin === "consultations_par_medecin" || besoin === "consultations_par_medecin_et_specialite" ) {
                filtres.style.display = "block";
            } else {
                filtres.style.display = "none";
            }
        }
    </script>
    <style>  
        body {  
            font-family: Arial, sans-serif;  
            background: url('img/fonde.jpg') no-repeat center center fixed; /* Ajustez le chemin de l'image ici */  
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
    <a href="votre_besoin.php"><i class="fas fa-user-md"></i> Besoins spécifiques</a>
</div>

</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center"><i class="fas fa-vials"></i> Besoins spécifiques</h1>
        <form method="POST" action="">
            <div class="row mb-4">
                <!-- Sélection du besoin -->
                <div class="col-md-4">
                    <label for="besoin" class="form-label">Sélectionnez le besoin à afficher</label>
                    <select name="besoin" id="besoin" class="form-select" required onchange="afficherFiltres()">
                        <option value="">-- Choisir --</option>
                        <option value="patients_hospitalises" <?= $besoin === "patients_hospitalises" ? 'selected' : '' ?>>Patients hospitalisés</option>
                        <option value="rendez_vous_avenir" <?= $besoin === "rendez_vous_avenir" ? 'selected' : '' ?>>Rendez-vous à venir</option>
                        <option value="medecins_consultations" <?= $besoin === "medecins_consultations" ? 'selected' : '' ?>>Consultations des médecins</option>
                        <option value="consultations_details" <?= $besoin === "consultations_details" ? 'selected' : '' ?>>Détails des consultations</option>
                        <option value="factures_impayees" <?= $besoin === "factures_impayees" ? 'selected' : '' ?>>Factures impayées</option>
                        <option value="nbr_cons_par_spec_doc" <?= $besoin === "nbr_cons_par_spec_doc" ? 'selected' : '' ?>>Nombre de consultations par doctor</option>
                        <option value="analyses_medicales_details" <?= $besoin === "analyses_medicales_details" ? 'selected' : '' ?>>Détails des analyses médicales</option>
                        <option value="patients_avec_ordonnances_et_medecins" <?= $besoin === "patients_avec_ordonnances_et_medecins" ? 'selected' : '' ?>>Patients avec ordonnances et médecins</option>
                        <option value="details_hospitalisations" <?= $besoin === "details_hospitalisations" ? 'selected' : '' ?>>Détails des hospitalisations</option>
                        <option value="analyses_medicales_mois" <?= $besoin === "analyses_medicales_mois" ? 'selected' : '' ?>>Analyses médicales par mois</option>
                        <option value="medecins_avec_consultations_mois" <?= $besoin === "medecins_avec_consultations_mois" ? 'selected' : '' ?>>Médecins avec consultations ce mois</option>
                        <option value="patients_hos_avec_factures" <?= $besoin === "patients_hos_avec_factures" ? 'selected' : '' ?>>Patients hospitalisés avec factures</option>
                        <option value="medecins_sans_consultations_mois" <?= $besoin === "medecins_sans_consultations_mois" ? 'selected' : '' ?>>Médecins sans consultations ce mois</option>
                        <option value="patients_par_chambre" <?= $besoin === "patients_par_chambre" ? 'selected' : '' ?>>Patients par chambre</option>
                        <option value="rendez_vous_par_medecin_specialite" <?= $besoin === "rendez_vous_par_medecin_specialite" ? 'selected' : '' ?>>Rendez-vous par médecin et patient</option>
                        <option value="patients_par_sexe_et_age" <?= $besoin === "patients_par_sexe_et_age" ? 'selected' : '' ?>>Patients par sexe et âge</option>
                        <option value="ordonnances_avec_medicaments" <?= $besoin === "ordonnances_avec_medicaments" ? 'selected' : '' ?>>Ordonnances avec médicaments</option>
                        <option value="ord_med_age_tranche" <?= $besoin === "ord_med_age_tranche" ? 'selected' : '' ?>>Ordonnances et âges des patients</option>
                        <option value="details_hospitalisations_et_sorties" <?= $besoin === "details_hospitalisations_et_sorties" ? 'selected' : '' ?>>Hospitalisations et sorties</option>
                        <option value="analyses_medicales_par_medecin" <?= $besoin === "analyses_medicales_par_medecin" ? 'selected' : '' ?>>Analyses médicales par médecin</option>
                        <option value="consultations_par_medecin" <?= $besoin === "consultations_par_medecin" ? 'selected' : '' ?>>Consultations par médecin</option>
                        <option value="consultations_par_medecin_et_specialite" <?= $besoin === "consultations_par_medecin_et_specialite" ? 'selected' : '' ?>>Consultations par médecin et spécialité</option>
                    </select>
                </div>


            <!-- Filtres dynamiques -->
            <div id="filtres" style="display: <?= !empty($besoin) ? 'block' : 'none' ?>;">
                <div class="row mb-4">
                    <!-- Nature de la valeur -->
                    <div class="col-md-6">
                        <label for="nature" class="form-label">Nature de la valeur à rechercher</label>
                        <select name="nature" id="nature" class="form-select">
                            <option value="all" <?= $nature === "all" ? 'selected' : '' ?>>Tous</option>
                            <option value="p.pat_id" <?= $nature === "p.pat_id" ? 'selected' : '' ?>>ID Patient</option>
                            <option value="p.pat_fname" <?= $nature === "p.pat_fname" ? 'selected' : '' ?>>Prénom Patient</option>
                            <option value="p.pat_lname" <?= $nature === "p.pat_lname" ? 'selected' : '' ?>>Nom Patient</option>
                            <option value="p.pat_age" <?= $nature === "p.pat_age" ? 'selected' : '' ?>>Âge Patient</option>
                            <option value="p.sexe" <?= $nature === "p.sexe" ? 'selected' : '' ?>>Sexe Patient</option>
                            <option value="c.num_salle" <?= $nature === "c.num_salle" ? 'selected' : '' ?>>Numéro Salle</option>
                            <option value="c.nbr_lit" <?= $nature === "c.nbr_lit" ? 'selected' : '' ?>>Nombre de Lits</option>
                            <option value="h.date_entree" <?= $nature === "h.date_entree" ? 'selected' : '' ?>>Date d'Entrée</option>
                            <option value="h.date_sortie" <?= $nature === "h.date_sortie" ? 'selected' : '' ?>>Date de Sortie</option>
                            <option value="f.montant" <?= $nature === "f.montant" ? 'selected' : '' ?>>Montant Facture</option>
                            <option value="r.date_RD" <?= $nature === "r.date_RD" ? 'selected' : '' ?>>Date Rendez-vous</option>
                            <option value="r.horaire" <?= $nature === "r.horaire" ? 'selected' : '' ?>>Horaire Rendez-vous</option>
                            <option value="c.date_consul" <?= $nature === "c.date_consul" ? 'selected' : '' ?>>Date Consultation</option>
                            <option value="c.desc_consul" <?= $nature === "c.desc_consul" ? 'selected' : '' ?>>Description Consultation</option>
                            <option value="d.doc_id" <?= $nature === "d.doc_id" ? 'selected' : '' ?>>ID Docteur</option>
                            <option value="d.doc_fname" <?= $nature === "d.doc_fname" ? 'selected' : '' ?>>Prénom Docteur</option>
                            <option value="d.doc_lname" <?= $nature === "d.doc_lname" ? 'selected' : '' ?>>Nom Docteur</option>
                            <option value="d.speciality" <?= $nature === "d.speciality" ? 'selected' : '' ?>>Spécialité Docteur</option>
                            <option value="t.tech_labo_fname" <?= $nature === "t.tech_labo_fname" ? 'selected' : '' ?>>Prénom Technicien</option>
                            <option value="t.tech_labo_lname" <?= $nature === "t.tech_labo_lname" ? 'selected' : '' ?>>Nom Technicien</option>
                            <option value="a.id_analyse_med" <?= $nature === "a.id_analyse_med" ? 'selected' : '' ?>>ID Analyse Médicale</option>
                            <option value="a.date_analyse_med" <?= $nature === "a.date_analyse_med" ? 'selected' : '' ?>>Date Analyse Médicale</option>
                            <option value="a.desc_analyse_med" <?= $nature === "a.desc_analyse_med" ? 'selected' : '' ?>>Description Analyse Médicale</option>
                            <option value="o.ord_id" <?= $nature === "o.ord_id" ? 'selected' : '' ?>>ID Ordonnance</option>
                            <option value="o.ord_descr" <?= $nature === "o.ord_descr" ? 'selected' : '' ?>>Description Ordonnance</option>
                        </select>
                    </div>
                    <!-- Valeur à rechercher -->
                    <div class="col-md-6">
                        <label for="valeur" class="form-label">Valeur à rechercher</label>
                        <input type="text" name="valeur" id="valeur" class="form-control" value="<?= htmlspecialchars($valeur) ?>" placeholder="Ex : tous, Amal, etc.">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary w-100">Rechercher</button>
                </div>
            </div>
        </form>

        <!-- Affichage des résultats -->
        <?php if (!empty($resultats)) : ?>
            <table class="table table-bordered mt-4">
                <thead>
                    <tr>
                        <?php foreach (array_keys($resultats[0]) as $colonne) : ?>
                            <th><?= htmlspecialchars($colonne) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resultats as $row) : ?>
                        <tr>
                            <?php foreach ($row as $value) : ?>
                                <td><?= htmlspecialchars($value ?? '') ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST') : ?>
            <div class="alert alert-warning text-center mt-4">Aucun résultat trouvé pour votre recherche.</div>
        <?php endif; ?>
    </div>
</body>
</html>