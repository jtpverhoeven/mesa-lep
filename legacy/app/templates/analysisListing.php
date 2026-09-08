<a href="#" id="" class="list-group-item" barcode="{barcode}" sampleAnalysis="{saId}">

    <!-- onClick='readAnalysis("{barcode}")' -->
    
    <h6 class="list-group-item-heading">{name}</h6>
    <span class="badge" id="{saId}_badge" onClick="authoriseAnalysis('{saId}', '{auth_dir}', '{barcode}')"><i id="icon_{saId}"class="{anaIcon} star-yellow" ></i></span>

    <p class="list-group-item-text">                           
          <i class="icon-barcode"></i> {barcode_for_render}                                      
    </p>

</a>