{roam_form_header}
<div id="roamingDillutionForm" class="tabbable">
    <h6><i class="icon-list-alt"></i> Verdunningen </h6>

    <div class="tab-content tabs-below">

        <div class="tab-pane active" id="roamFormDillutionsGUI">

            <table style="width: 100%">

                <tr>
                    <td><label for="roamFormDillution0"><input type="checkbox" class="roamDillutionCheck" id="roamFormDillution0" dilTxt="0" dilVal="1" > Onverdund</label></td>
                    <td><label for="roamFormDillution6"><input type="checkbox" class="roamDillutionCheck" id="roamFormDillution6" dilTxt="-6" dilVal="0.000001" > -6</label></td>
                </tr>

                <tr>
                    <td><label for="roamFormDillution1"><input type="checkbox" class="roamDillutionCheck" id="roamFormDillution1" dilTxt="-1" dilVal="0.1" > -1</label></td>
                    <td><label for="roamFormDillution7"><input type="checkbox" class="roamDillutionCheck" id="roamFormDillution7" dilTxt="-7" dilVal="0.0000001" > -7</label></td>
                </tr>

                <tr>
                    <td><label for="roamFormDillution2"><input type="checkbox" class="roamDillutionCheck" id="roamFormDillution2" dilTxt="-2" dilVal="0.01" > -2</label></td>
                    <td><label for="roamFormDillution8"><input type="checkbox" class="roamDillutionCheck" id="roamFormDillution8" dilTxt="-8" dilVal="0.00000001" > -8</label></td>
                </tr>

                <tr>
                  <td><label for="roamFormDillution3"><input type="checkbox" class="roamDillutionCheck" id="roamFormDillution3" dilTxt="-3" dilVal="0.001" > -3</label></td>
                  <td><label for="roamFormDillution9"><input type="checkbox" class="roamDillutionCheck" id="roamFormDillution9" dilTxt="-9"     dilVal="0.000000001" > -9</label></td>

                </tr>

                <tr>
                  <td><label for="roamFormDillution4"><input type="checkbox" class="roamDillutionCheck" id="roamFormDillution4" dilTxt="-4" dilVal="0.0001" > -4</label></td>
                  <td><label for="roamFormDillution10"><input type="checkbox" class="roamDillutionCheck" id="roamFormDillution10"  dilTxt="-10" dilVal="0.0000000001" s> -10</label></td>
                </tr>

                <tr>
                  <td><label for="roamFormDillution5"><input type="checkbox" class="roamDillutionCheck" id="roamFormDillution5" dilTxt="-5" dilVal="0.00001" > -5</label></td>
                  <td></td>
                </tr>


            </table>

        </div>

        <div class="tab-pane" id="roamFormDillutionsTXT">
            <textarea id="roam_dillution" class="input-block-level" rows="4">{roam_dillution}</textarea>
        </div>


        <ul class="nav nav-tabs">
            <li class="active"><a href="#roamFormDillutionsGUI" data-toggle="tab" tabindex="-1"><i class="icon-eye-open"></i></a></li>
            <li class=""><a href="#roamFormDillutionsTXT" data-toggle="tab" tabindex="-1"><i class="icon-code"></i></a></li>
        </ul>
</div>
</div>

<div id="roamingRefForm">
<h6><i class="icon-star"></i> Referentie waarden </h6>

{refvalue_fields}
{reference_source}

</div>

<div id="roamingReplicatesForm">
<h6><i class="icon-copy"></i> Replicas</h6>
{roam_replicates}
</div>

{roam_form_script}

</form>



<script>

    $(document).ready(function(){


        $('.roamDillutionCheck').on('click', function(){

            $('#roam_dillution').val('');

            $('#roamFormDillutionsGUI input[type=checkbox]').each(function () {
                if(this.checked){
                    var dilTxt = $(this).attr('dilTxt');
                    var dilVal = $(this).attr('dilVal');
                    var currTxtContents = $('#roam_dillution').val();
                    var addedTxtContent = dilTxt + '=' + dilVal;
                    var newTxtContents = currTxtContents +  addedTxtContent + '\n';
                    $('#roam_dillution').val(newTxtContents);
                }
            });
        });

        $('#roam_dillution').on('blur', function(){

            $('#roamFormDillutionsGUI input[type=checkbox]').prop("checked", false);

            var lines = $('#roam_dillution').val().split('\n');
            for(var i = 0;i < lines.length;i++){

                if(lines[i].trim().length == 0){
                    continue;
                }

                var dillutionLine = lines[i].split('=');
                var dillutionLineLength = dillutionLine.length;
                if(dillutionLineLength == 2 && !isNaN(dillutionLine[1])){
                    $('#roamFormDillutionsGUI input[type=checkbox]').each(function () {
                        var thisDilVal = $(this).attr('dilVal');
                        if( thisDilVal== dillutionLine[1]){
                            $(this).prop("checked", true);
                        }
                    });
                } else{
                    alert('Geen geldige verdunnings curve!');
                    $('#roam_dillution').val('');
                    $('#roam_dillution').focus();
                }
            }
        });

        $('#roam_dillution').blur();

        
    });

</script>
