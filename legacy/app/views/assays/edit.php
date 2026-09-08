<div class="span8">
    <div class="well">
        <h3> {MESA_ASE_EDITTITLE}
            <span  class="pull-right">
                <select id="revisionSelector">{revision_dropdown}</select>
            </span>
        </h3>

        <div id="assayFormDiv">
            {edit_form}
        </div>

     


        <div class="pull-left">
            <!--   <button id="mesaScriptEditor" type="button" class="btn btn-small btn-primary"><i class="icon-rocket"></i> {MESA_ASE_OPENSCRIPT} </button> -->
        </div>

        <div class="pull-right">
            <!-- <button id="saveAssayChangesButton" type="button" class="btn btn-small btn-danger"><i class="icon-warning-sign"></i> {MESA_ASE_FORCECHANGES} </button> -->
            <button id="reviseAssayChangesButton" type="button" class="btn btn-small btn-success"><i class="icon-save"></i> {MESA_ASE_REVISE} </button>
        </div>
        <br />
    </div>
</div>


<div class="span2">
    <ul class="nav nav-list well">
        <li><a href="{LB}/assays/listing"><i class="icon-backward"></i> {MESA_ASE_BACKTOLIST}</a> </li>
    </ul>
</div>

<script>

revision_istip = '{revision_istip}';

$(function(){

    $("#media_id").select2({
        minimumInputLength: 1,
        placeholder: "Selecteer media",
        multiple: true,


          ajax: {
          type: "POST",
          url: "{LB}/media/predict",
          dataType: 'json',
          quietMillis: 350,
          data: function (term, page) {
              return {
                  term: term, //search term
                  page_limit: 20 // page size
              };
          },
          results: function (data, page) {
              return { results: data.results };
          }

          },
          initSelection: function(element, callback) {
                  return $.getJSON("{LB}/media/predictInit/" + (element.val()), null, function(data) {
                  return callback(data);
                  });
      },
      dropdownCssClass: "bigdrop"
     })


    $("#meta_assays").select2({
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
            });
      },
      dropdownCssClass: "bigdrop"
     });

    if('{assay_is_meta}' == 'true'){

        //hide things we do not need here
        $('#type_baseCG').hide();
        //$('#type').prop("readonly", true);
        $('#type').find('option:not(:selected)').prop('disabled', true);
        $('#type_baseCG').hide();
        $('#min_countCG').hide();
        $('#max_countCG').hide();
        $('#dillutionCG').hide();
        $('#replicatesCG').hide();
        //$('#confirmationCG').hide();
        //$('#confirmation_scriptCG').hide();
    } else{
        $('#meta_assaysCG').hide();
        $('#hide_inputsCG').hide();

    }


    $('#forceChanges').val('0');

    if(revision_istip == '0'){
        $('#forceChanges').val('1');
        $('#reviseAssayChangesButton').hide();
    }

    $('#mesaScriptEditor').on('click', function(){
        popUp('{LB}/scriptEditor/editor/{id}', 'scriptEdit', 1100, 650);
    });

    $('#saveAssayChangesButton').on('click', function(){
       $('#forceChanges').val('1');
       $('#reviseAssayChangesButton').trigger('click');
    });

    $('#revisionSelector').on('change', function(){
       selectedRevision = $(this).val();
       window.location.href = '{LB}/assays/edit/' + selectedRevision;
    });

    $('#uses_indicator').on('change', function(){


        if($(this).val() == '0')
        {
            alert('Let op! Met deze instelling zal voor deze analyse geen bolletjes worden weergegeven op rapportages!');
        }

    });


    

    $('#startAnchor').on('change', function(){

       if($(this).val() == 'p'){
            $('#startFieldNameCG').show();
            loadProjectFields();
       }

       if($(this).val() == 's'){
           $('#startFieldNameCG').show();
           loadSampleFields();
       }

       if($(this).val() == 'r'){
         $('#startFieldNameCG').hide();
       }

        if($(this).val() == 'i'){
            $('#startFieldNameCG').hide();
        }

    });

    $('#confirmation').on('change', function(){

        if($(this).val() == '1'){
             $('#confirmation_typeCG').show();
         }
          if($(this).val() == '0'){
            $('#confirmation_typeCG').hide();
         }
     });

    if($('#startAnchor').val() == 'r'){
        $('#startFieldNameCG').hide();
    }

    if($('#startAnchor').val() == 'i'){
        $('#startFieldNameCG').hide();
    }

    if($('#confirmation').val() == '0'){
        $('#confirmation_typeCG').hide();
    }



    $('#assayFormDiv').on('change', '#widgetSelector', function(){
        if($(this).val() != 'NULL'){

            thisValue = $(this).val();
            var chainLength = $('#confirmationTable tr').length;

            $.ajax({
                type: "POST",
                url: "{LB}/media/addWidgetLine/" + thisValue  + '/' + chainLength
            }).done(function(widgetLine) {
                if(widgetLine != '0'){
                    $('#confirmationTable').append(widgetLine);
                    updateConfText();
                }
            });
            $(this).val('NULL');
        }
    });



      $('#assayFormDiv').on('change', '#supportWidgetSelector', function(){
          if($(this).val() != 'NULL'){

              thisValue = $(this).val();
              var chainLength = $('#confirmationSupportTable tr').length;

              $.ajax({
                  type: "POST",
                  url: "{LB}/media/addWidgetLine/" + thisValue  + '/' + chainLength + '/false/false/true'
              }).done(function(widgetLine) {
                  if(widgetLine != '0'){
                      $('#confirmationSupportTable').append(widgetLine);
                      updateConfSupportText();
                  }
              });
              $(this).val('NULL');
          }
      });

      $('#assayFormDiv').on('change', '#matrixSelector', function(){
          if($(this).val() != 'NULL'){

              thisValue = $(this).val();
              var chainLength = $('#matrixContentTable tr').length;

              $.ajax({
                  type: "POST",
                  url: "{LB}/matrixContent/returnWidgetLine/" + thisValue  + '/' + chainLength
              }).done(function(widgetLine) {
                  if(widgetLine != '0'){
                      $('#matrixContentTable').append(widgetLine);
                      updateMatrixText();
                  }
              });
              $(this).val('NULL');
          }
      });


    $('#assayFormDiv').on('change', '.dispositionDropper', function(){
        updateConfText();
    });

    loadConfirmationWidget();
});

function loadConfirmationWidget(){
    $.ajax({
        type: "POST",
        url: "{LB}/media/buildGUIWidget/{id}"
    }).done(function(widget) {
            $('#scriptCG').before(widget);
            updateConfText();
            //load up the next widget
            loadConfirmationSupportWidget();
    });
}

function loadConfirmationSupportWidget(){
    $.ajax({
        type: "POST",
        url: "{LB}/media/buildGUIWidget/{id}/True"
    }).done(function(widget) {
            $('#confirmation_widgetCG').after(widget);
            updateConfSupportText();
            loadMatrixWidget();
    });
}

function loadMatrixWidget(){
    $.ajax({
        type: "POST",
        url: "{LB}/matrixContent/buildGUIWidget/{original_id}"
    }).done(function(widget) {
            $('#confirmationSupport_widgetCG').after(widget);
            updateMatrixText();
    });
}


function receiveEditor(result) {
    $('#confirmation_script').val(result['confScript']);
    $('#script').val(result['resultScript']);
}

function loadSampleFields(){
    $.ajax({
        type: "POST",
        url: "{LB}/sampleFields/dateDropDownMenu"
    }).done(function(fields) {

        if(fields == ''){
            alert('No sample date fields available, reverting to sample registration date');
            $('#startAnchor option[value=r]').attr('selected', 'selected');
            $('#startFieldNameCG').hide();
        } else{
            $("#startFieldName").empty().append(fields);
        }
    });
}

function loadProjectFields(){
     $.ajax({
            type: "POST",
            url: "{LB}/projectFields/dateDropDownMenu"
        }).done(function(fields) {

            if(fields == ''){
                alert('No sample date fields available, reverting to sample registration date');
                $('#startAnchor option[value=r]').attr('selected', 'selected');
                $('#startFieldNameCG').hide();
            } else{
                $("#startFieldName").empty().append(fields);
            }
        });
}

function removeConfRow(id){
    $('#' +id).remove();
    updateConfText();
}

function removeConfSupportRow(id){
    $('#support_' +id).remove();
    updateConfSupportText();
}

function removeMatrix(id){
  $('#matrix_' +id).remove();
  updateMatrixText();
}

function updateConfText(){

    var chainId = 1;
    var arrayId = 0;
    var confirmationObject = []
    $('#confirmationTable tr').each(function() {
        $(this).find('td:eq(0)').html(chainId);

        thisObj = {}
        thisObj.chainId = chainId;
        thisObj.mediaId =  $(this).find('td:eq(0)').attr('mediaId');
        thisObj.disposition =  $(this).find('select').first().val();
        confirmationObject[arrayId] = thisObj;
        chainId = chainId + 1;
        arrayId = arrayId + 1;
    });

    var confObjectText = JSON.stringify(confirmationObject);
    $('#confirmation_script').val(confObjectText);

}

function updateConfSupportText(){

    var chainId = 1;
    var arrayId = 0;
    var confirmationObject = []

    $('#confirmationSupportTable tr').each(function() {
        $(this).find('td:eq(0)').html(chainId);

        thisObj = {}
        thisObj.chainId = chainId;
        thisObj.mediaId =  $(this).find('td:eq(0)').attr('mediaId');
        //thisObj.disposition =  $(this).find('select').first().val();
        confirmationObject[arrayId] = thisObj;
        chainId = chainId + 1;
        arrayId = arrayId + 1;
    });

    var confObjectText = JSON.stringify(confirmationObject);
    $('#confirmation_support').val(confObjectText);

}

function updateMatrixText(){

  var chainId = 1;
  var arrayId = 0;
  var confirmationObject = []

  $('#matrixContentTable tr').each(function() {
      console.log('chain');
      $(this).find('td:eq(0)').html(chainId);

      thisObj = {}
      thisObj.chainId = chainId;
      thisObj.matrixId =  $(this).find('td:eq(0)').attr('matrixId');
      //thisObj.disposition =  $(this).find('select').first().val();
      confirmationObject[arrayId] = thisObj;
      chainId = chainId + 1;
      arrayId = arrayId + 1;
  });

  var confObjectText = JSON.stringify(confirmationObject);
  $('#matrix').val(confObjectText);

}

</script>
