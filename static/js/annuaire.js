// Définition de variables globales ou accessibles à toutes les fonctions
const memberDetailModal = document.getElementById('memberDetailModal');
const annuaireMessageDiv = document.getElementById('annuaireMessage'); // Réf. à la div message de l'annuaire

let currentMemberId = null; // Pour stocker l'ID du membre actuellement dans la modale
const loggedInUserRoleElement = document.getElementById('loggedInUserRole');
const loggedInUserRole = loggedInUserRoleElement ? loggedInUserRoleElement.value : 'Agent'; // 'Agent' par défaut

// Références aux éléments de filtre pour la recherche
const searchNameMatriculeInput = document.getElementById('searchNameMatricule');
const searchServiceSelect = document.getElementById('searchService');
const searchButton = document.getElementById('searchButton');
const membersListBody = document.getElementById('membersListBody');


// --- Fonctions de gestion des messages de l'annuaire (maintenant directement dans ce fichier) ---
function showAnnuaireMessage(message, type = 'error') {
    if (!annuaireMessageDiv) return;
    annuaireMessageDiv.textContent = message;
    annuaireMessageDiv.className = 'annuaire-message ' + type;
    annuaireMessageDiv.style.display = 'block';
    annuaireMessageDiv.style.opacity = '0';
    void annuaireMessageDiv.offsetWidth;
    annuaireMessageDiv.style.opacity = '1';
}

function hideAnnuaireMessage() {
    if (!annuaireMessageDiv) return;
    annuaireMessageDiv.style.display = 'none';
    annuaireMessageDiv.textContent = '';
    annuaireMessageDiv.className = 'annuaire-message';
}

// Cette fonction gère l'affichage des messages pour la modale de DÉTAIL du membre (maintenant directement ici)
function showMemberDetailModalMessage(message, type = 'error') {
    const currentModalActionMessageDiv = memberDetailModal ? memberDetailModal.querySelector('#modalActionMessage') : null;
    if (!currentModalActionMessageDiv) return;

    currentModalActionMessageDiv.textContent = message;
    currentModalActionMessageDiv.className = 'modal-message ' + type;
    currentModalActionMessageDiv.style.display = 'block';
    currentModalActionMessageDiv.style.opacity = '0';
    void currentModalActionMessageDiv.offsetWidth;
    currentModalActionMessageDiv.style.opacity = '1';
}

function hideMemberDetailModalMessage() {
    const currentModalActionMessageDiv = memberDetailModal ? memberDetailModal.querySelector('#modalActionMessage') : null;
    if (!currentModalActionMessageDiv) return;

    currentModalActionMessageDiv.style.display = 'none';
    currentModalActionMessageDiv.textContent = '';
    currentModalActionMessageDiv.className = 'modal-message';
}

// --- Fonction d'échappement HTML pour prévenir le XSS (maintenant directement ici) ---
function htmlEscape(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// --- Fonctions pour charger les services (pour le sélecteur de recherche) ---
async function loadServicesForSearch() {
    // searchServiceSelect est une const globale
    if (!searchServiceSelect) return;
    try {
        const response = await fetch('/api/v1/services-list-api/index.php');
        const data = await response.json();
        if (response.ok && data.success && data.services) {
            searchServiceSelect.innerHTML = '<option value="">Tous les services</option>';
            data.services.forEach(service => {
                const option = document.createElement('option');
                option.value = service;
                option.textContent = service;
                searchServiceSelect.appendChild(option);
            });
        } else {
            console.error('Erreur chargement services (annuaire):', data.message);
            showAnnuaireMessage('Impossible de charger les services pour la recherche.', 'error');
            searchServiceSelect.disabled = true;
        }
    } catch (error) {
        console.error('Erreur réseau chargement services (annuaire):', error);
        showAnnuaireMessage('Erreur réseau lors du chargement des services.', 'error');
        searchServiceSelect.disabled = true;
    }
}

// --- Fonction pour charger la liste des membres ---
async function loadMembers(searchTerm = '', serviceFilter = '') {
    // membersListBody est une const globale
    if (!membersListBody) return;

    membersListBody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px;">Chargement des membres...</td></tr>';
    hideAnnuaireMessage();

    let queryParams = new URLSearchParams();
    if (searchTerm) queryParams.append('search', searchTerm);
    if (serviceFilter) queryParams.append('service', serviceFilter);

    try {
        const response = await fetch(`/api/v1/annuaire-list-api/index.php?${queryParams.toString()}`);
        const data = await response.json();

        if (response.ok && data.success && data.members) {
            membersListBody.innerHTML = '';

            if (data.members.length === 0) {
                membersListBody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px;">Aucun membre trouvé correspondant à la recherche.</td></tr>';
            } else {
                data.members.forEach(member => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${htmlEscape(member.matricule)}</td>
                        <td>${htmlEscape(member.nom)}</td>
                        <td>${htmlEscape(member.prenom)}</td>
                        <td>${htmlEscape(member.service)}</td>
                        <td>${htmlEscape(member.role)}</td>
                        <td>
                            <button class="view-member-details-button" data-member-id="${htmlEscape(member.id)}">Voir Détails</button>
                        </td>
                    `;
                    membersListBody.appendChild(row);
                });
                membersListBody.querySelectorAll('.view-member-details-button').forEach(button => {
                    button.addEventListener('click', handleViewDetails);
                });
            }
        } else {
            showAnnuaireMessage(data.message || 'Erreur lors du chargement des membres.', 'error');
            membersListBody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px; color:var(--error-color);">Erreur lors du chargement des membres.</td></tr>';
        }
    } catch (error) {
        console.error('Erreur réseau lors du chargement des membres:', error);
        showAnnuaireMessage('Erreur réseau lors du chargement des membres. Veuillez vérifier votre connexion.', 'error');
        membersListBody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px; color:var(--error-color);">Erreur réseau.</td></tr>';
    }
}

// Gestion de l'ouverture de la modale de détails du membre
// Définie globalement pour être accessible
async function handleViewDetails(event) {
    // memberDetailModal est une const globale
    const modalBodyContent = memberDetailModal ? memberDetailModal.querySelector('.modal-body-content') : null;
    
    currentMemberId = event.target.dataset.memberId;
    hideMemberDetailModalMessage();

    if (modalBodyContent) {
        modalBodyContent.innerHTML = '<div style="text-align:center; padding:50px;"><i class="fas fa-spinner fa-spin fa-3x" style="color:var(--primary-color);"></i><p style="color:var(--secondary-color);">Chargement des détails...</p></div>';
    }
    
    if (memberDetailModal) memberDetailModal.classList.add('active');

    try {
        const response = await fetch(`/api/v1/annuaire-detail-api/index.php?id=${currentMemberId}`);
        const data = await response.json();

        if (response.ok && data.success && data.member) {
            const member = data.member;

            let adminButtonsHtml = '';
            if (loggedInUserRole === 'Superviseur' || loggedInUserRole === 'Admin') {
                adminButtonsHtml += `<button class="action-button admin-action-button change-password-button" data-action="change_password" data-target-id="${htmlEscape(member.id)}"><i class="fas fa-key"></i> Changer MDP</button>`;
                adminButtonsHtml += `<button class="action-button admin-action-button change-email-button" data-action="change_email" data-target-id="${htmlEscape(member.id)}"><i class="fas fa-at"></i> Changer Email</button>`;
            }
            if (loggedInUserRole === 'Admin') {
                adminButtonsHtml += `<button class="action-button admin-action-button delete-account-button" data-action="delete_account" data-target-id="${htmlEscape(member.id)}"><i class="fas fa-trash-alt"></i> Supprimer Compte</button>`;
            }

            if (modalBodyContent) {
                modalBodyContent.innerHTML = `
                    <div class="detail-grid">
                        <div class="detail-item"><strong>Matricule:</strong> <span>${htmlEscape(member.matricule)}</span></div>
                        <div class="detail-item"><strong>Nom:</strong> <span>${htmlEscape(member.nom)}</span></div>
                        <div class="detail-item"><strong>Prénom:</strong> <span>${htmlEscape(member.prenom)}</span></div>
                        <div class="detail-item"><strong>Service:</strong> <span>${htmlEscape(member.service)}</span></div>
                        <div class="detail-item"><strong>Rôle:</strong> <span>${htmlEscape(member.role)}</span></div>
                        <div class="detail-item"><strong>Statut:</strong> <span>${htmlEscape(member.statut)}</span></div>
                        ${member.date_embauche ? `<div class="detail-item"><strong>Date d'embauche:</strong> <span>${htmlEscape(member.date_embauche)}</span></div>` : ''}
                    </div>
                    <div class="modal-actions">
                        <button class="action-button report-button" data-action="report" data-target-id="${htmlEscape(member.id)}"><i class="fas fa-flag"></i> Signaler</button>
                        <button class="action-button message-button" data-action="message" data-target-id="${htmlEscape(member.id)}"><i class="fas fa-envelope"></i> Envoyer un message</button>
                        ${adminButtonsHtml}
                    </div>
                    <div id="modalActionMessage" class="modal-message"></div>
                `;

                // Ré-attacher les écouteurs d'événements aux nouveaux boutons d'action
                modalBodyContent.querySelectorAll('.action-button').forEach(button => {
                    button.addEventListener('click', handleMemberAction);
                });
            }

        } else {
            const message = data.message || 'Erreur lors du chargement des détails du membre.';
            if (modalBodyContent) {
                modalBodyContent.innerHTML = `<div style="text-align:center; padding:50px; color:var(--error-color);">${htmlEscape(message)}</div>`;
            }
            showMemberDetailModalMessage(message, 'error');
        }
    } catch (error) {
        console.error('Erreur réseau ou du serveur (détails membre):', error);
        const errorMessage = 'Erreur réseau lors du chargement des détails du membre.';
        if (modalBodyContent) {
            modalBodyContent.innerHTML = `<div style="text-align:center; padding:50px; color:var(--error-color);">${htmlEscape(errorMessage)}</div>`;
        }
        showMemberDetailModalMessage(errorMessage, 'error');
    }
}

// --- Gestion de la fermeture des modales ---
const closeMemberDetailModalButton = document.getElementById('closeMemberDetailModalButton'); // Ré-acquérir la référence
if (closeMemberDetailModalButton) {
    closeMemberDetailModalButton.addEventListener('click', () => {
        if (memberDetailModal) memberDetailModal.classList.remove('active');
        hideMemberDetailModalMessage();
    });
}
if (memberDetailModal) {
    memberDetailModal.addEventListener('click', (event) => {
        if (event.target === memberDetailModal) {
            if (memberDetailModal) memberDetailModal.classList.remove('active');
            hideMemberDetailModalMessage();
        }
    });
}

// --- Gestion des actions sur un membre (Signaler, Message, Changer MDP/Email, Supprimer) ---
// Définie globalement pour être accessible
async function handleMemberAction(event) {
    const action = event.target.dataset.action;
    const targetId = event.target.dataset.targetId;
    if (!targetId) return;

    let promptTitle = '';
    let promptMessage = '';
    let showInput = false;
    let inputPlaceholder = '';
    let confirmButtonText = 'Confirmer';
    let confirmButtonType = 'primary-button';

    switch (action) {
        case 'report':
            promptTitle = 'Signaler un Membre';
            promptMessage = 'Veuillez décrire la raison du signalement :';
            showInput = true;
            inputPlaceholder = 'Description du signalement';
            confirmButtonText = 'Envoyer le signalement';
            confirmButtonType = 'report-button';
            break;
        case 'message':
            promptTitle = 'Envoyer un Message';
            promptMessage = 'Entrez le message à envoyer :';
            showInput = true;
            inputPlaceholder = 'Votre message';
            confirmButtonText = 'Envoyer le message';
            confirmButtonType = 'message-button';
            break;
        case 'change_password':
            promptTitle = 'Changer le Mot de Passe';
            promptMessage = 'Entrez le nouveau mot de passe pour ce membre :';
            showInput = true;
            inputPlaceholder = 'Nouveau mot de passe';
            confirmButtonText = 'Changer le MDP';
            confirmButtonType = 'change-password-button';
            break;
        case 'change_email':
            promptTitle = 'Changer l\'Adresse Email';
            promptMessage = 'Entrez la nouvelle adresse email pour ce membre :';
            showInput = true;
            inputPlaceholder = 'Nouvelle adresse email';
            confirmButtonText = 'Changer l\'Email';
            confirmButtonType = 'change-email-button';
            break;
        case 'delete_account':
            promptTitle = 'Supprimer le Compte';
            promptMessage = 'ATTENTION : Cette action est irréversible. Êtes-vous ABSOLUMENT sûr de vouloir supprimer ce compte ?';
            confirmButtonText = 'Oui, Supprimer';
            confirmButtonType = 'delete-account-button';
            break;
        default:
            showMemberDetailModalMessage('Action non reconnue.', 'error');
            return;
    }

    // Utilisation de la nouvelle modale personnalisée
    // showCustomActionModal est définie dans index.js et attachée à window
    const actionResult = await window.showCustomActionModal(promptTitle, promptMessage, showInput, inputPlaceholder, confirmButtonText, confirmButtonType);

    if (actionResult === null) { // Si l'utilisateur a annulé (clic sur Annuler ou sur la croix)
        return;
    }
    
    // Cacher la modale de détail du membre avant l'action
    if (memberDetailModal) memberDetailModal.classList.remove('active');

    const actionButton = event.target; // Le bouton original qui a été cliqué
    const originalButtonHtml = actionButton.innerHTML;
    actionButton.disabled = true;
    actionButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';


    try {
        let apiEndpoint = '';
        let requestBody = { target_user_id: targetId };

        switch (action) {
            case 'report':
                apiEndpoint = '/api/v1/signaler-user-api/index.php';
                if (!actionResult) { window.showGenericModalFeedback("Description du signalement requise.", 'error'); actionButton.disabled = false; actionButton.innerHTML = originalButtonHtml; return; }
                requestBody.type_signalement = 'Annuaire';
                requestBody.description = actionResult;
                break;
            case 'message':
                apiEndpoint = '/api/v1/send-message-api/index.php';
                if (!actionResult) { window.showGenericModalFeedback("Message requis.", 'error'); actionButton.disabled = false; actionButton.innerHTML = originalButtonHtml; return; }
                requestBody.message_content = actionResult;
                break;
            case 'change_password':
                apiEndpoint = '/api/v1/admin-change-password-api/index.php';
                if (!actionResult || actionResult.length < 8) { window.showGenericModalFeedback("Nouveau mot de passe invalide (min 8 caractères).", 'error'); actionButton.disabled = false; actionButton.innerHTML = originalButtonHtml; return; }
                requestBody.new_password = actionResult;
                break;
            case 'change_email':
                apiEndpoint = '/api/v1/admin-change-email-api/index.php';
                if (!actionResult || !actionResult.includes('@')) { window.showGenericModalFeedback("Format email invalide.", 'error'); actionButton.disabled = false; actionButton.innerHTML = originalButtonHtml; return; }
                requestBody.new_email = actionResult;
                break;
            case 'delete_account':
                apiEndpoint = '/api/v1/admin-delete-account-api/index.php';
                break;
            default:
                window.showGenericModalFeedback('Action non reconnue.', 'error');
                actionButton.disabled = false;
                actionButton.innerHTML = originalButtonHtml;
                return;
        }

        const response = await fetch(apiEndpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(requestBody)
        });
        const data = await response.json();

        if (response.ok && data.success) {
            showAnnuaireMessage(data.message || `Action "${action}" effectuée avec succès.`, 'success');
            if (action === 'delete_account') {
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            }
        } else {
            showAnnuaireMessage(data.message || `Erreur lors de l'action "${action}".`, 'error');
        }
    } catch (error) {
        console.error('Erreur réseau ou du serveur (action membre):', error);
        showAnnuaireMessage('Erreur réseau lors de l\'action. Veuillez vérifier votre connexion.', 'error');
    } finally {
        actionButton.disabled = false;
        actionButton.innerHTML = originalButtonHtml;
    }
}


// --- Exécution initiale au chargement de la page ---
document.addEventListener('DOMContentLoaded', () => {
    // Références des éléments qui peuvent être créés après le DOMContentLoaded initial
    const searchNameMatriculeInput = document.getElementById('searchNameMatricule');
    const searchServiceSelect = document.getElementById('searchService');
    const searchButton = document.getElementById('searchButton');
    const membersListBody = document.getElementById('membersListBody');

    // Écouteur pour le bouton de recherche
    if (searchButton) {
        searchButton.addEventListener('click', () => {
            const searchTerm = searchNameMatriculeInput.value.trim();
            const serviceFilter = searchServiceSelect.value;
            loadMembers(searchTerm, serviceFilter);
        });
    }

    // Chargement initial
    loadServicesForSearch();
    loadMembers();
});