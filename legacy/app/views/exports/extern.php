
<div class="span10">


    <div id="sampleLockDiv" class="alert alert-block {hideLock} ">
        <img id="usageAvatar" class='pull-left img-polaroid avatar-32' style='margin-right: 15px;' src='{locked_by_avatar}' />
        <h4>{MESA_PLU_PROJECTLOCKED_TITLE} </h4>
        {MESA_PLU_PROJECTLOCKED_DESCRIPTION} <strong><span id="usageName">{locked_by_name}</span></strong> en kan niet worden geëxporteerd.
    </div>

   <div class="row-fluid {hideExport}">
    
        <div class="span6">
            <div class="well well-small">
                <h6 style="padding-bottom: 10px;"><i class="icon icon-file-text"></i> Gegenereerd rapport 
                    <span class="pull-right">
                        <select id="exportable_selector">{selector_box}</select>
                        <a id="downloadBtn"  class="btn btn-sm btn-white"><i class="icon icon-download-alt"></i> Download</a>
                        <a href="{LB}/exports/downloadZip/{transaction_id}" class="{zip_download_show} btn btn-sm btn-white"><i class="icon icon-download-alt"></i> Alles downloaden</a>
                    </span>                
                </h6>                
                <iframe id="viewer" src="{LB}/exports/exportContentViewer/{first_report}" width="100%" height="680px"></iframe>
            </div>
        </div>

         <div class="span6">

         <div class="well well-small {block_hider}">
            <h4 style="padding-bottom: 10px;"><i class="icon icon-remove"></i> Verzenden uitgeschakeld </h4>

            <h6> Verzenden is niet mogelijk </h6>
            <p> Dit rapport kan niet verzonden worden, het project was niet geautoriseerd op het moment dat dit rapport werd gemaakt. </p>

            <h6> Oudere versies </h6> 
            <p>  Oudere, geautoriseerde, versies van dit project kunnen eventueel wel worden verstuurd of gedownload. Indien oudere versies bestaan van dit project, worden deze hieronder weergegeven. </p>
            <table class="table table-condensed" width="100%" id='exportTable'>
                    <thead>
                    <tr>                        
                        <th>Transactie</th>
                        <th>Datum</th>
                        <th>Type</th>
                        <th>Versie</th>
                        <th>Inhoud</th>                        
                        <th></th>
                    </tr>
                    </thead>

                    <tbody>
                        {other_versions}
                    </tbody>
                </table>

            <h6> Andere opties </h6>
            <p> 
                <a href="{LB}/projects/search/{project_id}" class="btn btn-primary">Open project</a>  
                <a href="{LB}/projects/viewCompleted" class="btn">Overzicht afgeronde projecten</a>
                <a href="{LB}/projects/viewAuthorised" class="btn">Overzicht geautoriseerde projecten</a>
            </p>
        </div>


        <div class="well well-small {email_hider}">

            <h6 style="padding-bottom: 10px;"><i class="icon icon-envelope-alt "></i> Email Rapportage naar klant </h6>
            <form>


                <div class="tabbable tabs-below">
                        <div class="tab-content">

                            <div class="tab-pane active" id="receiver-tab">
                                    
                                    <p><strong>Adressering</strong> <a href="{LB}/contactGroups/show/{client_id}" target="_blank" class="btn btn-mini">Bekijk groepen</a></p>
                                    {client_groups}

                                    <div class="control-group">
                                        <label class="control-label" for="extraTo">Extra geadreseerde</label>
                                        <div class="controls">
                                        <input type="text" id="extraTo" placeholder="Indien email niet in contact groep" name="extraTo">
                                        </div>
                                    </div>

                                    <div class="control-group">
                                        <label class="control-label" for="cc">CC</label>
                                        <div class="controls">
                                        <input type="text" id="cc" placeholder="CC Email" name="cc">
                                        </div>
                                    </div>                       

                                    <div class="control-group {zip_download_show}">
                                        <label class="control-label" for="exportType">Welke rapporten?</label>
                                        <div class="controls">
                                            <select id="exportType">                            
                                                <option value="all" selected="selected">Alles in deze uitvoer</option>
                                                <option value="onlySelected">Selectie</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="control-group {zip_download_show}">
                                        <label class="control-label" for="toExport">Maak een selectie</label>
                                        <div class="controls">
                                            <select id="toExport" multiple>                            
                                                {selector_box}
                                            </select>
                                        </div>
                                    </div>


                            </div>

                            <div class="tab-pane " id="email-tab">
                                <div id="normalEmailContainer" > 

                                    <div class="control-group">
                                        Email voor rapportages zonder bestanden
                                    </div>


                                    <div class="control-group">
                                        <label class="control-label" for="message">Email sjabloon </label>
                                        <div class="controls">
                                            <select class="input-block-level" id="mailTemplateSelector">
                                                {email_options}
                                            </select>
                                        </div>
                                    </div>    

                                    <div class="control-group">
                                        <label class="control-label" for="message">Taal</label>
                                        <div class="controls">
                                            <select class="input-block-level" id="mailTemplateSelectorLang">
                                                <option value="nl">Nederlands</option>
                                                <option value="en">Engels</option>
                                            </select>
                                        </div>
                                    </div>    

                                    <div class="control-group">
                                        <label class="control-label" for="message">Email (<a href="https://en.wikipedia.org/wiki/Markdown"> Markdown compatible </a>) </label>
                                        <div class="controls">
                                            <textarea rows="10" id="message" class="input-block-level" name="message" >{email}</textarea>
                                        </div>
                                    </div>
                                </div>

                                <div  id="attEmailContainer"> 
                                    <div class="control-group">
                                        Email voor rapportages met bestanden. 
                                    </div>

                                    <div class="control-group">
                                        <label class="control-label" for="message">Email sjabloon </label>
                                        <div class="controls">
                                            <select class="input-block-level" id="mailTemplateSelectorAtt">
                                                {email_options_att}
                                            </select>
                                        </div>
                                    </div>    

                                    <div class="control-group">
                                        <label class="control-label" for="message">Taal</label>
                                        <div class="controls">
                                            <select class="input-block-level" id="mailTemplateSelectorLangAtt">
                                                <option value="nl">Nederlands</option>
                                                <option value="en">Engels</option>
                                            </select>
                                        </div>
                                    </div>    

                                    <div class="control-group">
                                        <label class="control-label" for="message">Email (<a href="https://en.wikipedia.org/wiki/Markdown"> Markdown compatible </a>) </label>
                                        <div class="controls">
                                            <textarea rows="10" id="messageAtt" class="input-block-level" name="messageAtt" >{email_att}</textarea>
                                        </div>
                                    </div>
                                </div>

                                <div id="orphanedEmailContainer">
                                    <div class="control-group">
                                        Email voor losse bestanden.
                                    </div>

                                    <div class="control-group">
                                        <label class="control-label" for="messageFiles">Email sjabloon </label>
                                        <div class="controls">
                                            <select class="input-block-level" id="mailTemplateSelectorFiles">
                                                {email_options_files}
                                            </select>
                                        </div>
                                    </div>

                                    <div class="control-group">
                                        <label class="control-label" for="messageFiles">Taal</label>
                                        <div class="controls">
                                            <select class="input-block-level" id="mailTemplateSelectorLangFiles">
                                                <option value="nl">Nederlands</option>
                                                <option value="en">Engels</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="control-group">
                                        <label class="control-label" for="messageFiles">Email (<a href="https://en.wikipedia.org/wiki/Markdown"> Markdown compatible </a>) </label>
                                        <div class="controls">
                                            <textarea rows="10" id="messageFiles" class="input-block-level" name="messageFiles" >{email_files}</textarea>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <div class="tab-pane " id="debug-tab">

                                <div class="control-group ">
                                    <label class="control-label" for="bcc">BCC</label>
                                    <div class="controls">
                                    <input type="text" id="bcc" placeholder="BCC Email" name="bcc" value="{bcc}">
                                    </div>
                                </div>

                                <div class="control-group ">
                                    <label class="control-label" for="transaction">Transaction Id</label>
                                    <div class="controls">
                                        <input type="text" id="transaction" name="transaction" value="{transaction_id}" readonly>
                                    </div>
                                </div>
                            </div>


                        </div>

                        <ul class="nav nav-tabs">
                            <li class="active"><a href="#receiver-tab" data-toggle="tab" tabindex="-1" ><i class="icon-sitemap"></i> Adreseering</a></li>                        
                            <li class=""><a href="#email-tab" data-toggle="tab" tabindex="-1" ><i class="icon-file-alt"></i> Bericht </a></li>                        
                            <li class=""><a href="#debug-tab" data-toggle="tab" tabindex="-1" ><i class="icon-ellipsis-horizontal"></i> Overig</a></li>                        
                        
                        </ul>
                </div>
                
                

                <div class="{file_tool_tip}">
                                    
                <h3 style="padding-bottom: 10px; color: red;"><i class="icon icon-envelope-alt "></i> Wilt u deze bijlagen van dit project ook verzenden? </h3>                    
                
                {attachment_table}              
                 
                </div>

                <div class="{orphan_file_tool_tip}">
                                    
                <h3 style="padding-bottom: 10px; color: red;"><i class="icon icon-envelope-alt "></i> Deze bestanden ook versturen? </h3>                    

                <p> Deze bestanden zijn niet gekoppeld aan een specifiek rapport, maar zijn wel zichtbaar voor dit project. Indien u deze bestanden ook wilt verzenden, vink ze dan aan.</p>
                
                {orphaned_attachment_table}              
                 
                </div>


                </form>

                <table style="width: 100%; margin-top: 50px;">
                <tr>
                        <td style="vertical-align: top;">
                            <a href="{LB}/exports/project/{project_id}" class="btn btn-default">Terug naar rapport uitvoeren</a>
                        </td>
                        <td style="text-align: right;">
                            <button class="btn btn-primary" id="submitMail" >Verzenden - Email & portal</button>                 
                            <label for="backToOverview"><input type="checkbox" id="backToOverview" checked="checked"/> Terug naar geauthoriseerd <br />overzicht na verzenden</label>
                        </td>
                </tr>

                </table>
                

            </div>           
        </div>

    </div>
</div>




<div id="progressModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="progressModalTitle" aria-hidden="true">
    <div class="modal-header">        
        <h3 id="progressModalTitle">Rapport versturen </h3>
    </div>
    <div class="modal-body">
            
            <p> Bezig met versturen . . .  </p>
            
            <p id="errorMail"> Er is mogelijk een fout opgetreden!
                 <span id="msg"></span>
            </p>

            <p id="done"> 
                Emails succesvol afgedragen aan  portal
            </p>

    </div>    

    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">Sluiten</button>        
    </div>
</div>

<div id="fileSentHistoryModal" class="modal  hide fade" tabindex="-1" role="dialog" aria-labelledby="confirmationModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="confirmationModalTitle">Verzend geschiedenis</h3>
    </div>
    <div class="modal-body" id="fileSentHistoryBody" >

    history

    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_SLU_CLOSE}</button>
    </div>
</div>

<script>

var selected_files = []; 
var selected_orphaned_files = [];

$(function(){

    
    $(window).on('beforeunload', function() {
        navigator.sendBeacon("{LB}/keyrings/removeOwnLockById/{lock_id}");
    });

    
    $('#toExport').hide();
    $('.popper').popover();

    $('#submitMail').on('click', function(){        
        doMail();     
    });    

    $('#exportable_selector').on('change', function(){
        var navId = $(this).val();
        $('#viewer').attr('src', '{LB}/exports/exportContentViewer/' + navId );
    });

    $('#downloadBtn').on('click', function(){
        var navId = $('#exportable_selector').val();        
        window.location.href = "{LB}/exports/downloadRevision/" + navId;
    });

    $('#exportType').on('change', function(){
        if( $(this).val() == 'all'){
            $('#toExport').hide();
        }

        if( $(this).val() == 'onlySelected'){
            $('#toExport').show();
        }
    });


    $('#mailTemplateSelectorLang').on('change', function(){        
        console.log('changing language');
        loadMailTemplate($('#mailTemplateSelector').val(), $(this).val());
    });

    
    $('#mailTemplateSelector ').on('change', function(){        
        loadMailTemplate($(this).val(), $('#mailTemplateSelectorLang').val());            
    });

    $('#mailTemplateSelectorLangAtt').on('change', function(){        
        
        loadMailTemplateAtt($('#mailTemplateSelectorAtt').val(), $(this).val());
    });

    
    $('#mailTemplateSelectorAtt').on('change', function(){        
        loadMailTemplateAtt($(this).val(), $('#mailTemplateSelectorLang').val());            
    });

    $('#mailTemplateSelectorLangFiles').on('change', function(){        
        loadMailTemplateFiles($('#mailTemplateSelectorFiles').val(), $(this).val());
    });

    $('#mailTemplateSelectorFiles').on('change', function(){        
        loadMailTemplateFiles($(this).val(), $('#mailTemplateSelectorLangFiles').val());            
    });


      
    $('#fileOverViewToggleSwitch').click(function() {                

        var allOn = true;

        $('.fileCheckBox').each(function(){
            if(!$(this).is(':checked')){
                allOn = false;
            }
        });

        if(allOn){

            $('.fileCheckBox').click();

        } else {

            $('.fileCheckBox').each(function(){
                if(!$(this).is(':checked')){
                    $(this).click();
                }
            });

        }


    });

    $('#mailTemplateSelectorLang').val('{language}').change();
    $('#mailTemplateSelectorLangAtt').val('{language}').change();
    $('#mailTemplateSelectorLangFiles').val('{language}').change();

    $('#attEmailContainer').hide();
    $('#orphanedEmailContainer').hide();

});

function loadMailTemplate(template, language)
{
    $.ajax({
        type: "POST",    
        dataType: "json",
        url: "{LB}/emailTemplates/loadTemplate/" + template + "/" + language
    }).done(function(req) {

        $('#message').val(req['template']);
        $('#bcc').val(req['bcc']);
    });
}


function loadMailTemplateAtt(template, language)
{
    $.ajax({
        type: "POST",    
        dataType: "json",
        url: "{LB}/emailTemplates/loadTemplate/" + template + "/" + language
    }).done(function(req) {

        $('#messageAtt').val(req['template']);
        $('#bcc').val(req['bcc']);
    });
}

function loadMailTemplateFiles(template, language)
{
    $.ajax({
        type: "POST",    
        dataType: "json",
        url: "{LB}/emailTemplates/loadTemplate/" + template + "/" + language
    }).done(function(req) {

        $('#messageFiles').val(req['template']);
        $('#bcc').val(req['bcc']);
    });
}


function toggleFile(file, input)
{
    var orphanedFile = $(input).closest('table').attr('id') == 'orphanedSampleFileExportListing';
    var files = orphanedFile ? selected_orphaned_files : selected_files;

    if(files.indexOf(file) > -1){
        deselectFile(file, orphanedFile);
    } else {
        selectFile(file, orphanedFile);
    }
        
}


function selectFile(file, orphanedFile){    
    if(orphanedFile){
        selected_orphaned_files.push(file);
    } else {
        selected_files.push(file);
    }
    checkEmailAttachmentState(); 
}

function deselectFile(file, orphanedFile){
    var files = orphanedFile ? selected_orphaned_files : selected_files;
    files = jQuery.grep(files, function(value) {
        return value != file;
    });

    if(orphanedFile){
        selected_orphaned_files = files;
    } else {
        selected_files = files;
    }

    checkEmailAttachmentState();
}   

function checkEmailAttachmentState(){

    if(selected_orphaned_files.length > 0){
        $('#orphanedEmailContainer').show();
    } else {
        $('#orphanedEmailContainer').hide();
    }
      
    $.ajax({        
        type: "POST",
        dataType: "json",
        data: {selected_files : selected_files},
        url: "{LB}/exports/checkTransactionIsMixed/" + $('#transaction').val()
    }).done(function(msg) {

        //true/false, depending on mixd yes/no

        let selected_file_length = selected_files.length;

        if(msg == true && selected_file_length > 0){
            $('#attEmailContainer').show();
            $('#normalEmailContainer').show();
        }

        if (msg == false && selected_file_length > 0){
            $('#attEmailContainer').show();
            $('#normalEmailContainer').hide();
        }

        if(selected_file_length == 0){
            $('#attEmailContainer').hide();
            $('#normalEmailContainer').show();
        }

        

        

    });            



}


function doMail(){

             
            
    var receivers = []; 
    var extraTo = $('#extraTo').val();
    var cc = $('#cc').val();
    var bcc = $('#bcc').val();        
    var message = $('#message').val();
    var messageAtt = $('#messageAtt').val();
    var messageFiles = $('#messageFiles').val();
    var selectedReports = $('#toExport').val();
    
    if($('#exportType').val() === 'all' ){
        var exportAll = true;    
    } else{
        var exportAll = false;    
    }
    
    var selectedReport = $('#exportable_selector').val();
    var transaction = $('#transaction').val();       

    $('.cgroupbox:checked').each(function(i){
        receivers[i] = $(this).val();
    });

    if(receivers.length === 0){
        var r = confirm("Geen contact-groep geselecteerd, toch doorgaan met versturen?");
        if (r !== true) {
            return;
        }         
    }


    $('#submitMail').hide();            
    $('#done').hide();
    $('#errorMail').hide();
    $('#progressModal').modal('show');     
        
    
    $.ajax({        
        type: "POST",
        dataType: "json",
        data: {
            transaction: transaction,
            receivers: receivers,
            extraReceiver: extraTo,
            cc: cc,
            bcc: bcc,
            message: message,
            messageAtt: messageAtt,
            exportAll: exportAll,
            selectedReports: selectedReports,
            includeSampleFiles : selected_files,
            includeOrphanedSampleFiles : selected_orphaned_files,
            messageFiles: messageFiles
        },
        url: "{LB}/exports/mailer"
    }).done(function(msg) {
                
        
        if(msg['status'] == false){             
             $('#msg').html(msg['message']);
             $('#errorMail').show();
             $('#submitMail').show();
        } 

        if(msg['status'] == true){

            if(document.getElementById('backToOverview').checked)
            {
                window.location.href="{LB}/projects/viewAuthorised";
            }
            else
            {
                $('#done').show();
                $('#submitMail').show();
            }
        } 
        
    }).error(function(msg){
            $('#msg').html('Er is een fout opgetreden, probeer het nogmaals.');
            $('#errorMail').show();
            $('#submitMail').show();
    });
}

function tickAllFiles()
{

    if($('#tickAll').is(':checked')){
        $('.fileCheckbox').prop('checked', true);
    } else {
        $('.fileCheckbox').prop('checked', false);
    }

}

function openHistory(id)
{
    $.ajax({
        type: "POST",
        dataType: "json",
        data: {            
            sampleFileId: id
        },
        url: "{LB}/fileHistories/renderHistory"
    }).done(function(msg) {
        $('#fileSentHistoryBody').html(msg['html']);
        $('#fileSentHistoryModal').modal('show');
    });

    
}

</script>