<?php
// Définir une constante pour indiquer que l'application est en cours d'exécution
define('APP_RUNNING', true);

// Définir le type de contenu de la réponse en JSON
header('Content-Type: application/json');

// Démarrer la session PHP (non nécessaire pour ce script mais bonne pratique générale)
session_start();

// Inclure le fichier de configuration de la base de données
// Ajustez le chemin si database.php est ailleurs (ex: dirname(__DIR__, 3) . '/config/database.php')
$config = require_once '../config/database.php'; 

// Fonction utilitaire pour envoyer une réponse JSON et terminer le script
function sendJsonResponse($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message] + $data);
    exit();
}

// Récupérer les données POST (du JSON envoyé par Fetch API)
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Vérifier si les données nécessaires sont présentes
if (json_last_error() !== JSON_ERROR_NONE || !isset($data['token']) || !isset($data['new_password'])) {
    sendJsonResponse(false, 'Données manquantes ou invalides.');
}

$token = trim($data['token']);
$newPassword = $data['new_password']; // Mot de passe en clair avant hachage

// Validation côté serveur du mot de passe
if (empty($newPassword) || strlen($newPassword) < 8) {
    sendJsonResponse(false, 'Le nouveau mot de passe doit contenir au moins 8 caractères.');
}

// Connexion à la base de données
try {
    $dsn = "mysql:host={$config['host']};dbname={$config['db_name']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur de connexion BDD (handle-reset): " . $e->getMessage());
    sendJsonResponse(false, 'Impossible de se connecter à la base de données. Veuillez réessayer plus tard.');
}

// Début de la transaction
$pdo->beginTransaction();

try {
    // 1. Vérifier la validité et l'expiration du token
    $stmt = $pdo->prepare("SELECT email FROM password_resets WHERE token = :token AND expires_at > NOW()");
    $stmt->execute(['token' => $token]);
    $resetRequest = $stmt->fetch();

    if (!$resetRequest) {
        $pdo->rollBack(); // Annule la transaction
        sendJsonResponse(false, 'Le lien de réinitialisation est invalide ou a expiré.');
    }

    $userEmail = $resetRequest['email'];

    // 2. Hacher le nouveau mot de passe
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    if ($hashedPassword === false) {
        $pdo->rollBack();
        error_log("Erreur de hachage de mot de passe pour l'email: " . $userEmail);
        sendJsonResponse(false, 'Une erreur interne est survenue lors du traitement du mot de passe.');
    }

    // 3. Mettre à jour le mot de passe de l'utilisateur dans la table 'utilisateurs'
    $updateStmt = $pdo->prepare("UPDATE utilisateurs SET password = :password WHERE email = :email");
    $updateStmt->execute(['password' => $hashedPassword, 'email' => $userEmail]);

    // 4. Supprimer le token utilisé de la table 'password_resets'
    $deleteStmt = $pdo->prepare("DELETE FROM password_resets WHERE email = :email");
    $deleteStmt->execute(['email' => $userEmail]);

    $pdo->commit(); // Valide toutes les opérations de la transaction

    sendJsonResponse(true, 'Votre mot de passe a été réinitialisé avec succès !');

} catch (PDOException $e) {
    $pdo->rollBack(); // En cas d'erreur, annule la transaction
    error_log("Erreur BDD (handle-reset): " . $e->getMessage());
    sendJsonResponse(false, 'Une erreur est survenue lors de la réinitialisation du mot de passe: ' . $e->getMessage()); // Affichage complet pour le débogage
} catch (Exception $e) {
    // Capture d'autres types d'exceptions si nécessaire
    $pdo->rollBack();
    error_log("Erreur inattendue (handle-reset): " . $e->getMessage());
    sendJsonResponse(false, 'Une erreur inattendue est survenue: ' . $e->getMessage());
}
?>