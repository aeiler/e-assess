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
	
	
	// Set Redirect Path
	if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], "FCAREdit.php") === FALSE)
	{
		$redirect = $_SERVER['HTTP_REFERER'];
	}
	else
	{
		$redirect = 'FCARsManage.php';
	}
	
	
	
	
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
		redirect($redirect);
	}
	
	// 3. If User is Faculty, Ensure That User Possesses This FCAR
	if ($_SESSION['level'] == LEVEL_FACULTY && $FCARInfo['fkUser'] != $_SESSION['ID'])
	{
		redirect($redirect);
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
		redirect($redirect);
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
		redirect($redirect);
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
		redirect($redirect);
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
	if (!$success || ($assessments = $stmt->fetchAll(PDO::FETCH_ASSOC)) === FALSE) redirect($redirect);
	
	
	
	
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
	if (!$success || ($results = $stmt->fetchAll(PDO::FETCH_ASSOC)) === FALSE) redirect($redirect);
	
	
	
	
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
	
	
?>




<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - View Assessment Report</title>
		<link rel="icon" href="media/favicon.ico" />
		
		<!-- Meta Information -->
		<meta charset="utf-8" />
		
		<!-- Stylesheets -->
		<link rel="stylesheet" type="text/css" href="css/_reset.css" />
		<link rel="stylesheet" type="text/css" href="css/_globalStyles.css" />
		<link rel="stylesheet" type="text/css" href="css/_FCARStyles.css" />
	</head>
	
	<body>
		<?php require('html/header.php'); ?>
		
		<div id="siteContainer" class="pageWidth">
			<section class="bgField shadow corner center">
				<h2 class="title">View Assessment Report</h2>
				
				<?php outputErrorsHTML($errors); ?>
				
				<p>
					<?php
						if ($FCARInfo['fkUser'] == $_SESSION['ID'] && $FCARInfo['dateSubmitted'] == NULL)
						{
							echo '<a href="FCAREdit.php?idFCAR=' . $FCARInfo['ID'] . '" class="button">Edit</a>' . "\n";
							echo '<a href="FCARSubmit.php?idFCAR=' . $FCARInfo['ID'] . '" class="button">Submit</a>';
						}
					?>
					<a href="<?php echo $redirect; ?>" class="button">Done</a>
					<a href="FCARExport.php?idFCAR=<?php echo $FCARInfo['ID']; ?>" target="_blank" class="button">Export to PDF</a>
				</p>
				
				<table class="courseTable">
					<tr>
						<td class="header">Degree</td>
						<td><?php echo $FCARInfo['degreeName'] . ' (' . $FCARInfo['degreeCode'] . ')'; ?></td>
					</tr>
					<tr>
						<td class="header">Course</td>
						<td><?php echo $FCARInfo['coursePrefix'] . ' ' . $FCARInfo['courseNumber'] . ' ' . $FCARInfo['section'] . ' - ' . $FCARInfo['courseName']; ?></td>
					</tr>
					<tr>
						<td class="header">Academic Term</td>
						<td><?php echo $FCARInfo['term'] . ' ' . $FCARInfo['year']; ?></td>
					</tr>
					<tr>
						<td class="header">Instructor</td>
						<td><?php echo $FCARInfo['userFirstName'] . ' ' . $FCARInfo['userLastName']; ?></td>
					</tr>
				</table>
				
				<h3>Course Description</h3>
				<p><?php echo $FCARInfo['courseDescription']; ?></p>
				
				<h3>Grade Distribution</h3>
				<table class="gradeTable">
					<tr>
						<th class="header">A</th>
						<th class="header">B+</th>
						<th class="header">B</th>
						<th class="header">C+</th>
						<th class="header">C</th>
						<th class="header">D+</th>
						<th class="header">D</th>
						<th class="header">F</th>
						<th class="header">Total</th>
					</tr>
					<tr>
						<td><?php echo $FCARInfo['A']; ?></td>
						<td><?php echo $FCARInfo['BPlus']; ?></td>
						<td><?php echo $FCARInfo['B']; ?></td>
						<td><?php echo $FCARInfo['CPlus']; ?></td>
						<td><?php echo $FCARInfo['C']; ?></td>
						<td><?php echo $FCARInfo['DPlus']; ?></td>
						<td><?php echo $FCARInfo['D']; ?></td>
						<td><?php echo $FCARInfo['F']; ?></td>
						<td><?php echo $FCARInfo['gradeTotal']; ?></td>
					</tr>
				</table>
				
				<h3>Modifications Made to Course</h3>
				<p><?php echo $FCARInfo['modification']; ?></p>
				
				<h3>Course Objectives</h3>
				<p>Upon successful completion of this course, students should be able to</p>
				<p>
					<?php
						foreach ($objectives as $objective)
							echo $objective['number'] . '. ' . $objective['description'] . '<br />' . "\n";
						unset($objective);
					?>
				</p>
				
				<h3>Student Learning Outcomes</h3>
				<p>Upon successful completion of this course, students should be able to</p>
				<p>
					<?php
						foreach ($SLOs as $SLO)
							echo $SLO['code'] . '. ' . $SLO['description'] . '<br />' . "\n";
						unset($SLO);
					?>
				</p>
				
				<h4>Course Objectives Mapped to Student Learning Outcomes</h4>
				<table class="SLOXObjectiveTable">
					
					<tr>
						<th rowspan="2">Course Objectives</th>
						<th colspan="<?php echo count($SLOs); ?>">Student Learning Outcome</th>
					</tr>
					
					<tr>
						<?php
							foreach ($SLOs as $SLO)
								echo '<th>' . $SLO['code'] . '</th>';
							unset($SLO);
						?>
					</tr>
					
					<?php
						foreach ($objectives as $objective)
						{
							echo '<tr>';
							echo '<td class="header">' . $objective['number'] . '</td>';
							foreach ($SLOs as $SLO)
							{
								echo '<td>';
								if (in_array(array('fkSLO' => $SLO['ID'], 'fkObjective' => $objective['ID']), $SLOXObjective)) echo 'X';
								echo '</td>';
							}
							unset($SLO);
							echo '</tr>';
						}
						unset($objective);
					?>
				</table>
				
				<h3>Course Objective Assessment</h3>
				
				<table class="assessmentTable">
					<!-- Header Rows -->
					<tr>
						<th rowspan="2">Method of Assessment</th>
						<th colspan="<?php echo count($objectives); ?>">Course Objective Measured</th>
					</tr>
					
					<tr>
						<?php
							$totals = array();
							foreach ($objectives as $objective)
							{
								echo '<th>' . $objective['number'] . '</th>' . "\n";
								$totals[$objective['ID']] = array('num' => 0, 'denom' => 0);
							}
							unset($objective);
						?>
					</tr>
					<!-- End Header Rows -->
					
					<!-- Old Assessment Rows -->
					<?php
						foreach ($assessments as $assessment)
						{
					?>
					<tr>
						<th><?php echo $assessment['name']; ?></th>
						
						<?php
							foreach ($objectives as $objective)
							{
								echo '<td>';
								if (in_array(array('fkAssessment' => $assessment['ID'], 'fkObjective' => $objective['ID']), $results))
								{
									$totals[$objective['ID']]['num'] += $assessment['numSuccess'];
									$totals[$objective['ID']]['denom'] += $assessment['numEvaluated'];
									echo $assessment['numSuccess'] . '/' . $assessment['numEvaluated'];
								}
								echo '</td>' . "\n";
							}
							unset($objective);
						?>
					</tr>
					<?php
						}
						unset($assessment);
					?>
					<!-- End Old Assessment Rows -->
					
					<!-- Total Row -->
					<tr>
						<th>Total</th>
						
						<?php
							foreach ($objectives as $objective)
							{
								echo '<td>';
								if ($totals[$objective['ID']]['denom'] > 0)
								{
									echo $totals[$objective['ID']]['num'] . '/' . $totals[$objective['ID']]['denom'] . ' ';
									echo '(' . round($totals[$objective['ID']]['num'] / $totals[$objective['ID']]['denom'] * 100) . '%)';
								}
								echo "</td>\n";
							}
							unset($objective);
						?>
					</tr>
					<!-- End Total Row -->
				</table>
				
				<?php
					foreach ($objectives as $objective)
					{
				?>
				<h4>Evaluation of Course Objective <?php echo $objective['number'] . ': ' . $objective['description']; ?></h4>
				<p><?php echo $objective['evaluation']; ?></p>
				<?php
					}
					unset($objective);
				?>
				
				<h3>Report for Assessment Committee</h3>
				
				<table class="reportTable">
					<tr>
						<th rowspan="2">CO</th>
						<th colspan="<?php echo count($SLOs); ?>">SLO</th>
					</tr>
					
					<tr>
						<?php
							$totals = array();
							foreach ($SLOs as $SLO)
							{
								echo '<th>' . $SLO['code'] . '</th>';
								$totals[$SLO['ID']] = array('numSuccess' => 0, 'numEvaluated' => 0);
							}
							unset($SLO);
						?>
					</tr>
					
					<?php
						foreach ($fractions as $fraction)
						{
							echo '<tr>';
							echo '<td class="header">' . $fraction['number'] . '</td>';
							foreach ($SLOs as $SLO)
							{
								echo '<td>';
								if (in_array(array('fkSLO' => $SLO['ID'], 'fkObjective' => $fraction['ID']), $SLOXObjective))
								{
									echo $fraction['numSuccess'] . '/' . $fraction['numEvaluated'];
									$totals[$SLO['ID']]['numSuccess'] += $fraction['numSuccess'];
									$totals[$SLO['ID']]['numEvaluated'] += $fraction['numEvaluated'];
								}
								echo '</td>';
							}
							unset($SLO);
							echo '</tr>';
						}
						unset($fraction);
					?>
					
					<tr>
						<td class="header">Total</td>
						
						<?php
							foreach ($SLOs as $SLO)
							{
								echo '<td>';
								if ($totals[$SLO['ID']]['numEvaluated'] != 0)
								{
									echo $totals[$SLO['ID']]['numSuccess'] . '/' . $totals[$SLO['ID']]['numEvaluated'] . ' ';
									echo '(' . round($totals[$SLO['ID']]['numSuccess'] / $totals[$SLO['ID']]['numEvaluated'] * 100) . '%)';
								}
								else
									echo '-';
								echo '</td>';
							}
							unset($SLO);
							unset($totals);
						?>
						
					</tr>
				</table>
				
				<h3>Student Feedback</h3>
				<p><?php echo $FCARInfo['feedback']; ?></p>
				
				<h3>Reflections</h3>
				<p><?php echo $FCARInfo['reflection']; ?></p>
				
				<h3>Proposed Actions for Course Improvement</h3>
				<p><?php echo $FCARInfo['improvement']; ?></p>
				
				<p>
					<?php
						if ($FCARInfo['fkUser'] == $_SESSION['ID'] && $FCARInfo['dateSubmitted'] == NULL)
						{
							echo '<a href="FCAREdit.php?idFCAR=' . $FCARInfo['ID'] . '" class="button">Edit</a>' . "\n";
							echo '<a href="FCARSubmit.php?idFCAR=' . $FCARInfo['ID'] . '" class="button">Submit</a>';
						}
					?>
					<a href="<?php echo $redirect; ?>" class="button">Done</a>
					<a href="FCARExport.php?idFCAR=<?php echo $FCARInfo['ID']; ?>" target="_blank" class="button">Export to PDF</a>
				</p>
			</section>
			
			<?php require('html/footer.php'); ?>
		</div>
	</body>
</html>