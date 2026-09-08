<input
    confId="confchain_{chainN}_{disposition}"
    disposition="{disposition}"
    type="text" name="conf_{fieldName}"
    id="conf_{fieldName}"
    class="confirmation-input input-block-level formDate"
    placeholder="{placeHolder}"
    chainN="{chainN}"
    said="{said}"
    dF="{dF}"
    rep="{rep}"
    globalConf="{globalConf}"
    fieldname="{fieldName}"
    value="{default}" {disabled}
    mediaid="{mediaId}"
    data-out-of-date-here="{out_of_date_here}"/>

{explanation_button}


<script>

    $(function(){
        $('#conf_{fieldName}').Zebra_DatePicker({
            format: 'd-m-Y',
            zero_pad: true,
            show_icon: false,
            offset: [10, 200],
            readonly_element: false,
            onSelect: function(a, b,c,d) {
                $(d).trigger('change');

                //if($(d).attr('disposition') == 'inzet' ){
                //    updateTHTdates($(d).attr('said'), $(d).val(), $(d).attr('chainn'),  $(d).attr('mediaid'));
                //}

                //if($(d).attr('disposition') == 'tht' ){
                //    alert('changing dat frome zebra pick function ');
                //    updateAssuranceForm($(d).attr('said'), $(d).val(), $(d).attr('chainn'),  $(d).attr('mediaid'));
                //}
            }, onClear(a){
                $(a).trigger('change');
            }
        });
    });

</script>
