document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const errorMessageDiv = document.getElementById('errorMessage');

    // Nouveaux éléments pour la modale
    const forgotPasswordLink = document.getElementById('forgotPasswordLink');
    const forgotPasswordModal = document.getElementById('forgotPasswordModal');
    const closeModalButton = document.getElementById('closeModalButton');
    const forgotPasswordForm = document.getElementById('forgotPasswordForm');
    const resetEmailInput = document.getElementById('resetEmail');
    const resetMatriculeInput = document.getElementById('resetMatricule');
    const modalMessageDiv = document.getElementById('modalMessage');

    // --- Fonction pour afficher/masquer le mot de passe (sans changer l'icône visuellement) ---
    window.togglePasswordVisibility = function() {
        if (passwordInput.getAttribute('type') === 'password') {
            passwordInput.setAttribute('type', 'text');
        } else {
            passwordInput.setAttribute('type', 'password');
        }
    };

    // --- Fonctions pour gérer les messages sur la page principale ---
    function showErrorMessage(message) {
        errorMessageDiv.textContent = message;
        errorMessageDiv.style.display = 'block';
        errorMessageDiv.style.opacity = '0';
        void errorMessageDiv.offsetWidth; // Force le reflow pour que l'animation se rejoue
        errorMessageDiv.style.opacity = '1';
    }

    function hideErrorMessage() {
        errorMessageDiv.style.display = 'none';
        errorMessageDiv.textContent = '';
    }

    // --- Fonctions pour gérer les messages DANS la modale ---
    function showModalMessage(message, type = 'error') {
        modalMessageDiv.textContent = message;
        modalMessageDiv.className = 'modal-message ' + type; // Ajoute la classe 'error' ou 'success'
        modalMessageDiv.style.display = 'block';
        modalMessageDiv.style.opacity = '0';
        void modalMessageDiv.offsetWidth;
        modalMessageDiv.style.opacity = '1';
    }

    function hideModalMessage() {
        modalMessageDiv.style.display = 'none';
        modalMessageDiv.textContent = '';
        modalMessageDiv.className = 'modal-message'; // Réinitialise les classes
    }

    // --- Logique pour la modale "Mot de passe oublié" ---
    if (forgotPasswordLink && forgotPasswordModal && closeModalButton && forgotPasswordForm) {
        forgotPasswordLink.addEventListener('click', (event) => {
            event.preventDefault(); // Empêche le défilement vers le haut
            forgotPasswordModal.classList.add('active'); // Rend visible
            hideErrorMessage(); // Cache les messages de la page principale si la modale s'ouvre
            hideModalMessage(); // Cache les messages précédents de la modale
            resetEmailInput.focus(); // Met le focus sur le premier champ de la modale
        });

        closeModalButton.addEventListener('click', () => {
            forgotPasswordModal.classList.remove('active'); // Rend invisible
            forgotPasswordForm.reset(); // Réinitialise le formulaire de la modale
            hideModalMessage();
        });

        // Fermer la modale en cliquant sur l'overlay (en dehors du contenu de la modale)
        forgotPasswordModal.addEventListener('click', (event) => {
            if (event.target === forgotPasswordModal) { // Vérifie si le clic est sur l'overlay lui-même
                forgotPasswordModal.classList.remove('active');
                forgotPasswordForm.reset();
                hideModalMessage();
            }
        });

        forgotPasswordForm.addEventListener('submit', async (event) => {
            event.preventDefault(); // Empêche la soumission classique du formulaire

            hideModalMessage(); // Masquer tout message précédent dans la modale

            const email = resetEmailInput.value.trim();
            const matricule = resetMatriculeInput.value.trim();

            if (!email || !matricule) {
                showModalMessage("Veuillez remplir tous les champs.", 'error'); // Type 'error'
                return;
            }

            // Désactiver le bouton de soumission et ajouter un indicateur de chargement
            const submitButton = forgotPasswordForm.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi...';

            try {
                // Endpoint pour la réinitialisation de mot de passe
                const response = await fetch(forgotPasswordForm.action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ email, matricule })
                });

                const data = await response.json(); // Supposons que l'API renvoie du JSON

                if (response.ok) { // Le statut HTTP est 2xx
                    if (data.success) {
                        showModalMessage(data.message || 'Demande de réinitialisation envoyée. Veuillez vérifier votre email.', 'success');
                        forgotPasswordForm.reset(); // Vider le formulaire après succès
                    } else {
                        showModalMessage(data.message || 'Impossible de trouver un compte correspondant. Veuillez vérifier vos informations.', 'error');
                    }
                } else {
                    showModalMessage(data.message || 'Une erreur est survenue lors de l\'envoi de la demande. Veuillez réessayer.', 'error');
                    console.error('Erreur API (Réinitialisation):', response.status, data);
                }
            } catch (error) {
                console.error('Erreur réseau ou du serveur (Réinitialisation):', error);
                showModalMessage('Impossible de se connecter au serveur. Veuillez vérifier votre connexion.', 'error');
            } finally {
                // Réactiver le bouton de soumission
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            }
        });
    }

    // --- Gestion de la soumission du formulaire de connexion principal (code existant) ---
    if (loginForm) {
        loginForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            hideErrorMessage();

            const email = emailInput.value.trim();
            const password = passwordInput.value.trim();

            if (!email || !password) {
                showErrorMessage("Veuillez remplir tous les champs.");
                return;
            }

            const submitButton = loginForm.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Connexion...';

            try {
                const response = await fetch(loginForm.action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ email, password })
                });

                const data = await response.json();

                if (response.ok) {
                    if (data.success) {
                        console.log('Connexion réussie:', data.message);
                        window.location.href = '/dashboard';
                    } else {
                        showErrorMessage(data.message || 'Identifiants incorrects.');
                    }
                } else {
                    showErrorMessage(data.message || 'Une erreur est survenue lors de la connexion. Veuillez réessayer.');
                    console.error('Erreur API:', response.status, data);
                }
            } catch (error) {
                console.error('Erreur réseau ou du serveur:', error);
                showErrorMessage('Impossible de se connecter au serveur. Veuillez vérifier votre connexion.');
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            }
        });
    }
});