<?php
// Définir une constante pour indiquer que l'application est en cours d'exécution
define('APP_RUNNING', true);

// Définir le type de contenu de la réponse en JSON
header('Content-Type: application/json');

// Inclure le fichier de configuration de la base de données
$config = require_once __DIR__ . '/../config/database.php';

// Fonction utilitaire pour envoyer une réponse JSON et terminer le script
function sendJsonResponse($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message] + $data);
    exit();
}

// Connexion à la base de données
try {
    $dsn = "mysql:host={$config['host']};dbname={$config['db_name']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur de connexion BDD (services-list-api): " . $e->getMessage());
    sendJsonResponse(false, 'Impossible de se connecter à la base de données pour récupérer les services.');
}

try {
    // Récupérer la définition du type de la colonne 'service' de la table 'utilisateurs'
    // depuis INFORMATION_SCHEMA. Cela nous donnera la chaîne ENUM (ex: 'enum('Service1','Service2')')
    $stmt = $pdo->prepare("SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :db_name AND TABLE_NAME = 'utilisateurs' AND COLUMN_NAME = 'service'");
    $stmt->execute(['db_name' => $config['db_name']]);
    $row = $stmt->fetch();

    if (!$row) {
        sendJsonResponse(false, 'La colonne "service" n\'a pas pu être trouvée dans la table "utilisateurs".');
    }

    $enumString = $row['COLUMN_TYPE'];
    
    // Parser la chaîne ENUM pour extraire les valeurs
    // La chaîne est de la forme: enum('valeur1','valeur2','valeur3')
    // On utilise une expression régulière pour extraire les valeurs
    preg_match_all("/'([^']+)'/", $enumString, $matches);
    $services = $matches[1]; // Les valeurs des services sont dans $matches[1]

    sendJsonResponse(true, 'Liste des services récupérée avec succès.', ['services' => $services]);

} catch (PDOException $e) {
    error_log("Erreur BDD (services-list-api): " . $e->getMessage());
    sendJsonResponse(false, 'Une erreur est survenue lors de la récupération des services.');
}
?>