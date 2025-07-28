document.addEventListener('DOMContentLoaded', () => {
    console.log("Page d'accueil de l'intranet chargée. Bienvenue !");

    // Vous pouvez ajouter ici des fonctionnalités JavaScript
    // par exemple, pour des graphiques, des notifications en temps réel,
    // ou des interactions avec d'autres APIs.

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
});