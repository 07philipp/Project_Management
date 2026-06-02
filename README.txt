README.txt - Projekt- und Kundenmanagement-System
Inhalt:
Allgemeine Voraussetzungen
Installation auf Windows mit XAMPP
Installation auf Linux
Installation auf Synology NAS
SQL-Server und Datenbank-Setup
Ordnerstruktur und Dateien einrichten
Setup über setup.php
1. Allgemeine Voraussetzungen
PHP (mindestens Version 7.0 oder höher)
Apache Webserver (bei XAMPP enthalten)
MySQL- oder MariaDB-Server (bei XAMPP oder Linux enthalten)
Server-Dateien:
htdocs (Webseite)
vendor (PHP-Abhängigkeiten)
backup_cron.php (Backup-Skript)
config.php (Konfigurationsdatei)
setup.php (Automatisches Setup-Skript)
Datenbank-Backup oder SQL-Skript für die Datenbankstruktur
2. Installation auf Windows mit XAMPP
Voraussetzungen:
XAMPP
PHP-Version und MySQL in XAMPP aktiv
Server-Dateien (htdocs, vendor, backup_cron.php, config.php, setup.php)
Schritte:
XAMPP installieren: Lade XAMPP herunter und installiere es. Stelle sicher, dass während der Installation Apache und MySQL ausgewählt sind.

XAMPP starten:

Öffne das XAMPP Control Panel.
Starte die Apache- und MySQL-Module.
Dateien in XAMPP-Webverzeichnis kopieren:

Kopiere den Inhalt von htdocs in das Webverzeichnis von XAMPP (C:\xampp\htdocs).
Kopiere den vendor-Ordner ebenfalls nach C:\xampp\vendor.
Kopiere backup_cron.php, config.php und setup.php in das Verzeichnis C:\xampp\htdocs.
MySQL-Datenbank einrichten: Die Einrichtung der Datenbank wird automatisch über das Setup-Skript setup.php vorgenommen, daher musst du die Datenbank nicht manuell erstellen.

3. Installation auf Linux (Apache, MySQL, PHP)
Schritte:
Apache, MySQL und PHP installieren: Für Debian/Ubuntu:

bash
Code kopieren
sudo apt update
sudo apt install apache2 mysql-server php libapache2-mod-php php-mysql
Dateien ins Webverzeichnis kopieren:

Kopiere den Inhalt von htdocs nach /var/www/html.
Kopiere den vendor-Ordner ebenfalls nach /var/www/vendor.
Kopiere backup_cron.php, config.php und setup.php in das Verzeichnis /var/www/html.
Dateiberechtigungen anpassen:

bash
Code kopieren
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html
4. Installation auf Synology NAS
Schritte:
Web Station aktivieren und PHP/MariaDB installieren:

Öffne das Paket-Zentrum und installiere "Web Station", PHP (Version 7 oder höher) und MariaDB.
Dateien auf Synology NAS kopieren:

Lade den Inhalt des htdocs-Ordners nach /web.
Kopiere den vendor-Ordner ebenfalls nach /web/vendor.
Lade backup_cron.php, config.php und setup.php in den Ordner /web.
Datenbank einrichten: Die Einrichtung der Datenbank wird durch setup.php durchgeführt, also keine manuelle Einrichtung notwendig.

5. SQL-Server und Datenbank-Setup
Die MySQL-Datenbank wird durch das setup.php-Skript erstellt und konfiguriert, daher musst du nur sicherstellen, dass MySQL oder MariaDB auf deinem Server läuft und zugänglich ist.

6. Ordnerstruktur und Dateien einrichten
Die folgende Ordnerstruktur sollte verwendet werden:

lua
Code kopieren
/server
│
├── /htdocs(linux = html)--> Alle Dateien der Webseite (Frontend/Backend)
│   ├── index.php        --> Hauptdatei für den Server
│   └── ...
│
├── /vendor              --> PHP-Abhängigkeiten (über Composer)
│   └── ...
│
├── /backups             --> Optional: Backups der Webseite/Datenbank
│   └── ...
│
├── backup_cron.php      --> PHP-Skript zur Sicherung der Datenbank
├── config.php           --> Konfigurationsdatei für Datenbankverbindung
├── setup.php            --> Automatisches Setup-Skript zur Initialisierung der Datenbank und Konfiguration
└── sql_backup.sql       --> Optional: SQL-Datenbank-Backup-Datei
7. Setup über setup.php
Automatische Einrichtung:
Setup aufrufen:

Öffne deinen Webbrowser und rufe das Setup-Skript auf:
arduino
Code kopieren
http://localhost/setup.php
oder (bei Synology NAS):
perl
Code kopieren
http://<NAS-IP>/setup.php
Setup ausführen:

Das Skript erstellt automatisch die benötigte Datenbank und richtet die erforderlichen Tabellen ein.
Es wird auch die config.php Datei entsprechend mit deinen MySQL-Zugangsdaten und weiteren Parametern konfigurieren.
Folge den Anweisungen auf der Webseite, um die Einrichtung abzuschließen.
Setup-Skript löschen:

Nach erfolgreicher Installation solltest du die Datei setup.php aus Sicherheitsgründen löschen. Beispiel für XAMPP:
bash
Code kopieren
del C:\xampp\htdocs\setup.php
Beispiel für Linux:
bash
Code kopieren
sudo rm /var/www/html/setup.php