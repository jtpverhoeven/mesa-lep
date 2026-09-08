{portalwarn}

<div class="span10">         
    <div class="row-fluid hide" id="dashboardArea">

        <div id="dashCol1" class="span4 sortable-list">            	           
            {col1}
        </div>

        <div id="dashCol2"  class="span4 sortable-list">
            {col2}
        </div>

        <div id="dashCol3"  class="span4 sortable-list">
            {col3}
        </div>

    </div>
    
    <p style="text-align: right">
        <a href="#" id="setupDash" class="btn btn-mini btn-default"><i class="icon-move"></i> Aanpassen</a> 
        <a href="#" id="setupComplete" class="btn btn-mini btn-success hide"><i class="icon-ok"></i> Ok </a> 
        <a href="{LB}/applets/setupDash" id="goAppletSetup" class="btn btn-mini btn-default"><i class="icon-wrench"></i> Personaliseren </a>
    </p>
        
    
</div> 



<script>

    $(function(){
       
       loadItemsFromCookie('mesaDashboard'); 
       
       $('#setupDash').on('click', function(){
           startSetup();
       });
       
       $('#setupComplete').on('click', function(){
           stopSetup();
       });
              
    });


    function getItems(container){
        var columns = [];

        $(container + ' .sortable-list').each(function() {          
            columns.push($(this).sortable('toArray').join(','));
        });

        return columns.join('|');
    }

    function loadItemsFromCookie(name){
        if ($.cookie(name) != null)
        {
            renderItems($.cookie(name), 'dashboardArea');
        }

        showDash();

    }
    
    function showDash(){
        $('#dashboardArea').show();        
    }
    
    function startSetup(){
     
     $('#setupDash').hide();
     $('#goAppletSetup').hide();
     $('#setupComplete').show();
          
      $('.sortable-list').sortable({
		connectWith: '.sortable-list',
		placeholder: 'placeholder',                
                update: function(){
                    $.cookie('mesaDashboard', getItems('#dashboardArea'), {path: '/'});
                    $.ajax({
                        type: "POST",                       
                        url: "{LB}/profiles/saveDashCookie"
                    });                                                            
                }
	});
     
    }
    
    function stopSetup(){
        $('#setupDash').show();
        $('#goAppletSetup').show();
        $('#setupComplete').hide();        
        $('.sortable-list').sortable("destroy");
        
        //update current cookie to profile
        
        
        
    }

    function renderItems(items, container){
        var html = '';
        var columns = items.split('|');

        for (var c in columns)
        {
            html += '<div id="dashCol' + c + '" class="span4 sortable-list">';

            if (columns[c] != ''){
                var items = columns[c].split(',');
                for (var i in items)
                {                
                    html += $('#' + items[i])[0].outerHTML;
                }
            }
            html += '</div>';
        }

        $('#' + container).html(html);
        
        
        
    }
</script>

{appletJs}