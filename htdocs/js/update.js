function updateProgressBar(percentage, barId, textId) {
    const progressBar = document.getElementById(barId);
    const progressText = document.getElementById(textId);

    progressBar.style.width = percentage + '%';
    progressText.textContent = percentage + '%';

    // Farbwerte für Rot, Gelb, Grün
    let red, green, blue;

    if (percentage <= 50) {
        // Übergang von Rot (255, 0, 0) zu Gelb (255, 255, 0)
        red = 255;
        green = Math.round(255 * (percentage / 50));
        blue = 0;
    } else {
        // Übergang von Gelb (255, 255, 0) zu deinem spezifischen Grün (39, 174, 96)
        red = Math.round(255 * (1 - (percentage - 50) / 50));
        green = Math.round(255 * (1 - (percentage - 50) / 50) + 174 * ((percentage - 50) / 50));
        blue = Math.round(96 * ((percentage - 50) / 50));
    }

    // Stelle sicher, dass bei 100% das Grün exakt #27ae60 ist
    if (percentage >= 100) {
        red = 39;
        green = 174;
        blue = 96;
    }

    progressBar.style.backgroundColor = `rgb(${red}, ${green}, ${blue})`;
}
