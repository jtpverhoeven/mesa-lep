<div class="span10">
    <div class="well">

    <table style="width: 100%" id="">
        <tbody>
            <tr>
                <td style="width: 75%"></td>
                <td><i class="icon-calendar"></i>  Print stikkers voor inzetdag:</td>
                <td><input class="input-small" type="text" id="aformDate" value="{today}" name="date_offset" tabindex="-1" /></td>
            </tr>
            <tr>
                <td colspan="3">&nbsp;</td>              
            </tr>
        </tbody>
    </table> 




    <table class="table table-condensed " id="">
            <thead>
            <tr>
                <th>Status</th>
                <th>Project</th>
                <th>Klant referentie</th>                
                <th>Aantal monsters <br />in project</th>
                <th>Type <br /> monster(s)</th>
                <th>Aangemeld <br /> op </th>
                <th>Analyses <br /> gekoppeld </th>
                <th>Geprint <br /> door</th>
                <th style="text-align: right;">Acties</th>
            </tr>
            </thead>
            <tbody id="projectTableBody">

            </tbody>
        </table>

    </div>
</div>

<script>

    var lastLoaded = false;
    var initialLoad = false; 

    $(function(){

        $(window).scroll(function() {

        var limit = Math.max( document.body.scrollHeight, document.body.offsetHeight, document.documentElement.clientHeight, document.documentElement.scrollHeight, document.documentElement.offsetHeight) - window.innerHeight; 

        if(Math.ceil(window.pageYOffset) >= limit && initialLoad) {                
            loadList();
        }

        });


        $('#aformDate').Zebra_DatePicker({
            format: 'd-m-Y',
            zero_pad: true,
            show_icon: false,
            offset: [10, 200],
            readonly_element: false,
            onSelect: function() {
                changeDate(this.id);
            },
           onChange: function(view, elements) {

           }
        });

        loadList();
    });

    function loadList(){
        var loadFrom = $('#projectTableBody tr').length;
        $.ajax({
            type: "POST",
            dataType: "json",
            url: "{LB}/projects/overviewLoader/" + loadFrom + "/1/0/0/1"
        }).done(function(msg) {
            lastLoaded = msg['last_id'];
            initialLoad = true; 
            $('#projectTableBody').append(msg['table_html']);
        });
    }

    function printLabels(projectId){

        var offsetdate = $('#aformDate').val();        

        $.ajax({
            type: "GET",
            dataType: "json",
            url: "{LB}/projects/writePrintInfo/" + projectId
        }).done(function(msg) {
            var $row = $('tr[data-project-id="' + projectId + '"]');
            $row.find('td').eq(7).text(msg.last_print_name + ' (' + msg.print_times + ')');
        });        
                    
        $.ajax({
            type: "POST",                
            data: { 
                barcode: '',
                range_type_select: 'p_project_monster',
                range_start: '',
                range_stop: '',
                bar_range_start: '' ,
                bar_range_stop: '',
                print_normal_samples: 1,
                date_offset: offsetdate,
                pprofile_1: 1,
                pprofile_4: 4,
                also_print_amount: 1,
                also_print_selection: 7,
                print_sample_amount: 1,
                assay_type_select: 0,
                sample_label_select: 'default',
                assay_label_select: 'default',
                printer: 0,
                legbar_range_matrix: 'A',
                legbar_range_start:'' ,
                legar_range_end: '',
                leg_printer: 10,
                {pprofiles}
                project_id : projectId },
            url: "{LB}/printing/runRangeS2P"
        }).done(function(msg) {

        });
    }

</script>
