
    <tr>
        <td style="vertical-align: middle;"><span class="label {flair}">{status}</span></td>
        <td><h5><a href="{LB}/projects/search/{id}">{reference}</a> - <a href="{LB}/clients/show/{client_id}">{client}</a> <br /> <small> Aangemaakt op: {added}  </small> </h5></td>
        
        <td style="vertical-align: middle;">
            {client_reference}
        </td>
        
        <td style="vertical-align: middle;">
            <span  class="badge badge-info "></span>
            <h3>{samples_in_project}</h3>
            <!--
            <span  class="badge badge-info {normal_sample_show}"><i class="icon icon-beaker"></i></span>
            <span  class="badge badge-info {leg_sample_show}"><i class="icon icon-beer"></i></span> -->

        </td>

        <td style="vertical-align: middle;">
            {sample_flags}
        </td>


        <td class="{progressHide}">
            <div style="margin-top: 20px">
            <div class="progress">
                <div class="bar" id="sampleProgress" style="width: {percent}%;"></div>
            </div>
            </div>
        </td>

        <td style="vertical-align: middle;">
            <p style="{style}"> 
               {expected_date}
            </p>
        </td>

        <td class="{exportHide}" style="vertical-align: middle;">
               {exported_by}
        </td>


        <td style="vertical-align: middle; text-align:right;">
            <a href="{LB}/projects/search/{id}" class="btn btn-small btn-default"><i class="icon icon-suitcase"></i> Openen</a>
            <a href="{LB}/dataMining/export/{id}" class="btn btn-small btn-default {dataDumpHide}"><i class="icon icon-cloud-download"></i></a>
            <a href="{LB}/exports/project/{id}" class="btn btn-small btn-default"><i class="icon icon-print"></i></a></td>
    </tr>
