<?php

class billingController extends controller
{
    public function beforeAction($queryString)
    {
        $this->_template->set('MESA_LIMS_ACTIVE', 'active');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', '');
    }

    public function scan2bill() {}

    public function createBillingOverview()
    {

        $this->render = false;

        $projectInput = isset($_POST['projects']) ? $_POST['projects'] : [];
        $projectsSelected = $this->normalizeBillingProjectIds($projectInput);

        $returnObject = [];

        // check if $_POST['incoming_barcode'] is set and not empty, else default to null
        $incomingBarcode = isset($_POST['barcode']) && is_scalar($_POST['barcode']) && ! empty($_POST['barcode']) ? trim($_POST['barcode']) : null;

        $billingStatus = '0';
        if (isset($_POST['billing_status']) && is_scalar($_POST['billing_status'])) {
            $billingStatus = (string) $_POST['billing_status'];
        }

        if (! in_array($billingStatus, ['0', '1', 'all'], true)) {
            $billingStatus = '0';
        }

        $selectedClient = false;
        if (isset($_POST['client']) && is_scalar($_POST['client'])) {
            $selectedClient = filter_var($_POST['client'], FILTER_VALIDATE_INT);
        }

        $dateFrom = isset($_POST['date_from']) && is_scalar($_POST['date_from']) ? trim($_POST['date_from']) : '';
        $dateTo = isset($_POST['date_to']) && is_scalar($_POST['date_to']) ? trim($_POST['date_to']) : '';

        $rangeRequested = $selectedClient !== false || $dateFrom !== '' || $dateTo !== '';

        if ($rangeRequested) {
            if ($selectedClient === false || $dateFrom === '' || $dateTo === '') {
                echo json_encode(['status' => 'invalid_project_range']);

                return;
            }

            $rangeProjects = $this->getBillingProjectsForRange($selectedClient, $dateFrom, $dateTo, $billingStatus);
            if ($rangeProjects === false) {
                echo json_encode(['status' => 'invalid_project_range']);

                return;
            }

            $projectsSelected = array_values(array_unique(array_merge($projectsSelected, $rangeProjects)));
        }

        // use barcode to find project
        if ($incomingBarcode) {
            $projectObject = upa('projects', 'clientProjectsList', [false, false, false, false, $incomingBarcode, false, false, true], false);

            if (isset($projectObject['found']) && isset($projectObject['found_id'])) {
                $project = new Project;
                $project->where('id', $projectObject['found_id']);
                $project = $project->first();

                if ($project && $this->billingStatusMatches(isset($project['billed']) ? $project['billed'] : 0, $billingStatus) && ! in_array((int) $project['id'], $projectsSelected, true)) {
                    $projectsSelected[] = (int) $project['id'];
                } elseif ($project) {
                    $returnObject['status'] = 'barcode_filtered';
                } else {
                    $returnObject['status'] = 'barcode_not_found';
                }
            } else {
                $returnObject['status'] = 'barcode_not_found';
            }
        }

        // load existing ones so we can do a client check
        if (count($projectsSelected) > 0) {

            $selectedProjects = new Project;
            $placeholders = str_repeat('?,', count($projectsSelected) - 1).'?';
            $sql = "SELECT id, client, billed FROM projects WHERE id IN ($placeholders)";
            $stmt = $selectedProjects->getPdo()->prepare($sql);
            $stmt->execute($projectsSelected);
            $projectRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $projectClientMap = [];
            $projectBilledMap = [];
            foreach ($projectRows as $projectRow) {
                $projectClientMap[(int) $projectRow['id']] = (int) $projectRow['client'];
                $projectBilledMap[(int) $projectRow['id']] = isset($projectRow['billed']) ? (int) $projectRow['billed'] : 0;
            }

            $validProjects = [];
            $clients = [];
            foreach ($projectsSelected as $projectId) {
                if (! array_key_exists((int) $projectId, $projectClientMap) || ! $this->billingStatusMatches($projectBilledMap[(int) $projectId], $billingStatus)) {
                    continue;
                }

                $validProjects[] = (int) $projectId;
                $clients[] = $projectClientMap[(int) $projectId];
            }

            $projectsSelected = $validProjects;

            $uniqueClients = array_unique($clients);

            if (count($uniqueClients) > 1) {
                // todo some protection here
                echo json_encode(['status' => 'client_mismatch']);

                return;
            }

            if ($selectedClient !== false && count($uniqueClients) > 0 && ! in_array((int) $selectedClient, $uniqueClients, true)) {
                echo json_encode(['status' => 'client_mismatch']);

                return;
            }

            if (count($uniqueClients) > 0) {
                $client = new Client;
                $client->where('id', $uniqueClients[0]);
                $client = $client->first();

                $returnObject['client_id'] = $client['id'];
                $returnObject['client_name'] = $client['name'];
                $returnObject['debit_number'] = 'Debiteur nummer: '.$client['debit_number'];

                $cats = upa('clients', 'clientCategories', [$client['id']], false);

                $returnObject['client_cats'] = 'Klant categorie: '.implode(',', array_column($cats, 'name'));
            }

        }

        // no projects yet?
        if (count($projectsSelected) == 0) {
            $returnObject['projects'] = [];
            $returnObject['billingTypeTable'] = '';
            $returnObject['projectSearchTableBody'] = '';
            $returnObject['billedArticlesTable'] = '';
            $returnObject['client_name'] = '';
            $returnObject['client_cats'] = '';
            if (! isset($returnObject['status'])) {
                $returnObject['status'] = 'no_projects';
            }
            echo json_encode($returnObject);

            return;
        }

        // get all projects
        $billingData = $this->generateBillingData($projectsSelected);

        $tF = new tableFactory;
        $tF->setTableId('projectBillingTypesTable');
        $tF->loadTemplate('projectBillingTypesTable');
        $tF->loadValues($billingData['sampleTypes']);

        $returnObject['projects'] = $projectsSelected;
        $returnObject['billingTypeTable'] = $tF->renderTable();
        $returnObject['projectSearchTableBody'] = $billingData['scanTable'];
        $returnObject['billedArticlesTable'] = $billingData['billedArticlesTable'];

        echo json_encode($returnObject);

    }

    public function markProjectsBilled()
    {
        $this->render = false;

        $projectInput = isset($_POST['projects']) ? $_POST['projects'] : [];
        $projectIds = $this->normalizeBillingProjectIds($projectInput);

        if (count($projectIds) == 0) {
            echo json_encode(['status' => 'no_projects']);

            return;
        }

        $projects = new Project;
        $placeholders = str_repeat('?,', count($projectIds) - 1).'?';
        $sql = "UPDATE projects SET billed = 1 WHERE id IN ($placeholders)";
        $stmt = $projects->getPdo()->prepare($sql);
        $stmt->execute($projectIds);

        echo json_encode(['status' => 'ok', 'updated' => $stmt->rowCount()]);
    }

    public function setProjectBilled()
    {
        $this->render = false;

        $projectId = isset($_POST['project']) ? filter_var($_POST['project'], FILTER_VALIDATE_INT) : false;
        $billed = isset($_POST['billed']) ? filter_var($_POST['billed'], FILTER_VALIDATE_INT) : false;

        if ($projectId === false || $projectId <= 0 || ! in_array($billed, [0, 1], true)) {
            echo json_encode(['status' => 'invalid_input']);

            return;
        }

        $projects = new Project;
        $sql = 'UPDATE projects SET billed = ? WHERE id = ?';
        $stmt = $projects->getPdo()->prepare($sql);
        $stmt->execute([$billed, $projectId]);

        echo json_encode(['status' => 'ok', 'project' => $projectId, 'billed' => $billed]);
    }

    public function exportBillingCSV()
    {
        $this->render = false;
        $separator = $this->getBillingExportSeparator();

        $projectInput = isset($_POST['projects']) ? $_POST['projects'] : [];
        $projectIds = $this->normalizeBillingProjectIds($projectInput);

        if (count($projectIds) == 0) {
            http_response_code(400);
            echo 'Geen projecten geselecteerd.';

            return;
        }

        $projectPlaceholders = str_repeat('?,', count($projectIds) - 1).'?';
        $projects = new Project;
        $projectSql = "SELECT id, client, project_name, custom_fields FROM projects WHERE id IN ($projectPlaceholders)";
        $projectStmt = $projects->getPdo()->prepare($projectSql);
        $projectStmt->execute($projectIds);
        $projectRows = $projectStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($projectRows)) {
            http_response_code(404);
            echo 'Geen projecten gevonden.';

            return;
        }

        $projectsById = [];
        $clientIds = [];
        foreach ($projectRows as $projectRow) {
            $projectId = (int) $projectRow['id'];
            $projectsById[$projectId] = $projectRow;
            $clientIds[(int) $projectRow['client']] = true;
        }

        if (count($clientIds) > 1) {
            http_response_code(400);
            echo 'De geselecteerde projecten zijn niet van dezelfde klant.';

            return;
        }

        $clientId = (int) array_keys($clientIds)[0];
        $client = new Client;
        $client->where('id', $clientId);
        $client = $client->first();

        if (empty($client)) {
            http_response_code(404);
            echo 'Klant niet gevonden.';

            return;
        }

        $productGroups = upa('productGroups', 'productGroupsArray', [$clientId], false);
        if (! is_array($productGroups)) {
            $productGroups = [];
        }

        $sampleModel = new Sample;
        $sampleSql = "SELECT id, project, sample_innoculated, sample_type, tht_code, portal_product_group_id, barcode, client_description, custom_fields
            FROM samples WHERE project IN ($projectPlaceholders) ORDER BY project, id";
        $sampleStmt = $sampleModel->getPdo()->prepare($sampleSql);
        $sampleStmt->execute($projectIds);
        $sampleRows = $sampleStmt->fetchAll(PDO::FETCH_ASSOC);

        $sampleIds = array_map('intval', array_column($sampleRows, 'id'));
        $metadataBySample = [];
        $metadataColumns = [];
        $analysisBySample = [];

        if (count($sampleIds) > 0) {
            $samplePlaceholders = str_repeat('?,', count($sampleIds) - 1).'?';

            $metadataModel = new Metadata;
            $metadataSql = "SELECT sample, name, value FROM metadata WHERE sample IN ($samplePlaceholders) ORDER BY sample, meta_order, id";
            $metadataStmt = $metadataModel->getPdo()->prepare($metadataSql);
            $metadataStmt->execute($sampleIds);
            $metadataRows = $metadataStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($metadataRows as $metadataRow) {
                $sampleId = (int) $metadataRow['sample'];
                $metadataName = (string) $metadataRow['name'];

                if (! array_key_exists($metadataName, $metadataColumns)) {
                    $metadataColumns[$metadataName] = true;
                }

                if (! isset($metadataBySample[$sampleId])) {
                    $metadataBySample[$sampleId] = [];
                }

                $metadataBySample[$sampleId][$metadataName] = $metadataRow['value'];
            }

            $analysisModel = new SampleAnalysis;
            $analysisSql = "SELECT sa.sample, sa.assay_base, assays.name, assays.custom_fields
                FROM sampleanalysis sa
                LEFT JOIN assays ON assays.id = sa.assay_base
                WHERE sa.sample IN ($samplePlaceholders)
                ORDER BY sa.sample, sa.id";
            $analysisStmt = $analysisModel->getPdo()->prepare($analysisSql);
            $analysisStmt->execute($sampleIds);
            $analysisRows = $analysisStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($analysisRows as $analysisRow) {
                $sampleId = (int) $analysisRow['sample'];
                if (! isset($analysisBySample[$sampleId])) {
                    $analysisBySample[$sampleId] = [];
                }

                $analysisBySample[$sampleId][] = $analysisRow;
            }
        }

        $csv = fopen('php://temp', 'r+');
        $headers = [
            'Opdrachtgever',
            'Datum bemonstering',
            'Datum ontvangst',
            'Datum start analyse',
            'Type onderzoek',
            'Productgroep',
            'Referentie opdrachtgever',
            'Monster nummer',
            'Omschrijving opdrachtgever',
            'Monster details',
        ];

        foreach (array_keys($metadataColumns) as $metadataColumn) {
            $headers[] = $metadataColumn;
        }

        $headers = array_merge($headers, [
            'Analyse',
            'Eenheid',
            'Techniek',
            'Geaccrediteerde verrichting',
            'Conformiteit',
            'Referentiemethode',
        ]);

        fputcsv($csv, $headers, $separator);

        foreach ($sampleRows as $sample) {
            $sampleId = (int) $sample['id'];
            if (empty($analysisBySample[$sampleId])) {
                continue;
            }

            $project = isset($projectsById[(int) $sample['project']]) ? $projectsById[(int) $sample['project']] : [];
            $projectFields = json_decode(isset($project['custom_fields']) ? $project['custom_fields'] : '', true);
            $sampleFields = json_decode($sample['custom_fields'], true);
            $sampleFields = is_array($sampleFields) ? $sampleFields : [];
            $projectFields = is_array($projectFields) ? $projectFields : [];

            $sampleType = 'Unknown';
            if ($sample['sample_type'] == 'S') {
                $sampleType = empty($sample['tht_code']) ? 'Direct onderzoek' : 'THT onderzoek';
            }
            if ($sample['sample_type'] == 'L') {
                $sampleType = 'Legionella onderzoek';
            }
            if ($sample['sample_type'] == 'R') {
                $sampleType = 'RODAC onderzoek';
            }

            $metadataValues = [];
            foreach (array_keys($metadataColumns) as $metadataColumn) {
                $metadataValue = isset($metadataBySample[$sampleId][$metadataColumn]) ? $metadataBySample[$sampleId][$metadataColumn] : '';
                $metadataValues[] = $this->billingExportValue($metadataValue);
            }

            $commonRow = [
                $this->billingExportValue($client['name']),
                $this->billingExportValue(isset($projectFields['project_monster']) ? $projectFields['project_monster'] : ''),
                $this->billingExportValue(isset($projectFields['project_ontvangst']) ? $projectFields['project_ontvangst'] : ''),
                $this->formatBillingExportDate($sample['sample_innoculated']),
                $sampleType,
                $this->billingExportValue(isset($productGroups[$sample['portal_product_group_id']]['name']) ? $productGroups[$sample['portal_product_group_id']]['name'] : 'Onbekend'),
                $this->billingExportValue(isset($project['project_name']) ? $project['project_name'] : ''),
                $this->billingExportValue($sample['barcode']),
                $this->billingExportValue($sample['client_description']),
                $this->billingExportValue(isset($sampleFields['details']) ? $sampleFields['details'] : ''),
            ];
            $commonRow = array_merge($commonRow, $metadataValues);

            foreach ($analysisBySample[$sampleId] as $analysis) {
                $assayFields = json_decode($analysis['custom_fields'], true);
                $assayFields = is_array($assayFields) ? $assayFields : [];
                $analysisName = ! empty($assayFields['raportnaam']) ? $assayFields['raportnaam'] : $analysis['name'];

                $row = array_merge($commonRow, [
                    $this->billingExportValue($analysisName),
                    $this->billingExportValue(isset($assayFields['resultin']) ? $assayFields['resultin'] : ''),
                    $this->billingExportValue(isset($assayFields['techniek']) ? $assayFields['techniek'] : ''),
                    $this->billingExportValue(isset($assayFields['accred']) ? $assayFields['accred'] : ''),
                    $this->billingExportValue(isset($assayFields['conform']) ? $assayFields['conform'] : ''),
                    $this->billingExportValue(isset($assayFields['referentiemethode']) ? $assayFields['referentiemethode'] : ''),
                ]);

                fputcsv($csv, $row, $separator);
            }
        }

        header('Content-type: text/csv; charset=UTF-16LE');
        header('Content-Disposition: attachment; filename="billing-export-'.date('Y-m-d').'.csv"');

        rewind($csv);
        $csvContent = 'sep='.$separator."\r\n".stream_get_contents($csv);
        $csvContent = mb_convert_encoding($csvContent, 'UTF-16LE', 'UTF-8');
        echo "\xFF\xFE";
        echo $csvContent;
        fclose($csv);
    }

    private function getBillingExportSeparator()
    {
        if (isset($_POST['separator']) && is_scalar($_POST['separator']) && in_array($_POST['separator'], [',', ';'], true)) {
            return $_POST['separator'];
        }

        $acceptLanguage = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']) : '';
        if (preg_match('/(^|,)\s*(nl|de|fr|es|it|pt|da|sv|no|fi|pl|cs|sk|hu|ro|tr)(-|,|;|$)/', $acceptLanguage)) {
            return ';';
        }

        return ',';
    }

    private function formatBillingExportDate($timestamp)
    {
        if (empty($timestamp) || ! is_numeric($timestamp) || (int) $timestamp <= 0) {
            return 'Onbekend';
        }

        $date = new DateTime('@'.(int) $timestamp);
        $date->setTimezone(new DateTimeZone('Europe/Amsterdam'));

        return $date->format('d-m-Y');
    }

    private function billingExportValue($value)
    {
        if ($value === null || $value === false) {
            return '';
        }

        if (is_scalar($value)) {
            return $value;
        }

        return json_encode($value);
    }

    private function normalizeBillingProjectIds($projectInput)
    {
        if (! is_array($projectInput)) {
            $projectInput = is_scalar($projectInput) ? explode(',', (string) $projectInput) : [];
        }

        $projectIds = [];
        foreach ($projectInput as $projectId) {
            if (! is_scalar($projectId)) {
                continue;
            }

            $projectId = filter_var($projectId, FILTER_VALIDATE_INT);
            if ($projectId !== false && $projectId > 0) {
                $projectIds[] = (int) $projectId;
            }
        }

        return array_values(array_unique($projectIds));
    }

    private function billingStatusMatches($billed, $billingStatus)
    {
        return $billingStatus === 'all' || (int) $billed === (int) $billingStatus;
    }

    private function getBillingProjectsForRange($clientId, $dateFrom, $dateTo, $billingStatus)
    {
        $from = $this->parseBillingDate($dateFrom);
        $to = $this->parseBillingDate($dateTo);

        if ($from === false || $to === false || $from > $to) {
            return false;
        }

        $to->modify('+1 day');

        $projects = new Project;
        // Projects have no "inzetdatum"; it lives on their samples (sample_innoculated)
        $sql = 'SELECT id FROM projects WHERE client = ? AND id IN (
            SELECT DISTINCT project FROM samples WHERE sample_innoculated >= ? AND sample_innoculated < ?)';
        $params = [$clientId, $from->getTimestamp(), $to->getTimestamp()];

        if ($billingStatus !== 'all') {
            $sql .= ' AND billed = ?';
            $params[] = (int) $billingStatus;
        }

        $sql .= ' ORDER BY project_date DESC';
        $stmt = $projects->getPdo()->prepare($sql);
        $stmt->execute($params);

        $projectIds = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

        return array_map('intval', $projectIds);
    }

    private function parseBillingDate($date)
    {
        if (! is_scalar($date) || trim($date) === '') {
            return false;
        }

        $date = trim($date);
        $formats = ['!d-m-Y', '!Y-m-d'];

        foreach ($formats as $format) {
            $parsed = DateTime::createFromFormat($format, $date);
            $errors = DateTime::getLastErrors();

            if ($parsed !== false && ($errors === false || ($errors['warning_count'] == 0 && $errors['error_count'] == 0))) {
                return $parsed;
            }
        }

        return false;
    }

    private function generateBillingData($projectsSelected)
    {

        $automaticRodac = $this->getAutomaticallyRodacSampleMap($projectsSelected);
        $sampleTypes = $this->getSampleTypes($projectsSelected, $automaticRodac['samples']);

        $scanTable = $this->buildScannedTable(
            $projectsSelected,
            $automaticRodac['projects'],
            $automaticRodac['rodac_projects'],
            $automaticRodac['legionella_projects']
        );

        $billedArticlesTable = $this->getBilledArticles($projectsSelected);

        return compact('sampleTypes', 'scanTable', 'billedArticlesTable');

    }

    private function buildScannedTable($projects, $automaticRodacProjects = [], $rodacProjects = [], $legionellaProjects = [])
    {

        $trs = '';

        foreach ($projects as $project) {
            $automaticRodacCount = isset($automaticRodacProjects[(int) $project]) ? $automaticRodacProjects[(int) $project] : 0;
            $hasRodacSamples = isset($rodacProjects[(int) $project]);
            $hasLegionellaSamples = isset($legionellaProjects[(int) $project]);
            $tr = upa('projects', 'createProjectTableLine', [$project, $automaticRodacCount, $hasRodacSamples, $hasLegionellaSamples], false);
            $trs .= $tr;
        }

        return $trs;

    }

    private function getAutomaticallyRodacSampleMap($projects)
    {
        $projectIds = $this->normalizeBillingProjectIds($projects);
        $automaticRodac = [
            'samples' => [],
            'projects' => [],
            'rodac_projects' => [],
            'legionella_projects' => [],
        ];

        if (count($projectIds) == 0) {
            return $automaticRodac;
        }

        $placeholders = str_repeat('?,', count($projectIds) - 1).'?';
        $sampleModel = new Sample;
        $sampleSql = "SELECT id, project, sample_type FROM samples WHERE project IN ($placeholders)";
        $sampleStmt = $sampleModel->getPdo()->prepare($sampleSql);
        $sampleStmt->execute($projectIds);
        $sampleRows = $sampleStmt->fetchAll(PDO::FETCH_ASSOC);

        $sampleIds = [];
        foreach ($sampleRows as $sampleRow) {
            $projectId = (int) $sampleRow['project'];

            if ($sampleRow['sample_type'] == 'R') {
                $automaticRodac['rodac_projects'][$projectId] = true;
            }

            if ($sampleRow['sample_type'] == 'L') {
                $automaticRodac['legionella_projects'][$projectId] = true;
            }

            if ($sampleRow['sample_type'] == 'S') {
                $sampleIds[] = (int) $sampleRow['id'];
            }
        }

        if (count($sampleIds) == 0) {
            return $automaticRodac;
        }

        $samplePlaceholders = str_repeat('?,', count($sampleIds) - 1).'?';
        $analysisModel = new SampleAnalysis;
        $analysisSql = "SELECT sample, original_assay_base FROM sampleanalysis WHERE sample IN ($samplePlaceholders)";
        $analysisStmt = $analysisModel->getPdo()->prepare($analysisSql);
        $analysisStmt->execute($sampleIds);
        $analysisRows = $analysisStmt->fetchAll(PDO::FETCH_ASSOC);

        $analysesBySample = [];
        foreach ($analysisRows as $analysisRow) {
            $sampleId = (int) $analysisRow['sample'];
            if (! isset($analysesBySample[$sampleId])) {
                $analysesBySample[$sampleId] = [];
            }

            $analysesBySample[$sampleId][] = (int) $analysisRow['original_assay_base'];
        }

        $rodacAssays = json_decode(RODAC_ASSAYS_FOR_BILLING);
        if (! is_array($rodacAssays)) {
            $rodacAssays = [$rodacAssays];
        }
        $rodacAssays = array_map('intval', $rodacAssays);

        foreach ($sampleRows as $sampleRow) {
            $sampleId = (int) $sampleRow['id'];
            $sampleAnalyses = isset($analysesBySample[$sampleId]) ? $analysesBySample[$sampleId] : [];
            $hasOnlyRodacAnalyses = count($sampleAnalyses) > 0 && count(array_diff($sampleAnalyses, $rodacAssays)) == 0;

            if (! $hasOnlyRodacAnalyses) {
                continue;
            }

            $automaticRodac['samples'][$sampleId] = true;
            $projectId = (int) $sampleRow['project'];
            if (! isset($automaticRodac['projects'][$projectId])) {
                $automaticRodac['projects'][$projectId] = 0;
            }
            $automaticRodac['projects'][$projectId]++;
        }

        return $automaticRodac;
    }

    public function getSampleTypes($projects, $automaticRodacSampleIds = false)
    {

        $counts = [];

        $counts['S']['header'] = 'Normaal';
        $counts['S']['count'] = 0;
        $counts['S']['count50'] = 0;
        $counts['S']['count100'] = 0;

        $counts['T']['header'] = 'THT';
        $counts['T']['count'] = 0;
        $counts['T']['count50'] = 0;
        $counts['T']['count100'] = 0;

        $counts['R']['header'] = 'Rodac';
        $counts['R']['count'] = 0;
        $counts['R']['count50'] = 0;
        $counts['R']['count100'] = 0;

        $counts['L']['header'] = 'Legionella';
        $counts['L']['count'] = 0;
        $counts['L']['count50'] = 0;
        $counts['L']['count100'] = 0;

        foreach ($counts as $key => $count) {
            $counts[$key]['projects'] = [];
            $counts[$key]['receipt_dates'] = [];
        }

        $projectIds = [];
        foreach ($projects as $projectId) {
            $projectId = filter_var($projectId, FILTER_VALIDATE_INT);
            if ($projectId !== false && $projectId > 0) {
                $projectIds[] = (int) $projectId;
            }
        }
        $projectIds = array_values(array_unique($projectIds));

        if (count($projectIds) == 0) {
            return [];
        }

        if ($automaticRodacSampleIds === false) {
            $automaticRodac = $this->getAutomaticallyRodacSampleMap($projectIds);
            $automaticRodacSampleIds = $automaticRodac['samples'];
        }

        $placeholders = str_repeat('?,', count($projectIds) - 1).'?';

        $projectModel = new Project;
        $projectSql = "SELECT id, custom_fields FROM projects WHERE id IN ($placeholders)";
        $projectStmt = $projectModel->getPdo()->prepare($projectSql);
        $projectStmt->execute($projectIds);
        $projectRows = $projectStmt->fetchAll(PDO::FETCH_ASSOC);

        $projectReceiptDates = [];
        foreach ($projectRows as $projectRow) {
            $received = peekIntoJSON($projectRow['custom_fields'], 'project_ontvangst');
            if (is_scalar($received) && trim($received) !== '' && trim($received) !== 'Onbekend') {
                $projectReceiptDates[(int) $projectRow['id']] = trim($received);
            }
        }

        $sampleModel = new Sample;
        $sampleSql = "SELECT id, project, sample_type, tht_code, sample_innoculated FROM samples WHERE project IN ($placeholders)";
        $sampleStmt = $sampleModel->getPdo()->prepare($sampleSql);
        $sampleStmt->execute($projectIds);
        $sampleRows = $sampleStmt->fetchAll(PDO::FETCH_ASSOC);

        $holidays = new Holidays;

        foreach ($sampleRows as $result) {
            $markup = 'normal';

            if ($result['sample_innoculated']) {
                $meltedDate = date('Y-m-d', $result['sample_innoculated']);
                $markup = $holidays->getTypeOfSpecialDate($meltedDate);
            }

            $st = $result['sample_type'];
            $sampleType = $st;

            if ($st == 'S') {
                $isAutomaticRodac = isset($automaticRodacSampleIds[(int) $result['id']]);
                $sampleType = $isAutomaticRodac ? 'R' : (empty($result['tht_code']) ? 'S' : 'T');
            }

            if (! isset($counts[$sampleType])) {
                continue;
            }

            $counts[$sampleType]['count']++;
            $counts[$sampleType]['count50'] = $markup == 'saturday' ? $counts[$sampleType]['count50'] + 1 : $counts[$sampleType]['count50'];
            $counts[$sampleType]['count100'] = $markup == 'sunday' || $markup == 'holiday' ? $counts[$sampleType]['count100'] + 1 : $counts[$sampleType]['count100'];
            $counts[$sampleType]['count50flair'] = '';
            $counts[$sampleType]['count100flair'] = '';
            $counts[$sampleType]['projects'][(int) $result['project']] = true;

            if (isset($projectReceiptDates[(int) $result['project']])) {
                $counts[$sampleType]['receipt_dates'][$projectReceiptDates[(int) $result['project']]] = true;
            }
        }

        // drop empty types
        foreach ($counts as $key => $count) {
            if ($count['count'] == 0) {
                unset($counts[$key]);

                continue;
            }

            $counts[$key]['project_count'] = count($counts[$key]['projects']);
            $counts[$key]['mengmonsters'] = 'NA';
            $counts[$key]['receipt_date_count'] = count($counts[$key]['receipt_dates']);
            unset($counts[$key]['projects']);
            unset($counts[$key]['receipt_dates']);

            // set tablestyles to background yellow text black if > 0

            if ($count['count50'] > 0) {
                $counts[$key]['count50flair'] = 'background-color: yellow; color: black;';
            }

            if ($count['count100'] > 0) {
                $counts[$key]['count100flair'] = 'background-color: yellow; color: black;';
            }

        }

        return $counts;

    }

    public function getBilledArticles($projectIds)
    {

        $validatedInput = array_map(function ($value) {
            return filter_var($value, FILTER_VALIDATE_INT);
        }, $projectIds);

        $selectedProjects = new Project;

        $sql = "SELECT sampleanalysis.assay_base, sampleanalysis.id, assays.article_code, assays.billable, assays.name, COUNT(*) as total, 
        SUM(CASE WHEN sampleanalysis.conf_requested = '1' THEN 1 ELSE 0 END) as conf_requested_count,
        GROUP_CONCAT(sampleanalysis.id) as ids
        FROM sampleanalysis 
        JOIN assays ON sampleanalysis.assay_base = assays.id 
        WHERE sampleanalysis.project IN (".implode(',', $validatedInput).') 
        GROUP BY sampleanalysis.assay_base, assays.name
        ORDER BY assays.name ';

        $stmt = $selectedProjects->getPdo()->prepare($sql);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as $idx => &$result) {

            $legDataFound = false;

            $positives = 0;

            // was conf_requested?
            if ($result['conf_requested_count'] > 0) {

                // explode the ids column
                $ids = explode(',', $result['ids']);

                foreach ($ids as $id) {
                    $sa = new SampleAnalysis;
                    $sa->where('id', $id);
                    $sa = $sa->first();

                    $storedResult = json_decode($sa['storedResult'], true);

                    // is this an array?
                    if (! is_array($storedResult)) {
                        continue;
                    }

                    $detected = checkKeyOrFalse($storedResult, 'hidden', 'detected');

                    if ($detected) {
                        $legDataFound = true;
                        $positives++;
                    }

                }

            }

            // drop if not billable
            if ($result['billable'] == 0) {
                unset($results[$idx]);

                continue;
            }

            if (! isset($result['article_code']) || empty($result['article_code'])) {
                $result['article_code'] = '-';
            }

            if ($result['conf_requested_count'] > 0 && $legDataFound) {
                $result['leg_positive'] = ' - Waarvan positief:'.$positives;
            } else {
                $result['leg_positive'] = '';
            }

        }

        unset($result); // Unset reference after loop

        if (empty($results)) {
            return generateHTML('alertWarning', ['alert_title' => 'Geen resultaten', 'alert_message' => 'Geen resultaten gevonden voor dit project']);
        }

        $tF = new tableFactory;
        $tF->setTableId('projectBillingTable');
        $tF->loadTemplate('projectBillingTable');
        $tF->loadValues($results);

        return $tF->renderTable();
    }
}
