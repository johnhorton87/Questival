<?php
set_include_path('/');
include $_SERVER['DOCUMENT_ROOT']."/backAgain.php";


/////////////////////////////////////////////////////////////////////////////////////////
//	Questival Add Date Page
//	Created By J. Horton
//	Adds a new date (or dates) to an existing event
/////////////////////////////////////////////////////////////////////////////////////////


//Pull in the needed GET variables
$eventID     = $_GET['eventID'];
$newDate     = date_create($_GET['newDate']);
$newDate     = date_format($newDate, 'Y-m-d');
$newtime     = $_GET['newTime'];
$newLocation = $_GET['newLocation'];
$newComment = $_GET['newComment'];


// Verify that the user is logged in
if ($loggedIn) {

    //Query the event and org information for the event
    $query = "SELECT * FROM questival 
                INNER JOIN orgs ON questival._kf_org_id = orgs.org_id
                WHERE questival.quest_id = :id";
    $stmt  = $db->prepare($query);
    $stmt->execute(array(
        ':id' => $eventID
    ));
    
    //If the event is found
    if ($stmt->rowCount() == 1) {

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $orgOwner = $row['_kf_ownerID'];
        $eventOwner = $row['owner'];

        //check to see if we are authorized to add an event date
        if ($orgOwner == $userID || $eventOwner == $userID || $admin) {

            $owner = true;

	    // Check if we are plotting multiple dates
            if ($endDate == null) {
                $endDate = $newDate;
            } 

            //set them as Date Objects
            $endDate = new DateTime($endDate);
            $newDate = new DateTime($newDate);
            
            $total = 0;
            $notes = "";

            //Set up an date_ID that we can link recurring dates to (in case we need to undo them)
            $recurringID = '';

	    //loop through the dates until we get to End Date
            while($newDate <= $endDate) {
                $newDateString = $newDate;
                $newDateString = date_format($newDateString,"Y/m/d");

                //check to see if this date and time is already entered
                $query2 = "SELECT date_id FROM quest_dates 
                                WHERE _kf_questival=:id
                                AND _kf_loc_id=:location  
                                AND date=:date
                                AND time=:time";
                $params = array(
                    ':id' => $eventID,
                    ':location' => $newLocation,
                    ':date' => $newDateString,
                    ':time' => $newTime
                );

                $stmt   = $db->prepare($query2);
                $stmt->execute($params);

		//If this date does not existing in the database already, please add it.
                if ($stmt->rowCount() == 0) {

                    $query2 = "INSERT INTO quest_dates (_kf_questival, _kf_loc_id, date, recurringID, comments, createdBy, time)
                        VALUES (:id, :location, :date, :recurringID, :comments, :createdBy, :time)";
                    $params = array(
                        ':id' => $eventID,
                        ':location' => $newLocation,
                        ':date' => $newDateString,
                        ':recurringID' => $recurringID,
                        ':comments' => $newComment,
                        ':createdBy' => $userID,
                        ':time' => $_GET['newTime']
                    );

                    $stmt2   = $db->prepare($query2);
                    $stmt2->execute($params);

		    //Verify that the date was added
                    if ($stmt2) {

                        if ($recurringID == '') {
			    //Track the ID of the first date added, will be linked to all future dates that are inserted
                            $recurringID = $db->lastInsertId();
                            $query4 = "UPDATE quest_dates SET recurringID=:recurring WHERE date_id =:id";
                                $stmt   = $db->prepare($query4);
                                $stmt->execute(array(
                                    ':recurring' => $recurringID,
                                    ':id' => $recurringID
                                ));
                        }

			//Increment our total number of events added tracker
                        $total = $total+1;

                    } else {
			//If there is an error adding the date
                        echo "Error creating date";
                    }

                } else {
		    // If there is already an event on X date, take note of it.
                    $notes = "<br>Event already existing on " . $newDateString . " @ " . $newTime;
                }

                $newDate->modify("+7 days");

                if ($total > 30) {
		    //Stop after 30 weeks, I don't want people adding a years worth of events and then regretting it later
                    break;
                }             
             }
            
	    //Output the result of the inserts
            echo "<div class='orgName'>Added " . $total . " event dates!". $notes;

	    //Output an UNDO button if there are multiple dates added
            if ($recurringID != null) {
                echo "<span class='buttons' onClick=undoDates(" . $recurringID . ")>Undo</span>";
            }

            echo "</div><br>";

            //pull out the new date list
            $query3 = "SELECT * FROM quest_dates
                INNER JOIN locations ON quest_dates._kf_loc_id = locations.loc_id
                WHERE _kf_questival =:id
                AND date >= CURDATE()
                ORDER BY date ASC, time ASC
                ";
            $stmt   = $db->prepare($query3);
            $stmt->execute(array(
                ':id' => $eventID
            ));

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	    //Generate a new date list to replace it for the user
            dateList($rows, $owner);
            
            //update the most recent time for the event, to make our lives easier later
            $query4 = "UPDATE questival SET lastTimeInput=:time, lastLocInput=:location WHERE quest_id =:id";
            $stmt   = $db->prepare($query4);
            $stmt->execute(array(
                ':id' => $questID,
                ':time' => $newTime,
                ':location' => $newLocation
            ));


        } else {
            //not authorized
            echo "You are not authorized to add dates to this event";
        }
    } else {
        //no event found
        echo "Need event ID";
    }
    
} else {
    echo "Error: Not logged in";
}


?>
