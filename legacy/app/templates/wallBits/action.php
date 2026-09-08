<div class="well well-small" style="padding: 0px;">

    <div style="padding: 9px; width: 100%">
    
    <div class="media wall-media">
        
        <a class="pull-left" href="#">
            <div class="avaCrop">
            <img class="media-object" src="{post_avatar}" />
            </div>
        </a>
        <div class="media-body">            
        <p class="media-heading">
            <a href="{LB}/profiles/view/{post_username}" >  {post_realname} </a> {post_action}  <br /> 
            {post_date} <i class="icon-time"></i> {post_time}                        
        </p>                                                            
        </div>                
    </div>
    
        <div id="pad_{post_id}" style="text-align:center">
        {post_content}
        </div>
    
   
    
     </div>
    
    
    <div id="reactionlist_{post_id}"style="padding: 9px;  background-color: #f5f5f5; border-top: 1px solid #ddd;">
    
        {post_replies}
        

        <div id="reactionbox_{post_id}"class="media wall-media">
        <a class="pull-left" href="#">
               <div class="avaCrop">
                <img class="media-object" src="{MESA_USR_AVATAR}" />
               </div>
        </a>
        <div class="media-body">
        <p class="media-heading">
            <textarea id="reaction{post_id}" class="textarea textarea-block-level socialReactionBox" ROWS="1" placeholder="Plaats een reactie"
                      replyTo="{post_id}"></textarea>
        </p>                                                            
        </div>                
        </div>
        
    </div>
    
   
    
</div>