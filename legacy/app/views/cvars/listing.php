<div class="span8">
    <div class="well">

        <h3> Geavanceerde instellingen </h3>

            <div class="tabbable tabs-left">
            <ul class="nav nav-tabs">
                <li class="active"> <a href="#warning" data-toggle="tab" ><i class="icon-warning-sign"></i> Waarschuwing</a></li>
                <li> <a href="#cvars" data-toggle="tab" ><i class="icon-cogs"></i> Variabelen</a></li>
                <li><a href="#printers" data-toggle="tab" class="active"><i class="icon-print"></i> Printer hardware</a></li>

                <li><a href="#about" data-toggle="tab" class="active"><i class="icon-question-sign"></i> mesaLIMS version</a></li>
            </ul>
            <div class="tab-content">

                <div class="tab-pane active" id="warning">
                    <div style="text-align: center; margin-top: 10px;">
                        <p> U bevind zich in het geavanceerde instellingen systeem! Verander hier alleen instellingen als u weet waar deze voor dienen </p>
                        <p> Omgevings variabelen dienen in de meeste gevallen enkel aan het begin van een installatie te worden gewijzigd, aangezien deze
                            grote onderliggende processen kunnen wijzigen. Neem bij twijfel altijd contact op met joost@vm-solutions.nl </p>
                    </div>
                </div>



                

                <div class="tab-pane " id="cvars">
                   {cvar_table}
                </div>


                <div class="tab-pane" id="printers">
                    <span id="printHwSpan">
                        {print_hw}
                    </span>



                    <button id="addPrinter" class="btn btn-mini btn-primary" role="button" onClick="addPrinter();"><i class="icon-plus"></i> {MESA_PRN_ADDPRINTER} </button>

                </div>
                <div class="tab-pane" id="about">

                    <div style="text-align: center">
                        <h5> mesaLIMS {MESA_VERSION} <br /> <small> Copyright &amp; All rights reserved:  Joost Verhoeven </small></h5>
                        <h5> ALPACA MVC {ALPC_VERSION} <br /> <small> Copyright &amp; All rights reserved:  Joost Verhoeven </small></h5>

                        <h5> Third-party software contributions: </h5>

                        <p> jQuery </p>



                    </div>


                </div>
            </div>
            </div>

    </div>
</div>

<div class="span2">

</div>


<div id="addPrinterModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="addPrinterModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="addPrinterModalTitle">{MESA_PRN_ADDPRINTER}</h3>
    </div>
    <div class="modal-body" id="addPrinterModalBody" >
        {add_printer_form}
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_PRN_CANCEL}</button>
        <button class="btn btn-primary" id="addPrinterSubmit"  aria-hidden="true"><i class="icon-save"></i> {MESA_PRN_ADD} </button>
    </div>
</div>


<script>

function saveCvar(id){

    var cvarValue = $('#cvar_' + id).val();

    $.ajax({
        type: "POST",
        data:  { id: id, value: cvarValue },
        url: "{LB}/cvars/cvarSave"
    }).done(function(response) {
        $('#tr_' + id).highLight();
    });
}

function addPrinter(){
     $.ajax({
        type: "POST",
        url: "{LB}/printers/editPrinterForm/NULL"
    }).done(function(response) {
        $('#addPrinterModalBody').html(response);
        $('#addPrinterModal').modal('show');
    });
}

function editPrinter(selected_printer){

    $.ajax({
        type: "POST",
        url: "{LB}/printers/editPrinterForm/" + selected_printer
    }).done(function(response) {
        $('#addPrinterModalBody').html(response);
        $('#addPrinterModal').modal('show');
    });
}

function removePrinter(selected_printer){

        bootbox.confirm("<h3>{MESA_PRN_REMOVETITLE}</h3> <p>{MESA_PRN_REMOVEMSG}</p>", function(result) {
            if(result == true){
                window.location = "{LB}/printers/removePrinter/" + selected_printer;
            }
    });


}

</script>
