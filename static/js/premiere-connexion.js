document.addEventListener('DOMContentLoaded', () => {
    const registerForm = document.getElementById('registerForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const acceptTermsCheckbox = document.getElementById('acceptTerms');
    const errorMessageDiv = document.getElementById('errorMessage');
    const successMessageDiv = document.getElementById('successMessage');

    // --- Fonctions pour afficher/masquer les mots de passe (deux champs) ---
    window.togglePasswordVisibility = function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
    };

    window.togglePasswordVisibilityConfirm = function() {
        const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        confirmPasswordInput.setAttribute('type', type);
    };

    // --- Fonction pour afficher un message d'erreur ---
    function showErrorMessage(message) {
        successMessageDiv.style.display = 'none'; // Cacher le succès
        errorMessageDiv.textContent = message;
        errorMessageDiv.style.display = 'block';
        errorMessageDiv.style.opacity = '0';
        void errorMessageDiv.offsetWidth; // Force le reflow pour que l'animation se rejoue
        errorMessageDiv.style.opacity = '1';
    }

    // --- Fonction pour masquer les messages d'erreur ---
    function hideMessages() {
        errorMessageDiv.style.display = 'none';
        errorMessageDiv.textContent = '';
        successMessageDiv.style.display = 'none';
        successMessageDiv.textContent = '';
    }

    // --- Fonction pour afficher un message de succès ---
    function showSuccessMessage(message) {
        errorMessageDiv.style.display = 'none'; // Cacher l'erreur
        successMessageDiv.textContent = message;
        successMessageDiv.style.display = 'block';
        successMessageDiv.style.opacity = '0';
        void successMessageDiv.offsetWidth;
        successMessageDiv.style.opacity = '1';
    }


    // Gestion de la soumission du formulaire
    if (registerForm) {
        registerForm.addEventListener('submit', async (event) => {
            event.preventDefault(); // Empêche la soumission classique du formulaire

            hideMessages(); // Masquer tout message précédent

            // Récupérer les valeurs des champs
            const nom = document.getElementById('nom').value.trim();
            const prenom = document.getElementById('prenom').value.trim();
            const matricule = document.getElementById('matricule').value.trim();
            const service = document.getElementById('service').value.trim();
            const email = emailInput.value.trim();
            const password = passwordInput.value.trim();
            const confirmPassword = confirmPasswordInput.value.trim();

            // --- Validation côté client ---
            if (!nom || !prenom || !matricule || !service || !email || !password || !confirmPassword) {
                showErrorMessage("Veuillez remplir tous les champs.");
                return;
            }

            if (password !== confirmPassword) {
                showErrorMessage("Les mots de passe ne correspondent pas.");
                passwordInput.value = ''; // Efface les mots de passe pour ressaisie
                confirmPasswordInput.value = '';
                return;
            }

            if (password.length < 8) { // Exemple: exiger une longueur minimale
                showErrorMessage("Le mot de passe doit contenir au moins 8 caractères.");
                passwordInput.value = '';
                confirmPasswordInput.value = '';
                return;
            }

            if (!acceptTermsCheckbox.checked) {
                showErrorMessage("Vous devez accepter les mentions légales et la politique de confidentialité.");
                return;
            }

            // Désactiver le bouton de soumission et ajouter un indicateur de chargement
            const submitButton = registerForm.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi...';

            try {
                const response = await fetch(registerForm.action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ nom, prenom, matricule, service, email, password })
                });

                const data = await response.json(); // Supposons que l'API renvoie du JSON

                if (response.ok) { // Le statut HTTP est 2xx
                    if (data.success) {
                        showSuccessMessage(data.message || 'Votre demande a été soumise avec succès !');
                        registerForm.reset(); // Vider le formulaire après succès
                        // Optionnel: rediriger après quelques secondes
                        setTimeout(() => {
                            window.location.href = '/se-connecter';
                        }, 3000); // Rediriger après 3 secondes
                    } else {
                        showErrorMessage(data.message || 'Une erreur est survenue lors de la soumission de votre demande.');
                    }
                } else {
                    showErrorMessage(data.message || 'Une erreur de serveur est survenue lors de la soumission.');
                    console.error('Erreur API:', response.status, data);
                }
            } catch (error) {
                console.error('Erreur réseau ou du serveur:', error);
                showErrorMessage('Impossible de se connecter au serveur. Veuillez vérifier votre connexion.');
            } finally {
                // Réactiver le bouton de soumission
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            }
        });
    }
});