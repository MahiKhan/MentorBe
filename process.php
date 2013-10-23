<?php
if(!isset($_SESSION)){
	session_start(); 
}
/* Contents:
1) $_POST['login'] --> Check input + connection, set cookies/session
2) $_POST['register'] --> Check input and insert into users db 
3) $_POST['selectInterests ']
 */
require_once 'conx.php'; 
define("MENTOR_LEVEL", 4);   
/* -------------------------------------------------------------------------------------------*/
// Login
if (isset( $_POST['login']) )  {
	// $username = mysqli_real_escape_string($conx, $_POST['username']);
	$username = $_POST['username'];
	$password = $_POST['password'];

	$conx = dbConnect();
	$sql = "SELECT id 
			FROM users
			WHERE username = '$username' AND password='$password' "; 
	$result = mysqli_query($conx, $sql) or die( mysqli_error($result) );

	
	if(mysqli_num_rows($result) == 1) {

	// username cookie
	$_SESSION['username'] = $_POST['username'];
	setcookie("username", $_SESSION['username'], time() +3600); 

	// user_id cookie using result of login query 
	$row = mysqli_fetch_assoc($result); 
	$user_id = $row['id']; 
	$_SESSION['user_id'] = $user_id;
	setcookie("user_id", $_SESSION['user_id'], time() +3600 );
	
	header('location: posts.php');	
	
	}
	
	else if (mysqli_num_rows($result) <1 || !$result)  {
	
	// DONT SAY WHICH OF USER / PASS WAS WRONG. SAY EITHER WAS WRONG. 
	echo "Failure. GO back and try again. Will add a redirect with a get message in the url later"; 
	
	}
}

/* -------------------------------------------------------------------------------------------*/
/* No longer used.  */
if (isset ($_POST['register']) )
{
	$username = $_POST['username' ];
	$password = $_POST['password'];
	$email = $_POST['email'];
		
		
	if ( ($username == "") || ($password == "") || ($email == "") ) {
		header('location: register.php?msg=regunsuccesful');	
	}
	
	else if ( strlen($password) < 8) {
		header('location: register.php?msg=passShort');	
	}
		
	else {
		$conx = dbConnect(); 
		$sql = "INSERT INTO users 
			(id, username, password, email)
				VALUES
			(null, '$username', '$password', '$email')	
		"; 
		
		mysqli_query($conx, $sql) or die( mysqli_error($conx) );
		
		header('location: register.php?msg=regsuccesful');	
	}

	
	
}
/* -------------------------------------------------------------------------------------------*/
if (isset ($_POST['selectInterests']) ) 
{
	// this doesn't apply as all have a value in them 
	// also need to make sure interest 1 != (2 || 3 || 4 || 5)
	if ( ($_POST['interest1'] || $_POST['interest2'] || $_POST['interest3'] || $_POST['interest4'] || $_POST['interest5']) == ""  ) {
		Redirect("dashboard.php"); 
	}

	$user_id = $_SESSION['user_id']; 
	$interest1 = $_POST['interest1']; // these are all interest id's corresponding to the interest table  
	$interest2 = $_POST['interest2'];
	$interest3 = $_POST['interest3'];
	$interest4 = $_POST['interest4'];
	$interest5 = $_POST['interest5'];
	
	$conx = dbConnect();
	$sql = "INSERT INTO users_interests 
		(users_id, interests_id)
			VALUES
		($user_id, $interest1), 	
		($user_id, $interest2), 	
		($user_id, $interest3), 	
		($user_id, $interest4),
		($user_id, $interest5) 				
	"; 
	$result = mysqli_query($conx, $sql) or die( mysqli_error($conx) );
	
	// now do something if succesful / not succesful
	if($result) {
			header('location: dashboard.php?msg=selectInterestsSuccess');	

	}
	
	else {
			header('location: dashboard.php?msg=selectInterestsFail');	

	}
	
}

//******************** SEARCHMENTORS ************************** //
// Can move this directly into settings.php (where it's used), just change $output .= for echo

function searchMentors($interest) {
	$conx = dbConnect();

	$display = 3; // Should be 6-8. Number of mentors to display on page. Follows pg 317 of larry php book. 
	if (isset($_GET['p']) && is_numeric($_GET['p'])) {
		$pages = $_GET['p'];
	}
	/***** NUMBER OF PAGES *****/
	else {
		/* Get number of users available with not full of mentees and with that interest */ 
		$sql = "SELECT COUNT(users.id) as records 
				FROM users INNER JOIN users_interests
				ON users.id = users_interests.users_id
				WHERE users_interests.interests_id = $interest
				AND users.mentee_count !=" . MENTOR_LEVEL;
		$result = mysqli_query($conx, $sql) or die( mysqli_error($conx) );
		$row = mysqli_fetch_array($result);
		$records = $row['records'];	// $records is the number of overall free users. Will also have one just for free users with that interest.
		if ($records > $display) {
			$pages = ceil($records/$display);
		}
		else {
			$pages = 1; 
		}
	}	
	if (isset($_GET['s']) && is_numeric($_GET['s'])) {
		$start = $_GET['s']; 	
	} 
	else {
		$start = 0;
	}
	
	$sql = "SELECT users.username as username, users.id as userid, users.posts as posts, users.description
	FROM users INNER JOIN users_interests
	ON users.id = users_interests.users_id
	WHERE users_interests.interests_id = $interest
	ORDER BY posts DESC
	LIMIT $start, $display
	"; 
	$result = mysqli_query($conx, $sql) or die( mysqli_error($conx) );
	
	/****  OUTPUT *****/
	$divclass = 'results'; // Background - changed using ternary operator, pg 317 
	$output = ""; 	
	while ($row = mysqli_fetch_assoc($result) ) {
		$divclass = ($divclass == 'results' ? 'results1' : 'results'); // Switch thebackground color. Currently both classes just use containers background colour. 
		
		$output .= '<div class = "' . $divclass . '">';
		$output .= '<form method="POST" action="settings.php?id=searchmentorsresults">
					<input type="hidden" name="mentorid" value=" ' . $row['userid']. ' " />
					<button class="btn btn-primary btn-small" id ="button_right" type="submit" value = "Select Mentor" name="selectMentor">Select Mentor</button>
					</form>';
		$output .= '<span style="font-size:115%">' . usernameLink($row['userid'], $row['username']) . '</span><br/>';
		if ($row['description'] != "") {		
			$output .= '<b>' . $row['description'] . '</b><br />'; 
		}
		if ($row['posts'] != 0 ) {
		$output .= 'Posts: ' . $row['posts']; 
		}
		else {
		$output .= 'Posts: 0'; 
		}
		$output .= '</div>';
		
		/*
		' <form method="POST" action ="process.php"> 
			<textarea rows="1" class="span7" name="message" ></textarea>
			<input type="hidden" name="threadid" value=" ' . $row['thread_id']. ' " />
			<button class="btn btn-small" type="submit" value = "Post" name="newPost">Post</button>
						   <button class="btn btn-primary" type="submit" value = "Post" name="newThread">Post</button>

		</form> </div> '; 
		*/
		
	}	
	
	/****** LINKS TO OTHER PAGES ******/
	if ($pages > 1) {
		$current_page = ($start/$display)+ 1;
		// If not first page, make a previous page link
		if ($current_page != 1) {
			$output.=  '<br /> <a href="settings.php?id=searchmentorsresults&i=' . $interest . '&s=' . ($start - $display) . '&p=' . $pages . '">Previous</a> ';
		}
		for ($i = 1; $i <= $pages; $i++) {
			if ($i != $current_page) {
				$output.= '<a href="settings.php?id=searchmentorsresults&i=' . $interest . '&s=' . (($display *($i - 1))) . '&p=' . $pages . '">' . $i . '</a> ';
			} 
			else {
				$output.= $i . ' ';
			}
		}
		// If not last page
		if ($current_page != $pages) {
			$output.=  '<a href="settings.php?id=searchmentorsresults&i=' . $interest . '&s=' . ($start + $display) .'&p=' . $pages .'">Next</a>';
		}
	}
	return $output; 
	
}


/* -------------------------------------------------------------------------------------------*/
// addMentors


function addMentors($mentorid) {
	
	$user_id = $_SESSION['user_id']; 
	$output = ""; 

	// This lot - checking if mentor exists already, doesn't work for some reason. Sticking it in a function? 
	
	$conx = dbConnect();
	$sql = "SELECT mentor_id as mentorid
			FROM mentors
			WHERE mentee_id = $user_id
	"; 
	$result = mysqli_query($conx, $sql) or die( mysqli_error($conx) );
	$mentorcount = mysqli_num_rows($result); 
	
	$checkMentors = False; 
	while ($row = mysqli_fetch_assoc($result) ) {
		if ($mentorid == $row['mentorid']) {
		$checkMentors = True; 
		}
	}
	
	// Check mentor doesn't already exist
	if($checkMentors) {
		$output = "That person is already a mentor! Jesus man, pay attention. ";
	}
	
	// if they have 3 mentors already
	else if ($mentorcount == MENTOR_LEVEL) {
		$output = "Sorry mate, you've already got the full amount of mentors. Stay tuned though - we'll be increasing the mentor limit within a few weeks"; 
	}

	// else if not currentmentor && mentorcount !=3 , insert into db. 
	else if ($checkMentors == False && $mentorcount < MENTOR_LEVEL) {
	

		$conx = dbConnect();
		$sql = "INSERT INTO mentors 
				VALUES
				($mentorid, $user_id)
		"; 
		$result = mysqli_query($conx, $sql) or die( mysqli_error($conx) );
		
		if($result) {
			$output = "Mentor has been added succesfully! Go ahead and make a post if you wish."; 

		}
	
		else { 
			$output = "Mentor could not be added. Please try again. If the error persists, please send us a quick email ( mahiukhan at gmail.com) and we'll look into it for you. "; 

		} 
	} 

	return $output; 
	
}

/* -------------------------------------------------------------------------------------------*/
// Logout
if ( isset( $_POST['logout'] ) && isset( $_SESSION['user_id'] ) ) {
	//session
	session_destroy();
	
	//cookie - pg 385 delete cookie
	setcookie ('user_id', '', time( )-3600);
	setcookie ('username', '',  time( )-3600);
	
	header('location: index.php');	
}

/* -------------------------------------------------------------------------------------------*/
// New Thread Function 

function newThread($message) {
	$output = ""; 
	if($message == "") {
		header('location: posts.php?msg=empty'); 
	}
	
	else if ($message != "") {
		$conx = dbConnect();
		$user_id = $_SESSION['user_id']; 
		$message_safe = htmlspecialchars(strip_tags($message)); 

		
		$sql = "INSERT INTO threads
				(thread_id, user_id) 
				VALUES 
				(null, $user_id)"; 
		$result = mysqli_query($conx, $sql) or die( mysqli_error($conx) );
		
		
		
		if (mysqli_affected_rows($conx) == 1)  {
			$thread_id = mysqli_insert_id($conx);
		} else {
			$output = "Error - could not add a new thread id";
		}
		
		// or - week 5 slides 7 pg 22
		/*
		if(mysqli_query($link, $sql) === FALSE) {
			echo mysqli_error($link);
		*/
		
		
		if ($thread_id) { // insert into post
			$sql = "INSERT INTO posts 
			(post_id, thread_id, user_id, message, posted_on)
				VALUES
			(null, $thread_id, $user_id, '" . mysqli_real_escape_string($conx, $message_safe) . "' , UTC_TIMESTAMP())";
			$result = mysqli_query($conx, $sql) or die( mysqli_error($conx) );

		} else {
			$output = "Error - could not add into posts";
		}
	}
	return $output;
} 

/* -------------------------------------------------------------------------------------------*/
// New Thread from form

if (isset( $_POST['newThread']) ) {
	
	$message = $_POST['message'];
	if($message == "") {
		header('location: posts.php?msg=empty'); 
	}
	
	else if ($message != "") {
		$conx = dbConnect();
		$user_id = $_SESSION['user_id']; 
		$message_safe = htmlspecialchars(strip_tags($message)); 

		
		$sql = "INSERT INTO threads
				(thread_id, user_id) 
				VALUES 
				(null, $user_id)"; 
		$result = mysqli_query($conx, $sql) or die( mysqli_error($conx) );
		
		if (mysqli_affected_rows($conx) == 1)  {
			$thread_id = mysqli_insert_id($conx);
		} else {
			header('location: posts.php?msg=newThreadError'); 
		}
		$threadResult = mysqli_affected_rows($conx);
		
		
		if ($thread_id) { // insert into post
			$sql = "INSERT INTO posts 
			(post_id, thread_id, user_id, message, posted_on)
				VALUES
			(null, $thread_id, $user_id, '" . mysqli_real_escape_string($conx, $message_safe) . "' , UTC_TIMESTAMP())";
			$result = mysqli_query($conx, $sql) or die( mysqli_error($conx) );

		} else {
			header('location: posts.php?msg=newPostError'); 
		}
		$postResult = mysqli_affected_rows($conx);
		
		if ( ($threadResult & $postResult) == 1 ) {
		header('location: posts.php?msg=yeah'); 
		}
		else {
			header('location: posts.php?msg=newPostError'); 
		}
	}
}  

/* -------------------------------------------------------------------------------------------*/
// displayThreads(); -> for own user -> could do one for any user later. 

function displayThreads($user_id) {
		$conx = dbConnect();		
		$username = getUsername($user_id);
		
		$sql = "SELECT threads.thread_id, posts.post_id, posts.message, DATE_FORMAT(posts.posted_on, '%e-%b-%y %l:%i %p') as date
				FROM threads INNER JOIN posts ON threads.thread_id = posts.thread_id
				WHERE threads.user_id = $user_id
				GROUP BY (posts.thread_id)
				ORDER BY posts.posted_on DESC";
		$result = mysqli_query($conx, $sql) or die( mysqli_error($conx) );
		
		if(mysqli_num_rows($result) == 0) {
			echo 'You have not made any posts. If you need any help ';
		}
		
		else{ 
			while ($row = mysqli_fetch_assoc($result) ) {
				echo  '<div class = "threads"> <b>' . usernameLink($user_id, $username) . '</b> <br>' . $row['message'] .  '<font class="date_font">' . $row['date'] . ' </font> <br>' . displayPosts($row['thread_id']) .
				' <form method="POST" action ="process.php"> 
					<textarea rows="1" class="span7" name="message" ></textarea>
					<input type="hidden" name="threadid" value=" ' . $row['thread_id']. ' " />
					<button class="btn btn-small" type="submit" value = "Post" name="newPost">Post</button>
				</form> </div> '; 
			}
		}
		mysqli_close($conx);
}

/* -------------------------------------------------------------------------------------------*/
// New Post - book has this combined with new thread. Slight duplicate, but keep separate for now. 

// NEED TO ADD USER ID VALIDATION, MIGHT NOT EXIST OR BE SPOOFED 
if (isset( $_POST['newPost']) ) {
	
	$message = $_POST['message'];
	$thread_id = $_POST['threadid'];
	
	if($message == "") {
		header('location: posts.php?msg=empty'); 
	}
	
	if($thread_id == "") {
		header('location: posts.php?msg=invalidThread'); 
	}
	
	else if ($message != "") {
		$conx = dbConnect();
		$user_id = $_SESSION['user_id']; 
		$message_safe = htmlspecialchars(strip_tags($message)); 
		
		$sql = "INSERT INTO posts 
			(post_id, thread_id, user_id, message, posted_on)
				VALUES
			(null, $thread_id, $user_id, '" . mysqli_real_escape_string($conx, $message_safe) . "' , UTC_TIMESTAMP())";
		
		$result = mysqli_query($conx, $sql) or die( mysqli_error($conx) );
		
		if (mysqli_affected_rows($conx) == 1)  {
			header('location: posts.php?msg=yeah'); 
		} 
		else {
			header('location: posts.php?msg=newPostError'); 
		}
	}
}

/* -------------------------------------------------------------------------------------------*/
// displayPosts(); - can use for mentor/mentee pages 

function displayPosts($thread_id) {
	$conx = dbConnect();
	$output = "";

	$sql = "SELECT  posts.post_id, posts.message, users.id as user_id, users.username, DATE_FORMAT(posts.posted_on, '%e-%b-%y %l:%i %p') as date
			FROM threads LEFT JOIN posts USING (thread_id) INNER JOIN users ON posts.user_id = users.id
			WHERE threads.thread_id = $thread_id
			ORDER BY posts.posted_on ASC";		
	$result = mysqli_query($conx, $sql) or die( mysqli_error($conx) );
	
	$n = 1;
	while ($row = mysqli_fetch_assoc($result)  ) {
		if ($n == 1) { }
		else {
			$output.= '<div class="posts"> <b>' . usernameLink($row['user_id'], $row['username']) . '</b>: ' . $row['message'] . '<font class="date_font">' . $row['date'] . ' </font> <br> </div> ';
		}
		$n++;
	}
	mysqli_close($conx);
	return $output; 		
}

/* -------------------------------------------------------------------------------------------*/
// displayMenteePosts();

function displayMenteePosts() {
	$conx = dbConnect();
	$user_id = $_SESSION['user_id'];
	// rather than loading these each time, store them in a cookie/sessions
	$sql = "SELECT mentee_id 
			FROM mentors 
			WHERE mentor_id = $user_id";
	$result = mysqli_query($conx, $sql) or die( mysqli_error($conx) );
	$mentee_count = mysqli_num_rows($result);
	$mentee1; $mentee2; $mentee3; $mentee4; $mentee5; 
	$where_clause = ""; 
	
	if($mentee_count == 0) {
		echo 'Youre not a mentor to anybody you unhelpful person! <a href="#"> Clck here </a> to find some mentees';
	}
	else if ($mentee_count != 0 ){
		while ($row = mysqli_fetch_assoc($result)  ) {
			$mentee_array[] = $row;
		}
		if ( ($mentee_count == 1) && ($mentee_count <= MENTOR_LEVEL) ) {
			$mentee1 = $mentee_array[0]['mentee_id'];	
			$where_clause .= "(threads.user_id = $mentee1)";
		}  
		else if($mentee_count == 2 && ($mentee_count <= MENTOR_LEVEL) ) {
			$mentee1 = $mentee_array[0]['mentee_id'];
			$mentee2 = $mentee_array[1]['mentee_id'];
			$where_clause .= "(threads.user_id = $mentee1 OR threads.user_id = $mentee2)";
		}
		else if($mentee_count == 3 && ($mentee_count <= MENTOR_LEVEL) ) {
				$mentee1 = $mentee_array[0]['mentee_id'];
				$mentee2 = $mentee_array[1]['mentee_id'];
				$mentee3 = $mentee_array[2]['mentee_id'];
				$where_clause .= "(threads.user_id = $mentee1 OR threads.user_id = $mentee2 OR threads.user_id = $mentee3)";
		}
		else if($mentee_count == 4 && ($mentee_count <= MENTOR_LEVEL) ) {
			$mentee1 = $mentee_array[0]['mentee_id'];
			$mentee2 = $mentee_array[1]['mentee_id'];
			$mentee3 = $mentee_array[2]['mentee_id'];
			$mentee4 = $mentee_array[3]['mentee_id'];
			$where_clause .= "(threads.user_id = $mentee1 OR threads.user_id = $mentee2 OR threads.user_id = $mentee3 OR threads.user_id = $mentee4 )";
		}
		else if($mentee_count == 5 && ($mentee_count <= MENTOR_LEVEL) ) {
			$mentee1 = $mentee_array[0]['mentee_id'];
			$mentee2 = $mentee_array[1]['mentee_id'];
			$mentee3 = $mentee_array[2]['mentee_id'];
			$mentee4 = $mentee_array[3]['mentee_id'];
			$mentee5 = $mentee_array[4]['mentee_id'];
			$where_clause .= "(threads.user_id = $mentee1 OR threads.user_id = $mentee2 OR threads.user_id = $mentee3 OR threads.user_id = $mentee4 OR threads.user_id = $mentee5)";
		}
		
		mysqli_free_result($result);
		
		$sql = "SELECT threads.thread_id, users.id as user_id, users.username, posts.post_id, posts.message, DATE_FORMAT(posts.posted_on, '%e-%b-%y %l:%i %p') as date
		FROM threads INNER JOIN posts USING (thread_id) INNER JOIN users ON threads.user_id = users.id
		WHERE $where_clause
		GROUP BY (posts.thread_id)
		ORDER BY posts.posted_on DESC
		LIMIT 10 ";	
		
		// could use IN instead of OR. 

		$result = mysqli_query($conx, $sql) or die( mysqli_error($conx) );
		while ($row = mysqli_fetch_assoc($result) ) {
		echo  '<div class = "threads"> <b>' . usernameLink($row['user_id'] , $row['username']) . ' </b> <br>' . $row['message'] .  '<font class="date_font">' . $row['date'] . ' </font> <br>' . displayPosts($row['thread_id']) .
		' <form method="POST" action ="process.php"> 
			<textarea rows="1" class="span7" name="message" ></textarea>
			<input type="hidden" name="threadid" value=" ' . $row['thread_id']. ' " />
			<button class="btn btn-small" type="submit" value = "Post" name="newPost">Post</button>
		</form> </div> '; 	
		}
	}
	mysqli_close($conx);	
}

/* -------------------------------------------------------------------------------------------*/
// Username linking to profile 

function usernameLink($id, $name) {
	$output = '<a href = "profile.php?id=' . $id . '">' . $name . '</a>';
	return $output;
}

/* -------------------------------------------------------------------------------------------*/

function getUsername($user_id) {
	$conx = dbConnect(); 
	$sql = "SELECT username FROM users 
			where id = '$user_id' ";
	$result = mysqli_query($conx, $sql) or die( mysqli_error($conx) );
	$row = mysqli_fetch_assoc($result); 
	return $row['username'];
}




?>

