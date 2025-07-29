<?php
// views/annuaire.php - Vue pour la page de l'annuaire des membres

// Les variables $pdo et $user sont disponibles ici depuis index.php

// Le chargement initial des utilisateurs sera géré par JavaScript pour permettre la recherche.
// Mais vous pouvez pré-charger si vous voulez afficher toute la liste dès le début.
// Pour l'instant, le JS va faire la première requête.

?>
<section class="annuaire-section">
    <h3>Annuaire des Membres</h3>
    <p class="section-description">Recherchez et consultez les informations des membres de l'intranet.</p>

    <input type="hidden" id="loggedInUserRole" value="<?php echo htmlspecialchars($user['role']); ?>">

    <div class="search-bar">
        <div class="input-group search-input">
            <i class="fas fa-search icon"></i>
            <input type="text" id="searchNameMatricule" placeholder="Rechercher par nom, prénom ou matricule...">
        </div>
        <div class="input-group search-select">
            <i class="fas fa-building icon"></i>
            <select id="searchService">
                <option value="">Tous les services</option>
                </select>
        </div>
        <button id="searchButton" class="search-button">Rechercher</button>
    </div>

    <div id="annuaireMessage" class="annuaire-message"></div>

    <div class="members-list-container">
        <table>
            <thead>
                <tr>
                    <th>Matricule</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Service</th>
                    <th>Rôle</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="membersListBody">
                <tr><td colspan="6" style="text-align: center; padding: 20px;">Chargement des membres...</td></tr>
            </tbody>
        </table>
    </div>
</section>

<div id="memberDetailModal" class="modal-overlay">
    <div class="modal-content">
        <button class="close-button" id="closeMemberDetailModalButton">&times;</button>
        <h3>Profil Détaillé du Membre</h3>
        <div class="modal-body-content">
            <div style="text-align:center; padding:50px;"><i class="fas fa-spinner fa-spin fa-3x" style="color:var(--primary-color);"></i><p style="color:var(--secondary-color);">Chargement des détails...</p></div>
        </div>
    </div>
</div>