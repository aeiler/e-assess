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
	
	
	
	
	// Require Provided Course ID
	// 1. Attempt to Query Database for Supplied Course's Info
	$query = "
		SELECT
			`Course`.`ID`,
			`Course`.`name`,
			`Course`.`description`,
			`Course`.`prefix`,
			`Course`.`number`,
			IF(`Coordinator`.`fkUser` IS NULL, 0, 1) AS coordinator
		FROM `Course`
		LEFT JOIN `Coordinator`
		ON `Coordinator`.`fkCourse` = `Course`.`ID` AND `Coordinator`.`fkUser` = :fkUser
		WHERE `Course`.`ID` = :ID AND `Course`.`fkDepartment` = :fkDepartment AND `Course`.`status` = 1
		LIMIT 1
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':ID', parseInt($_REQUEST['idCourse']));
	$stmt->bindValue(':fkUser', $_SESSION['ID']);
	$stmt->bindValue(':fkDepartment', $_SESSION['fkDepartment']);
	$success = $stmt->execute();
	
	// 2. Ensure a Valid Course Was Found in the Database
	if (!$success || ($courseInfo = $stmt->fetch(PDO::FETCH_ASSOC)) === FALSE)
	{
		redirect('objectivesManage.php', array('idCourse' => $courseInfo['ID']));
	}
	
	// 3. If Logged In User Is Faculty, Check For Coordinator Permissions
	if ($_SESSION['level'] == LEVEL_FACULTY && $courseInfo['coordinator'] === 0)
	{
		redirect('objectivesManage.php', array('idCourse' => $courseInfo['ID']));
	}
	
	
	
	
	// Handle Cancel Button
	if (isset($_POST['addObjectiveCancel']) && $_POST['addObjectiveCancel'])
	{
		redirect('objectivesManage.php', array('idCourse' => $courseInfo['ID']));
	}
	
	
	// Handle Submit Button
	if (isset($_POST['addObjectiveSubmit']) && $_POST['addObjectiveSubmit'])
	{
		// Validate Form Data
		$dbData = array();
		
		// 1. Check Objective Number
		$dbData['number'] = parseInt($_POST['number']);
		if ($dbData['number'] == -1)
		{
			$errors[] = "Objective number must not be empty.";
		}
		
		// 2. Check Objective Description
		$dbData['description'] = ucfirst(parseStr($_POST['description']));
		if (isblank($dbData['description']))
		{
			$errors[] = "Objective description must not be empty.";
		}
		
		
		// Submit New Objective to Database
		if (count($errors) == 0)
		{
			// 1. Prepare Data for Query
			$assigns = array();
			$params = array();
			
			// 1.1. Date Created
			$assigns[] = "`dateCreated` = NOW()";
			
			// 1.2. Date Modified
			$assigns[] = "`dateModified` = NOW()";
			
			// 1.3. User Modified
			$assigns[] = "`fkUserModified` = :fkUserModified";
			$params[':fkUserModified'] = $_SESSION['ID'];
			
			// 1.4. Variables in $dbData
			foreach ($dbData as $key => $value)
			{
				$assigns[] = "`$key` = :$key";
				$params[':' . $key] = $value;
			}
			
			// 1.5. Course ID
			$assigns[] = "`fkCourse` = :fkCourse";
			$params[':fkCourse'] = $courseInfo['ID'];
			
			// 1.6. Implode Assignment Variable
			$assigns = implode(', ', $assigns);
			
			
			// 2. Send Query
			$stmt = $pdo->prepare("INSERT INTO `Objective` SET $assigns");
			$success = $stmt->execute($params);
			
			
			// 3. Check for Errors
			if (!$success)
			{
				$errors[] = "Unknown database error occurred.";
			}
			
			
			// 4. Add Objective-SLO Mappings
			else if (isset($_POST['SLO']))
			{
				$fkObjective = intval($pdo->lastInsertId());
				foreach($_POST['SLO'] as $idSLO)
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
					$stmt->bindValue(':fkObjective', $fkObjective);
					$stmt->bindValue(':fkSLO', $idSLO);
					$stmt->bindValue(':I', (isset($_POST['flag'][$idSLO]) && in_array('I', $_POST['flag'][$idSLO])));
					$stmt->bindValue(':R', (isset($_POST['flag'][$idSLO]) && in_array('R', $_POST['flag'][$idSLO])));
					$stmt->bindValue(':E', (isset($_POST['flag'][$idSLO]) && in_array('E', $_POST['flag'][$idSLO])));
					$stmt->bindValue(':A', (isset($_POST['flag'][$idSLO]) && in_array('A', $_POST['flag'][$idSLO])));
					$success = $stmt->execute();
					if (!$success)
					{
						$errors[] = "Internal database error: Could not add new SLO-Objective mapping.";
					}
				}
			}
			
			
			// 5. On Success, Refresh Page To Reflect New Objective
			if (count($errors) == 0)
			{
				redirect('objectivesManage.php', array('idCourse' => $courseInfo['ID']));
			}
		}
	}
	
	
	
	
	// Populate Form Data With Correct Values
	$PAGEDATA = array();
	$PAGEDATA['description'] = ucfirst(parseStr($_POST['description']));
	$PAGEDATA['number'] = parseStr($_POST['number']);
	
	// 1. Retrieve List of SLOs From Database
	$query = "
		SELECT DISTINCT
			`SLO`.`ID` AS ID,
			`SLO`.`code` AS code,
			`SLO`.`description`,
			GROUP_CONCAT(`Degree`.`code` ORDER BY `Degree`.`code` SEPARATOR ', ') AS degrees
		FROM `SLO`
		JOIN `SLOXDegree`
		ON `SLOXDegree`.`fkSLO` = `SLO`.`ID`
		JOIN `Degree`
		ON `SLOXDegree`.`fkDegree` = `Degree`.`ID`
		WHERE `SLO`.`status` = 1 AND `Degree`.`fkDepartment` = :fkDepartment
		GROUP BY `SLO`.`ID`
		ORDER BY `SLO`.`code`
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':fkDepartment', $_SESSION['fkDepartment']);
	$success = $stmt->execute();
	if (!$success || ($SLOs = $stmt->fetchAll(PDO::FETCH_ASSOC)) === FALSE)
	{
		redirect('objectivesManage.php', array('idCourse' => $courseInfo['ID']));
	}
	
	
	// 2. Handle SLO Checkboxes
	foreach($SLOs as &$SLO)
	{
		$SLO['objective'] = (isset($_POST['SLO']) && in_array($SLO['ID'], $_POST['SLO'])) ? 1 : 0;
		$SLO['I'] = (isset($_POST['flag'][$SLO['ID']]) && in_array('I', $_POST['flag'][$SLO['ID']])) ? 1 : 0;
		$SLO['R'] = (isset($_POST['flag'][$SLO['ID']]) && in_array('R', $_POST['flag'][$SLO['ID']])) ? 1 : 0;
		$SLO['E'] = (isset($_POST['flag'][$SLO['ID']]) && in_array('E', $_POST['flag'][$SLO['ID']])) ? 1 : 0;
		$SLO['A'] = (isset($_POST['flag'][$SLO['ID']]) && in_array('A', $_POST['flag'][$SLO['ID']])) ? 1 : 0;
	}
	unset($SLO);
	
?>




<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - Add Objective for <?php echo $courseInfo['prefix'] . ' ' . $courseInfo['number']; ?></title>
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
				var $form = $('form[name="addSLO"]');
				
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
				<h2 class="title">Add Objective for <?php echo $courseInfo['prefix'] . ' ' . $courseInfo['number']; ?></h2>
				
				<?php outputErrorsHTML($errors); ?>
				
				<form name="addSLO" action="" method="POST">
					<table class="formTable padded widthFull">
						<tr>
							<th>Number</th>
							<td colspan="3"><input name="number" type="text" value="<?php echo $PAGEDATA['number']; ?>" autofocus /></td>
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
								echo '<td><input type="checkbox" name="SLO[]" value="' . $SLO['ID'] . '"';
								if ($SLO['objective']) echo ' checked ';
								echo '></td>';
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
							<td colspan="4"><input name="addObjectiveSubmit" type="submit" value="Add Objective" /><input name="addObjectiveCancel" type="submit" value="Cancel" /></td>
						</tr>
					</table>
				</form>
			</section>
			
			<?php require('html/footer.php'); ?>
		</div>
	</body>
</html>