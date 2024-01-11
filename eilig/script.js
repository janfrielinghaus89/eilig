document.addEventListener('DOMContentLoaded', function () {

    // API-Key
    const apiKey = "<?php echo $_ENV['API_KEY']; ?>";

    const emailButton = document.getElementById('emailButton');
    const copyButton = document.getElementById('copyButton');
    const successLink = document.querySelector('.success-link span');
    const databaseButton = document.getElementById('databaseButton');

    emailButton.addEventListener('click', function () {
        // Konstruiere den E-Mail-Link
        const emailLink = 'mailto:?subject=Einmal-Link&body=Hier ist der Einmal-Link: ' + successLink;

        // Öffne den E-Mail-Client des Benutzers mit dem vorbereiteten E-Mail-Link
        window.location.href = emailLink;
    });

    copyButton.addEventListener('click', function () {
        const linkToCopy = successLink.textContent; // Hier wird der generierte Link aus success-link geholt
        const textArea = document.createElement('textarea');
        textArea.value = linkToCopy;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        alert('Link wurde in die Zwischenablage kopiert');
    });

    // Datenbank-Button vorhanden? Falls ja, mit Event-Listener versehen
    if (databaseButton) {
        databaseButton.addEventListener('click', function () {
            // Anhängen des API-Keys an den Link
            const apiUrl = 'datenbank.php?api_key=' + apiKey;

            // Leite den Benutzer auf die Datenbankseite weiter
            window.location.href = apiUrl;
        });
    }
});

function deleteRecord(identifier) {
    // Hier implementierst du den Code, um den Datensatz mit der übergebenen Identifier aus der Datenbank zu löschen
    console.log('Lösche Datensatz mit Identifier: ' + identifier);
}


