<?php
set_include_path('/');
include $_SERVER['DOCUMENT_ROOT'] . "/backAgain.php";

/////////////////////////////////////////////////////////////////////
//	Questival Favorites List Page
//	Created By J. Horton
//	Displays the upcoming favorite events for the logged in user
/////////////////////////////////////////////////////////////////////

//Check to make sure we are logged in first
if ($loggedIn) {

	// Build database query, select events that are labeled as favorites for this user
	// Events are sorted by Date (ascending) and grouped by EventID (so you don't get the same event listed 8 times in a row)

  	$query = "SELECT questival.quest_id, questival.name, MIN(quest_dates.date) as minDates, COUNT(quest_dates.date) as qtyDates from favorites 
        		INNER JOIN questival on favorites.qstvl_id = questival.quest_id 
        		INNER JOIN quest_dates on quest_dates._kf_questival = questival.quest_id 
        		WHERE favorites.user_id =:id
        		AND quest_dates.date >= CURDATE()
        		GROUP BY quest_dates._kf_questival
        		ORDER BY list_id, minDates" ;

	$stmt = $db->prepare($query);
	$stmt->bindValue(':id', $userID, PDO::PARAM_INT);
	$stmt->execute();

   //If query is successfull
   if ($stmt){
       if ($stmt->rowCount() > 0) {

	   //Start printing the favorites list
           echo "<div class='infoHeader'>Upcoming Favorites</div>";

           $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
	   foreach($rows as $row) {
		$date = date_create($row['minDates']);
		$formattedDate =  date_format($date, 'F jS');
		$qty = ( $row['qtyDates'] > 1 ? "(" . ($row['qtyDates']) . " dates)" : "" );
		echo <<<EOT
           		<div class='searchList' onClick=getEvent('$row[quest_id]')>
				$row[name] - $formattedDate $qty
			</div>
EOT;

           }

       } else {
	   //No upcoming events on your favorites list
           echo "No upcoming favorite events";
       }
   } else {
	//Datebase query had an error
       echo "Error";
   }
} else {
	// User is not logged in
  	echo "Log into access your favorites list";
	echo "<div class='buttons' onClick='loginButton()'> Log In </div>";
}

?>
