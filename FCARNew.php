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
	
	
	
	
	// Retrieve List of Degrees From Database
	$query = "
		SELECT `ID`, `name`, `code`
		FROM `Degree`
		WHERE `Degree`.`status` = 1 AND `Degree`.`fkDepartment` = :fkDepartment
		ORDER BY `Degree`.`name`
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':fkDepartment', $_SESSION['fkDepartment']);
	$success = $stmt->execute();
	if (!$success || ($degrees = $stmt->fetchAll(PDO::FETCH_ASSOC)) === FALSE) redirect('FCARsManage.php');
	
	
	
	
	// Retrieve List of Courses From Database
	$query = "
		SELECT
			`Course`.`ID` AS ID,
			`Course`.`name`,
			`Course`.`description` AS description,
			`Course`.`prefix`,
			`Course`.`number` AS number,
			GROUP_CONCAT(DISTINCT `SLOXDegree`.`fkDegree` SEPARATOR ' ') AS degrees
		FROM `Course`
		JOIN `Objective`
		ON `Objective`.`fkCourse` = `Course`.`ID`
		JOIN `SLOXObjective`
		ON `SLOXObjective`.`fkObjective` = `Objective`.`ID`
		JOIN `SLO`
		ON `SLO`.`ID` = `SLOXObjective`.`fkSLO`
		JOIN `SLOXDegree`
		ON `SLOXDegree`.`fkSLO` = `SLO`.`ID`
		WHERE
			`Course`.`fkDepartment` = :fkDepartment AND
			`Course`.`status` = 1 AND
			`Objective`.`status` = 1 AND
			`SLOXObjective`.`dateDisabled` IS NULL AND
			`SLO`.`status` = 1
		GROUP BY `Course`.`ID`
		ORDER BY `Course`.`prefix`, `Course`.`number`
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':fkDepartment', $_SESSION['fkDepartment']);
	$success = $stmt->execute();
	if (!$success || ($courses = $stmt->fetchAll(PDO::FETCH_ASSOC)) === FALSE) redirect('FCARsManage.php');
	
	
	
	
	// Handle Cancel Button
	if (isset($_POST['addFCARCancel']) && $_POST['addFCARCancel']) redirect('FCARsManage.php');
	
	
	// Handle Submit Button
	if (isset($_POST['addFCARSubmit']) && $_POST['addFCARSubmit'])
	{
		// Validate Form Data
		$dbData = array();
		
		// 1. Check FCAR Year
		$dbData['year'] = parseStr($_POST['year']);
		if (isblank($dbData['year'])) $errors[] = "Academic year must not be empty.";
		
		// 2. Check FCAR Term
		$dbData['term'] = parseStr($_POST['term']);
		if (isblank($dbData['term'])) $errors[] = "Academic term must not be empty.";
		
		// 3. Check FCAR Section
		$dbData['section'] = parseStr($_POST['section']);
		if (isblank($dbData['section'])) $errors[] = "Section must not be empty.";
		
		// 4. Check FCAR Course
		$dbData['fkCourse'] = parseInt($_POST['course']);
		if ($dbData['fkCourse'] == -1) $errors[] = "A course must be selected.";
		
		// 5. Check FCAR Degree
		$dbData['fkDegree'] = parseInt($_POST['degree']);
		if ($dbData['fkDegree'] == -1) $errors[] = "A degree must be selected.";
		
		// 6. Check for Valid Degree and Course
		if ($dbData['fkCourse'] != -1 && $dbData['fkDegree'] != -1)
		{
			$query = "
				SELECT
					COUNT(*) AS num
				FROM `Course`
				JOIN `Objective`
				ON `Objective`.`fkCourse` = `Course`.`ID`
				JOIN `SLOXObjective`
				ON `SLOXObjective`.`fkObjective` = `Objective`.`ID`
				JOIN `SLO`
				ON `SLO`.`ID` = `SLOXObjective`.`fkSLO`
				JOIN `SLOXDegree`
				ON `SLOXDegree`.`fkSLO` = `SLO`.`ID`
				JOIN `Degree`
				ON `Degree`.`ID` = `SLOXDegree`.`fkDegree`
				WHERE
					`Course`.`status` = 1 AND
					`Objective`.`status` = 1 AND
					`SLOXObjective`.`dateDisabled` IS NULL AND
					`SLO`.`status` = 1 AND
					`Course`.`fkDepartment` = :fkDepartment1 AND
					`Degree`.`fkDepartment` = :fkDepartment2 AND
					`Objective`.`fkCourse` = :fkCourse AND
					`SLOXDegree`.`fkDegree` = :fkDegree
			";
			$stmt = $pdo->prepare($query);
			$stmt->bindValue(':fkDepartment1', $_SESSION['fkDepartment']);
			$stmt->bindValue(':fkDepartment2', $_SESSION['fkDepartment']);
			$stmt->bindValue(':fkCourse', $dbData['fkCourse']);
			$stmt->bindValue(':fkDegree', $dbData['fkDegree']);
			$success = $stmt->execute();
			if (!$success || ($row = $stmt->fetch(PDO::FETCH_ASSOC)) === FALSE || $row['num'] == 0)
			{
				$errors[] = "Invalid degree/course combination.";
			}
		}
		
		
		// Submit New FCAR to Database
		if (count($errors) == 0)
		{
			// 1. Prepare Data for Query
			$assigns = array();
			$params = array();
			
			// 1.1. Date Created
			$assigns[] = "`dateCreated` = NOW()";
			
			// 1.2. Date Modified
			$assigns[] = "`dateModified` = NOW()";
			
			// 1.3. User
			$assigns[] = "`fkUser` = :fkUser";
			$params[':fkUser'] = $_SESSION['ID'];
			
			// 1.4. Variables in $dbData
			foreach ($dbData as $key => $value)
			{
				$assigns[] = "`$key` = :$key";
				$params[':' . $key] = $value;
			}
			
			// 1.5. Implode Assignment Variable
			$assigns = implode(', ', $assigns);
			
			
			// 2. Send Query
			$stmt = $pdo->prepare("INSERT INTO `FCAR` SET $assigns");
			$success = $stmt->execute($params);
			
			
			// 3. Check for Errors
			if (!$success) $errors[] = "Unknown database error occurred.";
			
			
			// 5. On Success, Refresh Page To Reflect New FCAR
			else
			{
				$idFCAR = $pdo->lastInsertId();
				redirect('FCAREdit.php', array('idFCAR' => $idFCAR));
			}
		}
	}
	
	
	
	
	// Populate Form Data With Correct Values
	$PAGEDATA = array();
	$PAGEDATA['year'] = parseInt($_POST['year'], strftime('%Y'));
	$PAGEDATA['term'] = parseStr($_POST['term']);
	$PAGEDATA['section'] = parseStr($_POST['section']);
	
	
	// 2. Handle Degree Radio Button
	foreach($degrees as &$degree)
		$degree['FCAR'] = (isset($_POST['degree']) && $degree['ID'] == $_POST['degree']) ? 1 : 0;
	unset($degree);
	
	
	
	// 4. Handle Course Radio Button
	foreach($courses as &$course)
		$course['FCAR'] = (isset($_POST['course']) && $course['ID'] == $_POST['course']) ? 1 : 0;
	unset($course);
?>




<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - Add Assessment Report</title>
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
				var $form = $('form[name="addFCAR"]');
				var degree = $('input[name="degree"]:checked', $form).val();
				
				if (degree != undefined)
				{
					$('input[name="course"]', $form).attr("disabled", true);
					$('input[name="course"].' + degree, $form).attr("disabled", false);
				}
				
				$('input[name="degree"]', $form).change(function() {
					$('input[name="course"]', $form).attr("disabled", true);
					$('input[name="course"].' + $(this).val(), $form).attr("disabled", false);
				});
			});
		</script>
	</head>

	<body>
		<?php require('html/header.php'); ?>
		
		<div id="siteContainer" class="pageWidth">
			<section class="bgField shadow corner center">
				<h2 class="title">Add Assessment Report</h2>
				
				<?php outputErrorsHTML($errors); ?>
				
				<form name="addFCAR" action="" method="POST">
					<table class="formTable padded widthFull">
						<tr>
							<th>Academic Year</th>
							<td>
								<select name="year">
									<?php
										//french changed to $i=-2 (from $i=-1) so that every-other-year classes could be counted
										for ($i=-2; $i<2; $i++)
										{
											$year = intval(strftime('%Y')) + $i;
											echo '<option value="' . $year . '"';
											if (strcmp($PAGEDATA['year'], $year) == 0)
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
							<th>Academic Term</th>
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
											if (strcmp($PAGEDATA['term'], $key) == 0) echo ' selected';
											echo '>' . $key . ' ' . $value . '</option>';
										}
									?>
								</select>
							</td>
						</tr>
						<tr>
							<th>Section</th>
							<td><input name="section" type="text" value="<?php echo $PAGEDATA['section']; ?>" /></td>
						</tr>
						<tr>
							<th>Degree</th>
							<td>
								<?php
									foreach($degrees as $degree)
									{
										echo '<label>';
										echo '<input type="radio" name="degree" value="' . $degree['ID'] . '"';
										if ($degree['FCAR']) echo ' checked ';
										echo '>' . $degree['name'] . ' (' . $degree['code'] . ')';
										echo '</label><br />';
									}
									unset($degree);
								?>
							</td>
						</tr>
						<tr>
							<th>Course</th>
							<td>
								<?php
									foreach($courses as $course)
									{
										echo '<label>';
										echo '<input type="radio" name="course" class="' . $course['degrees'] . '" value="' . $course['ID'] . '"';
										if ($course['FCAR']) echo ' checked ';
										echo '>' . $course['prefix'] . ' ' . $course['number'] . ' (' . $course['name'] . ')';
										echo '</label><br />';
									}
									unset($course);
								?>
							</td>
						</tr>
						<tr>
							<td colspan="2"><input name="addFCARSubmit" type="submit" value="Create" /><input name="addFCARCancel" type="submit" value="Cancel" /></td>
						</tr>
					</table>
				</form>
			</section>
			
			<?php require('html/footer.php'); ?>
		</div>
	</body>
</html>