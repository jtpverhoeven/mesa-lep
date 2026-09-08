<div class="span10">

<div class="well">
    
  <form method="POST" id="dataminingForm" action="{LB}/dataMining/deauthExport" target="_blank">
    <h4>Project deauthorisatie gebeurtenissen uitvoeren  </h4>
    <table class="table table-bordered">
        <thead>
            <tr>
            <th colspan="2">Scope van export</th>
            </tr>
        </thead>
     
        <tbody>
      
            <tr>
                <td>Datum start</td>
                <td>  <input class="input-small" type="text" id="startDate" name="startDate" value="01-01-2016" name="date_offset" tabindex="-1" /></td>
            </tr>

            <tr>
                <td>Datum einde</td>
                <td>  <input class="input-small" type="text" id="endDate" name="endDate" value="{today}" name="date_offset" tabindex="-1" /></td>
            </tr>


            <tr>
                <td>Teken voor CSV cell-delineatie</td>
                <td><input id="seperator" name="seperator" type="text" class="input-block-level" placeholder="" value=";"></td>
            </tr>

            <tr>
                <td>Uitvoeren</td>
                <td><button id="exportButton" type="submit" class="btn btn-success"><i class="icon-save"></i> Exporteren </button></td>
            </tr>

        </tbody>
    </table>
    
</div>

</div>

<script>

$(function(){

 

    $('#startDate').Zebra_DatePicker({
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

    $('#endDate').Zebra_DatePicker({
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

});


</script>