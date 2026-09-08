<div class="span4">
    <div class="well">
        <h4>Bulk kopieer klant beschikbaarheid van analyses</h4>
        
        
        <h5>Selecteer een klant</h5>

        <select id="clientOrigin" name="clientorigin">
            {client_opts}
        </select>      

        <h5>Kopieer de analyse beschikbaarheid van deze klant naar deze andere klanten</h5>
        {portal_assay_table}    
      
    </div>
</div>

<div class="span4">
    <div class="well">

        <div>
            
            <h5><span id="selectedClientName"></span> heeft toegang toe</h5>

        </div>

        

        <div id="loadingDiv">
            <svg width="64" height="64" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><style>.spinner_d9Sa{transform-origin:center}.spinner_qQQY{animation:spinner_ZpfF 9s linear infinite}.spinner_pote{animation:spinner_ZpfF .75s linear infinite}@keyframes spinner_ZpfF{100%{transform:rotate(360deg)}}</style><path d="M12,1A11,11,0,1,0,23,12,11,11,0,0,0,12,1Zm0,20a9,9,0,1,1,9-9A9,9,0,0,1,12,21Z"/><rect class="spinner_d9Sa spinner_qQQY" x="11" y="6" rx="1" width="2" height="7"/><rect class="spinner_d9Sa spinner_pote" x="11" y="11" rx="1" width="2" height="9"/></svg>
            <p> Een moment geduld aub, de klant beschikbaarheid aangepast...</p>
        </div>

        <div id="clientAvailabilityTable">
        
        </div>

        <button class="btn btn-primary" id="cloneToSelected"><i class="icon-copy"></i> Kopieren naar geselecteerde klanten</button>


    </div>
</div>

<div class="span2">

        <ul class="nav nav-list well">
            <li class="nav-header">Acties </li>

            <li><a href="{LB}/portalAssays/listing"><i class="icon-backward"></i> Terug naar overzicht </a> </li>


        </ul>

</div>

<script>
$(function(){


    $('#loadingDiv').hide();


    $('#assayAssociationTable').DataTable({
        "paging":   false,
        "info":     false,
        "order": [[ 1, "asc" ]],
                
    });

    $('#reloadClient').click(function(){     
        loadClientAvailability();
    }); 

    $("#clientOrigin").on("change", function(){
        loadClientAvailability();
    });

    $('#cloneToSelected').click(function(){
        cloneToSelected();
    });

    loadClientAvailability();
});

function loadClientAvailability()
{

    $('#loadingDiv').show();

    var clientOrigin = $('#clientOrigin').val();

    $('#selectedClientName').html($('#clientOrigin option:selected').text());   

    $.ajax({
        url: "{LB}/portalAssays/getClientAvailability",
        type: "POST",
        data: {client: clientOrigin},
        
        success: function(data){
            
            $('#clientAvailabilityTable').html(data);
            
            $('#loadingDiv').hide();

        }
    });

}

function cloneToSelected()
{

    //count number of selected
    var selected = 0;
    var clientIds = []; 

    $('input[name="selectedClient"]').each(function(){
        if($(this).is(':checked'))
        {
            selected++;
            clientIds.push($(this).attr('id').replace('client_', ''));
        }
    });

    //confirm
    if(selected == 0)
    {
        alert('Selecteer minstens 1 klant');
        return false;
    }

    //confirm, with the number of selected
    if(!confirm('Weet u zeker dat u de beschikbaarheid van ' + selected + ' klanten wilt aanpassen?'))
    {
        return false;
    }

    doClone(clientIds);

    
}

function doClone(clientIds)
{
    var clientOrigin = $('#clientOrigin').val();

    $('#loadingDiv').show();

    $.ajax({
        url: "{LB}/portalAssays/cloneClientAvailability",
        type: "POST",
        data: {client: clientOrigin, clients: clientIds},
        
        success: function(data){
            
            //reload page
            location.reload();

        }
    });
}


</script>
