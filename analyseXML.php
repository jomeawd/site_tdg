<?php
class FormatError extends Exception {}

class TaskXMLParser {
    private SimpleXMLElement $xml;

    public function __construct(string $xmlFilePath) {
        $xml = self::lireFichierXML($xmlFilePath);
        if (!$xml) {
            throw new FormatError("Fichier non valide ou impossible à lire.");
        }

        $this->xml = $this->stripNamespace($xml);
    }

    public function parse(): array {
        return [
            "RegistrationInfo" => [
                "URI" => $this->get("RegistrationInfo/URI"),
                "SecurityDescriptor" => $this->get("RegistrationInfo/SecurityDescriptor"),
                "Source" => $this->get("RegistrationInfo/Source"),
                "Date" => $this->get("RegistrationInfo/Date"),
                "Author" => $this->get("RegistrationInfo/Author"),
                "Version" => $this->get("RegistrationInfo/Version"),
                "Description" => $this->get("RegistrationInfo/Description"),
                "Documentation" => $this->get("RegistrationInfo/Documentation"),
            ],
            "Principal" => [
                "Id" => $this->xml->Principals->Principal['id'] ?? '',
                "UserId" => $this->get("Principals/Principal/UserId"),
                "LogonType" => $this->get("Principals/Principal/LogonType"),
                "GroupId" => $this->get("Principals/Principal/GroupId"),
                "DisplayName" => $this->get("Principals/Principal/DisplayName"),
                "RunLevel" => $this->get("Principals/Principal/RunLevel"),
                "ProcessTokenSidType" => $this->get("Principals/Principal/ProcessTokenSidType"),
                "RequiredPrivileges" => $this->getList("Principals/Principal/RequiredPrivileges/Privilege")
            ],
            "Settings" => [
                "AllowStartOnDemand" => $this->get("Settings/AllowStartOnDemand"),
                "DisallowStartIfOnBatteries" => $this->get("Settings/DisallowStartIfOnBatteries"),
                "StopIfGoingOnBatteries" => $this->get("Settings/StopIfGoingOnBatteries"),
                "AllowHardTerminate" => $this->get("Settings/AllowHardTerminate"),
                "StartWhenAvailable" => $this->get("Settings/StartWhenAvailable"),
                "NetworkProfileName" => $this->get("Settings/NetworkProfileName"),
                "RunOnlyIfNetworkAvailable" => $this->get("Settings/RunOnlyIfNetworkAvailable"),
                "WakeToRun" => $this->get("Settings/WakeToRun"),
                "Enabled" => $this->get("Settings/Enabled"),
                "Hidden" => $this->get("Settings/Hidden"),
                "DeleteExpiredTaskAfter" => $this->get("Settings/DeleteExpiredTaskAfter"),
                "ExecutionTimeLimit" => $this->get("Settings/ExecutionTimeLimit"),
                "RunOnlyIfIdle" => $this->get("Settings/RunOnlyIfIdle"),
                "UseUnifiedSchedulingEngine" => $this->get("Settings/UseUnifiedSchedulingEngine"),
                "DisallowStartOnRemoteAppSession" => $this->get("Settings/DisallowStartOnRemoteAppSession"),
                "MultipleInstancesPolicy" => $this->get("Settings/MultipleInstancesPolicy"),
                "Priority" => $this->get("Settings/Priority"),
                "IdleSettings" => [
                    "Duration" => $this->get("Settings/IdleSettings/Duration"),
                    "WaitTimeout" => $this->get("Settings/IdleSettings/WaitTimeout"),
                    "StopOnIdleEnd" => $this->get("Settings/IdleSettings/StopOnIdleEnd"),
                    "RestartOnIdle" => $this->get("Settings/IdleSettings/RestartOnIdle")
                ],
                "NetworkSettings" => [
                    "Name" => $this->get("Settings/NetworkSettings/Name"),
                    "Id" => $this->get("Settings/NetworkSettings/Id")
                ],
                "RestartOnFailure" => [
                    "Interval" => $this->get("Settings/RestartOnFailure/Interval"),
                    "Count" => $this->get("Settings/RestartOnFailure/Count")
                ]
            ],
            "Triggers" => $this->getTriggers(),
            "Actions" => $this->getActions()
        ];
    }

    private function get(string $path): string {
        $element = $this->xml->xpath($this->xpathSafe($path));
        return !empty($element[0]) ? trim((string)$element[0]) : '';
    }

    private function getList(string $path): array {
        $elements = $this->xml->xpath($this->xpathSafe($path));
        return array_map(fn($el) => trim((string)$el), $elements);
    }

    private function xpathSafe(string $path): string {
        return implode('/', array_map(fn($tag) => "*[local-name()='$tag']", explode('/', $path)));
    }

    private function getTriggers(): array {
        $triggers = [];
        foreach ($this->xml->Triggers->children() as $trigger) {
            $t = [
                "Type" => $trigger->getName(),
                "Enabled" => (string)$trigger->Enabled,
                "StartBoundary" => (string)$trigger->StartBoundary,
                "EndBoundary" => (string)$trigger->EndBoundary,
                "ExecutionTimeLimit" => (string)$trigger->ExecutionTimeLimit
            ];
            if (isset($trigger->Delay)) $t["Delay"] = (string)$trigger->Delay;
            if (isset($trigger->UserId)) $t["UserId"] = (string)$trigger->UserId;
            if (isset($trigger->RandomDelay)) $t["RandomDelay"] = (string)$trigger->RandomDelay;
            if (isset($trigger->Repetition)) {
                $t["Repetition"] = [
                    "Interval" => (string)$trigger->Repetition->Interval,
                    "Duration" => (string)$trigger->Repetition->Duration
                ];
            }
            if (isset($trigger->ScheduleByDay)) {
                $t["ScheduleByDay"] = [
                    "DaysInterval" => (string)$trigger->ScheduleByDay->DaysInterval
                ];
            }
            if (isset($trigger->ScheduleByWeek)) {
                $t["ScheduleByWeek"] = [
                    "WeeksInterval" => (string)$trigger->ScheduleByWeek->WeeksInterval,
                    "DaysOfWeek" => (string)$trigger->ScheduleByWeek->DaysOfWeek
                ];
            }
            if (isset($trigger->ScheduleByMonth)) {
                $t["ScheduleByMonth"] = [
                    "Months" => (string)$trigger->ScheduleByMonth->Months,
                    "DaysOfMonth" => (string)$trigger->ScheduleByMonth->DaysOfMonth
                ];
            }
            $triggers[] = $t;
        }
        return $triggers;
    }

    private function getActions(): array {
        $actions = [];
        foreach ($this->xml->Actions->children() as $action) {
            $a = ["Type" => $action->getName()];
            if ($a["Type"] === "Exec") {
                $a["Command"] = (string)$action->Command;
                $a["Arguments"] = (string)$action->Arguments;
                $a["WorkingDirectory"] = (string)$action->WorkingDirectory;
            } elseif ($a["Type"] === "SendEmail") {
                $a["Server"] = (string)$action->Server;
                $a["Subject"] = (string)$action->Subject;
                $a["To"] = (string)$action->To;
                $a["From"] = (string)$action->From;
                $a["Body"] = (string)$action->Body;
            } elseif ($a["Type"] === "ShowMessage") {
                $a["Title"] = (string)$action->Title;
                $a["Body"] = (string)$action->Body;
            }
            $actions[] = $a;
        }
        return $actions;
    }

    private function stripNamespace(SimpleXMLElement $xml): SimpleXMLElement {
        $xmlStr = $xml->asXML();
        $xmlStr = preg_replace('/xmlns(:\w+)?="[^"]+"/', '', $xmlStr);
        return new SimpleXMLElement($xmlStr);
    }

    private static function lireFichierXML(string $xmlFilePath): SimpleXMLElement|false {
        try {
            $xmlContent = file_get_contents($xmlFilePath);
            if ($xmlContent === false) throw new Exception("Erreur lecture fichier");

            if (substr($xmlContent, 0, 3) === "\xEF\xBB\xBF") {
                $xmlContent = substr($xmlContent, 3);
            }

            $xmlContent = preg_replace('/<\?xml[^>]+encoding=["\']UTF-16["\']/i', '<?xml version="1.0" encoding="UTF-8"', $xmlContent);

            libxml_use_internal_errors(true);
            $xml = new SimpleXMLElement($xmlContent, LIBXML_NOWARNING);
            if (!$xml) {
                foreach (libxml_get_errors() as $error) {
                    echo "Erreur XML: " . $error->message . "\n";
                }
                libxml_clear_errors();
                return false;
            }
            return $xml;
        } catch (Exception $e) {
            echo "Erreur lors du chargement XML : " . $e->getMessage();
            return false;
        }
    }
}

$config = [
    'host'     => 'localhost',
    'username' => 'root',
    'password' => 'eqjbeop669BqTngza5AQ',
    'database' => 'gestion_taches_et_serveurs'
];

try 
{
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4",
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} 
catch (PDOException $e) 
{
    die("Erreur connexion DB : " . $e->getMessage());
}

function validDateTime(?string $dateStr)
{
    if (empty($dateStr)) 
	{
        return null;
    }
    $date = date_create($dateStr);
    if ($date == false) 
	{
        return null;
    }
    return $date->format('Y-m-d H:i:s');
}

function getOrCreateServerId(PDO $pdo, string $serverName)
{
    // Recherche serveur existant
    $stmt = $pdo->prepare("SELECT ser_id FROM serveur WHERE ser_nom = :nom");
    $stmt->execute(['nom' => $serverName]);
    $serId = $stmt->fetchColumn();

    if ($serId != false) {
        // Mettre à jour la date de modification du serveur existant
        $updateStmt = $pdo->prepare("UPDATE serveur SET ser_date_modification = NOW() WHERE ser_id = :id");
        $updateStmt->execute(['id' => $serId]);
        return (int)$serId;
    }

    // Insert serveur si non existant
    $stmtInsert = $pdo->prepare("INSERT INTO serveur (ser_nom, ser_date_creation, ser_date_modification, ser_collect_automatique) VALUES (:nom, NOW(), NOW(), 1)");
    $stmtInsert->execute(['nom' => $serverName]);

    return (int)$pdo->lastInsertId();
}

function shouldIncludeTask(array $taskData, string $serverName): bool
{
    $author = $taskData['RegistrationInfo']['Author'] ?? '';
    $userId = $taskData['Principal']['UserId'] ?? '';
    
    // Nettoyer les chaînes (supprimer espaces en début/fin)
    $author = trim($author);
    $userId = trim($userId);
    
    // Vérifier les critères pour Author ou UserId
    $validUsers = [
        function($user) {
            return stripos($user, 'NARBONNE\\') == 0;
        },
        function($user) use ($serverName) {
            // Extraire le numéro du serveur depuis le nom (ex: 00101SRVDMZ01 -> 101)
            if (preg_match('/^(\d{5})SRV/', $serverName, $matches)) {
                $serverNumber = $matches[1];
                return stripos($user, $serverNumber . 'SRV') == 0 && 
                       (stripos($user, '\\') != false || $user == $serverNumber . 'SRV' . substr($serverName, 8));
            }
            return false;
        },
        // Est remadm
        function($user) {
            return strtolower(trim($user)) === 'remadm';
        }
    ];
    
    // Vérifier Author
    foreach ($validUsers as $validator) {
        if ($validator($author)) {
            return true;
        }
    }
    
    // Vérifier UserId
    foreach ($validUsers as $validator) {
        if ($validator($userId)) {
            return true;
        }
    }
    
    return false;
}


function upsertTask(PDO $pdo, array $taskParams)
{
    // Vérifier si la tâche existe déjà (par nom et serveur)
    $checkStmt = $pdo->prepare("SELECT tac_id FROM tache WHERE tac_nom = :nom AND tac_ser_id = :ser_id");
    $checkStmt->execute([
        'nom' => $taskParams['nom'],
        'ser_id' => $taskParams['ser_id']
    ]);
    
    $existingTaskId = $checkStmt->fetchColumn();
    
    if ($existingTaskId != false) 
	{
        // Mise à jour de la tâche existante
        $sql = "UPDATE tache SET 
                    tac_description = :desc,
                    tac_date_creation = :date_creation,
                    tac_date_modification = NOW(),
                    tac_compte_utilisateur_execution = :compte_exec,
                    tac_compte_utilisateur_creation = :compte_creation,
                    tac_active = :active,
                    tac_collecte_automatique = 1
                WHERE tac_id = :tac_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'desc' => $taskParams['desc'],
            'date_creation' => $taskParams['date_creation'],
            'compte_exec' => $taskParams['compte_exec'],
            'compte_creation' => $taskParams['compte_creation'],
            'active' => $taskParams['active'],
            'tac_id' => $existingTaskId
        ]);
        
        return ['action' => 'updated', 'id' => $existingTaskId];
    } 
	else 
	{
        // Insertion nouvelle tâche
        $sql = "INSERT INTO tache (
                    tac_nom, 
                    tac_description, 
                    tac_date_creation, 
                    tac_date_modification,
                    tac_ser_id, 
                    tac_compte_utilisateur_execution,
                    tac_compte_utilisateur_creation,
                    tac_active,
                    tac_collecte_automatique
                ) VALUES (
                    :nom, :desc, :date_creation, NOW(), :ser_id, :compte_exec, :compte_creation, :active, 1
                )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($taskParams);
        
        return ['action' => 'created', 'id' => $pdo->lastInsertId()];
    }
}

function getTaskCreationDate(array $taskData)
{
    // StartBoundary du CalendarTrigger
    if (!empty($taskData['Triggers'])) 
	{
        foreach ($taskData['Triggers'] as $trigger) 
		{
            if ($trigger['Type'] == 'CalendarTrigger' && !empty($trigger['StartBoundary'])) 
			{
                $date = validDateTime($trigger['StartBoundary']);
                if ($date != null) 
				{
                    return $date;
                }
            }
        }
    }

    // Date de RegistrationInfo
    if (!empty($taskData['RegistrationInfo']['Date'])) 
	{
        $date = validDateTime($taskData['RegistrationInfo']['Date']);
        if ($date != null) 
		{
            return $date;
        }
    }

    // Par défaut: maintenant
    return date('Y-m-d H:i:s');
}

function findServerNameFromPath(string $filePath, string $rootDir)
{
    // Normaliser les chemins
    $filePath = str_replace('\\', '/', realpath($filePath));
    $rootDir = str_replace('\\', '/', realpath($rootDir));
    
    // Obtenir le chemin relatif
    $relativePath = str_replace($rootDir . '/', '', $filePath);
    
    // Diviser le chemin en parties
    $pathParts = explode('/', $relativePath);
    
    // Chercher le premier dossier qui commence par "00"
    foreach ($pathParts as $part)
	{
        if (strpos($part, "00") === 0)
		{
            return $part;
        }
    }
    
    return null;
}

function getTaskScheduleInfo(array $taskData)
{
    if (empty($taskData['Triggers'])) 
	{
        return null;
    }

    foreach ($taskData['Triggers'] as $trigger) 
	{
        if ($trigger['Type'] != 'CalendarTrigger') 
		{
            continue;
        }

        $start = validDateTime($trigger['StartBoundary'] ?? null);
        $end = validDateTime($trigger['EndBoundary'] ?? null);


        // Lecture de l'intervalle
        $intervalRaw = $trigger['Repetition']['Interval'] ?? null;

        $intervalReadable = null;
        if (!empty($intervalRaw)) 
		{
            $pattern = '/P(T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?)?/';
            if (preg_match($pattern, $intervalRaw, $matches)) 
			{
                $hours = isset($matches[2]) ? (int)$matches[2] : 0;
                $minutes = isset($matches[3]) ? (int)$matches[3] : 0;
                $seconds = isset($matches[4]) ? (int)$matches[4] : 0;

                $parts = [];
                if ($hours)   $parts[] = "$hours heure(s)";
                if ($minutes) $parts[] = "$minutes minute(s)";
                if ($seconds) $parts[] = "$seconds seconde(s)";

                $intervalReadable = implode(' ', $parts) ?: '0 seconde';
            }
			else 
			{
                $intervalReadable = $intervalRaw;
            }
        }

        $frequency = null;
        if (isset($trigger['ScheduleByDay'])) 
		{
            $frequency = 'daily';
        } elseif (isset($trigger['ScheduleByWeek'])) 
		{
            $frequency = 'weekly';
        } elseif (isset($trigger['ScheduleByMonth'])) 
		{
            $frequency = 'monthly';
        }

        $executionTime = $startTime ?? null;

        return [
            'date_debut' => $start,
            'date_fin' => $end,
            'interval' => $intervalReadable,
            'frequence' => $frequency,
            'heure_execution' => $executionTime
        ];
    }

    return null;
}
function insertTaskSchedule(PDO $pdo, array $taskData, int $tacId)
{
    $scheduleInfo = getTaskScheduleInfo($taskData);

    if ($scheduleInfo == null) {
        return false; 
    }

    $sql = "INSERT INTO planification (
                plan_date_debut,
                plan_date_fin,
                plan_repetition,
                plan_intervalle,
                plan_heure_execution,
                plan_tac_id
            ) VALUES (
                :date_debut,
                :date_fin,
                :repetition,
                :intervalle,
                :heure_execution,
                :tac_id
            )";

    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        ':date_debut'      => $scheduleInfo['date_debut'],
        ':date_fin'        => $scheduleInfo['date_fin'],
        ':repetition'      => $scheduleInfo['frequence'],
        ':intervalle'      => $scheduleInfo['interval'],
        ':heure_execution' => $scheduleInfo['heure_execution'],
        ':tac_id'          => $tacId,
    ]);
}
function insertTaskActions(PDO $pdo, array $actions, int $tacId)
{
    $sqlAction = "INSERT INTO action (
        act_type,
        act_command,
        act_argument,
        act_working_directory,
        act_expediteur,
        act_destinataire,
        act_objet,
        act_texte,
        act_serveur,
        act_titre,
        act_message
    ) VALUES (
        :type,
        :command,
        :argument,
        :working_directory,
        :expediteur,
        :destinataire,
        :objet,
        :texte,
        :serveur,
        :titre,
        :message
    )";

    $stmtAction = $pdo->prepare($sqlAction);

    $sqlComposer = "INSERT INTO composer (cmp_tac_id, cmp_act_id) VALUES (:tac_id, :act_id)";
    $stmtComposer = $pdo->prepare($sqlComposer);

    foreach ($actions as $action) {
        // 1. Insérer l'action
        $stmtAction->execute([
            ':type'              => $action['type'] ?? null,
            ':command'           => $action['command'] ?? null,
            ':argument'          => $action['argument'] ?? null,
            ':working_directory' => $action['working_directory'] ?? null,
            ':expediteur'        => $action['expediteur'] ?? null,
            ':destinataire'      => $action['destinataire'] ?? null,
            ':objet'             => $action['objet'] ?? null,
            ':texte'             => $action['texte'] ?? null,
            ':serveur'           => $action['serveur'] ?? null,
            ':titre'             => $action['titre'] ?? null,
            ':message'           => $action['message'] ?? null
        ]);

        // 2. Récupérer l'ID de l'action insérée
        $actId = $pdo->lastInsertId();

        // 3. Insérer dans la table composer
        $stmtComposer->execute([
            ':tac_id' => $tacId,
            ':act_id' => $actId
        ]);
    }
}


function mapActionsFromTaskData(array $taskActions): array
{
    $result = [];

    foreach ($taskActions as $action) {
        $type = $action['Type'];
        $mapped = ['type' => $type];

        switch ($type) {
            case 'Exec':
                $mapped['command'] = $action['Command'] ?? null;
                $mapped['argument'] = $action['Arguments'] ?? null;
                $mapped['working_directory'] = $action['WorkingDirectory'] ?? null;
                break;

            case 'SendEmail':
                $mapped['expediteur'] = $action['From'] ?? null;
                $mapped['destinataire'] = $action['To'] ?? null;
                $mapped['objet'] = $action['Subject'] ?? null;
                $mapped['texte'] = $action['Body'] ?? null;
                $mapped['serveur'] = $action['SmtpServer'] ?? null;
                break;

            case 'ShowMessage':
                $mapped['titre'] = $action['Title'] ?? null;
                $mapped['message'] = $action['MessageBody'] ?? null;
                break;
        }

        $result[] = $mapped;
    }

    return $result;
}



function scanXMLFiles(string $dir, PDO $pdo) 
{
    if (!is_dir($dir)) {
        throw new Exception("Le dossier $dir n'existe pas");
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    $totalFiles = 0;
    $processedFiles = 0;
    $updatedFiles = 0;
    $createdFiles = 0;
    $errorFiles = 0;
    $filteredFiles = 0; // Nouveau compteur pour les fichiers filtrés

    foreach ($iterator as $file) {
        if ($file->isFile() && strtolower($file->getExtension()) == 'xml') {
            $totalFiles++;
            $filePath = $file->getPathname();

            $serverName = findServerNameFromPath($filePath, $dir);
            if ($serverName == null) {
                echo "Impossible de déterminer le serveur pour : $filePath\n";
                $errorFiles++;
                continue;
            }

            echo "Traitement de $filePath (Serveur: $serverName)...\n";

            try {
                $parser = new TaskXMLParser($filePath);
                $taskData = $parser->parse();

                // NOUVEAU : Vérifier les critères de filtrage
                if (!shouldIncludeTask($taskData, $serverName)) {
                    $author = $taskData['RegistrationInfo']['Author'] ?? '';
                    $userId = $taskData['Principal']['UserId'] ?? '';
                    echo "  -> Tâche filtrée (Author: '$author', UserId: '$userId')\n";
                    $filteredFiles++;
                    continue;
                }

                $tac_ser_id = getOrCreateServerId($pdo, $serverName);

                $tac_nom = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                $tac_description = substr($taskData['RegistrationInfo']['Description'] ?? '', 0, 65535);
                $tac_date_creation = getTaskCreationDate($taskData);
                $compte_execution = substr($taskData['Principal']['UserId'] ?? '', 0, 255);
                $compte_creation = substr($taskData['RegistrationInfo']['Author'] ?? $taskData['Principal']['UserId'] ?? '', 0, 255);
                $tac_active = ($taskData['Settings']['Enabled'] == 'true' || $taskData['Settings']['Enabled'] == '1') ? 1 : 0;

                $taskParams = [
                    'nom' => $tac_nom,
                    'desc' => $tac_description,
                    'date_creation' => $tac_date_creation,
                    'ser_id' => $tac_ser_id,
                    'compte_exec' => $compte_execution,
                    'compte_creation' => $compte_creation,
                    'active' => $tac_active
                ];

                $result = upsertTask($pdo, $taskParams);
                $tacId = $result['id'];

                $processedFiles++;
                if ($result['action'] === 'created') {
                    $createdFiles++;
                    echo "  -> Nouvelle tâche créée '$tac_nom' sur serveur '$serverName' (ID: $tacId, date: $tac_date_creation).\n";
                } else {
                    $updatedFiles++;
                    echo "  -> Tâche mise à jour '$tac_nom' sur serveur '$serverName' (ID: $tacId, date: $tac_date_creation).\n";
                }

                // Supprimer ancienne planification
                $pdo->prepare("DELETE FROM planification WHERE plan_tac_id = ?")->execute([$tacId]);

                // Insérer nouvelle planification
                if (getTaskScheduleInfo($taskData)) {
                    if (insertTaskSchedule($pdo, $taskData, $tacId)) {
                        echo "  -> Données de planification insérées pour la tâche ID $tacId.\n";
                    } else {
                        echo "  -> Échec de l'insertion des données de planification pour la tâche ID $tacId.\n";
                    }
                }

                // Supprimer anciennes actions liées à cette tâche via composer
                $pdo->prepare("DELETE FROM composer WHERE cmp_tac_id = ?")->execute([$tacId]);

                // Insérer actions et liens composer
                $actions = mapActionsFromTaskData($taskData['Actions'] ?? []);
                if (!empty($actions)) {
                    insertTaskActions($pdo, $actions, $tacId);
                    echo "  -> Actions insérées pour la tâche ID $tacId.\n";
                }

            } catch (FormatError $e) {
                echo "Erreur format XML dans $filePath : " . $e->getMessage() . "\n";
                $errorFiles++;
            } catch (Exception $e) {
                echo "Erreur inattendue : " . $e->getMessage() . "\n";
                $errorFiles++;
            }
        }
    }

    echo "\n=== RÉSUMÉ ===\n";
    echo "Fichiers XML trouvés : $totalFiles\n";
    echo "Fichiers filtrés (ne respectant pas les critères utilisateur) : $filteredFiles\n";
    echo "Fichiers traités avec succès : $processedFiles\n";
    echo "  - Nouvelles tâches créées : $createdFiles\n";
    echo "  - Tâches mises à jour : $updatedFiles\n";
    echo "Fichiers en erreur : $errorFiles\n";
}


$rootDir = 'backup_taches_windows_20250429\backup\20250429-010002';
scanXMLFiles($rootDir, $pdo);
