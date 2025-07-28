<?php
define('APP_RUNNING', true);
header('Content-Type: application/json');
session_start();

$config = require_once __DIR__ . '/../config/database.php';
$email_config = require_once __DIR__ . '/../config/email.php'; // Pour les informations d'envoi d'email
require_once __DIR__ . '/../../../vendor/autoload.php'; // Autoloader Mailjet

use \Mailjet\Resources;
use \Mailjet\Client;

function sendJsonResponse($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message] + $data);
    exit();
}

// Vérifier l'authentification et l'autorisation (Admin ou Superviseur)
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    sendJsonResponse(false, 'Non authentifié. Veuillez vous reconnecter.');
}

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['db_name']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT role FROM utilisateurs WHERE id = :id");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $loggedInUserRole = $stmt->fetchColumn();

    if ($loggedInUserRole !== 'Superviseur' && $loggedInUserRole !== 'Admin') {
        sendJsonResponse(false, 'Accès non autorisé pour valider des comptes.');
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE || !isset($data['user_id'])) {
        sendJsonResponse(false, 'ID utilisateur manquant ou invalide.');
    }
    $userId = $data['user_id'];

    $pdo->beginTransaction();

    // 1. Récupérer les infos complètes de l'utilisateur en attente
    $stmt = $pdo->prepare("SELECT id, email, password, nom, prenom, matricule, service FROM en_attente WHERE id = :id");
    $stmt->execute(['id' => $userId]);
    $user_to_validate = $stmt->fetch();

    if (!$user_to_validate) {
        $pdo->rollBack();
        sendJsonResponse(false, 'Compte en attente non trouvé.');
    }

    // 2. Insérer l'utilisateur dans la table 'utilisateurs'
    $insertStmt = $pdo->prepare("INSERT INTO utilisateurs (id, email, password, nom, prenom, matricule, service, statut, role) VALUES (:id, :email, :password, :nom, :prenom, :matricule, :service, 'Actif', 'Agent')"); // Rôle par défaut 'Agent', statut 'Actif'
    $insertStmt->execute([
        'id' => $user_to_validate['id'],
        'email' => $user_to_validate['email'],
        'password' => $user_to_validate['password'],
        'nom' => $user_to_validate['nom'],
        'prenom' => $user_to_validate['prenom'],
        'matricule' => $user_to_validate['matricule'],
        'service' => $user_to_validate['service']
    ]);

    // 3. Supprimer l'utilisateur de la table 'en_attente'
    $deleteStmt = $pdo->prepare("DELETE FROM en_attente WHERE id = :id");
    $deleteStmt->execute(['id' => $userId]);

    $pdo->commit(); // Valide la transaction

    // --- Envoi de l'email de validation via Mailjet ---
    $mailjetApiKey = $email_config['mailjet']['api_key'];
    $mailjetSecretKey = $email_config['mailjet']['secret_key'];
    $senderEmail = $email_config['mailjet']['sender_email'];
    $senderName = $email_config['mailjet']['sender_name'];

    $mj = new Client($mailjetApiKey, $mailjetSecretKey, true, ['version' => 'v3.1']);

    $recipientEmail = $user_to_validate['email'];
    $recipientName = $user_to_validate['nom'] . ' ' . $user_to_validate['prenom'];

    $subject = "Votre compte Intranet FDO a été validé !";
    $htmlContent = '
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Compte validé</title>
        <style>
            body { font-family: Arial, sans-serif; background-color: #1a1a2e; color: #e0e0e0; margin: 0; padding: 0; width: 100% !important; }
            .container { max-width: 600px; margin: 20px auto; background-color: #2a2a4a; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4); border: 1px solid #4a4a7a; }
            h3 { color: #4CAF50; text-align: center; }
            p { margin-bottom: 1em; line-height: 1.6; }
            .button { display: inline-block; padding: 12px 25px; margin: 20px auto; background-color: #007bff; color: #ffffff !important; text-decoration: none; border-radius: 5px; font-weight: bold; text-align: center; }
            .button:hover { background-color: #0056b3; }
            .footer { text-align: center; margin-top: 30px; font-size: 0.9em; color: #6c757d; border-top: 1px solid #4a4a7a; padding-top: 20px; }
        </style>
    </head>
    <body>
        <div class="container">
            <h3>Bonjour ' . htmlspecialchars($recipientName) . ',</h3>
            <p>Nous avons le plaisir de vous informer que votre demande d\'accès à l\'<strong>Intranet FDO</strong> a été <strong>validée avec succès</strong> !</p>
            <p>Votre compte est maintenant actif. Vous pouvez vous connecter en utilisant votre adresse email et le mot de passe que vous avez défini lors de votre inscription.</p>
            <p style="text-align: center;">
                <a href="https://' . $_SERVER['HTTP_HOST'] . '/se-connecter" class="button">Accéder à l\'Intranet</a>
            </p>
            <p>Si vous avez des questions, n\'hésitez pas à contacter l\'administration.</p>
            <div class="footer">
                <p>Ceci est un email automatique, merci de ne pas y répondre.</p>
                <p>&copy; 2025 Intranet FDO. Tous droits réservés.</p>
            </div>
        </div>
    </body>
    </html>';

    $textPart = "Bonjour " . $recipientName . ",\n\nVotre compte Intranet FDO a été validé avec succès !\nVous pouvez vous connecter ici : https://" . $_SERVER['HTTP_HOST'] . "/se-connecter\n\nCordialement,\nL'administration de l'Intranet FDO";

    $body = [
        'Messages' => [
            [
                'From' => ['Email' => $senderEmail, 'Name' => $senderName],
                'To' => [['Email' => $recipientEmail, 'Name' => $recipientName]],
                'Subject' => $subject,
                'TextPart' => $textPart,
                'HTMLPart' => $htmlContent
            ]
        ]
    ];

    $response = $mj->post(Resources::$Email, ['body' => $body]);
    if ($response->success()) {
        sendJsonResponse(true, 'Compte validé et email envoyé.');
    } else {
        error_log("Échec envoi email validation: " . json_encode($response->getData()));
        sendJsonResponse(true, 'Compte validé, mais échec de l\'envoi de l\'email de notification.'); // Valider le compte quand même
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erreur BDD (validate-user-api): " . $e->getMessage());
    sendJsonResponse(false, 'Erreur BDD: ' . $e->getMessage());
}