<?php
	
	// Include Utilities PHP File
	require_once('_utilities.php');
	
	
	// Initialize Error Array
	$errors = array();
	
	
	// Start Session
	session_start();
	
	
	// Connect To Database
	$pdo = dbConnect();
	
	
	// Require Admin Login
	requireLogin(LEVEL_ADMIN, LEVEL_SU);
	
	
	
	
	// Require Provided FCARs Array
	if (!isset($_POST['FCARs']) || count($_POST['FCARs']) == 0)
	{
		redirect('FCARsList.php');
	}
	array_walk($_POST['FCARs'], 'parseInt');
	$FCARConstraint = '(`FCAR`.`ID` = ' . implode(' OR `FCAR`.`ID` = ', $_POST['FCARs']) . ')';
	
	
	
	
	// Retrieve SLO List
	$query = "
		SELECT DISTINCT
			`SLO`.`ID` AS ID,
			`SLO`.`code` AS code,
			`SLO`.`description`
		FROM `SLO`
		JOIN `SLOXDegree`
		ON `SLOXDegree`.`fkSLO` = `SLO`.`ID`
		JOIN `Degree`
		ON `Degree`.`ID` = `SLOXDegree`.`fkDegree`
		WHERE
			`SLO`.`status` = 1 AND
			`Degree`.`fkDepartment` = :fkDepartment
		ORDER BY `SLO`.`code`
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':fkDepartment', $_SESSION['fkDepartment']);
	$success = $stmt->execute();
	if (!$success || ($SLOs = $stmt->fetchAll(PDO::FETCH_ASSOC)) === FALSE)
	{
		redirect('FCARsList.php');
	}
	
	
	
	
	// Loop Through SLOs and Retrieve FCAR Data
	foreach ($SLOs as &$SLO)
	{
		$query = "
		SELECT
			`FCAR`.`year`,
			`FCAR`.`term`,
			`FCAR`.`section`,
			`Degree`.`name`,
			`Degree`.`code`,
			`Course`.`name`,
			`Course`.`prefix`,
			`Course`.`number`,
			`User`.`firstName`,
			`User`.`lastName`,
			SUM(`Assessment`.`numSuccess`) AS numSuccess,
			SUM(`Assessment`.`numEvaluated`) AS numEvaluated
		FROM `FCAR`
		JOIN `Course`
		ON `Course`.`ID` = `FCAR`.`fkCourse`
		JOIN `User`
		ON `User`.`ID` = `FCAR`.`fkUser`
		JOIN `Degree`
		ON `Degree`.`ID` = `FCAR`.`fkDegree`
		JOIN `Assessment`
		ON `Assessment`.`fkFCAR` = `FCAR`.`ID`
		JOIN `Result`
		ON `Result`.`fkAssessment` = `Assessment`.`ID`
		JOIN `Objective`
		ON `Objective`.`ID` = `Result`.`fkObjective`
		JOIN `SLOXObjective`
		ON `SLOXObjective`.`fkObjective` = `Objective`.`ID`
		WHERE
			$FCARConstraint AND
			`SLOXObjective`.`fkSLO` = :fkSLO AND
			`Objective`.`dateCreated` < `FCAR`.`dateCreated` AND
			(`Objective`.`status` = 1 OR `FCAR`.`dateCreated` < `Objective`.`dateModified`) AND
			`SLOXObjective`.`dateCreated` < `FCAR`.`dateCreated` AND
			(`SLOXObjective`.`dateDisabled` IS NULL OR `FCAR`.`dateCreated` < `SLOXObjective`.`dateDisabled`)
		GROUP BY `FCAR`.`ID`
		";
		$stmt = $pdo->prepare($query);
		$stmt->bindValue(':fkSLO', $SLO['ID']);
		$success = $stmt->execute();
		if ($success)
		{
			$SLO['fractions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
	}
	unset($SLO);
	
	
	
	
	// Build $CSV Matrix
	$CSV = array();
	
	// Calculate Number of SLOs
	$numSLOs = count($SLOs);
	
	// Define Function to Initialize New Rows for $CSV
	function initRow(&$arr, $numCols, $rowNum, $val = '')
	{
		if (!isset($arr[$rowNum]))
		{
			$arr[$rowNum] = array();
			for ($i=0; $i<$numCols; $i++)
			{
				$arr[$rowNum][$i] = $val;
			}
		}
	}
	
	// Initialize First Two Rows of $CSV
	initRow($CSV, $numSLOs*7, 0);
	initRow($CSV, $numSLOs*7, 1);
	
	// Loop Through SLOs
	for ($i=0; $i<$numSLOs; $i++)
	{
		// Set Column Pointer For This SLO
		$c = $i*7;
		
		// Write SLO Header Row
		$CSV[0][$c+0] = 'SLO ' . $SLOs[$i]['code'];
		
		// Write FCAR Header Row
		$CSV[1][$c+0] = 'Degree';
		$CSV[1][$c+1] = 'Course';
		$CSV[1][$c+2] = 'Term';
		$CSV[1][$c+3] = 'Instructor';
		$CSV[1][$c+4] = 'Num';
		$CSV[1][$c+5] = 'Denom';
		
		// Write FCAR Rows
		for ($j=0; $j<count($SLOs[$i]['fractions']); $j++)
		{
			initRow($CSV, $numSLOs*7, $j+2);
			$FCAR = $SLOs[$i]['fractions'][$j];
			
			$CSV[$j+2][$c+0] = $FCAR['code'];
			$CSV[$j+2][$c+1] = $FCAR['prefix'] . ' ' . $FCAR['number'];
			$CSV[$j+2][$c+2] = $FCAR['term'] . ' ' . $FCAR['year'];
			$CSV[$j+2][$c+3] = $FCAR['firstName'] . ' ' . $FCAR['lastName'];
			$CSV[$j+2][$c+4] = $FCAR['numSuccess'];
			$CSV[$j+2][$c+5] = $FCAR['numEvaluated'];
		}
	}
	
	
	
	// Construct File Name
	$filename = 'FCAR_Export_' . date('m-d-Y') . '.csv';
	
	
	
	
	// Export $CSV Matrix as .csv
	$outputFile = fopen("php://output", 'w');
	header("Content-Type:application/csv"); 
	header("Content-Disposition:attachment;filename=" . $filename); 
	foreach($CSV as $row)
	{
		fputcsv($outputFile, $row);
	}
	fclose($outputFile);
	
?>