 
<div class="row-fluid">            
    
    <div class="span12">
       <div class="well">      
           
        <table width="100%">
            <tbody>
                <tr>
                    <td style="padding-right: 10px; width: 180px;"><img src="http://localhost/alpaca/public/img/avatar_company.jpg" class="avatar-170 img-polaroid pull-left"></td>
                    <td style="vertical-align: top;"> 
                        
                        <div> <h3> Project {project_name}  <small> {client_name} </small></h3>  </div>                        
                        <i class="icon-calendar"></i> Gestart op: {start_date}<br /> <i class="icon-pencil"></i> Laatst bewerkt op: {edit_date}<br />                        
                        
                        
                        
                        <div class="pull-right" style="text-align: right;"> 
                            <table>
                                <tr>
                                    <td style="padding-right: 25px;"><h3 style="margin-bottom: 0px;">{revision} <i class="icon-tag"></i> <br />  <small> Revisies </small> <br/> </h3> </td>
                                    <td style="padding-right: 25px;"><h3 style="margin-bottom: 0px;">{number_of_samples} <i class="icon-beaker"></i> <br />  <small> Monsters </small> <br/> </h3> </td>
                                    <td style="padding-right: 25px;"><h3 style="margin-bottom: 0px;">{number_of_followers} <i class="icon-bookmark"></i> <br />  <small> Volgers </small> <br/> </h3> </td>
                                </tr>
                            </table>
                        </div>
                    
                    </td>                   
                </tr>
            </tbody>
            
        </table>                               
       </div>
    </div>
    
</div>

<div class="row-fluid">    

    <div class="span12">
        <div class="well">
            {timeline}
        </div>
    </div>
</div>


<div class="row-fluid">    
    
    <div class="span2">
        <p style="font-size: 12px; margin: 2px;"><i class="icon-suitcase" style="margin-right: 5px;"></i><a href="{LB}/projects/search/{id}">Project openen</a></p>
        <p style="font-size: 12px; margin: 2px;"><i class="icon-building" style="margin-right: 5px;"></i><a href="{LB}/clients/show/{client}">Naar klant</a></p>
        <p style="font-size: 12px; margin: 2px;"><i class="icon-time" style="margin-right: 5px;"></i><a href="{LB}/projects/overview">Project overzicht</a></p>
    </div>
    
    <div class="span6">
        <div class="well well-small">                                    
            <textarea id="wallPost" class="textarea-block-level wallPostBox" placeholder="{wall_message}" rows="1" ></textarea>           
            <p style="margin-bottom: 22px; margin-top: 4px;">
                <button id="placeWallPost" class="btn btn-primary btn-mini pull-right"><i class="icon-comment"></i> {MESA_PRO_PLACEPOST}</button>
            </p>                        
        </div>
        
        <div class="" id="wallRender">   
            
        </div>
    </div>
    
    <div class="span4">
         <div class="panel panel-default">
            <div class="panel-heading"><strong><i class="icon-user-md"></i> Betrokken gebruikers </strong></div>
            <div class="panel-body">
               {contributors}                
            </div>
        </div>
        
        <div class="panel panel-default">
            <div class="panel-heading"><strong><i class="icon-beaker"></i> Monsters </strong></div>
            <div class="panel-body">
                {attached_samples}
            </div>
        </div>
        
        
    </div>
    
</div>

<script>
    
    $(function(){
        
        $('.contributor').tooltip({
            delay: { show: 500, hide: 100 },
            placement: 'bottom'
        });

        $('#placeWallPost').on('click', function(){
            var wallpost = $('#wallPost').val();
            $.ajax({
                 type: "POST",                
                 data: { payload: wallpost },
                 url: "{LB}/labtalkEvents/projectPost/{id}"
            }).done(function(msg) {        
                $('#wallPost').val('');
                $('#wallPost').attr('rows', '1');       
                reloadWall();
            });    
        });
                                     
    reloadWall();      
});
    
function reloadWall(){

    $.ajax({
        type: "POST",                        
        url: "{LB}/labtalkEvents/renderProjectWall/{id}"
    }).done(function(msg) {        
        $('#wallRender').html(msg);
    });    

}

function loadMoreWall(history){
        
    $('#socialLoadMoreButton').remove();
    
    $.ajax({
        type: "POST",                        
        url: "{LB}/labtalkEvents/renderProjectWall/{id}/" + history
    }).done(function(msg) {        
        $('#wallRender').append(msg)        
    });    
}
    
   
</script>