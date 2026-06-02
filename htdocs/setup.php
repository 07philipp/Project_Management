<?php
// Function to check the operating system
function getOperatingSystem() {
    return strtolower(PHP_OS);
}

// Get the absolute path of the backup script
$backupScript = realpath(dirname(__FILE__)) . '/../backup_cron.php';
$setupSuccess = false;
$errorMessage = '';

try {
    $os = getOperatingSystem();

    // Check if it's a Linux-based system (including Synology)
    if (strpos($os, 'linux') !== false || strpos($os, 'synology') !== false) {
        setupLinuxCronJob($backupScript);
        $setupSuccess = true;
    } 
    // Handle Windows-based systems
    elseif (strpos($os, 'win') !== false) {
        setupWindowsTaskScheduler($backupScript);
        $setupSuccess = true;
    } 
    // Unknown or unsupported operating system
    else {
        $errorMessage = 'Unsupported operating system: ' . $os;
    }
} catch (Exception $e) {
    $errorMessage = 'Error: ' . $e->getMessage();
}

// Function to set up a cron job on Linux/Synology
function setupLinuxCronJob($backupScript) {
    // Befehl, um den Cronjob hinzuzufügen
    $command = "* * * * * /usr/bin/php $backupScript >/dev/null 2>&1";
    
    // Prüfe, ob es sich um Synology handelt
    $isSynology = false;
    $uname = php_uname();
    
    // Synology-Systeme haben in der Regel "synology" in der Ausgabe von uname
    if (strpos(strtolower($uname), 'synology') !== false || is_dir('/etc.defaults/synoinfo.conf')) {
        $isSynology = true;
    }

    // Bestehende Crontab-Einträge abrufen
    exec('crontab -l', $output);

    // Prüfen, ob der Cronjob bereits existiert
    $cronExists = false;
    foreach ($output as $line) {
        if (strpos($line, $backupScript) !== false) {
            $cronExists = true;
            break;
        }
    }

    // Wenn der Cronjob noch nicht existiert, hinzufügen
    if (!$cronExists) {
        exec("(crontab -l ; echo \"$command\") | crontab -");
        echo "Der Cronjob für das Backup wurde erfolgreich geplant!\n";
        
        // Wenn es sich um ein Synology-System handelt, zusätzliche Hinweise
        if ($isSynology) {
            echo "Hinweis: Da Sie ein Synology NAS verwenden, kann es notwendig sein, den Task Scheduler in DSM zu verwenden, um Cron-Jobs dauerhaft zu planen.\n";
            echo "Bitte überprüfen Sie in DSM unter Systemsteuerung -> Aufgabenplaner, ob der Cron-Job korrekt erstellt wurde.\n";
        }
    } else {
        echo "Der Cronjob ist bereits geplant.\n";

        // Synology-spezifischer Hinweis
        if ($isSynology) {
            echo "Da Sie ein Synology NAS verwenden, stellen Sie sicher, dass der Task im Aufgabenplaner sichtbar ist.\n";
        }
    }
}


// Function to set up Windows Task Scheduler
function setupWindowsTaskScheduler($backupScript) {
    // Create a batch file that runs the backup script
    $batFile = realpath(dirname(__FILE__)) . '\\run_backup.bat';
    file_put_contents($batFile, "php $backupScript");

    // Command to create a scheduled task
    $taskName = "Automated Backup Task";
    $command = "schtasks /create /tn \"$taskName\" /tr \"$batFile\" /sc hourly";

    // Execute the command
    exec($command, $output, $returnCode);

    if ($returnCode !== 0) {
        throw new Exception('Failed to create task: ' . implode("\n", $output));
    }

    echo "Windows Task Scheduler has been set up successfully!\n";
}

// Output result
if ($setupSuccess) {
    echo "Setup completed successfully.";
} else {
    echo $errorMessage;
}
