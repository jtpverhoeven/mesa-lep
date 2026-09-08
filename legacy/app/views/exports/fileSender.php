<div class="span10">

    <div class="well well-small {email_hider}">

        <h6 style="padding-bottom: 10px;"><i class="icon icon-envelope-alt "></i> Verstuur monster bestanden naar klant </h6>
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


                            


                            </div>

                            <div class="tab-pane " id="email-tab">

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

                            <div class="tab-pane " id="debug-tab">

                                <div class="control-group ">
                                    <label class="control-label" for="bcc">BCC</label>
                                    <div class="controls">
                                    <input type="text" id="bcc" placeholder="BCC Email" name="bcc" value="{bcc}">
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
                    
                <div> 
                    
                <h6 style="padding-bottom: 10px;"><i class="icon icon-envelope-alt "></i> Bestanden te versturen in email </h6>
                    
                {attachment_table}   
            

                </div> 

            </form>

    
        <table style="width: 100%; margin-top: 50px;">
        <tr>
                <td style="vertical-align: top;">
                    <a href="{LB}/exports/project/{project_id}" class="btn btn-default">Terug naar rapport uitvoeren</a>
                </td>
                <td style="text-align: right;">
                    <button class="btn btn-primary" id="submitMail" >Verzenden </button>                 
                    <label for="backToOverview"><input type="checkbox" id="backToOverview" checked="checked"/> Terug naar rapport <br /> na verzenden</label>
                </td>
        </tr>

        </table>
    

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

$(function(){

    $('.popper').popover();

    $('#mailTemplateSelectorLang').on('change', function(){        
        console.log('changing language');
        loadMailTemplate($('#mailTemplateSelector').val(), $(this).val());
    });


    $('#mailTemplateSelector').on('change', function(){        
        loadMailTemplate($(this).val(), $('#mailTemplateSelectorLang').val());              
    });

    $('#submitMail').on('click', function(){        
        doMail();     
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

    //fileSentHistoryBody
    //fileSentHistoryModal
}

function toggleFile(file)
{
    if(selected_files.indexOf(file) > -1){
        deselectFile(file);
    } else {
        selectFile(file);
    }

    
    console.log(selected_files);
}


function selectFile(file){    
    selected_files.push(file);    
}

function deselectFile(file){
    selected_files = jQuery.grep(selected_files, function(value) {
        return value != file;
    });
}   


function doMail(){
    
    $('#submitMail').hide();         
    
    var receivers = []; 
    var extraTo = $('#extraTo').val();
    var cc = $('#cc').val();
    var bcc = $('#bcc').val();        
    var message = $('#message').val();    

    $('.cgroupbox:checked').each(function(i){
        receivers[i] = $(this).val();
    });

    //select files empty?
    if(selected_files.length === 0){
        alert("U dient minstens 1 bijlage te selecteren die u wilt verzenden");
        return;
    }

    if(receivers.length === 0){
        var r = confirm("Geen contact-groep geselecteerd, toch doorgaan met versturen?");
        if (r !== true) {
            return;
        }         
    }

    //$('#submitMail').hide();            
    $('#done').hide();
    $('#errorMail').hide();
    $('#progressModal').modal('show');     

    $.ajax({        
        type: "POST",
        dataType: "json",
        data: {            
            receivers: receivers,
            extraReceiver: extraTo,
            cc: cc,
            bcc: bcc,
            project: {project_id},
            message: message,            
            includedSampleFileHashes : selected_files
            
        },
        url: "{LB}/exports/documentMailer"
    }).done(function(msg) {
        
        if(document.getElementById('backToOverview').checked)
        {
                window.location = "{LB}/projects/search/{project_id}";          
        }
        
        else
        {
            alert('Verzonden');
            $('#done').show();
            $('#submitMail').show();
        }
        
    });


}
</script>