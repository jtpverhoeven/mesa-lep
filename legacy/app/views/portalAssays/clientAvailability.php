    <div class="span4">
    <div class="well">
        <h4>Klant met beschikking over deze portal analyse </h4>
        <h5>Portal analyse: {template_name}</h5>

        {portal_assay_table}

      

    </div>
</div>

<div class="span4">
    <div class="well">

        <div id="actionsDiv">
            <h6>Acties  voor tabel</h6>      

            <button onclick="filterOnAvailable()" class="btn btn-mini">Toon enkel met beschikbaarheid</button>
            
            <button onclick="filterOnNotAvailable()" class="btn btn-mini">Toon enkel zonder beschikbaarheid</button>

            <button onclick="showAll()" class="btn btn-mini">Toon alles</button>


            <h6>Bulk operaties</h6>      

            <button onclick="connectAll()" class="btn btn-mini">Toevoegen aan alle klanten</button>

            <button onclick="deleteAll()" class="btn btn-mini">Verwijderen van alle klanten</button>

            <h6>Kopieren</h6>      

            Kopieer klant beschikbaarheid vanuit deze common-assay: <select id="selectedCopyAssay">{assay_opts}</select> naar de huidige geopende assay      <button onclick="copyFrom()" class="btn btn-mini">Kopieer</button>
        
        </div>

        <div id="loadingDiv">
            <svg width="64" height="64" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><style>.spinner_d9Sa{transform-origin:center}.spinner_qQQY{animation:spinner_ZpfF 9s linear infinite}.spinner_pote{animation:spinner_ZpfF .75s linear infinite}@keyframes spinner_ZpfF{100%{transform:rotate(360deg)}}</style><path d="M12,1A11,11,0,1,0,23,12,11,11,0,0,0,12,1Zm0,20a9,9,0,1,1,9-9A9,9,0,0,1,12,21Z"/><rect class="spinner_d9Sa spinner_qQQY" x="11" y="6" rx="1" width="2" height="7"/><rect class="spinner_d9Sa spinner_pote" x="11" y="11" rx="1" width="2" height="9"/></svg>
            <p> Een moment geduld aub, de klant beschikbaarheid aangepast...</p>
        </div>


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
    

});

function filterOnAvailable(){    
    $('#assayAssociationTable').DataTable().column(4).search('Ja').draw();
}

function assayToggle(client){    

    //read the checkbox state
    var checkbox = $('#client_' + client);


    //update this row to say yes
    var row = $('#tr_' + client);
    
    //find the 4th td
    var td = row.find('td').eq(4);

    //if activate change the td content to Ja
    var active = 0;
    if(checkbox.is(':checked')){
        active = 1;
        td.html('Ja');
    } else {
        active = 0;
        td.html('Nee');
    }

    
    $.ajax({
        type: "POST",   
        data: {client: client, assay: {assay_id}, active: active},        
        url: "{LB}/portalAssays/setClientAvailability"
    }).done(function(msg) {                                 

    });  
    

    //did the table have a filter?
    var filter = $('#assayAssociationTable').DataTable().column(4).search();

    //recreate the datatable, this is very silly, but it works for now. 
    $('#assayAssociationTable').DataTable().destroy();
    
    $('#assayAssociationTable').DataTable({
        "paging":   false,
        "info":     false,
        "order": [[ 1, "asc" ]],
                
    });

    //reapply the filter
    $('#assayAssociationTable').DataTable().column(4).search(filter).draw();
        

}

function filterOnNotAvailable(){
    $('#assayAssociationTable').DataTable().column(4).search('Nee').draw();
}

function showAll(){
    //clear filter
    $('#assayAssociationTable').DataTable().column(4).search('').draw();
}

function copyFrom()
{

    //get the label of the selected option in selectedCopyAssay
    var selectedAssay = $('#selectedCopyAssay option:selected').text();
    var copyFrom = $('#selectedCopyAssay').val();    

    if(!confirm('Weet u zeker dat u de klant-beschikbaarheid van [' + selectedAssay +'] wilt kopieren naar de de geopende analyse [{template_name}]? De huidige instellinven voor [{template_name}] worden verwijderd!')){
        return false;
    }

    $('#loadingDiv').show();
    $('#actionsDiv').hide();


    $.ajax({
        type: "POST",   
        data: {assay: {assay_id}, copy_from : copyFrom },
        url: "{LB}/portalAssays/bulkCopy"
    }).done(function(msg) {                                 
        //reload page
        window.location.reload();        
    });  


}

function deleteAll(){

    //confirm
    if(!confirm('Weet u zeker dat u deze analyse van alle klanten wilt verwijderen?')){
        return false;
    }

    $('#loadingDiv').show();
    $('#actionsDiv').hide();

    $.ajax({
        type: "POST",   
        data: {assay: {assay_id}},        
        url: "{LB}/portalAssays/bulkRemove"
    }).done(function(msg) {                                 
        //reload page
        window.location.reload();        
    });  

}

function connectAll(){
    
        //confirm
        if(!confirm('Weet u zeker dat u deze analyse aan alle klanten wilt toevoegen?')){
            return false;
        }

        var includeStale = false;

        if(confirm('Wilt u inactieve klanten ook betrekken in deze operatie?')){
           includeStale = true;
        }

        $('#loadingDiv').show();
        $('#actionsDiv').hide();
    
        
        $.ajax({
            type: "POST",   
            data: {assay: {assay_id}, includeStale: includeStale},        
            url: "{LB}/portalAssays/bulkAdd"
        }).done(function(msg) {                                 
            //reload page
            window.location.reload();        
        });

}

</script>
