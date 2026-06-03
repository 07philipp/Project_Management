function searchProject(inputField, clomum) {
    const filter = inputField.value;
    const table = document.getElementById('projectTable');
    const tr = table.getElementsByTagName('tr');

    for (let i = 1; i < tr.length; i++) {
        const tdColum = tr[i].getElementsByTagName('td')[clomum];

        if (tdColum) {
            const textColum = tdColum.textContent || tdColum.innerText;

            if (textColum === filter || '' === filter) {
                tr[i].style.display = '';
            } else {
                tr[i].style.display = 'none';
            }
        }
    }
}

function filterProjects(inputField) {
    const filter = inputField.value.toLowerCase();
    const table = document.getElementById('projectTable');
    const tr = table.getElementsByTagName('tr');

    for (let i = 1; i < tr.length; i++) {
        const tdId = tr[i].getElementsByTagName('td')[0];
        const tdName = tr[i].getElementsByTagName('td')[1];

        if (tdId || tdName) {
            const textId = tdId.textContent || tdId.innerText;
            const textName = tdName.textContent || tdName.innerText;

            // Filtere nur nach Projekt-ID und Projektname
            if (textId.toLowerCase().indexOf(filter) > -1 || textName.toLowerCase().indexOf(filter) > -1) {
                tr[i].style.display = '';
            } else {
                tr[i].style.display = 'none';
            }
        }
    }
}

function searchProjectField(inputField, id) {
    const filter = inputField.value.toLowerCase();
    const options = document.getElementById(id).options;

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

function clearProjectFilter() {
    // Setze das Suchfeld zurück
    document.getElementById('projectSearchInput').value = '';

    // Setze das Auswahlfeld zurück
    document.getElementById('client_name_select').value = '';
    document.getElementById('user_name_select').value = '';

    // Zeige alle Projektzeilen wieder an
    const rows = document.querySelectorAll('.project-row');
    rows.forEach(row => {
        row.style.display = ''; // Zeige die Zeile an
    });

    const form = document.getElementById("filterForm");

    const checkboxes = form.querySelectorAll('input[type="checkbox"]');

    checkboxes.forEach(cb => {
        cb.checked = false;
    });

    form.submit();
}


function extractFirstNumber(str) {
    const match = str.match(/\d+/);
    return match ? parseInt(match[0], 10) : 0;
}

function parseGermanDate(dateStr) {
    dateStr = dateStr.trim();
    if (!dateStr || dateStr === '---') return null;

    const parts = dateStr.split('.');
    if (parts.length !== 3) return null;

    return new Date(parts[2], parts[1] - 1, parts[0]);
}

function parseProjectId(id) {
    const match = id.match(/A(\d+)\/\s*(\d+)/);

    if (!match) return { group: 0, number: 0 };

    return {
        group: parseInt(match[1]),
        number: parseInt(match[2])
    };
}

function sortProjects(mode) {
    const table = document.getElementById('projectTable');
    if (!table) return;

    // Holt alle Zeilen außer der Kopfzeile
    const rows = Array.from(table.querySelectorAll('tr')).slice(1);

    rows.sort((a, b) => {
        const tdA = a.getElementsByTagName('td');
        const tdB = b.getElementsByTagName('td');

        // ID sortieren (Alphanumerisch & Intelligent)
        if (mode === 'id_asc' || mode === 'id_desc') {
            const idA = tdA[0].textContent.trim();
            const idB = tdB[0].textContent.trim();

            // localeCompare mit numeric: true sortiert "A2", "A10", "A11" etc. absolut korrekt
            return mode === 'id_asc'
                ? idA.localeCompare(idB, undefined, { numeric: true, sensitivity: 'base' })
                : idB.localeCompare(idA, undefined, { numeric: true, sensitivity: 'base' });
        }

        // Name sortieren
        if (mode === 'name_asc' || mode === 'name_desc') {
            const nameA = tdA[1].textContent.trim().toLowerCase();
            const nameB = tdB[1].textContent.trim().toLowerCase();

            if (nameA < nameB) return mode === 'name_asc' ? -1 : 1;
            if (nameA > nameB) return mode === 'name_asc' ? 1 : -1;
            return 0;
        }

        // Datum sortieren
        if (mode === 'date_asc' || mode === 'date_desc') {
            const dateA = parseGermanDate(tdA[6].textContent);
            const dateB = parseGermanDate(tdB[6].textContent);

            if (!dateA && !dateB) return 0;
            if (!dateA) return 1;
            if (!dateB) return -1;

            return mode === 'date_asc' ? dateA - dateB : dateB - dateA;
        }

        return 0;
    });

    // Sortierte Zeilen wieder in die Tabelle einfügen
    rows.forEach(row => table.appendChild(row));
}
