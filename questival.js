/////////////////////////////////////////////////
// WWW.QUESTIVAL.EVENTS JAVASCRIPT FILE
// Created By J. Horton
// Runs the user interface of questival
/////////////////////////////////////////////////



$.ajaxSetup({
cache: false
});

/////////////////////////////////////////////////
//VARIABLES
/////////////////////////////////////////////////
var chicago = new google.maps.LatLng(41.900033, -87.6500523);

//variables for map bounds and your position
var map;
var markerIDs = []; // record IDs all downloaded markers
var markers = []; // markers being displayed on the map
var eventsList; //the html of the events list
var eventsArray = []; //the array of event id's
var pos = ""; // your position, in string format for our GET request
var north, south, east, west; // bounds of the map
var sBoundNorth, sBoundSouth, sBoundEast, sBoundWest; // bounds of the search
var zoomLevel; // self explanatory
var listCount = 0;
var activeMarker;
var loggedIn;

///info bubble variables
var boxOptions = {
  disableAutoPan: true,
  disableAnimation: true,
  alignBottom: true,
  closeBoxURL: "",
  maxWidth: 500,
  backgroundColor: '#1976D2',
  minWidth:350,
  padding: 0,
  zIndex: 3,
  pixelOffset: new google.maps.Size(-12, -12), 
  infoBoxClearance: new google.maps.Size(1, 1),
  };
var infoBubble = new InfoBubble(boxOptions);

//marker variables
var blueDot = new google.maps.MarkerImage("/markers/m-flag.png", null, null, null, new google.maps.Size(26, 45));

//variables for dates
var today = new Date();
var tomorrow = new Date();
tomorrow.setDate(today.getDate() + 1);
var weekendStart = new Date();
var weekendEnd = new Date();
var DayOfWeek = today.getDay();
if (DayOfWeek > 0 && DayOfWeek < 5) {
    weekendStart.setDate(today.getDate() + (5 - DayOfWeek));
} else {
    weekendStart = today;
}
if (DayOfWeek != 0) {
    weekendEnd.setDate(today.getDate() + (7 - DayOfWeek));
} else {
    weekendEnd = today;
}

////////////////////////////////////////////////
// PAGE ELEMENT NAMES YOU'LL RE-USE
////////////////////////////////////////////////

var content = "#content";
var contentMain = "#contentMain";
var search = "#search";
var backdrop = "#backdrop";
var listDiv = "#list";
var listContent = "#listContent";

////////////////////////////////////////////////
// THE LOAD FUNCTION THAT RUNS THE WHOLE SHOW
////////////////////////////////////////////////


function load() {
    setMaps();
    setDates();
    setBackdrop();
    setSearchButtons();
    setPopState();
}

/////////////////////////////////////////////////
//  LAYOUT RELATED FUNCTIONS
/////////////////////////////////////////////////

function setBackdrop() {
    $("#backdrop").click(function() {
	goToMap();   
    });
}

function openContent() {
    $(content).removeClass("hideSection");
    $(content).addClass("showSection");
    $(listDiv).addClass("hidden");
    $(backdrop).show();
}

function closeContent() {
    $(content).addClass("hideSection");
    $(backdrop).hide();
    $(listDiv).addClass("hidden");
}

function goToMap() {
    clearActive();
    closeContent();
    closeSearch();
    $(search).addClass("mobileHidden");
    $("#mapButton").addClass("active");
    history.pushState(null, 'Questival', "/");
}

function goToSearch() {
    clearActive();
    closeContent();
    $(search).removeClass("hideSection");
    $(search).removeClass("mobileHidden");
    $(search).addClass("showSection");
    $("#searchButton").addClass("active");
    history.pushState(null, 'Questival - Search', "/search");
}
function closeSearch() {
    $(search).addClass("mobileHidden");
}
function goToList() {
    clearActive();
    $("#listButton").addClass("active");
    closeContent();
    $(listDiv).removeClass("hidden");
     history.pushState(null, 'Questival - List', "/list");
}
function goToFaves(ignore) {
    clearActive();
    $("#favesButton").addClass("active");
     downloadPage("/ajax/favoritesList.php", "/faves", ignore);
}
function goToFAQ(ignore) {
    clearActive();
     downloadPage("/pages/faq.php", "/faq", ignore);
}
function goToAbout(ignore) {
    clearActive();
     downloadPage("/pages/about.php", "/about", ignore);
}

function goToMenu(ignore) {
    clearActive();
     downloadPage("/ajax/menu.php", "/menu", ignore);
}

function clearActive() {
     var navButtons = ["listButton", "searchButton", "favesButton", "mapButton"];
     navButtons.forEach( function(n) { $("#" + n).removeClass("active");} )
}

function openFaq(f) {
  $("#faq"+f).toggle();
}

function loginButton(ignore) {
	clearActive();
	downloadPage("/ajax/login.php", "/login", ignore);
}

//////////////////////////////////////////////////
// POPSTATE
//////////////////////////////////////////////////

function setPopState() {
    //set up the push/pop state for the browser history
    window.addEventListener("popstate", function(e) {
        if (location.pathname != '/') {
            var baseURL = location.pathname;
            processURL (baseURL);
        } else {
	    goToMap();
	}
    });
    if (typeof popState !== 'undefined') {
	console.log(popState);
	processURL("/" + popState);
    } else {
	console.log("No popstate");
    }
    if (typeof preEvent === "undefined") {
	
    } else {
        goToMap();   
    }
}


function processURL(baseURL) {
	//function to process the popstate URLs and act accordingly
	var urlParts = baseURL.split("/");
            if (urlParts[1] == "event") {
                //this is an event
                getEvent(urlParts[2], urlParts[3], true);
            } else if (urlParts[1] == "org") {
                getOrg(urlParts[2], urlParts[3], true);
            } else if (urlParts[1] == "search") {
                goToSearch();
            } else if (urlParts[1] == "faves") {
                goToFaves(true);
            } else if (urlParts[1] == "about") {
                goToAbout(true);
            } else if (urlParts[1] == "faq") {
                goToFAQ(true);
            } else {
		goToMap();
	    }
}


//////////////////////////////////////////////////
/// SEARCH FORM RELATED FUNCTIONS
//////////////////////////////////////////////////


function setSearchButtons() {
    //set the category buttons to be clickable
    //set a listener on every checkbox
    $("[id^=lb_]").click(function() {
        var cbID = "#" + (this.id).replace("lb_", "");
        if ($(this).hasClass("unselected")) {
            $(this).removeClass("unselected");
            $(this).addClass("selected");
            $(cbID).prop('checked', true);
        } else {
            $(this).removeClass("selected");
            $(this).addClass("unselected");
            $(cbID).prop('checked', false);
        }
        //refresh the markers
        downloadMarkers();
    });
}

function setDates() {
    
    //function to pre-populate the Date input fields
    var d1 = new Date();
    d1.setDate(d1.getDate());
    var sDate = convDate(d1);
    var d2 = new Date();
    d2.setDate(d2.getDate() + 3);
    var eDate = convDate(d2);
    document.getElementById("sDate").value = sDate;
    document.getElementById("eDate").value = eDate;
    $("#sDate").datepicker({
        onSelect: function(date) {
            downloadMarkers();
            formatDateButtons();
        },
        minDate: "-1D",
        maxDate: "+1Y"
    });
    $("#eDate").datepicker({
        onSelect: function(date) {
            downloadMarkers();
            formatDateButtons();
        },
        minDate: "-1D",
        maxDate: "+1Y"
    });

}


function convDate(d) {
    //Function to convert date to the correct format needed for our SQL query
    var d1 = d.getDate();
    if (d1 < 10) {
        d1 = "0" + d1;
    }
    var d2 = d.getMonth() + 1;
    if (d2 < 10) {
        d2 = "0" + d2;
    }
    var d3 = d.getFullYear();
    var d4 = d2 + "/" + d1 + "/" + d3;
    return d4;
}

function quickSearchDates(t) {
    var sDateBox = document.getElementById("sDate");
    var eDateBox = document.getElementById("eDate");
    if (t == 1) {
        sDateBox.value = convDate(today);
        eDateBox.value = convDate(today);
    } else if (t == 2) {
        sDateBox.value = convDate(tomorrow);
        eDateBox.value = convDate(tomorrow);
    } else {
        sDateBox.value = convDate(weekendStart);
        eDateBox.value = convDate(weekendEnd);
    }
    formatDateButtons();
    downloadMarkers();
}

function formatDateButtons() {
    var sDateBox = document.getElementById("sDate");
    var eDateBox = document.getElementById("eDate");
    $("#todayButton").addClass("unselected");
    $("#tomorrowButton").addClass("unselected");
    $("#weekendButton").addClass("unselected");
    if (sDateBox.value == convDate(today) && eDateBox.value == convDate(today)) {
        $("#todayButton").removeClass("unselected");
    } else if (sDateBox.value == convDate(tomorrow) && eDateBox.value == convDate(tomorrow)) {
        $("#tomorrowButton").removeClass("unselected");
    } else if (sDateBox.value == convDate(weekendStart) && eDateBox.value == convDate(weekendEnd)) {
        $("#weekendButton").removeClass("unselected");
    }
}


/////////////////////////////////////
// MAP RELATED FUNCTIONS
////////////////////////////////////

function setMaps() {
    map = new google.maps.Map(document.getElementById("map"), {
        center: chicago,
        zoom: 12,
        mapTypeControl: false,
        panControl: false,
        mapTypeId: 'roadmap',
        streetViewControl: false,
        zoomControlOptions: {
            style: google.maps.ZoomControlStyle.DEFAULT,
            position: google.maps.ControlPosition.RIGHT_BOTTOM
        }
    });

    map.set('styles', [{
        "elementType": "geometry",
        "stylers": [{
            "hue": "#0091FF"
        }, {
            "gamma": 0.86
        }]
    }, {
        "featureType": "road.highway.controlled_access",
        "stylers": [{
            "visibility": "simplified"
        }, {
            "weight": 0.5
        }]
    }, {
        "featureType": "poi",
        "stylers": [{
            "visibility": "simplified"
        }]
    }, {
        "featureType": "transit.line",
        "stylers": [{
            "color": "#195BD4"
        }, {
            "weight": 1
        }]
    }, {
        "elementType": "labels.icon",
        "stylers": [{
            "weight": 2
        }, {
            "visibility": "on"
        }]
    }]);

    // function that checks if the center has changed
    map.addListener("idle", function() {
        calcMapBounds();
        //markerRefresh();
    });

}

// function to calculate the bounds of the map
function calcMapBounds() {
    var bounds = map.getBounds();
    north = bounds.getNorthEast().lat();
    east = bounds.getNorthEast().lng();
    south = bounds.getSouthWest().lat();
    west = bounds.getSouthWest().lng();

    	if (newBounds()) {
        	$("#north").val(north);
        	$("#south").val(south);
        	$("#east").val(east);
        	$("#west").val(west);
        	console.log("Updating due to map moving");
        	pos = "north=" + sBoundNorth + "&south=" + sBoundSouth + "&east=" + sBoundEast + "&west=" + sBoundWest;
        	downloadMarkers();
	}

}


function newBounds() {
    //Compare the bounds of the map to the bounds of our last search
    //if we are in an area we haven't searched by, do a new search
    var needUpdate = 0;
    if (sBoundNorth == undefined) {
        var c = map.getCenter();
        sBoundNorth = c.lat();
        sBoundSouth = c.lat();
        sBoundEast = c.lng();
        sBoundWest = c.lng();
        needUpdate = 1;
    }
    var nsDif = north - south;
    var ewDif = east - west;
    if (north > sBoundNorth) {
        sBoundNorth = north + (nsDif * 1);
        needUpdate = 1;
    }
    if (south < sBoundSouth) {
        sBoundSouth = south - (nsDif * 1);
        needUpdate = 1;
    }
    if (east > sBoundEast) {
        sBoundEast = east + (ewDif * 1);
        needUpdate = 1;
    }
    if (west < sBoundWest) {
        sBoundWest = west - (ewDif * 1);
        needUpdate = 1;
    }
    return needUpdate;
}


//////////////////////////////////////////////////
// MARKER RELATED FUNCTIONS
//////////////////////////////////////////////////

function downloadMarkers() {
    var formData = $("#mySearch").serialize();
    $.get("/ajax/markers.php", formData, function(xml) {
        var eventsArray = [];
        if (xml == undefined) {
            //nothing
            console.log("Error getting XML");
        } else {
            console.log("Analyzing XML...");
            plotMarkers(xml);
        }
    }, "xml");
}

function plotMarkers(xml) {
    // xml is an xml list of the markers we need to place
    // we'll be making a marker object for each one and placing it on the map
    // HTML is generated in Javascript, not ideal but not too much of a way around it with this method.

    clearAllMarkers();
    var added = 0;
    var new_markers = xml.getElementsByTagName("marker");
    var eventsList = "";

    //Loop through each marker
    for (var i = 0; i < new_markers.length; i++) {

        //find the location ID to make sure that we don't have that location already
        var id = new_markers[i].getAttribute("loc_ID");
        if (jQuery.inArray(id, markerIDs) == -1) {
            var eventIDs = [];
            var eventIDpages = [];
            var eventNames = [];
            var markerFav = 0;
            // Set Up Basic Info for Marker
            var org = new_markers[i].getAttribute("org");
            var address = new_markers[i].getAttribute("address");
            var lat = parseFloat(new_markers[i].getAttribute("lat"));
            var lng = parseFloat(new_markers[i].getAttribute("lng"));
            var point = new google.maps.LatLng(lat, lng);
            var favorite = 0;
            var html = [];

            // Break it up by dates
            var dates = new_markers[i].childNodes;
            for (var j = 0; j < dates.length; j++) {

                //Start generating the HTML of the pop-up window
                var prevPage;
                var buttonClass;

                //create the date bar
                var dateBar = "<div id='dateContainer' class='dateContainer'>";

                //Add Previous Button            
                if (j > 0) {
                    prevPage = j - 1;
                    buttonClass = "navButtons floatLeft  noselect";
                } else {
                    prevPage = 0;
                    buttonClass = "navButtons faded floatLeft noselect";
                }
                dateBar = dateBar + "<span class='" + buttonClass + "' onClick=setMarkerPage('" + prevPage + "')>";
                dateBar = dateBar + "Prev</span>";

                //Add the date
                dateBar = dateBar + "<span class='infoBoxDate'> " + dates[j].getAttribute("dateString") + " </span>";

                // Add the next date button
                if (j < dates.length - 1) {
                    nextPage = j + 1;
                    buttonClass = "navButtons floatRight noselect";
                } else {
                    nextPage = j;
                    buttonClass = "navButtons faded floatRight noselect";
                }
                dateBar = dateBar + "<span class='" + buttonClass + "' onClick=setMarkerPage('" + nextPage + "')>";
                dateBar = dateBar + "Next</span></div>";


                var eventHTML = "";

                // break it up by events on each date!
                var events = dates[j].childNodes;
		
                for (var k = 0; k < events.length; k++) {
                    //Add the event name
                    var event_id = events[k].getAttribute("id");
                    var event_title = events[k].getAttribute("urlTitle");
                    var eventName = events[k].getAttribute("name");

                    //check to see if its in the events array
                    if (jQuery.inArray(event_id, eventIDs) == -1) {
                        // add the eventID to the eventsArray
                        eventIDs.push(event_id);
                        //add the right page to the pages array
                        eventIDpages.push(j);
                        //add the event names to the pages array
                        eventNames.push(eventName);
                        var listDescr = "<div class='listItem' id=el_" + i + "_" + event_id + " onmouseover='listFocus(" + i + ")' onmouseout='listLoseFocus(" + i + ")' onClick='listClick(" + i + ", " + j + ", " + event_id + ")'>" + eventName + "</div>";
                        eventsList = eventsList + listDescr;
                        listCount = listCount + 1;
                    }
                    //check to see if its in the global list
                    if (jQuery.inArray(event_id, eventsArray) == -1) {
                        // add it to the eventsArray
                        eventsArray.push(event_id);

                    }

                    eventHTML = eventHTML + "<div class='infoBoxEvents' id=event_" + event_id + " >" + events[k].getAttribute("time") + " - <span style='cursor:pointer;' onClick=getEvent('" + event_id + "'," + event_title + "')>" + eventName + "</span></div>";

                    var description = events[k].getAttribute("description");
                    var comments = events[k].getAttribute("comments");
                    if (comments != '') {
                        var description = "<span class='infoBoxEvents'>" + comments + "</span><br>" + description;
                    }
                    eventHTML = eventHTML + "<div class='infoBoxDescription' id=description_" + event_id + ">" + description + "</div>";

                    //add to favorites button

                    eventHTML = eventHTML + "<div class='buttons' id='fav_" + event_id + "' ";
                    favorite = (events[k].getAttribute("favorite") == 1 ? 1 : 0);
                    markerFav = (favorite == 1 ? 1 : markerFav);
                    if (favorite) {
                        eventHTML = eventHTML + "onClick='delFavorite(" + event_id + ")'>Favorite!";
                    } else {
                        eventHTML = eventHTML + "onClick='addFavorite(" + event_id + ")'>Add to Favorites";
                    }
                    eventHTML = eventHTML + "</div>";


                    // More info button
                    eventHTML = eventHTML + "<div class='buttons floatRight' onClick=getEvent('" + event_id + "','" + event_title + "')>More info</div>";


                    var url = events[k].getAttribute("url");
                    eventHTML = eventHTML + "<div class='infoBoxDescription'>Website: <a target='blank' href=" + (url.substr(0, 3) == "www" ? "//" : "") + url + ">" + url.substring(0, 50) + (url.length > 50 ? "..." : "") + "</a></div>";
                }

                //Close infoContainer
                html[j] = "<div id='infoContainer' class='infoContainer'>" + eventHTML + "</div>" + dateBar;



            }
            if (typeof preEvent != "undefined") {
                markerIcon = blueDot;
                //map.setCenter(point)
                map.setZoom(14);
            } else if (markerFav == 1) {
                markerIcon = favDot;
            } else {
                markerIcon = blueDot;
            }


            var marker = new google.maps.Marker({
                map: map,
                position: point,
                title: org,
                icon: markerIcon
            });
	
	    //Give the marker its attributes
            marker.locID = id;
            marker.lat = lat;
            marker.lng = lng;
            marker.active = true;
            marker.html = html;

            marker.eventIDs = eventIDs;
            marker.eventIDpages = eventIDpages;
            marker.eventNames = eventNames;

            markers.push(marker);
            markerIDs.push(id);

	    // Attach the listener to the marker
            bindInfoBubble(marker, map, infoBubble, html[0]);

            if (typeof preEvent != "undefined") {
                //map.setZoom(12);
		$("#eventID").val("");
                activeMarker = marker;
                setMarkerPage(0, marker)
                calcMapBounds();
                var newCenter = marker.lat + (north - south) / 3;
                map.setCenter(new google.maps.LatLng(newCenter, marker.lng));
            }
            added++;
        }
    }
    $(listContent).html(eventsList);
    //markerRefresh();
}

function clearAllMarkers() {
  //Clears all markers for when we update the map
  for (var i = 0; i < markers.length; i++) {
    markers[i].setMap(null);
  }
  infoBubble.close();
  markers = [];
  markerIDs = [];
}


//////////////////////////////////////////
// INFO BUBBLE FUNCTIONS
//////////////////////////////////////////

function bindInfoBubble(marker, map, info, html) {
    //This is the infobubble listener that will be applied to every marker
    google.maps.event.addListener(marker, 'click', function () {
    	activeMarker = marker;
    	infoBubble.setContent(html);
    	setMarkerPage(0, marker);
    	var newCenter = marker.lat + (north - south)/3;
    	//_gaq.push(['_trackEvent', 'Click', 'Marker', 'Loc_'+activeMarker.locID]);
    	$('.gm-style-iw').css('overflow-y', 'hidden');
    
  });
}

//Function to change the Page being displayed
function setMarkerPage(page, marker) {
  var newHtml = activeMarker.html[page];
  infoBubble.setContent(newHtml);
  infoBubble.open(map, marker);
  
}


//////////////////////////////////////////////////
// AJAX FUNCTIONS
/////////////////////////////////////////////////

function loading() {
    //Simple "Loading.." dialogue. Soon to be replaced by an animation.
    openContent();
    $(backdrop).show();
    $(contentMain).html("Loading..");
    $(contentMain).show();

}

function formatLinks() {
	//function to convert links to internal ajax requests, so the user does not leave the page
	$('#contentMain a[type!=null]').click(function(e){
		var targetType = $(this).attr('type');
		if (targetType != null) {
			e.preventDefault();
			var targetUrl = $(this).attr('href');
          		var targetTitle = $(this).attr('title');
			var targetID = $(this).attr('id');
			if (targetType == "event") {
				getEvent(targetID, targetTitle);
			} else if (targetType == "org") {
				getOrg(targetID, targetTitle);
			}
		}
	});
}

function getEvent(q, x, ignore) {
  //q = event id, x = title
  // this is the main function to populate the menu screen for events
  lastEvent = q;
  clearActive();
  loading();
  var fullURL = "/ajax/events.php?eventID=" + q;
  var pushURL = "/event/" + q + "/" + (x != null ? x:"");
  _gaq.push(['_trackPageview', pushURL]);
  
    $.get(fullURL, null, function(result) {
        if (result == undefined) {
            //nothing
            console.log("Error getting Event");
        } else {
            if (ignore != true) {
	    	history.pushState(null, '', pushURL);
	    }
            $(contentMain).html(result);
	    formatLinks();
        }
    }, "html");
  
}

function editEvent(q) {
  //q = event id
  clearActive();
  loading();
  var fullURL = "/ajax/editEvent.php?eventID=" + q;
  var pushURL = "/event/" + q + "/edit";
  
    $.get(fullURL, null, function(result) {
        if (result == undefined) {
            //nothing
            console.log("Error getting Event");
        } else {
	    history.pushState(null, '', pushURL);
            $(contentMain).html(result);
        }
    }, "html");
  
}

function editOrg(o) {
  //o = org id
  clearActive();
  loading();
  var fullURL = "/ajax/editOrg.php?orgID=" + o;
  var pushURL = "/org/" + o + "/edit";
  
    $.get(fullURL, null, function(result) {
        if (result == undefined) {
            //nothing
            console.log("Error getting Org");
        } else {
	    history.pushState(null, '', pushURL);
            $(contentMain).html(result);
        }
    }, "html");
  
}

function getOrg(o, x, ignore) {
  //o = event id, x = title
  // this is the main function to populate the menu screen for orgs
  clearActive();
  loading();
  var fullURL = "/ajax/orgs.php?orgID=" + o;
  var pushURL = "/org/" + o + "/" + (x != null ? x:"");
  _gaq.push(['_trackPageview', pushURL]);
    $.get(fullURL, null, function(result) {
        if (result == undefined) {
            //nothing
            console.log("Error getting Org");
        } else {
            if (ignore != true) {
	    	history.pushState(null, '', pushURL);
	    }
            $(contentMain).html(result);
	    formatLinks();
        }
    }, "html");
  
}

function downloadPage(p, t, ignore) {
  //p = page url, t = /page
  // this is the main function to populate the menu screen for orgs
  loading();
    $.get(p, null, function(result) {
        if (result == undefined) {
            //nothing
            console.log("Error getting page");
        } else {
            if (ignore != true) {
            history.pushState(null, '', t);
        }
        $(contentMain).html(result);
        formatLinks();
        }
    }, "html");
  
}

function downloadCal(id, cat, sM, sY) {
	//Calendar HTML is processed by calendar.php
	fullURL = "/ajax/calendar.php?id="+id+"&cat="+cat+"&sM="+sM+"&sY="+sY;

	$.get(fullURL, null, function(result) {
        if (result == undefined) {
            //nothing
            console.log("Error getting calendar");
        } else {
        	$("#calendar").html(result);
        	formatLinks();
        }
    }, "html");
}

function tutorial(p) {
  //p = page of the tutorial
  loading();

  var fullURL = "/pages/tutorial"+ p + ".php"; 
  
    $.get(fullURL, null, function(result) {
        if (result == undefined) {
            //nothing
            console.log("Error getting Event");
        } else {
            $(contentMain).html(result);
        }
    }, "html");
  
}

function verifyLogin() {
    //PHP page verified access token to make sure the user is not a fake
    //JSON is returned
    $.get("/facebookLogin.php", null, function(result) {
        if (result == undefined) {
            //nothing
            console.log("Error getting page");
        } else {
        	if(result.loggedIn) {
			$("#welcomeButton").html("Welcome, " + result.name + "!");
			$("#loginButton").html("Log Out");
			loggedIn = true;
		}
        }
    }, "json");
  
}

function logout() {
	//Set the user as logged out, contact the PHP page to remove the session
	loggedIn = false;
	$("#welcomeButton").html("Welcome");
	$("#loginButton").html("Log In");
	downloadPage("/ajax/logout.php", "Logout", true);

}

////////////////////////////////////////////////////
// MODIFY EVENT FUNCTIONS
////////////////////////////////////////////////////

function startDateAdd() {
	$("#addFormDiv").show();
	$("#addDateButton").hide();
	$("#newDate").datepicker({
        	minDate: "-1D",
        	maxDate: "+1Y"
    	});
}

function reoccuring() {
	//Displays the page elements that allow you to create re-occuring events
	document.getElementById("addDateForm").reoccuring.value = 1;
	document.getElementById("addDateForm").recDate.value = document.getElementById("addDateForm").newDate.value;
	$("#reoccuringButton").hide();
	$("#reoccuringDiv").show();
}

function cancelReoccuring() {
	//Removes the page elements that allow you to create re-occuring events
	document.getElementById("addDateForm").reoccuring.value = 0;
	document.getElementById("addDateForm").recDate.value = document.getElementById("addDateForm").newDate.value;
	$("#reoccuringButton").show();
	$("#reoccuringDiv").hide();
}

function addDate() {
	//Process the Add Date form and send it to addDate.php
	var formData = $("#addDateForm").serialize();
	var fullURL = "/ajax/addDate.php";
	$("#addDateButton").hide();

	//Ajax request, HTML is returned with the new date list
	$.get(fullURL, formData, function(result) {
        	if (result == undefined) {
            		//nothing
            		console.log("Error getting Org");
        	} else {
			$("#dateArea").html(result);
	    		$("#addDateButton").show();
        	}
    	}, "html");	

}



//////////////////////////////////////////////////
//LIST FUNCTIONS
//////////////////////////////////////////////////

function listFocus(x) {

}
function listLoseFocus(x) {

}

function listClick(markerNum, page, q) {
	//Go from the list to the marker you've chosen
	var m = markers[markerNum];
	activeMarker = m;
	setMarkerPage(page, m);
	var newCenter = m.lat + (north - south)/3;
	map.setCenter(new google.maps.LatLng(newCenter, m.lng));
}