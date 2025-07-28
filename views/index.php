<?php
// views/dashboard.php - C'est la vue pour le tableau de bord

// Les variables $user est disponible ici car elle est définie dans index.php

?>
<section class="user-overview">
    <h3>Vos informations personnelles</h3>
    <div class="info-grid">
        <div class="info-item">
            <i class="fas fa-id-badge icon"></i>
            <span class="label">Matricule:</span>
            <span class="value"><?php echo htmlspecialchars($user['matricule']); ?></span>
        </div>
        <div class="info-item">
            <i class="fas fa-envelope icon"></i>
            <span class="label">Email:</span>
            <span class="value"><?php echo htmlspecialchars($user['email']); ?></span>
        </div>
        <div class="info-item">
            <i class="fas fa-briefcase icon"></i>
            <span class="label">Service:</span>
            <span class="value"><?php echo htmlspecialchars($user['service']); ?></span>
        </div>
        <div class="info-item">
            <i class="fas fa-shield-alt icon"></i>
            <span class="label">Rôle:</span>
            <span class="value"><?php echo htmlspecialchars($user['role']); ?></span>
        </div>
        <div class="info-item">
            <i class="fas fa-user-check icon"></i>
            <span class="label">Statut:</span>
            <span class="value"><?php echo htmlspecialchars($user['statut']); ?></span>
        </div>
    </div>
</section>

<section class="quick-access">
    <h3>Accès rapide</h3>
    <div class="access-grid">
        <a href="/procedures/nouvelle" class="access-card">
            <i class="fas fa-plus-circle"></i>
            <span>Nouvelle Procédure</span>
        </a>
        <a href="/recherche" class="access-card">
            <i class="fas fa-search"></i>
            <span>Rechercher Dossier</span>
        </a>
        <a href="/messages" class="access-card">
            <i class="fas fa-comments"></i>
            <span>Messages</span>
        </a>
        <a href="/parametres" class="access-card">
            <i class="fas fa-cog"></i>
            <span>Paramètres</span>
        </a>
    </div>
</section>