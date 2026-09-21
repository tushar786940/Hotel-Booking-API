import Alpine from "alpinejs";

window.Alpine = Alpine;
Alpine.start();

// Flash message auto-dismiss
document.addEventListener("DOMContentLoaded", () => {
    const alerts = document.querySelectorAll("[data-auto-dismiss]");
    alerts.forEach((alert) => {
        const delay = parseInt(alert.dataset.autoDismiss) || 5000;
        setTimeout(() => {
            alert.style.transition = "opacity 0.5s, transform 0.5s";
            alert.style.opacity = "0";
            alert.style.transform = "translateY(-10px)";
            setTimeout(() => alert.remove(), 500);
        }, delay);
    });
});
