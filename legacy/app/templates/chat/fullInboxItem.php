 <a href="#" id="chatbox_preview_{chatbox_title}" class="list-group-item" onClick="loadConversation('{chatbox_title}');">               
     <div class="avaCrop pull-left" style="padding-right: 6px; margin-top: 5px; margin-bottom: 10px;">
         <img src="{avatar_url}" />      
     </div>
     
    <h6 class="list-group-item-heading" style="margin-bottom: 0px; padding-bottom: 0px;">{chatbox_name}</h6>    
    <p class="list-group-item-text" style="font-size: 10px;">                           
        <i id="fullInbox_replyindicator_{chatbox_title}" class="icon-reply {icon}"></i>
        {trimmed_msg} <span class="pull-right" style="color: #bfbfbf;">{sent_at}</span>
        
    </p>
</a>