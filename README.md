# Questival
Questival Events Website focused on mapping Chicago's festivals and other events

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
