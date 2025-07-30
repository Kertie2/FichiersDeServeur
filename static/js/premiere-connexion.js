document.addEventListener('DOMContentLoaded', () => {
    const registerForm = document.getElementById('registerForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const acceptTermsCheckbox = document.getElementById('acceptTerms');
    const serviceSelect = document.getElementById('service'); // Le nouveau sélecteur de service
    const errorMessageDiv = document.getElementById('errorMessage');
    const successMessageDiv = document.getElementById('successMessage');

    // --- Fonction pour afficher/masquer les mots de passe (deux champs) ---
    window.togglePasswordVisibility = function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
    };

    window.togglePasswordVisibilityConfirm = function() {
        const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        confirmPasswordInput.setAttribute('type', type);
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
            errorMessageDiv.style.display = 'none';
        } else {
            errorMessageDiv.textContent = message;
            errorMessageDiv.className = 'error-message';
            errorMessageDiv.style.display = 'block';
            errorMessageDiv.style.opacity = '0';
            void errorMessageDiv.offsetWidth;
            errorMessageDiv.style.opacity = '1';
            successMessageDiv.style.display = 'none';
        }
    }

    function hideMessages() {
        errorMessageDiv.style.display = 'none';
        errorMessageDiv.textContent = '';
        successMessageDiv.style.display = 'none';
        successMessageDiv.textContent = '';
    }

    // --- Fonction pour charger les services depuis l'API ---
    async function loadServices() {
        try {
            const response = await fetch('/api/v1/services-list-api/index.php');
            const data = await response.json();

            if (response.ok && data.success && data.services) {
                // Vider les options existantes sauf la première ("Sélectionnez...")
                serviceSelect.innerHTML = '<option value="">Sélectionnez votre service</option>';
                data.services.forEach(service => {
                    const option = document.createElement('option');
                    option.value = service;
                    option.textContent = service;
                    serviceSelect.appendChild(option);
                });
            } else {
                showMessage(data.message || 'Impossible de charger la liste des services. Veuillez réessayer.', 'error');
                // Optionnel: désactiver le sélecteur si les services ne peuvent pas être chargés
                serviceSelect.disabled = true;
            }
        } catch (error) {
            console.error('Erreur réseau lors du chargement des services:', error);
            showMessage('Erreur réseau lors du chargement des services. Veuillez vérifier votre connexion.', 'error');
            serviceSelect.disabled = true;
        }
    }

    // Charger les services au chargement de la page
    if (serviceSelect) { // S'assurer que l'élément select existe
        loadServices();
    }


    // Gestion de la soumission du formulaire
    if (registerForm) {
        registerForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            hideMessages();

            const nom = document.getElementById('nom').value.trim();
            const prenom = document.getElementById('prenom').value.trim();
            const matricule = document.getElementById('matricule').value.trim();
            const service = serviceSelect.value; // Récupère la valeur du sélecteur
            const email = emailInput.value.trim();
            const password = passwordInput.value.trim();
            const confirmPassword = confirmPasswordInput.value.trim();

            // Validation côté client
            if (!nom || !prenom || !matricule || !service || !email || !password || !confirmPassword) {
                showMessage("Veuillez remplir tous les champs.");
                return;
            }

            if (service === "") { // S'assurer qu'un service a été sélectionné
                showMessage("Veuillez sélectionner votre service.");
                return;
            }

            if (password !== confirmPassword) {
                showMessage("Les mots de passe ne correspondent pas.");
                passwordInput.value = '';
                confirmPasswordInput.value = '';
                return;
            }

            if (password.length < 8) {
                showMessage("Le mot de passe doit contenir au moins 8 caractères.");
                passwordInput.value = '';
                confirmPasswordInput.value = '';
                return;
            }

            if (!acceptTermsCheckbox.checked) {
                showMessage("Vous devez accepter les mentions légales et la politique de confidentialité.");
                return;
            }

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

                const data = await response.json();

                if (response.ok) {
                    if (data.success) {
                        showMessage(data.message || 'Votre demande a été soumise avec succès !', 'success');
                        registerForm.reset();
                        // Rediriger après quelques secondes
                        setTimeout(() => {
                            window.location.href = '/se-connecter';
                        }, 3000);
                    } else {
                        showMessage(data.message || 'Une erreur est survenue lors de la soumission de votre demande.', 'error');
                    }
                } else {
                    showMessage(data.message || 'Une erreur de serveur est survenue lors de la soumission.', 'error');
                    console.error('Erreur API:', response.status, data);
                }
            } catch (error) {
                console.error('Erreur réseau ou du serveur:', error);
                showMessage('Impossible de se connecter au serveur. Veuillez vérifier votre connexion.', 'error');
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            }
        });
    }
});