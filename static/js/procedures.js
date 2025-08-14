document.addEventListener('DOMContentLoaded', () => {
    // Éléments de la page principale des procédures
    const personSearchTermInput = document.getElementById('personSearchTerm');
    const searchPersonButton = document.getElementById('searchPersonButton');
    const proceduresMessageDiv = document.getElementById('proceduresMessage');
    const personsListBody = document.getElementById('personsListBody');
    const addPersonSection = document.querySelector('.add-person-section');
    const addPersonButton = document.getElementById('addPersonButton');

    // Éléments de la modale de détail de la personne
    const personDetailModal = document.getElementById('personDetailModal');
    const closePersonDetailModalButton = document.getElementById('closePersonDetailModalButton');
    const personDetailNameSpan = document.getElementById('personDetailName');
    const personDetailContentLoader = document.getElementById('personDetailContentLoader');
    const personDetailActualContent = document.getElementById('personDetailActualContent');
    const personDetailModalMessageDiv = document.getElementById('personDetailModalMessage');

    // Éléments des sections de détail dans la modale
    const detailNom = document.getElementById('detailNom');
    const detailPrenom = document.getElementById('detailPrenom');
    const detailEmail = document.getElementById('detailEmail');
    const detailDateNaissance = document.getElementById('detailDateNaissance');
    const detailLieuNaissance = document.getElementById('detailLieuNaissance');
    const detailAdresse = document.getElementById('detailAdresse');
    const detailTelephone = document.getElementById('detailTelephone');
    const detailNationalite = document.getElementById('detailNationalite');
    const detailGenre = document.getElementById('detailGenre');
    const detailTaille = document.getElementById('detailTaille');
    const detailPoids = document.getElementById('detailPoids');
    const detailCheveux = document.getElementById('detailCheveux');
    const detailYeux = document.getElementById('detailYeux');
    const detailSignesDistinctifs = document.getElementById('detailSignesDistinctifs');
    const detailCorpulence = document.getElementById('detailCorpulence');
    const physicalInfoSection = document.getElementById('physicalInfoSection');

    const detailNumPermis = document.getElementById('detailNumPermis');
    const detailCategoriePermis = document.getElementById('detailCategoriePermis');
    const detailPointsPermis = document.getElementById('detailPointsPermis');
    const detailStatutPermis = document.getElementById('detailStatutPermis');
    const licenseInfoSection = document.getElementById('licenseInfoSection');

    const detailFPRStatus = document.getElementById('detailFPRStatus');
    const detailFPRReason = document.getElementById('detailFPRReason');
    const detailFPRWantedBy = document.getElementById('detailFPRWantedBy');
    const detailFPRDateWanted = document.getElementById('detailFPRDateWanted');
    const fprStatusSection = document.getElementById('fprStatusSection');

    const infractionHistoryList = document.getElementById('infractionHistoryList');
    const addInfractionButton = document.getElementById('addInfractionButton');
    const addFPRButton = document.getElementById('addFPRButton');
    const deletePersonButton = document.getElementById('deletePersonButton');


    // Éléments de la modale d'ajout de personne
    const addPersonModal = document.getElementById('addPersonModal');
    const closeAddPersonModalButton = document.getElementById('closeAddPersonModalButton');
    const addPersonForm = document.getElementById('addPersonForm');
    const addPersonModalMessageDiv = document.getElementById('addPersonModalMessage');

    // Éléments de la modale d'ajout d'infraction
    const addInfractionModal = document.getElementById('addInfractionModal');
    const closeAddInfractionModalButton = document.getElementById('closeAddInfractionModalButton');
    const addInfractionPersonNameSpan = document.getElementById('addInfractionPersonName');
    const addInfractionPersonIdInput = document.getElementById('addInfractionPersonId');
    const infractionTypeSelect = document.getElementById('infractionTypeSelect');
    const dynamicInfractionFieldsDiv = document.getElementById('dynamicInfractionFields');
    const saveInfractionButton = document.getElementById('saveInfractionButton');
    const addInfractionModalMessageDiv = document.getElementById('addInfractionModalMessage');
    
    // Éléments de la modale de détail d'infraction
    const infractionDetailModal = document.getElementById('infractionDetailModal');
    const closeInfractionDetailModalButton = document.getElementById('closeInfractionDetailModalButton');
    const infractionDetailContentDiv = document.getElementById('infractionDetailContent');
    const deleteInfractionButton = document.getElementById('deleteInfractionButton'); // Ceci est le bouton de suppression DANS la modale de détail d'infraction
    const infractionDetailModalMessageDiv = document.getElementById('infractionDetailModalMessage');

    // Éléments de la modale FPR
    const manageFPRModal = document.getElementById('manageFPRModal');
    const closeFPRModalButton = document.getElementById('closeFPRModalButton');
    const fprPersonNameSpan = document.getElementById('fprPersonName');
    const fprForm = document.getElementById('fprForm');
    const fprPersonIdInput = document.getElementById('fprPersonId');
    const fprRecordIdInput = document.getElementById('fprRecordId');
    const fprIsWantedCheckbox = document.getElementById('fprIsWanted');
    const fprReasonTextarea = document.getElementById('fprReason');
    const saveFPRButton = document.getElementById('saveFPRButton');
    const fprModalMessageDiv = document.getElementById('fprModalMessage');


    let currentPersonId = null; // ID de la personne actuellement affichée dans la modale de détail
    const loggedInUserRoleElement = document.getElementById('loggedInUserRole');
    const loggedInUserRole = loggedInUserRoleElement ? loggedInUserRoleElement.value : 'Agent';
    const loggedInUserIdElement = document.getElementById('loggedInUserId');
    const loggedInUserId = loggedInUserIdElement ? loggedInUserIdElement.value : null;

    // --- Fonctions de gestion des messages ---
    function showProceduresMessage(message, type = 'error') {
        proceduresMessageDiv.textContent = message;
        proceduresMessageDiv.className = 'procedures-message ' + type;
        proceduresMessageDiv.style.display = 'block';
        proceduresMessageDiv.style.opacity = '0';
        void proceduresMessageDiv.offsetWidth;
        proceduresMessageDiv.style.opacity = '1';
    }
    function hideProceduresMessage() {
        proceduresMessageDiv.style.display = 'none';
        proceduresMessageDiv.textContent = '';
        proceduresMessageDiv.className = 'procedures-message';
    }

    // Fonctions de message pour les modales (réutilisables)
    function showModalMessage(messageDiv, message, type = 'error') {
        messageDiv.textContent = message;
        messageDiv.className = 'modal-message ' + type;
        messageDiv.style.display = 'block';
        messageDiv.style.opacity = '0';
        void messageDiv.offsetWidth;
        messageDiv.style.opacity = '1';
    }
    function hideModalMessage(messageDiv) {
        messageDiv.style.display = 'none';
        messageDiv.textContent = '';
        messageDiv.className = 'modal-message';
    }

    // --- Fonction d'échappement HTML ---
    function htmlEscape(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // --- Fonctions de chargement des listes déroulantes (réutilisées) ---
    async function loadSelectOptions(selectElement, apiEndpoint, defaultOptionText, preselectedValue = '') {
        try {
            const response = await fetch(apiEndpoint);
            const data = await response.json();
            if (response.ok && data.success && data.services) { // 'services' est le nom générique de la clé
                selectElement.innerHTML = `<option value="">${defaultOptionText}</option>`;
                data.services.forEach(optionVal => {
                    const option = document.createElement('option');
                    option.value = optionVal;
                    option.textContent = optionVal;
                    if (optionVal === preselectedValue) {
                        option.selected = true;
                    }
                    selectElement.appendChild(option);
                });
            } else {
                console.error(`DEBUG JS: Erreur chargement options depuis ${apiEndpoint}:`, data.message);
                showProceduresMessage(`Impossible de charger les options pour un champ.`, 'error');
                selectElement.disabled = true;
            }
        } catch (error) {
            console.error(`DEBUG JS: Erreur réseau chargement options depuis ${apiEndpoint}:`, error);
            showProceduresMessage(`Erreur réseau pour les options.`, 'error');
            selectElement.disabled = true;
        }
    }


    // --- Fonction principale de recherche et affichage des personnes ---
    async function searchPersons(searchTerm = '') {
        console.log("DEBUG JS: searchPersons() appelé avec terme:", searchTerm);
        personsListBody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 20px;">Recherche en cours...</td></tr>';
        addPersonSection.style.display = 'none';
        hideProceduresMessage();

        let queryParams = new URLSearchParams();
        if (searchTerm) queryParams.append('search', searchTerm);

        try {
            const response = await fetch(`/api/v1/persons-list-api/index.php?${queryParams.toString()}`);
            const data = await response.json();
            console.log("DEBUG JS: Réponse persons-list-api reçue:", data);

            if (response.ok && data.success && data.persons) {
                personsListBody.innerHTML = '';
                if (data.persons.length === 0) {
                    personsListBody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 20px;">Aucune personne trouvée.</td></tr>';
                    addPersonSection.style.display = 'block';
                } else {
                    data.persons.forEach(person => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${htmlEscape(person.nom)} ${htmlEscape(person.prenom)}</td>
                            <td>${htmlEscape(person.email || 'N/A')}</td>
                            <td>${htmlEscape(person.date_naissance || 'N/A')}</td>
                            <td><button class="view-person-details-button" data-person-id="${htmlEscape(person.id)}">Plus de détails</button></td>
                        `;
                        personsListBody.appendChild(row);
                    });
                    personsListBody.querySelectorAll('.view-person-details-button').forEach(button => {
                        button.addEventListener('click', openPersonDetailModal);
                    });
                }
            } else {
                showProceduresMessage(data.message || 'Erreur lors du chargement des personnes.', 'error');
                personsListBody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 20px; color:var(--error-color);">Erreur lors du chargement des personnes.</td></tr>';
            }
        } catch (error) {
            console.error('DEBUG JS: Erreur réseau chargement personnes:', error);
            showProceduresMessage('Erreur réseau lors du chargement des personnes. Veuillez vérifier votre connexion.', 'error');
            personsListBody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 20px; color:var(--error-color);">Erreur réseau.</td></tr>';
        }
    }

    // --- Gestion de la Modale de Détail de la Personne ---
    async function openPersonDetailModal(event) {
        console.log("DEBUG JS: openPersonDetailModal() appelé.");
        currentPersonId = event.target.dataset.personId;
        if (!currentPersonId) {
            showProceduresMessage("ID de personne manquant.", 'error');
            return;
        }

        if (personDetailContentLoader) {
            personDetailContentLoader.style.display = 'block';
            personDetailActualContent.style.display = 'none';
        }
        if (personDetailModal) personDetailModal.classList.add('active');
        hideModalMessage(personDetailModalMessageDiv);

        try {
            const response = await fetch(`/api/v1/person-detail-api/index.php?id=${currentPersonId}`);
            const data = await response.json();
            console.log("DEBUG JS: Réponse person-detail-api reçue:", data);

            if (response.ok && data.success && data.person) {
                const person = data.person;
                // Mettre à jour le titre de la modale
                personDetailNameSpan.textContent = `${htmlEscape(person.nom)} ${htmlEscape(person.prenom)}`;

                // Remplir les informations personnelles
                detailNom.textContent = person.nom || 'N/A';
                detailPrenom.textContent = person.prenom || 'N/A';
                detailEmail.textContent = person.email || 'N/A';
                detailDateNaissance.textContent = person.date_naissance || 'N/A';
                detailLieuNaissance.textContent = person.lieu_naissance || 'N/A';
                detailAdresse.textContent = person.adresse || 'N/A';
                detailTelephone.textContent = person.telephone || 'N/A';
                detailNationalite.textContent = person.nationalite || 'N/A';
                detailGenre.textContent = person.genre || 'N/A';
                detailCorpulence.textContent = person.corpulence || 'N/A';

                // Remplir les informations physiques (afficher la section seulement si des données existent)
                if (person.taille_cm || person.poids_kg || person.couleur_cheveux || person.couleur_yeux || person.signes_distinctifs || person.corpulence) {
                    physicalInfoSection.style.display = 'block';
                    detailTaille.textContent = person.taille_cm || 'N/A';
                    detailPoids.textContent = person.poids_kg || 'N/A';
                    detailCheveux.textContent = person.couleur_cheveux || 'N/A';
                    detailYeux.textContent = person.couleur_yeux || 'N/A';
                    detailSignesDistinctifs.textContent = person.signes_distinctifs || 'N/A';
                } else {
                    physicalInfoSection.style.display = 'none';
                }


                // Remplir les informations de permis (afficher la section seulement si FNPC est actif)
                const fnpcIsActive = data.fnpc_is_active; // Cette information vient de l'API
                if (fnpcIsActive && (person.permis_numero || person.permis_points_restants || person.permis_statut)) {
                    licenseInfoSection.style.display = 'block';
                    detailNumPermis.textContent = person.permis_numero || 'N/A';
                    detailCategoriePermis.textContent = person.permis_categorie || 'N/A';
                    detailPointsPermis.textContent = person.permis_points_restants || 'N/A';
                    detailStatutPermis.textContent = person.permis_statut || 'N/A';
                    detailStatutPermis.className = (person.permis_statut === 'Suspendu' || person.permis_statut === 'Annulé') ? 'StatutPermisRed' : 'StatutPermisGreen';
                } else {
                    licenseInfoSection.style.display = 'none';
                }
                
                // Remplir le statut FPR (afficher la section seulement si FPR est actif)
                const fprIsActive = data.fpr_is_active; // Cette information vient de l'API
                if (fprIsActive && (person.fpr_is_wanted || person.fpr_reason)) {
                    fprStatusSection.style.display = 'block';
                    detailFPRStatus.textContent = person.fpr_is_wanted ? 'Recherché' : 'Non recherché';
                    detailFPRStatus.className = person.fpr_is_wanted ? 'recherché' : 'non_recherché';
                    detailFPRReason.textContent = person.fpr_reason || 'Aucune';
                    detailFPRWantedBy.textContent = person.fpr_wanted_by_agent_matricule || 'N/A';
                    detailFPRDateWanted.textContent = person.fpr_date_wanted || 'N/A';
                } else {
                    fprStatusSection.style.display = 'none';
                }


                // Charger l'historique des infractions
                await loadInfractionHistory(currentPersonId);

                // Afficher/Cacher les boutons d'action basés sur les rôles
                // Bouton "Ajouter Fiche FPR" (OPJ ou rôle au-dessus)
                if (loggedInUserRole === 'OPJ' || loggedInUserRole === 'Superviseur' || loggedInUserRole === 'Admin') {
                    addFPRButton.style.display = 'inline-flex';
                } else {
                    addFPRButton.style.display = 'none';
                }
                // Bouton "Supprimer Personne" (Superviseur / Admin)
                if (loggedInUserRole === 'Superviseur' || loggedInUserRole === 'Admin') {
                    deletePersonButton.style.display = 'inline-flex';
                } else {
                    deletePersonButton.style.display = 'none';
                }
                 // Bouton "Ajouter une infraction" (Agent ou rôle au-dessus) - Pas FPR
                 if (loggedInUserRole === 'Agent' || loggedInUserRole === 'OPJ' || loggedInUserRole === 'Superviseur' || loggedInUserRole === 'Admin') {
                    addInfractionButton.style.display = 'inline-flex';
                } else {
                    addInfractionButton.style.display = 'none';
                }


                // Basculer l'affichage du contenu
                if (personDetailContentLoader) personDetailContentLoader.style.display = 'none';
                personDetailActualContent.style.display = 'block';

            } else {
                showModalMessage(personDetailModalMessageDiv, data.message || 'Erreur lors du chargement des détails de la personne.', 'error');
                if (personDetailContentLoader) personDetailContentLoader.style.display = 'none';
                personDetailActualContent.innerHTML = `<p style="text-align:center; padding:50px; color:var(--error-color);">${htmlEscape(data.message || 'Détails non trouvés.')}</p>`;
                personDetailActualContent.style.display = 'block';
            }
        } catch (error) {
            console.error('DEBUG JS: Erreur réseau ou du serveur (détails personne):', error);
            showModalMessage(personDetailModalMessageDiv, 'Erreur réseau lors du chargement des détails de la personne.', 'error');
            if (personDetailContentLoader) personDetailContentLoader.style.display = 'none';
            personDetailActualContent.innerHTML = '<p style="text-align:center; padding:50px; color:var(--error-color);">Erreur réseau. Impossible de charger les détails.</p>';
            personDetailActualContent.style.display = 'block';
        }
    }

    // Fonction pour charger l'historique des infractions
    async function loadInfractionHistory(personId) {
        console.log("DEBUG JS: loadInfractionHistory() appelé.");
        infractionHistoryList.innerHTML = '<p style="text-align: center; color: var(--secondary-color);">Chargement de l\'historique des infractions...</p>';
        try {
            const response = await fetch(`/api/v1/infraction-history-api/index.php?person_id=${personId}`);
            const data = await response.json();
            console.log("DEBUG JS: Réponse infraction-history-api reçue:", data);

            if (response.ok && data.success && data.infractions) {
                if (data.infractions.length === 0) {
                    infractionHistoryList.innerHTML = '<p style="text-align: center; color: var(--secondary-color);">Aucune infraction enregistrée pour cette personne.</p>';
                } else {
                    infractionHistoryList.innerHTML = '';
                    data.infractions.forEach(infraction => {
                        const item = document.createElement('div');
                        item.className = 'infraction-item';
                        item.dataset.infractionId = infraction.id;
                        item.innerHTML = `
                            <div class="infraction-item-info">
                                <strong>${htmlEscape(infraction.type_infraction)}</strong>
                                <span>Date: ${htmlEscape(infraction.date_heure_infraction)} - Lieu: ${htmlEscape(infraction.lieu_infraction)} - Agent: ${htmlEscape(infraction.agent_matricule || infraction.agent_email)}</span>
                            </div>
                            <div class="infraction-item-actions">
                                <button class="action-button primary-button view-infraction-details-button" data-infraction-id="${htmlEscape(infraction.id)}"><i class="fas fa-eye"></i> Voir</button>
                                ${loggedInUserRole === 'Superviseur' || loggedInUserRole === 'Admin' ? 
                                    `<button class="action-button reject-button delete-infraction-button" data-infraction-id="${htmlEscape(infraction.id)}"><i class="fas fa-trash-alt"></i> Supprimer</button>` : ''}
                            </div>
                        `;
                        infractionHistoryList.appendChild(item);
                    });
                    infractionHistoryList.querySelectorAll('.view-infraction-details-button').forEach(button => {
                        button.addEventListener('click', openInfractionDetailModal);
                    });
                    infractionHistoryList.querySelectorAll('.delete-infraction-button').forEach(button => {
                        console.log("DEBUG JS: Attaching handleDeleteInfraction to button:", button);
                        button.addEventListener('click', handleDeleteInfraction);
                    });
                }
            } else {
                infractionHistoryList.innerHTML = `<p style="color:var(--error-color);">Erreur chargement historique: ${htmlEscape(data.message || 'Inconnu')}</p>`;
            }
        } catch (error) {
            console.error('DEBUG JS: Erreur réseau chargement historique:', error);
            infractionHistoryList.innerHTML = '<p style="color:var(--error-color);">Erreur réseau lors du chargement de l\'historique.</p>';
        }
    }

    // Fonction pour ouvrir la modale de détail d'une infraction
    async function openInfractionDetailModal(event) {
        const infractionId = event.currentTarget.dataset.infractionId;
        if (!infractionId) return;

        infractionDetailContentDiv.innerHTML = '<p style="text-align:center; padding:50px;"><i class="fas fa-spinner fa-spin fa-3x" style="color:var(--primary-color);"></i></p>';
        hideModalMessage(infractionDetailModalMessageDiv);
        infractionDetailModal.classList.add('active');

        // Initialiser l'ID du bouton de suppression si l'utilisateur est Admin/Superviseur
        if (loggedInUserRole === 'Superviseur' || loggedInUserRole === 'Admin') {
            deleteInfractionButton.style.display = 'inline-flex';
            deleteInfractionButton.dataset.infractionId = infractionId;
            deleteInfractionButton.removeEventListener('click', handleDeleteInfraction);
            deleteInfractionButton.addEventListener('click', handleDeleteInfraction);
        } else {
            deleteInfractionButton.style.display = 'none';
        }

        try {
            const response = await fetch(`/api/v1/infraction-detail-api/index.php?id=${infractionId}`);
            const data = await response.json();

            if (response.ok && data.success && data.infraction) {
                const infraction = data.infraction;
                let dynamicFieldsHtml = '';
                if (infraction.data_json) {
                    const dynamicData = JSON.parse(infraction.data_json);
                    for (const key in dynamicData) {
                        if (Object.hasOwnProperty.call(dynamicData, key)) {
                            dynamicFieldsHtml += `<div class="detail-item"><strong>${htmlEscape(key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()))}:</strong> <span>${htmlEscape(dynamicData[key])}</span></div>`;
                        }
                    }
                }

                infractionDetailContentDiv.innerHTML = `
                    <div class="detail-grid">
                        <div class="detail-item"><strong>N° PV:</strong> <span>${htmlEscape(infraction.numero_pv)}</span></div>
                        <div class="detail-item"><strong>Agent Rédacteur:</strong> <span>${htmlEscape(infraction.agent_matricule || infraction.agent_email)}</span></div>
                        <div class="detail-item"><strong>Date & Heure:</strong> <span>${htmlEscape(infraction.date_heure_infraction)}</span></div>
                        <div class="detail-item"><strong>Lieu:</strong> <span>${htmlEscape(infraction.lieu_infraction)}</span></div>
                        <div class="detail-item"><strong>Type Infraction:</strong> <span>${htmlEscape(infraction.type_infraction)}</span></div>
                        <div class="detail-item detail-full-width"><strong>Description:</strong> <span>${htmlEscape(infraction.description)}</span></div>
                        ${dynamicFieldsHtml ? `<div class="detail-item detail-full-width"><h4>Champs Dynamiques:</h4><div class="detail-grid">${dynamicFieldsHtml}</div></div>` : ''}
                        <div class="detail-item"><strong>Statut:</strong> <span>${htmlEscape(infraction.statut)}</span></div>
                        <div class="detail-item detail-full-width"><strong>Notes Internes:</strong> <span>${htmlEscape(infraction.notes_internes || 'Aucune')}</span></div>
                    </div>
                `;
            } else {
                showModalMessage(infractionDetailModalMessageDiv, data.message || 'Erreur chargement détails infraction.', 'error');
                infractionDetailContentDiv.innerHTML = `<pre style="color:var(--error-color);">Impossible de charger les détails.</pre>`;
            }
        } catch (error) {
            console.error('Erreur réseau chargement détails infraction:', error);
            showModalMessage(infractionDetailModalMessageDiv, 'Erreur réseau chargement détails infraction.', 'error');
            infractionDetailContentDiv.innerHTML = '<pre style="color:var(--error-color);">Erreur réseau.</pre>';
        }
    }

    // Fonction pour gérer la suppression d'une infraction
    async function handleDeleteInfraction(event) {
        console.log("DEBUG JS: handleDeleteInfraction() appelé.");
        const actionButton = event.currentTarget; // Obtenir une référence stable au bouton
        const infractionId = actionButton.dataset.infractionId; // Utiliser la référence stable
        console.log("DEBUG JS: Infraction ID for delete:", infractionId);

        if (!infractionId) {
            console.error("DEBUG JS: Infraction ID est manquant pour la suppression.");
            showModalMessage(infractionDetailModalMessageDiv, 'ID de l\'infraction manquant pour la suppression.', 'error');
            return;
        }

        const isConfirmed = await window.showCustomActionModal('Confirmer la suppression', 'Êtes-vous sûr de vouloir supprimer cette infraction ? Cette action est irréversible.', false, '', 'Oui, Supprimer', 'delete-account-button');
        if (isConfirmed === null) {
            console.log("DEBUG JS: Suppression annulée par l'utilisateur.");
            return;
        }

        // --- Déplacé cette partie AVANT la requête asynchrone ---
        const originalButtonHtml = actionButton.innerHTML;
        actionButton.disabled = true;
        actionButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Suppr...';
        // --- FIN DÉPLACEMENT ---

        try {
            const response = await fetch('/api/v1/delete-infraction-api/index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ infraction_id: infractionId })
            });
            const data = await response.json();
            console.log("DEBUG JS: Réponse API delete-infraction reçue:", data);

            if (response.ok && data.success) {
                showProceduresMessage(data.message || 'Infraction supprimée.', 'success');
                // Recharger l'historique de la personne après suppression
                await loadInfractionHistory(currentPersonId);
                // Fermer la modale de détail de l'infraction
                setTimeout(() => {
                    infractionDetailModal.classList.remove('active');
                    hideModalMessage(infractionDetailModalMessageDiv);
                }, 1500);
            } else {
                showProceduresMessage(data.message || 'Erreur lors de la suppression.', 'error');
            }
        } catch (error) {
            console.error('DEBUG JS: Erreur réseau suppression infraction:', error);
            showProceduresMessage('Erreur réseau.', 'error');
        } finally {
            // S'assurer que le bouton existe encore avant de le manipuler (au cas où il a été retiré par reload)
            if (actionButton) { 
                actionButton.disabled = false;
                actionButton.innerHTML = originalButtonHtml;
            }
        }
    }


    // --- Fonctions de gestion de la Modale d'Ajout de Personne ---
    if (addPersonButton) {
        addPersonButton.addEventListener('click', () => {
            addPersonForm.reset();
            hideModalMessage(addPersonModalMessageDiv);
            addPersonModal.classList.add('active');
        });
    }
    if (closeAddPersonModalButton) {
        closeAddPersonModalButton.addEventListener('click', () => {
            console.log("DEBUG JS: closeAddPersonModalButton cliqué.");
            addPersonModal.classList.remove('active');
            hideModalMessage(addPersonModalMessageDiv);
        });
    }
    if (addPersonModal) {
        addPersonModal.addEventListener('click', (event) => {
            if (event.target === addPersonModal) {
                console.log("DEBUG JS: Clic sur overlay addPersonModal.");
                addPersonModal.classList.remove('active');
                hideModalMessage(addPersonModalMessageDiv);
            }
        });
    }
    // Soumission du formulaire d'ajout de personne
    if (addPersonForm) {
        addPersonForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            hideModalMessage(addPersonModalMessageDiv);

            const formData = new FormData(addPersonForm);
            const data = Object.fromEntries(formData.entries());

            if (!data.nom || !data.prenom) {
                showModalMessage(addPersonModalMessageDiv, "Nom et Prénom sont requis.", 'error');
                return;
            }

            const submitButton = addPersonForm.querySelector('button[type="submit"]');
            const originalButtonHtml = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Ajout...';

            try {
                const response = await fetch('/api/v1/add-person-api/index.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const responseData = await response.json();

                if (response.ok && responseData.success) {
                    showModalMessage(addPersonModalMessageDiv, responseData.message || 'Personne ajoutée avec succès !', 'success');
                    addPersonForm.reset();
                    searchPersons(personSearchTermInput.value);
                    setTimeout(() => {
                        addPersonModal.classList.remove('active');
                        hideModalMessage(addPersonModalMessageDiv);
                    }, 1500);
                } else {
                    showModalMessage(addPersonModalMessageDiv, responseData.message || 'Erreur lors de l\'ajout de la personne.', 'error');
                }
            } catch (error) {
                console.error('Erreur réseau ajout personne:', error);
                showModalMessage(addPersonModalMessageDiv, 'Erreur réseau lors de l\'ajout de la personne.', 'error');
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonHtml;
            }
        });
    }

    // --- Fonctions de gestion de la Modale d'Ajout d'Infraction ---
    if (addInfractionButton) {
        addInfractionButton.addEventListener('click', async () => {
            hideModalMessage(addInfractionModalMessageDiv);
            addInfractionForm.reset();
            dynamicInfractionFieldsDiv.innerHTML = '<p style="text-align: center; color: var(--secondary-color);">Sélectionnez un type d\'infraction pour afficher les champs.</p>';
            addInfractionModalTitle.textContent = `Ajouter une Infraction (${htmlEscape(personDetailNameSpan.textContent)})`;
            addInfractionPersonIdInput.value = currentPersonId;

            await loadInfractionTypesForSelect();

            addInfractionModal.classList.add('active');
        });
    }
    if (closeAddInfractionModalButton) {
        closeAddInfractionModalButton.addEventListener('click', () => {
            console.log("DEBUG JS: closeAddInfractionModalButton cliqué.");
            addInfractionModal.classList.remove('active');
            hideModalMessage(addInfractionModalMessageDiv);
        });
    }
    if (addInfractionModal) {
        addInfractionModal.addEventListener('click', (event) => {
            if (event.target === addInfractionModal) {
                console.log("DEBUG JS: Clic sur overlay addInfractionModal.");
                addInfractionModal.classList.remove('active');
                hideModalMessage(addInfractionModalMessageDiv);
            }
        });
    }

    async function loadInfractionTypesForSelect() {
        if (!infractionTypeSelect) return;
        try {
            const response = await fetch('/api/v1/procedure-config-api/index.php?action=list_types&active_only=true');
            const data = await response.json();
            if (response.ok && data.success && data.procedure_types) {
                infractionTypeSelect.innerHTML = '<option value="">Sélectionnez le type d\'infraction</option>';
                const allowedInfractionTypes = ['FNPC', 'TAJ', 'SIA', 'SIV']; // Types permis pour cette API (pas FPR)
                data.procedure_types.filter(type => allowedInfractionTypes.includes(type.name)).forEach(type => {
                    const option = document.createElement('option');
                    option.value = type.id;
                    option.textContent = type.name;
                    infractionTypeSelect.appendChild(option);
                });
            } else {
                showModalMessage(addInfractionModalMessageDiv, data.message || 'Impossible de charger les types d\'infraction.', 'error');
                infractionTypeSelect.disabled = true;
            }
        } catch (error) {
            console.error('Erreur réseau chargement types infraction:', error);
            showModalMessage(addInfractionModalMessageDiv, 'Erreur réseau lors du chargement des types d\'infraction.', 'error');
            infractionTypeSelect.disabled = true;
        }
    }

    // Quand le type d'infraction est sélectionné, charger les champs dynamiques
    if (infractionTypeSelect) {
        infractionTypeSelect.addEventListener('change', async () => {
            const selectedTypeId = infractionTypeSelect.value;
            dynamicInfractionFieldsDiv.innerHTML = '<p style="text-align: center; color: var(--secondary-color);">Chargement des champs...</p>';
            if (!selectedTypeId) {
                dynamicInfractionFieldsDiv.innerHTML = '<p style="text-align: center; color: var(--secondary-color);">Sélectionnez un type d\'infraction pour afficher les champs.</p>';
                return;
            }

            try {
                const response = await fetch(`/api/v1/procedure-config-api/index.php?action=list_fields&type_id=${selectedTypeId}`);
                const data = await response.json();

                if (response.ok && data.success && data.fields) {
                    dynamicInfractionFieldsDiv.innerHTML = '';
                    if (data.fields.length === 0) {
                        dynamicInfractionFieldsDiv.innerHTML = '<p style="text-align: center; color: var(--secondary-color);">Aucun champ configuré pour ce type d\'infraction.</p>';
                    } else {
                        data.fields.forEach(field => {
                            let inputElementHtml = '';
                            const fieldIdPrefix = `dynamic_field_${field.id}`;
                            const isRequiredAttr = field.is_required ? 'required' : '';
                            let iconClass = 'fas fa-info-circle';
                            if (field.field_type === 'text' || field.field_type === 'number') iconClass = 'fas fa-pen';
                            if (field.field_type === 'date') iconClass = 'fas fa-calendar-alt';
                            if (field.field_type === 'time') iconClass = 'fas fa-clock';
                            if (field.field_type === 'datetime-local') iconClass = 'fas fa-calendar-alt';
                            if (field.field_type === 'textarea') iconClass = 'fas fa-align-left';
                            if (field.field_type === 'select') iconClass = 'fas fa-list-alt';
                            if (field.field_type === 'checkbox') iconClass = 'fas fa-check-square';
                            if (field.field_type === 'radio') iconClass = 'fas fa-dot-circle';


                            switch (field.field_type) {
                                case 'textarea':
                                    inputElementHtml = `<textarea id="${fieldIdPrefix}" name="${htmlEscape(field.field_name)}" placeholder="${htmlEscape(field.field_label)}" ${isRequiredAttr}></textarea>`;
                                    break;
                                case 'select':
                                    const options = JSON.parse(field.options_json || '[]');
                                    let optionsHtml = options.map(optionVal => 
                                        `<option value="${htmlEscape(optionVal)}">${htmlEscape(optionVal)}</option>`
                                    ).join('');
                                    inputElementHtml = `<select id="${fieldIdPrefix}" name="${htmlEscape(field.field_name)}" ${isRequiredAttr}><option value="">${htmlEscape(field.field_label)}</option>${optionsHtml}</select>`;
                                    break;
                                case 'checkbox':
                                    inputElementHtml = `<label class="checkbox-container">
                                        <input type="checkbox" id="${fieldIdPrefix}" name="${htmlEscape(field.field_name)}" ${isRequiredAttr}> ${htmlEscape(field.field_label)}
                                        <span class="checkmark"></span>
                                    </label>`;
                                    iconClass = '';
                                    break;
                                case 'radio':
                                    const radioOptions = JSON.parse(field.options_json || '[]');
                                    inputElementHtml = radioOptions.map((optionVal, index) => `
                                        <label class="radio-container">
                                            <input type="radio" id="${fieldIdPrefix}_${index}" name="${htmlEscape(field.field_name)}" value="${htmlEscape(optionVal)}" ${isRequiredAttr}> ${htmlEscape(optionVal)}
                                            <span class="radiomark"></span>
                                        </label>
                                    `).join('');
                                    iconClass = '';
                                    break;
                                default: // text, number, date, time, datetime-local
                                    inputElementHtml = `<input type="${htmlEscape(field.field_type)}" id="${fieldIdPrefix}" name="${htmlEscape(field.field_name)}" placeholder="${htmlEscape(field.field_label)}" ${isRequiredAttr}>`;
                                    break;
                            }
                            
                            const fieldWrapper = document.createElement('div');
                            fieldWrapper.className = 'input-group';
                            fieldWrapper.innerHTML = `
                                ${iconClass ? `<i class="${iconClass} icon"></i>` : ''}
                                ${inputElementHtml}
                            `;
                            dynamicInfractionFieldsDiv.appendChild(fieldWrapper);
                        });
                    }
                } else {
                    showModalMessage(addInfractionModalMessageDiv, data.message || 'Erreur lors du chargement des champs d\'infraction dynamiques.', 'error');
                }
            } catch (error) {
                console.error('Erreur réseau chargement champs infraction dynamique:', error);
                showModalMessage(addInfractionModalMessageDiv, 'Erreur réseau lors du chargement des champs dynamiques.', 'error');
            }
        });
    }

    // Soumission du formulaire d'ajout d'infraction
    if (addInfractionForm) {
        addInfractionForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            hideModalMessage(addInfractionModalMessageDiv);

            const personId = addInfractionPersonIdInput.value;
            const infractionTypeId = infractionTypeSelect.value;
            const formElements = dynamicInfractionFieldsDiv.querySelectorAll('input, select, textarea');
            const dynamicFieldsData = {};

            formElements.forEach(element => {
                if (element.type === 'checkbox') {
                    dynamicFieldsData[element.name] = element.checked ? 1 : 0;
                } else if (element.type === 'radio') {
                    if (element.checked) {
                        dynamicFieldsData[element.name] = element.value;
                    }
                } else {
                    dynamicFieldsData[element.name] = element.value.trim();
                }
            });

            const fieldsConfigResponse = await fetch(`/api/v1/procedure-config-api/index.php?action=list_fields&type_id=${infractionTypeId}`);
            const fieldsConfigData = await fieldsConfigResponse.json();
            if (fieldsConfigResponse.ok && fieldsConfigData.success && fieldsConfigData.fields) {
                for (const field of fieldsConfigData.fields) {
                    if (field.is_required && !dynamicFieldsData[field.field_name]) {
                        showModalMessage(addInfractionModalMessageDiv, `Le champ "${htmlEscape(field.field_label)}" est obligatoire.`, 'error');
                        return;
                    }
                }
            } else {
                showModalMessage(addInfractionModalMessageDiv, "Erreur de validation des champs dynamiques.", 'error');
                return;
            }

            const postData = {
                action: 'add_infraction',
                person_id: personId,
                procedure_type_id: infractionTypeId,
                dynamic_data: dynamicFieldsData,
                agent_id: loggedInUserId
            };

            saveInfractionButton.disabled = true;
            saveInfractionButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';

            try {
                const response = await fetch('/api/v1/add-infraction-api/index.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(postData)
                });
                const data = await response.json();

                if (response.ok && data.success) {
                    showModalMessage(addInfractionModalMessageDiv, data.message || 'Infraction ajoutée avec succès !', 'success');
                    addInfractionForm.reset();
                    dynamicInfractionFieldsDiv.innerHTML = '<p style="text-align: center; color: var(--secondary-color);">Sélectionnez un type d\'infraction pour afficher les champs.</p>';
                    await loadInfractionHistory(currentPersonId);
                    setTimeout(() => {
                        addInfractionModal.classList.remove('active');
                        hideModalMessage(addInfractionModalMessageDiv);
                    }, 1500);
                } else {
                    showModalMessage(addInfractionModalMessageDiv, data.message || 'Erreur lors de l\'ajout de l\'infraction.', 'error');
                }
            } catch (error) {
                console.error('Erreur réseau ajout infraction:', error);
                showModalMessage(addInfractionModalMessageDiv, 'Erreur réseau lors de l\'ajout de l\'infraction.', 'error');
            } finally {
                saveInfractionButton.disabled = false;
                saveInfractionButton.innerHTML = '<i class="fas fa-save"></i> Enregistrer l\'infraction';
            }
        });
    }

    // --- Fonctions de gestion de la Modale FPR (Ajout/Modification) ---
    if (addFPRButton) {
        addFPRButton.addEventListener('click', async () => {
            hideModalMessage(fprModalMessageDiv);
            fprForm.reset();
            fprPersonNameSpan.textContent = htmlEscape(personDetailNameSpan.textContent);
            fprPersonIdInput.value = currentPersonId;
            fprRecordIdInput.value = '';

            try {
                const response = await fetch(`/api/v1/fpr-api/index.php?action=get_fpr&person_id=${currentPersonId}`);
                const data = await response.json();
                if (response.ok && data.success && data.fpr_record) {
                    fprRecordIdInput.value = data.fpr_record.id;
                    fprIsWantedCheckbox.checked = (data.fpr_record.is_wanted === 1);
                    fprReasonTextarea.value = data.fpr_record.reason || '';
                    saveFPRButton.textContent = 'Mettre à jour Fiche FPR';
                } else {
                    saveFPRButton.textContent = 'Créer Fiche FPR';
                }
            } catch (error) {
                console.error('Erreur réseau chargement FPR:', error);
                showModalMessage(fprModalMessageDiv, 'Erreur réseau lors du chargement de la fiche FPR.', 'error');
            }

            manageFPRModal.classList.add('active');
        });
    }
    if (closeFPRModalButton) {
        closeFPRModalButton.addEventListener('click', () => {
            console.log("DEBUG JS: closeFPRModalButton cliqué.");
            manageFPRModal.classList.remove('active');
            hideModalMessage(fprModalMessageDiv);
        });
    }
    if (manageFPRModal) {
        manageFPRModal.addEventListener('click', (event) => {
            if (event.target === manageFPRModal) {
                console.log("DEBUG JS: Clic sur overlay manageFPRModal.");
                manageFPRModal.classList.remove('active');
                hideModalMessage(fprModalMessageDiv);
            }
        });
    }
    // Soumission du formulaire FPR
    if (fprForm) {
        fprForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            hideModalMessage(fprModalMessageDiv);

            const action = fprRecordIdInput.value ? 'update_fpr' : 'add_fpr';
            const formData = {
                action: action,
                person_id: fprPersonIdInput.value,
                record_id: fprRecordIdInput.value,
                is_wanted: fprIsWantedCheckbox.checked ? 1 : 0,
                reason: fprReasonTextarea.value.trim(),
                wanted_by_agent_id: loggedInUserId
            };

            if (formData.is_wanted && !formData.reason) {
                showModalMessage(fprModalMessageDiv, "La raison est obligatoire si la personne est recherchée.", 'error');
                return;
            }

            saveFPRButton.disabled = true;
            saveFPRButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';

            try {
                const response = await fetch('/api/v1/fpr-api/index.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
                const data = await response.json();

                if (response.ok && data.success) {
                    showModalMessage(fprModalMessageDiv, data.message || 'Fiche FPR enregistrée !', 'success');
                    await openPersonDetailModal({target: {dataset: {personId: currentPersonId}}});
                    setTimeout(() => {
                        manageFPRModal.classList.remove('active');
                        hideModalMessage(fprModalMessageDiv);
                    }, 1500);
                } else {
                    showModalMessage(fprModalMessageDiv, data.message || 'Erreur lors de l\'enregistrement de la fiche FPR.', 'error');
                }
            } catch (error) {
                console.error('Erreur réseau enregistrement FPR:', error);
                showModalMessage(fprModalMessageDiv, 'Erreur réseau lors de l\'enregistrement de la fiche FPR.', 'error');
            } finally {
                saveFPRButton.disabled = false;
                saveFPRButton.innerHTML = '<i class="fas fa-save"></i> Enregistrer Fiche FPR';
            }
        });
    }

    // --- Actions Administratives sur la Personne (Supprimer Personne) ---
    if (deletePersonButton) {
        deletePersonButton.addEventListener('click', async () => {
            const isConfirmed = await window.showCustomActionModal('Confirmer la suppression', 'Êtes-vous sûr de vouloir supprimer cette personne ? Toutes ses infractions et fiches associées seront supprimées.', false, '', 'Oui, Supprimer', 'delete-account-button');
            if (isConfirmed === null) return;

            personDetailModal.classList.remove('active');
            
            const originalButtonHtml = deletePersonButton.innerHTML;
            deletePersonButton.disabled = true;
            deletePersonButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Suppr...';

            try {
                const response = await fetch('/api/v1/delete-person-api/index.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ person_id: currentPersonId })
                });
                const data = await response.json();

                if (response.ok && data.success) {
                    showProceduresMessage(data.message || 'Personne supprimée des fichiers.', 'success');
                    searchPersons(personSearchTermInput.value);
                } else {
                    showProceduresMessage(data.message || 'Erreur lors de la suppression de la personne.', 'error');
                }
            } catch (error) {
                console.error('Erreur réseau suppression personne:', error);
                showProceduresMessage('Erreur réseau lors de la suppression. Veuillez vérifier votre connexion.', 'error');
            } finally {
                deletePersonButton.disabled = false;
                deletePersonButton.innerHTML = originalButtonHtml;
            }
        });
    }

    if (closePersonDetailModalButton) {
        closePersonDetailModalButton.addEventListener('click', () => {
            console.log("DEBUG PROCEDURES JS: [CLOSE CLICKED] closePersonDetailModalButton cliqué !"); // <<< LOG
            if (personDetailModal) personDetailModal.classList.remove('active');
            hideModalMessage(personDetailModalMessageDiv);
        });
    }
    if (personDetailModal) {
        personDetailModal.addEventListener('click', (event) => {
            if (event.target === personDetailModal) { // Clic sur l'overlay
                console.log("DEBUG PROCEDURES JS: [OVERLAY CLICKED] Clic sur overlay personDetailModal !"); // <<< LOG
                if (personDetailModal) personDetailModal.classList.remove('active');
                hideModalMessage(personDetailModalMessageDiv);
            }
        });
    }

    // --- Exécution initiale au chargement de la page ---
    if (searchPersonButton) {
        searchPersonButton.addEventListener('click', () => {
            const searchTerm = personSearchTermInput.value.trim();
            searchPersons(searchTerm);
        });
        personSearchTermInput.addEventListener('keypress', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                searchPersonButton.click();
            }
        });
    }

    // --- Exécution initiale au chargement de la page ---
    if (searchPersonButton) {
        searchPersonButton.click();
    }
});