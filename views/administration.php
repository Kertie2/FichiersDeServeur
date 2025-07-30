<?php
// views/administration.php - Vue pour la page d'administration

// $pdo et $user sont disponibles depuis index.php
$loggedInUserRole = $user['role'];
?>

<section class="administration-section">
    <h3>Panneau d'Administration</h3>
    <p class="section-description">Gérez les utilisateurs, les paramètres du système et les configurations des procédures.</p>

    <div id="adminMessage" class="admin-message"></div>

    <section class="admin-subsection user-management">
        <h4>Gestion des Utilisateurs</h4>
        <p>Gérez les rôles, statuts et comptes des utilisateurs.</p>
        <div class="admin-actions-grid">
            <a href="/annuaire" class="admin-action-card">
                <i class="fas fa-users"></i>
                <span>Gérer Membres</span>
            </a>
            <a href="/attentes" class="admin-action-card">
                <i class="fas fa-user-plus"></i>
                <span>Valider Nouveaux Comptes</span>
            </a>
        </div>
    </section>

    <section class="admin-subsection procedure-management">
        <h4>Gestion des Procédures Spéciales</h4>
        <p>Activez/désactivez les types de procédures et configurez leurs champs.</p>

        <div id="procedureTypesList">
            <p style="text-align: center; color: var(--secondary-color);">Chargement des types de procédures...</p>
        </div>
    </section>

    <section class="admin-subsection system-settings">
        <h4>Options Système</h4>
        <p>Accédez aux options systèmes.</p>
        <div class="admin-actions-grid">
            <a href="#" class="admin-action-card disabled">
                <i class="fas fa-file-invoice"></i>
                <span>Historique des Logs</span>
            </a>
        </div>
    </section>
</section>

<div id="manageFieldsModal" class="modal-overlay">
    <div class="modal-content">
        <button class="close-button" id="closeManageFieldsModalButton">&times;</button>
        <h3 id="manageFieldsModalTitle">Gérer les champs de : <span id="currentProcedureTypeName"></span></h3>
        <div class="modal-body-content">
            <div id="fieldsListContainer">
                </div>
            <button id="addFieldButton" class="action-button primary-button"><i class="fas fa-plus-circle"></i> Ajouter un champ</button>
            <div id="fieldManageMessage" class="modal-message"></div>
        </div>
    </div>
</div>

<div id="fieldEditModal" class="modal-overlay">
    <div class="modal-content">
        <button class="close-button" id="closeFieldEditModalButton">&times;</button>
        <h3 id="fieldEditModalTitle">Ajouter/Modifier un Champ</h3>
        <div class="modal-body-content">
            <form id="fieldEditForm">
                <input type="hidden" id="editFieldProcedureTypeId" name="procedure_type_id">
                <input type="hidden" id="editFieldId" name="field_id">
                
                <div class="input-group">
                    <i class="fas fa-tag icon"></i>
                    <input type="text" id="editFieldName" name="field_name" placeholder="Nom technique du champ (ex: nom_suspect)" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-font icon"></i>
                    <input type="text" id="editFieldLabel" name="field_label" placeholder="Libellé affiché (ex: Nom du suspect)" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-list icon"></i>
                    <select id="editFieldType" name="field_type" required>
                        <option value="">Sélectionner le type de champ</option>
                        <option value="text">Texte court</option>
                        <option value="textarea">Texte long</option>
                        <option value="number">Numérique</option>
                        <option value="date">Date</option>
                        <option value="time">Heure</option>
                        <option value="datetime-local">Date et Heure</option>
                        <option value="select">Liste déroulante</option>
                        <option value="checkbox">Case à cocher</option>
                        <option value="radio">Bouton radio</option>
                    </select>
                </div>
                <div class="input-group">
                    <label class="checkbox-container">
                        <input type="checkbox" id="editFieldRequired" name="is_required"> Champ obligatoire
                        <span class="checkmark"></span>
                    </label>
                </div>
                <div class="input-group" id="optionsGroup" style="display: none;">
                    <i class="fas fa-list-alt icon"></i>
                    <textarea id="editFieldOptions" name="options_json" placeholder="Options (séparées par des virgules, ex: Option A,Option B)"></textarea>
                    <small class="field-hint">Pour les listes déroulantes/radio/checkbox.</small>
                </div>
                <div class="input-group">
                    <i class="fas fa-sort-numeric-down-alt icon"></i>
                    <input type="number" id="editFieldOrder" name="order_num" placeholder="Ordre d'affichage" value="0">
                </div>
                
                <button type="submit" id="saveFieldButton" class="action-button primary-button"><i class="fas fa-save"></i> Enregistrer le champ</button>
                <div id="fieldEditMessage" class="modal-message"></div>
            </form>
        </div>
    </div>
</div>