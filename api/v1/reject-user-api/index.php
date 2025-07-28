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
        sendJsonResponse(false, 'Accès non autorisé pour refuser des comptes.');
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE || !isset($data['user_id'])) {
        sendJsonResponse(false, 'ID utilisateur manquant ou invalide.');
    }
    $userId = $data['user_id'];

    $pdo->beginTransaction();

    // 1. Récupérer l'email de l'utilisateur avant de le supprimer pour l'envoi de l'email
    $stmt = $pdo->prepare("SELECT email, nom, prenom FROM en_attente WHERE id = :id");
    $stmt->execute(['id' => $userId]);
    $user_to_reject = $stmt->fetch();

    if (!$user_to_reject) {
        $pdo->rollBack();
        sendJsonResponse(false, 'Compte en attente non trouvé.');
    }

    // 2. Supprimer l'utilisateur de la table 'en_attente'
    $deleteStmt = $pdo->prepare("DELETE FROM en_attente WHERE id = :id");
    $deleteStmt->execute(['id' => $userId]);

    $pdo->commit(); // Valide la transaction

    // --- Envoi de l'email de refus via Mailjet ---
    $mailjetApiKey = $email_config['mailjet']['api_key'];
    $mailjetSecretKey = $email_config['mailjet']['secret_key'];
    $senderEmail = $email_config['mailjet']['sender_email'];
    $senderName = $email_config['mailjet']['sender_name'];

    $mj = new Client($mailjetApiKey, $mailjetSecretKey, true, ['version' => 'v3.1']);

    $recipientEmail = $user_to_reject['email'];
    $recipientName = $user_to_reject['nom'] . ' ' . $user_to_reject['prenom'];

    $subject = "Mise à jour concernant votre demande d'accès Intranet FDO";
    $htmlContent = '
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Demande de compte refusée</title>
        <style>
            body { font-family: Arial, sans-serif; background-color: #1a1a2e; color: #e0e0e0; margin: 0; padding: 0; width: 100% !important; }
            .container { max-width: 600px; margin: 20px auto; background-color: #2a2a4a; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4); border: 1px solid #4a4a7a; }
            h3 { color: #f44336; text-align: center; } /* Rouge pour le refus */
            p { margin-bottom: 1em; line-height: 1.6; }
            .footer { text-align: center; margin-top: 30px; font-size: 0.9em; color: #6c757d; border-top: 1px solid #4a4a7a; padding-top: 20px; }
        </style>
    </head>
    <body>
        <div class="container">
            <h3>Bonjour ' . htmlspecialchars($recipientName) . ',</h3>
            <p>Nous vous informons que votre demande d\'accès à l\'<strong>Intranet FDO</strong> a été <strong>refusée</strong>.</p>
            <p>Malheureusement, votre profil ne correspond pas aux critères d\'accès à notre plateforme. Si vous pensez qu\'il s\'agit d\'une erreur ou si vous avez des questions, veuillez nous contacter.</p>
            <div class="footer">
                <p>Ceci est un email automatique, merci de ne pas y répondre.</p>
                <p>&copy; 2025 Intranet FDO. Tous droits réservés.</p>
            </div>
        </div>
    </body>
    </html>';

    $textPart = "Bonjour " . $recipientName . ",\n\nNous vous informons que votre demande d'accès à l'Intranet FDO a été refusée.\n\nCordialement,\nL'administration de l'Intranet FDO";

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
        sendJsonResponse(true, 'Compte refusé et email envoyé.');
    } else {
        error_log("Échec envoi email refus: " . json_encode($response->getData()));
        sendJsonResponse(true, 'Compte refusé, mais échec de l\'envoi de l\'email de notification.');
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erreur BDD (reject-user-api): " . $e->getMessage());
    sendJsonResponse(false, 'Erreur BDD: ' . $e->getMessage());
}