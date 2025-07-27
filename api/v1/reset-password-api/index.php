<?php
// Définir une constante pour indiquer que l'application est en cours d'exécution
define('APP_RUNNING', true);

// Inclure l'autoloader de Composer. C'est crucial pour charger la librairie Mailjet.
require_once __DIR__ . '/../../../vendor/autoload.php';

// Utilisation du client Mailjet
use \Mailjet\Resources;
use \Mailjet\Client;

// Définir le type de contenu de la réponse en JSON
header('Content-Type: application/json');

// Démarrer la session PHP (si nécessaire pour d'autres usages futurs, sinon peut être omis ici)
session_start();

// Inclure les fichiers de configuration
$db_config = require_once '../config/database.php';
$email_config = require_once '../config/email.php'; // Inclure le fichier de config email

// Fonction utilitaire pour envoyer une réponse JSON et terminer le script
function sendJsonResponse($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message] + $data);
    exit();
}

// Fonction pour générer un token sécurisé
function generateResetToken() {
    return bin2hex(random_bytes(32)); // Génère une chaîne hexadécimale de 64 caractères
}

// Récupérer les données POST (du JSON envoyé par Fetch API)
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Vérifier si les données nécessaires sont présentes
if (json_last_error() !== JSON_ERROR_NONE || !isset($data['email']) || !isset($data['matricule'])) {
    sendJsonResponse(false, 'Données manquantes ou invalides.');
}

$email = trim($data['email']);
$matricule = trim($data['matricule']);

// Validation côté serveur
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendJsonResponse(false, 'Format d\'email invalide.');
}
if (empty($matricule)) {
    sendJsonResponse(false, 'Le matricule est requis.');
}

// Connexion à la base de données
try {
    $dsn = "mysql:host={$db_config['host']};dbname={$db_config['db_name']};charset={$db_config['charset']}";
    $pdo = new PDO($dsn, $db_config['username'], $db_config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur de connexion BDD (reset-password): " . $e->getMessage());
    sendJsonResponse(false, 'Impossible de se connecter à la base de données. Veuillez réessayer plus tard.');
}

// Vérifier si l'utilisateur existe avec l'email et le matricule fournis
try {
    $stmt = $pdo->prepare("SELECT id, nom, prenom FROM utilisateurs WHERE email = :email AND matricule = :matricule");
    $stmt->execute(['email' => $email, 'matricule' => $matricule]);
    $user = $stmt->fetch();

    if (!$user) {
        sendJsonResponse(false, 'Aucun compte correspondant n\'a été trouvé. Veuillez vérifier vos informations.');
    }

    // Générer un token de réinitialisation
    $token = generateResetToken();
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token valide 1 heure

    // Supprimer tout ancien token pour cet email
    $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = :email");
    $stmt->execute(['email' => $email]);

    // Enregistrer le nouveau token
    $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (:email, :token, :expires_at)");
    $stmt->execute([
        'email' => $email,
        'token' => $token,
        'expires_at' => $expiresAt
    ]);

    // --- Configuration et Envoi de l'email avec Mailjet ---
    $mailjetApiKey = $email_config['mailjet']['api_key'];
    $mailjetSecretKey = $email_config['mailjet']['secret_key'];
    $senderEmail = $email_config['mailjet']['sender_email'];
    $senderName = $email_config['mailjet']['sender_name'];

    $recipientEmail = $email;
    $recipientName = $user['nom'] . ' ' . $user['prenom'];

    $resetLink = "https://" . $_SERVER['HTTP_HOST'] . "/reset-password?token=" . $token;

    // --- Nouveau contenu HTML pour l'email ---
    $htmlContent = '
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Réinitialisation de mot de passe</title>
        <style>
            /* CSS inline pour une meilleure compatibilité client mail */
            body {
                font-family: Arial, sans-serif;
                background-color: #1a1a2e; /* dark-bg */
                color: #e0e0e0; /* text-color */
                margin: 0;
                padding: 0;
                -webkit-text-size-adjust: 100%;
                -ms-text-size-adjust: 100%;
                width: 100% !important;
            }
            .container {
                max-width: 600px;
                margin: 20px auto;
                background-color: #2a2a4a; /* card-bg */
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
                border: 1px solid #4a4a7a; /* input-border */
            }
            h1, h2, h3 {
                color: #007bff; /* primary-color */
                text-align: center;
            }
            p {
                margin-bottom: 1em;
                line-height: 1.6;
            }
            .button {
                display: inline-block;
                padding: 12px 25px;
                margin: 20px auto;
                background-color: #007bff; /* button-bg */
                color: #ffffff !important; /* Force blanc pour le texte du bouton */
                text-decoration: none;
                border-radius: 5px;
                font-weight: bold;
                text-align: center;
                -webkit-transition: background-color 0.3s ease;
                -moz-transition: background-color 0.3s ease;
                -ms-transition: background-color 0.3s ease;
                -o-transition: background-color 0.3s ease;
                transition: background-color 0.3s ease;
            }
            .button:hover {
                background-color: #0056b3; /* button-hover-bg */
            }
            .footer {
                text-align: center;
                margin-top: 30px;
                font-size: 0.9em;
                color: #6c757d; /* secondary-color */
                border-top: 1px solid #4a4a7a;
                padding-top: 20px;
            }
            .warning {
                color: #ff4d4f; /* error-color */
                font-weight: bold;
            }
            .link-text {
                color: #8ab4f8; /* link-color */
                word-break: break-all; /* Pour les liens très longs */
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h3>Bonjour ' . htmlspecialchars($recipientName) . ',</h3>
            <p>Vous avez demandé la réinitialisation de votre mot de passe pour l\'<strong>Intranet FDO</strong>.</p>
            <p>Pour définir un nouveau mot de passe, veuillez cliquer sur le bouton ci-dessous :</p>
            <p style="text-align: center;">
                <a href="' . htmlspecialchars($resetLink) . '" class="button">Réinitialiser mon mot de passe</a>
            </p>
            <p>Ce lien est valide pendant <strong>1 heure</strong>. Pour des raisons de sécurité, nous vous recommandons de l\'utiliser rapidement.</p>
            <p class="warning">Si vous n\'avez pas demandé cette réinitialisation, veuillez ignorer cet email.</p>
            <p>Si le bouton ne fonctionne pas, copiez et collez le lien suivant dans votre navigateur :</p>
            <p class="link-text">' . htmlspecialchars($resetLink) . '</p>
            <div class="footer">
                <p>Ceci est un email automatique, merci de ne pas y répondre.</p>
                <p>&copy; 2025 Intranet FDO. Tous droits réservés.</p>
            </div>
        </div>
    </body>
    </html>';

    $body = [
        'Messages' => [
            [
                'From' => [
                    'Email' => $senderEmail,
                    'Name' => $senderName
                ],
                'To' => [
                    [
                        'Email' => $recipientEmail,
                        'Name' => $recipientName
                    ]
                ],
                'Subject' => "Réinitialisation de votre mot de passe Intranet FDO",
                'TextPart' => "Bonjour,\n\nVous avez demandé la réinitialisation de votre mot de passe pour l'Intranet FDO.\nCliquez sur le lien suivant pour réinitialiser votre mot de passe : \n" . $resetLink . "\n\nCe lien est valide pendant 1 heure.\nSi vous n'avez pas demandé cette réinitialisation, veuillez ignorer cet email.\n\nCordialement,\nL'administration de l'Intranet FDO",
                'HTMLPart' => $htmlContent // Utilisation de la nouvelle variable
            ]
        ]
    ];

    $response = $mj->post(Resources::$Email, ['body' => $body]);
    $mailSent = $response->success();

    if ($mailSent) {
        sendJsonResponse(true, 'Un email de réinitialisation de mot de passe a été envoyé à votre adresse. Veuillez vérifier votre boîte de réception.');
    } else {
        $mailErrorData = $response->getData();
        error_log("Échec de l'envoi d'email de réinitialisation pour " . $email . ". Mailjet API Error: " . json_encode($mailErrorData));
        sendJsonResponse(false, 'La demande a été traitée, mais l\'envoi de l\'email a échoué. Veuillez contacter l\'administrateur.');
    }

} catch (PDOException $e) {
    error_log("Erreur BDD (reset-password insertion token): " . $e->getMessage());
    sendJsonResponse(false, 'Une erreur est survenue lors du traitement de votre demande. Veuillez réessayer.');
}
?>