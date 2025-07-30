document.addEventListener('DOMContentLoaded', () => {
    // Éléments du formulaire de mise à jour de profil
    const profileUpdateForm = document.getElementById('profileUpdateForm');
    const profileMessageDiv = document.getElementById('profileMessage');
    const profileServiceSelect = document.getElementById('profileService'); // Le sélecteur de service

    // IMPORTANT : Récupérer le service actuel de l'utilisateur directement depuis l'attribut data-
    // Cet attribut DOIT être défini dans views/profil.php par PHP:
    // <select id="profileService" name="service" required data-current-service="<?php echo htmlspecialchars($user['service']); ?>">
    const userCurrentService = profileServiceSelect ? profileServiceSelect.dataset.currentService : '';

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

    // --- Fonction pour charger les services depuis l'API et pré-sélectionner ---
    async function loadProfileServices() {
        if (!profileServiceSelect) return; // Quitter si le sélecteur n'existe pas

        // userCurrentService est déjà défini au début du script à partir du dataset
        const currentService = userCurrentService; // Utiliser la variable déjà lue

        try {
            const response = await fetch('/api/v1/services-list-api/index.php');
            const data = await response.json();

            if (response.ok && data.success && data.services) {
                // Vider les options existantes
                profileServiceSelect.innerHTML = '';
                // Ajouter une option par défaut si le service actuel n'est pas "A Définir"
                // ou si vous voulez toujours une option par défaut non sélectionnée.
                // Si l'utilisateur est déjà dans un service, le mettre par défaut.
                if (!currentService || currentService === "A Définir" || !data.services.includes(currentService)) {
                    const defaultOption = document.createElement('option');
                    defaultOption.value = "";
                    defaultOption.textContent = "Sélectionnez votre service";
                    profileServiceSelect.appendChild(defaultOption);
                }

                data.services.forEach(service => {
                    const option = document.createElement('option');
                    option.value = service;
                    option.textContent = service;
                    if (service === currentService) { // Pré-sélectionner le service de l'utilisateur
                        option.selected = true;
                    }
                    profileServiceSelect.appendChild(option);
                });
            } else {
                showProfileMessage(data.message || 'Impossible de charger la liste des services. Veuillez réessayer.', 'error');
                profileServiceSelect.disabled = true;
            }
        } catch (error) {
            console.error('Erreur réseau lors du chargement des services de profil:', error);
            showProfileMessage('Erreur réseau lors du chargement des services. Veuillez vérifier votre connexion.', 'error');
            profileServiceSelect.disabled = true;
        }
    }

    // Charger les services au chargement de la page si le sélecteur est présent
    if (profileServiceSelect) {
        loadProfileServices();
    }


    // --- Gestion de la soumission du formulaire de mise à jour de profil ---
    if (profileUpdateForm) {
        profileUpdateForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            hideProfileMessage();

            const formData = new FormData(profileUpdateForm);
            const data = Object.fromEntries(formData.entries());

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