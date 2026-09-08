<div class="span1">    
   
</div>

<div class="span8">    
    <div class="well">        
        <h3> Development database overzetten naar productie database </h3> 
        
    <div class="alert ">
        <strong><i class="icon-exclamation-sign"></i> WAARSCHUWING. Verlies van data is mogelijk ! </strong>
       Lees de hieronder aangegeven instructies aandachtig door om data verlies te voorkomen.
    </div>
        
        <p>U staat op het punt de <i>development</i> database over te zetten naar de <i>productie</i> database. Dit houd in dat alle gegevens die momenteel in de <strong>productie
            database staan worden verwijderd </strong> en worden vervangen door de gegevens in de huidige development database.  <br /><br />
            
            mesaLIMS zal u eerst vragen een backup te downloaden van uw huidige productie database, dit bestand kan in het geval van data verlies gebruikt worden
            door VM-solutions om uw data te herstellen, <strong>bewaar backup files altijd goed en voor geruime tijd.</strong> </p> 
        
        <p> Een aantal tips om deze functie te gebruiken:
        <ol style="margin-left: 25px;">
            <li> Gebruik de development functie op momenten waar er geen andere gebruikers actief zijn </li>
            <li> Gebruik de development functie maar met 1 gebruiker te gelijk, of met goede communicatie. Om te voorkomen dat twee of meer gebruikers verschillende wijzigingen maken
                en deze vervolgens door zetten naar de productie database </li>
            <li> Controleer eerst of de download van het backup bestand geslaagd is, voor het overzetten te starten.</li>
            <li> Klik niet meerdere malen op "opslaan" knoppen, en sluit het venster niet voordat het process voltooid is </li>
            <li> Bij twijfel, neem contact op met joost@vm-solutions.nl </li>                 
        </ol>            
        </p>
        
         <h3> Uitvoeren </h3> 
         
         <div id="buttonPanel">
            <button class="btn btn-primary" id="understood"><i class="icon-question"></i> Heeft u bovenstaande gelezen, en begrepen? </button>     <br />     <br /> 
            <button class="btn btn-primary hide" id="logdownload"><i class="icon-download"></i> Logbestand downloaden </button><br /><br /> 
            <button class="btn btn-primary hide" id="logdownloadConfirm"><i class="icon-question"></i> Logbestand is opgeslagen? </button><br /><br /> 
            <button class="btn btn-primary hide" id="transferStart"><i id="exchangeIcon" class="icon-exchange"></i> Klik om overzetten te starten</button><br />
        </div>


        <a class="btn btn-primary" href="{LB}/"><i class="icon-question"></i> Terug naar dashboard</a>

    </div>

</div>

<div class="span1">    
  
</div>

<script>
    
var transferInitiated = false;

$(function(){
   
   $('#understood').on('click', function(){
       $(this).addClass('disabled');
       $('#logdownload').show();
   }); 
   
   $('#logdownload').on('click', function(){
       $(this).addClass('disabled');       
       log = window.open('{LB}/admin/backupDatabase','dbBackup','height=100,width=100, menubar=no,resizable=no,directories=no,location=no');       
       $('#logdownloadConfirm').show();
   }); 
   
    $('#logdownloadConfirm').on('click', function(){
       $(this).addClass('disabled');
       $('#transferStart').show();
   }); 
   
   $('#transferStart').on('click', function(){
       $(this).addClass('disabled');
       
       if(transferInitiated == false){
           transferInitiated = true;        
           $('#exchangeIcon').removeClass('icon-exchange');
           $('#exchangeIcon').addClass('icon-spinner');
           $('#exchangeIcon').addClass('icon-spin');
           startTransfer();
       } else{
           alert('Is al begonnen! Even geduld aub');
       }              
   }); 
   
});
    
    
function startTransfer(){
    
    $.ajax({
        type: "POST",     
        url: "{LB}/admin/pushDevToProd"
    }).done(function(response) {     
        alert('Productie database update voltooid!');
        $('#buttonPanel').hide();

    });   
    
}
    
</script>