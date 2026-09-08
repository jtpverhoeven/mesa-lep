<div class="span8">   
  
    <div class="well">
        
        <h3> {MESA_CLI_FINDCUSTOMER} </h3>
        
        <div class="input-append">
              <input id="searchTerm" type="text">
              <div class="btn-group">
                <button class="btn" tabindex="-1" id="searchButton">{MESA_CLI_FIND}</button>
                <button class="btn dropdown-toggle" data-toggle="dropdown" tabindex="-1">
                  <span class="caret"></span>
                </button>
                <ul id="scopeMenu" class="dropdown-menu">
                   <li scope="all" class="active"><a class="" href="#"><i class="icon-question"></i> {MESA_CLI_SEARCHALL}</a></li>
                   <li scope="name" ><a href="#"><i class="icon-building"></i> {MESA_CLI_SEARCHNAME}</a></li>                  
                   <li scope="place" ><a href="#"><i class="icon-map-marker"></i> {MESA_CLI_SEARCHPLACE}</a></li>       
                </ul>
              </div>
            </div>                             
    </div>
    
    <div class="hide well" id="searchResults">
        <span id="resultSpan"></span>
    </div>
    
</div>


<div class="span2">
    <ul class="nav nav-list well">                                     
            <li class="nav-header">{MESA_CLI_OTHER} </li>            
            <li><a href="{LB}/clients/listing/"><i class="icon-list"></i> {MESA_CLI_BACKTOLIST} </a> </li>      
            <li><a href="{LB}/clients/dashboard/"><i class="icon-backward"></i> {MESA_CLI_BACKTODASH} </a> </li>
    </ul>         
</div>

<script>

var searchScope = 'all';

$(function(){

  $('#searchTerm').focus();
        
   $('#searchTerm').bind('keydown', 'return', function(){
        $('#searchButton').trigger('click');
   });
              
   $('#searchButton').on('click',function(){            
            
            var searchTerm = $('#searchTerm').val();            
        
            $.ajax({
                type: "POST",                                  
                data: { searchTerm: searchTerm, searchScope: window.searchScope},
                url: "{LB}/clients/searchResult/" 
            }).done(function(msg) {                         
                $('#resultSpan').html(msg);
                $('#searchResults').show();                
                $('#searchResults').highLight();                                
            });      
   });
   
   
    $('#scopeMenu').on('click', 'li', function(){   
        $("#scopeMenu").find('li').removeClass('active');
        $(this).addClass('active');                
        window.searchScope = $(this).attr('scope');        
    });
    
});

</script>