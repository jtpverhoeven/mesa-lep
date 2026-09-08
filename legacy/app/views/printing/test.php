<div class="span8">
    <div class="well">
        <h5><i class="icon-list"></i> Test print profiel</h5>
        <div class="alert">       
        <strong>Functie nog niet compleet</strong> Deze functie is bedoeld om te zien wat een script profiel doet, zonder dat men stickers moet printen. Echter, functionaliteit is nog niet compleet en test momenteel enkel op de nieuwe HASANYWITHCONFIRMATION 
        </div>
                
        
        <div class="control-group" id="samplenCG">
            <div class="controls">
                <div class="input-prepend input-append input-block-level">
                    <span class="add-on">Barcode start</span>
                    <input id="bar_range_start" name="bar_range_start" type="text" class="input-block-level" placeholder="" value="1">
                </div>
            </div>
        </div>

        <div class="control-group" id="samplenCG">
            <div class="controls">
                <div class="input-prepend input-append input-block-level">
                    <span class="add-on">Barcode stop</span>
                    <input id="bar_range_stop" name="bar_range_stop" type="text" class="input-block-level" placeholder="" value="1">
                </div>
            </div>
        </div>

        <div class="control-group" id="samplenCG">
            <div class="controls">
                <button type="button" id="runTest" class="btn btn-primary">Run</button>
            </div>
        </div>

        <div class="control-group" >
            <div class="controls">
                <textarea id="output" class="input-block-level"></textarea>
            </div>
        </div>

        
    </div>
</div>

<div class="span2">
<ul class="nav nav-list well">
        <li class="nav-header">Opties </li>       
        <li><a href="{LB}/printing/profiles">Terug naar lijst </a> </li>

    </ul>
</div>

<script>

    $(function(){

            
        $('#runTest').on('click', function(){

            var bar_range_start = $('#bar_range_start').val();
            var bar_range_stop =  $('#bar_range_stop').val();
            var profile_id = '{id}';


            $.ajax(
            {
                type: "POST",                                
                data: { bar_range_start: bar_range_start, bar_range_stop: bar_range_stop, profile_id: profile_id},
                url: "{LB}/printing/runTest"
            }).done(function(response) 
            {
                $('#output').html(response);            
            });
        });


    });
</script>