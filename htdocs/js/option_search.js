function searchFields(inputField) {
    const filter = inputField.value.toLowerCase();
    const options = document.getElementById('client_id').options;

    for (let i = 0; i < options.length; i++) {
        const option = options[i];
        const text = option.text.toLowerCase();

        if (text.indexOf(filter) > -1) {
            option.style.display = '';
        } else {
            option.style.display = 'none';
        }
    }
}

function searchUser(inputField) {
    const filter = inputField.value.toLowerCase();
    const options = document.getElementById('user_id').options;

    for (let i = 0; i < options.length; i++) {
        const option = options[i];
        const text = option.text.toLowerCase();

        if (text.indexOf(filter) > -1) {
            option.style.display = '';
        } else {
            option.style.display = 'none';
        }
    }
}

