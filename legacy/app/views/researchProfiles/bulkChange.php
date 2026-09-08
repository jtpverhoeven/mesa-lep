<div class="span5">
    <div class="well">
        <h3> Bulkwijziging </h3>

       Alles  <a onclick="selectAll()" href="#"> selecteren</a> |  <a onclick="deselectAll()" href="#">deselecteren</a> 
        {available_profiles}

    </div>
</div>

<div class="span5 well">
    <ul class="nav nav-list  ">
        <li class="nav-header">Wijzig het volgende</li>        
    </ul>
    <form id="bulkMutationForm" action="{LB}/researchProfiles/executeBulkChange" method="POST"> 
    <div class="flex flex-row items-center">
        <div><input type="radio" name="selectedMutation" id="selectedMutationAdd" value="add" class="pm-0" /></div>
        <div><label for="selectedMutationAdd">Analyse toevoegen of bestaande analyse updaten met nieuwe analyse-instellingen</label></div> 
    </div>

    <div class="flex flex-row items-center">
        <div><input type="radio" name="selectedMutation" id="selectedMutationDelete"  value="delete"  class="pm-0"/></div>
        <div><label for="selectedMutationDelete">Analyse verwijderen</label></div>        
    </div>
 
    <div class="flex flex-row items-center">
        <div><input type="radio" name="selectedMutation" id="selectedMutationSwap" value="swap"  class="pm-0"/></div>
        <div><label for="selectedMutationSwap">Analyse vervangen (de analyse-instellingen* blijven behouden)</a></div>
    </div>

    <div class="flex flex-row items-center" id="primaryAssayDiv">

        <div>Analyse:</div>
        
        <div>
            <select name="primaryAssay" id="primaryAssay">
                {available_assays}
            </select>
        </div> 

    </div>


    <div class="flex flex-row" id="secondaryAssayDiv" id="secondaryAssay">

        <div>Vervang met analyse:</div>
        
        <div>
            <select name="secondaryAssay" id="secondaryAssay">
                {available_assays}
            </select>
        </div> 

    </div>

    <button type="button" onclick="checkSubmit()" class="btn btn-primary mt-4">Uitvoeren</button>

    <div id="matrixDiv">

        <h6><i class="icon-beaker"></i> Toe te voegen analyse </h6>

        <table width="100%" class="table">
            <thead>
                <tr>                        
                    <th>{MESA_ERP_ASSAY}</th>
                    <th>{MESA_ERP_DILUTIONS}</th>
                    <th>{MESA_ERP_REPLICATES}</th>
                    <th>{MESA_ERP_REFERENCEVALUE}</th>
                    <th>Ref. bron </th>
                    <th>Bevestig boven</th>                      
                </tr>
            </thead>
            <tbody id="assayBody">
            </tody>
        </table>
                
        <h6><i class="icon-beaker"></i> {MESA_ERP_AVAILABLEASSAYS} </h6>
        
        <div class="control-group" id="matrixSelectorCG">
            <div class="controls">
                <div class="input-prepend input-append input-block-level">
                    <span class="add-on">Matrix</span>
                    <select id="matrixSelector" name="matrixSelector" class="input-block-level">{matrix_content}</select>
                </div>
            </div>
        </div>

        <table style="width: 100%">
            <tr>
                <td>
                    <label for="filterQ">
                    <input value="q" type="checkbox" class="filterTick" id="filterQ" style="zoom: 1.1;" />
                    Geaccrediteerd
                    </label>
                </td>
                
                <td style="text-align: right;">
                    <label for="filterNonQ">
                        <input value="nq" type="checkbox" class="filterTick" id="filterNonQ" style="zoom: 1.1;" />
                        Niet Geaccrediteerd
                    </label>
                </td>
            </tr>
        </table>

        <div class="control-group" id="matrixSearcherCG">
            <div class="controls">
                <div class="input-prepend input-append input-block-level">
                    <span class="add-on">Zoeken in matrix</span>
                    <input type="text" id="matrixSearcher" name="matrixSearcher" class="input-block-level" />
                </div>
            </div>
        </div>

        <div id="assaysDiv">
            <div id="assaysList" class="list-group">
            </div>
        </div>
        
    </div>

    <div>
        <p class="text-info" style="margin-top: 5px;">**Analyse instellingen zijn: verdunningen, referentiewaarden en bronvermelding.</p>
    </div>
    

</div> 
  

<div id="addAssayModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="addAssayModalTitle" aria-hidden="true">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h3 id="addAssayModalTitle">{MESA_ERP_ADDTORESPROF}</h3>
        </div>
        <div class="modal-body" id="addAssayModalBody" >
            {add_assay_form}
        </div>
        <div class="modal-footer">
            <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_ERP_CANCEL}</button>
            <button type="button" class="btn btn-primary" id="addAssaySubmit"  aria-hidden="true"><i class="icon-save"></i> {MESA_ERP_SAVE}</button>
            <button type="button" class="btn btn-primary" id="editAssaySubmit"  aria-hidden="true"><i class="icon-save"></i> {MESA_ERP_EDIT}</button>
        </div>
    </div>


<script>
 var openAssay = false;
var selectedAssays = [];

$(function(){

    $('#primaryAssayDiv').hide();
    $('#secondaryAssayDiv').hide();
    $('#matrixDiv').hide();

    $('#profileTable').DataTable({
            "paging":   false,
            "info":     false,
            "order": [[ 2, "asc" ]]
        });

    $('#matrixSelector').on('change', function(){
        clearTableFilter();
        loadInMatrix();
    });

    $('#matrixSearcher').on("keyup", function() {
        var value = $(this).val();
        liveSearchTable(value, 'assaysList');
    });

    
    $('#assaysDiv').slimScroll({
       height: '500px'
    });

    $('.filterTick').click(function() {
        
        clearTableFilter();    

        if ($(this).is(':checked')) {
          disableFilters();
          applyMatrixFilter($(this).val());
        } else{
          disableFilters();
        }
    });

    $('input[name="selectedMutation"]').on("click", function() {
            
            var mutation = $('input[name="selectedMutation"]:checked').val();
            
            if(mutation =='add')
            {
                $('#primaryAssayDiv').hide();
                $('#secondaryAssayDiv').hide();
                $('#matrixDiv').show();
            }

            if(mutation =='delete')
            {
                $('#primaryAssayDiv').show();
                $('#secondaryAssayDiv').hide();
                $('#matrixDiv').hide();
            }

            if(mutation =='swap')
            {
                $('#primaryAssayDiv').show();
                $('#secondaryAssayDiv').show();
                $('#matrixDiv').hide();
            }

    });

    $('#bulkMutationForm').submit(function(eventObj) {
                        
        
        var checked = []
    
        $('input[name="selectedProfiles"]:checked').each(function ()
        {
            checked.push($(this).val());
        });

        if(checked.length == 0)
        {
            alert('Er zijn geen profielen geselecteerd');
            return false;
        }


        //show a confirm message with the amount of selected profiles
        var confirmMessage = 'Weet u zeker dat u de geselecteerde bulkwijziging wilt uitvoeren op de  ' + checked.length + ' geselecteerde profielen?';

        if(!confirm(confirmMessage))
        {
            return false;
        }
        

        $(this).append('<input type="hidden" name="profiles" value="' + checked +'" /> ');

        $(this).append('<input type="hidden" name="addition" id="addition" /> ');

        $('#addition').val(JSON.stringify(selectedAssays));

        
        return true;
    });

    $('#addAssaySubmit').on('click', function(){
        
        var selectedSource = $('#reference_source').val();
        var setReference = $('#ref_kve').val(); 
        var referenceIsRestricted = $('#ref_kve').hasClass('numerical-only-filter');


        if(selectedSource == '' && setReference != '' )
        {


            alert('Een referentie bron is verplicht');
            return;

            
        }

        if(selectedSource != '' && setReference == '')
        {

            if(referenceIsRestricted == true)
            {
                alert('Een bron is bij een referentie-loos onderzoek niet nodig');
                return; 
            }
                
            
        }     

        $('#addAssayModal').modal('hide');
        var setObj = {};
        selectedAssays = [];

        $('#addAssayForm *').filter(':input').each(function () {
            setObj[$(this).attr('name')] = $(this).val();
            setObj['assay_id'] = openAssay;
        });

        console.log(setObj);

        addAssay(openAssay, setObj);
        });


    $('#assaysList, #searchList').on('click', 'a', function(){

        $('#editAssaySubmit').hide();
        $('#addAssaySubmit').show();

         openAssay = $(this).attr('assayid');
        // if(openAssay in selectedAssays){
        //     alert('{MESA_ERP_ALREADYREQ}');
        //     return;
        // }

        $.ajax({
            type: "POST",
            url: "{LB}/researchProfiles/updateAssayForm/{id}/" + $(this).attr('assayid') + "/" + $('#client').val()
        }).done(function(msg) {

            $('#addAssayModalBody').html(msg);
            $('#addAssayModal').modal('show');
        });
    });



});

function checkSubmit()
{

    var mutation = $('input[name="selectedMutation"]:checked').val();    

    if(!mutation)
    {
        alert('Selecteer een wijziging');
        return false;
    }

    if(mutation =='add')
    {
        if(selectedAssays.length == 0)
        {
            alert('Geen analyse geselecteerd!');
            return false;
     
        }
    }

    if(mutation =='swap')
    {
     
        if($('#primaryAssay').val() == $('#secondaryAssay').val())
        {
            alert('De vervangende analyse mag niet gelijk zijn aan de te vervangen analyse');
            return false;
        }
    }
    
    //count selectedProfiles
    if($('input[name="selectedProfiles"]:checked').length == 0)
    {
        alert('Selecteer minimaal 1 profiel');
        return false;
    }

    $('#bulkMutationForm').submit();
}

function selectAll(){

    $('#profileTable input[type=checkbox]').each(function () {
        
        $(this).prop( "checked", true );

    });

}


function deselectAll(){

$('#profileTable input[type=checkbox]').each(function () {
    
    $(this).prop( "checked", false );

});

}

function addAssay(assayId, settings){

    selectedAssays[assayId] = settings;

    var currentNumberOfAssays =  $('#assayBody tr').length;
    var thisNewOrder =  selectedAssays[assayId]['order'];    


    if (typeof thisNewOrder == 'undefined') {
        selectedAssays[assayId]['order'] = currentNumberOfAssays+1;
    }

    console.log(selectedAssays);

    $.ajax({
        type: "POST",
        data: {data: selectedAssays, prevent_sorting : true},
        url: "{LB}/assayProfiles/renderAssayRequest"
    }).done(function(msg) {
        $('#assayBody').html(msg);
        
        selectedAssays = settings; 


    });

}


</script>


