<div class="span3">
    <div class="well">
        <h5><i class="icon-list"></i> {MESA_S2P_LISTS}</h5>
        {lists_form}
        <btn id="exportList" class="btn btn-mini btn-primary"><i class="icon icon-save"></i> Openen</btn>
    </div>
</div>

<div class="span3">
    <div class="well">
        <h5><i class="icon-list"></i> {MESA_S2P_WORKLISTS}</h5>

        {worklist_form}
        
        <btn id="exportWorkList" class="btn btn-mini btn-primary pull-right"><i class="icon icon-save"></i> Openen</btn>
    </div>
</div>

<script>

    $(function(){

        $('#exportList').on('click', function(){
            var list = $('#export_list').val();
            if(list != 'NULL'){
                window.open('{LB}/printing/printList/' + list, '_blank');
            }
        });



        $('#work_list_date').Zebra_DatePicker({
            format: 'd-m-Y',
            zero_pad: true,
            show_icon: false,
            offset: [10, 200],
            readonly_element: false
        });

    });
</script>