<!DOCTYPE html>
<?
set_include_path('/');
?>

<!-- Welcome to Questival! www.questival.events-->
<!-- Created by J. Horton, May 2015 - for more info visit www.johnventions.com -->
<!-- Questival is a Google Map which helps users find events going on near them -->

<html>
<head>

	<title>Questival Events - Chicago's Best Events Guide</title>

	<!-- Set up javascript variables if we are at a /event or /org page upon load -->
		<? 	if ($_GET['pop'] != null) { 
				echo "<script> var popState = '" . $_GET['pop'] . "'; </script>";
	   		}
	    		if ($_GET['eventID'] != null) {
				echo "<script> var preEvent = '" . $_GET['eventID'] . "'; </script>";
			}
		?> 

	<!-- Import related javascript files and css -->
		<script src="https://maps.googleapis.com/maps/api/js?key={INSERT API KEY}&libraries=places"></script>
		<script type="text/javascript" src="/infobubble/infobubble.js"></script>
		<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
		<script src="//code.jquery.com/jquery-1.10.2.js"></script>
		<script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
	
	<!-- Questival Specific JS and CSS files -->
		<script type="text/javascript" src="/questival.js"></script>
		<link rel="stylesheet" type="text/css" href="/style.css">

	<meta content="width=device-width, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />
	<link rel="icon" type="image/png" href="/favicon.png">
</head>

<body onLoad='load()'>

	<!-- Import facebook login and google analytics JS files  -->
		<script type="text/javascript" src="/facebook.js"></script>
		<script type="text/javascript" src="/googleAnalytics.js"></script>

	<!-- TOP menu Bar -->
	<div id='top'>
		<div id='blackbar'>
		</div>
		<div id='whitebar'>
			<span class='logo'><img src="/qlogo.png" style="height: 34px;" alt="Questival">Questival</span>
			<ul class='inlineRight noselect'>
				<li class='hamburger mobileButtons' onClick='goToMenu()' id="menuButton">
					<div class="hamburgerLine">
					</div>
					<div class="hamburgerLine">
					</div>
					<div class="hamburgerLine">
					</div>
				</li>
				<li id='loginButton' class='whiteButton fullButtons noselect' onClick='loginButton()'>Log In</li>
				<li id='favesButtonFull' class='whiteButton fullButtons noselect' onClick='goToFaves()'>Favorites</li>
				<li id='welcomeButton' class='whiteButton fullButtons noselect'>Welcome</li>

			</ul>
		</div>
	</div>

	<!-- Semitransparent backdrop for pop-up dialogues -->
	<div id='backdrop' class='backdrop' <? echo ($_GET['preview'] ? "style='display:none;'" : ""); ?>  > </div>

	<!-- Set up Content DiV to house AJAX'ed pages-->
	<div id='content' class='content <? echo ($_GET['preview'] ? "hideSection" : ""); ?>'>
		<div id='contentMain' class='sections'>
			<?
				if ($_GET['preview'] == null) {
					include "pages/welcome.php";
				}
			?>
		</div>
	</div>

	<!-- Set up List DiV to for favorites list-->
	<div id='list' class='searchBar hidden'>
		<div id='backToSearch' onClick='goToSearch()' class='buttons wide fullButtons unselected'>
			Back To Search
		</div>
		<div id='listContent'>
		</div>
	</div>

	<!-- Set up the preview DIV for /event and /org pages -->
	<div id='preview' class='searchBar <? echo ($_GET['preview'] ? "" : "hidden"); ?>'>
		<?
			if ($_GET['eventID'] != null) {
				include "ajax/events.php";
			}
			if ($_GET['orgID'] != null) {
				include "ajax/orgs.php";
			}
		?>
	</div>

	<!-- Set up the search bar and form -->
	<div class='searchBar mobileHidden'  id='search'>
	<form id="mySearch" action="#">

            <div class="stepBar" id="step1"> Step 1: Pick a date range
                <br>
                <div id="datesDiv">
                    <span class="dateBox"> <input type="text" class="date-input" name="sDate" id="sDate" size="12" readonly> </span>
                    <span class="dateBox"> <input type="text" class="date-input" name="eDate" id="eDate" size="12" readonly></span>
                </div>
                <div id="quickLinks">
                    <span class="buttons unselected thirds noselect" onclick="quickSearchDates(1)" id="todayButton">Today</span>
                    <span class="buttons unselected thirds noselect" onclick="quickSearchDates(2)" id="tomorrowButton">Tomorrow</span>
                    <span class="buttons unselected thirds noselect" onclick="quickSearchDates(3)" id="weekendButton">Weekend</span>
                </div>
            </div>
            <div class="stepBar" id="step2">
                Step 2: What type of events?
                <br>
                
        <span id="lb_festival" class="buttons selected thirds noselect">Festival</span>
		<span id="lb_concert" class="buttons unselected thirds noselect">Concert</span>
		<span id="lb_theater" class="buttons unselected thirds noselect">Theater</span>
		<span id="lb_comedy" class="buttons selected thirds noselect">Comedy</span>
		<span id="lb_sports" class="buttons unselected thirds noselect">Sports</span>
		<span id="lb_museum" class="buttons selected thirds noselect">Museums</span>

                <br>
            </div>
            
            
        <div class="stepBar" id="step3">
                Step 3: Other Requirements?
                <br>
                
                <span id="lb_free" class="buttons unselected thirds">Free Only</span>
		<span id="lb_hasDrinks" class="buttons unselected thirds">Has Drinks</span>
		<span id="lb_kidFriendly" class="buttons unselected thirds">Kid Friendly</span>

                <br>
        </div>
	
		<div id='formFields' class='hidden'>
			<input type='checkbox' id='festival' name='Festival' value='1' checked>
			<input type='checkbox' id='concert' name='Concert' value='1'>
			<input type='checkbox' id='theater' name='Theater' value='1'>
			<input type='checkbox' id='comedy' name='Comedy' value='1' checked>
			<input type='checkbox' id='sports' name='Sports' value='1'>
			<input type='checkbox' id='museum' name='Museum' value='1' checked>
			<input type='checkbox' id='free' name='free' value='1'>
			<input type='checkbox' id='hasDrinks' name='hasDrinks' value='1'>
			<input type='checkbox' id='kidFriendly' name='kidFriendly' value='1'>
			<input type='hidden' id='north' name='north'>
			<input type='hidden' id='south' name='south'>
			<input type='hidden' id='east' name='east'>
			<input type='hidden' id='west' name='west'>
			<?
				if ($_GET['preview']) {
					echo "<input type='hidden' id='eventID' name='eventID' value='" . $_GET['eventID'] ."'>";
				}
			?>
		</div>
		</form>
		<div id="viewListButton" class="buttons wide fullButtons noselect" onclick="goToList()">
			View List
		</div>

	</div>

	<!-- Empty MAP div will be populated when the google maps API loads -->
	<div id='map'> </div>

	<!-- Bottom menu bar houses the menu buttons for both mobile and desktop pages -->
	<div id='bottom'>
		<div class='blackButton fullButtons noselect' id='faqButton' onClick='goToFAQ()'>FAQ</div>
		<div class='blackButton fullButtons noselect' id='aboutButton' onClick='goToAbout()'>ABOUT</div>
		<div class="blackButton mobileButtons noselect" id="searchButton" onClick='goToSearch()'>SEARCH</div>
		<div class="blackButton mobileButtons noselect" id="mapButton" onClick='goToMap()'>MAP</div>
  		<div class="blackButton mobileButtons noselect" id="favesButton" onClick='goToFaves()'>FAVES</div>
  		<div class="blackButton mobileButtons noselect" id="listButton" onClick='goToList()'>LIST</div>
	</div>

</body>
</html>
