<i>Deze tabel word iedere 30 seconden automatisch ververst </i>

<p class="{timestamp_class}"> Waardes opgeslagen om: {saved_time} </p> 

{table}

<button id="save_media_prediction">Huidige waardes opslaan</button>

<script>
$(function(){

    $('#mediaPrediction').DataTable({
        "paging":   false,
        "info":     false,
        "order": [[ 1, "asc" ]]
   
   
    });

    $('#save_media_prediction').on('click', function(){
        $.ajax({
            type: "POST",                      
            url: "{LB}/media/store_media_prediction"
        }).done(function(response) {
         
        });
    });



});





</script> 