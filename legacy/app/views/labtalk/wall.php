 
<div class="row-fluid">            
    
    <div class="span2">
        
        
      
    <div class="media wall-media">
        <a class="pull-left" href="#">
            <div class="avaCrop">
          <img class="media-object" src="{user_avatar}">
            </div>
        </a>
        <div class="media-body">         
        <p class="media-heading">
            
            <a href="{LB}/profiles/view/{user_name}"> {real_name}</a>  <br />
            Als: {user_name}
        </p>                                                            
        </div>                
    </div>

    <p style="font-size: 12px; margin: 2px;"><i class="icon-comments-alt"></i><a href="{LB}/labtalk/wall"> LabTalk</a></p>
    <p style="font-size: 12px; margin: 2px;"><i class="icon-envelope"></i> Prive berichten</p>
       
      
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
    
    <div class="span3">

     <div class="panel panel-default">
            <div class="panel-heading"><strong><i class="icon-bookmark"></i> Projects </strong></div>
            <div class="panel-body">
            </div>
     </div>
        
    </div>
    
</div>

<script>
$(function(){

        $('#placeWallPost').on('click', function(){
            var wallpost = $('#wallPost').val();
             $.ajax({
                 type: "POST",                
                 data: { payload: wallpost },
                 url: "{LB}/labtalkEvents/wallPost/{user_id}/{user_id}"
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
        url: "{LB}/labtalkEvents/renderWall"
    }).done(function(msg) {        
        $('#wallRender').html(msg);
    });    

}

function loadMoreWall(history){
        
    $('#socialLoadMoreButton').remove();
    
    $.ajax({
        type: "POST",                        
        url: "{LB}/labtalkEvents/renderWall/False/" + history
    }).done(function(msg) {        
        $('#wallRender').append(msg)        
    });    
}
    
    
</script>