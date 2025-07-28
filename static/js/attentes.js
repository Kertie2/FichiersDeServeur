document.addEventListener('DOMContentLoaded', () => {
    const accountDetailModal = document.getElementById('accountDetailModal');
    const closeDetailModalButton = document.getElementById('closeDetailModalButton');
    const viewDetailsButtons = document.querySelectorAll('.view-details-button');
    const modalActionMessageDiv = document.getElementById('modalActionMessage');
    // Note: validateButton et rejectButton seront maintenant récupérés APRÈS le chargement du contenu dynamique.
    // const validateButton = document.querySelector('.action-button.validate-button'); // Sera mis à jour
    // const rejectButton = document.querySelector('.action-button.reject-button'); // Sera mis à jour

    let currentUserId = null; // Pour stocker l'ID de l'utilisateur actuellement dans la modale

    // --- Fonctions de gestion des messages de la modale ---
    function showModalMessage(message, type = 'error') {
        modalActionMessageDiv.textContent = message;
        modalActionMessageDiv.className = 'modal-message ' + type;
        modalActionMessageDiv.style.display = 'block';
        modalActionMessageDiv.style.opacity = '0';
        void modalActionMessageDiv.offsetWidth;
        modalActionMessageDiv.style.opacity = '1';
    }

    function hideModalMessage() {
        modalActionMessageDiv.style.display = 'none';
        modalActionMessageDiv.textContent = '';
        modalActionMessageDiv.className = 'modal-message';
    }

    // --- Gestion de l'ouverture de la modale de détails ---
    viewDetailsButtons.forEach(button => {
        button.addEventListener('click', async (event) => {
            currentUserId = event.target.dataset.userId; // Récupère l'ID de l'utilisateur
            hideModalMessage(); // Cache les messages précédents de la modale

            const modalBodyContent = accountDetailModal.querySelector('.modal-body-content');
            // Afficher le spinner ou un message de chargement DANS modalBodyContent
            modalBodyContent.innerHTML = '<div style="text-align:center; padding:50px;"><i class="fas fa-spinner fa-spin fa-3x" style="color:var(--primary-color);"></i><p style="color:var(--secondary-color);">Chargement des détails...</p></div>';
            
            accountDetailModal.classList.add('active'); // Affiche la modale (avec le spinner)

            try {
                // Fetch les détails complets de l'utilisateur depuis une API
                const response = await fetch(`/api/v1/attentes-detail-api/index.php?id=${currentUserId}`);
                const data = await response.json();

                if (response.ok && data.success && data.user) {
                    const user = data.user;
                    // Construire tout le contenu HTML avec les données utilisateur
                    const detailHtml = `
                        <div class="detail-grid">
                            <div class="detail-item"><strong>ID:</strong> <span>${user.id ? htmlEscape(user.id) : 'N/A'}</span></div>
                            <div class="detail-item"><strong>Email:</strong> <span>${user.email ? htmlEscape(user.email) : 'N/A'}</span></div>
                            <div class="detail-item"><strong>Nom:</strong> <span>${user.nom ? htmlEscape(user.nom) : 'N/A'}</span></div>
                            <div class="detail-item"><strong>Prénom:</strong> <span>${user.prenom ? htmlEscape(user.prenom) : 'N/A'}</span></div>
                            <div class="detail-item"><strong>Matricule:</strong> <span>${user.matricule ? htmlEscape(user.matricule) : 'N/A'}</span></div>
                            <div class="detail-item"><strong>Service:</strong> <span>${user.service ? htmlEscape(user.service) : 'N/A'}</span></div>
                            <div class="detail-item"><strong>Date Demande:</strong> <span>${user.date_demande ? htmlEscape(user.date_demande) : 'N/A'}</span></div>
                        </div>
                        <div class="modal-actions">
                            <button class="action-button validate-button" data-action="validate">Valider ce compte</button>
                            <button class="action-button reject-button" data-action="reject">Refuser ce compte</button>
                        </div>
                        <div id="modalActionMessage" class="modal-message"></div>
                    `;
                    // Insérer le HTML construit dans la modale
                    modalBodyContent.innerHTML = detailHtml;

                    // Ré-attacher les écouteurs d'événements aux NOUVEAUX boutons Valider/Refuser
                    const newValidateButton = modalBodyContent.querySelector('.action-button.validate-button');
                    const newRejectButton = modalBodyContent.querySelector('.action-button.reject-button');
                    if (newValidateButton) newValidateButton.addEventListener('click', handleUserAction);
                    if (newRejectButton) newRejectButton.addEventListener('click', handleUserAction);

                    // Mettre à jour la référence du modalActionMessageDiv si elle a été écrasée
                    // (Si modalActionMessage n'était pas dans la modal-body-content initialement, il faudra ajuster)
                    // Pour l'instant, on suppose qu'il est recréé et la variable est mise à jour implicitement.

                } else {
                    const message = data.message || 'Erreur lors du chargement des détails du compte.';
                    modalBodyContent.innerHTML = `<div style="text-align:center; padding:50px; color:var(--error-color);">${htmlEscape(message)}</div>`;
                    showModalMessage(message, 'error');
                }
            } catch (error) {
                console.error('Erreur réseau ou du serveur (détails):', error);
                const errorMessage = 'Erreur réseau lors du chargement des détails.';
                modalBodyContent.innerHTML = `<div style="text-align:center; padding:50px; color:var(--error-color);">${htmlEscape(errorMessage)}</div>`;
                showModalMessage(errorMessage, 'error');
            }
        });
    });

    // --- Fonction d'échappement HTML pour prévenir le XSS lors de l'injection de données ---
    function htmlEscape(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }


    // --- Gestion de la fermeture de la modale ---
    if (closeDetailModalButton) {
        closeDetailModalButton.addEventListener('click', () => {
            accountDetailModal.classList.remove('active');
            hideModalMessage();
        });
    }

    // Fermer la modale en cliquant sur l'overlay
    if (accountDetailModal) {
        accountDetailModal.addEventListener('click', (event) => {
            if (event.target === accountDetailModal) {
                accountDetailModal.classList.remove('active');
                hideModalMessage();
            }
        });
    }

    // --- Gestion des actions Valider / Refuser ---
    async function handleUserAction(event) {
        const action = event.target.dataset.action; // 'validate' ou 'reject'
        if (!currentUserId) return;

        // Ré-obtenir la référence du message div de la modale car son contenu a pu être réécrit
        const currentModalActionMessageDiv = document.getElementById('modalActionMessage');
        function showCurrentModalMessage(msg, type) {
             currentModalActionMessageDiv.textContent = msg;
             currentModalActionMessageDiv.className = 'modal-message ' + type;
             currentModalActionMessageDiv.style.display = 'block';
             currentModalActionMessageDiv.style.opacity = '0';
             void currentModalActionMessageDiv.offsetWidth;
             currentModalActionMessageDiv.style.opacity = '1';
        }
        function hideCurrentModalMessage() {
            currentModalActionMessageDiv.style.display = 'none';
            currentModalActionMessageDiv.textContent = '';
            currentModalActionMessageDiv.className = 'modal-message';
        }

        hideCurrentModalMessage(); // Masquer tout message précédent dans la modale

        // Confirmation de l'action
        if (!confirm(`Êtes-vous sûr de vouloir ${action === 'validate' ? 'valider' : 'refuser'} ce compte ?`)) {
            return;
        }

        const actionButton = event.target;
        const originalButtonText = actionButton.innerHTML;
        actionButton.disabled = true;
        actionButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';

        try {
            let apiEndpoint = '';
            if (action === 'validate') {
                apiEndpoint = '/api/v1/validate-user-api/index.php';
            } else if (action === 'reject') {
                apiEndpoint = '/api/v1/reject-user-api/index.php';
            } else {
                showCurrentModalMessage('Action invalide.', 'error');
                return;
            }

            const response = await fetch(apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ user_id: currentUserId }) // user_id (et action est implicite par l'API)
            });

            const data = await response.json();

            if (response.ok && data.success) {
                showCurrentModalMessage(data.message || `Compte ${action === 'validate' ? 'validé' : 'refusé'} avec succès.`, 'success');
                // Recharger la page après succès pour mettre à jour la liste
                setTimeout(() => {
                    window.location.reload();
                }, 1500); // Recharge après 1.5 secondes
            } else {
                showCurrentModalMessage(data.message || `Erreur lors de l'action de ${action === 'validate' ? 'validation' : 'refus'}.`, 'error');
            }
        } catch (error) {
            console.error('Erreur réseau ou du serveur (action):', error);
            showCurrentModalMessage('Erreur réseau lors de l\'action. Veuillez vérifier votre connexion.', 'error');
        } finally {
            actionButton.disabled = false;
            actionButton.innerHTML = originalButtonText;
        }
    }
});