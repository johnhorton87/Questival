<?php
include_once "../backAgain.php";

/////////////////////////////////////////////////////////////////////////////////////////
//	Questival Markers Page
//	Created By J. Horton
//	- Pulls in the search critera from the Questival Home page
//	- Outputs XML objects for the events - grouped by location
////////////////////////////////////////////////////////////////////////////////////////


//////////////////////////////////////////////////////////////////////
// STEP 1: Pull in the Variables that define our search criteria
//////////////////////////////////////////////////////////////////////

// Event Types
$festival = $_GET["Festival"];
$concert  = $_GET["Concert"];
$theater  = $_GET["Theater"];
$trivia   = $_GET["Trivia"];
$comedy   = $_GET["Comedy"];
$sports   = $_GET["Sports"];
$museums  = $_GET["Museum"];

// Longitude and Latitude
$north    = $_GET["north"];
$south    = $_GET["south"];
$east     = $_GET["east"];
$west     = $_GET["west"];

// Additional Filters
$priceMax = $_GET["priceMax"];
$hasDrinks   = $_GET["hasDrinks"];
$kidFriendly = $_GET["kidFriendly"];
$free        = $_GET["free"];

// Dates
$sdate       = date("Y-m-d", strtotime( html_entity_decode( $_GET["sDate"])));
$edate       = date("Y-m-d", strtotime(html_entity_decode($_GET["eDate"])));


// Define our Parsing Function to remove HTML entities
function parseToXML($htmlStr)
{
    $xmlStr = str_replace('<', '<', $htmlStr);
    $xmlStr = str_replace('>', '&gt;', $xmlStr);
    $xmlStr = str_replace('"', '&quot;', $xmlStr);
    $xmlStr = str_replace("'", '&#39;', $xmlStr);
    $xmlStr = str_replace("&", '&amp;', $xmlStr);
    return $xmlStr;
}


/////////////////////////////////////////////////////////
// STEP 2: Build our query based off of search criteria
// For each step, the search query is concatened with the next criteria
// The parameters array is then appended with new variables
/////////////////////////////////////////////////////////

// Select all the rows in the markers table, join in the dates, orgs, and locations table

$query = "SELECT * FROM questival
		INNER JOIN quest_dates ON questival.quest_id = quest_dates._kf_questival
		INNER JOIN orgs ON questival._kf_org_id = orgs.org_id
		INNER JOIN locations ON quest_dates._kf_loc_id = locations.loc_id";

// IF we are searching only for a specific event, skip the rest of the search

if (isset($_GET['eventID']) and $_GET['eventID'] != "") {
    $query            = $query . " WHERE questival.quest_id = :quest ";
    $query = $query . "AND quest_dates.date >= :sdate ";

    // Parameters are EventID and Date
    $params = array(
        ':quest' => $quest,
        ':sdate' => date('Y-m-d')
    );

    $criteria = 1;

} else {

    // Start off Parameters with just lat/lng and date range
    $params = array(
    	':south' => $south,
    	':north' => $north,
    	':west' => $west,
    	':east' => $east,
    	':sdate' => $sdate,
    	':edate' => $edate
    );

    
    // IF USER is logged in add FAVORITES JOIN
    if (isset($userID)) {
    	$query           = $query . " LEFT JOIN favorites on questival.quest_id = favorites.qstvl_id AND favorites.user_id=:user";
    	$params[':user'] = $userID;
    }

    // add Location criteria
    $query = $query . " WHERE deleted ='0' AND locations.lat BETWEEN :south AND :north AND locations.lng BETWEEN :west AND :east AND (";
    
    
    // Add event type criteria. We have one statement for each Event Type
    // Will ultimately output 'where festival = 1 OR concert = 1 OR ...' etc

    $criteria = 0;

    if ($festival) {
        $query = $query . ($criteria > 0 ? " OR " : "") . "Festival = 1";
        $criteria += 1;
    }
    if ($concert) {
        $query = $query . ($criteria > 0 ? " OR " : "") . "Concert = 1";
        $criteria += 1;
    }
    if ($theater) {
        $query = $query . ($criteria > 0 ? " OR " : "") . "Theater = 1";
        $criteria += 1;
    }
    if ($trivia) {
        $query = $query . ($criteria > 0 ? " OR " : "") . "Trivia = 1";
        $criteria += 1;
    }
    if ($comedy) {
        $query = $query . ($criteria > 0 ? " OR " : "") . "Comedy = 1";
        $criteria += 1;
    }
    if ($sports) {
        $query = $query . ($criteria > 0 ? " OR " : "") . "Sports = 1";
        $criteria += 1;
    }
    if ($museums) {
        $query = $query . ($criteria > 0 ? " OR " : "") . "Museums = 1";
        $criteria += 1;
    }

    // Close out our Event Type OR list
    $query = $query . ")";
    
    // add Date Criteria
    $query = $query . " AND quest_dates.date BETWEEN :sdate AND :edate";
    
      
    //add 'Has Drinks', 'Free Only' or 'Kid Friendly' criteria, these are AND requests because they are mandatory filters
    $query = $query . ($hasDrinks ? " AND hasDrinks = '1'" : "");
    $query = $query . ($kidFriendly ? " AND kidFriendly = '1'" : "");
    $query = $query . ($free ? " AND free = '1'" : "");

    // add the price filter
    if ($priceMax != null) {
        $query            = $query . " AND price <= :price";
        $params[':price'] = $priceMax;
    }
}

//sort by location
$query = $query . " ORDER BY loc_id ASC, quest_dates.date ASC, quest_dates.time ASC";



///////////////////////////////////////////////////////////////////////////
// STEP 3: Run the database query
///////////////////////////////////////////////////////////////////////////

// Check to make sure we have at least one critera for event type

if ($criteria > 0 and $edate >= $sdate) {
    $stmt = $db->prepare($query);
    $stmt->execute($params);
}

if (!$stmt) {
    // if the query errors out, report and error and the query output
    die('Invalid query: ' . mysql_error() . "<br>" . $query) ;
}

// Otherwise.... Lets make some XML


/*  --------------------
XML structure that is generated

Location A
	- Date 1
		- First Event Details
		- Second Event Details
	- Date 2
		- First Event Details
		- Second Event Details
Location B
	- Date 3
		- First Event Details
etc etc...

------------------------- */



if ($stmt->rowCount() > 0) {

    // OUTPUT XML Headers
    header("Content-type: text/xml");
    
    // Start XML file, echo parent node
    echo '<markers>';
    
    // Iterate through the rows, printing XML nodes for each
    $prevLoc = -1;
    $rows    = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Loop through each location
    foreach ($rows as $row) {
        $locId = parseToXML($row['_kf_loc_id']);

	// Check to see if its a new location, if so start a new location node
        if ($locId != $prevLoc) {

            // Close out old Location Node
            if ($prevLoc <> -1) {
                echo '</date></marker>';
            }

	    // Refresh our Date ID tracker
            $prevDate = -1;

	    // Start New Location Node with Location, ORG, etc
            echo '<marker ';
            echo 'org="' . parseToXML($row['Org Name']) . '" ';
            $address = parseToXML($row['address']);
            $address = str_replace(", United States", "", $address);
            echo 'address="' . $address . '" ';
            echo 'loc_ID="' . $row['_kf_loc_id'] . '" ';
            echo 'lat="' . $row['lat'] . '" ';
            echo 'lng="' . $row['lng'] . '" >';

        } else {

        }

        $prevLoc = $locId;
        $date    = $row['date'];

	// Check to see if we've transfered to the next date
        if ($date != $prevDate) {
		
	    //End the previous Date Node
            if ($prevDate <> -1) {
                echo '</date>';
            }
          
                $dateString = date("l F jS", strtotime($date));
                $descrMax   = 200;
            
            //If new date, start new date node
            echo '<date ';
            echo 'date_id="' . $row['date_id'] . '" ';
            echo 'date="' . $date . '" ';
            echo 'dateString="' . $dateString . '" ';
            echo '>';
        }

	//Pull out the time variables for the event
        $time    = $row['time'];
        $minutes = date('i', strtotime($time));
        if ($minutes == '00') {
            $timeString = date('gA', strtotime($time));
        } else {
            $timeString = date('g:iA', strtotime($time));
        }

	// Shorten the description if over the maximum
        $description = substr($row['description'], 0, $descrMax);
        if (strlen($description) == $descrMax) {
            $description = $description . "...";
        }

	// Start the Event Node
        echo '<event ';
        echo 'id="' . parseToXML($row['quest_id']) . '" ';
        echo 'name="' . utf8_encode(parseToXML($row['name'])) . '" ';
        echo 'org="' . parseToXML($row['Org Name']) . '" ';
        echo 'time="' . parseToXML($timeString) . '" ';
        echo 'urlTitle="' . makeTitle($row['name']) . '" ';
        echo 'description="' .   parseToXML( utf8_encode(  mb_convert_encoding($description, "ISO-8859-1", "UTF-8") ) ) . '" ';
        echo 'comments="' .   parseToXML( utf8_encode(  mb_convert_encoding($row['comments'], "ISO-8859-1", "UTF-8") ) ) . '" ';
        echo 'festival="' . $row['festival'] . '" ';
        echo 'concert="' . $row['concert'] . '" ';
        echo 'theater="' . $row['theater'] . '" ';
        echo 'trivia="' . $row['trivia'] . '" ';
        echo 'url="' . parseToXML($row['url']) . '" ';
        echo 'price="' . $row['price'] . '" ';
        if (isset($row['fav_id'])) {
            echo 'favorite="1" ';
        }
        echo '/>';
        $prevDate = $date;
    }
    
    // End XML file
    echo '</date></marker></markers>';
}
?>