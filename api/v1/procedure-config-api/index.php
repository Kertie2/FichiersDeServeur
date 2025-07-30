<?php
// API pour la gestion des types de procédures et leurs champs dynamiques
define('APP_RUNNING', true);
header('Content-Type: application/json');
session_start();

// Inclure le fichier de configuration de la base de données
$config = require_once __DIR__ . '/../config/database.php';

// Fonction utilitaire pour envoyer une réponse JSON et terminer le script
function sendJsonResponse($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message] + $data);
    exit();
}

// Fonction pour générer un UUID v4
function generateUuid() {
    $data = openssl_random_pseudo_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set variant to 10
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// --- Connexion à la base de données ---
try {
    $dsn = "mysql:host={$config['host']};dbname={$config['db_name']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur de connexion BDD (procedure-config-api): " . $e->getMessage());
    sendJsonResponse(false, 'Impossible de se connecter à la base de données.');
}

// --- Vérification de l'authentification et de l'autorisation (Admin uniquement) ---
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    sendJsonResponse(false, 'Non authentifié. Veuillez vous reconnecter.');
}

$loggedInUserId = $_SESSION['user_id'];
$loggedInUserRole = null;
try {
    $stmt = $pdo->prepare("SELECT role FROM utilisateurs WHERE id = :id");
    $stmt->execute(['id' => $loggedInUserId]);
    $loggedInUserRole = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Erreur BDD (procedure-config-api role check): " . $e->getMessage());
    sendJsonResponse(false, 'Erreur lors de la vérification de votre rôle.');
}

if ($loggedInUserRole !== 'Admin') {
    sendJsonResponse(false, 'Accès non autorisé. Seuls les administrateurs peuvent gérer les configurations de procédures.');
}

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
    // --- ACTIONS GET (LECTURE) ---
    case 'list_types':
        try {
            $stmt = $pdo->query("SELECT id, name, description, is_active FROM procedure_types ORDER BY name ASC");
            $types = $stmt->fetchAll();
            sendJsonResponse(true, 'Types de procédures récupérés.', ['procedure_types' => $types]);
        } catch (PDOException $e) {
            error_log("Erreur BDD (list_types): " . $e->getMessage());
            sendJsonResponse(false, 'Erreur lors de la récupération des types de procédures.');
        }
        break;

    case 'list_fields':
        $typeId = $_GET['type_id'] ?? null;
        if (!$typeId) {
            sendJsonResponse(false, 'ID du type de procédure manquant.');
        }
        try {
            $stmt = $pdo->prepare("SELECT id, field_name, field_label, field_type, is_required, options_json, order_num FROM procedure_fields WHERE procedure_type_id = :type_id ORDER BY order_num ASC, field_label ASC");
            $stmt->execute(['type_id' => $typeId]);
            $fields = $stmt->fetchAll();
            sendJsonResponse(true, 'Champs de procédure récupérés.', ['fields' => $fields]);
        } catch (PDOException $e) {
            error_log("Erreur BDD (list_fields): " . $e->getMessage());
            sendJsonResponse(false, 'Erreur lors de la récupération des champs de procédure.');
        }
        break;

    case 'get_field':
        $fieldId = $_GET['field_id'] ?? null;
        if (!$fieldId) {
            sendJsonResponse(false, 'ID du champ manquant.');
        }
        try {
            $stmt = $pdo->prepare("SELECT id, procedure_type_id, field_name, field_label, field_type, is_required, options_json, order_num FROM procedure_fields WHERE id = :field_id");
            $stmt->execute(['field_id' => $fieldId]);
            $field = $stmt->fetch();
            if ($field) {
                sendJsonResponse(true, 'Détails du champ récupérés.', ['field' => $field]);
            } else {
                sendJsonResponse(false, 'Champ non trouvé.');
            }
        } catch (PDOException $e) {
            error_log("Erreur BDD (get_field): " . $e->getMessage());
            sendJsonResponse(false, 'Erreur lors de la récupération des détails du champ.');
        }
        break;

    // --- ACTIONS POST (ÉCRITURE / MODIFICATION) ---
    case 'activate_type':
    case 'deactivate_type':
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        $typeId = $data['type_id'] ?? null;
        // Cast explicite en INT (0 ou 1)
        $isActive = ($action === 'activate_type') ? 1 : 0; 

        if (!$typeId) {
            sendJsonResponse(false, 'ID du type de procédure manquant.');
        }
        try {
            $stmt = $pdo->prepare("UPDATE procedure_types SET is_active = :is_active WHERE id = :type_id");
            $stmt->execute(['is_active' => $isActive, 'type_id' => $typeId]);
            $message = ($isActive ? 'Activé' : 'Désactivé') . ' avec succès.';
            sendJsonResponse(true, 'Type de procédure ' . $message);
        } catch (PDOException $e) {
            error_log("Erreur BDD (toggle_type_active): " . $e->getMessage());
            sendJsonResponse(false, 'Erreur lors de la modification du statut du type de procédure.');
        }
        break;

    case 'add_field':
    case 'update_field':
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        $fieldId = $data['field_id'] ?? null;
        $procedureTypeId = $data['procedure_type_id'] ?? null;
        $fieldName = trim($data['field_name'] ?? '');
        $fieldLabel = trim($data['field_label'] ?? '');
        $fieldType = $data['field_type'] ?? '';
        // Cast explicite en INT (0 ou 1)
        $isRequired = (isset($data['is_required']) && $data['is_required']) ? 1 : 0;
        $optionsJson = $data['options_json'] ?? null;
        $orderNum = (int)($data['order_num'] ?? 0);

        // Validation basique
        if (!$procedureTypeId || empty($fieldName) || empty($fieldLabel) || empty($fieldType)) {
            sendJsonResponse(false, 'Données de champ obligatoires manquantes.');
        }
        if (!in_array($fieldType, ['text', 'textarea', 'number', 'date', 'time', 'datetime-local', 'select', 'checkbox', 'radio'])) {
            sendJsonResponse(false, 'Type de champ invalide.');
        }
        if (($fieldType === 'select' || $fieldType === 'checkbox' || $fieldType === 'radio') && ($optionsJson === null || !json_decode($optionsJson))) {
            sendJsonResponse(false, 'Les options sont obligatoires pour ce type de champ (format JSON).');
        }

        try {
            $pdo->beginTransaction();

            if ($action === 'add_field') {
                $uuid = generateUuid();
                $stmt = $pdo->prepare("INSERT INTO procedure_fields (id, procedure_type_id, field_name, field_label, field_type, is_required, options_json, order_num) VALUES (:id, :procedure_type_id, :field_name, :field_label, :field_type, :is_required, :options_json, :order_num)");
                $stmt->execute([
                    'id' => $uuid,
                    'procedure_type_id' => $procedureTypeId,
                    'field_name' => $fieldName,
                    'field_label' => $fieldLabel,
                    'field_type' => $fieldType,
                    'is_required' => $isRequired,
                    'options_json' => $optionsJson,
                    'order_num' => $orderNum
                ]);
                $pdo->commit();
                sendJsonResponse(true, 'Champ ajouté avec succès !');
            } else { // update_field
                if (!$fieldId) {
                    $pdo->rollBack();
                    sendJsonResponse(false, 'ID du champ à modifier manquant.');
                }
                $stmt = $pdo->prepare("UPDATE procedure_fields SET field_name = :field_name, field_label = :field_label, field_type = :field_type, is_required = :is_required, options_json = :options_json, order_num = :order_num WHERE id = :id AND procedure_type_id = :procedure_type_id");
                $stmt->execute([
                    'field_name' => $fieldName,
                    'field_label' => $fieldLabel,
                    'field_type' => $fieldType,
                    'is_required' => $isRequired,
                    'options_json' => $optionsJson,
                    'order_num' => $orderNum,
                    'id' => $fieldId,
                    'procedure_type_id' => $procedureTypeId
                ]);
                $pdo->commit();
                sendJsonResponse(true, 'Champ mis à jour avec succès !');
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->getCode() == '23000' && strpos($e->getMessage(), 'Duplicate entry') !== false) {
                 error_log("Erreur BDD (field_name duplicate): " . $e->getMessage());
                 sendJsonResponse(false, 'Un champ avec ce nom technique existe déjà pour ce type de procédure.');
            }
            error_log("Erreur BDD (add/update field): " . $e->getMessage());
            sendJsonResponse(false, 'Erreur lors de l\'enregistrement du champ.');
        }
        break;

    case 'delete_field':
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        $fieldId = $data['field_id'] ?? null;

        if (!$fieldId) {
            sendJsonResponse(false, 'ID du champ à supprimer manquant.');
        }
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("DELETE FROM procedure_fields WHERE id = :id");
            $stmt->execute(['id' => $fieldId]);
            $pdo->commit();
            sendJsonResponse(true, 'Champ supprimé avec succès !');
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Erreur BDD (delete_field): " . $e->getMessage());
            sendJsonResponse(false, 'Erreur lors de la suppression du champ.');
        }
        break;

    default:
        sendJsonResponse(false, 'Action non reconnue.');
        break;
}
?>