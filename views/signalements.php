<?php
// views/signalements.php - Vue pour la page des signalements
// Les variables $pdo et $user sont disponibles ici depuis index.php

// Le rôle de l'utilisateur est déjà vérifié dans index.php (Superviseur ou Admin)
// Il est passé à JS via un champ caché
?>
<section class="signalements-section">
    <h3>Gestion des Signalements</h3>
    <p class="section-description">Consultez et traitez les signalements faits par les membres de l'intranet.</p>

    <input type="hidden" id="loggedInUserRole" value="<?php echo htmlspecialchars($user['role']); ?>">
    <input type="hidden" id="loggedInUserId" value="<?php echo htmlspecialchars($user['id']); ?>">

    <div class="filters-bar">
        <div class="input-group filter-select">
            <i class="fas fa-filter icon"></i>
            <select id="statusFilter">
                <option value="">Tous les statuts</option>
                <option value="en_attente">En attente</option>
                <option value="traite">Traité</option>
                <option value="rejete">Rejeté</option>
            </select>
        </div>
        <div class="input-group filter-input">
            <i class="fas fa-user icon"></i>
            <input type="text" id="reporterFilter" placeholder="Filtrer par signaleur (email/matricule)">
        </div>
        <div class="input-group filter-input">
            <i class="fas fa-user-slash icon"></i>
            <input type="text" id="reportedFilter" placeholder="Filtrer par signalé (email/matricule)">
        </div>
        <button id="applyFiltersButton" class="apply-filters-button">Appliquer Filtres</button>
    </div>

    <div id="signalementsMessage" class="signalements-message"></div>

    <div class="signalements-list-container">
        <table>
            <thead>
                <tr>
                    <th>ID Signalement</th>
                    <th>Signaleur</th>
                    <th>Signalé</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Statut</th>
                    <th>Date Signalement</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="signalementsListBody">
                <tr><td colspan="8" style="text-align: center; padding: 20px;">Chargement des signalements...</td></tr>
            </tbody>
        </table>
    </div>
</section>