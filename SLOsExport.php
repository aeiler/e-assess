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
	requireLogin();
	
	
	
	
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
		redirect('SLOsManage.php');
	}
	
	
	
	
	// Loop Through SLOs and Retrieve Objective Data
	foreach ($SLOs as &$SLO)
	{
		$query = "
		SELECT
			`Course`.`name`,
			`Course`.`prefix`,
			`Course`.`number` AS courseNumber,
			`Objective`.`number` AS objectiveNumber,
			`SLOXObjective`.`I`,
			`SLOXObjective`.`R`,
			`SLOXObjective`.`E`,
			`SLOXObjective`.`A`
		FROM `SLOXObjective`
		JOIN `Objective`
		ON `Objective`.`ID` = `SLOXObjective`.`fkObjective`
		JOIN `Course`
		ON `Course`.`ID` = `Objective`.`fkCourse`
		WHERE
			`SLOXObjective`.`fkSLO` = :fkSLO AND
			`SLOXObjective`.`dateDisabled` IS NULL AND
			`Objective`.`status` = 1 AND
			`Course`.`status` = 1
		ORDER BY `Course`.`prefix`, `Course`.`number`, `Objective`.`number`
		";
		$stmt = $pdo->prepare($query);
		$stmt->bindValue(':fkSLO', $SLO['ID']);
		$success = $stmt->execute();
		if ($success)
		{
			$SLO['objectives'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
		
		// Write Objective Header Row
		$CSV[1][$c+0] = 'Course';
		$CSV[1][$c+1] = 'Objective';
		$CSV[1][$c+2] = 'I';
		$CSV[1][$c+3] = 'R';
		$CSV[1][$c+4] = 'E';
		$CSV[1][$c+5] = 'A';
		
		// Write Objective Rows
		for ($j=0; $j<count($SLOs[$i]['objectives']); $j++)
		{
			initRow($CSV, $numSLOs*7, $j+2);
			$row = $SLOs[$i]['objectives'][$j];
			
			$CSV[$j+2][$c+0] = $row['prefix'] . ' ' . $row['courseNumber'];
			$CSV[$j+2][$c+1] = $row['objectiveNumber'];
			if ($row['I']) $CSV[$j+2][$c+2] = 'I';
			if ($row['R']) $CSV[$j+2][$c+3] = 'R';
			if ($row['E']) $CSV[$j+2][$c+4] = 'E';
			if ($row['A']) $CSV[$j+2][$c+5] = 'A';
		}
	}
	
	
	
	// Construct File Name
	$filename = 'SLO_Export_' . date('m-d-Y') . '.csv';
	
	
	
	
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