<?php
set_include_path('/');
include $_SERVER['DOCUMENT_ROOT']."/backAgain.php";

/////////////////////////////////////////////////////////////////////
//	Questival View Organization Page
//	Created By J. Horton
//	Displays the organization info, to be pulled in through AJAX
/////////////////////////////////////////////////////////////////////

$orgID           = $_GET['orgID'];

// look up org information information
$query = "SELECT * FROM orgs
        WHERE org_id =:id
        ";
$stmt  = $db->prepare($query);
$stmt->bindValue(':id', $orgID, PDO::PARAM_INT);

//Execute the query
$stmt->execute();

//If one result returned (correct)
if ($stmt->rowCount() == 1) {

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $org = $row['_kf_org_id'];
    if (substr($row['orgURL'], 0, 3) == "www") {
        $orgURL = "//" . $row['orgURL'];
    } else {
        $orgURL = $row['orgURL'];
    }

	//Output ORG info using HEREDOC
echo <<<EOT
<h1 class='eventHeader' id=orgName>
	{$row['Org Name']}
</h1>
<div id=orgDescription>
	$row[orgDescription]
</div>
<div id=website>
	<a target=_blank href='$orgURL'>
		Website
	</a>
</div>
EOT;

	//If the owner of the organization, add the Edit & Create Event Buttons
    
    if ($row['_kf_ownerID'] == $userID || $admin) {
        $owner = true;

	echo <<<EOT
<div id=eventMenu class=eventMenu>
	<span id='editOrg' class='buttons' onClick=editOrg('$orgID','org')>
		Edit Org
	</span>
        <span id='newEvent' class='buttons' onClick=addEvent($orgID)>
		Create Event
	</span>
</div>
EOT;
    }
        
    
    // Calendar Setup
    $category = "org";
    $id = $orgID;

    //Query the search date for the calendar
    $query3 = "SELECT MIN(quest_dates.date) as startDate FROM quest_dates
            INNER JOIN questival ON quest_dates._kf_questival = questival.quest_id
            INNER JOIN orgs ON questival._kf_org_id = orgs.org_id
            WHERE orgs.org_id=:id AND quest_dates.date >= CURDATE()";

    $stmt3  = $db->prepare($query3);
    $stmt3->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt3->execute();

    //If upcoming event dates exist
    if ($stmt3) {

        $row = $stmt3->fetch(PDO::FETCH_ASSOC);
        $date = new DateTime($row['startDate']);
        $startMonth =  $date->format('m');
        $startYear = $date->format('Y');

	// Use the custom calendar function to output the event data
        calendar($id, $category, $startMonth, $startYear, $db);

    }

}
?>