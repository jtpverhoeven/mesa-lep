<div class="input-prepend input-append input-block-level">
    <span class="add-on">{alias}</span>
    <input type='text' class='input-block-level'  inputfilter="{input_filter}" id='{name}_{db_id}' name='{name}' value='{value}' placeholder='{alias}' dbrid="{db_id}" onChange="resultHandler('{name}_{db_id}');" {disabled} />
    <span id="{db_id}_conf_addon" class="add-on {show_confirmation_button}">
      <a class="btn btn-mini btn-primary" onClick="confirmation('{said}', '{dF}', '{rep}', '{globalConf}');"><i class="icon-eye-open"></i> Bevestiging</a>
      <!-- <a href="#" onClick="confirmation('{said}', '{dF}', '{rep}', '{globalConf}');"><i class="icon-eye-open"></i></a> -->
    </span>
</div>

<script>

$("#{name}_{db_id}").keypress( function(e) {

  if($(this).attr('inputfilter') == 1){
    //alert('Enkel [0-9] invoer toegestaan');
    if(e.which == 42 )
    {
      e.preventDefault()
      $(this).val('>');
      $(this).trigger('change');
      return;
    }
    return "1234567890,<>".indexOf(String.fromCharCode(e.which)) >= 0;
  }

  if($(this).attr('inputfilter') == 2){
    //alert('Enkel [A-Z] en [0-9] toegestaan als invoer');
    return "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789".indexOf(String.fromCharCode(e.which)) >= 0;
  }

  if($(this).attr('inputfilter') == 3){
    //alert('Enkel [A-Z] toegetaan');
    return "ABCDEFGHIJKLMNOPQRSTUVWXYZ".indexOf(String.fromCharCode(e.which)) >= 0;
  }

  if($(this).attr('inputfilter') == 4){
    //alert('Enkel [+] of [-] toegestaan als invoer');
    return "+-".indexOf(String.fromCharCode(e.which)) >= 0;
  }


});

</script>
