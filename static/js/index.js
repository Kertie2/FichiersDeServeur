document.addEventListener('DOMContentLoaded', () => {
    console.log("Page de l'intranet chargée. Bienvenue !");

    // Exemple simple : animation du logo au survol
    const sidebarLogo = document.querySelector('.sidebar-logo');
    if (sidebarLogo) {
        sidebarLogo.addEventListener('mouseover', () => {
            sidebarLogo.style.transform = 'scale(1.1)';
            sidebarLogo.style.transition = 'transform 0.3s ease';
        });
        sidebarLogo.addEventListener('mouseout', () => {
            sidebarLogo.style.transform = 'scale(1)';
        });
    }

    // --- Variables et Fonctions pour la Modale Générique (maintenant dans index.js) ---
    // Ces éléments doivent exister globalement dans le HTML de index.php pour que cela fonctionne.
    // Assurez-vous que la modale générique est définie UNE SEULE FOIS dans index.php ou une vue commune.
    const genericConfirmModal = document.getElementById('genericConfirmModal');
    const closeGenericModalButton = document.getElementById('closeGenericModalButton');
    const genericModalTitle = document.getElementById('genericModalTitle');
    const genericModalMessage = document.getElementById('genericModalMessage');
    const genericModalInputGroup = document.getElementById('genericModalInputGroup');
    const genericModalInput = document.getElementById('genericModalInput');
    const genericModalConfirmButton = document.getElementById('genericModalConfirmButton');
    const genericModalCancelButton = document.getElementById('genericModalCancelButton');
    const genericModalFeedback = document.getElementById('genericModalFeedback'); // Message dans la modale générique

    if (genericConfirmModal) { // S'assurer que la modale est présente dans le HTML
        // Définie globalement pour être accessible par les autres scripts
        window.showCustomActionModal = function(title, message, showInput = false, inputPlaceholder = '', confirmButtonText = 'Confirmer', confirmButtonType = 'primary-button') {
            return new Promise((resolve) => {
                genericModalTitle.textContent = title;
                genericModalMessage.textContent = message;
                genericModalConfirmButton.textContent = confirmButtonText;
                genericModalConfirmButton.className = 'action-button ' + confirmButtonType; // Appliquer le type de bouton
                genericModalFeedback.style.display = 'none'; // Cacher les anciens messages de la modale générique

                if (showInput) {
                    genericModalInputGroup.style.display = 'flex'; // Afficher le groupe d'input
                    genericModalInput.placeholder = inputPlaceholder;
                    genericModalInput.value = ''; // Vider le champ d'input
                    genericModalInput.focus();
                } else {
                    genericModalInputGroup.style.display = 'none';
                }

                // Afficher la modale générique
                genericConfirmModal.classList.add('active');

                const handleConfirm = () => {
                    const inputValue = showInput ? genericModalInput.value.trim() : true; // true pour une simple confirmation
                    genericConfirmModal.classList.remove('active');
                    removeEventListeners();
                    resolve(inputValue); // Résoudre la promesse avec la valeur de l'input ou true
                };

                const handleCancel = () => {
                    genericConfirmModal.classList.remove('active');
                    removeEventListeners();
                    resolve(null); // Résoudre la promesse avec null si annulé
                };

                const removeEventListeners = () => {
                    genericModalConfirmButton.removeEventListener('click', handleConfirm);
                    genericModalCancelButton.removeEventListener('click', handleCancel);
                    if (closeGenericModalButton) closeGenericModalButton.removeEventListener('click', handleCancel); // Vérifier l'existence
                    genericConfirmModal.removeEventListener('click', overlayClickHandler);
                };

                const overlayClickHandler = (event) => {
                    if (event.target === genericConfirmModal) {
                        handleCancel();
                    }
                };

                genericModalConfirmButton.addEventListener('click', handleConfirm);
                genericModalCancelButton.addEventListener('click', handleCancel);
                if (closeGenericModalButton) closeGenericModalButton.addEventListener('click', handleCancel); // Vérifier l'existence
                genericConfirmModal.addEventListener('click', overlayClickHandler);
            });
        };

        // Définie globalement pour être accessible par les autres scripts
        window.showGenericModalFeedback = function(message, type = 'error') {
            if (!genericModalFeedback) return; // S'assurer que l'élément existe

            genericModalFeedback.textContent = message;
            genericModalFeedback.className = 'modal-message ' + type;
            genericModalFeedback.style.display = 'block';
            genericModalFeedback.style.opacity = '0';
            void genericModalFeedback.offsetWidth;
            genericModalFeedback.style.opacity = '1';
        };

        // Assurez-vous que la modale générique est présente dans votre index.php principal
        // juste avant le script index.js.
        // Exemple HTML (à mettre dans index.php ou un fichier commun inclus par index.php):
        // <div id="genericConfirmModal" class="modal-overlay">... (structure de la modale) ...</div>
    } else {
        console.warn("Modale générique #genericConfirmModal introuvable. Les fonctions showCustomActionModal et showGenericModalFeedback ne seront pas disponibles.");
    }
    // --- FIN Modale Générique ---
});