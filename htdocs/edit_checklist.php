<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: ../login/");
    exit;
} else {
    define('INCLUDE_GUARD', true);
}

// Berechtigungsprüfung
if ($_SESSION["permission_level"] == 2) {
    // Projekt-ID holen (wird je nach Berechtigung geprüft oder direkt gesetzt)
    $project_id = $_POST['project_id'] ?? $_POST['id'] ?? null;
    if (!$project_id) {
        echo json_encode(['error' => 'Missing project_id']);
        exit;
    }
    include("mysql.php");
    $stmt = $mysql->prepare("SELECT project_user_id FROM project WHERE project_id = :project_id");
    $stmt->execute([':project_id' => $project_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data || $data['project_user_id'] != $_SESSION['user_id']) {
        echo json_encode(['error' => 'Keine Berechtigung für dieses Projekt.']);
        exit;
    }
} elseif ($_SESSION["user_id"] == 1) {
    // User-ID 1 hat keine Rechte (z. B. Testnutzer?)
    echo "<script>
        window.history.back();
        console.log('Keine Berechtigung.');
    </script>";
    exit;
} else {
    // Admins, PMs usw. (Level 3+): dürfen alles
    include("mysql.php");
}

require_once("log.php");

$action = $_GET['action'] ?? $_POST['action'] ?? null;

switch ($action) {
    case 'edit_settings':
        if (isset($_POST['add_global_todo'])){
            $newTodo = trim($_POST['new_global_todo'] ?? '');
            $applyToAll = isset($_POST['apply_global_todos']);
        
            if ($newTodo === '') {
                echo json_encode(['error' => 'Kein Todo angegeben']);
                exit;
            }
        
            // Aktuelle Todos laden
            $stmt = $mysql->prepare("SELECT setting FROM settings WHERE setting_type = 'global_todos'");
            $stmt->execute();
            $todosRaw = $stmt->fetchColumn();
            $todos = json_decode($todosRaw, true) ?? [];
            $id = time();
        
            // Neuen Eintrag hinzufügen
            $todos[] = ['id' => $id, 'name' => $newTodo];
        
            // In settings speichern
            $update = $mysql->prepare("UPDATE settings SET setting = :setting WHERE setting_type = 'global_todos'");
            $update->execute([':setting' => json_encode($todos)]);

            if(isset($_POST['apply_global_todos'])){
                // Optional in allen Projekten anlegen
                if ($applyToAll) {
                    $stmt = $mysql->query("SELECT project_id FROM project");
                    $projects = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
                    $checklist_id = time(); // oder uniqid()
                    $insert = $mysql->prepare("INSERT INTO checklist (checklist_id, project_id, checklist_name, is_done, is_global)
                                            VALUES (:id, :project, :title, 0, 1)");
                    foreach ($projects as $pid) {
                        $insert->execute([
                            ':id' => $checklist_id,
                            ':project' => $pid,
                            ':title' => $newTodo
                        ]);
                    }
                }
            }
        }elseif (isset($_POST['delete_global_todo'])){
            $deleteId = $_POST['delete_global_todo'] ?? null;

            // Todos laden
            $stmt = $mysql->prepare("SELECT setting FROM settings WHERE setting_type = 'global_todos'");
            $stmt->execute();
            $todos = json_decode($stmt->fetchColumn(), true) ?? [];

            // Todo mit passender ID suchen und löschen
            foreach ($todos as $index => $todo) {
                if ($todo['id'] == $deleteId) {
                    $deletedTodoName = $todo['name'];
                    unset($todos[$index]);
                    break;
                }
            }
        
            $todos = array_values($todos); // Reindizieren
        
            $update = $mysql->prepare("UPDATE settings SET setting = :setting WHERE setting_type = 'global_todos'");
            $update->execute([':setting' => json_encode($todos)]);


            if(isset($_POST['apply_global_todos'])){
                // Alle globalen checklist-Einträge mit diesem Titel löschen
                $delete = $mysql->prepare("DELETE FROM checklist WHERE is_global = 1 AND checklist_id = :id");
                $delete->execute([':id' => $deleteId]);
            }
        }

        header("Location: settings/");
        exit;
    
    case 'create_project':
        $project_id = $_POST['project_id'] ?? $_POST['id'] ?? null;
        $title = $_POST['title'] ?? null;
        if (!$title || !$project_id) {
            echo json_encode(['error' => 'Missing title or project_id']);
            exit;
        }

        $checklist_id = time();

        $insert = $mysql->prepare("INSERT INTO checklist (checklist_id, project_id, checklist_name, is_done, is_global)
                                 VALUES (:id, :project, :title, 0, 0)");
        $insert->execute([
            ':id' => $checklist_id,
            ':project' => $project_id,
            ':title' => $title
        ]);

        echo json_encode(['success' => true, 'checklist_id' => $checklist_id]);
        header("Location: projects/?id=$project_id");
        break;

    case 'delete_project':
        $project_id = $_POST['project_id'] ?? $_POST['id'] ?? null;
        $checklist_id = $_POST['checklist_id'] ?? null;
        if (!$checklist_id || !$project_id) {
            echo json_encode(['error' => 'Missing checklist_id or project_id']);
            exit;
        }

        $stmt = $mysql->prepare("DELETE FROM checklist WHERE checklist_id = :id AND project_id = :project");
        $stmt->execute([':id' => $checklist_id, ':project' => $project_id]);

        echo json_encode(['success' => true]);
        header("Location: projects/?id=$project_id");
        break;

    case 'edit':
        $checklist_id = $_POST['checklist_id'] ?? null;
        $project_id = $_POST['project_id'] ?? $_POST['id'] ?? null;

        if (!$checklist_id || !$project_id ) {
            echo json_encode(['error' => 'Missing checklist_id or project_id']);
            exit;
        }

        $stmt = $mysql->prepare("SELECT is_done FROM checklist WHERE checklist_id = :id AND project_id = :project");
        $stmt->execute([
            ':id' => $checklist_id,
            ':project' => $project_id
        ]);
        $check = $stmt->fetchColumn();

        $stmt = $mysql->prepare("UPDATE checklist SET is_done = :done WHERE checklist_id = :id AND project_id = :project");
        $stmt->execute([
            ':done' => !$check,
            ':id' => $checklist_id,
            ':project' => $project_id
        ]);

        echo json_encode(['success' => true]);
        header("Location: projects/?id=$project_id");
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
}
