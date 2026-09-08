
<div class="control-group" id="{name}_custom_cg">
      
	<div class="controls">
                 <div class="input-prepend input-append input-block-level">
                        <span class="add-on">{alias}</span>
                        <input type="text" id="{name}" name="{name}" value="{std_value}" class="input-block-level custom_input_field"  placeholder="{alias}" {disabled} />
                    </div>           		
	</div>
</div>


<script>

$(document).ready(function() {
    $('#{name}').Zebra_DatePicker({
        format: 'd-m-Y',
        zero_pad: true,
        show_icon: false,
        offset: [10, 200],
        readonly_element: false
              
    });

 });
</script>