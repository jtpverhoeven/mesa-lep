<div class="span8">   
    <div class="well">      
        
        <auth:clientcats>
        <h3> Klant categorien aanpassen <small>{name} </small> </h3>

        
        {category_table}

        </auth>
     
    </div>
</div>

<div class="span2">
    <ul class="nav nav-list well">                                     
        
        <li class="nav-header">{MESA_CLI_CLIENTS} </li>
        <li><a href="{LB}/clients/show/{id}"><i class="icon-eye-open"></i> {MESA_CLI_BACKTOCLIENT}</a> </li>
        

    </ul>         
</div>



<script>
$(function(){
   
    $('#catSelectTable').DataTable(
        {
            "paging":   false,
            "info":     false,        
        }
    );

    $('.catSelector').click(function(){
        
        if($(this).is(':checked')){
            $.ajax({
                type: "POST",
                url: "{LB}/clients/addCategoryToClient/{id}/" + $(this).val()
            }).done(function(confwindow) {                
            });    
        } else{
            $.ajax({
                type: "POST",
                url: "{LB}/clients/removeCategoryFromClient/{id}/" + $(this).val()
            }).done(function(confwindow) {
            });
        }

    });


});

</script>

