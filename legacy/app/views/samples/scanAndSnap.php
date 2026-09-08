<div class="span10">

<div id="mainLookupWindow" class="row-fluid">

    <div class="span4">

        <div class="well well-small">
            <h6><i class="icon-barcode"></i> {MESA_SLU_LOOKUPTITLE}
                <span class="pull-right">
                
                </span>
            </h6>
            
            <div class="control-group" id="barCG">
                <input class="input-block-level" type="text" placeholder="{MESA_SLU_SCANINFO}"  value="" id="barcodeEntry" />
            </div>

        </div>

        <div class="well well-small">
            <h6><i class="icon-camera"></i> Selecteer foto
                <span class="pull-right">
                
                </span>
            </h6>

            <label for="picture" class="custom-file-upload">Selecteer Foto</label>
  
            <input class="sas-picture" type="file" id="picture" name="picture" accept="image/*" capture="environment" />

        </div>

        <div class="well well-small" id="previewdiv">
            <h6><i class="icon-camera"></i> Foto
                <span class="pull-right">
                
                </span>
            </h6>

            <img id="preview" src="" alt="Image preview" style="display:none; max-width: 100%; height: auto;" />

            <br /> 

            <button class="btn btn-large btn-primary" onclick="upload()"><i class="icon-upload"></i> Opslaan</button>
        </div>


        
    
    
    
    </div>


    <div class="span4">

        <div id="scannedWell" class="well well-small">
            <h6><i class="icon-search"></i> {MESA_SLU_SCANNEDSAMPLE}</h6>
      

            <div id="sample_block">

            </div>


        

        </div>

    </div> 


    <div class="span4">

        <div id="scannedWell" class="well well-small">
            <h6><i class="icon-search"></i> Project</h6>
      

            <div id="project_block">

            </div>

        </div>

    </div> 

    

</div> 


<script>

var selected_sample = null;
var project_auth = false; 

$(document).ready(function() {

    $('#previewdiv').hide();

    $('#barcodeEntry').focus();
    
    $('#barcodeEntry').keypress(function(e) {
        if(e.which == 13) {
            var barcode = $('#barcodeEntry').val();            
            $('#barcodeEntry').focus();
            scanSample(barcode);
        }
    });


    $('#picture').change(function(event) {

        $('#previewdiv').show();


        var file = event.target.files[0];
        
        if (file) {
            
            var reader = new FileReader();

            reader.onload = function(e) {
                var img = document.getElementById('preview');
                img.src = e.target.result;
                img.style.display = 'block';
            };
            reader.readAsDataURL(file);

            
        }
    });

});

function reset()
{

    $('#barcodeEntry').val('');
    
    $('#sample_block').html('');

    $('#picture').val('');
    $('#preview').attr('src', '');

    selected_sample = null;

    $('#previewdiv').hide();

    $('#barcodeEntry').focus();



}

function scanSample(barcode)
{

    //clear current selected file and hide preview
    $('#previewdiv').hide();
    

    $.ajax({
        type: "POST",
        dataType: 'json',
        data: {previous_sample: undefined},
        url: "{LB}/samples/doLookup/" + barcode
    }).done(function(msg) {

        if(msg['error'] == 'BAR_WRONG'){
         
            selected_barcode = null;
            selected_sample = null;
                              
            $.playSound('{LP}/snd/scanDeny.wav');          
            return;
        }

        $.playSound('{LP}/snd/scan.wav');

        $('#sample_block').html(msg['sample_block']);
        $('#project_block').html(msg['project_block']);

        project_auth = msg['project_auth'];

        selected_sample = msg['selected_sample'];


    });

}

function upload()
{

    if(selected_sample == null)
    {
        alert('Geen sample gescand');
        return;
    }

    //check file selected
    if(document.getElementById('picture').files.length == 0)
    {
        alert('Geen foto geselecteerd');
        return;
    }

    event.preventDefault(); 



    if (project_auth != false)  
    {
        var random_check = Math.floor(Math.random() * (2000 - 1000 + 1) + 1000);

        bootbox.prompt("<h3>Foto toevoegen?</h3> <p>Dit monster is deel van een geautoriseerd project. Deze foto zal direct worden geupload naar de portal </p> <p> Bevestigings code:<span class='label label-warning'>" + random_check + "</span></p>", function(result,) {
            if (result != null) {
                if (result == random_check) {
                    doUpload();
                } else {
                    bootbox.alert("<h3>Error</h3> <p> Niet gewijzigd, bevestigings code correct? </p> ");
                }
            }
        });
    }

    else
    {
        doUpload();
    }




}


function doUpload()
{



    var file = document.getElementById('picture').files[0];
    var formData = new FormData();
    formData.append('file', file);
    formData.append('instantVis', 'true');
    formData.append('photoMode', 'true');
    formData.append('sampleId', selected_sample);
    

    $.ajax({
        url: "{LB}/sampleFiles/storeFile",
        type: 'POST',
        data: formData,
        async: false,
        
        success: function (data) {            
            reset();
        },


        cache: false,
        contentType: false,
        processData: false
    });


}

</script>