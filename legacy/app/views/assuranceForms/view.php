<div class="span10">
    <div class="well">

        <h3>Borgingsformulier</h3>

        <table style="margin-bottom: 10px; width: 100%">
            <tr>
                <td>Inzetdatum:
                <td><input class="input-small" type="text" id="aformDate" value="{this_date}" tabindex="-1" />
                <button class="btn btn-default" id="dateBackward" tabindex="-1"><i class="icon icon-backward"></i></button>
                <button class="btn btn-default" id="dateForward" tabindex="-1"><i class="icon icon-forward"></i></button>
                <button class="btn btn-primary" id="printPage" tabindex="-1"><i class="icon icon-print"></i></button>
                <button class="btn " id="revisionButton" tabindex="-1"><i class="icon icon-rotate-left"></i></button></td>
              </td>


                  <td style="text-align: right;">Borgingsformulieren met missende gegevens:
                    <select id="errorSelector"><option value=''> Selecteer </option> {this_errors}</select></td>
            </tr>
        </table>

        <div id="loadDiv" class="alert alert-block alert-info hide">
            <i class="icon icon-spinner icon-spin"></i> Formulier laden
        </div>

        <div id="formDiv">


        </div>


    </div>
</div>

<div id="revisionModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="revisionModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="revisionModalTitle">{MESA_SLU_REVISIONS}</h3>
    </div>
    <div class="modal-body" id="revisionModalBody" >



    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_SLU_CLOSE}</button>
    </div>
</div>

<div id="explanationModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="explanationModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="explanationModalTitle">Uitleg</h3>
    </div>
    <div class="modal-body">
        <p id="explanationOutOfDateHere" class="hide"></p>
        <textarea id="explanationText" style="width: 98%; height: 120px;"></textarea>
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">Sluiten</button>
    </div>
</div>

<script>

    var dateLoaded = false;
    var loadedFormId = false;
    var errorStack = false;
    var formLoaded = false;

    $(function(){

        $('#revisionButton').hide();

        $.ajax({
          type: "POST",
          dataType: "json",
          url: "{LB}/assuranceForms/checkEmpties"
        }).done(function(msg) {

        });

        $('#revisionButton').on('click', function(){

            if(formLoaded == true){
              $.ajax({
                  type: "POST",
                  url: "{LB}/changeTracker/changesForAssuranceForm/" +  loadedFormId
              }).done(function(msg) {
                  $('#revisionModalBody').html(msg);
                  $('#revisionModal').modal('show');
              });
            }
        });

        $("#errorSelector").on('change', function(){
          window.location.href = "{LB}/assuranceForms/view/" + $(this).val();
        });

        $('#aformDate').Zebra_DatePicker({
            format: 'd-m-Y',
            zero_pad: true,
            show_icon: false,
            offset: [10, 200],
            readonly_element: false,
            onSelect: function() {
                changeDate(this.id);
            },
           onChange: function(view, elements) {

           }
        });

        $('#aformDate').on('change', function(){

        });

        $('#dateBackward').on('click', function(){
            var currDate = $('#aformDate').val();
            $.ajax({
                type: "POST",
                data: {currDate: currDate},
                dataType: 'json',
                url: "{LB}/assuranceForms/previousDate"
            }).done(function(response) {
                $('#aformDate').val(response['date']);
                changeDate();
            });
        });

        $('#dateForward').on('click', function(){
            var currDate = $('#aformDate').val();
            $.ajax({
                type: "POST",
                data: {currDate: currDate},
                dataType: 'json',
                url: "{LB}/assuranceForms/nextDate"
            }).done(function(response) {
                $('#aformDate').val(response['date']);
                changeDate();
            });
        });


        $('#formDiv').on('change', 'input,select', function(){

            var elementId = $(this).attr('id');
            var elementValue = $(this).val();

            isExtendoDate = $(this).hasClass('formDate');

            if(isExtendoDate){
                var currentYear = new Date().getFullYear()
                if(elementValue.length < 6 && elementValue.length !== 0 ){
                    if(elementValue == 'nvt'){
                      elementValue = elementValue;
                    } else{
                      elementValue = elementValue + '-' + currentYear;
                    }
                    $(this).val(elementValue);
                }
            }

            $.ajax({
                type: "POST",
                data: { id: window.loadedFormId, elementId: elementId, elementValue: elementValue},
                dataType: 'json',
                url: "{LB}/assuranceForms/change"
            });

            updateExpiredThtDates();
        });

        $('#formDiv').on('click', '.explanationButton', function(){
            $('#explanationText').val($(this).attr('data-note') || '');
            var outOfDateHere = $(this).attr('data-out-of-date-here') || '';
            $('#explanationOutOfDateHere').text(outOfDateHere).toggleClass('hide', outOfDateHere === '');
            $('#explanationModal').data('fieldId', $(this).attr('data-field-id'));
            $('#explanationModal').modal('show');
        });

        $('#explanationModal').on('hidden', function(){
            var modal = $(this);
            var fieldId = modal.data('fieldId');
            if(!fieldId){
                return;
            }

            var explanation = $('#explanationText').val();
            $.ajax({
                type: 'POST',
                dataType: 'json',
                data: {id: window.loadedFormId, elementId: fieldId, explanation: explanation},
                url: '{LB}/assuranceForms/saveExplanation'
            }).done(function(){
                $('#formDiv .explanationButton[data-field-id="' + fieldId + '"]').attr('data-note', explanation);
                updateExpiredThtDates();
            });

            modal.removeData('fieldId');
        });

        $('#printPage').on('click', function(){
            printPage();
        });


        loadForm();

    });

    function changeDate(){
        newDate = $('#aformDate').val();
        if(newDate != dateLoaded){
            dateLoaded = newDate
            loadForm();
        }
    }


    function createForm(){

        var currDate = $('#aformDate').val();
        $('#formDiv').html('');
        $('#loadDiv').show();

        $.ajax({
            type: "POST",
            data: {currDate: currDate},
            url: "{LB}/assuranceForms/createForm"
        }).done(function(response) {
            $('#loadDiv').hide();
            loadForm();
        });
    }

    function loadForm(){
        var currDate = $('#aformDate').val();
        $('#formDiv').html('');
        $('#loadDiv').show();

        $.ajax({
            type: "POST",
            data: {currDate: currDate},
            dataType: 'json',
            url: "{LB}/assuranceForms/renderForm"
        }).done(function(response) {
            $('#loadDiv').hide();
            $('#formDiv').html(response['html']);
            loadedFormId = response['id'];

            if (typeof loadedFormId == 'undefined'){
              formLoaded = false;
              $('#revisionButton').hide();
            } else {
              formLoaded = true;
              $('#revisionButton').show();
            }

            $('.formDate').Zebra_DatePicker({
                format: 'd-m-Y',
                zero_pad: true,
                show_icon: false,
                offset: [10, 200],
                readonly_element: false,
                onSelect: function(a, b,c,d) {
                    $(d).trigger('change');
                }, onClear(a){
                    $(a).trigger('change');
                }
            });

            updateExpiredThtDates();

            $('#b0_beheer').focus();

        });
    }

    function updateExpiredThtDates(){
        var formDate = parseDateValue(document.getElementById('aformDate').value);

        document.querySelectorAll('#formDiv input[id^="b2_tht_"], #formDiv input[id^="b3_"]').forEach(function(field){
            var isNotApplicable = field.value.trim().toLowerCase() === 'nvt';
            var isExpired = !isNotApplicable && field.getAttribute('data-out-of-date-here') === '1';

            if(!isNotApplicable && field.classList.contains('materialValue')){
                isExpired = isExpired || materialValueIsOutOfSpec(field.value, field.getAttribute('data-acceptable-range'));
            } else if(!isNotApplicable){
                var date = parseDateValue(field.value);
                isExpired = isExpired || (formDate !== null && date !== null && date < formDate);
            }

            field.style.backgroundColor = isExpired ? 'red' : '';
            field.style.color = isExpired ? 'white' : '';

            var explanationButton = field.parentNode.querySelector('.explanationButton');
            if(explanationButton !== null){
                if(isExpired && (explanationButton.getAttribute('data-note') || '').trim() !== ''){
                    field.style.backgroundColor = 'orange';
                }
                explanationButton.style.display = isExpired ? '' : 'none';
            }
        });
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

    function parseDateValue(value){
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

    function printPage() {
        currDate = $('#aformDate').val();
        console.log(currDate);
        printFormWindow = window.open('{LB}/assuranceForms/printPage/' + currDate, 'printFormWindow', 'height=600,width=580, menubar=no');


    }


</script>
