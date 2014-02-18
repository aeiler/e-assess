<?php
	
	// Include Utilities PHP File
	require_once('_utilities.php');
	
	
	// Initialize Error Array
	$errors = array();
	
	
	// Start Session
	session_start();
	
	
	// Connect To Database
	$pdo = dbConnect();
	
	
	// Require Admin or Super User Login
	requireLogin(LEVEL_ADMIN, LEVEL_SU);
	
	
	// Handle Cancel Button
	if (isset($_POST['addUserCancel']) && $_POST['addUserCancel']) redirect('usersManage.php');
	
	
	// Handle Submit Button
	if (isset($_POST['addUserSubmit']) && $_POST['addUserSubmit'])
	{
		// Validate Form Data
		$dbData = array();
		
		// 1. Check Level
		$dbData['level'] = parseInt($_POST['level']);
		if ($dbData['level'] == LEVEL_SU && $_SESSION['level'] != LEVEL_SU || $dbData['level'] != LEVEL_FACULTY && $dbData['level'] != LEVEL_ADMIN && $dbData['level'] != LEVEL_SU)
			$errors[] = "Please specify account type.";
		
		
		// 2. Check Department
		if ($_SESSION['level'] == LEVEL_SU)
		{
			$dbData['fkDepartment'] = parseInt($_POST['department']);
			
			$stmt = $pdo->prepare("SELECT `status` FROM `Department` WHERE `ID` = :ID");
			$stmt->bindValue(':ID', $dbData['fkDepartment']);
			$success = $stmt->execute();
			if (!$success || ($row = $stmt->fetch(PDO::FETCH_ASSOC)) === FALSE || intval($row['status']) !== 1)
				$errors[] = "Please specify a department.";
		}
		else $dbData['fkDepartment'] = $_SESSION['fkDepartment'];
		
		
		// 3. Check First Name
		$dbData['firstName'] = parseStr($_POST['firstName']);
		if (isblank($dbData['firstName']))
			$errors[] = "First name must not be empty.";
		
		
		// 4. Check Last Name
		$dbData['lastName'] = parseStr($_POST['lastName']);
		if (isblank($dbData['lastName']))
			$errors[] = "Last name must not be empty.";
		
		
		// 5. Check Username
		$dbData['username'] = parseStr($_POST['username']);
		if (strlen($dbData['username']) < 2 || strlen($dbData['username']) > 10)
			$errors[] = "Invalid username. Username must be at least 2 characters and no more than 10 characters.";
		
		
		// 6. Check Password
		// 6.1. Check Presence of Passwords
		if (!isset($_POST['password1']) || !isset($_POST['password2']))
			$errors[] = "Password is a required field.";
		
		// 6.2. Check Passwords for Match
		else if (strcmp($_POST['password1'], $_POST['password2']) != 0)
			$errors[] = "Passwords do not match.";
		
		// 6.3. Check Password for Security
		else if (strlen($_POST['password1']) < 8 || preg_match('#\d#', $_POST['password1']) != 1)
			$errors[] = "Invalid password. Password must be at least 8 characters long and contain at least one digit.";
		
		// 6.4. Save Password Hash
		else
			$dbData['passwordHash'] = hasher(parseStr($_POST['password1']));
		
		
		// Submit New User to Database
		if (count($errors) == 0)
		{
			// 1. Prepare Data for Query
			$assigns = array();
			$params = array();
			
			// 1.1. Date Created
			$assigns[] = "`dateCreated` = NOW()";
			
			// 1.2. Variables in $dbData
			foreach ($dbData as $key => $value)
			{
				$assigns[] = "`$key` = :$key";
				$params[':' . $key] = $value;
			}
			
			// 1.3. Implode Assignment Variable
			$assigns = implode(', ', $assigns);
			
			
			// 2. Send Query
			$stmt = $pdo->prepare("INSERT INTO `User` SET $assigns");
			$success = $stmt->execute($params);
			
			
			// 3. Check for Errors
			if (!$success)
			{
				// Duplicate Username
				if (strcmp($stmt->errorCode(), '23000') == 0)
					$errors[] = "Username already exists in database.";
				
				// Unknown Error
				else
					$errors[] = "Unknown database error occurred.";
			}
			
			
			// 4. On Success, Refresh Page To Reflect New User
			else redirect('usersManage.php');
		}
	}
	
	
	// Populate Form Data With Correct Values
	$PAGEDATA = array();
	$PAGEDATA['firstName'] = parseStr($_POST['firstName']);
	$PAGEDATA['lastName'] = parseStr($_POST['lastName']);
	$PAGEDATA['username'] = parseStr($_POST['username']);
	$PAGEDATA['password1'] = parseStr($_POST['password1']);
	$PAGEDATA['level'] = parseInt($_POST['level']);
	$PAGEDATA['department'] = parseInt($_POST['department']);
	
?>




<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - Add User</title>
		<link rel="icon" href="media/favicon.ico" />
		
		<!-- Meta Information -->
		<meta charset="utf-8" />
		
		<!-- Stylesheets -->
		<link rel="stylesheet" type="text/css" href="css/_reset.css" />
		<link rel="stylesheet" type="text/css" href="css/_globalStyles.css" />

	<body>
		<?php require('html/header.php'); ?>
		
		<div id="siteContainer" class="pageWidth">
			<section id="addUser" class="bgField shadow corner center">
				<h2 class="title">Add User</h2>
				
				<?php outputErrorsHTML($errors); ?>
					
				<form name="addUser" action="" method="POST">
					<table class="formTable padded widthFull">
						
						<tr>
							<th>Account Type</th>
							<td>
								<label><input name="level" type="radio" value="<?php echo LEVEL_FACULTY; ?>" <?php if ($PAGEDATA['level'] == LEVEL_FACULTY) echo 'checked '; ?>/> Faculty</label><br />
								<label><input name="level" type="radio" value="<?php echo LEVEL_ADMIN; ?>" <?php if ($PAGEDATA['level'] == LEVEL_ADMIN) echo 'checked '; ?>/> Department Admin</label><br />
								<?php if ($_SESSION['level'] == LEVEL_SU) echo '<label><input name="level" type="radio" value="' . LEVEL_SU . '"' . (($PAGEDATA['level'] == LEVEL_SU) ? ' checked ' : '') . '/> System Admin</label><br />'; ?>
							</td>
						</tr>
						
						<?php
							if ($_SESSION['level'] == LEVEL_SU)
							{
						?>
						<tr>
							<th>Department</th>
							<td>
								<select name="department">
									<option value=""></option>
									<?php
										// Retrieve Current List of Departments
										$stmt = $pdo->prepare("SELECT `ID`, `name` FROM `Department` WHERE `status` = 1");
										$success = $stmt->execute();
										
										// Echo Departments as <option> Tags
										while ($success && ($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== FALSE)
										{
											echo '<option value="' . $row['ID'] . '"';
											if ($row['ID'] == $PAGEDATA['department']) echo " selected ";
											echo '>' . $row['name'] . '</option>';
										}
									?>
								</select>
							</td>
						</tr>
						<?php
							}
						?>
						
						<tr>
							<th>First Name</th>
							<td><input name="firstName" type="text" maxlength="35" value="<?php echo $PAGEDATA['firstName']; ?>" /></td>
						</tr>
						<tr>
							<th>Last Name</th>
							<td><input name="lastName" type="text" maxlength="35" value="<?php echo $PAGEDATA['lastName']; ?>" /></td>
						</tr>
						<tr>
							<th>Username</th>
							<td><input name="username" type="text" maxlength="10" value="<?php echo $PAGEDATA['username']; ?>" /></td>
						</tr>
						<tr>
							<th>Password</th>
							<td>
								<input name="password1" type="password" value="<?php echo $PAGEDATA['password1']; ?>" />
							</td>
						</tr>
						<tr>
							<th>Confirm Password</th>
							<td><input name="password2" type="password" /></td>
						</tr>
						<tr>
							<td colspan="2">
								<div>*Password must be at least 8 characters with at least one digit.</div>
								<input name="addUserSubmit" type="submit" value="Add User" /><input name="addUserCancel" type="submit" value="Cancel" />
							</td>
						</tr>
					</table>
				</form>
			</section>
			
			<?php require('html/footer.php'); ?>
		</div>
	</body>
</html>