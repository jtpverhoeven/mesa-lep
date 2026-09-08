<div class="span8">    
    <div class="well">        
        <h3> Referentie bron aanmaken </h3>         
        {editFieldForm}                             
    </div>

</div>

<div class="span2">

    <ul class="nav nav-list well">                                     
        <li class="nav-header">Opties </li>
        <li><a href="#" id="saveRefSource" ><i class="icon-save"></i> {MESA_SMF_EDITSAVECHANGES} </a> </li>               
        <li><a href="{LB}/referenceSources/index"><i class="icon-backward"></i> {MESA_SMF_EDITBACKTOLIST} </a> </li>
    </ul>
    
</div>

<script> 

$(function(){


    //check if i should hide
    if($('#global').val() == '1'){
        $('#client_nameCG').hide();
    } else{
        $("#client_name").select2("val", "{client}");
    }

    $('#global').on('change', function(){

        if($(this).val() == '1'){
            //global
            $('#client_nameCG').hide();
            $('#client_name').val('');
            $('#client').val('');
        }

        if($(this).val() == '0'){
            //client specific
            $('#client_nameCG').show();
            $('#client_name').select2("val", '{client}');
            $('#client').val('{client}');
        }
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

});


</script> 