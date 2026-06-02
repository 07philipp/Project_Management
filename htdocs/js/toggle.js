function toggleProjectForm() {
    var form = document.getElementById("projectForm");
    var form2 = document.getElementById("clientForm");
    form.style.display = (form.style.display === "none") ? "block" : "none";
    form2.style.display = "none";
}

function toggleClientForm() {
    var form = document.getElementById("clientForm");
    var form2 = document.getElementById("projectForm");
    form.style.display = (form.style.display === "none") ? "block" : "none";
    form2.style.display = "none";
}

function toggleNewClientFields() {
    const select = document.getElementById('client_id');
    const newClientFields = document.getElementById('newClientFields');
    if (select.value === 'neu') {
        newClientFields.style.display = 'block';
    } else {
        newClientFields.style.display = 'none';
    }
}



