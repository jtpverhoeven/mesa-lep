    <tr data-project-id="{id}">
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


        <td style="vertical-align: middle;">
          {added}
        </td>

        <td style="vertical-align: middle; text-align: center;">
          <p style="font-size: 36px; font-weight: 300;">{all_samples_have_analysis}</p>
        </td>

        <td style="vertical-align: middle;">
          {last_print_name} {print_times}
        </td>

        <td style="vertical-align: middle; text-align:right;">
            <a href="{LB}/projects/search/{id}" class="btn btn-small btn-default"><i class="icon icon-suitcase"></i> Openen</a>            
            <a onclick="printLabels({id})" class="btn btn-small btn-default {print_button_class}"><i class="icon icon-ticket"></i></a></td>
    </tr>
