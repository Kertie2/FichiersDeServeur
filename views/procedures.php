<?php
// views/procedures.php - Vue pour la page de gestion des procédures (fichiers personnes)

// Les variables $pdo et $user sont disponibles ici depuis index.php

// Rôle et ID de l'utilisateur connecté pour le JS (utilisé pour les permissions côté client)
$loggedInUserRole = $user['role'];
$loggedInUserId = $user['id'];
?>
<section class="procedures-section">
    <h3>Gestion des Procédures & Fichiers</h3>
    <p class="section-description">Recherchez des personnes dans les fichiers, consultez leur historique et gérez les infractions.</p>

    <input type="hidden" id="loggedInUserRole" value="<?php echo htmlspecialchars($loggedInUserRole); ?>">
    <input type="hidden" id="loggedInUserId" value="<?php echo htmlspecialchars($loggedInUserId); ?>">

    <div class="search-bar">
        <div class="input-group search-input">
            <i class="fas fa-user icon"></i>
            <input type="text" id="personSearchTerm" placeholder="Rechercher par nom, prénom ou email...">
        </div>
        <button id="searchPersonButton" class="search-button"><i class="fas fa-search"></i> Rechercher Personne</button>
    </div>

    <div id="proceduresMessage" class="procedures-message"></div>

    <div class="persons-list-container">
        <table>
            <thead>
                <tr>
                    <th>Nom Complet</th>
                    <th>Email</th>
                    <th>Date de Naissance</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="personsListBody">
                <tr><td colspan="4" style="text-align: center; padding: 20px;">Utilisez la barre de recherche ci-dessus pour trouver une personne.</td></tr>
            </tbody>
        </table>
        <div class="add-person-section" style="display: none;">
            <p>La personne recherchée n'est pas dans les fichiers ?</p>
            <button id="addPersonButton" class="action-button primary-button"><i class="fas fa-plus-circle"></i> Ajouter une Nouvelle Personne</button>
        </div>
    </div>
</section>

<div id="personDetailModal" class="modal-overlay">
    <div class="modal-content">
        <button class="close-button" id="closePersonDetailModalButton">&times;</button>
        <h3 id="personDetailModalTitle">Fiche de Personne : <span id="personDetailName"></span></h3>
        <div class="modal-body-content">
            <div id="personDetailContentLoader" style="text-align:center; padding:50px;">
                <i class="fas fa-spinner fa-spin fa-3x" style="color:var(--primary-color);"></i>
                <p style="color:var(--secondary-color);">Chargement des informations...</p>
            </div>
            <div id="personDetailActualContent" style="display:none;">
                <section class="detail-subsection">
                    <h4>Informations Personnelles</h4>
                    <div class="detail-grid">
                        <div class="detail-item"><strong>Nom:</strong> <span id="detailNom"></span></div>
                        <div class="detail-item"><strong>Prénom:</strong> <span id="detailPrenom"></span></div>
                        <div class="detail-item"><strong>Email:</strong> <span id="detailEmail"></span></div>
                        <div class="detail-item"><strong>Date de Naissance:</strong> <span id="detailDateNaissance"></span></div>
                        <div class="detail-item"><strong>Lieu de Naissance:</strong> <span id="detailLieuNaissance"></span></div>
                        <div class="detail-item"><strong>Adresse:</strong> <span id="detailAdresse"></span></div>
                        <div class="detail-item"><strong>Téléphone:</strong> <span id="detailTelephone"></span></div>
                        <div class="detail-item"><strong>Nationalité:</strong> <span id="detailNationalite"></span></div>
                        <div class="detail-item"><strong>Genre:</strong> <span id="detailGenre"></span></div>
                    </div>
                </section>

                <section class="detail-subsection" id="physicalInfoSection" style="display:none;">
                    <h4>Informations Physiques</h4>
                    <div class="detail-grid">
                        <div class="detail-item"><strong>Taille:</strong> <span id="detailTaille"></span> cm</div>
                        <div class="detail-item"><strong>Poids:</strong> <span id="detailPoids"></span> kg</div>
                        <div class="detail-item"><strong>Cheveux:</strong> <span id="detailCheveux"></span></div>
                        <div class="detail-item"><strong>Yeux:</strong> <span id="detailYeux"></span></div>
                        <div class="detail-item detail-full-width"><strong>Corpulence:</strong> <span id="detailCorpulence"></span></div>
                        <div class="detail-item detail-full-width"><strong>Signes Distinctifs:</strong> <span id="detailSignesDistinctifs"></span></div>
                    </div>
                </section>

                <section class="detail-subsection" id="licenseInfoSection" style="display:none;">
                    <h4>Permis de Conduire</h4>
                    <div class="detail-grid">
                        <div class="detail-item"><strong>N° Permis:</strong> <span id="detailNumPermis"></span></div>
                        <div class="detail-item"><strong>Catégories:</strong> <span id="detailCategoriePermis"></span></div>
                        <div class="detail-item"><strong>Points:</strong> <span id="detailPointsPermis"></span></div>
                        <div class="detail-item"><strong>Statut Permis:</strong> <span id="detailStatutPermis"></span></div>
                    </div>
                </section>

                <section class="detail-subsection" id="fprStatusSection" style="display:none;">
                    <h4>Statut FPR (<span id="detailFPRStatus"></span>)</h4>
                    <div class="detail-grid">
                        <div class="detail-item detail-full-width"><strong>Raison Recherche:</strong> <span id="detailFPRReason"></span></div>
                        <div class="detail-item"><strong>Recherché par:</strong> <span id="detailFPRWantedBy"></span></div>
                        <div class="detail-item"><strong>Date Recherche:</strong> <span id="detailFPRDateWanted"></span></div>
                    </div>
                </section>

                <section class="detail-subsection">
                    <h4>Historique des Infractions</h4>
                    <div id="infractionHistoryList">
                        <p style="text-align: center; color: var(--secondary-color);">Chargement de l'historique...</p>
                    </div>
                    <button id="addInfractionButton" class="action-button primary-button" style="display:none;"><i class="fas fa-plus-circle"></i> Ajouter une infraction</button>
                </section>
            </div>
            
            <div class="modal-actions">
                <button id="addFPRButton" class="action-button primary-button opj-action" style="display:none;"><i class="fas fa-plus-circle"></i> Ajouter Fiche FPR</button>
                <button id="deletePersonButton" class="action-button reject-button admin-action" style="display:none;"><i class="fas fa-trash-alt"></i> Supprimer Personne</button>
            </div>
            <div id="personDetailModalMessage" class="modal-message"></div>
        </div>
    </div>
</div>

<div id="addPersonModal" class="modal-overlay">
    <div class="modal-content">
        <button class="close-button" id="closeAddPersonModalButton">&times;</button>
        <h3>Ajouter une Nouvelle Personne aux Fichiers</h3>
        <div class="modal-body-content">
            <form id="addPersonForm">
                <div class="input-group">
                    <i class="fas fa-user icon"></i>
                    <input type="text" id="addPersonNom" name="nom" placeholder="Nom" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-user icon"></i>
                    <input type="text" id="addPersonPrenom" name="prenom" placeholder="Prénom" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-envelope icon"></i>
                    <input type="email" id="addPersonEmail" name="email" placeholder="Email (si connu)">
                </div>
                <div class="input-group">
                    <i class="fas fa-calendar-alt icon"></i>
                    <input type="date" id="addPersonDateNaissance" name="date_naissance" placeholder="Date de naissance">
                </div>
                <div class="input-group">
                    <i class="fas fa-map-marker-alt icon"></i>
                    <input type="text" id="addPersonLieuNaissance" name="lieu_naissance" placeholder="Lieu de naissance">
                </div>
                <div class="input-group">
                    <i class="fas fa-home icon"></i>
                    <input type="text" id="addPersonAdresse" name="adresse" placeholder="Adresse">
                </div>
                <div class="input-group">
                    <i class="fas fa-city icon"></i>
                    <input type="text" id="addPersonVille" name="ville" placeholder="Ville">
                </div>
                <div class="input-group">
                    <i class="fas fa-mail-bulk icon"></i>
                    <input type="text" id="addPersonCodePostal" name="code_postal" placeholder="Code Postal">
                </div>
                <div class="input-group">
                    <i class="fas fa-globe icon"></i>
                    <input type="text" id="addPersonNationalite" name="nationalite" placeholder="Nationalité" value="Française">
                </div>
                <div class="input-group">
                    <i class="fas fa-venus-mars icon"></i>
                    <select id="addPersonGenre" name="genre">
                        <option value="">Sélectionnez le genre</option>
                        <option value="Homme">Homme</option>
                        <option value="Femme">Femme</option>
                        <option value="Autre">Autre</option>
                    </select>
                </div>
                <div class="input-group">
                    <i class="fas fa-phone icon"></i>
                    <input type="text" id="addPersonTelephone" name="telephone" placeholder="Téléphone">
                </div>
                <div class="input-group">
                    <i class="fas fa-info-circle icon"></i>
                    <textarea id="addPersonInfosComplementaires" name="informations_complementaires" placeholder="Informations complémentaires"></textarea>
                </div>
                
                <button type="submit" id="savePersonButton" class="action-button primary-button"><i class="fas fa-save"></i> Ajouter la personne</button>
                <div id="addPersonModalMessage" class="modal-message"></div>
            </form>
        </div>
    </div>
</div>

<div id="infractionDetailModal" class="modal-overlay">
    <div class="modal-content">
        <button class="close-button" id="closeInfractionDetailModalButton">&times;</button>
        <h3>Détails de l'Infraction</h3>
        <div class="modal-body-content">
            <div id="infractionDetailContent">
                </div>
            <div class="modal-actions">
                <button id="deleteInfractionButton" class="action-button reject-button admin-action" style="display:none;"><i class="fas fa-trash-alt"></i> Supprimer Infraction</button>
            </div>
            <div id="infractionDetailModalMessage" class="modal-message"></div>
        </div>
    </div>
</div>

<div id="addInfractionModal" class="modal-overlay">
    <div class="modal-content">
        <button class="close-button" id="closeAddInfractionModalButton">&times;</button>
        <h3 id="addInfractionModalTitle">Ajouter une Infraction (<span id="addInfractionPersonName"></span>)</h3>
        <div class="modal-body-content">
            <form id="addInfractionForm">
                <input type="hidden" id="addInfractionPersonId" name="person_id">
                
                <div class="input-group">
                    <i class="fas fa-list-alt icon"></i>
                    <select id="infractionTypeSelect" name="infraction_type_id" required>
                        <option value="">Sélectionnez le type d'infraction</option>
                        </select>
                </div>
                
                <div id="dynamicInfractionFields">
                    <p style="text-align: center; color: var(--secondary-color);">Sélectionnez un type d'infraction pour afficher les champs.</p>
                </div>
                
                <button type="submit" id="saveInfractionButton" class="action-button primary-button"><i class="fas fa-save"></i> Enregistrer l'infraction</button>
                <div id="addInfractionModalMessage" class="modal-message"></div>
            </form>
        </div>
    </div>
</div>

<div id="manageFPRModal" class="modal-overlay">
    <div class="modal-content">
        <button class="close-button" id="closeFPRModalButton">&times;</button>
        <h3>Fiche FPR : <span id="fprPersonName"></span></h3>
        <div class="modal-body-content">
            <form id="fprForm">
                <input type="hidden" id="fprPersonId" name="person_id">
                <input type="hidden" id="fprRecordId" name="record_id">
                <div class="input-group">
                    <label class="checkbox-container">
                        <input type="checkbox" id="fprIsWanted" name="is_wanted"> Recherché (FPR Actif)
                        <span class="checkmark"></span>
                    </label>
                </div>
                <div class="input-group">
                    <i class="fas fa-info-circle icon"></i>
                    <textarea id="fprReason" name="reason" placeholder="Raison de la recherche"></textarea>
                </div>
                <button type="submit" id="saveFPRButton" class="action-button primary-button"><i class="fas fa-save"></i> Enregistrer Fiche FPR</button>
                <div id="fprModalMessage" class="modal-message"></div>
            </form>
        </div>
    </div>
</div>