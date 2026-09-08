<div class="span10">
    <div class="row-fluid">

        <div class="well well-small">
            
            <h2><i class="icon-linux"></i> Developer settings </h2>
            

            <h6><i class="icon-beaker"></i> Sample settings </h6>
            {dev_form}

            <h6><i class="icon-calendar"></i> Changetracker for this sample </h6>
            <table id="chg_table" >
                <thead>
                    <tr>
                        <th>id</th>
                        <th>userid</th> 
                        <th>timestamp</th>
                        <th>type</th> 
                        <th>assurance_form</th>
                        <th>project</th> 
                        <th>sample</th>
                        <th>said</th> 
                        <th>event</th> 
                        <th>from</th> 
                        <th>to</th> 
                    </tr>
                </thead>

                <tbody>
                    {chg_form}
                </tbody>

                
            </table>

    </div>

</div> 

<script>

$(function(){

    var sampleid = '{id}';

    $('#devform').on('change', 'input', function(evt){
        
        var r = confirm("Commit this change? This transaction will not be captured by the ChangeTracker");
                
        if (r == true) {

            var fieldname = $(this).attr('name');
            var newvalue = $(this).val();

            $.ajax({
                type: "POST",
                data: { id: sampleid, field: fieldname, value: newvalue},
                url: "{LB}/samples/developer_change"
            });
            
        }
        
    });

    $('#chg_table').on('change', 'input', function(evt){
        
        var r = confirm("Commit this change? This transaction will not be captured by the ChangeTracker");
                
        if (r == true) {
           
            var changeid = $(this).data('id');
            var colname = $(this).data('col');
            var newvalue = $(this).val();

            $.ajax({
                type: "POST",
                data: { id: changeid, colname: colname, value: newvalue},
                url: "{LB}/changeTracker/developer_change"
            });
            
        }
        
    });

});

</script>