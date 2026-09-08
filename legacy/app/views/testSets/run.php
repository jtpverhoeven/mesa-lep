<div class="span8">
    <div class="well">
        <h3> Test-set: {name} </h3>

        {run}
    </div>

    
</div>

<div class="span2">
    <div class="well">
        <p>
            <button id="startBtn" onClick="startTestSet()" class="btn btn-small btn-success">Start</button>
        </p>

        <p style="font-weight: bold;">Test monsters groeperen in 1 project</p>
        <select id="group"><option value="0">Nee</option><option value="1" SELECTED="SELECTED">Ja</option></select>
        <p style="font-weight: bold;">Stickers printen?</p>
        <select id="print"><option value="0">Nee</option><option value="1" SELECTED="SELECTED">Ja</option></select>

    </div>
</div>


<script>

    var tests = {tests};
    var running = false; 
    var firstRun = true;
    var currentTestIdx = 0;
    
    var projectId = 0; 
    var projectGroup = false; 

    function startTestSet()
    {
        $('#startBtn').addClass('disabled');
        
        if(running == false)
        {
            currentTestIdx = 0;            
            running = true; 
            projectGroup = $('#group').val();

            if(projectGroup == '1')
            {
                projectGroup = true; 
            } 

            else
            {
                projectGroup = false; 
            }

            run();
        }
    }

    function run()
    {   

        //if it is the first test, and project name != False, 
        //signal to the testing class that it needs to create a project
        //and return the project id  

        //if it is not the first test, sendt he previously set 

        if(currentTestIdx + 1 > tests.length)     
        {
            //done            
        }

        else
        {
            let testId = tests[currentTestIdx]['id'];
            startTest(testId);        
        }
    
    }

    function startTest(testId)
    {
        console.log('starting test id' + testId);

        $('#status_running_' + testId).removeClass('hidden');
        printval = $('#print').val();

        $.ajax({
            type: "POST",
            dataType: "JSON",        
            data: {         
                'group' : projectGroup, 
                'projectId' : projectId,
                'printbool' : printval
            },
            url: "{LB}/tests/run/" + testId
        }).done(function(testResult) {        
            checkProject(testResult);
            handleEvaluation(testId, testResult);          
        });
    }

    function checkProject(testResult)
    {
        projectId = testResult['projectId'];
    }

    function handleEvaluation(testId, testResult)    
    {

        $('#status_running_' + testId).hide();

        if(testResult['failed'] == true)
        {
            $('#status_fail_' + testId).removeClass('hidden');

            let logRow = "<tr><td colspan='4'>"  + testResult['fullLog'] + "</td></tr>";

            $("#status_fail_" + testId ).closest( "tr" ).after(logRow);
        }

        if(testResult['failed'] == false)
        {
            
            $('#status_pass_' + testId).removeClass('hidden');
        }

        currentTestIdx = currentTestIdx + 1;
        run();
    }

    

</script>
