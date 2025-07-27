<?php
// Définir une constante pour indiquer que l'application est en cours d'exécution
define('APP_RUNNING', true);

// Définir le type de contenu de la réponse en JSON
header('Content-Type: application/json');

// Inclure le fichier de configuration de la base de données
// Ajustez le chemin si database.php est ailleurs (ex: dirname(__DIR__, 3) . '/config/database.php')
$config = require_once '../config/database.php'; 

// Fonction utilitaire pour envoyer une réponse JSON et terminer le script
function sendJsonResponse($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message] + $data);
    exit();
}

// Fonction pour générer un UUID v4
function generateUuid() {
    // Générer 16 octets aléatoires
    $data = openssl_random_pseudo_bytes(16);

    // Mettre les bits de version et de variante
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set variant to 10

    // Formater en chaîne hexadécimale (UUID v4)
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}


// Récupérer les données POST (du JSON envoyé par Fetch API)
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Vérifier si les données nécessaires sont présentes
if (
    json_last_error() !== JSON_ERROR_NONE ||
    !isset($data['email']) ||
    !isset($data['password']) ||
    !isset($data['nom']) ||
    !isset($data['prenom']) ||
    !isset($data['matricule']) ||
    !isset($data['service'])
) {
    sendJsonResponse(false, 'Données manquantes ou invalides.');
}

$email = trim($data['email']);
$password_clair = $data['password']; // Mot de passe en clair avant hachage
$nom = trim($data['nom']);
$prenom = trim($data['prenom']);
$matricule = trim($data['matricule']);
$service = trim($data['service']);

// Validation côté serveur (longueur du mot de passe, format email basique)
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendJsonResponse(false, 'Format d\'email invalide.');
}

if (empty($password_clair) || strlen($password_clair) < 8) {
    sendJsonResponse(false, 'Le mot de passe doit contenir au moins 8 caractères.');
}

if (empty($nom) || empty($prenom) || empty($matricule) || empty($service)) {
    sendJsonResponse(false, 'Tous les champs sont requis.');
}


// Connexion à la base de données
try {
    $dsn = "mysql:host={$config['host']};dbname={$config['db_name']};charset={$config['charset']}";
    // Si vous utilisez un socket UNIX, décommentez la ligne suivante et commentez celle au-dessus
    // $dsn = "mysql:unix_socket={$config['host']};dbname={$config['db_name']};charset={$config['charset']}";
    
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // --- MODIFICATION ICI POUR LE DÉBOGAGE ---
    error_log("Erreur de connexion BDD: " . $e->getMessage()); 
    sendJsonResponse(false, 'Erreur de connexion BDD: ' . $e->getMessage()); // Affiche le message complet au client
    // --- FIN DE LA MODIFICATION ---
}

// Vérifier si l'email ou le matricule existe déjà dans 'utilisateurs' ou 'en_attente'
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE email = :email OR matricule = :matricule");
    $stmt->execute(['email' => $email, 'matricule' => $matricule]);
    if ($stmt->fetchColumn() > 0) {
        sendJsonResponse(false, 'Cet email ou matricule est déjà utilisé.');
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM en_attente WHERE email = :email OR matricule = :matricule");
    $stmt->execute(['email' => $email, 'matricule' => $matricule]);
    if ($stmt->fetchColumn() > 0) {
        sendJsonResponse(false, 'Une demande d\'inscription avec cet email ou matricule est déjà en cours.');
    }

} catch (PDOException $e) {
    // --- MODIFICATION ICI POUR LE DÉBOGAGE ---
    error_log("Erreur de vérification d'existence: " . $e->getMessage());
    sendJsonResponse(false, 'Erreur de vérification BDD: ' . $e->getMessage()); // Affiche le message complet au client
    // --- FIN DE LA MODIFICATION ---
}


// Hacher le mot de passe
$password_hache = password_hash($password_clair, PASSWORD_DEFAULT);
if ($password_hache === false) {
    error_log("Erreur de hachage de mot de passe pour l'email: " . $email);
    sendJsonResponse(false, 'Une erreur interne est survenue lors du traitement de votre mot de passe.');
}

// Générer un UUID pour le nouvel utilisateur en attente
$uuid = generateUuid();

// Insérer les données dans la table 'en_attente'
try {
    $stmt = $pdo->prepare("INSERT INTO en_attente (id, email, password, nom, prenom, matricule, service) VALUES (:id, :email, :password, :nom, :prenom, :matricule, :service)");
    $stmt->execute([
        'id' => $uuid,
        'email' => $email,
        'password' => $password_hache,
        'nom' => $nom,
        'prenom' => $prenom,
        'matricule' => $matricule,
        'service' => $service
    ]);

    sendJsonResponse(true, 'Votre demande d\'accès a été soumise et est en attente de validation.');

} catch (PDOException $e) {
    // --- MODIFICATION ICI POUR LE DÉBOGAGE ---
    error_log("Erreur d'insertion en attente: " . $e->getMessage());
    sendJsonResponse(false, 'Erreur d\'insertion BDD: ' . $e->getMessage()); // Affiche le message complet au client
    // --- FIN DE LA MODIFICATION ---
}
?>