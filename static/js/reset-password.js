document.addEventListener('DOMContentLoaded', () => {
    const resetPasswordForm = document.getElementById('resetPasswordForm');
    const resetTokenInput = document.getElementById('resetToken');
    const newPasswordInput = document.getElementById('newPassword');
    const confirmNewPasswordInput = document.getElementById('confirmNewPassword');
    const errorMessageDiv = document.getElementById('errorMessage');
    const successMessageDiv = document.getElementById('successMessage');

    // --- Fonction pour afficher/masquer le mot de passe ---
    window.togglePasswordVisibility = function(fieldId) {
        const inputField = document.getElementById(fieldId);
        const type = inputField.getAttribute('type') === 'password' ? 'text' : 'password';
        inputField.setAttribute('type', type);
        // L'icône visuelle reste fa-eye-slash fixée en CSS
    };

    // --- Fonctions pour gérer les messages ---
    function showMessage(message, type = 'error') {
        if (type === 'success') {
            successMessageDiv.textContent = message;
            successMessageDiv.className = 'success-message';
            successMessageDiv.style.display = 'block';
            successMessageDiv.style.opacity = '0';
            void successMessageDiv.offsetWidth;
            successMessageDiv.style.opacity = '1';
            errorMessageDiv.style.display = 'none'; // Cacher l'erreur si succès
        } else { // type = 'error'
            errorMessageDiv.textContent = message;
            errorMessageDiv.className = 'error-message';
            errorMessageDiv.style.display = 'block';
            errorMessageDiv.style.opacity = '0';
            void errorMessageDiv.offsetWidth;
            errorMessageDiv.style.opacity = '1';
            successMessageDiv.style.display = 'none'; // Cacher le succès si erreur
        }
    }

    function hideMessages() {
        errorMessageDiv.style.display = 'none';
        errorMessageDiv.textContent = '';
        successMessageDiv.style.display = 'none';
        successMessageDiv.textContent = '';
    }

    // --- Extraction du token de l'URL ---
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('token');

    if (!token) {
        showMessage("Token de réinitialisation manquant ou invalide. Veuillez vérifier votre lien.", 'error');
        // Optionnel: Désactiver le formulaire si pas de token
        if(resetPasswordForm) resetPasswordForm.style.pointerEvents = 'none';
        return; // Arrête l'exécution du script si pas de token
    } else {
        resetTokenInput.value = token; // Injecte le token dans le champ caché
    }

    // --- Gestion de la soumission du formulaire ---
    if (resetPasswordForm) {
        resetPasswordForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            hideMessages();

            const newPassword = newPasswordInput.value.trim();
            const confirmNewPassword = confirmNewPasswordInput.value.trim();
            const currentToken = resetTokenInput.value; // Le token déjà injecté

            // Validation côté client
            if (!newPassword || !confirmNewPassword) {
                showMessage("Veuillez remplir tous les champs de mot de passe.", 'error');
                return;
            }
            if (newPassword !== confirmNewPassword) {
                showMessage("Les mots de passe ne correspondent pas.", 'error');
                newPasswordInput.value = '';
                confirmNewPasswordInput.value = '';
                return;
            }
            if (newPassword.length < 8) {
                showMessage("Le mot de passe doit contenir au moins 8 caractères.", 'error');
                newPasswordInput.value = '';
                confirmNewPasswordInput.value = '';
                return;
            }

            // Désactiver le bouton de soumission et ajouter un indicateur de chargement
            const submitButton = resetPasswordForm.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Réinitialisation...';

            try {
                const response = await fetch(resetPasswordForm.action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ token: currentToken, new_password: newPassword })
                });

                const data = await response.json();

                if (response.ok) {
                    if (data.success) {
                        showMessage(data.message || 'Votre mot de passe a été réinitialisé avec succès ! Vous pouvez maintenant vous connecter.', 'success');
                        resetPasswordForm.reset(); // Vider les champs
                        // Optionnel: Rediriger vers la page de connexion après un délai
                        setTimeout(() => {
                            window.location.href = '/se-connecter';
                        }, 4000);
                    } else {
                        showMessage(data.message || 'La réinitialisation a échoué. Le lien pourrait être invalide ou expiré.', 'error');
                    }
                } else {
                    showMessage(data.message || 'Une erreur de serveur est survenue lors de la réinitialisation.', 'error');
                    console.error('Erreur API (Reset Password):', response.status, data);
                }
            } catch (error) {
                console.error('Erreur réseau ou du serveur (Reset Password):', error);
                showMessage('Impossible de se connecter au serveur. Veuillez vérifier votre connexion.', 'error');
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            }
        });
    }
});