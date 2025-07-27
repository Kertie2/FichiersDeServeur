document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const errorMessageDiv = document.getElementById('errorMessage');

    // --- Fonction pour afficher/masquer le mot de passe (sans changer l'icône visuellement) ---
    window.togglePasswordVisibility = function() {
        if (passwordInput.getAttribute('type') === 'password') {
            passwordInput.setAttribute('type', 'text');
        } else {
            passwordInput.setAttribute('type', 'password');
        }
        // L'icône visuelle ne change plus ici, elle est fixée en CSS
    };

    // --- Fonction pour afficher un message d'erreur ---
    function showErrorMessage(message) {
        errorMessageDiv.textContent = message;
        errorMessageDiv.style.display = 'block';
        errorMessageDiv.style.opacity = '0';
        void errorMessageDiv.offsetWidth; // Force le reflow pour que l'animation se rejoue
        errorMessageDiv.style.opacity = '1';
    }

    // --- Fonction pour masquer le message d'erreur ---
    function hideErrorMessage() {
        errorMessageDiv.style.display = 'none';
        errorMessageDiv.textContent = '';
    }

    // --- Gestion de la soumission du formulaire ---
    if (loginForm) {
        loginForm.addEventListener('submit', async (event) => {
            event.preventDefault(); // Empêche la soumission classique du formulaire

            hideErrorMessage(); // Masquer tout message d'erreur précédent

            const email = emailInput.value.trim();
            const password = passwordInput.value.trim();

            if (!email || !password) {
                showErrorMessage("Veuillez remplir tous les champs.");
                return; // Plus besoin de gérer les classes has-content ici
            }

            // Désactiver le bouton de soumission et ajouter un indicateur de chargement
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

                const data = await response.json(); // Supposons que l'API renvoie du JSON

                if (response.ok) { // Le statut HTTP est 2xx
                    if (data.success) {
                        console.log('Connexion réussie:', data.message);
                        window.location.href = '/dashboard'; // Remplacez par votre URL de redirection
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
                // Réactiver le bouton de soumission
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            }
        });
    }
});