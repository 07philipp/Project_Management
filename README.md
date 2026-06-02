
# Projekt- und Kundenmanagement-System

Ein maßgeschneidertes, webbasiertes System zur Verwaltung von Projekten, Kunden, Zeiterfassungen und automatisierten Abrechnungen. Diese Anwendung basiert auf nativem PHP und einer relationalen SQL-Datenbank (PDO) und wurde umfassend auf moderne Sicherheits- und Stabilitätsstandards optimiert.

---

## Architektur und Ordnerstruktur

Das System folgt einer strikten Trennung von öffentlich zugänglichen Webseiten-Dateien, System-Konfigurationen und externen Abhängigkeiten:


```

/server
│
├── /htdocs (Linux: /html)   --> Öffentlich erreichbare Web-Dateien (Frontend & Backend)
│   ├── /admin               --> Administrative Dashboards und KPI-Auswertungen
│   ├── /clients             --> Ansichten und Funktionen zur Kundenverwaltung
│   ├── /css                 --> Stylesheets für das User Interface
│   ├── /edit_client         --> Bearbeitungsmasken für Kundendaten
│   ├── /edit_project        --> Projektbearbeitung und Detailansichten
│   ├── /edit_time           --> Verwaltung und Korrektur von Zeiterfassungseinträgen
│   ├── /js                  --> JavaScript-Dateien (AJAX-Wechsel, Formular-Validierung)
│   ├── /log                 --> System-Log-Anzeigen für administrative Überwachungen
│   ├── /login               --> Authentifizierungs-Schnittstelle
│   ├── /projects            --> Hauptübersichten der Projektlisten
│   ├── /register            --> Benutzerregistrierung und Rechtevergabe
│   ├── /settings            --> Konfigurationsmenüs der Anwendung
│   ├── /users               --> Benutzerverwaltung
│   ├── /write_bill          --> Aggregierte Vorschau zur Rechnungserstellung
│   ├── index.php            --> Zentrales Dashboard und primärer Einstiegspunkt
│   ├── logout.php           --> Sichere Beendigung der Benutzersitzung
│   └── session_loguot.php   --> Sitzungsverwaltung und Session-Terminierung
│
├── /vendor                  --> PHP-Abhängigkeiten und externe Bibliotheken (z. B. dompdf)
├── /backups                 --> Lokales Verzeichnis für automatisierte Datenbanksicherungen
├── backup_cron.php          --> Hintergrund-Skript zur automatisierten Datenbanksicherung
├── config.php               --> Zentrale Konfigurationsdatei für die Datenbank-Zugangsdaten
├── setup.php                --> Automatisiertes Initialisierungs- und Installationsskript
└── sql_backup.sql           --> SQL-Schema und optionales Backup für die Tabellenstruktur

```

---

## Technische Optimierung und Sicherheits-Hardening (Refactoring)

Die Kern-Logik der Anwendung wurde in einer umfassenden Refactoring-Phase auf Enterprise-Niveau gehoben, um gängige Angriffsvektoren vollständig zu eliminieren:

* **Schutz vor SQL-Injections:** Über 40 ehemals string-interpolierte SQL-Abfragen in den Kern-Modulen (Projekt-, Kunden-, Zeiterfassungs- und Upload-Verwaltung) wurden auf sichere **PDO Prepared Statements** umgestellt. Benutzereingaben werden strikt von der Ausführungslogik der Datenbank getrennt.
* **Cross-Site Scripting (XSS) Prävention:** Dynamische HTML-Ausgaben in über 17 View-Dateien werden konsistent über die native Maskierungsfunktion `h()` abgesichert. Variablen-Übergaben innerhalb von JavaScript-Kontexten werden über `pm_json_script()` serialisiert, um Schadcode-Injektionen im Browser zu verhindern.
* **Zentralisierung und DRY-Prinzip:** Redundante Code-Blöcke (wie die mehrfache Überprüfung und Erstellung von Systemeinstellungen oder Berechtigungsprüfungen) wurden in performante Helper-Funktionen innerhalb der `mysql.php` ausgelagert.
* **Architektur-Bereinigung:** Das Protokollierungssystem (`log.php`) wurde strukturell in Library- und Controller-Ebenen getrennt. Dies verhindert Berechtigungskonflikte bei API- und Hintergrundaufrufen durch Nicht-Admins, während direkte administrative Zugriffe über strikte Tabellen-Whitelists geschützt bleiben.
* **Stabilitäts- und Bugfixes:** Logikfehler im Validierungsprozess der Projekterstellung (`add_project.php`) sowie Pluralisierungsfehler beim Word-Export (`generate_word.php`) wurden vollständig behoben. Inaktive Code-Reste und Debug-Artefakte wurden restlos entfernt.

---

## Installationsanleitung

### 1. Allgemeine Systemvoraussetzungen
- PHP (Version 7.0 oder höher)
- Apache Webserver (inklusive aktiviertem `mod_rewrite`)
- MySQL- oder MariaDB-Datenbankserver

### 2. Einrichtung unter Windows (mit XAMPP)
1. Starten Sie das XAMPP Control Panel und aktivieren Sie die Module **Apache** und **MySQL**.
2. Kopieren Sie den Inhalt des Ordners `htdocs` in das XAMPP-Webverzeichnis (`C:\xampp\htdocs`).
3. Platzieren Sie den Ordner `vendor` sowie die Dateien `backup_cron.php`, `config.php` und `setup.php` direkt im selben Verzeichnis.

### 3. Einrichtung unter Linux (Debian / Ubuntu)
1. Installieren Sie die benötigten Pakete über den Paketmanager:
   ```bash
   sudo apt update
   sudo apt install apache2 mysql-server php libapache2-mod-php php-mysql

```

2. Kopieren Sie die Web-Dateien nach `/var/www/html` und den `vendor`-Ordner nach `/var/www/vendor`.
3. Passen Sie die Dateiberechtigungen für den Webserver-Nutzer an:
```bash
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html

```



### 4. Einrichtung auf einer Synology NAS

1. Installieren Sie über das Paket-Zentrum die **Web Station**, **PHP** sowie **MariaDB**.
2. Laden Sie den Inhalt des `htdocs`-Ordners sowie die Dateien `backup_cron.php`, `config.php` und `setup.php` in das Verzeichnis `/web`.
3. Kopieren Sie den Ordner `vendor` nach `/web/vendor`.

---

## Ersteinrichtung über das Setup-Skript

Die Erstellung der Datenbanktabellen und die Konfiguration der Systemparameter erfolgt vollautomatisch:

1. Rufen Sie das Installationsskript über Ihren Webbrowser auf:
```
http://localhost/setup.php

```


*(Ersetzen Sie `localhost` bei Server- oder NAS-Installationen durch die entsprechende IP-Adresse).*
2. Folgen Sie den Anweisungen auf der Benutzeroberfläche. Das Skript initialisiert die Datenbankstruktur und generiert die finale `config.php`.
3. **Wichtig:** Löschen Sie die Datei `setup.php` nach erfolgreicher Installation umgehend aus Sicherheitsgründen vom Server.


