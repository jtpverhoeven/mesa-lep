<table id="reporttable" class="table table-bordered">
    <thead>
      <tr>
        <th>Project</th>
        <th>Revisie</th>
        <th>Print versie</th>
        <th>Datum</th>
        <th>Type</th>
        <th>Acties</th>
      </tr>
    </thead>

    <tbody>
      <tr>
        <td>{report_reference}</td>
        <td>{revision}</td>
        <td>{print_version}</td>
        <td>{date}</td>
        <td>{type}</td>
        <td>
          <a href="#" onClick="downloadRevision('{id}');" class="btn btn-small btn-default"><i class="icon icon-download"></i> Download</a>
          <a href="{LB}/exports/extern/{id}" class="btn btn-small btn-default"><i class="icon icon-envelope-alt"></i> Emailen</a>      
        </td> 
      </tr>

    </tbody>
  </table>

</table>
