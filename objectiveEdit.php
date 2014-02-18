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
	
	
	
	
	// Require Provided Objective ID
	// 1. Attempt to Query Database for Supplied Objective's Info
	$query = "
		SELECT DISTINCT
			`Objective`.`ID` AS ID,
			`Objective`.`fkCourse` AS fkCourse,
			`Objective`.`description` AS description,
			`Objective`.`number` AS number,
			`Course`.`prefix` AS coursePrefix,
			`Course`.`number` AS courseNumber,
			IF(`Coordinator`.`fkUser` IS NULL, 0, 1) AS coordinator
		FROM `Objective`
		JOIN `Course`
		ON `Course`.`ID` = `Objective`.`fkCourse`
		LEFT JOIN `Coordinator`
		ON `Coordinator`.`fkCourse` = `Course`.`ID` AND `Coordinator`.`fkUser` = :fkUser
		WHERE
			`Objective`.`ID` = :ID AND
			`Course`.`fkDepartment` = :fkDepartment AND
			`Course`.`status` = 1
		LIMIT 1
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':ID', parseInt($_REQUEST['idObjective']));
	$stmt->bindValue(':fkDepartment', $_SESSION['fkDepartment']);
	$stmt->bindValue(':fkUser', $_SESSION['ID']);
	$success = $stmt->execute();
	
	// 2. Ensure a Valid Objective Was Found in the Database
	if (!$success || ($objectiveInfo = $stmt->fetch(PDO::FETCH_ASSOC)) === FALSE)
	{
		redirect('coursesManage.php');
	}
	
	// 3. If Logged In User Is Faculty, Check For Coordinator Permissions
	if ($_SESSION['level'] == LEVEL_FACULTY && $courseInfo['coordinator'] === 0)
	{
		redirect('coursesManage.php');
	}
	
	
	
	
	// Retrieve List of SLOs From Database
	$query = "
		SELECT DISTINCT
			`SLO`.`ID` AS ID,
			`SLO`.`code` AS code,
			`SLO`.`description`,
			IF(`SLOXObjective`.`fkSLO` IS NULL, 0, 1) AS objective,
			IF(`SLOXObjective`.`I` = 1, 1, 0) AS I,
			IF(`SLOXObjective`.`R` = 1, 1, 0) AS R,
			IF(`SLOXObjective`.`E` = 1, 1, 0) AS E,
			IF(`SLOXObjective`.`A` = 1, 1, 0) AS A,
			GROUP_CONCAT(`Degree`.`code` ORDER BY `Degree`.`code` SEPARATOR ', ') AS degrees
		FROM `SLO`
		JOIN `SLOXDegree`
		ON `SLOXDegree`.`fkSLO` = `SLO`.`ID`
		JOIN `Degree`
		ON `SLOXDegree`.`fkDegree` = `Degree`.`ID`
		LEFT JOIN `SLOXObjective`
		ON
			`SLOXObjective`.`fkSLO` = `SLO`.`ID` AND
			`SLOXObjective`.`fkObjective` = :fkObjective AND
			`SLOXObjective`.`dateDisabled` IS NULL AND
			`SLO`.`status` = 1
		WHERE `SLO`.`status` = 1 AND `Degree`.`fkDepartment` = :fkDepartment
		GROUP BY `SLO`.`ID`
		ORDER BY `SLO`.`code`
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':fkObjective', $objectiveInfo['ID']);
	$stmt->bindValue(':fkDepartment', $_SESSION['fkDepartment']);
	$success = $stmt->execute();
	if (!$success || ($SLOs = $stmt->fetchAll(PDO::FETCH_ASSOC)) === FALSE)
	{
		redirect('objectivesManage.php', array('idCourse' => $objectiveInfo['fkCourse']));
	}
	
	
	
	
	// Handle Cancel Button
	if (isset($_POST['editObjectiveCancel']) && $_POST['editObjectiveCancel'])
	{
		redirect('objectivesManage.php', array('idCourse' => $objectiveInfo['fkCourse']));
	}
	
	
	// Handle Submit Button
	if (isset($_POST['editObjectiveSubmit']) && $_POST['editObjectiveSubmit'])
	{
		// Validate Form Data
		$dbData = array();
		
		// 1. Check Objective Number
		$dbData['number'] = parseInt($_POST['number']);
		if ($dbData['number'] == -1) $errors[] = "Objective number must not be empty.";
		
		// 2. Check Objective Description
		$dbData['description'] = ucfirst(parseStr($_POST['description']));
		if (isblank($dbData['description']))
		{
			$errors[] = "Objective description must not be empty.";
		}
		
		
		// Submit Objective Changes to Database
		if (count($errors) == 0)
		{
			// 1. Prepare Data for Query
			$assigns = array();
			$params = array();
			
			// 1.1. Objective ID
			$params[':ID'] = $objectiveInfo['ID'];
			
			// 1.2. Date Modified
			$assigns[] = "`dateModified` = NOW()";
			
			// 1.3. User Last Modified
			$assigns[] = "`fkUserModified` = :fkUserModified";
			$params[':fkUserModified'] = $_SESSION['ID'];
			
			// 1.4. Variables in $dbData
			foreach ($dbData as $key => $value)
			{
				$assigns[] = "`$key` = :$key";
				$params[':' . $key] = $value;
			}
			
			// 1.5. Implode Assignment Variable
			$assigns = implode(', ', $assigns);
			
			
			// 2. Send Query
			$stmt = $pdo->prepare("UPDATE `Objective` SET $assigns WHERE `ID` = :ID");
			$success = $stmt->execute($params);
			
			
			// 3. Check for Errors
			if (!$success)
			{
				$errors[] = "Unknown database error occurred.";
			}
			
			
			// 4. Add Objective-SLO Mappings
			else
			{
				// Loop Through SLOs
				foreach ($SLOs as $SLO)
				{
					$oldMap = intval($SLO['objective']);
					$newMap = (isset($_POST['SLO']) && in_array($SLO['ID'], $_POST['SLO']));
					
					// Add New Objective-SLO Mapping
					if (!$oldMap && $newMap)
					{
						$query = "
							INSERT INTO `SLOXObjective`
							SET
								`fkObjective` = :fkObjective,
								`fkSLO` = :fkSLO,
								`I` = :I,
								`R` = :R,
								`E` = :E,
								`A` = :A,
								`dateCreated` = NOW()
						";
						$stmt = $pdo->prepare($query);
						$stmt->bindValue(':fkObjective', $objectiveInfo['ID']);
						$stmt->bindValue(':fkSLO', $SLO['ID']);
						$stmt->bindValue(':I', (isset($_POST['flag'][$SLO['ID']]) && in_array('I', $_POST['flag'][$SLO['ID']])));
						$stmt->bindValue(':R', (isset($_POST['flag'][$SLO['ID']]) && in_array('R', $_POST['flag'][$SLO['ID']])));
						$stmt->bindValue(':E', (isset($_POST['flag'][$SLO['ID']]) && in_array('E', $_POST['flag'][$SLO['ID']])));
						$stmt->bindValue(':A', (isset($_POST['flag'][$SLO['ID']]) && in_array('A', $_POST['flag'][$SLO['ID']])));
						$success = $stmt->execute();
						if (!$success) $errors[] = "Internal database error: Could not add new SLO-Objective mapping.";
					}
					
					// Disable Current Objective-SLO Mapping
					else if ($oldMap && !$newMap)
					{
						$query = "
							UPDATE `SLOXObjective`
							SET `dateDisabled` = NOW()
							WHERE
								`fkObjective` = :fkObjective AND
								`fkSLO` = :fkSLO AND
								`dateDisabled` IS NULL
						";
						$stmt = $pdo->prepare($query);
						$stmt->bindValue(':fkObjective', $objectiveInfo['ID']);
						$stmt->bindValue(':fkSLO', $SLO['ID']);
						$success = $stmt->execute();
						if (!$success)
						{
							$errors[] = "Internal database error: Could not delete old Objective-SLO mappings.";
						}
					}
					
					// Update IREA
					else if ($oldMap && $newMap)
					{
						$query = "
							UPDATE `SLOXObjective`
							SET
								`I` = :I,
								`R` = :R,
								`E` = :E,
								`A` = :A
							WHERE
								`fkObjective` = :fkObjective AND
								`fkSLO` = :fkSLO AND
								`dateDisabled` IS NULL
						";
						$stmt = $pdo->prepare($query);
						$stmt->bindValue(':fkObjective', $objectiveInfo['ID']);
						$stmt->bindValue(':fkSLO', $SLO['ID']);
						$stmt->bindValue(':I', (isset($_POST['flag'][$SLO['ID']]) && in_array('I', $_POST['flag'][$SLO['ID']])));
						$stmt->bindValue(':R', (isset($_POST['flag'][$SLO['ID']]) && in_array('R', $_POST['flag'][$SLO['ID']])));
						$stmt->bindValue(':E', (isset($_POST['flag'][$SLO['ID']]) && in_array('E', $_POST['flag'][$SLO['ID']])));
						$stmt->bindValue(':A', (isset($_POST['flag'][$SLO['ID']]) && in_array('A', $_POST['flag'][$SLO['ID']])));
						$success = $stmt->execute();
						if (!$success)
						{
							$errors[] = "Internal database error: Could not update old Objective-SLO mappings.";
						}
					}
				}
				unset($SLO);
				unset($oldMap);
				unset($newMap);
			}
			
			
			// 5. On Success, Refresh Page To Reflect New Objective
			if (count($errors) == 0)
			{
				redirect('objectivesManage.php', array('idCourse' => $objectiveInfo['fkCourse']));
			}
		}
	}
	
	
	
	
	// Populate Form Data With Correct Values
	$PAGEDATA = array();
	if (isset($_POST['editObjectiveSubmit']) && $_POST['editObjectiveSubmit'])
	{
		$PAGEDATA['description'] = ucfirst(parseStr($_POST['description']));
		$PAGEDATA['number'] = parseStr($_POST['number']);
		
		// Handle SLO Checkboxes
		foreach($SLOs as &$SLO)
		{
			$SLO['objective'] = (isset($_POST['SLO']) && in_array($SLO['ID'], $_POST['SLO'])) ? 1 : 0;
			$SLO['I'] = (isset($_POST['flag'][$SLO['ID']]) && in_array('I', $_POST['flag'][$SLO['ID']])) ? 1 : 0;
			$SLO['R'] = (isset($_POST['flag'][$SLO['ID']]) && in_array('R', $_POST['flag'][$SLO['ID']])) ? 1 : 0;
			$SLO['E'] = (isset($_POST['flag'][$SLO['ID']]) && in_array('E', $_POST['flag'][$SLO['ID']])) ? 1 : 0;
			$SLO['A'] = (isset($_POST['flag'][$SLO['ID']]) && in_array('A', $_POST['flag'][$SLO['ID']])) ? 1 : 0;
		}
		unset($SLO);
	}
	else
	{
		$PAGEDATA['description'] = ucfirst($objectiveInfo['description']);
		$PAGEDATA['number'] = $objectiveInfo['number'];
	}
	
?>




<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - Edit Objective for <?php echo $objectiveInfo['coursePrefix'] . ' ' . $objectiveInfo['courseNumber']; ?></title>
		<link rel="icon" href="media/favicon.ico" />
		
		<!-- Meta Information -->
		<meta charset="utf-8" />
		
		<!-- Stylesheets -->
		<link rel="stylesheet" type="text/css" href="css/_reset.css" />
		<link rel="stylesheet" type="text/css" href="css/_globalStyles.css" />
		
		<!-- JQuery -->
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
		
		<!-- Custom Javascript -->
		<script type="text/javascript">
			$(function() {
				var $form = $('form[name="editSLO"]');
				
				$('input[name="SLO[]"]', $form).not(':checked').each(function() {
					$('input[name="flag[' + $(this).val() + '][]"]', $form).attr("disabled", true);
				});
				
				$('input[name="SLO[]"]', $form).change(function() {
					$('input[name="flag[' + $(this).val() + '][]"]', $form).attr("disabled", !$(this).is(':checked'));
				});
			});
		</script>
	</head>
	
	<body>
		<?php require('html/header.php'); ?>
		
		<div id="siteContainer" class="pageWidth">
			<section class="bgField shadow corner center">
				<h2 class="title">Edit Objective for <?php echo $objectiveInfo['coursePrefix'] . ' ' . $objectiveInfo['courseNumber']; ?></h2>
				
				<?php outputErrorsHTML($errors); ?>
				
				<form name="editSLO" action="" method="POST">
					<table class="formTable padded widthFull">
						<tr>
							<th>Number</th>
							<td colspan="3"><input name="number" type="text" value="<?php echo $PAGEDATA['number']; ?>" /></td>
						</tr>
						<tr>
							<th>Description</th>
							<td colspan="3"><textarea name="description"><?php echo $PAGEDATA['description']; ?></textarea></td>
						</tr>
						<tr>
							<th>SLOs</th>
							<td colspan="3"></td>
						</tr>
						<?php
							foreach($SLOs as $SLO)
							{
								echo '<tr>';
								echo '<td></td>';
								echo '<td><input type="checkbox" name="SLO[]" value="' . $SLO['ID'] . '" ';
								if ($SLO['objective']) echo 'checked ';
								echo '/></td>';
								echo '<td>' . $SLO['code'] . '</td>';
								
								echo '<td>';
								echo '<p>' . $SLO['description'] . '</p>';
								echo '<label><input type="checkbox" name="flag[' . $SLO['ID'] . '][]" value="I" ' . (($SLO['I']) ? 'checked ' : '') . '/> Introduced</label><br />' . "\n";
								echo '<label><input type="checkbox" name="flag[' . $SLO['ID'] . '][]" value="R" ' . (($SLO['R']) ? 'checked ' : '') . '/> Reinforced</label><br />' . "\n";
								echo '<label><input type="checkbox" name="flag[' . $SLO['ID'] . '][]" value="E" ' . (($SLO['E']) ? 'checked ' : '') . '/> Emphasized</label><br />' . "\n";
								echo '<label><input type="checkbox" name="flag[' . $SLO['ID'] . '][]" value="A" ' . (($SLO['A']) ? 'checked ' : '') . '/> Assessed</label><br />' . "\n";
								echo '</td>';
								
								echo '</tr>';
							}
							unset($SLO);
						?>
						<tr>
							<td colspan="4"><input name="editObjectiveSubmit" type="submit" value="Save Changes" /><input name="editObjectiveCancel" type="submit" value="Cancel" /></td>
						</tr>
					</table>
				</form>
			</section>
			
			<?php require('html/footer.php'); ?>
		</div>
	</body>
</html>