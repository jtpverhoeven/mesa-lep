<div class="span8">
    <div class="well">
        <h3> {MESA_LBE_LISTING} </h3>

        <h5> {MESA_LBD_SAMPLEREG}</h5>
        {group1Table}
        <button class="btn btn-primary btn-mini" id="addSampleReg"><i class="icon-plus"></i> {MESA_LBD_ADDEVENT}</button>

        <h5> {MESA_LBD_ASSAYREG}</h5>
        {group2Table}
        <button class="btn btn-primary btn-mini" id="addAssayReg"><i class="icon-plus"></i> {MESA_LBD_ADDEVENT}</button>

        <h5> Bij THT monster goedkeuren / aanmelden </h5>
        {group3Table}
        <button class="btn btn-primary btn-mini" id="addTHTReg"><i class="icon-plus"></i> {MESA_LBD_ADDEVENT}</button>

    </div>
</div>




<div id="addEventModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="addEventModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="addEventModalTitle">{MESA_LBD_ADDEVENT}</h3>
    </div>
    <div class="modal-body" id="addEventModalBody" >



    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_LBD_CANCEL}</button>
        <button class="btn btn-primary" id="addEventSubmit"  aria-hidden="true"><i class="icon-save"></i> {MESA_LBD_SAVE}</button>
    </div>
</div>


<div id="editEventModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="editEventModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="editEventModalTitle">{MESA_LBD_EDITEVENT}</h3>
    </div>
    <div class="modal-body" id="editEventModalBody" >


    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_LBD_CANCEL}</button>
        <button class="btn btn-primary" id="editEventSubmit"  aria-hidden="true"><i class="icon-save"></i> {MESA_LBD_SAVE}</button>
    </div>
</div>


<div id="addTHTmodal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="addTHTmodalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="addTHTmodalTitle">{MESA_LBD_ADDEVENT}</h3>
    </div>
    <div class="modal-body" id="editEventModalBody" >


    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_LBD_CANCEL}</button>
        <button class="btn btn-primary" id="addTHTsubmit"  aria-hidden="true"><i class="icon-save"></i> {MESA_LBD_SAVE}</button>
    </div>
</div>

<script>

var eventGroup = false;


$(function(){

   $('#addEventSubmit').on('click', function(){
        submitEvent();
   });

   $('#addSampleReg').on('click', function(){
       loadEventModal('1');
       eventGroup = 1;
   });

   $('#addAssayReg').on('click', function(){
       loadEventModal('2');
       eventGroup = 2;
   });

   $('#addTHTReg').on('click', function(){
       loadEventModal('3');
       eventGroup = 3;
   });

    $('#addEventModalBody').on('change', '#event', function(){

        console.log('in change event');

        if($(this).val() == 'P'){

          if(eventGroup == 1){
              hideAllEventGroup1();
              $("#group1_condition").val("NULL");
          }
          if(eventGroup == 2){
             hideAllEventGroup2();
             $("#group2_condition").val("NULL");
          }

           $('#negative_filterCG').hide();
      }

      if($(this).val() == 'L'){

            console.log('is P ')
            console.log('eventgroup' + eventGroup);

           if(eventGroup == 1){
                $('#group1_conditionCG').show();
           }

           if(eventGroup == 2){
                 $('#group2_conditionCG').show();
           }
           $('#negative_filterCG').show();
      }
    });

    $('#addEventModalBody').on('change', '#group1_condition', function(){

        hideOptionsGroup1();

        if($(this).val() == 'sField'){
            $('#sample_fieldCG').show();
            $('#sample_field_equalsCG').show();
        }

        if($(this).val() == 'sClient'){
            $('#client_nameCG').show();
        }

        if($(this).val() == 'mMethod'){
            $('#sample_collectionCG').show();
        }

        if($(this).val() == 'mType'){
            $('#sample_typeCG').show();
        }

    });


    $('#addEventModalBody').on('change', '#group2_condition', function(){

        hideOptionsGroup2();

        if($(this).val() == 'assayIs'){
            $('#assay_nameCG').show();
        }

        if($(this).val() == 'assayTypeIs'){
            $('#assay_typeCG').show();
        }



    });


});

function editLabelEvent(eventId){
    $.ajax({
        type: "POST",
        url: "{LB}/labelEvents/fetchEventEdit/" + eventId
    }).done(function(response) {



        $('#editEventModal').modal('show');

    });

}


function removeLabelEvent(eventId){

        bootbox.confirm("<h3>{MESA_LBE_DELETETITLE}</h3> <p>{MESA_LBE_DELETESURE}</p>", function(result) {
            if(result == true){
                authPopup('labelEvents', 'removeEvent', runRemove, Array(eventId) );
            }
        });

}

function runRemove(eventId){
    $.ajax({
        type: "POST",
        url: "{LB}/labelEvents/removeEvent/" + eventId
    }).done(function(response) {
        window.location.href = '{LB}/labelEvents/listing';
    });
}

//need to edit as well 


function loadEventModal(eventGroup, editId){

     //editId = (editId b !== 'undefined') ?  editId : false;
     window.eventGroup = eventGroup;
    
     if(editId === 'undefined'){
        var editId = false;
        var editing = false;
     } else{
       var editing = true;
     }
     console.log('event group' + eventGroup);
     console.log(editing);

     $.ajax({
        type: "POST",
        url: "{LB}/labelEvents/generateEventForm/" + eventGroup + "/" + editId
    }).done(function(response) {
        
        $('#addEventModalBody').html(response);

        //set initial
        if(eventGroup == '1'){
            

            hideAllEventGroup1();            
            if(editing == true){                
                $('#event').trigger('change');    
                $('#group1_condition').trigger('change');    
            }
            

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
                        return $.getJSON("{LB}/clients/predictInit/" + (element.val()), null, function(data) {
                            return callback(data);
                        //return $.getJSON("/ajax/select2_sample.php?id=" + (element.val()), null, function(data) {
                        //return callback(data);
                        });
            },
            dropdownCssClass: "bigdrop"
           });

        }

         if(eventGroup == '2'){

            hideAllEventGroup2();

            if(editing == true){                
                $('#event').trigger('change');    
                $('#group2_condition').trigger('change');    
            }

            $("#assay_name").select2({
                minimumInputLength: 2,
                placeholder: "{MESA_ASE_SELECTASSAYS}",
                multiple: true,

                ajax: {
                type: "POST",
                url: "{LB}/assays/predict/{id}",
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
                      return $.getJSON("{LB}/assays/predictInit/" + (element.val()), null, function(data) {
                      return callback(data);
                      //var data = [{id:3,text:'bug'},{id:6,text:'duplicate'}];
                      //return callback(data);
                  });
            },
            dropdownCssClass: "bigdrop"
           });
        }

        $('#addEventModal').modal('show');
    });

}

function submitEvent(){

    $.ajax({
        type: "POST",
        data: $("#addLabelEventForm").serialize(),
        url: "{LB}/labelEvents/saveEvent/"
    }).done(function(response){
        window.location.href = '{LB}/labelEvents/listing';
    });

}

function hideAllEventGroup1(){
        $('#group1_conditionCG').hide();
        $('#sample_fieldCG').hide();
        $('#sample_field_equalsCG').hide();
        $('#sample_collectionCG').hide();
        $('#client_nameCG').hide();
        $('#negative_filterCG').hide();
        $('#sample_typeCG').hide();
}

function hideAllEventGroup2(){
    $('#group2_conditionCG').hide();
    $('#assay_nameCG').hide();
    $('#assay_typeCG').hide();
    $('#negative_filterCG').hide();
}

function hideOptionsGroup1(){
    $('#sample_fieldCG').hide();
    $('#sample_field_equalsCG').hide();
    $('#sample_collectionCG').hide();
    $('#client_nameCG').hide();
    $('#sample_typeCG').hide();
}

function hideOptionsGroup2(){
    $('#assay_nameCG').hide();
    $('#assay_typeCG').hide();
}

</script>
