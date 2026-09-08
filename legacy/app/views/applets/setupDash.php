<div class="span10">   
    <div class="well">        
        <h4>{MESA_APL_DASHSETUP}</h4>
        <p> {MESA_APL_DASHEXPLAIN} </p>
        
        <div class="media-list" style="margin-top: 40px;">
            {applet_list}
        </div>               
    </div>    
</div>


<script>
$(function(){
   
   $('.appletSelector').on('change', function(){
    
    var applet = $(this).attr('applet');
    var appletSet = $(this).val();
    
    $.ajax({
        type: "POST",   
        data: {applet: applet, appletSet: appletSet},        
        url: "{LB}/applets/toggleApplet/"
    }).done(function(msg) {                                 
    });  
    
   });
   
});

</script>


