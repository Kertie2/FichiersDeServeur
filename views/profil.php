<?php
// views/profil.php - Vue pour la page "Mon Profil"

// La variable $user est disponible ici car elle est définie dans index.php
// Nous allons préparer les données pour l'édition

$message = null;
$error = null;

// Gérer la soumission du formulaire de mise à jour de profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    // Les données seront envoyées via Fetch API vers /api/v1/profile-update-api/index.php
    // Ce bloc est ici juste pour indiquer qu'une action POST sur cette page pourrait être gérée
    // mais le JS enverra vers l'API, donc ce bloc n'est pas strictement nécessaire pour la logique API
}

// Les données de l'utilisateur sont déjà dans $user, récupérées par index.php
// Pour l'édition, on peut pré-remplir les champs
$userEmail = htmlspecialchars($user['email']);
$userNom = htmlspecialchars($user['nom']);
$userPrenom = htmlspecialchars($user['prenom']);
$userMatricule = htmlspecialchars($user['matricule']);
$userService = htmlspecialchars($user['service']);
?>

<section class="profile-details">
    <h3>Mes Informations de Profil</h3>
    <p class="section-description">Mettez à jour vos informations personnelles.</p>
    
    <div id="profileMessage" class="profile-message"></div>

    <form id="profileUpdateForm" action="/api/v1/profile-update-api/index.php" method="POST">
        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">
        
        <div class="input-group">
            <i class="fas fa-envelope icon"></i>
            <input type="email" id="profileEmail" name="email" placeholder="Adresse email" value="<?php echo $userEmail; ?>" required>
        </div>
        <div class="input-group">
            <i class="fas fa-user icon"></i>
            <input type="text" id="profileNom" name="nom" placeholder="Nom" value="<?php echo $userNom; ?>" required>
        </div>
        <div class="input-group">
            <i class="fas fa-user icon"></i>
            <input type="text" id="profilePrenom" name="prenom" placeholder="Prénom" value="<?php echo $userPrenom; ?>" required>
        </div>
        <div class="input-group">
            <i class="fas fa-id-card icon"></i>
            <input type="text" id="profileMatricule" name="matricule" placeholder="Matricule" value="<?php echo $userMatricule; ?>" required>
            <small class="field-hint">Le matricule est un identifiant unique.</small>
        </div>
        <div class="input-group">
            <i class="fas fa-building icon"></i>
            <input type="text" id="profileService" name="service" placeholder="Service" value="<?php echo $userService; ?>" required>
        </div>
        
        <button type="submit" class="profile-update-button">Mettre à jour le profil <i class="fas fa-save"></i></button>
    </form>
</section>

<section class="password-change">
    <h3>Changer mon mot de passe</h3>
    <p class="section-description">Utilisez ce formulaire pour modifier votre mot de passe.</p>

    <div id="passwordMessage" class="password-message"></div>

    <form id="passwordChangeForm" action="/api/v1/password-change-api/index.php" method="POST">
        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">

        <div class="input-group">
            <i class="fas fa-lock icon"></i>
            <input type="password" id="currentPassword" name="current_password" placeholder="Mot de passe actuel" required autocomplete="current-password">
            <span class="toggle-password" onclick="togglePasswordVisibility('currentPassword')"><i class="fas fa-eye-slash"></i></span>
        </div>
        <div class="input-group">
            <i class="fas fa-key icon"></i>
            <input type="password" id="newPassword" name="new_password" placeholder="Nouveau mot de passe" required autocomplete="new-password">
            <span class="toggle-password" onclick="togglePasswordVisibility('newPassword')"><i class="fas fa-eye-slash"></i></span>
        </div>
        <div class="input-group">
            <i class="fas fa-key icon"></i>
            <input type="password" id="confirmNewPassword" name="confirm_new_password" placeholder="Confirmer nouveau mot de passe" required autocomplete="new-password">
            <span class="toggle-password" onclick="togglePasswordVisibility('confirmNewPassword')"><i class="fas fa-eye-slash"></i></span>
        </div>

        <button type="submit" class="password-change-button">Changer le mot de passe <i class="fas fa-exchange-alt"></i></button>
    </form>
</section>