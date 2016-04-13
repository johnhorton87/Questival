# Questival
Questival Events Website focused on mapping Chicago's festivals and other events

Events are stored in a MySQL database and access via PHP. Site interface is HTML / Javascript with AJAX requests pulling in event data as the user's search is narrowed.

Site is located at [www.questival.events](www.questival.events)

### File Descriptions
  * index.php -> Main interface for questival, all subsequent pages loaded through AJAX
  * questival.js -> Javascript file that houses all of the functions that run questival
  * style.css -> Style sheet for formating the map and interface
  * /ajax -> Folder that houses the dynamic pages pulled in via AJAX
    * events.php -> PHP file that queries Event info from the database and outputs it in HTML for an AJAX request
    * addDate.php -> PHP file that takes a users input and adds new date records for an event
    * orgs.php -> PHP file that queries Organization info from the database and outputs it in HTML format for an AJAX request
    * markers.php -> PHP file which downloads events based on search criteria, groups them by location, and outputs them in XML
    * favoritesList.php -> PHP file that queries the upcoming favorite events for a specified user and outputs them in HTML

### Requires
  * Google Maps API Key
  * JQuery API

### Database Tables
  * Orgs - Org_ID, Organization Name, Owner (User_ID), Website, Description
  * Questival - Event_ID, Event Names, Org_ID, Event Description, Cost, Website
  * Locations - Location_ID, Latitude, Longitude, Name, Address
  * Quest_Dates - Date_ID, Event_ID, Date, Time, Location_ID
  * Users - User_ID, Name, Email
  * Favorites - Favorite_ID, User_ID, Event_ID
