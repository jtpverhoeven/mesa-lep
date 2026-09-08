<script>

function updateClock(){
    var currentTime = new Date ( );
    var currentHours = currentTime.getHours ( );
    var currentMinutes = currentTime.getMinutes ( );
    var currentSeconds = currentTime.getSeconds ( );
     
    currentMinutes = ( currentMinutes < 10 ? "0" : "" ) + currentMinutes;
    currentSeconds = ( currentSeconds < 10 ? "0" : "" ) + currentSeconds;
     
 
    // Convert the hours component to 12-hour format if needed
    currentHours = ( currentHours < 10 ? "0" : "" ) + currentHours;
 
    // Convert an hours component of "0" to "12"
    //currentHours = ( currentHours == 0 ) ? 12 : currentHours;
 
    // Compose the string for display
    var currentTimeString = currentHours + ":" + currentMinutes + ":" + currentSeconds;
     
     
    $("#clock").html(currentTimeString);    
}

    
$(document).ready(function(){
    updateClock();
    setInterval('updateClock()', 1000);
});
    
</script>