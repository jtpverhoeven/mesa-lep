<div class="span10">
    <div class="row-fluid">
	   	<div class="well">

        
        	
            <h5> Gegevens in bestand </h5>
            <p> Voor klant: {client_name} </p>

            <div class="alert alert-warning {alert_classes}">
                <p>Kon voor een of meerdere rijen geen passende productgroep vinden voor deze klant.</p>               
            </div>

            <table class="table">
            	<thead>
            		<tr>
            			{column_headers}
            		</tr>
            	</thead>
            	<tbody>
            			{table_data}
            	</tbody>
            </table>

            <h5>Metadata selecteren</h5>
            
            <form method="POST" action="{LB}/sampleBuffers/commitToBuffer">

            <table class="table" style="width: 50%">

            <tbody>
               {mdk_data}
            </tbody>

            </table>

            
                <button href="about:blank" role="submit" class="btn btn-primary">Toevoegen aan voorportaal</button>
                
                <textarea class="hide" id="buffer_data" name="buffer_data">{buffer_data}</textarea>

                <textarea class="hide" id="pg_map_data" name="pg_map_data" ></textarea>


            </form>
          
    	</div>
    </div>
</div>

<script>


    $(document).ready(function() {

        $('.pgdropper').change(function() {
            updatePgMap();
        });

        
        updatePgMap();
       
    });

    function updatePgMap()
    {

        var pgMap = {};
        $('.pgdropper').each(function(index) {
            pgMap[index] = $(this).val();
        });
        $('#pg_map_data').val(JSON.stringify(pgMap));

    }

</script>