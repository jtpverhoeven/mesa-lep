
<div class="span10">

    <div id="sampleLockDiv" class="alert alert-block {hideLock} ">
        <img id="usageAvatar" class='pull-left img-polaroid avatar-32' style='margin-right: 15px;' src='{locked_by_avatar}' />
        <h4>{MESA_PLU_PROJECTLOCKED_TITLE} </h4>
        {MESA_PLU_PROJECTLOCKED_DESCRIPTION} <strong><span id="usageName">{locked_by_name}</span></strong> en kan niet worden geëxporteerd.
    </div>

<div class="row-fluid {hideExport}" >

<form id="exportSubmit" action="{LB}/exports/doExport" method="post">
<div class="span5">
    <div class="well well-small">
        <h6><i class="icon-suitcase"></i> {MESA_EXP_TITLE} </h6>

        <table class="table-condensed" style="width: 100%">
            <tbody>
            <tr>
                <td width="125px"><strong>{MESA_EXP_PNAME}</strong></td>
                <td><a href="{LB}/projects/search/{project_id}">{project_name}</a></td>
            </tr>
            <tr>
                <td width="125px"><strong>{MESA_EXP_PCLIENT}</strong></td>
                <td><a href="{LB}/clients/show/{client_id}">{client}</a></td>
            </tr>

            <tr>
                <td style="border-bottom: 1px solid #efefef;" colspan="2"></td>
            </tr>

            <tr>
                <td width="125px" style="vertical-align: top;"><strong>Portal Rapportage instellingen klant</strong></td>
                <td>
                    <p class="{show_no_pref_found}"> Niet gevonden </p>
                    
                    <div class="alert {show_rodac_alert}">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <svg width="24" height="24" viewBox="0 0 24 24" style="flex-shrink: 0;">
                                <circle cx="12" cy="12" r="10" fill="#f89406">
                                    <animate attributeName="opacity" values="1;0.3;1" dur="1.5s" repeatCount="indefinite"/>
                                </circle>
                                <circle cx="12" cy="12" r="6" fill="#fff"/>
                            </svg>
                            <span>Dit is een RODAC rapport, controleer handmatig rapport instellingen.</span>
                        </div>
                    </div>

                    <div class="alert {show_no_preference_alert}">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <svg width="24" height="24" viewBox="0 0 24 24" style="flex-shrink: 0;">
                                <circle cx="12" cy="12" r="10" fill="#f89406">
                                    <animate attributeName="opacity" values="1;0.3;1" dur="1.5s" repeatCount="indefinite"/>
                                </circle>
                                <circle cx="12" cy="12" r="6" fill="#fff"/>
                            </svg>
                            <span>Klantvoorkeuren voor rapportage niet beschikbaar. Stel zelf de jusite rapporatage instellingen in.</span>
                        </div>
                    </div>

                    
                    <div class="alert alert-success {show_preferences_loaded}" id="userPrefWereLoaded">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <svg width="24" height="24" viewBox="0 0 24 24" style="flex-shrink: 0;">
                                <circle cx="12" cy="12" r="10" fill="#468847" fill-opacity="0.1" stroke="#468847" stroke-width="2"/>
                                <path d="M7 12 L10.5 15.5 L17 9" fill="none" stroke="#468847" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" stroke-dasharray="20" stroke-dashoffset="20">
                                    <animate attributeName="stroke-dashoffset" from="20" to="0" dur="0.6s" fill="freeze"/>
                                </path>
                            </svg>
                            <span>Klantvoorkeuren succesvol geladen en toegepast.</span>
                        </div>
                    </div>

                                        
                    <div class="alert alert-danger hidden"  id="userPrefWereChanged">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <svg width="24" height="24" viewBox="0 0 24 24" style="flex-shrink: 0;">
                                <circle cx="12" cy="12" r="10" fill="#d9534f" fill-opacity="0.1" stroke="#d9534f" stroke-width="2"/>
                                <path d="M8 8 L16 16 M16 8 L8 16" fill="none" stroke="#d9534f" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" stroke-dasharray="20" stroke-dashoffset="20">
                                    <animate attributeName="stroke-dashoffset" from="20" to="0" dur="0.6s" fill="freeze"/>
                                </path>
                            </svg>
                            <span>Klantvoorkeuren ingeladen, maar aangepast door gebruiker!</span>
                        </div>
                    </div>

                    <div class="{show_report_pref_client}">
                        <p> Type rapport:  {human_readable_type} </p>
                        <p> Taal:  {human_readable_language} </p>
                        <p> Overschrijdingen markeren:  {human_readable_violation} </p>
                    </div>
                </td>
            </tr>


            </tbody>
        </table>

    </div>

    <div class="well well-small">
        <h6><i class="icon-calendar"></i> Eerdere rapportages van dit project </h6>

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

            
    </div>

     <div class="well well-small hide">
        <h6><i class="icon-external-link"></i> {MESA_EXP_EXPORTAS} </h6>

        <div id="exportType" class="list-group">

                <div class="controls">
                    <div class="input-prepend input-append input-block-level">
                        <span class="add-on">{MESA_EXP_EXPFILEFORMAT}</span>
                        <select id="sampling_method" name="sampling_method" class="input-block-level">
                            <option value="pdf">PDF</option>
                        </select>
                    </div>
                </div>
        </div>
    </div>


     <div id="settingsWell" class="well well-small">
        <h6><i class="icon-beaker"></i> {MESA_EXP_EXPORTSETTINGS} </h6>

        <div class="settingDiv" id="PDFSettings">
            
            {pdfTypeSelect}
            {reportType}
            {noSamples}
            {exportSample}


        </div>

    </div>

</div>

<div class="span4">


    <div id="parametersWell" class="well well-small">
        <h6><i class="icon-list"></i> Parameters op rapport </h6>

        <div class="tabbable tabs-below">
            <div class="tab-content">

              <div class="tab-pane " id="parametersDivTab">
                <div class="settingDiv" id="parametersDiv">
                        {paramList}
                </div>
              </div>

              <div class="tab-pane active" id="parametersOrderTab">
                  {param_sorter}
              </div>

            </div>
            <ul class="nav nav-tabs">
                <li class=""><a href="#parametersDivTab" data-toggle="tab" tabindex="-1" ><i class="icon-list"></i> Parameters </a></li>
                <li class="active"><a href="#parametersOrderTab" data-toggle="tab" tabindex="-1" ><i class="icon-sort"></i> Volgorde </a></li>
              </ul>
          </div>
    </div>

    <div class="well well-small" id="sampleNoteWell">
      <h6><i class="icon-pencil"></i> Monster notities</h6>
      {sample_notes}
    </div>

</div>
<div class="span3">

    <div  class="well well-small">

        <h6><i class="icon-list-alt"></i> Overige instellingen </h6>

        {showViolation}

        {languageSelect}

        <div class="control-group" id="templateSelectCG">
            <label class="control-label" for="templateSelect">Rapport stijl</label>
            <div class="controls">

                <input type="hidden" id="preferenceLanguage" value="{preference_language}" />
                {templates_available}
                <!-- <select size="10" id="templateSelect" name="templateSelect" class="input-block-level"> -->
                
                </select>
            </div>
        </div>

    </div>

    <div class="well well-small">
        
        <h6><i class="icon-print"></i> Uitvoeren </h6>

        <div class="alert {show_report_notes}"> 
            {report_notes_for_client}   
        </div>

        <button id="saveReport" type="button" class="btn btn-primary btn-block"><i class="icon icon-print"></i> Rapport maken</button> <br />
        <button id="previewReport"  type="button" class="btn btn-default btn-block"><i class="icon icon-globe"></i> Voorbeeld bekijken</button>
        <br />
        
        <!-- <label for="backToOverview"><input type="checkbox" id="backToOverview" checked="checked"/> Terug naar geautoriseerd overzicht na opslaan</label> -->

    </div>


</div>
<textarea id="paramSortString" name="paramSortString" class="hide"></textarea>

{reportExportType}
{projectId}
</form>

</div>

</div>

</div>



<script src='{LP}/js/jquery-sortable.js'></script>

<script>

$(function(){

  

    var isSubmitting = false;



    $(window).on('beforeunload', function() {
        navigator.sendBeacon("{LB}/keyrings/removeOwnLockById/{lock_id}");
    });


 $("#exportSampleCG").hide();

  $('#PDFtypeSelect').on('change', function(){

      if($(this).val() == '0'){
          $('#noSamplesCG').hide();
          $('#exportSampleCG').hide();
          $('#reportTypeCG').hide();
          $('#reportType').val('projectReport');


      } else{
          $('#previewReport').show();
          $('#noSamplesCG').show();
          //auto set to 5
          $('#noSamples').val('5');
          $('#reportTypeCG').show();
      }

  });


    $('#reportType').on('change', function(){

        if($(this).val() == 'projectSample'){
            $('#exportSampleCG').show();
        }else{
            $('#exportSampleCG').hide();
        }
    });

    $('#PDFtypeSelect').trigger('change');
    $('#reportType').trigger('change');

    filterTemplates();

    $('#PDFtypeSelect, #noSamples, #languageSelect').on('change', function(){
        filterTemplates();
    });


  $('#exportType').on('click', '.list-group-item', function(){
      var outputType = $(this).attr('id') + 'Settings';
      $("#settingsWell").find('.settingDiv').addClass('hide');
      $("#" + outputType).removeClass('hide');
      $("#settingsWell").highLight();
 });

  $('#saveReport').on('click', function(){

      if(isSubmitting === true){
          return;
      }

      if ( $('input[name="templateSelect"]:checked').length === 0 ){
        alert('Geen rapport sjabloon geselecteerd.');
        return;
      }

      $('#exportSubmit').attr('target', '_self');
      $('#reportExportType').val('export');


      var sortData = $('.sorterList').sortable("serialize").get();
      var jsonString = JSON.stringify(sortData, null, ' ');
      $('#paramSortString').text(jsonString);

      $(this).addClass('disabled');
      $(this).find("i").removeClass('icon-print').addClass('icon-spinner').addClass('icon-spin');
      isSubmitting = true;
                  

      $('#exportSubmit').submit();      

  });

  $('#exportSubmit').submit(function(e) {
          e.preventDefault();
          this.submit();
          return true;
  });

  $('#previewReport').on('click', function(){

      if ( $('input[name="templateSelect"]:checked').length === 0 ){
        alert('Geen rapport sjabloon geselecteerd.');
        return;
      }

      var sortData = $('.sorterList').sortable("serialize").get();
      var jsonString = JSON.stringify(sortData, null, ' ');
      $('#paramSortString').text(jsonString);

      $('#exportSubmit').attr('target', '_blank');
      $('#reportExportType').val('preview');
      $('#exportSubmit').submit();
  });

  $("ol.sorterList").sortable({  group: 'serialization'});

    // Capture initial preference values so we can detect if the user reverts
    var $userPrefLoaded = $('#userPrefWereLoaded');
    var initialPrefs = null;

    if ($userPrefLoaded.is(':visible')) {
        initialPrefs = {
            PDFtypeSelect: $('#PDFtypeSelect').val(),
            showViolation: $('#showViolation').val(),
            languageSelect: $('#languageSelect').val()
        };
    }

    // Watch for any input changes and swap visibility of preference alerts
    $('#exportSubmit').on('change', 'input, select, textarea', function(){
        if (initialPrefs === null) return;

        var isChanged = (
            $('#PDFtypeSelect').val()   !== initialPrefs.PDFtypeSelect ||
            $('#showViolation').val()   !== initialPrefs.showViolation ||
            $('#languageSelect').val()  !== initialPrefs.languageSelect
        );

        if (isChanged) {
            $userPrefLoaded.hide();
            $('#userPrefWereChanged').removeClass('hidden').show();
        } else {
            $('#userPrefWereChanged').hide();
            $userPrefLoaded.removeClass('hidden').show();
        }
    });

});

function downloadRevision(id){
  revisionPop = window.open('{LB}/exports/downloadRevision/' + id,'revisionPop','height=250,width=250, menubar=no,resizable=no,directories=no,location=no');
  return true;
}

function filterTemplates(){

    var lang = $('#languageSelect').val();
    var pdfType = $('#PDFtypeSelect').val();
    var noSamples = parseInt($('#noSamples').val());
    var needsBulk = (pdfType == '1' && noSamples > 1) ? '1' : '0';

    $('input[name="templateSelect"]').each(function(){
        var $radio = $(this);
        var $wrapper = $radio.closest('div');
        var matchLang = ($radio.data('lang') == lang);
        var matchBulk = ($radio.data('accepts_bulk') == needsBulk || $radio.data('accepts_bulk') == '2');

        if(matchLang && matchBulk){
            $wrapper.show();
        } else {
            $wrapper.hide();
            $radio.prop('checked', false);
        }
    });

    // auto-select first visible if none checked
    if($('input[name="templateSelect"]:checked').length === 0){
        $('input[name="templateSelect"]').each(function(){
            if($(this).closest('div').is(':visible')){
                $(this).prop('checked', true);
                return false;
            }
        });
    }
}

</script>
