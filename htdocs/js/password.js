function validatePasswords() {
    const pw = document.getElementById("pw").value;
    const pw2 = document.getElementById("pw2").value;
    const message = document.getElementById("password-message");
    
    if (pw !== pw2) {
        message.textContent = "Passwörter stimmen nicht überein";
        message.style.color = "red";
    }else{
        message.textContent = "Passwörter sind gültig";
        message.style.color = "green";
    }
}

function togglePassword() {
    const pw1 = document.getElementById("pw");
    const pw2 = document.getElementById("pw2");
    const icon1 = document.getElementById("togglePw1");

    if (pw1.type === "password") {
        pw1.type = "text";
        pw2.type = "text";
        icon1.textContent = "🔒"; // Schloss zu
    } else {
        pw1.type = "password";
        pw2.type = "password";
        icon1.textContent = "👁"; // Auge offen
    }
}

function togglePasswordLogin() {
    const pw1 = document.getElementById("password");
    const icon1 = document.getElementById("togglePw1");

    if (pw1.type === "password") {
        pw1.type = "text";
        icon1.textContent = "🔒"; // Schloss zu
    } else {
        pw1.type = "password";
        icon1.textContent = "👁"; // Auge offen
    }
}