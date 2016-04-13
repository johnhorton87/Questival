<?php
set_include_path('/');
include $_SERVER['DOCUMENT_ROOT'] . "/backAgain.php";

/////////////////////////////////////////////////////////////////////
//	Questival View Event Page
//	Created By J. Horton
//	Displays the event info, to be pulled in through AJAX
/////////////////////////////////////////////////////////////////////

// Pull in GET variables and replicate them as variables in this scope

$eventID = $_GET['eventID'];

// Look up the event information based on the eventID

$query = "SELECT * FROM questival
        INNER JOIN orgs ON questival._kf_org_id = orgs.org_id
        WHERE questival.quest_id =:id
        ";
$stmt = $db->prepare($query);
$stmt->bindValue(':id', $eventID, PDO::PARAM_INT);
$stmt->execute();

// Verify a successful query

if ($stmt) {
	if ($stmt->rowCount() == 1) {

		// Convert returned record to a row array

		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		// SET UP EVENT VARIABLES

		$org = $row['_kf_org_id'];
		$lastTimeInput = $row['lastTimeInput'];
		$lastLocInput = $row['lastLocInput'];
		$urlTitle = makeTitle($row['name']);
		$orgNameTitle = makeTitle($row['Org Name']);

		// check if the owner...
		if ($row['owner'] == $userID || $row['_kf_ownerID'] == $userID || $admin) {
			// this user owns, or is authorized to edit this event
			$owner = true;
		} else {
			//not owner
		}

		// FORMAT URL to link to external sites
		if (substr($row['url'], 0, 3) == "www") {
			$eventURL = "//" . $row['url'];
		} else {
			$eventURL = $row['url'];
		}

		// Start displaying the page, using HEREDOC functions...

		echo <<<EOT
<h1 class='eventHeader'>$row[name]</h1>
<div id=presentedBy class='italics center'>
	Presented by
	<span>
	<a href=/org/$row[org_id]/$orgNameTitle  title="$orgNameTitle" type='org' id="$row[org_id]">{$row['Org Name']}</a>
	</span>
</div>
EOT;

		// PRINT OUT THE EVENT DESCRIPTION

		echo <<<EOT
<div id=eventDescription class='standard'> $row[description]</div>
EOT;

		// FORMAT price to have the full range and the $ signs

		$priceRangeList = "$" . $row[price];
		if ($row['priceMax'] != null && $row['priceMax'] != 0) {
			$priceRangeList = $priceRangeList . "- $" . $row['priceMax'];
		}
		
		// DISPLAY Price and Website Info
		echo <<<EOT
<div id=extraDetails>
	<span id=price>
		Price: $priceRangeList
	</span>
	<span id=website class='floatRight'>
		<a target=_blank href='$eventURL'>
			Website
		</a>
	</span>

</div>
EOT;

		
		//SET UP URLS and DESCRIPTIONS for SHARE BUTTONS
		$URL = "http://www.questival.events/event/" . $eventID . "/" . $urlTitle;
		$encoded = urlencode($URL);
		$titleEncode = urlencode($row['name']);
		$twitterMessage = urlencode("Check out " . $row['name'] . " #qstvl");

		// PRINT OUT THE EVENT MENU BAR with SHARE BUTTONS
		echo <<<EOT
<div id=eventMenu class=eventMenu>
	<span id=facebookShare class='shareButtons fbShare'>
		<a target=_blank href='http://www.facebook.com/sharer.php?u=$encoded'>
			<img src=/img/Facebook.png alt='Share on Facebook'>
		</a>
	</span>
	<span id=twitterShare class='shareButtons twitterShare'>
		<a target=_blank href='https://twitter.com/intent/tweet?text=$twitterMessage&url=$encoded'>
			<img src=/img/Twitter.png height=30 alt='Share on Twitter'>
		</a>
	</span>
	<span id=googleShare class='shareButtons googleShare'>
		<a target=_blank href='https://plus.google.com/share?url=$encoded' title='Share on Google+'>
			<img src=/img/Google+.png height=30 alt='Share on Google+'>
		</a>
	</span>
EOT;

		// PRINT OUT THE EDIT BUTTON IF THE ADMIN OR AN OWNER

		if ($owner) {
			echo <<< EOT
<span id='editEvent' class='shareButtons editButton' onClick=editEvent('$eventID','$urlTitle')>
	Edit Event
</span>

EOT;
		} else {

		// OTHERWISE, print out the EMAIL BUTTON

			echo <<<EOT
<span id=emailShare class='shareButtons emailShare'>
	<a target=_blank href='mailto:?subject=$titleEncode&body=$encoded' title='Email'>
		<img src=/img/Email.png height=30  alt='Email to a friend'>
	</a>
</span>
EOT;
		}

		//end the Event Menu DIV
		echo "</div>";

		//IF OWNER - print out the ADD DATE FORM
	
		if ($owner) {

			// Print out the drop down box for the locations
			echo <<<EOT
			<div id='addDateButton' onClick='startDateAdd()' class='buttons'>
				Add Event Dates
			</div>
			<div id='addFormDiv' style='display:none'>
				<form id='addDateForm' action='null' method='POST'>
					<div>Location:
EOT;

			// Query locations for this event to put in the drop down box
			$query3 = "SELECT * FROM myLocations
            				INNER JOIN locations on myLocations._kf_locationID = locations.loc_id 
            				WHERE _kf_org_id =:org";
			$stmt = $db->prepare($query3);
			$stmt->bindValue(':org', $org, PDO::PARAM_INT);
			$stmt->execute();
			$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
			
			// IF Locations are found, display them
			if ($stmt->rowCount() > 0) {
				echo "<select id='locSelect' name='newLocation' style='width:375px;'>";

				// OUTPUT each location as a drop-down option
				foreach($rows as $row) {
					echo "<option value='" . $row['loc_id'] . "'>" . $row['loc_name'] . " " . $row['address'] . "</option>";
				}
				
				$currentDate = date('m/d/Y');

				// display the remainder of the Add Date form			

				echo <<<EOT
					<option value='newLoc'>
						Add new location
					</option>
				</select>
			</div>
			<div>
				Date: 
				<input type='text' name='newDate' id='newDate' value='$currentDate' readonly>
				<input type='hidden' name='eventID' value='$eventID'>
				<span class='orgName' id='reoccuringButton' onClick=reoccuring()>
					Recurring Event?
				</span>
			</div>
			<div id='reoccuringDiv' style='display:none'>
					<input type='hidden' name='reoccuring' value='0'>
					End Date:
					<input type='text' name='recDate' id='recDate' value='$currentDate' readonly>
					<span class='orgName' id='reoccuringCancel' onClick=cancelReoccuring()>
						Cancel Recurring
					</span>
				
			</div>
			<div>
				Time:
				<input type='time' name='newTime' value='$lastTimeInput'>
			</div>
			<div>
				Comment:
				<input type='text' name='newComment' placeholder='Comments (i.e. Special guests, themes, etc)'>
			</div>
			<div class='buttons' onClick=addDate()>
				Add Date
			</div>
EOT;
			} else {
				// if no locations, just give the option to add a location
				echo "<span class='orgName' onClick=addLocationStart()>Add Location</span>";
				echo "</div>";
			}

			echo "</form></div>";
		}

		// Look up the upcoming event dates

		$query2 = "SELECT * FROM quest_dates
                    INNER JOIN locations ON quest_dates._kf_loc_id = locations.loc_id
                    WHERE _kf_questival =:id
                    AND date >= CURDATE()
                    ORDER BY date ASC, time ASC
                    ";
		$stmt = $db->prepare($query2);
		$stmt->bindValue(':id', $eventID, PDO::PARAM_INT);
		$stmt->execute();

		// IF we've found upcoming dates, print out a date list
		if ($stmt && $stmt->rowCount() > 0) {
			echo "<div id='dateArea'>";
			$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

			//Using DateList function for consistent formatting across the site
			dateList($rows, $owner);
			echo "</div>";
		}
	} else {
		// Didn't find the event, or found multiple?
		echo "Event not found";
	}
} else {
	//Error in the SQL statement
	echo "Error Event " . $eventID;
}

?>
