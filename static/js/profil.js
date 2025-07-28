document.addEventListener('DOMContentLoaded', () => {
    // Éléments du formulaire de mise à jour de profil
    const profileUpdateForm = document.getElementById('profileUpdateForm');
    const profileMessageDiv = document.getElementById('profileMessage');

    // Éléments du formulaire de changement de mot de passe
    const passwordChangeForm = document.getElementById('passwordChangeForm');
    const passwordMessageDiv = document.getElementById('passwordMessage');
    const currentPasswordInput = document.getElementById('currentPassword');
    const newPasswordInput = document.getElementById('newPassword');
    const confirmNewPasswordInput = document.getElementById('confirmNewPassword');

    // --- Fonction pour afficher/masquer le mot de passe (réutilisée) ---
    window.togglePasswordVisibility = function(fieldId) {
        const inputField = document.getElementById(fieldId);
        const type = inputField.getAttribute('type') === 'password' ? 'text' : 'password';
        inputField.setAttribute('type', type);
        // L'icône visuelle reste fa-eye-slash fixée en CSS
    };

    // --- Fonctions pour gérer les messages (profil) ---
    function showProfileMessage(message, type = 'error') {
        profileMessageDiv.textContent = message;
        profileMessageDiv.className = 'profile-message ' + type;
        profileMessageDiv.style.display = 'block';
        profileMessageDiv.style.opacity = '0';
        void profileMessageDiv.offsetWidth;
        profileMessageDiv.style.opacity = '1';
    }

    function hideProfileMessage() {
        profileMessageDiv.style.display = 'none';
        profileMessageDiv.textContent = '';
        profileMessageDiv.className = 'profile-message';
    }

    // --- Fonctions pour gérer les messages (mot de passe) ---
    function showPasswordMessage(message, type = 'error') {
        passwordMessageDiv.textContent = message;
        passwordMessageDiv.className = 'password-message ' + type;
        passwordMessageDiv.style.display = 'block';
        passwordMessageDiv.style.opacity = '0';
        void passwordMessageDiv.offsetWidth;
        passwordMessageDiv.style.opacity = '1';
    }

    function hidePasswordMessage() {
        passwordMessageDiv.style.display = 'none';
        passwordMessageDiv.textContent = '';
        passwordMessageDiv.className = 'password-message';
    }

    // --- Gestion de la soumission du formulaire de mise à jour de profil ---
    if (profileUpdateForm) {
        profileUpdateForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            hideProfileMessage();

            const formData = new FormData(profileUpdateForm);
            const data = Object.fromEntries(formData.entries());

            // Simple validation
            if (!data.email || !data.nom || !data.prenom || !data.matricule || !data.service) {
                showProfileMessage("Veuillez remplir tous les champs.");
                return;
            }

            const submitButton = profileUpdateForm.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mise à jour...';

            try {
                const response = await fetch(profileUpdateForm.action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const responseData = await response.json();

                if (response.ok && responseData.success) {
                    showProfileMessage(responseData.message || 'Profil mis à jour avec succès !', 'success');
                } else {
                    showProfileMessage(responseData.message || 'Erreur lors de la mise à jour du profil.', 'error');
                }
            } catch (error) {
                console.error('Erreur réseau ou du serveur (profil):', error);
                showProfileMessage('Impossible de se connecter au serveur. Veuillez vérifier votre connexion.', 'error');
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            }
        });
    }

    // --- Gestion de la soumission du formulaire de changement de mot de passe ---
    if (passwordChangeForm) {
        passwordChangeForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            hidePasswordMessage();

            const currentPassword = currentPasswordInput.value.trim();
            const newPassword = newPasswordInput.value.trim();
            const confirmNewPassword = confirmNewPasswordInput.value.trim();

            if (!currentPassword || !newPassword || !confirmNewPassword) {
                showPasswordMessage("Veuillez remplir tous les champs de mot de passe.");
                return;
            }
            if (newPassword !== confirmNewPassword) {
                showPasswordMessage("Les nouveaux mots de passe ne correspondent pas.", 'error');
                newPasswordInput.value = '';
                confirmNewPasswordInput.value = '';
                return;
            }
            if (newPassword.length < 8) {
                showPasswordMessage("Le nouveau mot de passe doit contenir au moins 8 caractères.", 'error');
                newPasswordInput.value = '';
                confirmNewPasswordInput.value = '';
                return;
            }
            if (newPassword === currentPassword) {
                showPasswordMessage("Le nouveau mot de passe doit être différent de l'ancien.", 'error');
                newPasswordInput.value = '';
                confirmNewPasswordInput.value = '';
                return;
            }

            const formData = new FormData(passwordChangeForm);
            const data = Object.fromEntries(formData.entries());

            const submitButton = passwordChangeForm.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Changement...';

            try {
                const response = await fetch(passwordChangeForm.action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const responseData = await response.json();

                if (response.ok && responseData.success) {
                    showPasswordMessage(responseData.message || 'Mot de passe changé avec succès !', 'success');
                    passwordChangeForm.reset(); // Vider les champs
                } else {
                    showPasswordMessage(responseData.message || 'Erreur lors du changement de mot de passe.', 'error');
                }
            } catch (error) {
                console.error('Erreur réseau ou du serveur (changement mdp):', error);
                showPasswordMessage('Impossible de se connecter au serveur. Veuillez vérifier votre connexion.', 'error');
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            }
        });
    }
});