<div class="row-fluid">            
    
    <div class="span3">
      
        
        <center>
            {avatar} <br />
            <h5> {first_name} {last_name} </h5>
        </center>
        
        
        <div class="panel panel-default">
            <div class="panel-heading"><strong><i class="icon-info"></i> Info</strong></div>
            <div class="panel-body">
              <p class="panel-p"><i class="icon-suitcase icon-push-right"></i> {function} </p>
              <p class="panel-p"><i class="icon-envelope icon-push-right"></i> {email} </p>
              <p class="panel-p"><i class="icon-phone icon-push-right"></i> {phone} </p>
              <p class="panel-p"><i class="{status_icon} icon-push-right"></i> {status} </p>
            </div>
      </div>
        
       
    </div>
    
    <div class="span5">
        <div class="well well-small">            
            
            
            <textarea id="wallPost" class="textarea-block-level wallPostBox" rows="1" placeholder="{wall_message}" ></textarea>           
            <p style="margin-bottom: 22px; margin-top: 4px;">
                <button id="placeWallPost" class="btn btn-primary btn-mini pull-right"><i class="icon-comment"></i> {MESA_PRO_PLACEPOST}</button>
            </p>                        
        </div>
        
        <div id="wallRender">
            
            
        </div>
        
        
                        
        
        
    </div>
    
    <div class="span3">

        <div class="panel panel-default">
            <div class="panel-heading"><strong><i class="icon-bookmark"></i> {MESA_PRO_FOLLOWING}</strong></div>
            <div class="panel-body">
                {following}
            </div>
        </div>
        
        
        <div class="panel panel-default">
            <div class="panel-heading"><strong><i class="icon-group"></i> {MESA_PRO_PARTOFGROUPS}</strong></div>
            <div class="panel-body">                
                {user_groups}                
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
            url: "{LB}/labtalkEvents/wallPost/{userId}/{profileId}"
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
        url: "{LB}/labtalkEvents/renderWall/{profileId}"
    }).done(function(msg) {        
        $('#wallRender').html(msg);
    });    

}
         
function loadMoreWall(history){
        
    $('#socialLoadMoreButton').remove();
    
    $.ajax({
        type: "POST",                        
        url: "{LB}/labtalkEvents/renderWall/{profileId}/" + history
    }).done(function(msg) {        
        $('#wallRender').append(msg)        
    });    
}
    
</script>
