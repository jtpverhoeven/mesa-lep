<div class="container-fluid" style="width: 970px; margin-left: auto; margin-right: auto;"> 
<div class="row-fluid">               
    
    <div class="span3">
        
        <div class="well well-small">
           
            <span id="messageHistorySpan">
            
            <div id="messageHistoryList" class="list-group">
                {message_list}
            </div>    
                
            </span>

        </div>
        
    </div>
    
    <div class="span6">
        <div class="well well-small" >
            
            
            <div id="conversationScrollBox" >                
            </div>
        </div>                        
        </div>
    </div>    
    
</div>

<script>

$(function(){
    $('#messageHistorySpan').slimScroll({
       height: '800px'
    });
    
    $('#conversationScrollBox').slimScroll({
       height: '800px',
       width: '98%'       
    });
});


function loadConversation(chatId){
    
        
     $.ajax({
        type: "POST",    
        url: "{LB}/chats/loadConversation/" + chatId
    }).done(function(response) {           
        $('#conversationScrollBox').html(response);
        var scrollToVal = $('#conversationScrollBox').prop('scrollHeight') + 'px';
        $('#conversationScrollBox').slimScroll({scrollTo: scrollToVal});
    });
}

</script>
