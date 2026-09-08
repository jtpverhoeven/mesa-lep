<div class="span8">
    <div class="row-fluid">
	   	<div class="well">
        	
            <h3> Bulk aanmelden </h3>

            <form id="bulkForm" enctype="multipart/form-data" action="{LB}/sampleBuffers/uploadBulkCSV" method="POST">

            	<h5>Stap 1: Selecteer CSV bestand </h5>            
            	<input id="bulkFile" name="bulkFile" type="file" accept="text/csv">

                <h5>Stap 2: Selecteer Klant </h5>            
                    {client_name}
                    {client}

                <div id="clientInfoFetchDisplay" style="padding-bottom: 5px;"></div>

                <h5>Stap 3: Selecteer scheidings teken voor CSV (standaard: ";") </h5>            
                {seperator}

            	<h5>Stap 4: Klik upload knop </h5> 
            	En controleer in het volgende scherm de inhoud van het bestand. <br/> 
    			<button class="btn btn-primary" id="bulkFileUpload" type="button" value="Uploaden" onclick="submitUploadForm()"> Importeren </button></p>
            </form>
    	</div>
    </div>
</div>

<div class="span2">
<p>
    <strong> Hoe werkt deze import functie? </strong> <br />
    <br />
    <strong>1</strong> Klik <a href="{LP}/doc/maz_bulk.xlsm">hier</a> om het bulk formulier te downloaden. <br />
    <br />
    <strong>2</strong> Sla het 3de tabblad op als CSV (UTF-8) en gebruik dit bestand hier in de uploader <br />
    <br />
    <strong>Notitie</strong> Het wachtwoord voor dit Excel bestand te ontgrendelen is: &quot;porifera&quot; <br />

</p>
</div>

<script>

$(function(){


    $("#client_name").select2({
            minimumInputLength: 2,
            placeholder: "{MESA_SAD_SELECTCLIENT}",

            ajax: {
            type: "POST",
            url: "{LB}/clients/predictNoSub",
            dataType: 'json',
            quietMillis: 350,
            data: function (term, page) {
                return {
                    term: term, //search term
                    page_limit: 10 // page size
                };
            },
            results: function (data, page) {
                return { results: data.results };
            }

            },
            initSelection: function(element, callback) {
                    //return $.getJSON("/ajax/select2_sample.php?id=" + (element.val()), null, function(data) {
                    //return callback(data);
                    //});
        },
        dropdownCssClass: "bigdrop"
       }).on('change', function(){

            //check if its empty
            if ($(this).val() == ''){
                $('#client').val('NULL');
                $("#subclient").val('NULL');                
                return;
            }

            else{
                clSplit = $(this).val().split("||");                
                $('#client').val(clSplit[0]);
                fetchClientInfo(clSplit[0]);
            }

       });

   });

    function submitUploadForm(){

    if ($('#bulkFile').get(0).files.length === 0) {
        alert("Geen bestand geselecteerd");
        return;
    }

    if (!$('#bulkFile').hasExtension(['.csv', '.CSV'])) {
        alert('Geen CSV bestand.');
        return;
    }


    if($('#client').val() == 'NULL' || $('#client').val() == ''){
        alert('Geen klant geselecteerd');
        return;
    }

    $('#bulkForm').submit();


    }

    function fetchClientInfo(selected_client)
{

    $.ajax({
            type: "POST",    
            data: {cid: selected_client, scid: false},
            url: "{LB}/applets/clientInfoFetch" 
        }).done(function(response) {                                   
            $('#clientInfoFetchDisplay').html(response);
        });

}

</script>