<?php
// views/attentes.php - Vue pour la page des comptes en attente de validation

// La variable $pdo est disponible ici depuis index.php pour la connexion BDD
// Les variables $user est disponible ici depuis index.php

$pending_users = [];
try {
    $stmt = $pdo->query("SELECT id, email, nom, prenom, matricule, service, date_demande FROM en_attente ORDER BY date_demande DESC");
    $pending_users = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur de récupération des comptes en attente (attentes.php): " . $e->getMessage());
    echo '<div class="message error">Impossible de charger la liste des comptes en attente.</div>';
}
?>

<section class="pending-accounts-list">
    <h3>Comptes en Attente de Validation</h3>
    <p class="section-description">Liste des utilisateurs ayant soumis une demande d'accès.</p>

    <?php if (empty($pending_users)): ?>
        <div class="no-pending-users">
            <i class="fas fa-check-circle"></i>
            <p>Aucun compte en attente de validation pour le moment.</p>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Matricule</th>
                    <th>Service</th>
                    <th>Date Demande</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pending_users as $user_pending): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user_pending['email']); ?></td>
                        <td><?php echo htmlspecialchars($user_pending['nom']); ?></td>
                        <td><?php echo htmlspecialchars($user_pending['prenom']); ?></td>
                        <td><?php echo htmlspecialchars($user_pending['matricule']); ?></td>
                        <td><?php echo htmlspecialchars($user_pending['service']); ?></td>
                        <td><?php echo htmlspecialchars($user_pending['date_demande']); ?></td>
                        <td>
                            <button class="view-details-button" data-user-id="<?php echo htmlspecialchars($user_pending['id']); ?>">Voir Détails</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<div id="accountDetailModal" class="modal-overlay">
    <div class="modal-content">
        <button class="close-button" id="closeDetailModalButton">&times;</button>
        <h3>Détails du Compte en Attente</h3>
        <div class="modal-body-content">
            <div class="detail-grid">
                <div class="detail-item"><strong>ID:</strong> <span id="detailId"></span></div>
                <div class="detail-item"><strong>Email:</strong> <span id="detailEmail"></span></div>
                <div class="detail-item"><strong>Nom:</strong> <span id="detailNom"></span></div>
                <div class="detail-item"><strong>Prénom:</strong> <span id="detailPrenom"></span></div>
                <div class="detail-item"><strong>Matricule:</strong> <span id="detailMatricule"></span></div>
                <div class="detail-item"><strong>Service:</strong> <span id="detailService"></span></div>
                <div class="detail-item"><strong>Date Demande:</strong> <span id="detailDateDemande"></span></div>
                </div>
            
            <div class="modal-actions">
                <button class="action-button validate-button" data-action="validate">Valider ce compte</button>
                <button class="action-button reject-button" data-action="reject">Refuser ce compte</button>
            </div>
            <div id="modalActionMessage" class="modal-message"></div>
        </div>
    </div>
</div>