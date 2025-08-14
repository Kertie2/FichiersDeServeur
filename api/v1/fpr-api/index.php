<?php
define('APP_RUNNING', true);
// Le header est déplacé dans la fonction sendJsonResponse pour plus de flexibilité

define('DEV_MODE', true); // Mettre à false en production

// ------------------- BLOC DE GESTION DES ERREURS (CORRIGÉ) -------------------

// AJOUT : La fonction generateUuid() qui manquait probablement
function generateUuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// MODIFICATION : La version complète de la fonction de réponse JSON
function sendJsonResponse($success, $message, $data = [], $debug = null, $httpCode = 200) {
    if (!headers_sent()) {
        header('Content-Type: application/json');
        http_response_code($httpCode);
    }
    
    $response = ['success' => $success, 'message' => $message] + $data;
    
    // On ajoute la section 'debug' seulement si DEV_MODE est true ET si $debug n'est pas null
    if (defined('DEV_MODE') && DEV_MODE && $debug !== null) {
        $response['debug'] = $debug;
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
}

// Gestionnaire d'erreurs pour les exceptions non attrapées
set_exception_handler(function($exception) {
    $debug = [
        'error' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine()
    ];
    sendJsonResponse(false, 'Une erreur serveur inattendue est survenue (Exception).', [], $debug, 500);
});

// Gestionnaire pour les erreurs fatales
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        $debug = [
            'error' => $error['message'],
            'file' => $error['file'],
            'line' => $error['line']
        ];
        sendJsonResponse(false, 'Une erreur serveur fatale est survenue.', [], $debug, 500);
    }
});

// ------------------- FIN DU BLOC DE GESTION DES ERREURS -------------------


session_start();

$config = require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../../../vendor/autoload.php';


// --- Vérification de l'authentification et de l'autorisation (OPJ ou rôle au-dessus pour ajouter/modifier) ---
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    sendJsonResponse(false, 'Non authentifié. Veuillez vous reconnecter.');
}

$loggedInUserId = $_SESSION['user_id'];
$loggedInUserRole = null;
try {
    $dsn = "mysql:host={$config['host']};dbname={$config['db_name']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("SELECT role FROM utilisateurs WHERE id = :id");
    $stmt->execute(['id' => $loggedInUserId]);
    $loggedInUserRole = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Erreur BDD (fpr-api role check): " . $e->getMessage());
    sendJsonResponse(false, 'Erreur lors de la vérification de votre rôle.');
}

$isAuthorizedForWrite = ($loggedInUserRole === 'OPJ' || $loggedInUserRole === 'Superviseur' || $loggedInUserRole === 'Admin');


// --- Récupération de l'action ---
$action = $_REQUEST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (isset($data['action'])) {
        $action = $data['action'];
    }
}

switch ($action) {
    case 'get_fpr': // GET action pour récupérer une fiche FPR existante
        $personId = isset($_GET['person_id']) ? (int)$_GET['person_id'] : null;
        if (!$personId) {
            sendJsonResponse(false, 'ID de personne manquant.');
        }
        try {
            $stmt = $pdo->prepare("SELECT r.id, r.is_wanted, r.reason, u.matricule AS wanted_by_agent_matricule, r.date_wanted FROM fpr_records r LEFT JOIN utilisateurs u ON r.wanted_by_agent_id = u.id WHERE r.personne_id = :person_id");
            $stmt->execute(['person_id' => $personId]);
            $fprRecord = $stmt->fetch();
            if ($fprRecord) {
                sendJsonResponse(true, 'Fiche FPR récupérée.', ['fpr_record' => $fprRecord]);
            } else {
                sendJsonResponse(false, 'Fiche FPR non trouvée pour cette personne.');
            }
        } catch (PDOException $e) {
            error_log("Erreur BDD (get_fpr): " . $e->getMessage());
            sendJsonResponse(false, 'Erreur lors de la récupération de la fiche FPR.');
        }
        break;

    case 'add_fpr': // POST action pour ajouter une fiche FPR
    case 'update_fpr': // POST action pour modifier une fiche FPR
        if (!$isAuthorizedForWrite) {
            sendJsonResponse(false, 'Accès non autorisé. Seuls les OPJ et supérieurs peuvent gérer les fiches FPR.');
        }

        $personId = (int)($data['person_id'] ?? null);
        $recordId = $data['record_id'] ?? null; // Pour update
        $isWanted = (int)(bool)($data['is_wanted'] ?? false); // 0 ou 1
        $reason = trim($data['reason'] ?? '');
        $wantedByAgentId = $data['wanted_by_agent_id'] ?? null; // L'ID de l'agent connecté

        if (!$personId || ($isWanted && empty($reason)) || !$wantedByAgentId) {
            sendJsonResponse(false, 'Données manquantes ou invalides pour la fiche FPR.');
        }

        try {
            $pdo->beginTransaction();

            // Vérifier que la personne existe
            $stmt = $pdo->prepare("SELECT id FROM personnes_interessees WHERE id = :id");
            $stmt->execute(['id' => $personId]);
            if (!$stmt->fetch()) {
                $pdo->rollBack();
                sendJsonResponse(false, 'La personne n\'existe pas.');
            }

            // Vérifier que l'agent émetteur existe
            $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE id = :id");
            $stmt->execute(['id' => $wantedByAgentId]);
            if (!$stmt->fetch()) {
                $pdo->rollBack();
                sendJsonResponse(false, 'L\'agent émetteur n\'existe pas.');
            }

            if ($action === 'add_fpr') {
                // Vérifier si une fiche FPR existe déjà pour cette personne
                $stmt = $pdo->prepare("SELECT id FROM fpr_records WHERE personne_id = :person_id");
                $stmt->execute(['person_id' => $personId]);
                if ($stmt->fetch()) {
                    $pdo->rollBack();
                    sendJsonResponse(false, 'Une fiche FPR existe déjà pour cette personne. Utilisez la modification.');
                }
                
                $uuid = generateUuid();
                $stmt = $pdo->prepare("INSERT INTO fpr_records (id, personne_id, is_wanted, reason, wanted_by_agent_id, date_wanted) VALUES (:id, :personne_id, :is_wanted, :reason, :wanted_by_agent_id, NOW())");
                $stmt->execute([
                    'id' => $uuid,
                    'personne_id' => $personId,
                    'is_wanted' => $isWanted,
                    'reason' => $reason,
                    'wanted_by_agent_id' => $wantedByAgentId
                ]);
                sendJsonResponse(true, 'Fiche FPR créée avec succès !');

            } elseif ($action === 'update_fpr') {
                if (!$recordId) {
                    $pdo->rollBack();
                    sendJsonResponse(false, 'ID de la fiche FPR à modifier manquant.');
                }
                $stmt = $pdo->prepare("UPDATE fpr_records SET is_wanted = :is_wanted, reason = :reason, resolved_by_agent_id = :resolved_by_agent_id, date_resolved = :date_resolved WHERE id = :id AND personne_id = :personne_id");
                $stmt->execute([
                    'is_wanted' => $isWanted,
                    'reason' => $reason,
                    'resolved_by_agent_id' => ($isWanted == 0) ? $wantedByAgentId : null, // Si désactivé, on met qui l'a résolu
                    'date_resolved' => ($isWanted == 0) ? date('Y-m-d H:i:s') : null, // Si désactivé, on met la date de résolution
                    'id' => $recordId,
                    'personne_id' => $personId
                ]);
                sendJsonResponse(true, 'Fiche FPR mise à jour avec succès !');
            }
            $pdo->commit();

        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Erreur BDD (fpr-api add/update): " . $e->getMessage());
            sendJsonResponse(false, 'Erreur lors de l\'enregistrement de la fiche FPR.');
        }
        break;

    default:
        sendJsonResponse(false, 'Action non reconnue.');
        break;
}