<?php
// Définir une constante pour indiquer que l'application est en cours d'exécution
define('APP_RUNNING', true);

// Définir le type de contenu de la réponse en JSON
header('Content-Type: application/json');

// Démarrer la session PHP pour gérer les données de l'utilisateur
session_start();

// Inclure le fichier de configuration de la base de données
$config = require_once '../config/database.php';

// Fonction pour envoyer une réponse JSON
function sendJsonResponse($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message] + $data);
    exit();
}

// Récupérer les données POST (du JSON envoyé par Fetch API)
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Vérifier si les données nécessaires sont présentes
if (json_last_error() !== JSON_ERROR_NONE || !isset($data['email']) || !isset($data['password'])) {
    sendJsonResponse(false, 'Données manquantes ou invalides.');
}

$email = $data['email'];
$password = $data['password'];

// Connexion à la base de données
try {
    $dsn = "mysql:host={$config['host']};dbname={$config['db_name']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // En production, ne pas afficher les détails de l'erreur
    error_log("Erreur de connexion BDD: " . $e->getMessage()); // Log l'erreur
    sendJsonResponse(false, 'Impossible de se connecter à la base de données. Veuillez réessayer plus tard.');
}

// Vérifier les identifiants de l'utilisateur
try {
    $stmt = $pdo->prepare("SELECT id, email, password FROM utilisateurs WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Authentification réussie
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        // Vous pouvez stocker d'autres informations de session ici si besoin

        // Générer un ID de session PHP et le stocker dans un cookie
        // session_id() retourne l'ID de session actuel. Il est créé/récupéré par session_start().
        // Il est automatiquement envoyé dans le cookie PHPSESSID par défaut.
        // Si vous voulez un cookie nommé "auth_session_id" avec l'ID de session PHP:
        setcookie('auth_session_id', session_id(), [
            'expires' => time() + (86400 * 30), // Valide 30 jours (86400 secondes par jour)
            'path' => '/', // Accessible sur tout le site
            'domain' => $_SERVER['HTTP_HOST'], // Domaine de votre site
            'secure' => true, // Envoyer le cookie uniquement via HTTPS
            'httponly' => true, // Empêcher l'accès via JavaScript
            'samesite' => 'Lax' // Protection CSRF (Strict ou Lax)
        ]);

        // Optionnel: Mettre à jour l'ID de session dans la BDD si vous voulez le suivre
        $updateStmt = $pdo->prepare("UPDATE utilisateurs SET session_id = :session_id WHERE id = :id");
        $updateStmt->execute(['session_id' => session_id(), 'id' => $user['id']]);

        sendJsonResponse(true, 'Connexion réussie !', ['redirect_url' => '/dashboard']);

    } else {
        // Authentification échouée
        sendJsonResponse(false, 'Email ou mot de passe incorrect.');
    }

} catch (PDOException $e) {
    error_log("Erreur de requête BDD: " . $e->getMessage()); // Log l'erreur
    sendJsonResponse(false, 'Une erreur est survenue lors de la vérification des identifiants.');
}
?>