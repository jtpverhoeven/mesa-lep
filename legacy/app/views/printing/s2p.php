
<div class="span10">
    {formHeader}
    <div class="row-fluid">

        <div class="span3">
            <div class="well well-small">
                <h6><i class="icon-search"></i> {MESA_S2P_SCAN} </h6>
                {barcode}
                <div style="text-align: right;">
                    <span id="nowPrintIcon"><i class="icon-spinner icon-spin"></i> <em> {MESA_S2P_NOWPRINTING} </em></span>
                    <button id="printButton" class="btn btn-primary btn-mini" type="button"><i class="icon-print"></i> {MESA_S2P_PRINT} </button>
                </div>
            </div>


            <div class="well well-small">
                <h6><i class="icon-time"></i> {MESA_S2P_DATERANGE} </h6>

                {range_type_select}
                {range_start}
                {range_stop}


                <div style="text-align: right;">
                    <span id="nowPrintRangeIcon"><i class="icon-spinner icon-spin"></i> <em> {MESA_S2P_NOWPRINTING} </em></span>
                    <button id="printRangeButton" class="btn btn-primary btn-mini" type="button"><i class="icon-print"></i> {MESA_S2P_PRINT} </button>
                </div>
            </div>

            <div class="well well-small">
                <h6><i class="icon-beaker"></i> Print monster bereik </h6>

                {bar_range_start}
                {bar_range_stop}

                <label class="checkbox">
                    <input type="checkbox" name="print_normal_samples" value="1" checked="checked">
                    Normale analyse &amp; legionella monsters
                </label>


                <label class="checkbox">
                    <input type="checkbox" name="print_rodac_samples" value="1">
                    RODAC monsters
                </label>

               
                <div style="text-align: right;">
                    <span id="nowPrintSampleRangeIcon"><i class="icon-spinner icon-spin"></i> <em> {MESA_S2P_NOWPRINTING} </em></span>
                    <button id="printSampleRangeButton" class="btn btn-primary btn-mini" type="button"><i class="icon-print"></i> {MESA_S2P_PRINT} </button>
                </div>
            </div>

            <div class="well well-small">
                <h6><i class="icon-calendar"></i> Datum instellen </h6>
                Print stikkers voor inzetdag: 
                <input class="input-small" type="text" id="aformDate" value="{today}" name="date_offset" tabindex="-1" />

            </div>
        </div>


        <div class="span3">


             <div class="well well-small">
                <h6><i class="icon-cogs"></i> {MESA_S2P_PRINTOPTIONS} </h6>
                <label class="checkbox hide">
                    <input type="checkbox" name="print_analysis" value="1">
                        {MESA_S2P_PRINTANALYSIS}
                </label>

                {printProfiles}

                <label class="checkbox">
                    <input type="checkbox" id="duplicate_label" name="duplicate_label" value="1">
                     Dupliceer label
                </label><br />

                <label class="checkbox">
                   <input type="checkbox" name="also_print" value="1">
                   {MESA_S2P_PRINTALSO}  <input type="text" name="also_print_amount" class="input-mini" style="width: 10px;" value="1"> {MESA_S2P_PRINTALSO2}
                   <select name="also_print_selection" class="input-small">{label_values}</select>
                </label><br />


                <label class="checkbox">
                    <input type="checkbox" id="print_sample" name="print_sample" value="1">
                    {MESA_S2P_PRINT}  <input type="text" name="print_sample_amount" class="input-mini" value="1"> {MESA_S2P_SAMPLELABELS}
                </label><br />






                 <center>
                    <button id="show_advanced" class="btn btn-default btn-mini" style="margin-top: 5px;" type="button"><i class="icon-caret-down"></i> {MESA_S2P_SHOWMOREOPTIONS} </button>
                </center>
            </div>

              <div id="extendedOptions" class="well well-small hide">
                <h6><i class="icon-tag"></i> {MESA_S2P_OTHER} </h6>
                    {assay_type_select}
                    {sample_label_select}
                    {assay_label_select}
              </div>



        </div>

        <div class="span3">
             <div class="well well-small">
                <h6><i class="icon-print"></i> {MESA_S2P_PRINTOPTIONS} </h6>
                {printer}
             </div>

        </div>


        <div class="span3">
            

            <div class="well well-small">
                <h6><i class="icon-print"></i> Legionella stickers </h6>
                
                <div class="control-group" id="legbar_range_matrixCG">
                    <div class="controls">
                        <div class="input-prepend input-append input-block-level">
                            <span class="add-on">Matrix</span>
                            <select id="legbar_range_matrix" name="legbar_range_matrix"class="input-block-level">
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                            </select>
                        </div>
                    </div>
                </div>
                               
                <div class="control-group" id="legbar_range_startCG">
                    <div class="controls">
                        <div class="input-prepend input-append input-block-level">
                            <span class="add-on">Van</span>
                            <input id="legbar_range_start" name="legbar_range_start" type="text" class="input-block-level" placeholder="" value="">
                        </div>
                    </div>
                </div>

                <div class="control-group" id="legbar_range_endCG">
                    <div class="controls">
                        <div class="input-prepend input-append input-block-level">
                            <span class="add-on">Tot</span>
                            <input id="legar_range_end" name="legar_range_end" type="text" class="input-block-level" placeholder="" value="">
                        </div>
                    </div>
                </div>

                {leg_printer}

                <div style="text-align: right;">
                    <button id="downloadLeg" class="btn btn-primary btn-mini" type="button"><i class="icon-download"></i> Printen </button>
                    <span id="nowPrintLegIcon"><i class="icon-spinner icon-spin"></i> <em> {MESA_S2P_NOWPRINTING} </em></span>
                </div>

            </div>

        </div>

    </div>
    </form>
</div>


<script>

var extendedOptionsOpen = false;
var nowPrinting = false;

$(function(){

    

   $('#nowPrintIcon').hide();
   $('#nowPrintRangeIcon').hide();
   $('#nowPrintLegIcon').hide();
   $('#nowPrintSampleRangeIcon').hide();

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

   $('input,select').keypress(function(event) { return event.keyCode != 13; });

   $('#show_advanced').on('click', function(){

        if(extendedOptionsOpen == false){
           $('#extendedOptions').show();
           extendedOptionsOpen = true;
        } else{
           $('#extendedOptions').hide();
            extendedOptionsOpen = false;
        }
   });

    $('#downloadLeg').on('click', function(){
        var legFrom = $('#legbar_range_start').val();
        var legTo =  $('#legar_range_end').val();
        var legMatrix = $('#legbar_range_matrix').val();
        var legPrinter =   $('#leg_printer').val();        

        if (legFrom == '' || legTo == ''){
            alert('Voer een begin en eind barcode in')
            return;
        }
        
        else{

            if(legFrom.length > 6 || legTo.length > 6){
                alert('Geen geldige invoer: gebruik hier enkel volgnummers om labels te printen. Bijv: 1000 - 1010');
                return;
            }

            $('#nowPrintLegIcon').show();
            $('#downloadLeg').hide();

            $.ajax({
                type: "POST",                
                data: { 'legFrom' : legFrom, 'legTo' : legTo, 'legMatrix' : legMatrix, 'legPrinter' : legPrinter},
                url: "{LB}/printing/printLegLabels"
            }).done(function(msg) {
                $('#nowPrintLegIcon').hide();
                $('#downloadLeg').show();
                nowPrinting = false;
            });
            
        }

    });

   $('#printButton').on('click', function(){
       doPrint();
   });

    $('#printRangeButton').on('click', function(){
        doPrintRange();
    });

    $('#printSampleRangeButton').on('click', function(){
        doPrintSampleRange();
    });

   $('#barcode').bind('keydown', 'return', function(){
       doPrint();
   });

    $('#range_start,#range_stop').Zebra_DatePicker({
        format: 'd-m-Y',
        zero_pad: true,
        show_icon: false,
        offset: [10, 200],
        readonly_element: false
    });

    $('#duplicate_label').on('click', function(){
        //disable options
        dupliation_enabled = $(this).prop('checked');

        if(dupliation_enabled == true){
          $('.pp_check').prop('checked', false);
          $('.pp_check').prop('disabled', true);
          $('#print_sample').prop('disabled', true);

          $('#printRangeButton').prop('disabled', true);
          $('#printSampleRangeButton').prop('disabled', true);

        } else{
          $('.pp_check').prop('disabled', false);
          $('#print_sample').prop('disabled', false);
          $('#printRangeButton').prop('disabled', false);
          $('#printSampleRangeButton').prop('disabled', false);
        }
    });


    $('#bar_range_start').on("keypress", function(e){
        if(e.keyCode == 13) {
            $('#bar_range_stop').focus().select();
        }
    });

    $('#bar_range_stop').on("keypress", function(e){
        if(e.keyCode == 13) {
          doPrintSampleRange();
          $('#bar_range_start').focus().select();
        }
    });

});

function doPrintRange(){

    if(nowPrinting == true){
        return;
    }

    nowPrinting = true;

    $('#nowPrintRangeIcon').show();
    $('#printRangeButton').hide();

    formData = $('#scanForm').serialize();
    formData = formData + '&range_print=1';

    $.ajax({
        type: "POST",
        data: $('#scanForm').serialize(),
        url: "{LB}/printing/runRangeS2P"
    }).done(function(msg) {


        $('#nowPrintRangeIcon').hide();
        $('#printRangeButton').show();
        nowPrinting = false;
    });

}

function doPrintSampleRange(){


    var startBarCheck = $('#bar_range_start').val();
    var endBarCheck = $('#bar_range_stop').val();

    if(startBarCheck == '' || endBarCheck == '')
    {
        alert('Print monster bereik niet mogelijk: een van de barcodes is niet ingevoerd');
        return;
    }
    

    if(nowPrinting == true){
        return;
    }

    nowPrinting = true;

    $('#nowPrintSampleRangeIcon').show();
    $('#printSampleRangeButton').hide();

    formData = $('#scanForm').serialize();
    formData = formData + '&sample_range_print=1';

    $.ajax({
        type: "POST",
        data: $('#scanForm').serialize(),
        url: "{LB}/printing/runSampleRangeS2P"
    }).done(function(msg) {
        $('#nowPrintSampleRangeIcon').hide();
        $('#printSampleRangeButton').show();
        nowPrinting = false;
    });

}

function doPrint(){

    if(nowPrinting == true){
        return;
    }

    nowPrinting = true;
    $('#nowPrintIcon').show();
    $('#printButton').hide();

    $.ajax({
        type: "POST",
        data: $('#scanForm').serialize(),
        url: "{LB}/printing/runS2P"
    }).done(function(msg) {

        if(msg == 'BAR_WRONG'){
            $('#barcodeCG').addClass('error');
        } else{
            $('#barcodeCG').removeClass('error');
            $('#barcode').val('');
            $('#barcode').focus();
        }
        $('#nowPrintIcon').hide();
        $('#printButton').show();
        nowPrinting = false;


    });

}

</script>
