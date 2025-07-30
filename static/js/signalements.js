// Définition de variables globales ou accessibles à toutes les fonctions
// Déplacez les const globales à l'extérieur du DOMContentLoaded pour les rendre accessibles partout
const signalementsListBody = document.getElementById('signalementsListBody');
const statusFilterSelect = document.getElementById('statusFilter');
const reporterFilterInput = document.getElementById('reporterFilter');
const reportedFilterInput = document.getElementById('reportedFilter');
const applyFiltersButton = document.getElementById('applyFiltersButton');
const signalementsMessageDiv = document.getElementById('signalementsMessage');

const loggedInUserRoleElement = document.getElementById('loggedInUserRole');
const loggedInUserRole = loggedInUserRoleElement ? loggedInUserRoleElement.value : 'Agent';
const loggedInUserIdElement = document.getElementById('loggedInUserId');
const loggedInUserId = loggedInUserIdElement ? loggedInUserIdElement.value : null;

// DEBUG: Loguer les références DOM au démarrage
console.log("DEBUG GLOBAL: signalementsListBody:", signalementsListBody);
console.log("DEBUG GLOBAL: statusFilterSelect:", statusFilterSelect);
console.log("DEBUG GLOBAL: reporterFilterInput:", reporterFilterInput);
console.log("DEBUG GLOBAL: reportedFilterInput:", reportedFilterInput);
console.log("DEBUG GLOBAL: applyFiltersButton:", applyFiltersButton);
console.log("DEBUG GLOBAL: signalementsMessageDiv:", signalementsMessageDiv);
console.log("DEBUG GLOBAL: loggedInUserRoleElement:", loggedInUserRoleElement);
console.log("DEBUG GLOBAL: loggedInUserIdElement:", loggedInUserIdElement);

// Variables et éléments de la modale de détail de membre (définis globalement si elle est globale)
const memberDetailModal = document.getElementById('memberDetailModal');
const closeMemberDetailModalButton = document.getElementById('closeMemberDetailModalButton');

console.log("DEBUG GLOBAL: memberDetailModal:", memberDetailModal); // Vérifier si la modale principale est trouvée

let currentMemberId = null;

// --- Fonctions de gestion des messages --- (inchangées)
function showSignalementsMessage(message, type = 'error') {
    if (!signalementsMessageDiv) { console.error("signalementsMessageDiv introuvable."); return; } // Add error log
    signalementsMessageDiv.textContent = message;
    signalementsMessageDiv.className = 'signalements-message ' + type;
    signalementsMessageDiv.style.display = 'block';
    signalementsMessageDiv.style.opacity = '0';
    void signalementsMessageDiv.offsetWidth;
    signalementsMessageDiv.style.opacity = '1';
}

function hideSignalementsMessage() {
    if (!signalementsMessageDiv) { console.error("signalementsMessageDiv introuvable."); return; } // Add error log
    signalementsMessageDiv.style.display = 'none';
    signalementsMessageDiv.textContent = '';
    signalementsMessageDiv.className = 'signalements-message';
}

function showMemberDetailModalMessage(message, type = 'error') {
    const currentModalActionMessageDiv = memberDetailModal ? memberDetailModal.querySelector('#modalActionMessage') : null;
    if (!currentModalActionMessageDiv) { console.error("modalActionMessageDiv dans modale détail introuvable."); return; } // Add error log
    currentModalActionMessageDiv.textContent = message;
    currentModalActionMessageDiv.className = 'modal-message ' + type;
    currentModalActionMessageDiv.style.display = 'block';
    currentModalActionMessageDiv.style.opacity = '0';
    void currentModalActionMessageDiv.offsetWidth;
    currentModalActionMessageDiv.style.opacity = '1';
}

function hideMemberDetailModalMessage() {
    const currentModalActionMessageDiv = memberDetailModal ? memberDetailModal.querySelector('#modalActionMessage') : null;
    if (!currentModalActionMessageDiv) { console.error("modalActionMessageDiv dans modale détail introuvable."); return; } // Add error log
    currentModalActionMessageDiv.style.display = 'none';
    currentModalActionMessageDiv.textContent = '';
    currentModalActionMessageDiv.className = 'modal-message';
}

function htmlEscape(str) { // (inchangée)
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// NOTE: showCustomActionModal et showGenericModalFeedback SONT DÉFINIES DANS INDEX.JS
// Elles sont supposées être attachées à window. (window.showCustomActionModal, window.showGenericModalFeedback)

// --- Fonction pour charger la liste des signalements ---
async function loadSignalements(status = '', reporter = '', reported = '') {
    console.log("DEBUG: loadSignalements() appelé.");
    if (!signalementsListBody) {
        console.error("ERREUR CRITIQUE: 'signalementsListBody' introuvable. Impossible de charger les signalements.");
        return; // Arrêter ici si l'élément principal n'est pas trouvé
    }

    signalementsListBody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px;">Chargement des signalements...</td></tr>';
    hideSignalementsMessage(); // S'assure que cette fonction ne crash pas si signalementsMessageDiv est null

    let queryParams = new URLSearchParams();
    if (status) queryParams.append('status', status);
    if (reporter) queryParams.append('reporter', reporter);
    if (reported) queryParams.append('reported', reported);

    console.log("DEBUG: Tentative de fetch API: /api/v1/signalements-list-api/index.php?" + queryParams.toString());

    try {
        const response = await fetch(`/api/v1/signalements-list-api/index.php?${queryParams.toString()}`);
        const data = await response.json();
        console.log("DEBUG: Réponse API reçue:", data); // Log la réponse API

        if (response.ok && data.success && data.signalements) {
            signalementsListBody.innerHTML = '';

            if (data.signalements.length === 0) {
                signalementsListBody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px;">Aucun signalement trouvé correspondant aux filtres.</td></tr>';
            } else {
                data.signalements.forEach(signalement => {
                    const row = document.createElement('tr');
                    const shortDescription = signalement.description.length > 50 ? signalement.description.substring(0, 47) + '...' : signalement.description;
                    
                    let actionButtonsHtml = '';
                    // Vérifier si le statut est bien 'en_attente' et le rôle de l'utilisateur connecté
                    if (signalement.statut === 'en_attente' && (loggedInUserRole === 'Superviseur' || loggedInUserRole === 'Admin')) {
                        actionButtonsHtml += `
                            <button class="action-button process-button" data-signalement-id="${htmlEscape(String(signalement.id_signalement_short))}" data-action="process">Traiter</button>
                            <button class="action-button reject-button" data-signalement-id="${htmlEscape(String(signalement.id_signalement_short))}" data-action="reject">Rejeter</button>
                        `;
                    } else {
                        actionButtonsHtml += `<span style="color:var(--secondary-color);">${htmlEscape(signalement.statut)}</span>`;
                    }

                    row.innerHTML = `
                        <td>${htmlEscape(String(signalement.id_signalement_short))}</td>
                        <td>${htmlEscape(signalement.signaleur_email || signalement.signaleur_matricule)}</td>
                        <td>${htmlEscape(signalement.signale_email || signalement.signale_matricule)}</td>
                        <td>${htmlEscape(signalement.type_signalement)}</td>
                        <td title="${htmlEscape(signalement.description)}">${htmlEscape(shortDescription)}</td>
                        <td>${htmlEscape(signalement.statut)}</td>
                        <td>${htmlEscape(signalement.date_signalement)}</td>
                        <td class="signalement-actions">${actionButtonsHtml}</td>
                    `;
                    signalementsListBody.appendChild(row);
                });

                signalementsListBody.querySelectorAll('.signalement-actions button').forEach(button => {
                    button.addEventListener('click', handleSignalementAction);
                });
            }
        } else {
            console.error("DEBUG: API a retourné un échec ou des données inattendues:", data);
            showSignalementsMessage(data.message || 'Erreur lors du chargement des signalements.', 'error');
            signalementsListBody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px; color:var(--error-color);">Erreur lors du chargement des signalements.</td></tr>';
        }
    } catch (error) {
        console.error('DEBUG: Erreur réseau ou du serveur (loadSignalements):', error);
        showSignalementsMessage('Erreur réseau lors du chargement des signalements. Veuillez vérifier votre connexion.', 'error');
        signalementsListBody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px; color:var(--error-color);">Erreur réseau.</td></tr>';
    }
}

// --- Gestion des actions sur un signalement (Traiter / Rejeter) ---
async function handleSignalementAction(event) {
    console.log("DEBUG: handleSignalementAction() appelé.");
    const action = event.target.dataset.action;
    const signalementId = event.target.dataset.signalementId; // Devrait être correct maintenant
    
    console.log("DEBUG: Action:", action, "Signalement ID:", signalementId);

    if (!signalementId) {
        showSignalementsMessage('ID du signalement manquant pour l\'action.', 'error');
        console.error("DEBUG: Signalement ID est vide ou null lors de l'action.");
        return;
    }

    let promptTitle = '';
    let promptMessage = '';
    let confirmButtonText = '';
    let confirmButtonType = 'primary-button';

    switch (action) {
        case 'process':
            promptTitle = 'Traiter le Signalement';
            promptMessage = 'Confirmez-vous que ce signalement a été traité ?';
            confirmButtonText = 'Oui, Traiter';
            confirmButtonType = 'process-button';
            break;
        case 'reject':
            promptTitle = 'Rejeter le Signalement';
            promptMessage = 'Confirmez-vous le rejet de ce signalement ?';
            confirmButtonText = 'Oui, Rejeter';
            confirmButtonType = 'reject-button';
            break;
        default:
            showSignalementsMessage('Action invalide.', 'error');
            return;
    }

    // showCustomActionModal est maintenant accessible via window
    const isConfirmed = await window.showCustomActionModal(promptTitle, promptMessage, false, '', confirmButtonText, confirmButtonType);

    if (isConfirmed === null) {
        console.log("DEBUG: Action annulée par l'utilisateur.");
        return;
    }

    const actionButton = event.target;
    const originalButtonHtml = actionButton.innerHTML;
    actionButton.disabled = true;
    actionButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';

    try {
        const response = await fetch('/api/v1/handle-signalement-api/index.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ signalement_id: signalementId, action: action, treated_by_id: loggedInUserId })
        });
        const data = await response.json();

        console.log("DEBUG: Réponse API handle-signalement reçue:", data);

        if (response.ok && data.success) {
            showSignalementsMessage(data.message || `Signalement ${action === 'process' ? 'traité' : 'rejeté'} avec succès.`, 'success');
            loadSignalements( // Recharger la liste
                statusFilterSelect.value,
                reporterFilterInput.value.trim(),
                reportedFilterInput.value.trim()
            );
        } else {
            showSignalementsMessage(data.message || `Erreur lors du ${action === 'process' ? 'traitement' : 'rejet'} du signalement.`, 'error');
        }
    } catch (error) {
        console.error('DEBUG: Erreur réseau ou du serveur (handleSignalementAction):', error);
        showSignalementsMessage('Erreur réseau lors de l\'action. Veuillez vérifier votre connexion.', 'error');
    } finally {
        actionButton.disabled = false;
        actionButton.innerHTML = originalButtonHtml;
    }
}


// --- Exécution initiale au chargement de la page ---
document.addEventListener('DOMContentLoaded', () => {
    console.log("DEBUG: DOMContentLoaded déclenché.");
    // Références des éléments qui peuvent être créés après le DOMContentLoaded initial
    // Ces références sont maintenant des const globales en haut du fichier.

    // Écouteurs pour les filtres
    if (applyFiltersButton) {
        applyFiltersButton.addEventListener('click', () => {
            console.log("DEBUG: applyFiltersButton cliqué.");
            loadSignalements(
                statusFilterSelect.value,
                reporterFilterInput.value.trim(),
                reportedFilterInput.value.trim()
            );
        });
    } else {
        console.warn("DEBUG: Le bouton 'applyFiltersButton' est introuvable.");
    }


    // Chargement initial des signalements
    loadSignalements(); // Appel initial
});