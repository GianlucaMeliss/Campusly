document.addEventListener('DOMContentLoaded', () => {
    // Seleziona tutti gli elementi con la classe .fade-in-up
    const animatedElements = document.querySelectorAll('.fade-in-up');

    // Crea l'osservatore
    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            // Se l'elemento entra nella visuale dello schermo
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                // Smette di osservare l'elemento dopo l'animazione
                observer.unobserve(entry.target);
            }
        });
    }, {
        root: null,
        rootMargin: '0px',
        threshold: 0.15 // L'animazione parte quando il 15% dell'elemento è visibile
    });

    // Applica l'osservatore a ogni elemento
    animatedElements.forEach(el => observer.observe(el));
});