let notificationTimeout;

function showNotification(message) {
    var notification = document.getElementById("notification");
    var messageElement = document.getElementById("notification-message");
    var progressBar = document.getElementById("progress-bar");

    messageElement.textContent = message;
    notification.classList.add("show");

    // Starte den Fortschrittsbalken neu
    progressBar.style.animation = 'none';
    progressBar.offsetHeight; // Trigger reflow
    progressBar.style.animation = null;

    // Setze den Timeout auf 10 Sekunden
    notificationTimeout = setTimeout(hideNotification, 10000);
}

function hideNotification() {
    var notification = document.getElementById("notification");
    notification.classList.remove("show");
    clearTimeout(notificationTimeout);
}