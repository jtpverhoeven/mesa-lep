

<div class="span10">

<div id="sampleLockDiv" class="alert alert-block {hideLock} ">
    <img id="usageAvatar" class='pull-left img-polaroid avatar-32' style='margin-right: 15px;' src='{locked_by_avatar}' />
    <h4>{MESA_PLU_PROJECTLOCKED_TITLE} </h4>
    {MESA_PLU_PROJECTLOCKED_DESCRIPTION} <strong><span id="usageName">{locked_by_name}</span></strong> en kan niet worden geëxporteerd.
  </div>

    <div class="row-fluid {hideExport}">

        <form id="exportSubmit" action="{LB}/exports/doTempExport" method="post">
        <div class="span4">
            <div class="well well-small">
                <h6><i class="icon-suitcase"></i> {MESA_EXP_TITLE} </h6>

                <table class="table-condensed">
                    <tbody>
                    <tr>
                        <td width="125px"><strong>{MESA_EXP_PNAME}</strong></td>
                        <td><a href="{LB}/projects/search/{project_id}">{project_name}</a></td>
                    </tr>
                    <tr>
                        <td width="125px"><strong>{MESA_EXP_PCLIENT}</strong></td>
                        <td>{client}</td>
                    </tr>


                    </tbody>
                </table>

            </div>

             <div class="well well-small">
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

                    <div class="control-group" id="PDFtypeSelectCG">
                        <label class="control-label" for="PDFtypeSelect">PDF uitvoer</label>
                        <div class="controls">
                            <select id="PDFtypeSelect" name="PDFtypeSelect" class="input-block-level">
                                <option value="1" selected="SELECTED">Verzamel rapport</option>
                                <!-- <option value="0">1 PDF per monster (ZIP uitvoer)</option> -->
                            </select>
                        </div>
                    </div>

                    {reportType}
                    {noSamples}
                    {exportSample}



                </div>

            </div>

        </div>

        <div class="span4">


            <div id="parametersWell" class="well well-small">
                <h6><i class="icon-list"></i> Parameters op rapport </h6>

                <div class="settingDiv" id="parametersDiv">
                      <p><span style="color: red; font-weight: bold;">Let op! </span><br />
                      U genereert hier een voorlopig rapport! <br />
                      Na het uitvoeren van een voorlopig rapport, wordt de autorisatie van het volledige project terug ingetrokken en de status aangepast naar ''Lopend'' of ''Afgerond''
                      <br /> <br />
                      In deze modus kan enkel een volledig rapport worden uitgevoerd, selectie van parameters is momenteel niet beschikbaar.

                      </p>

                      {paramList}
                </div>

            </div>

            <div class="well well-small" id="sampleNoteWell">
              <h6><i class="icon-pencil"></i> Monster notities</h6>
              {sample_notes}
            </div>

        </div>
        <div class="span4">

            <div  class="well well-small">

                <h6><i class="icon-list-alt"></i> Overige instellingen </h6>

                {showViolation}


                <div class="control-group" id="templateSelectCG">
                    <label class="control-label" for="templateSelect">Rapport stijl</label>
                    <div class="controls">
                        <select size="10" id="templateSelect" name="templateSelect" class="input-block-level">
                            {templates_available}
                        </select>
                    </div>
                </div>





            </div>

            <div class="well well-small">

                <h6><i class="icon-print"></i> Uitvoeren </h6>

                <div class="alert {show_report_notes}"> 
                    {report_notes_for_client}   
                </div>

                <button id="saveReport" type="button" class="btn btn-primary btn-block"> Rapport maken</button> <br />
                <button id="previewReport"  type="button" class="btn btn-default btn-block"><i class="icon icon-globe"></i> Voorbeeld</button>
                <br />

                <!--
                <label for="backToOverview"><input type="checkbox" id="backToOverview" checked="checked"/> Terug naar afgerond overzicht na opslaan</label>
                -->

            </div>


        </div>

    </div>
{reportExportType}
{projectId}
</form>


<script>

$(function(){

    var isSubmitting = false;

    $(window).on('beforeunload', function() {
        navigator.sendBeacon("{LB}/keyrings/removeOwnLockById/{lock_id}");
    });

   $("#exportSampleCG").hide();

    $('#PDFtypeSelect').on('change', function(){

        if($(this).val() == '0'){
            $('#previewReport').hide();
            $('#noSamplesCG').hide();
            $('#exportSampleCG').hide();
            $('#reportTypeCG').hide();
            $('#reportType').val('projectReport');


        } else{
            $('#previewReport').show();
            $('#noSamplesCG').show();
            //$('#exportSampleCG').show();
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

    $('#exportType').on('click', '.list-group-item', function(){
        var outputType = $(this).attr('id') + 'Settings';
        $("#settingsWell").find('.settingDiv').addClass('hide');
        $("#" + outputType).removeClass('hide');
        $("#settingsWell").highLight();
   });

    $('#saveReport').on('click', function(){

        if ( $('#templateSelect option:selected').val() == undefined){
          alert('Geen rapport sjabloon geselecteerd.');
          return;
        }

        if(isSubmitting === true){
          return;
        }

        //$('#exportSubmit').attr('target', '_blank');
        $('#reportExportType').val('export');

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

      if ( $('#templateSelect option:selected').val() == undefined){
        alert('Geen rapport sjabloon geselecteerd.');
        return;
      }

        $('#exportSubmit').attr('target', '_blank');
        $('#reportExportType').val('preview');
        $('#exportSubmit').submit();
    });



});

</script>
