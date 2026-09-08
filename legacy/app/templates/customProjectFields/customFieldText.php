<div class="control-group" id="{name_prefix}_{name}_customproject_cg">

	<div class="controls">
            <div class="input-prepend input-append input-block-level">
                <span style="width: 40%;  text-align: left;" class="add-on">{alias}</span>
                <input type="text" id="{name_prefix}{name}" name="{name_prefix}{name}" value="{std_value}" project_field_name="{name}" class="input-block-level {input_class}"  placeholder="{alias}" data-defaultvalue="{std_value}"  data-keepcurrent="{keep_current}" data-lockedvalue="0" />
                <span class="add-on {hider}"><a  id="{name_prefix}{name}_locker"><i id="{name_prefix}{name}_locked_icon" class="icon-unlock"></i></a></span>
            </div>
	</div>
</div>


<script>
    $(document).ready(function() {

        $('#{name_prefix}{name}_locker').on('click', function(){

            if($('#{name_prefix}{name}').data('lockedvalue') == '1'){
                $('#{name_prefix}{name}').data('lockedvalue', 0);
                $("#{name_prefix}{name}_locked_icon").toggleClass("icon-unlock icon-lock");
                $("#{name_prefix}{name}_locked_icon").toggleClass("icon-unlock icon-lock");

								$('#pj__{name}_customproject_cg').removeClass('error');
								$('#preload__{name}_customproject_cg').removeClass('error');


                if('{name_prefix}' == 'pj_'){
                    $('#preload_{name}').data('lockedvalue', 0);
                    $("#preload_{name}_locked_icon").toggleClass("icon-unlock icon-lock");
                }

                if('{name_prefix}' == 'preload_'){
                    $('#pj_{name}').data('lockedvalue', 0);
                    $("#pj_{name}_locked_icon").toggleClass("icon-unlock icon-lock");
                }

            } else{
                $('#{name_prefix}{name}').data('lockedvalue', 1);
                $("#{name_prefix}{name}_locked_icon").toggleClass("icon-unlock icon-lock");

								$('#pj__{name}_customproject_cg').addClass('error');
								$('#preload__{name}_customproject_cg').addClass('error');


                if('{name_prefix}' == 'pj_'){
                    $('#preload_{name}').val($('#{name_prefix}{name}').val());
                    $("#preload_{name}").data('lockedvalue', 1);
                    $("#preload_{name}_locked_icon").toggleClass("icon-unlock icon-lock");

                }

                if('{name_prefix}' == 'preload_'){
                    $('#pj{name}').val($('#{name_prefix}{name}').val());
                    $("#pj_{name}").data('lockedvalue', 1);
                    $("#pj_{name}_locked_icon").toggleClass("icon-unlock icon-lock");
                }
            }

        });

    });
</script>
