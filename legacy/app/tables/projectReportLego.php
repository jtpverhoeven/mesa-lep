        <table class="table table-condensed">         
            <thead>                       
            </thead>
           
            <tbody>
            
            <tr brickId="sampleStart">
                <td rowspan="{rowspan}" style="width:25%;"><i class="icon-barcode"></i>  {sample_barcode} </td>
                <td rowspan="{rowspan}"> {sample_name} </td>
                <td rowspan="{arowspan}"> {flow_name} </td>                
                <td> {result_name}</td>
                <td> <bold> {result_value} </bold></td>
                
            </tr>
            
            <tr brickId="newTest">
                <td rowspan="{rowspan}"> {flow_name} </td>                
                <td> {result_name}</td>
                <td> <bold> {result_value} </bold></td>
            
        </tr>
            
             <tr brickId="resultOnly">                         
                <td> {result_name}</td>
                <td> <bold> {result_value} </bold></td>                
        
        </tr>
            
            
        </tbody>
            
        </table>