<div class="accordion-group">
    <div class="accordion-heading">
        <a class="accordion-toggle" data-toggle="collapse" data-parent="#analysisAccordion" href="#{id}">
            {name} <small>{description}</small> <span class="badge badge-info pull-right">{noOfTests}</span>
        </a>
    </div>
    
    <div id="{id}" class="accordion-body collapse ">
        
        <div class="accordion-inner">
            {testsSection}
        </div>
    </div>
</div>