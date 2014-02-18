<?php
	
	// Include Utilities PHP File
	require_once('_utilities.php');
	
	
	// Initialize Error Array
	$errors = array();
	
	
	// Start Session
	session_start();
	
	
	// Connect To Database
	$pdo = dbConnect();
	
	
	// Require User Login
	requireLogin();
	
	
	
	
	// Require Provided FCAR ID
	// 1. Attempt to Query Database for Supplied FCAR's Info
	$query = "
		SELECT
			`FCAR`.`ID` AS ID,
			`FCAR`.`fkCourse`,
			`FCAR`.`fkDegree`,
			`FCAR`.`fkUser`,
			`FCAR`.`dateSubmitted`,
			`Degree`.`name` AS degreeName,
			`Degree`.`code` AS degreeCode,
			`Course`.`name` AS courseName,
			`Course`.`description` AS courseDescription,
			`Course`.`prefix` AS coursePrefix,
			`Course`.`number` AS courseNumber,
			`User`.`firstName` AS userFirstName,
			`User`.`lastName` AS userLastName,
			`FCAR`.`year`,
			`FCAR`.`term`,
			`FCAR`.`section`,
			`FCAR`.`modification`,
			`FCAR`.`feedback`,
			`FCAR`.`reflection`,
			`FCAR`.`improvement`,
			
			IF(`FCAR`.`A`, `FCAR`.`A`, 0) AS A,
			IF(`FCAR`.`BPlus`, `FCAR`.`BPlus`, 0) AS BPlus,
			IF(`FCAR`.`B`, `FCAR`.`B`, 0) AS B,
			IF(`FCAR`.`CPlus`, `FCAR`.`CPlus`, 0) AS CPlus,
			IF(`FCAR`.`C`, `FCAR`.`C`, 0) AS C,
			IF(`FCAR`.`DPlus`, `FCAR`.`DPlus`, 0) AS DPlus,
			IF(`FCAR`.`D`, `FCAR`.`D`, 0) AS D,
			IF(`FCAR`.`F`, `FCAR`.`F`, 0) AS F,
			
			IF(`FCAR`.`A`, `FCAR`.`A`, 0) +
			IF(`FCAR`.`BPlus`, `FCAR`.`BPlus`, 0) +
			IF(`FCAR`.`B`, `FCAR`.`B`, 0) +
			IF(`FCAR`.`CPlus`, `FCAR`.`CPlus`, 0) +
			IF(`FCAR`.`C`, `FCAR`.`C`, 0) +
			IF(`FCAR`.`DPlus`, `FCAR`.`DPlus`, 0) +
			IF(`FCAR`.`D`, `FCAR`.`D`, 0) +
			IF(`FCAR`.`F`, `FCAR`.`F`, 0) AS gradeTotal
		
		FROM `FCAR`
		JOIN `Course`
		ON `Course`.`ID` = `FCAR`.`fkCourse`
		JOIN `User`
		ON `User`.`ID` = `FCAR`.`fkUser`
		JOIN `Degree`
		ON `Degree`.`ID` = `FCAR`.`fkDegree`
		WHERE
			`FCAR`.`ID` = :ID AND
			`FCAR`.`status` = 1
		LIMIT 1
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':ID', parseInt($_REQUEST['idFCAR']));
	$success = $stmt->execute();
	
	// 2. Ensure a Valid FCAR Was Found in the Database
	if (!$success || ($FCARInfo = $stmt->fetch(PDO::FETCH_ASSOC)) === FALSE)
	{
		redirect('FCARsManage.php');
	}
	
	// 3. If User is Faculty, Ensure That User Possesses This FCAR
	if ($_SESSION['level'] == LEVEL_FACULTY && $FCARInfo['fkUser'] != $_SESSION['ID'])
	{
		redirect('FCARsManage.php');
	}
	
	
	
	
	// Retrieve Objective List
	$query = "
		SELECT
			`Objective`.`ID` AS ID,
			`Objective`.`description`,
			`Objective`.`number`,
			`Evaluation`.`evaluation`
		FROM `Objective`
		JOIN `FCAR`
		ON `FCAR`.`ID` = :fkFCAR
		LEFT JOIN `Evaluation`
		ON
			`Evaluation`.`fkFCAR` = `FCAR`.`ID` AND
			`Evaluation`.`fkObjective` = `Objective`.`ID`
		WHERE
			`Objective`.`fkCourse` = :fkCourse AND
			`Objective`.`dateCreated` < `FCAR`.`dateCreated` AND
			(`Objective`.`status` = 1 OR `FCAR`.`dateCreated` < `Objective`.`dateModified`)
		ORDER BY `number`
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':fkCourse', $FCARInfo['fkCourse']);
	$stmt->bindValue(':fkFCAR', $FCARInfo['ID']);
	$success = $stmt->execute();
	if (!$success || ($objectives = $stmt->fetchAll(PDO::FETCH_ASSOC)) === FALSE)
	{
		redirect('FCARsManage.php');
	}
	
	
	
	
	// Retrieve SLO List
	$query = "
		SELECT DISTINCT
			`SLO`.`ID` AS ID,
			`SLO`.`code` AS code,
			`SLO`.`description`
		FROM `SLO`
		JOIN `SLOXDegree`
		ON `SLOXDegree`.`fkSLO` = `SLO`.`ID`
		JOIN `SLOXObjective`
		ON `SLOXObjective`.`fkSLO` = `SLO`.`ID`
		JOIN `Objective`
		ON `Objective`.`ID` = `SLOXObjective`.`fkObjective`
		JOIN `FCAR`
		ON `FCAR`.`ID` = :ID
		WHERE
			`SLOXDegree`.`fkDegree` = :fkDegree AND
			`Objective`.`fkCourse` = :fkCourse AND
			`Objective`.`dateCreated` < `FCAR`.`dateCreated` AND
			(`Objective`.`status` = 1 OR `FCAR`.`dateCreated` < `Objective`.`dateModified`) AND
			`SLOXObjective`.`dateCreated` < `FCAR`.`dateCreated` AND
			(`SLOXObjective`.`dateDisabled` IS NULL OR `FCAR`.`dateCreated` < `SLOXObjective`.`dateDisabled`)
		ORDER BY `SLO`.`code`
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':ID', $FCARInfo['ID']);
	$stmt->bindValue(':fkDegree', $FCARInfo['fkDegree']);
	$stmt->bindValue(':fkCourse', $FCARInfo['fkCourse']);
	$success = $stmt->execute();
	if (!$success || ($SLOs = $stmt->fetchAll(PDO::FETCH_ASSOC)) === FALSE)
	{
		redirect('FCARsManage.php');
	}
	
	
	
	
	// Retrieve SLOXObjective List
	$query = "
		SELECT
			`SLOXObjective`.`fkSLO`,
			`SLOXObjective`.`fkObjective`
		FROM `SLOXObjective`
		JOIN `Objective`
		ON `Objective`.`ID` = `SLOXObjective`.`fkObjective`
		JOIN `FCAR`
		ON `FCAR`.`ID` = :ID
		WHERE
			`Objective`.`fkCourse` = :fkCourse AND
			`Objective`.`dateCreated` < `FCAR`.`dateCreated` AND
			(`Objective`.`status` = 1 OR `FCAR`.`dateCreated` < `Objective`.`dateModified`) AND
			`SLOXObjective`.`dateCreated` < `FCAR`.`dateCreated` AND
			(`SLOXObjective`.`dateDisabled` IS NULL OR `FCAR`.`dateCreated` < `SLOXObjective`.`dateDisabled`)
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':ID', $FCARInfo['ID']);
	$stmt->bindValue(':fkCourse', $FCARInfo['fkCourse']);
	$success = $stmt->execute();
	if (!$success || ($SLOXObjective = $stmt->fetchAll(PDO::FETCH_ASSOC)) === FALSE)
	{
		redirect('FCARsManage.php');
	}
	
	
	
	
	// Retrieve Assessment List
	$query = "
		SELECT
			`ID`,
			`name`,
			`numSuccess`,
			`numEvaluated`
		FROM `Assessment`
		WHERE `fkFCAR` = :fkFCAR AND `status` = 1
		ORDER BY `dateCreated`
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':fkFCAR', $FCARInfo['ID']);
	$success = $stmt->execute();
	if (!$success || ($assessments = $stmt->fetchAll(PDO::FETCH_ASSOC)) === FALSE) redirect('FCARsManage.php');
	
	
	
	
	// Retrieve Result List
	$query = "
		SELECT
			`Result`.`fkAssessment`,
			`Result`.`fkObjective`
		FROM `Result`
		JOIN `Assessment`
		ON `Assessment`.`ID` = `Result`.`fkAssessment`
		WHERE `Assessment`.`fkFCAR` = :fkFCAR AND `Assessment`.`status` = 1
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':fkFCAR', $FCARInfo['ID']);
	$success = $stmt->execute();
	if (!$success || ($results = $stmt->fetchAll(PDO::FETCH_ASSOC)) === FALSE) redirect('FCARsManage.php');
	
	
	
	
	// Retrieve Aggregated Fraction Figures
	$query = "
		SELECT
			`Objective`.`ID` AS ID,
			`Objective`.`number`,
			SUM(`Assessment`.`numSuccess`) AS numSuccess,
			SUM(`Assessment`.`numEvaluated`) AS numEvaluated
		FROM `Objective`
		JOIN `Result`
		ON `Result`.`fkObjective` = `Objective`.`ID`
		JOIN `Assessment`
		ON `Assessment`.`ID` = `Result`.`fkAssessment`
		JOIN `FCAR`
		ON `FCAR`.`ID` = :ID
		WHERE
			`Assessment`.`fkFCAR` = :fkFCAR AND
			`Assessment`.`status` = 1 AND
			`Objective`.`dateCreated` < `FCAR`.`dateCreated` AND
			(`Objective`.`status` = 1 OR `FCAR`.`dateCreated` < `Objective`.`dateModified`)
		GROUP BY `Objective`.`ID`
		ORDER BY `Objective`.`number`
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':fkFCAR', $FCARInfo['ID']);
	$stmt->bindValue(':ID', $FCARInfo['ID']);
	$success = $stmt->execute();
	if (!$success || ($fractions = $stmt->fetchAll(PDO::FETCH_ASSOC)) === FALSE)
	{
		redirect('FCARView.php', array('idFCAR' => $FCARInfo['ID']));
	}
	
	
	
	
	
	
	
	
	
	
	
	
	// Include PDF Writer
	require_once('tcpdf/tcpdf.php');
	
	// Make PDF Class
	class PDF extends TCPDF
	{
		const UNIT = 'in';
		
		const PAGE_FORMAT = 'Letter';
		const PAGE_ORIENTATION = 'P';
		
		const MARGIN_L = 0.75;
		const MARGIN_R = 0.75;
		const MARGIN_T = 0.75;
		const MARGIN_B = 0.75;
		
		const FONT_TYPE = 'helvetica';
		const FONT_SIZE = 11;
		
		
		
		
		public $pageWidth = 8.5;
		
		
		
		
		function orphanControl($h)
		{
			$const = PDF::FONT_SIZE/72;
			
			if ($this->GetY() - PDF::MARGIN_T > ($this->getPageHeight() - PDF::MARGIN_T - PDF::MARGIN_B)/2)
				if ($this->GetY() + $h + $const > $this->getPageHeight() - PDF::MARGIN_B)
					$this->AddPage();
		}
		
		
		
		
		function h1($text)
		{
			// Note: Moves position down 52 points
			
			$this->SetX(PDF::MARGIN_L);
			$this->SetFont(PDF::FONT_TYPE, 'B', 16);
			$this->Cell(0, 0, $text, 0, 1, 'C');
			
			$this->Ln(36/72);
		}
		
		function h2($text)
		{
			// Note: Moves position down 46 points
			
			$this->Ln(20/72);
			
			$this->SetFont(PDF::FONT_TYPE, 'BU', 13);
			$this->Cell(0, 0, $text, 0, 1);
			
			$this->Ln(13/72);
		}
		
		function h3($text)
		{
			// Note: Moves position down 34 points
			
			$this->Ln(12/72);
			
			$this->SetFont(PDF::FONT_TYPE, 'B', 11);
			//$this->Cell(0, 0, $text, 0, 1);
			$this->MultiCell(0, 0, $text, 0, 'L');
			
			$this->Ln(11/72);
		}
		
		function p($text)
		{
			$this->SetFont(PDF::FONT_TYPE, '', PDF::FONT_SIZE);
			$this->orphanControl($this->getStringHeight(0, $text, TRUE));
			//$this->MultiCell(0, 0, $text, 0, 'L');
			$this->writeHTMLCell(0, 0, $this->GetX(), $this->GetY(), $text, 0, 1, 0, 1, 'L');
		}
	}
	
	
	
	
	
	// Create New PDF Object
	$title = 'Faculty Course Assessment Report';
	$pdf = new PDF(PDF::PAGE_ORIENTATION, PDF::UNIT, PDF::PAGE_FORMAT);
	
	// PDF Meta Infomation
	$pdf->SetCreator(PDF_CREATOR);
	$pdf->SetAuthor($FCARInfo['userFirstName'] . ' ' . $FCARInfo['userLastName']);
	$pdf->SetTitle($title);
	$pdf->SetSubject($title);
	$pdf->SetKeywords('Faculty Course Assessment Report, FCAR, e-Assess, assessment');
	
	// PDF Page Information
	$pdf->SetMargins(PDF::MARGIN_L, PDF::MARGIN_T, PDF::MARGIN_R);
	$pdf->SetAutoPageBreak(TRUE, PDF::MARGIN_B);
	$pdf->setPrintHeader(FALSE);
	$pdf->setPrintFooter(FALSE);
	$pdf->AddPage();
	
	
	
	
	
	
	// Add Document Title
	$pdf->h1($title);
	
	
	
	// Course Table
	$table = array();
	$table['Degree:'] = $FCARInfo['degreeName'] . ' (' . $FCARInfo['degreeCode'] . ')';
	$table['Course:'] = $FCARInfo['coursePrefix'] . ' ' . $FCARInfo['courseNumber'] . ' ' . $FCARInfo['section'] . ' - ' . $FCARInfo['courseName'];
	$table['Academic Term:'] = $FCARInfo['term'] . ' ' . $FCARInfo['year'];
	$table['Instructor:'] = $FCARInfo['userFirstName'] . ' ' . $FCARInfo['userLastName'];
	
	foreach($table as $left => $right)
	{
		$pdf->SetFont(PDF::FONT_TYPE, 'B', PDF::FONT_SIZE);
		$pdf->Cell(1.5, (PDF::FONT_SIZE + 6)/72, $left, 1);
		
		$pdf->SetFont(PDF::FONT_TYPE, '', PDF::FONT_SIZE);
		$pdf->Cell(1.5 - PDF::MARGIN_L - PDF::MARGIN_R, (PDF::FONT_SIZE + 6)/72, $right, 1, 1);
	}
	
	
	
	// Course Description
	$pdf->orphanControl(46/72 + $pdf->getStringHeight(0, $FCARInfo['courseDescription'], TRUE));
	$pdf->h2('Course Description:');
	$pdf->p($FCARInfo['courseDescription']);
	
	
	
	// Grade Distribution
	$pdf->orphanControl(46/72 + 2*(PDF::FONT_SIZE + 6)/72);
	$pdf->h2('Grade Distribution:');
	$table = array();
	$table['A']  = $FCARInfo['A'];
	$table['B+'] = $FCARInfo['BPlus'];
	$table['B']  = $FCARInfo['B'];
	$table['C+'] = $FCARInfo['CPlus'];
	$table['C']  = $FCARInfo['C'];
	$table['D+'] = $FCARInfo['DPlus'];
	$table['D']  = $FCARInfo['D'];
	$table['F']  = $FCARInfo['F'];
	$table['Total']  = $FCARInfo['gradeTotal'];
	
	$pdf->SetX((PDF::MARGIN_L + $pdf->getPageWidth() - PDF::MARGIN_R - 0.5*count($table))/2);
	foreach(array_keys($table) as $cell)
	{
		$pdf->SetFont(PDF::FONT_TYPE, 'B', PDF::FONT_SIZE);
		$pdf->Cell(0.5, (PDF::FONT_SIZE + 6)/72, $cell, 1, 0, 'C');
	}
	unset($cell);
	$pdf->Ln();
	$pdf->SetX((PDF::MARGIN_L + $pdf->getPageWidth() - PDF::MARGIN_R - 0.5*count($table))/2);
	foreach($table as $cell)
	{
		$pdf->SetFont(PDF::FONT_TYPE, '', PDF::FONT_SIZE);
		$pdf->Cell(0.5, (PDF::FONT_SIZE + 6)/72, $cell, 1);
	}
	unset($cell);
	$pdf->Ln();
	
	
	
	// Course Modifications
	$pdf->orphanControl(46/72 + $pdf->getStringHeight(0, $FCARInfo['modification'], TRUE));
	$pdf->h2('Modifications Made to Course:');
	$pdf->p($FCARInfo['modification']);
	
	
	
	// Course Objectives
	$pdf->orphanControl(46/72 + PDF::FONT_SIZE/72 + count($objectives)*(4 + PDF::FONT_SIZE)/72);
	$pdf->h2('Course Objectives:');
	$pdf->p('Upon successful completion of this course, students should be able to:');
	
	foreach ($objectives as $objective)
	{
		$pdf->Ln(4/72);
		$pdf->SetX(PDF::MARGIN_L + 0.25);
		$pdf->SetFont(PDF::FONT_TYPE, '', PDF::FONT_SIZE);
		$pdf->MultiCell(0, 0, $objective['number'] . '. ' . $objective['description'], 0, 'L');
	}
	unset($objective);
	
	
	
	// Student Learning Outcomes
	$pdf->orphanControl(46/72 + PDF::FONT_SIZE/72 + count($SLOs)*(4 + PDF::FONT_SIZE)/72);
	$pdf->h2('Student Learning Outcomes:');
	$pdf->p('Upon successful completion of this course, students should be able to:');
	
	foreach ($SLOs as $SLO)
	{
		$pdf->Ln(4/72);
		$pdf->SetX(PDF::MARGIN_L + 0.25);
		$pdf->SetFont(PDF::FONT_TYPE, '', PDF::FONT_SIZE);
		$pdf->MultiCell(0, 0, $SLO['code'] . '. ' . $SLO['description'], 0, 'L');
	}
	unset($SLO);
	
	
	
	// Course Objectives Mapped to Student Learning Outcomes
	// 1. Get Cell Width and Height Information
	$numSLOs = count($SLOs);
	$numObjectives = count($objectives);
	
	// 2. Handle Orphan Control
	$pdf->orphanControl(34/72 + (2 + $numObjectives)*(PDF::FONT_SIZE + 4)/72);
	
	// 3. Print Title
	$pdf->h3('Course Objectives Mapped to Student Learning Outcomes');
	
	// 4. Print "Course Objectives" and "Student Learning Outcomes" Header Cells
	$pdf->SetFont(PDF::FONT_TYPE, 'B', PDF::FONT_SIZE);
	$pdf->Cell(1.7, 2*(PDF::FONT_SIZE + 4)/72, 'Course Objectives', 1, 0, 'C');
	$pdf->Cell($pdf->getPageWidth() - PDF::MARGIN_L - PDF::MARGIN_R - 1.7, (PDF::FONT_SIZE + 4)/72, 'Student Learning Outcomes', 1, 1, 'C');
	
	// 5. Print SLO Header Cells
	$pdf->SetX(1.7 + PDF::MARGIN_L);
	foreach ($SLOs as $SLO)
	{
		$pdf->Cell(($pdf->getPageWidth() - PDF::MARGIN_L - PDF::MARGIN_R - 1.7)/$numSLOs, (PDF::FONT_SIZE + 4)/72, $SLO['code'], 1, 0, 'C');
	}
	unset($SLO);
	$pdf->Ln();
	
	// 6. Print Table Rows
	foreach ($objectives as $objective)
	{
		$pdf->SetFont(PDF::FONT_TYPE, 'B', PDF::FONT_SIZE);
		$pdf->Cell(1.7, (PDF::FONT_SIZE + 4)/72, $objective['number'], 1, 0, 'C');
		$pdf->SetFont(PDF::FONT_TYPE, '', PDF::FONT_SIZE);
		foreach ($SLOs as $SLO)
		{
			$val = (in_array(array('fkSLO' => $SLO['ID'], 'fkObjective' => $objective['ID']), $SLOXObjective)) ? 'X' : '';
			$pdf->Cell(($pdf->getPageWidth() - PDF::MARGIN_L - PDF::MARGIN_R - 1.7)/$numSLOs, (PDF::FONT_SIZE + 4)/72, $val, 1, 0, 'C');
		}
		unset($SLO);
		$pdf->Ln();
	}
	unset($objective);
	
	
	
	// Course Objective Assessment
	// 1. Get Cell Width and Height Information
	$numObjectives = count($objectives);
	$numAssessments = count($assessments);
	
	// 2. Handle Orphan Control
	$pdf->orphanControl(46/72 + (4 + $numAssessments)*(PDF::FONT_SIZE + 4)/72);
	
	// 3. Print Title
	$pdf->h2('Course Objective Assessment:');
	
	// 4. Print "Method of Assessment" and "Course Objective Measured" Header Cells
	$pdf->SetFont(PDF::FONT_TYPE, 'B', PDF::FONT_SIZE);
	$pdf->Cell(2, 2*(PDF::FONT_SIZE + 4)/72, 'Method of Assessment', 1, 0, 'C');
	$pdf->Cell(($pdf->getPageWidth() - PDF::MARGIN_L - PDF::MARGIN_R - 2), (PDF::FONT_SIZE + 4)/72, 'Course Objective Measured', 1, 1, 'C');
	
	// 5. Print Objective Header Cells
	$pdf->SetX(2 + PDF::MARGIN_L);
	$totals = array();
	foreach ($objectives as $objective)
	{
		$totals[$objective['ID']] = array('num' => 0, 'denom' => 0);
		$pdf->Cell(($pdf->getPageWidth() - PDF::MARGIN_L - PDF::MARGIN_R - 2)/$numObjectives, (PDF::FONT_SIZE + 4)/72, $objective['number'], 1, 0, 'C');
	}
	unset($objective);
	$pdf->Ln();
	
	// 6. Print Table Rows
	foreach ($assessments as $assessment)
	{
		// Get Row Height In Number of Lines
		$rh = $pdf->getNumLines($assessment['name'], 2);
		
		$pdf->SetFont(PDF::FONT_TYPE, 'B', PDF::FONT_SIZE);
		$pdf->MultiCell(2, (PDF::FONT_SIZE + 4)/72, $assessment['name'], 1, 'C', 0, 0);
		$pdf->SetFont(PDF::FONT_TYPE, '', PDF::FONT_SIZE);
		foreach ($objectives as $objective)
		{
			$val = '';
			if (in_array(array('fkAssessment' => $assessment['ID'], 'fkObjective' => $objective['ID']), $results))
			{
				$val = $assessment['numSuccess'] . '/' . $assessment['numEvaluated'];
				$totals[$objective['ID']]['num'] += $assessment['numSuccess'];
				$totals[$objective['ID']]['denom'] += $assessment['numEvaluated'];
			}
			$pdf->Cell(($pdf->getPageWidth() - PDF::MARGIN_L - PDF::MARGIN_R - 2)/$numObjectives, $rh*(PDF::FONT_SIZE + 2)/72 + 2/72, $val, 1, 0, 'C');
		}
		unset($objective);
		$pdf->Ln();
	}
	unset($assessment);
	
	// 7. Print Total Row
	$pdf->SetFont(PDF::FONT_TYPE, 'B', PDF::FONT_SIZE);
	$pdf->Cell(2, 2*(PDF::FONT_SIZE + 4)/72, 'Total', 1, 0, 'C');
	$pdf->SetFont(PDF::FONT_TYPE, '', PDF::FONT_SIZE);
	foreach ($objectives as $objective)
	{
		if ($totals[$objective['ID']]['denom'] > 0)
		{
			$val = $totals[$objective['ID']]['num'] . '/' . $totals[$objective['ID']]['denom'] . "\n";
			$val .= '(' . round($totals[$objective['ID']]['num'] / $totals[$objective['ID']]['denom'] * 100) . '%)';
		}
		else
		{
			$val = '';
		}
		$pdf->MultiCell(($pdf->getPageWidth() - PDF::MARGIN_L - PDF::MARGIN_R - 2)/$numObjectives, 2*(PDF::FONT_SIZE + 4)/72, $val, 1, 'C', 0, 0);
	}
	unset($objective);
	$pdf->Ln();
	
	
	
	// Evaluation of Course Objectives
	foreach ($objectives as $objective)
	{
		$pdf->orphanControl(34/72 + $pdf->getStringHeight(0, $objective['evaluation'], TRUE));
		$pdf->h3('Evaluation of Course Objective ' . $objective['number'] . ': ' . $objective['description']);
		$pdf->p($objective['evaluation']);
	}
	unset($objective);
	
	
	
	// Report for Assessment Committee
	// 1. Get Cell Width Information
	$numSLOs = count($SLOs);
	$numObjectives = count($objectives);
	
	// 2. Handle Orphan Control
	$pdf->orphanControl(46/72 + (4 + $numObjectives)*(PDF::FONT_SIZE + 4)/72);
	
	// 3. Print Title
	$pdf->h2('Report for Assessment Committee:');
	
	// 4. Print "Course Objectives" and "Student Learning Outcomes" Header Cells
	$pdf->SetFont(PDF::FONT_TYPE, 'B', PDF::FONT_SIZE);
	$pdf->Cell(1.7, 2*(PDF::FONT_SIZE + 4)/72, 'Course Objectives', 1, 0, 'C');
	$pdf->Cell($pdf->getPageWidth() - PDF::MARGIN_L - PDF::MARGIN_R - 1.7, (PDF::FONT_SIZE + 4)/72, 'Student Learning Outcomes', 1, 1, 'C');
	
	// 5. Print SLO Header Cells
	$pdf->SetX(1.7 + PDF::MARGIN_L);
	$totals = array();
	foreach ($SLOs as $SLO)
	{
		$pdf->Cell(($pdf->getPageWidth() - PDF::MARGIN_L - PDF::MARGIN_R - 1.7)/$numSLOs, (PDF::FONT_SIZE + 4)/72, $SLO['code'], 1, 0, 'C');
		$totals[$SLO['ID']] = array('numSuccess' => 0, 'numEvaluated' => 0);
	}
	unset($SLO);
	$pdf->Ln();
	
	// 6. Print Table Rows
	foreach ($fractions as $fraction)
	{
		$pdf->SetFont(PDF::FONT_TYPE, 'B', PDF::FONT_SIZE);
		$pdf->Cell(1.7, (PDF::FONT_SIZE + 4)/72, $fraction['number'], 1, 0, 'C');
		$pdf->SetFont(PDF::FONT_TYPE, '', PDF::FONT_SIZE);
		foreach ($SLOs as $SLO)
		{
			$val = '';
			if (in_array(array('fkSLO' => $SLO['ID'], 'fkObjective' => $fraction['ID']), $SLOXObjective))
			{
				$val = $fraction['numSuccess'] . '/' . $fraction['numEvaluated'];
				$totals[$SLO['ID']]['numSuccess'] += $fraction['numSuccess'];
				$totals[$SLO['ID']]['numEvaluated'] += $fraction['numEvaluated'];
			}
			$pdf->Cell(($pdf->getPageWidth() - PDF::MARGIN_L - PDF::MARGIN_R - 1.7)/$numSLOs, (PDF::FONT_SIZE + 4)/72, $val, 1, 0, 'C');
		}
		unset($SLO);
		$pdf->Ln();
	}
	unset($fraction);
	
	// 7. Print Total Row
	$pdf->SetFont(PDF::FONT_TYPE, 'B', PDF::FONT_SIZE);
	$pdf->Cell(1.7, 2*(PDF::FONT_SIZE + 4)/72, 'Total', 1, 0, 'C');
	$pdf->SetFont(PDF::FONT_TYPE, '', PDF::FONT_SIZE);
	foreach ($SLOs as $SLO)
	{
		if ($totals[$SLO['ID']]['numEvaluated'] != 0)
		{
			$val = $totals[$SLO['ID']]['numSuccess'] . '/' . $totals[$SLO['ID']]['numEvaluated'] . "\n";
			$val .= '(' . round($totals[$SLO['ID']]['numSuccess'] / $totals[$SLO['ID']]['numEvaluated'] * 100) . '%)';
		}
		else
		{
			$val = '-';
		}
		$pdf->MultiCell(($pdf->getPageWidth() - PDF::MARGIN_L - PDF::MARGIN_R - 1.7)/$numSLOs, 2*(PDF::FONT_SIZE + 4)/72, $val, 1, 'C', 0, 0);
	}
	unset($SLO);
	unset($totals);
	$pdf->Ln();
	
	
	// Student Feedback
	$pdf->orphanControl(46/72 + $pdf->getStringHeight(0, $FCARInfo['feedback'], TRUE));
	$pdf->h2('Student Feedback:');
	$pdf->p($FCARInfo['feedback']);
	
	
	
	// Reflections
	$pdf->orphanControl(46/72 + $pdf->getStringHeight(0, $FCARInfo['reflection'], TRUE));
	$pdf->h2('Reflections:');
	$pdf->p($FCARInfo['reflection']);
	
	
	
	// Improvements
	$pdf->orphanControl(46/72 + $pdf->getStringHeight(0, $FCARInfo['improvement'], TRUE));
	$pdf->h2('Proposed Actions for Course Improvement:');
	$pdf->p($FCARInfo['improvement']);
	
	
	
	
	
	// Output PDF File
	$filename = array();
	$filename[] = $FCARInfo['year'] . $FCARInfo['term'];
	$filename[] = $FCARInfo['coursePrefix'] . $FCARInfo['courseNumber'];
	$filename[] = $FCARInfo['section'];
	$filename[] = $FCARInfo['userLastName'];
	$pdf->Output(implode('_', $filename) . '.pdf');
?>
