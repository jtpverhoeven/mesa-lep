<div style="text-align: center" id="syncing">
    <div class="well" style="margin-left:auto; margin-right: auto;  margin-top: 150px; width: 50%"> 
        <h5><i class="icon-spin icon-spinner"></i> {MESA_SYN_SYNCINGTITLE} </h5>
        {MESA_SYN_SYNCING}         
    </div>
</div>

<div class="hide" style="text-align: center" id="syncError">
    <div class="well" style="margin-left:auto; margin-right: auto;   width: 50%"> 
        <h5><i class="icon-exclamation-sign"></i> {MESA_SYN_ERROR} </h5>
        {MESA_SYN_ERROREXPLAIN} <br /> &nbsp;   
        <a href="{LB}/lims/dashboard" type="button" class="pull-right btn btn-small btn-primary"><i class="icon-ok"></i> {MESA_SYN_PROCEED}</a>
    </div>
</div>

<script>


$(function(){   
    $.ajax({
        type: "POST",              
        dataType: "json",
        url: "{LB}/sync/silentSync"
    }).done(function(msg) {   

        console.log(msg);
        
        if(msg['succes'] == true){
           
           if(msg['cust_issues'] > 0 || msg['subcust_issues'] > 0){
                $('#syncing').hide();
                $('#syncError').show();
           } else{
               window.location.href = '{LB}/lims/dashboard'; 
           }                      
        } else{
                $('#syncing').hide();
                $('#syncError').show();
        }
                
    }).error(function(e){
        $('#syncing').hide();
        $('#syncError').show();
    });   
});

</script>