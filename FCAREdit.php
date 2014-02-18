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
			`FCAR`.`fkUser` = :fkUser AND
			`FCAR`.`status` = 1 AND
			`FCAR`.`dateSubmitted` IS NULL
		LIMIT 1
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':ID', parseInt($_REQUEST['idFCAR']));
	$stmt->bindValue(':fkUser', $_SESSION['ID']);
	$success = $stmt->execute();
	
	// 2. Ensure a Valid FCAR Was Found in the Database
	if (!$success || ($FCARInfo = $stmt->fetch(PDO::FETCH_ASSOC)) === FALSE)
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
		redirect('FCARView.php', array('idFCAR' => $FCARInfo['ID']));
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
		redirect('FCARView.php', array('idFCAR' => $FCARInfo['ID']));
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
		redirect('FCARView.php', array('idFCAR' => $FCARInfo['ID']));
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
	if (!$success || ($assessments = $stmt->fetchAll(PDO::FETCH_ASSOC)) === FALSE)
	{
		redirect('FCARView.php', array('idFCAR' => $FCARInfo['ID']));
	}
	
	
	
	
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
	if (!$success || ($results = $stmt->fetchAll(PDO::FETCH_ASSOC)) === FALSE)
	{
		redirect('FCARView.php', array('idFCAR' => $FCARInfo['ID']));
	}
	
	
	
	
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
	
	
	
	
	// Handle Cancel Button
	if (isset($_POST['editFCARCancel']) && $_POST['editFCARCancel'])
	{
		redirect('FCARView.php', array('idFCAR' => $FCARInfo['ID']));
	}
	
	
	// Handle Submit Button
	if (isset($_POST['editFCARSubmit']) && $_POST['editFCARSubmit'])
	{
		// Prepare Data for FCAR Update Query
		$assigns = array();
		$params = array();
		
		// 1. Date Modified
		$assigns[] = "`dateModified` = NOW()";
		
		// 2. FCAR ID
		$params[':ID'] = $FCARInfo['ID'];
		
		// 3. Grades
		$assigns[] = "`A` = :A";
		$assigns[] = "`BPlus` = :BPlus";
		$assigns[] = "`B` = :B";
		$assigns[] = "`CPlus` = :CPlus";
		$assigns[] = "`C` = :C";
		$assigns[] = "`DPlus` = :DPlus";
		$assigns[] = "`D` = :D";
		$assigns[] = "`F` = :F";
		$params[':A'] = parseInt($_POST['A'], 0);
		$params[':BPlus'] = parseInt($_POST['BPlus'], 0);
		$params[':B'] = parseInt($_POST['B'], 0);
		$params[':CPlus'] = parseInt($_POST['CPlus'], 0);
		$params[':C'] = parseInt($_POST['C'], 0);
		$params[':DPlus'] = parseInt($_POST['DPlus'], 0);
		$params[':D'] = parseInt($_POST['D'], 0);
		$params[':F'] = parseInt($_POST['F'], 0);
		
		// 4. Course Modifications
		$assigns[] = "`modification` = :modification";
		$params[':modification'] = parseStr($_POST['modification']);
		
		// 5. Student Feedback
		$assigns[] = "`feedback` = :feedback";
		$params[':feedback'] = parseStr($_POST['feedback']);
		
		// 6. Reflections
		$assigns[] = "`reflection` = :reflection";
		$params[':reflection'] = parseStr($_POST['reflection']);
		
		// 7. Course Improvement
		$assigns[] = "`improvement` = :improvement";
		$params[':improvement'] = parseStr($_POST['improvement']);
		
		// 8. Year
		if (!isblank($_POST['year']))
		{
			$assigns[] = "`year` = :year";
			$params[':year'] = parseInt($_POST['year']);
		}
		
		// 9. Term
		if (!isblank($_POST['term']))
		{
			$assigns[] = "`term` = :term";
			$params[':term'] = parseStr($_POST['term']);
		}
		
		// 10. Section
		if (!isblank($_POST['section']))
		{
			$assigns[] = "`section` = :section";
			$params[':section'] = parseStr($_POST['section']);
		}
		
		// 11. Implode Assignment Variable
		$assigns = implode(', ', $assigns);
		
		
		// Send FCAR Update Query
		$stmt = $pdo->prepare("UPDATE `FCAR` SET $assigns WHERE ID = :ID");
		$success = $stmt->execute($params);
		if (!$success)
		{
			$errors[] = "Unknown database error occurred during FCAR update.";
		}
		
		
		// Handle Objective Evaluation Textarea Updates
		if (isset($_POST['evaluation']))
		{
			foreach ($objectives as $objective)
			{
				$query = "
					INSERT INTO `Evaluation`
					SET
						`fkFCAR` = :fkFCAR,
						`fkObjective` = :fkObjective,
						`evaluation` = :evaluation,
						`dateCreated` = NOW()
					ON DUPLICATE KEY UPDATE
						`evaluation` = VALUES(`evaluation`),
						`status` = 1
				";
				$stmt = $pdo->prepare($query);
				$stmt->bindValue(':fkFCAR', $FCARInfo['ID']);
				$stmt->bindValue(':fkObjective', $objective['ID']);
				$stmt->bindValue(':evaluation', parseStr($_POST['evaluation'][$objective['ID']]));
				$success = $stmt->execute();
				
				if (!$success)
				{
					$errors[] = "Unknown database error occurred during evaluation update.";
				}
			}
			unset($objective);
		}
		
		
		// Handle HTML Assessment/Result Table Row Updates
		if (isset($_POST['assessment']))
		{
			foreach ($_POST['assessment'] as $ID => $assessment)
			{
				// 1. Ensure Name Field Is Not Blank
				if (isblank($assessment['name']))
				{
					continue;
				}
				
				
				// 2. Update Assessment
				$query = "
					UPDATE `Assessment`
					SET
						`name` = :name,
						`numSuccess` = :numSuccess,
						`numEvaluated` = :numEvaluated,
						`status` = :status
					WHERE `ID` = :ID AND `fkFCAR` = :fkFCAR AND `status` = 1
				";
				$stmt = $pdo->prepare($query);
				$stmt->bindValue(':name', parseStr($assessment['name']));
				$stmt->bindValue(':numSuccess', parseInt($assessment['numSuccess'], 0));
				$stmt->bindValue(':numEvaluated', parseInt($assessment['numEvaluated'], 0));
				$stmt->bindValue(':ID', $ID);
				$stmt->bindValue(':fkFCAR', $FCARInfo['ID']);
				$stmt->bindValue(':status', (isset($assessment['delete'])) ? 0 : 1);
				$success = $stmt->execute();
				if (!$success) $errors[] = "Cannot edit assessment.";
				
				// 3. Remove All Old Results
				$stmt = $pdo->prepare("DELETE FROM `Result` WHERE `fkAssessment` = :fkAssessment");
				$stmt->bindValue(':fkAssessment', $ID);
				$success = $stmt->execute();
				if (!$success) $errors[] = "Cannot remove old results.";
				
				// 4. Add New Results
				foreach($assessment['result'] as $fkObjective)
				{
					$stmt = $pdo->prepare("INSERT INTO `Result` SET `fkAssessment` = :fkAssessment, `fkObjective` = :fkObjective");
					$stmt->bindValue(':fkAssessment', $ID);
					$stmt->bindValue(':fkObjective', $fkObjective);
					$success = $stmt->execute();
					if (!$success) $errors[] = "Cannot add new result.";
				}
				unset($fkObjective);
			}
			unset($assessment);
			unset($ID);
		}
		
		
		// Handle New HTML Assessment/Result Rows
		if (isset($_POST['newAssessment']))
		{
			foreach ($_POST['newAssessment'] as $newAssessment)
			{
				// 1. Ensure Field Name Is Not Blank
				if (isblank($newAssessment['name'])) continue;
				
				// 2. Insert Into Database Table `Assessment`
				$query = "
					INSERT INTO `Assessment`
					SET
						`fkFCAR` = :fkFCAR,
						`name` = :name,
						`numSuccess` = :numSuccess,
						`numEvaluated` = :numEvaluated,
						`dateCreated` = NOW()
				";
				$stmt = $pdo->prepare($query);
				$stmt->bindValue(':name', parseStr($newAssessment['name']));
				$stmt->bindValue(':numSuccess', parseInt($newAssessment['numSuccess'], 0));
				$stmt->bindValue(':numEvaluated', parseInt($newAssessment['numEvaluated'], 0));
				$stmt->bindValue(':fkFCAR', $FCARInfo['ID']);
				$success = $stmt->execute();
				if (!$success) $errors[] = "Cannot insert new assessment into database.";
				
				// 3. Insert Into Database Table `Result`
				else
				{
					$fkAssessment = intval($pdo->lastInsertId());
					
					foreach($newAssessment['result'] as $fkObjective)
					{
						$stmt = $pdo->prepare("INSERT INTO `Result` SET `fkAssessment` = :fkAssessment, `fkObjective` = :fkObjective");
						$stmt->bindValue(':fkAssessment', $fkAssessment);
						$stmt->bindValue(':fkObjective', $fkObjective);
						$success = $stmt->execute();
						if (!$success) $errors[] = "Cannot create result mapping.";
					}
					
					unset($fkObjective);
					unset($fkAssessment);
				}
			}
			unset($newAssessment);
		}
		
		
		// Refresh Page To Reflect New FCAR
		if (count($errors) == 0) redirect('FCARView.php', array('idFCAR' => $FCARInfo['ID']));
	}
	
	
?>




<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - Edit Assessment Report</title>
		<link rel="icon" href="media/favicon.ico" />
		
		<!-- Meta Information -->
		<meta charset="utf-8" />
		
		<!-- Stylesheets -->
		<link rel="stylesheet" type="text/css" href="css/_reset.css" />
		<link rel="stylesheet" type="text/css" href="css/_globalStyles.css" />
		<link rel="stylesheet" type="text/css" href="css/_FCARStyles.css" />
		
		<!-- JQuery -->
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
		
		<!-- TinyMCE -->
		<script src="//tinymce.cachefly.net/4.0/tinymce.min.js"></script>
		<script>
			var formHasChanged = false;
			
			tinymce.init({
				selector: 'textarea',
				setup: function (ed) {
					ed.on("change", function (ed) {
						formHasChanged = true;
					});
				}
			});
		</script>
		
		<!-- Custom Javascript -->
		<script type="text/javascript">
			$(function() {
				var numRows = 0,
				    formSubmitted = false;
				
				$('#newRowButton').click(function() {
					formHasChanged = true;
					
					var newRow = '<tr>'
						+ '<td><input type="text" name="newAssessment[' + numRows + '][name]" placeholder="Name" /></td>'
						+ '<td>'
						+ '<input type="text" name="newAssessment[' + numRows + '][numSuccess]" placeholder="Passed" />'
						+ ' / '
						+ '<input type="text" name="newAssessment[' + numRows + '][numEvaluated]" placeholder="Total" />'
						+ '</td>'
						+ '<td></td>';
					
					<?php
						foreach ($objectives as $objective)
						{
					?>
					newRow += '<td><input type="checkbox" name="newAssessment[' + numRows + '][result][]" value="<?php echo $objective['ID']; ?>" /></td>';
					<?php
						}
						unset($objective);
					?>
					
					newRow += '</tr>';
					
					$('.assessmentTable tr:last').after(newRow);
					$('input[name="newAssessment[' + numRows + '][name]"]').focus();
					
					numRows++;
				});
				
				$('form[name="editFCAR"]').submit(function() {
					formSubmitted = true;
				});
				$('input, select, textarea', 'form[name="editFCAR"]').change(function () {
					formHasChanged = true;
				});
				
				$(window).on('beforeunload', function() {
					if (!formSubmitted && formHasChanged) return 'If you leave this page, any unsaved changes to your FCAR will be lost.';
				});
				
			});
		</script>
		
	</head>
	
	<body>
		<?php require('html/header.php'); ?>
		
		<div id="siteContainer" class="pageWidth">
			<section class="bgField shadow corner center">
				<h2 class="title">Edit Assessment Report</h2>
				
				<?php outputErrorsHTML($errors); ?>
				
				<form name="editFCAR" action="" method="POST">
					
					<p>
						<input name="editFCARSubmit" type="submit" value="Save Changes" />
						<input name="editFCARCancel" type="submit" value="Cancel Changes" />
					</p>
					
					<table class="courseTable">
						<tr>
							<td class="header">Degree</td>
							<td><?php echo $FCARInfo['degreeName'] . ' (' . $FCARInfo['degreeCode'] . ')'; ?></td>
						</tr>
						<tr>
							<td class="header">Course</td>
							<td>
								<?php
									echo $FCARInfo['coursePrefix'] . ' ' . $FCARInfo['courseNumber'];
									echo ' <input name="section" type="text" value="' . $FCARInfo['section'] . '" /> - ';
									echo $FCARInfo['courseName'];
								?>
							</td>
						</tr>
						<tr>
							<td class="header">Academic Term</td>
							<td>
								<select name="term">
									<?php
										$terms = array(
											'FA' => 'Fall',
											'WI' => 'Winter',
											'SP' => 'Spring',
											'MY' => 'May',
											'S1' => 'Summer 1',
											'S2' => 'Summer 2'
										);
										
										foreach ($terms as $key => $value)
										{
											echo '<option value="' . $key . '"';
											if (strcmp($FCARInfo['term'], $key) == 0) echo ' selected';
											echo '>' . $key . ' ' . $value . '</option>';
										}
									?>
								</select>
								
								<select name="year">
									<?php
										for ($i=-1; $i<2; $i++)
										{
											$year = intval(strftime('%Y')) + $i;
											echo '<option value="' . $year . '"';
											if (strcmp($FCARInfo['year'], $year) == 0)
											{
												echo ' selected';
											}
											echo '>' . $year . '</option>';
										}
									?>
								</select>
							</td>
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
							<td><input type="text" name="A" value="<?php echo $FCARInfo['A']; ?>" /></td>
							<td><input type="text" name="BPlus" value="<?php echo $FCARInfo['BPlus']; ?>" /></td>
							<td><input type="text" name="B" value="<?php echo $FCARInfo['B']; ?>" /></td>
							<td><input type="text" name="CPlus" value="<?php echo $FCARInfo['CPlus']; ?>" /></td>
							<td><input type="text" name="C" value="<?php echo $FCARInfo['C']; ?>" /></td>
							<td><input type="text" name="DPlus" value="<?php echo $FCARInfo['DPlus']; ?>" /></td>
							<td><input type="text" name="D" value="<?php echo $FCARInfo['D']; ?>" /></td>
							<td><input type="text" name="F" value="<?php echo $FCARInfo['F']; ?>" /></td>
							<td><?php echo $FCARInfo['gradeTotal']; ?></td>
						</tr>
					</table>
					
					<h3>Modifications Made to Course</h3>
					<textarea name="modification"><?php echo $FCARInfo['modification']; ?></textarea>
					
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
							<th rowspan="2">Success/Total</th>
							<th rowspan="2">Delete</th>
							<th colspan="<?php echo count($objectives); ?>">Course Objective Measured</th>
						</tr>
						
						<tr>
							<?php
								foreach ($objectives as $objective)
									echo '<th>' . $objective['number'] . '</th>' . "\n";
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
							<td>
								<input type="text" name="assessment[<?php echo $assessment['ID']; ?>][name]" value="<?php echo $assessment['name']; ?>" />
							</td>
							
							<td>
								<input type="text" name="assessment[<?php echo $assessment['ID']; ?>][numSuccess]" value="<?php echo $assessment['numSuccess']; ?>" /> /
								<input type="text" name="assessment[<?php echo $assessment['ID']; ?>][numEvaluated]" value="<?php echo $assessment['numEvaluated']; ?>" />
							</td>
							
							<td><input type="checkbox" name="assessment[<?php echo $assessment['ID']; ?>][delete]" /></td>
							
							<?php
								foreach ($objectives as $objective)
								{
									echo '<td>';
									echo '<input type="checkbox" name="assessment[' . $assessment['ID'] . '][result][]" value="' . $objective['ID'] . '" ';
									if (in_array(array('fkAssessment' => $assessment['ID'], 'fkObjective' => $objective['ID']), $results)) echo 'checked ';
									echo '/></td>';
								}
								unset($objective);
							?>
						</tr>
						<?php
							}
							unset($assessment);
						?>
					</table>
					<button type="button" id="newRowButton">Add Row</button>
					
					<?php
						foreach ($objectives as $objective)
						{
					?>
					<h4>Evaluation of Course Objective <?php echo $objective['number'] . ': ' . $objective['description']; ?></h4>
					<textarea name="evaluation[<?php echo $objective['ID']; ?>]"><?php echo $objective['evaluation']; ?></textarea>
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
					<textarea name="feedback"><?php echo $FCARInfo['feedback']; ?></textarea>
					
					<h3>Reflections</h3>
					<textarea name="reflection"><?php echo $FCARInfo['reflection']; ?></textarea>
					
					<h3>Proposed Actions for Course Improvement</h3>
					<textarea name="improvement"><?php echo $FCARInfo['improvement']; ?></textarea>
					
					<p>
						<input name="editFCARSubmit" type="submit" value="Save Changes" />
						<input name="editFCARCancel" type="submit" value="Cancel Changes" />
					</p>
					
				</form>
			</section>
			
			<?php require('html/footer.php'); ?>
		</div>
	</body>
</html>