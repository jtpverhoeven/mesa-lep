
<div class="span5">

    <div class="row-fluid">

        <div class="well well-small">
            <h5 style="padding-bottom: 10px;"><i class="icon icon-search"></i> Zoeken </h5>
            
            <div id="searchDiv">

                <div>       
                    {client_name} {client}
                </div>

                <div style="display: flex; width: 100%">

                    <div style="flex-grow: 1">{date_from}</div>
                    <div style="flex-grow: 1">{date_to}</div> 

                </div> 


                <div style="display: flex; width: 100%">

                    <div style="flex-grow: 1">{report_type}</div>
                    <div style="flex-grow: 1; margin-left: 5px;">{hide_temp}</div> 
                    <div style="flex-grow: 1; align-self: center; margin-left:15px; "><button id="searchBtn" class="btn btn-primary" type="button">Zoeken</button></div> 

                </div> 
              
            </div> 
    
            <hr />
    
            <div id="resultsDiv"> 
                <table class="table table-condensed table-bordered" style="margin-top: 20px" id="exportsTable">
                    <thead>
                        <tr>
                            <th><i class="icon-check"></i></th>                    
                            <th>Referentie</th>
                            <th>Verstuurd op</th>                         
                            <th>Auth. Datum</th>                         
                            <th>Acties</th>                         
                        </tr>
                    </thead>

                    <tbody id="exportBody">

                    

                    </tbody>
                </table>

            </div> 

            <div id="downloadDiv"> 
                <img src="{LB}/images/arrow_ltr.png" /> <a onClick="selectAll()">Alles selecteren</a>
                <button class="btn btn-small btn-primary" onClick="downloadCollection()"> Downloaden </button>
            </div>


        </div> 

    </div>
    </div>


<div class="span5">
    <div class="well well-small">
        <h5 style="padding-bottom: 10px;"><i class="icon icon-briefcase"></i> Inhoud </h5>
        <iframe id="viewer" src="" width="100%" height="680px"></iframe>
    </div>
</div> 

<form id="downloadExportForm" action="" method="POST">
    <input type="hidden" id="downloadIds" name="downloadIds" />    
</form>




<script>
$(function() {

    $('#downloadDiv').hide();

    $('#resultsDiv').slimScroll({
            height: '450px',
            railVisible: true,
            railOpacity: 0.1,
            alwaysVisible: true           
    });


    $("#client_name").select2({
        minimumInputLength: 2,
        placeholder: "Select a client",

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
                return $.getJSON("{LB}/clients/predictInit/" + (element.val()), null, function(data) {
                return callback(data);
            });
        },
        dropdownCssClass: "bigdrop"
    }).on('change', function() {
        $('#client').val($(this).val());
    });

    $('#searchBtn').on('click', function()
    {
        doSearch();
    }); 

    var dtable = $('#exportsTable').DataTable({
        "paging":   false,
        "info":     true,
        "ordering": true
    });


});




function doSearch()
{

    var client  = $('#client').val(); 
    var date_start = $('#date_from').val(); 
    var date_end  = $('#date_to').val(); 
    var type = $('#report_type').val(); 

    $('#downloadDiv').show();

    $.ajax({
        type: "POST",
        data: {
            'client' : client,
            'date_start' : date_start,
            'date_end' : date_end,
            'type' : type,
            'hide_temp' : $('#hide_temp').val(),
            'report_type' : $('#report_type').val()
        },
        url: "{LB}/exports/doSearch"
    }).done(function(msg) {        
        
        $('#exportsTable').dataTable().fnDestroy();

        $('#exportBody').html(msg);
        
        reinit(); 
        
    });

}

function reinit()
{
    

    $('#exportsTable').DataTable({
        "paging":   false,
        "info":     true,
        "ordering": true
    });
}


function selectAll()
{

    console.log('yo');

    var doCheck = undefined; 

    // input[type=checkbox]:visible
    $('#resultsDiv .selectedReport').each(function () {
    
        console.log($(this));

        if(doCheck === undefined)
        {
            doCheck =  !$(this).prop( "checked");

        }        
        
        $(this).prop( "checked", doCheck );
    
    });
}

function view(id)
{    
    $('#viewer').attr('src', '{LB}/exports/exportContentViewer/' + id );
}

function download(id)
{
    window.location.href = "{LB}/exports/downloadRevision/" + id;
}

function downloadCollection()
{
    //var exportIds = $("#reportsTable input:checkbox:checked:visible").map(function(){
    

    var exportIds = $("#resultsDiv .selectedReport").map(function(){

        if($(this).prop( "checked"))
        {
            return $(this).val();
        }
        
    }).get();

    var commitString = JSON.stringify(exportIds);
    
    $('#downloadIds').val(commitString);
    $('#downloadExportForm').attr('action', '{LB}/exports/downloadZipCollection');
    $('#downloadExportForm').submit();
    
    
}

</script> 