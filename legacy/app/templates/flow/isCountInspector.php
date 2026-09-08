<div class="input-prepend input-append input-block-level">
    <span class="add-on">CFU field </span>
    <select id="cfu_count_field" class="input-block-level">
        {fields}
    </select>
</div>

<div class="input-prepend input-append input-block-level">
    <span class="add-on">Dilution field</span>
    <select id="dilution_field" class="input-block-level">
        {fields}
    </select>
</div>

<div class="input-prepend input-append input-block-level">
    <span class="add-on">Max CFU</span>
    <input type="text" value="{value}" class="input input-block-level" id="maxCFU" />
</div>

<button id="saveSettingsISO" class="btn btn-primary">Save</button>

<script>
    $(function() {
      
      $('#saveSettingsISO').on('click', function(){
         
         var component = '{component_id}';
         var cfuField = $('#cfu_count_field').val();
         var dilField = $('#dilution_field').val();
         var maxCFU = $('#maxCFU').val();
         
         $.ajax({
           type: "POST",  
           data: {'component': component, 'cfuField': cfuField, 'dilField': dilField, 'maxCFU': maxCFU},
           url: "{LB}/flowComponents/saveISOcount/" 
        }).done(function(msg) {            
           $('#inspector_gadget').highLight();
        }); 
         
         
      });
      
    });
    
</script>