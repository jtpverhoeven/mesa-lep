<span class="{errorClassShow}">
  <p><i class="icon icon-exclamation"></i> Kan bevestiging niet uitvoeren</p>
</span>

<span class="{contentClassShow}">

<h5> Monster: {barcode}, Verdunning: {dillution} </h5>

{output}

<button id="addContender" type="button" class="btn">KVe toevoegen </button>

<h5>Ondersteunende media / materialen </h5>

<table class="table table-condensed">
  <thead>
    <th>Media / Materiaal </th>
    <th>THT / Waarde </th>
  </thead>
  <tbody>
    {support_output}
  </tbody>

</table>

<hr />
{conf_note}

<hr />
{conf_table}

</span>

<div id="confirmationExplanationModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="confirmationExplanationModalTitle" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
    <h3 id="confirmationExplanationModalTitle">Uitleg</h3>
  </div>
  <div class="modal-body">
    <p id="confirmationOutOfDateHere" class="hide"></p>
    <textarea id="confirmationExplanationText" style="width: 98%; height: 120px;"></textarea>
  </div>
  <div class="modal-footer">
    <button class="btn" data-dismiss="modal" aria-hidden="true">Sluiten</button>
  </div>
</div>

<script>



  function updateTHTdates(said, newDate, chainn, mediaId){

        $.ajax({
            type: "POST",
            url: "{LB}/assuranceForms/checkExpiryDate/" + said + '/' + newDate + '/' + mediaId
        }).done(function(msg) {
            $('#conf_' + chainn + '_tht').val(msg);
        });
    }

    function updateAssuranceForm(said, newDate, chainn, mediaId){

        if(newDate === ''){
          newDate = false;
        }

        return $.ajax({
            type: "POST",
            dataType: "json",
            url: "{LB}/assuranceForms/updateExpiryDate/" + said + '/' + chainn + '/' + newDate + '/' + mediaId
        }).then(function(msg) {
            if(msg['status'] == false){
              alert('Kan THT datum niet opslaan! Dit monster heeft nog geen inzet datum! ');
              return;
            }

            var thtField = null;
            document.querySelectorAll('input[disposition="tht"]').forEach(function(field){
              if(thtField === null && field.getAttribute('mediaid') === String(mediaId) && field.getAttribute('chainN') === String(chainn)){
                thtField = field;
              }
            });

            if(thtField !== null){
              return updateOutOfDateHere(thtField, undefined, newDate);
            }
        });
    }

      function updateExpiredConfirmationThtDates(){
        document.querySelectorAll('input[disposition="tht"]').forEach(function(thtField){
          var inzetField = findConfirmationInzetField(thtField);

          var inzetDate = inzetField === null ? null : parseConfirmationDate(inzetField.value);
          var thtDate = parseConfirmationDate(thtField.value);
          var isNotApplicable = thtField.value.trim().toLowerCase() === 'nvt';
          var isExpired = !isNotApplicable && thtField.getAttribute('data-out-of-date-here') === '1';

          console.log(thtDate);
          console.log(inzetDate);

          if(!isNotApplicable && thtField.classList.contains('materialValue')){
            isExpired = isExpired || materialValueIsOutOfSpec(thtField.value, thtField.getAttribute('data-acceptable-range'));
          } else if(!isNotApplicable){
            isExpired = isExpired || (inzetDate !== null && thtDate !== null && thtDate < inzetDate);
          }

          thtField.style.backgroundColor = isExpired ? 'red' : '';
          thtField.style.color = isExpired ? 'white' : '';

          var explanationButton = thtField.parentNode.querySelector('.assuranceExplanationButton');
          if(explanationButton !== null){
            if(isExpired && (explanationButton.getAttribute('data-note') || '').trim() !== ''){
              thtField.style.backgroundColor = 'orange';
            }
            explanationButton.style.display = isExpired ? '' : 'none';
          }
        });
      }

      function findConfirmationInzetField(thtField){
        var chainN = thtField.getAttribute('chainN');
        var mediaId = thtField.getAttribute('mediaid');
        var inzetField = null;

        document.querySelectorAll('input[disposition="inzet"]').forEach(function(field){
          if(inzetField === null && field.getAttribute('chainN') === chainN && field.getAttribute('mediaid') === mediaId){
            inzetField = field;
          }
        });

        return inzetField;
      }

      function updateOutOfDateHere(thtField, inzetValue, expiryValue){
        var inzetField = findConfirmationInzetField(thtField);
        if(inzetField === null){
          return $.when();
        }

        if(inzetValue === undefined){
          inzetValue = inzetField.value;
        }
        if(expiryValue === undefined){
          expiryValue = thtField.value;
        }

        return $.ajax({
          type: 'POST',
          dataType: 'json',
          url: '{LB}/assuranceForms/updateOutOfDateHere/' + encodeURIComponent(thtField.getAttribute('said')) + '/' + encodeURIComponent(thtField.getAttribute('mediaid')) + '/' + encodeURIComponent(inzetValue || 'false') + '/' + encodeURIComponent(expiryValue || 'false')
        }).done(function(info) {
          if(info['status'] != true){
            return;
          }

          thtField.setAttribute('data-out-of-date-here', info['outOfDateHere'] ? '1' : '0');
          var explanationButton = thtField.parentNode.querySelector('.assuranceExplanationButton');
          if(explanationButton !== null){
            explanationButton.setAttribute('data-form-id', info['formId'] || '');
            explanationButton.setAttribute('data-note', info['explanation'] || '');
            explanationButton.setAttribute('data-out-of-date-here', info['sampleText'] || '');
          }
          updateExpiredConfirmationThtDates();
        });
      }

      function parseConfirmationDate(value){
        var parts = value.trim().split('-');

        if(parts.length !== 3 || !parts.every(function(part){ return /^\d+$/.test(part); })){
          return null;
        }

        var date = new Date(Number(parts[2]), Number(parts[1]) - 1, Number(parts[0]));
        date.setHours(0, 0, 0, 0);

        if(date.getFullYear() !== Number(parts[2]) ||
           date.getMonth() !== Number(parts[1]) - 1 ||
           date.getDate() !== Number(parts[0])){
          return null;
        }

        return date;
      }

      function materialValueIsOutOfSpec(value, range){
        value = value.trim();
        range = (range || '').trim();

        if(value === '' || range === ''){
          return false;
        }

        if(value.toLowerCase() === 'nvt'){
          return false;
        }

        var numericValue = Number(value.replace(',', '.'));
        if(!isFinite(numericValue)){
          return true;
        }

        var comparisonPattern = /(<=|>=|<|>|=)\s*(-?\d+(?:[.,]\d+)?)/g;
        if(range.replace(comparisonPattern, '').trim() !== ''){
          return false;
        }

        comparisonPattern.lastIndex = 0;
        var match;
        var foundComparison = false;
        var acceptable = true;

        while((match = comparisonPattern.exec(range)) !== null){
          foundComparison = true;
          var bound = Number(match[2].replace(',', '.'));

          if(match[1] === '<' && !(numericValue < bound)){
            acceptable = false;
          }
          if(match[1] === '<=' && !(numericValue <= bound)){
            acceptable = false;
          }
          if(match[1] === '>' && !(numericValue > bound)){
            acceptable = false;
          }
          if(match[1] === '>=' && !(numericValue >= bound)){
            acceptable = false;
          }
          if(match[1] === '=' && numericValue !== bound){
            acceptable = false;
          }
        }

        return foundComparison && !acceptable;
      }

    $(function() {

      $('#mainConfWindow').off('.confirmationViewer');
      $('#confirmationExplanationModal').off('.confirmationViewer');

        $('#addContender').on('click', function(){
          
          var said = '{said}'
          var globalConf = '{globalConf}'
          var df =  '{df}';
          var rep = '{rep}';
          
          addContender(said, df, rep, globalConf);          
        });

        $('.activatorCheckbox').on('click', function(){
            var active = $(this).prop('checked');
            var mediaId = $(this).attr('mediaId');
            var said = $(this).attr('said');
            var globalConf = $(this).attr('globalConf');
            var dF = $(this).attr('df');
            var rep = $(this).attr('rep');

            $.ajax({
                type: "POST",
                data: { said: said, dF:dF, rep:rep, globalConf:globalConf,  active: active, mediaId: mediaId},
                url: "{LB}/confirmations/saveActivator"
            }).done(function(msg) {
               confirmation(said, dF, rep, globalConf)
            });

          });

        $('.confNote').on('change', function(){

          var id = $(this).attr('confId');
          var note = $(this).val();

          $.ajax({
              type: "POST",
              data: {
                  id: id,
                  note: note,
              },

              url: "{LB}/confirmations/saveNote"
          }).done(function(msg) {

          });
        });

        $('#mainConfWindow').on('click.confirmationViewer', '.assuranceExplanationButton', function(){
          $('#confirmationExplanationText').val($(this).attr('data-note') || '');
          var outOfDateHere = $(this).attr('data-out-of-date-here') || '';
          $('#confirmationOutOfDateHere').text(outOfDateHere).toggleClass('hide', outOfDateHere === '');
          $('#confirmationExplanationModal').data('formId', $(this).attr('data-form-id'));
          $('#confirmationExplanationModal').data('fieldId', $(this).attr('data-field-id'));
          $('#confirmationExplanationModal').modal('show');
        });

        $('#confirmationExplanationModal').on('hidden.confirmationViewer', function(){
          var modal = $(this);
          var formId = modal.data('formId');
          var fieldId = modal.data('fieldId');
          if(!formId || !fieldId){
            return;
          }

          var explanation = $('#confirmationExplanationText').val();
          $.ajax({
            type: 'POST',
            dataType: 'json',
            data: {id: formId, elementId: fieldId, explanation: explanation},
            url: '{LB}/assuranceForms/saveExplanation'
          }).done(function(){
            $('#mainConfWindow .assuranceExplanationButton[data-form-id="' + formId + '"][data-field-id="' + fieldId + '"]').attr('data-note', explanation);
            updateExpiredConfirmationThtDates();
          });

          modal.removeData('formId');
          modal.removeData('fieldId');
        });



        //$("#{name}_{db_id}").keypress( function(e) {

        $('#mainConfWindow').on('keypress.confirmationViewer', 'input', function (e) {

          var thisDisposition = $(this).attr('disposition');
          var thisFieldName = $(this).attr('fieldname');

          if(thisDisposition == 'poscontrol' || thisDisposition == 'negcontrol' || thisFieldName == 'contender' ){

            var charCode = (e.which) ? e.which : e.keyCode;
            if(charCode == 13 || charCode == 9){
                 return true;
            }

            return "+-".indexOf(String.fromCharCode(e.which)) >= 0;

          }

          if(thisDisposition == 'blankcontrol'){

            var charCode = (e.which) ? e.which : e.keyCode;
            if(charCode == 13 || charCode == 9){
                 return true;
             }

            return "0".indexOf(String.fromCharCode(e.which)) >= 0;
          }

        });



        $('#mainConfWindow').on('change.confirmationViewer', 'input,select', function () {
          updateExpiredConfirmationThtDates();
        });

        updateExpiredConfirmationThtDates();
    });


</script>
