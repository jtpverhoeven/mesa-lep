<div class="span8">              
    <div class="well">
        


        <div class="tabbable tabs-above">
            <ul class="nav nav-tabs">
               <li class="active"><a href="#inProgress" data-toggle="tab" tabindex="-1" ><i class="icon-time"></i> {MESA_PLU_RUNNING}</a></li>
               <li class=""><a href="#analysisFinished" data-toggle="tab" tabindex="-1" ><i class="icon-check-empty"></i> {MESA_PLU_FINISHED}</a></li>
               <li class=""><a href="#authorised" data-toggle="tab" tabindex="-1" ><i class="icon-check-sign"></i> {MESA_PLU_AUTHORISED} </a></li>
            </ul>
            
            <div class="tab-content">

                <div class="tab-pane active" id="inProgress">      
                    
                    <table style="width: 100%;">
                        
                        <tbody>
                                                     
                             {running}
                        </tbody>
                        
                    </table>
                    
                </div>

                <div class="tab-pane" id="analysisFinished">  
                    
                     <table style="width: 100%;">
                        
                        <tbody>
                                                     
                             {finished}
                        </tbody>
                        
                    </table>
                </div>
                
                <div class="tab-pane " id="authorised">  
                    
                    <span id="authPages" class="width: 100%;">
                        
                        
                        
                       
                    </span>
                    
                    <ul class="pager">                       
                        <li id='newer' class="previous">
                          <a href="#" onClick='loadNewer();'>&larr; Nieuwer </a>
                        </li>
                         <li id='older' class="next">
                          <a href="#" onClick='loadOlder();'>Ouder &rarr;</a>
                        </li>
                      </ul>
                </div>                                           
            </div>
                        
        </div>
        
        
    </div>       
</div>

<div class="span2">              
    <div class="well">       
    </div>       
</div>

<script>

var currentAuthPage = 0;
var maxAuthPage = 0;
var finalPage = false;
var lastLoadId = false;


function fetchAuthProjects(loadFrom, page){
    
    currentAuthPage = page;
    if(currentAuthPage > maxAuthPage){
        maxAuthPage = currentAuthPage;
    }
             
    $.ajax({
        type: "POST",    
        data: {loadFrom: loadFrom},
        dataType: "json",
        url: "{LB}/projects/loadAuthProjects" 
    }).done(function(msg) {
             
        var appending = '<div class="authPage" id="authPage' + page  +'"><table style="width: 100%;"><tbody>' + msg['html'] +'</tbody></table></div>';
        $('#authPages').append(appending);
        lastLoadId = msg['lastId'];
        
        if(msg['lastPage'] == true){
            finalPage = page;
        }
        
        showPage(currentAuthPage);             
    });        
}

function loadOlder(){
    //load from db
    if(maxAuthPage >= currentAuthPage + 1){    
        currentAuthPage = currentAuthPage +1;
        showPage(currentAuthPage);
    } else{          
        fetchAuthProjects(lastLoadId, currentAuthPage + 1);        
    }
    

}

function loadNewer(){       
    currentAuthPage = currentAuthPage -1;
    showPage(currentAuthPage);   
}

function showPage(page){
    $('.authPage').hide();
    $('#authPage' + page).show();
    
    if(currentAuthPage > 0 ){
        $('#newer').show();
    } else{
        $('#newer').hide();
    }
    
    if(currentAuthPage === finalPage){
        $('#older').hide();
    } else{
        $('#older').show();
    }
}


$(function(){   
   $('#newer').hide();
   fetchAuthProjects(false, 0);       
});
    
</script>